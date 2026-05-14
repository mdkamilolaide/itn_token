/**
 * EOLIN / Dashboard — Vue 3 Composition API in place.
 * Nine components — page-body + 4 mobilization stat panes (all/lga/ward/dp)
 * + 4 distribution stat panes. The two halves drill independently
 * (LGA → Ward → DP) sharing reactive state via appState.
 *
 * The Vue 2 `eventBusMixin` is replaced by inline bus.on/off in each
 * setup()'s onMounted/onBeforeUnmount.
 */

const { ref, reactive, computed, onMounted, onBeforeUnmount } = Vue;
const { useApp, useFormat, bus, safeMessage } = window.utils;

/* ------------------------------------------------------------------ */
/* Shared module-local reactive state                                   */
/* ------------------------------------------------------------------ */
const createPageState = () => {
  return {
    page: "",
    data: {
      lgaId: null,
      lgaName: null,
      wardId: null,
      wardName: null,
      hhmId: null,
      hhmName: null,
    },
  };
};

const appState = Vue.reactive({
  mobStates: createPageState(),
  mobStatisticData: [],
  distStates: createPageState(),
  currentDrillData: "",
});

/* Shared formatting / progress helpers (replaces global Vue.mixin) */
const fmt = useFormat();
const percentageUsed = (total_data, used) => {
  var p = (parseFloat(used) / parseFloat(total_data)) * 100;
  return isNaN(p) ? 0 : p.toFixed(1);
};
const progressBarWidth = (total_data, used) => {
  return percentageUsed(total_data, used) + "%";
};
const progressBarStatus = (total_data, used) => {
  var progress = percentageUsed(total_data, used);
  if (progress >= 90) return "bg-success";
  if (progress >= 70) return "bg-info";
  if (progress >= 40) return "bg-warning";
  if (progress > 30) return "bg-secondary";
  return "bg-danger";
};

/* Reusable shared returns (everything templates may reference) */
const sharedExports = () => {
  return {
    appState,
    formatNumber: fmt.formatNumber,
    capitalize: fmt.capitalize,
    displayDate: fmt.displayDate,
    percentageUsed,
    progressBarWidth,
    progressBarStatus,
  };
};

const fetchData = (qid, query, onSuccess) => {
  var url = common.DataService + "?qid=" + qid + (query || "");
  return axios
    .get(url)
    .then((response) => {
      var data = (response && response.data && response.data.data) || [];
      onSuccess(data);
    })
    .catch((error) => {
      console.error("Error fetching qid=" + qid + " data:", error);
    });
};

/* ------------------------------------------------------------------ */
/* page-body                                                            */
/* ------------------------------------------------------------------ */
const PageBody = {
  setup() {
    const refreshAllData = () => {
      bus.emit("g-event-refresh-page", {});
    };
    return Object.assign({ refreshAllData }, sharedExports());
  },
  template: `
        <div class="content-header row" id="basic-statistics">
            <div class="content-header-left col-sm-8 col-md-9 col-8 mb-2">
                <div class="row breadcrumbs-top">
                    <div class="col-12">
                        <h2 class="content-header-title header-txt float-left mb-0">EOLIN</h2>
                        <div class="breadcrumb-wrapper">
                            <ol class="breadcrumb"><li class="breadcrumb-item active">Dashboard</li></ol>
                        </div>
                    </div>
                </div>
            </div>
            <div class="content-header-right text-right col-sm-4 col-md-3 col-4 d-md-block d-sm-block mb-2">
                <button @click="refreshAllData()" class="btn-icon btn btn-primary btn-round btn-sm" type="button"><i data-feather="refresh-cw"></i></button>
            </div>

            <div class="col-12 col-md-6 col-sm-12 col-lg-6">
                <eolin_mob_all_stat_component/>
                <div v-show="appState.mobStates.page == ''"><eolin_lga_mob_top_summary_component/></div>
                <div v-show="appState.mobStates.page == 'ward_summary'"><eolin_ward_mob_top_summary_component/></div>
                <div v-show="appState.mobStates.page == 'dp_summary'"><eolin_dp_mob_top_summary_component/></div>
            </div>

            <div class="col-12 col-md-6 col-sm-12 col-lg-6">
                <eolin_dist_all_stat_component/>
                <div v-show="appState.distStates.page == ''"><eolin_lga_dist_top_summary_component/></div>
                <div v-show="appState.distStates.page == 'ward_summary'"><eolin_ward_dist_top_summary_component/></div>
                <div v-show="appState.distStates.page == 'dp_summary'"><eolin_dp_dist_top_summary_component/></div>
            </div>
        </div>
    `,
};

/* ------------------------------------------------------------------ */
/* Mobilization stat tile (top of left column)                          */
/* ------------------------------------------------------------------ */
const EolinMobAllStat = {
  setup() {
    const allStatistics = ref([]);
    const refreshData = () => {
      overlay.show();
      Promise.all([
        fetchData("1150", "", (data) => {
          allStatistics.value = data;
          appState.mobStatisticData = data;
        }),
      ]).finally(() => {
        overlay.hide();
      });
    };
    const refreshDataHandler = () => {
      refreshData();
    };

    const keyLabels = {
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
    const topStats = computed(() => {
      var keys = ["total_household", "total_net"];
      var obj = allStatistics.value[0] || {};
      return keys.map((key) => {
        var meta = keyLabels[key] || {};
        return {
          key: key,
          label: meta.label || key,
          icon: meta.icon || "info",
          colorClass: meta.colorClass || "text-dark",
          value: obj[key],
        };
      });
    });

    const goBack = (data) => {
      appState.mobStates.page = data && data.page;
      appState.mobStates.lgaId = (data && data.lgaId) || "";
      appState.mobStates.lgaName = (data && data.lgaName) || "";
      appState.mobStates.wardId = (data && data.wardId) || "";
      appState.mobStates.wardName = (data && data.wardName) || "";
      bus.emit("g-event-goto-page", data);
    };

    onMounted(() => {
      bus.on("g-event-refresh-page", refreshDataHandler);
      refreshData();
    });
    onBeforeUnmount(() => {
      bus.off("g-event-refresh-page", refreshDataHandler);
    });

    return Object.assign(
      {
        allStatistics,
        topStats,
        refreshData,
        goBack,
      },
      sharedExports(),
    );
  },
  template: `
        <div class="content-header row">
            <div class="col-lg-6 col-sm-6 col-12" v-for="g in topStats" :key="g.key">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <span>{{ g.label }}</span>
                            <div class="avatar bg-light-primary p-50 m-0 mt--1">
                                <div class="avatar-content"><i :data-feather="g.icon" class="font-medium-4"></i></div>
                            </div>
                        </div>
                        <div class="d-block mt-50 pb-1">
                            <div class="role-heading pb-25">
                                <h4 class="fw-bolder">
                                    <span v-if="!isNaN(g.value)">{{ formatNumber(g.value) }}</span>
                                    <span v-else class="spinner-border text-secondary spinner-border-sm" role="status" aria-hidden="true"></span>
                                </h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="breadcrumb-wrapper reporting-dashboard">
                    <ol class="breadcrumb pl-0">
                        <li class="breadcrumb-item" @click="goBack({page: '', lgaName: appState.mobStates.lgaName, lgaId: appState.mobStates.lgaId })" :class="appState.mobStates.page=='' ? 'active' : ''" v-if="appState.mobStates.page=='ward_summary' || appState.mobStates.page=='dp_summary' || appState.mobStates.page==''">LGA Summary</li>
                        <li class="breadcrumb-item" @click="goBack({page: 'ward_summary', lgaName: appState.mobStates.lgaName, lgaId: appState.mobStates.lgaId, wardId: appState.mobStates.wardId })" :class="appState.mobStates.page=='ward_summary' ? 'active' : ''" v-if="appState.mobStates.page=='ward_summary' || appState.mobStates.page=='dp_summary'">{{ appState.mobStates.lgaName }} LGA</li>
                        <li class="breadcrumb-item" :class="appState.mobStates.page == 'dp_summary' ? 'active' : ''" v-if="appState.mobStates.page=='dp_summary'">{{ appState.mobStates.wardName }} Ward</li>
                    </ol>
                </div>
            </div>
        </div>
    `,
};

/* ------------------------------------------------------------------ */
/* Mobilization LGA / Ward / DP top summaries                           */
/* ------------------------------------------------------------------ */
const EolinLgaMobTopSummary = {
  setup() {
    const tableData = ref([]);
    const refreshData = () => {
      overlay.show();
      Promise.all([
        fetchData("1151", "", (data) => {
          tableData.value = data;
        }),
      ]).finally(() => {
        overlay.hide();
      });
    };
    const gotoPageHandler = () => {
      if (
        appState.mobStates.page == "" &&
        (appState.currentDrillData == "mobilization" ||
          appState.currentDrillData == "")
      ) {
        refreshData();
      }
    };
    const refreshDataHandler = () => {
      if (appState.mobStates.page == "") refreshData();
    };
    const goToWardSummaryPage = (data) => {
      appState.mobStates.page = data && data.page;
      appState.mobStates.lgaId = data && data.lgaId;
      appState.mobStates.lgaName = data && data.lgaName;
      appState.currentDrillData = "mobilization";
      bus.emit("g-event-goto-page", data);
    };
    const sortedByLGA = computed(() =>
      [].concat(tableData.value).sort((a, b) => a.lga.localeCompare(b.lga)),
    );

    onMounted(() => {
      bus.on("g-event-goto-page", gotoPageHandler);
      bus.on("g-event-refresh-page", refreshDataHandler);
      refreshData();
    });
    onBeforeUnmount(() => {
      bus.off("g-event-goto-page", gotoPageHandler);
      bus.off("g-event-refresh-page", refreshDataHandler);
    });

    return Object.assign(
      { tableData, sortedByLGA, goToWardSummaryPage },
      sharedExports(),
    );
  },
  template: `
        <div class="content-header row" id="basic-table" v-cloak>
            <div class="col-12 mb-1">
                <div class="card" style="height: 350px !important;">
                    <div class="table-responsive scrollBox">
                        <table class="table table-fixed border-top table-striped table-hover table-hover-animation">
                            <thead>
                                <tr><th colspan="2">LGA</th><th>HHs with Old Nets</th><th>Old Nets Available</th></tr>
                            </thead>
                            <tbody>
                                <tr v-for="g in sortedByLGA" :key="g.lga" @click="goToWardSummaryPage({lgaId: g.lgaid, lgaName: g.lga, page: 'ward_summary'})">
                                    <td><i class="ti ti-circle-plus text-primary"></i></td>
                                    <td style="padding-left: .4rem !important;">{{ g.lga }}</td>
                                    <td>{{ formatNumber(g.total_household) }}</td>
                                    <td>{{ formatNumber(g.total_net) }}</td>
                                </tr>
                                <tr v-if="tableData.length == 0"><td colspan="4" class="text-center pt-2"><small>No Data</small></td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="mb-50"></div>
                </div>
            </div>
        </div>
    `,
};

const EolinWardMobTopSummary = {
  setup() {
    const tableData = ref([]);
    const refreshData = () => {
      overlay.show();
      Promise.all([
        fetchData("1152", "&lgaId=" + appState.mobStates.lgaId, (data) => {
          tableData.value = data;
        }),
      ]).finally(() => {
        overlay.hide();
      });
    };
    const gotoPageHandler = () => {
      if (
        appState.mobStates.page == "ward_summary" &&
        appState.currentDrillData == "mobilization"
      ) {
        refreshData();
      }
    };
    const refreshDataHandler = () => {
      if (appState.mobStates.page == "ward_summary") refreshData();
    };
    const goToDPSummaryPage = (data) => {
      appState.mobStates.page = data && data.page;
      appState.mobStates.wardId = data && data.wardId;
      appState.mobStates.wardName = data && data.wardName;
      appState.currentDrillData = "mobilization";
      bus.emit("g-event-goto-page", data);
    };
    const sortedByWard = computed(() =>
      [].concat(tableData.value).sort((a, b) => a.ward.localeCompare(b.ward)),
    );

    onMounted(() => {
      bus.on("g-event-goto-page", gotoPageHandler);
      bus.on("g-event-refresh-page", refreshDataHandler);
    });
    onBeforeUnmount(() => {
      bus.off("g-event-goto-page", gotoPageHandler);
      bus.off("g-event-refresh-page", refreshDataHandler);
    });

    return Object.assign(
      { tableData, sortedByWard, goToDPSummaryPage },
      sharedExports(),
    );
  },
  template: `
        <div class="content-header row" id="basic-table" v-cloak>
            <div class="col-12 mb-1">
                <div class="card" style="height: 350px !important;">
                    <div class="table-responsive scrollBox">
                        <table class="table table-fixed border-top table-striped table-hover table-hover-animation">
                            <thead>
                                <tr><th colspan="2">Ward</th><th>HHs with Old Nets</th><th>Old Nets Available</th></tr>
                            </thead>
                            <tbody>
                                <tr v-for="g in sortedByWard" :key="g.wardid" @click="goToDPSummaryPage({wardId: g.wardid, wardName: g.ward, page: 'dp_summary'})">
                                    <td><i class="ti ti-circle-plus text-primary"></i></td>
                                    <td style="padding-left: .4rem !important;">{{ g.ward }}</td>
                                    <td>{{ formatNumber(g.total_household) }}</td>
                                    <td>{{ formatNumber(g.total_net) }}</td>
                                </tr>
                                <tr v-if="tableData.length == 0"><td colspan="4" class="text-center pt-2"><small>No Data</small></td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="mb-50"></div>
                </div>
            </div>
        </div>
    `,
};

const EolinDpMobTopSummary = {
  setup() {
    const tableData = ref([]);
    const refreshData = () => {
      overlay.show();
      Promise.all([
        fetchData("1153", "&wardId=" + appState.mobStates.wardId, (data) => {
          tableData.value = data;
        }),
      ]).finally(() => {
        overlay.hide();
      });
    };
    const gotoPageHandler = () => {
      if (
        appState.mobStates.page == "dp_summary" &&
        appState.currentDrillData == "mobilization"
      ) {
        refreshData();
      }
    };
    const refreshDataHandler = () => {
      if (appState.mobStates.page == "dp_summary") refreshData();
    };
    onMounted(() => {
      bus.on("g-event-goto-page", gotoPageHandler);
      bus.on("g-event-refresh-page", refreshDataHandler);
    });
    onBeforeUnmount(() => {
      bus.off("g-event-goto-page", gotoPageHandler);
      bus.off("g-event-refresh-page", refreshDataHandler);
    });
    return Object.assign({ tableData }, sharedExports());
  },
  template: `
        <div class="content-header row" id="basic-table" v-cloak>
            <div class="col-12 mb-1">
                <div class="card" style="height: 350px !important;">
                    <div class="table-responsive scrollBox">
                        <table class="table table-fixed border-top table-striped table-hover table-hover-animation">
                            <thead>
                                <tr><th>#</th><th style="padding-left: .4rem !important;">DP</th><th>HHs with Old Nets</th><th>Old Nets Available</th></tr>
                            </thead>
                            <tbody>
                                <tr v-for="(g, i) in tableData" :key="g.userid">
                                    <td>{{ i + 1 }}</td>
                                    <td style="padding-left: .4rem !important;">{{ g.dp }}</td>
                                    <td>{{ formatNumber(g.total_household) }}</td>
                                    <td>{{ formatNumber(g.total_net) }}</td>
                                </tr>
                                <tr v-if="tableData.length == 0"><td colspan="2" class="text-center pt-2"><small>No Data</small></td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="mb-50"></div>
                </div>
            </div>
        </div>
    `,
};

/* ------------------------------------------------------------------ */
/* Distribution stat tile + LGA / Ward / DP top summaries               */
/* ------------------------------------------------------------------ */
const EolinDistAllStat = {
  setup() {
    const allDistributionStatistics = ref([]);
    const refreshData = () => {
      overlay.show();
      Promise.all([
        fetchData("1180", "", (data) => {
          allDistributionStatistics.value = data;
        }),
      ]).finally(() => {
        overlay.hide();
      });
    };
    const refreshDataHandler = () => {
      refreshData();
    };

    const keyLabels = {
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
    const topStats = computed(() => {
      var keys = ["total_household", "total_net"];
      var obj = allDistributionStatistics.value[0] || {};
      return keys.map((key) => {
        var meta = keyLabels[key] || {};
        return {
          key: key,
          label: meta.label || key,
          icon: meta.icon || "info",
          colorClass: meta.colorClass || "text-dark",
          value: obj[key],
        };
      });
    });

    const progressTarget = (index) => {
      var data = appState.mobStatisticData[0] || {};
      return index === 0 ? data.total_household : data.total_net;
    };
    const showProgress = (index, value) => {
      return [0, 1].indexOf(index) !== -1 && !isNaN(value);
    };

    const goBack = (data) => {
      appState.distStates.page = data && data.page;
      appState.distStates.lgaId = (data && data.lgaId) || "";
      appState.distStates.lgaName = (data && data.lgaName) || "";
      appState.distStates.wardId = (data && data.wardId) || "";
      appState.distStates.wardName = (data && data.wardName) || "";
      bus.emit("g-event-goto-page", data);
    };

    onMounted(() => {
      bus.on("g-event-refresh-page", refreshDataHandler);
      refreshData();
    });
    onBeforeUnmount(() => {
      bus.off("g-event-refresh-page", refreshDataHandler);
    });

    return Object.assign(
      {
        allDistributionStatistics,
        topStats,
        progressTarget,
        showProgress,
        goBack,
      },
      sharedExports(),
    );
  },
  template: `
        <div class="content-header row">
            <div class="col-lg-6 col-sm-6 col-12" v-for="(g, i) in topStats" :key="g.key">
                <div class="card">
                    <div class="card-body pb-1">
                        <div class="d-flex justify-content-between">
                            <span>{{ g.label }}</span>
                            <div class="avatar bg-light-primary p-50 m-0 mt--1">
                                <div class="avatar-content"><i :data-feather="g.icon" class="font-medium-4"></i></div>
                            </div>
                        </div>

                        <div class="d-block pt-25">
                            <div class="role-heading">
                                <h4 class="fw-bolder">
                                    <template v-if="!isNaN(g.value)">{{ formatNumber(g.value) }}</template>
                                    <template v-else><span class="spinner-border text-secondary spinner-border-sm" role="status" aria-hidden="true"></span></template>
                                </h4>
                            </div>

                            <div v-if="showProgress(i, g.value)">
                                <div class="text-right pt-25">
                                    <small class="text-heading d-block text-right">{{ progressBarWidth(progressTarget(i), g.value) }}</small>
                                    <div class="d-flex font-small-1 align-items-right">
                                        <div class="progress w-100 me-3" style="height: 6px;">
                                            <div class="progress-bar"
                                                :class="progressBarStatus(progressTarget(i), g.value)"
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
                        <li class="breadcrumb-item" @click="goBack({page: '', lgaName: appState.distStates.lgaName, lgaId: appState.distStates.lgaId })" :class="appState.distStates.page=='' ? 'active' : ''" v-if="appState.distStates.page=='ward_summary' || appState.distStates.page=='dp_summary' || appState.distStates.page==''">LGA Summary</li>
                        <li class="breadcrumb-item" @click="goBack({page: 'ward_summary', lgaName: appState.distStates.lgaName, lgaId: appState.distStates.lgaId, wardId: appState.distStates.wardId })" :class="appState.distStates.page=='ward_summary' ? 'active' : ''" v-if="appState.distStates.page=='ward_summary' || appState.distStates.page=='dp_summary'">{{ appState.distStates.lgaName }} LGA</li>
                        <li class="breadcrumb-item" :class="appState.distStates.page == 'dp_summary' ? 'active' : ''" v-if="appState.distStates.page=='dp_summary'">{{ appState.distStates.wardName }} Ward</li>
                    </ol>
                </div>
            </div>
        </div>
    `,
};

const EolinLgaDistTopSummary = {
  setup() {
    const tableData = ref([]);
    const refreshData = () => {
      overlay.show();
      Promise.all([
        fetchData("1181", "", (data) => {
          tableData.value = data;
        }),
      ]).finally(() => {
        overlay.hide();
      });
    };
    const gotoPageHandler = () => {
      if (
        appState.distStates.page == "" &&
        (appState.currentDrillData == "distribution" ||
          appState.currentDrillData == "")
      ) {
        refreshData();
      }
    };
    const refreshDataHandler = () => {
      if (appState.distStates.page == "") refreshData();
    };
    const goToWardSummaryPage = (data) => {
      appState.distStates.page = data && data.page;
      appState.distStates.lgaId = data && data.lgaId;
      appState.distStates.lgaName = data && data.lgaName;
      appState.currentDrillData = "distribution";
      bus.emit("g-event-goto-page", data);
    };
    const sortedByLGA = computed(() =>
      [].concat(tableData.value).sort((a, b) => a.lga.localeCompare(b.lga)),
    );

    onMounted(() => {
      bus.on("g-event-goto-page", gotoPageHandler);
      bus.on("g-event-refresh-page", refreshDataHandler);
      refreshData();
    });
    onBeforeUnmount(() => {
      bus.off("g-event-goto-page", gotoPageHandler);
      bus.off("g-event-refresh-page", refreshDataHandler);
    });

    return Object.assign(
      { tableData, sortedByLGA, goToWardSummaryPage },
      sharedExports(),
    );
  },
  template: `
        <div class="content-header row" id="basic-table" v-cloak>
            <div class="col-12 mb-1">
                <div class="card" style="height: 350px !important;">
                    <div class="table-responsive scrollBox">
                        <table class="table table-fixed border-top table-striped table-hover table-hover-animation">
                            <thead>
                                <tr><th colspan="2">LGA</th><th>HHs that Returned old Nets</th><th>Returned old Nets</th></tr>
                            </thead>
                            <tbody>
                                <tr v-for="g in sortedByLGA" :key="g.lga" @click="goToWardSummaryPage({lgaId: g.lgaid, lgaName: g.lga, page: 'ward_summary'})">
                                    <td><i class="ti ti-circle-plus text-primary"></i></td>
                                    <td style="padding-left: .4rem !important;">{{ g.lga }}</td>
                                    <td>{{ formatNumber(g.total_household) }}</td>
                                    <td>{{ formatNumber(g.total_net) }}</td>
                                </tr>
                                <tr v-if="tableData.length == 0"><td colspan="4" class="text-center pt-2"><small>No Data</small></td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="mb-50"></div>
                </div>
            </div>
        </div>
    `,
};

const EolinWardDistTopSummary = {
  setup() {
    const tableData = ref([]);
    const refreshData = () => {
      overlay.show();
      Promise.all([
        fetchData("1182", "&lgaId=" + appState.distStates.lgaId, (data) => {
          tableData.value = data;
        }),
      ]).finally(() => {
        overlay.hide();
      });
    };
    const gotoPageHandler = () => {
      if (
        appState.distStates.page == "ward_summary" &&
        appState.currentDrillData == "distribution"
      ) {
        refreshData();
      }
    };
    const refreshDataHandler = () => {
      if (appState.distStates.page == "ward_summary") refreshData();
    };
    const goToDPSummaryPage = (data) => {
      appState.distStates.page = data && data.page;
      appState.distStates.wardId = data && data.wardId;
      appState.distStates.wardName = data && data.wardName;
      appState.currentDrillData = "distribution";
      bus.emit("g-event-goto-page", data);
    };
    const sortedByWard = computed(() =>
      [].concat(tableData.value).sort((a, b) => a.ward.localeCompare(b.ward)),
    );

    onMounted(() => {
      bus.on("g-event-goto-page", gotoPageHandler);
      bus.on("g-event-refresh-page", refreshDataHandler);
    });
    onBeforeUnmount(() => {
      bus.off("g-event-goto-page", gotoPageHandler);
      bus.off("g-event-refresh-page", refreshDataHandler);
    });

    return Object.assign(
      { tableData, sortedByWard, goToDPSummaryPage },
      sharedExports(),
    );
  },
  template: `
        <div class="content-header row" id="basic-table" v-cloak>
            <div class="col-12 mb-1">
                <div class="card" style="height: 350px !important;">
                    <div class="table-responsive scrollBox">
                        <table class="table table-fixed border-top table-striped table-hover table-hover-animation">
                            <thead>
                                <tr><th colspan="2">Ward</th><th>HHs that Returned old Nets</th><th>Returned old Nets</th></tr>
                            </thead>
                            <tbody>
                                <tr v-for="g in sortedByWard" :key="g.wardid" @click="goToDPSummaryPage({wardId: g.wardid, wardName: g.ward, page: 'dp_summary'})">
                                    <td><i class="ti ti-circle-plus text-primary"></i></td>
                                    <td style="padding-left: .4rem !important;">{{ g.ward }}</td>
                                    <td>{{ formatNumber(g.total_household) }}</td>
                                    <td>{{ formatNumber(g.total_net) }}</td>
                                </tr>
                                <tr v-if="tableData.length == 0"><td colspan="4" class="text-center pt-2"><small>No Data</small></td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="mb-50"></div>
                </div>
            </div>
        </div>
    `,
};

const EolinDpDistTopSummary = {
  setup() {
    const tableData = ref([]);
    const refreshData = () => {
      overlay.show();
      Promise.all([
        fetchData("1183", "&wardId=" + appState.distStates.wardId, (data) => {
          tableData.value = data;
        }),
      ]).finally(() => {
        overlay.hide();
      });
    };
    const gotoPageHandler = () => {
      if (
        appState.distStates.page == "dp_summary" &&
        appState.currentDrillData == "distribution"
      ) {
        refreshData();
      }
    };
    const refreshDataHandler = () => {
      if (appState.distStates.page == "dp_summary") refreshData();
    };
    onMounted(() => {
      bus.on("g-event-goto-page", gotoPageHandler);
      bus.on("g-event-refresh-page", refreshDataHandler);
    });
    onBeforeUnmount(() => {
      bus.off("g-event-goto-page", gotoPageHandler);
      bus.off("g-event-refresh-page", refreshDataHandler);
    });
    return Object.assign({ tableData }, sharedExports());
  },
  template: `
        <div class="content-header row" v-cloak>
            <div class="col-12 mb-1">
                <div class="card" style="height: 350px !important;">
                    <div class="table-responsive scrollBox">
                        <table class="table table-fixed border-top table-striped table-hover table-hover-animation">
                            <thead>
                                <tr><th>#</th><th style="padding-left: .4rem !important;">DP</th><th>HHs that Returned old Nets</th><th>Returned old Nets</th></tr>
                            </thead>
                            <tbody>
                                <tr v-for="(g, i) in tableData" :key="g.userid">
                                    <td>{{ i + 1 }}</td>
                                    <td style="padding-left: .4rem !important;">{{ g.dp }}</td>
                                    <td>{{ formatNumber(g.total_household) }}</td>
                                    <td>{{ formatNumber(g.total_net) }}</td>
                                </tr>
                                <tr v-if="tableData.length == 0"><td colspan="2" class="text-center pt-2"><small>No Data</small></td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="mb-50"></div>
                </div>
            </div>
        </div>
    `,
};

useApp({ template: `<div><page-body/></div>` })
  .component("page-body", PageBody)
  .component("eolin_mob_all_stat_component", EolinMobAllStat)
  .component("eolin_lga_mob_top_summary_component", EolinLgaMobTopSummary)
  .component("eolin_ward_mob_top_summary_component", EolinWardMobTopSummary)
  .component("eolin_dp_mob_top_summary_component", EolinDpMobTopSummary)
  .component("eolin_dist_all_stat_component", EolinDistAllStat)
  .component("eolin_lga_dist_top_summary_component", EolinLgaDistTopSummary)
  .component("eolin_ward_dist_top_summary_component", EolinWardDistTopSummary)
  .component("eolin_dp_dist_top_summary_component", EolinDpDistTopSummary)
  .mount("#app");
