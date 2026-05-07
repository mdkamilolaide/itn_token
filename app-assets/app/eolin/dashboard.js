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
  data: {
    lgaId: null,
    lgaName: null,
    wardId: null,
    wardName: null,
    hhmId: null,
    hhmName: null,
  },
});
// Centralized reactive state
const appState = Vue.observable({
  mobStates: createPageState(),
  mobStatisticData: [],
  distStates: createPageState(),
  currentDrillData: "",
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
    formatNumber(num) {
      let data = num ? parseInt(num) : 0;
      return data ? data.toLocaleString() : 0;
    },
    percentageUsed(total_data, used) {
      const percentUsed = (parseFloat(used) / parseFloat(total_data)) * 100;
      if (isNaN(percentUsed)) {
        return 0;
      }
      // return Math.ceil(percentUsed * 10) / 10;

      return percentUsed.toFixed(1);
    },
    progressBarWidth(total_data, used) {
      return this.percentageUsed(total_data, used) + "%";
    },
    progressBarStatus(total_data, used) {
      const progress = this.percentageUsed(total_data, used);

      if (progress >= 90) return "bg-success";
      if (progress >= 70) return "bg-info";
      if (progress >= 40) return "bg-warning";
      if (progress > 30) return "bg-secondary";
      return "bg-danger";
    },
  },
});

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
          <div class="content-header row" id="basic-statistics">
              <div class="content-header-left col-sm-8 col-md-9 col-8 mb-2">
                  <div class="row breadcrumbs-top">
                    <div class="col-12">
                        <h2 class="content-header-title header-txt float-left mb-0">EOLIN</h2>
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

             

              <div class="col-12 col-md-6 col-sm-12 col-lg-6">
                  <EOLIN_MOB_ALL_STAT_COMPONENT/>

                  <div v-show="appState.mobStates.page == ''">
                      <EOLIN_LGA_MOB_TOP_SUMMARY_COMPONENT/>
                  </div>

                  <div v-show="appState.mobStates.page == 'ward_summary'">
                      <EOLIN_WARD_MOB_TOP_SUMMARY_COMPONENT/>
                  </div>

                  <div v-show="appState.mobStates.page == 'dp_summary'">
                      <EOLIN_DP_MOB_TOP_SUMMARY_COMPONENT/>
                  </div>
              </div>

              <div class="col-12 col-md-6 col-sm-12 col-lg-6">

                  <EOLIN_DIST_ALL_STAT_COMPONENT />
                  
                  <div v-show="appState.distStates.page == ''">
                      <EOLIN_LGA_DIST_TOP_SUMMARY_COMPONENT />
                  </div>

                  <div v-show="appState.distStates.page == 'ward_summary'">
                      <EOLIN_WARD_DIST_TOP_SUMMARY_COMPONENT />
                  </div>

                  <div v-show="appState.distStates.page == 'dp_summary'">
                      <EOLIN_DP_DIST_TOP_SUMMARY_COMPONENT />
                  </div>

              
              </div>
          </div>

    `,
});

Vue.component("EOLIN_MOB_ALL_STAT_COMPONENT", {
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
        this.fetchData("1150", (data) => {
          this.allStatistics = appState.mobStatisticData = data;
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
    goBack(data) {
      appState.mobStates.page = data?.page;
      appState.mobStates.lgaId = data?.lgaId ?? "";
      appState.mobStates.lgaName = data?.lgaName ?? "";
      appState.mobStates.wardId = data?.wardId ?? "";
      appState.mobStates.wardName = data?.wardName ?? "";
      EventBus.$emit("g-event-goto-page", data);
    },
  },
  computed: {
    keyLabels() {
      return {
        total_household: {
          label: "HHs with Old Nets",
          icon: "arrow-up-left",
          colorClass: "text-success",
        },
        total_net: {
          label: "Old Nets Available",
          icon: "trending-up",
          colorClass: "text-primary",
        },
      };
    },
    // Part 1: These are shown first
    topStats() {
      const keys = ["total_household", "total_net"];
      const obj = this.allStatistics[0] || {};
      return keys.map((key) => ({
        key,
        label: this.keyLabels[key]?.label || key,
        icon: this.keyLabels[key]?.icon || "info",
        colorClass: this.keyLabels[key]?.colorClass || "text-dark",
        value: obj[key],
      }));
    },
  },
  template: `
            <div class="content-header row">

                <!-- Top Stats Cards -->
                <div class="col-lg-6 col-sm-6 col-12" v-for="g in topStats" :key="g.key">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <span>{{ g.label }}</span>
                                <div class="avatar bg-light-primary p-50 m-0 mt--1">
                                    <div class="avatar-content">
                                        <i :data-feather="g.icon" class="font-medium-4"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="d-block mt-50 pb-1">
                                <div class="role-heading pb-25">
                                    <h4 class="fw-bolder">
                                        <span v-if="!isNaN(g.value)">{{ formatNumber(g.value) }}</span>
                                        <span v-else class="spinner-border text-secondary spinner-border-sm" role="status"
                                            aria-hidden="true"></span>
                                    </h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <div class="breadcrumb-wrapper reporting-dashboard">
                        <ol class="breadcrumb pl-0">
                            <li class="breadcrumb-item" @click="goBack({page: '', lgaName: appState.mobStates.lgaName, lgaId: appState.mobStates.lgaId })" :class="appState.mobStates.page==''? 'active': ''" v-if="appState.mobStates.page=='ward_summary' || appState.mobStates.page=='dp_summary' || appState.mobStates.page==''">LGA Summary</li>
                            <li class="breadcrumb-item" @click="goBack({page: 'ward_summary', lgaName: appState.mobStates.lgaName, lgaId: appState.mobStates.lgaId, wardId: appState.mobStates.wardId })" :class="appState.mobStates.page=='ward_summary'? 'active': ''" v-if="appState.mobStates.page=='ward_summary' || appState.mobStates.page=='dp_summary'">{{appState.mobStates.lgaName}} LGA</li>
                            <li class="breadcrumb-item" :class="appState.mobStates.page == 'dp_summary'? 'active': ''"  v-if="appState.mobStates.page=='dp_summary'">{{appState.mobStates.wardName}} Ward</li>
                        </ol>
                    </div>
                </div>

                
            </div>
        
    `,
});

Vue.component("EOLIN_LGA_MOB_TOP_SUMMARY_COMPONENT", {
  mixins: [eventBusMixin],
  data: function () {
    return {
      appState,
      tableData: [],
    };
  },
  mounted() {
    /*  Manages events Listening    */
    this.refreshData();
  },
  methods: {
    gotoPageHandler(data) {
      if (
        appState.mobStates.page == "" &&
        (appState.currentDrillData == "mobilization" ||
          appState.currentDrillData == "")
      ) {
        this.refreshData();
      }
    },
    refreshDataHandler() {
      if (appState.mobStates.page == "") {
        this.refreshData();
      }
    },
    goToWardSummaryPage(data) {
      appState.mobStates.page = data?.page;
      appState.mobStates.lgaId = data?.lgaId;
      appState.mobStates.lgaName = data?.lgaName;

      appState.currentDrillData = "mobilization";
      EventBus.$emit("g-event-goto-page", data);
    },
    refreshData() {
      overlay.show();
      Promise.all([
        this.fetchData("1151", (data) => {
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
  },
  computed: {
    // Part 4: Sort table data by LGA
    sortedByLGA() {
      return [...this.tableData].sort((a, b) => a.lga.localeCompare(b.lga));
    },
  },
  template: `
            <div class="content-header row" id="basic-table" v-cloak>

                <div class="col-12 mb-1">
                    <div class="card" style="height: 350px !important;">
                        
                        <div class="table-responsive scrollBox">
                            <table class="table table-fixed border-top table-striped table-hover table-hover-animation">
                                <thead>
                                    <tr>
                                        <th colspan="2">LGA</th>
                                        <th>HHs with Old Nets</th>
                                        <th>Old Nets Available</th>
                                    </tr>
                                </thead>
                                <tbody>

                                    <tr v-for="g in sortedByLGA" :key="g.lga" 
                                        @click="goToWardSummaryPage({lgaId: g.lgaid, lgaName: g.lga, page: 'ward_summary'})">
                                        <td ><i class="ti ti-circle-plus text-primary"></i></td>
                                        <td style="padding-left: .4rem !important;">{{g.lga}}</td>
                                        <td>{{formatNumber(g.total_household)}}</td>
                                        <td>{{formatNumber(g.total_net)}}</td>
                                    </tr>
                                    
                                    <tr v-if="tableData.length == 0">
                                        <td colspan="4" class="text-center pt-2"><small>No Data</small></td>
                                    </tr>
                                </tbody>
                            </table>

                        </div>
                        <div class="mb-50"></div>
                           
                    </div>
                </div>

            </div>
        
    `,
});

Vue.component("EOLIN_WARD_MOB_TOP_SUMMARY_COMPONENT", {
  mixins: [eventBusMixin],
  data: function () {
    return {
      appState,
      tableData: [],
    };
  },
  methods: {
    refreshDataHandler() {
      if (appState.mobStates.page == "ward_summary") {
        this.refreshData();
      }
    },
    goToDPSummaryPage(data) {
      appState.mobStates.page = data?.page;

      appState.mobStates.wardId = data?.wardId;
      appState.mobStates.wardName = data?.wardName;

      appState.currentDrillData = "mobilization";
      EventBus.$emit("g-event-goto-page", data);
    },
    gotoPageHandler(data) {
      if (
        appState.mobStates.page == "ward_summary" &&
        appState.currentDrillData == "mobilization"
      ) {
        this.refreshData();
      }
    },
    refreshData() {
      overlay.show();

      Promise.all([
        this.fetchData("1152", (data) => {
          this.tableData = data;
        }),
      ]).finally(() => {
        overlay.hide();
      });
    },
    fetchData(qid, onSuccess) {
      const url = `${common.DataService}?qid=${qid}&lgaId=${appState.mobStates.lgaId}`;
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
  },
  computed: {
    // Part 4: Sort table data by LGA
    sortedByWard() {
      return [...this.tableData].sort((a, b) => a.ward.localeCompare(b.ward));
    },
  },
  template: `
            <div class="content-header row" id="basic-table" v-cloak>
    
                <div class="col-12 mb-1">
                    <div class="card" style="height: 350px !important;">
                        
                        <div class="table-responsive scrollBox">
                            <table class="table table-fixed border-top table-striped table-hover table-hover-animation">
                                <thead>
                                    <tr>
                                        <th colspan="2">Ward</th>
                                        <th>HHs with Old Nets</th>
                                        <th>Old Nets Available</th>
                                    </tr>
                                </thead>
                                <tbody>

                                    <tr v-for="g in sortedByWard" :key="g.wardid" @click="goToDPSummaryPage({wardId: g.wardid, wardName: g.ward, page: 'dp_summary'})">
                                        <td ><i class="ti ti-circle-plus text-primary"></i></td>
                                        <td style="padding-left: .4rem !important;">{{g.ward}}</td>
                                        <td>{{formatNumber(g.total_household)}}</td>
                                        <td>{{formatNumber(g.total_net)}}</td>
                                    </tr>
                                    
                                    <tr v-if="tableData.length == 0">
                                        <td colspan="4" class="text-center pt-2"><small>No Data</small></td>
                                    </tr>
                                </tbody>
                            </table>

                        </div>
                        <div class="mb-50"></div>

                        
                    </div>
                </div>

            </div>
        
    `,
});

Vue.component("EOLIN_DP_MOB_TOP_SUMMARY_COMPONENT", {
  mixins: [eventBusMixin],
  data: function () {
    return {
      appState,
      tableData: [],
    };
  },
  mounted() {
    /*  Manages events Listening    */
    // this.refreshData();
  },
  methods: {
    refreshDataHandler() {
      if (appState.mobStates.page == "dp_summary") {
        this.refreshData();
      }
    },
    refreshAllData() {
      EventBus.$emit("g-event-refresh-page", {
        page: this.page,
        distributionPage: this.distributionPage,
      });
    },
    refreshData() {
      overlay.show();

      Promise.all([
        this.fetchData("1153", (data) => {
          this.tableData = data;
        }),
      ]).finally(() => {
        overlay.hide();
      });
    },
    fetchData(qid, onSuccess) {
      const url = `${common.DataService}?qid=${qid}&wardId=${appState.mobStates.wardId}`;
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
      if (
        appState.mobStates.page == "dp_summary" &&
        appState.currentDrillData == "mobilization"
      ) {
        this.refreshData();
      }
    },
  },
  template: `
            <div class="content-header row" id="basic-table" v-cloak>

                <div class="col-12 mb-1">
                    <div class="card" style="height: 350px !important;">
                        
                        <div class="table-responsive scrollBox">
                            <table class="table table-fixed border-top table-striped table-hover table-hover-animation">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th style="padding-left: .4rem !important;">DP</th>
                                        <th>HHs with Old Nets</th>
                                        <th>Old Nets Available</th>
                                    </tr>
                                </thead>
                                <tbody>

                                    <tr v-for="(g, i) in tableData" :key="g.userid">
                                        <td>{{i+1}}</td>
                                        <td style="padding-left: .4rem !important;">{{g.dp}}</td>
                                        <td>{{formatNumber(g.total_household)}}</td>
                                        <td>{{formatNumber(g.total_net)}}</td>
                                    </tr>
                                    
                                    <tr v-if="tableData.length == 0">
                                        <td colspan="2" class="text-center pt-2"><small>No Data</small></td>
                                    </tr>
                                </tbody>
                            </table>

                        </div>
                        <div class="mb-50"></div>

                        
                    </div>
                </div>

            </div>
        
    `,
});

Vue.component("EOLIN_DIST_ALL_STAT_COMPONENT", {
  mixins: [eventBusMixin],
  data: function () {
    return {
      appState,
      allDistributionStatistics: [],
    };
  },
  mounted() {
    /*  Manages events Listening    */
    this.refreshData();
  },
  methods: {
    refreshDataHandler() {
      this.refreshData();
    },
    refreshData() {
      overlay.show();

      Promise.all([
        this.fetchData("1180", (data) => {
          this.allDistributionStatistics = data;
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
    goBack(data) {
      appState.distStates.page = data?.page;
      appState.distStates.lgaId = data?.lgaId ?? "";
      appState.distStates.lgaName = data?.lgaName ?? "";
      appState.distStates.wardId = data?.wardId ?? "";
      appState.distStates.wardName = data?.wardName ?? "";

      EventBus.$emit("g-event-goto-page", data);
    },
    progressTarget(index) {
      const data = this.appState.mobStatisticData[0] || {};
      return index === 0 ? data.total_household : data.total_net;
    },
    showProgress(index, value) {
      return [0, 1].includes(index) && !isNaN(value);
    },
  },
  computed: {
    keyLabels() {
      return {
        total_household: {
          label: "HHs that Returned old Nets",
          icon: "arrow-down-left",
          colorClass: "text-success",
        },
        total_net: {
          label: "Returned old Nets",
          icon: "trending-down",
          colorClass: "text-primary",
        },
      };
    },
    // Part 1: These are shown first
    topStats() {
      const keys = ["total_household", "total_net"];
      const obj = this.allDistributionStatistics[0] || {};
      return keys.map((key) => ({
        key,
        label: this.keyLabels[key]?.label || key,
        icon: this.keyLabels[key]?.icon || "info",
        colorClass: this.keyLabels[key]?.colorClass || "text-dark",
        value: obj[key],
      }));
    },

    // Part 2: Sort table data by LGA
    sortedByLGA() {
      return [...this.tableData].sort((a, b) => a.lga.localeCompare(b.lga));
    },
  },
  template: `
            <div class="content-header row">
             
                <!-- Top Stats Cards -->
                <div class="col-lg-6 col-sm-6 col-12" v-for="(g, i) in topStats" :key="g.key">
                    <div class="card">
                        <div class="card-body pb-1">
                            <div class="d-flex justify-content-between">
                                <span>{{ g.label }}</span>
                                <div class="avatar bg-light-primary p-50 m-0 mt--1">
                                    <div class="avatar-content">
                                        <i :data-feather="g.icon" class="font-medium-4"></i>
                                    </div>
                                </div>
                            </div>

                            <div class="d-block pt-25">
                                <div class="role-heading">
                                    <h4 class="fw-bolder">
                                        <template v-if="!isNaN(g.value)">
                                            {{ formatNumber(g.value) }}
                                        </template>
                                        <template v-else>
                                            <span class="spinner-border text-secondary spinner-border-sm" role="status"
                                                aria-hidden="true"></span>
                                        </template>
                                    </h4>
                                </div>

                                <div v-if="showProgress(i, g.value)">
                                    <div class="text-right pt-25">
                                        <small class="text-heading d-block text-right">
                                            {{ progressBarWidth(progressTarget(i), g.value) }}
                                        </small>
                                        <div class="d-flex font-small-1 align-items-right">
                                            <div class="progress w-100 me-3" style="height: 6px;">
                                                <div class="progress-bar" :class="progressBarStatus(progressTarget(i), g.value)"
                                                    :style="{ width: progressBarWidth(progressTarget(i), g.value) }"
                                                    :aria-valuenow="parseFloat(progressBarWidth(progressTarget(i), g.value))"
                                                    aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
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
                            <li class="breadcrumb-item" @click="goBack({page: '', lgaName: appState.distStates.lgaName, lgaId: appState.distStates.lgaId })" :class="appState.distStates.page==''? 'active': ''" v-if="appState.distStates.page=='ward_summary' || appState.distStates.page=='dp_summary' || appState.distStates.page==''">LGA Summary</li>
                            <li class="breadcrumb-item" @click="goBack({page: 'ward_summary', lgaName: appState.distStates.lgaName, lgaId: appState.distStates.lgaId, wardId: appState.distStates.wardId })" :class="appState.distStates.page=='ward_summary'? 'active': ''" v-if="appState.distStates.page=='ward_summary' || appState.distStates.page=='dp_summary'">{{appState.distStates.lgaName}} LGA</li>
                            <li class="breadcrumb-item" :class="appState.distStates.page == 'dp_summary'? 'active': ''"  v-if="appState.distStates.page=='dp_summary'">{{appState.distStates.wardName}} Ward</li>
                        </ol>
                    </div>
                </div>

                
            </div>
        
    `,
});

Vue.component("EOLIN_LGA_DIST_TOP_SUMMARY_COMPONENT", {
  mixins: [eventBusMixin],
  data: function () {
    return {
      appState,
      tableData: [],
    };
  },
  mounted() {
    /*  Manages events Listening    */
    this.refreshData();
  },
  methods: {
    gotoPageHandler(data) {
      if (
        appState.distStates.page == "" &&
        (appState.currentDrillData == "distribution" ||
          appState.currentDrillData == "")
      ) {
        this.refreshData();
      }
    },
    refreshDataHandler() {
      if (appState.distStates.page == "") {
        this.refreshData();
      }
    },
    goToWardSummaryPage(data) {
      appState.distStates.page = data?.page;
      appState.distStates.lgaId = data?.lgaId;
      appState.distStates.lgaName = data?.lgaName;

      appState.currentDrillData = "distribution";
      EventBus.$emit("g-event-goto-page", data);
    },
    refreshData() {
      overlay.show();
      Promise.all([
        this.fetchData("1181", (data) => {
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
  },
  computed: {
    // Part 4: Sort table data by LGA
    sortedByLGA() {
      return [...this.tableData].sort((a, b) => a.lga.localeCompare(b.lga));
    },
  },
  template: `
            <div class="content-header row" id="basic-table" v-cloak>

                <div class="col-12 mb-1">
                    <div class="card" style="height: 350px !important;">
                        
                        <div class="table-responsive scrollBox">
                            <table class="table table-fixed border-top table-striped table-hover table-hover-animation">
                                <thead>
                                    <tr>
                                        <th colspan="2">LGA</th>
                                        <th>HHs that Returned old Nets</th>
                                        <th>Returned old Nets</th>
                                    </tr>
                                </thead>
                                <tbody>

                                    <tr v-for="g in sortedByLGA" :key="g.lga" 
                                        @click="goToWardSummaryPage({lgaId: g.lgaid, lgaName: g.lga, page: 'ward_summary'})">
                                        <td ><i class="ti ti-circle-plus text-primary"></i></td>
                                        <td style="padding-left: .4rem !important;">{{g.lga}}</td>
                                        <td>{{formatNumber(g.total_household)}}</td>
                                        <td>{{formatNumber(g.total_net)}}</td>
                                    </tr>
                                    
                                    <tr v-if="tableData.length == 0">
                                        <td colspan="4" class="text-center pt-2"><small>No Data</small></td>
                                    </tr>
                                </tbody>
                            </table>

                        </div>
                        <div class="mb-50"></div>
                           
                    </div>
                </div>

            </div>
        
    `,
});

Vue.component("EOLIN_WARD_DIST_TOP_SUMMARY_COMPONENT", {
  mixins: [eventBusMixin],
  data: function () {
    return {
      appState,
      tableData: [],
    };
  },
  methods: {
    refreshDataHandler() {
      if (appState.distStates.page == "ward_summary") {
        this.refreshData();
      }
    },
    goToDPSummaryPage(data) {
      appState.distStates.page = data?.page;

      appState.distStates.wardId = data?.wardId;
      appState.distStates.wardName = data?.wardName;

      appState.currentDrillData = "distribution";
      EventBus.$emit("g-event-goto-page", data);
    },
    gotoPageHandler(data) {
      if (
        appState.distStates.page == "ward_summary" &&
        appState.currentDrillData == "distribution"
      ) {
        this.refreshData();
      }
    },
    refreshData() {
      overlay.show();

      Promise.all([
        this.fetchData("1182", (data) => {
          this.tableData = data;
        }),
      ]).finally(() => {
        overlay.hide();
      });
    },
    fetchData(qid, onSuccess) {
      const url = `${common.DataService}?qid=${qid}&lgaId=${appState.distStates.lgaId}`;
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
  },
  computed: {
    // Part 4: Sort table data by LGA
    sortedByWard() {
      return [...this.tableData].sort((a, b) => a.ward.localeCompare(b.ward));
    },
  },
  template: `
            <div class="content-header row" id="basic-table" v-cloak>
    
                <div class="col-12 mb-1">
                    <div class="card" style="height: 350px !important;">
                        
                        <div class="table-responsive scrollBox">
                            <table class="table table-fixed border-top table-striped table-hover table-hover-animation">
                                <thead>
                                    <tr>
                                        <th colspan="2">Ward</th>
                                        <th>HHs that Returned old Nets</th>
                                        <th>Returned old Nets</th>
                                    </tr>
                                </thead>
                                <tbody>

                                    <tr v-for="g in sortedByWard" :key="g.wardid" @click="goToDPSummaryPage({wardId: g.wardid, wardName: g.ward, page: 'dp_summary'})">
                                        <td ><i class="ti ti-circle-plus text-primary"></i></td>
                                        <td style="padding-left: .4rem !important;">{{g.ward}}</td>
                                        <td>{{formatNumber(g.total_household)}}</td>
                                        <td>{{formatNumber(g.total_net)}}</td>
                                    </tr>
                                    
                                    <tr v-if="tableData.length == 0">
                                        <td colspan="4" class="text-center pt-2"><small>No Data</small></td>
                                    </tr>
                                </tbody>
                            </table>

                        </div>
                        <div class="mb-50"></div>

                        
                    </div>
                </div>

            </div>
        
    `,
});

Vue.component("EOLIN_DP_DIST_TOP_SUMMARY_COMPONENT", {
  mixins: [eventBusMixin],
  data: function () {
    return {
      appState,
      tableData: [],
    };
  },
  mounted() {
    /*  Manages events Listening    */
    // this.refreshData();
  },
  methods: {
    refreshDataHandler() {
      if (appState.distStates.page == "dp_summary") {
        this.refreshData();
      }
    },
    refreshData() {
      overlay.show();

      Promise.all([
        this.fetchData("1183", (data) => {
          this.tableData = data;
        }),
      ]).finally(() => {
        overlay.hide();
      });
    },
    fetchData(qid, onSuccess) {
      const url = `${common.DataService}?qid=${qid}&wardId=${appState.distStates.wardId}`;
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
      if (
        appState.distStates.page == "dp_summary" &&
        appState.currentDrillData == "distribution"
      ) {
        this.refreshData();
      }
    },
  },
  template: `
            <div class="content-header row" v-cloak>

                <div class="col-12 mb-1">
                    <div class="card" style="height: 350px !important;">
                        
                        <div class="table-responsive scrollBox">
                            <table class="table table-fixed border-top table-striped table-hover table-hover-animation">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th style="padding-left: .4rem !important;">DP</th>
                                        <th>HHs that Returned old Nets</th>
                                        <th>Returned old Nets</th>
                                    </tr>
                                </thead>
                                <tbody>

                                    <tr v-for="(g, i) in tableData" :key="g.userid">
                                        <td>{{i+1}}</td>
                                        <td style="padding-left: .4rem !important;">{{g.dp}}</td>
                                        <td>{{formatNumber(g.total_household)}}</td>
                                        <td>{{formatNumber(g.total_net)}}</td>
                                    </tr>
                                    
                                    <tr v-if="tableData.length == 0">
                                        <td colspan="2" class="text-center pt-2"><small>No Data</small></td>
                                    </tr>
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
