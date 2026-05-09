/**
 * Netcard / Pushed — Vue 3 Composition API in place.
 * Three components — page-body, wallet_list, mobilization_details.
 *
 * wallet_list: paginated list of e-Netcard pushes (qid=205) with the
 * same filter/sort/page UX as the other list views.
 * mobilization_details: read-only / edit user profile pane (qid=005, 006).
 */

const { ref, reactive, onMounted, onBeforeUnmount } = Vue;
const { useApp, useFormat, bus, safeMessage } = window.utils;

const PageBody = {
  setup() {
    const page = ref("list");
    function gotoPageHandler(data) {
      page.value = data && data.page;
    }
    onMounted(function () {
      bus.on("g-event-goto-page", gotoPageHandler);
    });
    onBeforeUnmount(function () {
      bus.off("g-event-goto-page", gotoPageHandler);
    });
    return { page };
  },
  template: `
        <div>
            <div class="content-body">
                <div v-show="page == 'list'"><wallet_list/></div>
                <div v-show="page == 'detail'"><mobilization_details/></div>
            </div>
        </div>
    `,
};

const WalletList = {
  setup() {
    const fmtUtils = useFormat();

    const url = ref(window.common && window.common.BadgeService);
    const permission = ref(
      typeof getPermission === "function"
        ? getPermission(
            typeof per !== "undefined" ? per : null,
            "enetcard",
          ) || { permission_value: 0 }
        : { permission_value: 0 },
    );
    const tableData = ref([]);
    const geoData = ref([]);
    const userRole = reactive({ currentUserRole: "", currentUserid: "" });
    const checkToggle = ref(false);
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
        loginid: "",
        mobilization_date: "",
        geo_level: "",
        geo_level_id: "",
        geo_string: "",
      },
    });
    const sysDefaultData = ref([]);
    const userPass = reactive({ pass: "", loginid: "", name: "" });

    function reloadUserListOnUpdate() {
      paginationDefault();
      loadTableData();
    }

    function loadTableData() {
      overlay.show();
      axios
        .get(
          common.TableService +
            "?qid=205&draw=" +
            tableOptions.currentPage +
            "&order_column=" +
            tableOptions.orderField +
            "&length=" +
            tableOptions.perPage +
            "&start=" +
            tableOptions.limitStart +
            "&order_dir=" +
            tableOptions.orderDir +
            "&gl=" +
            tableOptions.filterParam.geo_level +
            "&lgid=" +
            tableOptions.filterParam.loginid +
            "&glid=" +
            tableOptions.filterParam.geo_level_id +
            "&mdt=" +
            tableOptions.filterParam.mobilization_date,
        )
        .then(function (response) {
          var d = response && response.data;
          tableData.value = Array.isArray(d && d.data) ? d.data : [];
          tableOptions.total = (d && d.recordsTotal) || 0;
          if (tableOptions.currentPage == 1) paginationDefault();
          overlay.hide();
        })
        .catch(function (error) {
          overlay.hide();
          alert.Error("ERROR", safeMessage(error));
        });
    }

    function selectAll() {
      for (var i = 0; i < tableData.value.length; i++)
        tableData.value[i].pick = true;
    }
    function uncheckAll() {
      for (var i = 0; i < tableData.value.length; i++)
        tableData.value[i].pick = false;
    }
    function selectToggle() {
      if (checkToggle.value === false) {
        selectAll();
        checkToggle.value = true;
      } else {
        uncheckAll();
        checkToggle.value = false;
      }
    }
    function checkedBg(pickOne) {
      return pickOne != "" ? "bg-select" : "";
    }
    function toggleFilter() {
      if (filterState.value === false) filters.value = false;
      return (filterState.value = !filterState.value);
    }
    function selectedItems() {
      return tableData.value.filter(function (r) {
        return r.pick;
      });
    }
    function selectedID() {
      return tableData.value
        .filter(function (r) {
          return r.pick;
        })
        .map(function (r) {
          return r.userid;
        });
    }

    function paginationDefault() {
      tableOptions.pageLength = Math.ceil(
        tableOptions.total / tableOptions.perPage,
      );
      tableOptions.limitStart = Math.ceil(
        (tableOptions.currentPage - 1) * tableOptions.perPage,
      );
      tableOptions.isNext = tableOptions.currentPage < tableOptions.pageLength;
      tableOptions.isPrev = tableOptions.currentPage > 1;
    }
    function nextPage() {
      tableOptions.currentPage += 1;
      paginationDefault();
      loadTableData();
    }
    function prevPage() {
      tableOptions.currentPage -= 1;
      paginationDefault();
      loadTableData();
    }
    function currentPage() {
      paginationDefault();
      if (tableOptions.currentPage < 1)
        alert.Error("ERROR", "The Page requested doesn't exist");
      else if (tableOptions.currentPage > tableOptions.pageLength)
        alert.Error("ERROR", "The Page requested doesn't exist");
      else loadTableData();
    }
    function changePerPage(val) {
      var maxPerPage = Math.ceil(tableOptions.total / val);
      if (maxPerPage < tableOptions.currentPage)
        tableOptions.currentPage = maxPerPage;
      tableOptions.perPage = val;
      paginationDefault();
      loadTableData();
    }
    function sort(col) {
      if (tableOptions.orderField === col)
        tableOptions.orderDir =
          tableOptions.orderDir === "asc" ? "desc" : "asc";
      else tableOptions.orderField = col;
      paginationDefault();
      loadTableData();
    }
    function applyFilter() {
      var checkFill = 0;
      checkFill += tableOptions.filterParam.loginid != "" ? 1 : 0;
      checkFill += tableOptions.filterParam.mobilization_date != "" ? 1 : 0;
      checkFill += tableOptions.filterParam.geo_level != "" ? 1 : 0;
      checkFill += tableOptions.filterParam.geo_level_id != "" ? 1 : 0;
      if (checkFill > 0) {
        toggleFilter();
        filters.value = true;
        paginationDefault();
        loadTableData();
      } else {
        alert.Error("ERROR", "Invalid required data");
      }
    }
    function removeSingleFilter(column_name) {
      tableOptions.filterParam[column_name] = "";
      if (column_name == "geo_level" || column_name == "geo_level_id") {
        tableOptions.filterParam.geo_level = "";
        tableOptions.filterParam.geo_level_id = "";
      }
      var g = 0;
      for (var k in tableOptions.filterParam) {
        if (tableOptions.filterParam[k] != "") g++;
      }
      if (g == 0) filters.value = false;
      paginationDefault();
      loadTableData();
    }
    function clearAllFilter() {
      try {
        $("#mobilization_date")
          .flatpickr({
            altInput: true,
            altFormat: "F j, Y",
            dateFormat: "Y-m-d",
          })
          .clear();
      } catch (e) {}
      filters.value = false;
      tableOptions.filterParam.mobilization_date = "";
      tableOptions.filterParam.loginid = "";
      tableOptions.filterParam.geo_level = "";
      tableOptions.filterParam.geo_level_id = "";
      paginationDefault();
      loadTableData();
    }
    function goToDetail(userid, user_status) {
      bus.emit("g-event-goto-page", {
        userid: userid,
        page: "detail",
        user_status: user_status,
      });
    }
    function refreshData() {
      paginationDefault();
      loadTableData();
    }
    function getGeoLocation() {
      overlay.show();
      axios
        .get(common.DataService + "?qid=gen009")
        .then(function (response) {
          geoData.value = (response.data && response.data.data) || [];
          overlay.hide();
        })
        .catch(function (error) {
          overlay.hide();
          alert.Error("ERROR", safeMessage(error));
        });
    }
    function setLocation(select_index) {
      var i = select_index || 0;
      var row = geoData.value[i];
      if (!row) return;
      tableOptions.filterParam.geo_level = row.geo_level;
      tableOptions.filterParam.geo_level_id = row.geo_level_id;
      tableOptions.filterParam.geo_string = row.title;
    }

    async function exportMobilization() {
      var qs =
        "qid=301&draw=" +
        tableOptions.currentPage +
        "&order_column=" +
        tableOptions.orderField +
        "&length=" +
        tableOptions.perPage +
        "&start=" +
        tableOptions.limitStart +
        "&order_dir=" +
        tableOptions.orderDir +
        "&gl=" +
        tableOptions.filterParam.geo_level +
        "&lgid=" +
        tableOptions.filterParam.loginid +
        "&glid=" +
        tableOptions.filterParam.geo_level_id +
        "&mdt=" +
        tableOptions.filterParam.mobilization_date;
      var filename =
        (tableOptions.filterParam.geo_string
          ? tableOptions.filterParam.geo_string
          : "Recent ") +
        " " +
        (tableOptions.filterParam.loginid
          ? tableOptions.filterParam.loginid
          : "Recent ") +
        " Mobilization List";
      overlay.show();

      var count = await new Promise(function (resolve) {
        $.ajax({
          url: common.DataService,
          type: "POST",
          data: qs,
          dataType: "json",
          success: function (data) {
            resolve(data.total);
          },
        });
      });
      var downloadMax =
        (window.common && window.common.ExportDownloadLimit) || 25000;
      if (parseInt(count) > downloadMax) {
        alert.Error(
          "Download Error",
          "Unable to download data because it has exceeded download limit, download limit is " +
            downloadMax,
        );
      } else if (parseInt(count) == 0) {
        alert.Error("Download Error", "No data found");
      } else {
        alert.Info("DOWNLOADING...", "Downloading " + count + " record(s)");
        var outcome = await new Promise(function (resolve) {
          $.ajax({
            url: common.ExportService,
            type: "POST",
            data: qs,
            success: function (data) {
              resolve(data);
            },
          });
        });
        var exportData = JSON.parse(outcome);
        if (window.Jhxlsx && typeof window.Jhxlsx.export === "function") {
          window.Jhxlsx.export(exportData, { fileName: filename });
        }
      }
      overlay.hide();
    }

    onMounted(function () {
      getGeoLocation();
      loadTableData();
      bus.on("g-event-update-user", reloadUserListOnUpdate);

      try {
        var select = $(".select2");
        select.each(function () {
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
        $(".select2-selection__arrow").html(
          '<i class="feather icon-chevron-down"></i>',
        );
        $(".date").flatpickr({
          altInput: true,
          altFormat: "F j, Y",
          dateFormat: "Y-m-d",
        });
      } catch (e) {}
    });
    onBeforeUnmount(function () {
      bus.off("g-event-update-user", reloadUserListOnUpdate);
    });

    return {
      url,
      permission,
      tableData,
      geoData,
      userRole,
      checkToggle,
      filterState,
      filters,
      tableOptions,
      sysDefaultData,
      userPass,
      reloadUserListOnUpdate,
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
      goToDetail,
      refreshData,
      getGeoLocation,
      setLocation,
      exportMobilization,
      capitalize: fmtUtils.capitalize,
      displayDate: fmtUtils.displayDate,
      formatNumber: fmtUtils.formatNumber,
    };
  },
  template: `
        <div class="row" id="basic-table">
            <div class="col-md-8 col-sm-12 col-12 mb-0">
                <h2 class="content-header-title header-txt float-left mb-0">e-Netcard</h2>
                <div class="breadcrumb-wrapper">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../mobilization">Home</a></li>
                        <li class="breadcrumb-item active">e-Netcard Pushed</li>
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
                    <button class="btn btn-outline-primary round dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Actions</button>
                    <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                        <a class="dropdown-item" v-if="permission.permission_value >= 2" href="javascript:void(0);" @click="exportMobilization()">Export Data</a>
                    </div>
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

            <div class="col-12 mt-1">
                <div class="card custom-select-down">
                    <div class="card-body py-1" v-show="filterState">
                        <form id="filterForm">
                            <div class="row">
                                <div class="col-12 col-sm-6 col-md-6 col-lg-2">
                                    <div class="form-group">
                                        <label>Mobilizer Login ID</label>
                                        <input type="text" v-model="tableOptions.filterParam.loginid" class="form-control login-id" id="login-id" placeholder="Login ID" name="loginid" />
                                    </div>
                                </div>
                                <div class="col-12 col-sm-6 col-md-6 col-lg-4">
                                    <div class="form-group">
                                        <label>Geo Location</label>
                                        <select class="select2 form-control" @change="setLocation()" placeholder="Geo Location">
                                            <option value="">Select Geo Location</option>
                                            <option v-for="(g, i) in geoData" :key="i" :value="i">{{ g.geo_string }}</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-12 col-sm-6 col-md-6 col-lg-3">
                                    <div class="form-group date_filter">
                                        <label>Mobilization Date</label>
                                        <input type="text" id="mobilization_date" v-model="tableOptions.filterParam.mobilization_date" class="form-control mobilization_date date" placeholder="Mobilization Date" name="mobilization_date" />
                                    </div>
                                </div>
                                <div class="col-12 col-sm-6 col-md-6 col-lg-3">
                                    <div class="form-group mt-2 text-right">
                                        <button type="button" class="btn btn-md btn-primary" @click="applyFilter()">Apply Filters</button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th @click="sort(0)" style="padding-right: 2px !important;">#
                                        <i class="feather icon-chevron-up sort-up" :class="(tableOptions.orderField == 0 && tableOptions.orderDir =='asc')? 'active-sort': ''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 0 && tableOptions.orderDir =='desc')? 'active-sort': ''"></i>
                                    </th>
                                    <th @click="sort(1)">Household Mobilizer
                                        <i class="feather icon-chevron-up sort-up" :class="(tableOptions.orderField == 1 && tableOptions.orderDir =='asc')? 'active-sort': ''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 1 && tableOptions.orderDir =='desc')? 'active-sort': ''"></i>
                                    </th>
                                    <th @click="sort(4)">Device Serial
                                        <i class="feather icon-chevron-up sort-up" :class="(tableOptions.orderField == 4 && tableOptions.orderDir =='asc')? 'active-sort': ''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 4 && tableOptions.orderDir =='desc')? 'active-sort': ''"></i>
                                    </th>
                                    <th @click="sort(5)">Amount
                                        <i class="feather icon-chevron-up sort-up" :class="(tableOptions.orderField == 5 && tableOptions.orderDir =='asc')? 'active-sort': ''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 5 && tableOptions.orderDir =='desc')? 'active-sort': ''"></i>
                                    </th>
                                    <th @click="sort(6)">Location
                                        <i class="feather icon-chevron-up sort-up" :class="(tableOptions.orderField == 6 && tableOptions.orderDir =='asc')? 'active-sort': ''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 6 && tableOptions.orderDir =='desc')? 'active-sort': ''"></i>
                                    </th>
                                    <th @click="sort(3)">Phone No
                                        <i class="feather icon-chevron-up sort-up" :class="(tableOptions.orderField == 3 && tableOptions.orderDir =='asc')? 'active-sort': ''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 3 && tableOptions.orderDir =='desc')? 'active-sort': ''"></i>
                                    </th>
                                    <th @click="sort(7)">Mobilized Date
                                        <i class="feather icon-chevron-up sort-up" :class="(tableOptions.orderField == 7 && tableOptions.orderDir =='asc')? 'active-sort': ''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 7 && tableOptions.orderDir =='desc')? 'active-sort': ''"></i>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(g, i) in tableData" :key="g.id || i">
                                    <td>{{ g.id }}</td>
                                    <td>
                                        <div class="d-flex justify-content-left align-items-center">
                                            <div class="d-flex flex-column">
                                                <span class="user_name text-truncate text-body"><span class="fw-bolder">{{ g.fullname }}</span></span>
                                                <small class="emp_post text-primary">{{ g.loginid }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{ g.device_serial }}</td>
                                    <td><span class="badge badge-light-success">{{ g.amount }}</span></td>
                                    <td>{{ g.geo_string }}</td>
                                    <td>{{ g.phone }}</td>
                                    <td>{{ displayDate(g.created) }}</td>
                                </tr>
                                <tr v-if="tableData.length == 0"><td class="text-center pt-2" colspan="7"><small>No Wallet Data</small></td></tr>
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
                                        <button type="button" @click="prevPage()" class="btn btn-sm btn-primary round btn-page-block-overlay" :disabled="!tableOptions.isPrev"><i data-feather='chevron-left'></i> Prev</button>
                                        <input @keyup.enter="currentPage()" class="btn btn-page-block-overlay btn-sm btn-outline-primary pagination-input" type="number" v-model.number="tableOptions.currentPage" :max="tableOptions.pageLength" />
                                        <button class="btn btn-outline-primary btn-page-block-overlay border-l-0">
                                            <small class="form-text text-primary"> of {{ tableOptions.pageLength }}</small>
                                        </button>
                                        <button type="button" @click="nextPage()" class="btn btn-sm btn-primary round" :disabled="!tableOptions.isNext">Next <i data-feather='chevron-right'></i></button>
                                    </div>
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

const MobilizationDetails = {
  setup() {
    const fmtUtils = useFormat();

    const userid = ref("");
    const userDetails = ref(true);
    const user_status = ref("");
    const bankListData = ref([]);
    const roleListData = ref([]);
    const userData = reactive({
      baseData: [],
      financeData: [],
      identityData: [],
      roleData: [],
    });

    function gotoPageHandler(data) {
      userDetails.value = true;
      userid.value = data.userid;
      user_status.value = data.user_status;
      getUserDetails();
    }
    function goToList() {
      bus.emit("g-event-goto-page", { page: "list", userid: userid.value });
    }
    function discardUpdate() {
      $.confirm({
        title: "WARNING!",
        content:
          "<p>Are you sure you want to discard the changes? </p><br>Discarding the changes means you will loss all changes made",
        buttons: {
          delete: {
            text: "Discard Changes",
            btnClass: "btn btn-warning mr-1",
            action: function () {
              getUserDetails();
              userDetails.value = true;
              overlay.hide();
            },
          },
          close: {
            text: "Cancel",
            btnClass: "btn btn-outline-secondary",
            action: function () {
              overlay.hide();
            },
          },
        },
      });
    }
    function getUserDetails() {
      overlay.show();
      axios
        .get(common.DataService + "?qid=005&e=" + userid.value)
        .then(function (response) {
          userData.baseData =
            (response.data.base && response.data.base[0]) || {};
          userData.financeData =
            (response.data.finance && response.data.finance[0]) || {};
          userData.identityData =
            (response.data.identity && response.data.identity[0]) || {};
          userData.roleData =
            (response.data.role && response.data.role[0]) || {};
          overlay.hide();
        })
        .catch(function (error) {
          overlay.hide();
          alert.Error("ERROR", safeMessage(error));
        });
    }
    function getBankLists() {
      overlay.show();
      axios
        .get(common.DataService + "?qid=gen008")
        .then(function (response) {
          bankListData.value = (response.data && response.data.data) || [];
          overlay.hide();
        })
        .catch(function (error) {
          overlay.hide();
          alert.Error("ERROR", safeMessage(error));
        });
    }
    function updateUserProfile() {
      var updateFormData = {
        userid: userid.value,
        roleid: userData.baseData.roleid,
        first: userData.identityData.first,
        middle: userData.identityData.middle,
        last: userData.identityData.last,
        gender: userData.identityData.gender,
        email: userData.identityData.email,
        phone: userData.identityData.phone,
        bank_name: userData.financeData.bank_name,
        account_name: userData.financeData.account_name,
        account_no: userData.financeData.account_no,
        bank_code: userData.financeData.bank_code,
        bio_feature: "",
      };
      overlay.show();
      $.confirm({
        title: "WARNING!",
        content:
          "<p>Are you sure you want to Update the User? </p><br>Updating the User profile means you are changing the user permissions and details",
        buttons: {
          delete: {
            text: "Update Details",
            btnClass: "btn btn-warning mr-1",
            action: function () {
              axios
                .post(
                  common.DataService + "?qid=006",
                  JSON.stringify(updateFormData),
                )
                .then(function (response) {
                  if (response.data.result_code == "200") {
                    overlay.hide();
                    bus.emit("g-event-update-user", {});
                    userDetails.value = true;
                    alert.Success(
                      "SUCCESS",
                      response.data.total + " User Updated",
                    );
                  } else {
                    overlay.hide();
                    alert.Error("ERROR", "User De/Activation failed");
                  }
                })
                .catch(function (error) {
                  overlay.hide();
                  alert.Error("ERROR", safeMessage(error));
                });
            },
          },
          close: {
            text: "Cancel",
            btnClass: "btn btn-outline-secondary",
            action: function () {
              overlay.hide();
            },
          },
        },
      });
    }
    function getRoleList() {
      overlay.show();
      axios
        .get(common.DataService + "?qid=007")
        .then(function (response) {
          roleListData.value = (response.data && response.data.data) || [];
          overlay.hide();
        })
        .catch(function (error) {
          overlay.hide();
          alert.Error("ERROR", safeMessage(error));
        });
    }
    function checkIfEmpty(data) {
      return data === null || data === "" ? "Nil" : data;
    }
    function userActivationDeactivation(actionid) {
      overlay.show();
      axios
        .post(common.DataService + "?qid=001", JSON.stringify([actionid]))
        .then(function (response) {
          overlay.hide();
          if (response.data.result_code == "200") {
            bus.emit("g-event-update-user", {});
            user_status.value = user_status.value == "1" ? 0 : 1;
            alert.Success("SUCCESS", "User De/Activation Successful");
          } else {
            alert.Error("ERROR", "User De/Activation failed");
          }
        })
        .catch(function (error) {
          overlay.hide();
          alert.Error("ERROR", safeMessage(error));
        });
    }
    function changeRole(event) {
      userData.baseData.role =
        event.target.options[event.target.options.selectedIndex].text;
    }
    function changeBank(event) {
      userData.financeData.bank_name =
        event.target.options[event.target.options.selectedIndex].text;
    }
    function downloadBadge(uid) {
      overlay.show();
      window.open(common.BadgeService + "?qid=002&e=" + uid, "_parent");
      overlay.hide();
    }

    onMounted(function () {
      bus.on("g-event-goto-page", gotoPageHandler);
    });
    onBeforeUnmount(function () {
      bus.off("g-event-goto-page", gotoPageHandler);
    });

    return {
      userid,
      userDetails,
      user_status,
      bankListData,
      roleListData,
      userData,
      gotoPageHandler,
      goToList,
      discardUpdate,
      getUserDetails,
      getBankLists,
      updateUserProfile,
      getRoleList,
      checkIfEmpty,
      userActivationDeactivation,
      changeRole,
      changeBank,
      downloadBadge,
      capitalize: fmtUtils.capitalize,
      displayDate: fmtUtils.displayDate,
    };
  },
  template: `
        <div class="row">
            <div class="col-md-12 col-sm-12 col-12 mb-1">
                <h2 class="content-header-title header-txt float-left mb-0">Users</h2>
                <div class="breadcrumb-wrapper">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../users">Home</a></li>
                        <li class="breadcrumb-item"><a href="javascript:void(0);" @click="goToList()">Users List</a></li>
                        <li v-if="userDetails" class="breadcrumb-item active">User Details</li>
                        <li v-else class="breadcrumb-item active">User Update</li>
                    </ol>
                </div>
            </div>

            <div class="col-xl-4 col-lg-5 col-md-5 order-1 order-md-0 sidebar-sticky">
                <div class="card">
                    <div class="card-body">
                        <div class="user-avatar-section">
                            <div class="d-flex align-items-center flex-column">
                                <img class="img-fluid rounded mt-3 mb-2" src="../app-assets/images/avatar.png" height="110" width="110" alt="User avatar">
                                <div class="user-info text-center">
                                    <h4 v-html="userData.baseData.loginid"></h4>
                                    <span class="badge bg-light-primary" v-html="userData.baseData.role"></span>
                                </div>
                            </div>
                        </div>
                        <div class="fw-bolder border-bottom font-small-2 pb-20 mb-1 mt-1 text-center">{{ userData.baseData.geo_string }}</div>
                        <div class="info-container">
                            <ul class="list-unstyled pl-2">
                                <li class="mb-75"><span class="fw-bolder me-25">Username:</span><span v-html="userData.baseData.username"></span></li>
                                <li class="mb-75"><span class="fw-bolder me-25">User Group:</span><span v-html="userData.baseData.user_group"></span></li>
                                <li class="mb-75"><span class="fw-bolder me-25">Status:</span><span class="badge" :class="user_status==1 ? 'bg-light-success' : 'bg-light-danger'">{{ user_status==1 ? 'Active' : 'Inactive' }}</span></li>
                            </ul>
                            <div class="justify-content-center pt-2 form-group">
                                <button class="btn btn-primary mb-1 form-control suspend-user waves-effect" @click="downloadBadge(userid)"><i class="feather icon-download"></i> Download Badge</button>
                                <button v-if="userDetails" @click="userDetails = false" class="btn btn-outline-primary mb-1 form-control suspend-user waves-effect"><i class="feather icon-edit-2"></i> Edit</button>
                                <button class="btn form-control suspend-user waves-effect" :class="user_status == 1 ? 'btn-danger' : 'btn-success'" @click="userActivationDeactivation(userid)"><i class="feather" :class="user_status == 1 ? 'icon-user-x' : 'icon-user-check'"></i> {{ user_status==1 ? ' Deactivate' : ' Activate' }}</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-8 col-lg-7 col-md-7 order-0 order-md-1">
                <div v-if="userDetails">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title mb-50">Details</h4>
                            <button class="btn btn-primary btn-sm waves-effect waves-float waves-light" @click="userDetails = false">
                                <i class="feather icon-edit-2"></i> <span> Edit</span>
                            </button>
                        </div>
                        <div class="card-body row">
                            <div class="col-12">
                                <table class="table">
                                    <tr>
                                        <td class="user-detail-txt"><label class="d-block text-primary">Firstname</label>{{ checkIfEmpty(userData.identityData.first) }}</td>
                                        <td class="user-detail-txt"><label class="d-block text-primary">Middle</label>{{ checkIfEmpty(userData.identityData.middle) }}</td>
                                    </tr>
                                    <tr>
                                        <td class="user-detail-txt"><label class="d-block text-primary">Lastname</label>{{ checkIfEmpty(userData.identityData.last) }}</td>
                                        <td class="user-detail-txt"><label class="d-block text-primary">Gender</label>{{ checkIfEmpty(userData.identityData.gender) }}</td>
                                    </tr>
                                    <tr>
                                        <td class="user-detail-txt"><label class="d-block text-primary">Phone No</label>{{ checkIfEmpty(userData.identityData.phone) }}</td>
                                        <td class="user-detail-txt"><label class="d-block text-primary">Email</label>{{ checkIfEmpty(userData.identityData.email) }}</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header"><h4 class="card-title mb-50">Finance</h4></div>
                        <div class="card-body row">
                            <div class="col-12">
                                <table class="table">
                                    <tr><td colspan="2" class="user-detail-txt"><label class="d-block text-primary">Account Name</label>{{ checkIfEmpty(userData.financeData.account_name) }}</td></tr>
                                    <tr>
                                        <td class="user-detail-txt"><label class="d-block text-primary">Account Number</label>{{ checkIfEmpty(userData.financeData.account_no) }}</td>
                                        <td class="user-detail-txt"><label class="d-block text-primary">Bank Name</label>{{ checkIfEmpty(userData.financeData.bank_name) }}</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header"><h4 class="card-title mb-50">Role</h4></div>
                        <div class="card-body row">
                            <div class="col-12">
                                <table class="table">
                                    <tr><td colspan="2" class="user-detail-txt"><label class="d-block text-primary">Role</label>{{ checkIfEmpty(userData.baseData.role) }}</td></tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <form method="POST" @submit.stop.prevent="updateUserProfile()" v-else>
                    <div class="card">
                        <div class="card-header"><h4 class="card-title mb-50">Details</h4></div>
                        <div class="card-body row">
                            <div class="col-6"><div class="form-group"><label>First Name</label><input type="text" id="firstname" v-model="userData.identityData.first" class="form-control firstname" placeholder="First Name" name="firstname" /></div></div>
                            <div class="col-6"><div class="form-group"><label>Middle Name</label><input type="text" id="middlename" v-model="userData.identityData.middle" class="form-control middlename" placeholder="Middle Name" name="middlename" /></div></div>
                            <div class="col-6"><div class="form-group"><label>Lastname</label><input type="text" id="lastname" v-model="userData.identityData.last" class="form-control lastname" placeholder="Last Name" name="lastname" /></div></div>
                            <div class="col-6"><div class="form-group"><label>Gender</label><select name="gender" v-model="userData.identityData.gender" class="form-control"><option>Male</option><option>Female</option></select></div></div>
                            <div class="col-6"><div class="form-group"><label>Phone No</label><input type="text" id="phoneno" v-model="userData.identityData.phone" class="form-control phoneno" placeholder="Phone No" name="phoneno" /></div></div>
                            <div class="col-6"><div class="form-group"><label>Email</label><input type="email" id="email" v-model="userData.identityData.email" class="form-control email" placeholder="Email" name="email" /></div></div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header"><h4 class="card-title mb-50">Finance</h4></div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-12"><div class="form-group"><label>Account Name</label><input type="text" id="account_name" v-model="userData.financeData.account_name" class="form-control account_name" placeholder="Account Name" name="account_name" /></div></div>
                                <div class="col-6"><div class="form-group"><label>Account Number</label><input type="text" id="account_no" v-model="userData.financeData.account_no" class="form-control account_no" placeholder="Account Number" name="account_no" /></div></div>
                                <div class="col-6"><div class="form-group"><label>Bank Name</label>
                                    <select name="bank_code" v-model="userData.financeData.bank_code" @change="changeBank($event)" class="form-control bank_code select2">
                                        <option v-for="b in bankListData" :value="b.bank_code" :key="b.bank_code">{{ b.bank_name }}</option>
                                    </select>
                                </div></div>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header"><h4 class="card-title mb-50">Role</h4></div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-12"><div class="form-group"><label>Role</label>
                                    <select name="role" v-model="userData.baseData.roleid" @change="changeRole($event)" class="form-control role select2">
                                        <option v-for="r in roleListData" :value="r.roleid" :key="r.roleid">{{ r.role }}</option>
                                    </select>
                                </div></div>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-6"><button type="button" @click="discardUpdate()" class="btn btn-outline-secondary form-control mt-2 waves-effect">Cancel</button></div>
                        <div class="col-6"><button class="btn btn-primary form-control mt-2 waves-effect waves-float waves-light">Update Details</button></div>
                    </div>
                </form>
            </div>
        </div>
    `,
};

useApp({ template: `<div><page-body/></div>` })
  .component("page-body", PageBody)
  .component("wallet_list", WalletList)
  .component("mobilization_details", MobilizationDetails)
  .mount("#app");
