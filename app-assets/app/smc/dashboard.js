/**
 * SMC Dashboard — Vue 3 Composition API conversion.
 *
 * Four side-by-side report panels mounted by PageBody:
 *   - ChildList     (qid 1111/1112/1113) — registration roll-up + chart
 *   - DrugAdmin     (qid 1114/1115/1116) — SPAQ administration + chart
 *   - ReferralAdmin (qid 1117/1118/1119) — referrals + chart
 *   - IccAdmin      (qid 1120/1121/1122) — inventory control table only
 *
 * ChildList owns the filter UI (period multi-select + flatpickr date
 * range) and broadcasts changes over the local in-module bus. The other
 * three panels listen and re-fetch when filters apply/refresh/clear or a
 * single chip is removed. Each panel drills State → LGA → Ward → DP via
 * an in-page breadcrumb, swapping the qid as `reportLevel` advances.
 *
 * Period dropdown loads from qid=1004.
 */

const { ref, reactive, computed, onMounted, onBeforeUnmount } = Vue;
const { useApp, useFormat, bus, safeMessage } = window.utils;

// In-module event names — distinct from the global g-event-* bus so
// other modules' listeners don't fire on dashboard filter changes.
const EVT_APPLY = 'fire-event-apply-filter';
const EVT_REFRESH = 'fire-event-refresh';
const EVT_CLEAR = 'fire-event-clear-filter';
const EVT_REMOVE_SINGLE = 'fire-event-remove-single-filter';

const joinWithCommaAnd = (array, status) => {
    if (!array || array.length === 0) return '';
    if (array.length === 1) return array[0];
    var copy = array.slice();
    var lastElement = copy.pop();
    return status ? copy.join(',') + ',' + lastElement : copy.join(', ') + ' and ' + lastElement;
}

const cleanUrl = (url) => {
    var urlObj = new URL(url);
    var params = urlObj.searchParams;
    var allowed = ['qid', 'filterId'];
    var keysToRemove = [];
    var keys = Array.from(params.keys());
    keys.forEach(k => { if (allowed.indexOf(k) === -1) keysToRemove.push(k); });
    keysToRemove.forEach(k => { params.delete(k); });
    return urlObj.toString();
}

const splitWordAndCapitalize = (str) => {
    var words = String(str || '').split(/(?=[A-Z])|_| /);
    return words.map(w => w.charAt(0).toUpperCase() + w.slice(1).toLowerCase()).join(' ');
}

const buildFilterQs = (periodIds, reportDate) => {
    var rd = reportDate || '';
    var parts = rd.split(' to ');
    var sd = parts[0] || '';
    var ed = parts[1] || '';
    return '&pid=' + periodIds + '&sdate=' + sd + '&edate=' + ed;
}

const splitDateRange = (reportDate) => {
    var rd = reportDate || '';
    var parts = rd.split(' to ');
    return { start: parts[0] || '', end: parts[1] || '' };
}

// Common chart shell: each panel overrides title/colors/series and reuses
// the same axis + label formatting.
const makeBarChartOptions = (opts) => {
    return {
        chart: { type: 'bar', stacked: !!opts.stacked },
        colors: opts.colors,
        xaxis: { categories: opts.categories || [] },
        legend: {
            position: 'bottom',
            formatter: (seriesName) => { return capitalizeOne(seriesName); },
            fontFamily: 'Arial, sans-serif',
            fontSize: '12px',
        },
        title: { text: opts.title, align: 'center' },
        yaxis: {
            labels: {
                formatter: (val) => { return parseInt(val).toLocaleString(); },
            },
        },
        plotOptions: {
            bar: { horizontal: false, dataLabels: { position: 'top' } },
        },
        dataLabels: {
            enabled: true,
            formatter: (val) => { return parseInt(val).toLocaleString(); },
            offsetY: -18,
            style: { fontSize: '12px', colors: ['#000'] },
        },
        noData: {
            text: 'No data available, kindly refresh',
            align: 'center',
            verticalAlign: 'middle',
            offsetX: 0,
            offsetY: 0,
            style: { color: '#333', fontSize: '14px' },
        },
    };
}

const capitalizeOne = (word) => {
    if (!word) return word;
    var lower = String(word).toLowerCase();
    return lower.charAt(0).toUpperCase() + lower.slice(1);
}

// PageBody — simple shell. PerfectScrollbar setup runs after children
// mount so .scrollBox elements inside each panel are picked up.
const PageBody = {
    setup() {
        onMounted(() => {
            try {
                var els = document.querySelectorAll('.scrollBox');
                if (els && window.PerfectScrollbar) {
                    els.forEach(el => { new window.PerfectScrollbar(el); });
                }
            } catch (e) { /* swallow */ }
        });
        return {};
    },
    template: `
        <div>
            <div class="content-body">
                <div>
                    <child_list/>
                    <drug_admin/>
                    <referral_admin/>
                    <icc_admin/>
                </div>
            </div>
        </div>
    `,
};

// ChildList — owner of the filter UI + the first (registration) panel.
const ChildList = {
    setup() {
        const fmtUtils = useFormat();

        const url = ref(window.common && window.common.DataService);
        const filterUrl = ref('');
        const periodData = ref([]);
        const reportLevel = ref(1);
        const filterId = ref('');
        const lgaId = ref('');
        const lgaName = ref('');
        const wardId = ref('');
        const wardName = ref('');
        const dpId = ref('');
        const dpName = ref('');
        const checkIfFilterOn = ref(false);
        const filterState = ref(false);
        const filters = ref(false);

        const tableOptions = reactive({
            filterParam: { periodid: [], visitTitle: '', reportDate: '' },
        });
        const statData = reactive({ tableData: [], chartData: [] });
        const series = ref([]);
        const chartOptions = ref({ xaxis: { title: { text: '' } } });

        const loadTableData = (fId, title) => {
            var fp = tableOptions.filterParam;
            var periodIds = joinWithCommaAnd(fp.periodid.slice(), true);
            var range = splitDateRange(fp.reportDate);

            var queryUrl = url.value;
            switch (reportLevel.value) {
                case 1:
                    filterId.value = 0;
                    queryUrl += '?qid=1111';
                    break;
                case 2:
                    lgaId.value = fId;
                    lgaName.value = title || lgaName.value;
                    queryUrl += '?qid=1112&filterId=' + lgaId.value;
                    break;
                case 3:
                    wardId.value = fId;
                    wardName.value = title || wardName.value;
                    queryUrl += '?qid=1113&filterId=' + wardId.value;
                    break;
                default:
                    return;
            }
            if (periodIds) queryUrl += '&pid=' + periodIds;
            if (range.start) queryUrl += '&sdate=' + range.start;
            if (range.end) queryUrl += '&edate=' + range.end;

            loadDashboardData(queryUrl);
        }

        const loadDashboardData = async (u) => {
            try {
                overlay.show();
                filterUrl.value = u;
                var response = await axios.get(u);
                if (response.data.result_code == 200) {
                    statData.tableData = response.data.data.table;
                    statData.chartData = response.data.data.chart;
                    statData.chartData.xAxisLabel = 'Days';
                    reportLevel.value = response.data.level;
                    plotChart();
                } else {
                    statData.tableData = [];
                    alert.Error('ERROR', response.data.message);
                }
            } catch (error) {
                alert.Error('ERROR', safeMessage(error));
            } finally {
                overlay.hide();
            }
        }

        const toggleFilter = () => {
            if (!filterState.value && !checkIfFilterOn.value) filters.value = false;
            return (filterState.value = !filterState.value);
        }
        const fireFilterEvent = () => {
            bus.emit(EVT_APPLY, [
                tableOptions.filterParam.periodid.slice(),
                tableOptions.filterParam.visitTitle,
                tableOptions.filterParam.reportDate,
            ]);
        }
        const fireRefreshEvent = () => { bus.emit(EVT_REFRESH); };
        const fireClearFilter = () => { bus.emit(EVT_CLEAR); };
        const fireRemoveSingleFilter = (name) => { bus.emit(EVT_REMOVE_SINGLE, name); };

        const applyFilter = () => {
            var checkFill = 0;
            if (tableOptions.filterParam.periodid.length > 0) checkFill++;
            if ((tableOptions.filterParam.reportDate || '').length > 0) checkFill++;

            if (checkFill > 0) {
                toggleFilter();
                filters.value = checkIfFilterOn.value = true;
                fireFilterEvent();
                var periodIds = joinWithCommaAnd(tableOptions.filterParam.periodid.slice(), true);
                var u = cleanUrl(filterUrl.value) + buildFilterQs(periodIds, tableOptions.filterParam.reportDate);
                loadDashboardData(u);
            } else {
                clearAllFilter();
            }
        }
        const loadNewData = () => {
            var checkFill = 0;
            if (tableOptions.filterParam.periodid.length > 0) checkFill++;
            if ((tableOptions.filterParam.reportDate || '').length > 0) checkFill++;
            var periodIds = joinWithCommaAnd(tableOptions.filterParam.periodid.slice(), true);
            var u = cleanUrl(filterUrl.value) + buildFilterQs(periodIds, tableOptions.filterParam.reportDate);
            loadDashboardData(u);
            if (checkFill > 0) {
                toggleFilter();
                filterState.value = checkIfFilterOn.value = false;
            } else {
                filters.value = checkIfFilterOn.value = false;
            }
        }
        const removeSingleFilter = (column_name) => {
            var fp = tableOptions.filterParam;
            if (Array.isArray(fp[column_name])) fp[column_name] = [];
            else fp[column_name] = '';
            if (column_name === 'visitTitle') {
                fp.periodid = [];
                fp.visitTitle = '';
                try { $('.period').val('').trigger('change'); } catch (e) {}
            }
            if (column_name === 'reportDate') clearFlatpickr('date');
            var hasActive = Object.values(fp).some(v => Array.isArray(v) ? v.length > 0 : v !== '');
            filters.value = checkIfFilterOn.value = hasActive;
            fireRemoveSingleFilter(column_name);
            loadNewData();
        }
        const clearAllFilter = () => {
            filters.value = false;
            Object.assign(tableOptions.filterParam, { periodid: [], visitTitle: '', reportDate: '' });
            try { $('.period').val('').trigger('change'); } catch (e) {}
            clearFlatpickr('date');
            fireClearFilter();
            loadDashboardData(cleanUrl(filterUrl.value));
        }
        const clearFlatpickr = (dateClass) => {
            try {
                var inst = $('.' + dateClass)[0] && $('.' + dateClass)[0]._flatpickr;
                if (inst) inst.clear();
            } catch (e) {}
        }
        const checkAndHideFilter = (name) => {
            return name !== 'periodid';
        }
        const refreshData = () => {
            getAllPeriodLists();
            fireRefreshEvent();
            loadDashboardData(filterUrl.value);
        }
        const controlBreadCrum = (fId, level, title) => {
            reportLevel.value = level;
            loadTableData(fId, title);
        }
        const plotChart = () => {
            chartOptions.value = makeBarChartOptions({
                colors: ['#7367f0', '#b9b3f7'],
                title: 'Child Registration',
                stacked: true,
                categories: (statData.chartData && statData.chartData[1]) || [],
            });
            series.value = (statData.chartData && statData.chartData[0]) || [];
        }
        const getAllPeriodLists = () => {
            overlay.show();
            axios.get((window.common && window.common.DataService) + '?qid=1004')
                .then(response => {
                    periodData.value = (response.data && response.data.data) || [];
                    overlay.hide();
                })
                .catch(error => {
                    overlay.hide();
                    alert.Error('ERROR', safeMessage(error));
                });
        }
        const setPeriodTitle = (event) => {
            var selected = Array.isArray(event) ? event : [];
            tableOptions.filterParam.periodid = [];
            var titles = [];
            selected.forEach(id => {
                tableOptions.filterParam.periodid.push(id);
                var period = (periodData.value || []).find(p => p.periodid == id);
                if (period) titles.push(period.title);
            });
            tableOptions.filterParam.visitTitle = joinWithCommaAnd(titles);
        }

        const getTopChildStat = computed(() => statData.tableData.reduce((acc, curr) => {
            acc.male += parseInt(curr.male, 10) || 0;
            acc.female += parseInt(curr.female, 10) || 0;
            acc.total += parseInt(curr.total, 10) || 0;
            return acc;
        }, { male: 0, female: 0, total: 0 }));

        onMounted(() => {
            getAllPeriodLists();
            loadTableData(0, '');
            try {
                $('.period').each(function () {
                    var $this = $(this);
                    $this.wrap('<div class="position-relative"></div>');
                    $this.select2({
                        multiple: true,
                        dropdownAutoWidth: true,
                        width: '100%',
                        dropdownParent: $this.parent(),
                        placeholder: 'Select Options',
                    }).on('change', function () { setPeriodTitle($(this).val()); });
                });
                $('.select2-selection__arrow').html('<i class="feather icon-chevron-down"></i>');
                $('.date').flatpickr({
                    altInput: true,
                    altFormat: 'F j, Y',
                    dateFormat: 'Y-m-d',
                    mode: 'range',
                });
            } catch (e) {}
        });

        return {
            url, filterUrl, periodData, reportLevel,
            lgaId, lgaName, wardId, wardName, dpId, dpName,
            checkIfFilterOn, filterState, filters,
            tableOptions, statData, series, chartOptions,
            getTopChildStat,
            loadTableData, toggleFilter, applyFilter, removeSingleFilter,
            clearAllFilter, checkAndHideFilter, refreshData, controlBreadCrum,
            setPeriodTitle, splitWordAndCapitalize,
            capitalize: fmtUtils.capitalize,
            convertStringNumberToFigures: fmtUtils.convertStringNumberToFigures,
        };
    },
    template: `
        <div class="row" id="basic-table">

            <div class="col-md-6 col-sm-6 col-6 col-sm-6">
                <h2 class="content-header-title header-txt float-left mb-0">SMC Dashboard</h2>
            </div>
            <div class="col-md-6 col-sm-6 col-6 text-md-right text-right d-md-block">
                <div class="btn-group">
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
                        <span class="badge badge-dark filter-box" v-if="(Array.isArray(filterParam) ? filterParam.length : String(filterParam).length) > 0 && checkAndHideFilter(i)" @click="removeSingleFilter(i)">{{ splitWordAndCapitalize(i) }}: {{ filterParam }} <i class="feather icon-x"></i></span>
                    </template>
                    <a href="#" class="float-right clear-filter" @click="clearAllFilter()">Clear</a>
                </div>
            </div>

            <div class="col-12 mt-1" v-show="filterState">
                <div class="card mb-1">
                    <div class="card-body py-1">
                        <form id="filterForm">
                            <div class="row">
                                <div class="col-12 col-sm-6 col-md-5 col-lg-5">
                                    <div class="form-group">
                                        <label>Visit</label>
                                        <select @change="setPeriodTitle($event && $event.target ? Array.from($event.target.selectedOptions).map(o => o.value) : [])" v-model="tableOptions.filterParam.periodid" multiple class="form-control period" id="period">
                                            <option v-for="g in periodData" :key="g.periodid" :value="g.periodid">{{ g.title }}</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-12 col-sm-6 col-md-4 col-lg-5">
                                    <div class="form-group date_filter">
                                        <label>Report Date Range</label>
                                        <input type="text" id="reg_date" v-model="tableOptions.filterParam.reportDate" class="form-control reg_date date" placeholder="Report Date Range" name="reg_date" />
                                    </div>
                                </div>
                                <div class="col-12 col-sm-12 col-md-3 col-lg-2">
                                    <div class="form-group mt-2 text-right">
                                        <button type="button" class="btn btn-md btn-primary" @click="applyFilter()">Apply Filters</button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-12">
                <div class="breadcrumb-wrapper reporting-dashboard">
                    <ol class="breadcrumb pl-0">
                        <li class="breadcrumb-item" v-if="reportLevel>1" :class="reportLevel==2? 'active': ''" @click="controlBreadCrum(0, 1, '')">Child Registration</li>
                        <li class="breadcrumb-item" v-if="reportLevel>2" :class="reportLevel==3? 'active': ''" @click="controlBreadCrum(lgaId, 2, lgaName)">{{ lgaName }} LGA Wards</li>
                        <li class="breadcrumb-item" v-if="reportLevel>3" :class="reportLevel==4? 'active': ''" @click="controlBreadCrum(wardId, 3, wardName)">{{ wardName }} Wards DPs</li>
                        <li class="breadcrumb-item" v-if="reportLevel>4" :class="reportLevel==5? 'active': ''" @click="controlBreadCrum(dpId, 4, dpName)">{{ dpName }} Child DPs</li>
                    </ol>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-6 col-sm-12 mt-0">
                <div class="card mb-0 btmlr">
                    <div class="card-widget-separator-wrapper">
                        <div class="card-body pb-75 pt-75 card-widget-separator">
                            <div class="row gy-4 gy-sm-1">
                                <div class="col-sm-6 col-lg-4">
                                    <div class="d-flex justify-content-between align-items-start card-widget-1 border-end pb-sm-0">
                                        <div>
                                            <h6 class="mb-50 small text-primary">Male</h6>
                                            <h4 class="mb-0">{{ convertStringNumberToFigures(getTopChildStat.male) }}</h4>
                                        </div>
                                        <span class="avatar1 me-sm-4"><span class="avatar-initial bg-label-success rounded"><i class="ti-md ti ti-man"></i></span></span>
                                    </div>
                                    <hr class="d-none d-sm-block d-lg-none">
                                </div>
                                <div class="col-sm-6 col-lg-4">
                                    <div class="d-flex justify-content-between align-items-start card-widget-2 border-end pb-sm-0">
                                        <div>
                                            <h6 class="mb-50 small text-primary">Female</h6>
                                            <h4 class="mb-0">{{ convertStringNumberToFigures(getTopChildStat.female) }}</h4>
                                        </div>
                                        <span class="avatar1 me-sm-4"><span class="avatar-initial bg-label-info rounded"><i class="ti-md ti ti-woman"></i></span></span>
                                    </div>
                                    <hr class="d-none d-sm-block d-lg-none">
                                </div>
                                <div class="col-sm-12 col-lg-4">
                                    <div class="d-flex justify-content-between align-items-start card-widget-1 pb-sm-0">
                                        <div>
                                            <h6 class="mb-50 small text-primary">Total Children</h6>
                                            <h4 class="mb-0">{{ convertStringNumberToFigures(getTopChildStat.male + getTopChildStat.female) }}</h4>
                                        </div>
                                        <hr class="d-none d-sm-block d-lg-none me-4">
                                        <span class="avatar1 me-sm-4"><span class="avatar-initial bg-label-primary rounded"><i class="ti-md ti ti-users-group"></i></span></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card" style="height: 350px !important;">
                    <div class="table-responsive scrollBox">
                        <table class="table table-fixed border-top table-striped table-hover table-hover-animation">
                            <thead>
                                <tr>
                                    <th :colspan="reportLevel<4? 2: 1">Description</th>
                                    <th>Male</th>
                                    <th>Female</th>
                                    <th class="text-left">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template v-if="reportLevel<5">
                                    <tr v-for="g in statData.tableData" :key="g.id" @click="loadTableData(g.id, g.title)">
                                        <td style="padding-left: 1rem !important; padding-right: .2rem !important; width:34px" v-if="reportLevel<4"><i class="ti ti-circle-plus text-primary"></i></td>
                                        <td style="padding-left: .4rem !important;">{{ capitalize(g.title) }}</td>
                                        <td><small class="fw-bolder">{{ g.male }}</small></td>
                                        <td>{{ g.female }}</td>
                                        <td>{{ convertStringNumberToFigures(parseInt(g.total)) }}</td>
                                    </tr>
                                </template>
                                <tr v-if="statData.tableData.length == 0"><td class="text-center pt-2" :colspan="reportLevel<4? 6: 5"><small>No Data</small></td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="mb-50"></div>
                </div>
            </div>

            <div class="col-12 col-md-6 col-xl-6 col-sm-12 mb-0">
                <div class="card" style="height: 416px !important;">
                    <div class="card-body">
                        <div class="tab-content p-0 ms-0 ms-sm-2">
                            <div class="tab-pane fade show active" id="hh-mobilized-id" role="tabpanel">
                                <apexchart height="372" type="bar" :options="chartOptions" :series="series"></apexchart>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `,
};

// DrugAdmin — listens for filter events from ChildList and re-fetches.
const DrugAdmin = {
    setup() {
        const fmtUtils = useFormat();

        const url = ref(window.common && window.common.DataService);
        const filterUrl = ref('');
        const reportLevel = ref(1);
        const filterId = ref('');
        const lgaId = ref('');
        const lgaName = ref('');
        const wardId = ref('');
        const wardName = ref('');
        const dpId = ref('');
        const dpName = ref('');

        const tableOptions = reactive({
            filterParam: { periodid: [], visitTitle: '', reportDate: '' },
        });
        const statData = reactive({ tableData: [], chartData: [] });
        const series = ref([]);
        const chartOptions = ref({ xaxis: { title: { text: '' } } });

        const loadTableData = (fId, title) => {
            var fp = tableOptions.filterParam;
            var periodIds = joinWithCommaAnd(fp.periodid.slice(), true);
            var range = splitDateRange(fp.reportDate);

            var queryUrl = url.value;
            switch (reportLevel.value) {
                case 1:
                    filterId.value = 0;
                    queryUrl += '?qid=1114';
                    break;
                case 2:
                    lgaId.value = fId;
                    lgaName.value = title || lgaName.value;
                    queryUrl += '?qid=1115&filterId=' + lgaId.value;
                    break;
                case 3:
                    wardId.value = fId;
                    wardName.value = title || wardName.value;
                    queryUrl += '?qid=1116&filterId=' + wardId.value;
                    break;
                default:
                    return;
            }
            if (periodIds) queryUrl += '&pid=' + periodIds;
            if (range.start) queryUrl += '&sdate=' + range.start;
            if (range.end) queryUrl += '&edate=' + range.end;

            loadDashboardData(queryUrl);
        }
        const loadDashboardData = async (u) => {
            try {
                overlay.show();
                filterUrl.value = u;
                var response = await axios.get(u);
                if (response.data.result_code == 200) {
                    statData.tableData = response.data.data.table;
                    statData.chartData = response.data.data.chart;
                    statData.chartData.xAxisLabel = 'Days';
                    reportLevel.value = response.data.level;
                    plotChart();
                } else {
                    statData.tableData = [];
                    alert.Error('ERROR', response.data.message);
                }
            } catch (error) {
                alert.Error('ERROR', safeMessage(error));
            } finally {
                overlay.hide();
            }
        }
        const handleFilterChange = (data) => {
            tableOptions.filterParam.periodid = (data && data[0]) ? data[0].slice() : [];
            tableOptions.filterParam.visitTitle = (data && data[1]) || '';
            tableOptions.filterParam.reportDate = (data && data[2]) || '';
            applyFilter();
        }
        const controlBreadCrum = (fId, level, title) => {
            reportLevel.value = level;
            loadTableData(fId, title);
        }
        const plotChart = () => {
            chartOptions.value = makeBarChartOptions({
                colors: ['#FF9800', '#7367f0'],
                title: 'Drug Administration',
                stacked: false,
                categories: (statData.chartData && statData.chartData[1]) || [],
            });
            series.value = (statData.chartData && statData.chartData[0]) || [];
        }
        const refreshData = () => { loadDashboardData(filterUrl.value); };
        const applyFilter = () => {
            var checkFill = 0;
            if (tableOptions.filterParam.periodid.length > 0) checkFill++;
            if ((tableOptions.filterParam.reportDate || '').length > 0) checkFill++;
            if (checkFill > 0) {
                var periodIds = joinWithCommaAnd(tableOptions.filterParam.periodid.slice(), true);
                var u = cleanUrl(filterUrl.value) + buildFilterQs(periodIds, tableOptions.filterParam.reportDate);
                loadDashboardData(u);
            } else {
                clearAllFilter();
            }
        }
        const clearAllFilter = () => {
            Object.assign(tableOptions.filterParam, { periodid: [], visitTitle: '', reportDate: '' });
            loadDashboardData(cleanUrl(filterUrl.value));
        }
        const removeSingleFilter = (column_name) => {
            var fp = tableOptions.filterParam;
            if (Array.isArray(fp[column_name])) fp[column_name] = [];
            else fp[column_name] = '';
            if (column_name === 'visitTitle') {
                fp.periodid = [];
                fp.visitTitle = '';
            }
            applyFilter();
        }

        const getTopChildStat = computed(() => statData.tableData.reduce((acc, curr) => {
            acc.eligible += parseInt(curr.eligible, 10) || 0;
            acc.non_eligible += parseInt(curr.non_eligible, 10) || 0;
            acc.referral += parseInt(curr.referral, 10) || 0;
            acc.spaq1 += parseInt(curr.spaq1, 10) || 0;
            acc.spaq2 += parseInt(curr.spaq2, 10) || 0;
            acc.total += parseInt(curr.total, 10) || 0;
            return acc;
        }, { eligible: 0, non_eligible: 0, referral: 0, spaq1: 0, spaq2: 0, total: 0 }));

        onMounted(() => {
            bus.on(EVT_APPLY, handleFilterChange);
            bus.on(EVT_REFRESH, refreshData);
            bus.on(EVT_CLEAR, clearAllFilter);
            bus.on(EVT_REMOVE_SINGLE, removeSingleFilter);
            loadTableData(0, '');
        });
        onBeforeUnmount(() => {
            bus.off(EVT_APPLY, handleFilterChange);
            bus.off(EVT_REFRESH, refreshData);
            bus.off(EVT_CLEAR, clearAllFilter);
            bus.off(EVT_REMOVE_SINGLE, removeSingleFilter);
        });

        return {
            url, filterUrl, reportLevel,
            lgaId, lgaName, wardId, wardName, dpId, dpName,
            tableOptions, statData, series, chartOptions,
            getTopChildStat,
            loadTableData, controlBreadCrum,
            capitalize: fmtUtils.capitalize,
            convertStringNumberToFigures: fmtUtils.convertStringNumberToFigures,
        };
    },
    template: `
        <div class="row" id="basic-table">
            <div class="col-12">
                <div class="breadcrumb-wrapper reporting-dashboard">
                    <ol class="breadcrumb pl-0">
                        <li class="breadcrumb-item" v-if="reportLevel>1" :class="reportLevel==2? 'active': ''" @click="controlBreadCrum(0, 1, '')">Drug Administration</li>
                        <li class="breadcrumb-item" v-if="reportLevel>2" :class="reportLevel==3? 'active': ''" @click="controlBreadCrum(lgaId, 2, lgaName)">{{ lgaName }} LGA Wards</li>
                        <li class="breadcrumb-item" v-if="reportLevel>3" :class="reportLevel==4? 'active': ''" @click="controlBreadCrum(wardId, 3, wardName)">{{ wardName }} Wards DPs</li>
                        <li class="breadcrumb-item" v-if="reportLevel>4" :class="reportLevel==5? 'active': ''" @click="controlBreadCrum(dpId, 4, dpName)">{{ dpName }} Child DPs</li>
                    </ol>
                </div>
            </div>
            <div class="col-12">
                <div class="card mb-0 btmlr drug-card">
                    <div class="card-widget-separator-wrapper">
                        <div class="card-body pb-75 pt-75 card-widget-separator">
                            <div class="row gy-4 gy-sm-1">
                                <div class="col-sm-4 col-md-4 col-lg-2">
                                    <div class="d-flex justify-content-between align-items-start card-widget-1 border-end pb-sm-0">
                                        <div>
                                            <h6 class="mb-50 text-success small">Eligible</h6>
                                            <h4 class="mb-0">{{ convertStringNumberToFigures(getTopChildStat.eligible) }}</h4>
                                        </div>
                                        <span class="avatar1 me-sm-4"><span class="avatar-initial bg-label-success rounded"><i class="ti-md ti ti-accessible"></i></span></span>
                                    </div>
                                    <hr class="d-none d-sm-block d-lg-none">
                                </div>
                                <div class="col-sm-4 col-md-4 col-lg-2">
                                    <div class="d-flex justify-content-between align-items-start card-widget-1 border-end pb-sm-0">
                                        <div>
                                            <h6 class="mb-50 text-danger small">Non Eligible</h6>
                                            <h4 class="mb-0">{{ convertStringNumberToFigures(getTopChildStat.non_eligible) }}</h4>
                                        </div>
                                        <span class="avatar1 me-sm-4"><span class="avatar-initial bg-label-danger rounded"><i class="ti-md ti ti-accessible-off"></i></span></span>
                                    </div>
                                    <hr class="d-none d-sm-block d-lg-none">
                                </div>
                                <div class="col-sm-4 col-md-4 col-lg-2">
                                    <div class="d-flex justify-content-between align-items-start card-widget-2 border-end pb-sm-0">
                                        <div>
                                            <h6 class="mb-50 text-warning small">Referral</h6>
                                            <h4 class="mb-0">{{ convertStringNumberToFigures(getTopChildStat.referral) }}</h4>
                                        </div>
                                        <span class="avatar1 me-sm-4"><span class="avatar-initial bg-label-warning rounded"><i class="ti-md ti ti-emergency-bed"></i></span></span>
                                    </div>
                                    <hr class="d-none d-sm-block d-lg-none">
                                </div>
                                <div class="col-sm-4 col-md-4 col-lg-2">
                                    <div class="d-flex justify-content-between align-items-start card-widget-1 border-end pb-sm-0">
                                        <div>
                                            <h6 class="mb-50 small">SPAQ 1</h6>
                                            <h4 class="mb-0">{{ convertStringNumberToFigures(getTopChildStat.spaq1) }}</h4>
                                        </div>
                                        <span class="avatar1 me-sm-4"><span class="avatar-initial bg-label-blue rounded"><i class="ti-md ti ti-pill"></i></span></span>
                                    </div>
                                </div>
                                <div class="col-sm-4 col-md-4 col-lg-2">
                                    <div class="d-flex justify-content-between align-items-start card-widget-1 border-end pb-sm-0">
                                        <div>
                                            <h6 class="mb-50 small">SPAQ 2</h6>
                                            <h4 class="mb-0">{{ convertStringNumberToFigures(getTopChildStat.spaq2) }}</h4>
                                        </div>
                                        <span class="avatar1 me-sm-4"><span class="avatar-initial bg-label-dark rounded"><i class="ti-md ti ti-pill-off"></i></span></span>
                                    </div>
                                </div>
                                <div class="col-sm-4 col-md-4 col-lg-2">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="mb-50 text-primary small">Total</h6>
                                            <h4 class="mb-0">{{ convertStringNumberToFigures(getTopChildStat.total) }}</h4>
                                        </div>
                                        <span class="avatar1 me-sm-4"><span class="avatar-initial bg-label-primary rounded"><i class="ti-md ti ti-pills"></i></span></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="container-fluid no-gutters">
                <div class="row no-gutters">
                    <div class="col-12 col-md-6 col-xl-6 no-margin col-sm-12 mt-0">
                        <div class="card ttlr br-10 drug-tab">
                            <div class="scrollBox h-100 table-wrapper table-responsive">
                                <table class="table border-top table-striped table-hover table-hover-animation">
                                    <thead>
                                        <tr>
                                            <th :colspan="reportLevel<4? 2: 1">Description</th>
                                            <th>Eligible</th>
                                            <th>Non-Eligible</th>
                                            <th>Referral</th>
                                            <th>SPAQ 1</th>
                                            <th>SPAQ 2</th>
                                            <th class="text-left">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template v-if="reportLevel<5">
                                            <tr v-for="g in statData.tableData" :key="g.id" @click="loadTableData(g.id, g.title)">
                                                <td style="padding-left: 1rem !important; padding-right: .2rem !important; width:34px" v-if="reportLevel<4"><i class="ti ti-circle-plus text-primary"></i></td>
                                                <td style="padding-left: .4rem !important;">{{ capitalize(g.title) }}</td>
                                                <td>{{ convertStringNumberToFigures(g.eligible) }}</td>
                                                <td>{{ g.non_eligible }}</td>
                                                <td>{{ g.referral }}</td>
                                                <td>{{ g.spaq1 }}</td>
                                                <td>{{ g.spaq2 }}</td>
                                                <td>{{ convertStringNumberToFigures(parseInt(g.total)) }}</td>
                                            </tr>
                                        </template>
                                        <tr v-if="statData.tableData.length == 0"><td class="text-center pt-2" :colspan="reportLevel<4? 9: 8"><small>No Data</small></td></tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="mb-50"></div>
                        </div>
                    </div>

                    <div class="col-12 col-md-6 col-xl-6 no-margin col-sm-12 mb-0">
                        <div class="card ttlr drug-chart">
                            <div class="card-body pt-25 ttlr">
                                <div class="tab-content p-0 ms-0 ms-sm-2">
                                    <div class="tab-pane fade show active" id="hh-mobilized-id" role="tabpanel">
                                        <apexchart height="372" type="bar" :options="chartOptions" :series="series"></apexchart>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `,
};

// ReferralAdmin — same event-driven shape as DrugAdmin (qid 1117/1118/1119).
const ReferralAdmin = {
    setup() {
        const fmtUtils = useFormat();

        const url = ref(window.common && window.common.DataService);
        const filterUrl = ref('');
        const reportLevel = ref(1);
        const filterId = ref('');
        const lgaId = ref('');
        const lgaName = ref('');
        const wardId = ref('');
        const wardName = ref('');
        const dpId = ref('');
        const dpName = ref('');

        const tableOptions = reactive({
            filterParam: { periodid: [], visitTitle: '', reportDate: '' },
        });
        const statData = reactive({ tableData: [], chartData: [] });
        const series = ref([]);
        const chartOptions = ref({ xaxis: { title: { text: '' } } });

        const loadTableData = (fId, title) => {
            var fp = tableOptions.filterParam;
            var periodIds = joinWithCommaAnd(fp.periodid.slice(), true);
            var range = splitDateRange(fp.reportDate);

            var queryUrl = url.value;
            switch (reportLevel.value) {
                case 1:
                    filterId.value = 0;
                    queryUrl += '?qid=1117';
                    break;
                case 2:
                    lgaId.value = fId;
                    lgaName.value = title || lgaName.value;
                    queryUrl += '?qid=1118&filterId=' + lgaId.value;
                    break;
                case 3:
                    wardId.value = fId;
                    wardName.value = title || wardName.value;
                    queryUrl += '?qid=1119&filterId=' + wardId.value;
                    break;
                default:
                    return;
            }
            if (periodIds) queryUrl += '&pid=' + periodIds;
            if (range.start) queryUrl += '&sdate=' + range.start;
            if (range.end) queryUrl += '&edate=' + range.end;

            loadDashboardData(queryUrl);
        }
        const loadDashboardData = async (u) => {
            try {
                overlay.show();
                filterUrl.value = u;
                var response = await axios.get(u);
                if (response.data.result_code == 200) {
                    statData.tableData = response.data.data.table;
                    statData.chartData = response.data.data.chart;
                    statData.chartData.xAxisLabel = 'Days';
                    reportLevel.value = response.data.level;
                    plotChart();
                } else {
                    statData.tableData = [];
                    alert.Error('ERROR', response.data.message);
                }
            } catch (error) {
                alert.Error('ERROR', safeMessage(error));
            } finally {
                overlay.hide();
            }
        }
        const handleFilterChange = (data) => {
            tableOptions.filterParam.periodid = (data && data[0]) ? data[0].slice() : [];
            tableOptions.filterParam.visitTitle = (data && data[1]) || '';
            tableOptions.filterParam.reportDate = (data && data[2]) || '';
            applyFilter();
        }
        const controlBreadCrum = (fId, level, title) => {
            reportLevel.value = level;
            loadTableData(fId, title);
        }
        const plotChart = () => {
            chartOptions.value = makeBarChartOptions({
                colors: ['#D7D8E2', '#4351F4'],
                title: 'Referral',
                stacked: false,
                categories: (statData.chartData && statData.chartData[1]) || [],
            });
            series.value = (statData.chartData && statData.chartData[0]) || [];
        }
        const refreshData = () => { loadDashboardData(filterUrl.value); };
        const applyFilter = () => {
            var checkFill = 0;
            if (tableOptions.filterParam.periodid.length > 0) checkFill++;
            if ((tableOptions.filterParam.reportDate || '').length > 0) checkFill++;
            if (checkFill > 0) {
                var periodIds = joinWithCommaAnd(tableOptions.filterParam.periodid.slice(), true);
                var u = cleanUrl(filterUrl.value) + buildFilterQs(periodIds, tableOptions.filterParam.reportDate);
                loadDashboardData(u);
            } else {
                clearAllFilter();
            }
        }
        const clearAllFilter = () => {
            Object.assign(tableOptions.filterParam, { periodid: [], visitTitle: '', reportDate: '' });
            loadDashboardData(cleanUrl(filterUrl.value));
        }
        const removeSingleFilter = (column_name) => {
            var fp = tableOptions.filterParam;
            if (Array.isArray(fp[column_name])) fp[column_name] = [];
            else fp[column_name] = '';
            if (column_name === 'visitTitle') {
                fp.periodid = [];
                fp.visitTitle = '';
            }
            applyFilter();
        }

        const getTopChildStat = computed(() => statData.tableData.reduce((acc, curr) => {
            acc.referred += parseInt(curr.referred, 10) || 0;
            acc.attended += parseInt(curr.attended, 10) || 0;
            acc.total += parseInt(curr.total, 10) || 0;
            return acc;
        }, { referred: 0, attended: 0, total: 0 }));

        onMounted(() => {
            bus.on(EVT_APPLY, handleFilterChange);
            bus.on(EVT_REFRESH, refreshData);
            bus.on(EVT_CLEAR, clearAllFilter);
            bus.on(EVT_REMOVE_SINGLE, removeSingleFilter);
            loadTableData(0, '');
        });
        onBeforeUnmount(() => {
            bus.off(EVT_APPLY, handleFilterChange);
            bus.off(EVT_REFRESH, refreshData);
            bus.off(EVT_CLEAR, clearAllFilter);
            bus.off(EVT_REMOVE_SINGLE, removeSingleFilter);
        });

        return {
            url, filterUrl, reportLevel,
            lgaId, lgaName, wardId, wardName, dpId, dpName,
            tableOptions, statData, series, chartOptions,
            getTopChildStat,
            loadTableData, controlBreadCrum,
            capitalize: fmtUtils.capitalize,
            convertStringNumberToFigures: fmtUtils.convertStringNumberToFigures,
        };
    },
    template: `
        <div class="row" id="basic-table">
            <div class="col-12">
                <div class="breadcrumb-wrapper reporting-dashboard">
                    <ol class="breadcrumb pl-0">
                        <li class="breadcrumb-item" v-if="reportLevel>1" :class="reportLevel==2? 'active': ''" @click="controlBreadCrum(0, 1, '')">Referrals</li>
                        <li class="breadcrumb-item" v-if="reportLevel>2" :class="reportLevel==3? 'active': ''" @click="controlBreadCrum(lgaId, 2, lgaName)">{{ lgaName }} LGA Wards</li>
                        <li class="breadcrumb-item" v-if="reportLevel>3" :class="reportLevel==4? 'active': ''" @click="controlBreadCrum(wardId, 3, wardName)">{{ wardName }} Wards DPs</li>
                        <li class="breadcrumb-item" v-if="reportLevel>4" :class="reportLevel==5? 'active': ''" @click="controlBreadCrum(dpId, 4, dpName)">{{ dpName }} Child DPs</li>
                    </ol>
                </div>
            </div>

            <div class="col-12 col-md-6 col-xl-6 mt-0 col-sm-12">
                <div class="card mb-0 btmlr">
                    <div class="card-widget-separator-wrapper">
                        <div class="card-body pb-75 pt-75 card-widget-separator">
                            <div class="row gy-4 gy-sm-1">
                                <div class="col-sm-4 col-lg-4">
                                    <div class="d-flex justify-content-between align-items-start card-widget-1 border-end pb-sm-0">
                                        <div>
                                            <h6 class="mb-50 text-warning small">Referred</h6>
                                            <h4 class="mb-0">{{ convertStringNumberToFigures(getTopChildStat.referred) }}</h4>
                                        </div>
                                        <hr class="d-none d-sm-block d-lg-none">
                                        <span class="avatar1 me-sm-4"><span class="avatar-initial bg-label-warning rounded"><i class="ti-md ti ti-emergency-bed"></i></span></span>
                                    </div>
                                </div>
                                <div class="col-sm-4 col-lg-4">
                                    <div class="d-flex justify-content-between align-items-start card-widget-1 border-end pb-sm-0">
                                        <div>
                                            <h6 class="mb-50 text-success small">Attended</h6>
                                            <h4 class="mb-0">{{ convertStringNumberToFigures(getTopChildStat.attended) }}</h4>
                                        </div>
                                        <hr class="d-none d-sm-block d-lg-none">
                                        <span class="avatar1 me-sm-4"><span class="avatar-initial bg-label-success rounded"><i class="ti-md ti ti-stethoscope"></i></span></span>
                                    </div>
                                </div>
                                <div class="col-sm-4 col-lg-4">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="mb-50 text-primary small">Total</h6>
                                            <h4 class="mb-0">{{ convertStringNumberToFigures(getTopChildStat.total) }}</h4>
                                        </div>
                                        <hr class="d-none d-sm-block d-lg-none me-4">
                                        <span class="avatar1 me-sm-4"><span class="avatar-initial bg-label-primary rounded"><i class="ti-md ti ti-sum"></i></span></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card" style="height: 350px !important;">
                    <div class="table-responsive scrollBox">
                        <table class="table table-fixed border-top table-striped table-hover table-hover-animation">
                            <thead>
                                <tr>
                                    <th :colspan="reportLevel<4? 2: 1">Description</th>
                                    <th>Reffered</th>
                                    <th>Attended</th>
                                    <th class="text-left">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template v-if="reportLevel<5">
                                    <tr v-for="g in statData.tableData" :key="g.id" @click="loadTableData(g.id, g.title)">
                                        <td style="padding-left: 1rem !important; padding-right: .2rem !important; width:34px" v-if="reportLevel<4"><i class="ti ti-circle-plus text-primary"></i></td>
                                        <td style="padding-left: .4rem !important;">{{ capitalize(g.title) }}</td>
                                        <td>{{ convertStringNumberToFigures(g.referred) }}</td>
                                        <td>{{ convertStringNumberToFigures(g.attended) }}</td>
                                        <td>{{ convertStringNumberToFigures(parseInt(g.total)) }}</td>
                                    </tr>
                                </template>
                                <tr v-if="statData.tableData.length == 0"><td class="text-center pt-2" :colspan="reportLevel<4? 6: 5"><small>No Data</small></td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="mb-50"></div>
                </div>
            </div>

            <div class="col-12 col-md-6 col-xl-6 mb-0 col-sm-12">
                <div class="card" style="height: 416px !important;">
                    <div class="card-body">
                        <div class="tab-content p-0 ms-0 ms-sm-2">
                            <div class="tab-pane fade show active" id="hh-mobilized-id" role="tabpanel">
                                <apexchart height="372" type="bar" :options="chartOptions" :series="series"></apexchart>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `,
};

// IccAdmin — inventory control panel. Two-row group display with merged
// rows for paired SPAQ 1/SPAQ 2 entries (findDuplicateIds tracks indices
// of consecutive same-id rows; rowColSpan/hideCell drive the rowspan
// markup). Has no chart of its own.
const IccAdmin = {
    setup() {
        const fmtUtils = useFormat();

        const url = ref(window.common && window.common.DataService);
        const filterUrl = ref('');
        const reportLevel = ref(1);
        const filterId = ref('');
        const lgaId = ref('');
        const lgaName = ref('');
        const wardId = ref('');
        const wardName = ref('');
        const dpId = ref('');
        const dpName = ref('');

        const tableOptions = reactive({
            filterParam: { periodid: [], visitTitle: '', reportDate: '' },
        });
        const statData = reactive({
            tableData: [],
            chartData: [],
            firstDuplicateIds: [],
            secondDuplicateIds: [],
        });

        const loadTableData = (fId, title) => {
            var fp = tableOptions.filterParam;
            var periodIds = joinWithCommaAnd(fp.periodid.slice(), true);
            var range = splitDateRange(fp.reportDate);

            var queryUrl = url.value;
            switch (reportLevel.value) {
                case 1:
                    filterId.value = 0;
                    queryUrl += '?qid=1120';
                    break;
                case 2:
                    lgaId.value = fId;
                    lgaName.value = title || lgaName.value;
                    queryUrl += '?qid=1121&filterId=' + lgaId.value;
                    break;
                case 3:
                    wardId.value = fId;
                    wardName.value = title || wardName.value;
                    queryUrl += '?qid=1122&filterId=' + wardId.value;
                    break;
                default:
                    return;
            }
            if (periodIds) queryUrl += '&pid=' + periodIds;
            if (range.start) queryUrl += '&sdate=' + range.start;
            if (range.end) queryUrl += '&edate=' + range.end;

            loadDashboardData(queryUrl);
        }
        const loadDashboardData = async (u) => {
            try {
                overlay.show();
                filterUrl.value = u;
                var response = await axios.get(u);
                if (response.data.result_code == 200) {
                    statData.tableData = response.data.data;
                    var dupes = findDuplicateIds(response.data.data || []);
                    statData.firstDuplicateIds = dupes.duplicates;
                    statData.secondDuplicateIds = dupes.firstDuplicateOccurence;
                    reportLevel.value = response.data.level;
                } else {
                    statData.tableData = [];
                    alert.Error('ERROR', response.data.message);
                }
            } catch (error) {
                alert.Error('ERROR', safeMessage(error));
            } finally {
                overlay.hide();
            }
        }
        const handleFilterChange = (data) => {
            tableOptions.filterParam.periodid = (data && data[0]) ? data[0].slice() : [];
            tableOptions.filterParam.visitTitle = (data && data[1]) || '';
            tableOptions.filterParam.reportDate = (data && data[2]) || '';
            applyFilter();
        }
        const controlBreadCrum = (fId, level, title) => {
            reportLevel.value = level;
            loadTableData(fId, title);
        }
        const refreshData = () => { loadDashboardData(filterUrl.value); };
        const applyFilter = () => {
            var checkFill = 0;
            if (tableOptions.filterParam.periodid.length > 0) checkFill++;
            if ((tableOptions.filterParam.reportDate || '').length > 0) checkFill++;
            if (checkFill > 0) {
                var periodIds = joinWithCommaAnd(tableOptions.filterParam.periodid.slice(), true);
                var u = cleanUrl(filterUrl.value) + buildFilterQs(periodIds, tableOptions.filterParam.reportDate);
                loadDashboardData(u);
            } else {
                clearAllFilter();
            }
        }
        const clearAllFilter = () => {
            Object.assign(tableOptions.filterParam, { periodid: [], visitTitle: '', reportDate: '' });
            loadDashboardData(cleanUrl(filterUrl.value));
        }
        const removeSingleFilter = (column_name) => {
            var fp = tableOptions.filterParam;
            if (Array.isArray(fp[column_name])) fp[column_name] = [];
            else fp[column_name] = '';
            if (column_name === 'visitTitle') {
                fp.periodid = [];
                fp.visitTitle = '';
            }
            applyFilter();
        }

        const findDuplicateIds = (data) => {
            var idMap = {};
            var duplicates = [];
            var firstDuplicateOccurence = [];
            (data || []).forEach((item, index) => {
                if (idMap[item.id] !== undefined) {
                    duplicates.push(index);
                    firstDuplicateOccurence.push(index - 1);
                } else {
                    idMap[item.id] = index;
                }
            });
            return { duplicates: duplicates, firstDuplicateOccurence: firstDuplicateOccurence };
        }
        const rowColSpan = (index) => {
            return statData.firstDuplicateIds.indexOf(index) !== -1;
        }
        const hideCell = (index) => {
            return statData.secondDuplicateIds.indexOf(index) !== -1;
        }
        const groupStyle = (i) => {
            var merged = statData.firstDuplicateIds.concat(statData.secondDuplicateIds);
            return merged.indexOf(i) !== -1;
        }
        const total = (g) => {
            return ['administered', 'redosed', 'wasted', 'loss'].reduce((sum, key) => sum + (parseInt(g[key] || 0, 10) || 0), 0);
        }

        const getTopIccStat = computed(() => statData.tableData.reduce((acc, item) => {
            if (item.drug === 'SPAQ 1') {
                acc.sumSpaq1Issued += parseInt(item.total_issued) || 0;
                acc.sumSpaq1Administered += parseInt(item.administered) || 0;
                acc.sumSpaq1Redosed += parseInt(item.redosed) || 0;
                acc.sumSpaq1Wasted += parseInt(item.wasted) || 0;
                acc.sumSpaq1Loss += parseInt(item.loss) || 0;
                acc.sumSpaq1Facility += parseInt(item.count_facility) || 0;
            } else if (item.drug === 'SPAQ 2') {
                acc.sumSpaq2Issued += parseInt(item.total_issued) || 0;
                acc.sumSpaq2Administered += parseInt(item.administered) || 0;
                acc.sumSpaq2Redosed += parseInt(item.redosed) || 0;
                acc.sumSpaq2Wasted += parseInt(item.wasted) || 0;
                acc.sumSpaq2Loss += parseInt(item.loss) || 0;
                acc.sumSpaq2Facility += parseInt(item.count_facility) || 0;
            }
            return acc;
        }, {
            sumSpaq1Issued: 0, sumSpaq1Administered: 0, sumSpaq1Redosed: 0,
            sumSpaq1Wasted: 0, sumSpaq1Loss: 0, sumSpaq1Facility: 0,
            sumSpaq2Issued: 0, sumSpaq2Administered: 0, sumSpaq2Redosed: 0,
            sumSpaq2Wasted: 0, sumSpaq2Loss: 0, sumSpaq2Facility: 0,
        }));

        onMounted(() => {
            bus.on(EVT_APPLY, handleFilterChange);
            bus.on(EVT_REFRESH, refreshData);
            bus.on(EVT_CLEAR, clearAllFilter);
            bus.on(EVT_REMOVE_SINGLE, removeSingleFilter);
            loadTableData(0, '');
        });
        onBeforeUnmount(() => {
            bus.off(EVT_APPLY, handleFilterChange);
            bus.off(EVT_REFRESH, refreshData);
            bus.off(EVT_CLEAR, clearAllFilter);
            bus.off(EVT_REMOVE_SINGLE, removeSingleFilter);
        });

        return {
            url, filterUrl, reportLevel,
            lgaId, lgaName, wardId, wardName, dpId, dpName,
            tableOptions, statData,
            getTopIccStat,
            loadTableData, controlBreadCrum,
            rowColSpan, hideCell, groupStyle, total,
            convertStringNumberToFigures: fmtUtils.convertStringNumberToFigures,
            progressBarWidth: fmtUtils.progressBarWidth,
            progressBarStatus: fmtUtils.progressBarStatus,
        };
    },
    template: `
        <div class="row" id="basic-table">
            <div class="col-12">
                <div class="breadcrumb-wrapper reporting-dashboard">
                    <ol class="breadcrumb pl-0">
                        <li class="breadcrumb-item" v-if="reportLevel>1" :class="reportLevel==2? 'active': ''" @click="controlBreadCrum(0, 1, '')">Inventory Control</li>
                        <li class="breadcrumb-item" v-if="reportLevel>2" :class="reportLevel==3? 'active': ''" @click="controlBreadCrum(lgaId, 2, lgaName)">{{ lgaName }} LGA Wards</li>
                        <li class="breadcrumb-item" v-if="reportLevel>3" :class="reportLevel==4? 'active': ''" @click="controlBreadCrum(wardId, 3, wardName)">{{ wardName }} Wards DPs ICCs</li>
                        <li class="breadcrumb-item" v-if="reportLevel>4" :class="reportLevel==5? 'active': ''" @click="controlBreadCrum(dpId, 4, dpName)">{{ dpName }} Child DPs</li>
                    </ol>
                </div>
            </div>

            <div class="col-12 col-md-12 col-xl-12 mt-0 col-sm-12">
                <div class="card mb-0 btmlr drug-card">
                    <div class="card-header py-50 d-flex justify-content-between">
                        <h5 class="card-title font-small-2 font-weight-bolder mb-25 text-default">SPAQ 1</h5>
                    </div>
                    <div class="card-body pb-0 icc-card d-flex align-items-end">
                        <div class="w-100">
                            <div class="row gy-3">
                                <div class="col-sm-4 col-md-4 col-lg-2 col-6">
                                    <div class="d-flex align-items-center mb-1">
                                        <div class="badge rounded bg-label-primary me-4 p-50"><i class="ti ti-package-export ti-md"></i></div>
                                        <div class="card-info">
                                            <h5 class="mb-0">{{ convertStringNumberToFigures(getTopIccStat.sumSpaq1Issued) }}</h5>
                                            <small>Issued</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-4 col-md-4 col-lg-2 col-6">
                                    <div class="d-flex align-items-center mb-1">
                                        <div class="badge rounded bg-label-success me-4 p-50"><i class="ti ti-pills ti-md"></i></div>
                                        <div class="card-info w-100">
                                            <h5 class="mb-0">{{ convertStringNumberToFigures(getTopIccStat.sumSpaq1Administered) }}</h5>
                                            <div class="d-flex align-items-center w-100">
                                                <div class="progress w-100 me-3" style="height: 6px;">
                                                    <div class="progress-bar" :class="progressBarStatus(getTopIccStat.sumSpaq1Issued, getTopIccStat.sumSpaq1Administered)" :style="{ width: progressBarWidth(getTopIccStat.sumSpaq1Issued, getTopIccStat.sumSpaq1Administered) }" aria-valuemin="0" aria-valuemax="100"></div>
                                                </div>
                                                <small class="text-heading ml-50">{{ progressBarWidth(getTopIccStat.sumSpaq1Issued, getTopIccStat.sumSpaq1Administered) }}</small>
                                            </div>
                                            <small>Administered</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-4 col-md-4 col-lg-2 col-6">
                                    <div class="d-flex align-items-center mb-1">
                                        <div class="badge rounded bg-label-info me-4 p-50"><i class="ti ti-pill ti-md"></i></div>
                                        <div class="card-info w-100">
                                            <h5 class="mb-0">{{ convertStringNumberToFigures(getTopIccStat.sumSpaq1Redosed) }}</h5>
                                            <div class="d-flex align-items-center w-100">
                                                <div class="progress w-100 me-3" style="height: 6px;">
                                                    <div class="progress-bar" :class="progressBarStatus(getTopIccStat.sumSpaq1Issued, getTopIccStat.sumSpaq1Redosed)" :style="{ width: progressBarWidth(getTopIccStat.sumSpaq1Issued, getTopIccStat.sumSpaq1Redosed) }" aria-valuemin="0" aria-valuemax="100"></div>
                                                </div>
                                                <small class="text-heading ml-50">{{ progressBarWidth(getTopIccStat.sumSpaq1Issued, getTopIccStat.sumSpaq1Redosed) }}</small>
                                            </div>
                                            <small>Redose</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-4 col-md-4 col-lg-2 col-6">
                                    <div class="d-flex align-items-center mb-1">
                                        <div class="badge rounded bg-label-danger me-4 p-50"><i class="ti ti-bucket-droplet ti-md"></i></div>
                                        <div class="card-info w-100">
                                            <h5 class="mb-0">{{ convertStringNumberToFigures(getTopIccStat.sumSpaq1Wasted) }}</h5>
                                            <div class="d-flex align-items-center w-100">
                                                <div class="progress w-100 me-3" style="height: 6px;">
                                                    <div class="progress-bar" :class="progressBarStatus(getTopIccStat.sumSpaq1Issued, getTopIccStat.sumSpaq1Wasted)" :style="{ width: progressBarWidth(getTopIccStat.sumSpaq1Issued, getTopIccStat.sumSpaq1Wasted) }" aria-valuemin="0" aria-valuemax="100"></div>
                                                </div>
                                                <small class="text-heading ml-50">{{ progressBarWidth(getTopIccStat.sumSpaq1Issued, getTopIccStat.sumSpaq1Wasted) }}</small>
                                            </div>
                                            <small>Wasted</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-4 col-md-4 col-lg-2 col-6">
                                    <div class="d-flex align-items-center mb-1">
                                        <div class="badge rounded bg-label-warning me-4 p-50"><i class="ti ti-circle-half-2 ti-md"></i></div>
                                        <div class="card-info w-100">
                                            <h5 class="mb-0">{{ convertStringNumberToFigures(getTopIccStat.sumSpaq1Loss) }}</h5>
                                            <div class="d-flex align-items-center w-100">
                                                <div class="progress w-100 me-3" style="height: 6px;">
                                                    <div class="progress-bar" :class="progressBarStatus(getTopIccStat.sumSpaq1Issued, getTopIccStat.sumSpaq1Loss)" :style="{ width: progressBarWidth(getTopIccStat.sumSpaq1Issued, getTopIccStat.sumSpaq1Loss) }" aria-valuemin="0" aria-valuemax="100"></div>
                                                </div>
                                                <small class="text-heading ml-50">{{ progressBarWidth(getTopIccStat.sumSpaq1Issued, getTopIccStat.sumSpaq1Loss) }}</small>
                                            </div>
                                            <small>Loss</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-4 col-md-4 col-lg-2 col-6">
                                    <div class="d-flex align-items-center mb-1">
                                        <div class="badge rounded bg-label-secondary me-4 p-50"><i class="ti ti-building-hospital ti-md"></i></div>
                                        <div class="card-info">
                                            <h5 class="mb-0">{{ convertStringNumberToFigures(getTopIccStat.sumSpaq1Facility) }}</h5>
                                            <small>Facility</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-0 btmlr ttlr">
                    <div class="card-header py-50 d-flex justify-content-between">
                        <h5 class="card-title font-small-2 font-weight-bolder mb-25 text-default">SPAQ 2</h5>
                    </div>
                    <div class="card-body pb-50 icc-card d-flex align-items-end">
                        <div class="w-100">
                            <div class="row gy-3">
                                <div class="col-sm-4 col-md-4 col-lg-2 col-6">
                                    <div class="d-flex align-items-center mb-1">
                                        <div class="badge rounded bg-label-primary me-4 p-50"><i class="ti ti-package-export ti-md"></i></div>
                                        <div class="card-info w-100">
                                            <h5 class="mb-0">{{ convertStringNumberToFigures(getTopIccStat.sumSpaq2Issued) }}</h5>
                                            <small>Issued</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-4 col-md-4 col-lg-2 col-6">
                                    <div class="d-flex align-items-center mb-1">
                                        <div class="badge rounded bg-label-success me-4 p-50"><i class="ti ti-pills ti-md"></i></div>
                                        <div class="card-info w-100">
                                            <h5 class="mb-0">{{ convertStringNumberToFigures(getTopIccStat.sumSpaq2Administered) }}</h5>
                                            <div class="d-flex align-items-center w-100">
                                                <div class="progress w-100 me-3" style="height: 6px;">
                                                    <div class="progress-bar" :class="progressBarStatus(getTopIccStat.sumSpaq2Issued, getTopIccStat.sumSpaq2Administered)" :style="{ width: progressBarWidth(getTopIccStat.sumSpaq2Issued, getTopIccStat.sumSpaq2Administered) }" aria-valuemin="0" aria-valuemax="100"></div>
                                                </div>
                                                <small class="text-heading ml-50">{{ progressBarWidth(getTopIccStat.sumSpaq2Issued, getTopIccStat.sumSpaq2Administered) }}</small>
                                            </div>
                                            <small>Administered</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-4 col-md-4 col-lg-2 col-6">
                                    <div class="d-flex align-items-center mb-1">
                                        <div class="badge rounded bg-label-info me-4 p-50"><i class="ti ti-pill ti-md"></i></div>
                                        <div class="card-info w-100">
                                            <h5 class="mb-0">{{ convertStringNumberToFigures(getTopIccStat.sumSpaq2Redosed) }}</h5>
                                            <div class="d-flex align-items-center w-100">
                                                <div class="progress w-100 me-3" style="height: 6px;">
                                                    <div class="progress-bar" :class="progressBarStatus(getTopIccStat.sumSpaq2Issued, getTopIccStat.sumSpaq2Redosed)" :style="{ width: progressBarWidth(getTopIccStat.sumSpaq2Issued, getTopIccStat.sumSpaq2Redosed) }" aria-valuemin="0" aria-valuemax="100"></div>
                                                </div>
                                                <small class="text-heading ml-50">{{ progressBarWidth(getTopIccStat.sumSpaq2Issued, getTopIccStat.sumSpaq2Redosed) }}</small>
                                            </div>
                                            <small>Redose</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-4 col-md-4 col-lg-2 col-6">
                                    <div class="d-flex align-items-center mb-1">
                                        <div class="badge rounded bg-label-danger me-4 p-50"><i class="ti ti-bucket-droplet ti-md"></i></div>
                                        <div class="card-info w-100">
                                            <h5 class="mb-0">{{ convertStringNumberToFigures(getTopIccStat.sumSpaq2Wasted) }}</h5>
                                            <div class="d-flex align-items-center w-100">
                                                <div class="progress w-100 me-3" style="height: 6px;">
                                                    <div class="progress-bar" :class="progressBarStatus(getTopIccStat.sumSpaq2Issued, getTopIccStat.sumSpaq2Wasted)" :style="{ width: progressBarWidth(getTopIccStat.sumSpaq2Issued, getTopIccStat.sumSpaq2Wasted) }" aria-valuemin="0" aria-valuemax="100"></div>
                                                </div>
                                                <small class="text-heading ml-50">{{ progressBarWidth(getTopIccStat.sumSpaq2Issued, getTopIccStat.sumSpaq2Wasted) }}</small>
                                            </div>
                                            <small>Wasted</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-4 col-md-4 col-lg-2 col-6">
                                    <div class="d-flex align-items-center mb-1">
                                        <div class="badge rounded bg-label-warning me-4 p-50"><i class="ti ti-circle-half-2 ti-md"></i></div>
                                        <div class="card-info w-100">
                                            <h5 class="mb-0">{{ convertStringNumberToFigures(getTopIccStat.sumSpaq2Loss) }}</h5>
                                            <div class="d-flex align-items-center w-100">
                                                <div class="progress w-100 me-3" style="height: 6px;">
                                                    <div class="progress-bar" :class="progressBarStatus(getTopIccStat.sumSpaq2Issued, getTopIccStat.sumSpaq2Loss)" :style="{ width: progressBarWidth(getTopIccStat.sumSpaq2Issued, getTopIccStat.sumSpaq2Loss) }" aria-valuemin="0" aria-valuemax="100"></div>
                                                </div>
                                                <small class="text-heading ml-50">{{ progressBarWidth(getTopIccStat.sumSpaq2Issued, getTopIccStat.sumSpaq2Loss) }}</small>
                                            </div>
                                            <small>Loss</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-4 col-md-4 col-lg-2 col-6">
                                    <div class="d-flex align-items-center mb-1">
                                        <div class="badge rounded bg-label-secondary me-4 p-50"><i class="ti ti-building-hospital ti-md"></i></div>
                                        <div class="card-info">
                                            <h5 class="mb-0">{{ convertStringNumberToFigures(getTopIccStat.sumSpaq2Facility) }}</h5>
                                            <small>Facility</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card" style="height: 460px !important;">
                    <div class="table-wrapper table-responsive scrollBox">
                        <table class="table table-striped-custom" :class="reportLevel!=4? '': 'table-striped'">
                            <thead>
                                <tr>
                                    <th :colspan="reportLevel<=4? 2: 1">Location</th>
                                    <th v-if="reportLevel<=4" style="padding: 0.72rem 1rem !important">Drug</th>
                                    <th>Period</th>
                                    <th>Facility</th>
                                    <th>Team</th>
                                    <th>Administered</th>
                                    <th>Redose</th>
                                    <th>Wasted</th>
                                    <th>Loss</th>
                                    <th>Total Issued</th>
                                    <th>Issued</th>
                                    <th>Pending</th>
                                    <th>Confirmed</th>
                                    <th>Accepted</th>
                                    <th>Returned</th>
                                    <th>Reconciled</th>
                                    <th class="px-50" style="min-width: 200px">% Used</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template v-if="reportLevel<5">
                                    <tr v-for="(g, i) in statData.tableData" :key="i" :class="groupStyle(i) && reportLevel!=4? 'group1': 'non-group'" @click="loadTableData(g.id, g.title)">
                                        <td v-if="hideCell(i) && reportLevel<4" :rowspan="!rowColSpan(i)? 2: 1" style="padding-left: 1rem !important; padding-right: .2rem !important; width:34px"><i class="ti ti-circle-plus text-primary"></i></td>
                                        <td v-if="hideCell(i) && reportLevel<=4" :rowspan="!rowColSpan(i)? 2: 1" style="padding-left: .4rem !important;">
                                            <div class="text-nowrap">{{ g.title }}</div>
                                        </td>
                                        <td class="text-nowrap" :colspan="reportLevel==4? 2: 1" style="padding: 0.72rem 1rem !important">{{ g.drug }}</td>
                                        <td>{{ g.period }}</td>
                                        <td>{{ convertStringNumberToFigures(g.count_facility) }}</td>
                                        <td>{{ convertStringNumberToFigures(g.count_team) }}</td>
                                        <td>{{ convertStringNumberToFigures(g.administered) }}</td>
                                        <td>{{ convertStringNumberToFigures(g.redosed) }}</td>
                                        <td>{{ convertStringNumberToFigures(g.wasted) }}</td>
                                        <td>{{ convertStringNumberToFigures(g.loss) }}</td>
                                        <td>{{ convertStringNumberToFigures(g.total_issued) }}</td>
                                        <td>{{ convertStringNumberToFigures(g.issued) }}</td>
                                        <td>{{ convertStringNumberToFigures(g.pending) }}</td>
                                        <td>{{ convertStringNumberToFigures(g.confirmed) }}</td>
                                        <td>{{ convertStringNumberToFigures(g.accepted) }}</td>
                                        <td>{{ convertStringNumberToFigures(g.returned) }}</td>
                                        <td>{{ convertStringNumberToFigures(g.reconciled) }}</td>
                                        <td class="px-75">
                                            <div class="d-flex align-items-center">
                                                <div class="progress w-100 me-3" style="height: 6px;">
                                                    <div class="progress-bar" :class="progressBarStatus(g.total_issued, total(g))" :style="{ width: progressBarWidth(g.total_issued, total(g)) }" aria-valuemin="0" aria-valuemax="100"></div>
                                                </div>
                                                <span class="text-heading ml-50">{{ progressBarWidth(g.total_issued, total(g)) }}</span>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                                <tr v-if="statData.tableData.length == 0"><td class="text-center pt-2" :colspan="reportLevel<4? 16: 17"><small>No Data</small></td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="mb-50"></div>
                </div>
            </div>
        </div>
    `,
};

useApp({ template: `<div><page-body/></div>` })
    .component('page-body', PageBody)
    .component('child_list', ChildList)
    .component('drug_admin', DrugAdmin)
    .component('referral_admin', ReferralAdmin)
    .component('icc_admin', IccAdmin)
    .mount('#app');
