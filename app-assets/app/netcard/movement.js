/**
 * Netcard / Movement — Vue 3 Composition API in place.
 * Two components — page-body and necard_movement.
 *
 * necard_movement: e-Netcard transfer flows
 *   - State → LGA       (qid=202)
 *   - LGA → Ward        (qid=204) / LGA → State reverse (qid=203)
 *   - Ward → LGA reverse (qid=205)
 * Plus a paginated movement-history table (qid=201) with type filter
 * and a view-balances modal (lga / ward) using qid=206 / qid=207.
 */

const { ref, reactive, onMounted, onBeforeUnmount } = Vue;
const { useApp, useFormat, bus, safeMessage } = window.utils;

const PageBody = {
    setup() {
        const page = ref('movement');
        const gotoPageHandler = (data) => { page.value = data && data.page; };
        onMounted(() => { bus.on('g-event-goto-page', gotoPageHandler); });
        onBeforeUnmount(() => { bus.off('g-event-goto-page', gotoPageHandler); });
        return { page };
    },
    template: `
        <div>
            <div class="content-body">
                <div v-show="page == 'movement'"><necard_movement/></div>
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
            aLength: [10, 20, 50, 100, 150],
            filterParam: { movementType: '' },
        });
        const stateMovementForm = reactive({
            stateMoveModal: false, stateid: '', lgaid: '',
            totalNetcard: 1, lgaName: '',
        });
        const lgaMovementForm = reactive({
            totalNetcard: 1, lgaMoveBtn: '', lgaMoveModal: false,
            originid: '', originName: '', originBalance: 0,
            destinationid: '', destinationName: '',
        });
        const wardMovementForm = reactive({
            totalNetcard: 1, wardMoveBtn: '', wardMoveModal: false,
            originid: '', originName: '', originBalance: 0,
            destinationid: '', destinationName: '',
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
        const isLgabalance = ref(true);
        const allStatistics = reactive({
            stateBalance: 0, lgaBalance: 0, wardBalance: 0,
            beneficiary: 0, total: 0, mobilizer: 0,
        });

        const loadTableData = () => {
            overlay.show();
            axios.get(
                common.TableService +
                '?qid=201&draw=' + tableOptions.currentPage +
                '&order_column=' + tableOptions.orderField +
                '&length=' + tableOptions.perPage +
                '&start=' + tableOptions.limitStart +
                '&order_dir=' + tableOptions.orderDir +
                '&mt=' + tableOptions.filterParam.movementType
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
            if (tableOptions.filterParam.movementType != '') {
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
            tableOptions.filterParam.movementType = '';
            paginationDefault();
            loadTableData();
        }

        /* State move modal ------------------------------------------- */
        const showStateMoveModal = () => {
            overlay.show();
            stateMovementForm.totalNetcard = 1;
            stateMovementForm.stateMoveModal = true;
            stateMovementForm.lgaName = '';
            overlay.hide();
        }
        const hideStateMoveModal = () => {
            overlay.show();
            stateMovementForm.lgaid = '';
            stateMovementForm.totalNetcard = 1;
            stateMovementForm.lgaName = '';
            $('#stateMove').modal('hide');
            stateMovementForm.stateMoveModal = false;
            wardLevelData.value = [];
            overlay.hide();
        }
        const setLgaName = (event) => {
            stateMovementForm.lgaName = event.target.options[event.target.options.selectedIndex].text;
        }
        const transferFromStateToLGA = () => {
            if (
                parseInt(stateMovementForm.totalNetcard) > 0 &&
                parseInt(stateMovementForm.totalNetcard) <= parseInt(allStatistics.stateBalance)
            ) {
                $.confirm({
                    title: 'WARNING!',
                    content: 'Are you sure you want to transfer <b>' + stateMovementForm.totalNetcard + '</b> e-Netcard to <b>' + stateMovementForm.lgaName + '</b> LGA?',
                    buttons: {
                        delete: {
                            text: 'transfer', btnClass: 'btn btn-danger mr-1 text-capitalize',
                            action: () => {
                                axios.post(
                                    common.DataService +
                                    '?qid=202&total=' + stateMovementForm.totalNetcard +
                                    '&stateid=' + stateMovementForm.stateid +
                                    '&lgaid=' + stateMovementForm.lgaid +
                                    '&id=' + $('#v_g_id').val()
                                )
                                    .then(response => {
                                        if (response.data.result_code == 200) {
                                            hideStateMoveModal();
                                            refreshData();
                                            alert.Success('Success', response.data.message + ' has been moved from State to ' + stateMovementForm.lgaName + ' LGA');
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
                alert.Error('ERROR', "You can't transfer more than " + allStatistics.stateBalance + ' available e-Netcards');
                overlay.hide();
            }
        }

        /* LGA move modal --------------------------------------------- */
        const showLgaMoveModal = () => {
            overlay.show();
            lgaMovementForm.lgaMoveModal = true;
            lgaMovementForm.lgaMoveBtn = 'Forward';
            lgaMovementForm.totalNetcard = 1;
            lgaMovementForm.originName = '';
            lgaMovementForm.destinationName = '';
            overlay.hide();
        }
        const hideLgaMoveModal = () => {
            overlay.show();
            lgaMovementForm.lgaMoveModal = false;
            lgaMovementForm.lgaMoveBtn = '';
            lgaMovementForm.destinationid = '';
            lgaMovementForm.originid = '';
            lgaMovementForm.totalNetcard = 1;
            lgaMovementForm.originName = '';
            lgaMovementForm.destinationName = '';
            geoIndicator.lga = '';
            wardNetBalancesData.value = [];
            wardLevelData.value = [];
            $('#lgaMove').modal('hide');
            overlay.hide();
        }
        const setLgaOriginName = (event) => {
            if (lgaMovementForm.lgaMoveBtn == 'Forward') {
                lgaMovementForm.destinationid = '';
                lgaMovementForm.destinationName = '';
            }
            getWardLevel();
            geoIndicator.lga = lgaMovementForm.originid;
            lgaMovementForm.originName = event.target.options[event.target.options.selectedIndex].text;
            var origin = lgaMovementForm.originName.trim().replace(',', '').split('-');
            var raw = origin[origin.length - 1];
            lgaMovementForm.originBalance = raw == '' ? 0 : parseInt(raw);
        }
        const setLgaDestinationName = (event) => {
            if (lgaMovementForm.lgaMoveBtn == 'Forward') {
                lgaMovementForm.destinationName = event.target.options[event.target.options.selectedIndex].text;
            }
        }
        const setLgaReverseVariable = () => {
            lgaMovementForm.lgaMoveBtn = 'Reverse';
            lgaMovementForm.destinationid = sysDefaultData.value.stateid;
            lgaMovementForm.destinationName = sysDefaultData.value.state;
        }
        const lgaTransfer = (transfer_type) => {
            var origin_name = lgaMovementForm.originName.split(' - ')[0];
            if (parseInt(lgaMovementForm.totalNetcard) > parseInt(lgaMovementForm.originBalance)) {
                alert.Error('ERROR', "You can't transfer more than " + lgaMovementForm.originBalance + ' e-Netcard');
                overlay.hide();
                return;
            }
            var qid = transfer_type == 'Reverse' ? '203' : '204';
            var content = transfer_type == 'Reverse'
                ? 'Are you sure you want to reverse <b>' + lgaMovementForm.totalNetcard + '</b> e-Netcard from <b>' + origin_name + '</b> to <b>' + lgaMovementForm.destinationName + '</b> State?'
                : 'Are you sure you want to Transfer <b>' + lgaMovementForm.totalNetcard + '</b> e-Netcards from <b>' + origin_name + '</b> LGA to <b>' + lgaMovementForm.destinationName + '</b> Ward?';
            var successMsg = transfer_type == 'Reverse'
                ? n => n + ' Netcards has been reversed successfully from <b>' + origin_name + '</b> LGA to <b>' + lgaMovementForm.destinationName + '</b>'
                : n => '<b>' + n + '</b> e-Netcards has been transfered successfully from <b>' + origin_name + '</b> LGA to <b>' + lgaMovementForm.destinationName + '</b> Ward';

            $.confirm({
                title: 'WARNING!', content: content,
                buttons: {
                    delete: {
                        text: transfer_type, btnClass: 'btn btn-danger mr-1 text-capitalize',
                        action: () => {
                            axios.post(
                                common.DataService +
                                '?qid=' + qid +
                                '&total=' + lgaMovementForm.totalNetcard +
                                '&originid=' + lgaMovementForm.originid +
                                '&destinationid=' + lgaMovementForm.destinationid +
                                '&id=' + $('#v_g_id').val()
                            )
                                .then(response => {
                                    if (response.data.result_code == '200') {
                                        alert.Success('Success', successMsg(response.data.message));
                                        hideLgaMoveModal();
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

        /* Ward move modal -------------------------------------------- */
        const showWardMoveModal = () => {
            overlay.show();
            wardMovementForm.wardMoveModal = true;
            wardMovementForm.wardMoveBtn = 'Forward';
            wardMovementForm.totalNetcard = 1;
            wardMovementForm.originName = '';
            wardMovementForm.destinationName = '';
            wardMovementForm.destinationid = '';
            overlay.hide();
        }
        const hideWardMoveModal = () => {
            overlay.show();
            $('#wardMove').modal('hide');
            wardMovementForm.wardMoveModal = false;
            wardMovementForm.wardMoveBtn = '';
            wardMovementForm.destinationid = '';
            wardMovementForm.originid = '';
            wardMovementForm.totalNetcard = 1;
            wardMovementForm.originName = '';
            wardMovementForm.destinationName = '';
            wardMovementForm.originBalance = 0;
            geoIndicator.lga = '';
            wardLevelData.value = [];
            wardNetBalancesData.value = [];
            overlay.hide();
        }
        const setWardOriginName = (event) => {
            wardMovementForm.originName = event.target.options[event.target.options.selectedIndex].text;
            var trimmed = wardMovementForm.originName.trim().replace(',', '').split('-');
            wardMovementForm.originBalance = trimmed[1] == '' ? 0 : parseInt(trimmed[1]);
        }
        const setWardDestinationName = (event) => {
            wardMovementForm.originBalance = 0;
            wardMovementForm.originid = '';
            getWardsNetBalances();
            wardMovementForm.destinationName = event.target.options[event.target.options.selectedIndex].text;
        }
        const wardTransfer = () => {
            var origin_name = wardMovementForm.originName.split(' - ')[0];
            if (parseInt(wardMovementForm.totalNetcard) > parseInt(wardMovementForm.originBalance)) {
                alert.Error('ERROR', "You can't reverse more than " + wardMovementForm.originBalance + ' e-Netcard');
                overlay.hide();
                return;
            }
            $.confirm({
                title: 'WARNING!',
                content: 'Are you sure you want to reverse <b>' + wardMovementForm.totalNetcard + '</b> e-Netcard from <b>' + origin_name + '</b> Ward to <b>' + wardMovementForm.destinationName + '</b> LGA?',
                buttons: {
                    delete: {
                        text: 'Reverse e-Netcard', btnClass: 'btn btn-danger mr-1 text-capitalize',
                        action: () => {
                            axios.post(
                                common.DataService +
                                '?qid=205&total=' + wardMovementForm.totalNetcard +
                                '&originid=' + wardMovementForm.originid +
                                '&destinationid=' + wardMovementForm.destinationid +
                                '&id=' + $('#v_g_id').val()
                            )
                                .then(response => {
                                    if (response.data.result_code == '200') {
                                        alert.Success('Success', '<b>' + response.data.message + '</b> Netcards has been reversed successfully from <b>' + origin_name + '</b> Ward to <b>' + wardMovementForm.destinationName + '</b> LGA');
                                        hideWardMoveModal();
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

        /* Geo lookups + stats ---------------------------------------- */
        const getsysDefaultDataSettings = () => {
            overlay.show();
            axios.get(common.DataService + '?qid=gen007')
                .then(response => {
                    if (response.data.data && response.data.data.length > 0) {
                        sysDefaultData.value = response.data.data[0];
                        getLgasLevel(response.data.data[0].stateid);
                        movementForm.geoLevel = 'state';
                        movementForm.geoLevelId = response.data.data[0].stateid;
                        stateMovementForm.stateid = response.data.data[0].stateid;
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
            axios.get(common.DataService + '?qid=gen005&e=' + lgaMovementForm.originid)
                .then(response => {
                    wardLevelData.value = (response.data && response.data.data) || [];
                    overlay.hide();
                })
                .catch(error => {
                    overlay.hide();
                    alert.Error('ERROR', safeMessage(error));
                });
        }
        const getWardsNetBalances = () => {
            overlay.show();
            axios.get(common.DataService + '?qid=207&lgaid=' + wardMovementForm.destinationid)
                .then(response => {
                    wardNetBalancesData.value = (response.data && response.data.data) || [];
                    overlay.hide();
                })
                .catch(error => {
                    overlay.hide();
                    alert.Error('ERROR', safeMessage(error));
                });
        }
        const getWardData = (event) => {
            overlay.show();
            var lgaid = event.target.options[event.target.options.selectedIndex].value;
            axios.get(common.DataService + '?qid=207&lgaid=' + lgaid)
                .then(response => {
                    wardNetBalancesData.value = (response.data && response.data.data) || [];
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
        const extractTotals = (data) => {
            var state = 0, lga = 0, ward = 0, mobilizer = 0, beneficiary = 0;
            if (Array.isArray(data)) {
                data.forEach(item => {
                    if (!item || typeof item !== 'object') return;
                    if (item.location === 'state') state = item.total || 0;
                    else if (item.location === 'lga') lga = item.total || 0;
                    else if (item.location === 'ward') ward = item.total || 0;
                    else if (item.location === 'mobilizer') mobilizer = item.total || 0;
                    else if (item.location === 'beneficiary') beneficiary = item.total || 0;
                });
            }
            return { state: state, lga: lga, ward: ward, mobilizer: mobilizer, beneficiary: beneficiary };
        }
        const getAllStat = () => {
            var endpoints = [common.DataService + '?qid=201', common.DataService + '?qid=201a'];
            Promise.all(endpoints.map(e => axios.get(e))).then(
                axios.spread((...allData) => {
                    overlay.show();
                    var totalRow = (allData[1] && allData[1].data && allData[1].data.data && allData[1].data.data[0]) || {};
                    allStatistics.total = parseInt(totalRow.total || 0);
                    var stat = extractTotals((allData[0] && allData[0].data && allData[0].data.data) || []);
                    allStatistics.stateBalance = stat.state;
                    allStatistics.lgaBalance = stat.lga;
                    allStatistics.wardBalance = stat.ward;
                    allStatistics.mobilizer = stat.mobilizer;
                    allStatistics.beneficiary = stat.beneficiary;
                    overlay.hide();
                })
            ).catch(() => { overlay.hide(); });
        }

        const onlyNumber = (event) => {
            var keyCode = event.keyCode || event.which;
            if ((keyCode < 48 || keyCode > 57) && keyCode == 46) event.preventDefault();
        }

        onMounted(() => {
            getsysDefaultDataSettings();
            getLgasNetBalances();
            loadTableData();
            getAllStat();
            bus.on('g-event-update', loadTableData);
        });
        onBeforeUnmount(() => {
            bus.off('g-event-update', loadTableData);
        });

        return {
            tableData, checkToggle, filterState, filters, permission, url,
            tableOptions, stateMovementForm, lgaMovementForm, wardMovementForm,
            movementForm, geoIndicator, geoLevelData, sysDefaultData, lgaLevelData,
            clusterLevelData, wardLevelData, lgaNetBalancesData, wardNetBalancesData,
            isLgabalance, allStatistics,
            loadTableData, selectAll, uncheckAll, selectToggle, checkedBg, toggleFilter,
            paginationDefault, nextPage, prevPage, currentPage, changePerPage, sort,
            applyFilter, removeSingleFilter, clearAllFilter,
            showStateMoveModal, hideStateMoveModal, setLgaName, transferFromStateToLGA,
            showLgaMoveModal, hideLgaMoveModal, setLgaOriginName, setLgaDestinationName,
            setLgaReverseVariable, lgaTransfer,
            showWardMoveModal, hideWardMoveModal, setWardOriginName, setWardDestinationName, wardTransfer,
            getsysDefaultDataSettings, getLgasLevel, getLgasNetBalances, getWardLevel,
            getWardsNetBalances, getWardData, refreshData, getAllStat, extractTotals,
            onlyNumber,
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
                        <li class="breadcrumb-item active">e-Netcard Movement</li>
                    </ol>
                </div>
            </div>

            <div class="col-lg-3 col-sm-6 col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <span>Total e-Netcard</span>
                            <div class="avatar bg-light-primary p-50 m-0 mt--1"><div class="avatar-content"><i data-feather="database" class="font-medium-4"></i></div></div>
                        </div>
                        <div class="d-block justify-content-between align-items-end mt-50 pt-25">
                            <div class="role-heading d-block pb-2">
                                <h4 class="fw-bolder pb-50" v-cloak>{{ formatNumber(allStatistics.total) }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-sm-6 col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <span>State Balance</span>
                            <div class="avatar bg-light-primary p-50 m-0 mt--1"><div class="avatar-content"><i data-feather="globe" class="font-medium-4"></i></div></div>
                        </div>
                        <div class="d-block justify-content-between align-items-end mt-50 pt-25">
                            <div class="role-heading d-block">
                                <h4 class="fw-bolder" v-cloak>{{ formatNumber(allStatistics.stateBalance) }}</h4>
                                <a v-if="permission.permission_value == 3" href="javascript:void(0);" data-backdrop="static" data-keyboard="false" class="state-move-modal float-right btn btn-sm btn-primary" @click="showStateMoveModal()" data-toggle="modal" data-target="#stateMove">
                                    <small class="fw-bolder">Transfer</small>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-sm-6 col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <span>LGAs Balance</span>
                            <div class="avatar bg-light-primary p-50 m-0 mt--1"><div class="avatar-content"><i data-feather="grid" class="font-medium-4"></i></div></div>
                        </div>
                        <div class="d-block justify-content-between align-items-end mt-50 pt-25">
                            <div class="role-heading d-block">
                                <h4 class="fw-bolder" v-cloak>{{ formatNumber(allStatistics.lgaBalance) }}</h4>
                                <div class="text-right">
                                    <a href="javascript:void(0);" data-backdrop="static" @click="isLgabalance = true" data-keyboard="false" class="lga-details-modal btn btn-sm btn-outline-primary mr-50" data-toggle="modal" data-target="#viewDetails">
                                        <small class="fw-bolder">View</small>
                                    </a>
                                    <a v-if="permission.permission_value == 3" href="javascript:void(0);" data-backdrop="static" data-keyboard="false" class="lga-move-modal btn btn-sm btn-primary" @click="showLgaMoveModal()" data-toggle="modal" data-target="#lgaMove">
                                        <small class="fw-bolder">Transfer</small>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-sm-6 col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <span>Wards Balance</span>
                            <div class="avatar bg-light-primary p-50 m-0 mt--1"><div class="avatar-content"><i data-feather="target" class="font-medium-4"></i></div></div>
                        </div>
                        <div class="d-block justify-content-between align-items-end mt-50 pt-25">
                            <div class="role-heading d-block">
                                <h4 class="fw-bolder" v-cloak>{{ formatNumber(allStatistics.wardBalance) }}</h4>
                                <div class="text-right">
                                    <a href="javascript:void(0);" data-backdrop="static" @click="isLgabalance = false" data-keyboard="false" class="ward-move-modal btn btn-sm btn-outline-primary mr-50" data-toggle="modal" data-target="#viewDetails">
                                        <small class="fw-bolder">View</small>
                                    </a>
                                    <a v-if="permission.permission_value == 3" href="javascript:void(0);" data-backdrop="static" data-keyboard="false" class="ward-move-modal btn btn-sm btn-primary" @click="showWardMoveModal()" data-toggle="modal" data-target="#wardMove">
                                        <small class="fw-bolder">Reverse</small>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-7 col-sm-7 col-7 mb-0">
                <h4 class="font-medium-1 float-left mb-0">Movement Transactions</h4>
            </div>
            <div class="col-md-5 col-sm-5 col-5 text-md-right text-right d-md-block">
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
                                <div class="col-9">
                                    <div class="form-group">
                                        <label>Movement Type</label>
                                        <select name="active" v-model="tableOptions.filterParam.movementType" class="form-control active select2">
                                            <option value="" selected>All</option>
                                            <option value="forward">Forward Movement</option>
                                            <option value="reverse">Reverse Movement</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-3">
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
                                    <th style="padding-right: 2px !important;">#</th>
                                    <th @click="sort(7)">Transfer By
                                        <i class="feather icon-chevron-up sort-up" :class="(tableOptions.orderField == 7 && tableOptions.orderDir =='asc')? 'active-sort': ''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 7 && tableOptions.orderDir =='desc')? 'active-sort': ''"></i>
                                    </th>
                                    <th @click="sort(2)">Movement Type
                                        <i class="feather icon-chevron-up sort-up" :class="(tableOptions.orderField == 2 && tableOptions.orderDir =='asc')? 'active-sort': ''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 2 && tableOptions.orderDir =='desc')? 'active-sort': ''"></i>
                                    </th>
                                    <th @click="sort(4)">Origin
                                        <i class="feather icon-chevron-up sort-up" :class="(tableOptions.orderField == 4 && tableOptions.orderDir =='asc')? 'active-sort': ''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 4 && tableOptions.orderDir =='desc')? 'active-sort': ''"></i>
                                    </th>
                                    <th @click="sort(6)">Destination
                                        <i class="feather icon-chevron-up sort-up" :class="(tableOptions.orderField == 6 && tableOptions.orderDir =='asc')? 'active-sort': ''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 6 && tableOptions.orderDir =='desc')? 'active-sort': ''"></i>
                                    </th>
                                    <th @click="sort(1)" style="padding-left: 5px !important; padding-right: 10px !important">Total
                                        <i class="feather icon-chevron-up sort-up" :class="(tableOptions.orderField == 1 && tableOptions.orderDir =='asc')? 'active-sort': ''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 1 && tableOptions.orderDir =='desc')? 'active-sort': ''"></i>
                                    </th>
                                    <th @click="sort(8)">Date
                                        <i class="feather icon-chevron-up sort-up" :class="(tableOptions.orderField == 8 && tableOptions.orderDir =='asc')? 'active-sort': ''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 8 && tableOptions.orderDir =='desc')? 'active-sort': ''"></i>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(g, i) in tableData" :key="i" :class="checkedBg(g.pick)">
                                    <td style="padding-right: 2px !important;">{{ i + 1 }}</td>
                                    <td>{{ g.user_fullname }}</td>
                                    <td>
                                        <div class="d-flex justify-content-left align-items-center">
                                            <div class="d-flex flex-column">
                                                <a href="#" class="user_name text-truncate text-body"><span class="fw-bolder text-primary">{{ capitalize(g.move_type) }}</span></a>
                                                <small class="emp_post text-muted">{{ capitalize(g.origin_level) }} <i class="feather icon-arrow-right"></i> {{ capitalize(g.destination_level) }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{ capitalize(g.origin) }}</td>
                                    <td>{{ capitalize(g.destination) }}</td>
                                    <td style="padding-left: 5px !important; padding-right: 10px !important">{{ Number(g.total).toLocaleString() }}</td>
                                    <td>{{ displayDate(g.created) }}</td>
                                </tr>
                                <tr v-if="tableData.length == 0"><td class="text-center pt-2" colspan="7"><small>No Training Added</small></td></tr>
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

            <!-- View Balances Modal -->
            <div class="modal fade modal-primary" id="viewDetails" tabindex="-1" role="dialog" aria-labelledby="exampleModalScrollableTitle" aria-hidden="true">
                <div class="modal-dialog modal-dialog-scrollable modal-lg" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalScrollableTitle" v-html="isLgabalance == true ? 'LGA e-Netcard Balances' : 'Ward e-Netcard Balances'"></h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close" @click="wardNetBalancesData = []">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div v-if="isLgabalance == true" class="table-responsive mt-3">
                                <table class="table table-bordered table-hover">
                                    <thead>
                                        <tr>
                                            <th style="padding-right: 2px !important;">#</th>
                                            <th>LGA Name</th>
                                            <th>Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="(g, i) in lgaNetBalancesData" :key="g.lgaid || i">
                                            <td>{{ i + 1 }}</td>
                                            <td>{{ g.lga }}</td>
                                            <td>{{ g.total }}</td>
                                        </tr>
                                        <tr v-if="lgaNetBalancesData.length == 0"><td class="text-center pt-4 pb-4" colspan="3"><small>No LGA with Balances</small></td></tr>
                                    </tbody>
                                </table>
                            </div>
                            <div v-else class="table-responsive mt-1">
                                <div class="form-group">
                                    <label class="form-label" for="user-role">Choose LGA to View Ward Balances</label>
                                    <select required class="form-control" @change="getWardData($event)">
                                        <option value="" selected>Choose LGA to View</option>
                                        <option v-for="(lga, i) in lgaLevelData" :key="lga.lgaid" :value="lga.lgaid">{{ lga.lga }}</option>
                                    </select>
                                </div>
                                <table class="table table-bordered table-hover">
                                    <thead>
                                        <tr>
                                            <th style="padding-right: 2px !important;">#</th>
                                            <th>Ward Name</th>
                                            <th>Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="(g, i) in wardNetBalancesData" :key="g.wardid || i">
                                            <td>{{ i + 1 }}</td>
                                            <td>{{ g.ward }}</td>
                                            <td>{{ g.total }}</td>
                                        </tr>
                                        <tr v-if="wardNetBalancesData.length == 0"><td class="text-center font-small-3 pt-4 pb-4" colspan="3"><span class="text-info">No Ward with Balances</span> or <span class="text-warning">Kindly check if LGA was selected</span></td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-primary" data-dismiss="modal" @click="wardNetBalancesData = []">Close</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- State Move Modal -->
            <div class="modal modal-slide-in move modal-primary" id="stateMove" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-scrollable modal-xl">
                    <form class="add-new-user modal-content pt-0" @submit.stop.prevent="transferFromStateToLGA()" id="state-form">
                        <button type="reset" class="close" @click="hideStateMoveModal()" data-dismiss="modal">×</button>
                        <div class="modal-header mb-1">
                            <h5 class="modal-title font-weight-bolder">State Movement</h5>
                        </div>
                        <div class="modal-body flex-grow-1 vertical-wizard">
                            <div class="form-group">
                                <label class="form-label full">Total Netcard to Transfer</label>
                                <input type="number" id="state-spin" placeholder="Total Netcard to Transfer" @keypress="onlyNumber($event)" required v-model="stateMovementForm.totalNetcard" class="form-control" />
                            </div>
                            <div class="form-group">
                                <label class="form-label">Originating State</label>
                                <select placeholder="Select Geo Level" class="form-control" v-model="stateMovementForm.stateid">
                                    <option :value="sysDefaultData.stateid">{{ sysDefaultData.state }}</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Destination LGA</label>
                                <select required class="form-control" v-model="stateMovementForm.lgaid" @change="setLgaName($event)">
                                    <option value="">Choose a Destination LGA</option>
                                    <option v-for="lga in lgaLevelData" :key="lga.lgaid" :value="lga.lgaid">{{ lga.lga }}</option>
                                </select>
                            </div>
                            <div class="mt-4">
                                <button type="submit" class="btn btn-primary mr-1 data-submit">Transfer eNetCard</button>
                                <button type="reset" class="btn btn-outline-secondary" data-dismiss="modal" @click="hideStateMoveModal()">Cancel</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- LGA Move Modal -->
            <div class="modal modal-slide-in move" id="lgaMove" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-scrollable modal-xl">
                    <form class="add-new-user modal-content pt-0" @submit.stop.prevent="lgaTransfer(lgaMovementForm.lgaMoveBtn)">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close" @click="hideLgaMoveModal()">×</button>
                        <div class="modal-header mb-1">
                            <h5 v-if="lgaMovementForm.lgaMoveBtn == 'Forward'" class="modal-title font-weight-bolder text-capitalize">Transfer e-Netcard From <span class="text-info">LGA </span> to <span class="text-success">Ward</span></h5>
                            <h5 v-else class="modal-title font-weight-bolder text-capitalize">Reverse e-Netcard from <span class="text-info">LGA </span> to <span class="text-success">State</span></h5>
                        </div>
                        <div class="modal-body flex-grow-1 vertical-wizard mt-1">
                            <div class="bs-stepper vertical vertical-wizard-example">
                                <div class="bs-stepper-header mb-50">
                                    <label class="form-label">Kindly Check an Options</label>
                                    <div class="step" :class="lgaMovementForm.lgaMoveBtn == 'Forward' ? 'active' : ''" data-target="#account-details-vertical">
                                        <button type="button" class="step-trigger" @click="lgaMovementForm.lgaMoveBtn = 'Forward'">
                                            <span class="bs-stepper-box"><i class="feather" :class="lgaMovementForm.lgaMoveBtn == 'Forward' ? 'icon-check' : 'icon-x'"></i></span>
                                            <span class="bs-stepper-label">
                                                <span class="bs-stepper-title">Forward Movement</span>
                                                <span class="bs-stepper-subtitle">From State to LGA</span>
                                            </span>
                                        </button>
                                    </div>
                                    <div class="step" :class="lgaMovementForm.lgaMoveBtn == 'Reverse' ? 'active' : ''" data-target="#personal-info-vertical">
                                        <button type="button" class="step-trigger" @click="setLgaReverseVariable()">
                                            <span class="bs-stepper-box"><i class="feather" :class="lgaMovementForm.lgaMoveBtn == 'Reverse' ? 'icon-check' : 'icon-x'"></i></span>
                                            <span class="bs-stepper-label">
                                                <span class="bs-stepper-title">Reverse Movement</span>
                                                <span class="bs-stepper-subtitle">From LGA to State</span>
                                            </span>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div v-if="lgaMovementForm.lgaMoveBtn == 'Reverse'">
                                <div class="form-group">
                                    <label class="form-label">Originating LGA</label>
                                    <select required class="form-control" v-model="lgaMovementForm.originid" @change="setLgaOriginName($event)">
                                        <option value="">Choose a LGA to Reverse From</option>
                                        <option v-for="(lga, i) in lgaNetBalancesData" :key="lga.lgaid || i" :value="lga.lgaid">{{ lga.lga }} - {{ lga.total }}</option>
                                    </select>
                                    <label><b class="text-danger">Origin Balance:</b> {{ formatNumber(parseInt(lgaMovementForm.originBalance)) }}</label>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Destination State</label>
                                    <select class="form-control" v-model="lgaMovementForm.destinationid" @change="setLgaDestinationName($event)">
                                        <option :value="sysDefaultData.stateid">{{ sysDefaultData.state }}</option>
                                    </select>
                                </div>
                            </div>

                            <div v-else>
                                <div class="form-group">
                                    <label class="form-label">Originating LGA</label>
                                    <select required class="form-control" v-model="lgaMovementForm.originid" @change="setLgaOriginName($event)">
                                        <option value="">Choose a LGA to Transfer From</option>
                                        <option v-for="(lga, i) in lgaNetBalancesData" :key="lga.lgaid || i" :value="lga.lgaid">{{ lga.lga }} - {{ lga.total }}</option>
                                    </select>
                                    <label><b class="text-danger">Origin Balance:</b> {{ formatNumber(parseInt(lgaMovementForm.originBalance)) }}</label>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Destination Ward</label>
                                    <select class="form-control" v-model="lgaMovementForm.destinationid" @change="setLgaDestinationName($event)">
                                        <option value="">Choose a Ward to Transfer To</option>
                                        <option v-for="g in wardLevelData" :key="g.wardid" :value="g.wardid">{{ g.ward }}</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Total Netcards to Move</label>
                                <input type="number" required v-model="lgaMovementForm.totalNetcard" @keypress="onlyNumber($event)" placeholder="Total Netcards to Move" class="form-control" />
                            </div>

                            <div class="mt-2">
                                <button type="submit" class="btn btn-primary mr-1 data-submit">{{ lgaMovementForm.lgaMoveBtn }} eNetCard</button>
                                <button type="reset" class="btn btn-outline-secondary" data-dismiss="modal" @click="hideLgaMoveModal()">Cancel</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Ward Reverse Modal -->
            <div class="modal modal-slide-in move" id="wardMove" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-scrollable modal-xl">
                    <form class="add-new-user modal-content pt-0" @submit.stop.prevent="wardTransfer()">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close" @click="hideWardMoveModal()">×</button>
                        <div class="modal-header mb-1">
                            <h5 class="modal-title font-weight-bolder text-capitalize">Reverse e-Netcard From <span class="text-info">Ward </span> to <span class="text-success">LGA</span></h5>
                        </div>
                        <div class="modal-body flex-grow-1 vertical-wizard mt-1">
                            <div>
                                <div class="form-group">
                                    <label class="form-label">Destination LGA</label>
                                    <select required class="form-control" v-model="wardMovementForm.destinationid" @change="setWardDestinationName($event)">
                                        <option value="">Choose a LGA to Transfer To</option>
                                        <option v-for="lga in lgaLevelData" :key="lga.lgaid" :value="lga.lgaid">{{ lga.lga }}</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Originating Ward</label>
                                    <select class="form-control" v-model="wardMovementForm.originid" @change="setWardOriginName($event)">
                                        <option value="">Choose a Ward to Reverse From</option>
                                        <option v-for="g in wardNetBalancesData" :key="g.wardid" :value="g.wardid">{{ g.ward }} - {{ g.total }}</option>
                                    </select>
                                    <label><b class="text-danger">Origin Balance:</b> {{ formatNumber(parseInt(wardMovementForm.originBalance)) }}</label>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Total Netcards to Move</label>
                                <input type="number" required placeholder="Total Netcards to Move" @keypress="onlyNumber($event)" v-model="wardMovementForm.totalNetcard" class="form-control" />
                            </div>
                            <div class="mt-2">
                                <button type="submit" class="btn btn-primary mr-1 data-submit">Reverse eNetCard</button>
                                <button type="reset" class="btn btn-outline-secondary" data-dismiss="modal" @click="hideWardMoveModal()">Cancel</button>
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
    .component('necard_movement', NecardMovement)
    .mount('#app');
