/**
 * Netcard / Dashboard — Vue 3 Composition API in place.
 * Five components — page-body, eNetcard_all_stat_component (top stat
 * cards + breadcrumb), lga_top_level_summary, ward_top_level_summary,
 * hhm_top_level_summary. The drill state lives on
 * eNetcard_all_stat_component (page + newPageData) and is broadcast via
 * the bus events g-event-goto-page / g-event-refresh-page.
 */

const { ref, reactive, computed, onMounted, onBeforeUnmount } = Vue;
const { useApp, useFormat, bus, safeMessage } = window.utils;

/* Shared helpers ---------------------------------------------------- */
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
const fetchData = (qid, query, onSuccess) => {
  return axios
    .get(common.DataService + "?qid=" + qid + (query || ""))
    .then((response) => {
      onSuccess((response && response.data && response.data.data) || []);
    })
    .catch((error) => {
      console.error("Error fetching qid=" + qid + " data:", error);
    });
};
const sharedExports = () => {
  return {
    formatNumber: fmt.formatNumber,
    capitalize: fmt.capitalize,
    displayDate: fmt.displayDate,
    percentageUsed,
    progressBarWidth,
    progressBarStatus,
  };
};

/* page-body --------------------------------------------------------- */
const PageBody = {
  setup() {
    const page = ref("lga_summary");
    const gotoPageHandler = (data) => {
      page.value = data && data.page;
    };
    onMounted(() => {
      bus.on("g-event-goto-page", gotoPageHandler);
    });
    onBeforeUnmount(() => {
      bus.off("g-event-goto-page", gotoPageHandler);
    });
    return { page };
  },
  template: `
        <div>
            <div class="content-body">
                <eNetcard_all_stat_component/>
                <div v-show="page == 'lga_summary' || page == ''"><lga_top_level_summary/></div>
                <div v-show="page == 'ward_summary'"><ward_top_level_summary/></div>
                <div v-show="page == 'hhm_summary'"><hhm_top_level_summary/></div>
            </div>
        </div>
    `,
};

/* eNetcard_all_stat_component --------------------------------------- */
const EnetcardAllStat = {
  setup() {
    const page = ref("");
    const newPageData = reactive({
      lgaId: null,
      lgaName: null,
      wardId: null,
      wardName: null,
      hhmId: null,
      hhmName: null,
    });
    const allStatistics = ref([]);

    const refreshData = () => {
      overlay.show();
      Promise.all([
        fetchData("217", "", (data) => {
          allStatistics.value = data;
        }),
      ]).finally(() => {
        overlay.hide();
      });
    };
    const refreshAllData = () => {
      bus.emit("g-event-refresh-page", { page: page.value });
    };
    const gotoPageHandler = (data) => {
      page.value = data && data.page;
      newPageData.lgaId = data && data.lgaId;
      newPageData.lgaName = data && data.lgaName;
      newPageData.wardId = data && data.wardId;
      newPageData.wardName = data && data.wardName;
      newPageData.hhmId = data && data.hhmId;
      newPageData.hhmName = data && data.hhmName;
      overlay.show();
      setTimeout(() => {
        overlay.hide();
      }, 1000);
    };
    const refreshDataHandler = () => {
      refreshData();
    };

    const goBack = (data) => {
      page.value = data && data.page;
      newPageData.lgaId = data && data.lgaId;
      newPageData.lgaName = data && data.lgaName;
      newPageData.wardId = data && data.wardId;
      newPageData.wardName = data && data.wardName;
      bus.emit("g-event-goto-page", data);
      refreshAllData();
    };

    const keyLabels = {
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
    const buildStat = (key, obj) => {
      var meta = keyLabels[key] || {};
      return {
        key: key,
        label: meta.label || key,
        icon: meta.icon || "info",
        colorClass: meta.colorClass || "text-dark",
        value: obj[key],
      };
    };
    const topStats = computed(() => {
      var obj = allStatistics.value[0] || {};
      return ["total", "state", "lga", "ward"].map((k) => buildStat(k, obj));
    });
    const beneficiaryStat = computed(() => {
      var obj = allStatistics.value[0] || {};
      return buildStat("beneficiary", obj);
    });
    const mergedMobilizerStats = computed(() => {
      var obj = allStatistics.value[0] || {};
      return ["mobilizer_online", "mobilizer_pending", "mobilizer_wallet"].map(
        (k) => buildStat(k, obj),
      );
    });

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
      {
        page,
        newPageData,
        allStatistics,
        topStats,
        beneficiaryStat,
        mergedMobilizerStats,
        refreshAllData,
        goBack,
        refreshData,
      },
      sharedExports(),
    );
  },
  template: `
        <div class="content-header row" id="basic-statistics" v-cloak>
            <div class="content-header-left col-sm-8 col-md-9 col-8 mb-2">
                <div class="row breadcrumbs-top">
                    <div class="col-12">
                        <h2 class="content-header-title header-txt float-left mb-0">e-Netcard</h2>
                        <div class="breadcrumb-wrapper">
                            <ol class="breadcrumb"><li class="breadcrumb-item active">Dashboard</li></ol>
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

            <div class="col-lg-3 col-sm-6 col-12" v-for="(g, i) in topStats" :key="g.key">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <span>{{ g.label }}</span>
                            <div class="avatar bg-light-primary p-50 m-0 mt--1"><div class="avatar-content"><i :data-feather="g.icon" class="font-medium-4"></i></div></div>
                        </div>
                        <div class="d-block mt-50 pt-25">
                            <div class="role-heading">
                                <h4 class="fw-bolder">
                                    <span v-if="!isNaN(g.value)">{{ formatNumber(g.value) }}</span>
                                    <span v-else class="spinner-border text-secondary spinner-border-sm" role="status" aria-hidden="true"></span>
                                </h4>
                            </div>
                        </div>
                        <div v-if="i==0" class="text-right pt-1">
                            <small class="text-heading d-block text-right">{{ progressBarWidth(topStats[0].value, beneficiaryStat.value) }}</small>
                            <div class="d-flex font-small-1 align-items-center">
                                <div class="progress w-100 me-3" style="height: 8px;">
                                    <div class="progress-bar"
                                        :class="progressBarStatus(topStats[0].value, beneficiaryStat.value)"
                                        :style="{ width: progressBarWidth(topStats[0].value, beneficiaryStat.value) }"
                                        :aria-valuenow="parseFloat(progressBarWidth(topStats[0].value, beneficiaryStat.value))"
                                        aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </div>
                        </div>
                        <div v-else class="py-2 w-100"></div>
                    </div>
                </div>
            </div>

            <div class="col-sm-12 col-md-9 col-xl-9 col-12">
                <div class="card">
                    <div class="card-body card-widget-separator">
                        <div class="row">
                            <div class="col-sm-4 col-lg-4"
                                :class="{ 'border-end border-sm-end-0': index < mergedMobilizerStats.length - 1 }"
                                v-for="(stat, index) in mergedMobilizerStats" :key="stat.key">
                                <div>
                                    <div class="d-flex justify-content-between align-items-start"
                                        :class="{ 'card-widget-1 pb-sm-0': index < mergedMobilizerStats.length - 1 }">
                                        <span>{{ stat.label }}</span>
                                        <div class="avatar bg-light-primary p-50 m-0 mt--1"><div class="avatar-content"><i :data-feather="stat.icon" class="font-medium-4"></i></div></div>
                                    </div>
                                    <div class="d-block mt-50 pt-25">
                                        <div class="role-heading">
                                            <h4 class="fw-bolder">
                                                <span v-if="!isNaN(stat.value)">{{ formatNumber(stat.value) }}</span>
                                                <span v-else class="spinner-border text-secondary spinner-border-sm" role="status" aria-hidden="true"></span>
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

            <div class="col-lg-3 col-sm-12 col-md-3 col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <span>{{ capitalize(beneficiaryStat.label) }}</span>
                            <div class="avatar bg-light-primary p-50 m-0 mt--1"><div class="avatar-content"><i :data-feather="beneficiaryStat.icon" class="font-medium-4"></i></div></div>
                        </div>
                        <div class="d-block mt-50 pt-25">
                            <div class="role-heading">
                                <h4 class="fw-bolder">
                                    <span v-if="!isNaN(beneficiaryStat.value)">{{ formatNumber(beneficiaryStat.value) }}</span>
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
                        <li class="breadcrumb-item" @click="goBack({page: '', lgaName: newPageData.lgaName, lgaId: newPageData.lgaId })" :class="page == '' ? 'active' : ''" v-if="page == 'ward_summary' || page == 'hhm_summary' || page == ''">LGA Summary</li>
                        <li class="breadcrumb-item" @click="goBack({page: 'ward_summary', lgaName: newPageData.lgaName, lgaId: newPageData.lgaId })" :class="page == 'ward_summary' ? 'active' : ''" v-if="page == 'ward_summary' || page == 'hhm_summary'">{{ newPageData.lgaName }} LGA</li>
                        <li class="breadcrumb-item" :class="page == 'hhm_summary' ? 'active' : ''" v-if="page == 'hhm_summary'">{{ newPageData.wardName }} Ward</li>
                    </ol>
                </div>
            </div>
        </div>
    `,
};

/* lga_top_level_summary --------------------------------------------- */
const LgaTopLevelSummary = {
  setup() {
    const page = ref("");
    const newPageData = reactive({
      lgaId: null,
      lgaName: null,
      wardId: null,
      wardName: null,
      hhmId: null,
      hhmName: null,
    });
    const tableData = ref([]);

    const refreshData = () => {
      overlay.show();
      Promise.all([
        fetchData("218", "", (data) => {
          tableData.value = data;
        }),
      ]).finally(() => {
        overlay.hide();
      });
    };
    const refreshDataHandler = () => {
      if (page.value == "lga_summary" || page.value == "") refreshData();
    };
    const gotoPageHandler = (data) => {
      page.value = data && data.page;
      newPageData.lgaId = data && data.lgaId;
      newPageData.lgaName = data && data.lgaName;
      newPageData.wardId = data && data.wardId;
      newPageData.wardName = data && data.wardName;
      newPageData.hhmId = data && data.hhmId;
      newPageData.hhmName = data && data.hhmName;
      overlay.show();
      setTimeout(() => {
        overlay.hide();
      }, 1000);
    };
    const goToWardSummaryPage = (data) => {
      bus.emit("g-event-goto-page", data);
      bus.emit("g-event-refresh-page", data);
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
      {
        page,
        newPageData,
        tableData,
        sortedByLGA,
        goToWardSummaryPage,
      },
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
                                    <td><i class="ti ti-circle-plus text-primary"></i></td>
                                    <td style="padding-left: .4rem !important;">{{ g.lga }}</td>
                                    <td>{{ formatNumber(g.lga_total) }}</td>
                                    <td>{{ formatNumber(g.lga_balance) }}</td>
                                    <td>{{ formatNumber(g.ward) }}</td>
                                    <td>{{ formatNumber(g.mob_online) }}</td>
                                    <td>{{ formatNumber(g.mob_pending) }}</td>
                                    <td>{{ formatNumber(g.wallet) }}</td>
                                    <td>{{ formatNumber(g.beneficiary) }}</td>
                                    <td width="200px">
                                        <div class="progress" style="height: 4px;">
                                            <div :class="progressBarStatus(g.lga_total, g.beneficiary)" class="progress-bar" role="progressbar" :style="{ width: progressBarWidth(g.lga_total, g.beneficiary) }"></div>
                                        </div>
                                        <small class="text-heading">{{ progressBarWidth(g.lga_total, g.beneficiary) }}</small>
                                    </td>
                                </tr>
                                <tr v-if="tableData.length == 0"><td colspan="9" class="text-center pt-2"><small>No Data</small></td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="mb-50"></div>
                </div>
            </div>
        </div>
    `,
};

/* ward_top_level_summary -------------------------------------------- */
const WardTopLevelSummary = {
  setup() {
    const page = ref("");
    const newPageData = reactive({
      lgaId: null,
      lgaName: null,
      wardId: null,
      wardName: null,
      hhmId: null,
      hhmName: null,
    });
    const tableData = ref([]);

    const refreshData = () => {
      overlay.show();
      Promise.all([
        fetchData("219", "&lgaId=" + newPageData.lgaId, (data) => {
          tableData.value = data;
        }),
      ]).finally(() => {
        overlay.hide();
      });
    };
    const refreshDataHandler = () => {
      tableData.value = [];
      if (page.value == "ward_summary") refreshData();
    };
    const gotoPageHandler = (data) => {
      page.value = data && data.page;
      newPageData.lgaId = data && data.lgaId;
      newPageData.lgaName = data && data.lgaName;
      newPageData.wardId = data && data.wardId;
      newPageData.wardName = data && data.wardName;
      newPageData.hhmId = data && data.hhmId;
    };
    const goToHHMSummaryPage = (data) => {
      bus.emit("g-event-goto-page", data);
      bus.emit("g-event-refresh-page", data);
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
      {
        page,
        newPageData,
        tableData,
        sortedByWard,
        goToHHMSummaryPage,
      },
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
                                    <td><i class="ti ti-circle-plus text-primary"></i></td>
                                    <td style="padding-left: .4rem !important;">{{ g.ward }}</td>
                                    <td>{{ formatNumber(g.ward_total) }}</td>
                                    <td>{{ formatNumber(g.ward_balance) }}</td>
                                    <td>{{ formatNumber(g.mob_online) }}</td>
                                    <td>{{ formatNumber(g.mob_pending) }}</td>
                                    <td>{{ formatNumber(g.wallet) }}</td>
                                    <td>{{ formatNumber(g.beneficiary) }}</td>
                                    <td width="200px">
                                        <div class="progress" style="height: 4px;">
                                            <div :class="progressBarStatus(g.ward_total, g.beneficiary)" class="progress-bar" role="progressbar" :style="{ width: progressBarWidth(g.ward_total, g.beneficiary) }"></div>
                                        </div>
                                        <small class="text-heading">{{ progressBarWidth(g.ward_total, g.beneficiary) }}</small>
                                    </td>
                                </tr>
                                <tr v-if="tableData.length == 0"><td colspan="9" class="text-center pt-2"><small>No Data</small></td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="mb-50"></div>
                </div>
            </div>
        </div>
    `,
};

/* hhm_top_level_summary --------------------------------------------- */
const HhmTopLevelSummary = {
  setup() {
    const page = ref("");
    const newPageData = reactive({
      lgaId: null,
      lgaName: null,
      wardId: null,
      wardName: null,
      hhmId: null,
      hhmName: null,
    });
    const tableData = ref([]);

    const refreshData = () => {
      overlay.show();
      Promise.all([
        fetchData("220", "&wardId=" + newPageData.wardId, (data) => {
          tableData.value = data;
        }),
      ]).finally(() => {
        overlay.hide();
      });
    };
    const refreshDataHandler = () => {
      tableData.value = [];
      if (page.value == "hhm_summary") refreshData();
    };
    const gotoPageHandler = (data) => {
      page.value = data && data.page;
      newPageData.lgaId = data && data.lgaId;
      newPageData.lgaName = data && data.lgaName;
      newPageData.wardId = data && data.wardId;
      newPageData.wardName = data && data.wardName;
      newPageData.hhmId = data && data.hhmId;
    };

    onMounted(() => {
      bus.on("g-event-goto-page", gotoPageHandler);
      bus.on("g-event-refresh-page", refreshDataHandler);
    });
    onBeforeUnmount(() => {
      bus.off("g-event-goto-page", gotoPageHandler);
      bus.off("g-event-refresh-page", refreshDataHandler);
    });

    return Object.assign({ page, newPageData, tableData }, sharedExports());
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
                                    <td>{{ i + 1 }}</td>
                                    <td style="padding-left: .4rem !important;">{{ g.mobilizer }}</td>
                                    <td>{{ formatNumber(g.total) }}</td>
                                    <td>{{ formatNumber(g.mob_online) }}</td>
                                    <td>{{ formatNumber(g.mob_pending) }}</td>
                                    <td>{{ formatNumber(g.wallet) }}</td>
                                    <td>{{ formatNumber(g.beneficiary) }}</td>
                                    <td width="200px">
                                        <div class="progress" style="height: 4px;">
                                            <div :class="progressBarStatus(g.total, g.beneficiary)" class="progress-bar" role="progressbar" :style="{ width: progressBarWidth(g.total, g.beneficiary) }"></div>
                                        </div>
                                        <small class="text-heading">{{ progressBarWidth(g.total, g.beneficiary) }}</small>
                                    </td>
                                </tr>
                                <tr v-if="tableData.length == 0"><td colspan="8" class="text-center pt-2"><small>No Data</small></td></tr>
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
  .component("eNetcard_all_stat_component", EnetcardAllStat)
  .component("lga_top_level_summary", LgaTopLevelSummary)
  .component("ward_top_level_summary", WardTopLevelSummary)
  .component("hhm_top_level_summary", HhmTopLevelSummary)
  .mount("#app");
