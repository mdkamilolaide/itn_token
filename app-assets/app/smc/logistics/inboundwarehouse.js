/**
 * SMC / Logistics / Inbound Warehouse — Vue 3 Composition API in place.
 * Two views — page-table (qid=802 paginated list) and page-create-issue
 * (dynamic-row inbound creation form, qid=1130).
 */

const { ref, reactive, computed, watch, nextTick, onMounted, onBeforeUnmount } = Vue;
const { useApp, useFormat, bus, safeMessage } = window.utils;

const appState = Vue.reactive({
    pageState: { page: 'table', title: '' },
    permission: (typeof getPermission === 'function')
        ? (getPermission(typeof per !== 'undefined' ? per : null, 'smc') || { permission_value: 0 })
        : { permission_value: 0 },
    userId: (function () { var el = document.getElementById('v_g_id'); return el ? el.value : ''; })(),
    geoLevelForm: { geoLevel: '', geoLevelId: 0 },
    defaultStateId: '',
    sysDefaultData: [],
    productData: [],
    lgaData: [],
    cmsLocationMaster: [],
    inBoundData: [],
    periodData: [],
    currentPeriodId: '',
    currentLgaId: '',
    selectedLgaKey: '',
    facilityTitles: '',
    level: 'lga',
});

function setSelectedLga() {
    var key = appState.selectedLgaKey;
    var selectedLga = appState.lgaData[key];
    if (!selectedLga) return;
    appState.facilityTitles = selectedLga.lga;
    appState.currentLgaId = selectedLga.lgaid;
    bus.emit('g-event-reset-form');
}

function displayDateLong(d, fullDate, withTime) {
    if (fullDate === undefined) fullDate = false;
    if (withTime === undefined) withTime = true;
    var date = new Date(d);
    var options = {
        year: 'numeric',
        month: fullDate ? 'long' : 'short',
        day: 'numeric',
    };
    if (withTime) { options.hour = '2-digit'; options.minute = '2-digit'; options.hour12 = true; }
    return date.toLocaleString('en-US', options);
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
            orderDir: 'desc', orderField: 15, limitStart: 0,
            isNext: false, isPrev: false,
            aLength: [10, 20, 50, 100, 150, 200],
            filterParam: {
                gid: appState.currentLgaId,
                glv: appState.level,
                lga_name: appState.facilityTitles,
            },
        });
        const geoLevelData = ref([]);

        function reloadTableListOnUpdate() { paginationDefault(); loadTableData(); }
        function loadTableData() {
            overlay.show();
            axios.get(
                common.TableService +
                '?qid=802&draw=' + tableOptions.currentPage +
                '&order_column=' + tableOptions.orderField +
                '&length=' + tableOptions.perPage +
                '&start=' + tableOptions.limitStart +
                '&order_dir=' + tableOptions.orderDir +
                '&gid=' + appState.currentLgaId +
                '&glv=lga'
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
        function resetSelected() { uncheckAll(); checkToggle.value = false; totalCheckedBox(); }
        function nextPage() { resetSelected(); tableOptions.currentPage += 1; paginationDefault(); loadTableData(); }
        function prevPage() { resetSelected(); tableOptions.currentPage -= 1; paginationDefault(); loadTableData(); }
        function currentPage() {
            paginationDefault();
            if (tableOptions.currentPage < 1)                            alert.Error('ERROR', "The Page requested doesn't exist");
            else if (tableOptions.currentPage > tableOptions.pageLength) alert.Error('ERROR', "The Page requested doesn't exist");
            else                                                         loadTableData();
        }
        function changePerPage(val) {
            resetSelected();
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
        function removeSingleFilter(column_name) {
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
        function clearAllFilter() {
            filters.value = false;
            tableOptions.filterParam.gid = '';
            tableOptions.filterParam.level = '';
            tableOptions.filterParam.lga_name = '';
            appState.selectedLgaKey = '';
            paginationDefault();
            loadTableData();
        }
        function refreshData() { paginationDefault(); loadTableData(); }
        function totalCheckedBox() {
            var total = tableData.value.filter(function (r) { return r.pick; }).length;
            var el = document.getElementById('total-selected');
            if (!el) return;
            if (total > 0) el.innerHTML = '<span class="badge badge-primary btn-icon"><span class="badge badge-success">' + total + '</span> Selected</span>';
            else el.replaceChildren();
        }
        function goToCreateIssue() {
            appState.pageState.page = 'create-inbound';
            appState.currentPeriodId = '';
            appState.currentLgaId = '';
            appState.facilityTitles = '';
            appState.selectedLgaKey = '';
            bus.emit('g-event-goto-page');
        }
        function getProductMaster() {
            overlay.show();
            axios.get(common.DataService + '?qid=gen011')
                .then(function (response) {
                    var data = ((response.data && response.data.data) || []).slice().sort(function (a, b) {
                        return a.product_code.localeCompare(b.product_code);
                    });
                    appState.productData = data;
                    overlay.hide();
                })
                .catch(function (error) {
                    overlay.hide();
                    alert.Error('ERROR', safeMessage(error));
                });
        }

        onMounted(function () {
            getProductMaster();
            loadTableData();
            bus.on('g-event-refresh-page', reloadTableListOnUpdate);
        });
        onBeforeUnmount(function () {
            bus.off('g-event-refresh-page', reloadTableListOnUpdate);
        });

        return {
            appState, tableData, roleListData, geoData, checkToggle, filterState, filters,
            tableOptions, geoLevelData,
            reloadTableListOnUpdate, loadTableData,
            selectAll, uncheckAll, selectToggle, checkedBg, toggleFilter,
            paginationDefault, resetSelected, nextPage, prevPage, currentPage,
            changePerPage, sort, applyFilter, removeSingleFilter, clearAllFilter,
            refreshData, totalCheckedBox, goToCreateIssue, getProductMaster,
            setSelectedLga,
            capitalize: fmtUtils.capitalize,
            displayDate: function (d, fullDate, withTime) { return displayDateLong(d, fullDate, withTime); },
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
                        <li class="breadcrumb-item active">Inbound</li>
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
                                    <th class="px-1">#</th>
                                    <th class="pl-1" @click="sort(4)">CMS Details</th>
                                    <th @click="sort(2)" class="pl-1">Product</th>
                                    <th @click="sort(9)" class="pl-1">Previous Qty</th>
                                    <th @click="sort(10)" class="pl-1">Current Qty</th>
                                    <th @click="sort(11)" class="pl-1">Total Qty</th>
                                    <th @click="sort(5)" class="pl-1">Batch</th>
                                    <th @click="sort(6)" class="pl-1">Expiry</th>
                                    <th @click="sort(7)" class="pl-1">Rate (&#8358;)</th>
                                    <th @click="sort(8)" class="pl-1">Unit</th>
                                    <th @click="sort(15)" class="pl-1">Created</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(item, rowIndex) in tableData" :key="rowIndex">
                                    <td class="px-1">{{ rowIndex + 1 }}</td>
                                    <td class="px-1">
                                        <div class="d-flex flex-column">
                                            <span class="fw-bolder">{{ item.cms_name }}</span>
                                            <span class="font-small-2 text-muted">{{ item.location_type }}</span>
                                        </div>
                                    </td>
                                    <td class="px-1">
                                        <div class="d-flex flex-column">
                                            <span class="fw-bolder">{{ item.product_name }}</span>
                                            <small class="font-small-2 text-muted">{{ item.product_code }}</small>
                                        </div>
                                    </td>
                                    <td class="px-1">
                                        <div class="d-flex flex-column">
                                            <small><span class="text-muted w-text">Primary:</span> <span class="fw-bolder">{{ convertStringNumberToFigures(item.previous_primary_qty) }}</span></small>
                                            <small><span class="text-muted w-text">Secondary:</span> <span class="fw-bolder">{{ convertStringNumberToFigures(item.previous_secondary_qty) }}</span></small>
                                        </div>
                                    </td>
                                    <td class="px-1">
                                        <div class="d-flex flex-column">
                                            <small><span class="text-muted w-text">Primary:</span> <span class="fw-bolder">{{ convertStringNumberToFigures(item.current_primary_qty) }}</span></small>
                                            <small><span class="text-muted w-text">Secondary:</span> <span class="fw-bolder">{{ convertStringNumberToFigures(item.current_secondary_qty) }}</span></small>
                                        </div>
                                    </td>
                                    <td class="px-1">
                                        <div class="d-flex flex-column">
                                            <small><span class="text-muted w-text">Primary:</span> <span class="fw-bolder">{{ convertStringNumberToFigures(item.total_primary_qty) }}</span></small>
                                            <small><span class="text-muted w-text">Secondary:</span> <span class="fw-bolder">{{ convertStringNumberToFigures(item.total_secondary_qty) }}</span></small>
                                        </div>
                                    </td>
                                    <td class="px-1">{{ item.batch }}</td>
                                    <td class="px-1">{{ displayDate(item.expiry, false, false) }}</td>
                                    <td class="px-1">{{ convertStringNumberToFigures(item.rate) }}</td>
                                    <td class="px-1">{{ item.unit }}</td>
                                    <td class="px-1">{{ displayDate(item.created, false, true) }}</td>
                                </tr>
                                <tr v-if="tableData.length == 0"><td class="text-center pt-2" colspan="11"><small>No User Added</small></td></tr>
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

        const facilityData = ref([]);
        const tempFacilityData = ref([]);
        const isUpdated = ref(false);
        const expiryRefs = ref({});
        const flatpickrInstances = {};

        function getSysDefaultDataSettings() {
            overlay.show();
            axios.get(common.DataService + '?qid=gen007')
                .then(function (response) {
                    if (response.data.data && response.data.data.length > 0) {
                        appState.sysDefaultData = response.data.data[0];
                        getAllLga(response.data.data[0].stateid);
                        appState.geoLevelForm.geoLevel = 'state';
                        appState.geoLevelForm.geoLevelId = response.data.data[0].stateid;
                        appState.defaultStateId = response.data.data[0].stateid;
                    }
                    overlay.hide();
                })
                .catch(function (error) {
                    overlay.hide();
                    alert.Error('ERROR', safeMessage(error));
                });
        }
        function getAllLga(stateid) {
            overlay.show();
            axios.post(common.DataService + '?qid=gen003', JSON.stringify(stateid))
                .then(function (response) {
                    appState.lgaData = (response.data && response.data.data) || [];
                    overlay.hide();
                })
                .catch(function (error) {
                    overlay.hide();
                    alert.Error('ERROR', safeMessage(error));
                });
        }
        async function getCmsLocationMaster() {
            overlay.show();
            try {
                var response = await axios.post(common.DataService + '?qid=gen012');
                appState.cmsLocationMaster = (response.data && response.data.data) || [];
            } catch (error) {
                alert.Error('ERROR', safeMessage(error));
            } finally {
                overlay.hide();
            }
        }
        function getAllPeriodLists() {
            overlay.show();
            axios.get(common.DataService + '?qid=1004')
                .then(function (response) {
                    appState.periodData = (response.data && response.data.data) || [];
                    overlay.hide();
                })
                .catch(function (error) {
                    overlay.hide();
                    alert.Error('ERROR', safeMessage(error));
                });
        }

        function submitInboundCreation() {
            if (!appState.inBoundData || appState.inBoundData.length === 0) {
                alert.Error('ERROR', 'Please add at least one inbound item.');
                return;
            }
            overlay.show();
            axios.post(common.DataService + '?qid=1130', JSON.stringify(appState.inBoundData))
                .then(function (response) {
                    overlay.hide();
                    if (response.data.result_code == '200') {
                        bus.emit('g-event-refresh-page');
                        alert.Success('SUCCESS', response.data.message);
                        goToInboundTable();
                    } else {
                        alert.Error('ERROR', response.data.message);
                    }
                })
                .catch(function (error) {
                    overlay.hide();
                    alert.Error('ERROR', safeMessage(error));
                });
        }
        function goToInboundTable() {
            appState.pageState.page = 'table';
            appState.inBoundData = [];
        }
        function resetForm() { facilityData.value = []; tempFacilityData.value = []; }
        function resetInboundCreation() {
            facilityData.value = JSON.parse(JSON.stringify(tempFacilityData.value));
        }
        function cancelInboundCreation() {
            if (isUpdated.value) {
                $.confirm({
                    title: 'WARNING!',
                    content: 'Are you sure you want to discard the changes made?',
                    buttons: {
                        discard: { text: 'Discard', btnClass: 'btn btn-danger mr-1', action: function () { goToInboundTable(); } },
                        cancel: function () {},
                    },
                });
            } else {
                goToInboundTable();
            }
        }

        function addInbound() {
            appState.inBoundData.push({
                product_code: '', product_name: '',
                location_type: '', location_id: '',
                batch: '', expiry_date: '',
                rate: '', unit: '',
                primary_qty: '', secondary_qty: '',
                userid: appState.userId,
            });
            nextTick(function () {
                var index = appState.inBoundData.length - 1;
                var inputEl = document.querySelector('input.expiry_date_' + index);
                if (inputEl && typeof flatpickr === 'function') {
                    flatpickrInstances[index] = flatpickr(inputEl, {
                        altInput: true, altFormat: 'F j, Y', dateFormat: 'Y-m-d',
                        onChange: function (selectedDates, dateStr) {
                            if (appState.inBoundData[index]) appState.inBoundData[index].expiry_date = dateStr;
                        },
                    });
                }
            });
        }
        function deleteItem(index) {
            try { if (flatpickrInstances[index] && flatpickrInstances[index].destroy) flatpickrInstances[index].destroy(); } catch (e) {}
            delete flatpickrInstances[index];
            appState.inBoundData.splice(index, 1);
        }

        watch(function () { return appState.inBoundData; }, function (newVal) {
            var packSize = 50;
            (newVal || []).forEach(function (item) {
                var secondaryQty = Number(item.secondary_qty);
                item.primary_qty = isNaN(secondaryQty) ? 0 : secondaryQty * packSize;
                if (item.location_id && !item.location_type) {
                    var cms = (appState.cmsLocationMaster || []).find(function (loc) { return loc.location_id === item.location_id; });
                    item.location_type = (cms && cms.location_type) || '';
                }
                if (item.product_code && !item.product_name) {
                    var prod = (appState.productData || []).find(function (p) { return p.product_code === item.product_code; });
                    item.product_name = (prod && prod.name) || '';
                }
            });
        }, { deep: true });

        onMounted(function () {
            getSysDefaultDataSettings();
            getAllPeriodLists();
            getCmsLocationMaster();
            bus.on('g-event-reset-form', resetForm);
            bus.on('g-event-goto-page', addInbound);
        });
        onBeforeUnmount(function () {
            bus.off('g-event-reset-form', resetForm);
            bus.off('g-event-goto-page', addInbound);
            Object.keys(flatpickrInstances).forEach(function (k) {
                try { if (flatpickrInstances[k] && flatpickrInstances[k].destroy) flatpickrInstances[k].destroy(); } catch (e) {}
            });
        });

        return {
            appState, facilityData, tempFacilityData, isUpdated,
            getSysDefaultDataSettings, getAllLga, getCmsLocationMaster, getAllPeriodLists,
            submitInboundCreation, goToInboundTable, resetForm,
            resetInboundCreation, cancelInboundCreation, addInbound, deleteItem,
            capitalize: fmtUtils.capitalize,
            displayDate: function (d, fullDate, withTime) { return displayDateLong(d, fullDate, withTime); },
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
                        <li class="breadcrumb-item"><a href="javascript:void(0)" @click="cancelInboundCreation()">Inbound</a></li>
                        <li class="breadcrumb-item active">Create Inbound</li>
                    </ol>
                </div>
            </div>

            <div class="col-12 mt-1">
                <div class="card">
                    <div class="card-header">
                        <button class="btn pl-0 pr-50 py-50 waves-effect" @click="cancelInboundCreation()"><i class="feather icon-chevron-left"></i> Back</button>
                    </div>

                    <form class="card-body inboundForm" id="inboundForm" @submit.prevent="submitInboundCreation()">
                        <div v-for="(item, index) in appState.inBoundData" :key="index" class="card border mb-3 shadow shadow-sm border-lighten-2">
                            <div class="card-body inbound-item">
                                <button type="reset" v-if="index != 0" class="close-btn shadow active waves-effect waves-float waves-light" @click="deleteItem(index)"><span>x</span></button>
                                <div class="row">
                                    <div class="col-3 col-md-3 col-sm-3 col-lg-3">
                                        <div class="form-group">
                                            <label>*Choose Product</label>
                                            <select class="form-control" v-model="item.product_code">
                                                <option value="">Choose Product</option>
                                                <option v-for="(g, i) in appState.productData" :key="g.product_code" :value="g.product_code">{{ g.name }}</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-3 col-md-3 col-sm-3 col-lg-3">
                                        <div class="form-group">
                                            <label>*Choose CMS Location</label>
                                            <select class="form-control" v-model="item.location_id">
                                                <option value="">Select CMS Location</option>
                                                <option v-for="(g, i) in appState.cmsLocationMaster" :key="g.location_id" :value="g.location_id">{{ g.cms_name }}</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-3 col-md-3 col-sm-3 col-lg-3">
                                        <div class="form-group">
                                            <label>*Batch No</label>
                                            <input type="text" v-model="item.batch" placeholder="Batch No" class="form-control" />
                                        </div>
                                    </div>
                                    <div class="col-3 col-md-3 col-sm-3 col-lg-3">
                                        <div class="form-group">
                                            <label>*Expiring Date</label>
                                            <input type="text" v-model="item.expiry_date" placeholder="Expiring Date" :class="'form-control date expiry_date_' + index" />
                                        </div>
                                    </div>
                                    <div class="col-3 col-md-3 col-sm-3 col-lg-3">
                                        <div class="form-group">
                                            <label>*Rate (&#8358;)</label>
                                            <input type="text" @paste="validatePaste($event)" v-model="item.rate" placeholder="Rate" class="form-control" />
                                        </div>
                                    </div>
                                    <div class="col-3 col-md-3 col-sm-3 col-lg-3">
                                        <div class="form-group">
                                            <label>*Unit (1X50)</label>
                                            <input type="text" placeholder="Unit" v-model="item.unit" class="form-control" />
                                        </div>
                                    </div>
                                    <div class="col-3 col-md-3 col-sm-3 col-lg-3">
                                        <div class="form-group">
                                            <label>*Quantity</label>
                                            <input type="text" @paste="validatePaste($event)" @keypress="numbersOnlyWithoutDot($event)" placeholder="Quantity" v-model="item.secondary_qty" class="form-control" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 px-0 justify-content-end text-right">
                            <button @click="addInbound()" type="button" class="btn btn-outline-primary btn-md btn-add-new waves-effect waves-float waves-light"><i class="feather icon-plus"></i> <span class="align-middle">Add Inbound</span></button>
                            <button v-if="appState.inBoundData.length > 0" type="submit" class="btn btn-primary ml-1 btn-md btn-add-new waves-effect waves-float waves-light"><i class="feather icon-plus"></i> <span class="align-middle">Submit Inbound</span></button>
                        </div>
                    </form>
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
            <div v-show="appState.pageState.page == 'create-inbound'"><page-create-issue/></div>
        </div>
    `,
    setup() { return { appState }; },
})
    .component('page-table', PageTable)
    .component('page-create-issue', PageCreateIssue)
    .mount('#app');
