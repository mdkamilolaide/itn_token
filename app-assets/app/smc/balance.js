/**
 * SMC / IC Balance — Vue 3 Composition API in place.
 * Two components — page-body and icc_list.
 *
 * qid=705 paginated balance ledger with multi-period + geo + login-id
 * filter. Per-row CDD details modal (qid=1123) with two tabs: SPAQ
 * Issued and SPAQ Returned (with full/partial/wasted progress bars).
 * Excel export count via qid=1126, dump via qid=803. Per-row Unlock
 * (qid=1128) for stranded device balances.
 */

const { ref, reactive, computed, onMounted, onBeforeUnmount } = Vue;
const { useApp, useFormat, bus, safeMessage } = window.utils;

const PageBody = {
    setup() {
        const page = ref('list');
        function gotoPageHandler(data) { page.value = data && data.page; }
        onMounted(function () { bus.on('g-event-goto-page', gotoPageHandler); });
        onBeforeUnmount(function () { bus.off('g-event-goto-page', gotoPageHandler); });
        return { page };
    },
    template: `
        <div>
            <div class="content-body">
                <div v-show="page == 'list'"><icc_list/></div>
            </div>
        </div>
    `,
};

const IccList = {
    setup() {
        const fmtUtils = useFormat();

        const url = ref(window.common && window.common.DataService);
        const permission = ref(
            (typeof getPermission === 'function')
                ? (getPermission(typeof per !== 'undefined' ? per : null, 'smc') || { permission_value: 0 })
                : { permission_value: 0 }
        );
        const tableData = ref([]);
        const selectedICCDetails = ref({});
        const iccIssuedDetails = ref([]);
        const iccReceivedDetails = ref([]);
        const geoData = ref([]);
        const periodData = ref([]);
        const checkIfFilterOn = ref(false);
        const filterState = ref(false);
        const filters = ref(false);
        const tableOptions = reactive({
            total: 1, pageLength: 1, perPage: 10, currentPage: 1,
            orderDir: 'desc', orderField: 0, limitStart: 0,
            isNext: false, isPrev: false,
            aLength: [10, 20, 50, 100, 150, 200],
            filterParam: {
                periodid: [], globalPeriod: '', visitTitle: '',
                geo_level: '', geo_level_id: '', geo_string: '', loginId: '',
            },
        });

        function joinWithCommaAnd(array, status) {
            if (!array || array.length === 0) return '';
            if (array.length === 1) return array[0];
            var copy = array.slice();
            var lastElement = copy.pop();
            return status ? copy.join(',') + ',' + lastElement : copy.join(', ') + ' and ' + lastElement;
        }

        async function loadTableData() {
            overlay.show();
            var fp = tableOptions.filterParam;
            fp.globalPeriod = joinWithCommaAnd(fp.periodid, true);
            var endpoint = common.TableService +
                '?qid=705&draw=' + tableOptions.currentPage +
                '&order_column=' + tableOptions.orderField +
                '&length=' + tableOptions.perPage +
                '&start=' + tableOptions.limitStart +
                '&order_dir=' + tableOptions.orderDir +
                '&pid=' + fp.globalPeriod + '&gid=' + fp.geo_level_id + '&glv=' + fp.geo_level + '&lid=' + fp.loginId;
            try {
                var response = await axios.get(endpoint);
                var d = response && response.data;
                tableData.value = Array.isArray(d && d.data) ? d.data : [];
                tableOptions.total = (d && d.recordsTotal) || 0;
                if (tableOptions.currentPage === 1) paginationDefault();
            } catch (error) {
                alert.Error('ERROR', safeMessage(error));
            } finally {
                overlay.hide();
            }
        }
        function toggleFilter() {
            if (!filterState.value && !checkIfFilterOn.value) filters.value = false;
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
            if (tableOptions.filterParam.geo_level != '') checkFill++;
            if (tableOptions.filterParam.geo_level_id != '') checkFill++;
            if (tableOptions.filterParam.loginId != '') checkFill++;
            if ((tableOptions.filterParam.periodid || []).length > 0) checkFill++;
            if (checkFill > 0) {
                toggleFilter();
                filters.value = checkIfFilterOn.value = true;
                paginationDefault();
                loadTableData();
            } else {
                alert.Error('ERROR', 'Invalid required data');
            }
        }
        function removeSingleFilter(column_name) {
            var fp = tableOptions.filterParam;
            if (Array.isArray(fp[column_name])) fp[column_name] = [];
            else fp[column_name] = '';
            if (['geo_level', 'geo_level_id', 'geo_string'].indexOf(column_name) !== -1) {
                fp.geo_level = '';
                fp.geo_level_id = '';
                try { $('.select2').val('').trigger('change'); } catch (e) {}
            }
            if (column_name === 'visitTitle') {
                fp.periodid = [];
                fp.visitTitle = '';
                fp.globalPeriod = '';
                try { $('.period').val('').trigger('change'); } catch (e) {}
            }
            var hasActive = Object.values(fp).some(function (v) {
                return Array.isArray(v) ? v.length > 0 : v !== '';
            });
            filters.value = checkIfFilterOn.value = hasActive;
            paginationDefault();
            loadTableData();
        }
        function clearAllFilter() {
            filters.value = false;
            Object.assign(tableOptions.filterParam, {
                geo_level: '', geo_level_id: '', loginId: '', geo_string: '',
                periodid: [], visitTitle: '', globalPeriod: '',
            });
            try { $('.select2').val('').trigger('change'); } catch (e) {}
            try { $('.period').val('').trigger('change'); } catch (e) {}
            paginationDefault();
            loadTableData();
        }
        function checkAndHideFilter(name) {
            return ['periodid', 'geo_level_id', 'geo_level', 'globalPeriod'].indexOf(name) === -1;
        }
        async function getIccDetails(cddid, id) {
            overlay.show();
            selectedICCDetails.value = tableData.value[id] || {};
            var periodIds = tableOptions.filterParam.globalPeriod;
            try {
                var response = await axios.get(url.value + '?qid=1123&cddid=' + cddid + '&pid=' + periodIds);
                if (response.data.result_code == 200) {
                    $('#iccDetailsModal').modal('show');
                    iccIssuedDetails.value = (response.data.data && response.data.data[0]) || [];
                    iccReceivedDetails.value = (response.data.data && response.data.data[1]) || [];
                } else {
                    iccIssuedDetails.value = [];
                    selectedICCDetails.value = {};
                    iccReceivedDetails.value = [];
                    alert.Error('ERROR', response.data.message);
                }
            } catch (error) {
                alert.Error('ERROR', safeMessage(error));
            } finally {
                overlay.hide();
            }
        }
        function hideGetIccDetails() {
            overlay.show();
            selectedICCDetails.value = {};
            iccIssuedDetails.value = [];
            iccReceivedDetails.value = [];
            $('#iccDetailsModal').modal('hide');
            overlay.hide();
        }
        async function unlockIcc(cddid, id) {
            var cddDetails = tableData.value[id] || {};
            var geo_level_id = cddDetails.geo_level_id;
            var drug = cddDetails.drug;
            var total_qty = cddDetails.downloaded;
            var cdd_lead_name = cddDetails.fullname;
            var issueId = cddDetails.issue_id;
            if (total_qty <= 0) {
                alert.Error('Zero Balance', "You don't have a balance to unlock.");
                return;
            }
            var userid = document.getElementById('v_g_id').value;
            var unlockUrl = url.value + '?qid=1128&issueId=' + issueId + '&cddid=' + cddid + '&drug=' + drug + '&dpid=' + geo_level_id + '&qty=' + total_qty + '&user_id=' + userid;
            $.confirm({
                title: 'WARNING!',
                content: 'Are you sure you want to unlock <b>' + total_qty + '</b> ' + drug + ' from ' + cdd_lead_name + ' device?',
                buttons: {
                    delete: {
                        text: 'Unlock', btnClass: 'btn btn-danger mr-1 text-capitalize',
                        action: function () {
                            axios.post(unlockUrl)
                                .then(function (response) {
                                    if (response.data.result_code == '200') {
                                        refreshData();
                                        alert.Success('Success', '<b>' + total_qty + '</b> ' + drug + ' Unlocked');
                                    } else {
                                        alert.Error('Error', response.data.message);
                                    }
                                })
                                .catch(function (error) {
                                    alert.Error('ERROR', safeMessage(error));
                                })
                                .finally(function () { overlay.hide(); });
                        },
                    },
                    cancel: function () { overlay.hide(); },
                },
            });
        }
        function checkIfEmpty(data) { return data === null || data === '' ? 'Nil' : data; }
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
        function getAllPeriodLists() {
            overlay.show();
            axios.get(common.DataService + '?qid=1004')
                .then(function (response) {
                    periodData.value = (response.data && response.data.data) || [];
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
        function setPeriodTitle(event) {
            var selected = Array.isArray(event) ? event : [];
            tableOptions.filterParam.periodid = [];
            var titles = [];
            selected.forEach(function (id) {
                tableOptions.filterParam.periodid.push(id);
                var period = (periodData.value || []).find(function (p) { return p.periodid == id; });
                if (period) titles.push(period.title);
            });
            tableOptions.filterParam.visitTitle = joinWithCommaAnd(titles);
        }
        function splitWordAndCapitalize(str) {
            var words = String(str || '').split(/(?=[A-Z])|_| /);
            return words.map(function (w) { return w.charAt(0).toUpperCase() + w.slice(1).toLowerCase(); }).join(' ');
        }
        function displayDayMonthYearTime(d) {
            if (!d) return '';
            var date = new Date(d);
            return date.toLocaleString('en-us', {
                year: 'numeric', month: 'long', day: 'numeric',
                hour12: true, hour: '2-digit', minute: '2-digit',
            });
        }
        function convertStringNumberToFigures(d) {
            var data = d ? parseInt(d) : 0;
            return data ? data.toLocaleString() : 0;
        }

        const getTopIccIssued = computed(function () {
            return (iccIssuedDetails.value || []).reduce(function (acc, item) {
                if (item.issue_drug === 'SPAQ 1') acc.sumSpaq1Qty += parseInt(item.drug_qty || 0);
                else if (item.issue_drug === 'SPAQ 2') acc.sumSpaq2Qty += parseInt(item.drug_qty || 0);
                return acc;
            }, { sumSpaq1Qty: 0, sumSpaq2Qty: 0 });
        });
        const getTopIccReturned = computed(function () {
            return (iccReceivedDetails.value || []).reduce(function (acc, item) {
                var f = parseInt(item.full_dose_qty || 0);
                var p = parseInt(item.partial_qty || 0);
                var w = parseInt(item.wasted_qty || 0);
                if (item.received_drug === 'SPAQ 1') {
                    acc.sumSpaq1FullReturn += f;
                    acc.sumSpaq1partialReturn += p;
                    acc.sumSpaq1Wasted += w;
                    acc.sumSpaq1Returned += f + p + w;
                } else if (item.received_drug === 'SPAQ 2') {
                    acc.sumSpaq2FullReturn += f;
                    acc.sumSpaq2partialReturn += p;
                    acc.sumSpaq2Wasted += w;
                    acc.sumSpaq2Returned += f + p + w;
                }
                return acc;
            }, {
                sumSpaq1FullReturn: 0, sumSpaq1partialReturn: 0, sumSpaq1Wasted: 0, sumSpaq1Returned: 0,
                sumSpaq2FullReturn: 0, sumSpaq2partialReturn: 0, sumSpaq2Wasted: 0, sumSpaq2Returned: 0,
            });
        });

        async function exportIcc() {
            var fp = tableOptions.filterParam;
            var periodIds = joinWithCommaAnd(fp.periodid, true);
            fp.globalPeriod = periodIds;
            var qs = '&draw=' + tableOptions.currentPage +
                '&order_column=' + tableOptions.orderField +
                '&length=' + tableOptions.perPage +
                '&start=' + tableOptions.limitStart +
                '&order_dir=' + tableOptions.orderDir +
                '&pid=' + periodIds + '&gid=' + fp.geo_level_id + '&glv=' + fp.geo_level;
            var veriUrl = 'qid=1126' + qs;
            var dlString = 'qid=803' + qs;
            var formattedDate = new Date().toLocaleString('en-GB', {
                day: '2-digit', month: '2-digit', year: 'numeric',
                hour: '2-digit', minute: '2-digit',
            }).replace(/[\s,\/:]/g, '_');
            var filename = fp.geo_level + '_' + fp.globalPeriod + '_ICC_Export_' + formattedDate;
            overlay.show();
            try {
                var countResponse = await $.ajax({
                    url: common.DataService, type: 'POST', data: veriUrl, dataType: 'json',
                });
                var count = parseInt(countResponse.total, 10);
                var downloadMax = (window.common && window.common.ExportDownloadLimit) || 25000;
                if (count > downloadMax) {
                    alert.Error('Download Error', 'Unable to download data because it has exceeded the download limit of ' + downloadMax);
                } else if (count === 0) {
                    alert.Error('Download Error', 'No data found');
                } else {
                    alert.Info('DOWNLOADING...', 'Downloading ' + count + ' record(s)');
                    var dl = await $.ajax({ url: common.ExportService, type: 'POST', data: dlString });
                    var exportData = JSON.parse(dl);
                    if (window.Jhxlsx && typeof window.Jhxlsx.export === 'function') {
                        window.Jhxlsx.export(exportData, { fileName: filename });
                    }
                }
            } catch (error) {
                console.error('Error during export:', error);
                alert.Error('Export Error', 'An error occurred while exporting data.');
            } finally {
                overlay.hide();
            }
        }

        onMounted(function () {
            getGeoLocation();
            getAllPeriodLists();
            loadTableData();
            try {
                $('.select2').each(function () {
                    var $this = $(this);
                    $this.wrap('<div class="position-relative"></div>');
                    $this.select2({
                        dropdownAutoWidth: true, width: '100%',
                        dropdownParent: $this.parent(),
                    }).on('change', function () { setLocation(this.value); });
                });
                $('.period').each(function () {
                    var $this = $(this);
                    $this.wrap('<div class="position-relative"></div>');
                    $this.select2({
                        multiple: true, dropdownAutoWidth: true, width: '100%',
                        dropdownParent: $this.parent(),
                        placeholder: 'Select Visits',
                    }).on('change', function () { setPeriodTitle($(this).val()); });
                });
                $('.select2-selection__arrow').html('<i class="feather icon-chevron-down"></i>');
            } catch (e) {}
        });

        return {
            url, permission, tableData, selectedICCDetails,
            iccIssuedDetails, iccReceivedDetails, geoData, periodData,
            checkIfFilterOn, filterState, filters, tableOptions,
            getTopIccIssued, getTopIccReturned,
            loadTableData, toggleFilter, paginationDefault,
            nextPage, prevPage, currentPage, changePerPage, sort,
            applyFilter, removeSingleFilter, clearAllFilter, checkAndHideFilter,
            getIccDetails, hideGetIccDetails, unlockIcc, checkIfEmpty,
            refreshData, getGeoLocation, getAllPeriodLists, setLocation,
            setPeriodTitle, splitWordAndCapitalize, displayDayMonthYearTime,
            convertStringNumberToFigures, exportIcc,
            capitalize: fmtUtils.capitalize,
            displayDate: fmtUtils.displayDate,
            progressBarWidth: fmtUtils.progressBarWidth,
            progressBarStatus: fmtUtils.progressBarStatus,
        };
    },
    template: `
        <div class="row" id="basic-table">
            <div class="col-md-8 col-sm-12 col-12 mb-0">
                <h2 class="content-header-title header-txt float-left mb-0">SMC</h2>
                <div class="breadcrumb-wrapper">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../smc">Home</a></li>
                        <li class="breadcrumb-item active">IC Balance</li>
                    </ol>
                </div>
            </div>

            <div class="col-md-4 col-sm-12 col-12 text-md-right text-right d-md-block">
                <div class="btn-group">
                    <button type="button" class="btn btn-outline-primary round searchBtn" @click="refreshData()" data-toggle="tooltip" data-placement="top" title="Refresh"><i class="feather icon-refresh-cw"></i></button>
                    <button type="button" class="btn btn-outline-primary round searchBtn" @click="toggleFilter()" data-toggle="tooltip" data-placement="top" title="Filter"><i class="feather" :class="filterState ? 'icon-x' : 'icon-filter'"></i></button>
                    <button v-if="permission.permission_value >= 1" type="button" class="btn btn-outline-primary round" data-toggle="tooltip" data-placement="top" title="Download" @click="exportIcc()"><i class="feather icon-download"></i></button>
                </div>
            </div>

            <div class="col-12" v-if="filters">
                <div class="col-12 filter-bar">
                    <template v-for="(filterParam, i) in tableOptions.filterParam" :key="i">
                        <span class="badge badge-dark filter-box" v-if="(Array.isArray(filterParam) ? filterParam.length : String(filterParam).length) > 0 && checkAndHideFilter(i)" @click="removeSingleFilter(i)">{{ splitWordAndCapitalize(i) }}: {{ filterParam }} <i class="feather icon-x"></i></span>
                    </template>
                    <a href="#" class="float-right clear-filter" @click="clearAllFilter()">Clear</a>
                </div>
            </div>

            <div class="col-12 mt-1">
                <div class="card custom-select-down">
                    <div class="card-body py-1" v-show="filterState">
                        <form id="filterForm">
                            <div class="row">
                                <div class="col-12 col-sm-6 col-md-6 col-lg-3"><div class="form-group"><label>Login ID</label><input type="text" v-model="tableOptions.filterParam.loginId" class="form-control" placeholder="Login ID" /></div></div>
                                <div class="col-12 col-sm-6 col-md-6 col-lg-3">
                                    <div class="form-group">
                                        <label>Visit</label>
                                        <select @change="setPeriodTitle($event && $event.target ? Array.from($event.target.selectedOptions).map(o => o.value) : [])" v-model="tableOptions.filterParam.periodid" multiple class="form-control period">
                                            <option v-for="(g, i) in periodData" :key="g.periodid" :value="g.periodid">{{ g.title }}</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-12 col-sm-9 col-md-9 col-lg-4">
                                    <div class="form-group">
                                        <label>Geo Location</label>
                                        <select class="select2 form-control" @change="setLocation()">
                                            <option value="">Select Geo Location</option>
                                            <option v-for="(g, i) in geoData" :key="i" :value="i">{{ g.geo_string }}</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-12 col-sm-3 col-md-3 col-lg-2"><div class="form-group mt-2 text-right"><button type="button" class="btn btn-md btn-primary" @click="applyFilter()">Apply Filters</button></div></div>
                            </div>
                        </form>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th @click="sort(0)" class="pl-1 pr-2">Issue ID</th>
                                    <th @click="sort(1)" class="pl-1 pr-2">CDD Lead Details</th>
                                    <th @click="sort(4)" class="pl-1 pr-2">Drug</th>
                                    <th @click="sort(14)" class="pl-1 pr-2">Period</th>
                                    <th @click="sort(5)" class="pl-1 pr-2">Issued</th>
                                    <th @click="sort(6)" class="pl-1 pr-2">Pending</th>
                                    <th @click="sort(7)" class="pl-1 pr-2">Confirmed</th>
                                    <th @click="sort(8)" class="pl-1 pr-2">Accepted</th>
                                    <th @click="sort(9)" class="pl-1 pr-2">Returned</th>
                                    <th @click="sort(10)" class="pl-1 pr-2">Reconciled</th>
                                    <th width="40px" class="pl-0 pr-0" v-if="permission.permission_value >= 2"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(g, i) in tableData" :key="g.issue_id || i">
                                    <td class="pl-1 pr-2">{{ g.issue_id }}</td>
                                    <td class="pl-1 pr-1">
                                        <div class="d-flex flex-column">
                                            <small><span>{{ checkIfEmpty(g.fullname) }}</span></small>
                                            <small><span class="badge badge-light-primary">{{ g.loginid }}</span></small>
                                        </div>
                                    </td>
                                    <td class="pl-1 pr-1">{{ g.drug }}</td>
                                    <td class="pl-1 pr-1">{{ g.period }}</td>
                                    <td class="pl-1 pr-1">{{ convertStringNumberToFigures(g.issued) }}</td>
                                    <td class="pl-1 pr-1">{{ convertStringNumberToFigures(g.pending) }}</td>
                                    <td class="pl-1 pr-1">{{ convertStringNumberToFigures(g.confirmed) }}</td>
                                    <td class="pl-1 pr-1">{{ convertStringNumberToFigures(g.accepted) }}</td>
                                    <td class="pl-1 pr-1">{{ convertStringNumberToFigures(g.returned) }}</td>
                                    <td class="pl-1 pr-1">{{ convertStringNumberToFigures(g.reconciled) }}</td>
                                    <td class="pl-0 pr-1" v-if="permission.permission_value >= 2">
                                        <button class="btn btn-primary btn-sm px-50" @click="unlockIcc(g.cdd_lead_id, i)">Unlock</button>
                                    </td>
                                </tr>
                                <tr v-if="tableData.length == 0"><td class="text-center pt-2" :colspan="permission.permission_value < 2 ? 10 : 11"><small>No Data</small></td></tr>
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

            <div class="modal fade modal-primary" id="iccDetailsModal" tabindex="-1" role="dialog" aria-hidden="true">
                <div class="modal-dialog modal-scrollable modal-xl">
                    <form class="modal-content pt-0" @submit.stop.prevent="">
                        <div class="modal-header mb-0">
                            <h5 class="modal-title font-weight-bolder text-dark">{{ selectedICCDetails.issuer_name ? selectedICCDetails.issuer_name : selectedICCDetails.issuer_loginid }}, ICC Details<br><span class="badge badge-light-success">{{ selectedICCDetails.issuer_loginid }}</span></h5>
                            <button type="reset" class="close" @click="hideGetIccDetails()" data-dismiss="modal">×</button>
                        </div>
                        <div class="modal-body flex-grow-1 vertical-wizard">
                            <ul class="nav nav-tabs" role="tablist">
                                <li class="nav-item"><a class="nav-link active" id="spaq-issued-tab" data-toggle="tab" href="#spaq-issued" role="tab">SPAQ Issued</a></li>
                                <li class="nav-item"><a class="nav-link" id="spaq-returned-tab" data-toggle="tab" href="#spaq-returned" role="tab">SPAQ Returned</a></li>
                            </ul>
                            <div class="tab-content">
                                <div class="tab-pane fade pt-75 show active" id="spaq-issued" role="tabpanel">
                                    <div class="card mb-0 btmlr">
                                        <div class="card-widget-separator-wrapper">
                                            <div class="card-body pb-75 pt-75 card-widget-separator">
                                                <div class="row gy-4 gy-sm-1">
                                                    <div class="col-sm-6 col-lg-6">
                                                        <div class="d-flex justify-content-between align-items-start card-widget-1 border-end pb-sm-0">
                                                            <div><h6 class="mb-50 small">Total SPAQ 1 Issued</h6><h4 class="mb-0">{{ convertStringNumberToFigures(getTopIccIssued.sumSpaq1Qty) }}</h4></div>
                                                            <span class="avatar1 me-sm-4"><span class="avatar-initial bg-label-blue rounded"><i class="ti-md ti ti-pill text-body"></i></span></span>
                                                        </div>
                                                    </div>
                                                    <div class="col-sm-6 col-lg-6">
                                                        <div class="d-flex justify-content-between align-items-start">
                                                            <div><h6 class="mb-50 small">Total SPAQ 2 Issued</h6><h4 class="mb-0">{{ convertStringNumberToFigures(getTopIccIssued.sumSpaq2Qty) }}</h4></div>
                                                            <span class="avatar1 me-sm-4"><span class="avatar-initial bg-label-dark rounded"><i class="ti-md ti ti-pill-off text-body"></i></span></span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="info-container table-responsive pt-0">
                                        <table class="table">
                                            <thead>
                                                <th>Issuer Details</th>
                                                <th>Visit</th>
                                                <th>Issued Drug</th>
                                                <th>Quantity</th>
                                                <th>Issue Date</th>
                                                <th>Created Date</th>
                                            </thead>
                                            <tbody>
                                                <tr v-for="(g, i) in iccIssuedDetails" :key="i">
                                                    <td>
                                                        <div class="d-flex flex-column">
                                                            <span class="fw-bolder">{{ capitalize(g.issuer_name) }}</span>
                                                            <small><span class="badge badge-light-primary">{{ g.issuer_loginid }}</span></small>
                                                        </div>
                                                    </td>
                                                    <td>{{ checkIfEmpty(g.period) }}</td>
                                                    <td>{{ checkIfEmpty(g.issue_drug) }}</td>
                                                    <td>{{ convertStringNumberToFigures(g.drug_qty) }}</td>
                                                    <td>{{ displayDayMonthYearTime(g.issue_date) }}</td>
                                                    <td>{{ displayDayMonthYearTime(g.created) }}</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <div class="tab-pane fade" id="spaq-returned" role="tabpanel">
                                    <div class="card mb-0 btmlr drug-card">
                                        <div class="card-header py-50"><h5 class="card-title font-small-2 mb-25">SPAQ 1</h5></div>
                                        <div class="card-body pb-50">
                                            <div class="row gy-3">
                                                <div class="col-sm-3 col-6"><h5 class="mb-0">{{ convertStringNumberToFigures(getTopIccReturned.sumSpaq1Returned) }}</h5><small>Total Returned</small></div>
                                                <div class="col-sm-3 col-6"><h5 class="mb-0">{{ convertStringNumberToFigures(getTopIccReturned.sumSpaq1FullReturn) }}</h5><div class="progress" style="height: 6px;"><div class="progress-bar" :class="progressBarStatus(getTopIccReturned.sumSpaq1Returned, getTopIccReturned.sumSpaq1FullReturn)" :style="{ width: progressBarWidth(getTopIccReturned.sumSpaq1Returned, getTopIccReturned.sumSpaq1FullReturn) }"></div></div><small>Full Return</small></div>
                                                <div class="col-sm-3 col-6"><h5 class="mb-0">{{ convertStringNumberToFigures(getTopIccReturned.sumSpaq1Wasted) }}</h5><small>Wasted</small></div>
                                                <div class="col-sm-3 col-6"><h5 class="mb-0">{{ convertStringNumberToFigures(getTopIccReturned.sumSpaq1partialReturn) }}</h5><small>Partial Return</small></div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="card mb-0 btmlr ttlr">
                                        <div class="card-header py-50"><h5 class="card-title font-small-2 mb-25">SPAQ 2</h5></div>
                                        <div class="card-body pb-50">
                                            <div class="row gy-3">
                                                <div class="col-sm-3 col-6"><h5 class="mb-0">{{ convertStringNumberToFigures(getTopIccReturned.sumSpaq2Returned) }}</h5><small>Total Returned</small></div>
                                                <div class="col-sm-3 col-6"><h5 class="mb-0">{{ convertStringNumberToFigures(getTopIccReturned.sumSpaq2FullReturn) }}</h5><div class="progress" style="height: 6px;"><div class="progress-bar" :class="progressBarStatus(getTopIccReturned.sumSpaq2Returned, getTopIccReturned.sumSpaq2FullReturn)" :style="{ width: progressBarWidth(getTopIccReturned.sumSpaq2Returned, getTopIccReturned.sumSpaq2FullReturn) }"></div></div><small>Full Return</small></div>
                                                <div class="col-sm-3 col-6"><h5 class="mb-0">{{ convertStringNumberToFigures(getTopIccReturned.sumSpaq2Wasted) }}</h5><small>Wasted</small></div>
                                                <div class="col-sm-3 col-6"><h5 class="mb-0">{{ convertStringNumberToFigures(getTopIccReturned.sumSpaq2partialReturn) }}</h5><small>Partial Return</small></div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="info-container table-responsive pt-25">
                                        <table class="table">
                                            <thead>
                                                <th>Receiver Details</th>
                                                <th>Visit</th>
                                                <th>Received Drug</th>
                                                <th>Full Dose Qty</th>
                                                <th>Partial Qty</th>
                                                <th>Wasted Qty</th>
                                                <th>Received Date</th>
                                                <th>Created Date</th>
                                            </thead>
                                            <tbody>
                                                <tr v-for="(g, i) in iccReceivedDetails" :key="i">
                                                    <td>
                                                        <div class="d-flex flex-column">
                                                            <span class="fw-bolder">{{ capitalize(g.receiver_name) }}</span>
                                                            <small><span class="badge badge-light-primary">{{ g.receiver_loginid }}</span></small>
                                                        </div>
                                                    </td>
                                                    <td>{{ checkIfEmpty(g.period) }}</td>
                                                    <td>{{ checkIfEmpty(g.received_drug) }}</td>
                                                    <td>{{ convertStringNumberToFigures(g.full_dose_qty) }}</td>
                                                    <td>{{ convertStringNumberToFigures(g.partial_qty) }}</td>
                                                    <td>{{ convertStringNumberToFigures(g.wasted_qty) }}</td>
                                                    <td>{{ displayDayMonthYearTime(g.received_date) }}</td>
                                                    <td>{{ displayDayMonthYearTime(g.created) }}</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="reset" class="btn btn-secondary" data-dismiss="modal" @click="hideGetIccDetails()">Close</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    `,
};

useApp({ template: `<div><page-body/></div>` })
    .component('page-body', PageBody)
    .component('icc_list', IccList)
    .mount('#app');
