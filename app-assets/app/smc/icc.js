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
                    <icc_list/>
                </div>

            </div>
        </div>
    `,
});

// User List Page
Vue.component("icc_list", {
  data: function () {
    return {
      url: common.DataService,
      permission: getPermission(per, "smc"),
      tableData: [],
      selectedICCDetails: [],
      iccIssuedReconcileDetails: [],
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
          globalPeriod: "",
          visitTitle: "",
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

    // Dynamically calculate and apply left offsets
    // this.setStickyOffsets();
    this.$nextTick(this.setStickyOffsets);
  },
  updated() {
    // this.setStickyOffsets();
    this.$nextTick(this.setStickyOffsets);
  },
  methods: {
    setStickyOffsets() {
      const table = document.getElementById("fixed-table");
      if (!table) return;

      const th1 = table.querySelector("th.col-1");
      const th2 = table.querySelector("th.col-2");
      const th3 = table.querySelector("th.col-3");

      const col1Width = th1?.offsetWidth || 0;
      const col2Width = th2?.offsetWidth || 0;

      const col2Left = col1Width;
      const col3Left = col1Width + col2Width;

      table
        .querySelectorAll(".col-2")
        .forEach((el) => (el.style.left = `${col2Left}px`));
      table
        .querySelectorAll(".col-3")
        .forEach((el) => (el.style.left = `${col3Left}px`));
    },
    refreshData() {
      this.getGeoLocation();
      this.getAllPeriodLists();
      this.loadTableData();
    },
    async loadTableData() {
      // Manages the loading of table data
      const url = common.TableService;
      overlay.show();

      const {
        currentPage,
        orderField,
        perPage,
        limitStart,
        orderDir,
        filterParam,
      } = this.tableOptions;

      // Prepare filter parameters
      filterParam.globalPeriod = this.joinWithCommaAnd(
        filterParam.periodid,
        true
      );
      const { geo_level_id, geo_level } = filterParam;

      // Construct the endpoint URL
      const endpoint = `${url}?qid=706&draw=${currentPage}&order_column=${orderField}&length=${perPage}&start=${limitStart}&order_dir=${orderDir}&pid=${filterParam.globalPeriod}&gid=${geo_level_id}&glv=${geo_level}`;

      try {
        // Fetch data from the endpoint
        const tableResponse = await axios.get(endpoint);
        const { data, recordsTotal } = tableResponse.data;

        // Update table data and total records
        this.tableData = data;
        this.tableOptions.total = recordsTotal;

        // Reset pagination if on the first page
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
        filterParam.visitTitle = filterParam.globalPeriod = "";
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
        globalPeriod: "",
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
      let arrayToExclude = [
        "periodid",
        "geo_level_id",
        "geo_level",
        "globalPeriod",
      ];

      if (!arrayToExclude.includes(dataToCheck)) {
        return true;
      } else {
        return false;
      }
    },
    async GetIccFlowDetailByCdd(cddid, id) {
      overlay.show();
      this.selectedICCDetails = this.tableData[id];
      const { filterParam } = this.tableOptions;
      const periodIds = filterParam.globalPeriod;

      try {
        let url = this.url + "?qid=1127&cddid=" + cddid + "&pid=" + periodIds;
        const response = await axios.get(url);
        if (response.data.result_code == 200) {
          $("#iccDetailsModal").modal("show");

          this.iccIssuedReconcileDetails = response.data;
          console.log(this.iccIssuedReconcileDetails);
        } else {
          this.iccIssuedReconcileDetails = [];

          alert.Error("ERROR", response.data.message);
        }
      } catch (error) {
        alert.Error("ERROR", error);
      } finally {
        overlay.hide();
      }
    },
    hideGetIccFlowDetailByCdd() {
      overlay.show();
      this.selectedICCDetails = [];
      $("#iccDetailsModal").modal("hide");
      let g = 0;
      this.iccIssuedReconcileDetails = [];
      for (let i in this.iccIssuedReconcileDetails) {
        this.iccIssuedReconcileDetails[i] = "";
        g++;
      }
      overlay.hide();
    },
    capitalize(word) {
      if (word) {
        const loweredCase = word.toLowerCase();
        return word[0].toUpperCase() + loweredCase.slice(1);
      } else {
        return word;
      }
    },
    checkIfEmpty(data) {
      if (data === null || data === "") {
        return "Nil";
      } else {
        return data;
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
      if (!d) return "";
      let date = new Date(d);
      let options = {
        year: "numeric",
        month: "long",
        day: "numeric",
      };
      return date.toLocaleString("en-us", options);
    },
    displayDayMonthYearTime(d) {
      if (!d) return "";

      let date = new Date(d);
      let options = {
        year: "numeric",
        month: "long",
        day: "numeric",
        hour12: true,
        hour: "2-digit",
        minute: "2-digit",
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
    percentageUsed(issue, used) {
      const percentUsed = (parseFloat(used) / parseFloat(issue)) * 100;
      if (isNaN(percentUsed)) {
        return 0;
      }
      // return Math.ceil(percentUsed * 10) / 10;

      return percentUsed.toFixed(2);
    },
    progressBarWidth(issue, used) {
      return this.percentageUsed(issue, used) + "%";
    },
    progressBarStatus(issue, used) {
      let progress = this.percentageUsed(issue, used);
      let state = "warning";
      if (progress <= 30) {
        state = "danger";
      } else if (progress <= 60) {
        state = "warning";
      } else if (progress < 90) {
        state = "primary";
      } else if (progress > 90) {
        state = "success";
      }

      return "bg-" + state;
    },
    async exportIcc() {
      const {
        currentPage,
        orderField,
        perPage,
        limitStart,
        orderDir,
        filterParam,
      } = this.tableOptions;
      const periodIds = this.joinWithCommaAnd(filterParam.periodid, true);
      filterParam.globalPeriod = periodIds;

      const { geo_level_id, geo_level } = filterParam;

      const queryParams = `&draw=${currentPage}&order_column=${orderField}&length=${perPage}&start=${limitStart}&order_dir=${orderDir}&pid=${periodIds}&gid=${geo_level_id}&glv=${geo_level}`;

      const veriUrl = `qid=1126&${queryParams}`;
      const dlString = `qid=803&${queryParams}`;

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
        geo_level +
        "_" +
        filterParam.globalPeriod +
        "_ICC_Export_" +
        formattedDate;

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
    selectStatusColor(g) {
      if (g == "pending") return "badge-secondary";
      if (g == "downloaded") return "badge-success";
      if (g == "rejected") return "danger-danger";
      if (g == "reconciled") return "danger-success";
      if (g == "accepted") return "danger-success";
      return "bg-danger";
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
                        <li class="breadcrumb-item active">Inventory Control</li>
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
                    <button v-if="permission.permission_value >=2" type="button" class="btn btn-outline-primary round " data-toggle="tooltip" data-placement="top" title="Download" @click="exportIcc()">
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

                <div class="card custom-select-down">
                    <div class="card-body py-1" v-show="filterState">
                        <form id="filterForm">

                            <div class="row">
                                <div class="col-12 col-sm-6 col-md-4 col-lg-5">
                                    <div class="form-group">
                                        <label>Visit</label>
                                        <select @change="setPeriodTitle($event)" v-model="tableOptions.filterParam.periodid" multiple class="form-control period" id="period">
                                            <option v-for="(g, i) in periodData" :value="g.periodid">{{ g.title }}</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-12 col-sm-6 col-md-5 col-lg-5">
                                    <div class="form-group">
                                        <label>Geo Location</label>
                                        <select class="select2 form-control" @change="setLocation()" placeholder="Geo Location" >
                                            <option value="">Select Geo Location</option>
                                            <option v-for="(g, i) in geoData" :value="i">{{ g.geo_string }}</option>
                                        </select>
                                    </div>
                                </div>
                      
                                <div class="col-12 col-sm-12 col-md-3 col-lg-2">
                                    <div class="form-group mt-2 text-right">
                                        <button type="button" class="btn btn-md btn-primary"  @click="applyFilter()">Apply Filters</button>
                                    </div>
                                </div>
                                
                            </div>
                        </form>
                    </div>

                    <div class="table-responsive" id="icc_long">
                        <table class="table table-hover" id="fixed-table">
                            <thead>
                                <tr>
                                    <th @click="sort(0)" class="pl-1 pr-2">
                                        #
                                        <i class="feather icon-chevron-up sort-up" :class="(tableOptions.orderField == 0 && tableOptions.orderDir =='asc')? 'active-sort': ''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 0 && tableOptions.orderDir =='desc')? 'active-sort': ''"></i>
                                    </th>
                                    <th class="pl-1 pr-2 sticky-col col-1">Destination</th>
                                    <th class="pl-1 pr-1 sticky-col col-2">Location</th>
                                    <th class="pl-1 pr-1 sticky-col col-3">Drug</th>
                                    <th class="pl-1 pr-1">Qty. Issued</th>
                                    <th class="pl-1 pr-1">Issue Status</th>
                                    <th class="pl-1 pr-1">Download Status</th>
                                    <th class="pl-1 pr-1">Reject Status</th>
                                    <th class="pl-1 pr-1">Acceptance Status</th>
                                    <th class="pl-1 pr-1">Calculated Used</th>
                                    <th class="pl-1 pr-1">Calculated Partial</th>
                                    <th class="pl-1 pr-1">Return Status</th>
                                    <th class="pl-1 pr-1">Return Qty.</th>
                                    <th class="pl-1 pr-1">Return Partial</th>
                                    <th class="pl-1 pr-1">Reconcile Status</th>
                                    <th class="pl-1 pr-1">Full Qty.</th>
                                    <th class="pl-1 pr-1">Partial Qty.</th>
                                    <th class="pl-1 pr-1">Wasted Qty.</th>
                                    <th class="pl-1 pr-1">Loss Qty.</th>
                                    <th class="pl-1 pr-2">Loss Reason</th>
                                </tr>
                                <!--
                                <tr>
                                    <th class="pl-1 pr-1">Used</th>
                                    <th class="pl-1 pr-1">Partial</th>
                                </tr>
                                -->
                            </thead>
                            <tbody>
                                <tr v-for="(g, i) in tableData">
                                    <td class="pl-1 pr-2">{{ g.issue_id }}</td>
                                    <td class="pl-1 pr-1 sticky-col col-1">
                                        <div class="d-flex justify-content-left align-items-center">
                                            <div class="d-flex flex-column">
                                                <small class="emp_post text-left">
                                                    <span class="text-muted w-text">From:</span>
                                                    <span class="fw-bolder">{{checkIfEmpty(g.issuer)}} ({{g.issuer_loginid}})</span>
                                                </small>
                                                <small class="emp_post text-left">
                                                    <span class="text-muted w-text">To:</span>
                                                    <span class="fw-bolder">{{checkIfEmpty(g.cdd_lead)}} ({{g.cdd_loginid}})</span>
                                                </small>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="pl-1 pr-1  sticky-col col-2">
                                        <div class="d-flex flex-column">{{capitalize(g.geo_string)}}</div>
                                    </td>
                                    <td class="pl-1 pr-1 sticky-col col-3">{{ g.issue_drug }}</td>
                                    <td class="pl-1 pr-1">{{ convertStringNumberToFigures(g.drug_qty)}}</td>
                                    <td class="pl-1 pr-1">
                                      <div class="d-flex justify-content-left align-items-center">
                                            <div class="d-flex flex-column">
                                                <small class="emp_post text-left" v-if="g.status">
                                                    <span class="fw-bolder">{{g.status}}</span>
                                                </small>
                                                <small class="emp_post text-left">
                                                    <span class="fw-bolder">{{displayDayMonthYearTime(g.issue_date)}}</span>
                                                </small>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="pl-1 pr-1">
                                        <div class="d-flex justify-content-left align-items-center">
                                            <div class="d-flex flex-column">
                                                <small class="emp_post text-left" v-if="g.downloaded">
                                                    <span class="fw-bolder">{{g.downloaded}}</span>
                                                </small>
                                                <small class="emp_post text-left">
                                                    <span class="fw-bolder">{{displayDayMonthYearTime(g.download_confirm_date)}}</span>
                                                </small>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="pl-1 pr-1">
                                        <div class="d-flex justify-content-left align-items-center">
                                            <div class="d-flex flex-column">
                                                <small class="emp_post text-left">
                                                    <span class="fw-bolder">{{g.is_rejected}}</span>
                                                </small>
                                                <small class="emp_post text-left" v-if="g.is_rejected">
                                                    <span class="fw-bolder">{{g.rejection_note}}</span>
                                                </small>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="pl-1 pr-1">
                                        <div class="d-flex justify-content-left align-items-center">
                                            <div class="d-flex flex-column">
                                                <small class="emp_post text-left">
                                                    <span class="fw-bolder">{{g.is_accepted}}</span>
                                                </small>
                                                <small class="emp_post text-left">
                                                    <span class="fw-bolder">{{displayDayMonthYearTime(g.accepted_date)}}</span>
                                                </small>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="pl-1 pr-1">{{convertStringNumberToFigures(g.calculated_used)}}</td>
                                    <td class="pl-1 pr-1">{{convertStringNumberToFigures(g.calculated_partial) }}</td>
                                    <td class="pl-1 pr-1">
                                        <div class="d-flex justify-content-left align-items-center">
                                            <div class="d-flex flex-column">
                                                <small class="emp_post text-left">
                                                    <span class="fw-bolder">{{g.is_returned}}</span>
                                                </small>
                                                <small class="emp_post text-left">
                                                    <span class="fw-bolder">{{displayDayMonthYearTime(g.returned_date)}}</span>
                                                </small>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="pl-1 pr-1">{{convertStringNumberToFigures(g.returned_qty) }}</td>
                                    <td class="pl-1 pr-1">{{convertStringNumberToFigures(g.returned_partial) }}</td>
                                    <td class="pl-1 pr-1">
                                        <div class="d-flex justify-content-left align-items-center">
                                            <div class="d-flex flex-column">
                                                <small class="emp_post text-left">
                                                    <span class="fw-bolder">{{g.is_reconciled}}</span>
                                                </small>
                                                <small class="emp_post text-left">
                                                    <span class="fw-bolder">{{displayDayMonthYearTime(g.reconciled_date)}}</span>
                                                </small>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="pl-1 pr-1">{{convertStringNumberToFigures(g.full_qty) }}</td>
                                    <td class="pl-1 pr-1">{{convertStringNumberToFigures(g.partial_qty) }}</td>
                                    <td class="pl-1 pr-1">{{convertStringNumberToFigures(g.wasted_qty) }}</td>
                                    <td class="pl-1 pr-1">{{convertStringNumberToFigures(g.loss_qty) }}</td>
                                    <td class="pl-1 pr-2">{{g.loss_reason}}</td>
                                </tr>
                                <tr v-if="tableData.length == 0"><td class="text-center pt-2" colspan="9"><small>No Data Found</small></td></tr>
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


           <!-- Modal to Show Child Administrations details starts-->
            <div class="modal fade modal-primary" id="iccDetailsModal" tabindex="-1" role="dialog" aria-labelledby="iccIssuedDetails" aria-hidden="true">
                <div class="modal-dialog modal-scrollable modal-xl">
                    <form class="modal-content pt-0" @submit.stop.prevent="" id="state-form">
                        <div class="modal-header mb-0-">
                          <h5 class="modal-title font-weight-bolder text-dark" id="iccIssuedDetails">{{selectedICCDetails.issuer_name? selectedICCDetails.issuer_name : selectedICCDetails.issuer_loginid}}, ICC Details <br></span><span class="badge badge-light-success">{{selectedICCDetails.issuer_loginid}}</span></h5>
                          <button type="reset" class="close" @click="hideGetIccFlowDetailByCdd()" data-dismiss="modal">×</button>
                        </div>                        

                        <div class="modal-body flex-grow-1 vertical-wizard">
                          <ul class="nav nav-tabs" id="myTab" role="tablist">
                            <li class="nav-item">
                              <a class="nav-link active" id="spaq-issued-tab" data-toggle="tab" href="#spaq-issued" role="tab" aria-controls="spaq-issued" aria-selected="true">SPAQ Issued</a>
                            </li>
                            <li class="nav-item">
                              <a class="nav-link" id="spaq-returned-tab" data-toggle="tab" href="#spaq-returned" role="tab" aria-controls="spaq-returned" aria-selected="false">SPAQ Returned</a>
                            </li>
                          </ul>
                          <div class="tab-content" id="myTabContent">
                            <div class="tab-pane fade pt-75 show active" id="spaq-issued" role="tabpanel" aria-labelledby="spaq-issued-tab">
                              <!--
                              <div class="card mb-0 btmlr">
                                <div class="card-widget-separator-wrapper">
                                  <div class="card-body pb-75 pt-75 card-widget-separator">
                                    <div class="row gy-4 gy-sm-1">
                                      <div class="col-sm-6 col-lg-6">
                                        <div class="d-flex justify-content-between align-items-start card-widget-1 border-end pb-sm-0">
                                          <div>
                                            <h6 class="mb-50 small">Total SPAQ 1 Issued</h6>
                                            <h4 class="mb-0">{{convertStringNumberToFigures(getTopIccIssued.sumSpaq1Qty)}}</h4>
                                          </div>
                                          <span class="avatar1 me-sm-4">
                                            <span class="avatar-initial bg-label-blue rounded"><i class="ti-md ti ti-pill text-body"></i></span>
                                          </span>
                                        </div>
                                        <hr class="d-none d-sm-block d-lg-none me-4">
                                      </div>
                                      
                                      <div class="col-sm-6 col-lg-6">
                                        <div class="d-flex justify-content-between align-items-start">
                                          <div>
                                            <h6 class="mb-50 small">Total SPAQ 2 Issued</h6>
                                            <h4 class="mb-0">{{convertStringNumberToFigures(getTopIccIssued.sumSpaq2Qty)}}</h4>
                                          </div>
                                          <span class="avatar1 me-sm-4">
                                            <span class="avatar-initial bg-label-dark rounded"><i class="ti-md ti ti-pill-off text-body"></i></span>
                                          </span>
                                        </div>
                                      </div>
                                      
                                    </div>
                                  </div>
                                </div>
                              </div>
                              -->

                              <div class="info-container table-responsive pt-0">
                                <table class="table">
                                  <thead>
                                    <th>Issuer Details</th>
                                    <th>Visit</th>
                                    <th>Issued Drug</th>
                                    <th>Quantity</th>
                                    <th>Issue Date</th>
                                    <th>Created Date</th>
                                  </thead>
                                  <tbody>
                                    <tr v-for="(g, i) in iccIssuedReconcileDetails">
                                      <td>
                                        <div class="d-flex justify-content-left align-items-center">
                                          <div class="d-flex flex-column">
                                            <span class="user_name text-truncate text-body">
                                              <span class="fw-bolder">{{capitalize(g.issuer_name)}}</span>
                                            </span>
                                            <small class="emp_post text-primary text-left">
                                              <span class="badge badge-light-primary">{{g.issuer_loginid}}</span>
                                            </small>
                                          </div>
                                        </div>
                                      </td>
                                      <td>{{checkIfEmpty(g.period)}}</td>
                                      <td>{{checkIfEmpty(g.issue_drug)}}</td>
                                      <td>{{convertStringNumberToFigures(g.drug_qty)}}</td>
                                      <td>{{displayDayMonthYearTime(g.issue_date)}}</td>
                                      <td>{{displayDayMonthYearTime(g.created)}}</td>
                                    </tr>

                                  </tbody>
                                </table>
                              </div>
                            </div>
                            <div class="tab-pane fade" id="spaq-returned" role="tabpanel" aria-labelledby="spaq-returned-tab">

                              <div class="info-container table-responsive pt-25">
                                 
                              </div>
                            </div>
                          </div>
  
                        </div>
                        <div class="modal-footer">
                              <button type="reset" class="btn btn-secondary" data-dismiss="modal" @click="hideGetIccFlowDetailByCdd()">Close</button>
                        </div>
                    </form>
                </div>
            </div>
            <!-- Modal to Show Child Administrations details Ends-->

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
