/**
 * SMC / Referral — Vue 3 Composition API in place.
 * Two components — page-body and referral_list.
 *
 * qid=703 paginated referral list with multi-select periods, geo filter,
 * referral status filter. Header stats from qid=1110 (referrals/attended/
 * period count + percentageAttended progress bar). Excel export
 * (count via qid=1125, dump via qid=802).
 */

const { ref, reactive, computed, onMounted, onBeforeUnmount } = Vue;
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
                <div v-show="page == 'list'"><referral_list/></div>
            </div>
        </div>
    `,
};

const ReferralList = {
  setup() {
    const fmtUtils = useFormat();

    const url = ref(window.common && window.common.BadgeService);
    const tableData = ref([]);
    const permission = ref(
      typeof getPermission === "function"
        ? getPermission(typeof per !== "undefined" ? per : null, "smc") || {
            permission_value: 0,
          }
        : { permission_value: 0 },
    );
    const statData = reactive({ referrals: 0, attended: 0, period: 0 });
    const statProgessBarStatus = ref("progress-bar-default");
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
        visitTitle: "",
        attendedDate: "",
        geo_level: "",
        geo_level_id: "",
        geo_string: "",
        referralStatus: "",
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

    const loadTableData = async () => {
      overlay.show();
      var fp = tableOptions.filterParam;
      var periodIds = joinWithCommaAnd(fp.periodid, true);
      var endpoints = [
        common.TableService +
          "?qid=703&draw=" +
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
          "&gl=" +
          fp.geo_level +
          "&atd=" +
          fp.referralStatus,
        common.DataService +
          "?qid=1110&pid=" +
          periodIds +
          "&gid=" +
          fp.geo_level_id +
          "&gl=" +
          fp.geo_level +
          "&atd=" +
          fp.referralStatus,
      ];
      try {
        var responses = await Promise.all(endpoints.map((e) => axios.get(e)));
        var t = responses[0] && responses[0].data;
        tableData.value = Array.isArray(t && t.data) ? t.data : [];
        tableOptions.total = (t && t.recordsTotal) || 0;
        var s =
          responses[1] &&
          responses[1].data &&
          responses[1].data.data &&
          responses[1].data.data[0];
        if (s) {
          statData.referrals = s.referrals || 0;
          statData.attended = s.attended || 0;
          statData.period = s.period || 0;
        }
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
      if (tableOptions.filterParam.referralStatus != "") checkFill++;
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
        attendedDate: "",
        referralStatus: "",
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
      return ["periodid", "geo_level_id", "geo_level"].indexOf(name) === -1;
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
      // multi-select. Vue's v-model on a multi-select gives an array of strings.
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
      words = words.map(
        (w) => w.charAt(0).toUpperCase() + w.slice(1).toLowerCase(),
      );
      return words.join(" ");
    };
    const exportChildRefferal = async () => {
      var fp = tableOptions.filterParam;
      var periodIds = joinWithCommaAnd(fp.periodid, true);
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
        "&gl=" +
        fp.geo_level +
        "&atd=" +
        fp.referralStatus;
      var veriUrl = "qid=1125" + qs;
      var dlString = "qid=802" + qs;
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
        fp.geo_level +
        "_" +
        fp.referralStatus +
        "_Refferal_Export_" +
        formattedDate;
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
    const convertStringNumberToFigures = (d) => {
      var data = d ? parseInt(d) : 0;
      return data ? data.toLocaleString() : 0;
    };

    const percentageAttended = computed(() => {
      if (!statData.referrals) return 0;
      var p = ((statData.attended / statData.referrals) * 100).toFixed(2);
      if (p < 50) statProgessBarStatus.value = "progress-bar-danger";
      else if (p < 70) statProgessBarStatus.value = "progress-bar-warning";
      else statProgessBarStatus.value = "progress-bar-success";
      return p;
    });

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
    });

    return {
      url,
      tableData,
      permission,
      statData,
      statProgessBarStatus,
      geoData,
      periodData,
      checkIfFilterOn,
      filterState,
      filters,
      tableOptions,
      percentageAttended,
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
      refreshData,
      getGeoLocation,
      getAllPeriodLists,
      setLocation,
      setPeriodTitle,
      splitWordAndCapitalize,
      exportChildRefferal,
      convertStringNumberToFigures,
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
                        <li class="breadcrumb-item active">Referral</li>
                    </ol>
                </div>
            </div>

            <div class="col-md-4 col-sm-12 col-12 text-md-right text-right d-md-block">
                <div class="btn-group">
                    <button type="button" class="btn btn-outline-primary round searchBtn" @click="refreshData()" data-toggle="tooltip" data-placement="top" title="Refresh"><i class="feather icon-refresh-cw"></i></button>
                    <button type="button" class="btn btn-outline-primary round searchBtn" @click="toggleFilter()" data-toggle="tooltip" data-placement="top" title="Filter"><i class="feather" :class="filterState ? 'icon-x' : 'icon-filter'"></i></button>
                    <button v-if="permission.permission_value >= 2" type="button" class="btn btn-outline-primary round" data-toggle="tooltip" data-placement="top" title="Download" @click="exportChildRefferal()"><i class="feather icon-download"></i></button>
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
                <div class="card mb-1">
                    <div class="card-widget-separator-wrapper">
                        <div class="card-body pb-75 pt-75 card-widget-separator">
                            <div class="row gy-4 gy-sm-1">
                                <div class="col-sm-6 col-lg-3">
                                    <div class="d-flex justify-content-between align-items-start card-widget-1 border-end pb-sm-0">
                                        <div><h6 class="mb-50">Total Referral</h6><h4 class="mb-0">{{ convertStringNumberToFigures(statData.referrals) }}</h4></div>
                                        <span class="avatar1 me-sm-4"><span class="avatar-initial bg-light-secondary rounded"><i class="ti-md ti ti-arrow-ramp-right text-body"></i></span></span>
                                    </div>
                                </div>
                                <div class="col-sm-6 col-lg-3">
                                    <div class="d-flex justify-content-between align-items-start card-widget-2 border-end pb-sm-0">
                                        <div><h6 class="mb-50">Attended</h6><h4 class="mb-0">{{ convertStringNumberToFigures(statData.attended) }}</h4></div>
                                        <span class="avatar1 me-sm-4"><span class="avatar-initial bg-light-secondary rounded"><i class="ti-md ti ti-arrow-down-left-circle text-body"></i></span></span>
                                    </div>
                                </div>
                                <div class="col-sm-6 col-lg-3">
                                    <div class="d-flex justify-content-between align-items-start border-end pb-sm-0 card-widget-3">
                                        <div><h6 class="mb-50">Total Visit</h6><h4 class="mb-0">{{ convertStringNumberToFigures(statData.period) }}</h4></div>
                                        <span class="avatar1 me-sm-4"><span class="avatar-initial bg-light-secondary rounded"><i class="ti-md ti ti-brand-spacehey text-body"></i></span></span>
                                    </div>
                                </div>
                                <div class="col-sm-6 col-lg-3">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="mb-50">Percentage Attended</h6>
                                            <h4 class="mb-0" v-if="percentageAttended <= 0">{{ percentageAttended }}%</h4>
                                        </div>
                                    </div>
                                    <div v-if="percentageAttended > 0" class="progress referral" :class="statProgessBarStatus" style="height: 22px; font-weight: bolder">
                                        <div class="progress-bar" role="progressbar" :style="{ width: percentageAttended + '%' }">{{ percentageAttended + '%' }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card custom-select-down">
                    <div class="card-body py-1" v-show="filterState">
                        <form id="filterForm">
                            <div class="row">
                                <div class="col-12 col-sm-6 col-md-6 col-lg-6">
                                    <div class="form-group">
                                        <label>Visit</label>
                                        <select @change="setPeriodTitle($event && $event.target ? Array.from($event.target.selectedOptions).map(o => o.value) : [])" v-model="tableOptions.filterParam.periodid" multiple class="form-control period">
                                            <option v-for="(g, i) in periodData" :key="g.periodid" :value="g.periodid">{{ g.title }}</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-12 col-sm-6 col-md-6 col-lg-6">
                                    <div class="form-group">
                                        <label>Geo Location</label>
                                        <select class="select2 form-control" @change="setLocation()">
                                            <option value="">Select Geo Location</option>
                                            <option v-for="(g, i) in geoData" :key="i" :value="i">{{ g.geo_string }}</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-12 col-sm-6 col-md-6 col-lg-6">
                                    <div class="form-group">
                                        <label>Referral Status</label>
                                        <select class="form-control" v-model="tableOptions.filterParam.referralStatus">
                                            <option value="">All</option>
                                            <option value="Yes">Attended</option>
                                            <option value="No">Not Attended</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-12 col-sm-12 col-md-12 col-lg-6"><div class="form-group mt-2 text-right"><button type="button" class="btn btn-md btn-primary" @click="applyFilter()">Apply Filters</button></div></div>
                            </div>
                        </form>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th @click="sort(2)">Beneficiary</th>
                                    <th @click="sort(1)">Visit</th>
                                    <th @click="sort(4)">Referred Type</th>
                                    <th @click="sort(7)">Refer Date</th>
                                    <th @click="sort(5)">Attended Status</th>
                                    <th @click="sort(7)" class="text-center">Attended Date</th>
                                    <th @click="sort(6)">Geo String</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(g, i) in tableData" :key="g.beneficiary_id || i">
                                    <td>
                                        <div class="d-flex flex-column">
                                            <span class="fw-bolder">{{ g.name }}</span>
                                            <small><span class="badge badge-light-primary">{{ g.beneficiary_id }}</span></small>
                                        </div>
                                    </td>
                                    <td>{{ g.period }}</td>
                                    <td><span class="fw-bolder">{{ g.refer_type }}</span></td>
                                    <td><span class="badge badge-light-warning">{{ displayDate(g.referred_date) }}</span></td>
                                    <td>
                                        <span class="fw-bolder badge p-25" :class="g.attended == 'No' ? 'badge-light-warning' : 'badge-light-success'">
                                            <i class="feather" :class="g.attended == 'No' ? 'icon-x-circle' : 'icon-check-circle'"></i>
                                        </span>
                                    </td>
                                    <td><span class="badge badge-light-success">{{ g.attended_date }}</span></td>
                                    <td><small class="fw-bolder">{{ g.geo_string }}</small></td>
                                </tr>
                                <tr v-if="tableData.length == 0"><td class="text-center pt-2" colspan="7"><small>No Data Found</small></td></tr>
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
        </div>
    `,
};

useApp({ template: `<div><page-body/></div>` })
  .component("page-body", PageBody)
  .component("referral_list", ReferralList)
  .mount("#app");
