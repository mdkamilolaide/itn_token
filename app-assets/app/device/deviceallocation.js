/**
 * Device / Allocation submodule — Vue 3 Composition API in place.
 * Same shape as loginlog.js with an extra "Allocate Device" button +
 * placeholder "Scan User Badge" modal. qid=602.
 */

const { ref, reactive, onMounted, onBeforeUnmount } = Vue;
const { useApp, useFormat, safeMessage } = window.utils;

/* ------------------------------------------------------------------ */
const PageBody = {
  setup() {
    const page = ref("home");
    return { page };
  },
  template: `
        <div>
            <div class="content-body">
                <sample_table/>
            </div>
        </div>
    `,
};

/* ------------------------------------------------------------------ */
const SampleTable = {
  setup() {
    const fmtUtils = useFormat();

    const tableData = ref([]);
    const filterState = ref(false);
    const filters = ref(false);
    const checkToggle = ref(false);
    const userGroup = ref([]);
    const permission = ref(
      typeof getPermission === "function"
        ? getPermission(typeof per !== "undefined" ? per : null, "device") || {
            permission_value: 0,
          }
        : { permission_value: 0 },
    );
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
      aLength: [10, 20, 50, 100],
      filterParam: { date: "", loginid: "", serial_no: "" },
    });

    let dateFlatpickr = null;

    const loadTableData = () => {
      overlay.show();
      var url = common.TableService;
      axios
        .get(
          url +
            "?qid=602&draw=" +
            tableOptions.currentPage +
            "&order_column=" +
            tableOptions.orderField +
            "&length=" +
            tableOptions.perPage +
            "&start=" +
            tableOptions.limitStart +
            "&order_dir=" +
            tableOptions.orderDir +
            "&dat=" +
            tableOptions.filterParam.date +
            "&lid=" +
            tableOptions.filterParam.loginid +
            "&sno=" +
            tableOptions.filterParam.serial_no,
        )
        .then((response) => {
          var d = response && response.data;
          tableData.value = Array.isArray(d && d.data) ? d.data : [];
          tableOptions.total = (d && d.recordsTotal) || 0;
          if (tableOptions.currentPage == 1) paginationDefault();
          overlay.hide();
        })
        .catch((error) => {
          overlay.hide();
          alert.Error("ERROR", safeMessage(error));
        });
    };

    const selectAll = () => {
      for (var i = 0; i < tableData.value.length; i++)
        tableData.value[i].pick = true;
    };
    const uncheckAll = () => {
      for (var i = 0; i < tableData.value.length; i++)
        tableData.value[i].pick = false;
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
      return tableData.value.filter((r) => r.pick).map((r) => r.serial_no);
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
      checkFill += tableOptions.filterParam.loginid != "" ? 1 : 0;
      checkFill += tableOptions.filterParam.serial_no != "" ? 1 : 0;
      checkFill += tableOptions.filterParam.date != "" ? 1 : 0;
      if (checkFill > 0) {
        toggleFilter();
        filters.value = true;
        paginationDefault();
        loadTableData();
      } else {
        alert.Error("ERROR", "Invalid required data");
      }
    };
    const removeSingleFilter = (column_name) => {
      tableOptions.filterParam[column_name] = "";
      var g = 0;
      for (var k in tableOptions.filterParam) {
        if (tableOptions.filterParam[k] != "") g++;
      }
      if (g == 0) filters.value = false;
      paginationDefault();
      loadTableData();
    };
    const clearAllFilter = () => {
      if (dateFlatpickr && typeof dateFlatpickr.clear === "function")
        dateFlatpickr.clear();
      filters.value = false;
      tableOptions.filterParam.loginid = "";
      tableOptions.filterParam.serial_no = "";
      tableOptions.filterParam.date = "";
      paginationDefault();
      loadTableData();
    };
    const refreshData = () => {
      paginationDefault();
      loadTableData();
    };

    // Allocation-specific placeholders (preserved from v2 for parity).
    const allocateDevice = () => {
      console.log("Hello");
    };
    const hidePassResetModal = () => {};
    const scanUserBadges = () => {};

    onMounted(() => {
      var $el = $("#date");
      if ($el.length && typeof $el.flatpickr === "function") {
        dateFlatpickr = $el.flatpickr({
          altInput: true,
          altFormat: "F j, Y",
          dateFormat: "Y-m-d",
          onChange: (selectedDates, dateStr) => {
            tableOptions.filterParam.date = dateStr;
          },
        });
      }
      loadTableData();
    });

    onBeforeUnmount(() => {
      if (dateFlatpickr && typeof dateFlatpickr.destroy === "function") {
        try {
          dateFlatpickr.destroy();
        } catch (e) {
          /* swallow */
        }
        dateFlatpickr = null;
      }
    });

    return {
      tableData,
      filterState,
      filters,
      checkToggle,
      userGroup,
      permission,
      tableOptions,
      loadTableData,
      selectAll,
      uncheckAll,
      selectToggle,
      checkedBg,
      toggleFilter,
      selectedItems,
      selectedID,
      nextPage,
      prevPage,
      currentPage,
      paginationDefault,
      changePerPage,
      sort,
      applyFilter,
      removeSingleFilter,
      clearAllFilter,
      refreshData,
      allocateDevice,
      hidePassResetModal,
      scanUserBadges,
      capitalize: fmtUtils.capitalize,
      formatNumber: fmtUtils.formatNumber,
      displayDate: fmtUtils.displayDate,
    };
  },
  template: `
        <div class="row" id="basic-table">

            <div class="col-md-8 col-sm-12 col-12 mb-0">
                <h2 class="content-header-title header-txt float-left mb-0">Allocation</h2>
                <div class="breadcrumb-wrapper">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../device">Home</a></li>
                        <li class="breadcrumb-item active">Device Allocation</li>
                    </ol>
                </div>
            </div>

            <div class="col-md-4 col-sm-12 col-12 text-md-right text-right d-md-block">
                <div class="btn-group mr-1">
                    <button type="button" class="btn btn-outline-primary round searchBtn" @click="refreshData()" data-toggle="tooltip" data-placement="top" title="Refresh">
                        <i class="feather icon-refresh-cw"></i>
                    </button>
                    <button type="button" class="btn btn-outline-primary round searchBtn" @click="toggleFilter()" data-toggle="tooltip" data-placement="top" title="Filter">
                        <i class="feather" :class="filterState ? 'icon-x' : 'icon-filter'"></i>
                    </button>
                    <button type="button" class="btn btn-outline-primary round searchBtn" @click="allocateDevice()" data-placement="top" title="Allocate Device" data-backdrop="static" data-keyboard="false" data-toggle="modal" data-target="#scanUserBadges">
                        <i class="feather icon-plus"></i>
                    </button>
                </div>
            </div>

            <div class="col-12" v-if="filters">
                <div class="col-12 filter-bar">
                    <template v-for="(filterParam, i) in tableOptions.filterParam" :key="i">
                        <span class="badge badge-dark filter-box" v-if="String(filterParam).length > 0" @click="removeSingleFilter(i)">
                            {{ capitalize(i) }}: {{ filterParam }} <i class="feather icon-x"></i>
                        </span>
                    </template>
                    <a href="#" class="float-right clear-filter" @click="clearAllFilter()">Clear</a>
                </div>
            </div>

            <div class="col-12 mt-1" v-if="permission.permission_value >= 1">
                <div class="card">
                    <div class="card-body py-1" v-show="filterState">
                        <form id="filterForm">
                            <div class="row">
                                <div class="col-12 col-sm-6 col-md-3 col-lg-3">
                                    <div class="form-group">
                                        <label>Login ID</label>
                                        <input type="text" v-model="tableOptions.filterParam.loginid" class="form-control loginid" id="loginid" placeholder="Login ID" name="loginid" />
                                    </div>
                                </div>
                                <div class="col-12 col-sm-6 col-md-3 col-lg-3">
                                    <div class="form-group">
                                        <label>Device Serial No.</label>
                                        <input type="text" v-model="tableOptions.filterParam.serial_no" class="form-control serial_no" id="serial_no" placeholder="Serial Number" name="serial_no" />
                                    </div>
                                </div>
                                <div class="col-12 col-sm-6 col-md-3 col-lg-3">
                                    <div class="form-group">
                                        <label>Last Connected Date</label>
                                        <input type="text" v-model="tableOptions.filterParam.date" class="form-control date" id="date" placeholder="Date" name="date" />
                                    </div>
                                </div>
                                <div class="col-12 col-sm-6 col-md-3 col-lg-3">
                                    <div class="form-group mt-2 text-right">
                                        <button type="button" class="btn mt-25 btn-md btn-primary" @click="applyFilter()">Apply Filters</button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th @click="sort(0)" width="60px" style="padding-right: 2px !important;">#
                                        <i class="feather icon-chevron-up sort-up"   :class="(tableOptions.orderField == 0 && tableOptions.orderDir =='asc' )? 'active-sort':''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 0 && tableOptions.orderDir =='desc')? 'active-sort':''"></i>
                                    </th>
                                    <th @click="sort(0)">Device ID
                                        <i class="feather icon-chevron-up sort-up"   :class="(tableOptions.orderField == 0 && tableOptions.orderDir =='asc' )? 'active-sort':''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 0 && tableOptions.orderDir =='desc')? 'active-sort':''"></i>
                                    </th>
                                    <th @click="sort(3)">Serial No
                                        <i class="feather icon-chevron-up sort-up"   :class="(tableOptions.orderField == 3 && tableOptions.orderDir =='asc' )? 'active-sort':''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 3 && tableOptions.orderDir =='desc')? 'active-sort':''"></i>
                                    </th>
                                    <th @click="sort(1)">Device Description
                                        <i class="feather icon-chevron-up sort-up"   :class="(tableOptions.orderField == 1 && tableOptions.orderDir =='asc' )? 'active-sort':''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 1 && tableOptions.orderDir =='desc')? 'active-sort':''"></i>
                                    </th>
                                    <th @click="sort(5)">Status
                                        <i class="feather icon-chevron-up sort-up"   :class="(tableOptions.orderField == 5 && tableOptions.orderDir =='asc' )? 'active-sort':''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 5 && tableOptions.orderDir =='desc')? 'active-sort':''"></i>
                                    </th>
                                    <th @click="sort(7)">User Details
                                        <i class="feather icon-chevron-up sort-up"   :class="(tableOptions.orderField == 7 && tableOptions.orderDir =='asc' )? 'active-sort':''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 7 && tableOptions.orderDir =='desc')? 'active-sort':''"></i>
                                    </th>
                                    <th @click="sort(10)">Last Time Connected
                                        <i class="feather icon-chevron-up sort-up"   :class="(tableOptions.orderField == 10 && tableOptions.orderDir =='asc' )? 'active-sort':''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 10 && tableOptions.orderDir =='desc')? 'active-sort':''"></i>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(g, i) in tableData" :key="g.serial_no || i" :class="checkedBg(g.pick)">
                                    <td>{{ i + 1 }}</td>
                                    <td>{{ g.device_id }}</td>
                                    <td>{{ g.serial_no }}</td>
                                    <td>
                                        <div class="d-flex justify-content-left align-items-center">
                                            <div class="d-flex flex-column">
                                                <span class="user_name text-truncate text-body">
                                                    <span class="fw-bolder" v-text="g.device_name? capitalize(g.device_name) : 'Unknown Device'"></span>
                                                </span>
                                                <small class="emp_post text-primary" v-html="g.device_type ? g.device_type : ''"></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><span class="badge rounded-pill font-small-1" :class="g.active=='1'? 'bg-success' : 'bg-danger'">{{ g.active=='1'? 'Active' : 'Inactive' }}</span></td>
                                    <td>
                                        <div class="d-flex justify-content-left align-items-center">
                                            <div class="d-flex flex-column">
                                                <span class="user_name text-truncate text-body">
                                                    <span class="fw-bolder">{{ capitalize(g.first) }} {{ capitalize((g.middle || '') + ' ') }} {{ capitalize(g.last) }}</span>
                                                </span>
                                                <small class="emp_post text-primary" v-html="g.loginid ? g.loginid : ''"></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{ g.created }}</td>
                                </tr>
                                <tr v-if="tableData.length === 0"><td class="text-center pt-2" colspan="7"><small>No Device Login Logs</small></td></tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="card-footer">
                        <div class="content-fluid">
                            <div class="row">
                                <div class="col-12 col-xl-4 col-md-4 col-sm-5">
                                    <div class="dropdown sort-dropdown mb-1 mb-sm-0">
                                        <button class="btn filter-btn btn-primary dropdown-toggle border text-dark" type="button" id="tablePaginationDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                            {{ tableOptions.limitStart + 1 }} - {{ tableOptions.limitStart + tableData.length }} of {{ tableOptions.total }}
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-right" aria-labelledby="tablePaginationDropdown">
                                            <a @click="changePerPage(g)" v-for="g in tableOptions.aLength" :key="g" class="dropdown-item" href="javascript:void(0);">{{ g }}</a>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 col-xl-8 col-md-8 col-sm-7 text-right text-pag">
                                    <div class="btn-group">
                                        <button type="button" @click="prevPage()" class="btn btn-sm btn-primary round btn-page-block-overlay" :disabled="!tableOptions.isPrev">
                                            <i data-feather='chevron-left'></i> Prev
                                        </button>
                                        <input @keyup.enter="currentPage()" class="btn btn-page-block-overlay btn-sm btn-outline-primary pagination-input" type="number" v-model.number="tableOptions.currentPage" :max="tableOptions.pageLength" />
                                        <button class="btn btn-outline-primary btn-page-block-overlay border-l-0">
                                            <small class="form-text text-primary"> of {{ tableOptions.pageLength }}</small>
                                        </button>
                                        <button type="button" @click="nextPage()" class="btn btn-sm btn-primary round" :disabled="!tableOptions.isNext">
                                            Next <i data-feather='chevron-right'></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-50"></div>

                    <!-- Scan User Badge Modal (placeholder, preserved from v2) -->
                    <div class="modal fade text-left" id="scanUserBadges" tabindex="-1" role="dialog" aria-labelledby="myModalLabel34" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered modal-sm" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h4 class="modal-title text-primary" id="myModalLabel34">Scan User Badge</h4>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close" @click="hidePassResetModal()">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <form class="validate-form" method="POST" @submit.stop.prevent="scanUserBadges()">
                                    <div class="modal-body"></div>
                                    <div class="modal-footer">
                                        <div class="text-center">
                                            <button type="submit" class="btn btn-primary mt-2 me-1 waves-effect waves-float waves-light">Reset Password</button>
                                            <button type="reset" class="btn btn-outline-secondary mt-2 waves-effect" data-bs-dismiss="modal" @click="hidePassResetModal()" data-dismiss="modal" aria-label="Close">Cancel</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-12 col-sm-12 col-12" v-else>
                <h6 class="text-center text-info pt-4 pb-4">You don't have permission to view this page</h6>
            </div>
        </div>
    `,
};

useApp({ template: `<div><page-body/></div>` })
  .component("page-body", PageBody)
  .component("sample_table", SampleTable)
  .mount("#app");
