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
                    <wallet_list/>
                </div>

                <div v-show="page == 'detail'">
                    <mobilization_details/>
                </div>

            </div>
        </div>
    `,
});

// User List Page
Vue.component("wallet_list", {
  data: function () {
    return {
      url: common.BadgeService,
      permission: getPermission(per, "enetcard"),
      tableData: [],
      geoData: [],
      userRole: {
        currentUserRole: "",
        currentUserid: "",
      },
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
          "?qid=205&draw=" +
          self.tableOptions.currentPage +
          "&order_column=" +
          self.tableOptions.orderField +
          "&length=" +
          self.tableOptions.perPage +
          "&start=" +
          self.tableOptions.limitStart +
          "&order_dir=" +
          self.tableOptions.orderDir +
          "&gl=" +
          self.tableOptions.filterParam.geo_level +
          "&lgid=" +
          self.tableOptions.filterParam.loginid +
          "&glid=" +
          self.tableOptions.filterParam.geo_level_id +
          "&mdt=" +
          self.tableOptions.filterParam.mobilization_date
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
        "qid=301&draw=" +
        self.tableOptions.currentPage +
        "&order_column=" +
        self.tableOptions.orderField +
        "&length=" +
        self.tableOptions.perPage +
        "&start=" +
        self.tableOptions.limitStart +
        "&order_dir=" +
        self.tableOptions.orderDir +
        "&gl=" +
        self.tableOptions.filterParam.geo_level +
        "&lgid=" +
        self.tableOptions.filterParam.loginid +
        "&glid=" +
        self.tableOptions.filterParam.geo_level_id +
        "&mdt=" +
        self.tableOptions.filterParam.mobilization_date;

      var dlString =
        "qid=301&draw=" +
        self.tableOptions.currentPage +
        "&order_column=" +
        self.tableOptions.orderField +
        "&length=" +
        self.tableOptions.perPage +
        "&start=" +
        self.tableOptions.limitStart +
        "&order_dir=" +
        self.tableOptions.orderDir +
        "&gl=" +
        self.tableOptions.filterParam.geo_level +
        "&lgid=" +
        self.tableOptions.filterParam.loginid +
        "&glid=" +
        self.tableOptions.filterParam.geo_level_id +
        "&mdt=" +
        self.tableOptions.filterParam.mobilization_date;

      var filename =
        (this.tableOptions.filterParam.geo_string
          ? this.tableOptions.filterParam.geo_string
          : "Recent ") +
        " " +
        (this.tableOptions.filterParam.loginid
          ? this.tableOptions.filterParam.loginid
          : "Recent ") +
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
  },
  computed: {},
  template: `

        <div class="row" id="basic-table">
            <div class="col-md-8 col-sm-12 col-12 mb-0">
                <h2 class="content-header-title header-txt float-left mb-0">e-Netcard</h2>
                <div class="breadcrumb-wrapper">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../mobilization">Home</a></li>
                        <li class="breadcrumb-item active">e-Netcard Pushed</li>
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
                        <a class="dropdown-item" v-if="permission.permission_value >=2" href="javascript:void(0);" @click="exportMobilization()">Export Data</a>
                    </div>
                </div>
            </div>
            <div class="col-12" v-if="filters">
                <div class="col-12 filter-bar">
                    <span class="badge badge-dark filter-box" @click="removeSingleFilter(i)" v-for="(filterParam, i) in tableOptions.filterParam" v-if="filterParam.length > 0">{{capitalize(i)}}: {{filterParam}} <i class="feather icon-x"></i></span>
                    <a href="#" class="float-right clear-filter" @click="clearAllFilter()">Clear</a>
                </div>
            </div>

            <div class="col-12 mt-1">
                <div class="card custom-select-down">
                    <div class="card-body py-1" v-show="filterState">
                        <form id="filterForm">

                            <div class="row">
                                <div class="col-12 col-sm-6 col-md-6 col-lg-2">
                                    <div class="form-group">
                                        <label>Mobilizer Login ID</label>
                                        <input type="text" v-model="tableOptions.filterParam.loginid" class="form-control login-id" id="login-id" placeholder="Login ID" name="loginid" />
                                    </div>
                                </div>

                                <div class="col-12 col-sm-6 col-md-6 col-lg-4">
                                    <div class="form-group">
                                        <label>Geo Location</label>
                                        <select class="select2 form-control" @change="setLocation()" placeholder="Geo Location">
                                            <option value="">Select Geo Location</option>
                                            <option v-for="(g, i) in geoData" :value="i">{{ g.geo_string }}</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-12 col-sm-6 col-md-6 col-lg-3">
                                    <div class="form-group date_filter">
                                        <label>Mobilization Date</label>
                                        <input type="text" id="mobilization_date" v-model="tableOptions.filterParam.mobilization_date" class="form-control mobilization_date date" placeholder="Mobilization Date" name="mobilization_date" />
                                    </div>
                                </div>
                                <div class="col-12 col-sm-6 col-md-6 col-lg-3">
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
                                    <th @click="sort(0)" style="padding-right: 2px !important;">
                                        #
                                        <i class="feather icon-chevron-up sort-up" :class="(tableOptions.orderField == 0 && tableOptions.orderDir =='asc')? 'active-sort': ''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 0 && tableOptions.orderDir =='desc')? 'active-sort': ''"></i>
                                    </th>
                                    
                                    <th @click="sort(1)">
                                        Household Mobilizer
                                        <i class="feather icon-chevron-up sort-up" :class="(tableOptions.orderField == 1 && tableOptions.orderDir =='asc')? 'active-sort': ''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 1 && tableOptions.orderDir =='desc')? 'active-sort': ''"></i>
                                    </th>
                                    <th @click="sort(4)">
                                        Device Serial
                                        <i class="feather icon-chevron-up sort-up" :class="(tableOptions.orderField == 4 && tableOptions.orderDir =='asc')? 'active-sort': ''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 4 && tableOptions.orderDir =='desc')? 'active-sort': ''"></i>
                                    </th>
                                    <th @click="sort(5)">
                                        Amount
                                        <i class="feather icon-chevron-up sort-up" :class="(tableOptions.orderField == 5 && tableOptions.orderDir =='asc')? 'active-sort': ''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 5 && tableOptions.orderDir =='desc')? 'active-sort': ''"></i>
                                    </th>
                                    <th @click="sort(6)">
                                        Location
                                        <i class="feather icon-chevron-up sort-up" :class="(tableOptions.orderField == 6 && tableOptions.orderDir =='asc')? 'active-sort': ''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 6 && tableOptions.orderDir =='desc')? 'active-sort': ''"></i>
                                    </th> 
                                    <th @click="sort(3)">
                                        Phone No
                                        <i class="feather icon-chevron-up sort-up" :class="(tableOptions.orderField == 3 && tableOptions.orderDir =='asc')? 'active-sort': ''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 3 && tableOptions.orderDir =='desc')? 'active-sort': ''"></i>
                                    </th>
                                    <th @click="sort(7)">
                                        Mobilized Date
                                        <i class="feather icon-chevron-up sort-up" :class="(tableOptions.orderField == 7 && tableOptions.orderDir =='asc')? 'active-sort': ''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 7 && tableOptions.orderDir =='desc')? 'active-sort': ''"></i>
                                    </th> 
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="g in tableData">
                                    <td>{{g.id}}</td>
                                    <td>
                                        <div class="d-flex justify-content-left align-items-center">
                                            <div class="d-flex flex-column">
                                                <span class="user_name text-truncate text-body">
                                                    <span class="fw-bolder">{{g.fullname}}</span>
                                                </span>
                                                <small class="emp_post text-primary">{{g.loginid}}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{g.device_serial}}</td>
                                    <td>
                                        <span class="badge badge-light-success">{{g.amount}}</span>
                                    </td>
                                    <td>{{g.geo_string}}</td>
                                    <td>{{g.phone}}</td>
                                    <td>{{displayDate(g.created)}}</td>
                                    
                                </tr>
                                <tr v-if="tableData.length == 0"><td class="text-center pt-2" colspan="7"><small>No Wallet Data</small></td></tr>
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

            <!-- Change Geo Level Modal: Ends -->

        </div>
    `,
});

// User Details Page
Vue.component("mobilization_details", {
  data: function () {
    return {
      userid: "",
      userDetails: true,
      user_status: "",
      bankListData: [],
      roleListData: [],
      userData: {
        baseData: [],
        financeData: [],
        identityData: [],
        roleData: [],
      },
    };
  },
  mounted() {
    EventBus.$on("g-event-goto-page", this.gotoPageHandler);
    // this.getRoleList();
    // this.getBankLists();
  },
  methods: {
    gotoPageHandler(data) {
      this.userDetails = true;
      this.userid = data.userid;
      this.user_status = data.user_status;
      this.getUserDetails();
    },
    goToList() {
      EventBus.$emit("g-event-goto-page", {
        page: "list",
        userid: this.userid,
      });
    },
    discardUpdate() {
      var self = this;
      $.confirm({
        title: "WARNING!",
        content:
          "<p>Are you sure you want to discard the changes? </p><br>Discarding the changes means you will loss all changes made",
        buttons: {
          delete: {
            text: "Discard Changes",
            btnClass: "btn btn-warning mr-1",
            action: function () {
              //Attempt Delete
              self.getUserDetails();
              self.userDetails = true;
              overlay.hide();
            },
          },
          close: {
            text: "Cancel",
            btnClass: "btn btn-outline-secondary",
            action: function () {
              // Do nothing
              overlay.hide();
            },
          },
        },
      });
    },
    getUserDetails() {
      /*  Get User Details using userid */
      var self = this;
      var url = common.DataService;
      overlay.show();

      axios
        .get(url + "?qid=005&e=" + self.userid)
        .then(function (response) {
          self.userData.baseData = response.data.base[0]; //All Data
          self.userData.financeData = response.data.finance[0]; //Total Records
          self.userData.identityData = response.data.identity[0]; //Total Records
          self.userData.roleData = response.data.role[0]; //Total Records
          overlay.hide();
        })
        .catch(function (error) {
          overlay.hide();
          alert.Error("ERROR", error);
        });
    },
    getBankLists() {
      /*  Get User Details using userid */
      var self = this;
      var url = common.DataService;
      overlay.show();

      axios
        .get(url + "?qid=gen008")
        .then(function (response) {
          self.bankListData = response.data.data; //All Data
          overlay.hide();
        })
        .catch(function (error) {
          overlay.hide();
          alert.Error("ERROR", error);
        });
    },
    updateUserProfile() {
      var updateFormData = {
        userid: this.userid,
        roleid: this.userData.baseData.roleid,
        first: this.userData.identityData.first,
        middle: this.userData.identityData.middle,
        last: this.userData.identityData.last,
        gender: this.userData.identityData.gender,
        email: this.userData.identityData.email,
        phone: this.userData.identityData.phone,
        bank_name: this.userData.financeData.bank_name,
        account_name: this.userData.financeData.account_name,
        account_no: this.userData.financeData.account_no,
        bank_code: this.userData.financeData.bank_code,
        bio_feature: "",
      };
      /*  Get User Details using userid */
      var self = this;
      var url = common.DataService;
      overlay.show();
      $.confirm({
        title: "WARNING!",
        content:
          "<p>Are you sure you want to Update the User? </p><br>Updating the User profile means you are changing the user permissions and details",
        buttons: {
          delete: {
            text: "Update Details",
            btnClass: "btn btn-warning mr-1",
            action: function () {
              //Attempt Delete
              axios
                .post(url + "?qid=006", JSON.stringify(updateFormData))
                .then(function (response) {
                  if (response.data.result_code == "200") {
                    overlay.hide();
                    EventBus.$emit("g-event-update-user", {});
                    self.userDetails = true;
                    alert.Success(
                      "SUCCESS",
                      response.data.total + " User Updated"
                    );
                  } else {
                    overlay.hide();
                    alert.Error("ERROR", "User De/Activation failed");
                  }
                })
                .catch(function (error) {
                  overlay.hide();
                  alert.Error("ERROR", error);
                });
            },
          },
          close: {
            text: "Cancel",
            btnClass: "btn btn-outline-secondary",
            action: function () {
              // Do nothing
              overlay.hide();
            },
          },
        },
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
    checkIfEmpty(data) {
      if (data === null || data === "") {
        return "Nil";
      } else {
        return data;
      }
    },
    userActivationDeactivation(actionid) {
      var self = this;
      let selectedId = [actionid];

      var url = common.DataService;
      overlay.show();

      axios
        .post(url + "?qid=001", JSON.stringify(selectedId))
        .then(function (response) {
          overlay.hide();
          if (response.data.result_code == "200") {
            EventBus.$emit("g-event-update-user", {});
            self.user_status == "1"
              ? (self.user_status = 0)
              : (self.user_status = 1);
            alert.Success("SUCCESS", "User De/Activation Successful");
          } else {
            alert.Error("ERROR", "User De/Activation failed");
          }
          overlay.hide();
        })
        .catch(function (error) {
          overlay.hide();
          alert.Error("ERROR", error);
        });
    },
    changeRole(event) {
      this.userData.baseData.role =
        event.target.options[event.target.options.selectedIndex].text;
    },
    changeBank(event) {
      this.userData.financeData.bank_name =
        event.target.options[event.target.options.selectedIndex].text;
    },
    downloadBadge(userid) {
      overlay.show();
      window.open(common.BadgeService + "?qid=002&e=" + userid, "_parent");
      overlay.hide();
    },
  },
  template: `
        <div class="row">
            <div class="col-md-12 col-sm-12 col-12 mb-1">
                <h2 class="content-header-title header-txt float-left mb-0">Users</h2>
                <div class="breadcrumb-wrapper">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../users">Home</a></li>
                        <li class="breadcrumb-item"><a href="javascript:void(0);" @click="goToList()">Users List</a></li>
                        <li v-if="userDetails" class="breadcrumb-item active">User Details</li>
                        <li v-else class="breadcrumb-item active">User Update</li>
                    </ol>
                </div>
            </div>

            <!-- User Sidebar -->
            <div class="col-xl-4 col-lg-5 col-md-5 order-1 order-md-0 sidebar-sticky">
                <!-- User Card -->
                <div class="card">
                    <div class="card-body">
                        <div class="user-avatar-section">
                            <div class="d-flex align-items-center flex-column">
                                <img class="img-fluid rounded mt-3 mb-2" src="../app-assets/images/avatar.png" height="110" width="110" alt="User avatar">
                                <div class="user-info text-center">
                                    <h4 v-html="userData.baseData.loginid"></h4>
                                    <span class="badge bg-light-primary" v-html="userData.baseData.role"></span>
                                </div>
                            </div>
                        </div>
                        <div class="fw-bolder border-bottom font-small-2 pb-20 mb-1 mt-1 text-center">{{userData.baseData.geo_string}}</div>
                        <div class="info-container">
                            <ul class="list-unstyled pl-2">
                                <li class="mb-75">
                                    <span class="fw-bolder me-25">Username:</span>
                                    <span v-html="userData.baseData.username"></span>
                                </li>
                                <li class="mb-75">
                                    <span class="fw-bolder me-25">User Group:</span>
                                    <span v-html="userData.baseData.user_group"></span>
                                </li>
                                <li class="mb-75">
                                    <span class="fw-bolder me-25">Status:</span>
                                    <span class="badge " :class="user_status==1? 'bg-light-success' : 'bg-light-danger'">{{user_status==1? 'Active' : 'Inactive'}}</span>
                                </li>
                            </ul>

                            <div class="justify-content-center pt-2 form-group">
                                <button class="btn btn-primary mb-1 form-control suspend-user waves-effect" @click="downloadBadge(userid)"><i class="feather icon-download"></i> Download Badge</button>
                                <button v-if="userDetails" @click="userDetails = false" class="btn btn-outline-primary mb-1 form-control suspend-user waves-effect"><i class="feather icon-edit-2"></i>  Edit</button>
                                <button class="btn form-control suspend-user waves-effect" :class="user_status== 1 ? 'btn-danger' : 'btn-success'" @click="userActivationDeactivation(userid)"><i class="feather" :class="user_status== 1 ? 'icon-user-x' : 'icon-user-check'"></i> {{user_status==1? ' Deactivate' : ' Activate'}}</button>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- /User Left Sidebar -->
            </div>
            <!--/ User Sidebar -->

            <!-- User Content -->
            <div class="col-xl-8 col-lg-7 col-md-7 order-0 order-md-1">
                <!-- User Details: Start -->
                <div v-if="userDetails">
                    <!-- User Details -->
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title mb-50">Details</h4>
                            <button class="btn btn-primary btn-sm waves-effect waves-float waves-light" @click="userDetails = false">
                                <i class="feather icon-edit-2"></i> <span> Edit</span>
                            </button>
                        </div>
                        <div class="card-body row">
                            <div class="col-12">
                                <table class="table">
                                    <tr>

                                        <td class="user-detail-txt">
                                            <label class="d-block text-primary">Firstname</label>
                                            {{checkIfEmpty(userData.identityData.first)}}
                                        </td>
                                        <td class="user-detail-txt">
                                            <label class="d-block text-primary">Middle</label>
                                            {{checkIfEmpty(userData.identityData.middle)}}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="user-detail-txt">
                                            <label class="d-block text-primary">Lastname</label>
                                            {{checkIfEmpty(userData.identityData.last)}}
                                        </td>
                                        <td class="user-detail-txt">
                                            <label class="d-block text-primary">Gender</label>
                                            {{checkIfEmpty(userData.identityData.gender)}}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="user-detail-txt">
                                            <label class="d-block text-primary">Phone No</label>
                                            {{checkIfEmpty(userData.identityData.phone)}}
                                        </td>
                                        <td class="user-detail-txt">
                                            <label class="d-block text-primary">Email</label>
                                            {{checkIfEmpty(userData.identityData.email)}}
                                        </td>
                                    </tr>

                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title mb-50">Finance</h4>
                        </div>
                        <div class="card-body row">
                            <div class="col-12">
                                <table class="table">
                                    <tr>
                                        <td colspan="2" class="user-detail-txt">
                                            <label class="d-block text-primary">Account Name</label>
                                            {{checkIfEmpty(userData.financeData.account_name)}}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="user-detail-txt">
                                            <label class="d-block text-primary">Account Number</label>
                                            {{checkIfEmpty(userData.financeData.account_no)}}
                                        </td>
                                        <td class="user-detail-txt">
                                            <label class="d-block text-primary">Bank Name</label>
                                            {{checkIfEmpty(userData.financeData.bank_name)}}
                                        </td>
                                    </tr>

                                </table>
                            </div>
                        </div>
                    </div> 

                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title mb-50">Role</h4>
                        </div>
                        <div class="card-body row">
                            <div class="col-12">
                                <table class="table">
                                    <tr>
                                        <td colspan="2" class="user-detail-txt">
                                            <label class="d-block text-primary">Role</label>
                                            {{checkIfEmpty(userData.baseData.role)}}
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div> 
              
                </div>
                <!-- User Details: End -->

                <!-- User Details Form -->
                <form method="POST" @submit.stop.prevent="updateUserProfile()" v-else>
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title mb-50">Details</h4>
                        </div>
                        <div class="card-body row">
                            <div class="col-6">
                                <div class="form-group">
                                    <label>First Name</label>
                                    <input type="text" id="firstname" v-model="userData.identityData.first" class="form-control firstname" placeholder="First Name" name="firstname" />
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label>Middle Name</label>
                                    <input type="text" id="middlename" v-model="userData.identityData.middle" class="form-control middlename" placeholder="Middle Name" name="middlename" />
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label>Lastname</label>
                                    <input type="text" id="lastname" v-model="userData.identityData.last" class="form-control lastname" placeholder="Last Name" name="lastname" />
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label>Gender</label>
                                    <select name="gender" v-model="userData.identityData.gender" class="form-control">
                                        <option :selected="userData.identityData.gender == 'Male'">Male</option>
                                        <option :selected="userData.identityData.gender == 'Female'">Female</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label>Phone No</label>
                                    <input type="text" id="phoneno" v-model="userData.identityData.phone" class="form-control phoneno" placeholder="Phone No" name="phoneno" />
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label>Email</label>
                                    <input type="email" id="email" v-model="userData.identityData.email" class="form-control email" placeholder="Email" name="email" />
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- / User Details Form -->

                    <!--  Finance -->
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title mb-50">Finance</h4>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-12">
                                    <div class="form-group">
                                        <label>Account Name</label>
                                        <input type="text" id="account_name" v-model="userData.financeData.account_name" class="form-control account_name" placeholder="Account Name" name="account_name" />
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="form-group">
                                        <label>Account Number</label>
                                        <input type="text" id="account_no" v-model="userData.financeData.account_no" class="form-control account_no" placeholder="Account Number" name="account_no" />
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="form-group">
                                        <label>Bank Name</label>
                                        <select name="bank_code" v-model="userData.financeData.bank_code" @change="changeBank($event)" class="form-control bank_code select2">
                                            <option v-for="b in bankListData" :value="b.bank_code" :selected="b.bank_code == userData.financeData.bank_code">{{b.bank_name}}</option>
                                        </select>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>

                    <!--  User Role and Priviledge -->
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title mb-50">Role</h4>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-12">
                                    <div class="form-group">
                                        <label>Role</label>
                                        <select name="role" v-model="userData.baseData.roleid" @change="changeRole($event)" class="form-control role select2">
                                            <option v-for="r in roleListData" :value="r.roleid" :key="r.roleid" :selected="r.roleid == userData.baseData.roleid">{{r.role}}</option>
                                        </select>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-6">
                            <button type="button" @click="discardUpdate()" class="btn btn-outline-secondary form-control mt-2 waves-effect">Cancel</button>
                        </div>
                        <div class="col-6">
                            <button class="btn btn-primary form-control mt-2 waves-effect waves-float waves-light">Update Details</button>
                        </div>
                    </div>


                </form>
                <!--/ Billing Finance -->
            </div>
            <!--/ User Content -->
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
