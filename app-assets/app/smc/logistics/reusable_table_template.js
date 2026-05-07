const EventBus = new Vue();
window.eventBusMixin = {
  mounted() {
    if (this.gotoPageHandler && typeof this.gotoPageHandler === "function") {
      this.boundGotoPageHandler = this.gotoPageHandler.bind(this);
      EventBus.$on("g-event-goto-page", this.boundGotoPageHandler);
    }

    if (
      this.refreshDataHandler &&
      typeof this.refreshDataHandler === "function"
    ) {
      this.boundRefreshDataHandler = this.refreshDataHandler.bind(this);
      EventBus.$on("g-event-refresh-page", this.boundRefreshDataHandler);
    }
  },
  beforeDestroy() {
    if (this.boundGotoPageHandler) {
      EventBus.$off("g-event-goto-page", this.boundGotoPageHandler);
    }
    if (this.boundRefreshDataHandler) {
      EventBus.$off("g-event-refresh-page", this.boundRefreshDataHandler);
    }
  },
};

const createPageState = () => ({
  page: "table",
  title: "",
});
// Centralized reactive state
const appState = Vue.observable({
  pageState: createPageState(),
  permission: getPermission(per, "smc"),
});

Vue.mixin({
  methods: {
    displayDate(d) {
      let date = new Date(d);
      let options = {
        year: "numeric",
        month: "long",
        day: "numeric",
      };
      return date.toLocaleString("en-us", options);
    },
    capitalize(word) {
      if (word) {
        const loweredCase = word.toLowerCase();
        return word[0].toUpperCase() + loweredCase.slice(1);
      } else {
        return word;
      }
    },
    capitalizeEachWords(text) {
      if (!text) return text;
      return text
        .toLowerCase()
        .split(" ")
        .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
        .join(" ");
    },
    formatNumber(num) {
      let data = num ? parseInt(num) : 0;
      return data ? data.toLocaleString() : 0;
    },
  },
});

Vue.component("page-table", {
  mixins: [eventBusMixin],
  data: function () {
    return {
      appState,
      url: common.BadgeService,
      tableData: [],
      defaultStateId: "",
      roleListData: [],
      geoData: [],
      checkToggle: false,
      filterState: false,
      filters: false,
      tableOptions: {
        total: 1, //Total record
        pageLength: 1, //Total
        perPage: 10,
        currentPage: 1,
        orderDir: "asc", // (asc|desc)
        orderField: 0, //(Order fields)
        limitStart: 0, //(currentPage - 1) * perPage
        isNext: false,
        isPrev: false,
        aLength: [10, 20, 50, 100, 150, 200],
        filterParam: {
          user_status: "",
          loginid: "",
          fullname: "",
          user_group: "",
          phoneno: "",
          geo_level: "",
          geo_level_id: "",
          geo_string: "",
          bank_status: "",
          role_id: "",
          role: "",
        },
      },
      geoIndicator: {
        state: 50,
        currentLevelId: 0,
        lga: "",
        cluster: "",
        ward: "",
      },
      geoLevelData: [],
      sysDefaultData: [],
      lgaLevelData: [],
      clusterLevelData: [],
      wardLevelData: [],
      dpLevelData: [],
      userPass: {
        pass: "",
        loginid: "",
        name: "",
        isBulk: false,
      },
    };
  },
  mounted() {
    /*  Manages events Listening    */

    this.getGeoLocation();
    var select = $(".select2");
    let self = this;
    select.each(function () {
      var $this = $(this);
      $this.wrap('<div class="position-relative"></div>');
      $this
        .select2({
          // the following code is used to disable x-scrollbar when click in select input and
          // take 100% width in responsive also
          dropdownAutoWidth: true,
          width: "100%",
          dropdownParent: $this.parent(),
        })
        .on("change", function () {
          self.setLocation(this.value);
        });
    });
    $(".select2-selection__arrow").html(
      '<i class="feather icon-chevron-down"></i>'
    );

    this.loadTableData();
    this.getGeoLevel();
    EventBus.$on("g-event-update-user", this.reloadUserListOnUpdate);
  },
  methods: {
    autocomplete(inp, arr) {
      var self = this;
      /*the autocomplete function takes two arguments,
            the text field element and an array of possible autocompleted values:*/
      var currentFocus;
      /*execute a function when someone writes in the text field:*/
      inp.addEventListener("input", function (e) {
        var a,
          b,
          i,
          val = this.value;
        /*close any already open lists of autocompleted values*/
        closeAllLists();
        if (!val) {
          return false;
        }
        currentFocus = -1;
        /*create a DIV element that will contain the items (values):*/
        a = document.createElement("DIV");
        a.setAttribute("id", this.id + "autocomplete-list");
        a.setAttribute("class", "autocomplete-items");
        /*append the DIV element as a child of the autocomplete container:*/
        this.parentNode.appendChild(a);
        /*for each item in the array...*/
        for (i = 0; i < arr.length; i++) {
          /*check if the item starts with the same letters as the text field value:*/
          if (arr[i].substr(0, val.length).toUpperCase() == val.toUpperCase()) {
            /*create a DIV element for each matching element:*/
            b = document.createElement("DIV");
            /*make the matching letters bold:*/
            b.innerHTML =
              "<strong>" + arr[i].substr(0, val.length) + "</strong>";
            b.innerHTML += arr[i].substr(val.length);
            /*insert a input field that will hold the current array item's value:*/
            b.innerHTML += "<input type='hidden' value='" + arr[i] + "'>";
            /*execute a function when someone clicks on the item value (DIV element):*/
            b.addEventListener("click", function (e) {
              /*insert the value for the autocomplete text field:*/
              inp.value = this.getElementsByTagName("input")[0].value;
              /*close the list of autocompleted values,
                            (or any other open lists of autocompleted values:*/
              closeAllLists();
            });
            a.appendChild(b);
          }
        }
      });
      /*execute a function presses a key on the keyboard:*/
      inp.addEventListener("keydown", function (e) {
        var x = document.getElementById(this.id + "autocomplete-list");
        if (x) x = x.getElementsByTagName("div");
        if (e.keyCode == 40) {
          /*If the arrow DOWN key is pressed,
                    increase the currentFocus variable:*/
          currentFocus++;
          /*and and make the current item more visible:*/
          addActive(x);
        } else if (e.keyCode == 38) {
          //up
          /*If the arrow UP key is pressed,
                    decrease the currentFocus variable:*/
          currentFocus--;
          /*and and make the current item more visible:*/
          addActive(x);
        } else if (e.keyCode == 13) {
          /*If the ENTER key is pressed, prevent the form from being submitted,*/
          e.preventDefault();
          if (currentFocus > -1) {
            /*and simulate a click on the "active" item:*/
            if (x) x[currentFocus].click();
          }
        }
      });

      function addActive(x) {
        /*a function to classify an item as "active":*/
        if (!x) return false;
        /*start by removing the "active" class on all items:*/
        removeActive(x);
        if (currentFocus >= x.length) currentFocus = 0;
        if (currentFocus < 0) currentFocus = x.length - 1;
        /*add class "autocomplete-active":*/
        x[currentFocus].classList.add("autocomplete-active");
      }

      function removeActive(x) {
        /*a function to remove the "active" class from all autocomplete items:*/
        for (var i = 0; i < x.length; i++) {
          x[i].classList.remove("autocomplete-active");
        }
      }

      function closeAllLists(elmnt) {
        /*close all autocomplete lists in the document,
                except the one passed as an argument:*/
        var x = document.getElementsByClassName("autocomplete-items");
        for (var i = 0; i < x.length; i++) {
          if (elmnt != x[i] && elmnt != inp) {
            x[i].parentNode.removeChild(x[i]);
            self.tableOptions.filterParam.user_group = inp.value;
          }
        }
      }
      /*execute a function when someone clicks in the document:*/
      document.addEventListener("click", function (e) {
        closeAllLists(e.target);
      });
    },
    reloadUserListOnUpdate(data) {
      this.paginationDefault();
      this.loadTableData();
    },
    loadTableData() {
      /*  Manages the loading of table data */
      var self = this;
      var url = common.TableService;
      overlay.show();

      axios
        .get(
          url +
            "?qid=001&draw=" +
            self.tableOptions.currentPage +
            "&order_column=" +
            self.tableOptions.orderField +
            "&length=" +
            self.tableOptions.perPage +
            "&start=" +
            self.tableOptions.limitStart +
            "&order_dir=" +
            self.tableOptions.orderDir +
            "&ac=" +
            self.tableOptions.filterParam.user_status +
            "&lo=" +
            self.tableOptions.filterParam.loginid +
            "&na=" +
            self.tableOptions.filterParam.fullname +
            "&gr=" +
            self.tableOptions.filterParam.user_group +
            "&ph=" +
            self.tableOptions.filterParam.phoneno +
            "&gl=" +
            self.tableOptions.filterParam.geo_level +
            "&gl_id=" +
            self.tableOptions.filterParam.geo_level_id +
            "&bv=" +
            self.tableOptions.filterParam.bank_status +
            "&ri=" +
            self.tableOptions.filterParam.role_id
        )
        .then(function (response) {
          self.tableData = response.data.data; //All Data
          self.tableOptions.total = response.data.recordsTotal; //Total Records
          if (self.tableOptions.currentPage == 1) {
            self.paginationDefault();
          }
          overlay.hide();
        })
        .catch(function (error) {
          overlay.hide();
          alert.Error("ERROR", error);
        });
    },
    selectAll() {
      /*  Manages all check box selection checked */
      if (this.tableData.length > 0) {
        for (let i = 0; i < this.tableData.length; i++) {
          this.tableData[i].pick = true;
        }
      }
    },
    uncheckAll() {
      /*  Manages unchecking of all check box checked */
      if (this.tableData.length > 0) {
        for (let i = 0; i < this.tableData.length; i++) {
          this.tableData[i].pick = false;
        }
      }
    },
    selectToggle() {
      /*  Manages all check box checking and unchecking  */
      if (this.checkToggle == false) {
        this.selectAll();
        this.checkToggle = true;
      } else {
        this.uncheckAll();
        this.checkToggle = false;
      }
    },
    checkedBg(pickOne) {
      /*  Manages the checking of a checkbox */
      return pickOne != "" ? "bg-select" : "";
    },
    toggleFilter() {
      /*  Manages the toggling of a filter box */
      if (this.filterState === false) {
        this.filters = false;
      }
      return (this.filterState = !this.filterState);
    },
    selectedItems() {
      /*  Manages the selections of checkedor selected data object */
      let selectedItems = [];
      if (this.tableData.length > 0) {
        for (let i = 0; i < this.tableData.length; i++) {
          if (this.tableData[i].pick) {
            selectedItems.push(this.tableData[i]);
          }
        }
      }
      return selectedItems;
    },
    selectedID() {
      /*  Manages the selections of checkedor selected data object */
      let selectedIds = [];
      if (this.tableData.length > 0) {
        for (let i = 0; i < this.tableData.length; i++) {
          if (this.tableData[i].pick) {
            selectedIds.push(this.tableData[i].userid);
          }
        }
      }
      return selectedIds;
    },
    nextPage() {
      this.resetSelected();
      /*  Manages the selections of checked or selected data object */
      this.tableOptions.currentPage += 1;
      this.paginationDefault();
      this.loadTableData();
    },
    prevPage() {
      this.resetSelected();
      /*  Manages the selections of checked or selected data object */
      this.tableOptions.currentPage -= 1;
      this.paginationDefault();
      this.loadTableData();
    },
    resetSelected() {
      this.uncheckAll();
      this.checkToggle = false;
      this.totalCheckedBox();
    },
    currentPage() {
      this.paginationDefault();
      if (this.tableOptions.currentPage < 1) {
        alert.Error("ERROR", "The Page requested doesn't exist");
      } else if (this.tableOptions.currentPage > this.tableOptions.pageLength) {
        alert.Error("ERROR", "The Page requested doesn't exist");
      } else {
        this.loadTableData();
      }
    },
    paginationDefault() {
      //  total page
      this.tableOptions.pageLength = Math.ceil(
        this.tableOptions.total / this.tableOptions.perPage
      );

      // Page Limit
      this.tableOptions.limitStart = Math.ceil(
        (this.tableOptions.currentPage - 1) * this.tableOptions.perPage
      );

      //  Next
      if (
        this.tableOptions.currentPage < this.tableOptions.pageLength &&
        this.tableOptions.currentPage != this.tableOptions.pageLength
      ) {
        this.tableOptions.isNext = true;
      } else {
        this.tableOptions.isNext = false;
      }

      // Previous
      if (this.tableOptions.currentPage > 1) {
        this.tableOptions.isPrev = true;
      } else {
        this.tableOptions.isPrev = false;
      }
    },
    changePerPage(val) {
      /*
      let maxPerPage = Math.ceil(this.tableOptions.total / val);
      if (maxPerPage < this.tableOptions.currentPage) {
        this.tableOptions.currentPage = maxPerPage;
      }
      */
      this.resetSelected();
      this.tableOptions.currentPage = 1;
      this.tableOptions.perPage = val;
      this.paginationDefault();
      this.loadTableData();
    },
    sort(col) {
      if (this.tableOptions.orderField === col) {
        this.tableOptions.orderDir =
          this.tableOptions.orderDir === "asc" ? "desc" : "asc";
      } else {
        this.tableOptions.orderField = col;
      }

      this.paginationDefault();
      this.loadTableData();
    },
    applyFilter() {
      // Check if any filter fields are filled
      let checkFill = 0;
      checkFill += this.tableOptions.filterParam.user_status != "" ? 1 : 0;
      checkFill += this.tableOptions.filterParam.loginid != "" ? 1 : 0;
      checkFill += this.tableOptions.filterParam.fullname != "" ? 1 : 0;
      checkFill += this.tableOptions.filterParam.user_group != "" ? 1 : 0;
      checkFill += this.tableOptions.filterParam.phoneno != "" ? 1 : 0;
      checkFill += this.tableOptions.filterParam.geo_level != "" ? 1 : 0;
      checkFill += this.tableOptions.filterParam.bank_status != "" ? 1 : 0;
      checkFill += this.tableOptions.filterParam.role_id != "" ? 1 : 0;

      if (checkFill > 0) {
        this.toggleFilter();
        this.filters = true;
        this.paginationDefault();
        this.loadTableData();
      } else {
        alert.Error("ERROR", "Invalid required data");
        return;
      }
    },
    removeSingleFilter(column_name) {
      // this.tableOptions.filterParam + '.' + column_name == "";
      this.tableOptions.filterParam[column_name] = "";
      if (column_name == "geo_string") {
        this.tableOptions.filterParam.geo_level =
          this.tableOptions.filterParam.geo_level_id =
          this.tableOptions.filterParam.geo_string =
            "";
      }
      if (column_name == "role") {
        this.tableOptions.filterParam.role_id =
          this.tableOptions.filterParam.role = "";
      }
      let g = 0;
      for (let i in this.tableOptions.filterParam) {
        if (this.tableOptions.filterParam[i] != "") {
          g++;
        }
      }
      if (g == 0) {
        this.filters = false;
      }
      this.paginationDefault();
      this.loadTableData();
    },
    clearAllFilter() {
      this.filters = false;
      $(".select2").val("").trigger("change");
      this.tableOptions.filterParam.user_status =
        this.tableOptions.filterParam.loginid =
        this.tableOptions.filterParam.fullname =
        this.tableOptions.filterParam.user_group =
        this.tableOptions.filterParam.phoneno =
        this.tableOptions.filterParam.geo_level =
        this.tableOptions.filterParam.geo_level_id =
        this.tableOptions.filterParam.geo_string =
        this.tableOptions.filterParam.bank_status =
        this.tableOptions.filterParam.role_id =
        this.tableOptions.filterParam.role =
          "";

      this.paginationDefault();
      this.loadTableData();
    },
    capitalize(word) {
      if (word) {
        const loweredCase = word.toLowerCase();
        return word[0].toUpperCase() + loweredCase.slice(1);
      } else {
        return word;
      }
    },
    goToDetail(userid, user_status) {
      EventBus.$emit("g-event-goto-page", {
        userid: userid,
        page: "detail",
        user_status: user_status,
        role: this.roleListData,
      });
    },
    getRoleList() {
      /*  Get User Details using userid */
      var self = this;
      var url = common.DataService;
      overlay.show();

      axios
        .get(url + "?qid=007")
        .then(function (response) {
          self.roleListData = response.data.data; //All Data
          overlay.hide();
        })
        .catch(function (error) {
          overlay.hide();
          alert.Error("ERROR", error);
        });
    },
    getGeoLocation() {
      /*  Manages the loading of Geo Level data */
      var self = this;
      var url = common.DataService;
      overlay.show();

      axios
        .get(url + "?qid=gen009")
        .then(function (response) {
          self.geoData = response.data.data; //All Data
          // self.tableOptions.total = response.data.recordsTotal; //Total Records
          overlay.hide();
        })
        .catch(function (error) {
          overlay.hide();
          alert.Error("ERROR", error);
        });
    },
    setLocation(select_index) {
      let i = select_index ? select_index : 0;
      this.tableOptions.filterParam.geo_level = this.geoData[i].geo_level;
      this.tableOptions.filterParam.geo_level_id = this.geoData[i].geo_level_id;
      this.tableOptions.filterParam.geo_string = this.geoData[i].geo_string;
    },
    refreshData() {
      this.paginationDefault();
      this.loadTableData();
    },
    downloadBadge(userid) {
      overlay.show();
      window.open(this.url + "?qid=002&e=" + userid, "_parent");
      overlay.hide();
    },
    downloadBadges() {
      // : href = "url+'?qid=003&e='+selectedID()"
      overlay.show();
      // console.log(this.selectedID());

      if (parseInt(this.selectedID().length) > 0) {
        window.open(this.url + "?qid=003&e=" + this.selectedID(), "_parent");
      } else {
        alert.Error("Badge Download Failed", "No user selected");
      }
      overlay.hide();
    },
    getGeoLevel() {
      /*  Manages the loading of Geo Level data */
      var self = this;
      var url = common.DataService;
      overlay.show();

      axios
        .get(url + "?qid=gen001")
        .then(function (response) {
          self.geoLevelData = response.data.data; //All Data
          // self.tableOptions.total = response.data.recordsTotal; //Total Records
          overlay.hide();
        })
        .catch(function (error) {
          overlay.hide();
          alert.Error("ERROR", error);
        });
    },
    totalCheckedBox() {
      let total = this.selectedID().length;
      if (this.selectedID().length > 0) {
        document.getElementById(
          "total-selected"
        ).innerHTML = `<span id="selected-counter" class="badge badge-primary btn-icon"><span class="badge badge-success">${total}</span> Selected</span>`;
      } else {
        document.getElementById("total-selected").replaceChildren();
      }
    },
    async exportUserData() {
      var self = this;
      var common_url =
        "&draw=" +
        self.tableOptions.currentPage +
        "&order_column=" +
        self.tableOptions.orderField +
        "&length=" +
        self.tableOptions.perPage +
        "&start=" +
        self.tableOptions.limitStart +
        "&order_dir=" +
        self.tableOptions.orderDir +
        "&ac=" +
        self.tableOptions.filterParam.user_status +
        "&lo=" +
        self.tableOptions.filterParam.loginid +
        "&na=" +
        self.tableOptions.filterParam.fullname +
        "&gr=" +
        self.tableOptions.filterParam.user_group +
        "&ph=" +
        self.tableOptions.filterParam.phoneno +
        "&gl=" +
        self.tableOptions.filterParam.geo_level +
        "&gl_id=" +
        self.tableOptions.filterParam.geo_level_id +
        "&bv=" +
        self.tableOptions.filterParam.bank_status +
        "&ri=" +
        self.tableOptions.filterParam.role_id;

      var veriUrl = "qid=014" + common_url;
      var dlString = "qid=001" + common_url;

      var filename =
        (self.tableOptions.filterParam.geo_string
          ? self.tableOptions.filterParam.geo_string
          : "Recent ") +
        " " +
        (self.tableOptions.filterParam.loginid
          ? self.tableOptions.filterParam.loginid
          : "Recent ") +
        " User List";
      overlay.show();

      //  count export data
      let count = new Promise((resolve, reject) => {
        $.ajax({
          url: common.DataService,
          type: "POST",
          data: veriUrl,
          dataType: "json",
          success: function (data) {
            resolve(data.total);
          },
        });
      });
      let result = await count; //  wait till the promise resolves (*)
      var downloadMax = common.ExportDownloadLimit;

      if (parseInt(result) > downloadMax) {
        //  stop download
        alert.Error(
          "Download Error",
          "Unable to download data because it has exceeded download limit, download limit is " +
            downloadMax
        );
      } else if (parseInt(result) == 0) {
        alert.Error("Download Error", "No data found");
      } else {
        alert.Info("DOWNLOADING...", "Downloading " + result + " record(s)");
        //  Else continue download data
        var options = {
          fileName: filename,
        };

        let dl = new Promise((resolve, reject) => {
          $.ajax({
            url: common.ExportService,
            type: "POST",
            data: dlString,
            success: function (data) {
              resolve(data);
            },
          });
        });

        let outcome = await dl; //  Wait till downloaded
        var exportData = JSON.parse(outcome);
        Jhxlsx.export(exportData, options);
      }
      this.resetSelected();

      overlay.hide();
    },
    checkIfAndReturnEmpty(data) {
      if (data === null || data === "") {
        return "";
      } else {
        return data;
      }
    },
    numbersOnlyWithoutDot(evt) {
      evt = evt ? evt : window.event;
      var charCode = evt.which ? evt.which : evt.keyCode;
      if (charCode > 31 && (charCode < 48 || charCode > 57)) {
        evt.preventDefault();
      } else {
        return true;
      }
    },
  },
  computed: {},
  template: `

        <div class="row" id="basic-table">
            <div class="col-md-8 col-sm-12 col-12 mb-0">
                <h2 class="content-header-title header-txt float-left mb-0">SMC</h2>
                <div class="breadcrumb-wrapper">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../smc/logistics">Logistics</a></li>
                        <li class="breadcrumb-item active">Issue </li>
                    </ol>
                    <span id="total-selected"></span>
                </div>
                
            </div>

            <div class="col-md-4 col-sm-12 col-12 text-md-right text-right d-md-block">
                <div class="btn-group">
                    <button type="button" class="btn btn-outline-primary round searchBtn" @click="refreshData()" data-toggle="tooltip" data-placement="top" title="Refresh">
                        <i class="feather icon-refresh-cw"></i>         
                    </button>       
                    <button type="button" class="btn btn-outline-primary round searchBtn" @click="toggleFilter()" data-toggle="tooltip" data-placement="top" title="Filter">
                        <i class="feather" :class="filterState ? 'icon-x' : 'icon-filter'"></i>               
                    </button>
                    <button v-if="appState.permission.permission_value >=2" type="button"
                        class="btn btn-outline-primary round" data-toggle="tooltip" data-placement="top" title="Create Issue" @click="exportIcc()">
                        <i class="feather icon-plus"></i>             
                    </button>
                </div>
            </div>
            <div class="col-12" v-if="filters">
                <div class="col-12 filter-bar">
                    <span class="badge badge-dark filter-box" @click="removeSingleFilter(i)" v-for="(filterParam, i) in tableOptions.filterParam" v-if="filterParam.length > 0 && i != 'geo_level' && i != 'geo_level_id'&& i != 'role_id'">{{capitalize(i)}}: {{filterParam}} <i class="feather icon-x"></i></span>
                    <a href="#" class="float-right clear-filter" @click="clearAllFilter()">Clear</a>
                </div>
            </div>

            <div class="col-12 mt-1">
                <div class="card  custom-select-down">
                    <div class="card-body py-1" v-show="filterState">
                        <form id="filterForm">
                            <div class="row">
                                <div class="col-12 col-sm-12 col-md-12 col-lg-12">
                                    <div class="form-group">
                                        <label>Geo Location</label>
                                        <select class="select2 form-control" @change="setLocation()" placeholder="Geo Location">
                                            <option value="">Select Geo Location</option>
                                            <option v-for="(g, i) in geoData" :value="i">{{ g.geo_string }}</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                                    <div class="form-group">
                                        <label>Active Status</label>
                                        <select name="active" v-model="tableOptions.filterParam.user_status" class="form-control active">
                                            <option value="">All Users</option>
                                            <option value="active">Active Users</option>
                                            <option value="inactive">Inactive Users</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                                    <div class="form-group">
                                        <label>Login ID</label>
                                        <input type="text" v-model="tableOptions.filterParam.loginid" class="form-control login-id" id="login-id" placeholder="Login ID" name="loginid" />
                                    </div>
                                </div>
                                <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                                    <div class="form-group">
                                        <label>Fullname</label>
                                        <input type="text" id="fullname" v-model="tableOptions.filterParam.fullname" class="form-control fullname" placeholder="Fullname" name="fullname" />
                                    </div>
                                </div>

                                <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                                    <div class="form-group">
                                        <label>Phone Number</label>
                                        <input type="text" id="phoneno" v-model="tableOptions.filterParam.phoneno" class="form-control phoneno" placeholder="Phone Number" name="phoneno" />
                                    </div>
                                </div>
                                <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                                    <div class="form-group">
                                        <label>Bank Verification Status</label>
                                        <select name="bank_status" v-model="tableOptions.filterParam.bank_status" class="form-control active">
                                            <option value="">All</option>
                                            <option value="success">Successfull Verification</option>
                                            <option value="none">Pending Verification</option>
                                            <option value="failed">Failed Verification</option>
                                            <option value="warning">Invalid Account Name</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                                    <div class="form-group">
                                        <label>Role</label>
                                        <select name="role" v-model="tableOptions.filterParam.role_id"  @change="setRole($event)" class="form-control role">
                                            <option value="" selected="selected">All</option>
                                            <option v-for="r in roleListData" :value="r.roleid" >{{r.role}}</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-12 col-sm-4 col-md-3 col-lg-3">
                                    <div class="form-group mt-2 text-right">
                                        <button type="button" class="btn btn-md btn-primary"  @click="applyFilter()">Apply Filters</button>
                                    </div>
                                </div>
                                
                            </div>
                        </form>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th width="60px">

                                        <div class="custom-control custom-checkbox checkbox">
                                            <input type="checkbox" class="custom-control-input" :checked="checkToggle" @change="selectToggle(), totalCheckedBox()" id="all-check" />
                                            <label class="custom-control-label" for="all-check"></label>
                                        </div>
                                    </th>
                                    <th @click="sort(0)" class="pl-0">
                                        Login ID
                                        <i class="feather icon-chevron-up sort-up" :class="(tableOptions.orderField == 0 && tableOptions.orderDir =='asc')? 'active-sort': ''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 0 && tableOptions.orderDir =='desc')? 'active-sort': ''"></i>
                                    </th>
                                    <th @click="sort(8)" class="pl-1">
                                        Fullname
                                        <i class="feather icon-chevron-up sort-up" :class="(tableOptions.orderField == 8 && tableOptions.orderDir =='asc')? 'active-sort': ''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 8 && tableOptions.orderDir =='desc')? 'active-sort': ''"></i>
                                    </th>
                                    <th @click="sort(5)" class="pl-1">
                                        Role
                                        <i class="feather icon-chevron-up sort-up" :class="(tableOptions.orderField == 5 && tableOptions.orderDir =='asc')? 'active-sort': ''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 5 && tableOptions.orderDir =='desc')? 'active-sort': ''"></i>
                                    </th>
                                    <th @click="sort(16)" class="pl-1">
                                        Geo String
                                        <i class="feather icon-chevron-up sort-up" :class="(tableOptions.orderField == 16 && tableOptions.orderDir =='asc')? 'active-sort': ''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 16 && tableOptions.orderDir =='desc')? 'active-sort': ''"></i>
                                    </th>
                                    <th @click="sort(12)" class="pl-1">
                                        Status
                                        <i class="feather icon-chevron-up sort-up" :class="(tableOptions.orderField == 12 && tableOptions.orderDir =='asc')? 'active-sort': ''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 12 && tableOptions.orderDir =='desc')? 'active-sort': ''"></i>
                                    </th>
                                    <th class="pl-1 pr-1"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="g in tableData" :class="checkedBg(g.pick)">
                                    <td>
                                        <div class="custom-control custom-checkbox checkbox">
                                            <input type="checkbox" class="custom-control-input" :id="g.loginid" v-model="g.pick" @change="totalCheckedBox()" />
                                            <label class="custom-control-label" :for="g.loginid"></label>
                                        </div>
                                    </td>
                                    <td class="pl-0">
                                      {{g.loginid}}
                                    </td>
                                    <td class="pl-1">{{g.first}} {{g.middle}} {{g.last}}</td>
                                    <td class="pl-1">                                  
                                        <div class="d-flex justify-content-left align-items-center">
                                            <div class="d-flex flex-column">
                                                <span class="user_name text-truncate text-body">
                                                    <span class="fw-bolder text-primary"  v-html="g.role? g.role: 'Role Not Assigned'"></span>
                                                </span>
                                                <small class="emp_post text-muted" v-html="g.user_group? g.user_group: ''"></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="pl-1">
                                        <div class="d-flex justify-content-left align-items-center">
                                            <div class="d-flex flex-column">
                                                <small class="emp_post text-primary" v-html="g.geo_level? g.geo_level.toUpperCase(): 'Geo Not Assigned'"></small>
                                                <small class="emp_post text-muted" v-html="g.geo_string? capitalize(g.geo_string): ''"></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="pl-1"><span class="badge rounded-pill font-small-1" :class="g.active==1? 'bg-success' : 'bg-danger'">{{g.active==1? 'Active' : 'Inactive'}}</span>  </td>
                                    <td class="pl-1 pr-1">
                                        <div class="dropdown">
                                            <button type="button" class="btn btn-sm dropdown-toggle hide-arrow" data-toggle="dropdown">
                                                <i class="feather icon-more-vertical"></i>
                                            </button>
                                            <div class="dropdown-menu">
                                                <a class="dropdown-item" href="javascript:void(0);" @click="goToDetail(g.userid, g.active)">
                                                    <i class="feather icon-eye mr-50"></i>
                                                    <span>Details</span>
                                                </a>
                                                <a v-if="appState.permission.permission_value >=2" class="dropdown-item" href="javascript:void(0);" data-toggle="modal" data-target="#geoLevelModal" data-backdrop="static" data-keyboard="false">
                                                    <i class="feather icon-user mr-50"></i>
                                                    <span>Change Geo Level</span>
                                                </a>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="tableData.length == 0"><td class="text-center pt-2" colspan="7"><small>No User Added</small></td></tr>
                            </tbody>
                        </table>

                    </div>

                    <div class="card-footer">
                        <div class="content-fluid">
                            <div class="row">
                                <div class="col-12 col-xl-4 col-md-4 col-sm-5">
                                    <div class="dropdown sort-dropdown mb-1 mb-sm-0">
                                        <button class="btn filter-btn btn-primary dropdown-toggle border text-dark" type="button" id="tablePaginationDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                            {{tableOptions.limitStart+1}} - {{tableOptions.limitStart+tableData.length}} of {{tableOptions.total}} 
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-right" aria-labelledby="tablePaginationDropdown">
                                            <a @click="changePerPage(g)" v-for="g in tableOptions.aLength" class="dropdown-item" href="javascript:void(0);">{{g}}</a>
                                        </div>
                                    </div>                            
                                </div>

                                <div class="col-12 col-xl-8 col-md-8 col-sm-7 text-right text-pag">
                                    
                                    <div class="btn-group">
                                        <button type="button" @click="prevPage()" class="btn btn-sm btn-primary round btn-page-block-overlay" :disabled="tableOptions.isPrev? false: true">
                                            <i data-feather='chevron-left'></i> Prev
                                        </button>
                                        
                                        <input @keyup.13="currentPage()" class="btn btn-page-block-overlay btn-sm btn-outline-primary pagination-input" type="number" v-model.number="tableOptions.currentPage" :max="tableOptions.pageLength" />
                                        
                                        <button class="btn btn-outline-primary btn-page-block-overlay border-l-0">
                                            <small class="form-text text-primary">  of {{this.tableOptions.pageLength}} </small>
                                        </button>
                                        
                                        <button type="button" @click="nextPage()" class="btn btn-sm btn-primary round"  :disabled="tableOptions.isNext? false: true">
                                            Next <i data-feather='chevron-right'></i>
                                        </button>
                                        
                                    </div>

                                </div>
                            </div>
                        </div>

                    </div>

                    <div class="mb-50"></div>
                </div>
            </div>

           

        </div>
    `,
});

var vm = new Vue({
  mixins: [eventBusMixin],
  el: "#app",
  data: function () {
    return {
      appState,
    };
  },

  methods: {},
  template: `
        <div>
            <div v-show="appState.pageState.page == 'table'">
                <page-table/>
            </div>
            <div v-show="appState.pageState.page == 'create-issues'">
                <h1>Others</h1>
            </div>
        </div>
      `,
});
