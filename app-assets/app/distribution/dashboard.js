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
  page: "",
  title: "",
  lgaId: "",
  lgaName: "",
  wardId: "",
  wardName: "",
  date: "",
  chartTitle: "Daily Summary Chart",
});
// Centralized reactive state
const appState = Vue.observable({
  currentDrillData: "",
  aggregate: createPageState(),
  dailyAggregate: createPageState(),
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
      const progress = this.percentageUsed(issue, used);

      if (progress >= 90) return "bg-success";
      if (progress >= 70) return "bg-info";
      if (progress >= 40) return "bg-warning";
      if (progress > 30) return "bg-secondary";
      return "bg-danger";
    },
  },
});

Vue.component("apex-chart", VueApexCharts);

Vue.component("page-body", {
  mixins: [eventBusMixin],
  data: function () {
    return {
      appState,
    };
  },
  mounted() {
    /*  Manages events Listening    */
  },
  methods: {
    refreshAllData() {
      EventBus.$emit("g-event-refresh-page", {
        page: this.page,
        distributionPage: this.distributionPage,
      });
    },
  },
  template: `
            <div>
                <div class="content-header row" >
                    <div class="content-header-left col-sm-8 col-md-9 col-8 mb-2">
                        <div class="row breadcrumbs-top">
                          <div class="col-12">
                              <h2 class="content-header-title header-txt float-left mb-0">Distribution</h2>
                              <div class="breadcrumb-wrapper">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item active">Dashboard</li>
                                </ol>
                              </div>
                          </div>
                        </div>
                    </div>
                    <div class="content-header-right text-right col-sm-4 col-md-3 col-4 d-md-block d-sm-block mb-2">
                        <button @click="refreshAllData()" class="btn-icon btn btn-primary btn-round btn-sm" type="button"><i data-feather="refresh-cw"></i></button>
                    </div>
                </div>

                <Distribution-General-Stats />
                <Distribution-Lga-Aggregate-table />
                <Daily-Aggregate-table />
            </div>
          `,
});

Vue.component("Distribution-General-Stats", {
  mixins: [eventBusMixin],
  data: function () {
    return {
      appState,
      allStatistics: [],
    };
  },
  mounted() {
    /*  Manages events Listening    */
    this.refreshData();
  },
  methods: {
    refreshAllData() {
      EventBus.$emit("g-event-refresh-page");
    },
    refreshDataHandler() {
      this.refreshData();
    },
    refreshData() {
      overlay.show();

      Promise.all([
        this.fetchData("403", (data) => {
          this.allStatistics = data;
        }),
      ]).finally(() => {
        overlay.hide();
      });
    },
    fetchData(qid, onSuccess) {
      const url = `${common.DataService}?qid=${qid}`;
      return axios
        .get(url)
        .then((response) => {
          const data = response.data?.data[0] || [];
          onSuccess(data);
        })
        .catch((error) => {
          console.error(`Error fetching qid=${qid} data:`, error);
        });
    },
    goBack(data) {
      appState.currentDrillData = "aggregate_lga";

      appState.aggregate.title = data?.title;
      appState.aggregate.page = data?.page;

      EventBus.$emit("g-event-goto-page", data);
    },
  },
  template: `

        <div class="content-header row">

          <!-- Top Stats Cards -->
          <div class="col-12 col-lg-6 col-md-6 col-sm-12">
              <div class="card ">
                  <div class="card-widget-separator-wrapper">
                      <div class="card-body pb-75 pt-75 card-widget-separator">
                          <div class="row gy-4 gy-sm-1 pr-sm-custom-0">
                              <div class="col-sm-6 col-lg-4">
                                  <div
                                      class="d-flex justify-content-between align-items-start card-widget-1 border-end pb-sm-0">
                                      <div>
                                          <h6 class="mb-50 small text-primary">HH Mobilized</h6>
                                          <h4 class="mb-0">
                                              {{formatNumber(allStatistics.household_mobilized)}}</h4>
                                      </div>
                                      <span class="avatar1 me-sm-4">
                                          <span class="avatar-initial bg-label-primary rounded"><i
                                                  class="ti-md ti ti-home-share"></i></span>
                                      </span>
                                  </div>
                              </div>

                              <div class="col-sm-6 col-lg-4">
                                  <div
                                      class="d-flex justify-content-between align-items-start card-widget-2 border-end pb-sm-0">
                                      <div>
                                          <h6 class="mb-50 small text-primary">HH Redeemed</h6>
                                          <h4 class="mb-0">
                                              {{formatNumber(allStatistics.household_redeemed)}}
                                          </h4>
                                      </div>
                                      <span class="avatar1 me-sm-4 second">
                                          <span class="avatar-initial bg-label-success rounded"><i
                                                  class="ti-md ti ti-home-check"></i></span>
                                      </span>
                                  </div>
                              </div>

                              <div class="col-sm-12 col-lg-4">
                                  <hr class="d-none d-sm-block d-lg-none">
                                  <div class="d-flex justify-content-between align-items-start card-widget-1 pb-sm-0">
                                      <div>
                                          <h6 class="mb-50 small text-primary">Redemption Rate</h6>
                                          <h4 class="mb-0">
                                              {{percentageUsed(allStatistics.household_mobilized, allStatistics.household_redeemed)}}%
                                          </h4>
                                      </div>
                                      <hr class="d-none d-sm-block d-lg-none me-4">
                                      <span class="avatar1 me-sm-4 end">
                                          <span class="avatar-initial bg-label-info rounded"><i
                                                  class="ti-md ti ti-percentage"></i></span>
                                      </span>
                                  </div>
                              </div>

                          </div>
                      </div>
                  </div>
              </div>
          </div>

          <div class="col-12 col-lg-6 col-md-6 col-sm-12">
              <div class="card ">
                  <div class="card-widget-separator-wrapper">
                      <div class="card-body pb-75 pt-75 card-widget-separator">
                          <div class="row gy-4 gy-sm-1 pr-sm-custom-0">
                              <div class="col-sm-6 col-lg-4">
                                  <div
                                      class="d-flex justify-content-between align-items-start card-widget-1 border-end pb-sm-0">
                                      <div>
                                          <h6 class="mb-50 small text-primary">Family Size Mobilized</h6>
                                          <h4 class="mb-0">
                                              {{formatNumber(allStatistics.familysize_mobilized)}}</h4>
                                      </div>
                                      <span class="avatar1 me-sm-4">
                                          <span class="avatar-initial bg-label-primary rounded"><i
                                                  class="ti-md ti ti-users-group"></i></span>
                                      </span>
                                  </div>
                              </div>

                              <div class="col-sm-6 col-lg-4">
                                  <div
                                      class="d-flex justify-content-between align-items-start card-widget-2 border-end pb-sm-0">
                                      <div>
                                          <h6 class="mb-50 small text-primary">Family Size Redeemed</h6>
                                          <h4 class="mb-0">
                                              {{formatNumber(allStatistics.familysize_redeemed)}}
                                          </h4>
                                      </div>
                                      <span class="avatar1 me-sm-4 second">
                                          <span class="avatar-initial bg-label-success rounded"><i
                                                  class="ti-md ti ti-users-group"></i></span>
                                      </span>
                                  </div>
                              </div>

                              <div class="col-sm-12 col-lg-4">
                                  <hr class="d-none d-sm-block d-lg-none">
                                  <div class="d-flex justify-content-between align-items-start card-widget-1 pb-sm-0">
                                      <div>
                                          <h6 class="mb-50 small text-primary">Redemption Rate</h6>
                                          <h4 class="mb-0">
                                              {{percentageUsed(allStatistics.familysize_mobilized, allStatistics.familysize_redeemed)}}%
                                          </h4>
                                      </div>
                                      <hr class="d-none d-sm-block d-lg-none me-4">
                                      <span class="avatar1 me-sm-4 end">
                                          <span class="avatar-initial bg-label-info rounded"><i
                                                  class="ti-md ti ti-percentage"></i></span>
                                      </span>
                                  </div>
                              </div>

                          </div>
                      </div>
                  </div>
              </div>
          </div>

          <div class="col-12 col-lg-12 col-md-12 col-sm-12">
              <div class="card ">
                  <div class="card-widget-separator-wrapper">
                      <div class="card-body pb-75 pt-75 card-widget-separator">
                          <div class="row gy-4 gy-sm-1">
                              <div class="col-sm-6 col-lg-4 pr-sm-custom-0">
                                  <div
                                      class="d-flex justify-content-between align-items-start card-widget-1 border-end pb-sm-0">
                                      <div>
                                          <h6 class="mb-50 small text-primary">Net Mobilized</h6>
                                          <h4 class="mb-0">
                                              {{formatNumber(allStatistics.net_issued)}}</h4>
                                      </div>
                                      <span class="avatar1 me-sm-4">
                                          <span class="avatar-initial bg-label-primary rounded"><i
                                                  class="ti-md ti ti-brand-netbeans"></i></span>
                                      </span>
                                  </div>
                              </div>

                              <div class="col-sm-6 col-lg-4">
                                  <div
                                      class="d-flex justify-content-between align-items-start card-widget-2 border-end pb-sm-0">
                                      <div>
                                          <h6 class="mb-50 small text-primary">Net Redeemed</h6>
                                          <h4 class="mb-0">
                                              {{formatNumber(allStatistics.net_redeemed)}}
                                          </h4>
                                      </div>
                                      <span class="avatar1 me-sm-4 second">
                                          <span class="avatar-initial bg-label-success rounded"><i
                                                  class="ti-md ti ti-brand-netbeans"></i></span>
                                      </span>
                                  </div>
                              </div>

                              <div class="col-sm-12 col-lg-4">
                                  <hr class="d-none d-sm-block d-lg-none">

                                  <div class="d-flex justify-content-between align-items-start card-widget-1 pb-sm-0">
                                      <div>
                                          <h6 class="mb-50 small text-primary">Redemption Rate</h6>
                                          <h4 class="mb-0">
                                              {{percentageUsed(allStatistics.net_issued, allStatistics.net_redeemed)}}%
                                          </h4>
                                      </div>
                                      <hr class="d-none d-sm-block d-lg-none me-4">
                                      <span class="avatar1 me-sm-4 end">
                                          <span class="avatar-initial bg-label-info rounded"><i
                                                  class="ti-md ti ti-percentage"></i></span>
                                      </span>
                                  </div>
                              </div>

                          </div>
                      </div>
                  </div>
              </div>
          </div>
          <div class="col-12">
              <div class="breadcrumb-wrapper reporting-dashboard">
                  <ol class="breadcrumb pl-0">
                      <li class="breadcrumb-item" @click="goBack({page: '', title: 'LGA' })" :class="appState.aggregate.page==''? 'active': ''" v-if="appState.aggregate.page=='ward_summary' || appState.aggregate.page=='dp_summary' || appState.aggregate.page==''">LGA Aggregate </li>
                      <li class="breadcrumb-item" @click="goBack({page: 'ward_summary', title: 'Ward' })" :class="appState.aggregate.page=='ward_summary'? 'active': ''" v-if="appState.aggregate.page=='ward_summary' || appState.aggregate.page=='dp_summary'">{{appState.aggregate.lgaName}}</li>
                      <li class="breadcrumb-item" :class="appState.aggregate.page == 'dp_summary'? 'active': ''"  v-if="appState.aggregate.page=='dp_summary'">{{appState.aggregate.wardName}}</li>
                  </ol>
              </div>
          </div>
      </div>
    `,
});

Vue.component("Distribution-Lga-Aggregate-table", {
  mixins: [eventBusMixin],
  data: function () {
    return {
      appState,
      tableData: [],
      psInstances: [],
    };
  },
  mounted() {
    /*  Manages events Listening    */
    this.initPerfectScrollbars();
    /*  Manages events Listening    */
    appState.aggregate.title = "LGA";
    this.refreshData();
  },
  beforeDestroy() {
    this.destroyPerfectScrollbars();
  },
  methods: {
    initPerfectScrollbars() {
      const containers = this.$el.querySelectorAll(".perfect-scroll-grid");
      const self = this;
      containers.forEach((el) => {
        self.psInstances.push(new PerfectScrollbar(el));
      });
    },
    destroyPerfectScrollbars() {
      this.psInstances.forEach((ps) => ps.destroy());
      this.psInstances = [];
    },
    refreshAllData() {
      EventBus.$emit("g-event-refresh-page");
    },
    refreshDataHandler() {
      this.refreshData();
    },
    refreshData() {
      overlay.show();
      let qid = "";

      if (
        appState.aggregate.title === "LGA" ||
        appState.aggregate.title === ""
      ) {
        qid = "404a";
      } else if (appState.aggregate.title === "Ward") {
        qid = "404b&lgaId=" + appState.aggregate.lgaId;
      } else if (appState.aggregate.title === "DP") {
        qid = "404c&wardId=" + appState.aggregate.wardId;
      }

      Promise.all([
        this.fetchData(qid, (data) => {
          this.tableData = data;
        }),
      ]).finally(() => {
        overlay.hide();
      });
    },
    fetchData(qid, onSuccess) {
      const url = `${common.DataService}?qid=${qid}`;
      return axios
        .get(url)
        .then((response) => {
          const data = response.data?.data || [];
          onSuccess(data);
        })
        .catch((error) => {
          console.error(`Error fetching qid=${qid} data:`, error);
        });
    },
    gotoPageHandler(data) {
      if (appState.currentDrillData == "aggregate_lga") {
        this.refreshData();
      }
    },
    goToWardSummaryPage(data) {
      Object.assign(appState.aggregate, {
        title: "Ward",
        lgaId: data?.lgaId,
        lgaName: data?.lgaName,
        page: data?.page,
      });

      this.refreshData();
    },
    goToDPSummaryPage(data) {
      Object.assign(appState.aggregate, {
        title: "DP",
        wardId: data?.wardId,
        wardName: data?.wardName,
        page: data?.page,
      });

      this.refreshData();
    },
    handleRowClick(g) {
      appState.currentDrillData == "aggregate_lga";

      if (this.appState.aggregate.title === "LGA") {
        this.goToWardSummaryPage({
          lgaId: g.id,
          lgaName: g.title,
          page: "ward_summary",
        });
      } else if (this.appState.aggregate.title === "Ward") {
        this.goToDPSummaryPage({
          wardId: g.id,
          wardName: g.title,
          page: "dp_summary",
        });
      }
    },
  },
  template: `
      <div class="content-header row">
          <div class="col-12 mb-1">
              <div class="card">
                  <div class="table-responsive scrollBox perfect-scroll-grid" style="height: 420px !important; overflow: hidden;">
                     <table class="table table-fixed border-top table-hover table-fixed">
                          <thead  style="position: sticky; top: 0; background: #fff; z-index: 2;">
                            <tr>
                              <th>{{ appState.aggregate.title }}</th>
                              <th class="bg-light-primary-1 px-1">HH Mobilized</th>
                              <th class="bg-light-success-1 px-1">HH Redeemed</th>
                              <th class="bg-light-primary-1 px-1">Family Size Mobilized</th>
                              <th class="bg-light-success-1 px-1">Family Size Redeemed</th>
                              <th class="bg-light-primary-1 px-1">Net Mobilized</th>
                              <th class="bg-light-success-1 px-1">Net Redeemed</th>
                              <th class="px-75">Net Rate</th>
                            </tr>
                          </thead>

                          <tbody>
                            <template v-if="tableData.length">
                              <tr v-for="g in tableData" :key="g.id" @click="handleRowClick(g)">
                                <td>
                                  <i v-if="appState.aggregate.title !== 'DP'" class="ti ti-circle-plus text-primary mr-2"></i>
                                  {{ g.title }} {{ appState.aggregate.title }}
                                </td>

                                <td class="bg-light-primary-1 px-1">{{ formatNumber(g.household_mobilized) }}</td>
                                <td class="bg-light-success-1 px-1">{{ formatNumber(g.household_redeemed) }}</td>
                                <td class="bg-light-primary-1 px-1">{{ formatNumber(g.familysize_mobilized) }}</td>
                                <td class="bg-light-success-1 px-1">{{ formatNumber(g.familysize_redeemed) }}</td>
                                <td class="bg-light-primary-1 px-1">{{ formatNumber(g.net_issued) }}</td>
                                <td class="bg-light-success-1 px-1">{{ formatNumber(g.net_redeemed) }}</td>

                                <td class="px-75 pt-25 bg-progress">
                                  <small class="text-heading d-flex">
                                    {{ progressBarWidth(g.net_issued, g.net_redeemed) }}
                                  </small>
                                  <div class="d-flex font-small-1 align-items-center">
                                    <div class="progress w-100 me-3" style="height: 6px;">
                                      <div
                                        class="progress-bar"
                                        :class="progressBarStatus(g.net_issued, g.net_redeemed)"
                                        :style="{ width: progressBarWidth(g.net_issued, g.net_redeemed) }"
                                        :aria-valuenow="parseFloat(progressBarWidth(g.net_issued, g.net_redeemed))"
                                        aria-valuemin="0"
                                        aria-valuemax="100"
                                      ></div>
                                    </div>
                                  </div>
                                </td>
                              </tr>
                            </template>

                            <tr v-else>
                              <td colspan="8" class="text-center pt-2">
                                <small>No Data</small>
                              </td>
                            </tr>
                          </tbody>
                        </table>

                      <div class="mb-2"></div>
                  </div>

              </div>
          </div>
      </div>
    `,
});

Vue.component("Daily-Aggregate-table", {
  mixins: [eventBusMixin],
  data: function () {
    return {
      appState,
      tableData: [],
      psInstances: [],
      isLoading: false,
      chartData: [],
      chartTitle: "Daily Summary",
    };
  },
  mounted() {
    /* Manages events Listening */
    this.initPerfectScrollbars();
    /* Manages events Listening */
    appState.dailyAggregate.title = "Date";
    appState.dailyAggregate.chartTitle = "Daily Summary";

    const switchMoon = document.querySelector(".switch-moon");
    if (switchMoon) {
      switchMoon.addEventListener("click", this.toggleDarkMode);
    }

    this.refreshData();
  },
  beforeDestroy() {
    this.destroyPerfectScrollbars();
  },
  methods: {
    initPerfectScrollbars() {
      const containers = this.$el.querySelectorAll(".perfect-scroll-grid");
      const self = this;
      containers.forEach((el) => {
        self.psInstances.push(new PerfectScrollbar(el));
      });
    },
    destroyPerfectScrollbars() {
      this.psInstances.forEach((ps) => ps.destroy());
      this.psInstances = [];
    },
    toggleDarkMode() {
      const isDarkMode =
        document.documentElement.classList.contains("dark-layout");

      // Update chart with the correct color
      const chartComponent = this.$refs.dailyAggregateChart;
      if (chartComponent) {
        const newColor = isDarkMode ? "#d0d2d6" : "#212121";
        chartComponent.updateOptions({
          dataLabels: {
            style: {
              colors: [newColor],
            },
          },
        });
      }
    },
    refreshAllData() {
      EventBus.$emit("g-event-refresh-page");
    },
    refreshDataHandler() {
      this.refreshData();
    },
    refreshData() {
      this.isLoading = true; // Start loading
      overlay.show();

      const { title, date, lgaId, wardId } = appState.dailyAggregate;
      let qid = "";

      switch (title) {
        case "Ward":
          qid = `405c&lgaId=${lgaId}&date=${date}`;
          break;
        case "Dp":
          qid = `405d&wardId=${wardId}&date=${date}`;
          break;
        case "LGA":
          qid = `405b&date=${date}`;
          break;
        default:
          qid = `405a`;
          break;
      }

      this.fetchData(qid, (data) => {
        this.tableData = data?.data || [];
        const newChartData = data?.chart || [];

        this.chartData = newChartData;
        this.updateChart(newChartData);
      })
        .catch((error) => {
          console.error("Fetch Data Error:", error);
          alert.Error("ERROR", error);
        })
        .finally(() => {
          this.isLoading = false; // End loading
          overlay.hide();
        });
    },
    updateChart(newData) {
      const chartComponent = this.$refs.dailyAggregateChart;

      if (chartComponent && chartComponent.updateSeries) {
        const newSeries = newData?.[0] || [];
        const newCategories = newData?.[1] || [];

        chartComponent.updateSeries(newSeries, true); // true = animate update

        chartComponent.updateOptions(
          {
            xaxis: {
              categories: newCategories,
            },
          },
          true,
          true
        ); // both true = animate update
      }
    },
    fetchData(qid, onSuccess) {
      const url = `${common.DataService}?qid=${qid}`;
      return axios
        .get(url)
        .then((response) => {
          const data = response?.data || [];
          onSuccess(data);
        })
        .catch((error) => {
          console.error(`Error fetching qid=${qid} data:`, error);
        });
    },
    gotoPageHandler(data) {
      if (appState.currentDrillData == "dailyAggregate_lga") {
        this.refreshData();
      }
    },
    goToTopSummaryPage(data) {
      Object.assign(appState.dailyAggregate, {
        title: "LGA",
        date: data?.date,
        page: data?.page,
        chartTitle: this.displayDate(data?.date) + " Summary",
      });

      this.refreshData();
    },
    goToLGASummaryPage(data) {
      Object.assign(appState.dailyAggregate, {
        title: "Ward",
        lgaId: data?.lgaId,
        lgaName: data?.lgaName,
        page: data?.page,
        chartTitle: data?.lgaName + " Summary",
      });

      this.refreshData();
    },
    goToWardSummaryPage(data) {
      Object.assign(appState.dailyAggregate, {
        title: "Dp",
        wardId: data?.wardId,
        wardName: data?.wardName,
        page: data?.page,
        chartTitle: data?.wardName + " Summary",
      });

      this.refreshData();
    },
    goToDPSummaryPage(data) {
      Object.assign(appState.dailyAggregate, {
        page: data?.page,
        chartTitle: data?.page + " Summary",
      });

      this.refreshData();
    },
    handleRowClick(g) {
      appState.currentDrillData = "dailyAggregate_lga";

      const { title } = appState.dailyAggregate;

      const actions = {
        Date: () =>
          this.goToTopSummaryPage({ date: g.title, page: "top_summary" }),
        LGA: () =>
          this.goToLGASummaryPage({
            lgaId: g.id,
            lgaName: g.title,
            page: "lga_summary",
          }),
        Ward: () =>
          this.goToWardSummaryPage({
            wardId: g.id,
            wardName: g.title,
            lgaId: g.id,
            lgaName: g.title,
            page: "ward_summary",
          }),
      };

      actions[title]?.();
    },
    goBack(data) {
      Object.assign(appState.dailyAggregate, {
        title: data?.title,
        page: data?.page,
        chartTitle: data?.chartTitle,
        currentDrillData: "dailyAggregate_lga",
      });
      EventBus.$emit("g-event-goto-page", data);
    },
  },
  computed: {
    // Generate the chart series from chartData
    series() {
      return this.chartData && this.chartData[0] ? this.chartData[0] : [];
    },

    // Generate the x-axis categories from chartData
    chartOptions() {
      const chartData = this.chartData || [];
      const categories = chartData[1] || [];

      const isDarkMode =
        document.documentElement.classList.contains("dark-layout");
      const newColor = isDarkMode ? "#d0d2d6" : "#212121";
      return {
        chart: {
          id: "daily-aggregate",
          type: "bar",
          offsetX: 0,
        },
        states: {
          normal: {
            filter: { type: "none", value: 0 },
          },
          hover: {
            filter: { type: "darken", value: 0.7 },
          },
        },
        animations: {
          enabled: true,
          easing: "easeinout",
          speed: 800,
          animateGradually: { enabled: true, delay: 150 },
          dynamicAnimation: { enabled: true, speed: 700 },
        },
        toolbar: { show: true },
        // colors: ["#826af9", "#C3A1F1", "#F4B8E1"],
        // colors: ["#17395c", "#efb758", "#c24229"],
        // colors: ["#6050dc", "#0247fe", "#8a2be2"],

        colors: ["#53d2dc", "#3196e2", "#ff826c", "ffc05f"],
        legend: { show: true, position: "top", horizontalAlign: "start" },
        grid: { show: false },
        xaxis: {
          axisBorder: { show: true },
          categories,
          title: {
            style: {
              color: "#6e6b7b",
              fontWeight: "bold",
            },
            offsetY: 10,
          },
          axisTicks: { show: false },
        },
        fill: { opacity: 1, type: "solid" },
        tooltip: { shared: false },
        yaxis: {
          axisBorder: { show: false },
          axisTicks: { show: false },
          title: {
            style: {
              color: "#6e6b7b",
              fontWeight: "bold",
            },
          },
          labels: {
            formatter: function (val) {
              return parseInt(val).toLocaleString();
            },
            show: false,
          },
        },
        plotOptions: {
          bar: {
            dataLabels: {
              position: "top", // Place data labels on top of the bars
            },
            // columnWidth: "10%",
          },
        },
        dataLabels: {
          enabled: true,
          formatter: function (val) {
            return parseInt(val).toLocaleString();
          },
          distributed: true,
          offsetY: -20,

          style: {
            fontSize: "12px",
            colors: [newColor],
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
    },
  },

  template: `
            <div class="content-header row" v-cloak>

                <div class="col-12 mt-2">
                    <div class="breadcrumb-wrapper reporting-dashboard">
                        <ol class="breadcrumb pl-0">
                            <li class="breadcrumb-item" @click="goBack({page: '', title: 'Date', chartTitle: 'Daily Summary' })" :class="appState.dailyAggregate.page==''? 'active': ''" v-if="appState.dailyAggregate.page=='' || appState.dailyAggregate.page=='top_summary' || appState.dailyAggregate.page=='lga_summary' || appState.dailyAggregate.page=='ward_summary'">Daily Summary </li>
                            <li class="breadcrumb-item" @click="goBack({page: 'top_summary', title: 'LGA', chartTitle: displayDate(appState.dailyAggregate.date) })" :class="appState.dailyAggregate.page=='top_summary'? 'active': ''" v-if="appState.dailyAggregate.page=='top_summary' || appState.dailyAggregate.page=='lga_summary' || appState.dailyAggregate.page=='ward_summary'">{{displayDate(appState.dailyAggregate.date)}} </li>
                            <li class="breadcrumb-item" @click="goBack({page: 'lga_summary', title: 'Ward', chartTitle: capitalizeEachWords(appState.dailyAggregate.lgaName) })" :class="appState.dailyAggregate.page=='lga_summary'? 'active': ''" v-if="appState.dailyAggregate.page=='lga_summary' || appState.dailyAggregate.page=='ward_summary'">{{capitalizeEachWords(appState.dailyAggregate.lgaName)}} </li>
                            <li class="breadcrumb-item" :class="appState.dailyAggregate.page=='ward_summary'? 'active': ''" v-if="appState.dailyAggregate.page=='ward_summary'">{{capitalizeEachWords(appState.dailyAggregate.wardName)}} </li>
                        </ol>
                    </div>
                </div>

                <div class="col-12 col-sm-6 col-md-6 col-lg-6">
                    <div class="card">
                        <div class="table-responsive scrollBox perfect-scroll-grid"
                            style="height: 550px !important; overflow: hidden;">
                          
                            <table class="table table-fixed bordered border-top table-hover table-fixed">
                              <thead style="position: sticky; top: 0; background: #fff; z-index: 2;">
                                  <tr>
                                      <th colspan="2">{{ appState.dailyAggregate.title }}</th>
                                      <th>HH Redeemed</th>
                                      <th>Net Redeemed</th>
                                      <th>Family Size Redeemed</th>
                                  </tr>
                              </thead>

                              <tbody>
                                  <tr v-if="isLoading">
                                      <td colspan="5" class="text-center py-2">
                                          <div class="spinner-border text-primary" role="status" style="width: 2rem; height: 2rem;">
                                              <span class="sr-only">Loading...</span>
                                          </div>
                                      </td>
                                  </tr>

                                  <template v-else-if="tableData.length > 0">
                                      <tr v-for="g in tableData" :key="g.id" @click="handleRowClick(g)">
                                          <td v-if="appState.dailyAggregate.title !== 'Dp'">
                                              <i class="ti ti-circle-plus text-primary"></i>
                                          </td>
                                          <td v-if="appState.dailyAggregate.page==''" :colspan="appState.dailyAggregate.title === 'Dp' ? 2 : 1">
                                              {{ displayDate(g.title) }}
                                          </td>

                                          <td v-else :colspan="appState.dailyAggregate.title === 'Dp' ? 2 : 1">
                                              {{ capitalizeEachWords(g.title) }}
                                          </td>

                                          <td>{{ formatNumber(g.household_redeemed) }}</td>
                                          <td>{{ formatNumber(g.net_redeemed) }}</td>
                                          <td>{{ formatNumber(g.familysize_redeemed) }}</td>
                                      </tr>
                                  </template>

                                  <tr v-else>
                                      <td colspan="5" class="text-center pt-2">
                                          <small>No Data</small>
                                      </td>
                                  </tr>
                              </tbody>
                          </table>

                            <div class="mb-2"></div>
                        </div>

                    </div>



                </div>

                <div class="col-12 col-sm-6 col-md-6 col-lg-6">
                  <div class="card">
                      <div class="card-header pt-1 d-flex flex-md-row flex-column justify-content-md-between justify-content-start align-items-md-center align-items-start">
                        <div class="font-weight-bold font-small-4 custom-breadcrum">{{appState.dailyAggregate.chartTitle}}</div>
                      </div>
                      <div class="card-body" style="position: relative;">
                          <apex-chart v-if="chartOptions && series.length"
                                type="bar"
                                :options="chartOptions"
                                :series="series"
                                ref="dailyAggregateChart"
                              />
                      </div>
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
