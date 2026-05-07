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
    const tableContainer = document.querySelectorAll(".scrollBox");
    if (tableContainer) {
      tableContainer.forEach(function (element) {
        new PerfectScrollbar(element);
      });
    }
    /*  Manages events Listening    */
    // EventBus.$on("g-event-goto-page", this.gotoPageHandler);
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
                    <child_list />
                    <drug_admin />
                    <referral_admin />
                    <icc_admin />
                </div>

            </div>
        </div>
    `,
});

Vue.component("apexchart", VueApexCharts);

// Child List Page
Vue.component("child_list", {
  data: function () {
    return {
      url: common.DataService,
      filterUrl: "",
      geoData: [],
      periodData: [],
      childDetails: [],
      reportLevel: 1,
      filterId: "",
      lgaId: "",
      lgaName: "",
      wardId: "",
      wardName: "",
      dpId: "",
      dpName: "",
      checkIfFilterOn: false,
      filterState: false,
      filters: false,
      tableOptions: {
        filterParam: {
          periodid: [],
          visitTitle: "",
          reportDate: "",
        },
      },
      statData: {
        tableData: [],
        chartData: [],
      },
      series: [],
      allChartData: [],
      chartOptions: {
        xaxis: {
          title: {
            text: "",
          },
        },
      },
    };
  },
  mounted() {
    /* Manages event listening */
    this.getAllPeriodLists();
    this.loadTableData(0, "");

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

    initializeSelect(".period", this.setPeriodTitle, true, "Select Options");

    $(".select2-selection__arrow").html(
      '<i class="feather icon-chevron-down"></i>'
    );

    $(".date").flatpickr({
      altInput: true,
      altFormat: "F j, Y",
      dateFormat: "Y-m-d",
      mode: "range",
    });
  },
  methods: {
    loadTableData(filterId, title) {
      const { reportLevel, tableOptions } = this;

      const periodIdsCopy = [...this.tableOptions.filterParam.periodid];
      const periodIds = this.joinWithCommaAnd(periodIdsCopy, true);

      // const periodIds = this.joinWithCommaAnd(
      //   tableOptions.filterParam.periodid,
      //   true
      // );
      const reportDate = tableOptions.filterParam.reportDate || "";
      const [start_date = "", end_date = ""] = reportDate.split(" to ");

      let queryUrl = this.url;
      let queryParams = {
        pid: periodIds,
        sdate: start_date,
        edate: end_date,
      };

      switch (reportLevel) {
        case 1:
          this.filterId = 0;
          queryUrl += "?qid=1111";
          break;
        case 2:
          this.lgaId = filterId;
          this.lgaName = title || this.lgaName;
          queryUrl += `?qid=1112&filterId=${this.lgaId}`;
          break;
        case 3:
          this.wardId = filterId;
          this.wardName = title || this.wardName;
          queryUrl += `?qid=1113&filterId=${this.wardId}`;
          break;
        default:
          return; // handle unexpected reportLevel
      }

      // Append common parameters to queryUrl
      for (const key in queryParams) {
        if (queryParams[key]) {
          queryUrl += `&${key}=${queryParams[key]}`;
        }
      }

      this.loadDashboardData(queryUrl);
    },
    async loadDashboardData(url) {
      try {
        overlay.show();
        this.filterUrl = url;
        const response = await axios.get(url);
        if (response.data.result_code == 200) {
          this.statData.tableData = response.data.data.table;
          this.statData.chartData = response.data.data.chart;
          this.statData.chartData.xAxisLabel = "Days";
          // console.log(this.statData.chartData);
          // console.log(url);
          this.reportLevel = response.data.level;

          this.plotChart();
        } else {
          this.statData.tableData = [];
          alert.Error("ERROR", response.data.message);
        }
        overlay.hide();
      } catch (error) {
        overlay.hide();
        alert.Error("ERROR", error);
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
    fireFilterEvent() {
      const periodIds = [...this.tableOptions.filterParam.periodid];
      // const periodIds = this.joinWithCommaAnd(periodIdsCopy, true);

      EventBus.$emit("fire-event-apply-filter", [
        periodIds,
        this.tableOptions.filterParam.visitTitle,
        this.tableOptions.filterParam.reportDate,
      ]);
    },
    fireRefreshEvent() {
      EventBus.$emit("fire-event-refresh");
    },
    fireClearFilter() {
      EventBus.$emit("fire-event-clear-filter");
    },
    fireRemoveSingleFilter(column_name) {
      EventBus.$emit("fire-event-remove-single-filter", column_name);
    },
    applyFilter() {
      // Check if any filter fields are filled
      let checkFill = 0;
      checkFill += this.tableOptions.filterParam.periodid.length > 0 ? 1 : 0;
      checkFill += this.tableOptions.filterParam.reportDate.length > 0 ? 1 : 0;

      if (checkFill > 0) {
        this.toggleFilter();
        this.filters = this.checkIfFilterOn = true;
        //	- Event Fire
        this.fireFilterEvent();
        /*
        const periodIds = this.joinWithCommaAnd(
          this.tableOptions.filterParam.periodid,
          true
        );
        */
        const { filterParam } = this.tableOptions;
        const periodIdsCopy = [...filterParam.periodid];
        const periodIds = this.joinWithCommaAnd(periodIdsCopy, true);

        let reportDate =
          this.tableOptions.filterParam.reportDate == null
            ? ""
            : this.tableOptions.filterParam.reportDate;
        let start_date =
          reportDate.split(" to ")[0] == undefined
            ? ""
            : reportDate.split(" to ")[0];
        let end_date =
          reportDate.split(" to ")[1] == undefined
            ? ""
            : reportDate.split(" to ")[1];

        let url =
          this.cleanUrl(this.filterUrl) +
          "&pid=" +
          periodIds +
          "&sdate=" +
          start_date +
          "&edate=" +
          end_date;

        this.loadDashboardData(url);
      } else {
        this.clearAllFilter();
      }
    },
    loadNewData() {
      let checkFill = 0;
      checkFill += this.tableOptions.filterParam.periodid.length > 0 ? 1 : 0;
      checkFill += this.tableOptions.filterParam.reportDate.length > 0 ? 1 : 0;

      const periodIds = this.joinWithCommaAnd(
        this.tableOptions.filterParam.periodid,
        true
      );
      let reportDate =
        this.tableOptions.filterParam.reportDate == null
          ? ""
          : this.tableOptions.filterParam.reportDate;
      let start_date =
        reportDate.split(" to ")[0] == undefined
          ? ""
          : reportDate.split(" to ")[0];
      let end_date =
        reportDate.split(" to ")[1] == undefined
          ? ""
          : reportDate.split(" to ")[1];

      let url =
        this.cleanUrl(this.filterUrl) +
        "&pid=" +
        periodIds +
        "&sdate=" +
        start_date +
        "&edate=" +
        end_date;

      this.loadDashboardData(url);
      if (checkFill > 0) {
        this.toggleFilter();
        this.filterState = this.checkIfFilterOn = false;
      } else {
        this.filters = this.checkIfFilterOn = false;
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

      if (column_name === "visitTitle") {
        filterParam.periodid = [];
        filterParam.visitTitle = "";
        $(".period").val("").trigger("change");
      }

      if (column_name === "reportDate") {
        this.clearFlatpickr("date");
      }

      // Check if there are any active filters
      const hasActiveFilters = Object.values(filterParam).some((value) =>
        Array.isArray(value) ? value.length > 0 : value !== ""
      );

      this.filters = this.checkIfFilterOn = hasActiveFilters;
      this.fireRemoveSingleFilter(column_name);

      //  Reload table data
      this.loadNewData();
      // this.loadDashboardData(this.filterUrl);
    },
    clearAllFilter() {
      this.filters = false;
      // Reset filter parameters
      Object.assign(this.tableOptions.filterParam, {
        periodid: [],
        visitTitle: "",
        reportDate: "",
      });

      $(".period").val("").trigger("change");
      this.clearFlatpickr("date");

      this.fireClearFilter();
      let url = this.cleanUrl(this.filterUrl);

      this.loadDashboardData(url);
    },
    clearFlatpickr(dateClass) {
      const flatpickrInstance = $("." + dateClass)[0]._flatpickr;
      if (flatpickrInstance) {
        flatpickrInstance.clear();
      }
    },
    checkAndHideFilter(dataToCheck) {
      let arrayToExclude = ["periodid"];

      if (!arrayToExclude.includes(dataToCheck)) {
        return true;
      } else {
        return false;
      }
    },
    refreshData() {
      this.getAllPeriodLists();
      this.fireRefreshEvent();
      this.loadDashboardData(this.filterUrl);
    },
    controlBreadCrum(filterId, reportLevel, title) {
      this.reportLevel = reportLevel;
      this.loadTableData(filterId, title);
    },
    plotChart() {
      const yAxislabel = "Child Population";
      const xAxisLabel = this.statData.chartData.xAxisLabel;
      let self = this;
      const options = {
        chart: {
          type: "bar",
          stacked: true,
        },
        colors: ["#7367f0", "#b9b3f7"],
        xaxis: {
          categories: [],
        },
        legend: {
          position: "bottom",
          formatter: function (seriesName) {
            return self.capitalize(seriesName);
          },
          fontFamily: "Arial, sans-serif",
          fontSize: "12px",
        },
        title: {
          text: "Child Registration",
          align: "center",
        },
        yaxis: {
          labels: {
            formatter: function (val) {
              return parseInt(val).toLocaleString();
            },
          },
        },
        plotOptions: {
          bar: {
            horizontal: false,
            dataLabels: {
              position: "top", // Place data labels on top of the bars
            },
          },
        },
        dataLabels: {
          style: {
            colors: ["#000"], // Change value color on bars here
          },
          enabled: true,
          formatter: function (val, opts) {
            return parseInt(val).toLocaleString();
          },
          offsetY: -18,
          style: {
            fontSize: "12px",
            colors: ["#000"],
          },
        },
        noData: {
          text: "No data available, kindly refresh",
          align: "center",
          verticalAlign: "middle",
          offsetX: 0,
          offsetY: 0,
          style: {
            color: "#333",
            fontSize: "14px",
          },
        },
      };

      this.chartOptions = options;

      const newData = {
        categories: this.statData.chartData[1],
      };

      this.series = this.statData.chartData[0];
      // console.log(this.series);

      this.chartOptions.xaxis.categories.splice(
        0,
        this.chartOptions.xaxis.categories.length,
        ...newData.categories
      );
    },
    cleanUrl(url) {
      const urlObj = new URL(url);
      const params = urlObj.searchParams;
      const allowedParams = ["qid", "filterId"];

      // Create a copy of the keys to avoid modifying the object while iterating
      const keysToRemove = [];

      for (const key of params.keys()) {
        if (!allowedParams.includes(key)) {
          keysToRemove.push(key);
        }
      }

      // Remove all parameters that are not allowed
      for (const key of keysToRemove) {
        params.delete(key);
      }

      return urlObj.toString();
    },
    capitalize(word) {
      if (word) {
        const loweredCase = word.toLowerCase();
        return word[0].toUpperCase() + loweredCase.slice(1);
      } else {
        return word;
      }
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
    convertStringNumberToFigures(d) {
      let data = d ? parseInt(d) : 0;
      return data ? data.toLocaleString() : 0;
    },
    checkIfEmpty(data) {
      if (data === null || data === "") {
        return "Nil";
      } else {
        return data;
      }
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
  },
  computed: {
    getTopChildStat() {
      const result = this.statData.tableData.reduce(
        (acc, curr) => {
          acc.male += parseInt(curr.male, 10);
          acc.female += parseInt(curr.female, 10);
          acc.total += curr.total;
          return acc;
        },
        { male: 0, female: 0, total: 0 }
      );
      return result;
    },
  },
  template: `

        <div class="row" id="basic-table">

            <div class="col-md-6 col-sm-6 col-6 col-sm-6">
                <h2 class="content-header-title header-txt float-left mb-0">SMC Dashboard</h2>
            </div>
            <div class="col-md-6 col-sm-6 col-6 text-md-right text-right d-md-block">
                <div class="btn-group">
                    <button type="button" class="btn btn-outline-primary round searchBtn" @click="refreshData()" data-toggle="tooltip" data-placement="top" title="Refresh">
                        <i class="feather icon-refresh-cw"></i>         
                    </button>
                    <button type="button" class="btn btn-outline-primary round searchBtn" @click="toggleFilter()" data-toggle="tooltip" data-placement="top" title="Filter">
                        <i class="feather" :class="filterState ? 'icon-x' : 'icon-filter'"></i>               
                    </button>     
                </div>
            </div>

            <div class="col-12" v-if="filters">
                <div class="col-12 filter-bar">
                    <span class="badge badge-dark filter-box" @click="removeSingleFilter(i)" v-for="(filterParam, i) in tableOptions.filterParam" v-if="filterParam.length > 0 && checkAndHideFilter(i)">{{splitWordAndCapitalize(i)}}: {{filterParam}} <i class="feather icon-x"></i> </span>
                    <a href="#" class="float-right clear-filter" @click="clearAllFilter()">Clear</a>
                </div>
            </div>

            <div class="col-12 mt-1" v-show="filterState">
                <div class="card mb-1">
                    <div class="card-body py-1">
                        <form id="filterForm">

                            <div class="row">
                                <div class="col-12 col-sm-6 col-md-5 col-lg-5">
                                    <div class="form-group">
                                        <label>Visit</label>
                                        <select @change="setPeriodTitle($event)" v-model="tableOptions.filterParam.periodid" class="form-control period" id="period">
                                            <option v-for="(g, i) in periodData" :value="g.periodid">{{ g.title }}</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-12 col-sm-6 col-md-4 col-lg-5">
                                    <div class="form-group date_filter">
                                        <label>Report Date Range</label>
                                        <input type="text" id="reg_date" v-model="tableOptions.filterParam.reportDate" class="form-control reg_date date" placeholder="Report Date Range" name="reg_date" />
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
                </div>
            </div>
            <div class="col-12">
                <div class="breadcrumb-wrapper reporting-dashboard">
                    <ol class="breadcrumb pl-0">
                        <li class="breadcrumb-item" v-if="reportLevel>1" :class="reportLevel==2? 'active': ''" @click="controlBreadCrum(0, 1, '')">Child Registration</li>
                        <li class="breadcrumb-item" v-if="reportLevel>2" :class="reportLevel==3? 'active': ''" @click="controlBreadCrum(lgaId, 2, lgaName)">{{lgaName}} LGA Wards</li>
                        <li class="breadcrumb-item" v-if="reportLevel>3" :class="reportLevel==4? 'active': ''" @click="controlBreadCrum(wardId, 3, wardName)">{{wardName}} Wards DPs</li>
                        <li class="breadcrumb-item" v-if="reportLevel>4" :class="reportLevel==5? 'active': ''" @click="controlBreadCrum(dpId, 4, dpName)">{{dpName}} Child DPs</li>
                    </ol>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-6  col-sm-12 mt-0">
                <div class="card mb-0 btmlr">
                  <div class="card-widget-separator-wrapper">
                    <div class="card-body pb-75 pt-75 card-widget-separator">
                      <div class="row gy-4 gy-sm-1">
                        <div class="col-sm-6 col-lg-4">
                          <div class="d-flex justify-content-between align-items-start card-widget-1 border-end pb-sm-0">
                            <div>
                              <h6 class="mb-50 small text-primary">Male</h6>
                              <h4 class="mb-0">
                                {{convertStringNumberToFigures(getTopChildStat.male)}}</h4>
                            </div>
                            <span class="avatar1 me-sm-4">
                              <span class="avatar-initial bg-label-success rounded"><i class="ti-md ti ti-man"></i></span>
                            </span>
                          </div>
                          <hr class="d-none d-sm-block d-lg-none">
                        </div>

                        <div class="col-sm-6 col-lg-4">
                          <div class="d-flex justify-content-between align-items-start card-widget-2 border-end pb-sm-0">
                            <div>
                              <h6 class="mb-50 small text-primary">Female</h6>
                              <h4 class="mb-0">
                                {{convertStringNumberToFigures(getTopChildStat.female)}}
                              </h4>
                            </div>
                            <span class="avatar1 me-sm-4">
                              <span class="avatar-initial bg-label-info rounded"><i class="ti-md ti ti-woman"></i></span>
                            </span>
                          </div>
                            <hr class="d-none d-sm-block d-lg-none">
                        </div>

                        <div class="col-sm-12 col-lg-4">
                          <div class="d-flex justify-content-between align-items-start card-widget-1 pb-sm-0">
                            <div>
                              <h6 class="mb-50 small text-primary">Total Children</h6>
                              <h4 class="mb-0">
                                {{convertStringNumberToFigures(getTopChildStat.male + getTopChildStat.female)}}
                              </h4>
                            </div>
                            <hr class="d-none d-sm-block d-lg-none me-4">
                            <span class="avatar1 me-sm-4">
                              <span class="avatar-initial bg-label-primary rounded"><i class="ti-md ti ti-users-group"></i></span>
                            </span>
                          </div>
                        </div>

                      </div>
                    </div>
                  </div>
                </div>

                <div class="card" style="height: 350px !important;">
                    
                    <div class="table-responsive scrollBox">
                        <table class="table table-fixed border-top table-striped table-hover table-hover-animation">
                            <thead>
                                <tr>
                                    <th :colspan="reportLevel< 4? 2: 1">Description</th>
                                    <th>Male</th>
                                    <th>Female</th>
                                    <th class="text-left">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="reportLevel<5" v-for="g in statData.tableData" @click="loadTableData(g.id, g.title)">
                                    <td style="padding-left: 1rem !important; padding-right: .2rem !important; width:34px" v-if="reportLevel<4"><i class="ti ti-circle-plus text-primary"></i></td>
                                    <td style="padding-left: .4rem !important;">{{capitalize(g.title) }}</td>
                                    <td>
                                      <small class="fw-bolder">{{g.male}}</small>
                                    </td>
                                    <td>{{g.female}}</td>
                                    <td>{{ convertStringNumberToFigures(parseInt(g.total)) }}</td>
                                </tr>
                                
                                <tr v-if="statData.tableData.length == 0"><td class="text-center pt-2" :colspan="reportLevel< 4? 6: 5"><small>No Data</small></td></tr>
                            </tbody>
                        </table>

                    </div>
                    <div class="mb-50"></div>

                    
                </div>
            </div>

            <!-- SMC Bar Chart: Start -->
            <div class="col-12 col-md-6 col-xl-6  col-sm-12 mb-0">
              <div class="card"  style="height: 416px !important;">

                <div class="card-body">
                          
                  <div class="tab-content p-0 ms-0 ms-sm-2">
                    <div class="tab-pane fade show active" id="hh-mobilized-id" role="tabpanel">
                      <apexchart height="372" type="bar" :options="chartOptions" :series="series"></apexchart>
                    </div>
                  </div>

                </div>
              </div>
            </div>
            <!-- SMC Bar Chart: End -->


        </div>
    `,
});

// Drug List Page
Vue.component("drug_admin", {
  data: function () {
    return {
      url: common.DataService,
      filterUrl: "",
      geoData: [],
      periodData: [],
      childDetails: [],
      reportLevel: 1,
      filterId: "",
      lgaId: "",
      lgaName: "",
      wardId: "",
      wardName: "",
      dpId: "",
      dpName: "",
      checkIfFilterOn: false,
      filterState: false,
      filters: false,
      tableOptions: {
        filterParam: {
          periodid: [],
          visitTitle: "",
          reportDate: "",
        },
      },
      statData: {
        tableData: [],
        chartData: [],
      },
      series: [],
      allChartData: [],
      chartOptions: {
        xaxis: {
          title: {
            text: "",
          },
        },
      },
    };
  },
  mounted() {
    /* Manages event listening */
    // - On Filter Apply, or Removed Event receiver
    EventBus.$on("fire-event-apply-filter", this.handleFilterChange);
    EventBus.$on("fire-event-refresh", this.refreshData);
    EventBus.$on("fire-event-clear-filter", this.clearAllFilter);
    EventBus.$on("fire-event-remove-single-filter", this.removeSingleFilter);

    this.loadTableData(0, "");
  },
  methods: {
    loadTableData(filterId, title) {
      const { reportLevel, tableOptions, url } = this;
      const periodIds = this.joinWithCommaAnd(
        tableOptions.filterParam.periodid,
        true
      );
      let { reportDate } = tableOptions.filterParam;

      // Handle undefined reportDate
      reportDate = reportDate || "";
      const [start_date = "", end_date = ""] = reportDate.split(" to ");

      let queryUrl = url;
      let queryParams = {
        pid: periodIds,
        sdate: start_date,
        edate: end_date,
      };

      switch (reportLevel) {
        case 1:
          this.filterId = 0;
          queryUrl += "?qid=1114";
          break;
        case 2:
          this.lgaId = filterId;
          this.lgaName = title || this.lgaName;
          queryUrl += `?qid=1115&filterId=${this.lgaId}`;
          break;
        case 3:
          this.wardId = filterId;
          this.wardName = title || this.wardName;
          queryUrl += `?qid=1116&filterId=${this.wardId}`;
          break;
        default:
          return; // handle unexpected reportLevel
      }

      // Append common parameters to queryUrl
      for (const key in queryParams) {
        if (queryParams[key]) {
          queryUrl += `&${key}=${queryParams[key]}`;
        }
      }

      this.loadDashboardData(queryUrl);
    },
    async loadDashboardData(url) {
      try {
        overlay.show();
        this.filterUrl = url;

        const response = await axios.get(url);
        if (response.data.result_code == 200) {
          // console.log(response.data);
          this.statData.tableData = response.data.data.table;
          this.statData.chartData = response.data.data.chart;
          this.statData.chartData.xAxisLabel = "Days";
          // console.log(this.statData.chartData);
          // console.log(url);
          this.reportLevel = response.data.level;

          this.plotChart();
        } else {
          this.statData.tableData = [];
          alert.Error("ERROR", response.data.message);
        }
        overlay.hide();
      } catch (error) {
        overlay.hide();
        alert.Error("ERROR", error);
      }
    },
    handleFilterChange(data) {
      this.tableOptions.filterParam.periodid = [...data[0]];
      this.tableOptions.filterParam.visitTitle = data[1];
      this.tableOptions.filterParam.reportDate = data[2];
      this.applyFilter();
    },
    controlBreadCrum(filterId, reportLevel, title) {
      this.reportLevel = reportLevel;
      this.loadTableData(filterId, title);
    },
    plotChart() {
      const yAxislabel = "Total SPAQ";
      const xAxisLabel = this.statData.chartData.xAxisLabel;
      let self = this;
      const options = {
        chart: {
          type: "bar",
          stacked: false,
        },
        colors: ["#FF9800", "#7367f0"],
        xaxis: {
          categories: [],
        },
        legend: {
          position: "bottom",
          formatter: function (seriesName) {
            return self.capitalize(seriesName);
          },
          fontFamily: "Arial, sans-serif",
          fontSize: "12px",
        },
        title: {
          text: "Drug Administration",
          align: "center",
        },
        yaxis: {
          labels: {
            formatter: function (val) {
              return parseInt(val).toLocaleString();
            },
          },
        },
        plotOptions: {
          bar: {
            horizontal: false,
            dataLabels: {
              position: "top", // Place data labels on top of the bars
            },
          },
        },
        dataLabels: {
          style: {
            colors: ["#000"], // Change value color on bars here
          },
          enabled: true,
          formatter: function (val, opts) {
            return parseInt(val).toLocaleString();
          },
          offsetY: -18,
          style: {
            fontSize: "12px",
            colors: ["#000"],
          },
        },
        noData: {
          text: "No data available, kindly refresh",
          align: "center",
          verticalAlign: "middle",
          offsetX: 0,
          offsetY: 0,
          style: {
            color: "#333",
            fontSize: "14px",
          },
        },
      };

      this.chartOptions = options;

      const newData = {
        categories: this.statData.chartData[1],
      };

      this.series = this.statData.chartData[0];
      // console.log(this.series);

      this.chartOptions.xaxis.categories.splice(
        0,
        this.chartOptions.xaxis.categories.length,
        ...newData.categories
      );
    },
    cleanUrl(url) {
      const urlObj = new URL(url);
      const params = urlObj.searchParams;
      const allowedParams = ["qid", "filterId"];

      // Create a copy of the keys to avoid modifying the object while iterating
      const keysToRemove = [];

      for (const key of params.keys()) {
        if (!allowedParams.includes(key)) {
          keysToRemove.push(key);
        }
      }

      // Remove all parameters that are not allowed
      for (const key of keysToRemove) {
        params.delete(key);
      }

      return urlObj.toString();
    },
    refreshData() {
      this.loadDashboardData(this.filterUrl);
    },
    applyFilter() {
      // Check if any filter fields are filled
      let checkFill = 0;
      checkFill += this.tableOptions.filterParam.periodid.length > 0 ? 1 : 0;
      checkFill += this.tableOptions.filterParam.reportDate.length > 0 ? 1 : 0;

      if (checkFill > 0) {
        /*
        const periodIds = this.joinWithCommaAnd(
          this.tableOptions.filterParam.periodid,
          true
        );
        */
        const { filterParam } = this.tableOptions;
        const periodIdsCopy = [...filterParam.periodid];
        const periodIds = this.joinWithCommaAnd(periodIdsCopy, true);

        let reportDate =
          this.tableOptions.filterParam.reportDate == null
            ? ""
            : this.tableOptions.filterParam.reportDate;
        let start_date =
          reportDate.split(" to ")[0] == undefined
            ? ""
            : reportDate.split(" to ")[0];
        let end_date =
          reportDate.split(" to ")[1] == undefined
            ? ""
            : reportDate.split(" to ")[1];

        let url =
          this.cleanUrl(this.filterUrl) +
          "&pid=" +
          periodIds +
          "&sdate=" +
          start_date +
          "&edate=" +
          end_date;
        this.loadDashboardData(url);
      } else {
        this.clearAllFilter();
      }
    },
    clearAllFilter() {
      this.filters = false;
      // Reset filter parameters
      Object.assign(this.tableOptions.filterParam, {
        periodid: [],
        visitTitle: "",
        reportDate: "",
      });

      let url = this.cleanUrl(this.filterUrl);
      console.log(url, "-----");
      this.loadDashboardData(url);
    },
    removeSingleFilter(column_name) {
      const filterParam = this.tableOptions.filterParam;

      // Clear the specified filter
      if (Array.isArray(filterParam[column_name])) {
        filterParam[column_name] = [];
      } else {
        filterParam[column_name] = "";
      }

      if (column_name === "visitTitle") {
        filterParam.periodid = [];
        filterParam.visitTitle = "";
      }

      //  Reload table data
      this.applyFilter();
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
    capitalize(word) {
      if (word) {
        const loweredCase = word.toLowerCase();
        return word[0].toUpperCase() + loweredCase.slice(1);
      } else {
        return word;
      }
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
    convertStringNumberToFigures(d) {
      let data = d ? parseInt(d) : 0;
      return data ? data.toLocaleString() : 0;
    },
    checkIfEmpty(data) {
      if (data === null || data === "") {
        return "Nil";
      } else {
        return data;
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
  },
  computed: {
    getTopChildStat() {
      const result = this.statData.tableData.reduce(
        (acc, curr) => {
          acc.eligible += parseInt(curr.eligible, 10);
          acc.non_eligible += parseInt(curr.non_eligible, 10);
          acc.referral += parseInt(curr.referral, 10);
          acc.spaq1 += parseInt(curr.spaq1, 10);
          acc.spaq2 += parseInt(curr.spaq2, 10);
          acc.total += parseInt(curr.total, 10);
          return acc;
        },
        {
          eligible: 0,
          non_eligible: 0,
          referral: 0,
          spaq1: 0,
          spaq2: 0,
          total: 0,
        }
      );
      return result;
    },
  },
  template: `

        <div class="row" id="basic-table">


            <div class="col-12">
                <div class="breadcrumb-wrapper reporting-dashboard">
                    <ol class="breadcrumb pl-0">
                        <li class="breadcrumb-item" v-if="reportLevel>1" :class="reportLevel==2? 'active': ''" @click="controlBreadCrum(0, 1, '')">Drug Administration</li>
                        <li class="breadcrumb-item" v-if="reportLevel>2" :class="reportLevel==3? 'active': ''" @click="controlBreadCrum(lgaId, 2, lgaName)">{{lgaName}} LGA Wards</li>
                        <li class="breadcrumb-item" v-if="reportLevel>3" :class="reportLevel==4? 'active': ''" @click="controlBreadCrum(wardId, 3, wardName)">{{wardName}} Wards DPs</li>
                        <li class="breadcrumb-item" v-if="reportLevel>4" :class="reportLevel==5? 'active': ''" @click="controlBreadCrum(dpId, 4, dpName)">{{dpName}} Child DPs</li>
                    </ol>
                </div>
            </div>
            <div class="col-12">
                <div class="card mb-0 btmlr drug-card">
                  <div class="card-widget-separator-wrapper">
                    <div class="card-body pb-75 pt-75 card-widget-separator">
                      <div class="row gy-4 gy-sm-1">
                        <div class="col-sm-4 col-md-4 col-lg-2">
                          <div class="d-flex justify-content-between align-items-start card-widget-1 border-end pb-sm-0">
                            <div>
                              <h6 class="mb-50 text-success small">Eligible</h6>
                              <h4 class="mb-0">
                                {{convertStringNumberToFigures(getTopChildStat.eligible)}}</h4>
                            </div>
                            <span class="avatar1 me-sm-4">
                              <span class="avatar-initial bg-label-success rounded"><i class="ti-md ti ti-accessible"></i></span>
                            </span>
                          </div>
                          <hr class="d-none d-sm-block d-lg-none">
                        </div>
                        <div class="col-sm-4 col-md-4 col-lg-2">
                          <div class="d-flex justify-content-between align-items-start card-widget-1 border-end pb-sm-0">
                            <div>
                              <h6 class="mb-50 text-danger small">Non Eligible</h6>
                              <h4 class="mb-0">
                                {{convertStringNumberToFigures(getTopChildStat.non_eligible)}}</h4>
                            </div>
                            <span class="avatar1 me-sm-4">
                              <span class="avatar-initial bg-label-danger rounded"><i class="ti-md ti ti-accessible-off"></i></span>
                            </span>
                          </div>
                          <hr class="d-none d-sm-block d-lg-none">
                        </div>
                        <div class="col-sm-4 col-md-4 col-lg-2">
                          <div class="d-flex justify-content-between align-items-start card-widget-2 border-end pb-sm-0">
                            <div>
                              <h6 class="mb-50 text-warning small">Referral</h6>
                              <h4 class="mb-0">
                                {{convertStringNumberToFigures(getTopChildStat.referral)}}
                              </h4>
                            </div>
                            <span class="avatar1 me-sm-4">
                              <span class="avatar-initial bg-label-warning rounded"><i class="ti-md ti ti-emergency-bed"></i></span>
                            </span>
                          </div>
                          <hr class="d-none d-sm-block d-lg-none">
                        </div>

                        <div class="col-sm-4 col-md-4 col-lg-2">
                          <div class="d-flex justify-content-between align-items-start card-widget-1 border-end pb-sm-0">
                            <div>
                              <h6 class="mb-50 small">SPAQ 1</h6>
                              <h4 class="mb-0">
                                {{convertStringNumberToFigures(getTopChildStat.spaq1)}}
                              </h4>
                            </div>
                            <span class="avatar1 me-sm-4">
                              <span class="avatar-initial bg-label-blue rounded"><i class="ti-md ti ti-pill"></i></span>
                            </span>
                          </div>
                            <hr class="d-none d-md-none d-sm-none d-lg-none">
                        </div>

                        <div class="col-sm-4 col-md-4 col-lg-2">
                          <div class="d-flex justify-content-between align-items-start card-widget-1 border-end pb-sm-0">
                            <div>
                              <h6 class="mb-50 small">SPAQ 2</h6>
                              <h4 class="mb-0">
                                {{convertStringNumberToFigures(getTopChildStat.spaq2)}}
                              </h4>
                            </div>
                            <span class="avatar1 me-sm-4">
                              <span class="avatar-initial bg-label-dark rounded"><i class="ti-md ti ti-pill-off"></i></span>
                            </span>
                          </div>
                            <hr class="d-none d-md-none d-sm-none d-lg-none">
                        </div>

                        <div class="col-sm-4 col-md-4 col-lg-2">
                          <div class="d-flex justify-content-between align-items-start">
                            <div>
                              <h6 class="mb-50 text-primary small">Total</h6>
                              <h4 class="mb-0">
                                {{convertStringNumberToFigures(getTopChildStat.total)}}
                              </h4>
                            </div>
                            <span class="avatar1 me-sm-4">
                              <span class="avatar-initial bg-label-primary rounded"><i class="ti-md ti ti-pills"></i></span>
                            </span>
                          </div>
                            <hr class="d-none d-md-none d-sm-none d-lg-none">
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
            </div>
            <!-- Drug Administration Dashboard: Starts -->
            <div class="container-fluid no-gutters">
              <div class="row no-gutters">
                  <!-- Drug Administration Table Ends -->
                  <div class="col-12 col-md-6 col-xl-6 no-margin col-sm-12 mt-0">
                      <div class="card ttlr br-10 drug-tab">
                          <div class="scrollBox h-100  table-wrapper table-responsive">
                              <table class="table border-top table-striped table-hover table-hover-animation">
                                  <thead>
                                      <tr>
                                          <th :colspan="reportLevel< 4? 2: 1">Description</th>
                                          <th>Eligible</th>
                                          <th>Non-Eligible</th>
                                          <th>Referral</th>
                                          <th>SPAQ 1</th>
                                          <th>SPAQ 2</th>
                                          <th class="text-left">Total</th>
                                      </tr>
                                  </thead>
                                  <tbody>
                                      <tr v-if="reportLevel<5" v-for="g in statData.tableData" @click="loadTableData(g.id, g.title)">
                                          <td style="padding-left: 1rem !important; padding-right: .2rem !important; width:34px" v-if="reportLevel<4"><i class="ti ti-circle-plus text-primary"></i></td>
                                          <td style="padding-left: .4rem !important;">{{capitalize(g.title) }}</td>
                                          <td>{{convertStringNumberToFigures(g.eligible) }}</td>
                                          <td>{{g.non_eligible}}</td>
                                          <td>{{g.referral}}</td>
                                          <td>{{g.spaq1}}</td>
                                          <td>{{g.spaq2}}</td>
                                          <td>{{ convertStringNumberToFigures(parseInt(g.total)) }}</td>
                                      </tr>
                                      
                                      <tr v-if="statData.tableData.length == 0"><td class="text-center pt-2" :colspan="reportLevel< 4? 9: 8"><small>No Data</small></td></tr>
                                  </tbody>
                              </table>

                          </div>
                          <div class="mb-50"></div>                   
                      </div>
                  </div>
                  <!-- Drug Administration Table Ends -->

                  <!-- SMC Bar Chart: Start -->
                  <div class="col-12 col-md-6 col-xl-6 no-margin col-sm-12 mb-0">
                    <div class="card ttlr drug-chart">

                      <div class="card-body pt-25 ttlr">
                                
                        <div class="tab-content p-0 ms-0 ms-sm-2">
                          <div class="tab-pane fade show active" id="hh-mobilized-id" role="tabpanel">
                            <apexchart height="372" type="bar" :options="chartOptions" :series="series"></apexchart>
                          </div>
                        </div>

                      </div>
                    </div>
                  </div>
                  <!-- SMC Bar Chart: End -->

              </div>
            </div>
            <!-- Drug Administration Dashboard: End -->
        </div>
    `,
});

// Referral List Page
Vue.component("referral_admin", {
  data: function () {
    return {
      url: common.DataService,
      filterUrl: "",
      geoData: [],
      periodData: [],
      childDetails: [],
      reportLevel: 1,
      filterId: "",
      lgaId: "",
      lgaName: "",
      wardId: "",
      wardName: "",
      dpId: "",
      dpName: "",
      checkIfFilterOn: false,
      filterState: false,
      filters: false,
      tableOptions: {
        filterParam: {
          periodid: [],
          visitTitle: "",
        },
      },
      statData: {
        tableData: [],
        chartData: [],
        firstDuplicateIds: [],
        secondDuplicateIds: [],
      },
      series: [],
      allChartData: [],
      chartOptions: {
        xaxis: {
          title: {
            text: "",
          },
        },
      },
    };
  },
  mounted() {
    /* Manages event listening */
    // - On Filter Apply, or Removed Event receiver
    EventBus.$on("fire-event-apply-filter", this.handleFilterChange);
    EventBus.$on("fire-event-refresh", this.refreshData);
    EventBus.$on("fire-event-clear-filter", this.clearAllFilter);
    EventBus.$on("fire-event-remove-single-filter", this.removeSingleFilter);

    this.loadTableData(0, "");
  },
  methods: {
    loadTableData(filterId, title) {
      const { reportLevel, tableOptions, url } = this;
      const periodIds = this.joinWithCommaAnd(
        tableOptions.filterParam.periodid,
        true
      );
      let reportDate = tableOptions.filterParam.reportDate || "";
      const [start_date = "", end_date = ""] = reportDate.split(" to ");

      let queryUrl = url;
      let queryParams = {
        pid: periodIds,
        sdate: start_date,
        edate: end_date,
      };

      switch (reportLevel) {
        case 1:
          this.filterId = 0;
          queryUrl += "?qid=1117";
          break;
        case 2:
          this.lgaId = filterId;
          this.lgaName = title || this.lgaName;
          queryUrl += `?qid=1118&filterId=${this.lgaId}`;
          break;
        case 3:
          this.wardId = filterId;
          this.wardName = title || this.wardName;
          queryUrl += `?qid=1119&filterId=${this.wardId}`;
          break;
        default:
          return; // handle unexpected reportLevel
      }

      // Append common parameters to queryUrl
      for (const key in queryParams) {
        if (queryParams[key]) {
          queryUrl += `&${key}=${queryParams[key]}`;
        }
      }

      this.loadDashboardData(queryUrl);
    },
    async loadDashboardData(url) {
      try {
        overlay.show();
        this.filterUrl = url;

        const response = await axios.get(url);
        if (response.data.result_code == 200) {
          // console.log(response.data);
          this.statData.tableData = response.data.data.table;
          this.statData.chartData = response.data.data.chart;
          this.statData.chartData.xAxisLabel = "Days";
          // console.log(this.statData.chartData);
          // console.log(url);
          this.reportLevel = response.data.level;

          this.plotChart();
        } else {
          this.statData.tableData = [];
          alert.Error("ERROR", response.data.message);
        }
        overlay.hide();
      } catch (error) {
        overlay.hide();
        alert.Error("ERROR", error);
      }
    },
    handleFilterChange(data) {
      this.tableOptions.filterParam.periodid = [...data[0]];
      this.tableOptions.filterParam.visitTitle = data[1];
      this.tableOptions.filterParam.reportDate = data[2];
      this.applyFilter();
    },
    controlBreadCrum(filterId, reportLevel, title) {
      this.reportLevel = reportLevel;
      this.loadTableData(filterId, title);
    },
    plotChart() {
      const yAxislabel = "Total SPAQ";
      const xAxisLabel = this.statData.chartData.xAxisLabel;
      let self = this;
      const options = {
        chart: {
          type: "bar",
          stacked: false,
        },
        colors: ["#D7D8E2", "#4351F4"],
        xaxis: {
          categories: [],
        },
        legend: {
          position: "bottom",
          formatter: function (seriesName) {
            return self.capitalize(seriesName);
          },
          fontFamily: "Arial, sans-serif",
          fontSize: "12px",
        },
        title: {
          text: "Referral",
          align: "center",
        },
        yaxis: {
          labels: {
            formatter: function (val) {
              return parseInt(val).toLocaleString();
            },
          },
        },
        plotOptions: {
          bar: {
            horizontal: false,
            dataLabels: {
              position: "top", // Place data labels on top of the bars
            },
          },
        },
        dataLabels: {
          style: {
            colors: ["#000"], // Change value color on bars here
          },
          enabled: true,
          formatter: function (val, opts) {
            return parseInt(val).toLocaleString();
          },
          offsetY: -18,
          style: {
            fontSize: "12px",
            colors: ["#000"],
          },
        },
        noData: {
          text: "No data available, kindly refresh",
          align: "center",
          verticalAlign: "middle",
          offsetX: 0,
          offsetY: 0,
          style: {
            color: "#333",
            fontSize: "14px",
          },
        },
      };

      this.chartOptions = options;

      const newData = {
        categories: this.statData.chartData[1],
      };

      this.series = this.statData.chartData[0];
      // console.log(this.series);

      this.chartOptions.xaxis.categories.splice(
        0,
        this.chartOptions.xaxis.categories.length,
        ...newData.categories
      );
    },
    cleanUrl(url) {
      const urlObj = new URL(url);
      const params = urlObj.searchParams;
      const allowedParams = ["qid", "filterId"];

      // Create a copy of the keys to avoid modifying the object while iterating
      const keysToRemove = [];

      for (const key of params.keys()) {
        if (!allowedParams.includes(key)) {
          keysToRemove.push(key);
        }
      }

      // Remove all parameters that are not allowed
      for (const key of keysToRemove) {
        params.delete(key);
      }

      return urlObj.toString();
    },
    refreshData() {
      this.loadDashboardData(this.filterUrl);
    },
    applyFilter() {
      // Check if any filter fields are filled
      let checkFill = 0;
      checkFill += this.tableOptions.filterParam.periodid.length > 0 ? 1 : 0;
      checkFill += this.tableOptions.filterParam.reportDate.length > 0 ? 1 : 0;

      if (checkFill > 0) {
        /*
        const periodIds = this.joinWithCommaAnd(
          this.tableOptions.filterParam.periodid,
          true
        );
        */
        const { filterParam } = this.tableOptions;
        const periodIdsCopy = [...filterParam.periodid];
        const periodIds = this.joinWithCommaAnd(periodIdsCopy, true);

        let reportDate =
          this.tableOptions.filterParam.reportDate == null
            ? ""
            : this.tableOptions.filterParam.reportDate;
        let start_date =
          reportDate.split(" to ")[0] == undefined
            ? ""
            : reportDate.split(" to ")[0];
        let end_date =
          reportDate.split(" to ")[1] == undefined
            ? ""
            : reportDate.split(" to ")[1];

        let url =
          this.cleanUrl(this.filterUrl) +
          "&pid=" +
          periodIds +
          "&sdate=" +
          start_date +
          "&edate=" +
          end_date;
        this.loadDashboardData(url);
      } else {
        this.clearAllFilter();
      }
    },
    clearAllFilter() {
      this.filters = false;
      // Reset filter parameters
      Object.assign(this.tableOptions.filterParam, {
        periodid: [],
        visitTitle: "",
        reportDate: "",
      });

      let url = this.cleanUrl(this.filterUrl);
      this.loadDashboardData(url);
    },
    removeSingleFilter(column_name) {
      const filterParam = this.tableOptions.filterParam;

      // Clear the specified filter
      if (Array.isArray(filterParam[column_name])) {
        filterParam[column_name] = [];
      } else {
        filterParam[column_name] = "";
      }

      if (column_name === "visitTitle") {
        filterParam.periodid = [];
        filterParam.visitTitle = "";
      }

      //  Reload table data
      this.applyFilter();
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
    capitalize(word) {
      if (word) {
        const loweredCase = word.toLowerCase();
        return word[0].toUpperCase() + loweredCase.slice(1);
      } else {
        return word;
      }
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
    convertStringNumberToFigures(d) {
      let data = d ? parseInt(d) : 0;
      return data ? data.toLocaleString() : 0;
    },
    checkIfEmpty(data) {
      if (data === null || data === "") {
        return "Nil";
      } else {
        return data;
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
  },
  computed: {
    getTopChildStat() {
      const result = this.statData.tableData.reduce(
        (acc, curr) => {
          acc.referred += parseInt(curr.referred, 10);
          acc.attended += parseInt(curr.attended, 10);
          acc.total += parseInt(curr.total, 10);
          return acc;
        },
        {
          referred: 0,
          attended: 0,
          total: 0,
        }
      );
      return result;
    },
  },
  template: `

        <div class="row" id="basic-table">


            <div class="col-12">
                <div class="breadcrumb-wrapper reporting-dashboard">
                    <ol class="breadcrumb pl-0">
                        <li class="breadcrumb-item" v-if="reportLevel>1" :class="reportLevel==2? 'active': ''" @click="controlBreadCrum(0, 1, '')">Referrals</li>
                        <li class="breadcrumb-item" v-if="reportLevel>2" :class="reportLevel==3? 'active': ''" @click="controlBreadCrum(lgaId, 2, lgaName)">{{lgaName}} LGA Wards</li>
                        <li class="breadcrumb-item" v-if="reportLevel>3" :class="reportLevel==4? 'active': ''" @click="controlBreadCrum(wardId, 3, wardName)">{{wardName}} Wards DPs</li>
                        <li class="breadcrumb-item" v-if="reportLevel>4" :class="reportLevel==5? 'active': ''" @click="controlBreadCrum(dpId, 4, dpName)">{{dpName}} Child DPs</li>
                    </ol>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-6  mt-0 col-sm-12">
                <div class="card mb-0 btmlr">
                  <div class="card-widget-separator-wrapper">
                    <div class="card-body pb-75 pt-75 card-widget-separator">
                      <div class="row gy-4 gy-sm-1">
                        <div class="col-sm-4 col-lg-4">
                          <div class="d-flex justify-content-between align-items-start card-widget-1 border-end pb-sm-0">
                            <div>
                              <h6 class="mb-50 text-warning small">Referred</h6>
                              <h4 class="mb-0">
                                {{convertStringNumberToFigures(getTopChildStat.referred)}}</h4>
                            </div>
                            <hr class="d-none d-sm-block d-lg-none">
                            <span class="avatar1 me-sm-4">
                              <span class="avatar-initial bg-label-warning rounded"><i class="ti-md ti ti-emergency-bed"></i></span>
                            </span>
                          </div>
                        </div>
                        <div class="col-sm-4 col-lg-4">
                          <div class="d-flex justify-content-between align-items-start card-widget-1 border-end pb-sm-0">
                            <div>
                              <h6 class="mb-50 text-success small">Attended</h6>
                              <h4 class="mb-0">
                                {{convertStringNumberToFigures(getTopChildStat.attended)}}</h4>
                            </div>
                            <hr class="d-none d-sm-block d-lg-none">
                            <span class="avatar1 me-sm-4">
                              <span class="avatar-initial bg-label-success rounded"><i class="ti-md ti ti-stethoscope"></i></span>
                            </span>
                          </div>
                        </div>
                        <div class="col-sm-4 col-lg-4">
                          <div class="d-flex justify-content-between align-items-start">
                            <div>
                              <h6 class="mb-50 text-primary small">Total</h6>
                              <h4 class="mb-0">
                                {{convertStringNumberToFigures(getTopChildStat.total)}}
                              </h4>
                            </div>
                            <hr class="d-none d-sm-block d-lg-none me-4">
                            <span class="avatar1 me-sm-4">
                              <span class="avatar-initial bg-label-primary rounded"><i class="ti-md ti ti-sum"></i></span>
                            </span>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="card" style="height: 350px !important;">
                    
                    <div class="table-responsive scrollBox">
                        <table class="table table-fixed border-top table-striped table-hover table-hover-animation">
                            <thead>
                                <tr>
                                    <th :colspan="reportLevel< 4? 2: 1">Description</th>
                                    <th>Reffered</th>
                                    <th>Attended</th>
                                    <th class="text-left">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="reportLevel<5" v-for="g in statData.tableData" @click="loadTableData(g.id, g.title)">
                                    <td style="padding-left: 1rem !important; padding-right: .2rem !important; width:34px" v-if="reportLevel<4"><i class="ti ti-circle-plus text-primary"></i></td>
                                    <td style="padding-left: .4rem !important;">{{capitalize(g.title) }}</td>
                                    <td>{{ convertStringNumberToFigures(g.referred) }}</td>
                                    <td>{{ convertStringNumberToFigures(g.attended) }}</td>
                                    <td>{{ convertStringNumberToFigures(parseInt(g.total)) }}</td>
                                </tr>
                                
                                <tr v-if="statData.tableData.length == 0"><td class="text-center pt-2" :colspan="reportLevel< 4? 6: 5"><small>No Data</small></td></tr>
                            </tbody>
                        </table>

                    </div>
                    <div class="mb-50"></div>

                    
                </div>
            </div>

            <!-- SMC Bar Chart: Start -->
            <div class="col-12 col-md-6 col-xl-6  mb-0 col-sm-12">
              <div class="card"  style="height: 416px !important;">

                <div class="card-body">
                          
                  <div class="tab-content p-0 ms-0 ms-sm-2">
                    <div class="tab-pane fade show active" id="hh-mobilized-id" role="tabpanel">
                      <apexchart height="372" type="bar" :options="chartOptions" :series="series"></apexchart>
                    </div>
                  </div>

                </div>
              </div>
            </div>
            <!-- SMC Bar Chart: End -->


        </div>
    `,
});

// Referral List Page
Vue.component("icc_admin", {
  data: function () {
    return {
      url: common.DataService,
      filterUrl: "",
      geoData: [],
      periodData: [],
      childDetails: [],
      reportLevel: 1,
      filterId: "",
      lgaId: "",
      lgaName: "",
      wardId: "",
      wardName: "",
      dpId: "",
      dpName: "",
      checkIfFilterOn: false,
      filterState: false,
      filters: false,
      tableOptions: {
        filterParam: {
          periodid: [],
          visitTitle: "",
        },
      },
      statData: {
        tableData: [],
        chartData: [],
      },
    };
  },
  mounted() {
    /* Manages event listening */
    // - On Filter Apply, or Removed Event receiver
    EventBus.$on("fire-event-apply-filter", this.handleFilterChange);
    EventBus.$on("fire-event-refresh", this.refreshData);
    EventBus.$on("fire-event-clear-filter", this.clearAllFilter);
    EventBus.$on("fire-event-remove-single-filter", this.removeSingleFilter);

    this.loadTableData(0, "");
  },
  methods: {
    loadTableData(filterId, title) {
      const { reportLevel, tableOptions, url } = this;
      const periodIds = this.joinWithCommaAnd(
        tableOptions.filterParam.periodid,
        true
      );
      let reportDate = tableOptions.filterParam.reportDate || "";
      const [start_date = "", end_date = ""] = reportDate.split(" to ");

      let queryUrl = url;
      let queryParams = {
        pid: periodIds,
        sdate: start_date,
        edate: end_date,
      };

      switch (reportLevel) {
        case 1:
          this.filterId = 0;
          queryUrl += "?qid=1120";
          break;
        case 2:
          this.lgaId = filterId;
          this.lgaName = title || this.lgaName;
          queryUrl += `?qid=1121&filterId=${this.lgaId}`;
          break;
        case 3:
          this.wardId = filterId;
          this.wardName = title || this.wardName;
          queryUrl += `?qid=1122&filterId=${this.wardId}`;
          break;
        default:
          return; // handle unexpected reportLevel
      }

      // Append common parameters to queryUrl
      for (const key in queryParams) {
        if (queryParams[key]) {
          queryUrl += `&${key}=${queryParams[key]}`;
        }
      }

      this.loadDashboardData(queryUrl);
    },
    async loadDashboardData(url) {
      try {
        overlay.show();
        this.filterUrl = url;

        const response = await axios.get(url);
        if (response.data.result_code == 200) {
          // console.log(response.data.data);
          this.statData.tableData = response.data.data;
          // this.statData.tableData = response.data.data.table;
          // console.log(this.statData.chartData);
          // console.log(this.statData.tableData);
          let duplicates = this.findDuplicateIds(response.data.data);

          this.statData.firstDuplicateIds = duplicates.duplicates;
          this.statData.secondDuplicateIds = duplicates.firstDuplicateOccurence;

          this.reportLevel = response.data.level;
        } else {
          this.statData.tableData = [];
          alert.Error("ERROR", response.data.message);
        }
        overlay.hide();
      } catch (error) {
        overlay.hide();
        alert.Error("ERROR", error);
      }
    },
    handleFilterChange(data) {
      this.tableOptions.filterParam.periodid = [...data[0]];
      this.tableOptions.filterParam.visitTitle = data[1];
      this.tableOptions.filterParam.reportDate = data[2];
      this.applyFilter();
    },
    controlBreadCrum(filterId, reportLevel, title) {
      this.reportLevel = reportLevel;
      this.loadTableData(filterId, title);
    },
    cleanUrl(url) {
      const urlObj = new URL(url);
      const params = urlObj.searchParams;
      const allowedParams = ["qid", "filterId"];

      // Create a copy of the keys to avoid modifying the object while iterating
      const keysToRemove = [];

      for (const key of params.keys()) {
        if (!allowedParams.includes(key)) {
          keysToRemove.push(key);
        }
      }

      // Remove all parameters that are not allowed
      for (const key of keysToRemove) {
        params.delete(key);
      }

      return urlObj.toString();
    },
    refreshData() {
      this.loadDashboardData(this.filterUrl);
    },
    applyFilter() {
      // Check if any filter fields are filled
      let checkFill = 0;
      checkFill += this.tableOptions.filterParam.periodid.length > 0 ? 1 : 0;
      checkFill += this.tableOptions.filterParam.reportDate.length > 0 ? 1 : 0;

      if (checkFill > 0) {
        const periodIds = this.joinWithCommaAnd(
          this.tableOptions.filterParam.periodid,
          true
        );

        let reportDate =
          this.tableOptions.filterParam.reportDate == null
            ? ""
            : this.tableOptions.filterParam.reportDate;
        let start_date =
          reportDate.split(" to ")[0] == undefined
            ? ""
            : reportDate.split(" to ")[0];
        let end_date =
          reportDate.split(" to ")[1] == undefined
            ? ""
            : reportDate.split(" to ")[1];

        let url =
          this.cleanUrl(this.filterUrl) +
          "&pid=" +
          periodIds +
          "&sdate=" +
          start_date +
          "&edate=" +
          end_date;
        this.loadDashboardData(url);
      } else {
        this.clearAllFilter();
      }
    },
    clearAllFilter() {
      this.filters = false;
      // Reset filter parameters
      Object.assign(this.tableOptions.filterParam, {
        periodid: [],
        visitTitle: "",
        reportDate: "",
      });

      let url = this.cleanUrl(this.filterUrl);
      this.loadDashboardData(url);
    },
    removeSingleFilter(column_name) {
      const filterParam = this.tableOptions.filterParam;

      // Clear the specified filter
      if (Array.isArray(filterParam[column_name])) {
        filterParam[column_name] = [];
      } else {
        filterParam[column_name] = "";
      }

      if (column_name === "visitTitle") {
        filterParam.periodid = [];
        filterParam.visitTitle = "";
      }

      //  Reload table data
      this.applyFilter();
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
    capitalize(word) {
      if (word) {
        const loweredCase = word.toLowerCase();
        return word[0].toUpperCase() + loweredCase.slice(1);
      } else {
        return word;
      }
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
    convertStringNumberToFigures(d) {
      let data = d ? parseInt(d) : 0;
      return data ? data.toLocaleString() : 0;
    },
    checkIfEmpty(data) {
      if (data === null || data === "") {
        return "Nil";
      } else {
        return data;
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
    findDuplicateIds(data) {
      const idMap = {};
      const duplicates = [];
      const firstDuplicateOccurence = [];

      data.forEach((item, index) => {
        if (idMap[item.id] !== undefined) {
          // If id already exists in idMap, it's a duplicate
          duplicates.push(index);
          firstDuplicateOccurence.push(index - 1);
        } else {
          // Store the index of the first occurrence of each id
          idMap[item.id] = index;
        }
      });

      return { duplicates, firstDuplicateOccurence };
    },
    rowColSpan(index) {
      if (this.statData.firstDuplicateIds.includes(index)) {
        return true;
      }
      return false;
    },
    hideCell(index) {
      if (this.statData.secondDuplicateIds.includes(index)) {
        return true;
      }
      return false;
    },
    groupStyle(i) {
      const mergedArray = [
        ...new Set([
          ...this.statData.firstDuplicateIds,
          ...this.statData.secondDuplicateIds,
        ]),
      ];
      if (mergedArray.includes(i)) {
        return true;
      }
      return false;
    },
    total(g) {
      return ["administered", "redosed", "wasted", "loss"].reduce(
        (sum, key) => sum + parseInt(g[key] || 0, 10),
        0
      );
    },
  },
  computed: {
    getTopIccStat() {
      const result = this.statData.tableData.reduce(
        (acc, item) => {
          if (item.drug === "SPAQ 1") {
            acc.sumSpaq1Issued += parseInt(item.total_issued);
            acc.sumSpaq1Administered += parseInt(item.administered);
            acc.sumSpaq1Redosed += parseInt(item.redosed);
            acc.sumSpaq1Wasted += parseInt(item.wasted);
            acc.sumSpaq1Loss += parseInt(item.loss);
            acc.sumSpaq1Facility += parseInt(item.count_facility);
          } else if (item.drug === "SPAQ 2") {
            acc.sumSpaq2Issued += parseInt(item.total_issued);
            acc.sumSpaq2Administered += parseInt(item.administered);
            acc.sumSpaq2Redosed += parseInt(item.redosed);
            acc.sumSpaq2Wasted += parseInt(item.wasted);
            acc.sumSpaq2Loss += parseInt(item.loss);
            acc.sumSpaq2Facility += parseInt(item.count_facility);
          }
          return acc;
        },
        {
          sumSpaq1Issued: 0,
          sumSpaq1Administered: 0,
          sumSpaq1Redosed: 0,
          sumSpaq1Wasted: 0,
          sumSpaq1Loss: 0,
          sumSpaq1Facility: 0,

          sumSpaq2Issued: 0,
          sumSpaq2Administered: 0,
          sumSpaq2Redosed: 0,
          sumSpaq2Wasted: 0,
          sumSpaq2Loss: 0,
          sumSpaq2Facility: 0,
        }
      );
      return result;
    },
  },
  template: `

        <div class="row" id="basic-table">


            <div class="col-12">
                <div class="breadcrumb-wrapper reporting-dashboard">
                    <ol class="breadcrumb pl-0">
                        <li class="breadcrumb-item" v-if="reportLevel>1" :class="reportLevel==2? 'active': ''" @click="controlBreadCrum(0, 1, '')">Inventory Control</li>
                        <li class="breadcrumb-item" v-if="reportLevel>2" :class="reportLevel==3? 'active': ''" @click="controlBreadCrum(lgaId, 2, lgaName)">{{lgaName}} LGA Wards</li>
                        <li class="breadcrumb-item" v-if="reportLevel>3" :class="reportLevel==4? 'active': ''" @click="controlBreadCrum(wardId, 3, wardName)">{{wardName}} Wards DPs ICCs</li>
                        <li class="breadcrumb-item" v-if="reportLevel>4" :class="reportLevel==5? 'active': ''" @click="controlBreadCrum(dpId, 4, dpName)">{{dpName}} Child DPs</li>
                    </ol>
                </div>
            </div>
            
            <div class="col-12 col-md-12 col-xl-12  mt-0 col-sm-12">
            
                <div class="card mb-0 btmlr drug-card">
                  <div class="card-header py-50 d-flex justify-content-between">
                    <h5 class="card-title font-small-2 font-weight-bolder mb-25 text-default">SPAQ 1</h5>
                  </div>
                  <div class="card-body pb-0 icc-card d-flex align-items-end">
                    <div class="w-100">
                      <div class="row gy-3">
                        <div class="col-sm-4 col-md-4 col-lg-2 col-6">
                          <div class="d-flex align-items-center mb-1">
                            <div class="badge rounded bg-label-primary me-4 p-50"><i class="ti ti-package-export ti-md"></i></div>
                            <div class="card-info">
                              <h5 class="mb-0">{{convertStringNumberToFigures(getTopIccStat.sumSpaq1Issued)}}</h5>
                              <small>Issued</small>
                            </div>
                          </div>
                        </div>
                        <div class="col-sm-4 col-md-4 col-lg-2 col-6">
                          <div class="d-flex align-items-center mb-1">
                            <div class="badge rounded bg-label-success me-4 p-50"><i class="ti ti-pills ti-md"></i></div>
                            <div class="card-info w-100">
                              <h5 class="mb-0">{{convertStringNumberToFigures(getTopIccStat.sumSpaq1Administered)}}</h5>
                              <div class="d-flex align-items-center w-100">
                                <div class="progress w-100 me-3" style="height: 6px;">
                                  <div class="progress-bar" :class="progressBarStatus(getTopIccStat.sumSpaq1Issued, getTopIccStat.sumSpaq1Administered)" :style="{ width: progressBarWidth(getTopIccStat.sumSpaq1Issued, getTopIccStat.sumSpaq1Administered)}" :aria-valuenow="{ width: progressBarWidth(getTopIccStat.sumSpaq1Issued, getTopIccStat.sumSpaq1Administered)}" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                                <small class="text-heading ml-50">{{progressBarWidth(getTopIccStat.sumSpaq1Issued, getTopIccStat.sumSpaq1Administered)}}</small>
                              </div>
                              <small>Administered</small>
                            </div>
                          </div>
                        </div>
                        <div class="col-sm-4 col-md-4 col-lg-2 col-6">
                          <div class="d-flex align-items-center mb-1">
                            <div class="badge rounded bg-label-info me-4 p-50"><i class="ti ti-pill ti-md"></i></div>
                            <div class="card-info w-100">
                              <h5 class="mb-0">{{convertStringNumberToFigures(getTopIccStat.sumSpaq1Redosed)}}</h5>
                              <div class="d-flex align-items-center w-100">
                                <div class="progress w-100 me-3" style="height: 6px;">
                                  <div class="progress-bar" :class="progressBarStatus(getTopIccStat.sumSpaq1Issued, getTopIccStat.sumSpaq1Redosed)" :style="{ width: progressBarWidth(getTopIccStat.sumSpaq1Issued, getTopIccStat.sumSpaq1Redosed)}" :aria-valuenow="{ width: progressBarWidth(getTopIccStat.sumSpaq1Issued, getTopIccStat.sumSpaq1Redosed)}" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                                <small class="text-heading ml-50">{{progressBarWidth(getTopIccStat.sumSpaq1Issued, getTopIccStat.sumSpaq1Redosed)}}</small>
                              </div>
                              <small>Redose</small>
                            </div>
                          </div>
                        </div>
                        <div class="col-sm-4 col-md-4 col-lg-2 col-6">
                          <div class="d-flex align-items-center mb-1">
                            <div class="badge rounded bg-label-danger me-4 p-50"><i class="ti ti-bucket-droplet ti-md"></i></div>
                            <div class="card-info w-100">
                              <h5 class="mb-0">{{convertStringNumberToFigures(getTopIccStat.sumSpaq1Wasted)}}</h5>
                              <div class="d-flex align-items-center w-100">
                                <div class="progress w-100 me-3" style="height: 6px;">
                                  <div class="progress-bar" :class="progressBarStatus(getTopIccStat.sumSpaq1Issued, getTopIccStat.sumSpaq1Wasted)" :style="{ width: progressBarWidth(getTopIccStat.sumSpaq1Issued, getTopIccStat.sumSpaq1Wasted)}" :aria-valuenow="{ width: progressBarWidth(getTopIccStat.sumSpaq1Issued, getTopIccStat.sumSpaq1Wasted)}" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                                <small class="text-heading ml-50">{{progressBarWidth(getTopIccStat.sumSpaq1Issued, getTopIccStat.sumSpaq1Wasted)}}</small>
                              </div>
                              <small>Wasted</small>
                            </div>
                          </div>
                        </div>
                        <div class="col-sm-4 col-md-4 col-lg-2 col-6">
                          <div class="d-flex align-items-center mb-1">
                            <div class="badge rounded bg-label-warning me-4 p-50"><i class="ti ti-circle-half-2 ti-md"></i></div>
                            <div class="card-info w-100">
                              <h5 class="mb-0">{{convertStringNumberToFigures(getTopIccStat.sumSpaq1partialReturn)}}</h5>
                              <div class="d-flex align-items-center w-100">
                                <div class="progress w-100 me-3" style="height: 6px;">
                                  <div class="progress-bar" :class="progressBarStatus(getTopIccStat.sumSpaq1Issued, getTopIccStat.sumSpaq1Loss)" :style="{ width: progressBarWidth(getTopIccStat.sumSpaq1Issued, getTopIccStat.sumSpaq1Loss)}" :aria-valuenow="{ width: progressBarWidth(getTopIccStat.sumSpaq1Issued, getTopIccStat.sumSpaq1Loss)}" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                                <small class="text-heading ml-50">{{progressBarWidth(getTopIccStat.sumSpaq1Issued, getTopIccStat.sumSpaq1Loss)}}</small>
                              </div>
                              <small>Loss</small>
                            </div>
                          </div>
                        </div>
                        <div class="col-sm-4 col-md-4 col-lg-2 col-6">
                          <div class="d-flex align-items-center mb-1">
                            <div class="badge rounded bg-label-secondary me-4 p-50"><i class="ti ti-building-hospital ti-md"></i></div>
                            <div class="card-info">
                              <h5 class="mb-0">{{convertStringNumberToFigures(getTopIccStat.sumSpaq1Facility)}}</h5>
                              <small>Facility</small>
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
                                <div class="col-sm-4 col-md-4 col-lg-2 col-6">
                                    <div class="d-flex align-items-center mb-1">
                                        <div class="badge rounded bg-label-primary me-4 p-50"><i class="ti ti-package-export ti-md"></i>
                                        </div>
                                        <div class="card-info w-100">
                                          <h5 class="mb-0">{{convertStringNumberToFigures(getTopIccStat.sumSpaq2Issued)}}</h5>
                                          <small>Issued</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-4 col-md-4 col-lg-2 col-6">
                                    <div class="d-flex align-items-center mb-1">
                                        <div class="badge rounded bg-label-success me-4 p-50"><i class="ti ti-pills ti-md"></i></div>
                                        <div class="card-info w-100">
                                            <h5 class="mb-0">{{convertStringNumberToFigures(getTopIccStat.sumSpaq2Administered)}}</h5>
                                            <div class="d-flex align-items-center w-100">
                                              <div class="progress w-100 me-3" style="height: 6px;">
                                                <div class="progress-bar" :class="progressBarStatus(getTopIccStat.sumSpaq2Issued, getTopIccStat.sumSpaq2Administered)" :style="{ width: progressBarWidth(getTopIccStat.sumSpaq2Issued, getTopIccStat.sumSpaq2Administered)}" :aria-valuenow="{ width: progressBarWidth(getTopIccStat.sumSpaq2Issued, getTopIccStat.sumSpaq2Administered)}" aria-valuemin="0" aria-valuemax="100"></div>
                                              </div>
                                              <small class="text-heading ml-50">{{progressBarWidth(getTopIccStat.sumSpaq2Issued, getTopIccStat.sumSpaq2Administered)}}</small>
                                            </div>
                                            <small>Administered</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-4 col-md-4 col-lg-2 col-6">
                                    <div class="d-flex align-items-center mb-1">
                                        <div class="badge rounded bg-label-info me-4 p-50"><i class="ti ti-pill ti-md"></i></div>
                                        <div class="card-info w-100">
                                            <h5 class="mb-0">{{convertStringNumberToFigures(getTopIccStat.sumSpaq2Redosed)}}</h5>
                                            <div class="d-flex align-items-center w-100">
                                              <div class="progress w-100 me-3" style="height: 6px;">
                                                <div class="progress-bar" :class="progressBarStatus(getTopIccStat.sumSpaq2Issued, getTopIccStat.sumSpaq2Redosed)" :style="{ width: progressBarWidth(getTopIccStat.sumSpaq2Issued, getTopIccStat.sumSpaq2Redosed)}" :aria-valuenow="{ width: progressBarWidth(getTopIccStat.sumSpaq2Issued, getTopIccStat.sumSpaq2Redosed)}" aria-valuemin="0" aria-valuemax="100"></div>
                                              </div>
                                              <small class="text-heading ml-50">{{progressBarWidth(getTopIccStat.sumSpaq2Issued, getTopIccStat.sumSpaq2Redosed)}}</small>
                                            </div>
                                            <small>Redose</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-4 col-md-4 col-lg-2 col-6">
                                    <div class="d-flex align-items-center mb-1">
                                        <div class="badge rounded bg-label-danger me-4 p-50"><i class="ti ti-bucket-droplet ti-md"></i>
                                        </div>
                                        <div class="card-info w-100">
                                            <h5 class="mb-0">{{convertStringNumberToFigures(getTopIccStat.sumSpaq2Wasted)}}</h5>
                                            <div class="d-flex align-items-center w-100">
                                              <div class="progress w-100 me-3" style="height: 6px;">
                                                <div class="progress-bar" :class="progressBarStatus(getTopIccStat.sumSpaq2Issued, getTopIccStat.sumSpaq2Wasted)" :style="{ width: progressBarWidth(getTopIccStat.sumSpaq2Issued, getTopIccStat.sumSpaq2Wasted)}" :aria-valuenow="{ width: progressBarWidth(getTopIccStat.sumSpaq2Issued, getTopIccStat.sumSpaq2Wasted)}" aria-valuemin="0" aria-valuemax="100"></div>
                                              </div>
                                              <small class="text-heading ml-50">{{progressBarWidth(getTopIccStat.sumSpaq2Issued, getTopIccStat.sumSpaq2Wasted)}}</small>
                                            </div>
                                            <small>Wasted</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-4 col-md-4 col-lg-2 col-6">
                                    <div class="d-flex align-items-center mb-1">
                                        <div class="badge rounded bg-label-warning me-4 p-50"><i class="ti ti-circle-half-2 ti-md"></i>
                                        </div>
                                        <div class="card-info w-100">
                                            <h5 class="mb-0">{{convertStringNumberToFigures(getTopIccStat.sumSpaq2Loss)}}</h5>
                                            <div class="d-flex align-items-center w-100">
                                              <div class="progress w-100 me-3" style="height: 6px;">
                                                <div class="progress-bar" :class="progressBarStatus(getTopIccStat.sumSpaq2Issued, getTopIccStat.sumSpaq2Loss)" :style="{ width: progressBarWidth(getTopIccStat.sumSpaq2Issued, getTopIccStat.sumSpaq2Loss)}" :aria-valuenow="{ width: progressBarWidth(getTopIccStat.sumSpaq2Issued, getTopIccStat.sumSpaq2Loss)}" aria-valuemin="0" aria-valuemax="100"></div>
                                              </div>
                                              <small class="text-heading ml-50">{{progressBarWidth(getTopIccStat.sumSpaq2Issued, getTopIccStat.sumSpaq2Loss)}}</small>
                                            </div>
                                            <small>Loss</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-4 col-md-4 col-lg-2 col-6">
                                    <div class="d-flex align-items-center mb-1">
                                        <div class="badge rounded bg-label-secondary me-4 p-50"><i
                                                class="ti ti-building-hospital ti-md"></i></div>
                                        <div class="card-info">
                                            <h5 class="mb-0">{{convertStringNumberToFigures(getTopIccStat.sumSpaq2Facility)}}</h5>
                                            <small>Facility</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>


                <div class="card" style="height: 460px !important;">
                    
                    <div class="table-wrapper table-responsive scrollBox">
                        <table class="table table-striped-custom"  :class="reportLevel!=4? '': 'table-striped'">
                            <thead>
                                <tr>
                                    <th :colspan="reportLevel<= 4? 2: 1">Location</th>
                                    <th v-if="reportLevel<= 4" style="padding: 0.72rem 1rem !important">Drug</th>
                                    <th>Period</th>
                                    <th>Facility</th>
                                    <th>Team</th>
                                    <th>Administered</th>
                                    <th>Redose</th>
                                    <th>Wasted</th>
                                    <th>Loss</th>
                                    <th>Total Issued</th>
                                    <th>Issued</th>
                                    <th>Pending</th>
                                    <th>Confirmed</th>
                                    <th>Accepted</th>
                                    <th>Returned</th>
                                    <th>Reconciled</th>


                                    <th class="px-50" style="min-width: 200px">% Used</th>
                                    
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="reportLevel<5" :class="groupStyle(i) && reportLevel!=4? 'group1': 'non-group'" v-for="(g, i) in statData.tableData" @click="loadTableData(g.id, g.title)">
                                    <td v-if="hideCell(i) && reportLevel<4" :rowspan="!rowColSpan(i)? 2: 1"  style="padding-left: 1rem !important; padding-right: .2rem !important; width:34px"><i class="ti ti-circle-plus text-primary"></i></td>
                                    <td v-if="hideCell(i) && reportLevel<=4" :rowspan="!rowColSpan(i)? 2: 1" style="padding-left: .4rem !important;">
                                      <div class="text-nowrap">{{g.title }}</div>
                                    </td>
                                    <td class="text-nowrap" :colspan="reportLevel==4? 2: 1" style="padding: 0.72rem 1rem !important">{{ g.drug }}</td>
                                    <td>{{g.period}}</td>
                                    <td>{{ convertStringNumberToFigures(g.count_facility) }}</td>
                                    <td>{{ convertStringNumberToFigures(g.count_team) }}</td>
                                    <td>{{ convertStringNumberToFigures(g.administered) }}</td>
                                    <td>{{ convertStringNumberToFigures(g.redosed) }}</td>
                                    <td>{{ convertStringNumberToFigures(g.wasted) }}</td>
                                    <td>{{ convertStringNumberToFigures(g.loss) }}</td>
                                    <td>{{ convertStringNumberToFigures(g.total_issued) }}</td>
                                    <td>{{ convertStringNumberToFigures(g.issued) }}</td>
                                    <td>{{ convertStringNumberToFigures(g.pending) }}</td>
                                    <td>{{ convertStringNumberToFigures(g.confirmed) }}</td>
                                    <td>{{ convertStringNumberToFigures(g.accepted) }}</td>
                                    <td>{{ convertStringNumberToFigures(g.returned) }}</td>
                                    <td>{{ convertStringNumberToFigures(g.reconciled) }}</td>
                                    <td class="px-75">
                                      <div class="d-flex align-items-center">
                                        <div class="progress w-100 me-3" style="height: 6px;">
                                          <div class="progress-bar" :class="progressBarStatus(g.total_issued, total(g))" :style="{ width: progressBarWidth(g.total_issued, total(g))}" :aria-valuenow="{ width: progressBarWidth(g.total_issued, total(g)) }" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                        <span class="text-heading ml-50">{{progressBarWidth(g.total_issued, total(g))}}</span>
                                      </div>
                                    </td>
                                </tr>
                                
                                <tr v-if="statData.tableData.length == 0"><td class="text-center pt-2" :colspan="reportLevel< 4? 16: 17"><small>No Data</small></td></tr>
                            </tbody>
                        </table>

                    </div>
                    <div class="mb-50"></div>

                    
                </div>
            </div>


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
