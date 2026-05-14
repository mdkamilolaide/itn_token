/**
 * Distribution / Dashboard — Vue 3 Composition API in place.
 * Four components: page-body, distribution_general_stats,
 * distribution_lga_aggregate_table, daily_aggregate_table.
 *
 * Drill state lives in module-local appState (Vue.reactive). The bar
 * chart uses the shared <apexchart> wrapper from window.utils — chart
 * re-renders automatically when chartOptions/series change (the wrapper
 * does destroy+re-init on prop changes, so manual updateSeries/
 * updateOptions ref calls aren't needed).
 */

const { ref, reactive, computed, onMounted, onBeforeUnmount } = Vue;
const { useApp, useFormat, bus, safeMessage } = window.utils;

const appState = Vue.reactive({
    currentDrillData: '',
    aggregate: { page: '', title: '', lgaId: '', lgaName: '', wardId: '', wardName: '', date: '', chartTitle: 'Daily Summary Chart' },
    dailyAggregate: { page: '', title: '', lgaId: '', lgaName: '', wardId: '', wardName: '', date: '', chartTitle: 'Daily Summary Chart' },
});

const PageBody = {
    setup() {
        const refreshAllData = () => { bus.emit('g-event-refresh-page', { page: '', distributionPage: '' }); };
        return { appState, refreshAllData };
    },
    template: `
        <div>
            <div class="content-header row">
                <div class="content-header-left col-sm-8 col-md-9 col-8 mb-2">
                    <div class="row breadcrumbs-top">
                        <div class="col-12">
                            <h2 class="content-header-title header-txt float-left mb-0">Distribution</h2>
                            <div class="breadcrumb-wrapper">
                                <ol class="breadcrumb"><li class="breadcrumb-item active">Dashboard</li></ol>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="content-header-right text-right col-sm-4 col-md-3 col-4 d-md-block d-sm-block mb-2">
                    <button @click="refreshAllData()" class="btn-icon btn btn-primary btn-round btn-sm" type="button"><i data-feather="refresh-cw"></i></button>
                </div>
            </div>
            <distribution_general_stats/>
            <distribution_lga_aggregate_table/>
            <daily_aggregate_table/>
        </div>
    `,
};

const DistributionGeneralStats = {
    setup() {
        const fmtUtils = useFormat();
        const allStatistics = ref({});

        const refreshData = () => {
            overlay.show();
            Promise.all([
                fetchData('403', data => { allStatistics.value = data || {}; }),
            ]).finally(() => { overlay.hide(); });
        }
        const fetchData = (qid, onSuccess) => {
            return axios.get(common.DataService + '?qid=' + qid)
                .then(response => {
                    var data = (response.data && response.data.data && response.data.data[0]) || {};
                    onSuccess(data);
                })
                .catch(error => { console.error('Error fetching qid=' + qid + ' data:', error); });
        }
        const refreshDataHandler = () => { refreshData(); };
        const goBack = (data) => {
            appState.currentDrillData = 'aggregate_lga';
            appState.aggregate.title = data && data.title;
            appState.aggregate.page = data && data.page;
            bus.emit('g-event-goto-page', data);
        }

        onMounted(() => {
            bus.on('g-event-refresh-page', refreshDataHandler);
            refreshData();
        });
        onBeforeUnmount(() => {
            bus.off('g-event-refresh-page', refreshDataHandler);
        });

        return {
            appState, allStatistics, refreshData, goBack,
            formatNumber: fmtUtils.formatNumber,
            percentageUsed: fmtUtils.percentageUsed,
            progressBarWidth: fmtUtils.progressBarWidth,
            progressBarStatus: fmtUtils.progressBarStatus,
        };
    },
    template: `
        <div class="content-header row">
            <div class="col-12 col-lg-6 col-md-6 col-sm-12">
                <div class="card">
                    <div class="card-widget-separator-wrapper">
                        <div class="card-body pb-75 pt-75 card-widget-separator">
                            <div class="row gy-4 gy-sm-1 pr-sm-custom-0">
                                <div class="col-sm-6 col-lg-4">
                                    <div class="d-flex justify-content-between align-items-start card-widget-1 border-end pb-sm-0">
                                        <div>
                                            <h6 class="mb-50 small text-primary">HH Mobilized</h6>
                                            <h4 class="mb-0">{{ formatNumber(allStatistics.household_mobilized) }}</h4>
                                        </div>
                                        <span class="avatar1 me-sm-4"><span class="avatar-initial bg-label-primary rounded"><i class="ti-md ti ti-home-share"></i></span></span>
                                    </div>
                                </div>
                                <div class="col-sm-6 col-lg-4">
                                    <div class="d-flex justify-content-between align-items-start card-widget-2 border-end pb-sm-0">
                                        <div>
                                            <h6 class="mb-50 small text-primary">HH Redeemed</h6>
                                            <h4 class="mb-0">{{ formatNumber(allStatistics.household_redeemed) }}</h4>
                                        </div>
                                        <span class="avatar1 me-sm-4 second"><span class="avatar-initial bg-label-success rounded"><i class="ti-md ti ti-home-check"></i></span></span>
                                    </div>
                                </div>
                                <div class="col-sm-12 col-lg-4">
                                    <hr class="d-none d-sm-block d-lg-none">
                                    <div class="d-flex justify-content-between align-items-start card-widget-1 pb-sm-0">
                                        <div>
                                            <h6 class="mb-50 small text-primary">Redemption Rate</h6>
                                            <h4 class="mb-0">{{ percentageUsed(allStatistics.household_mobilized, allStatistics.household_redeemed) }}%</h4>
                                        </div>
                                        <hr class="d-none d-sm-block d-lg-none me-4">
                                        <span class="avatar1 me-sm-4 end"><span class="avatar-initial bg-label-info rounded"><i class="ti-md ti ti-percentage"></i></span></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-6 col-md-6 col-sm-12">
                <div class="card">
                    <div class="card-widget-separator-wrapper">
                        <div class="card-body pb-75 pt-75 card-widget-separator">
                            <div class="row gy-4 gy-sm-1 pr-sm-custom-0">
                                <div class="col-sm-6 col-lg-4">
                                    <div class="d-flex justify-content-between align-items-start card-widget-1 border-end pb-sm-0">
                                        <div>
                                            <h6 class="mb-50 small text-primary">Family Size Mobilized</h6>
                                            <h4 class="mb-0">{{ formatNumber(allStatistics.familysize_mobilized) }}</h4>
                                        </div>
                                        <span class="avatar1 me-sm-4"><span class="avatar-initial bg-label-primary rounded"><i class="ti-md ti ti-users-group"></i></span></span>
                                    </div>
                                </div>
                                <div class="col-sm-6 col-lg-4">
                                    <div class="d-flex justify-content-between align-items-start card-widget-2 border-end pb-sm-0">
                                        <div>
                                            <h6 class="mb-50 small text-primary">Family Size Redeemed</h6>
                                            <h4 class="mb-0">{{ formatNumber(allStatistics.familysize_redeemed) }}</h4>
                                        </div>
                                        <span class="avatar1 me-sm-4 second"><span class="avatar-initial bg-label-success rounded"><i class="ti-md ti ti-users-group"></i></span></span>
                                    </div>
                                </div>
                                <div class="col-sm-12 col-lg-4">
                                    <hr class="d-none d-sm-block d-lg-none">
                                    <div class="d-flex justify-content-between align-items-start card-widget-1 pb-sm-0">
                                        <div>
                                            <h6 class="mb-50 small text-primary">Redemption Rate</h6>
                                            <h4 class="mb-0">{{ percentageUsed(allStatistics.familysize_mobilized, allStatistics.familysize_redeemed) }}%</h4>
                                        </div>
                                        <hr class="d-none d-sm-block d-lg-none me-4">
                                        <span class="avatar1 me-sm-4 end"><span class="avatar-initial bg-label-info rounded"><i class="ti-md ti ti-percentage"></i></span></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-12 col-md-12 col-sm-12">
                <div class="card">
                    <div class="card-widget-separator-wrapper">
                        <div class="card-body pb-75 pt-75 card-widget-separator">
                            <div class="row gy-4 gy-sm-1">
                                <div class="col-sm-6 col-lg-4 pr-sm-custom-0">
                                    <div class="d-flex justify-content-between align-items-start card-widget-1 border-end pb-sm-0">
                                        <div>
                                            <h6 class="mb-50 small text-primary">Net Mobilized</h6>
                                            <h4 class="mb-0">{{ formatNumber(allStatistics.net_issued) }}</h4>
                                        </div>
                                        <span class="avatar1 me-sm-4"><span class="avatar-initial bg-label-primary rounded"><i class="ti-md ti ti-brand-netbeans"></i></span></span>
                                    </div>
                                </div>
                                <div class="col-sm-6 col-lg-4">
                                    <div class="d-flex justify-content-between align-items-start card-widget-2 border-end pb-sm-0">
                                        <div>
                                            <h6 class="mb-50 small text-primary">Net Redeemed</h6>
                                            <h4 class="mb-0">{{ formatNumber(allStatistics.net_redeemed) }}</h4>
                                        </div>
                                        <span class="avatar1 me-sm-4 second"><span class="avatar-initial bg-label-success rounded"><i class="ti-md ti ti-brand-netbeans"></i></span></span>
                                    </div>
                                </div>
                                <div class="col-sm-12 col-lg-4">
                                    <hr class="d-none d-sm-block d-lg-none">
                                    <div class="d-flex justify-content-between align-items-start card-widget-1 pb-sm-0">
                                        <div>
                                            <h6 class="mb-50 small text-primary">Redemption Rate</h6>
                                            <h4 class="mb-0">{{ percentageUsed(allStatistics.net_issued, allStatistics.net_redeemed) }}%</h4>
                                        </div>
                                        <hr class="d-none d-sm-block d-lg-none me-4">
                                        <span class="avatar1 me-sm-4 end"><span class="avatar-initial bg-label-info rounded"><i class="ti-md ti ti-percentage"></i></span></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="breadcrumb-wrapper reporting-dashboard">
                    <ol class="breadcrumb pl-0">
                        <li class="breadcrumb-item" @click="goBack({page: '', title: 'LGA' })" :class="appState.aggregate.page == '' ? 'active' : ''" v-if="appState.aggregate.page == 'ward_summary' || appState.aggregate.page == 'dp_summary' || appState.aggregate.page == ''">LGA Aggregate</li>
                        <li class="breadcrumb-item" @click="goBack({page: 'ward_summary', title: 'Ward' })" :class="appState.aggregate.page == 'ward_summary' ? 'active' : ''" v-if="appState.aggregate.page == 'ward_summary' || appState.aggregate.page == 'dp_summary'">{{ appState.aggregate.lgaName }}</li>
                        <li class="breadcrumb-item" :class="appState.aggregate.page == 'dp_summary' ? 'active' : ''" v-if="appState.aggregate.page == 'dp_summary'">{{ appState.aggregate.wardName }}</li>
                    </ol>
                </div>
            </div>
        </div>
    `,
};

const DistributionLgaAggregateTable = {
    setup() {
        const fmtUtils = useFormat();
        const tableData = ref([]);
        var psInstances = [];

        const fetchData = (qid, onSuccess) => {
            return axios.get(common.DataService + '?qid=' + qid)
                .then(response => {
                    onSuccess((response.data && response.data.data) || []);
                })
                .catch(error => { console.error('Error fetching qid=' + qid + ' data:', error); });
        }
        const refreshData = () => {
            overlay.show();
            var qid = '';
            if (appState.aggregate.title === 'LGA' || appState.aggregate.title === '') qid = '404a';
            else if (appState.aggregate.title === 'Ward') qid = '404b&lgaId=' + appState.aggregate.lgaId;
            else if (appState.aggregate.title === 'DP') qid = '404c&wardId=' + appState.aggregate.wardId;
            Promise.all([
                fetchData(qid, data => { tableData.value = data; }),
            ]).finally(() => { overlay.hide(); });
        }
        const refreshDataHandler = () => { refreshData(); };
        const gotoPageHandler = () => {
            if (appState.currentDrillData == 'aggregate_lga') refreshData();
        }
        const goToWardSummaryPage = (data) => {
            appState.aggregate.title = 'Ward';
            appState.aggregate.lgaId = data && data.lgaId;
            appState.aggregate.lgaName = data && data.lgaName;
            appState.aggregate.page = data && data.page;
            refreshData();
        }
        const goToDPSummaryPage = (data) => {
            appState.aggregate.title = 'DP';
            appState.aggregate.wardId = data && data.wardId;
            appState.aggregate.wardName = data && data.wardName;
            appState.aggregate.page = data && data.page;
            refreshData();
        }
        const handleRowClick = (g) => {
            appState.currentDrillData = 'aggregate_lga';
            if (appState.aggregate.title === 'LGA') {
                goToWardSummaryPage({ lgaId: g.id, lgaName: g.title, page: 'ward_summary' });
            } else if (appState.aggregate.title === 'Ward') {
                goToDPSummaryPage({ wardId: g.id, wardName: g.title, page: 'dp_summary' });
            }
        }

        onMounted(() => {
            bus.on('g-event-goto-page', gotoPageHandler);
            bus.on('g-event-refresh-page', refreshDataHandler);
            try {
                var containers = document.querySelectorAll('.distribution-lga-perfect-scroll-grid');
                containers.forEach(el => { psInstances.push(new PerfectScrollbar(el)); });
            } catch (e) {}
            appState.aggregate.title = 'LGA';
            refreshData();
        });
        onBeforeUnmount(() => {
            bus.off('g-event-goto-page', gotoPageHandler);
            bus.off('g-event-refresh-page', refreshDataHandler);
            psInstances.forEach(ps => { try { ps.destroy(); } catch (e) {} });
            psInstances = [];
        });

        return {
            appState, tableData, refreshData,
            goToWardSummaryPage, goToDPSummaryPage, handleRowClick,
            formatNumber: fmtUtils.formatNumber,
            progressBarWidth: fmtUtils.progressBarWidth,
            progressBarStatus: fmtUtils.progressBarStatus,
        };
    },
    template: `
        <div class="content-header row">
            <div class="col-12 mb-1">
                <div class="card">
                    <div class="table-responsive scrollBox distribution-lga-perfect-scroll-grid" style="height: 420px !important; overflow: hidden;">
                        <table class="table table-fixed border-top table-hover">
                            <thead style="position: sticky; top: 0; background: #fff; z-index: 2;">
                                <tr>
                                    <th>{{ appState.aggregate.title }}</th>
                                    <th class="bg-light-primary-1 px-1">HH Mobilized</th>
                                    <th class="bg-light-success-1 px-1">HH Redeemed</th>
                                    <th class="bg-light-primary-1 px-1">Family Size Mobilized</th>
                                    <th class="bg-light-success-1 px-1">Family Size Redeemed</th>
                                    <th class="bg-light-primary-1 px-1">Net Mobilized</th>
                                    <th class="bg-light-success-1 px-1">Net Redeemed</th>
                                    <th class="px-75">Net Rate</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template v-if="tableData.length">
                                    <tr v-for="g in tableData" :key="g.id" @click="handleRowClick(g)">
                                        <td>
                                            <i v-if="appState.aggregate.title !== 'DP'" class="ti ti-circle-plus text-primary mr-2"></i>
                                            {{ g.title }} {{ appState.aggregate.title }}
                                        </td>
                                        <td class="bg-light-primary-1 px-1">{{ formatNumber(g.household_mobilized) }}</td>
                                        <td class="bg-light-success-1 px-1">{{ formatNumber(g.household_redeemed) }}</td>
                                        <td class="bg-light-primary-1 px-1">{{ formatNumber(g.familysize_mobilized) }}</td>
                                        <td class="bg-light-success-1 px-1">{{ formatNumber(g.familysize_redeemed) }}</td>
                                        <td class="bg-light-primary-1 px-1">{{ formatNumber(g.net_issued) }}</td>
                                        <td class="bg-light-success-1 px-1">{{ formatNumber(g.net_redeemed) }}</td>
                                        <td class="px-75 pt-25 bg-progress">
                                            <small class="text-heading d-flex">{{ progressBarWidth(g.net_issued, g.net_redeemed) }}</small>
                                            <div class="d-flex font-small-1 align-items-center">
                                                <div class="progress w-100 me-3" style="height: 6px;">
                                                    <div class="progress-bar" :class="progressBarStatus(g.net_issued, g.net_redeemed)" :style="{ width: progressBarWidth(g.net_issued, g.net_redeemed) }"></div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                                <tr v-else>
                                    <td colspan="8" class="text-center pt-2"><small>No Data</small></td>
                                </tr>
                            </tbody>
                        </table>
                        <div class="mb-2"></div>
                    </div>
                </div>
            </div>
        </div>
    `,
};

const DailyAggregateTable = {
    setup() {
        const fmtUtils = useFormat();
        const tableData = ref([]);
        const isLoading = ref(false);
        const chartData = ref([]);
        var psInstances = [];

        const fetchData = (qid, onSuccess) => {
            return axios.get(common.DataService + '?qid=' + qid)
                .then(response => { onSuccess(response.data || []); })
                .catch(error => { console.error('Error fetching qid=' + qid + ' data:', error); });
        }
        const refreshData = () => {
            isLoading.value = true;
            overlay.show();
            var title = appState.dailyAggregate.title;
            var date = appState.dailyAggregate.date;
            var lgaId = appState.dailyAggregate.lgaId;
            var wardId = appState.dailyAggregate.wardId;
            var qid;
            switch (title) {
                case 'Ward': qid = '405c&lgaId=' + lgaId + '&date=' + date; break;
                case 'Dp':   qid = '405d&wardId=' + wardId + '&date=' + date; break;
                case 'LGA':  qid = '405b&date=' + date; break;
                default:     qid = '405a';
            }
            fetchData(qid, data => {
                tableData.value = (data && data.data) || [];
                chartData.value = (data && data.chart) || [];
            }).catch(error => {
                alert.Error('ERROR', safeMessage(error));
            }).finally(() => {
                isLoading.value = false;
                overlay.hide();
            });
        }
        const refreshDataHandler = () => { refreshData(); };
        const gotoPageHandler = () => {
            if (appState.currentDrillData == 'dailyAggregate_lga') refreshData();
        }

        const goToTopSummaryPage = (data) => {
            appState.dailyAggregate.title = 'LGA';
            appState.dailyAggregate.date = data && data.date;
            appState.dailyAggregate.page = data && data.page;
            appState.dailyAggregate.chartTitle = fmtUtils.displayDate(data && data.date) + ' Summary';
            refreshData();
        }
        const goToLGASummaryPage = (data) => {
            appState.dailyAggregate.title = 'Ward';
            appState.dailyAggregate.lgaId = data && data.lgaId;
            appState.dailyAggregate.lgaName = data && data.lgaName;
            appState.dailyAggregate.page = data && data.page;
            appState.dailyAggregate.chartTitle = (data && data.lgaName) + ' Summary';
            refreshData();
        }
        const goToWardSummaryPage = (data) => {
            appState.dailyAggregate.title = 'Dp';
            appState.dailyAggregate.wardId = data && data.wardId;
            appState.dailyAggregate.wardName = data && data.wardName;
            appState.dailyAggregate.page = data && data.page;
            appState.dailyAggregate.chartTitle = (data && data.wardName) + ' Summary';
            refreshData();
        }
        const handleRowClick = (g) => {
            appState.currentDrillData = 'dailyAggregate_lga';
            var title = appState.dailyAggregate.title;
            if (title === 'Date') goToTopSummaryPage({ date: g.title, page: 'top_summary' });
            else if (title === 'LGA') goToLGASummaryPage({ lgaId: g.id, lgaName: g.title, page: 'lga_summary' });
            else if (title === 'Ward') goToWardSummaryPage({ wardId: g.id, wardName: g.title, lgaId: g.id, lgaName: g.title, page: 'ward_summary' });
        }
        const goBack = (data) => {
            appState.dailyAggregate.title = data && data.title;
            appState.dailyAggregate.page = data && data.page;
            appState.dailyAggregate.chartTitle = data && data.chartTitle;
            appState.currentDrillData = 'dailyAggregate_lga';
            bus.emit('g-event-goto-page', data);
        }

        const series = computed(() => chartData.value && chartData.value[0] ? chartData.value[0] : []);
        const chartOptions = computed(() => {
            var categories = (chartData.value && chartData.value[1]) || [];
            var isDark = document.documentElement.classList.contains('dark-layout');
            var color = isDark ? '#d0d2d6' : '#212121';
            return {
                chart: { id: 'daily-aggregate', type: 'bar', offsetX: 0 },
                colors: ['#53d2dc', '#3196e2', '#ff826c', '#ffc05f'],
                legend: { show: true, position: 'top', horizontalAlign: 'start' },
                grid: { show: false },
                xaxis: {
                    axisBorder: { show: true },
                    categories: categories,
                    title: { style: { color: '#6e6b7b', fontWeight: 'bold' }, offsetY: 10 },
                    axisTicks: { show: false },
                },
                fill: { opacity: 1, type: 'solid' },
                tooltip: { shared: false },
                yaxis: {
                    axisBorder: { show: false },
                    axisTicks: { show: false },
                    title: { style: { color: '#6e6b7b', fontWeight: 'bold' } },
                    labels: { formatter: (val) => { return parseInt(val).toLocaleString(); }, show: false },
                },
                plotOptions: { bar: { dataLabels: { position: 'top' } } },
                dataLabels: {
                    enabled: true,
                    formatter: (val) => { return parseInt(val).toLocaleString(); },
                    distributed: true, offsetY: -20,
                    style: { fontSize: '12px', colors: [color] },
                },
                noData: {
                    text: 'No data available, kindly refresh',
                    align: 'center', verticalAlign: 'middle',
                    offsetX: 0, offsetY: 0,
                    style: { color: '#333', fontSize: '14px' },
                },
            };
        });

        onMounted(() => {
            bus.on('g-event-goto-page', gotoPageHandler);
            bus.on('g-event-refresh-page', refreshDataHandler);
            try {
                var containers = document.querySelectorAll('.daily-aggregate-perfect-scroll-grid');
                containers.forEach(el => { psInstances.push(new PerfectScrollbar(el)); });
            } catch (e) {}
            appState.dailyAggregate.title = 'Date';
            appState.dailyAggregate.chartTitle = 'Daily Summary';
            refreshData();
        });
        onBeforeUnmount(() => {
            bus.off('g-event-goto-page', gotoPageHandler);
            bus.off('g-event-refresh-page', refreshDataHandler);
            psInstances.forEach(ps => { try { ps.destroy(); } catch (e) {} });
            psInstances = [];
        });

        return {
            appState, tableData, isLoading, chartData,
            series, chartOptions,
            refreshData, goToTopSummaryPage, goToLGASummaryPage,
            goToWardSummaryPage, handleRowClick, goBack,
            formatNumber: fmtUtils.formatNumber,
            displayDate: fmtUtils.displayDate,
            capitalizeEachWords: fmtUtils.capitalizeEachWords,
        };
    },
    template: `
        <div class="content-header row" v-cloak>
            <div class="col-12 mt-2">
                <div class="breadcrumb-wrapper reporting-dashboard">
                    <ol class="breadcrumb pl-0">
                        <li class="breadcrumb-item" @click="goBack({page: '', title: 'Date', chartTitle: 'Daily Summary' })" :class="appState.dailyAggregate.page == '' ? 'active' : ''" v-if="appState.dailyAggregate.page == '' || appState.dailyAggregate.page == 'top_summary' || appState.dailyAggregate.page == 'lga_summary' || appState.dailyAggregate.page == 'ward_summary'">Daily Summary</li>
                        <li class="breadcrumb-item" @click="goBack({page: 'top_summary', title: 'LGA', chartTitle: displayDate(appState.dailyAggregate.date) })" :class="appState.dailyAggregate.page == 'top_summary' ? 'active' : ''" v-if="appState.dailyAggregate.page == 'top_summary' || appState.dailyAggregate.page == 'lga_summary' || appState.dailyAggregate.page == 'ward_summary'">{{ displayDate(appState.dailyAggregate.date) }}</li>
                        <li class="breadcrumb-item" @click="goBack({page: 'lga_summary', title: 'Ward', chartTitle: capitalizeEachWords(appState.dailyAggregate.lgaName) })" :class="appState.dailyAggregate.page == 'lga_summary' ? 'active' : ''" v-if="appState.dailyAggregate.page == 'lga_summary' || appState.dailyAggregate.page == 'ward_summary'">{{ capitalizeEachWords(appState.dailyAggregate.lgaName) }}</li>
                        <li class="breadcrumb-item" :class="appState.dailyAggregate.page == 'ward_summary' ? 'active' : ''" v-if="appState.dailyAggregate.page == 'ward_summary'">{{ capitalizeEachWords(appState.dailyAggregate.wardName) }}</li>
                    </ol>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-md-6 col-lg-6">
                <div class="card">
                    <div class="table-responsive scrollBox daily-aggregate-perfect-scroll-grid" style="height: 550px !important; overflow: hidden;">
                        <table class="table table-fixed bordered border-top table-hover">
                            <thead style="position: sticky; top: 0; background: #fff; z-index: 2;">
                                <tr>
                                    <th colspan="2">{{ appState.dailyAggregate.title }}</th>
                                    <th>HH Redeemed</th>
                                    <th>Net Redeemed</th>
                                    <th>Family Size Redeemed</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="isLoading">
                                    <td colspan="5" class="text-center py-2">
                                        <div class="spinner-border text-primary" role="status" style="width: 2rem; height: 2rem;"><span class="sr-only">Loading...</span></div>
                                    </td>
                                </tr>
                                <template v-else-if="tableData.length > 0">
                                    <tr v-for="g in tableData" :key="g.id" @click="handleRowClick(g)">
                                        <td v-if="appState.dailyAggregate.title !== 'Dp'"><i class="ti ti-circle-plus text-primary"></i></td>
                                        <td v-if="appState.dailyAggregate.page == ''" :colspan="appState.dailyAggregate.title === 'Dp' ? 2 : 1">{{ displayDate(g.title) }}</td>
                                        <td v-else :colspan="appState.dailyAggregate.title === 'Dp' ? 2 : 1">{{ capitalizeEachWords(g.title) }}</td>
                                        <td>{{ formatNumber(g.household_redeemed) }}</td>
                                        <td>{{ formatNumber(g.net_redeemed) }}</td>
                                        <td>{{ formatNumber(g.familysize_redeemed) }}</td>
                                    </tr>
                                </template>
                                <tr v-else>
                                    <td colspan="5" class="text-center pt-2"><small>No Data</small></td>
                                </tr>
                            </tbody>
                        </table>
                        <div class="mb-2"></div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-md-6 col-lg-6">
                <div class="card">
                    <div class="card-header pt-1 d-flex flex-md-row flex-column justify-content-md-between justify-content-start align-items-md-center align-items-start">
                        <div class="font-weight-bold font-small-4 custom-breadcrum">{{ appState.dailyAggregate.chartTitle }}</div>
                    </div>
                    <div class="card-body" style="position: relative;">
                        <apexchart v-if="chartOptions && series.length"
                            type="bar"
                            :options="chartOptions"
                            :series="series" />
                    </div>
                </div>
            </div>
        </div>
    `,
};

useApp({ template: `<div><page-body/></div>` })
    .component('page-body', PageBody)
    .component('distribution_general_stats', DistributionGeneralStats)
    .component('distribution_lga_aggregate_table', DistributionLgaAggregateTable)
    .component('daily_aggregate_table', DailyAggregateTable)
    .mount('#app');
