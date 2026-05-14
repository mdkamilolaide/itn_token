/**
 * SMC / Logistics — reusable_table_template.js — Vue 3 Composition API.
 * A scaffold copy of the users-list table; not directly wired to a page
 * route in system_structure.json but kept in case any submodule loads it.
 */

const { ref, reactive, onMounted, onBeforeUnmount } = Vue;
const { useApp, useFormat, bus, safeMessage } = window.utils;

const appState = Vue.reactive({
    pageState: { page: 'table', title: '' },
    permission: (typeof getPermission === 'function')
        ? (getPermission(typeof per !== 'undefined' ? per : null, 'smc') || { permission_value: 0 })
        : { permission_value: 0 },
});

const PageTable = {
    setup() {
        const fmtUtils = useFormat();

        const url = ref(window.common && window.common.BadgeService);
        const tableData = ref([]);
        const defaultStateId = ref('');
        const roleListData = ref([]);
        const geoData = ref([]);
        const checkToggle = ref(false);
        const filterState = ref(false);
        const filters = ref(false);
        const tableOptions = reactive({
            total: 1, pageLength: 1, perPage: 10, currentPage: 1,
            orderDir: 'asc', orderField: 0, limitStart: 0,
            isNext: false, isPrev: false,
            aLength: [10, 20, 50, 100, 150, 200],
            filterParam: {
                user_status: '', loginid: '', fullname: '', user_group: '',
                phoneno: '', geo_level: '', geo_level_id: '', geo_string: '',
                bank_status: '', role_id: '', role: '',
            },
        });
        const geoIndicator = reactive({
            state: 50, currentLevelId: 0,
            lga: '', cluster: '', ward: '',
        });
        const geoLevelData = ref([]);
        const sysDefaultData = ref([]);
        const lgaLevelData = ref([]);
        const clusterLevelData = ref([]);
        const wardLevelData = ref([]);
        const dpLevelData = ref([]);
        const userPass = reactive({ pass: '', loginid: '', name: '', isBulk: false });

        const reloadUserListOnUpdate = () => { paginationDefault(); loadTableData(); };
        const loadTableData = () => {
            overlay.show();
            axios.get(
                common.TableService +
                '?qid=001&draw=' + tableOptions.currentPage +
                '&order_column=' + tableOptions.orderField +
                '&length=' + tableOptions.perPage +
                '&start=' + tableOptions.limitStart +
                '&order_dir=' + tableOptions.orderDir +
                '&ac=' + tableOptions.filterParam.user_status +
                '&lo=' + tableOptions.filterParam.loginid +
                '&na=' + tableOptions.filterParam.fullname +
                '&gr=' + tableOptions.filterParam.user_group +
                '&ph=' + tableOptions.filterParam.phoneno +
                '&gl=' + tableOptions.filterParam.geo_level +
                '&gl_id=' + tableOptions.filterParam.geo_level_id +
                '&bv=' + tableOptions.filterParam.bank_status +
                '&ri=' + tableOptions.filterParam.role_id
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
        const checkedBg = (pickOne) => { return pickOne != '' ? 'bg-select' : ''; };
        const toggleFilter = () => {
            if (filterState.value === false) filters.value = false;
            return (filterState.value = !filterState.value);
        }
        const selectedItems = () => { return tableData.value.filter(r => r.pick); };
        const selectedID = () => { return tableData.value.filter(r => r.pick).map(r => r.userid); };

        const paginationDefault = () => {
            tableOptions.pageLength = Math.ceil(tableOptions.total / tableOptions.perPage);
            tableOptions.limitStart = Math.ceil((tableOptions.currentPage - 1) * tableOptions.perPage);
            tableOptions.isNext = tableOptions.currentPage < tableOptions.pageLength;
            tableOptions.isPrev = tableOptions.currentPage > 1;
        }
        const resetSelected = () => { uncheckAll(); checkToggle.value = false; totalCheckedBox(); };
        const nextPage = () => { resetSelected(); tableOptions.currentPage += 1; paginationDefault(); loadTableData(); };
        const prevPage = () => { resetSelected(); tableOptions.currentPage -= 1; paginationDefault(); loadTableData(); };
        const currentPage = () => {
            paginationDefault();
            if (tableOptions.currentPage < 1)                            alert.Error('ERROR', "The Page requested doesn't exist");
            else if (tableOptions.currentPage > tableOptions.pageLength) alert.Error('ERROR', "The Page requested doesn't exist");
            else                                                         loadTableData();
        }
        const changePerPage = (val) => {
            resetSelected();
            tableOptions.currentPage = 1;
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
            checkFill += tableOptions.filterParam.user_status != '' ? 1 : 0;
            checkFill += tableOptions.filterParam.loginid != '' ? 1 : 0;
            checkFill += tableOptions.filterParam.fullname != '' ? 1 : 0;
            checkFill += tableOptions.filterParam.user_group != '' ? 1 : 0;
            checkFill += tableOptions.filterParam.phoneno != '' ? 1 : 0;
            checkFill += tableOptions.filterParam.geo_level != '' ? 1 : 0;
            checkFill += tableOptions.filterParam.bank_status != '' ? 1 : 0;
            checkFill += tableOptions.filterParam.role_id != '' ? 1 : 0;
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
            if (column_name == 'geo_string') {
                tableOptions.filterParam.geo_level = '';
                tableOptions.filterParam.geo_level_id = '';
                tableOptions.filterParam.geo_string = '';
            }
            if (column_name == 'role') {
                tableOptions.filterParam.role_id = '';
                tableOptions.filterParam.role = '';
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
            filters.value = false;
            try { $('.select2').val('').trigger('change'); } catch (e) {}
            tableOptions.filterParam.user_status = '';
            tableOptions.filterParam.loginid = '';
            tableOptions.filterParam.fullname = '';
            tableOptions.filterParam.user_group = '';
            tableOptions.filterParam.phoneno = '';
            tableOptions.filterParam.geo_level = '';
            tableOptions.filterParam.geo_level_id = '';
            tableOptions.filterParam.geo_string = '';
            tableOptions.filterParam.bank_status = '';
            tableOptions.filterParam.role_id = '';
            tableOptions.filterParam.role = '';
            paginationDefault();
            loadTableData();
        }
        const goToDetail = (userid, user_status) => {
            bus.emit('g-event-goto-page', {
                userid: userid, page: 'detail',
                user_status: user_status, role: roleListData.value,
            });
        }
        const getRoleList = () => {
            overlay.show();
            axios.get(common.DataService + '?qid=007')
                .then(response => {
                    roleListData.value = (response.data && response.data.data) || [];
                    overlay.hide();
                })
                .catch(error => {
                    overlay.hide();
                    alert.Error('ERROR', safeMessage(error));
                });
        }
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
            tableOptions.filterParam.geo_string = row.geo_string;
        }
        const refreshData = () => { paginationDefault(); loadTableData(); };
        const downloadBadge = (uid) => {
            overlay.show();
            window.open(url.value + '?qid=002&e=' + uid, '_parent');
            overlay.hide();
        }
        const downloadBadges = () => {
            overlay.show();
            if (parseInt(selectedID().length) > 0) {
                window.open(url.value + '?qid=003&e=' + selectedID(), '_parent');
            } else {
                alert.Error('Badge Download Failed', 'No user selected');
            }
            overlay.hide();
        }
        const getGeoLevel = () => {
            overlay.show();
            axios.get(common.DataService + '?qid=gen001')
                .then(response => {
                    geoLevelData.value = (response.data && response.data.data) || [];
                    overlay.hide();
                })
                .catch(error => {
                    overlay.hide();
                    alert.Error('ERROR', safeMessage(error));
                });
        }
        const totalCheckedBox = () => {
            var total = selectedID().length;
            var el = document.getElementById('total-selected');
            if (!el) return;
            if (total > 0) {
                el.innerHTML = '<span id="selected-counter" class="badge badge-primary btn-icon"><span class="badge badge-success">' + total + '</span> Selected</span>';
            } else {
                el.replaceChildren();
            }
        }
        const exportUserData = async () => {
            var qs =
                '&draw=' + tableOptions.currentPage +
                '&order_column=' + tableOptions.orderField +
                '&length=' + tableOptions.perPage +
                '&start=' + tableOptions.limitStart +
                '&order_dir=' + tableOptions.orderDir +
                '&ac=' + tableOptions.filterParam.user_status +
                '&lo=' + tableOptions.filterParam.loginid +
                '&na=' + tableOptions.filterParam.fullname +
                '&gr=' + tableOptions.filterParam.user_group +
                '&ph=' + tableOptions.filterParam.phoneno +
                '&gl=' + tableOptions.filterParam.geo_level +
                '&gl_id=' + tableOptions.filterParam.geo_level_id +
                '&bv=' + tableOptions.filterParam.bank_status +
                '&ri=' + tableOptions.filterParam.role_id;
            var veriUrl = 'qid=014' + qs;
            var dlString = 'qid=001' + qs;
            var filename =
                (tableOptions.filterParam.geo_string ? tableOptions.filterParam.geo_string : 'Recent ') + ' ' +
                (tableOptions.filterParam.loginid ? tableOptions.filterParam.loginid : 'Recent ') +
                ' User List';
            overlay.show();

            var count = await new Promise(resolve => {
                $.ajax({
                    url: common.DataService, type: 'POST', data: veriUrl, dataType: 'json',
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
                        url: common.ExportService, type: 'POST', data: dlString,
                        success: (data) => { resolve(data); },
                    });
                });
                var exportData = JSON.parse(outcome);
                if (window.Jhxlsx && typeof window.Jhxlsx.export === 'function') {
                    window.Jhxlsx.export(exportData, { fileName: filename });
                }
            }
            resetSelected();
            overlay.hide();
        }
        const checkIfAndReturnEmpty = (data) => { return data === null || data === '' ? '' : data; };

        onMounted(() => {
            getGeoLocation();
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
            } catch (e) {}
            loadTableData();
            getGeoLevel();
            bus.on('g-event-update-user', reloadUserListOnUpdate);
        });
        onBeforeUnmount(() => {
            bus.off('g-event-update-user', reloadUserListOnUpdate);
        });

        return {
            appState, url, tableData, defaultStateId, roleListData, geoData,
            checkToggle, filterState, filters, tableOptions, geoIndicator,
            geoLevelData, sysDefaultData, lgaLevelData, clusterLevelData,
            wardLevelData, dpLevelData, userPass,
            reloadUserListOnUpdate, loadTableData,
            selectAll, uncheckAll, selectToggle, checkedBg, toggleFilter,
            selectedItems, selectedID,
            paginationDefault, resetSelected, nextPage, prevPage, currentPage,
            changePerPage, sort, applyFilter, removeSingleFilter, clearAllFilter,
            goToDetail, getRoleList, getGeoLocation, setLocation, refreshData,
            downloadBadge, downloadBadges, getGeoLevel, totalCheckedBox,
            exportUserData, checkIfAndReturnEmpty,
            capitalize: fmtUtils.capitalize,
            formatNumber: fmtUtils.formatNumber,
        };
    },
    template: `
        <div class="row" id="basic-table">
            <div class="col-md-8 col-sm-12 col-12 mb-0">
                <h2 class="content-header-title header-txt float-left mb-0">SMC</h2>
                <div class="breadcrumb-wrapper">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../smc/logistics">Logistics</a></li>
                        <li class="breadcrumb-item active">Issue</li>
                    </ol>
                    <span id="total-selected"></span>
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
                        <span class="badge badge-dark filter-box" v-if="String(filterParam).length > 0 && i != 'geo_level' && i != 'geo_level_id' && i != 'role_id'" @click="removeSingleFilter(i)">{{ capitalize(i) }}: {{ filterParam }} <i class="feather icon-x"></i></span>
                    </template>
                    <a href="#" class="float-right clear-filter" @click="clearAllFilter()">Clear</a>
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
                                        <select class="select2 form-control" @change="setLocation()" placeholder="Geo Location">
                                            <option value="">Select Geo Location</option>
                                            <option v-for="(g, i) in geoData" :key="i" :value="i">{{ g.geo_string }}</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-12 col-sm-4 col-md-3 col-lg-3">
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
                                    <th width="60px">
                                        <div class="custom-control custom-checkbox checkbox">
                                            <input type="checkbox" class="custom-control-input" :checked="checkToggle" @change="selectToggle(); totalCheckedBox()" id="all-check" />
                                            <label class="custom-control-label" for="all-check"></label>
                                        </div>
                                    </th>
                                    <th @click="sort(0)" class="pl-0">Login ID</th>
                                    <th @click="sort(8)" class="pl-1">Fullname</th>
                                    <th @click="sort(5)" class="pl-1">Role</th>
                                    <th @click="sort(16)" class="pl-1">Geo String</th>
                                    <th @click="sort(12)" class="pl-1">Status</th>
                                    <th class="pl-1 pr-1"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(g, i) in tableData" :key="g.userid || i" :class="checkedBg(g.pick)">
                                    <td>
                                        <div class="custom-control custom-checkbox checkbox">
                                            <input type="checkbox" class="custom-control-input" :id="g.loginid" v-model="g.pick" @change="totalCheckedBox()" />
                                            <label class="custom-control-label" :for="g.loginid"></label>
                                        </div>
                                    </td>
                                    <td class="pl-0">{{ g.loginid }}</td>
                                    <td class="pl-1">{{ g.first }} {{ g.middle }} {{ g.last }}</td>
                                    <td class="pl-1">
                                        <div class="d-flex flex-column">
                                            <span class="text-primary fw-bolder" v-html="g.role ? g.role : 'Role Not Assigned'"></span>
                                            <small class="text-muted" v-html="g.user_group ? g.user_group : ''"></small>
                                        </div>
                                    </td>
                                    <td class="pl-1">
                                        <div class="d-flex flex-column">
                                            <small class="text-primary" v-html="g.geo_level ? g.geo_level.toUpperCase() : 'Geo Not Assigned'"></small>
                                            <small class="text-muted" v-html="g.geo_string ? capitalize(g.geo_string) : ''"></small>
                                        </div>
                                    </td>
                                    <td class="pl-1"><span class="badge rounded-pill font-small-1" :class="g.active == 1 ? 'bg-success' : 'bg-danger'">{{ g.active == 1 ? 'Active' : 'Inactive' }}</span></td>
                                    <td class="pl-1 pr-1">
                                        <div class="dropdown">
                                            <button type="button" class="btn btn-sm dropdown-toggle hide-arrow" data-toggle="dropdown"><i class="feather icon-more-vertical"></i></button>
                                            <div class="dropdown-menu">
                                                <a class="dropdown-item" href="javascript:void(0);" @click="goToDetail(g.userid, g.active)"><i class="feather icon-eye mr-50"></i><span>Details</span></a>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="tableData.length == 0"><td class="text-center pt-2" colspan="7"><small>No User Added</small></td></tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="card-footer">
                        <div class="content-fluid">
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
                    </div>
                    <div class="mb-50"></div>
                </div>
            </div>
        </div>
    `,
};

useApp({
    template: `
        <div>
            <div v-show="appState.pageState.page == 'table'"><page-table/></div>
            <div v-show="appState.pageState.page == 'create-issues'"><h1>Others</h1></div>
        </div>
    `,
    setup() { return { appState }; },
})
    .component('page-table', PageTable)
    .mount('#app');
