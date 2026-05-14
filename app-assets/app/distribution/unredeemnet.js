/**
 * Distribution / Unredeemed Net — Vue 3 Composition API in place.
 * Two components — page-body and unredeemed_search.
 *
 * Paginated list of unredeemed e-Tokens (qid=402) with multi-field
 * filter, geo select2, flatpickr date filter, and per-row distribution
 * details modal. Excel export round-trip preserved.
 */

const { ref, reactive, onMounted, onBeforeUnmount } = Vue;
const { useApp, useFormat, bus, safeMessage } = window.utils;

const PageBody = {
    setup() {
        const page = ref('list');
        const permission = ref(
            (typeof getPermission === 'function')
                ? (getPermission(typeof per !== 'undefined' ? per : null, 'distribution') || { permission_value: 0 })
                : { permission_value: 0 }
        );
        const gotoPageHandler = (data) => { page.value = data && data.page; };
        onMounted(() => { bus.on('g-event-goto-page', gotoPageHandler); });
        onBeforeUnmount(() => { bus.off('g-event-goto-page', gotoPageHandler); });
        return { page, permission };
    },
    template: `
        <div>
            <div class="content-body">
                <div v-show="page == 'list'">
                    <unredeemed_search v-if="permission.permission_value > 1"/>
                    <div class="alert alert-danger" v-else>
                        <div class="alert-body"><strong>Access Denied!</strong> You don't have permission to access this page.</div>
                    </div>
                </div>
            </div>
        </div>
    `,
};

const UnredeemedSearch = {
    setup() {
        const fmtUtils = useFormat();

        const url = ref(window.common && window.common.BadgeService);
        const tableData = ref([]);
        const permission = ref(
            (typeof getPermission === 'function')
                ? (getPermission(typeof per !== 'undefined' ? per : null, 'distribution') || { permission_value: 0 })
                : { permission_value: 0 }
        );
        const tableDetails = reactive({
            hhid: '', hoh_first: '', hoh_last: '', hoh_phone: '', hoh_gender: '',
            family_size: '', hod_mother: '', allocated_net: '', sleeping_space: '',
            adult_female: '', adult_male: '', children: '',
            etoken_serial: '', etoken_pin: '',
            geo_level: '', geo_level_id: '', geo_string: '',
            mobilization_date: '', location_description: '',
        });
        const id = ref(0);
        const geoData = ref([]);
        const checkToggle = ref(false);
        const filterState = ref(false);
        const filters = ref(false);
        const tableOptions = reactive({
            total: 1, pageLength: 1, perPage: 10, currentPage: 1,
            orderDir: 'desc', orderField: 0, limitStart: 0,
            isNext: false, isPrev: false,
            aLength: [10, 20, 50, 100, 150, 200],
            filterParam: {
                loginid: '', hh_phone_no: '', etoken_serial: '', etoken_pin: '',
                mobilization_date: '', geo_level: '', geo_level_id: '', geo_string: '',
            },
        });

        const reloadUserListOnUpdate = () => { paginationDefault(); loadTableData(); };
        const loadTableData = () => {
            overlay.show();
            axios.get(
                common.TableService +
                '?qid=402&draw=' + tableOptions.currentPage +
                '&order_column=' + tableOptions.orderField +
                '&length=' + tableOptions.perPage +
                '&start=' + tableOptions.limitStart +
                '&order_dir=' + tableOptions.orderDir +
                '&glv=' + tableOptions.filterParam.geo_level +
                '&lgid=' + tableOptions.filterParam.loginid +
                '&gid=' + tableOptions.filterParam.geo_level_id +
                '&mdt=' + tableOptions.filterParam.mobilization_date +
                '&pph=' + tableOptions.filterParam.hh_phone_no +
                '&ets=' + tableOptions.filterParam.etoken_serial +
                '&etp=' + tableOptions.filterParam.etoken_pin
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

        const selectAll = () => { for (var i = 0; i < tableData.value.length; i++) tableData.value[i].pick = true; };
        const uncheckAll = () => { for (var i = 0; i < tableData.value.length; i++) tableData.value[i].pick = false; };
        const selectToggle = () => {
            if (checkToggle.value === false) { selectAll(); checkToggle.value = true; }
            else                              { uncheckAll(); checkToggle.value = false; }
        }
        const checkedBg = (p) => { return p != '' ? 'bg-select' : ''; };
        const toggleFilter = () => {
            if (filterState.value === false) {
                filters.value = false;
                try {
                    $('#mobilization_date').flatpickr({ altInput: true, altFormat: 'F j, Y', dateFormat: 'Y-m-d' }).clear();
                } catch (e) {}
            }
            return (filterState.value = !filterState.value);
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
            if (tableOptions.orderField === col) tableOptions.orderDir = tableOptions.orderDir === 'asc' ? 'desc' : 'asc';
            else                                  tableOptions.orderField = col;
            paginationDefault();
            loadTableData();
        }
        const applyFilter = () => {
            var checkFill = 0;
            checkFill += tableOptions.filterParam.loginid != '' ? 1 : 0;
            checkFill += tableOptions.filterParam.mobilization_date != '' ? 1 : 0;
            checkFill += tableOptions.filterParam.etoken_serial != '' ? 1 : 0;
            checkFill += tableOptions.filterParam.etoken_pin != '' ? 1 : 0;
            checkFill += tableOptions.filterParam.hh_phone_no != '' ? 1 : 0;
            checkFill += tableOptions.filterParam.geo_level != '' ? 1 : 0;
            checkFill += tableOptions.filterParam.geo_level_id != '' ? 1 : 0;
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
            if (column_name == 'geo_level' || column_name == 'geo_level_id') {
                tableOptions.filterParam.geo_level = '';
                tableOptions.filterParam.geo_level_id = '';
            }
            if (column_name == 'mobilization_date') {
                try { $('#mobilization_date').flatpickr({ altInput: true, altFormat: 'F j, Y', dateFormat: 'Y-m-d' }).clear(); } catch (e) {}
            }
            var g = 0;
            for (var k in tableOptions.filterParam) {
                if (tableOptions.filterParam[k] != '') g++;
            }
            if (g == 0) filters.value = false;
            paginationDefault();
            loadTableData();
        }
        const clearAllFilter = () => {
            try { $('#mobilization_date').flatpickr({ altInput: true, altFormat: 'F j, Y', dateFormat: 'Y-m-d' }).clear(); } catch (e) {}
            filters.value = false;
            tableOptions.filterParam.mobilization_date = '';
            tableOptions.filterParam.loginid = '';
            tableOptions.filterParam.geo_level = '';
            tableOptions.filterParam.geo_level_id = '';
            paginationDefault();
            loadTableData();
        }
        const refreshData = () => { paginationDefault(); loadTableData(); };
        const getGeoLocation = () => {
            overlay.show();
            axios.get(common.DataService + '?qid=gen009')
                .then(response => {
                    geoData.value = (response.data && response.data.data) || [];
                    overlay.hide();
                })
                .catch(error => {
                    overlay.hide();
                    alert.Error('ERROR', safeMessage(error));
                });
        }
        const setLocation = (select_index) => {
            var i = select_index || 0;
            var row = geoData.value[i];
            if (!row) return;
            tableOptions.filterParam.geo_level = row.geo_level;
            tableOptions.filterParam.geo_level_id = row.geo_level_id;
            tableOptions.filterParam.geo_string = row.title;
        }

        const exportMobilization = async () => {
            var qs =
                'qid=402&draw=' + tableOptions.currentPage +
                '&order_column=' + tableOptions.orderField +
                '&length=' + tableOptions.perPage +
                '&start=' + tableOptions.limitStart +
                '&order_dir=' + tableOptions.orderDir +
                '&glv=' + tableOptions.filterParam.geo_level +
                '&lgid=' + tableOptions.filterParam.loginid +
                '&gid=' + tableOptions.filterParam.geo_level_id +
                '&mdt=' + tableOptions.filterParam.mobilization_date +
                '&pph=' + tableOptions.filterParam.hh_phone_no +
                '&ets=' + tableOptions.filterParam.etoken_serial +
                '&etp=' + tableOptions.filterParam.etoken_pin;
            var filename =
                (tableOptions.filterParam.geo_string ? tableOptions.filterParam.geo_string : 'UnRedeemed ') + ' ' +
                (tableOptions.filterParam.loginid ? tableOptions.filterParam.loginid : 'UnRedeemed ') +
                ' Mobilization List';
            overlay.show();

            var count = await new Promise(resolve => {
                $.ajax({
                    url: common.DataService, type: 'POST', data: qs, dataType: 'json',
                    success: (data) => { resolve(data.total); },
                });
            });
            var downloadMax = (window.common && window.common.ExportDownloadLimit) || 25000;
            if (parseInt(count) > downloadMax) {
                alert.Error('Download Error', 'Unable to download data because it has exceeded download limit, download limit is ' + downloadMax);
            } else if (parseInt(count) == 0) {
                alert.Error('Download Error', 'No data found');
            } else {
                alert.Info('DOWNLOADING...', 'Downloading ' + count + ' record(s)');
                var outcome = await new Promise(resolve => {
                    $.ajax({
                        url: common.ExportService, type: 'POST', data: qs,
                        success: (data) => { resolve(data); },
                    });
                });
                var exportData = JSON.parse(outcome);
                if (window.Jhxlsx && typeof window.Jhxlsx.export === 'function') {
                    window.Jhxlsx.export(exportData, { fileName: filename });
                }
            }
            overlay.hide();
        }

        const showdistributionDetailsModal = (i) => {
            overlay.show();
            var row = tableData.value[i] || {};
            tableDetails.geo_string = row.geo_string;
            tableDetails.allocated_net = row.allocated_net;
            tableDetails.mobilization_date = row.collected_date;
            tableDetails.etoken_serial = row.etoken_serial;
            tableDetails.etoken_pin = row.etoken_pin;
            tableDetails.sleeping_space = row.sleeping_space;
            tableDetails.adult_female = row.adult_female;
            tableDetails.adult_male = row.adult_male;
            tableDetails.family_size = row.family_size;
            tableDetails.geo_level = row.geo_level;
            tableDetails.hoh_first = row.hoh_first;
            tableDetails.hoh_last = row.hoh_last;
            tableDetails.hoh_gender = row.hoh_gender;
            tableDetails.hoh_phone = row.hoh_phone;
            tableDetails.children = row.children;
            tableDetails.location_description = row.location_description;
            $('#distributionDetails').modal('show');
            overlay.hide();
        }
        const hidedistributionDetailsModal = () => {
            overlay.show();
            $('#distributionDetails').modal('hide');
            for (var k in tableDetails) tableDetails[k] = '';
            overlay.hide();
        }
        const checkIfEmpty = (data) => { return data === null || data === '' ? 'Nil' : data; };

        // Kick off data fetches in setup rather than onMounted — the
        // <unredeemed_search v-if="permission.permission_value > 1"> wrap
        // (combined with the parent's v-show) made onMounted fire too late
        // for the initial render to pick up the response. Setup-level calls
        // are independent of mount lifecycle and reliably populate tableData
        // on first paint.
        getGeoLocation();
        loadTableData();

        onMounted(() => {
            bus.on('g-event-update-user', reloadUserListOnUpdate);
            try {
                var select = $('.select2');
                select.each(function () {
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
        onBeforeUnmount(() => {
            bus.off('g-event-update-user', reloadUserListOnUpdate);
        });

        return {
            url, tableData, permission, tableDetails, id, geoData,
            checkToggle, filterState, filters, tableOptions,
            reloadUserListOnUpdate, loadTableData, selectAll, uncheckAll,
            selectToggle, checkedBg, toggleFilter,
            paginationDefault, nextPage, prevPage, currentPage,
            changePerPage, sort, applyFilter, removeSingleFilter,
            clearAllFilter, refreshData,
            getGeoLocation, setLocation, exportMobilization,
            showdistributionDetailsModal, hidedistributionDetailsModal,
            checkIfEmpty,
            capitalize: fmtUtils.capitalize,
            displayDate: fmtUtils.displayDate,
            formatNumber: fmtUtils.formatNumber,
        };
    },
    template: `
        <div class="row" id="basic-table">
            <div class="col-md-8 col-sm-12 col-12 mb-0">
                <h2 class="content-header-title header-txt float-left mb-0">Distribution</h2>
                <div class="breadcrumb-wrapper">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../distribution">Home</a></li>
                        <li class="breadcrumb-item active">Unredeemed e-Token</li>
                    </ol>
                </div>
            </div>

            <div class="col-md-4 col-sm-12 col-12 text-md-right text-right d-md-block">
                <div class="btn-group mr-1">
                    <button type="button" class="btn btn-outline-primary round searchBtn" @click="refreshData()" data-toggle="tooltip" data-placement="top" title="Refresh"><i class="feather icon-refresh-cw"></i></button>
                    <button type="button" class="btn btn-outline-primary round searchBtn" @click="toggleFilter()" data-toggle="tooltip" data-placement="top" title="Filter"><i class="feather" :class="filterState ? 'icon-x' : 'icon-filter'"></i></button>
                    <button class="btn btn-outline-primary round dropdown-toggle" type="button" data-toggle="dropdown">Actions</button>
                    <div class="dropdown-menu">
                        <a class="dropdown-item" href="javascript:void(0);" @click="exportMobilization()">Export Data</a>
                    </div>
                </div>
            </div>

            <div class="col-12" v-if="filters">
                <div class="col-12 filter-bar">
                    <template v-for="(filterParam, i) in tableOptions.filterParam" :key="i">
                        <span class="badge badge-dark filter-box" v-if="String(filterParam).length > 0" @click="removeSingleFilter(i)">{{ capitalize(i) }}: {{ filterParam }} <i class="feather icon-x"></i></span>
                    </template>
                    <a href="javascript:void(0);" class="float-right clear-filter" @click="clearAllFilter()">Clear</a>
                </div>
            </div>

            <div class="col-12 mt-1">
                <div class="card custom-select-down">
                    <div class="card-body py-1" v-show="filterState">
                        <form id="filterForm">
                            <div class="row">
                                <div class="col-12 col-sm-12 col-md-12 col-lg-12">
                                    <div class="form-group">
                                        <label>Geo Location</label>
                                        <select class="select2 form-control" @change="setLocation()">
                                            <option value="">Select Geo Location</option>
                                            <option v-for="(g, i) in geoData" :key="i" :value="i">{{ g.geo_string }}</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                                    <div class="form-group">
                                        <label>HHM Login ID</label>
                                        <input type="text" v-model="tableOptions.filterParam.loginid" class="form-control" placeholder="HHM Login ID" />
                                    </div>
                                </div>
                                <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                                    <div class="form-group">
                                        <label>HH Phone No</label>
                                        <input type="text" v-model="tableOptions.filterParam.hh_phone_no" class="form-control" placeholder="HH Phone No" />
                                    </div>
                                </div>
                                <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                                    <div class="form-group">
                                        <label>e-Token Serial</label>
                                        <input type="text" v-model="tableOptions.filterParam.etoken_serial" class="form-control" placeholder="e-Token Serial" />
                                    </div>
                                </div>
                                <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                                    <div class="form-group">
                                        <label>e-Token Pin</label>
                                        <input type="text" v-model="tableOptions.filterParam.etoken_pin" class="form-control" placeholder="e-Token Pin" />
                                    </div>
                                </div>
                                <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                                    <div class="form-group date_filter">
                                        <label>Mobilization Date</label>
                                        <input type="text" id="mobilization_date" v-model="tableOptions.filterParam.mobilization_date" class="form-control date" placeholder="Mobilization Date" />
                                    </div>
                                </div>
                                <div class="col-12 col-sm-12 col-md-12 col-lg-6">
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
                                    <th @click="sort(0)" width="60px">#</th>
                                    <th @click="sort(1)">Household Name</th>
                                    <th>Household Mothers Name</th>
                                    <th @click="sort(6)">Net</th>
                                    <th>Geo Location</th>
                                    <th @click="sort(7)">Mobilization Date</th>
                                    <th v-if="permission.permission_value == 3"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(g, i) in tableData" :key="g.hhid || i">
                                    <td>{{ i + 1 }}</td>
                                    <td>
                                        <div class="d-flex flex-column">
                                            <span class="fw-bolder">{{ checkIfEmpty(g.hoh_first) }} {{ checkIfEmpty(g.hoh_last) }}</span>
                                            <small class="text-primary"><span class="text-muted"><span class="badge badge-light-primary">Family Size:</span> {{ g.family_size }}</span></small>
                                        </div>
                                    </td>
                                    <td>{{ checkIfEmpty(g.hod_mother) }}</td>
                                    <td><span class="badge badge-light-success">{{ g.allocated_net }}</span></td>
                                    <td>{{ g.geo_string }}</td>
                                    <td>{{ displayDate(g.collected_date) }}</td>
                                    <td v-if="permission.permission_value == 3" class="text-center" style="padding: 0.72rem !important">
                                        <a href="javascript:void(0);" @click="showdistributionDetailsModal(i)" class="btn btn-primary btn-sm px-50 py-25"><i class="feather icon-eye"></i></a>
                                    </td>
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

            <!-- Distribution details modal -->
            <div class="modal modal-slide-in move modal-primary" id="distributionDetails" tabindex="-1" role="dialog" aria-hidden="true">
                <div class="modal-dialog modal-scrollable modal-xl">
                    <form class="add-new-user modal-content pt-0" @submit.stop.prevent="">
                        <button type="reset" class="close" @click="hidedistributionDetailsModal()">×</button>
                        <div class="modal-header mb-1"><h5 class="modal-title font-weight-bolder">Details</h5></div>
                        <div class="modal-body flex-grow-1 vertical-wizard">
                            <div class="info-container pt-25">
                                <h6>Household Details</h6>
                                <table class="table" id="distribution-list">
                                    <tr><td class="user-detail-txt" colspan="2"><label class="d-block text-primary">Household Name</label>{{ checkIfEmpty(tableDetails.hoh_first) }} {{ checkIfEmpty(tableDetails.hoh_last) }}</td></tr>
                                    <tr>
                                        <td class="user-detail-txt"><label class="d-block text-primary">Phone No</label>{{ checkIfEmpty(tableDetails.hoh_phone) }}</td>
                                        <td class="user-detail-txt"><label class="d-block text-primary">Gender</label>{{ checkIfEmpty(tableDetails.hoh_gender) }}</td>
                                    </tr>
                                    <tr>
                                        <td class="user-detail-txt"><label class="d-block text-primary">Family Size</label>{{ checkIfEmpty(tableDetails.family_size) }}</td>
                                        <td class="user-detail-txt"><label class="d-block text-primary">Allocated Netcard</label><span class="badge badge-light-primary">{{ checkIfEmpty(tableDetails.allocated_net) }}</span></td>
                                    </tr>
                                    <tr>
                                        <td class="user-detail-txt"><label class="d-block text-primary">Sleeping Space</label>{{ checkIfEmpty(tableDetails.sleeping_space) }}</td>
                                        <td class="user-detail-txt"><label class="d-block text-primary">Adult Male</label><span class="badge badge-light-primary">{{ checkIfEmpty(tableDetails.adult_male) }}</span></td>
                                    </tr>
                                    <tr>
                                        <td class="user-detail-txt"><label class="d-block text-primary">Adult Female</label>{{ checkIfEmpty(tableDetails.adult_female) }}</td>
                                        <td class="user-detail-txt"><label class="d-block text-primary">Children</label><span class="badge badge-light-primary">{{ checkIfEmpty(tableDetails.children) }}</span></td>
                                    </tr>
                                    <tr><td class="user-detail-txt" colspan="2"><label class="d-block text-primary">Mobilization Date</label>{{ displayDate(tableDetails.mobilization_date) }}</td></tr>
                                    <tr><td class="user-detail-txt" colspan="2"><label class="d-block text-primary">Geo Location</label>{{ checkIfEmpty(tableDetails.geo_string) }}</td></tr>
                                </table>

                                <table class="table card bg-light-default mt-2">
                                    <tr><td class="user-detail-txt"><label class="d-block text-primary">e-Token Serial:</label></td><td class="user-detail-txt"><span class="badge badge-light-primary">{{ tableDetails.etoken_serial }}</span></td></tr>
                                    <tr><td class="user-detail-txt"><label class="d-block text-primary">e-Token Pin:</label></td><td class="user-detail-txt"><span class="badge badge-light-primary">{{ tableDetails.etoken_pin }}</span></td></tr>
                                </table>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    `,
};

useApp({ template: `<div><page-body/></div>` })
    .component('page-body', PageBody)
    .component('unredeemed_search', UnredeemedSearch)
    .mount('#app');
