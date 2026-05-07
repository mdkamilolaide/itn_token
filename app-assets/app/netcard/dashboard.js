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

Vue.component("page-body", {
  mixins: [eventBusMixin],
  data: function () {
    return {
      page: "lga_summary", //  page by name training | session | participant | attendance ...
    };
  },
  mounted() {
    /*  Manages events Listening    */
  },
  methods: {
    gotoPageHandler(data) {
      this.page = data.page;
    },
  },
  template: `
    <div>
        <div class="content-body">
            <eNetcard_all_stat_component/>
            <div v-show="page == 'lga_summary' || page == ''">
                <lga_top_level_summary/>
            </div>
            <div v-show="page == 'ward_summary'">
                <ward_top_level_summary/>
            </div>
            <div v-show="page == 'hhm_summary'">
                <hhm_top_level_summary/>
            </div>

        </div>
    </div>
    `,
});

Vue.component("eNetcard_all_stat_component", {
  mixins: [eventBusMixin],
  data: function () {
    return {
      page: "",
      newPageData: {
        lgaId: null,
        lgaName: null,
        wardId: null,
        wardName: null,
        hhmId: null,
        hhmName: null,
      },
      allStatistics: [],
    };
  },
  mounted() {
    /*  Manages events Listening    */
    this.refreshData();
  },
  methods: {
    refreshAllData() {
      EventBus.$emit("g-event-refresh-page", { page: this.page });
    },
    gotoPageHandler(data) {
      this.page = data?.page;
      // Do something else like updating local state or navigating
      this.newPageData.lgaId = data?.lgaId;
      this.newPageData.lgaName = data?.lgaName;
      this.newPageData.wardId = data?.wardId;
      this.newPageData.wardName = data?.wardName;
      this.newPageData.hhmId = data?.hhmId;
      this.newPageData.hhmName = data?.hhmName;
      overlay.show();
      setTimeout(() => {
        overlay.hide();
      }, 1000);
    },
    refreshDataHandler() {
      this.refreshData();
    },
    refreshData() {
      overlay.show();

      Promise.all([
        this.fetchData("217", (data) => {
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
          const data = response.data?.data || [];
          onSuccess(data);
        })
        .catch((error) => {
          console.error(`Error fetching qid=${qid} data:`, error);
        });
    },
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
    goBack(data) {
      this.page = data?.page;
      this.newPageData.lgaId = data?.lgaId;
      this.newPageData.lgaName = data?.lgaName;
      this.newPageData.wardId = data?.wardId;
      this.newPageData.wardName = data?.wardName;
      console.log("Go back to page:", this.page);
      EventBus.$emit("g-event-goto-page", data);
      this.refreshAllData();
    },
  },
  computed: {
    keyLabels() {
      return {
        total: {
          label: "Total e-Netcard",
          icon: "database",
          colorClass: "text-success",
        },
        state: {
          label: "State Balance",
          icon: "globe",
          colorClass: "text-primary",
        },
        lga: { label: "LGA Balance", icon: "grid", colorClass: "text-info" },
        ward: { label: "Ward", icon: "target", colorClass: "text-secondary" },
        mobilizer_online: {
          label: "Mobilizer Online",
          icon: "user-check",
          colorClass: "text-success",
        },
        mobilizer_pending: {
          label: "Mobilizer Pending Balance",
          icon: "clock",
          colorClass: "text-warning",
        },
        mobilizer_wallet: {
          label: "Mobilizer Wallet Balance",
          icon: "credit-card",
          colorClass: "text-muted",
        },
        beneficiary: {
          label: "Beneficiaries",
          icon: "users",
          colorClass: "text-danger",
        },
      };
    },
    // Part 1: These are shown first
    topStats() {
      const keys = ["total", "state", "lga", "ward"];
      const obj = this.allStatistics[0] || {};
      return keys.map((key) => ({
        key,
        label: this.keyLabels[key]?.label || key,
        icon: this.keyLabels[key]?.icon || "info",
        colorClass: this.keyLabels[key]?.colorClass || "text-dark",
        value: obj[key],
      }));
    },

    // Part 2: Single item for beneficiary (after topStats)
    beneficiaryStat() {
      const obj = this.allStatistics[0] || {};
      return {
        key: "beneficiary",
        label: this.keyLabels["beneficiary"].label,
        icon: this.keyLabels["beneficiary"].icon,
        colorClass: this.keyLabels["beneficiary"].colorClass,
        value: obj["beneficiary"],
      };
    },

    // Part 3: Merged mobilizer stats grouped together (at the end)
    mergedMobilizerStats() {
      const keys = [
        "mobilizer_online",
        "mobilizer_pending",
        "mobilizer_wallet",
      ];
      const obj = this.allStatistics[0] || {};
      return keys.map((key) => ({
        key,
        label: this.keyLabels[key]?.label || key,
        icon: this.keyLabels[key]?.icon || "info",
        colorClass: this.keyLabels[key]?.colorClass || "text-dark",
        value: obj[key],
      }));
    },

    // Part 4: Sort table data by LGA
    sortedByLGA() {
      return [...this.tableData].sort((a, b) => a.lga.localeCompare(b.lga));
    },
  },
  template: `
            <div class="content-header row" id="basic-statistics" v-cloak>
                <div class="content-header-left col-sm-8 col-md-9 col-8 mb-2">
                    <div class="row breadcrumbs-top">
                    <div class="col-12">
                        <h2 class="content-header-title header-txt float-left mb-0">e-Netcard</h2>
                        <div class="breadcrumb-wrapper">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item active">Dashboard</li>
                        </ol>
                        </div>
                    </div>
                    </div>
                </div>
                <div class="content-header-right text-right col-sm-4 col-md-3 col-4 d-md-block d-sm-block">
                    <div class="form-group breadcrumb-right">
                        <div class="dropdown">
                            <button @click="refreshAllData()" class="btn-icon btn btn-primary btn-round btn-sm" type="button"><i data-feather="refresh-cw"></i></button>
                        </div>
                    </div>
                </div>

                <!-- Top Stats Cards -->
                <div class="col-lg-3 col-sm-6 col-12" v-for="(g, i) in topStats" :key="g.key">
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
                            <div class="d-block mt-50 pt-25">
                                <div class="role-heading">
                                    <h4 class="fw-bolder">
                                        <span v-if="!isNaN(g.value)">{{ formatNumber(g.value) }}</span>
                                        <span v-else class="spinner-border text-secondary spinner-border-sm" role="status"
                                            aria-hidden="true"></span>
                                    </h4>
                                </div>
                            </div>
                            <div v-if="i==0" class="text-right pt-1">
                                  <small class="text-heading d-block text-right">
                                      {{ progressBarWidth(topStats[0].value, beneficiaryStat.value) }}
                                  </small>
                                  <div class="d-flex font-small-1 align-items-center">
                                    <div class="progress w-100 me-3" style="height: 8px;">
                                      <div
                                        class="progress-bar"
                                        :class="progressBarStatus(topStats[0].value, beneficiaryStat.value)"
                                        :style="{ width: progressBarWidth(topStats[0].value, beneficiaryStat.value) }"
                                        :aria-valuenow="parseFloat(progressBarWidth(topStats[0].value, beneficiaryStat.value))"
                                        aria-valuemin="0"
                                        aria-valuemax="100"
                                      ></div>
                                    </div>
                                  </div>
                            </div>
                            <div v-else class="py-2 w-100"></div>
                        </div>
                    </div>
                </div>

                <!-- Mobilizer Stats -->
                <div class="col-sm-12 col-md-9 col-xl-9 col-12">
                    <div class="card">
                        <div class="card-body card-widget-separator">
                            <div class="row">
                                <div class="col-sm-4 col-lg-4"
                                    :class="{ 'border-end border-sm-end-0': index < mergedMobilizerStats.length - 1 }"
                                    v-for="(stat, index) in mergedMobilizerStats" :key="stat.key">
                                    <div>
                                        <div class="d-flex justify-content-between align-items-start"
                                            :class="{ 'card-widget-1  pb-sm-0': index < mergedMobilizerStats.length - 1 }">
                                            <span>{{ stat.label }}</span>
                                            <div class="avatar bg-light-primary p-50 m-0 mt--1">
                                                <div class="avatar-content">
                                                    <i :data-feather="stat.icon" class="font-medium-4"></i>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="d-block mt-50 pt-25">
                                            <div class="role-heading">
                                                <h4 class="fw-bolder">
                                                    <span v-if="!isNaN(stat.value)">{{ formatNumber(stat.value) }}</span>
                                                    <span v-else class="spinner-border text-secondary spinner-border-sm"
                                                        role="status" aria-hidden="true"></span>
                                                </h4>
                                            </div>
                                        </div>
                                        <hr class="d-block d-sm-none pb-2" v-if="index < mergedMobilizerStats.length - 1" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Beneficiary Stat Card -->
                <div class="col-lg-3 col-sm-12 col-md-3 col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <span>{{ capitalize(beneficiaryStat.label) }}</span>
                                <div class="avatar bg-light-primary p-50 m-0 mt--1">
                                    <div class="avatar-content">
                                        <i :data-feather="beneficiaryStat.icon" class="font-medium-4"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="d-block mt-50 pt-25">
                                <div class="role-heading">
                                    <h4 class="fw-bolder">
                                        <span v-if="!isNaN(beneficiaryStat.value)">{{ formatNumber(beneficiaryStat.value) }}</span>
                                        <span v-else class="spinner-border text-secondary spinner-border-sm" role="status"
                                            aria-hidden="true"></span>
                                    </h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- End of Beneficiary Stat Card -->

                <div class="col-12">
                    <div class="breadcrumb-wrapper reporting-dashboard">
                        <ol class="breadcrumb pl-0">
                            <li class="breadcrumb-item" @click="goBack({page: '', lgaName: newPageData.lgaName, lgaId: newPageData.lgaId })" :class="page==''? 'active': ''" v-if="page=='ward_summary' || page=='hhm_summary' || page==''">LGA Summary</li>
                            <li class="breadcrumb-item" @click="goBack({page: 'ward_summary', lgaName: newPageData.lgaName, lgaId: newPageData.lgaId })" :class="page=='ward_summary'? 'active': ''" v-if="page=='ward_summary' || page=='hhm_summary'">{{newPageData.lgaName}} LGA</li>
                            <li class="breadcrumb-item" :class="page == 'hhm_summary'? 'active': ''"  v-if="page=='hhm_summary'">{{newPageData.wardName}} Ward</li>
                        </ol>
                    </div>
                </div>

                
            </div>
        
    `,
});

Vue.component("lga_top_level_summary", {
  mixins: [eventBusMixin],
  data: function () {
    return {
      page: "",
      newPageData: {
        lgaId: null,
        lgaName: null,
        wardId: null,
        wardName: null,
        hhmId: null,
        hhmName: null,
      },
      tableData: [],
    };
  },
  mounted() {
    /*  Manages events Listening    */
    this.refreshData();
  },
  methods: {
    refreshDataHandler() {
      if (this.page == "lga_summary" || this.page == "") {
        this.refreshData();
      }
    },
    gotoPageHandler(data) {
      this.page = data?.page;
      // Do something else like updating local state or navigating
      this.newPageData.lgaId = data?.lgaId;
      this.newPageData.lgaName = data?.lgaName;
      this.newPageData.wardId = data?.wardId;
      this.newPageData.wardName = data?.wardName;
      this.newPageData.hhmId = data?.hhmId;
      this.newPageData.hhmName = data?.hhmName;
      overlay.show();
      setTimeout(() => {
        overlay.hide();
      }, 1000);
    },
    goToWardSummaryPage(data) {
      EventBus.$emit("g-event-goto-page", data);
      EventBus.$emit("g-event-refresh-page", data);
    },
    refreshData() {
      overlay.show();

      Promise.all([
        this.fetchData("218", (data) => {
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
                                        <th>LGA Total</th>
                                        <th>LGA Balance</th>
                                        <th>Ward Balance</th>
                                        <th>Online Balance</th>
                                        <th>Pending Balance</th>
                                        <th>Wallet Balance</th>
                                        <th>Beneficiary</th>
                                        <th>Progress</th>
                                    </tr>
                                </thead>
                                <tbody>

                                    <tr v-for="g in sortedByLGA" :key="g.lga" @click="goToWardSummaryPage({lgaId: g.LgaId, lgaName: g.lga, page: 'ward_summary'})">
                                        <td ><i class="ti ti-circle-plus text-primary"></i></td>
                                        <td style="padding-left: .4rem !important;">{{g.lga}}</td>
                                        <td>{{formatNumber(g.lga_total)}}</td>
                                        <td>{{formatNumber(g.lga_balance)}}</td>
                                        <td>{{formatNumber(g.ward)}}</td>
                                        <td>{{formatNumber(g.mob_online)}}</td>
                                        <td>{{formatNumber(g.mob_pending)}}</td>
                                        <td>{{formatNumber(g.wallet)}}</td>
                                        <td>{{formatNumber(g.beneficiary)}}</td>
                                        <td width="200px">
                                            <div class="progress" style="height: 4px;">
                                                <div :class="progressBarStatus(g.lga_total, g.beneficiary)" class="progress-bar" role="progressbar"
                                                    :style="{ width: progressBarWidth(g.lga_total, g.beneficiary) }"></div>
                                            </div>
                                            <small class="text-heading">{{progressBarWidth(g.lga_total, g.beneficiary)}}</small>
                                        </td>
                                    </tr>
                                    
                                    <tr v-if="tableData.length == 0">
                                        <td colspan="9" class="text-center pt-2"><small>No Data</small></td>
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

Vue.component("ward_top_level_summary", {
  mixins: [eventBusMixin],
  data: function () {
    return {
      page: "",
      newPageData: {
        lgaId: null,
        lgaName: null,
        wardId: null,
        wardName: null,
        hhmId: null,
        hhmName: null,
      },
      tableData: [],
    };
  },
  mounted() {
    /*  Manages events Listening    */
    // this.refreshData();
  },
  methods: {
    refreshDataHandler() {
      this.tableData = [];
      if (this.page == "ward_summary") {
        this.refreshData();
      }
    },
    refreshAllData() {
      EventBus.$emit("g-event-refresh-page", { page: this.page });
    },
    gotoPageHandler(data) {
      this.page = data?.page;
      this.newPageData.lgaId = data?.lgaId;
      this.newPageData.lgaName = data?.lgaName;
      this.newPageData.wardId = data?.wardId;
      this.newPageData.wardName = data?.wardName;
      this.newPageData.hhmId = data?.hhmId;
    },
    goToHHMSummaryPage(data) {
      EventBus.$emit("g-event-goto-page", data);
      EventBus.$emit("g-event-refresh-page", data);
    },
    refreshData() {
      overlay.show();

      Promise.all([
        this.fetchData("219", (data) => {
          this.tableData = data;
        }),
      ]).finally(() => {
        overlay.hide();
      });
    },
    fetchData(qid, onSuccess) {
      const url = `${common.DataService}?qid=${qid}&lgaId=${this.newPageData.lgaId}`;
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
        return word[0]?.toUpperCase() + loweredCase?.slice(1);
      } else {
        return word;
      }
    },
    formatNumber(num) {
      let data = num ? parseInt(num) : 0;
      return data ? data?.toLocaleString() : 0;
    },
    percentageUsed(total_data, used) {
      const percentUsed = (parseFloat(used) / parseFloat(total_data)) * 100;
      if (isNaN(percentUsed)) {
        return 0;
      }
      // return Math.ceil(percentUsed * 10) / 10;

      return percentUsed?.toFixed(1);
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
                                        <th>Ward Total</th>
                                        <th>Ward Balance</th>
                                        <th>Mobilizer Online Balance</th>
                                        <th>Mobilizer Pending Balance</th>
                                        <th>Wallet</th>
                                        <th>Beneficiary</th>
                                        <th>Progress</th>
                                    </tr>
                                </thead>
                                <tbody>

                                    <tr v-for="g in sortedByWard" :key="g.wardid" @click="goToHHMSummaryPage({wardId: g.wardid, wardName: g.ward, lgaId: newPageData.lgaId, lgaName: newPageData.lgaName, page: 'hhm_summary'})">
                                        <td ><i class="ti ti-circle-plus text-primary"></i></td>
                                        <td style="padding-left: .4rem !important;">{{g.ward}}</td>
                                        <td>{{formatNumber(g.ward_total)}}</td>
                                        <td>{{formatNumber(g.ward_balance)}}</td>
                                        <td>{{formatNumber(g.mob_online)}}</td>
                                        <td>{{formatNumber(g.mob_pending)}}</td>
                                        <td>{{formatNumber(g.wallet)}}</td>
                                        <td>{{formatNumber(g.beneficiary)}}</td>
                                        <td width="200px">
                                            <div class="progress" style="height: 4px;">
                                                <div :class="progressBarStatus(g.ward_total, g.beneficiary)" class="progress-bar" role="progressbar"
                                                    :style="{ width: progressBarWidth(g.ward_total, g.beneficiary) }"></div>
                                            </div>
                                            <small class="text-heading">{{progressBarWidth(g.ward_total, g.beneficiary)}}</small>
                                        </td>
                                    </tr>
                                    
                                    <tr v-if="tableData.length == 0">
                                        <td colspan="9" class="text-center pt-2"><small>No Data</small></td>
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

Vue.component("hhm_top_level_summary", {
  mixins: [eventBusMixin],
  data: function () {
    return {
      page: "",
      newPageData: {
        lgaId: null,
        lgaName: null,
        wardId: null,
        wardName: null,
        hhmId: null,
        hhmName: null,
      },
      tableData: [],
    };
  },
  mounted() {
    /*  Manages events Listening    */
    // this.refreshData();
  },
  methods: {
    refreshDataHandler() {
      this.tableData = [];
      if (this.page == "hhm_summary") {
        this.refreshData();
      }
    },
    refreshAllData() {
      EventBus.$emit("g-event-refresh-page", { page: this.page });
    },
    gotoPageHandler(data) {
      this.page = data?.page;
      this.newPageData.lgaId = data?.lgaId;
      this.newPageData.lgaName = data?.lgaName;
      this.newPageData.wardId = data?.wardId;
      this.newPageData.wardName = data?.wardName;
      this.newPageData.hhmId = data?.hhmId;
    },
    refreshData() {
      overlay.show();

      Promise.all([
        this.fetchData("220", (data) => {
          this.tableData = data;
        }),
      ]).finally(() => {
        overlay.hide();
      });
    },
    fetchData(qid, onSuccess) {
      const url = `${common.DataService}?qid=${qid}&wardId=${this.newPageData.wardId}`;
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
    displayDate(d) {
      let date = new Date(d);
      let options = {
        year: "numeric",
        month: "long",
        day: "numeric",
      };
      return date?.toLocaleString("en-us", options);
    },
    capitalize(word) {
      if (word) {
        const loweredCase = word?.toLowerCase();
        return word[0]?.toUpperCase() + loweredCase.slice(1);
      } else {
        return word;
      }
    },
    formatNumber(num) {
      let data = num ? parseInt(num) : 0;
      return data ? data?.toLocaleString() : 0;
    },
    percentageUsed(total_data, used) {
      const percentUsed = (parseFloat(used) / parseFloat(total_data)) * 100;
      if (isNaN(percentUsed)) {
        return 0;
      }
      // return Math.ceil(percentUsed * 10) / 10;

      return percentUsed?.toFixed(1);
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
  template: `
            <div class="content-header row" id="basic-table" v-cloak>

                <div class="col-12 mb-1">
                    <div class="card" style="height: 350px !important;">
                        
                        <div class="table-responsive scrollBox">
                            <table class="table table-fixed border-top table-striped table-hover table-hover-animation">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th style="padding-left: .4rem !important;">Mobilizer</th>
                                        <th>Mobilizer Total</th>
                                        <th>Online Balance</th>
                                        <th>Pending Balance</th>
                                        <th>Wallet</th>
                                        <th>Beneficiary</th>
                                        <th>Progress</th>
                                    </tr>
                                </thead>
                                <tbody>

                                    <tr v-for="(g, i) in tableData" :key="g.userid">
                                        <td>{{i+1}}</td>
                                        <td style="padding-left: .4rem !important;">{{g.mobilizer}}</td>
                                        <td>{{formatNumber(g.total)}}</td>
                                        <td>{{formatNumber(g.mob_online)}}</td>
                                        <td>{{formatNumber(g.mob_pending)}}</td>
                                        <td>{{formatNumber(g.wallet)}}</td>
                                        <td>{{formatNumber(g.beneficiary)}}</td>
                                        <td width="200px">
                                            <div class="progress" style="height: 4px;">
                                                <div :class="progressBarStatus(g.total, g.beneficiary)" class="progress-bar" role="progressbar"
                                                    :style="{ width: progressBarWidth(g.total, g.beneficiary) }"></div>
                                            </div>
                                            <small class="text-heading">{{progressBarWidth(g.total, g.beneficiary)}}</small>
                                        </td>
                                    </tr>
                                    
                                    <tr v-if="tableData.length == 0">
                                        <td colspan="8" class="text-center pt-2"><small>No Data</small></td>
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
