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
                    <referral_list/>
                </div>

            </div>
        </div>
    `,
});

// User List Page
Vue.component("referral_list", {
  data: function () {
    return {
      url: common.BadgeService,
      tableData: [],
      permission: getPermission(per, "smc"),
      statData: {
        referrals: 0,
        attended: 0,
        period: 0,
      },
      statProgessBarStatus: "progress-bar-default",
      geoData: [],
      periodData: [],
      userRole: {
        currentUserRole: "",
        currentUserid: "",
      },
      checkIfFilterOn: false,
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
          periodid: [],

          visitTitle: "",
          attendedDate: "",
          geo_level: "",
          geo_level_id: "",
          geo_string: "",
          referralStatus: "",
        },
      },
    };
  },
  mounted() {
    /* Manages event listening */
    this.getGeoLocation();
    this.getAllPeriodLists();
    this.loadTableData();
    //EventBus.$on("g-event-update-user", this.reloadUserListOnUpdate);

    const initializeSelect = (
      selector,
      onChangeCallback,
      multiple = false,
      placeholder = "Select an option"
    ) => {
      $(selector).each(function () {
        const $this = $(this);
        $this.wrap('<div class="position-relative"></div>');
        $this
          .select2({
            multiple: multiple,
            dropdownAutoWidth: true,
            width: "100%",
            dropdownParent: $this.parent(),
            placeholder: placeholder, // Set the placeholder here
          })
          .on("change", function () {
            onChangeCallback($(this).val());
          });
      });
    };

    initializeSelect(".select2", this.setLocation, false);
    initializeSelect(".period", this.setPeriodTitle, true, "Select Options");

    $(".select2-selection__arrow").html(
      '<i class="feather icon-chevron-down"></i>'
    );
  },
  methods: {
    refreshData() {
      this.getGeoLocation();
      this.getAllPeriodLists();
      this.loadTableData();
    },
    async loadTableData() {
      /* Manages the loading of table data */
      const url = common.TableService;
      const urlDataService = common.DataService;
      overlay.show();

      const {
        currentPage,
        orderField,
        perPage,
        limitStart,
        orderDir,
        filterParam,
      } = this.tableOptions;
      const periodIdsCopy = [...filterParam.periodid];
      const periodIds = this.joinWithCommaAnd(periodIdsCopy, true);

      const { geo_level_id, geo_level, referralStatus } = filterParam;

      const endpoints = [
        `${url}?qid=703&draw=${currentPage}&order_column=${orderField}&length=${perPage}&start=${limitStart}&order_dir=${orderDir}&pid=${periodIds}&gid=${geo_level_id}&gl=${geo_level}&atd=${referralStatus}`,
        `${urlDataService}?qid=1110&pid=${periodIds}&gid=${geo_level_id}&gl=${geo_level}&atd=${referralStatus}`,
      ];

      try {
        const [tableResponse, statsResponse] = await Promise.all(
          endpoints.map((endpoint) => axios.get(endpoint))
        );
        this.tableData = tableResponse.data.data; // All TableData
        this.statData = statsResponse.data.data[0]; // Get all Card Statistics

        this.tableOptions.total = tableResponse.data.recordsTotal; // Total Records
        if (this.tableOptions.currentPage === 1) {
          this.paginationDefault();
        }
      } catch (error) {
        console.error("Error loading table data:", error);
      } finally {
        overlay.hide();
      }
    },
    toggleFilter() {
      // Manages the toggling of a filter box
      if (!this.filterState && !this.checkIfFilterOn) {
        this.filters = false;
      }
      this.filterState = !this.filterState;
      return this.filterState;
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

      checkFill += this.tableOptions.filterParam.periodid.length > 0 ? 1 : 0;
      checkFill += this.tableOptions.filterParam.referralStatus != "" ? 1 : 0;

      if (checkFill > 0) {
        this.toggleFilter();
        this.filters = this.checkIfFilterOn = true;
        this.paginationDefault();
        this.loadTableData();
      } else {
        alert.Error("ERROR", "Invalid required data");
        return;
      }
    },
    removeSingleFilter(column_name) {
      const filterParam = this.tableOptions.filterParam;

      // Clear the specified filter
      if (Array.isArray(filterParam[column_name])) {
        filterParam[column_name] = [];
      } else {
        filterParam[column_name] = "";
      }

      // Handle special cases for geo filters and visitTitle
      if (["geo_level", "geo_level_id", "geo_string"].includes(column_name)) {
        filterParam.geo_level = "";
        filterParam.geo_level_id = "";
        $(".select2").val("").trigger("change");
      }

      if (column_name === "visitTitle") {
        filterParam.periodid = [];
        filterParam.visitTitle = "";

        $(".period").val("").trigger("change");
      }

      /*
      if (column_name === "attendedDate") {
        this.clearFlatpickr("date");
      }
      */

      // Check if there are any active filters
      const hasActiveFilters = Object.values(filterParam).some((value) =>
        Array.isArray(value) ? value.length > 0 : value !== ""
      );

      this.filters = this.checkIfFilterOn = hasActiveFilters;

      // Update pagination and reload table data
      this.paginationDefault();
      this.loadTableData();
    },
    clearAllFilter() {
      this.filters = false;
      // Reset filter parameters
      Object.assign(this.tableOptions.filterParam, {
        geo_level: "",
        geo_level_id: "",
        geo_string: "",
        periodid: [],
        visitTitle: "",
        attendedDate: "",
        referralStatus: "",
      });

      $(".select2").val("").trigger("change");
      $(".period").val("").trigger("change");
      // this.clearFlatpickr("date");
      this.paginationDefault();
      this.loadTableData();
    },
    clearFlatpickr(dateClass) {
      const flatpickrInstance = $("." + dateClass)[0]._flatpickr;
      if (flatpickrInstance) {
        flatpickrInstance.clear();
      }
    },
    checkAndHideFilter(dataToCheck) {
      let arrayToExclude = ["periodid", "geo_level_id", "geo_level"];

      if (!arrayToExclude.includes(dataToCheck)) {
        return true;
      } else {
        return false;
      }
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
      if (select_index) {
        this.tableOptions.filterParam.geo_level_id =
          this.geoData[select_index].geo_level_id;
        this.tableOptions.filterParam.geo_level =
          this.geoData[select_index].geo_level;
        this.tableOptions.filterParam.geo_string =
          this.geoData[select_index].title;
      }
    },
    setPeriodTitle(event) {
      // Clear existing period IDs and title
      this.tableOptions.filterParam.periodid = [];
      this.tableOptions.filterParam.visitTitle = "";

      // Map selected IDs to titles and update period IDs
      const titles = event
        .map((id) => {
          this.tableOptions.filterParam.periodid.push(id);
          const period = this.periodData.find(
            (period) => period.periodid == id
          );
          return period ? period.title : null;
        })
        .filter((title) => title !== null); // Remove null values if any

      // Join titles with commas and set visitTitle
      this.tableOptions.filterParam.visitTitle = this.joinWithCommaAnd(titles);
    },
    joinWithCommaAnd(array, status = false) {
      if (array.length === 0) {
        return "";
      }
      if (array.length === 1) {
        return array[0];
      }
      const lastElement = array.pop();
      if (status) {
        return `${array.join(",")},${lastElement}`;
      } else {
        return `${array.join(", ")} and ${lastElement}`;
      }
    },
    convertStringNumberToFigures(d) {
      let data = d ? parseInt(d) : 0;
      return data ? data.toLocaleString() : 0;
    },
    splitWordAndCapitalize(str) {
      // Split the string by underscores, capital letters, or spaces
      let words = str.split(/(?=[A-Z])|_| /);

      // Capitalize each word
      words = words.map(
        (word) => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase()
      );

      // Join the words back into a sentence
      return words.join(" ");
    },
    async exportChildRefferal() {
      const {
        currentPage,
        orderField,
        perPage,
        limitStart,
        orderDir,
        filterParam,
      } = this.tableOptions;
      const periodIds = this.joinWithCommaAnd(filterParam.periodid, true);
      const { geo_level_id, geo_level, referralStatus } = filterParam;
      const queryParams = `$&draw=${currentPage}&order_column=${orderField}&length=${perPage}&start=${limitStart}&order_dir=${orderDir}&pid=${periodIds}&gid=${geo_level_id}&gl=${geo_level}&atd=${referralStatus}`;

      const veriUrl = `qid=1125&${queryParams}`;
      const dlString = `qid=802&${queryParams}`;

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
      const filename =
        geo_level + "_" + referralStatus + "_Refferal_Export_" + formattedDate;

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
  },
  computed: {
    percentageAttended() {
      if (this.statData.referrals === 0) {
        return 0;
      }

      let percentageAttended = (
        (this.statData.attended / this.statData.referrals) *
        100
      ).toFixed(2);
      if (percentageAttended < 50) {
        this.statProgessBarStatus = "progress-bar-danger";
      } else if (percentageAttended >= 50 && percentageAttended < 70) {
        this.statProgessBarStatus = "progress-bar-warning";
      } else if (percentageAttended >= 70) {
        this.statProgessBarStatus = "progress-bar-success";
      }
      return percentageAttended;
    },
  },
  template: `

        <div class="row" id="basic-table">
            <div class="col-md-8 col-sm-12 col-12 mb-0">
                <h2 class="content-header-title header-txt float-left mb-0">SMC</h2>
                <div class="breadcrumb-wrapper">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../smc">Home</a></li>
                        <li class="breadcrumb-item active">Referral</li>
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
                    <button v-if="permission.permission_value >=2" type="button" class="btn btn-outline-primary round " data-toggle="tooltip" data-placement="top" title="Download" @click="exportChildRefferal()">
                        <i class="feather icon-download"></i>               
                    </button>
                </div>
            </div>
            <div class="col-12" v-if="filters">
                <div class="col-12 filter-bar">
                    <span class="badge badge-dark filter-box" @click="removeSingleFilter(i)" v-for="(filterParam, i) in tableOptions.filterParam" v-if="filterParam.length > 0 && checkAndHideFilter(i)">{{splitWordAndCapitalize(i)}}: {{filterParam}} <i class="feather icon-x"></i> </span>
                    <a href="#" class="float-right clear-filter" @click="clearAllFilter()">Clear</a>
                </div>
            </div>
            
            <div class="col-12 mt-1">

                <div class="card mb-1">
                  <div class="card-widget-separator-wrapper">
                    <div class="card-body pb-75 pt-75 card-widget-separator">
                      <div class="row gy-4 gy-sm-1">
                        <div class="col-sm-6 col-lg-3">
                          <div class="d-flex justify-content-between align-items-start card-widget-1 border-end pb-sm-0">
                            <div>
                              <h6 class="mb-50">Total Referral</h6>
                              <h4 class="mb-0">
                                {{convertStringNumberToFigures(statData.referrals)}}
                              </h4>
                            </div>
                            <span class="avatar1 me-sm-4">
                              <span class="avatar-initial bg-light-secondary rounded"><i class="ti-md ti ti-arrow-ramp-right text-body"></i></span>
                            </span>
                          </div>
                          <hr class="d-none d-sm-block d-lg-none me-4">
                        </div>
                        <div class="col-sm-6 col-lg-3">
                          <div class="d-flex justify-content-between align-items-start card-widget-2 border-end pb-sm-0">
                            <div>
                              <h6 class="mb-50">Attended</h6>
                              <h4 class="mb-0">
                                {{convertStringNumberToFigures(statData.attended)}}
                              </h4>
                            </div>
                            <span class="avatar1 me-sm-4">
                              <span class="avatar-initial bg-light-secondary rounded"><i class="ti-md ti ti-arrow-down-left-circle text-body"></i></span>
                            </span>
                          </div>
                          <hr class="d-none d-sm-block d-lg-none">
                        </div>
                        <div class="col-sm-6 col-lg-3">
                          <div class="d-flex justify-content-between align-items-start border-end pb-sm-0 card-widget-3">
                            <div>
                              <h6 class="mb-50">Total Visit</h6>
                              <h4 class="mb-0">
                                {{convertStringNumberToFigures(statData.period)}}
                              </h4>
                            </div>
                            <span class="avatar1 me-sm-4">
                              <span class="avatar-initial bg-light-secondary rounded"><i class="ti-md ti ti-brand-spacehey text-body"></i></span>
                            </span>
                          </div>
                        </div>
                        <div class="col-sm-6 col-lg-3">
                          <div class="d-flex justify-content-between align-items-start">
                            <div>
                              <h6 class="mb-50">Percentage Attended</h6>
                              <h4 class="mb-0" v-if="percentageAttended<=0">{{percentageAttended}}%</h4>
                            </div>
                            <!--
                            <span class="avatar1 me-sm-4">
                              <span class="avatar-initial bg-light-secondary rounded"><i class="ti-md ti ti-wallet text-body"></i></span>
                            </span>
                            -->
                          </div>
                          <div v-if="percentageAttended>0" class="progress referral" :class="statProgessBarStatus" style="height: 22px; font-weight: bolder">
                              <div class="progress-bar" role="progressbar" :aria-valuenow="percentageAttended" :aria-valuemin="percentageAttended" aria-valuemax="100" :style="{ width: percentageAttended + '%' }">{{ percentageAttended + '%' }}</div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="card custom-select-down">
                    <div class="card-body py-1" v-show="filterState">
                        <form id="filterForm">

                            <div class="row">
                                <div class="col-12 col-sm-6 col-md-6 col-lg-6">
                                    <div class="form-group">
                                        <label>Visit</label>
                                        <select @change="setPeriodTitle($event)" v-model="tableOptions.filterParam.periodid" multiple class="form-control period" id="period">
                                            <option v-for="(g, i) in periodData" :key="i" :value="g.periodid">{{ g.title }}</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-12 col-sm-6 col-md-6 col-lg-6">
                                    <div class="form-group">
                                        <label>Geo Location</label>
                                        <select class="select2 form-control" @change="setLocation()" placeholder="Geo Location" >
                                            <option value="">Select Geo Location</option>
                                            <option v-for="(g, i) in geoData" :value="i">{{ g.geo_string }}</option>
                                        </select>
                                    </div>
                                </div>
                                <!--
                                <div class="col-12 col-sm-6 col-md-6 col-lg-6">
                                    <div class="form-group date_filter">
                                        <label>Attended Date</label>
                                        <input type="text" id="attended_date" v-model="tableOptions.filterParam.attendedDate" class="form-control attended_date date" placeholder="Attended Date" name="attended_date" />
                                    </div>
                                </div>
                                -->

                                <div class="col-12 col-sm-6 col-md-6 col-lg-6">
                                    <div class="form-group">
                                        <label>Referral Status</label>
                                        <select class="form-control" placeholder="Select Status" v-model="tableOptions.filterParam.referralStatus" >
                                            <option value="">All</option>
                                            <option value="Yes">Attended</option>
                                            <option value="No">Not Attended</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-12 col-sm-12 col-md-12 col-lg-6">
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
                                        Beneficiary
                                        <i class="feather icon-chevron-up sort-up" :class="(tableOptions.orderField == 2 && tableOptions.orderDir =='asc')? 'active-sort': ''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 2 && tableOptions.orderDir =='desc')? 'active-sort': ''"></i>
                                    </th>
                                    <th @click="sort(1)">
                                       Visit
                                        <i class="feather icon-chevron-up sort-up" :class="(tableOptions.orderField == 1 && tableOptions.orderDir =='asc')? 'active-sort': ''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 1 && tableOptions.orderDir =='desc')? 'active-sort': ''"></i>
                                    </th>
                                    <th @click="sort(4)">
                                       Referred Type
                                        <i class="feather icon-chevron-up sort-up" :class="(tableOptions.orderField == 4 && tableOptions.orderDir =='asc')? 'active-sort': ''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 4 && tableOptions.orderDir =='desc')? 'active-sort': ''"></i>
                                    </th>
                                    <th @click="sort(7)">
                                        Refer Date
                                        <i class="feather icon-chevron-up sort-up" :class="(tableOptions.orderField == 7 && tableOptions.orderDir =='asc')? 'active-sort': ''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 7 && tableOptions.orderDir =='desc')? 'active-sort': ''"></i>
                                    </th>
                                    <th @click="sort(5)">
                                        Attended
                                        Status
                                        <i class="feather icon-chevron-up sort-up" :class="(tableOptions.orderField == 5 && tableOptions.orderDir =='asc')? 'active-sort': ''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 5 && tableOptions.orderDir =='desc')? 'active-sort': ''"></i>
                                    </th>
                                    <th @click="sort(7)" class="text-wrap text-center">
                                       Attended Date
                                        <i class="feather icon-chevron-up sort-up" :class="(tableOptions.orderField == 7 && tableOptions.orderDir =='asc')? 'active-sort': ''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 7 && tableOptions.orderDir =='desc')? 'active-sort': ''"></i>
                                    </th>
                                    <th @click="sort(6)">
                                        Geo String
                                        <i class="feather icon-chevron-up sort-up" :class="(tableOptions.orderField == 6 && tableOptions.orderDir =='asc')? 'active-sort': ''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 6 && tableOptions.orderDir =='desc')? 'active-sort': ''"></i>
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
                                    <td>{{ g.period }}</td>
                                    <td>
                                        <div class="d-flex justify-content-left align-items-center">
                                            <div class="d-flex flex-column">
                                                <span class="user_name text-truncate text-body">
                                                    <span class="fw-bolder">{{ g.refer_type }}</span>
                                                </span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex justify-content-left align-items-center">
                                            <div class="d-flex flex-column">
                                                <span class="user_name text-truncate text-body">
                                                  <span class="badge badge-light-warning">{{displayDate(g.referred_date)}}</span>
                                                </span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex justify-content-center text-center align-items-center">
                                            <div class="d-flex flex-column">
                                                <span class="user_name text-truncate text-body">
                                                  <span class="fw-bolder badge p-25" :class="g.attended =='No'? 'badge-light-warning':'badge-light-success'"><i class="feather" :class="g.attended == 'No'? 'icon-x-circle' : 'icon-check-circle'"></i></span>
                                                </span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex justify-content-left align-items-center">
                                            <div class="d-flex flex-column">
                                                <span class="user_name text-truncate text-body">
                                                  <span class="badge badge-light-success">{{g.attended_date}}</span>
                                                </span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                      <small class="fw-bolder">{{g.geo_string}}</small>
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
