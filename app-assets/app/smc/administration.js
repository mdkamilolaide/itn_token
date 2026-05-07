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
      permission: getPermission(per, "smc"),
      periodData: [],
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
        orderField: 10, //(Order fields)
        limitStart: 0, //(currentPage - 1) * perPage
        isNext: false,
        isPrev: false,
        aLength: [10, 20, 50, 100, 150, 200],
        filterParam: {
          periodid: "",
          periodTitle: "",
          beneficiary_id: "",
          eligibility: "",
          redose: "",
          created: "",
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
    this.getAllPeriodLists();
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
            "?qid=702&draw=" +
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
            "&gid=" +
            self.tableOptions.filterParam.geo_level_id +
            "&pid=" +
            self.tableOptions.filterParam.periodid +
            "&ise=" +
            self.tableOptions.filterParam.eligibility +
            "&isr=" +
            self.tableOptions.filterParam.redose +
            "&bid=" +
            self.tableOptions.filterParam.beneficiary_id +
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

      checkFill += this.tableOptions.filterParam.periodid != "" ? 1 : 0;
      checkFill += this.tableOptions.filterParam.beneficiary_id != "" ? 1 : 0;
      checkFill += this.tableOptions.filterParam.eligibility != "" ? 1 : 0;
      checkFill += this.tableOptions.filterParam.redose != "" ? 1 : 0;
      checkFill += this.tableOptions.filterParam.created != "" ? 1 : 0;

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

      if (column_name == "periodid") {
        this.tableOptions.filterParam.periodid =
          this.tableOptions.filterParam.periodTitle = "";
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
      this.tableOptions.filterParam.geo_level =
        this.tableOptions.filterParam.geo_level_id =
        this.tableOptions.filterParam.periodid =
        this.tableOptions.filterParam.beneficiary_id =
        this.tableOptions.filterParam.eligibility =
        this.tableOptions.filterParam.redose =
        this.tableOptions.filterParam.created =
        this.tableOptions.filterParam.periodTitle =
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
    getAllPeriodLists() {
      /*  Manages the loading of Geo Level data */
      var self = this;
      var url = common.DataService;
      overlay.show();

      axios
        .get(url + "?qid=1004")
        .then(function (response) {
          self.periodData = response.data.data; //All Data
          // console.log(self.periodData);
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
    setPeriodTitle(event) {
      this.tableOptions.filterParam.periodTitle =
        event.target.options[event.target.options.selectedIndex].text;
    },
    async exportDrugAdministration() {
      const { tableOptions } = this;
      const filterParams = tableOptions.filterParam;

      // Construct query parameters
      const queryParams = new URLSearchParams({
        draw: tableOptions.currentPage,
        order_column: tableOptions.orderField,
        length: tableOptions.perPage,
        start: tableOptions.limitStart,
        order_dir: tableOptions.orderDir,
        glv: filterParams.geo_level,
        gid: filterParams.geo_level_id,
        pid: filterParams.periodid,
        ise: filterParams.eligibility,
        isr: filterParams.redose,
        bid: filterParams.beneficiary_id,
        rda: filterParams.created,
      });

      const veriUrl = `qid=1124&${queryParams.toString()}`;
      const dlString = `qid=801&${queryParams.toString()}`;

      // Format date
      const now = new Date();
      const formattedDate = now
        .toLocaleString("en-GB", {
          day: "2-digit",
          month: "2-digit",
          year: "numeric",
          hour: "2-digit",
          minute: "2-digit",
        })
        .replace(/[\s,\/:]/g, "_");

      // Build filename
      const filename = [
        filterParams.geo_string || "",
        "Drug_Admin_List_",
        filterParams.periodid ? `_Visit_${filterParams.periodid}` : "",
        filterParams.eligibility ? `_Eligible_${filterParams.eligibility}` : "",
        filterParams.redose ? `_Redose_${filterParams.redose}` : "",
        "_",
        formattedDate,
      ].join("");

      overlay.show();

      try {
        // Count export data
        const countResponse = await $.ajax({
          url: common.DataService,
          type: "POST",
          data: veriUrl,
          dataType: "json",
        });

        const resultCount = parseInt(countResponse.total, 10);
        const downloadMax = common.ExportDownloadLimit;

        if (resultCount > downloadMax) {
          alert.Error(
            "Download Error",
            `Unable to download data because it has exceeded the download limit of ${downloadMax}`
          );
        } else if (resultCount === 0) {
          alert.Error("Download Error", "No data found");
        } else {
          alert.Info("DOWNLOADING...", `Downloading ${resultCount} record(s)`);

          // Download data
          const downloadResponse = await $.ajax({
            url: common.ExportService,
            type: "POST",
            data: dlString,
          });

          const exportData = JSON.parse(downloadResponse);
          Jhxlsx.export(exportData, { fileName: filename });
        }
      } catch (error) {
        console.error("Error during export:", error);
        alert.Error("Export Error", "An error occurred while exporting data.");
      } finally {
        overlay.hide();
      }
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
                        <li class="breadcrumb-item active">Drug Administration</li>
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
                    <button v-if="permission.permission_value >=2" type="button" class="btn btn-outline-primary round " data-toggle="tooltip" data-placement="top" title="Download" @click="exportDrugAdministration()">
                        <i class="feather icon-download"></i>               
                    </button>
                    <!--
                    <button class="btn btn-outline-primary round dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        Actions
                    </button>
                    <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                        <a class="dropdown-item" href="javascript:void(0);" @click="exportDrugAdministration()">Export Data</a>
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
                                        <label>Beneficiary ID</label>
                                        <input type="text" v-model="tableOptions.filterParam.beneficiary_id" class="form-control beneficiary-id" id="beneficiary-id" placeholder="Beneficiary ID" name="beneficiary_id" />
                                    </div>
                                </div>
                                <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                                    <div class="form-group">
                                        <label>Visit</label>
                                        <select v-model="tableOptions.filterParam.periodid" @change="setPeriodTitle($event)" class="form-control period" id="period">
                                          <option value="">All</option>
                                          <option v-for="(g, i) in periodData" :value="g.periodid">{{ g.title }}</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                                    <div class="form-group">
                                        <label>Eligibility</label>
                                        <select v-model="tableOptions.filterParam.eligibility" class="form-control period" id="period">
                                          <option value="">All</option>
                                          <option value="yes">Yes</option>
                                          <option value="no">No</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                                    <div class="form-group">
                                        <label>Redose Status</label>
                                        <select v-model="tableOptions.filterParam.redose" class="form-control period" id="period">
                                          <option value="">All</option>
                                          <option value="yes">Yes</option>
                                          <option value="no">No</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                                    <div class="form-group date_filter">
                                        <label>Registration Date</label>
                                        <input type="text" id="reg_date" v-model="tableOptions.filterParam.created" class="form-control reg_date date" placeholder="Registration Date" name="reg_date" />
                                    </div>
                                </div>

                                <div class="col-12 col-sm-6 col-md-4 col-lg-6">
                                    <div class="form-group">
                                        <label>Geo Location</label>
                                        <select class="select2 form-control" @change="setLocation()" placeholder="Geo Location" >
                                            <option value="">Select Geo Location</option>
                                            <option v-for="(g, i) in geoData" :value="i">{{ g.geo_string }}</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-12 col-sm-12 col-md-12 col-lg-3">
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
                                    <th @click="sort(2)">
                                        Beneficiary Name
                                        <i class="feather icon-chevron-up sort-up" :class="(tableOptions.orderField == 2 && tableOptions.orderDir =='asc')? 'active-sort': ''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 2 && tableOptions.orderDir =='desc')? 'active-sort': ''"></i>
                                    </th>
                                    <th @click="sort(4)">
                                       Date of Birth
                                        <i class="feather icon-chevron-up sort-up" :class="(tableOptions.orderField == 4 && tableOptions.orderDir =='asc')? 'active-sort': ''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 4 && tableOptions.orderDir =='desc')? 'active-sort': ''"></i>
                                    </th>
                                    <th @click="sort(5)">
                                        Drug
                                        <i class="feather icon-chevron-up sort-up" :class="(tableOptions.orderField == 5 && tableOptions.orderDir =='asc')? 'active-sort': ''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 5 && tableOptions.orderDir =='desc')? 'active-sort': ''"></i>
                                    </th>
                                    <th @click="sort(6)">
                                        Redose
                                        <i class="feather icon-chevron-up sort-up" :class="(tableOptions.orderField == 6 && tableOptions.orderDir =='asc')? 'active-sort': ''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 6 && tableOptions.orderDir =='desc')? 'active-sort': ''"></i>
                                    </th>
                                    <th @click="sort(8)" class="text-wrap text-center">
                                       Eligibility
                                        <i class="feather icon-chevron-up sort-up" :class="(tableOptions.orderField == 8 && tableOptions.orderDir =='asc')? 'active-sort': ''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 8 && tableOptions.orderDir =='desc')? 'active-sort': ''"></i>
                                    </th>
                                    <th @click="sort(1)">
                                       Visit
                                        <i class="feather icon-chevron-up sort-up" :class="(tableOptions.orderField == 1 && tableOptions.orderDir =='asc')? 'active-sort': ''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 1 && tableOptions.orderDir =='desc')? 'active-sort': ''"></i>
                                    </th>
                                    <th @click="sort(0)">
                                        Health Facility
                                        <i class="feather icon-chevron-up sort-up" :class="(tableOptions.orderField == 0 && tableOptions.orderDir =='asc')? 'active-sort': ''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 0 && tableOptions.orderDir =='desc')? 'active-sort': ''"></i>
                                    </th>
                                    <th @click="sort(10)">
                                        Created Date
                                        <i class="feather icon-chevron-up sort-up" :class="(tableOptions.orderField == 10 && tableOptions.orderDir =='asc')? 'active-sort': ''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 10 && tableOptions.orderDir =='desc')? 'active-sort': ''"></i>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="g in tableData">
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
                                    <td>
                                        <div class="d-flex justify-content-left align-items-center">
                                            <div class="d-flex flex-column">
                                                <span class="user_name text-truncate text-body">
                                                    <span class="fw-bolder">{{g.drug}}</span>
                                                </span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex justify-content-left align-items-center">
                                            <div class="d-flex flex-column">
                                                <span class="user_name text-truncate text-body">
                                                  <span class="badge badge-light-warning">{{g.redose}}</span>
                                                </span>
                                                <span class="fw-bolder">{{g.redose_reason}}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex justify-content-center text-center align-items-center">
                                            <div class="d-flex flex-column">
                                                <span class="user_name text-truncate text-body">
                                                  <span class="fw-bolder badge p-25" :class="g.eligibility =='NA'? 'badge-light-success':'badge-light-danger'">
                                                    <i class="feather" :class="g.eligibility =='NA'? 'icon-check-square':'icon-x-square'" data-toggle="tooltip" data-placement="top" title="Double Click on this Icon to Verify Bank Details"></i>
                                                  </span>
                                                  <small v-if="g.eligibility !='NA'" class="emp_post d-block text-danger"><span class="fw-bolder">{{ capitalize(g.not_eligible_reason) }}</span></small>
                                                </span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{ g.period }}</td>
                                    <td>
                                      <small class="fw-bolder">{{g.geo_string}}</small>
                                    </td>
                                    <td>{{displayDate(g.collected_date)}}</td>
                                    
                                </tr>
                                <tr v-if="tableData.length == 0"><td class="text-center pt-2" colspan="8"><small>No Data Found</small></td></tr>
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
