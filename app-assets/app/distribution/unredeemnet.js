const EventBus = new Vue();
/*
 *      EVENT HANDLED BY EventBus
 *
 *      - g-event-change-page (Emit multiple - to change page)
 * 		EventBus.$emit('g-event-change-page', 2); 	- Event Fire
 *		EventBus.$on('g-event-change-page', this.gotoPageHandler)  - Event receiver
		gotoPageHandler(data){
            overlay.show();
            this.page = data;
            overlay.hide();
        },						-	Event Handler
 *  
 */

// g-event-goto-page
// g-event-update-user

Vue.component("page-body", {
  data: function () {
    return {
      page: "list", //  page by name home | result | ...
      permission: getPermission(per, "distribution"),
    };
  },
  mounted() {
    /*  Manages events Listening    */
    EventBus.$on("g-event-goto-page", this.gotoPageHandler);
  },
  methods: {
    gotoPageHandler(data) {
      this.page = data.page;
    },
  },
  template: `
        <div>

            <div class="content-body">

                <div v-show="page == 'list'">
                    <unredeemed_search v-if="permission.permission_value >1" />
                    <div class="alert alert-danger" v-else>
                      <div class="alert-body">
                        <strong>Access Denied!</strong> You don't have permission to access this page.
                        </div>
                    </div>
                </div>

            </div>
        </div>

    `,
});

// User List Page
Vue.component("unredeemed_search", {
  data: function () {
    return {
      url: common.BadgeService,
      tableData: [],
      permission: getPermission(per, "distribution"),
      tableDetails: {
        hhid: "",
        hoh_first: "",
        hoh_last: "",
        hoh_phone: "",
        hoh_gender: "",
        family_size: "",
        hod_mother: "",
        allocated_net: "",
        sleeping_space: "",
        adult_female: "",
        adult_male: "",
        children: "",
        etoken_serial: "",
        etoken_pin: "",
        geo_level: "",
        geo_level_id: "",
        geo_string: "",
        mobilization_date: "",
      },
      id: 0,
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
          loginid: "",
          hh_phone_no: "",
          etoken_serial: "",
          etoken_pin: "",
          mobilization_date: "",
          geo_level: "",
          geo_level_id: "",
          geo_string: "",
        },
      },
      sysDefaultData: [],
      userPass: {
        pass: "",
        loginid: "",
        name: "",
      },
    };
  },
  mounted() {
    /*  Manages events Listening    */
    this.getGeoLocation();
    this.loadTableData();
    EventBus.$on("g-event-update-user", this.reloadUserListOnUpdate);
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
    $(".date").flatpickr({
      altInput: true,
      altFormat: "F j, Y",
      dateFormat: "Y-m-d",
    });
  },
  methods: {
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
            "?qid=402&draw=" +
            self.tableOptions.currentPage +
            "&order_column=" +
            self.tableOptions.orderField +
            "&length=" +
            self.tableOptions.perPage +
            "&start=" +
            self.tableOptions.limitStart +
            "&order_dir=" +
            self.tableOptions.orderDir +
            "&glv=" +
            self.tableOptions.filterParam.geo_level +
            "&lgid=" +
            self.tableOptions.filterParam.loginid +
            "&gid=" +
            self.tableOptions.filterParam.geo_level_id +
            "&mdt=" +
            self.tableOptions.filterParam.mobilization_date +
            "&pph=" +
            self.tableOptions.filterParam.hh_phone_no +
            "&ets=" +
            self.tableOptions.filterParam.etoken_serial +
            "&etp=" +
            self.tableOptions.filterParam.etoken_pin
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
        $("#mobilization_date")
          .flatpickr({
            altInput: true,
            altFormat: "F j, Y",
            dateFormat: "Y-m-d",
          })
          .clear();
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
      /*  Manages the selections of checked or selected data object */
      this.tableOptions.currentPage += 1;
      this.paginationDefault();
      this.loadTableData();
    },
    prevPage() {
      /*  Manages the selections of checked or selected data object */
      this.tableOptions.currentPage -= 1;
      this.paginationDefault();
      this.loadTableData();
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
      let maxPerPage = Math.ceil(this.tableOptions.total / val);
      if (maxPerPage < this.tableOptions.currentPage) {
        this.tableOptions.currentPage = maxPerPage;
      }
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
      checkFill += this.tableOptions.filterParam.loginid != "" ? 1 : 0;
      checkFill +=
        this.tableOptions.filterParam.mobilization_date != "" ? 1 : 0;
      checkFill += this.tableOptions.filterParam.etoken_serial != "" ? 1 : 0;
      checkFill += this.tableOptions.filterParam.etoken_pin != "" ? 1 : 0;
      checkFill += this.tableOptions.filterParam.hh_phone_no != "" ? 1 : 0;
      checkFill += this.tableOptions.filterParam.geo_level != "" ? 1 : 0;
      checkFill += this.tableOptions.filterParam.geo_level_id != "" ? 1 : 0;

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
      if (column_name == "geo_level" || column_name == "geo_level_id") {
        this.tableOptions.filterParam["geo_level"] =
          this.tableOptions.filterParam["geo_level_id"] = "";
      }

      if (column_name == "mobilization_date") {
        $("#mobilization_date")
          .flatpickr({
            altInput: true,
            altFormat: "F j, Y",
            dateFormat: "Y-m-d",
          })
          .clear();
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
      $("#mobilization_date")
        .flatpickr({
          altInput: true,
          altFormat: "F j, Y",
          dateFormat: "Y-m-d",
        })
        .clear();
      this.filters = false;
      this.tableOptions.filterParam.mobilization_date =
        this.tableOptions.filterParam.loginid =
        this.tableOptions.filterParam.geo_level =
        this.tableOptions.filterParam.geo_level_id =
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
      });
    },
    refreshData() {
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
    displayDate(d) {
      let date = new Date(d);
      let options = {
        year: "numeric",
        month: "long",
        day: "numeric",
        hour12: true,
        hour: "2-digit",
        minute: "2-digit",
        second: "2-digit",
      };
      return date.toLocaleString("en-us", options);
    },
    setLocation(select_index) {
      this.tableOptions.filterParam.geo_level =
        this.geoData[select_index].geo_level;
      this.tableOptions.filterParam.geo_level_id =
        this.geoData[select_index].geo_level_id;
      this.tableOptions.filterParam.geo_string =
        this.geoData[select_index].title;
    },
    async exportMobilization() {
      var self = this;
      var veriUrl =
        "qid=402&draw=" +
        self.tableOptions.currentPage +
        "&order_column=" +
        self.tableOptions.orderField +
        "&length=" +
        self.tableOptions.perPage +
        "&start=" +
        self.tableOptions.limitStart +
        "&order_dir=" +
        self.tableOptions.orderDir +
        "&glv=" +
        self.tableOptions.filterParam.geo_level +
        "&lgid=" +
        self.tableOptions.filterParam.loginid +
        "&gid=" +
        self.tableOptions.filterParam.geo_level_id +
        "&mdt=" +
        self.tableOptions.filterParam.mobilization_date +
        "&pph=" +
        self.tableOptions.filterParam.hh_phone_no +
        "&ets=" +
        self.tableOptions.filterParam.etoken_serial +
        "&etp=" +
        self.tableOptions.filterParam.etoken_pin;

      var dlString =
        "qid=402&draw=" +
        self.tableOptions.currentPage +
        "&order_column=" +
        self.tableOptions.orderField +
        "&length=" +
        self.tableOptions.perPage +
        "&start=" +
        self.tableOptions.limitStart +
        "&order_dir=" +
        self.tableOptions.orderDir +
        "&glv=" +
        self.tableOptions.filterParam.geo_level +
        "&lgid=" +
        self.tableOptions.filterParam.loginid +
        "&gid=" +
        self.tableOptions.filterParam.geo_level_id +
        "&mdt=" +
        self.tableOptions.filterParam.mobilization_date +
        "&pph=" +
        self.tableOptions.filterParam.hh_phone_no +
        "&ets=" +
        self.tableOptions.filterParam.etoken_serial +
        "&etp=" +
        self.tableOptions.filterParam.etoken_pin;

      var filename =
        (this.tableOptions.filterParam.geo_string
          ? this.tableOptions.filterParam.geo_string
          : "UnRedeemed ") +
        " " +
        (this.tableOptions.filterParam.loginid
          ? this.tableOptions.filterParam.loginid
          : "UnRedeemed ") +
        " Mobilization List";
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

      overlay.hide();
    },
    showdistributionDetailsModal(i) {
      overlay.show();
      this.tableDetails.geo_string = this.tableData[i].geo_string;
      this.tableDetails.allocated_net = this.tableData[i].allocated_net;
      this.tableDetails.mobilization_date = this.tableData[i].collected_date;
      this.tableDetails.etoken_serial = this.tableData[i].etoken_serial;
      this.tableDetails.etoken_pin = this.tableData[i].etoken_pin;
      this.tableDetails.sleeping_space = this.tableData[i].sleeping_space;
      this.tableDetails.adult_female = this.tableData[i].adult_female;
      this.tableDetails.adult_male = this.tableData[i].adult_male;
      this.tableDetails.family_size = this.tableData[i].family_size;
      this.tableDetails.geo_level = this.tableData[i].geo_level;
      this.tableDetails.geo_string = this.tableData[i].geo_string;
      this.tableDetails.hoh_first = this.tableData[i].hoh_first;
      this.tableDetails.hoh_last = this.tableData[i].hoh_last;
      this.tableDetails.hoh_gender = this.tableData[i].hoh_gender;
      this.tableDetails.hoh_phone = this.tableData[i].hoh_phone;
      this.tableDetails.children = this.tableData[i].children;

      this.tableDetails.location_description =
        this.tableData[i].location_description;

      $("#distributionDetails").modal("show");
      overlay.hide();
    },
    hidedistributionDetailsModal() {
      overlay.show();
      $("#distributionDetails").modal("hide");
      let g = 0;
      for (let i in this.tableDetails) {
        this.tableDetails[i] = "";
        g++;
      }
      overlay.hide();
    },
    checkIfEmpty(data) {
      if (data === null || data === "") {
        return "Nil";
      } else {
        return data;
      }
    },
  },
  computed: {},
  template: `

        <div class="row" id="basic-table">
            <div class="col-md-8 col-sm-12 col-12 mb-0">
                <h2 class="content-header-title header-txt float-left mb-0">Distribution</h2>
                <div class="breadcrumb-wrapper">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../distribution">Home</a></li>
                        <li class="breadcrumb-item active">Unredeemed e-Token</li>
                    </ol>
                </div>
            </div>

            <div class="col-md-4 col-sm-12 col-12 text-md-right text-right d-md-block">
                <div class="btn-group mr-1">
                    <button type="button" class="btn btn-outline-primary round searchBtn" @click="refreshData()" data-toggle="tooltip" data-placement="top" title="Refresh">
                        <i class="feather icon-refresh-cw"></i>         
                    </button>       
                    <button type="button" class="btn btn-outline-primary round searchBtn" @click="toggleFilter()" data-toggle="tooltip" data-placement="top" title="Filter">
                        <i class="feather" :class="filterState ? 'icon-x' : 'icon-filter'"></i>               
                    </button>
                    
                    <button class="btn btn-outline-primary round dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        Actions
                    </button>
                    <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                        <a class="dropdown-item" href="javascript:void(0);" @click="exportMobilization()">Export Data</a>
                    </div>
                </div>
            </div>
            <div class="col-12" v-if="filters">
                <div class="col-12 filter-bar">
                    <span class="badge badge-dark filter-box" @click="removeSingleFilter(i)" v-for="(filterParam, i) in tableOptions.filterParam" v-if="filterParam.length > 0">{{capitalize(i)}}: {{filterParam}} <i class="feather icon-x"></i></span>
                    <a href="javascript:void(0);" class="float-right clear-filter" @click="clearAllFilter()">Clear</a>
                </div>
            </div>

            <div class="col-12 mt-1">
                <div class="card custom-select-down">
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
                                        <label>HHM Login ID</label>
                                        <input type="text" v-model="tableOptions.filterParam.hhm_loginid" class="form-control login-id" id="login-id" placeholder="HHM Login ID" name="loginid" />
                                    </div>
                                </div>
                                <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                                    <div class="form-group">
                                        <label>HH Phone No</label>
                                        <input type="text" v-model="tableOptions.filterParam.hh_phone_no" class="form-control hh_phone_no" id="hh_phone_no" placeholder="Head of Household Phone No" name="hh_phone_no" />
                                    </div>
                                </div>
                                <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                                    <div class="form-group">
                                        <label>e-Token Serial</label>
                                        <input type="text" v-model="tableOptions.filterParam.etoken_serial" class="form-control e_token_serial" id="etoken_serial" placeholder="e-Token Serial" name="etoken_serial" />
                                    </div>
                                </div>
                                <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                                    <div class="form-group">
                                        <label>Serial</label>
                                        <input type="text" v-model="tableOptions.filterParam.serial" class="form-control serial" id="serial" placeholder="Token Serial No" name="serial" />
                                    </div>
                                </div>
                                <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                                    <div class="form-group">
                                        <label>e-Token Pin</label>
                                        <input type="text" v-model="tableOptions.filterParam.etoken_pin" class="form-control etoken_pin" id="etoken_pin" placeholder="e-Token Pin" name="etoken_pin" />
                                    </div>
                                </div>
                                <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                                    <div class="form-group date_filter">
                                        <label>Mobilization Date</label>
                                        <input type="text" id="mobilization_date" v-model="tableOptions.filterParam.mobilization_date" class="form-control mobilization_date date" placeholder="Mobilization Date" name="mobilization_date" />
                                    </div>
                                </div>
                                <div class="col-12 col-sm-12 col-md-12 col-lg-6">
                                    <div class="form-group mt-2 text-right">
                                        <button type="button" class="btn btn-md btn-primary" @click="applyFilter()">Apply Filters</button>
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
                                            <input type="checkbox" class="custom-control-input" @change="selectToggle()" id="all-check" />
                                            <label class="custom-control-label" for="all-check"></label>
                                        </div>
                                    </th>
                                    <th @click="sort(0)">
                                        #
                                        <i class="feather icon-chevron-up sort-up" :class="(tableOptions.orderField == 1 && tableOptions.orderDir =='asc')? 'active-sort': ''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 1 && tableOptions.orderDir =='desc')? 'active-sort': ''"></i>
                                    </th>
                                    -->

                                    <th @click="sort(0)" width="60px">
                                        #
                                        <i class="feather icon-chevron-up sort-up" :class="(tableOptions.orderField == 0 && tableOptions.orderDir =='asc')? 'active-sort': ''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 0 && tableOptions.orderDir =='desc')? 'active-sort': ''"></i>
                                    </th>
                                    <th @click="sort(1)">
                                        Household Name
                                        <i class="feather icon-chevron-up sort-up" :class="(tableOptions.orderField == 1 && tableOptions.orderDir =='asc')? 'active-sort': ''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 1 && tableOptions.orderDir =='desc')? 'active-sort': ''"></i>
                                    </th>
                                    <th>
                                        Household Mothers Name
                                    </th>
                                    <th @click="sort(6)">
                                        Net
                                        <i class="feather icon-chevron-up sort-up" :class="(tableOptions.orderField == 6 && tableOptions.orderDir =='asc')? 'active-sort': ''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 6 && tableOptions.orderDir =='desc')? 'active-sort': ''"></i>
                                    </th>
                                    <th>
                                        Geo Location
                                    </th>
                                    <th @click="sort(7)">
                                        Mobilization Date
                                        <i class="feather icon-chevron-up sort-up" :class="(tableOptions.orderField == 7 && tableOptions.orderDir =='asc')? 'active-sort': ''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 7 && tableOptions.orderDir =='desc')? 'active-sort': ''"></i>
                                    </th>
                                    <th v-if="permission.permission_value ==3"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(g, i) in tableData">
                                <!--
                                  <tr v-for="(g, i) in tableData" :class="checkedBg(g.pick)">
                                        <td>
                                            <div class="custom-control custom-checkbox checkbox">
                                                <input type="checkbox" class="custom-control-input" :id="g.hhid" v-model="g.pick" />
                                                <label class="custom-control-label" :for="g.hhid"></label>
                                            </div>
                                        </td>
                                        <td>{{g.hhid}}</td>
                                    -->
                                    <td>
                                      <!--
                                        <div class="d-flex justify-content-left align-items-center">
                                            <div class="d-flex flex-column">
                                                <span class="user_name text-truncate text-body">
                                                    <span class="fw-bolder">{{checkIfEmpty(g.hoh_first)}} {{checkIfEmpty(g.hoh_last)}}</span>
                                                </span>
                                                <small class="emp_post text-primary">{{checkIfEmpty(g.hoh_phone)}}</small>
                                            </div>
                                        </div>
                                        -->
                                        {{i+1}}
                                    </td>
                                    <td>
                                        <div class="d-flex justify-content-left align-items-center">
                                            <div class="d-flex flex-column">
                                                <span class="user_name text-truncate text-body">
                                                    <span class="fw-bolder">{{checkIfEmpty(g.hoh_first)}}  {{checkIfEmpty(g.hoh_last)}}</span>
                                                </span>
                                                <small class="emp_post text-primary text-left"><span class="text-muted"><span class="badge badge-light-primary">Family Size:</span> {{g.family_size}} </span></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{checkIfEmpty(g.hod_mother)}}</td>
                                    <td>
                                        <div class="d-flex justify-content-left align-items-center">
                                            <div class="d-flex flex-column">
                                                <span class="user_name text-truncate text-body">
                                                    <span class="badge badge-light-success">{{g.allocated_net}}</span>
                                                </span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{g.geo_string}} </td>
                                    <td>{{displayDate(g.collected_date)}}</td>
                                    <td style="padding: 0.72rem !important" class="text-center" v-if="permission.permission_value ==3">
                                        <a href="javascript:void(0);" @click="showdistributionDetailsModal(i)" class="btn btn-primary btn-sm px-50 py-25"><i class="feather icon-eye"></i></a>
                                    </td>
                                </tr>
                                <tr v-if="tableData.length == 0"><td class="text-center pt-2" colspan="7"><small>No Data Found</small></td></tr>
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

            <!-- Modal to Show Distributions details starts-->
            <div class="modal modal-slide-in move modal-primary" id="distributionDetails" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-scrollable modal-xl">
                    <form class="add-new-user modal-content pt-0" @submit.stop.prevent="" id="state-form">
                        <button type="reset" class="close" @click="hidedistributionDetailsModal()" data-dismiss="modal">×</button>
                        <div class="modal-header mb-1">
                            <h5 class="modal-title font-weight-bolder" id="exampleModalLabel">Details</h5>
                        </div>                        

                        <div class="modal-body flex-grow-1 vertical-wizard">
                            <div class="info-container pt-25">
                                <h6>Household Details</h6>
                                <table class="table" id="distribution-list">
                                    <tr>

                                        <td class="user-detail-txt" colspan="2">
                                            <label class="d-block text-primary">Household Name</label>
                                            {{checkIfEmpty(tableDetails.hoh_first)}} {{checkIfEmpty(tableDetails.hoh_last)}}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="user-detail-txt">
                                            <label class="d-block text-primary">Phone No</label>
                                            {{checkIfEmpty(tableDetails.hoh_phone)}}
                                        </td>
                                        <td class="user-detail-txt">
                                            <label class="d-block text-primary">Gender</label>
                                            {{checkIfEmpty(tableDetails.hoh_gender)}}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="user-detail-txt">
                                            <label class="d-block text-primary">Family Size</label>
                                            {{checkIfEmpty(tableDetails.family_size)}}
                                        </td>
                                        <td class="user-detail-txt">
                                            <label class="d-block text-primary">Allocated Netcard</label>
                                            <span class="badge badge-light-primary">{{checkIfEmpty(tableDetails.allocated_net)}}</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="user-detail-txt">
                                            <label class="d-block text-primary">Sleeping Space</label>
                                            {{checkIfEmpty(tableDetails.sleeping_space)}}
                                        </td>
                                        <td class="user-detail-txt">
                                            <label class="d-block text-primary">Adult Male</label>
                                            <span class="badge badge-light-primary">{{checkIfEmpty(tableDetails.adult_male)}}</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="user-detail-txt">
                                            <label class="d-block text-primary">Adult Female</label>
                                            {{checkIfEmpty(tableDetails.adult_female)}}
                                        </td>
                                        <td class="user-detail-txt">
                                            <label class="d-block text-primary">Children</label>
                                            <span class="badge badge-light-primary">{{checkIfEmpty(tableDetails.children)}}</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="user-detail-txt" colspan="2">
                                            <label class="d-block text-primary">Mobilization Date</label>
                                            {{displayDate(tableDetails.mobilization_date)}}
                                        </td>
                                    </tr>

                                    <tr>
                                        <td class="user-detail-txt" colspan="2" style="width: 100% !important">
                                            <label class="d-block text-primary">Geo Location</label>
                                            {{checkIfEmpty(tableDetails.geo_string)}}
                                        </td>
                                    </tr>

                                </table>

                                
                                <table class="table card bg-light-default mt-2">
                                    <tr>
                                        <td class="user-detail-txt" style="width: 100% !important">
                                            <label class="d-block text-primary">e-Token Serial:</label>
                                        </td>
                                        <td class="user-detail-txt" style="width: 100% !important">
                                            <span class="badge badge-light-primary">{{ tableDetails.etoken_serial}}</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="user-detail-txt" style="width: 100% !important">
                                            <label class="d-block text-primary">e-Token Pin:</label>
                                        </td>
                                        <td class="user-detail-txt" style="width: 100% !important">
                                            <span class="badge badge-light-primary">{{ tableDetails.etoken_pin }}</span>
                                        </td>
                                    </tr>
                                </table>

                            </div>

                        </div>
                    </form>
                </div>
            </div>
            <!-- Modal to Show Distributions details Ends-->

            <!-- Change Geo Level Modal: Ends -->

        </div>
    `,
});

var vm = new Vue({
  el: "#app",
  data: {},
  methods: {},
  template: `
        <div>
            <page-body/>
        </div>
    `,
});
