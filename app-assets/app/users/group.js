/**
 * Users / Group submodule — Vue 3 Composition API in place.
 * User-group list (qid=002), bulk-create modal (qid=002 POST),
 * activate/deactivate by group (qid=003 / qid=004), geo cascade,
 * download group badge via hidden iframe.
 */

const { ref, reactive, onMounted } = Vue;
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
        const defaultStateId = ref('');
        const checkToggle = ref(false);
        const filterState = ref(false);
        const filters = ref(false);
        const url = ref(window.common && window.common.BadgeService);
        const userGroup = ref([]);
        const errors = ref([]);
        const bulkUserModal = ref(false);
        const permission = ref(
            (typeof getPermission === 'function')
                ? (getPermission(typeof per !== 'undefined' ? per : null, 'users') || { permission_value: 0 })
                : { permission_value: 0 }
        );

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
            filterParam: { usergroup: '' },
        });

        const bulkUserForm = reactive({
            totalUser: 1,
            groupName: '',
            password: '',
            geoLevel: '',
            geoLevelId: 0,
            roleid: '',
        });
        const geoIndicator = reactive({
            state: 50,
            currentLevelId: 0,
            lga: '',
            cluster: '',
            ward: '',
        });
        const geoLevelData = ref([]);
        const sysDefaultData = ref({});
        const lgaLevelData = ref([]);
        const clusterLevelData = ref([]);
        const wardLevelData = ref([]);
        const dpLevelData = ref([]);
        const roleListData = ref([]);

        function loadTableData() {
            overlay.show();
            var u = common.TableService;
            axios.get(
                u +
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

        function selectAll()    { for (var i = 0; i < tableData.value.length; i++) tableData.value[i].pick = true; }
        function uncheckAll()   { for (var i = 0; i < tableData.value.length; i++) tableData.value[i].pick = false; }
        function selectToggle() {
            if (checkToggle.value === false) { selectAll(); checkToggle.value = true; }
            else                              { uncheckAll(); checkToggle.value = false; }
        }
        function checkedBg(pickOne) { return pickOne != '' ? 'bg-select' : ''; }

        function toggleFilter() {
            if (filterState.value === false) filters.value = false;
            return (filterState.value = !filterState.value);
        }
        function selectedItems() { return tableData.value.filter(function (r) { return r.pick; }); }
        function selectedID()    { return tableData.value.filter(function (r) { return r.pick; }).map(function (r) { return r.userid; }); }

        function totalCheckedBox() {
            var total = selectedID().length;
            var el = document.getElementById('total-selected');
            if (!el) return;
            if (total > 0) {
                el.innerHTML = '<span id="selected-counter" class="badge badge-primary btn-icon"><span class="badge badge-success">' + total + '</span> Selected</span>';
            } else {
                el.replaceChildren();
            }
        }

        function paginationDefault() {
            tableOptions.pageLength = Math.ceil(tableOptions.total / tableOptions.perPage);
            tableOptions.limitStart = Math.ceil((tableOptions.currentPage - 1) * tableOptions.perPage);
            tableOptions.isNext = tableOptions.currentPage < tableOptions.pageLength;
            tableOptions.isPrev = tableOptions.currentPage > 1;
        }
        function nextPage() { tableOptions.currentPage += 1; paginationDefault(); loadTableData(); }
        function prevPage() { tableOptions.currentPage -= 1; paginationDefault(); loadTableData(); }
        function resetSelected() { uncheckAll(); checkToggle.value = false; totalCheckedBox(); }
        function currentPage() {
            paginationDefault();
            if (tableOptions.currentPage < 1)                            alert.Error('ERROR', "The Page requested doesn't exist");
            else if (tableOptions.currentPage > tableOptions.pageLength) alert.Error('ERROR', "The Page requested doesn't exist");
            else                                                         loadTableData();
        }
        function changePerPage(val) {
            tableOptions.currentPage = 1;
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
            var checkFill = tableOptions.filterParam.usergroup != '' ? 1 : 0;
            if (checkFill > 0) {
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
            var u = common.DataService;
            $.confirm({
                title: 'WARNING!',
                content: 'Are you sure you want to Activate all the Users in <b>' + group + '</b> group? <br><br>Make sure you are sure that you want to activate all the user in this group.',
                buttons: {
                    delete: {
                        text: 'Activate All', btnClass: 'btn btn-danger mr-1',
                        action: function () {
                            axios.post(u + '?qid=004&e=' + group)
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
            var u = common.DataService;
            overlay.show();
            $.confirm({
                title: 'WARNING!',
                content: 'Are you sure you want to Deactivate all the Users in <b>' + group + '</b> group? <br><br>Make sure you are sure that you want to deactivate all the user in this group, deactivating users means you want to deny them access to the system.',
                buttons: {
                    delete: {
                        text: 'Deactivate All', btnClass: 'btn btn-danger mr-1',
                        action: function () {
                            axios.post(u + '?qid=003&e=' + group)
                                .then(function (response) {
                                    overlay.hide();
                                    if (response.data.result_code == '201') {
                                        loadTableData();
                                        alert.Success('SUCCESS', response.data.group + ' user group has been deactivated successfully');
                                    } else {
                                        alert.Error('ERROR', 'Unable to deactivate ' + response.data.group + ' at the moment please try again later.');
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

        function showBulkUserModal() {
            bulkUserModal.value = true;
            bulkUserForm.groupName = '';
            clearAllFilter();
        }
        function hideBulkUserModal() {
            bulkUserModal.value = false;
            bulkUserForm.groupName = '';
            tableOptions.filterParam.usergroup = '';
        }

        function getsysDefaultDataSettings() {
            overlay.show();
            axios.get(common.DataService + '?qid=gen007')
                .then(function (response) {
                    if (response.data.data && response.data.data.length > 0) {
                        sysDefaultData.value = response.data.data[0];
                        getLgasLevel(response.data.data[0].stateid);
                        bulkUserForm.geoLevel = 'state';
                        bulkUserForm.geoLevelId = response.data.data[0].stateid;
                        defaultStateId.value = response.data.data[0].stateid;
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
        function getDpLevel() {
            overlay.show();
            axios.get(common.DataService + '?qid=gen006&wardid=' + geoIndicator.ward)
                .then(function (response) {
                    dpLevelData.value = (response.data && response.data.data) || [];
                    overlay.hide();
                })
                .catch(function (error) {
                    overlay.hide();
                    alert.Error('ERROR', safeMessage(error));
                });
        }
        function getAllUserGroup() {
            overlay.show();
            axios.get(common.DataService + '?qid=026')
                .then(function (response) {
                    var group = [];
                    var rows = (response.data && response.data.data) || [];
                    for (var i = 0; i < rows.length; i++) group.push(rows[i]['user_group']);
                    userGroup.value = group;
                    overlay.hide();
                })
                .catch(function (error) {
                    overlay.hide();
                    alert.Error('ERROR', safeMessage(error));
                });
        }
        function getRoleList() {
            overlay.show();
            axios.get(common.DataService + '?qid=007')
                .then(function (response) {
                    roleListData.value = (response.data && response.data.data) || [];
                    overlay.hide();
                })
                .catch(function (error) {
                    overlay.hide();
                    alert.Error('ERROR', safeMessage(error));
                });
        }

        function changeGeoLevel() {
            if (bulkUserForm.geoLevel == 'country') {
                alert.Error('ERROR', 'Invalid Geo-Level selected, please select a valid Geo-Level');
            } else if (bulkUserForm.geoLevel == 'state') {
                bulkUserForm.geoLevelId = defaultStateId.value;
            } else {
                bulkUserForm.geoLevelId = '';
                geoIndicator.lga = '';
                geoIndicator.ward = '';
                geoIndicator.cluster = '';
            }
        }

        function onSubmitBulkUserCreation() {
            var u = common.DataService;
            overlay.show();
            axios.post(u + '?qid=002', JSON.stringify(bulkUserForm))
                .then(function (response) {
                    if (response.data.result_code == '201') {
                        resetBulkUserForm();
                        bulkUserModal.value = false;
                        $('#addNewUser').modal('hide');
                        var form = $('#add-new-user')[0];
                        if (form && typeof form.reset === 'function') form.reset();
                        getAllUserGroup();
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
            tableOptions.filterParam.usergroup = '';
            getsysDefaultDataSettings();
            overlay.hide();
        }

        function refreshData() {
            paginationDefault();
            getAllUserGroup();
            loadTableData();
        }

        function downloadGroupBadge(user_group) {
            overlay.show();
            var iframe = document.createElement('iframe');
            iframe.style.display = 'none';
            iframe.src = url.value + '?qid=001&e=' + user_group;
            document.body.appendChild(iframe);
            setTimeout(function () {
                overlay.hide();
                if (iframe.parentNode) iframe.parentNode.removeChild(iframe);
            }, 5000);
        }

        // Lightweight autocomplete that wires the listeners onto a DOM input
        // by id. Preserved from the v2 component for the "Group Name" field
        // and the filter "User Group" field.
        function autocomplete(inp, arr) {
            if (!inp) return;
            var currentFocus;
            inp.addEventListener('input', function () {
                var a, b, i, val = this.value;
                closeAllLists();
                if (!val) return false;
                currentFocus = -1;
                a = document.createElement('DIV');
                a.setAttribute('id', this.id + 'autocomplete-list');
                a.setAttribute('class', 'autocomplete-items');
                this.parentNode.appendChild(a);
                for (i = 0; i < arr.length; i++) {
                    if (String(arr[i]).substr(0, val.length).toUpperCase() == val.toUpperCase()) {
                        b = document.createElement('DIV');
                        b.innerHTML = '<strong>' + arr[i].substr(0, val.length) + '</strong>';
                        b.innerHTML += arr[i].substr(val.length);
                        b.innerHTML += "<input type='hidden' value='" + arr[i] + "'>";
                        b.addEventListener('click', function () {
                            inp.value = this.getElementsByTagName('input')[0].value;
                            closeAllLists();
                        });
                        a.appendChild(b);
                    }
                }
            });
            inp.addEventListener('keydown', function (e) {
                var x = document.getElementById(this.id + 'autocomplete-list');
                if (x) x = x.getElementsByTagName('div');
                if (e.keyCode == 40) { currentFocus++; addActive(x); }
                else if (e.keyCode == 38) { currentFocus--; addActive(x); }
                else if (e.keyCode == 13) {
                    e.preventDefault();
                    if (currentFocus > -1 && x) x[currentFocus].click();
                }
            });
            function addActive(x) {
                if (!x) return false;
                removeActive(x);
                if (currentFocus >= x.length) currentFocus = 0;
                if (currentFocus < 0) currentFocus = x.length - 1;
                x[currentFocus].classList.add('autocomplete-active');
            }
            function removeActive(x) { for (var i = 0; i < x.length; i++) x[i].classList.remove('autocomplete-active'); }
            function closeAllLists(elmnt) {
                var x = document.getElementsByClassName('autocomplete-items');
                for (var i = 0; i < x.length; i++) {
                    if (elmnt != x[i] && elmnt != inp) {
                        x[i].parentNode.removeChild(x[i]);
                        tableOptions.filterParam.usergroup = inp.value;
                        bulkUserForm.groupName = inp.value;
                    }
                }
            }
            document.addEventListener('click', function () {
                var node = document.getElementById('group-nameautocomplete-list');
                if (node) node.innerHTML = '';
            });
        }

        function loadAuto()  { autocomplete(document.getElementById('group-name'), userGroup.value); }
        function autoGroup() { autocomplete(document.getElementById('usergroup'),  userGroup.value); }

        onMounted(function () {
            getGeoLevel();
            getsysDefaultDataSettings();
            getAllUserGroup();
            getDpLevel();
            getRoleList();
            loadTableData();
        });

        return {
            tableData, defaultStateId, checkToggle, filterState, filters, url,
            userGroup, errors, bulkUserModal, permission,
            tableOptions, bulkUserForm, geoIndicator,
            geoLevelData, sysDefaultData, lgaLevelData, clusterLevelData,
            wardLevelData, dpLevelData, roleListData,
            loadTableData, selectAll, uncheckAll, selectToggle, checkedBg,
            toggleFilter, selectedItems, selectedID, totalCheckedBox,
            nextPage, prevPage, resetSelected, currentPage, paginationDefault,
            changePerPage, sort, applyFilter, clearAllFilter,
            activateUserByGroup, deactivateUserByGroup,
            showBulkUserModal, hideBulkUserModal,
            getsysDefaultDataSettings, getGeoLevel, getLgasLevel, getClusterLevel,
            getWardLevel, getDpLevel, getAllUserGroup, getRoleList,
            changeGeoLevel, onSubmitBulkUserCreation, resetBulkUserForm,
            refreshData, downloadGroupBadge,
            autocomplete, loadAuto, autoGroup,
            capitalize: fmtUtils.capitalize,
            formatNumber: fmtUtils.formatNumber,
            displayDate: fmtUtils.displayDate,
            fmt: fmtUtils.fmt,
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
                    <button v-if="permission.permission_value == 3" type="button" @click="showBulkUserModal()" data-toggle="tooltip" data-placement="top" title="Create New User" data-target="#addNewUser" class="btn btn-outline-primary round">
                        <i data-feather='user-plus'></i>
                    </button>
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
                        <span class="badge badge-dark filter-box" v-if="String(filterParam).length > 0">
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
                                <div class="col-9 col-md-9">
                                    <div class="form-group autocomplete">
                                        <input type="text" autocomplete="off" v-model="tableOptions.filterParam.usergroup" @focus="autoGroup()" class="form-control usergroup" id="usergroup" placeholder="User Group Name" name="usergroup" />
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
                                    <th @click="sort(2)">User Group Name
                                        <i class="feather icon-chevron-up sort-up"   :class="(tableOptions.orderField == 2 && tableOptions.orderDir =='asc' )? 'active-sort':''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 2 && tableOptions.orderDir =='desc')? 'active-sort':''"></i>
                                    </th>
                                    <th @click="sort(1)">Total Users
                                        <i class="feather icon-chevron-up sort-up"   :class="(tableOptions.orderField == 1 && tableOptions.orderDir =='asc' )? 'active-sort':''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 1 && tableOptions.orderDir =='desc')? 'active-sort':''"></i>
                                    </th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(g, i) in tableData" :key="g.user_group || i" :class="checkedBg(g.pick)">
                                    <td>{{ g.user_group }}</td>
                                    <td>{{ parseInt(g.total).toLocaleString() }}</td>
                                    <td>
                                        <div class="dropdown">
                                            <button type="button" class="btn btn-sm dropdown-toggle hide-arrow" data-toggle="dropdown">
                                                <i class="feather icon-more-vertical"></i>
                                            </button>
                                            <div class="dropdown-menu">
                                                <a v-if="permission.permission_value == 3" class="dropdown-item" href="javascript:void(0);" @click="activateUserByGroup(g.user_group)">
                                                    <i class="feather icon-user-check mr-50"></i><span>Activate Users</span>
                                                </a>
                                                <a v-if="permission.permission_value == 3" class="dropdown-item" href="javascript:void(0);" @click="deactivateUserByGroup(g.user_group)">
                                                    <i class="feather icon-user-x mr-50"></i><span>Deactivate Users</span>
                                                </a>
                                                <a class="dropdown-item" @click="downloadGroupBadge(g.user_group)" href="javascript:void(0)">
                                                    <i class="feather icon-download mr-50"></i><span>Download Badge</span>
                                                </a>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="tableData.length === 0"><td class="text-center pt-2" colspan="4"><small>No User Group Added</small></td></tr>
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

            <!-- Modal to add new user -->
            <div v-if="bulkUserModal" class="modal modal-slide-in new-user-modal" :class="bulkUserModal? 'show' : 'fade'" id="addNewUser" style="display: block; padding-right: 17px;">
                <div class="modal-dialog modal-scrollable modal-xl">
                    <form id="add-new-user" class="add-new-user modal-content pt-0" @submit.stop.prevent="onSubmitBulkUserCreation()">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close" @click="hideBulkUserModal()">×</button>
                        <div class="modal-header mb-1">
                            <h5 class="modal-title" id="exampleModalLabel">Create New User</h5>
                        </div>
                        <div class="modal-body flex-grow-1">
                            <div class="form-group">
                                <label class="form-label" for="total-user">Total Number of User</label>
                                <input type="number" id="total-user" class="form-control total-user" required v-model="bulkUserForm.totalUser" placeholder="Total Number of User" name="total-user" />
                            </div>
                            <div class="form-group autocomplete">
                                <label class="form-label" for="group-name">Group Name</label>
                                <input type="text" autocomplete="off" class="form-control" @focus="loadAuto()" required id="group-name" v-model="bulkUserForm.groupName" placeholder="Group Name" name="group-name" />
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="password">Password</label>
                                <input type="text" required id="password" class="form-control password" v-model="bulkUserForm.password" placeholder="Password" name="password" />
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="role">Role</label>
                                <select v-model="bulkUserForm.roleid" class="form-control role">
                                    <option value="">No Role</option>
                                    <option v-for="r in roleListData" :value="r.roleid" :key="r.roleid">{{ r.role }}</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="user-role">Geo Level</label>
                                <select id="user-role" @change="changeGeoLevel()" class="form-control" v-model="bulkUserForm.geoLevel">
                                    <option v-for="geo in geoLevelData" :value="geo.geo_level" :key="geo.geo_level">{{ geo.geo_level }}</option>
                                </select>
                            </div>
                            <div class="form-group" v-if="bulkUserForm.geoLevel == 'state'">
                                <label class="form-label" for="user-role">State</label>
                                <select id="user-role" placeholder="Select Geo Level" class="form-control" v-model="bulkUserForm.geoLevelId">
                                    <option :value="sysDefaultData.stateid">{{ sysDefaultData.state }}</option>
                                </select>
                            </div>
                            <div class="form-group" v-if="bulkUserForm.geoLevel == 'lga'">
                                <label class="form-label" for="user-role">LGA List</label>
                                <select id="user-role" class="form-control" v-model="bulkUserForm.geoLevelId">
                                    <option v-for="lga in lgaLevelData" :value="lga.lgaid" :key="lga.lgaid">{{ lga.lga }}</option>
                                </select>
                            </div>
                            <div v-if="bulkUserForm.geoLevel == 'cluster'">
                                <div class="form-group">
                                    <label class="form-label" for="user-role">LGA List</label>
                                    <select id="user-role" class="form-control" @change="getClusterLevel()" v-model="geoIndicator.cluster">
                                        <option v-for="lga in lgaLevelData" :value="lga.lgaid" :key="lga.lgaid">{{ lga.lga }}</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Cluster</label>
                                    <select class="form-control" v-model="bulkUserForm.geoLevelId">
                                        <option v-for="g in clusterLevelData" :value="g.clusterid" :key="g.clusterid">{{ g.cluster }}</option>
                                    </select>
                                </div>
                            </div>
                            <div v-if="bulkUserForm.geoLevel == 'ward'">
                                <div class="form-group">
                                    <label class="form-label">LGA List</label>
                                    <select class="form-control" @change="getWardLevel()" v-model="geoIndicator.lga">
                                        <option v-for="lga in lgaLevelData" :value="lga.lgaid" :key="lga.lgaid">{{ lga.lga }}</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Ward</label>
                                    <select class="form-control" v-model="bulkUserForm.geoLevelId">
                                        <option v-for="g in wardLevelData" :value="g.wardid" :key="g.wardid">{{ g.ward }}</option>
                                    </select>
                                </div>
                            </div>
                            <div v-if="bulkUserForm.geoLevel == 'dp'">
                                <div class="form-group">
                                    <label class="form-label">LGA List</label>
                                    <select class="form-control" @change="getWardLevel()" v-model="geoIndicator.lga">
                                        <option v-for="lga in lgaLevelData" :value="lga.lgaid" :key="lga.lgaid">{{ lga.lga }}</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Ward</label>
                                    <select class="form-control" @change="getDpLevel()" v-model="geoIndicator.ward">
                                        <option v-for="g in wardLevelData" :value="g.wardid" :key="g.wardid">{{ g.ward }}</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">DP List</label>
                                    <select class="form-control" v-model="bulkUserForm.geoLevelId">
                                        <option v-for="g in dpLevelData" :value="g.dpid" :key="g.dpid">{{ g.dp }}</option>
                                    </select>
                                </div>
                            </div>
                            <div class="mt-2">
                                <button type="submit" class="btn btn-primary mr-1 data-submit">Save</button>
                                <button type="reset" class="btn btn-outline-secondary" data-dismiss="modal" @click="hideBulkUserModal()">Cancel</button>
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
