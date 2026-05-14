/**
 * Admin / Log submodule — Vue 3 Composition API in place.
 * System Activity Log view (qid=501).
 */

const { ref, reactive, computed, onMounted } = Vue;
const { useApp, useFormat, safeMessage } = window.utils;

/* ------------------------------------------------------------------ */
const PageBody = {
    setup() {
        const page = ref('home');
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
        const checkToggle = ref(false);
        const filterState = ref(false);
        const filters = ref(false);
        const userGroup = ref([]);

        const tableOptions = reactive({
            total: 1,
            pageLength: 1,
            perPage: 10,
            currentPage: 1,
            orderDir: 'desc',
            orderField: 0,
            limitStart: 0,
            isNext: false,
            isPrev: false,
            aLength: [10, 20, 50, 100],
            filterParam: {
                userid: '',
                loginid: '',
                platform: '',
                module: '',
                result: '',
            },
        });

        const loadTableData = () => {
            overlay.show();
            var url = common.TableService;
            axios
                .get(
                    url +
                        '?qid=501&draw=' + tableOptions.currentPage +
                        '&order_column=' + tableOptions.orderField +
                        '&length=' + tableOptions.perPage +
                        '&start=' + tableOptions.limitStart +
                        '&order_dir=' + tableOptions.orderDir +
                        '&uid=' + tableOptions.filterParam.userid +
                        '&lid=' + tableOptions.filterParam.loginid +
                        '&pla=' + tableOptions.filterParam.platform +
                        '&mod=' + tableOptions.filterParam.module +
                        '&res=' + tableOptions.filterParam.result
                )
                .then(response => {
                    var d = response && response.data;
                    tableData.value = Array.isArray(d && d.data) ? d.data : [];
                    tableOptions.total = (d && d.recordsTotal) || 0;
                    if (tableOptions.currentPage == 1) paginationDefault();
                    overlay.hide();
                })
                .catch(error => {
                    overlay.hide();
                    alert.Error('ERROR', safeMessage(error));
                });
        }

        const selectAll = () => {
            for (var i = 0; i < tableData.value.length; i++) tableData.value[i].pick = true;
        }
        const uncheckAll = () => {
            for (var i = 0; i < tableData.value.length; i++) tableData.value[i].pick = false;
        }
        const selectToggle = () => {
            if (checkToggle.value === false) { selectAll(); checkToggle.value = true; }
            else                              { uncheckAll(); checkToggle.value = false; }
        }
        const checkedBg = (pickOne) => { return pickOne != '' ? 'bg-select' : ''; };

        const toggleFilter = () => {
            if (filterState.value === false) filters.value = false;
            return (filterState.value = !filterState.value);
        }

        const selectedItems = () => {
            return tableData.value.filter(r => r.pick);
        }
        const selectedID = () => {
            return tableData.value.filter(r => r.pick).map(r => r.userid);
        }

        const paginationDefault = () => {
            tableOptions.pageLength = Math.ceil(tableOptions.total / tableOptions.perPage);
            tableOptions.limitStart = Math.ceil((tableOptions.currentPage - 1) * tableOptions.perPage);
            tableOptions.isNext = tableOptions.currentPage < tableOptions.pageLength;
            tableOptions.isPrev = tableOptions.currentPage > 1;
        }

        const nextPage = () => { tableOptions.currentPage += 1; paginationDefault(); loadTableData(); };
        const prevPage = () => { tableOptions.currentPage -= 1; paginationDefault(); loadTableData(); };
        const currentPage = () => {
            paginationDefault();
            if (tableOptions.currentPage < 1)                            alert.Error('ERROR', "The Page requested doesn't exist");
            else if (tableOptions.currentPage > tableOptions.pageLength) alert.Error('ERROR', "The Page requested doesn't exist");
            else                                                         loadTableData();
        }
        const changePerPage = (val) => {
            var maxPerPage = Math.ceil(tableOptions.total / val);
            if (maxPerPage < tableOptions.currentPage) tableOptions.currentPage = maxPerPage;
            tableOptions.perPage = val;
            paginationDefault();
            loadTableData();
        }
        const sort = (col) => {
            if (tableOptions.orderField === col) {
                tableOptions.orderDir = tableOptions.orderDir === 'asc' ? 'desc' : 'asc';
            } else {
                tableOptions.orderField = col;
            }
            paginationDefault();
            loadTableData();
        }

        const applyFilter = () => {
            var checkFill = 0;
            checkFill += tableOptions.filterParam.loginid  != '' ? 1 : 0;
            checkFill += tableOptions.filterParam.userid   != '' ? 1 : 0;
            checkFill += tableOptions.filterParam.platform != '' ? 1 : 0;
            checkFill += tableOptions.filterParam.module   != '' ? 1 : 0;
            checkFill += tableOptions.filterParam.result   != '' ? 1 : 0;
            if (checkFill > 0) {
                toggleFilter();
                filters.value = true;
                paginationDefault();
                loadTableData();
            } else {
                alert.Error('ERROR', 'Invalid required data');
            }
        }
        const removeSingleFilter = (column_name) => {
            tableOptions.filterParam[column_name] = '';
            var g = 0;
            for (var k in tableOptions.filterParam) {
                if (tableOptions.filterParam[k] != '') g++;
            }
            if (g == 0) filters.value = false;
            paginationDefault();
            loadTableData();
        }
        const clearAllFilter = () => {
            filters.value = false;
            tableOptions.filterParam.loginid = '';
            tableOptions.filterParam.userid = '';
            tableOptions.filterParam.platform = '';
            tableOptions.filterParam.module = '';
            tableOptions.filterParam.result = '';
            paginationDefault();
            loadTableData();
        }
        const refreshData = () => { paginationDefault(); loadTableData(); };

        // Pre-existing v2 template references @focus="loadAuto()" on the
        // module input — the v2 component never defined loadAuto. Stub it
        // as a no-op so Vue 3 doesn't warn on focus.
        const loadAuto = () => { /* no-op (pre-existing v2 binding without implementation) */ };

        onMounted(() => {
            loadTableData();
        });

        return {
            // state
            tableData, checkToggle, filterState, filters, userGroup, tableOptions,
            // methods
            loadTableData, selectAll, uncheckAll, selectToggle, checkedBg,
            toggleFilter, selectedItems, selectedID,
            nextPage, prevPage, currentPage, paginationDefault, changePerPage,
            sort, applyFilter, removeSingleFilter, clearAllFilter, refreshData,
            loadAuto,
            // utility methods
            capitalize: fmtUtils.capitalize,
            formatNumber: fmtUtils.formatNumber,
            displayDate: fmtUtils.displayDate,
            fmt: fmtUtils.fmt,
        };
    },
    template: `
        <div class="row" id="basic-table">

            <div class="col-md-8 col-sm-12 col-12 mb-0">
                <h2 class="content-header-title header-txt float-left mb-0">System Admin</h2>
                <div class="breadcrumb-wrapper">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../admin/log">Home</a></li>
                        <li class="breadcrumb-item active">Activity Log</li>
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
                <div class="card">
                    <div class="card-body py-1" v-show="filterState">
                        <form id="filterForm">
                            <div class="row">
                                <div class="col-12 col-sm-6 col-md-4 col-lg-4">
                                    <div class="form-group">
                                        <label>Activity Result</label>
                                        <select v-model="tableOptions.filterParam.result" class="form-control active">
                                            <option value="">All</option>
                                            <option value="success">Success</option>
                                            <option value="failed">Failed</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-12 col-sm-6 col-md-4 col-lg-4">
                                    <div class="form-group">
                                        <label>Login ID</label>
                                        <input type="text" v-model="tableOptions.filterParam.loginid" class="form-control login-id" id="login-id" placeholder="Login ID" name="loginid" />
                                    </div>
                                </div>
                                <div class="col-12 col-sm-6 col-md-4 col-lg-4">
                                    <div class="form-group">
                                        <label>Platform</label>
                                        <select v-model="tableOptions.filterParam.platform" class="form-control active">
                                            <option value="">All</option>
                                            <option value="web">Web</option>
                                            <option value="pos">POS</option>
                                            <option value="mobile">Mobile</option>
                                            <option value="pos|mobile">POS|Mobile</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-12 col-sm-6 col-md-4 col-lg-4">
                                    <div class="form-group autocomplete">
                                        <label>Module</label>
                                        <input autocomplete="off" type="text" @focus="loadAuto()" id="user_group" v-model="tableOptions.filterParam.module" class="form-control module" placeholder="Module" name="module" />
                                    </div>
                                </div>
                                <div class="col-12 col-sm-6 col-md-4 col-lg-4">
                                    <div class="form-group">
                                        <label>User ID</label>
                                        <input type="number" id="phoneno" v-model="tableOptions.filterParam.userid" class="form-control userid" placeholder="User ID" name="userid" />
                                    </div>
                                </div>
                                <div class="col-12 col-sm-6 col-md-4 col-lg-4">
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
                                    <th @click="sort(0)" width="60px">
                                        #
                                        <i class="feather icon-chevron-up sort-up"   :class="(tableOptions.orderField == 0 && tableOptions.orderDir =='asc' )? 'active-sort':''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 0 && tableOptions.orderDir =='desc')? 'active-sort':''"></i>
                                    </th>
                                    <th @click="sort(9)">
                                        Date and Time
                                        <i class="feather icon-chevron-up sort-up"   :class="(tableOptions.orderField == 9 && tableOptions.orderDir =='asc' )? 'active-sort':''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 9 && tableOptions.orderDir =='desc')? 'active-sort':''"></i>
                                    </th>
                                    <th @click="sort(2)">
                                        Account
                                        <i class="feather icon-chevron-up sort-up"   :class="(tableOptions.orderField == 2 && tableOptions.orderDir =='asc' )? 'active-sort':''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 2 && tableOptions.orderDir =='desc')? 'active-sort':''"></i>
                                    </th>
                                    <th @click="sort(7)">
                                        Description
                                        <i class="feather icon-chevron-up sort-up"   :class="(tableOptions.orderField == 7 && tableOptions.orderDir =='asc' )? 'active-sort':''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 7 && tableOptions.orderDir =='desc')? 'active-sort':''"></i>
                                    </th>
                                    <th @click="sort(5)">
                                        Module
                                        <i class="feather icon-chevron-up sort-up"   :class="(tableOptions.orderField == 5 && tableOptions.orderDir =='asc' )? 'active-sort':''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 5 && tableOptions.orderDir =='desc')? 'active-sort':''"></i>
                                    </th>
                                    <th @click="sort(8)">
                                        Status
                                        <i class="feather icon-chevron-up sort-up"   :class="(tableOptions.orderField == 8 && tableOptions.orderDir =='asc' )? 'active-sort':''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 8 && tableOptions.orderDir =='desc')? 'active-sort':''"></i>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(g, i) in tableData" :key="g.id || i">
                                    <td>{{ g.id }}</td>
                                    <td>{{ g.created }}</td>
                                    <td>
                                        <div class="d-flex justify-content-left align-items-center">
                                            <div class="d-flex flex-column">
                                                <span class="user_name text-truncate text-body">
                                                    <span class="fw-bolder" v-text="g.fullname ? capitalize(g.fullname) : 'Unknown User'"></span>
                                                </span>
                                                <small class="emp_post text-primary" v-html="g.loginid ? g.loginid : ''"></small>
                                                <small class="emp_post text-info"    v-html="g.platform ? g.platform : ''"></small>
                                                <small class="emp_post text-muted"   v-html="g.ip ? g.ip : ''"></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{ g.description }}</td>
                                    <td>{{ capitalize(g.module) }}</td>
                                    <td><span class="badge rounded-pill font-small-1" :class="g.result=='success'? 'bg-success' : 'bg-danger'">{{ g.result=='success'? 'Success' : 'Failed' }}</span></td>
                                </tr>
                                <tr v-if="tableData.length === 0">
                                    <td class="text-center pt-2" colspan="6"><small>No Activity Log</small></td>
                                </tr>
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
                </div>
            </div>
        </div>
    `,
};

useApp({ template: `<div><page-body/></div>` })
    .component('page-body', PageBody)
    .component('sample_table', SampleTable)
    .mount('#app');
