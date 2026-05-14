/**
 * SMC / Logistics / Bulk Allocation — Vue 3 Composition API in place.
 * Two views — page-table (paginated qid=801 list) and page-create-issue
 * (drag-fill grid where you enter secondary_qty per facility/product).
 */

const { ref, reactive, computed, watch, nextTick, onMounted, onBeforeUnmount } = Vue;
const { useApp, useFormat, bus, safeMessage } = window.utils;

const appState = Vue.reactive({
    pageState: { page: 'table', title: '' },
    permission: (typeof getPermission === 'function')
        ? (getPermission(typeof per !== 'undefined' ? per : null, 'smc') || { permission_value: 0 })
        : { permission_value: 0 },
    userId: (() => { var el = document.getElementById('v_g_id'); return el ? el.value : ''; })(),
    geoLevelForm: { geoLevel: '', geoLevelId: 0 },
    defaultStateId: '',
    sysDefaultData: [],
    productData: [],
    lgaData: [],
    periodData: [],
    currentPeriodId: '',
    currentLgaId: '',
    selectedLgaKey: '',
    facilityTitles: '',
    level: 'lga',
});

const setSelectedLga = () => {
    var key = appState.selectedLgaKey;
    var selectedLga = appState.lgaData[key];
    if (!selectedLga) return;
    appState.facilityTitles = selectedLga.lga;
    appState.currentLgaId = selectedLga.lgaid;
    bus.emit('g-event-reset-form');
}

const PageTable = {
    setup() {
        const fmtUtils = useFormat();

        const tableData = ref([]);
        const roleListData = ref([]);
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
                gid: appState.currentLgaId,
                glv: appState.level,
                lga_name: appState.facilityTitles,
            },
        });
        const geoLevelData = ref([]);

        const reloadTableListOnUpdate = () => { paginationDefault(); loadTableData(); };
        const loadTableData = () => {
            overlay.show();
            axios.get(
                common.TableService +
                '?qid=801&draw=' + tableOptions.currentPage +
                '&order_column=' + tableOptions.orderField +
                '&length=' + tableOptions.perPage +
                '&start=' + tableOptions.limitStart +
                '&order_dir=' + tableOptions.orderDir +
                '&gid=' + appState.currentLgaId +
                '&glv=lga'
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
            if (filterState.value === false) filters.value = false;
            return (filterState.value = !filterState.value);
        }
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
            if (appState.currentLgaId != '') {
                tableOptions.filterParam.gid = appState.currentLgaId;
                tableOptions.filterParam.glv = appState.level;
                tableOptions.filterParam.lga_name = appState.facilityTitles;
            }
            var checkFill = 0;
            checkFill += tableOptions.filterParam.gid != '' ? 1 : 0;
            checkFill += tableOptions.filterParam.level != '' ? 1 : 0;
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
            if (column_name == 'lga_name') {
                tableOptions.filterParam.gid = '';
                tableOptions.filterParam.glv = '';
                tableOptions.filterParam.lga_name = '';
                appState.selectedLgaKey = '';
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
            tableOptions.filterParam.gid = '';
            tableOptions.filterParam.level = '';
            tableOptions.filterParam.lga_name = '';
            appState.selectedLgaKey = '';
            paginationDefault();
            loadTableData();
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
        const totalCheckedBox = () => {
            var total = tableData.value.filter(r => r.pick).length;
            var el = document.getElementById('total-selected');
            if (!el) return;
            if (total > 0) el.innerHTML = '<span class="badge badge-primary btn-icon"><span class="badge badge-success">' + total + '</span> Selected</span>';
            else el.replaceChildren();
        }
        const goToCreateIssue = () => {
            appState.pageState.page = 'create-issues';
            appState.currentPeriodId = '';
            appState.currentLgaId = '';
            appState.facilityTitles = '';
            appState.selectedLgaKey = '';
        }
        const getProductMaster = () => {
            overlay.show();
            axios.get(common.DataService + '?qid=gen011')
                .then(response => {
                    var data = ((response.data && response.data.data) || []).slice().sort((a, b) => a.product_code.localeCompare(b.product_code));
                    appState.productData = data;
                    overlay.hide();
                })
                .catch(error => {
                    overlay.hide();
                    alert.Error('ERROR', safeMessage(error));
                });
        }

        onMounted(() => {
            getProductMaster();
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
            bus.on('g-event-refresh-page', reloadTableListOnUpdate);
        });
        onBeforeUnmount(() => {
            bus.off('g-event-refresh-page', reloadTableListOnUpdate);
        });

        return {
            appState, tableData, roleListData, geoData, checkToggle, filterState, filters,
            tableOptions, geoLevelData,
            reloadTableListOnUpdate, loadTableData,
            selectAll, uncheckAll, selectToggle, checkedBg, toggleFilter,
            paginationDefault, resetSelected, nextPage, prevPage, currentPage,
            changePerPage, sort, applyFilter, removeSingleFilter, clearAllFilter,
            getGeoLocation, setLocation, refreshData, totalCheckedBox,
            goToCreateIssue, getProductMaster, setSelectedLga,
            capitalize: fmtUtils.capitalize,
            displayDate: fmtUtils.displayDate,
            formatNumber: fmtUtils.formatNumber,
            convertStringNumberToFigures: fmtUtils.convertStringNumberToFigures,
        };
    },
    template: `
        <div class="row">
            <div class="col-md-8 col-sm-12 col-12 mb-0">
                <h2 class="content-header-title header-txt float-left mb-0">SMC</h2>
                <div class="breadcrumb-wrapper">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../smc/logistics">Logistics</a></li>
                        <li class="breadcrumb-item active">Bulk Allocation</li>
                    </ol>
                    <span id="total-selected"></span>
                </div>
            </div>
            <div class="col-md-4 col-sm-12 col-12 text-md-right text-right d-md-block">
                <div class="btn-group">
                    <button type="button" class="btn btn-outline-primary round searchBtn" @click="refreshData()" data-toggle="tooltip" data-placement="top" title="Refresh"><i class="feather icon-refresh-cw"></i></button>
                    <button type="button" class="btn btn-outline-primary round searchBtn" @click="toggleFilter()" data-toggle="tooltip" data-placement="top" title="Filter"><i class="feather" :class="filterState ? 'icon-x' : 'icon-filter'"></i></button>
                    <button v-if="appState.permission.permission_value >= 2" type="button" class="btn btn-outline-primary round" data-toggle="tooltip" data-placement="top" title="Create Issue" @click="goToCreateIssue()"><i class="feather icon-plus"></i></button>
                </div>
            </div>

            <div class="col-12" v-if="filters">
                <div class="col-12 filter-bar">
                    <template v-for="(filterParam, i) in tableOptions.filterParam" :key="i">
                        <span class="badge badge-dark filter-box" v-if="String(filterParam).length > 0 && i != 'glv' && i != 'geo_level_id' && i != 'role_id'" @click="removeSingleFilter(i)">{{ capitalize(i) }}: {{ filterParam }} <i class="feather icon-x"></i></span>
                    </template>
                    <a href="#" class="float-right clear-filter" @click="clearAllFilter()">Clear</a>
                </div>
            </div>

            <div class="col-12 mt-1">
                <div class="card custom-select-down">
                    <div class="card-body py-1" v-show="filterState">
                        <form id="filterForm">
                            <div class="row">
                                <div class="col-12 col-md-5 col-sm-12 col-lg-5">
                                    <div class="form-group">
                                        <label>Choose LGA to Populate Issue</label>
                                        <select class="form-control" v-model="appState.selectedLgaKey" @change="setSelectedLga($event)">
                                            <option value="">Choose LGA</option>
                                            <option v-for="(g, i) in appState.lgaData" :key="i" :value="i">{{ g.lga }}</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-12 col-md-5 col-sm-12 col-lg-5">
                                    <div class="form-group">
                                        <label>Visit</label>
                                        <select v-model="appState.currentPeriodId" class="form-control period">
                                            <option value="">Choose Visit</option>
                                            <option v-for="(g, i) in appState.periodData" :key="g.periodid" :value="g.periodid">{{ g.title }}</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-12 col-sm-12 col-md-2 col-lg-2">
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
                                    <th>#</th>
                                    <th class="pl-0">Geo String</th>
                                    <th class="pl-1">Product</th>
                                    <th class="pl-1">Secondary QTY.</th>
                                    <th class="pl-1">Primary QTY.</th>
                                    <th class="pl-1">Created</th>
                                    <th class="px-1">Updated</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(g, rowIndex) in tableData" :key="rowIndex">
                                    <td>{{ rowIndex + 1 }}</td>
                                    <td class="pl-0 text-wrap">{{ g.geo_string }}</td>
                                    <td class="pl-1">{{ g.product_name }}</td>
                                    <td class="pl-1">{{ convertStringNumberToFigures(g.secondary_qty) }}</td>
                                    <td class="pl-1">{{ convertStringNumberToFigures(g.primary_qty) }}</td>
                                    <td class="pl-1">{{ displayDate(g.created) }}</td>
                                    <td class="px-1">{{ displayDate(g.updated) }}</td>
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

const PageCreateIssue = {
    setup() {
        const fmtUtils = useFormat();

        const facilityData = ref({});
        const tempFacilityData = ref({});
        const editingCell = reactive({ rowIndex: null, productCode: null });
        const isUpdated = ref(false);
        const dragStart = ref(null);
        const dragField = ref(null);
        const facilityName = ref(null);
        const product = ref(null);

        const getSysDefaultDataSettings = () => {
            overlay.show();
            axios.get(common.DataService + '?qid=gen007')
                .then(response => {
                    if (response.data.data && response.data.data.length > 0) {
                        appState.sysDefaultData = response.data.data[0];
                        getAllLga(response.data.data[0].stateid);
                        appState.geoLevelForm.geoLevel = 'state';
                        appState.geoLevelForm.geoLevelId = response.data.data[0].stateid;
                        appState.defaultStateId = response.data.data[0].stateid;
                    }
                    overlay.hide();
                })
                .catch(error => {
                    overlay.hide();
                    alert.Error('ERROR', safeMessage(error));
                });
        }
        const getAllLga = (stateid) => {
            overlay.show();
            axios.post(common.DataService + '?qid=gen003', JSON.stringify(stateid))
                .then(response => {
                    appState.lgaData = (response.data && response.data.data) || [];
                    overlay.hide();
                })
                .catch(error => {
                    overlay.hide();
                    alert.Error('ERROR', safeMessage(error));
                });
        }
        const getAllPeriodLists = () => {
            overlay.show();
            axios.get(common.DataService + '?qid=1004')
                .then(response => {
                    appState.periodData = (response.data && response.data.data) || [];
                    overlay.hide();
                })
                .catch(error => {
                    overlay.hide();
                    alert.Error('ERROR', safeMessage(error));
                });
        }

        const groupAndFillMissingProducts = (data, masterProductData) => {
            var grouped = {};
            const sanitize = (obj) => {
                var clean = {};
                for (var key in obj) {
                    clean[key] = obj[key] === null || obj[key] === undefined ? '' : obj[key];
                }
                return clean;
            }
            data.forEach(item => {
                var key = item.geo_string;
                if (!grouped[key]) grouped[key] = [];
                if (!item.product_code) {
                    masterProductData.forEach(prod => {
                        grouped[key].push(sanitize(Object.assign({}, item, {
                            product_code: prod.product_code,
                            product_name: prod.name,
                        })));
                    });
                } else {
                    grouped[key].push(sanitize(item));
                }
            });
            for (var key in grouped) {
                var entries = grouped[key];
                var existingCodes = new Set(entries.map(i => i.product_code));
                var baseDPID = (entries[0] && entries[0].dpid) || '';
                masterProductData.forEach(prod => {
                    if (!existingCodes.has(prod.product_code)) {
                        entries.push(sanitize({
                            geo_string: key, dpid: baseDPID, issue_id: '',
                            period: appState.currentPeriodId,
                            product_code: prod.product_code,
                            product_name: prod.name,
                            primary_qty: '', secondary_qty: '', created: '',
                        }));
                    }
                });
                entries.sort((a, b) => a.product_code.localeCompare(b.product_code));
            }
            return grouped;
        }

        const getFacilityIssueByPeriod = async () => {
            var currentLgaId = appState.currentLgaId;
            var currentPeriodId = appState.currentPeriodId;
            var productData = appState.productData;
            if (!currentLgaId)    { alert.Error('ERROR', 'Please select LGA'); return; }
            if (!currentPeriodId) { alert.Error('ERROR', 'Please select a visit'); return; }
            overlay.show();
            try {
                var response = await axios.post(common.DataService + '?qid=gen010', {
                    lgaId: currentLgaId, periodId: currentPeriodId,
                });
                facilityData.value = groupAndFillMissingProducts(
                    (response.data && response.data.data) || [],
                    productData || []
                );
                tempFacilityData.value = typeof structuredClone === 'function'
                    ? structuredClone(facilityData.value)
                    : JSON.parse(JSON.stringify(facilityData.value));
            } catch (error) {
                alert.Error('ERROR', safeMessage(error));
            } finally {
                overlay.hide();
            }
        }

        const isEditing = (rowIndex, productCode) => {
            return editingCell.rowIndex === rowIndex && editingCell.productCode === productCode;
        }
        const stopEdit = () => { editingCell.rowIndex = null; editingCell.productCode = null; };

        const prepareIssues = (fd) => {
            var packSize = 50;
            var periodId = appState.currentPeriodId || null;
            var result = [];
            if (!fd || typeof fd !== 'object') return result;
            Object.values(fd).forEach(entries => {
                (entries || []).forEach(entry => {
                    if (!entry) return;
                    var secondaryQty = Number(entry.secondary_qty);
                    if (!isNaN(secondaryQty) && secondaryQty > 0) {
                        result.push({
                            issue_id: entry.issue_id != null && entry.issue_id !== '' ? parseInt(entry.issue_id) : '',
                            periodid: periodId,
                            dpid: entry.dpid != null ? entry.dpid : null,
                            product_code: entry.product_code || null,
                            product_name: entry.product_name || null,
                            primary_qty: secondaryQty * packSize,
                            secondary_qty: secondaryQty,
                        });
                    }
                });
            });
            return result;
        }
        const submitIssues = () => {
            if (appState.currentPeriodId == '') { alert.Error('ERROR', 'Please select a visit'); return; }
            var data = prepareIssues(facilityData.value);
            overlay.show();
            axios.post(common.DataService + '?qid=1129', JSON.stringify(data))
                .then(response => {
                    overlay.hide();
                    if (response.data.result_code == '200') {
                        bus.emit('g-event-refresh-page');
                        alert.Success('SUCCESS', response.data.message);
                        goToIssueTable();
                    } else {
                        alert.Error('ERROR', response.data.message);
                    }
                })
                .catch(error => {
                    overlay.hide();
                    alert.Error('ERROR', safeMessage(error));
                });
        }
        const goToIssueTable = () => {
            appState.pageState.page = 'table';
            facilityData.value = {};
            tempFacilityData.value = {};
            appState.currentPeriodId = '';
            appState.currentLgaId = '';
            appState.facilityTitles = '';
            appState.selectedLgaKey = '';
        }
        const resetForm = () => { facilityData.value = {}; tempFacilityData.value = {}; };

        const startDrag = (rowIndex, field, fName, prod) => {
            dragStart.value = rowIndex;
            dragField.value = field;
            facilityName.value = fName;
            product.value = prod;
            window.addEventListener('mouseup', finishDrag);
        }
        const onDragOver = (currentRow, fName, field) => {
            if (dragStart.value !== null && dragField.value !== null) {
                var startRow = Math.min(dragStart.value, currentRow);
                var endRow = Math.max(dragStart.value, currentRow);
                var startField = dragField.value;
                var currentField = field;
                var updated = fName;
                var valueToFill = product.value && product.value.secondary_qty;
                var allProductCodes = (facilityData.value[updated] || []).map(item => item.product_code && item.product_code.toUpperCase());
                var startColIndex = allProductCodes.indexOf(startField && startField.toUpperCase());
                var endColIndex = allProductCodes.indexOf(currentField && currentField.toUpperCase());
                if (startColIndex === -1 || endColIndex === -1) return;
                var minCol = Math.min(startColIndex, endColIndex);
                var maxCol = Math.max(startColIndex, endColIndex);
                for (var i = startRow; i <= endRow; i++) {
                    for (var j = minCol; j <= maxCol; j++) {
                        var targetCode = allProductCodes[j];
                        var position = (facilityData.value[updated] || []).findIndex(item => item.product_code && item.product_code.toUpperCase() === targetCode);
                        if (position !== -1) {
                            facilityData.value[updated][position]['secondary_qty'] = valueToFill;
                        }
                    }
                }
            }
        }
        const finishDrag = () => {
            dragStart.value = null;
            dragField.value = null;
            facilityName.value = null;
            product.value = null;
            window.removeEventListener('mouseup', finishDrag);
        }
        const getCellClass = (rowIndex) => {
            return { 'drag-target': dragStart.value !== null && rowIndex !== dragStart.value };
        }
        const resetIssues = () => { facilityData.value = JSON.parse(JSON.stringify(tempFacilityData.value)); };
        const cancelIssue = () => {
            if (isUpdated.value) {
                $.confirm({
                    title: 'WARNING!',
                    content: 'Are you sure you want to discard the changes made?',
                    buttons: {
                        discard: { text: 'Discard', btnClass: 'btn btn-danger mr-1', action: () => { goToIssueTable(); } },
                        cancel: () => {},
                    },
                });
            } else {
                goToIssueTable();
            }
        }

        const groupedProductSummary = computed(() => {
            var totals = {};
            Object.values(facilityData.value || {}).forEach(records => {
                (records || []).forEach(r => {
                    var code = r && r.product_code && r.product_code.toUpperCase();
                    if (!code) return;
                    totals[code] = (totals[code] || 0) + (Number(r.secondary_qty) || 0);
                });
            });
            return (appState.productData || []).map(p => {
                var code = p.product_code && p.product_code.toUpperCase();
                return { product_code: code, total: totals[code] || 0 };
            });
        });
        const hasFacilityData = computed(() => facilityData.value && Object.values(facilityData.value).flat().length > 0);

        watch(facilityData, () => {
            isUpdated.value = JSON.stringify(facilityData.value) !== JSON.stringify(tempFacilityData.value);
        }, { deep: true });

        onMounted(() => {
            getSysDefaultDataSettings();
            getAllPeriodLists();
            bus.on('g-event-reset-form', resetForm);
        });
        onBeforeUnmount(() => {
            bus.off('g-event-reset-form', resetForm);
        });

        return {
            appState, facilityData, tempFacilityData, editingCell, isUpdated,
            dragStart, dragField, facilityName, product,
            groupedProductSummary, hasFacilityData,
            getSysDefaultDataSettings, getAllLga, getAllPeriodLists,
            groupAndFillMissingProducts, getFacilityIssueByPeriod,
            isEditing, stopEdit, prepareIssues, submitIssues,
            goToIssueTable, resetForm,
            startDrag, onDragOver, finishDrag, getCellClass,
            resetIssues, cancelIssue, setSelectedLga,
            capitalize: fmtUtils.capitalize,
            displayDate: fmtUtils.displayDate,
            formatNumber: fmtUtils.formatNumber,
            convertStringNumberToFigures: fmtUtils.convertStringNumberToFigures,
            numbersOnlyWithoutDot: fmtUtils.numbersOnlyWithoutDot,
            validatePaste: fmtUtils.validatePaste,
        };
    },
    template: `
        <div class="row">
            <div class="col-md-8 col-sm-12 col-12 mb-0">
                <h2 class="content-header-title header-txt float-left mb-0">SMC</h2>
                <div class="breadcrumb-wrapper">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../smc/logistics">Logistics</a></li>
                        <li class="breadcrumb-item"><a href="javascript:void(0)" @click="cancelIssue()">Issues</a></li>
                        <li class="breadcrumb-item active">Bulk Allocation</li>
                    </ol>
                </div>
            </div>

            <div class="col-12 mt-1">
                <div class="card">
                    <div class="card-header">
                        <button class="btn pl-0 pr-50 py-50 waves-effect" @click="cancelIssue()"><i class="feather icon-chevron-left"></i> Back</button>
                    </div>

                    <div class="card-body">
                        <div class="card border mb-0 shadow shadow-sm border-light border-lighten-1">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-12 col-md-5 col-sm-12 col-lg-5">
                                        <div class="form-group">
                                            <label>Choose LGA to Populate Issue</label>
                                            <select class="form-control" v-model="appState.selectedLgaKey" @change="setSelectedLga($event)">
                                                <option value="">Choose LGA</option>
                                                <option v-for="(g, i) in appState.lgaData" :key="i" :value="i">{{ g.lga }}</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-12 col-md-4 col-sm-12 col-lg-5">
                                        <div class="form-group">
                                            <label>Visit</label>
                                            <select v-model="appState.currentPeriodId" @change="resetForm()" class="form-control period">
                                                <option value="">Choose Visit</option>
                                                <option v-for="(g, i) in appState.periodData" :key="g.periodid" :value="g.periodid">{{ g.title }}</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-12 col-md-3 col-sm-12 col-lg-2 text-right">
                                        <div class="form-group">
                                            <label class="d-none d-md-block d-lg-block">&nbsp;</label>
                                            <button type="button" style="max-width: 120px !important" class="btn btn-primary form-control" @click="getFacilityIssueByPeriod()">Load <i class="feather icon-send ml-1 text-right"></i></button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive mt-2" v-show="hasFacilityData">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th class="px-1">#</th>
                                        <th>{{ appState.facilityTitles }} Facilities</th>
                                        <th class="px-1" v-for="(p, i) in appState.productData" :key="p.product_code">{{ p.name }} QTY</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(facility, rowIndex, g) in facilityData" :key="rowIndex">
                                        <td class="px-1">{{ g + 1 }}</td>
                                        <td>{{ rowIndex }}</td>
                                        <td style="max-width: 160px;" class="px-1" v-for="p in facility" :key="p.product_code">
                                            <input type="text" class="form-control" v-model="p.secondary_qty"
                                                @mousedown="startDrag(g, p.product_code, rowIndex, p)"
                                                @mouseover="onDragOver(g, rowIndex, p.product_code)"
                                                @keypress="numbersOnlyWithoutDot($event)"
                                                @paste="validatePaste($event)"
                                                @blur="stopEdit()"
                                                @keyup.enter="stopEdit()" />
                                            <small class="font-small-1">{{ p.product_code }}</small>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th colspan="2" class="text-right">Total</th>
                                        <th class="pl-1 pr-1 text-wrap" v-for="p in appState.productData" :key="p.product_code + '-total'">
                                            {{ convertStringNumberToFigures((groupedProductSummary.find(x => x.product_code && x.product_code.toUpperCase() === (p.product_code && p.product_code.toUpperCase())) || {}).total || 0) }}
                                        </th>
                                    </tr>
                                    <tr v-if="!hasFacilityData">
                                        <td class="text-center pt-2" :colspan="parseInt((appState.productData || []).length) + 2"><small>No Facility Added</small></td>
                                    </tr>
                                    <tr>
                                        <th :colspan="parseInt((appState.productData || []).length) + 2" class="text-right pl-1 pr-1">
                                            <button :disabled="!isUpdated || appState.currentPeriodId === ''" class="btn btn-outline-warning btn-md mr-1" @click="resetIssues()">Reset <i class="feather icon-trash-2 ml-50"></i></button>
                                            <button class="btn btn-outline-danger mr-1 btn-md" @click="cancelIssue()">Cancel <i class="feather icon-x ml-50"></i></button>
                                            <button :disabled="!isUpdated || appState.currentPeriodId === ''" class="btn btn-primary btn-md" @click="submitIssues()">Update Issue <i class="feather icon-send ml-50"></i></button>
                                        </th>
                                    </tr>
                                </tbody>
                            </table>
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
            <div v-show="appState.pageState.page == 'create-issues'"><page-create-issue/></div>
        </div>
    `,
    setup() { return { appState }; },
})
    .component('page-table', PageTable)
    .component('page-create-issue', PageCreateIssue)
    .mount('#app');
