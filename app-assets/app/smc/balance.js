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
      iccIssuedDetails: [],
      iccReceivedDetails: [],
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
          loginId: "",
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
      filterParam.globalPeriod = periodIds;

      const { geo_level_id, geo_level, loginId } = filterParam;

      const endpoints = [
        `${url}?qid=705&draw=${currentPage}&order_column=${orderField}&length=${perPage}&start=${limitStart}&order_dir=${orderDir}&pid=${periodIds}&gid=${geo_level_id}&glv=${geo_level}&lid=${loginId}`,
      ];

      try {
        const [tableResponse] = await Promise.all(
          endpoints.map((endpoint) => axios.get(endpoint))
        );
        this.tableData = tableResponse.data.data; // All TableData

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
      checkFill += this.tableOptions.filterParam.loginId != "" ? 1 : 0;

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
        loginId: "",
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
    async getIccDetails(cddid, id) {
      overlay.show();
      this.selectedICCDetails = this.tableData[id];
      const { filterParam } = this.tableOptions;
      const periodIds = filterParam.globalPeriod;

      try {
        let url = this.url + "?qid=1123&cddid=" + cddid + "&pid=" + periodIds;
        const response = await axios.get(url);
        if (response.data.result_code == 200) {
          $("#iccDetailsModal").modal("show");

          this.iccIssuedDetails = response.data.data[0];
          this.iccReceivedDetails = response.data.data[1];
          console.log(this.iccReceivedDetails);
        } else {
          this.iccIssuedDetails =
            this.selectedICCDetails =
            this.iccReceivedDetails =
              [];

          alert.Error("ERROR", response.data.message);
        }
      } catch (error) {
        alert.Error("ERROR", error);
      } finally {
        overlay.hide();
      }
    },
    hideGetIccDetails() {
      overlay.show();
      this.selectedICCDetails = [];
      $("#iccDetailsModal").modal("hide");
      let g = 0;
      this.iccIssuedDetails =
        this.selectedICCDetails =
        this.iccReceivedDetails =
          [];
      for (let i in this.iccIssuedDetails) {
        this.iccIssuedDetails[i] = "";
        g++;
      }
      overlay.hide();
    },
    async unlockIcc(cddid, id) {
      // Set selected ICC details
      let cddDetails = this.tableData[id];
      let geo_level_id = cddDetails.geo_level_id;
      let drug = cddDetails.drug;
      let total_qty = cddDetails.downloaded;
      let cdd_lead_name = cddDetails.fullname;
      let issueId = cddDetails.issue_id;

      // Early exit if total_qty is zero or less
      if (total_qty <= 0) {
        alert.Error("Zero Balance", "You don't have a balance to unlock.");
        return;
      }

      const userid = document.getElementById("v_g_id").value;
      const unlockUrl = `${this.url}?qid=1128&issueId=${issueId}&cddid=${cddid}&drug=${drug}&dpid=${geo_level_id}&qty=${total_qty}&user_id=${userid}`;

      // Confirm Unlock Action
      $.confirm({
        title: "WARNING!",
        content: `Are you sure you want to unlock <b>${total_qty}</b> ${drug} from ${cdd_lead_name} device?`,
        buttons: {
          delete: {
            text: "Unlock",
            btnClass: "btn btn-danger mr-1 text-capitalize",
            action: () => {
              // Perform Unlock Action
              axios
                .post(unlockUrl)
                .then((response) => {
                  const { result_code, message } = response.data;
                  if (result_code == "200") {
                    this.refreshData();
                    alert.Success(
                      "Success",
                      `<b>${total_qty}</b> ${drug} Unlocked`
                    );
                  } else {
                    alert.Error("Error", message);
                  }
                })
                .catch((error) => {
                  alert.Error("ERROR", error);
                })
                .finally(() => {
                  overlay.hide();
                });
            },
          },
          cancel: () => overlay.hide(),
        },
      });
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
      if (!d) return "";
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
  },
  computed: {
    getTopIccIssued() {
      const result = this.iccIssuedDetails.reduce(
        (acc, item) => {
          if (item.issue_drug === "SPAQ 1") {
            acc.sumSpaq1Qty += parseInt(item.drug_qty);
          } else if (item.issue_drug === "SPAQ 2") {
            acc.sumSpaq2Qty += parseInt(item.drug_qty);
          }
          return acc;
        },
        {
          sumSpaq1Qty: 0,
          sumSpaq2Qty: 0,
        }
      );
      return result;
    },
    getTopIccReturned() {
      const result = this.iccReceivedDetails.reduce(
        (acc, item) => {
          if (item.received_drug === "SPAQ 1") {
            acc.sumSpaq1FullReturn += parseInt(item.full_dose_qty);
            acc.sumSpaq1partialReturn += parseInt(item.partial_qty);
            acc.sumSpaq1Wasted += parseInt(item.wasted_qty);

            acc.sumSpaq1Returned +=
              parseInt(item.full_dose_qty) +
              parseInt(item.partial_qty) +
              parseInt(item.wasted_qty);
          } else if (item.received_drug === "SPAQ 2") {
            acc.sumSpaq2FullReturn += parseInt(item.full_dose_qty);
            acc.sumSpaq2partialReturn += parseInt(item.partial_qty);
            acc.sumSpaq2Wasted += parseInt(item.wasted_qty);

            acc.sumSpaq2Returned +=
              parseInt(item.full_dose_qty) +
              parseInt(item.partial_qty) +
              parseInt(item.wasted_qty);
          }
          return acc;
        },
        {
          sumSpaq1FullReturn: 0,
          sumSpaq1partialReturn: 0,
          sumSpaq1Wasted: 0,
          sumSpaq1Returned: 0,

          sumSpaq2FullReturn: 0,
          sumSpaq2partialReturn: 0,
          sumSpaq2Wasted: 0,
          sumSpaq2Returned: 0,
        }
      );
      return result;
    },
  },
  template: `

        <div class="row" id="basic-table">
            <div class="col-md-8 col-sm-12 col-12 mb-0">
                <h2 class="content-header-title header-txt float-left mb-0">SMC</h2>
                <div class="breadcrumb-wrapper">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../smc">Home</a></li>
                        <li class="breadcrumb-item active">IC Balance</li>
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
                    <button v-if="permission.permission_value >=1" type="button" class="btn btn-outline-primary round " data-toggle="tooltip" data-placement="top" title="Download" @click="exportIcc()">
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
                                <div class="col-12 col-sm-6 col-md-6 col-lg-3">
                                    <div class="form-group">
                                        <label>Login ID</label>
                                        <input type="text" v-model="tableOptions.filterParam.loginId" class="form-control login-id" id="login-id" placeholder="Login ID" name="login_id" />
                                    </div>
                                </div>
                                <div class="col-12 col-sm-6 col-md-6 col-lg-3">
                                    <div class="form-group">
                                        <label>Visit</label>
                                        <select @change="setPeriodTitle($event)" v-model="tableOptions.filterParam.periodid" multiple class="form-control period" id="period">
                                            <option v-for="(g, i) in periodData" :value="g.periodid">{{ g.title }}</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-12 col-sm-9 col-md-9 col-lg-4">
                                    <div class="form-group">
                                        <label>Geo Location</label>
                                        <select class="select2 form-control" @change="setLocation()" placeholder="Geo Location" >
                                            <option value="">Select Geo Location</option>
                                            <option v-for="(g, i) in geoData" :value="i">{{ g.geo_string }}</option>
                                        </select>
                                    </div>
                                </div>
                      
                                <div class="col-12 col-sm-3 col-md-3 col-lg-2">
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
                                    <th @click="sort(0)" class="pl-1 pr-2">
                                        Issue ID
                                        <i class="feather icon-chevron-up sort-up" :class="(tableOptions.orderField == 0 && tableOptions.orderDir =='asc')? 'active-sort': ''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 0 && tableOptions.orderDir =='desc')? 'active-sort': ''"></i>
                                    </th>
                                    <th @click="sort(1)" class="pl-1 pr-2">
                                        CDD Lead Details
                                        <i class="feather icon-chevron-up sort-up" :class="(tableOptions.orderField == 1 && tableOptions.orderDir =='asc')? 'active-sort': ''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 1 && tableOptions.orderDir =='desc')? 'active-sort': ''"></i>
                                    </th>
                                    <!--
                                    <th @click="sort(3)" class="pl-1 pr-2 text-wrap">
                                        Geo String
                                        <i class="feather icon-chevron-up sort-up" :class="(tableOptions.orderField == 3 && tableOptions.orderDir =='asc')? 'active-sort': ''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 3 && tableOptions.orderDir =='desc')? 'active-sort': ''"></i>
                                    </th>
                                    -->
                                    <th @click="sort(4)" class="pl-1 pr-2">
                                       Drug
                                        <i class="feather icon-chevron-up sort-up" :class="(tableOptions.orderField == 4 && tableOptions.orderDir =='asc')? 'active-sort': ''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 4 && tableOptions.orderDir =='desc')? 'active-sort': ''"></i>
                                    </th>
                                    <th @click="sort(14)" class="pl-1 pr-2">
                                       Period
                                        <i class="feather icon-chevron-up sort-up" :class="(tableOptions.orderField == 14 && tableOptions.orderDir =='asc')? 'active-sort': ''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 14 && tableOptions.orderDir =='desc')? 'active-sort': ''"></i>
                                    </th>
                                    <th @click="sort(5)" class="pl-1 pr-2">
                                        Issued
                                        <i class="feather icon-chevron-up sort-up" :class="(tableOptions.orderField == 5 && tableOptions.orderDir =='asc')? 'active-sort': ''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 5 && tableOptions.orderDir =='desc')? 'active-sort': ''"></i>
                                    </th>
                                    <th @click="sort(6)" class="pl-1 pr-2">
                                        Pending
                                        <i class="feather icon-chevron-up sort-up" :class="(tableOptions.orderField == 6 && tableOptions.orderDir =='asc')? 'active-sort': ''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 6 && tableOptions.orderDir =='desc')? 'active-sort': ''"></i>
                                    </th>
                                    <th @click="sort(7)" class="pl-1 pr-2">
                                        Confirmed
                                        <i class="feather icon-chevron-up sort-up" :class="(tableOptions.orderField == 7 && tableOptions.orderDir =='asc')? 'active-sort': ''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 7 && tableOptions.orderDir =='desc')? 'active-sort': ''"></i>
                                    </th>
                                    <th @click="sort(8)" class="pl-1 pr-2">
                                        Accepted
                                        <i class="feather icon-chevron-up sort-up" :class="(tableOptions.orderField == 8 && tableOptions.orderDir =='asc')? 'active-sort': ''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 8 && tableOptions.orderDir =='desc')? 'active-sort': ''"></i>
                                    </th>
                                    <th @click="sort(9)" class="pl-1 pr-2">
                                        Returned
                                        <i class="feather icon-chevron-up sort-up" :class="(tableOptions.orderField == 9 && tableOptions.orderDir =='asc')? 'active-sort': ''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 9 && tableOptions.orderDir =='desc')? 'active-sort': ''"></i>
                                    </th>
                                    <th @click="sort(10)" class="pl-1 pr-2">
                                        Reconciled
                                        <i class="feather icon-chevron-up sort-up" :class="(tableOptions.orderField == 10 && tableOptions.orderDir =='asc')? 'active-sort': ''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 10 && tableOptions.orderDir =='desc')? 'active-sort': ''"></i>
                                    </th>
                                    <th width="40px" class="pl-0 pr-0" v-if="permission.permission_value >=2"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(g, i) in tableData">
                                    <td class="pl-1 pr-2">{{g.issue_id}}</td>
                                    <td class="pl-1 pr-1">
                                        <div class="d-flex justify-content-left align-items-center">
                                            <div class="d-flex flex-column">
                                                <small class="user_name text-truncate text-body">
                                                    <span>{{checkIfEmpty(g.fullname)}}</span>
                                                </small>
                                                <small class="emp_post text-primary text-left">
                                                    <span class="badge badge-light-primary">{{g.loginid}}</span>
                                                </small>
                                            </div>
                                        </div>
                                    </td>
                                    <!--
                                    <td class="pl-1 pr-1 text-wrap">{{ capitalize(g.geo_string) }}</td>
                                    -->
                                    <td class="pl-1 pr-1"> {{ g.drug }}</td>
                                    <td class="pl-1 pr-1"> {{ g.period }}</td>
                                    <td class="pl-1 pr-1">{{convertStringNumberToFigures(g.issued)}}</td>
                                    <td class="pl-1 pr-1">{{convertStringNumberToFigures(g.pending)}}</td>
                                    <td class="pl-1 pr-1">{{convertStringNumberToFigures(g.confirmed)}}</td>
                                    <td class="pl-1 pr-1">{{convertStringNumberToFigures(g.accepted)}}</td>
                                    <td class="pl-1 pr-1">{{convertStringNumberToFigures(g.returned)}}</td>
                                    <td class="pl-1 pr-1">{{convertStringNumberToFigures(g.reconciled)}}</td>

                                    <td class="pl-0 pr-1"  v-if="permission.permission_value >=2">
                                      <button class="btn btn-primary btn-sm px-50" @click="unlockIcc(g.cdd_lead_id, i)">Unlock</button>
                                    </td>
                                    
                                </tr>
                                <tr v-if="tableData.length == 0">
                                  <td class="text-center pt-2" :colspan="permission.permission_value < 2 ? 7 : 8">
                                    <small>No Data</small>
                                  </td>
                                </tr>
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
                          <button type="reset" class="close" @click="hideGetIccDetails()" data-dismiss="modal">×</button>
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
                                    <tr v-for="(g, i) in iccIssuedDetails">
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

                              <div class="card mb-0 btmlr drug-card">
                                <div class="card-header py-50 d-flex justify-content-between">
                                  <h5 class="card-title font-small-2 font-weight-bolder mb-25 text-default">SPAQ 1</h5>
                                </div>
                                <div class="card-body pb-50 icc-card d-flex align-items-end">
                                  <div class="w-100">
                                    <div class="row gy-3">
                                      <div class="col-sm-3 col-md-3 col-lg-3 col-6">
                                        <div class="d-flex align-items-center">
                                          <div class="badge rounded bg-label-primary me-4 p-50"><i class="ti ti-package-export ti-md"></i></div>
                                          <div class="card-info">
                                            <h5 class="mb-0">{{convertStringNumberToFigures(getTopIccReturned.sumSpaq1Returned)}}</h5>
                                            <small>Total Returned</small>
                                          </div>
                                        </div>
                                      </div>
                                      
                                      <div class="col-sm-3 col-md-3 col-lg-3 col-6">
                                        <div class="d-flex align-items-center">
                                          <div class="badge rounded bg-label-info me-4 p-50"><i class="ti ti-pill ti-md"></i></div>
                                          <div class="card-info w-100">
                                            <h5 class="mb-0">{{convertStringNumberToFigures(getTopIccReturned.sumSpaq1FullReturn)}}</h5>
                                            <div class="d-flex align-items-center w-100">
                                              <div class="progress w-100 me-3" style="height: 6px;">
                                                <div class="progress-bar"
                                                  :class="progressBarStatus(getTopIccReturned.sumSpaq1Returned, getTopIccReturned.sumSpaq1FullReturn)"
                                                  :style="{ width: progressBarWidth(getTopIccReturned.sumSpaq1Returned, getTopIccReturned.sumSpaq1FullReturn)}"
                                                  :aria-valuenow="{ width: progressBarWidth(getTopIccReturned.sumSpaq1Returned, getTopIccReturned.sumSpaq1FullReturn)}"
                                                  aria-valuemin="0" aria-valuemax="100"></div>
                                              </div>
                                              <span class="text-heading ml-50">{{progressBarWidth(getTopIccReturned.sumSpaq1Returned,
                                                getTopIccReturned.sumSpaq1FullReturn)}}</span>
                                            </div>
                                            <small>Full Return</small>
                                          </div>
                                        </div>
                                      </div>
                                      <div class="col-sm-3 col-md-3 col-lg-3 col-6">
                                        <div class="d-flex align-items-center">
                                          <div class="badge rounded bg-label-danger me-4 p-50"><i class="ti ti-bucket-droplet ti-md"></i></div>
                                          <div class="card-info w-100">
                                            <h5 class="mb-0">{{convertStringNumberToFigures(getTopIccReturned.sumSpaq1Wasted)}}</h5>
                                            <div class="d-flex align-items-center w-100">
                                              <div class="progress w-100 me-3" style="height: 6px;">
                                                <div class="progress-bar"
                                                  :class="progressBarStatus(getTopIccReturned.sumSpaq1Returned, getTopIccReturned.sumSpaq1Wasted)"
                                                  :style="{ width: progressBarWidth(getTopIccReturned.sumSpaq1Returned, getTopIccReturned.sumSpaq1Wasted)}"
                                                  :aria-valuenow="{ width: progressBarWidth(getTopIccReturned.sumSpaq1Returned, getTopIccReturned.sumSpaq1Wasted)}"
                                                  aria-valuemin="0" aria-valuemax="100"></div>
                                              </div>
                                              <span class="text-heading ml-50">{{progressBarWidth(getTopIccReturned.sumSpaq1Returned,
                                                getTopIccReturned.sumSpaq1Wasted)}}</span>
                                            </div>
                                            <small>Wasted</small>
                                          </div>
                                        </div>
                                      </div>
                                      <div class="col-sm-3 col-md-3 col-lg-3 col-6">
                                        <div class="d-flex align-items-center">
                                          <div class="badge rounded bg-label-warning me-4 p-50"><i class="ti ti-circle-half-2 ti-md"></i></div>
                                          <div class="card-info w-100">
                                            <h5 class="mb-0">{{convertStringNumberToFigures(getTopIccReturned.sumSpaq1partialReturn)}}</h5>
                                            <div class="d-flex align-items-center w-100">
                                              <div class="progress w-100 me-3" style="height: 6px;">
                                                <div class="progress-bar"
                                                  :class="progressBarStatus(getTopIccReturned.sumSpaq1Returned, getTopIccReturned.sumSpaq1partialReturn)"
                                                  :style="{ width: progressBarWidth(getTopIccReturned.sumSpaq1Returned, getTopIccReturned.sumSpaq1partialReturn)}"
                                                  :aria-valuenow="{ width: progressBarWidth(getTopIccReturned.sumSpaq1Returned, getTopIccReturned.sumSpaq1partialReturn)}"
                                                  aria-valuemin="0" aria-valuemax="100"></div>
                                              </div>
                                              <span class="text-heading ml-50">{{progressBarWidth(getTopIccReturned.sumSpaq1Returned,
                                                getTopIccReturned.sumSpaq1partialReturn)}}</span>
                                            </div>
                                            <small>Partial Return</small>
                                          </div>
                                        </div>
                                      </div>

                                    </div>
                                  </div>
                                </div>
                              </div>

                              <div class="card mb-0 btmlr ttlr">
                                <div class="card-header py-50 d-flex justify-content-between">
                                  <h5 class="card-title font-small-2 font-weight-bolder mb-25 text-default">SPAQ 2</h5>
                                </div>
                                <div class="card-body pb-50 icc-card d-flex align-items-end">
                                  <div class="w-100">
                                    <div class="row gy-3">
                                      <div class="col-sm-3 col-md-3 col-lg-3 col-6">
                                        <div class="d-flex align-items-center">
                                          <div class="badge rounded bg-label-primary me-4 p-50"><i class="ti ti-package-export ti-md"></i>
                                          </div>
                                          <div class="card-info w-100">
                                            <h5 class="mb-0">{{convertStringNumberToFigures(getTopIccReturned.sumSpaq2Returned)}}</h5>
                                            <small>Total Returned</small>
                                          </div>
                                        </div>
                                      </div>
                                      <div class="col-sm-3 col-md-3 col-lg-3 col-6">
                                        <div class="d-flex align-items-center">
                                          <div class="badge rounded bg-label-info me-4 p-50"><i class="ti ti-pill ti-md"></i></div>
                                          <div class="card-info w-100">
                                            <h5 class="mb-0">{{convertStringNumberToFigures(getTopIccReturned.sumSpaq2FullReturn)}}</h5>
                                            <div class="d-flex align-items-center w-100">
                                              <div class="progress w-100 me-3" style="height: 6px;">
                                                <div class="progress-bar"
                                                  :class="progressBarStatus(getTopIccReturned.sumSpaq2Returned, getTopIccReturned.sumSpaq2FullReturn)"
                                                  :style="{ width: progressBarWidth(getTopIccReturned.sumSpaq2Returned, getTopIccReturned.sumSpaq2FullReturn)}"
                                                  :aria-valuenow="{ width: progressBarWidth(getTopIccReturned.sumSpaq2Returned, getTopIccReturned.sumSpaq2FullReturn)}"
                                                  aria-valuemin="0" aria-valuemax="100"></div>
                                              </div>
                                              <span class="text-heading ml-50">{{progressBarWidth(getTopIccReturned.sumSpaq2Returned,
                                                getTopIccReturned.sumSpaq2FullReturn)}}</span>
                                            </div>
                                            <small>Full Return</small>
                                          </div>
                                        </div>
                                      </div>
                                      <div class="col-sm-3 col-md-3 col-lg-3 col-6">
                                        <div class="d-flex align-items-center">
                                          <div class="badge rounded bg-label-danger me-4 p-50"><i class="ti ti-bucket-droplet ti-md"></i>
                                          </div>
                                          <div class="card-info w-100">
                                            <h5 class="mb-0">{{convertStringNumberToFigures(getTopIccReturned.sumSpaq2Wasted)}}</h5>
                                            <div class="d-flex align-items-center w-100">
                                              <div class="progress w-100 me-3" style="height: 6px;">
                                                <div class="progress-bar"
                                                  :class="progressBarStatus(getTopIccReturned.sumSpaq2Returned, getTopIccReturned.sumSpaq2Wasted)"
                                                  :style="{ width: progressBarWidth(getTopIccReturned.sumSpaq2Returned, getTopIccReturned.sumSpaq2Wasted)}"
                                                  :aria-valuenow="{ width: progressBarWidth(getTopIccReturned.sumSpaq2Returned, getTopIccReturned.sumSpaq2Wasted)}"
                                                  aria-valuemin="0" aria-valuemax="100"></div>
                                              </div>
                                              <span class="text-heading ml-50">{{progressBarWidth(getTopIccReturned.sumSpaq2Returned,
                                                getTopIccReturned.sumSpaq2Wasted)}}</span>
                                            </div>
                                            <small>Wasted</small>
                                          </div>
                                        </div>
                                      </div>
                                      <div class="col-sm-3 col-md-3 col-lg-3 col-6">
                                        <div class="d-flex align-items-center">
                                          <div class="badge rounded bg-label-warning me-4 p-50"><i class="ti ti-circle-half-2 ti-md"></i>
                                          </div>
                                          <div class="card-info w-100">
                                            <h5 class="mb-0">{{convertStringNumberToFigures(getTopIccReturned.sumSpaq2partialReturn)}}</h5>
                                            <div class="d-flex align-items-center w-100">
                                              <div class="progress w-100 me-3" style="height: 6px;">
                                                <div class="progress-bar"
                                                  :class="progressBarStatus(getTopIccReturned.sumSpaq2Returned, getTopIccReturned.sumSpaq2partialReturn)"
                                                  :style="{ width: progressBarWidth(getTopIccReturned.sumSpaq2Returned, getTopIccReturned.sumSpaq2partialReturn)}"
                                                  :aria-valuenow="{ width: progressBarWidth(getTopIccReturned.sumSpaq2Returned, getTopIccReturned.sumSpaq2partialReturn)}"
                                                  aria-valuemin="0" aria-valuemax="100"></div>
                                              </div>
                                              <span class="text-heading ml-50">{{progressBarWidth(getTopIccReturned.sumSpaq2Returned,
                                                getTopIccReturned.sumSpaq2partialReturn)}}</span>
                                            </div>
                                            <small>Partial Return</small>
                                          </div>
                                        </div>
                                      </div>

                                    </div>
                                  </div>
                                </div>
                              </div>
                            
                              <div class="info-container table-responsive pt-25">
                                <table class="table">
                                  <thead>
                                    <th>Receiver Details</th>
                                    <th>Visit</th>
                                    <th>Received Drug</th>
                                    <th>Full Dose Qty</th>
                                    <th>Partial Qty</th>
                                    <th>Wasted Qty</th>
                                    <th>Received Date</th>
                                    <th>Created Date</th>
                                  </thead>
                                  <tbody>
                                    <tr v-for="(g, i) in iccReceivedDetails">
                                      <td>
                                        <div class="d-flex justify-content-left align-items-center">
                                          <div class="d-flex flex-column">
                                            <span class="user_name text-truncate text-body">
                                              <span class="fw-bolder">{{capitalize(g.receiver_name)}}</span>
                                            </span>
                                            <small class="emp_post text-primary text-left">
                                              <span class="badge badge-light-primary">{{g.receiver_loginid}}</span>
                                            </small>
                                          </div>
                                        </div>
                                      </td>
                                      <td>{{checkIfEmpty(g.period)}}</td>
                                      <td>{{checkIfEmpty(g.received_drug)}}</td>
                                      <td>{{convertStringNumberToFigures(g.full_dose_qty)}}</td>
                                      <td>{{convertStringNumberToFigures(g.partial_qty)}}</td>
                                      <td>{{convertStringNumberToFigures(g.wasted_qty)}}</td>
                                      <td>{{displayDayMonthYearTime(g.received_date)}}</td>
                                      <td>{{displayDayMonthYearTime(g.created)}}</td>
                                    </tr>

                                  </tbody>
                                </table>
                              </div>
                            </div>
                          </div>
  
                        </div>
                        <div class="modal-footer">
                              <button type="reset" class="btn btn-secondary" data-dismiss="modal" @click="hideGetIccDetails()">Close</button>
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
