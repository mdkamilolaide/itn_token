/**
 * Distribution / List — Vue 3 Composition API in place.
 * Three components — page-body, distribution_list, distribution_details.
 *
 * Paginated list of distributions (qid=401) with geo + date filters,
 * Excel export, per-row details modal showing recorder/distributor +
 * GS1 status + map link. The distribution_details view is the same
 * user-profile edit pane shared with mobilization/list (qid=005, 006).
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
        function gotoPageHandler(data) { page.value = data && data.page; }
        onMounted(function () { bus.on('g-event-goto-page', gotoPageHandler); });
        onBeforeUnmount(function () { bus.off('g-event-goto-page', gotoPageHandler); });
        return { page, permission };
    },
    template: `
        <div>
            <div class="content-body">
                <div v-show="page == 'list'">
                    <distribution_list v-if="permission.permission_value > 1"/>
                    <div class="alert alert-danger" v-else><div class="alert-body"><strong>Access Denied!</strong> You don't have permission to access this page.</div></div>
                </div>
                <div v-show="page == 'detail'">
                    <distribution_details v-if="permission.permission_value > 1"/>
                    <div class="alert alert-danger" v-else><div class="alert-body"><strong>Access Denied!</strong> You don't have permission to access this page.</div></div>
                </div>
            </div>
        </div>
    `,
};

const DistributionList = {
    setup() {
        const fmtUtils = useFormat();

        const url = ref(window.common && window.common.BadgeService);
        const tableData = ref([]);
        const tableDetails = reactive({
            allocated_net: '', collected_date: '', collected_nets: '',
            created: '', dis_id: '', dpid: '', etoken_serial: '',
            family_size: '', geo_level: '', geo_string: '',
            hoh_first: '', hoh_gender: '', hoh_last: '', hoh_phone: '',
            is_gs_one_record: '', location_description: '',
            recorder_loginid: '', recorder_name: '',
            distributor_name: '', distributor_loginid: '',
            longitude: '', latitude: '',
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
            filterParam: { loginid: '', collected_date: '', geo_level: '', geo_level_id: '', geo_string: '' },
        });

        function reloadUserListOnUpdate() { paginationDefault(); loadTableData(); }
        function loadTableData() {
            overlay.show();
            axios.get(
                common.TableService +
                '?qid=401&draw=' + tableOptions.currentPage +
                '&order_column=' + tableOptions.orderField +
                '&length=' + tableOptions.perPage +
                '&start=' + tableOptions.limitStart +
                '&order_dir=' + tableOptions.orderDir +
                '&gl=' + tableOptions.filterParam.geo_level +
                '&lgid=' + tableOptions.filterParam.loginid +
                '&glid=' + tableOptions.filterParam.geo_level_id +
                '&mdt=' + tableOptions.filterParam.collected_date
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
            if (filterState.value === false) {
                filters.value = false;
                try { $('#collected_date').flatpickr({ altInput: true, altFormat: 'F j, Y', dateFormat: 'Y-m-d' }).clear(); } catch (e) {}
            }
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
            checkFill += tableOptions.filterParam.loginid != '' ? 1 : 0;
            checkFill += tableOptions.filterParam.collected_date != '' ? 1 : 0;
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
        function removeSingleFilter(column_name) {
            tableOptions.filterParam[column_name] = '';
            if (column_name == 'geo_level' || column_name == 'geo_level_id') {
                tableOptions.filterParam.geo_level = '';
                tableOptions.filterParam.geo_level_id = '';
            }
            if (column_name == 'collected_date') {
                try { $('#collected_date').flatpickr({ altInput: true, altFormat: 'F j, Y', dateFormat: 'Y-m-d' }).clear(); } catch (e) {}
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
            try { $('#collected_date').flatpickr({ altInput: true, altFormat: 'F j, Y', dateFormat: 'Y-m-d' }).clear(); } catch (e) {}
            filters.value = false;
            tableOptions.filterParam.collected_date = '';
            tableOptions.filterParam.loginid = '';
            tableOptions.filterParam.geo_level = '';
            tableOptions.filterParam.geo_level_id = '';
            paginationDefault();
            loadTableData();
        }
        function goToDetail(userid, user_status) {
            bus.emit('g-event-goto-page', { userid: userid, page: 'detail', user_status: user_status });
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

        async function exportMobilization() {
            var qs =
                'qid=401&draw=' + tableOptions.currentPage +
                '&order_column=' + tableOptions.orderField +
                '&length=' + tableOptions.perPage +
                '&start=' + tableOptions.limitStart +
                '&order_dir=' + tableOptions.orderDir +
                '&gl=' + tableOptions.filterParam.geo_level +
                '&lgid=' + tableOptions.filterParam.loginid +
                '&glid=' + tableOptions.filterParam.geo_level_id +
                '&mdt=' + tableOptions.filterParam.collected_date;
            var filename =
                (tableOptions.filterParam.geo_string ? tableOptions.filterParam.geo_string : 'Recent ') + ' ' +
                (tableOptions.filterParam.loginid ? tableOptions.filterParam.loginid : 'Recent ') +
                ' Mobilization List';
            overlay.show();

            var count = await new Promise(function (resolve) {
                $.ajax({
                    url: common.DataService, type: 'POST', data: qs, dataType: 'json',
                    success: function (data) { resolve(data.total); },
                });
            });
            var downloadMax = (window.common && window.common.ExportDownloadLimit) || 25000;
            if (parseInt(count) > downloadMax) {
                alert.Error('Download Error', 'Unable to download data because it has exceeded download limit, download limit is ' + downloadMax);
            } else if (parseInt(count) == 0) {
                alert.Error('Download Error', 'No data found');
            } else {
                alert.Info('DOWNLOADING...', 'Downloading ' + count + ' record(s)');
                var outcome = await new Promise(function (resolve) {
                    $.ajax({
                        url: common.ExportService, type: 'POST', data: qs,
                        success: function (data) { resolve(data); },
                    });
                });
                var exportData = JSON.parse(outcome);
                if (window.Jhxlsx && typeof window.Jhxlsx.export === 'function') {
                    window.Jhxlsx.export(exportData, { fileName: filename });
                }
            }
            overlay.hide();
        }

        function showdistributionDetailsModal(i) {
            overlay.show();
            var row = tableData.value[i] || {};
            tableDetails.geo_string = row.geo_string;
            tableDetails.allocated_net = row.allocated_net;
            tableDetails.collected_date = row.collected_date;
            tableDetails.collected_nets = row.collected_nets;
            tableDetails.created = row.created;
            tableDetails.dis_id = row.dis_id;
            tableDetails.dpid = row.dpid;
            tableDetails.etoken_serial = row.etoken_serial;
            tableDetails.family_size = row.family_size;
            tableDetails.geo_level = row.geo_level;
            tableDetails.hoh_first = row.hoh_first;
            tableDetails.hoh_last = row.hoh_last;
            tableDetails.hoh_gender = row.hoh_gender;
            tableDetails.hoh_phone = row.hoh_phone;
            tableDetails.is_gs_one_record = row.is_gs_one_record;
            tableDetails.location_description = row.location_description;
            tableDetails.recorder_loginid = row.recorder_loginid;
            tableDetails.recorder_name = row.recorder_name;
            tableDetails.distributor_name = row.recorder_name;
            tableDetails.distributor_loginid = row.distributor_loginid;
            tableDetails.longitude = row.longitude;
            tableDetails.latitude = row.latitude;
            $('#distributionDetails').modal('show');
            overlay.hide();
        }
        function hidedistributionDetailsModal() {
            overlay.show();
            $('#distributionDetails').modal('hide');
            for (var k in tableDetails) tableDetails[k] = '';
            overlay.hide();
        }
        function checkIfEmpty(data) { return data === null || data === '' ? 'Nil' : data; }

        onMounted(function () {
            getGeoLocation();
            loadTableData();
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
        onBeforeUnmount(function () {
            bus.off('g-event-update-user', reloadUserListOnUpdate);
        });

        return {
            url, tableData, tableDetails, id, geoData,
            checkToggle, filterState, filters, tableOptions,
            reloadUserListOnUpdate, loadTableData,
            selectAll, uncheckAll, selectToggle, checkedBg, toggleFilter,
            paginationDefault, nextPage, prevPage, currentPage,
            changePerPage, sort, applyFilter, removeSingleFilter, clearAllFilter,
            goToDetail, refreshData, getGeoLocation, setLocation,
            exportMobilization, showdistributionDetailsModal,
            hidedistributionDetailsModal, checkIfEmpty,
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
                        <li class="breadcrumb-item active">Distribution List</li>
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
                                <div class="col-12 col-sm-6 col-md-6 col-lg-6">
                                    <div class="form-group">
                                        <label>Geo Location</label>
                                        <select class="select2 form-control" @change="setLocation()">
                                            <option value="">Select Geo Location</option>
                                            <option v-for="(g, i) in geoData" :key="i" :value="i">{{ g.geo_string }}</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-12 col-sm-6 col-md-6 col-lg-3">
                                    <div class="form-group date_filter">
                                        <label>Distribution Date</label>
                                        <input type="text" id="collected_date" v-model="tableOptions.filterParam.collected_date" class="form-control date" placeholder="Distribution Date" />
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
                                    <th @click="sort(14)">Recorder</th>
                                    <th @click="sort(4)">Household Name</th>
                                    <th @click="sort(12)">Net</th>
                                    <th @click="sort(3)">DP Location</th>
                                    <th @click="sort(16)">Date of Redemption</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(g, i) in tableData" :key="g.dis_id || i" :class="checkedBg(g.pick)">
                                    <td>
                                        <div class="d-flex flex-column">
                                            <span class="fw-bolder">{{ g.recorder_name }}</span>
                                            <small class="text-primary">{{ g.recorder_loginid }}</small>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column">
                                            <span class="fw-bolder">{{ g.hoh_first }} {{ g.hoh_last }}</span>
                                            <small class="text-muted"><span class="badge badge-light-primary">Family Size:</span> {{ g.family_size }}</small>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge badge-light-primary">{{ g.collected_nets }}</span> of <span class="badge badge-light-success">{{ g.allocated_net }}</span>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column">
                                            <span class="fw-bolder">{{ g.geo_name }}</span>
                                            <small class="text-muted">{{ g.geo_string }}</small>
                                        </div>
                                    </td>
                                    <td>{{ displayDate(g.collected_date) }}</td>
                                    <td class="text-center" style="padding: 0.72rem !important">
                                        <a href="javascript:void(0);" @click="showdistributionDetailsModal(i)" class="btn btn-primary btn-sm px-50 py-25"><i class="feather icon-eye"></i></a>
                                    </td>
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

            <div class="modal modal-slide-in move modal-primary" id="distributionDetails" tabindex="-1" role="dialog" aria-hidden="true">
                <div class="modal-dialog modal-scrollable modal-xl">
                    <form class="add-new-user modal-content pt-0" @submit.stop.prevent="">
                        <button type="reset" class="close" @click="hidedistributionDetailsModal()">×</button>
                        <div class="modal-header mb-1"><h5 class="modal-title font-weight-bolder">Details</h5></div>

                        <div class="modal-body flex-grow-1 vertical-wizard">
                            <div class="info-container pt-25">
                                <h6>Household Details</h6>
                                <table class="table">
                                    <tr>
                                        <td class="user-detail-txt"><label class="d-block text-primary">Full Name</label>{{ checkIfEmpty(tableDetails.hoh_first) }} {{ checkIfEmpty(tableDetails.hoh_last) }}</td>
                                        <td class="user-detail-txt"><label class="d-block text-primary">Gender</label>{{ checkIfEmpty(tableDetails.hoh_gender) }}</td>
                                    </tr>
                                    <tr>
                                        <td class="user-detail-txt"><label class="d-block text-primary">Phone No</label>{{ checkIfEmpty(tableDetails.hoh_phone) }}</td>
                                        <td class="user-detail-txt"><label class="d-block text-primary">Location Category</label>{{ checkIfEmpty(tableDetails.location_description) }}</td>
                                    </tr>
                                    <tr>
                                        <td class="user-detail-txt"><label class="d-block text-primary">Family Size</label>{{ checkIfEmpty(tableDetails.family_size) }}</td>
                                        <td class="user-detail-txt"><label class="d-block text-primary">Allocated Netcard</label><span class="badge badge-light-primary">{{ checkIfEmpty(tableDetails.allocated_net) }}</span></td>
                                    </tr>
                                    <tr>
                                        <td class="user-detail-txt"><label class="d-block text-primary">Date of Collection</label>{{ displayDate(tableDetails.collected_date) }}</td>
                                        <td class="user-detail-txt"><label class="d-block text-primary">Collected Nets</label><span class="badge badge-light-success">{{ checkIfEmpty(tableDetails.collected_nets) }}</span></td>
                                    </tr>
                                    <tr>
                                        <td class="user-detail-txt"><label class="d-block text-primary">e-Token Serial</label>{{ checkIfEmpty(tableDetails.etoken_serial) }}</td>
                                        <td class="user-detail-txt"><label class="d-block text-primary">GS1 Status</label><span class="badge" :class="tableDetails.is_gs_one_record == 'Yes' ? 'bg-light-success' : 'bg-light-danger'">{{ tableDetails.is_gs_one_record == 'Yes' ? 'Yes' : 'No' }}</span></td>
                                    </tr>
                                    <tr><td class="user-detail-txt" colspan="2"><label class="d-block text-primary">Geo Location</label>{{ checkIfEmpty(tableDetails.geo_string) }}</td></tr>
                                </table>

                                <div class="justify-content-center mb-50 form-group text-right">
                                    <hr>
                                    <a :href="'https://www.google.com/maps/@?api=1&map_action=map&basemap=satellite&center=' + tableDetails.latitude + ',' + tableDetails.longitude + '&zoom=5'" target="_blank" class="btn btn-primary mr-50">Map</a>
                                    <button type="reset" class="btn btn-secondary" @click="hidedistributionDetailsModal()">Close</button>
                                </div>

                                <table class="table card bg-light-default mt-2">
                                    <tr>
                                        <td class="user-detail-txt" style="width: 70% !important"><label class="d-block text-primary">Recorder Name</label>{{ checkIfEmpty(tableDetails.recorder_name) }}</td>
                                        <td class="user-detail-txt"><label class="d-block text-primary">Login ID</label><span class="badge badge-light-success">{{ tableDetails.recorder_loginid }}</span></td>
                                    </tr>
                                    <tr>
                                        <td class="user-detail-txt" style="width: 70% !important"><label class="d-block text-primary">Distributor Name</label>{{ checkIfEmpty(tableDetails.distributor_name) }}</td>
                                        <td class="user-detail-txt"><label class="d-block text-primary">Login ID</label><span class="badge badge-light-success">{{ tableDetails.distributor_loginid }}</span></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    `,
};

const DistributionDetails = {
    setup() {
        const fmtUtils = useFormat();

        const userid = ref('');
        const userDetails = ref(true);
        const user_status = ref('');
        const bankListData = ref([]);
        const roleListData = ref([]);
        const userData = reactive({ baseData: [], financeData: [], identityData: [], roleData: [] });

        function gotoPageHandler(data) {
            userDetails.value = true;
            userid.value = data.userid;
            user_status.value = data.user_status;
            getUserDetails();
        }
        function goToList() { bus.emit('g-event-goto-page', { page: 'list', userid: userid.value }); }
        function discardUpdate() {
            $.confirm({
                title: 'WARNING!',
                content: '<p>Are you sure you want to discard the changes?</p>',
                buttons: {
                    delete: { text: 'Discard Changes', btnClass: 'btn btn-warning mr-1', action: function () { getUserDetails(); userDetails.value = true; overlay.hide(); } },
                    close: { text: 'Cancel', btnClass: 'btn btn-outline-secondary', action: function () { overlay.hide(); } },
                },
            });
        }
        function getUserDetails() {
            overlay.show();
            axios.get(common.DataService + '?qid=005&e=' + userid.value)
                .then(function (response) {
                    userData.baseData = (response.data.base && response.data.base[0]) || {};
                    userData.financeData = (response.data.finance && response.data.finance[0]) || {};
                    userData.identityData = (response.data.identity && response.data.identity[0]) || {};
                    userData.roleData = (response.data.role && response.data.role[0]) || {};
                    overlay.hide();
                })
                .catch(function (error) { overlay.hide(); alert.Error('ERROR', safeMessage(error)); });
        }
        function getBankLists() {
            overlay.show();
            axios.get(common.DataService + '?qid=gen008')
                .then(function (response) {
                    bankListData.value = (response.data && response.data.data) || [];
                    overlay.hide();
                })
                .catch(function (error) { overlay.hide(); alert.Error('ERROR', safeMessage(error)); });
        }
        function updateUserProfile() {
            var data = {
                userid: userid.value,
                roleid: userData.baseData.roleid,
                first: userData.identityData.first, middle: userData.identityData.middle, last: userData.identityData.last,
                gender: userData.identityData.gender, email: userData.identityData.email, phone: userData.identityData.phone,
                bank_name: userData.financeData.bank_name, account_name: userData.financeData.account_name,
                account_no: userData.financeData.account_no, bank_code: userData.financeData.bank_code, bio_feature: '',
            };
            overlay.show();
            $.confirm({
                title: 'WARNING!',
                content: '<p>Are you sure you want to Update the User?</p>',
                buttons: {
                    delete: {
                        text: 'Update Details', btnClass: 'btn btn-warning mr-1',
                        action: function () {
                            axios.post(common.DataService + '?qid=006', JSON.stringify(data))
                                .then(function (response) {
                                    if (response.data.result_code == '200') {
                                        overlay.hide();
                                        bus.emit('g-event-update-user', {});
                                        userDetails.value = true;
                                        alert.Success('SUCCESS', response.data.total + ' User Updated');
                                    } else { overlay.hide(); alert.Error('ERROR', 'User update failed'); }
                                })
                                .catch(function (error) { overlay.hide(); alert.Error('ERROR', safeMessage(error)); });
                        },
                    },
                    close: { text: 'Cancel', btnClass: 'btn btn-outline-secondary', action: function () { overlay.hide(); } },
                },
            });
        }
        function getRoleList() {
            overlay.show();
            axios.get(common.DataService + '?qid=007')
                .then(function (response) {
                    roleListData.value = (response.data && response.data.data) || [];
                    overlay.hide();
                })
                .catch(function (error) { overlay.hide(); alert.Error('ERROR', safeMessage(error)); });
        }
        function checkIfEmpty(data) { return data === null || data === '' ? 'Nil' : data; }
        function userActivationDeactivation(actionid) {
            overlay.show();
            axios.post(common.DataService + '?qid=001', JSON.stringify([actionid]))
                .then(function (response) {
                    overlay.hide();
                    if (response.data.result_code == '200') {
                        bus.emit('g-event-update-user', {});
                        user_status.value = user_status.value == '1' ? 0 : 1;
                        alert.Success('SUCCESS', 'User De/Activation Successful');
                    } else { alert.Error('ERROR', 'User De/Activation failed'); }
                })
                .catch(function (error) { overlay.hide(); alert.Error('ERROR', safeMessage(error)); });
        }
        function changeRole(event) { userData.baseData.role = event.target.options[event.target.options.selectedIndex].text; }
        function changeBank(event) { userData.financeData.bank_name = event.target.options[event.target.options.selectedIndex].text; }
        function downloadBadge(uid) {
            overlay.show();
            window.open(common.BadgeService + '?qid=002&e=' + uid, '_parent');
            overlay.hide();
        }

        onMounted(function () { bus.on('g-event-goto-page', gotoPageHandler); });
        onBeforeUnmount(function () { bus.off('g-event-goto-page', gotoPageHandler); });

        return {
            userid, userDetails, user_status, bankListData, roleListData, userData,
            gotoPageHandler, goToList, discardUpdate, getUserDetails, getBankLists,
            updateUserProfile, getRoleList, checkIfEmpty,
            userActivationDeactivation, changeRole, changeBank, downloadBadge,
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
                                <button class="btn btn-primary mb-1 form-control" @click="downloadBadge(userid)"><i class="feather icon-download"></i> Download Badge</button>
                                <button v-if="userDetails" @click="userDetails = false" class="btn btn-outline-primary mb-1 form-control"><i class="feather icon-edit-2"></i> Edit</button>
                                <button class="btn form-control" :class="user_status == 1 ? 'btn-danger' : 'btn-success'" @click="userActivationDeactivation(userid)"><i class="feather" :class="user_status == 1 ? 'icon-user-x' : 'icon-user-check'"></i> {{ user_status==1 ? ' Deactivate' : ' Activate' }}</button>
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
                            <button class="btn btn-primary btn-sm" @click="userDetails = false"><i class="feather icon-edit-2"></i> Edit</button>
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
                        <div class="card-body row"><div class="col-12">
                            <table class="table">
                                <tr><td colspan="2" class="user-detail-txt"><label class="d-block text-primary">Account Name</label>{{ checkIfEmpty(userData.financeData.account_name) }}</td></tr>
                                <tr>
                                    <td class="user-detail-txt"><label class="d-block text-primary">Account Number</label>{{ checkIfEmpty(userData.financeData.account_no) }}</td>
                                    <td class="user-detail-txt"><label class="d-block text-primary">Bank Name</label>{{ checkIfEmpty(userData.financeData.bank_name) }}</td>
                                </tr>
                            </table>
                        </div></div>
                    </div>

                    <div class="card">
                        <div class="card-header"><h4 class="card-title mb-50">Role</h4></div>
                        <div class="card-body row"><div class="col-12">
                            <table class="table"><tr><td colspan="2" class="user-detail-txt"><label class="d-block text-primary">Role</label>{{ checkIfEmpty(userData.baseData.role) }}</td></tr></table>
                        </div></div>
                    </div>
                </div>

                <form method="POST" @submit.stop.prevent="updateUserProfile()" v-else>
                    <div class="card">
                        <div class="card-header"><h4 class="card-title mb-50">Details</h4></div>
                        <div class="card-body row">
                            <div class="col-6"><div class="form-group"><label>First Name</label><input type="text" v-model="userData.identityData.first" class="form-control" placeholder="First Name" /></div></div>
                            <div class="col-6"><div class="form-group"><label>Middle Name</label><input type="text" v-model="userData.identityData.middle" class="form-control" placeholder="Middle Name" /></div></div>
                            <div class="col-6"><div class="form-group"><label>Lastname</label><input type="text" v-model="userData.identityData.last" class="form-control" placeholder="Last Name" /></div></div>
                            <div class="col-6"><div class="form-group"><label>Gender</label><select v-model="userData.identityData.gender" class="form-control"><option>Male</option><option>Female</option></select></div></div>
                            <div class="col-6"><div class="form-group"><label>Phone No</label><input type="text" v-model="userData.identityData.phone" class="form-control" placeholder="Phone No" /></div></div>
                            <div class="col-6"><div class="form-group"><label>Email</label><input type="email" v-model="userData.identityData.email" class="form-control" placeholder="Email" /></div></div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header"><h4 class="card-title mb-50">Finance</h4></div>
                        <div class="card-body"><div class="row">
                            <div class="col-12"><div class="form-group"><label>Account Name</label><input type="text" v-model="userData.financeData.account_name" class="form-control" placeholder="Account Name" /></div></div>
                            <div class="col-6"><div class="form-group"><label>Account Number</label><input type="text" v-model="userData.financeData.account_no" class="form-control" placeholder="Account Number" /></div></div>
                            <div class="col-6"><div class="form-group"><label>Bank Name</label>
                                <select v-model="userData.financeData.bank_code" @change="changeBank($event)" class="form-control select2">
                                    <option v-for="b in bankListData" :key="b.bank_code" :value="b.bank_code">{{ b.bank_name }}</option>
                                </select>
                            </div></div>
                        </div></div>
                    </div>

                    <div class="card">
                        <div class="card-header"><h4 class="card-title mb-50">Role</h4></div>
                        <div class="card-body"><div class="row">
                            <div class="col-12"><div class="form-group"><label>Role</label>
                                <select v-model="userData.baseData.roleid" @change="changeRole($event)" class="form-control select2">
                                    <option v-for="r in roleListData" :value="r.roleid" :key="r.roleid">{{ r.role }}</option>
                                </select>
                            </div></div>
                        </div></div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-6"><button type="button" @click="discardUpdate()" class="btn btn-outline-secondary form-control mt-2">Cancel</button></div>
                        <div class="col-6"><button class="btn btn-primary form-control mt-2">Update Details</button></div>
                    </div>
                </form>
            </div>
        </div>
    `,
};

useApp({ template: `<div><page-body/></div>` })
    .component('page-body', PageBody)
    .component('distribution_list', DistributionList)
    .component('distribution_details', DistributionDetails)
    .mount('#app');
