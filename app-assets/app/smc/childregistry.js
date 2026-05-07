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
                    <child_list/>
                </div>

            </div>
        </div>
    `,
});

// User List Page
Vue.component("child_list", {
  data: function () {
    return {
      url: common.BadgeService,
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
          hh_token: "",
          hoh_name: "",
          hoh_phone: "",
          beneficiary_id: "",
          name: "",
          gender: "",
          dob: "",
          created: "",
          updated: "",
          geo_level: "",
          geo_level_id: "",
          geo_string: "",
        },
      },
    };
  },
  mounted() {
    /* Manages event listening */
    this.getGeoLocation();
    this.loadTableData();
    EventBus.$on("g-event-update-user", this.reloadUserListOnUpdate);

    const initializeSelect2 = (selector, onChangeCallback) => {
      $(selector).each(function () {
        const $this = $(this);
        $this.wrap('<div class="position-relative"></div>');
        $this
          .select2({
            dropdownAutoWidth: true,
            width: "100%",
            dropdownParent: $this.parent(),
          })
          .on("change", function () {
            onChangeCallback(this.value);
          });
      });
    };

    initializeSelect2(".select2", this.setLocation);

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
            "?qid=701&draw=" +
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
            "&hht=" +
            self.tableOptions.filterParam.hh_token +
            "&gid=" +
            self.tableOptions.filterParam.geo_level_id +
            "&hhn=" +
            self.tableOptions.filterParam.hoh_name +
            "&hhp=" +
            self.tableOptions.filterParam.hoh_phone +
            "&chi=" +
            self.tableOptions.filterParam.beneficiary_id +
            "&chn=" +
            self.tableOptions.filterParam.name +
            "&dob=" +
            self.tableOptions.filterParam.dob +
            "&rda=" +
            self.tableOptions.filterParam.created
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
      checkFill += this.tableOptions.filterParam.geo_level != "" ? 1 : 0;
      checkFill += this.tableOptions.filterParam.geo_level_id != "" ? 1 : 0;

      checkFill += this.tableOptions.filterParam.hh_token != "" ? 1 : 0;
      checkFill += this.tableOptions.filterParam.hoh_name != "" ? 1 : 0;
      checkFill += this.tableOptions.filterParam.hoh_phone != "" ? 1 : 0;
      checkFill += this.tableOptions.filterParam.beneficiary_id != "" ? 1 : 0;
      checkFill += this.tableOptions.filterParam.name != "" ? 1 : 0;
      checkFill += this.tableOptions.filterParam.gender != "" ? 1 : 0;
      checkFill += this.tableOptions.filterParam.dob != "" ? 1 : 0;
      checkFill += this.tableOptions.filterParam.created != "" ? 1 : 0;
      checkFill += this.tableOptions.filterParam.updated != "" ? 1 : 0;

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
      // $("#dob_date")
      //   .flatpickr({
      //     altInput: true,
      //     altFormat: "F j, Y",
      //     dateFormat: "Y-m-d",
      //   })
      //   .clear();
      this.filters = false;

      this.tableOptions.filterParam.geo_level =
        this.tableOptions.filterParam.geo_level_id =
        this.tableOptions.filterParam.hh_token =
        this.tableOptions.filterParam.hoh_name =
        this.tableOptions.filterParam.hoh_phone =
        this.tableOptions.filterParam.beneficiary_id =
        this.tableOptions.filterParam.name =
        this.tableOptions.filterParam.gender =
        this.tableOptions.filterParam.dob =
        this.tableOptions.filterParam.created =
        this.tableOptions.filterParam.updated =
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
    displayDayMonthYear(d) {
      let date = new Date(d);
      let options = {
        year: "numeric",
        month: "long",
        day: "numeric",
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
        "qid=701&draw=" +
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
        self.tableOptions.filterParam.dob_date;

      var dlString =
        "qid=701&draw=" +
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
        self.tableOptions.filterParam.dob_date;

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
    calculateTotalMonths(dateOfBirth) {
      // Parse the date of birth into a Date object
      const dob = new Date(dateOfBirth);

      // Get the current date
      const currentDate = new Date();

      // Calculate the difference in months
      let months = (currentDate.getFullYear() - dob.getFullYear()) * 12;
      months -= dob.getMonth() + 1; // Subtract 1 because months are zero-indexed
      months += currentDate.getMonth() + 1; // Add 1 to account for current month

      // Handle cases where the birthdate might be in the future
      if (currentDate.getDate() < dob.getDate()) {
        months--;
      }
      let suffix = "";
      if (months > 1) {
        suffix = "s";
      }
      return months + " Month" + suffix + " Old";
    },
  },

  computed: {},
  template: `

        <div class="row" id="basic-table">
            <div class="col-md-8 col-sm-12 col-12 mb-0">
                <h2 class="content-header-title header-txt float-left mb-0">SMC</h2>
                <div class="breadcrumb-wrapper">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../smc">Home</a></li>
                        <li class="breadcrumb-item active">Child Registry</li>
                    </ol>
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
                    <!--
                    <button class="btn btn-outline-primary round dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        Actions
                    </button>
                    <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                        <a class="dropdown-item" href="javascript:void(0);" @click="exportMobilization()">Export Data</a>
                    </div>
                    -->
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
                                <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                                    <div class="form-group">
                                        <label>Head of Household Token</label>
                                        <input type="text" v-model="tableOptions.filterParam.hh_token" class="form-control hh-token" id="hh-token" placeholder="Household Token" name="hh_token" />
                                    </div>
                                </div>

                                <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                                    <div class="form-group">
                                        <label>Head of Household Name</label>
                                        <input type="text" v-model="tableOptions.filterParam.hoh_name" class="form-control hh-name" id="hh-name" placeholder="Household Name" name="hoh_name" />
                                    </div>
                                </div>

                                <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                                    <div class="form-group">
                                        <label>Household Phone No</label>
                                        <input type="text" v-model="tableOptions.filterParam.hoh_phone" class="form-control hh-phone-no" id="hh-phone-no" placeholder="Household Phone No" name="hoh_phone_no" />
                                    </div>
                                </div>

                                <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                                    <div class="form-group">
                                        <label>Beneficiary ID</label>
                                        <input type="text" v-model="tableOptions.filterParam.beneficiary_id" class="form-control beneficiary-id" id="beneficiary-id" placeholder="Beneficiary ID" name="beneficiary_id" />
                                    </div>
                                </div>

                                <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                                    <div class="form-group">
                                        <label>Beneficiary Name</label>
                                        <input type="text" v-model="tableOptions.filterParam.name" class="form-control name" id="name" placeholder="Beneficiary Name" name="name" />
                                    </div>
                                </div>



                                <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                                    <div class="form-group date_filter">
                                        <label>Registration Date</label>
                                        <input type="text" id="reg_date" v-model="tableOptions.filterParam.created" class="form-control reg_date date" placeholder="Registration Date" name="reg_date" />
                                    </div>
                                </div>

                                <div class="col-12 col-sm-8 col-md-9 col-lg-4">
                                    <div class="form-group">
                                        <label>Geo Location</label>
                                        <select class="select2 form-control" @change="setLocation()" placeholder="Geo Location" >
                                            <option value="">Select Geo Location</option>
                                            <option v-for="(g, i) in geoData" :value="i">{{ g.geo_string }}</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-12 col-sm-4 col-md-3 col-lg-2">
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
                                    <th @click="sort(1)">
                                        Head of Household
                                        <i class="feather icon-chevron-up sort-up" :class="(tableOptions.orderField == 1 && tableOptions.orderDir =='asc')? 'active-sort': ''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 1 && tableOptions.orderDir =='desc')? 'active-sort': ''"></i>
                                    </th>
                                    <th @click="sort(4)">
                                        Child Name
                                        <i class="feather icon-chevron-up sort-up" :class="(tableOptions.orderField == 4 && tableOptions.orderDir =='asc')? 'active-sort': ''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 4 && tableOptions.orderDir =='desc')? 'active-sort': ''"></i>
                                    </th>
                                    <th @click="sort(6)">
                                       Date of Birth
                                        <i class="feather icon-chevron-up sort-up" :class="(tableOptions.orderField == 6 && tableOptions.orderDir =='asc')? 'active-sort': ''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 6 && tableOptions.orderDir =='desc')? 'active-sort': ''"></i>
                                    </th>
                                    <th @click="sort(5)">
                                       Gender
                                        <i class="feather icon-chevron-up sort-up" :class="(tableOptions.orderField == 5 && tableOptions.orderDir =='asc')? 'active-sort': ''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 5 && tableOptions.orderDir =='desc')? 'active-sort': ''"></i>
                                    </th>
                                    <th @click="sort(9)">
                                        Health Facility
                                        <i class="feather icon-chevron-up sort-up" :class="(tableOptions.orderField == 9 && tableOptions.orderDir =='asc')? 'active-sort': ''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 9 && tableOptions.orderDir =='desc')? 'active-sort': ''"></i>
                                    </th>
                                    <th @click="sort(7)">
                                        Created Date
                                        <i class="feather icon-chevron-up sort-up" :class="(tableOptions.orderField == 7 && tableOptions.orderDir =='asc')? 'active-sort': ''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 7 && tableOptions.orderDir =='desc')? 'active-sort': ''"></i>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- <tr v-for="g in tableData" :class="checkedBg(g.pick)"> -->
                                <tr v-for="g in tableData">
                                    <td>
                                        <div class="d-flex justify-content-left align-items-center">
                                            <div class="d-flex flex-column">
                                                <span class="user_name text-truncate text-body">
                                                    <span class="fw-bolder">{{g.hoh_name}}</span>
                                                </span>
                                                <small class="emp_post text-primary">{{g.hoh_phone}}</small>
                                                <span class="badge badge-light-success">{{g.hh_token}}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex justify-content-left align-items-center">
                                            <div class="d-flex flex-column">
                                                <span class="user_name text-truncate text-body">
                                                    <span class="fw-bolder">{{g.name}}</span>
                                                </span>
                                                <small class="emp_post text-primary text-left">
                                                    <span class="badge badge-light-primary">{{g.beneficiary_id}}</span>
                                                    <!-- <span class="text-muted">  </span> -->
                                                </small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex justify-content-left align-items-center">
                                            <div class="d-flex flex-column">
                                                <span class="user_name text-truncate text-body">
                                                    <span class="fw-bolder">{{displayDayMonthYear(g.dob)}}</span>
                                                </span>
                                                <span class="badge badge-light-success">{{ calculateTotalMonths(g.dob) }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{ capitalize(g.gender) }}</td>
                                    <td>
                                        <div class="d-flex justify-content-left align-items-center">
                                            <div class="d-flex flex-column">
                                                <span class="user_name text-truncate text-body">
                                                    <span class="fw-bolder">{{g.geo_name}}</span>
                                                </span>
                                                <small class="emp_post text-primary"><span class="text-muted">{{g.geo_string}} </span></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{displayDayMonthYear(g.created)}}</td>
                                    
                                </tr>
                                <tr v-if="tableData.length == 0"><td class="text-center pt-2" colspan="6"><small>No Data Found</small></td></tr>
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
