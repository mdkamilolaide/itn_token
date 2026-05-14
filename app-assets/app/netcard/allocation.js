/**
 * Netcard / Allocation — Vue 3 Composition API in place.
 * Two components — page-body and necard_movement.
 *
 * necard_movement: paginated movement history (qid=202 forward / 203
 * reverse / 204 online-reverse) with filter/sort/page UX, plus a
 * full HHM allocation modal that:
 *   - Allocates e-Netcards from a ward to selected HH Mobilizers
 *     (qid=209)
 *   - Reverses on-device e-Netcards (qid=210)
 *   - Reverses online e-Netcards (qid=212)
 * And a balance-view modal showing HHM balances (qid=208/211).
 */

const { ref, reactive, onMounted, onBeforeUnmount } = Vue;
const { useApp, useFormat, bus, safeMessage } = window.utils;

const PageBody = {
    setup() {
        const page = ref('allocation');
        const gotoPageHandler = (data) => { page.value = data && data.page; };
        onMounted(() => { bus.on('g-event-goto-page', gotoPageHandler); });
        onBeforeUnmount(() => { bus.off('g-event-goto-page', gotoPageHandler); });
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
                ? (getPermission(typeof per !== 'undefined' ? per : null, 'enetcard') || { permission_value: 0 })
                : { permission_value: 0 }
        );
        const url = ref(window.common && window.common.TableService);
        const tableOptions = reactive({
            total: 1, pageLength: 1, perPage: 10, currentPage: 1,
            orderDir: 'desc', orderField: 0, limitStart: 0,
            isNext: false, isPrev: false,
            aLength: [10, 20, 50, 100, 150, 200],
            filterParam: {
                movementType: 'Forward',
                requester_loginid: '', mobilizer_loginid: '', request_date: '',
            },
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
        const sysDefaultData = ref({});
        const lgaLevelData = ref([]);
        const clusterLevelData = ref([]);
        const wardLevelData = ref([]);
        const lgaNetBalancesData = ref([]);
        const wardNetBalancesData = ref([]);
        const hhmBalanacesData = ref([]);
        const isLgabalance = ref(true);
        const isHHMbalance = ref(true);
        const allStatistics = reactive({
            stateBalance: 0, lgaBalance: 0, wardBalance: 0,
            mobilizer: 0, beneficiary: 0,
        });

        const loadTableData = () => {
            overlay.show();
            var qid;
            if (tableOptions.filterParam.movementType == 'Reverse') qid = '203';
            else if (tableOptions.filterParam.movementType == 'Forward') qid = '202';
            else qid = '204';

            var endpoint =
                common.TableService + '?qid=' + qid +
                '&draw=' + tableOptions.currentPage +
                '&order_column=' + tableOptions.orderField +
                '&length=' + tableOptions.perPage +
                '&start=' + tableOptions.limitStart +
                '&order_dir=' + tableOptions.orderDir +
                '&mt=' + tableOptions.filterParam.movementType +
                '&rid=' + tableOptions.filterParam.requester_loginid +
                '&mid=' + tableOptions.filterParam.mobilizer_loginid +
                '&rda=' + tableOptions.filterParam.request_date;

            axios.get(endpoint)
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
            for (var i = 0; i < hhmBalanacesData.value.length; i++) hhmBalanacesData.value[i].pick = true;
        }
        const uncheckAll = () => {
            for (var i = 0; i < hhmBalanacesData.value.length; i++) hhmBalanacesData.value[i].pick = false;
        }
        const selectToggle = () => {
            if (checkToggle.value === false) { selectAll(); checkToggle.value = true; }
            else                              { uncheckAll(); checkToggle.value = false; }
        }
        const selectedItemsCount = () => { /* original was empty */ };
        const checkedBg = (pickOne) => { return pickOne != '' ? 'bg-select' : ''; };
        const toggleFilter = () => {
            if (filterState.value === false) filters.value = false;
            return (filterState.value = !filterState.value);
        }
        const selectedItems = () => { return hhmBalanacesData.value.filter(r => r.pick); };

        const forwardReverseSelectedID = () => {
            var id = $('#v_g_id').val();
            return hhmBalanacesData.value.filter(r => r.pick).map(row => ({
                total: wardMovementForm.totalNetcard,
                wardid: wardMovementForm.wardid,
                mobilizerid: row.userid,
                mobilizer_balance: row.balance,
                mobilizer_loginid: row.loginid,
                userid: id,
                device_serial: row.device_serial
            }));
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
            checkFill += tableOptions.filterParam.requester_loginid != '' ? 1 : 0;
            checkFill += tableOptions.filterParam.mobilizer_loginid != '' ? 1 : 0;
            checkFill += tableOptions.filterParam.request_date != '' ? 1 : 0;
            if (checkFill > 0) {
                toggleFilter();
                filters.value = true;
                paginationDefault();
                loadTableData();
            } else {
                alert.Error('ERROR', 'Atleast one Filter field must be filled');
            }
        }
        const removeSingleFilter = (column_name) => {
            tableOptions.filterParam[column_name] = '';
            if (column_name == 'request_date') clearDate('request_date');
            var g = 0;
            for (var k in tableOptions.filterParam) {
                if (tableOptions.filterParam[k] != '' && k != 'movementType') g++;
            }
            if (g == 0) filters.value = false;
            paginationDefault();
            loadTableData();
        }
        const clearAllFilter = () => {
            filters.value = false;
            tableOptions.filterParam.requester_loginid = '';
            tableOptions.filterParam.mobilizer_loginid = '';
            tableOptions.filterParam.request_date = '';
            clearDate('request_date');
            paginationDefault();
            loadTableData();
        }
        const clearDate = (id) => {
            try {
                $('#' + id).flatpickr({ altInput: true, altFormat: 'F j, Y', dateFormat: 'Y-m-d' }).clear();
            } catch (e) {}
        }

        /* Ward modal show/hide --------------------------------------- */
        const showWardMoveModal = (movement_type) => {
            overlay.show();
            scroll();
            wardMovementForm.totalNetcard = 1;
            wardMovementForm.wardMoveBtn = movement_type;
            wardMovementForm.wardMoveModal = true;
            wardMovementForm.lgaid = '';
            wardMovementForm.wardid = '';
            wardMovementForm.wardName = '';
            wardMovementForm.wardBalance = '';
            overlay.hide();
        }
        const hideWardMoveModal = () => {
            overlay.show();
            $('#wardMovement').modal('hide');
            wardMovementForm.totalNetcard = 0;
            wardMovementForm.wardMoveBtn = '';
            wardMovementForm.wardMoveModal = false;
            wardMovementForm.lgaid = '';
            wardMovementForm.wardid = '';
            wardMovementForm.wardName = '';
            wardMovementForm.wardBalance = '';
            geoIndicator.lga = '';
            hhmBalanacesData.value = [];
            wardNetBalancesData.value = [];
            overlay.hide();
        }
        const hideHHMBalanceModal = () => {
            overlay.show();
            wardMovementForm.lgaid = '';
            wardMovementForm.wardid = '';
            $('#viewDetails').modal('hide');
            overlay.hide();
        }

        /* Allocation / Reverse logic --------------------------------- */
        const wardTransfer = () => {
            var selectedId = forwardReverseSelectedID();
            if (parseInt(wardMovementForm.totalNetcard) <= 0) {
                alert.Error('Error', "You can't " + wardMovementForm.wardMoveBtn + ' <b>0</b> e-Netcard');
                overlay.hide();
                return;
            }

            if (wardMovementForm.wardMoveBtn == 'Forward') {
                if (!wardMovementForm.wardid || !wardMovementForm.totalNetcard) {
                    alert.Error('Require Fields', 'All fields are required');
                    overlay.hide();
                    return;
                }
                if (!(wardMovementForm.wardBalance > 0)) {
                    alert.Error('Error', "You don't have e-Netcard to transfer");
                    overlay.hide();
                    return;
                }
                if (selectedId.length == 0) {
                    alert.Error('Error', 'No Mobilizer Selected for transfer');
                    overlay.hide();
                    return;
                }
                var len = selectedId.length;
                var checkIfSharable = parseInt(wardMovementForm.wardBalance) / parseInt(wardMovementForm.totalNetcard);
                var totalSharable = Math.floor(wardMovementForm.wardBalance / len);

                if (checkIfSharable < len) {
                    alert.Error('Balance Exceeded', "You don't have enough e-Netcard to allocate. You can only share <b>" + totalSharable + '</b> e-Netcard for the <b>' + len + '</b> selected HHM');
                    return;
                }
                $.confirm({
                    title: 'WARNING!',
                    content: 'Are you sure you want to allocate <b>' + wardMovementForm.totalNetcard + '</b> e-Netcard each to <b>' + len + '</b> selected HH Mobilizers?',
                    buttons: {
                        delete: {
                            text: 'Allocate e-Netcard', btnClass: 'btn btn-danger mr-1 text-capitalize',
                            action: () => {
                                axios.post(common.DataService + '?qid=209', JSON.stringify(selectedId))
                                    .then(response => {
                                        if (response.data.result_code == '200') {
                                            tableOptions.filterParam.movementType = 'Forward';
                                            refreshHHMList();
                                            refreshData();
                                            alert.Success('Success', response.data.total + ' e-Netcards has been successfully allocated to <b>' + len + '</b> HH Mobilizers');
                                            wardMovementForm.totalNetcard = 1;
                                            overlay.hide();
                                        } else {
                                            overlay.hide();
                                            alert.Error('Error', response.data.message);
                                        }
                                    })
                                    .catch(error => {
                                        alert.Error('ERROR', safeMessage(error));
                                        overlay.hide();
                                    });
                            },
                        },
                        cancel: () => { overlay.hide(); },
                    },
                });
                return;
            }

            // Reverse path
            if (selectedId.length != 1) {
                alert.Error('Error', 'You must select <b>1</b> Household Mobilizer to <b>Reverse From</b>');
                return;
            }
            if (!wardMovementForm.wardid || !wardMovementForm.totalNetcard) {
                alert.Error('Require Fields', 'All fields are required');
                overlay.hide();
                return;
            }
            if (!(selectedId[0].mobilizer_balance > 0)) {
                alert.Error('Error', 'HHM with Login ID: <b>' + selectedId[0].mobilizer_loginid + "</b> doesn't have e-Netcard balances to Reverse");
                return;
            }
            var totalReversable = parseInt(selectedId[0].mobilizer_balance) || 0;
            if (wardMovementForm.totalNetcard > totalReversable) {
                alert.Error('HHM Balance Exceeded', "Selected HHM doesn't enough e-Netcard. You can only reverse <b>" + totalReversable + '</b> e-Netcard from the selected HHM');
                return;
            }

            // On-device vs online
            if (selectedId[0].device_serial != null) {
                $.confirm({
                    title: 'WARNING!',
                    content: 'Are you sure you want to Retract <b>' + wardMovementForm.totalNetcard + '</b> e-Netcard from HH Mobilizers with Login ID: <b>' + selectedId[0].mobilizer_loginid + '</b>?',
                    buttons: {
                        delete: {
                            text: 'Reverse e-Netcard', btnClass: 'btn btn-danger mr-1 text-capitalize',
                            action: () => {
                                axios.post(common.DataService + '?qid=210', JSON.stringify(selectedId))
                                    .then(response => {
                                        if (response.data.result_code == '200') {
                                            alert.Success('Success', response.data.total + ' e-Netcards Reverse order has been successfully placed');
                                            tableOptions.filterParam.movementType = 'Reverse';
                                            refreshHHMList();
                                            refreshData();
                                            wardMovementForm.totalNetcard = 1;
                                            overlay.hide();
                                        } else {
                                            overlay.hide();
                                            alert.Error('Error', response.data.message);
                                        }
                                    })
                                    .catch(error => {
                                        alert.Error('ERROR', safeMessage(error));
                                        overlay.hide();
                                    });
                            },
                        },
                        cancel: () => { overlay.hide(); },
                    },
                });
            } else {
                $.confirm({
                    title: 'WARNING!',
                    content: 'Are you sure you want to Retract <b>' + wardMovementForm.totalNetcard + '</b> e-Netcard <b>Online</b> from HH Mobilizers with Login ID: <b>' + selectedId[0].mobilizer_loginid + '</b>?',
                    buttons: {
                        delete: {
                            text: 'Reverse e-Netcard', btnClass: 'btn btn-danger mr-1 text-capitalize',
                            action: () => {
                                axios.post(common.DataService + '?qid=212', JSON.stringify(selectedId))
                                    .then(response => {
                                        if (response.data.result_code == '200') {
                                            alert.Success('Success', response.data.total + ' Online e-Netcards Reverse successfull');
                                            tableOptions.filterParam.movementType = 'ReverseOnline';
                                            refreshHHMList();
                                            refreshData();
                                            wardMovementForm.totalNetcard = 1;
                                            overlay.hide();
                                        } else if (response.data.result_code == '401') {
                                            alert.Success('Error', response.data.message);
                                            refreshHHMList();
                                            tableOptions.filterParam.movementType = 'ReverseOnline';
                                            refreshData();
                                            overlay.hide();
                                        } else {
                                            overlay.hide();
                                            alert.Error('Error', response.data.message);
                                        }
                                    })
                                    .catch(error => {
                                        alert.Error('ERROR', safeMessage(error));
                                        overlay.hide();
                                    });
                            },
                        },
                        cancel: () => { overlay.hide(); },
                    },
                });
            }
        }

        /* Geo + balance lookups -------------------------------------- */
        const getsysDefaultDataSettings = () => {
            overlay.show();
            axios.get(common.DataService + '?qid=gen007')
                .then(response => {
                    if (response.data.data && response.data.data.length > 0) {
                        sysDefaultData.value = response.data.data[0];
                        getLgasLevel(response.data.data[0].stateid);
                        movementForm.geoLevel = 'state';
                        movementForm.geoLevelId = response.data.data[0].stateid;
                    }
                    overlay.hide();
                })
                .catch(error => {
                    overlay.hide();
                    alert.Error('ERROR', safeMessage(error));
                });
        }
        const getLgasLevel = (stateid) => {
            overlay.show();
            axios.post(common.DataService + '?qid=gen003', JSON.stringify(stateid))
                .then(response => {
                    lgaLevelData.value = (response.data && response.data.data) || [];
                    overlay.hide();
                })
                .catch(error => {
                    overlay.hide();
                    alert.Error('ERROR', safeMessage(error));
                });
        }
        const getLgasNetBalances = () => {
            overlay.show();
            axios.post(common.DataService + '?qid=206')
                .then(response => {
                    lgaNetBalancesData.value = (response.data && response.data.data) || [];
                    overlay.hide();
                })
                .catch(error => {
                    overlay.hide();
                    alert.Error('ERROR', safeMessage(error));
                });
        }
        const getWardLevel = () => {
            overlay.show();
            wardMovementForm.wardid = '';
            axios.get(common.DataService + '?qid=gen005&e=' + wardMovementForm.lgaid)
                .then(response => {
                    wardLevelData.value = (response.data && response.data.data) || [];
                    overlay.hide();
                })
                .catch(error => {
                    overlay.hide();
                    alert.Error('ERROR', safeMessage(error));
                });
        }
        const getWardData = (event) => {
            overlay.show();
            wardMovementForm.wardid = '';
            wardMovementForm.lgaid = event.target.options[event.target.options.selectedIndex].value;
            axios.get(common.DataService + '?qid=gen005&lgaid=' + wardMovementForm.lgaid + '&e=' + wardMovementForm.lgaid)
                .then(response => {
                    wardNetBalancesData.value = (response.data && response.data.data) || [];
                    wardLevelData.value = (response.data && response.data.data) || [];
                    overlay.hide();
                })
                .catch(error => {
                    overlay.hide();
                    alert.Error('ERROR', safeMessage(error));
                });
        }
        const getHhmBalances = (event) => {
            var current_endpoint = wardMovementForm.wardMoveBtn == 'Reverse' ? '208' : '211';
            var optText = event.target.options[event.target.options.selectedIndex].text;
            wardMovementForm.wardName = optText.trim().replace(',', '').split('-')[0];
            currentWardBalance.wardName = optText;
            overlay.show();
            getCurrentWardBalance();
            axios.get(common.DataService + '?qid=' + current_endpoint + '&wardid=' + wardMovementForm.wardid)
                .then(response => {
                    hhmBalanacesData.value = (response.data && response.data.data) || [];
                    overlay.hide();
                })
                .catch(error => {
                    overlay.hide();
                    alert.Error('ERROR', safeMessage(error));
                });
        }
        const HHMBalancesRefresh = () => {
            var current_endpoint = wardMovementForm.wardMoveBtn == 'Reverse' ? '208' : '211';
            overlay.show();
            if (wardMovementForm.wardid == '') {
                alert.Error('Ward Selection Error', 'Please select a ward first');
                overlay.hide();
                return;
            }
            getCurrentWardBalance();
            axios.get(common.DataService + '?qid=' + current_endpoint + '&wardid=' + wardMovementForm.wardid)
                .then(response => {
                    hhmBalanacesData.value = (response.data && response.data.data) || [];
                    overlay.hide();
                })
                .catch(error => {
                    overlay.hide();
                    alert.Error('ERROR', safeMessage(error));
                });
        }
        const refreshHHMList = () => {
            var current_endpoint = wardMovementForm.wardMoveBtn == 'Reverse' ? '208' : '211';
            overlay.show();
            getCurrentWardBalance();
            axios.get(common.DataService + '?qid=' + current_endpoint + '&wardid=' + wardMovementForm.wardid)
                .then(response => {
                    hhmBalanacesData.value = (response.data && response.data.data) || [];
                    overlay.hide();
                })
                .catch(error => {
                    overlay.hide();
                    alert.Error('ERROR', safeMessage(error));
                });
        }
        const getCurrentWardBalance = () => {
            axios.get(common.DataService + '?qid=214&wardid=' + wardMovementForm.wardid)
                .then(response => {
                    var row = (response.data && response.data.data && response.data.data[0]) || {};
                    currentWardBalance.balance = row.balance ? parseInt(row.balance) : 0;
                    wardMovementForm.wardBalance = currentWardBalance.balance;
                    currentWardBalance.received = row.received ? parseInt(row.received) : 0;
                    currentWardBalance.disbursed = row.disbursed ? parseInt(row.disbursed) : 0;
                    overlay.hide();
                })
                .catch(error => {
                    overlay.hide();
                    alert.Error('ERROR', safeMessage(error));
                });
        }

        const refreshData = () => {
            paginationDefault();
            loadTableData();
            getLgasNetBalances();
            getAllStat();
        }
        const getAllStat = () => {
            var endpoints = [common.DataService + '?qid=201'];
            Promise.all(endpoints.map(e => axios.get(e))).then(
                axios.spread((...allData) => {
                    overlay.show();
                    var data = (allData[0] && allData[0].data && allData[0].data.data) || [];
                    for (var i = 0; i < data.length; i++) {
                        var row = data[i];
                        if (!row || !row.location) continue;
                        if (row.location === 'state') allStatistics.stateBalance = row.total || 0;
                        else if (row.location === 'lga') allStatistics.lgaBalance = row.total || 0;
                        else if (row.location === 'ward') allStatistics.wardBalance = row.total || 0;
                        else if (row.location === 'mobilizer') allStatistics.mobilizer = row.total || 0;
                        else if (row.location === 'beneficiary') allStatistics.beneficiary = row.total || 0;
                    }
                    overlay.hide();
                })
            ).catch(() => { overlay.hide(); });
        }

        const scroll = () => {
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
        const onlyNumber = (event) => {
            var keyCode = event.keyCode || event.which;
            if ((keyCode < 48 || keyCode > 57) && keyCode == 46) event.preventDefault();
        }

        onMounted(() => {
            try { $('.date').flatpickr({ altInput: true, altFormat: 'F j, Y', dateFormat: 'Y-m-d' }); } catch (e) {}
            getsysDefaultDataSettings();
            getLgasNetBalances();
            loadTableData();
            getAllStat();
            bus.on('g-event-update', loadTableData);
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
        onBeforeUnmount(() => {
            bus.off('g-event-update', loadTableData);
        });

        return {
            tableData, checkToggle, filterState, filters, permission, url,
            tableOptions, currentWardBalance, wardMovementForm, movementForm,
            geoIndicator, geoLevelData, sysDefaultData, lgaLevelData,
            clusterLevelData, wardLevelData, lgaNetBalancesData, wardNetBalancesData,
            hhmBalanacesData, isLgabalance, isHHMbalance, allStatistics,
            loadTableData, selectAll, uncheckAll, selectToggle, selectedItemsCount,
            checkedBg, toggleFilter, selectedItems, forwardReverseSelectedID,
            paginationDefault, nextPage, prevPage, currentPage, changePerPage, sort,
            applyFilter, removeSingleFilter, clearAllFilter, clearDate,
            showWardMoveModal, hideWardMoveModal, hideHHMBalanceModal,
            wardTransfer,
            getsysDefaultDataSettings, getLgasLevel, getLgasNetBalances,
            getWardLevel, getWardData, getHhmBalances,
            HHMBalancesRefresh, refreshHHMList, getCurrentWardBalance,
            refreshData, getAllStat, onlyNumber,
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
                        <li class="breadcrumb-item active">e-Netcard Allocation</li>
                    </ol>
                </div>
            </div>

            <div class="col-lg-4 col-sm-4 col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <span>Ward Balance</span>
                            <div class="avatar bg-light-primary p-50 m-0 mt--1"><div class="avatar-content"><i data-feather="target" class="font-medium-4"></i></div></div>
                        </div>
                        <div class="d-block justify-content-between align-items-end mt-50 pt-25">
                            <div class="role-heading d-block">
                                <h4 class="fw-bolder" v-cloak>{{ formatNumber(allStatistics.wardBalance) }}</h4>
                                <a v-if="permission.permission_value == 3" href="javascript:void(0);" data-backdrop="static" data-keyboard="false" class="state-move-modal float-right btn btn-sm btn-primary" @click="showWardMoveModal('Forward')" data-toggle="modal" data-target="#wardMovement">
                                    <small class="fw-bolder">Transfer</small>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 col-sm-4 col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <span>HHM Balance</span>
                            <div class="avatar bg-light-primary p-50 m-0 mt--1"><div class="avatar-content"><i data-feather="users" class="font-medium-4"></i></div></div>
                        </div>
                        <div class="d-block justify-content-between align-items-end mt-50 pt-25">
                            <div class="role-heading d-block">
                                <h4 class="fw-bolder" v-cloak>{{ formatNumber(allStatistics.mobilizer) }}</h4>
                                <div class="text-right">
                                    <a href="javascript:void(0);" data-backdrop="static" @click="isHHMbalance = true" data-keyboard="false" class="lga-details-modal btn btn-sm btn-outline-primary mr-50" data-toggle="modal" data-target="#viewDetails">
                                        <small class="fw-bolder">View</small>
                                    </a>
                                    <a v-if="permission.permission_value == 3" href="javascript:void(0);" data-backdrop="static" data-keyboard="false" class="lga-move-modal btn btn-sm btn-primary" @click="showWardMoveModal('Reverse')" data-toggle="modal" data-target="#wardMovement">
                                        <small class="fw-bolder">Reverse</small>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 col-sm-4 col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <span>Household/Beneficiary</span>
                            <div class="avatar bg-light-primary p-50 m-0 mt--1"><div class="avatar-content"><i data-feather="home" class="font-medium-4"></i></div></div>
                        </div>
                        <div class="d-block justify-content-between align-items-end mt-50 pt-25">
                            <div class="role-heading d-block">
                                <h4 class="fw-bolder" v-cloak>{{ formatNumber(allStatistics.beneficiary) }}</h4>
                                <div class="text-right">
                                    <a href="../mobilization/list" class="btn btn-sm btn-primary"><small class="fw-bolder">View</small></a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-5 col-sm-6 col-6 mb-0">
                <div class="form-group">
                    <select class="form-control max-width-200" v-model="tableOptions.filterParam.movementType" @change="loadTableData()">
                        <option value="Forward">Allocation Transaction</option>
                        <option value="Reverse">Reverse Transaction</option>
                        <option value="ReverseOnline">Online Reverse Transaction</option>
                    </select>
                </div>
            </div>
            <div class="col-md-7 col-sm-6 col-6 text-md-right text-right d-md-block">
                <div class="btn-group mr-1">
                    <button type="button" class="btn btn-outline-primary round searchBtn" data-toggle="tooltip" data-placement="top" title="Refresh Page" @click="refreshData()">
                        <i class="feather icon-refresh-cw"></i>
                    </button>
                    <button type="button" class="btn btn-outline-primary round searchBtn" data-toggle="tooltip" data-placement="top" title="Filter" @click="toggleFilter()">
                        <i class="feather" :class="filterState ? 'icon-x' : 'icon-filter'"></i>
                    </button>
                </div>
            </div>

            <div class="col-12" v-if="filters">
                <div class="col-12 filter-bar">
                    <template v-for="(filterParam, i) in tableOptions.filterParam" :key="i">
                        <span class="badge badge-dark filter-box" v-if="String(filterParam).length > 0 && i != 'movementType'" @click="removeSingleFilter(i)">
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
                                <div class="col-12 col-sm-6 col-md-3 col-lg-3"><div class="form-group"><label>Requester Login ID</label><input type="text" v-model="tableOptions.filterParam.requester_loginid" class="form-control requester_loginid" id="requester_loginid" placeholder="Requester Login ID" name="requester_loginid" /></div></div>
                                <div class="col-12 col-sm-6 col-md-3 col-lg-3"><div class="form-group"><label>Mobilizer Login ID</label><input type="text" v-model="tableOptions.filterParam.mobilizer_loginid" class="form-control mobilizer_loginid" id="mobilizer_loginid" placeholder="Mobilizer Login ID" name="mobilizer_loginid" /></div></div>
                                <div class="col-12 col-sm-6 col-md-3 col-lg-3"><div class="form-group"><label>Request Date</label><input type="text" v-model="tableOptions.filterParam.request_date" class="form-control date" id="request_date" placeholder="Request Date" name="request_date" /></div></div>
                                <div class="col-3"><div class="form-group mt-2 text-right"><button type="button" class="btn btn-md btn-primary" @click="applyFilter()">Apply Filters</button></div></div>
                            </div>
                        </form>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr v-if="tableOptions.filterParam.movementType == 'Forward'">
                                    <th style="padding-right: 2px !important;">#</th>
                                    <th @click="sort(1)">Transfer By
                                        <i class="feather icon-chevron-up sort-up" :class="(tableOptions.orderField == 1 && tableOptions.orderDir =='asc')? 'active-sort': ''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 1 && tableOptions.orderDir =='desc')? 'active-sort': ''"></i>
                                    </th>
                                    <th @click="sort(4)">Origin
                                        <i class="feather icon-chevron-up sort-up" :class="(tableOptions.orderField == 4 && tableOptions.orderDir =='asc')? 'active-sort': ''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 4 && tableOptions.orderDir =='desc')? 'active-sort': ''"></i>
                                    </th>
                                    <th @click="sort(7)">HH Mobilizer
                                        <i class="feather icon-chevron-up sort-up" :class="(tableOptions.orderField == 7 && tableOptions.orderDir =='asc')? 'active-sort': ''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 7 && tableOptions.orderDir =='desc')? 'active-sort': ''"></i>
                                    </th>
                                    <th @click="sort(2)" style="padding-left: 5px !important; padding-right: 10px !important">Total
                                        <i class="feather icon-chevron-up sort-up" :class="(tableOptions.orderField == 2 && tableOptions.orderDir =='asc')? 'active-sort': ''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 2 && tableOptions.orderDir =='desc')? 'active-sort': ''"></i>
                                    </th>
                                    <th @click="sort(8)">Date
                                        <i class="feather icon-chevron-up sort-up" :class="(tableOptions.orderField == 8 && tableOptions.orderDir =='asc')? 'active-sort': ''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 8 && tableOptions.orderDir =='desc')? 'active-sort': ''"></i>
                                    </th>
                                </tr>
                                <tr v-if="tableOptions.filterParam.movementType == 'Reverse'">
                                    <th style="padding-right: 2px !important;">#</th>
                                    <th @click="sort(5)">Request By</th>
                                    <th @click="sort(7)">Total Request</th>
                                    <th @click="sort(1)">Request From</th>
                                    <th @click="sort(10)">Requested Date</th>
                                    <th @click="sort(9)" style="padding-left: 5px !important; padding-right: 10px !important">Status</th>
                                    <th @click="sort(8)">Total Fulfilled</th>
                                    <th @click="sort(11)">Fulfilled Date</th>
                                </tr>
                                <tr v-if="tableOptions.filterParam.movementType == 'ReverseOnline'">
                                    <th style="padding-right: 2px !important;">#</th>
                                    <th @click="sort(5)">Request By</th>
                                    <th @click="sort(1)">Request From</th>
                                    <th @click="sort(7)">Total Reversed</th>
                                    <th @click="sort(8)">Requested Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template v-if="tableOptions.filterParam.movementType == 'Forward'">
                                    <tr v-for="(g, i) in tableData" :key="i" :class="checkedBg(g.pick)">
                                        <td style="padding-right: 2px !important;">{{ i + 1 }}</td>
                                        <td>{{ g.transfer_by }}</td>
                                        <td>{{ g.origin }}</td>
                                        <td>{{ g.mobilizer }}</td>
                                        <td style="padding-left: 5px !important; padding-right: 10px !important">{{ g.total ? Number(g.total).toLocaleString() : 0 }}</td>
                                        <td>{{ displayDate(g.created) }}</td>
                                    </tr>
                                    <tr v-if="tableData.length == 0"><td class="text-center pt-2" colspan="6"><small>No Forward Transaction</small></td></tr>
                                </template>
                                <template v-if="tableOptions.filterParam.movementType == 'Reverse'">
                                    <tr v-for="(g, i) in tableData" :key="i" :class="checkedBg(g.pick)">
                                        <td style="padding-right: 2px !important;">{{ g.orderid }}</td>
                                        <td>
                                            <div class="d-flex justify-content-left align-items-center">
                                                <div class="d-flex flex-column">
                                                    <a href="#" class="user_name text-truncate text-body"><span class="fw-bolder">{{ g.requester }}</span></a>
                                                    <small class="emp_post text-muted">{{ g.requester_loginid }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td style="padding-left: 5px !important; padding-right: 10px !important">{{ g.total_order ? Number(g.total_order).toLocaleString() : 0 }}</td>
                                        <td>
                                            <div class="d-flex justify-content-left align-items-center">
                                                <div class="d-flex flex-column">
                                                    <a href="#" class="user_name text-truncate text-body"><span class="fw-bolder">{{ g.mobilizer }}</span></a>
                                                    <small class="emp_post text-muted">{{ g.mobilizer_loginid }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>{{ displayDate(g.created) }}</td>
                                        <td><span class="badge rounded-pill font-small-1" :class="g.status == 'pending' ? 'bg-danger' : 'bg-success'">{{ g.status == 'pending' ? 'Pending' : 'Fulfilled' }}</span></td>
                                        <td>{{ g.total_fulfilment ? Number(g.total_fulfilment).toLocaleString() : 0 }}</td>
                                        <td>{{ g.fulfilled_date ? displayDate(g.fulfilled_date) : 'Nil' }}</td>
                                    </tr>
                                    <tr v-if="tableData.length == 0"><td class="text-center pt-2" colspan="8"><small>No Reverse Order Added</small></td></tr>
                                </template>
                                <template v-if="tableOptions.filterParam.movementType == 'ReverseOnline'">
                                    <tr v-for="(g, i) in tableData" :key="i" :class="checkedBg(g.pick)">
                                        <td style="padding-right: 2px !important;">{{ g.orderid }}</td>
                                        <td>
                                            <div class="d-flex justify-content-left align-items-center">
                                                <div class="d-flex flex-column">
                                                    <a href="#" class="user_name text-truncate text-body"><span class="fw-bolder">{{ g.requester }}</span></a>
                                                    <small class="emp_post text-muted">{{ g.requester_loginid }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex justify-content-left align-items-center">
                                                <div class="d-flex flex-column">
                                                    <a href="#" class="user_name text-truncate text-body"><span class="fw-bolder">{{ g.mobilizer }}</span></a>
                                                    <small class="emp_post text-muted">{{ g.mobilizer_loginid }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td style="padding-left: 5px !important; padding-right: 10px !important">{{ g.amount ? Number(g.amount).toLocaleString() : 0 }}</td>
                                        <td>{{ displayDate(g.created) }}</td>
                                    </tr>
                                    <tr v-if="tableData.length == 0"><td class="text-center pt-2" colspan="5"><small>No Online Reverse History</small></td></tr>
                                </template>
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

            <!-- Allocate / Reverse modal (#wardMovement) -->
            <div class="modal fade modal-primary ward-movement" id="wardMovement" tabindex="-1" role="dialog" aria-hidden="true">
                <div class="modal-dialog modal-xl" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" v-html="wardMovementForm.wardMoveBtn == 'Forward' ? 'Allocate e-Netcard To Household Mobilizer' : 'Reverse e-Netcard From Household Mobilizer'"></h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close" @click="hideWardMoveModal()">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="allot">
                                <div class="left-side main-body">
                                    <h6 class="mb-1" v-html="wardMovementForm.wardMoveBtn == 'Forward' ? 'Allocation Form' : 'Reversal Form'"></h6>
                                    <div>
                                        <div class="form-group">
                                            <label class="form-label">Choose LGA</label>
                                            <select required class="form-control" @change="getWardData($event)" v-model="wardMovementForm.lgaid">
                                                <option value="" selected>Choose LGA to View</option>
                                                <option v-for="(lga, i) in lgaLevelData" :key="lga.lgaid" :value="lga.lgaid">{{ lga.lga }}</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">Choose HHM Ward</label>
                                            <select class="form-control" v-model="wardMovementForm.wardid" @change="getHhmBalances($event)">
                                                <option value="" selected>Choose a Ward</option>
                                                <option v-for="(ward, i) in wardNetBalancesData" :key="ward.wardid" :value="ward.wardid">{{ ward.ward }}</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label" v-html="wardMovementForm.wardMoveBtn == 'Forward' ? 'Total No of Netcard to Allocate' : 'Total No of Netcard to Reverse'"></label>
                                            <input type="number" class="form-control" @keypress="onlyNumber($event)" v-model="wardMovementForm.totalNetcard" placeholder="Total No of Netcard" />
                                        </div>
                                        <div class="e-details pt-2" v-if="wardMovementForm.wardid != ''">
                                            <small class="mt-3"><span class="font-weight-bolder text-primary" v-text="currentWardBalance.wardName"></span> e-Netcard Details</small>
                                            <hr class="invoice-spacing mt-0">
                                            <div class="invoice-terms mt-1"><div class="d-flex justify-content-between"><label class="invoice-terms-title mb-0">Total Received: </label><div class="custom-control badge badge-light-success" v-text="currentWardBalance.received"></div></div></div>
                                            <div class="invoice-terms mt-1"><div class="d-flex justify-content-between"><label class="invoice-terms-title mb-0">Total Disbursed: </label><div class="custom-control badge badge-light-info" v-text="currentWardBalance.disbursed"></div></div></div>
                                            <div class="invoice-terms mt-1"><hr class="my-50"><div class="d-flex justify-content-between"><label class="invoice-terms-title mb-0">Balance: </label><div class="custom-control badge badge-light-warning" v-text="currentWardBalance.balance"></div></div></div>
                                        </div>
                                    </div>
                                </div>

                                <div class="right-side">
                                    <div class="allot-mobile-form main-body">
                                        <div class="form-group mb-50">
                                            <label class="form-label">Choose LGA</label>
                                            <select required class="form-control" @change="getWardData($event)" v-model="wardMovementForm.lgaid">
                                                <option value="" selected>Choose LGA to View</option>
                                                <option v-for="(lga, i) in lgaLevelData" :key="lga.lgaid" :value="lga.lgaid">{{ lga.lga }}</option>
                                            </select>
                                        </div>
                                        <div class="form-group mb-50">
                                            <label class="form-label">Choose HHM Ward</label>
                                            <select class="form-control" v-model="wardMovementForm.wardid" @change="getHhmBalances($event)">
                                                <option value="" selected>Choose a Ward</option>
                                                <option v-for="(ward, i) in wardNetBalancesData" :key="ward.wardid" :value="ward.wardid">{{ ward.ward }}</option>
                                            </select>
                                        </div>
                                        <div class="form-group mb-50">
                                            <label class="form-label" v-html="wardMovementForm.wardMoveBtn == 'Forward' ? 'Total No of Netcard to Allocate' : 'Total No of Netcard to Reverse'"></label>
                                            <input type="number" class="form-control" @keypress="onlyNumber($event)" v-model="wardMovementForm.totalNetcard" placeholder="Total No of Netcard" />
                                        </div>
                                        <div class="e-details pt-2" v-if="wardMovementForm.wardid != ''">
                                            <small class="mt-3"><span class="font-weight-bolder text-primary" v-text="currentWardBalance.wardName"></span> e-Netcard Details</small>
                                            <hr class="invoice-spacing mt-0">
                                            <div class="invoice-terms mt-1"><div class="d-flex justify-content-between"><label class="invoice-terms-title mb-0">Total Received: </label><div class="custom-control badge badge-light-success" v-text="currentWardBalance.received"></div></div></div>
                                            <div class="invoice-terms mt-1"><div class="d-flex justify-content-between"><label class="invoice-terms-title mb-0">Total Disbursed: </label><div class="custom-control badge badge-light-info" v-text="currentWardBalance.disbursed"></div></div></div>
                                            <div class="invoice-terms mt-1"><hr class="my-50"><div class="d-flex justify-content-between"><label class="invoice-terms-title mb-0">Balance: </label><div class="custom-control badge badge-light-warning" v-text="currentWardBalance.balance"></div></div></div>
                                        </div>
                                    </div>

                                    <div class="app-fixed-search d-flex align-items-center">
                                        <div class="d-flex align-content-center justify-content-between w-100">
                                            <div class="input-group input-group-merge">
                                                <div class="input-group-prepend"><span class="input-group-text"><i data-feather="search" class="text-muted"></i></span></div>
                                                <input type="text" class="form-control search" id="todo-search" placeholder="Search HHM" />
                                                <div class="input-group-append">
                                                    <button type="button" class="btn btn-sm btn-outline-default searchBtn" data-toggle="tooltip" data-placement="top" title="Refresh Page" @click="HHMBalancesRefresh()">
                                                        <i class="feather icon-refresh-cw"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="main-body">
                                        <div v-if="isLgabalance == true" class="mt-0">
                                            <table class="table table-hover scroll-now" id="moveTable">
                                                <thead>
                                                    <tr>
                                                        <th width="40px">
                                                            <div class="custom-control custom-checkbox checkbox">
                                                                <input type="checkbox" class="custom-control-input" @change="selectToggle(); selectedItemsCount();" id="all-check" />
                                                                <label class="custom-control-label" for="all-check"></label>
                                                            </div>
                                                        </th>
                                                        <th>Login ID</th>
                                                        <th>Fullname</th>
                                                        <th v-show="wardMovementForm.wardMoveBtn == 'Reverse'">Location</th>
                                                        <th>Total</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr v-for="(g, i) in hhmBalanacesData" :key="g.userid || i" :class="checkedBg(g.pick)">
                                                        <td>
                                                            <div class="custom-control custom-checkbox checkbox">
                                                                <input type="checkbox" class="custom-control-input" @click="selectedItemsCount()" :id="'hhm-' + i" v-model="g.pick" />
                                                                <label class="custom-control-label" :for="'hhm-' + i"></label>
                                                            </div>
                                                        </td>
                                                        <td>{{ g.loginid }}</td>
                                                        <td>{{ g.fullname }}</td>
                                                        <td v-show="wardMovementForm.wardMoveBtn == 'Reverse'">
                                                            <i class="feather" :class="g.device_serial ? 'icon-smartphone bg-light-info rounded' : 'icon-cloud bg-light-success rounded'"></i>
                                                            <small v-html="g.device_serial ? ' (' + g.device_serial + ')' : ' (Online)'"></small>
                                                        </td>
                                                        <td>{{ g.balance }}</td>
                                                    </tr>
                                                    <tr v-if="hhmBalanacesData.length == 0"><td class="text-center text-info pt-4 pb-4" colspan="5"><small>No Household Mobilizer Assigned to <b>{{ wardMovementForm.wardName }}</b></small></td></tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <div class="form-group">
                                <button type="button" data-dismiss="modal" @click="hideWardMoveModal()" class="btn btn-outline-primary mr-1">Cancel</button>
                                <button type="button" @click="wardTransfer()" class="btn btn-primary" v-text="wardMovementForm.wardMoveBtn == 'Forward' ? 'Allocate Netcard' : 'Reverse Netcard'"></button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- HHM balance details modal (#viewDetails) -->
            <div class="modal fade modal-primary ward-movement" id="viewDetails" tabindex="-1" role="dialog" aria-hidden="true">
                <div class="modal-dialog modal-xl" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">HHM Balances</h5>
                            <button type="button" class="close" data-dismiss="modal" @click="hideHHMBalanceModal()">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="allot">
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
                                            <div class="invoice-terms mt-1"><hr class="my-50"><div class="d-flex justify-content-between"><label class="invoice-terms-title mb-0">Balance: </label><div class="custom-control badge badge-light-warning" v-text="currentWardBalance.balance"></div></div></div>
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
                                    </div>

                                    <div class="app-fixed-search d-flex align-items-center">
                                        <div class="d-flex align-content-center justify-content-between w-100">
                                            <div class="input-group input-group-merge">
                                                <div class="input-group-prepend"><span class="input-group-text"><i data-feather="search" class="text-muted"></i></span></div>
                                                <input type="text" class="form-control search" id="todo-search1" placeholder="Search HHM" />
                                            </div>
                                        </div>
                                    </div>

                                    <div class="main-body">
                                        <div v-if="isLgabalance == true" class="mt-0">
                                            <table class="table table-hover scroll-now" id="moveTable1">
                                                <thead>
                                                    <tr>
                                                        <th width="40px">#</th>
                                                        <th>Login ID</th>
                                                        <th>Fullname</th>
                                                        <th>Geo Location</th>
                                                        <th>HHM Balance</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr v-for="(g, i) in hhmBalanacesData" :key="g.userid || i" :class="checkedBg(g.pick)">
                                                        <td>{{ i + 1 }}</td>
                                                        <td>{{ g.loginid }}</td>
                                                        <td v-html="g.fullname ? g.fullname : 'Not Assigned'"></td>
                                                        <td>{{ g.geo_string }}</td>
                                                        <td>{{ g.balance }}</td>
                                                    </tr>
                                                    <tr v-if="hhmBalanacesData.length == 0"><td class="text-center text-info pt-4 pb-4" colspan="5"><small>No ward Choosen/No Household Mobilizer Assigned to <b>{{ wardMovementForm.wardName }}</b></small></td></tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <div class="form-group">
                                <button type="button" data-dismiss="modal" @click="hideHHMBalanceModal()" class="btn btn-primary">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `,
};

useApp({ template: `<div><page-body/></div>` })
    .component('page-body', PageBody)
    .component('necard_movement', NecardMovement)
    .mount('#app');
