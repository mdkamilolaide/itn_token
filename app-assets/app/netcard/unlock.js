/**
 * Netcard / Unlock — Vue 3 Composition API in place.
 * Two components — page-body and necard_movement.
 *
 * necard_movement: pick LGA → Ward, view HHM balances on each device,
 * unlock the requested e-Netcards back to the system (qid=215).
 */

const { ref, reactive, onMounted, onBeforeUnmount } = Vue;
const { useApp, useFormat, bus, safeMessage } = window.utils;

const PageBody = {
    setup() {
        const page = ref('allocation');
        function gotoPageHandler(data) { page.value = data.page; }
        onMounted(function () { bus.on('g-event-goto-page', gotoPageHandler); });
        onBeforeUnmount(function () { bus.off('g-event-goto-page', gotoPageHandler); });
        return { page };
    },
    template: `
        <div>
            <div class="content-body">
                <div v-show="page == 'allocation'"><necard_movement/></div>
            </div>
        </div>
    `,
};

const NecardMovement = {
    setup() {
        const fmtUtils = useFormat();

        const tableData = ref([]);
        const checkToggle = ref(false);
        const filterState = ref(false);
        const filters = ref(false);
        const permission = ref(
            (typeof getPermission === 'function')
                ? (getPermission(typeof per !== 'undefined' ? per : null, 'enetcard_unlock') || { permission_value: 0 })
                : { permission_value: 0 }
        );
        const url = ref(window.common && window.common.TableService);
        const tableOptions = reactive({
            total: 1, pageLength: 1, perPage: 10, currentPage: 1,
            orderDir: 'desc', orderField: 0, limitStart: 0,
            isNext: false, isPrev: false,
            aLength: [10, 20, 50, 100, 150, 200],
            filterParam: { movementType: 'Forward' },
        });
        const currentWardBalance = reactive({
            wardName: '', balance: 0, disbursed: 0, received: 0,
        });
        const wardMovementForm = reactive({
            totalNetcard: 1, wardMoveBtn: '', wardMoveModal: false,
            lgaid: '', wardid: '', wardName: '', wardBalance: '',
        });
        const movementForm = reactive({ geoLevel: '', geoLevelId: 0 });
        const geoIndicator = reactive({
            state: 50, currentLevelId: 0,
            lga: '', cluster: '', ward: '',
        });
        const geoLevelData = ref([]);
        const sysDefaultData = ref([]);
        const lgaLevelData = ref([]);
        const clusterLevelData = ref([]);
        const wardLevelData = ref([]);
        const lgaNetBalancesData = ref([]);
        const wardNetBalancesData = ref([]);
        const hhmBalanacesData = ref([]);
        const isLgabalance = ref(true);
        const isHHMbalance = ref(true);
        const allStatistics = reactive({
            stateBalance: 0, lgaBalance: 0, wardBalance: 0, mobilizer: 0,
        });

        function getsysDefaultDataSettings() {
            overlay.show();
            axios.get(common.DataService + '?qid=gen007')
                .then(function (response) {
                    if (response.data.data && response.data.data.length > 0) {
                        sysDefaultData.value = response.data.data[0];
                        getLgasLevel(response.data.data[0].stateid);
                        movementForm.geoLevel = 'state';
                        movementForm.geoLevelId = response.data.data[0].stateid;
                    }
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
        function getLgasNetBalances() {
            overlay.show();
            axios.post(common.DataService + '?qid=206')
                .then(function (response) {
                    lgaNetBalancesData.value = (response.data && response.data.data) || [];
                    overlay.hide();
                })
                .catch(function (error) {
                    overlay.hide();
                    alert.Error('ERROR', safeMessage(error));
                });
        }
        function getWardLevel() {
            overlay.show();
            wardMovementForm.wardid = '';
            axios.get(common.DataService + '?qid=gen005&e=' + wardMovementForm.lgaid)
                .then(function (response) {
                    wardLevelData.value = (response.data && response.data.data) || [];
                    overlay.hide();
                })
                .catch(function (error) {
                    overlay.hide();
                    alert.Error('ERROR', safeMessage(error));
                });
        }
        function getWardData(event) {
            overlay.show();
            wardMovementForm.wardid = '';
            wardMovementForm.lgaid = event.target.options[event.target.options.selectedIndex].value;
            axios.get(common.DataService + '?qid=gen005&lgaid=' + wardMovementForm.lgaid + '&e=' + wardMovementForm.lgaid)
                .then(function (response) {
                    wardNetBalancesData.value = (response.data && response.data.data) || [];
                    wardLevelData.value = (response.data && response.data.data) || [];
                    overlay.hide();
                })
                .catch(function (error) {
                    overlay.hide();
                    alert.Error('ERROR', safeMessage(error));
                });
        }
        function getHhmBalances(event) {
            var optText = event.target.options[event.target.options.selectedIndex].text;
            var trimmed = optText.trim().replace(',', '');
            wardMovementForm.wardName = trimmed.split('-')[0];
            var rawBal = trimmed.split('-')[1];
            wardMovementForm.wardBalance = rawBal == '' ? 0 : parseInt(rawBal);
            currentWardBalance.wardName = optText;
            getHHMOfflineBalancesList();
            getCurrentWardBalance();
            overlay.show();
        }
        function refreshData() {
            if (currentWardBalance.wardName != '') getHHMOfflineBalancesList();
        }
        function getHHMOfflineBalancesList() {
            overlay.show();
            axios.get(common.DataService + '?qid=216&wardid=' + wardMovementForm.wardid)
                .then(function (response) {
                    hhmBalanacesData.value = (response.data && response.data.data) || [];
                    overlay.hide();
                })
                .catch(function (error) {
                    overlay.hide();
                    alert.Error('ERROR', safeMessage(error));
                });
        }
        function getCurrentWardBalance() {
            axios.get(common.DataService + '?qid=214&wardid=' + wardMovementForm.wardid)
                .then(function (response) {
                    var row = (response.data && response.data.data && response.data.data[0]) || {};
                    currentWardBalance.balance = row.balance ? parseInt(row.balance) : 0;
                    wardMovementForm.wardBalance = currentWardBalance.balance;
                    currentWardBalance.received = row.received ? parseInt(row.received) : 0;
                    currentWardBalance.disbursed = row.disbursed ? parseInt(row.disbursed) : 0;
                    overlay.hide();
                })
                .catch(function (error) {
                    overlay.hide();
                    alert.Error('ERROR', safeMessage(error));
                });
        }

        function scroll() {
            try {
                var sidebarMenuList = $('.main-body');
                if ($.app && $.app.menu && !$.app.menu.is_touch_device()) {
                    if (sidebarMenuList.length > 0) {
                        for (var i = 0; i < sidebarMenuList.length; ++i) {
                            new PerfectScrollbar(sidebarMenuList[i], { theme: 'dark' });
                        }
                    }
                } else {
                    sidebarMenuList.css('overflow', 'scroll');
                }
            } catch (e) { /* swallow */ }
        }
        function onlyNumber(event) {
            var keyCode = event.keyCode || event.which;
            if ((keyCode < 48 || keyCode > 57) && keyCode == 46) event.preventDefault();
        }

        function unlockNetcardFromDevice(userid, device_serial, total) {
            var requester_userid = document.getElementById('v_g_id').value;
            if (total <= 0) {
                alert.Error('Zero Balance', "You don't have an e-Netcard Residing on this device");
                return;
            }
            $.confirm({
                title: 'WARNING!',
                content: 'Are you sure you want to Unlock <b>' + total + '</b> e-Netcard on the Device with Serial <b>' + device_serial + '</b>?',
                buttons: {
                    delete: {
                        text: 'Unlock e-Netcard',
                        btnClass: 'btn btn-danger mr-1 text-capitalize',
                        action: function () {
                            axios.post(
                                common.DataService +
                                '?qid=215&device_serial=' + device_serial +
                                '&userid=' + userid +
                                '&requester_userid=' + requester_userid
                            )
                                .then(function (response) {
                                    if (response.data.result_code == '200') {
                                        refreshData();
                                        getCurrentWardBalance();
                                        alert.Success('Success', '<b>' + response.data.total + '</b> e-Netcards has been successfully Unlocked on Device with Serial No: <b>' + device_serial + '</b>');
                                        overlay.hide();
                                    } else {
                                        overlay.hide();
                                        alert.Error('Error', response.data.message);
                                    }
                                })
                                .catch(function (error) {
                                    alert.Error('ERROR', safeMessage(error));
                                    overlay.hide();
                                });
                        },
                    },
                    cancel: function () { overlay.hide(); },
                },
            });
        }

        onMounted(function () {
            getsysDefaultDataSettings();
            bus.on('g-event-update', refreshData);
            $('#todo-search').on('keyup', function () {
                var value = $(this).val().toLowerCase();
                $('#moveTable tbody tr').filter(function () {
                    $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
                });
            });
            $('#todo-search1').on('keyup', function () {
                var value = $(this).val().toLowerCase();
                $('#moveTable1 tbody tr').filter(function () {
                    $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
                });
            });
            try { $('[data-toggle="tooltip"]').tooltip({ container: 'body' }); } catch (e) {}
            scroll();
        });
        onBeforeUnmount(function () {
            bus.off('g-event-update', refreshData);
        });

        return {
            tableData, checkToggle, filterState, filters, permission, url,
            tableOptions, currentWardBalance, wardMovementForm, movementForm,
            geoIndicator, geoLevelData, sysDefaultData, lgaLevelData,
            clusterLevelData, wardLevelData, lgaNetBalancesData, wardNetBalancesData,
            hhmBalanacesData, isLgabalance, isHHMbalance, allStatistics,
            getsysDefaultDataSettings, getLgasLevel, getLgasNetBalances,
            getWardLevel, getWardData, getHhmBalances, refreshData,
            getHHMOfflineBalancesList, getCurrentWardBalance,
            unlockNetcardFromDevice, onlyNumber,
            capitalize: fmtUtils.capitalize,
            formatNumber: fmtUtils.formatNumber,
            displayDate: fmtUtils.displayDate,
        };
    },
    template: `
        <div class="row" id="basic-table" v-cloak>
            <div class="col-md-12 col-sm-12 col-12 mb-1">
                <h2 class="content-header-title header-txt float-left mb-0">e-Netcard</h2>
                <div class="breadcrumb-wrapper">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../netcard">Home</a></li>
                        <li class="breadcrumb-item active">e-Netcard Unlock</li>
                    </ol>
                </div>
            </div>

            <div class="col-md-12 col-sm-12 col-12" v-if="permission.permission_value == 3">
                <div class="card p-0">
                    <div class="card-body p-0">
                        <div class="allot mt-0">
                            <div class="left-side">
                                <h6 class="mb-1">HHM Balances</h6>
                                <div>
                                    <div class="form-group">
                                        <label class="form-label">Choose LGA</label>
                                        <select required class="form-control" @change="getWardLevel($event)" v-model="wardMovementForm.lgaid">
                                            <option value="" selected>Choose LGA to View</option>
                                            <option v-for="(lga, i) in lgaLevelData" :key="lga.lgaid" :value="lga.lgaid">{{ lga.lga }}</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Choose a Ward</label>
                                        <select class="form-control" v-model="wardMovementForm.wardid" @change="getHhmBalances($event)">
                                            <option value="" selected>Choose a Ward</option>
                                            <option v-for="(ward, i) in wardLevelData" :key="ward.wardid" :value="ward.wardid">{{ ward.ward }}</option>
                                        </select>
                                    </div>

                                    <div class="e-details pt-2" v-if="wardMovementForm.wardid != ''">
                                        <small class="mt-3"><span class="font-weight-bolder text-primary" v-text="currentWardBalance.wardName"></span> e-Netcard Details</small>
                                        <hr class="invoice-spacing mt-0">
                                        <div class="invoice-terms mt-1"><div class="d-flex justify-content-between"><label class="invoice-terms-title mb-0">Total Received: </label><div class="custom-control badge badge-light-success" v-text="currentWardBalance.received"></div></div></div>
                                        <div class="invoice-terms mt-1"><div class="d-flex justify-content-between"><label class="invoice-terms-title mb-0">Total Disbursed: </label><div class="custom-control badge badge-light-info" v-text="currentWardBalance.disbursed"></div></div></div>
                                        <div class="invoice-terms mt-1">
                                            <hr class="my-50">
                                            <div class="d-flex justify-content-between"><label class="invoice-terms-title mb-0">Balance: </label><div class="custom-control badge badge-light-warning" v-text="currentWardBalance.balance"></div></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="right-side">
                                <div class="allot-mobile-form">
                                    <div class="form-group mb-50">
                                        <label class="form-label">Choose LGA</label>
                                        <select required class="form-control" @change="getWardLevel($event)" v-model="wardMovementForm.lgaid">
                                            <option value="" selected>Choose LGA to View</option>
                                            <option v-for="(lga, i) in lgaLevelData" :key="lga.lgaid" :value="lga.lgaid">{{ lga.lga }}</option>
                                        </select>
                                    </div>
                                    <div class="form-group mb-50">
                                        <label class="form-label">Choose a Ward</label>
                                        <select class="form-control" v-model="wardMovementForm.wardid" @change="getHhmBalances($event)">
                                            <option value="" selected>Choose a Ward</option>
                                            <option v-for="(ward, i) in wardLevelData" :key="ward.wardid" :value="ward.wardid">{{ ward.ward }}</option>
                                        </select>
                                    </div>

                                    <div class="e-details pt-1" v-if="wardMovementForm.wardid != ''">
                                        <small class="mt-3"><span class="font-weight-bolder text-primary" v-text="currentWardBalance.wardName"></span> e-Netcard Details</small>
                                        <hr class="invoice-spacing mt-0">
                                        <div class="invoice-terms mt-1"><div class="d-flex justify-content-between"><label class="invoice-terms-title mb-0">Total Received: </label><div class="custom-control badge badge-light-success" v-text="currentWardBalance.received"></div></div></div>
                                        <div class="invoice-terms mt-1"><div class="d-flex justify-content-between"><label class="invoice-terms-title mb-0">Total Disbursed: </label><div class="custom-control badge badge-light-info" v-text="currentWardBalance.disbursed"></div></div></div>
                                        <div class="invoice-terms mt-1">
                                            <hr class="my-50">
                                            <div class="d-flex justify-content-between"><label class="invoice-terms-title mb-0">Balance: </label><div class="custom-control badge badge-light-warning" v-text="currentWardBalance.balance"></div></div>
                                        </div>
                                    </div>
                                </div>

                                <div class="app-fixed-search d-flex align-items-center">
                                    <div class="d-flex align-content-center justify-content-between w-100">
                                        <div class="input-group input-group-merge">
                                            <div class="input-group-prepend"><span class="input-group-text"><i data-feather="search" class="text-muted"></i></span></div>
                                            <input type="text" class="form-control search" id="todo-search1" placeholder="Search HHM" aria-label="Search..." aria-describedby="todo-search1" />
                                            <div class="input-group-append">
                                                <button class="btn" type="button" @click="refreshData()"><i class="feather icon-refresh-cw text-primary"></i></button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="main-body">
                                    <div v-if="isLgabalance == true" class="mt-0">
                                        <table class="table table-hover scroll-now" id="moveTable1">
                                            <thead>
                                                <tr>
                                                    <th>Login ID</th>
                                                    <th>Fullname</th>
                                                    <th>Location</th>
                                                    <th>HHM Balance</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr v-for="(g, i) in hhmBalanacesData" :key="g.userid || i">
                                                    <td>{{ g.loginid }}</td>
                                                    <td v-html="g.fullname ? g.fullname : 'Not Assigned'"></td>
                                                    <td>
                                                        <i class="feather" :class="g.device_serial ? 'icon-smartphone bg-light-info rounded' : 'icon-cloud bg-light-success rounded'"></i>
                                                        <small v-html="g.device_serial ? ' (' + g.device_serial + ')' : ' (Online)'"></small>
                                                    </td>
                                                    <td>{{ g.balance }}</td>
                                                    <td>
                                                        <button v-if="permission.permission_value == 3" type="button" @click="unlockNetcardFromDevice(g.userid, g.device_serial, g.balance)" class="btn btn-sm btn-primary p-50 waves-float waves-effect">
                                                            <i class="feather icon-unlock mr-25"></i><span>Unlock</span>
                                                        </button>
                                                        <button v-else type="button" class="btn btn-sm btn-Secondary p-50 waves-float waves-effect">
                                                            <i class="feather icon-unlock mr-25"></i><span>Unlock</span>
                                                        </button>
                                                    </td>
                                                </tr>
                                                <tr v-if="hhmBalanacesData.length == 0">
                                                    <td class="text-center text-info pt-4 pb-4" colspan="5">
                                                        <small>No Ward Choosen/No Pending e-Netcard on devices <b class="text-primary" v-text="wardMovementForm.wardName ? ' in ' + wardMovementForm.wardName + ' Ward' : ''"></b></small>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
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
    .component('page-body', PageBody)
    .component('necard_movement', NecardMovement)
    .mount('#app');
