/**
 * Mobilization / Dashboard — Vue 3 Composition API in place.
 * Three components — page-body, mobilization_dashboard,
 * lga_aggregate_mobilization_dashboard.
 *
 * Uses the <apexchart> wrapper from window.utils (registered automatically
 * by useApp). The Vue 2-only vue-apexcharts package is no longer referenced.
 */

const { ref, reactive, onMounted, onBeforeMount } = Vue;
const { useApp, useFormat, bus, safeMessage } = window.utils;

/* ------------------------------------------------------------------ */
/* page-body                                                            */
/* ------------------------------------------------------------------ */
const PageBody = {
    setup() {
        const page = ref('dashboard');
        function gotoPageHandler(data) { page.value = data.page; }
        onMounted(function () {
            const containers = document.querySelectorAll('.lgaAggregate');
            if (containers && typeof PerfectScrollbar !== 'undefined') {
                containers.forEach(function (el) { new PerfectScrollbar(el); });
            }
            bus.on('g-event-goto-page', gotoPageHandler);
        });
        return { page };
    },
    template: `
        <div>
            <div class="content-body">
                <div v-show="page == 'dashboard'">
                    <mobilization_dashboard/>
                    <lga_aggregate_mobilization_dashboard/>
                </div>
            </div>
        </div>
    `,
};

/* ------------------------------------------------------------------ */
/* mobilization_dashboard                                               */
/* ------------------------------------------------------------------ */
const MobilizationDashboard = {
    setup() {
        const fmtUtils = useFormat();

        const topMobilizationStat = reactive({ eNetcard: 0, hhMobilized: 0, familySize: 0 });
        const statData = reactive({ tableData: [], chartData: [], dataIndex: '' });
        const chartStates = ref(['hhMobilized', 'eNetcard', 'family_size']);
        const chartCurrentTab = ref(0);
        const chartFilter = reactive({
            lgaid: '', lgaName: '', wardid: '', wardName: '',
            dpid: '', dpName: '', date: '', chartLevel: 0,
        });
        const lgaMobilizationAggregate = reactive({
            mobilizationData: [], mobDateData: [],
            dateFilter: '', lgaIdFilter: 0, lgaNameFilter: '',
        });
        const series = ref([]);
        const allChartData = ref([]);
        const chartOptions = ref({ xaxis: { title: { text: '' } } });

        function convertStringNumberToFigures(d) {
            var data = d ? parseInt(d) : 0;
            return data ? data.toLocaleString() : 0;
        }
        function convertToDateMonthDay(date) {
            return new Date(date).toLocaleDateString('en-US', { month: 'short', day: '2-digit' });
        }
        function checkIfDateIsToday(date) {
            var today = new Date().toISOString().slice(0, 10);
            if (date === today) return 'Today';
            var parsed = new Date(date);
            return parsed.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: '2-digit' });
        }
        function isValidDate(dateString) {
            var regex = /^\d{4}-\d{2}-\d{2}$/;
            return regex.test(dateString) ? convertToDateMonthDay(dateString) : dateString;
        }
        function capitalizeWords(str) {
            if (typeof str !== 'string') return '';
            return str.toLowerCase().split(' ').map(function (w) {
                return w.charAt(0).toUpperCase() + w.slice(1);
            }).join(' ');
        }

        async function getTopListStatistics() {
            try {
                overlay.show();
                var response = await axios.get(common.DataService + '?qid=750');
                var allData = (response.data && response.data.data && response.data.data[0]) || {};
                topMobilizationStat.eNetcard = convertStringNumberToFigures(allData.netcards);
                topMobilizationStat.hhMobilized = convertStringNumberToFigures(allData.households);
                topMobilizationStat.familySize = convertStringNumberToFigures(allData.family_size);
                overlay.hide();
            } catch (error) {
                overlay.hide();
                alert.Error('ERROR', safeMessage(error));
            }
        }

        async function getDailyTopSummary() {
            try {
                overlay.show();
                var response = await axios.get(common.DataService + '?qid=751');
                var allData = response.data || {};
                statData.tableData = allData.table || [];
                statData.chartData = allData.chart || [];
                statData.chartData.xAxisLabel = 'Days';
                chartFilter.chartLevel = allData.level;
                if (Array.isArray(statData.chartData[1])) {
                    statData.chartData[1] = statData.chartData[1].map(function (item) {
                        return convertToDateMonthDay(item);
                    });
                }
                plotChart();
                overlay.hide();
            } catch (error) {
                overlay.hide();
                alert.Error('ERROR', safeMessage(error));
            }
        }

        function plotChart() {
            var yAxislabel = (statData.chartData[0] && statData.chartData[0][chartCurrentTab.value] && statData.chartData[0][chartCurrentTab.value].name) || '';
            var xAxisLabel = statData.chartData.xAxisLabel;
            chartOptions.value = {
                chart: { type: 'bar' },
                colors: '#7367f0',
                xaxis: {
                    categories: statData.chartData[1] || [],
                    title: { text: xAxisLabel, style: { color: '#6e6b7b', fontWeight: 'bold' }, offsetY: 10 },
                },
                yaxis: {
                    title: { text: yAxislabel, style: { color: '#6e6b7b', fontWeight: 'bold' } },
                    labels: { formatter: function (val) { return parseInt(val).toLocaleString(); } },
                },
                plotOptions: { bar: { dataLabels: { position: 'top' } } },
                dataLabels: {
                    enabled: true,
                    formatter: function (val) { return parseInt(val).toLocaleString(); },
                    offsetY: -20,
                    style: { fontSize: '12px', colors: ['#000'] },
                },
                noData: {
                    text: 'No data available, kindly refresh',
                    align: 'center', verticalAlign: 'middle', offsetX: 0, offsetY: 0,
                    style: { color: '#333', fontSize: '14px' },
                },
            };
            series.value = statData.chartData[0] ? [statData.chartData[0][chartCurrentTab.value]] : [];
        }

        function loadNewChart(d) {
            chartCurrentTab.value = d;
            plotChart();
        }

        function generateStatData(i) {
            if (i === undefined) i = '';
            getTopListStatistics();
            statData.dataIndex = i;
            var date = chartFilter.date, lgaid = chartFilter.lgaid, wardid = chartFilter.wardid, dpid = chartFilter.dpid;
            var rowData = statData.tableData[statData.dataIndex];
            if (!isNaN(i)) {
                if (!date && !lgaid && !wardid && !dpid) {
                    chartFilter.date = rowData.title;
                    getDailySummaryPerDate();
                } else if (date && !lgaid && !wardid && !dpid) {
                    chartFilter.lgaid = rowData.lgaid;
                    chartFilter.lgaName = rowData.title;
                    getDailySummaryPerWard();
                } else if (date && lgaid && (!wardid || wardid) && !dpid) {
                    chartFilter.wardName = rowData.title;
                    chartFilter.wardid = rowData.wardid;
                    if (chartFilter.chartLevel != 3) getDailySummaryPerDatePerDp();
                } else {
                    getDailyTopSummary();
                }
            } else {
                if (date && lgaid && !wardid && !dpid) getDailySummaryPerWard();
                else if (date && lgaid && wardid) getDailySummaryPerDatePerDp();
                else if (date && !lgaid && !wardid) getDailySummaryPerDate();
                else getDailyTopSummary();
            }
        }

        function refresh() {
            getTopListStatistics();
            if (chartFilter.chartLevel == 0) getDailyTopSummary();
            else if (chartFilter.chartLevel == 1) getDailySummaryPerDate();
            else if (chartFilter.chartLevel == 2) getDailySummaryPerWard();
            else if (chartFilter.chartLevel == 3) getDailySummaryPerDatePerDp();
            else getDailyTopSummary();
        }

        function dailyStatBreadCrum(state) {
            var sel = document.querySelector('.data-index-' + state);
            statData.dataIndex = sel ? sel.getAttribute('data-index') : '';
            if (state == 1) {
                chartFilter.lgaid = chartFilter.wardid = chartFilter.dpid = '';
                getDailySummaryPerDate();
            } else if (state == 2) {
                chartFilter.wardid = chartFilter.dpid = '';
                getDailySummaryPerWard();
            } else if (state == 3) {
                chartFilter.wardid = chartFilter.dpid = '';
                getDailySummaryPerDatePerDp();
            } else {
                chartFilter.date = chartFilter.lgaid = chartFilter.wardid = chartFilter.dpid = '';
                getDailyTopSummary();
            }
        }

        async function getDailySummaryPerDate() {
            await fetchData('753', { date: chartFilter.date, xAxisLabel: 'LGAS' });
            var list = document.querySelector('.data-index-1');
            if (list) list.setAttribute('data-index', statData.dataIndex);
        }
        async function getDailySummaryPerWard() {
            await fetchData('754', { date: chartFilter.date, lgaid: chartFilter.lgaid, xAxisLabel: 'Wards' });
            var list = document.querySelector('.data-index-2');
            if (list) list.setAttribute('data-index', statData.dataIndex);
        }
        async function getDailySummaryPerDatePerDp() {
            await fetchData('755', { date: chartFilter.date, wardid: chartFilter.wardid, xAxisLabel: 'DPs' });
        }

        async function fetchData(queryId, params) {
            try {
                overlay.show();
                var response = await axios.get(common.DataService + '?qid=' + queryId, { params: params });
                var allData = response.data || {};
                statData.tableData = allData.table || [];
                statData.chartData = allData.chart || [];
                statData.chartData.xAxisLabel = params.xAxisLabel;
                chartFilter.chartLevel = allData.level;
                plotChart();
                overlay.hide();
            } catch (error) {
                overlay.hide();
                alert.Error('ERROR', safeMessage(error));
            }
        }

        onBeforeMount(function () { getDailyTopSummary(); });
        onMounted(function () { getTopListStatistics(); });

        return {
            topMobilizationStat, statData, chartStates, chartCurrentTab, chartFilter,
            lgaMobilizationAggregate, series, allChartData, chartOptions,
            getTopListStatistics, getDailyTopSummary, plotChart, loadNewChart,
            convertToDateMonthDay, checkIfDateIsToday, isValidDate,
            convertStringNumberToFigures, generateStatData, refresh,
            dailyStatBreadCrum, getDailySummaryPerDate, getDailySummaryPerWard,
            getDailySummaryPerDatePerDp, fetchData, capitalizeWords,
            capitalize: fmtUtils.capitalize, formatNumber: fmtUtils.formatNumber,
        };
    },
    template: `
        <div>
            <div class="row">
                <div class="col-12 mb-2">
                    <h2 class="content-header-title header-txt float-left mb-0">Mobilization</h2>
                    <div class="breadcrumb-wrapper">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item active">Dashboard</li>
                        </ol>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-sm-12 col-md-12 col-lg-12 col-12">
                    <div class="row">
                        <div class="col-sm-6 col-md-4 col-lg-4 col-12">
                            <div class="card">
                                <div class="card-header d-flex align-items-center justify-content-between">
                                    <div>
                                        <h3 class="fw-bolder mb-75" v-text="topMobilizationStat.hhMobilized"></h3>
                                        <span class="card-text">HH Mobilized</span>
                                    </div>
                                    <div class="avatar bg-light-primary p-50 m-0"><div class="avatar-content"><i data-feather="home" class="font-medium-4"></i></div></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-4 col-lg-4 col-12">
                            <div class="card">
                                <div class="card-header d-flex align-items-center justify-content-between">
                                    <div>
                                        <h3 class="fw-bolder mb-75" v-text="topMobilizationStat.familySize"></h3>
                                        <span>Family Size</span>
                                    </div>
                                    <div class="avatar bg-light-primary p-50 m-0"><div class="avatar-content"><i data-feather="users" class="font-medium-4"></i></div></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-4 col-lg-4 col-12">
                            <div class="card">
                                <div class="card-header d-flex align-items-center justify-content-between">
                                    <div>
                                        <h3 class="fw-bolder mb-75" v-text="topMobilizationStat.eNetcard"></h3>
                                        <span>e-Netcard Issued</span>
                                    </div>
                                    <div class="avatar bg-light-primary p-50 m-0"><div class="avatar-content"><i data-feather="credit-card" class="font-medium-4"></i></div></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 mt-2 pl-0 pr-0 pb-0">
                    <div class="breadcrumb-wrapper reporting-dashboard d-flex justify-content-between">
                        <ol class="breadcrumb pt-75">
                            <li class="breadcrumb-item data-index-0" data-index="0" :class="chartFilter.chartLevel == 0 ? 'active' : ''" v-if="chartFilter.chartLevel >= 0" @click="dailyStatBreadCrum(0)">Daily mobilization Report</li>
                            <li class="breadcrumb-item data-index-1" data-index="" :class="chartFilter.chartLevel == 1 ? 'active' : ''" v-if="chartFilter.chartLevel >= 1" @click="dailyStatBreadCrum(1)"><span>{{ convertToDateMonthDay(chartFilter.date) }}</span>, LGAs Mobilization</li>
                            <li class="breadcrumb-item data-index-2" data-index="" :class="chartFilter.chartLevel == 2 ? 'active' : ''" v-if="chartFilter.chartLevel >= 2" @click="dailyStatBreadCrum(2)">{{ capitalizeWords(chartFilter.lgaName) + ' LGA, Wards' }}</li>
                            <li class="breadcrumb-item data-index-3" data-index="" :class="chartFilter.chartLevel == 3 ? 'active' : ''" v-if="chartFilter.chartLevel >= 3">{{ capitalizeWords(chartFilter.wardName) + ' Ward, DPs' }}</li>
                        </ol>
                        <div class="dropdown">
                            <button class="btn tb-primary" @click="refresh()" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="ti ti-refresh ti-sm text-muted"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-xl-6 mb-0">
                    <div class="card" style="height: 550px !important;">
                        <div class="table-responsive lgaAggregate">
                            <table class="table table-fixed border-top table-striped table-hover table-hover-animation" id="stat-table">
                                <thead class="border-bottom">
                                    <tr>
                                        <th colspan="2">{{ chartOptions.xaxis.title.text }}</th>
                                        <th>HH Mobilized</th>
                                        <th>e-Netcard Issued</th>
                                        <th>Family Size</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr @click="generateStatData(i)" v-for="(g, i) in statData.tableData" :key="g.title">
                                        <td style="padding-left: 1rem !important; padding-right: .2rem !important;"><i class="ti ti-circle-plus text-primary" v-if="chartFilter.chartLevel < 3"></i></td>
                                        <td style="padding-left: .4rem !important;">{{ isValidDate(capitalizeWords(g.title)) }}</td>
                                        <td>{{ convertStringNumberToFigures(g.households) }}</td>
                                        <td>{{ convertStringNumberToFigures(g.netcards) }}</td>
                                        <td>{{ convertStringNumberToFigures(g.family_size) }}</td>
                                    </tr>
                                    <tr v-if="statData.tableData.length == 0"><td class="text-center pt-4 pb-4" colspan="5"><small>No Data Available, Kindly Refresh</small></td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-xl-6 mb-0">
                    <div class="card" style="height: 550px !important;">
                        <div class="card-body">
                            <ul class="nav nav-tabs widget-nav-tabs pb-1 gap-4 mx-25 d-flex flex-nowrap" role="tablist">
                                <li class="nav-item" @click="loadNewChart(0)">
                                    <a href="javascript:void(0);" class="nav-link btn active d-flex flex-column align-items-center justify-content-center" role="tab" data-toggle="tab" data-target="#hh-mobilized-id" aria-controls="hh-mobilized-id" aria-selected="true">
                                        <div class="badge bg-label-secondary rounded p-50 pb-75"><i class="ti ti-home ti-sm"></i></div>
                                        <h6 class="tab-widget-title mb-0 mt-50">HH Mobilized</h6>
                                    </a>
                                </li>
                                <li class="nav-item" @click="loadNewChart(1)">
                                    <a href="javascript:void(0);" class="nav-link btn d-flex flex-column align-items-center justify-content-center" role="tab" data-toggle="tab" data-target="#e-netcard-issued-id" aria-controls="e-netcard-issued-id" aria-selected="false">
                                        <div class="badge bg-label-secondary rounded p-50 pb-75"><i class="ti ti-cash ti-sm"></i></div>
                                        <h6 class="tab-widget-title mb-0 mt-50">e-Netcard Issued</h6>
                                    </a>
                                </li>
                                <li class="nav-item" @click="loadNewChart(2)">
                                    <a href="javascript:void(0);" class="nav-link btn d-flex flex-column align-items-center justify-content-center" role="tab" data-toggle="tab" data-target="#family-size-id" aria-controls="family-size-id" aria-selected="false">
                                        <div class="badge bg-label-secondary rounded p-50 pb-75"><i class="ti ti-users ti-sm"></i></div>
                                        <h6 class="tab-widget-title mb-0 mt-50">Family Size</h6>
                                    </a>
                                </li>
                            </ul>
                            <div class="tab-content p-0 ms-0 ms-sm-2">
                                <div class="tab-pane fade show active" id="hh-mobilized-id" role="tabpanel">
                                    <apexchart height="410" type="bar" :options="chartOptions" :series="series"></apexchart>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `,
};

/* ------------------------------------------------------------------ */
/* lga_aggregate_mobilization_dashboard                                 */
/* ------------------------------------------------------------------ */
const LgaAggregateMobilizationDashboard = {
    setup() {
        const fmtUtils = useFormat();

        const statData = reactive({ tableData: [], chartData: [], dataIndex: '' });
        const chartCurrentTab = ref(0);
        const chartFilter = reactive({
            lgaid: '', lgaName: '', wardid: '', wardName: '',
            dpid: '', dpName: '', date: '', chartLevel: 0,
        });
        const series = ref([]);
        const allChartData = ref([]);
        const chartOptions = ref({ xaxis: { title: { text: '' } } });

        function convertStringNumberToFigures(d) {
            var data = d ? parseInt(d) : 0;
            return data ? data.toLocaleString() : 0;
        }
        function capitalizeWords(str) {
            if (typeof str !== 'string') return '';
            return str.toLowerCase().split(' ').map(function (w) {
                return w.charAt(0).toUpperCase() + w.slice(1);
            }).join(' ');
        }

        async function getAggregateByLocation() {
            try {
                overlay.show();
                var response = await axios.get(common.DataService + '?qid=752');
                var allData = response.data || {};
                statData.tableData = allData.table || [];
                statData.chartData = allData.chart || [];
                statData.chartData.xAxisLabel = 'LGAs';
                chartFilter.chartLevel = allData.level;
                plotAggregateChart();
                overlay.hide();
            } catch (error) {
                overlay.hide();
                alert.Error('ERROR', safeMessage(error));
            }
        }

        function plotAggregateChart() {
            var yAxislabel = (statData.chartData[0] && statData.chartData[0][chartCurrentTab.value] && statData.chartData[0][chartCurrentTab.value].name) || '';
            var xAxisLabel = statData.chartData.xAxisLabel;
            chartOptions.value = {
                chart: { type: 'bar' },
                colors: '#7367f0',
                xaxis: {
                    categories: statData.chartData[1] || [],
                    title: { text: xAxisLabel, style: { color: '#6e6b7b', fontWeight: 'bold' }, offsetY: 10 },
                },
                yaxis: {
                    title: { text: yAxislabel, style: { color: '#6e6b7b', fontWeight: 'bold' } },
                    labels: { formatter: function (val) { return parseInt(val).toLocaleString(); } },
                },
                plotOptions: { bar: { dataLabels: { position: 'top' } } },
                dataLabels: {
                    enabled: true,
                    formatter: function (val) { return parseInt(val).toLocaleString(); },
                    offsetY: -20,
                    style: { fontSize: '12px', colors: ['#000'] },
                },
                noData: {
                    text: 'No data available, kindly refresh',
                    align: 'center', verticalAlign: 'middle', offsetX: 0, offsetY: 0,
                    style: { color: '#333', fontSize: '14px' },
                },
            };
            series.value = statData.chartData[0] ? [statData.chartData[0][chartCurrentTab.value]] : [];
        }

        function loadAggregateNewChart(d) {
            chartCurrentTab.value = d;
            plotAggregateChart();
        }

        function generateAggregateStatData(i) {
            if (i === undefined) i = '';
            statData.dataIndex = i;
            var lgaid = chartFilter.lgaid, wardid = chartFilter.wardid, dpid = chartFilter.dpid;
            var rowData = statData.tableData[statData.dataIndex];
            if (!isNaN(i)) {
                if (!lgaid && !wardid && !dpid) {
                    chartFilter.lgaid = rowData.lgaid;
                    chartFilter.lgaName = rowData.title;
                    getAggregateSummaryPerWard();
                } else if (lgaid && !wardid && !dpid) {
                    chartFilter.wardid = rowData.wardid;
                    chartFilter.wardName = rowData.title;
                    getAggregateSummaryPerDp();
                } else if (wardid && !dpid) {
                    chartFilter.dpName = rowData.title;
                    chartFilter.dpid = rowData.dpid;
                    getAggregateSummaryPerDp();
                }
            } else {
                if (lgaid && !wardid && !dpid) getAggregateSummaryPerWard();
                else if (wardid) getAggregateSummaryPerDp();
                else if (!lgaid && !wardid) getAggregateByLocation();
            }
        }

        function refreshAggregatePage() {
            if (chartFilter.chartLevel == 0) getAggregateByLocation();
            else if (chartFilter.chartLevel == 1) getAggregateSummaryPerWard();
            else if (chartFilter.chartLevel == 2) getAggregateSummaryPerDp();
            else getAggregateByLocation();
        }

        function aggregateStatBreadCrum(state) {
            var sel = document.querySelector('.data-index-' + state);
            statData.dataIndex = sel ? sel.getAttribute('data-index') : '';
            if (state == 1) {
                chartFilter.wardid = chartFilter.dpid = '';
                getAggregateSummaryPerWard();
            } else if (state == 2) {
                chartFilter.wardid = chartFilter.dpid = '';
                getAggregateSummaryPerDp();
            } else {
                chartFilter.lgaid = chartFilter.wardid = chartFilter.dpid = '';
                getAggregateByLocation();
            }
        }

        async function getAggregateSummaryPerWard() {
            await fetchData('756', { lgaid: chartFilter.lgaid, xAxisLabel: 'Wards' });
            var list = document.querySelector('.data-index-1');
            if (list) list.setAttribute('data-index', statData.dataIndex);
        }
        async function getAggregateSummaryPerDp() {
            await fetchData('757', { wardid: chartFilter.wardid, xAxisLabel: 'DPs' });
        }

        async function fetchData(queryId, params) {
            try {
                overlay.show();
                var response = await axios.get(common.DataService + '?qid=' + queryId, { params: params });
                var allData = response.data || {};
                statData.tableData = allData.table || [];
                statData.chartData = allData.chart || [];
                statData.chartData.xAxisLabel = params.xAxisLabel;
                chartFilter.chartLevel = allData.level;
                plotAggregateChart();
                overlay.hide();
            } catch (error) {
                overlay.hide();
                alert.Error('ERROR', safeMessage(error));
            }
        }

        onBeforeMount(function () { getAggregateByLocation(); });
        onMounted(function () { getAggregateByLocation(); });

        return {
            statData, chartCurrentTab, chartFilter, series, allChartData, chartOptions,
            getAggregateByLocation, plotAggregateChart, loadAggregateNewChart,
            convertStringNumberToFigures, generateAggregateStatData,
            refreshAggregatePage, aggregateStatBreadCrum,
            getAggregateSummaryPerWard, getAggregateSummaryPerDp, fetchData,
            capitalizeWords,
            capitalize: fmtUtils.capitalize, formatNumber: fmtUtils.formatNumber,
        };
    },
    template: `
        <div>
            <div class="row">
                <div class="col-12 mt-1 pl-0 pr-0 pb-0">
                    <div class="breadcrumb-wrapper reporting-dashboard d-flex justify-content-between">
                        <ol class="breadcrumb pt-75">
                            <li class="breadcrumb-item data-index-0" data-index="0" :class="chartFilter.chartLevel == 0 ? 'active' : ''" v-if="chartFilter.chartLevel >= 0" @click="aggregateStatBreadCrum(0)">LGA Mobilization Report</li>
                            <li class="breadcrumb-item data-index-1" data-index="" :class="chartFilter.chartLevel == 1 ? 'active' : ''" v-if="chartFilter.chartLevel >= 1" @click="aggregateStatBreadCrum(1)"><span>{{ capitalizeWords(chartFilter.lgaName) }}</span> LGA, Wards Mobilization</li>
                            <li class="breadcrumb-item data-index-2" data-index="" :class="chartFilter.chartLevel == 2 ? 'active' : ''" v-if="chartFilter.chartLevel >= 2" @click="aggregateStatBreadCrum(2)">{{ capitalizeWords(chartFilter.wardName) + ' Ward, Dps' }}</li>
                        </ol>
                        <div class="dropdown">
                            <button class="btn tb-primary" @click="refreshAggregatePage()" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="ti ti-refresh ti-sm text-muted"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-xl-6 mb-4">
                    <div class="card" style="height: 550px !important;">
                        <div class="table-responsive lgaAggregate table-wrapper">
                            <table class="table table-fixed border-top table-striped table-hover table-hover-animation" id="stat-table">
                                <thead class="border-bottom">
                                    <tr>
                                        <th colspan="2">{{ chartOptions.xaxis.title.text }}</th>
                                        <th>HH Mobilized</th>
                                        <th>e-Netcard Issued</th>
                                        <th>Family Size</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr @click="generateAggregateStatData(i)" v-for="(g, i) in statData.tableData" :key="g.title">
                                        <td style="padding-left: 1rem !important; padding-right: .2rem !important;"><i class="ti ti-circle-plus text-primary" v-if="chartFilter.chartLevel < 2"></i></td>
                                        <td style="padding-left: .4rem !important;">{{ capitalizeWords(g.title) }}</td>
                                        <td>{{ convertStringNumberToFigures(g.households) }}</td>
                                        <td>{{ convertStringNumberToFigures(g.netcards) }}</td>
                                        <td>{{ convertStringNumberToFigures(g.family_size) }}</td>
                                    </tr>
                                    <tr v-if="statData.tableData.length == 0"><td class="text-center pt-4 pb-4" colspan="5"><small>No Data Available, Kindly Refresh</small></td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-xl-6 mb-4">
                    <div class="card" style="height: 550px !important;">
                        <div class="card-body">
                            <ul class="nav nav-tabs widget-nav-tabs pb-1 gap-4 mx-25 d-flex flex-nowrap" role="tablist">
                                <li class="nav-item" @click="loadAggregateNewChart(0)">
                                    <a href="javascript:void(0);" class="nav-link btn active d-flex flex-column align-items-center justify-content-center" role="tab" data-toggle="tab" data-target="#hh-mobilized-id-aggregate" aria-controls="hh-mobilized-id-aggregate" aria-selected="true">
                                        <div class="badge bg-label-secondary rounded p-50 pb-75"><i class="ti ti-home ti-sm"></i></div>
                                        <h6 class="tab-widget-title mb-0 mt-50">HH Mobilized</h6>
                                    </a>
                                </li>
                                <li class="nav-item" @click="loadAggregateNewChart(1)">
                                    <a href="javascript:void(0);" class="nav-link btn d-flex flex-column align-items-center justify-content-center" role="tab" data-toggle="tab" data-target="#e-netcard-issued-id-aggregate" aria-controls="e-netcard-issued-id-aggregate" aria-selected="false">
                                        <div class="badge bg-label-secondary rounded p-50 pb-75"><i class="ti ti-cash ti-sm"></i></div>
                                        <h6 class="tab-widget-title mb-0 mt-50">e-Netcard Issued</h6>
                                    </a>
                                </li>
                                <li class="nav-item" @click="loadAggregateNewChart(2)">
                                    <a href="javascript:void(0);" class="nav-link btn d-flex flex-column align-items-center justify-content-center" role="tab" data-toggle="tab" data-target="#family-size-id-aggregate" aria-controls="family-size-id-aggregate" aria-selected="false">
                                        <div class="badge bg-label-secondary rounded p-50 pb-75"><i class="ti ti-users ti-sm"></i></div>
                                        <h6 class="tab-widget-title mb-0 mt-50">Family Size</h6>
                                    </a>
                                </li>
                            </ul>
                            <div class="tab-content p-0 ms-0 ms-sm-2">
                                <div class="tab-pane fade show active" id="hh-mobilized-id-aggregate" role="tabpanel">
                                    <apexchart height="410" type="bar" :options="chartOptions" :series="series"></apexchart>
                                </div>
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
    .component('mobilization_dashboard', MobilizationDashboard)
    .component('lga_aggregate_mobilization_dashboard', LgaAggregateMobilizationDashboard)
    .mount('#app');
