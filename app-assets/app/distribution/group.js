/**
 * Distribution / Group — Vue 3 Composition API in place.
 * Two components — page-body and sample_table.
 *
 * User-group list with bulk-create modal (qid=002 POST) and per-group
 * activate/deactivate (qid=003/004) and download-badge actions.
 */

const { ref, reactive, onMounted } = Vue;
const { useApp, useFormat, safeMessage } = window.utils;

const PageBody = {
    setup() { return {}; },
    template: `<div><div class="content-body"><sample_table/></div></div>`,
};

const SampleTable = {
    setup() {
        const fmtUtils = useFormat();

        const tableData = ref([]);
        const checkToggle = ref(false);
        const filterState = ref(false);
        const filters = ref(false);
        const url = ref(window.common && window.common.BadgeService);
        const tableOptions = reactive({
            total: 1, pageLength: 1, perPage: 10, currentPage: 1,
            orderDir: 'asc', orderField: 0, limitStart: 0,
            isNext: false, isPrev: false,
            aLength: [10, 20, 50, 100],
            filterParam: { usergroup: '' },
        });
        const errors = ref([]);
        const bulkUserModal = ref(false);
        const bulkUserForm = reactive({
            totalUser: 1, groupName: '', password: '',
            geoLevel: '', geoLevelId: 0,
        });
        const geoIndicator = reactive({
            state: 50, currentLevelId: 0,
            lga: '', cluster: '', ward: '',
        });
        const geoLevelData = ref([]);
        const sysDefaultData = ref({});
        const lgaLevelData = ref([]);
        const clusterLevelData = ref([]);
        const wardLevelData = ref([]);
        const dpLevelData = ref([]);

        function loadTableData() {
            overlay.show();
            axios.get(
                common.TableService +
                '?qid=002&draw=' + tableOptions.currentPage +
                '&order_column=' + tableOptions.orderField +
                '&length=' + tableOptions.perPage +
                '&start=' + tableOptions.limitStart +
                '&order_dir=' + tableOptions.orderDir +
                '&gr=' + tableOptions.filterParam.usergroup
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
            if (tableOptions.filterParam.usergroup != '') {
                toggleFilter();
                filters.value = true;
                paginationDefault();
                loadTableData();
            } else {
                alert.Error('ERROR', 'Invalid required data');
            }
        }
        function clearAllFilter() {
            filters.value = false;
            tableOptions.filterParam.usergroup = '';
            paginationDefault();
            loadTableData();
        }

        function activateUserByGroup(group) {
            $.confirm({
                title: 'WARNING!',
                content: 'Are you sure you want to Activate all the Users in <b>' + group + '</b> group?',
                buttons: {
                    delete: {
                        text: 'Activate All', btnClass: 'btn btn-danger mr-1',
                        action: function () {
                            axios.post(common.DataService + '?qid=004&e=' + group)
                                .then(function (response) {
                                    overlay.hide();
                                    if (response.data.result_code == '201') {
                                        loadTableData();
                                        alert.Success('SUCCESS', response.data.group + ' user group has been activated successfully');
                                    } else {
                                        alert.Error('ERROR', 'Unable to activate ' + response.data.group + ' at the moment please try again later');
                                    }
                                })
                                .catch(function (error) {
                                    overlay.hide();
                                    alert.Error('ERROR', safeMessage(error));
                                });
                        },
                    },
                    cancel: function () { overlay.hide(); },
                },
            });
        }
        function deactivateUserByGroup(group) {
            $.confirm({
                title: 'WARNING!',
                content: 'Are you sure you want to Deactivate all the Users in <b>' + group + '</b> group?',
                buttons: {
                    delete: {
                        text: 'Deactivate All', btnClass: 'btn btn-danger mr-1',
                        action: function () {
                            axios.post(common.DataService + '?qid=003&e=' + group)
                                .then(function (response) {
                                    overlay.hide();
                                    if (response.data.result_code == '201') {
                                        loadTableData();
                                        alert.Success('SUCCESS', response.data.group + ' user group has been deactivated successfully');
                                    } else {
                                        alert.Error('ERROR', 'Unable to deactivate ' + response.data.group + ' at the moment please try again later');
                                    }
                                })
                                .catch(function (error) {
                                    overlay.hide();
                                    alert.Error('ERROR', safeMessage(error));
                                });
                        },
                    },
                    cancel: function () { overlay.hide(); },
                },
            });
        }
        function showBulkUserModal() { bulkUserModal.value = true; }
        function hideBulkUserModal() { bulkUserModal.value = false; }

        function getsysDefaultDataSettings() {
            overlay.show();
            axios.get(common.DataService + '?qid=gen007')
                .then(function (response) {
                    if (response.data.data && response.data.data.length > 0) {
                        sysDefaultData.value = response.data.data[0];
                        getLgasLevel(response.data.data[0].stateid);
                        bulkUserForm.geoLevel = 'state';
                        bulkUserForm.geoLevelId = response.data.data[0].stateid;
                    }
                    overlay.hide();
                })
                .catch(function (error) {
                    overlay.hide();
                    alert.Error('ERROR', safeMessage(error));
                });
        }
        function getGeoLevel() {
            overlay.show();
            axios.get(common.DataService + '?qid=gen001')
                .then(function (response) {
                    geoLevelData.value = (response.data && response.data.data) || [];
                    overlay.hide();
                })
                .catch(function (error) {
                    overlay.hide();
                    alert.Error('ERROR', safeMessage(error));
                });
        }
        function getLgasLevel(stateid) {
            overlay.show();
            axios.post(common.DataService + '?qid=gen003', JSON.stringify(stateid))
                .then(function (response) {
                    lgaLevelData.value = (response.data && response.data.data) || [];
                    overlay.hide();
                })
                .catch(function (error) {
                    overlay.hide();
                    alert.Error('ERROR', safeMessage(error));
                });
        }
        function getClusterLevel() {
            overlay.show();
            axios.get(common.DataService + '?qid=gen004&e=' + geoIndicator.cluster)
                .then(function (response) {
                    clusterLevelData.value = (response.data && response.data.data) || [];
                    overlay.hide();
                })
                .catch(function (error) {
                    overlay.hide();
                    alert.Error('ERROR', safeMessage(error));
                });
        }
        function getWardLevel() {
            overlay.show();
            axios.get(common.DataService + '?qid=gen005&e=' + geoIndicator.lga)
                .then(function (response) {
                    wardLevelData.value = (response.data && response.data.data) || [];
                    overlay.hide();
                })
                .catch(function (error) {
                    overlay.hide();
                    alert.Error('ERROR', safeMessage(error));
                });
        }
        function changeGeoLevel() {
            if (bulkUserForm.geoLevel == 'country' || bulkUserForm.geoLevel == 'dp') {
                alert.Error('ERROR', 'Invalid Geo-Level selected, please select a valid Geo-Level');
            }
        }
        function onSubmitBulkUserCreation() {
            overlay.show();
            axios.post(common.DataService + '?qid=002', JSON.stringify(bulkUserForm))
                .then(function (response) {
                    if (response.data.result_code == '201') {
                        resetBulkUserForm();
                        bulkUserModal.value = false;
                        $('#addNewUser').modal('hide');
                        loadTableData();
                        alert.Success('Success', response.data.total + ' Users Created Successfully');
                        overlay.hide();
                    } else {
                        overlay.hide();
                        alert.Error('Error', 'Users Creation Failed, Kindly check your input fields');
                    }
                })
                .catch(function (error) {
                    alert.Error('ERROR', safeMessage(error));
                    overlay.hide();
                });
        }
        function resetBulkUserForm() {
            bulkUserForm.totalUser = 1;
            bulkUserForm.groupName = '';
            bulkUserForm.password = '';
            getsysDefaultDataSettings();
            overlay.hide();
        }
        function refreshData() { paginationDefault(); loadTableData(); }
        function downloadGroupBadge(user_group) {
            overlay.show();
            window.popup = window.open(url.value + '?qid=001&e=' + user_group, '_parent');
            overlay.hide();
        }

        onMounted(function () {
            getGeoLevel();
            getsysDefaultDataSettings();
            loadTableData();
        });

        return {
            tableData, checkToggle, filterState, filters, url, tableOptions,
            errors, bulkUserModal, bulkUserForm, geoIndicator,
            geoLevelData, sysDefaultData, lgaLevelData, clusterLevelData,
            wardLevelData, dpLevelData,
            loadTableData, selectAll, uncheckAll, selectToggle, checkedBg,
            toggleFilter, paginationDefault, nextPage, prevPage, currentPage,
            changePerPage, sort, applyFilter, clearAllFilter,
            activateUserByGroup, deactivateUserByGroup,
            showBulkUserModal, hideBulkUserModal,
            getsysDefaultDataSettings, getGeoLevel, getLgasLevel, getClusterLevel,
            getWardLevel, changeGeoLevel, onSubmitBulkUserCreation, resetBulkUserForm,
            refreshData, downloadGroupBadge,
            capitalize: fmtUtils.capitalize,
        };
    },
    template: `
        <div class="row" id="basic-table">
            <div class="col-md-8 col-sm-12 col-12 mb-0">
                <h2 class="content-header-title header-txt float-left mb-0">Users</h2>
                <div class="breadcrumb-wrapper">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../users">Home</a></li>
                        <li class="breadcrumb-item active">User Group</li>
                    </ol>
                </div>
            </div>

            <div class="col-md-4 col-sm-12 col-12 text-md-right text-right d-md-block">
                <div class="btn-group mr-1">
                    <button type="button" @click="showBulkUserModal()" data-toggle="modal" data-target="#addNewUser" class="btn btn-outline-primary round"><i data-feather='user-plus'></i></button>
                    <button type="button" class="btn btn-outline-primary round searchBtn" @click="refreshData()"><i class="feather icon-refresh-cw"></i></button>
                    <button type="button" class="btn btn-outline-primary round searchBtn" @click="toggleFilter()"><i class="feather" :class="filterState ? 'icon-x' : 'icon-filter'"></i></button>
                </div>
            </div>

            <div class="col-12" v-if="filters">
                <div class="col-12 filter-bar">
                    <template v-for="(filterParam, i) in tableOptions.filterParam" :key="i">
                        <span class="badge badge-dark filter-box" v-if="String(filterParam).length > 0">{{ capitalize(i) }}: {{ filterParam }} <i class="feather icon-x"></i></span>
                    </template>
                    <a href="#" class="float-right clear-filter" @click="clearAllFilter()">Clear</a>
                </div>
            </div>

            <div class="col-12 mt-1">
                <div class="card">
                    <div class="card-body py-1" v-show="filterState">
                        <form id="filterForm">
                            <div class="row">
                                <div class="col-9 col-md-9">
                                    <div class="form-group">
                                        <input type="text" v-model="tableOptions.filterParam.usergroup" class="form-control" placeholder="User Group Name" />
                                    </div>
                                </div>
                                <div class="col-3 col-md-3 text-right">
                                    <button type="button" class="btn btn-md btn-primary" @click="applyFilter()">Apply Filters</button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th width="60px">
                                        <div class="custom-control custom-checkbox checkbox">
                                            <input type="checkbox" class="custom-control-input" @change="selectToggle()" id="all-check" />
                                            <label class="custom-control-label" for="all-check"></label>
                                        </div>
                                    </th>
                                    <th @click="sort(2)">User Group Name</th>
                                    <th @click="sort(1)">Total Users</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(g, i) in tableData" :key="g.user_group || i" :class="checkedBg(g.pick)">
                                    <td>
                                        <div class="custom-control custom-checkbox checkbox">
                                            <input type="checkbox" class="custom-control-input" :id="g.user_group" v-model="g.pick" />
                                            <label class="custom-control-label" :for="g.user_group"></label>
                                        </div>
                                    </td>
                                    <td>{{ g.user_group }}</td>
                                    <td>{{ parseInt(g.total).toLocaleString() }}</td>
                                    <td>
                                        <div class="dropdown">
                                            <button type="button" class="btn btn-sm dropdown-toggle hide-arrow" data-toggle="dropdown"><i class="feather icon-more-vertical"></i></button>
                                            <div class="dropdown-menu">
                                                <a class="dropdown-item" href="javascript:void(0);" @click="activateUserByGroup(g.user_group)"><i class="feather icon-user-check mr-50"></i><span>Activate Users</span></a>
                                                <a class="dropdown-item" href="javascript:void(0);" @click="deactivateUserByGroup(g.user_group)"><i class="feather icon-user-x mr-50"></i><span>Deactivate Users</span></a>
                                                <a class="dropdown-item" @click="downloadGroupBadge(g.user_group)" href="javascript:void(0)"><i class="feather icon-download mr-50"></i><span>Download Badge</span></a>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="tableData.length == 0"><td class="text-center pt-2" colspan="4"><small>No User Group Added</small></td></tr>
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

            <div v-if="bulkUserModal" class="modal modal-slide-in new-user-modal show" id="addNewUser" style="display: block; padding-right: 17px;">
                <div class="modal-dialog modal-scrollable modal-xl">
                    <form class="add-new-user modal-content pt-0" @submit.stop.prevent="onSubmitBulkUserCreation()">
                        <button type="button" class="close" @click="hideBulkUserModal()">×</button>
                        <div class="modal-header mb-1"><h5 class="modal-title">Create New User</h5></div>
                        <div class="modal-body flex-grow-1">
                            <div class="form-group">
                                <label class="form-label">Total Number of User</label>
                                <input type="number" class="form-control" required v-model="bulkUserForm.totalUser" placeholder="Total Number of User" />
                            </div>
                            <div class="form-group">
                                <label class="form-label">Group Name</label>
                                <input type="text" class="form-control" required v-model="bulkUserForm.groupName" placeholder="Group Name" />
                            </div>
                            <div class="form-group">
                                <label class="form-label">Password</label>
                                <input type="text" required class="form-control" v-model="bulkUserForm.password" placeholder="Password" />
                            </div>
                            <div class="form-group">
                                <label class="form-label">Geo Level</label>
                                <select @change="changeGeoLevel()" class="form-control" v-model="bulkUserForm.geoLevel">
                                    <option v-for="geo in geoLevelData" :key="geo.geo_level" :value="geo.geo_level">{{ geo.geo_level }}</option>
                                </select>
                            </div>
                            <div class="form-group" v-if="bulkUserForm.geoLevel == 'state'">
                                <label class="form-label">State</label>
                                <select class="form-control" v-model="bulkUserForm.geoLevelId">
                                    <option :value="sysDefaultData.stateid">{{ sysDefaultData.state }}</option>
                                </select>
                            </div>
                            <div class="form-group" v-if="bulkUserForm.geoLevel == 'lga'">
                                <label class="form-label">LGA List</label>
                                <select class="form-control" v-model="bulkUserForm.geoLevelId">
                                    <option v-for="lga in lgaLevelData" :key="lga.lgaid" :value="lga.lgaid">{{ lga.lga }}</option>
                                </select>
                            </div>
                            <div v-if="bulkUserForm.geoLevel == 'cluster'">
                                <div class="form-group">
                                    <label class="form-label">LGA List</label>
                                    <select class="form-control" @change="getClusterLevel()" v-model="geoIndicator.cluster">
                                        <option v-for="lga in lgaLevelData" :key="lga.lgaid" :value="lga.lgaid">{{ lga.lga }}</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Cluster</label>
                                    <select class="form-control" v-model="bulkUserForm.geoLevelId">
                                        <option v-for="g in clusterLevelData" :key="g.clusterid" :value="g.clusterid">{{ g.cluster }}</option>
                                    </select>
                                </div>
                            </div>
                            <div v-if="bulkUserForm.geoLevel == 'ward'">
                                <div class="form-group">
                                    <label class="form-label">LGA List</label>
                                    <select class="form-control" @change="getWardLevel()" v-model="geoIndicator.lga">
                                        <option v-for="lga in lgaLevelData" :key="lga.lgaid" :value="lga.lgaid">{{ lga.lga }}</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Ward</label>
                                    <select class="form-control" v-model="bulkUserForm.geoLevelId">
                                        <option v-for="g in wardLevelData" :key="g.wardid" :value="g.wardid">{{ g.ward }}</option>
                                    </select>
                                </div>
                            </div>
                            <div class="mt-2">
                                <button type="submit" class="btn btn-primary mr-1 data-submit">Save</button>
                                <button type="reset" class="btn btn-outline-secondary" @click="hideBulkUserModal()">Cancel</button>
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
    .component('sample_table', SampleTable)
    .mount('#app');
