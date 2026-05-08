/**
 * SMC / Child Registry — Vue 3 Composition API in place.
 * Two components — page-body and child_list.
 *
 * qid=701 paginated child registry with multi-field filters
 * (HH token / HH name / HH phone / beneficiary id / child name /
 * registration date / geo). Excel export round-trip.
 */

const { ref, reactive, onMounted, onBeforeUnmount } = Vue;
const { useApp, useFormat, bus, safeMessage } = window.utils;

const PageBody = {
    setup() {
        const page = ref('list');
        function gotoPageHandler(data) { page.value = data && data.page; }
        onMounted(function () { bus.on('g-event-goto-page', gotoPageHandler); });
        onBeforeUnmount(function () { bus.off('g-event-goto-page', gotoPageHandler); });
        return { page };
    },
    template: `
        <div>
            <div class="content-body">
                <div v-show="page == 'list'"><child_list/></div>
            </div>
        </div>
    `,
};

const ChildList = {
    setup() {
        const fmtUtils = useFormat();

        const url = ref(window.common && window.common.BadgeService);
        const tableData = ref([]);
        const geoData = ref([]);
        const userRole = reactive({ currentUserRole: '', currentUserid: '' });
        const checkToggle = ref(false);
        const filterState = ref(false);
        const filters = ref(false);
        const tableOptions = reactive({
            total: 1, pageLength: 1, perPage: 10, currentPage: 1,
            orderDir: 'desc', orderField: 0, limitStart: 0,
            isNext: false, isPrev: false,
            aLength: [10, 20, 50, 100, 150, 200],
            filterParam: {
                hh_token: '', hoh_name: '', hoh_phone: '',
                beneficiary_id: '', name: '', gender: '',
                dob: '', created: '', updated: '',
                geo_level: '', geo_level_id: '', geo_string: '',
            },
        });

        function reloadUserListOnUpdate() { paginationDefault(); loadTableData(); }
        function loadTableData() {
            overlay.show();
            axios.get(
                common.TableService +
                '?qid=701&draw=' + tableOptions.currentPage +
                '&order_column=' + tableOptions.orderField +
                '&length=' + tableOptions.perPage +
                '&start=' + tableOptions.limitStart +
                '&order_dir=' + tableOptions.orderDir +
                '&glv=' + tableOptions.filterParam.geo_level +
                '&hht=' + tableOptions.filterParam.hh_token +
                '&gid=' + tableOptions.filterParam.geo_level_id +
                '&hhn=' + tableOptions.filterParam.hoh_name +
                '&hhp=' + tableOptions.filterParam.hoh_phone +
                '&chi=' + tableOptions.filterParam.beneficiary_id +
                '&chn=' + tableOptions.filterParam.name +
                '&dob=' + tableOptions.filterParam.dob +
                '&rda=' + tableOptions.filterParam.created
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
                    alert.Error('ERROR', safeMessage(error));
                });
        }

        function selectAll()  { for (var i = 0; i < tableData.value.length; i++) tableData.value[i].pick = true; }
        function uncheckAll() { for (var i = 0; i < tableData.value.length; i++) tableData.value[i].pick = false; }
        function selectToggle() {
            if (checkToggle.value === false) { selectAll(); checkToggle.value = true; }
            else                              { uncheckAll(); checkToggle.value = false; }
        }
        function checkedBg(p) { return p != '' ? 'bg-select' : ''; }
        function toggleFilter() {
            if (filterState.value === false) filters.value = false;
            return (filterState.value = !filterState.value);
        }
        function paginationDefault() {
            tableOptions.pageLength = Math.ceil(tableOptions.total / tableOptions.perPage);
            tableOptions.limitStart = Math.ceil((tableOptions.currentPage - 1) * tableOptions.perPage);
            tableOptions.isNext = tableOptions.currentPage < tableOptions.pageLength;
            tableOptions.isPrev = tableOptions.currentPage > 1;
        }
        function nextPage() { tableOptions.currentPage += 1; paginationDefault(); loadTableData(); }
        function prevPage() { tableOptions.currentPage -= 1; paginationDefault(); loadTableData(); }
        function currentPage() {
            paginationDefault();
            if (tableOptions.currentPage < 1)                            alert.Error('ERROR', "The Page requested doesn't exist");
            else if (tableOptions.currentPage > tableOptions.pageLength) alert.Error('ERROR', "The Page requested doesn't exist");
            else                                                         loadTableData();
        }
        function changePerPage(val) {
            var maxPerPage = Math.ceil(tableOptions.total / val);
            if (maxPerPage < tableOptions.currentPage) tableOptions.currentPage = maxPerPage;
            tableOptions.perPage = val;
            paginationDefault();
            loadTableData();
        }
        function sort(col) {
            if (tableOptions.orderField === col) tableOptions.orderDir = tableOptions.orderDir === 'asc' ? 'desc' : 'asc';
            else                                  tableOptions.orderField = col;
            paginationDefault();
            loadTableData();
        }
        function applyFilter() {
            var checkFill = 0;
            ['geo_level', 'geo_level_id', 'hh_token', 'hoh_name', 'hoh_phone',
             'beneficiary_id', 'name', 'gender', 'dob', 'created', 'updated'].forEach(function (k) {
                if (tableOptions.filterParam[k] != '') checkFill++;
            });
            if (checkFill > 0) {
                toggleFilter();
                filters.value = true;
                paginationDefault();
                loadTableData();
            } else {
                alert.Error('ERROR', 'Invalid required data');
            }
        }
        function removeSingleFilter(column_name) {
            tableOptions.filterParam[column_name] = '';
            if (column_name == 'geo_level' || column_name == 'geo_level_id') {
                tableOptions.filterParam.geo_level = '';
                tableOptions.filterParam.geo_level_id = '';
            }
            var g = 0;
            for (var k in tableOptions.filterParam) {
                if (tableOptions.filterParam[k] != '') g++;
            }
            if (g == 0) filters.value = false;
            paginationDefault();
            loadTableData();
        }
        function clearAllFilter() {
            filters.value = false;
            ['geo_level', 'geo_level_id', 'hh_token', 'hoh_name', 'hoh_phone',
             'beneficiary_id', 'name', 'gender', 'dob', 'created', 'updated'].forEach(function (k) {
                tableOptions.filterParam[k] = '';
            });
            paginationDefault();
            loadTableData();
        }
        function refreshData() { paginationDefault(); loadTableData(); }
        function getGeoLocation() {
            overlay.show();
            axios.get(common.DataService + '?qid=gen009')
                .then(function (response) {
                    geoData.value = (response.data && response.data.data) || [];
                    overlay.hide();
                })
                .catch(function (error) {
                    overlay.hide();
                    alert.Error('ERROR', safeMessage(error));
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
        function displayDayMonthYear(d) {
            var date = new Date(d);
            return date.toLocaleString('en-us', { year: 'numeric', month: 'long', day: 'numeric' });
        }
        function calculateTotalMonths(dob) {
            var d = new Date(dob);
            var c = new Date();
            var months = (c.getFullYear() - d.getFullYear()) * 12;
            months -= d.getMonth() + 1;
            months += c.getMonth() + 1;
            if (c.getDate() < d.getDate()) months--;
            return months + ' Month' + (months > 1 ? 's' : '') + ' Old';
        }

        onMounted(function () {
            getGeoLocation();
            loadTableData();
            bus.on('g-event-update-user', reloadUserListOnUpdate);
            try {
                $('.select2').each(function () {
                    var $this = $(this);
                    $this.wrap('<div class="position-relative"></div>');
                    $this.select2({
                        dropdownAutoWidth: true, width: '100%',
                        dropdownParent: $this.parent(),
                    }).on('change', function () { setLocation(this.value); });
                });
                $('.select2-selection__arrow').html('<i class="feather icon-chevron-down"></i>');
                $('.date').flatpickr({ altInput: true, altFormat: 'F j, Y', dateFormat: 'Y-m-d' });
            } catch (e) {}
        });
        onBeforeUnmount(function () {
            bus.off('g-event-update-user', reloadUserListOnUpdate);
        });

        return {
            url, tableData, geoData, userRole, checkToggle, filterState, filters,
            tableOptions,
            reloadUserListOnUpdate, loadTableData,
            selectAll, uncheckAll, selectToggle, checkedBg, toggleFilter,
            paginationDefault, nextPage, prevPage, currentPage,
            changePerPage, sort, applyFilter, removeSingleFilter, clearAllFilter,
            refreshData, getGeoLocation, setLocation,
            displayDayMonthYear, calculateTotalMonths,
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
                        <li class="breadcrumb-item active">Child Registry</li>
                    </ol>
                </div>
            </div>

            <div class="col-md-4 col-sm-12 col-12 text-md-right text-right d-md-block">
                <div class="btn-group">
                    <button type="button" class="btn btn-outline-primary round searchBtn" @click="refreshData()" data-toggle="tooltip" data-placement="top" title="Refresh"><i class="feather icon-refresh-cw"></i></button>
                    <button type="button" class="btn btn-outline-primary round searchBtn" @click="toggleFilter()" data-toggle="tooltip" data-placement="top" title="Filter"><i class="feather" :class="filterState ? 'icon-x' : 'icon-filter'"></i></button>
                </div>
            </div>

            <div class="col-12" v-if="filters">
                <div class="col-12 filter-bar">
                    <template v-for="(filterParam, i) in tableOptions.filterParam" :key="i">
                        <span class="badge badge-dark filter-box" v-if="String(filterParam).length > 0" @click="removeSingleFilter(i)">{{ capitalize(i) }}: {{ filterParam }} <i class="feather icon-x"></i></span>
                    </template>
                    <a href="#" class="float-right clear-filter" @click="clearAllFilter()">Clear</a>
                </div>
            </div>

            <div class="col-12 mt-1">
                <div class="card custom-select-down">
                    <div class="card-body py-1" v-show="filterState">
                        <form id="filterForm">
                            <div class="row">
                                <div class="col-12 col-sm-6 col-md-4 col-lg-3"><div class="form-group"><label>Head of Household Token</label><input type="text" v-model="tableOptions.filterParam.hh_token" class="form-control" placeholder="Household Token" /></div></div>
                                <div class="col-12 col-sm-6 col-md-4 col-lg-3"><div class="form-group"><label>Head of Household Name</label><input type="text" v-model="tableOptions.filterParam.hoh_name" class="form-control" placeholder="Household Name" /></div></div>
                                <div class="col-12 col-sm-6 col-md-4 col-lg-3"><div class="form-group"><label>Household Phone No</label><input type="text" v-model="tableOptions.filterParam.hoh_phone" class="form-control" placeholder="Household Phone No" /></div></div>
                                <div class="col-12 col-sm-6 col-md-4 col-lg-3"><div class="form-group"><label>Beneficiary ID</label><input type="text" v-model="tableOptions.filterParam.beneficiary_id" class="form-control" placeholder="Beneficiary ID" /></div></div>
                                <div class="col-12 col-sm-6 col-md-4 col-lg-3"><div class="form-group"><label>Beneficiary Name</label><input type="text" v-model="tableOptions.filterParam.name" class="form-control" placeholder="Beneficiary Name" /></div></div>
                                <div class="col-12 col-sm-6 col-md-4 col-lg-3"><div class="form-group date_filter"><label>Registration Date</label><input type="text" id="reg_date" v-model="tableOptions.filterParam.created" class="form-control date" placeholder="Registration Date" /></div></div>
                                <div class="col-12 col-sm-8 col-md-9 col-lg-4">
                                    <div class="form-group">
                                        <label>Geo Location</label>
                                        <select class="select2 form-control" @change="setLocation()">
                                            <option value="">Select Geo Location</option>
                                            <option v-for="(g, i) in geoData" :key="i" :value="i">{{ g.geo_string }}</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-12 col-sm-4 col-md-3 col-lg-2"><div class="form-group mt-2 text-right"><button type="button" class="btn btn-md btn-primary" @click="applyFilter()">Apply Filters</button></div></div>
                            </div>
                        </form>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th @click="sort(1)">Head of Household</th>
                                    <th @click="sort(4)">Child Name</th>
                                    <th @click="sort(6)">Date of Birth</th>
                                    <th @click="sort(5)">Gender</th>
                                    <th @click="sort(9)">Health Facility</th>
                                    <th @click="sort(7)">Created Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(g, i) in tableData" :key="g.beneficiary_id || i">
                                    <td>
                                        <div class="d-flex flex-column">
                                            <span class="fw-bolder">{{ g.hoh_name }}</span>
                                            <small class="text-primary">{{ g.hoh_phone }}</small>
                                            <span class="badge badge-light-success">{{ g.hh_token }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column">
                                            <span class="fw-bolder">{{ g.name }}</span>
                                            <small><span class="badge badge-light-primary">{{ g.beneficiary_id }}</span></small>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column">
                                            <span class="fw-bolder">{{ displayDayMonthYear(g.dob) }}</span>
                                            <span class="badge badge-light-success">{{ calculateTotalMonths(g.dob) }}</span>
                                        </div>
                                    </td>
                                    <td>{{ capitalize(g.gender) }}</td>
                                    <td>
                                        <div class="d-flex flex-column">
                                            <span class="fw-bolder">{{ g.geo_name }}</span>
                                            <small class="text-muted">{{ g.geo_string }}</small>
                                        </div>
                                    </td>
                                    <td>{{ displayDayMonthYear(g.created) }}</td>
                                </tr>
                                <tr v-if="tableData.length == 0"><td class="text-center pt-2" colspan="6"><small>No Data Found</small></td></tr>
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
    .component('page-body', PageBody)
    .component('child_list', ChildList)
    .mount('#app');
