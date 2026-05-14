/**
 * SMC / ICC (Inventory Control) — Vue 3 Composition API in place.
 * Two components — page-body and icc_list.
 *
 * qid=706 paginated ICC ledger with multi-select periods + geo filter.
 * Per-row CDD details modal via qid=1127. Excel export count via
 * qid=1126, dump via qid=803. Sticky-column offsets recomputed on
 * mount/update.
 */

const { ref, reactive, nextTick, onMounted, onBeforeUnmount, onUpdated } = Vue;
const { useApp, useFormat, bus, safeMessage } = window.utils;

const PageBody = {
  setup() {
    const page = ref("list");
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
                <div v-show="page == 'list'"><icc_list/></div>
            </div>
        </div>
    `,
};

const IccList = {
  setup() {
    const fmtUtils = useFormat();

    const url = ref(window.common && window.common.DataService);
    const permission = ref(
      typeof getPermission === "function"
        ? getPermission(typeof per !== "undefined" ? per : null, "smc") || {
            permission_value: 0,
          }
        : { permission_value: 0 },
    );
    const tableData = ref([]);
    const selectedICCDetails = ref({});
    const iccIssuedReconcileDetails = ref([]);
    const geoData = ref([]);
    const periodData = ref([]);
    const checkIfFilterOn = ref(false);
    const filterState = ref(false);
    const filters = ref(false);
    const tableOptions = reactive({
      total: 1,
      pageLength: 1,
      perPage: 10,
      currentPage: 1,
      orderDir: "desc",
      orderField: 0,
      limitStart: 0,
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
    });

    const joinWithCommaAnd = (array, status) => {
      if (!array || array.length === 0) return "";
      if (array.length === 1) return array[0];
      var copy = array.slice();
      var lastElement = copy.pop();
      return status
        ? copy.join(",") + "," + lastElement
        : copy.join(", ") + " and " + lastElement;
    };

    const setStickyOffsets = () => {
      var table = document.getElementById("fixed-table");
      if (!table) return;
      var th1 = table.querySelector("th.col-1");
      var th2 = table.querySelector("th.col-2");
      var col1Width = (th1 && th1.offsetWidth) || 0;
      var col2Width = (th2 && th2.offsetWidth) || 0;
      var col2Left = col1Width;
      var col3Left = col1Width + col2Width;
      table.querySelectorAll(".col-2").forEach((el) => {
        el.style.left = col2Left + "px";
      });
      table.querySelectorAll(".col-3").forEach((el) => {
        el.style.left = col3Left + "px";
      });
    };

    const loadTableData = async () => {
      overlay.show();
      var fp = tableOptions.filterParam;
      fp.globalPeriod = joinWithCommaAnd(fp.periodid, true);
      var endpoint =
        common.TableService +
        "?qid=706&draw=" +
        tableOptions.currentPage +
        "&order_column=" +
        tableOptions.orderField +
        "&length=" +
        tableOptions.perPage +
        "&start=" +
        tableOptions.limitStart +
        "&order_dir=" +
        tableOptions.orderDir +
        "&pid=" +
        fp.globalPeriod +
        "&gid=" +
        fp.geo_level_id +
        "&glv=" +
        fp.geo_level;
      try {
        var response = await axios.get(endpoint);
        var d = response && response.data;
        tableData.value = Array.isArray(d && d.data) ? d.data : [];
        tableOptions.total = (d && d.recordsTotal) || 0;
        if (tableOptions.currentPage === 1) paginationDefault();
      } catch (error) {
        alert.Error("ERROR", safeMessage(error));
      } finally {
        overlay.hide();
      }
    };
    const toggleFilter = () => {
      if (!filterState.value && !checkIfFilterOn.value) filters.value = false;
      return (filterState.value = !filterState.value);
    };
    const paginationDefault = () => {
      tableOptions.pageLength = Math.ceil(
        tableOptions.total / tableOptions.perPage,
      );
      tableOptions.limitStart = Math.ceil(
        (tableOptions.currentPage - 1) * tableOptions.perPage,
      );
      tableOptions.isNext = tableOptions.currentPage < tableOptions.pageLength;
      tableOptions.isPrev = tableOptions.currentPage > 1;
    };
    const nextPage = () => {
      tableOptions.currentPage += 1;
      paginationDefault();
      loadTableData();
    };
    const prevPage = () => {
      tableOptions.currentPage -= 1;
      paginationDefault();
      loadTableData();
    };
    const currentPage = () => {
      paginationDefault();
      if (tableOptions.currentPage < 1)
        alert.Error("ERROR", "The Page requested doesn't exist");
      else if (tableOptions.currentPage > tableOptions.pageLength)
        alert.Error("ERROR", "The Page requested doesn't exist");
      else loadTableData();
    };
    const changePerPage = (val) => {
      var maxPerPage = Math.ceil(tableOptions.total / val);
      if (maxPerPage < tableOptions.currentPage)
        tableOptions.currentPage = maxPerPage;
      tableOptions.perPage = val;
      paginationDefault();
      loadTableData();
    };
    const sort = (col) => {
      if (tableOptions.orderField === col)
        tableOptions.orderDir =
          tableOptions.orderDir === "asc" ? "desc" : "asc";
      else tableOptions.orderField = col;
      paginationDefault();
      loadTableData();
    };
    const applyFilter = () => {
      var checkFill = 0;
      if (tableOptions.filterParam.geo_level != "") checkFill++;
      if (tableOptions.filterParam.geo_level_id != "") checkFill++;
      if ((tableOptions.filterParam.periodid || []).length > 0) checkFill++;
      if (checkFill > 0) {
        toggleFilter();
        filters.value = checkIfFilterOn.value = true;
        paginationDefault();
        loadTableData();
      } else {
        alert.Error("ERROR", "Invalid required data");
      }
    };
    const removeSingleFilter = (column_name) => {
      var fp = tableOptions.filterParam;
      if (Array.isArray(fp[column_name])) fp[column_name] = [];
      else fp[column_name] = "";
      if (
        ["geo_level", "geo_level_id", "geo_string"].indexOf(column_name) !== -1
      ) {
        fp.geo_level = "";
        fp.geo_level_id = "";
        try {
          $(".select2").val("").trigger("change");
        } catch (e) {}
      }
      if (column_name === "visitTitle") {
        fp.periodid = [];
        fp.visitTitle = "";
        fp.globalPeriod = "";
        try {
          $(".period").val("").trigger("change");
        } catch (e) {}
      }
      var hasActive = Object.values(fp).some((v) =>
        Array.isArray(v) ? v.length > 0 : v !== "",
      );
      filters.value = checkIfFilterOn.value = hasActive;
      paginationDefault();
      loadTableData();
    };
    const clearAllFilter = () => {
      filters.value = false;
      Object.assign(tableOptions.filterParam, {
        geo_level: "",
        geo_level_id: "",
        geo_string: "",
        periodid: [],
        visitTitle: "",
        globalPeriod: "",
      });
      try {
        $(".select2").val("").trigger("change");
      } catch (e) {}
      try {
        $(".period").val("").trigger("change");
      } catch (e) {}
      paginationDefault();
      loadTableData();
    };
    const checkAndHideFilter = (name) => {
      return (
        ["periodid", "geo_level_id", "geo_level", "globalPeriod"].indexOf(
          name,
        ) === -1
      );
    };
    const GetIccFlowDetailByCdd = async (cddid, id) => {
      overlay.show();
      selectedICCDetails.value = tableData.value[id] || {};
      var periodIds = tableOptions.filterParam.globalPeriod;
      try {
        var response = await axios.get(
          url.value + "?qid=1127&cddid=" + cddid + "&pid=" + periodIds,
        );
        if (response.data.result_code == 200) {
          $("#iccDetailsModal").modal("show");
          iccIssuedReconcileDetails.value = response.data.data || [];
        } else {
          iccIssuedReconcileDetails.value = [];
          alert.Error("ERROR", response.data.message);
        }
      } catch (error) {
        alert.Error("ERROR", safeMessage(error));
      } finally {
        overlay.hide();
      }
    };
    const hideGetIccFlowDetailByCdd = () => {
      overlay.show();
      selectedICCDetails.value = {};
      $("#iccDetailsModal").modal("hide");
      iccIssuedReconcileDetails.value = [];
      overlay.hide();
    };
    const checkIfEmpty = (data) => {
      return data === null || data === "" ? "Nil" : data;
    };
    const refreshData = () => {
      paginationDefault();
      loadTableData();
    };
    const getGeoLocation = () => {
      overlay.show();
      axios
        .get(common.DataService + "?qid=gen009")
        .then((response) => {
          geoData.value = (response.data && response.data.data) || [];
          overlay.hide();
        })
        .catch((error) => {
          overlay.hide();
          alert.Error("ERROR", safeMessage(error));
        });
    };
    const getAllPeriodLists = () => {
      overlay.show();
      axios
        .get(common.DataService + "?qid=1004")
        .then((response) => {
          periodData.value = (response.data && response.data.data) || [];
          overlay.hide();
        })
        .catch((error) => {
          overlay.hide();
          alert.Error("ERROR", safeMessage(error));
        });
    };
    const setLocation = (select_index) => {
      var i = select_index || 0;
      var row = geoData.value[i];
      if (!row) return;
      tableOptions.filterParam.geo_level = row.geo_level;
      tableOptions.filterParam.geo_level_id = row.geo_level_id;
      tableOptions.filterParam.geo_string = row.title;
    };
    const setPeriodTitle = (event) => {
      var selected = Array.isArray(event) ? event : [];
      tableOptions.filterParam.periodid = [];
      var titles = [];
      selected.forEach((id) => {
        tableOptions.filterParam.periodid.push(id);
        var period = (periodData.value || []).find((p) => p.periodid == id);
        if (period) titles.push(period.title);
      });
      tableOptions.filterParam.visitTitle = joinWithCommaAnd(titles);
    };
    const splitWordAndCapitalize = (str) => {
      var words = String(str || "").split(/(?=[A-Z])|_| /);
      return words
        .map((w) => w.charAt(0).toUpperCase() + w.slice(1).toLowerCase())
        .join(" ");
    };
    const displayDayMonthYearTime = (d) => {
      if (!d) return "";
      var date = new Date(d);
      return date.toLocaleString("en-us", {
        year: "numeric",
        month: "long",
        day: "numeric",
        hour12: true,
        hour: "2-digit",
        minute: "2-digit",
      });
    };
    const convertStringNumberToFigures = (d) => {
      var data = d ? parseInt(d) : 0;
      return data ? data.toLocaleString() : 0;
    };
    const exportIcc = async () => {
      var fp = tableOptions.filterParam;
      var periodIds = joinWithCommaAnd(fp.periodid, true);
      fp.globalPeriod = periodIds;
      var qs =
        "&draw=" +
        tableOptions.currentPage +
        "&order_column=" +
        tableOptions.orderField +
        "&length=" +
        tableOptions.perPage +
        "&start=" +
        tableOptions.limitStart +
        "&order_dir=" +
        tableOptions.orderDir +
        "&pid=" +
        periodIds +
        "&gid=" +
        fp.geo_level_id +
        "&glv=" +
        fp.geo_level;
      var veriUrl = "qid=1126" + qs;
      var dlString = "qid=803" + qs;
      var formattedDate = new Date()
        .toLocaleString("en-GB", {
          day: "2-digit",
          month: "2-digit",
          year: "numeric",
          hour: "2-digit",
          minute: "2-digit",
        })
        .replace(/[\s,\/:]/g, "_");
      var filename =
        fp.geo_level + "_" + fp.globalPeriod + "_ICC_Export_" + formattedDate;
      overlay.show();
      try {
        var countResponse = await $.ajax({
          url: common.DataService,
          type: "POST",
          data: veriUrl,
          dataType: "json",
        });
        var count = parseInt(countResponse.total, 10);
        var downloadMax =
          (window.common && window.common.ExportDownloadLimit) || 25000;
        if (count > downloadMax) {
          alert.Error(
            "Download Error",
            "Unable to download data because it has exceeded the download limit of " +
              downloadMax,
          );
        } else if (count === 0) {
          alert.Error("Download Error", "No data found");
        } else {
          alert.Info("DOWNLOADING...", "Downloading " + count + " record(s)");
          var dl = await $.ajax({
            url: common.ExportService,
            type: "POST",
            data: dlString,
          });
          var exportData = JSON.parse(dl);
          if (window.Jhxlsx && typeof window.Jhxlsx.export === "function") {
            window.Jhxlsx.export(exportData, { fileName: filename });
          }
        }
      } catch (error) {
        console.error("Error during export:", error);
        alert.Error("Export Error", "An error occurred while exporting data.");
      } finally {
        overlay.hide();
      }
    };

    onMounted(() => {
      getGeoLocation();
      getAllPeriodLists();
      loadTableData();
      try {
        $(".select2").each(function () {
          var $this = $(this);
          $this.wrap('<div class="position-relative"></div>');
          $this
            .select2({
              dropdownAutoWidth: true,
              width: "100%",
              dropdownParent: $this.parent(),
            })
            .on("change", function () {
              setLocation(this.value);
            });
        });
        $(".period").each(function () {
          var $this = $(this);
          $this.wrap('<div class="position-relative"></div>');
          $this
            .select2({
              multiple: true,
              dropdownAutoWidth: true,
              width: "100%",
              dropdownParent: $this.parent(),
              placeholder: "Select Visits",
            })
            .on("change", function () {
              setPeriodTitle($(this).val());
            });
        });
        $(".select2-selection__arrow").html(
          '<i class="feather icon-chevron-down"></i>',
        );
      } catch (e) {}
      nextTick(setStickyOffsets);
    });
    onUpdated(() => {
      nextTick(setStickyOffsets);
    });

    return {
      url,
      permission,
      tableData,
      selectedICCDetails,
      iccIssuedReconcileDetails,
      geoData,
      periodData,
      checkIfFilterOn,
      filterState,
      filters,
      tableOptions,
      setStickyOffsets,
      loadTableData,
      toggleFilter,
      paginationDefault,
      nextPage,
      prevPage,
      currentPage,
      changePerPage,
      sort,
      applyFilter,
      removeSingleFilter,
      clearAllFilter,
      checkAndHideFilter,
      GetIccFlowDetailByCdd,
      hideGetIccFlowDetailByCdd,
      checkIfEmpty,
      refreshData,
      getGeoLocation,
      getAllPeriodLists,
      setLocation,
      setPeriodTitle,
      splitWordAndCapitalize,
      displayDayMonthYearTime,
      convertStringNumberToFigures,
      exportIcc,
      capitalize: fmtUtils.capitalize,
      displayDate: fmtUtils.displayDate,
    };
  },
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
                    <button type="button" class="btn btn-outline-primary round searchBtn" @click="refreshData()" data-toggle="tooltip" data-placement="top" title="Refresh"><i class="feather icon-refresh-cw"></i></button>
                    <button type="button" class="btn btn-outline-primary round searchBtn" @click="toggleFilter()" data-toggle="tooltip" data-placement="top" title="Filter"><i class="feather" :class="filterState ? 'icon-x' : 'icon-filter'"></i></button>
                    <button v-if="permission.permission_value >= 2" type="button" class="btn btn-outline-primary round" data-toggle="tooltip" data-placement="top" title="Download" @click="exportIcc()"><i class="feather icon-download"></i></button>
                </div>
            </div>

            <div class="col-12" v-if="filters">
                <div class="col-12 filter-bar">
                    <template v-for="(filterParam, i) in tableOptions.filterParam" :key="i">
                        <span class="badge badge-dark filter-box" v-if="(Array.isArray(filterParam) ? filterParam.length : String(filterParam).length) > 0 && checkAndHideFilter(i)" @click="removeSingleFilter(i)">{{ splitWordAndCapitalize(i) }}: {{ filterParam }} <i class="feather icon-x"></i></span>
                    </template>
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
                                        <select @change="setPeriodTitle($event && $event.target ? Array.from($event.target.selectedOptions).map(o => o.value) : [])" v-model="tableOptions.filterParam.periodid" multiple class="form-control period">
                                            <option v-for="(g, i) in periodData" :key="g.periodid" :value="g.periodid">{{ g.title }}</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-12 col-sm-6 col-md-5 col-lg-5">
                                    <div class="form-group">
                                        <label>Geo Location</label>
                                        <select class="select2 form-control" @change="setLocation()">
                                            <option value="">Select Geo Location</option>
                                            <option v-for="(g, i) in geoData" :key="i" :value="i">{{ g.geo_string }}</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-12 col-sm-12 col-md-3 col-lg-2"><div class="form-group mt-2 text-right"><button type="button" class="btn btn-md btn-primary" @click="applyFilter()">Apply Filters</button></div></div>
                            </div>
                        </form>
                    </div>

                    <div class="table-responsive" id="icc_long">
                        <table class="table table-hover" id="fixed-table">
                            <thead>
                                <tr>
                                    <th @click="sort(0)" class="pl-1 pr-2">#</th>
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
                            </thead>
                            <tbody>
                                <tr v-for="(g, i) in tableData" :key="g.issue_id || i">
                                    <td class="pl-1 pr-2">{{ g.issue_id }}</td>
                                    <td class="pl-1 pr-1 sticky-col col-1">
                                        <div class="d-flex flex-column">
                                            <small><span class="text-muted w-text">From:</span> <span class="fw-bolder">{{ checkIfEmpty(g.issuer) }} ({{ g.issuer_loginid }})</span></small>
                                            <small><span class="text-muted w-text">To:</span> <span class="fw-bolder">{{ checkIfEmpty(g.cdd_lead) }} ({{ g.cdd_loginid }})</span></small>
                                        </div>
                                    </td>
                                    <td class="pl-1 pr-1 sticky-col col-2"><div class="d-flex flex-column">{{ capitalize(g.geo_string) }}</div></td>
                                    <td class="pl-1 pr-1 sticky-col col-3">{{ g.issue_drug }}</td>
                                    <td class="pl-1 pr-1">{{ convertStringNumberToFigures(g.drug_qty) }}</td>
                                    <td class="pl-1 pr-1">
                                        <div class="d-flex flex-column">
                                            <small v-if="g.status"><span class="fw-bolder">{{ g.status }}</span></small>
                                            <small><span class="fw-bolder">{{ displayDayMonthYearTime(g.issue_date) }}</span></small>
                                        </div>
                                    </td>
                                    <td class="pl-1 pr-1">
                                        <div class="d-flex flex-column">
                                            <small v-if="g.downloaded"><span class="fw-bolder">{{ g.downloaded }}</span></small>
                                            <small><span class="fw-bolder">{{ displayDayMonthYearTime(g.download_confirm_date) }}</span></small>
                                        </div>
                                    </td>
                                    <td class="pl-1 pr-1">
                                        <div class="d-flex flex-column">
                                            <small><span class="fw-bolder">{{ g.is_rejected }}</span></small>
                                            <small v-if="g.is_rejected"><span class="fw-bolder">{{ g.rejection_note }}</span></small>
                                        </div>
                                    </td>
                                    <td class="pl-1 pr-1">
                                        <div class="d-flex flex-column">
                                            <small><span class="fw-bolder">{{ g.is_accepted }}</span></small>
                                            <small><span class="fw-bolder">{{ displayDayMonthYearTime(g.accepted_date) }}</span></small>
                                        </div>
                                    </td>
                                    <td class="pl-1 pr-1">{{ convertStringNumberToFigures(g.calculated_used) }}</td>
                                    <td class="pl-1 pr-1">{{ convertStringNumberToFigures(g.calculated_partial) }}</td>
                                    <td class="pl-1 pr-1">
                                        <div class="d-flex flex-column">
                                            <small><span class="fw-bolder">{{ g.is_returned }}</span></small>
                                            <small><span class="fw-bolder">{{ displayDayMonthYearTime(g.returned_date) }}</span></small>
                                        </div>
                                    </td>
                                    <td class="pl-1 pr-1">{{ convertStringNumberToFigures(g.returned_qty) }}</td>
                                    <td class="pl-1 pr-1">{{ convertStringNumberToFigures(g.returned_partial) }}</td>
                                    <td class="pl-1 pr-1">
                                        <div class="d-flex flex-column">
                                            <small><span class="fw-bolder">{{ g.is_reconciled }}</span></small>
                                            <small><span class="fw-bolder">{{ displayDayMonthYearTime(g.reconciled_date) }}</span></small>
                                        </div>
                                    </td>
                                    <td class="pl-1 pr-1">{{ convertStringNumberToFigures(g.full_qty) }}</td>
                                    <td class="pl-1 pr-1">{{ convertStringNumberToFigures(g.partial_qty) }}</td>
                                    <td class="pl-1 pr-1">{{ convertStringNumberToFigures(g.wasted_qty) }}</td>
                                    <td class="pl-1 pr-1">{{ convertStringNumberToFigures(g.loss_qty) }}</td>
                                    <td class="pl-1 pr-2">{{ g.loss_reason }}</td>
                                </tr>
                                <tr v-if="tableData.length == 0"><td class="text-center pt-2" colspan="20"><small>No Data Found</small></td></tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="card-footer">
                        <div class="row">
                            <div class="col-12 col-xl-4 col-md-4 col-sm-5">
                                <div class="dropdown sort-dropdown mb-1 mb-sm-0">
                                    <button class="btn filter-btn btn-primary dropdown-toggle border text-dark" type="button" data-toggle="dropdown">{{ tableOptions.limitStart + 1 }} - {{ tableOptions.limitStart + tableData.length }} of {{ tableOptions.total }}</button>
                                    <div class="dropdown-menu dropdown-menu-right">
                                        <a @click="changePerPage(g)" v-for="g in tableOptions.aLength" :key="g" class="dropdown-item" href="javascript:void(0);">{{ g }}</a>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-xl-8 col-md-8 col-sm-7 text-right text-pag">
                                <div class="btn-group">
                                    <button type="button" @click="prevPage()" class="btn btn-sm btn-primary round btn-page-block-overlay" :disabled="!tableOptions.isPrev"><i data-feather='chevron-left'></i> Prev</button>
                                    <input @keyup.enter="currentPage()" class="btn btn-page-block-overlay btn-sm btn-outline-primary pagination-input" type="number" v-model.number="tableOptions.currentPage" :max="tableOptions.pageLength" />
                                    <button class="btn btn-outline-primary btn-page-block-overlay border-l-0"><small class="form-text text-primary"> of {{ tableOptions.pageLength }}</small></button>
                                    <button type="button" @click="nextPage()" class="btn btn-sm btn-primary round" :disabled="!tableOptions.isNext">Next <i data-feather='chevron-right'></i></button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mb-50"></div>
                </div>
            </div>

            <div class="modal fade modal-primary" id="iccDetailsModal" tabindex="-1" role="dialog" aria-hidden="true">
                <div class="modal-dialog modal-scrollable modal-xl">
                    <form class="modal-content pt-0" @submit.stop.prevent="">
                        <div class="modal-header mb-0">
                            <h5 class="modal-title font-weight-bolder text-dark">{{ selectedICCDetails.issuer_name ? selectedICCDetails.issuer_name : selectedICCDetails.issuer_loginid }}, ICC Details<br><span class="badge badge-light-success">{{ selectedICCDetails.issuer_loginid }}</span></h5>
                            <button type="reset" class="close" @click="hideGetIccFlowDetailByCdd()" data-dismiss="modal">×</button>
                        </div>
                        <div class="modal-body flex-grow-1 vertical-wizard">
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
                                        <tr v-for="(g, i) in iccIssuedReconcileDetails" :key="i">
                                            <td>
                                                <div class="d-flex flex-column">
                                                    <span class="fw-bolder">{{ capitalize(g.issuer_name) }}</span>
                                                    <small><span class="badge badge-light-primary">{{ g.issuer_loginid }}</span></small>
                                                </div>
                                            </td>
                                            <td>{{ checkIfEmpty(g.period) }}</td>
                                            <td>{{ checkIfEmpty(g.issue_drug) }}</td>
                                            <td>{{ convertStringNumberToFigures(g.drug_qty) }}</td>
                                            <td>{{ displayDayMonthYearTime(g.issue_date) }}</td>
                                            <td>{{ displayDayMonthYearTime(g.created) }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="reset" class="btn btn-secondary" data-dismiss="modal" @click="hideGetIccFlowDetailByCdd()">Close</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    `,
};

useApp({ template: `<div><page-body/></div>` })
  .component("page-body", PageBody)
  .component("icc_list", IccList)
  .mount("#app");
