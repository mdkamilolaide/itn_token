/**
 * Monitoring module — Vue 3 Composition API.
 * Converted in place from Vue 2 Options API. Same template, same DOM,
 * same API endpoints (qid=700 list, qid=701..707 per-tool downloads).
 *
 */

const { ref, reactive, computed, onMounted } = Vue;
const {
  bus,
  fmt,
  displayDate,
  capitalize,
  capitalizeEachWords,
  formatNumber,
  checkIfEmpty,
  convertStringNumberToFigures,
  numbersOnlyWithoutDot,
  validatePaste,
  useFormat,
  useApp,
  dataQuery,
  exportPost,
  safeMessage,
} = window.utils;

/* ------------------------------------------------------------------ */
/* page-body — thin shell that mounts <monitoring_lists/>              */
/* ------------------------------------------------------------------ */
const PageBody = {
  setup() {
    const page = ref("dashboard");
    return { page };
  },
  template: `
        <div>
            <div class="content-body">
                <monitoring_lists/>
            </div>
        </div>
    `,
};

/* ------------------------------------------------------------------ */
/* monitoring_lists — table + search + per-tool Excel download         */
/* ------------------------------------------------------------------ */
const MonitoringLists = {
  setup() {
    const fmtUtils = useFormat();

    // Reactive state (same shape as the original data())
    const tableData = ref([]);
    const checkToggle = ref(false);
    const filterState = ref(false);
    const filters = ref(false);
    const geoIndicator = reactive({
      state: 50,
      currentLevelId: 0,
      lga: "",
      ward: "",
    });
    const geoLevelData = ref([]);
    const sysDefaultData = ref([]);
    const lgaLevelData = ref([]);
    const wardLevelData = ref([]);
    const bulkUserForm = reactive({
      geoLevel: "",
      geoLevelId: 0,
      mobilizationDate: "",
    });

    // Search now drives a computed instead of jQuery DOM filtering.
    const filteredData = computed(() => {
      var q = String(bulkUserForm.mobilizationDate || "")
        .toLowerCase()
        .trim();
      if (!q) return tableData.value;
      return tableData.value.filter((row) =>
        Object.values(row || {}).some(
          (v) => String(v).toLowerCase().indexOf(q) !== -1,
        ),
      );
    });

    const getMonitoringToolsList = () => {
      overlay.show();
      dataQuery("700")
        .then((response) => {
          if (
            response.data &&
            response.data.data &&
            response.data.data.length > 0
          ) {
            tableData.value = response.data.data;
          } else {
            tableData.value = [];
          }
          overlay.hide();
        })
        .catch((error) => {
          overlay.hide();
          alert.Error("ERROR", safeMessage(error));
        });
    };

    const downloadReport = (i, total) => {
      const today = window.utils.formatTimestampForFilename();
      const map = {
        1: {
          fileName: "i-9a_Forms_" + total + "_" + today,
          dlString: "qid=701",
        },
        2: {
          fileName: "i-9b_Forms_" + total + "_" + today,
          dlString: "qid=702",
        },
        3: {
          fileName: "i-9c_Forms_" + total + "_" + today,
          dlString: "qid=703",
        },
        4: {
          fileName: "5_Percent_Revisit_Forms_" + total + "_" + today,
          dlString: "qid=704",
        },
        5: {
          fileName: "End_Process_Forms_" + total + "_" + today,
          dlString: "qid=705",
        },
        7: {
          fileName: "SMC_Supervisory_CDD_Forms_" + total + "_" + today,
          dlString: "qid=706",
        },
        8: {
          fileName: "SMC_Supervisory_HFW_Forms_" + total + "_" + today,
          dlString: "qid=707",
        },
      };
      const cfg = map[i] || map[5];

      overlay.show();
      const downloadMax =
        (window.common && window.common.ExportDownloadLimit) || 25000;
      const totalNum = parseInt(total, 10) || 0;

      if (totalNum > downloadMax) {
        alert.Error(
          "Download Error",
          "Unable to download data because it has exceeded the download limit. The download limit is " +
            downloadMax +
            ".",
        );
        overlay.hide();
        return;
      }
      if (totalNum === 0) {
        alert.Error("Download Error", "No data found");
        overlay.hide();
        return;
      }

      alert.Info("DOWNLOADING...", "Downloading " + total + " record(s)");

      downloadData(cfg.dlString)
        .then((outcome) => {
          var exportData =
            typeof outcome === "string" ? JSON.parse(outcome) : outcome;
          if (window.Jhxlsx && typeof window.Jhxlsx.export === "function") {
            window.Jhxlsx.export(exportData, { fileName: cfg.fileName });
          } else {
            alert.Error("Download Error", "Excel exporter (Jhxlsx) not loaded");
          }
        })
        .catch((error) => {
          console.error(error);
          alert.Error("Download Error", safeMessage(error));
        })
        .finally(() => {
          overlay.hide();
        });
    };

    const downloadData = (dlString) => {
      return new Promise((resolve, reject) => {
        $.ajax({
          url: common.ExportService,
          type: "POST",
          data: dlString,
          success: (data) => {
            resolve(data);
          },
          error: (error) => {
            reject(error);
          },
        });
      });
    };

    // Selection helpers (preserved for parity with v2; not currently used in the template)
    const selectAll = () => {
      if (tableData.value.length > 0) {
        for (var i = 0; i < tableData.value.length; i++) {
          tableData.value[i].pick = true;
        }
      }
    };
    const uncheckAll = () => {
      if (tableData.value.length > 0) {
        for (var i = 0; i < tableData.value.length; i++) {
          tableData.value[i].pick = false;
        }
      }
    };
    const selectToggle = () => {
      if (checkToggle.value === false) {
        selectAll();
        checkToggle.value = true;
      } else {
        uncheckAll();
        checkToggle.value = false;
      }
    };
    const checkedBg = (pickOne) => {
      return pickOne != "" ? "bg-select" : "";
    };
    const toggleFilter = () => {
      if (filterState.value === false) filters.value = false;
      return (filterState.value = !filterState.value);
    };
    const selectedItems = () => {
      return tableData.value.filter((r) => r.pick);
    };
    const selectedID = () => {
      return tableData.value.filter((r) => r.pick).map((r) => r.dpid);
    };

    const loadDp = () => {
      // Preserved hook — original template's "Load" button calls this.
      // The search/filter is now reactive via the computed above; reload simply re-fetches.
      getMonitoringToolsList();
    };

    onMounted(() => {
      getMonitoringToolsList();
    });

    return {
      // state
      tableData,
      filteredData,
      checkToggle,
      geoIndicator,
      geoLevelData,
      sysDefaultData,
      lgaLevelData,
      wardLevelData,
      bulkUserForm,
      // methods
      getMonitoringToolsList,
      downloadReport,
      loadDp,
      selectAll,
      uncheckAll,
      selectToggle,
      checkedBg,
      toggleFilter,
      selectedItems,
      selectedID,
      // utility methods (returned so templates work unchanged)
      displayDate: fmtUtils.displayDate,
      capitalize: fmtUtils.capitalize,
      capitalizeEachWords: fmtUtils.capitalizeEachWords,
      formatNumber: fmtUtils.formatNumber,
      convertStringNumberToFigures: fmtUtils.convertStringNumberToFigures,
      fmt: fmtUtils.fmt,
      checkIfEmpty: fmtUtils.checkIfEmpty,
      numbersOnlyWithoutDot: fmtUtils.numbersOnlyWithoutDot,
      validatePaste: fmtUtils.validatePaste,
    };
  },
  template: `
        <div>
            <div class="row">
                <div class="col-md-12 col-sm-12 col-12 mb-2">
                    <h2 class="content-header-title header-txt float-left mb-0">Monitoring Tool</h2>
                    <div class="breadcrumb-wrapper">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="./">Home</a></li>
                            <li class="breadcrumb-item active">Monitoring Tools</li>
                        </ol>
                    </div>
                </div>

                <div class="col-12">
                    <div class="alert bg-light-primary pt-1 pb-1">
                        <div class="col-12">
                            <div class="row">
                                <div class="col-12 ">
                                    <div class="input-group date_filter">
                                        <input type="text" id="form-search" v-model="bulkUserForm.mobilizationDate" class="form-control date" placeholder="Search using Monitoring tool Name" />
                                        <div class="input-group-append">
                                            <button class="btn btn-primary pl-1 pr-1" @click="loadDp()" type="button">Load</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="table-responsive">
                    <table class="table" id="dpTable">
                        <thead class="bg-light-primary">
                            <tr>
                                <th width="60px">#</th>
                                <th>Monitoring Tools Name</th>
                                <th>Total Form Filled</th>
                                <th width="60px" class="text-center">
                                    <a href="javascript:void(0)" data-toggle="tooltip" data-placement="top" title="Download All" class="btn btn-primary btn-sm p-50"><i class="feather icon-download-cloud"></i></a>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(g, i) in filteredData" :key="g.sn || i">
                                <td>{{ i + 1 }}</td>
                                <td>{{ g.name }}</td>
                                <td>{{ formatNumber(g.total) }}</td>
                                <td class="text-center">
                                    <a href="javascript:void(0)" class="btn btn-sm btn-primary btn-sm p-25" @click="downloadReport(g.sn, g.total)"><i class="feather icon-download"></i></a>
                                </td>
                            </tr>
                            <tr v-if="filteredData.length === 0">
                                <td class="text-center pt-2" colspan="4"><small>{{ bulkUserForm.mobilizationDate ? 'No matching monitoring tools' : 'No Monitoring Tools Added' }}</small></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    `,
};

/* ------------------------------------------------------------------ */
/* Mount                                                              */
/* ------------------------------------------------------------------ */
useApp({
  template: `<div><page-body/></div>`,
})
  .component("page-body", PageBody)
  .component("monitoring_lists", MonitoringLists)
  .mount("#app");
