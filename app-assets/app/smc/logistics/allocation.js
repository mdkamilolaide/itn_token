const EventBus = new Vue();

window.eventBusMixin = {
  mounted() {
    // Handle reset form event
    if (typeof this.resetPageHandler === "function") {
      this.boundResetPageHandler = this.resetPageHandler.bind(this);
      EventBus.$on("g-event-reset-form", this.boundResetPageHandler);
    }

    // Handle refresh data event
    if (typeof this.refreshDataHandler === "function") {
      this.boundRefreshDataHandler = this.refreshDataHandler.bind(this);
      EventBus.$on("g-event-refresh-page", this.boundRefreshDataHandler);
    }
  },

  beforeDestroy() {
    if (this.boundResetPageHandler) {
      EventBus.$off("g-event-reset-form", this.boundResetPageHandler);
    }
    if (this.boundRefreshDataHandler) {
      EventBus.$off("g-event-refresh-page", this.boundRefreshDataHandler);
    }
  },
};

const createPageState = () => ({
  // page: "create-issues",
  page: "table",
  title: "",
});
// Centralized reactive state
const appState = Vue.observable({
  pageState: createPageState(),
  permission: getPermission(per, "smc"),
  userId: (currentUserId = document.getElementById("v_g_id").value),
  geoLevelForm: {
    geoLevel: "",
    geoLevelId: 0,
  },
  defaultStateId: "",
  sysDefaultData: [],
  productData: [],
  lgaData: [],

  periodData: [],
  currentPeriodId: "",
  currentLgaId: "",
  selectedLgaKey: "",
  facilityTitles: "",
  level: "lga",
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
    numbersOnlyWithoutDot(event) {
      const allowedKeys = [
        "Backspace",
        "Delete",
        "ArrowLeft",
        "ArrowRight",
        "Tab",
        "Escape",
        "Home",
        "End",
      ];

      // Allow control keys
      if (allowedKeys.includes(event.key)) return;

      // Block if not a digit (0–9)
      if (!/^\d$/.test(event.key)) {
        event.preventDefault();
      }
    },
    validatePaste(event) {
      const pasteData = (event.clipboardData || window.clipboardData).getData(
        "text"
      );
      if (!/^\d+$/.test(pasteData)) {
        event.preventDefault();
      }
    },
    convertStringNumberToFigures(d) {
      let data = d ? parseInt(d) : 0;
      return data ? data.toLocaleString() : 0;
    },
    setSelectedLga() {
      const key = appState.selectedLgaKey;
      // if (!key) return;

      const selectedLga = appState.lgaData[key];
      if (!selectedLga) return;

      appState.facilityTitles = selectedLga.lga;
      appState.currentLgaId = selectedLga.lgaid;
      EventBus.$emit("g-event-reset-form");
    },
  },
});

Vue.component("page-table", {
  mixins: [eventBusMixin],
  data: function () {
    return {
      appState,
      tableData: [],
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
        orderDir: "desc", // (asc|desc)
        orderField: 0, //(Order fields)
        limitStart: 0, //(currentPage - 1) * perPage
        isNext: false,
        isPrev: false,
        aLength: [10, 20, 50, 100, 150, 200],
        filterParam: {
          gid: appState.currentLgaId,
          glv: appState.level,
          lga_name: appState.facilityTitles,
        },
      },
      geoLevelData: [],
    };
  },
  mounted() {
    /*  Manages events Listening    */
    this.getProductMaster();
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
    EventBus.$on("g-event-refresh-page", this.reloadTableListOnUpdate);
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
    reloadTableListOnUpdate(data = {}) {
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
            "?qid=801&draw=" +
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
            self.tableOptions.filterParam.geo_level +
            "&gid=" +
            appState.currentLgaId +
            "&glv=lga"
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
      if (appState.currentLgaId != "") {
        this.tableOptions.filterParam.gid = appState.currentLgaId;
        this.tableOptions.filterParam.glv = appState.level;
        this.tableOptions.filterParam.lga_name = appState.facilityTitles;
      }
      let checkFill = 0;
      checkFill += this.tableOptions.filterParam.gid != "" ? 1 : 0;
      checkFill += this.tableOptions.filterParam.level != "" ? 1 : 0;

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
      console.log(column_name);
      // this.tableOptions.filterParam + '.' + column_name == "";
      this.tableOptions.filterParam[column_name] = "";
      if (column_name == "lga_name") {
        this.tableOptions.filterParam.gid =
          this.tableOptions.filterParam.glv =
          this.tableOptions.filterParam.lga_name =
          appState.selectedLgaKey =
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
      // $(".select2").val("").trigger("change");
      this.tableOptions.filterParam.gid =
        this.tableOptions.filterParam.level =
        this.tableOptions.filterParam.lga_name =
        appState.selectedLgaKey =
          "";

      this.paginationDefault();
      this.loadTableData();
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
    checkIfAndReturnEmpty(data) {
      if (data === null || data === "") {
        return "";
      } else {
        return data;
      }
    },
    goToCreateIssue() {
      appState.pageState.page = "create-issues";
      appState.currentPeriodId = "";
      appState.currentLgaId = "";
      appState.facilityTitles = "";
      appState.selectedLgaKey = "";
    },
    getProductMaster() {
      var url = common.DataService;
      overlay.show();

      axios
        .get(url + "?qid=gen011")
        .then(function (response) {
          let data = response.data.data.sort((a, b) =>
            a.product_code.localeCompare(b.product_code)
          );

          appState.productData = data; //All Data
          overlay.hide();
        })
        .catch(function (error) {
          overlay.hide();
          alert.Error("ERROR", error);
        });
    },
  },
  computed: {},
  template: `

        <div class="row">
            <div class="col-md-8 col-sm-12 col-12 mb-0">
                <h2 class="content-header-title header-txt float-left mb-0">SMC</h2>
                <div class="breadcrumb-wrapper">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../smc/logistics">Logistics</a></li>
                        <li class="breadcrumb-item active">Bulk Allocation </li>
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
                        class="btn btn-outline-primary round" data-toggle="tooltip" data-placement="top" title="Create Issue" @click="goToCreateIssue()">
                        <i class="feather icon-plus"></i>             
                    </button>
                </div>
            </div>
            <div class="col-12" v-if="filters">
                <div class="col-12 filter-bar">
                    <span class="badge badge-dark filter-box" @click="removeSingleFilter(i)" v-for="(filterParam, i) in tableOptions.filterParam" v-if="filterParam.length > 0 && i != 'glv' && i != 'geo_level_id'&& i != 'role_id'">{{capitalize(i)}}: {{filterParam}} <i class="feather icon-x"></i></span>
                    <a href="#" class="float-right clear-filter" @click="clearAllFilter()">Clear</a>
                </div>
            </div>

            <div class="col-12 mt-1">
                <div class="card  custom-select-down">
                    <div class="card-body py-1" v-show="filterState">
                        <form id="filterForm">
                            <div class="row">
                                    <div class="col-12 col-md-5 col-sm-12 col-lg-5">
                                        <div class="form-group">
                                            <label>Choose LGA to Populate Issue</label>
                                            <select class="form-control" v-model="appState.selectedLgaKey" @change="setSelectedLga($event)" placeholder="Choose LGA">
                                                <option value="">Choose LGA</option>
                                                <option v-for="(g, i) in appState.lgaData" :value="i">{{ g.lga }}</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-12 col-md-5 col-sm-12 col-lg-5">
                                        <div class="form-group">
                                            <label>Visit</label>
                                            <select v-model="appState.currentPeriodId" class="form-control period" id="period">
                                              <option value="">Choose Visit</option>
                                              <option v-for="(g, i) in appState.periodData" :value="g.periodid">{{ g.title }}</option>
                                            </select>
                                        </div>
                                    </div>
                                <div class="col-12 col-sm-12 col-md-2 col-lg-2">
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
                                    <!--
                                    <th width="60px">
                                        <div class="custom-control custom-checkbox checkbox">
                                            <input type="checkbox" class="custom-control-input" :checked="checkToggle" @change="selectToggle(), totalCheckedBox()" id="all-check" />
                                            <label class="custom-control-label" for="all-check"></label>
                                        </div>
                                    </th>
                                    -->
                                    <th>#</th>
                                    <th class="pl-0">
                                        Geo String
                                        <!--
                                        <i class="feather icon-chevron-up sort-up" :class="(tableOptions.orderField == 0 && tableOptions.orderDir =='asc')? 'active-sort': ''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 0 && tableOptions.orderDir =='desc')? 'active-sort': ''"></i>
                                        -->
                                    </th>
                                    <th class="pl-1">
                                        Product
                                    </th>
                                    <th class="pl-1">
                                        Secondary QTY.
                                    </th>
                                    <th class="pl-1">
                                        Primary QTY.
                                    </th>
                                    <th class="pl-1">
                                        Created
                                    </th>
                                    <th class="px-1">
                                        Updated
                                    </th>
                                    <!--
                                    <th class="text-center">Actions</th>
                                    -->
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(g, rowIndex) in tableData">
                                    <!--
                                    <tr v-for="(g, i) in tableData" :class="checkedBg(g.pick)">
                                    <td>
                                        <div class="custom-control custom-checkbox checkbox">
                                            <input type="checkbox" class="custom-control-input" :id="g.loginid" v-model="g.pick" @change="totalCheckedBox()" />
                                            <label class="custom-control-label" :for="g.loginid"></label>
                                        </div>
                                    </td>
                                    -->
                                    <td>{{rowIndex+1}}</td>
                                    <td class="pl-0 text-wrap">{{g.geo_string}}</td>
                                    <td class="pl-1">{{g.product_name}}</td>
                                    <td class="pl-1">{{convertStringNumberToFigures(g.secondary_qty)}}</td>
                                    <td class="pl-1">{{convertStringNumberToFigures(g.primary_qty)}}</td>
                                    <td class="pl-1">{{displayDate(g.created)}}</td>
                                    <td class="px-1">{{displayDate(g.updated)}}</td>
                                    <!--
                                    <td class="px-1 text-center">
                                      <span class="text-primary font-medium-2 font-weight-bold mr-2"><i class="feather icon-edit"></i></span>
                                    </td>
                                    -->
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

Vue.component("page-create-issue", {
  mixins: [eventBusMixin],
  data: function () {
    return {
      appState,
      tableData: [],
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
      facilityData: [],
      tempFacilityData: [],
      currentEditField: "",
      editingCell: {
        rowIndex: null,
        productCode: null,
      },
      isUpdated: false,

      dragStart: null,
      dragField: null,
      facilityName: null,
      product: null,
    };
  },
  mounted() {
    /*  Manages events Listening    */
    this.getSysDefaultDataSettings();
    this.getAllPeriodLists();

    EventBus.$on("g-event-reset-form", this.resetForm);
  },
  methods: {
    goToIssueTable() {
      appState.pageState.page = "table";
      this.facilityData = [];
      this.tempFacilityData = [];
      Object.assign(appState, {
        currentPeriodId: "",
        currentLgaId: "",
        facilityTitles: "",
        selectedLgaKey: "",
      });
    },
    getSysDefaultDataSettings() {
      /*  Manages the loading of System default settings */
      var self = this;
      var url = common.DataService;
      overlay.show();

      axios
        .get(url + "?qid=gen007")
        .then(function (response) {
          if (response.data.data.length > 0) {
            appState.sysDefaultData = response.data.data[0]; //All Data
            self.getAllLga(response.data.data[0].stateid);
            //  Set preventDefault();
            appState.geoLevelForm.geoLevel = "state";
            appState.geoLevelForm.geoLevelId = response.data.data[0].stateid;
            appState.defaultStateId = response.data.data[0].stateid;
          }

          overlay.hide();
        })
        .catch(function (error) {
          overlay.hide();
          alert.Error("ERROR", error);
        });
    },
    getAllLga(stateid) {
      /*  Manages the loading of Geo Level data */
      var self = this;
      var url = common.DataService;
      overlay.show();

      axios
        .post(url + "?qid=gen003", JSON.stringify(stateid))
        .then(function (response) {
          appState.lgaData = response.data.data; //All Data
          overlay.hide();
        })
        .catch(function (error) {
          overlay.hide();
          alert.Error("ERROR", error);
        });
    },
    groupAndFillMissingProducts(data, masterProductData) {
      const grouped = {};

      // Helper to convert null or undefined to ""
      const sanitize = (obj) => {
        const clean = {};
        for (const key in obj) {
          clean[key] =
            obj[key] === null || obj[key] === undefined ? "" : obj[key];
        }
        return clean;
      };

      // Create map of product_code -> name
      const productMap = Object.fromEntries(
        masterProductData.map((p) => [p.product_code, p.name])
      );

      // Group data by geo_string
      data.forEach((item) => {
        const key = item.geo_string;
        grouped[key] ??= [];

        if (!item.product_code) {
          // Expand to all products if product_code is missing
          masterProductData.forEach((prod) => {
            grouped[key].push(
              sanitize({
                ...item,
                product_code: prod.product_code,
                product_name: prod.name,
              })
            );
          });
        } else {
          grouped[key].push(sanitize(item));
        }
      });

      // Ensure all groups have all products and sort
      for (const key in grouped) {
        const entries = grouped[key];
        const existingCodes = new Set(entries.map((i) => i.product_code));
        const baseDPID = entries[0]?.dpid ?? "";

        masterProductData.forEach((prod) => {
          if (!existingCodes.has(prod.product_code)) {
            entries.push(
              sanitize({
                geo_string: key,
                dpid: baseDPID,
                issue_id: "",
                period: appState.currentPeriodId,
                product_code: prod.product_code,
                product_name: prod.name,
                primary_qty: "",
                secondary_qty: "",
                created: "",
              })
            );
          }
        });

        // Sort entries by product_code
        entries.sort((a, b) => a.product_code.localeCompare(b.product_code));
      }

      return grouped;
    },
    async getFacilityIssueByPeriod() {
      const { currentLgaId, currentPeriodId, productData } = appState;

      if (!currentLgaId) {
        alert.Error("ERROR", "Please select LGA");
        return;
      }
      if (!currentPeriodId) {
        alert.Error("ERROR", "Please select a visit");
        return;
      }

      overlay.show();

      try {
        const response = await axios.post(`${common.DataService}?qid=gen010`, {
          lgaId: currentLgaId,
          periodId: currentPeriodId,
        });

        this.facilityData = this.groupAndFillMissingProducts(
          response.data?.data || [],
          productData || []
        );

        // Deep clone safely
        this.tempFacilityData = structuredClone
          ? structuredClone(this.facilityData)
          : JSON.parse(JSON.stringify(this.facilityData));
      } catch (error) {
        alert.Error("ERROR", error?.message || "An error occurred");
      } finally {
        overlay.hide();
      }
    },
    startEdit(rowIndex, productCode) {
      this.editingCell = { rowIndex, productCode };
      this.$nextTick(() => {
        // Construct the input ref name dynamically to focus it
        const inputRef = `input-${rowIndex}-${productCode}`;
        const input = this.$refs[inputRef];
        if (input) {
          // If v-for renders multiple inputs, $refs[inputRef] might be an array
          if (Array.isArray(input)) {
            input[0].focus();
          } else {
            input.focus();
          }
        }
      });
    },
    isEditing(rowIndex, productCode) {
      return (
        this.editingCell.rowIndex === rowIndex &&
        this.editingCell.productCode === productCode
      );
    },
    stopEdit() {
      this.editingCell = { rowIndex: null, productCode: null };
    },
    prepareIssues(facilityData) {
      const packSize = 50;
      const periodId = appState.currentPeriodId ?? null;

      const result = [];

      if (!facilityData || typeof facilityData !== "object") return result;

      Object.values(facilityData).forEach((entries = []) => {
        entries.forEach((entry = {}) => {
          const secondaryQty = Number(entry.secondary_qty);

          if (!isNaN(secondaryQty) && secondaryQty > 0) {
            result.push({
              issue_id: entry.issue_id != null ? parseInt(entry.issue_id) : "",
              periodid: periodId,
              dpid: entry.dpid ?? null,
              product_code: entry.product_code ?? null,
              product_name: entry.product_name ?? null,
              primary_qty: !isNaN(packSize) ? secondaryQty * packSize : null,
              secondary_qty: secondaryQty,
            });
          }
        });
      });

      return result;
    },
    submitIssues() {
      if (appState.currentPeriodId == "") {
        alert.Error("ERROR", "Please select a visit");
        return;
      }
      let data = this.prepareIssues(this.facilityData);

      let self = this;
      let url = common.DataService;
      overlay.show();
      axios
        .post(url + "?qid=1129", JSON.stringify(data))
        .then(function (response) {
          overlay.hide();
          if (response.data.result_code == "200") {
            EventBus.$emit("g-event-refresh-page");
            alert.Success("SUCCESS", response.data.message);
            self.goToIssueTable();
          } else {
            alert.Error("ERROR", response.data.message);
          }
        })
        .catch(function (error) {
          overlay.hide();
          alert.Error("ERROR", error);
        });
    },
    resetForm() {
      this.facilityData = [];
      this.tempFacilityData = [];
    },
    getAllPeriodLists() {
      /*  Manages the loading of Geo Level data */
      var self = this;
      var url = common.DataService;
      overlay.show();

      axios
        .get(url + "?qid=1004")
        .then(function (response) {
          appState.periodData = response.data.data; //All Data
          overlay.hide();
        })
        .catch(function (error) {
          overlay.hide();
          alert.Error("ERROR", error);
        });
    },
    hasChanged() {
      this.isUpdated =
        JSON.stringify(this.facilityData) !==
        JSON.stringify(this.tempFacilityData);
    },
    startDrag(rowIndex, field, facilityName, product) {
      this.dragStart = rowIndex;
      this.dragField = field;
      this.facilityName = facilityName;
      this.product = product;

      window.addEventListener("mouseup", this.finishDrag);
    },
    onDragOver(currentRow, facilityName, field) {
      if (this.dragStart !== null && this.dragField !== null) {
        const startRow = Math.min(this.dragStart, currentRow);
        const endRow = Math.max(this.dragStart, currentRow);

        const startField = this.dragField;
        const currentField = field;
        const updated = facilityName;
        const valueToFill = this.product?.secondary_qty;

        const allProductCodes =
          this.facilityData?.[updated]?.map((item) =>
            item.product_code?.toUpperCase()
          ) || [];

        const startColIndex = allProductCodes.indexOf(
          startField?.toUpperCase()
        );
        const endColIndex = allProductCodes.indexOf(
          currentField?.toUpperCase()
        );

        if (startColIndex === -1 || endColIndex === -1) return;

        const minCol = Math.min(startColIndex, endColIndex);
        const maxCol = Math.max(startColIndex, endColIndex);

        for (let i = startRow; i <= endRow; i++) {
          for (let j = minCol; j <= maxCol; j++) {
            const targetCode = allProductCodes[j];
            const position = this.facilityData?.[updated]?.findIndex(
              (item) => item.product_code?.toUpperCase() === targetCode
            );

            if (position !== -1) {
              this.facilityData[updated][position]["secondary_qty"] =
                valueToFill;
            }
          }
        }
      }
    },
    finishDrag() {
      this.dragStart = null;
      this.dragField = null;
      this.facilityName = null;
      this.product = null;

      window.removeEventListener("mouseup", this.finishDrag);
    },
    getCellClass(rowIndex) {
      return {
        "drag-target": this.dragStart !== null && rowIndex !== this.dragStart,
      };
    },
    resetIssues() {
      this.facilityData = JSON.parse(JSON.stringify(this.tempFacilityData));
    },
    cancelIssue() {
      if (this.isUpdated) {
        $.confirm({
          title: "WARNING!",
          content: "Are you sure you want to discard the changes made?",
          buttons: {
            discard: {
              text: "Discard",
              btnClass: "btn btn-danger mr-1",
              action: () => this.goToIssueTable(),
            },
            cancel: () => {}, // no-op
          },
        });
      } else {
        this.goToIssueTable();
      }
    },
  },
  computed: {
    groupedProductSummary() {
      const totals = {};

      Object.values(this.facilityData).forEach((records) => {
        records.forEach(({ product_code, secondary_qty }) => {
          const code = product_code?.toUpperCase();
          if (!code) return;
          totals[code] = (totals[code] || 0) + (Number(secondary_qty) || 0);
        });
      });

      return this.appState.productData.map((p) => {
        const code = p.product_code?.toUpperCase();
        return {
          product_code: code,
          total: totals[code] || 0,
        };
      });
    },
    hasFacilityData() {
      return (
        this.facilityData && Object.values(this.facilityData).flat().length > 0
      );
    },
  },
  watch: {
    facilityData: {
      handler: "hasChanged", // cleaner syntax
      deep: true,
      immediate: false, // set to true if you want it to run on initial load
    },
  },
  template: `

        <div class="row">
            <div class="col-md-8 col-sm-12 col-12 mb-0">
                <h2 class="content-header-title header-txt float-left mb-0">SMC</h2>
                <div class="breadcrumb-wrapper">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../smc/logistics">Logistics</a></li>
                        <li class="breadcrumb-item"><a href="javascript:void(0)" @click="cancelIssue()">Issues</a></li>
                        <li class="breadcrumb-item active">Bulk Allocation</li>
                    </ol>
                </div>
                
            </div>
 

            <div class="col-12 mt-1">

                <div class="card">
                    <div class="card-header">
                        <button class="btn pl-0 pr-50 py-50 waves-effect"  @click="cancelIssue()">
                            <i class="feather icon-chevron-left"></i> Back
                        </button>
                        <!--
                        <h5 class="card-title text-primary">Create Issue</h5>
                        -->
                    </div>

                    <div class="card-body">
 
                        <div class="card border mb-0 shadow shadow-sm border-light border-lighten-1">

                            <div class="card-body">
                                <div class="row">
                                    <div class="col-12 col-md-5 col-sm-12 col-lg-5">
                                        <div class="form-group">
                                            <label>Choose LGA to Populate Issue</label>
                                            <select class="form-control" v-model="appState.selectedLgaKey" @change="setSelectedLga($event)" placeholder="Choose LGA">
                                                <option value="">Choose LGA</option>
                                                <option v-for="(g, i) in appState.lgaData" :value="i">{{ g.lga }}</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-12 col-md-4 col-sm-12 col-lg-5">
                                        <div class="form-group">
                                            <label>Visit</label>
                                            <select v-model="appState.currentPeriodId" @change="resetForm()" class="form-control period" id="period">
                                              <option value="">Choose Visit</option>
                                              <option v-for="(g, i) in appState.periodData" :value="g.periodid">{{ g.title }}</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-12 col-md-3 col-sm-12 col-lg-2 text-right">
                                        <div class="form-group">
                                            <label class="d-none d-md-block d-lg-block">&nbsp;</label>
                                            <button type="button" style="max-width: 120px !important" class="btn btn-primary  form-control" @click="getFacilityIssueByPeriod()">
                                              Load  <i class="feather icon-send ml-1 text-right"></i>
                                            </button>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                    
                        <div class="table-responsive mt-2" v-show="hasFacilityData">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                      <th class="px-1">#</th>
                                      <th>{{ appState.facilityTitles }} Facilities</th>
                                      <th class="px-1" v-for="(product, i) in appState.productData" :key="i">
                                          {{ product.name }} QTY
                                      </th>
                                    </tr>
                                </thead>
                                <tbody>
                                  <tr v-for="(facility, rowIndex, g) in facilityData" :key="facility.geo_string || g">
                                    <td class="px-1">{{g+1}}</td>
                                    <td>{{ rowIndex }}</td>
                                    <td
                                        style="max-width: 160px;"
                                        class="px-1" v-for="product in facility" :key="product.product_code">
                                          <input
                                            type="text"
                                            class="form-control"
                                            v-model="product.secondary_qty"

                                            @mousedown="startDrag(g, product.product_code, rowIndex, product)"
                                            @mouseover="onDragOver(g, rowIndex, product.product_code)"

                                            @keypress="numbersOnlyWithoutDot($event)"
                                            @paste="validatePaste($event)"
                                            @blur="stopEdit()"
                                            @keyup.enter="stopEdit()"
                                            :ref="'input-' + rowIndex + '-' + product.product_code?.toUpperCase()"
                                        />
                                        <small class="font-small-1">{{product.product_code}}</small>
                                    </td>
                                  </tr>

                                  <tr>
                                    <th colspan="2" class="text-right">Total</th>
                                    <th class="pl-1 pr-1 text-wrap" v-for="product in appState.productData" :key="product.product_code + '-total'">
                                      {{ convertStringNumberToFigures(groupedProductSummary.find(p => p.product_code?.toUpperCase() === product.product_code?.toUpperCase())?.total || 0) }}
                                    </th>
                                  </tr>

                                  <tr v-if="!hasFacilityData">
                                    <td class="text-center pt-2" :colspan="parseInt(appState?.productData.length)+2">
                                      <small>No Facility Added</small>
                                    </td>
                                  </tr>
                                  <tr>
                                      <th :colspan="parseInt(appState?.productData.length)+2" class="text-right pl-1 pr-1">
                                          <button  :disabled="!isUpdated || appState.currentPeriodId === ''" class="btn btn-outline-warning btn-md mr-1" @click="resetIssues()">
                                              Reset <i class="feather icon-trash-2 ml-50"></i> 
                                          </button>
                                          <button  class="btn btn-outline-danger mr-1 btn-md" @click="cancelIssue()">
                                              Cancel <i class="feather icon-x ml-50"></i> 
                                          </button>
                                          <button :disabled="!isUpdated || appState.currentPeriodId === ''" class="btn btn-primary btn-md" @click="submitIssues()">
                                              Update Issue <i class="feather icon-send ml-50"></i> 
                                          </button>
                                      </th>
                                  </tr>
                                </tbody>

                            </table>


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
                <page-create-issue />
            </div>
        </div>
      `,
});
