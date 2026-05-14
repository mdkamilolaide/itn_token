/**
 * SMC / Cohort Tracking — Vue 3 Composition API in place.
 * Two components — page-body and child_list.
 *
 * Drill state machine: report level 1 (LGA) → 2 (Wards) → 3 (DPs) →
 * 4 (Children) using qid 1005 / 1006 / 1007 / 1008 — and qid=1009 for
 * per-child cohort details modal.
 */

const { ref, reactive, onMounted, onBeforeUnmount } = Vue;
const { useApp, useFormat, bus, safeMessage } = window.utils;

const PageBody = {
    setup() {
        const page = ref('list');
        const gotoPageHandler = (data) => { page.value = data && data.page; };
        onMounted(() => { bus.on('g-event-goto-page', gotoPageHandler); });
        onBeforeUnmount(() => { bus.off('g-event-goto-page', gotoPageHandler); });
        return { page };
    },
    template: `
        <div>
            <div class="content-body">
                <div v-show="page == 'list'"><child_list/></div>
            </div>
        </div>
    `,
};

const ChildList = {
    setup() {
        const fmtUtils = useFormat();

        const url = ref(window.common && window.common.DataService);
        const filterUrl = ref('');
        const tableData = ref([]);
        const childDetails = ref([]);
        const reportLevel = ref(1);
        const filterId = ref('');
        const lgaId = ref('');
        const lgaName = ref('');
        const wardId = ref('');
        const wardName = ref('');
        const dpId = ref('');
        const dpName = ref('');
        const selectedChild = ref({});

        const loadTableData = (fid, title) => {
            var lvl = reportLevel.value;
            if (lvl == 1) {
                filterId.value = 0;
                loadCohortData(url.value + '?qid=1005');
            } else if (lvl == 2) {
                lgaId.value = fid;
                lgaName.value = title != '' ? title : lgaName.value;
                loadCohortData(url.value + '?qid=1006&filterId=' + lgaId.value);
            } else if (lvl == 3) {
                wardId.value = fid;
                wardName.value = title != '' ? title : wardName.value;
                loadCohortData(url.value + '?qid=1007&filterId=' + wardId.value);
            } else if (lvl == 4) {
                dpId.value = fid;
                dpName.value = title != '' ? title : dpName.value;
                loadCohortData(url.value + '?qid=1008&filterId=' + dpId.value);
            }
        }
        const loadCohortData = async (u) => {
            try {
                overlay.show();
                filterUrl.value = u;
                var response = await axios.get(u);
                if (response.data.result_code == 200) {
                    tableData.value = response.data.data || [];
                    reportLevel.value = response.data.level;
                } else {
                    tableData.value = [];
                    alert.Error('ERROR', response.data.message);
                }
                overlay.hide();
            } catch (error) {
                overlay.hide();
                alert.Error('ERROR', safeMessage(error));
            }
        }
        const refreshData = () => { loadCohortData(filterUrl.value); };
        const controlBreadCrum = (fid, lvl, title) => {
            reportLevel.value = lvl;
            loadTableData(fid, title);
        }
        const displayDayMonthYear = (d) => {
            var date = new Date(d);
            return date.toLocaleString('en-us', { year: 'numeric', month: 'long', day: 'numeric' });
        }
        const viewChildAdminDetails = async (beneficiary_id, id) => {
            overlay.show();
            selectedChild.value = tableData.value[id] || {};
            try {
                var response = await axios.get(url.value + '?qid=1009&bid=' + beneficiary_id);
                if (response.data.result_code == 200) {
                    childDetails.value = response.data.data || [];
                } else {
                    childDetails.value = [];
                    selectedChild.value = {};
                    alert.Error('ERROR', response.data.message);
                }
            } catch (error) {
                alert.Error('ERROR', safeMessage(error));
            }
            $('#childAdminDetails').modal('show');
            overlay.hide();
        }
        const hideViewChildAdminDetails = () => {
            overlay.show();
            selectedChild.value = {};
            $('#childAdminDetails').modal('hide');
            childDetails.value = [];
            overlay.hide();
        }
        const checkIfEmpty = (data) => { return data === null || data === '' ? 'Nil' : data; };

        onMounted(() => { loadTableData(0, ''); });

        return {
            url, filterUrl, tableData, childDetails, reportLevel, filterId,
            lgaId, lgaName, wardId, wardName, dpId, dpName, selectedChild,
            loadTableData, loadCohortData, refreshData, controlBreadCrum,
            displayDayMonthYear, viewChildAdminDetails, hideViewChildAdminDetails,
            checkIfEmpty,
            capitalize: fmtUtils.capitalize,
            displayDate: fmtUtils.displayDate,
            convertStringNumberToFigures: fmtUtils.convertStringNumberToFigures,
        };
    },
    template: `
        <div class="row" id="basic-table">
            <div class="col-md-10 col-sm-10 col-12 mb-0">
                <h2 class="content-header-title header-txt float-left mb-0">SMC</h2>
                <div class="breadcrumb-wrapper reporting-dashboard">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../smc">Home</a></li>
                        <li class="breadcrumb-item" v-if="reportLevel > 1" :class="reportLevel == 2 ? 'active' : ''" @click="controlBreadCrum(0, 1, '')">LGA Cohort Tracking</li>
                        <li class="breadcrumb-item" v-if="reportLevel > 2" :class="reportLevel == 3 ? 'active' : ''" @click="controlBreadCrum(lgaId, 2, lgaName)">{{ lgaName }} LGA Wards</li>
                        <li class="breadcrumb-item" v-if="reportLevel > 3" :class="reportLevel == 4 ? 'active' : ''" @click="controlBreadCrum(wardId, 3, wardName)">{{ wardName }} Wards DPs</li>
                        <li class="breadcrumb-item" v-if="reportLevel > 4" :class="reportLevel == 5 ? 'active' : ''" @click="controlBreadCrum(dpId, 4, dpName)">{{ dpName }} Child DPs</li>
                    </ol>
                </div>
            </div>

            <div class="col-md-2 col-sm-2 col-12 text-md-right text-right d-md-block">
                <div class="btn-group">
                    <button type="button" class="btn btn-outline-primary round searchBtn" @click="refreshData()" data-toggle="tooltip" data-placement="top" title="Refresh"><i class="feather icon-refresh-cw"></i></button>
                </div>
            </div>

            <div class="col-12 mt-1">
                <div class="card custom-select-down">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr v-if="reportLevel < 5">
                                    <th colspan="2">Description</th>
                                    <th>Total Visit</th>
                                    <th>Ineligible</th>
                                    <th>Incomplete</th>
                                    <th>Complete</th>
                                    <th class="text-left">Total</th>
                                </tr>
                                <tr v-if="reportLevel == 5">
                                    <th style="padding-left: .4rem !important; min-width:45px">#</th>
                                    <th>Beneficiary Name</th>
                                    <th>Beneficiary ID</th>
                                    <th class="text-center">Visit Count</th>
                                    <th class="text-center">Total Visit</th>
                                    <th class="text-center">Status</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <template v-if="reportLevel < 5">
                                    <tr v-for="(g, i) in tableData" :key="g.id || i" @click="loadTableData(g.id, g.title)">
                                        <td style="padding-left: 1rem !important; padding-right: .2rem !important; width:34px"><i class="ti ti-circle-plus text-primary"></i></td>
                                        <td style="padding-left: .4rem !important;">{{ capitalize(g.title) }}</td>
                                        <td><small class="fw-bolder">{{ g.period }}</small></td>
                                        <td>{{ g.ineligible }}</td>
                                        <td>{{ g.incomplete }}</td>
                                        <td>{{ g.complete }}</td>
                                        <td>{{ parseInt(g.complete) + parseInt(g.incomplete) }}</td>
                                    </tr>
                                </template>
                                <template v-if="reportLevel == 5">
                                    <tr v-for="(g, i) in tableData" :key="g.beneficiary_id || i">
                                        <td style="padding-left: 1rem !important;">{{ i + 1 }}</td>
                                        <td style="padding-left: .4rem !important;">
                                            <div class="d-flex flex-column"><span class="fw-bolder">{{ capitalize(g.name) }}</span></div>
                                        </td>
                                        <td class="text-center"><span class="badge badge-light-primary">{{ g.beneficiary_id }}</span></td>
                                        <td class="text-center"><small class="fw-bolder">{{ g.total }}</small></td>
                                        <td class="text-center">{{ g.period }}</td>
                                        <td class="text-center">
                                            <span class="fw-bolder badge p-25" :class="g.status == 'Incomplete' ? 'badge-light-warning' : 'badge-light-success'">{{ g.status }}</span>
                                        </td>
                                        <td>
                                            <a href="javascript:void(0);" @click="viewChildAdminDetails(g.beneficiary_id, i)"><i class="btn ti ti-eye mx-2 ti-sm"></i></a>
                                        </td>
                                    </tr>
                                </template>
                                <tr v-if="tableData.length == 0"><td class="text-center pt-2" colspan="7"><small>No Cohort Tracking Data</small></td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="mb-50"></div>
                </div>
            </div>

            <div class="modal fade modal-primary" id="childAdminDetails" tabindex="-1" role="dialog" aria-hidden="true">
                <div class="modal-dialog modal-scrollable modal-lg">
                    <form class="modal-content pt-0" @submit.stop.prevent="">
                        <div class="modal-header mb-0">
                            <h5 class="modal-title font-weight-bolder">{{ selectedChild.name }}, Cohort Tracking Details <span class="badge badge-light-success">{{ selectedChild.beneficiary_id }}</span></h5>
                            <button type="reset" class="close" @click="hideViewChildAdminDetails()" data-dismiss="modal">×</button>
                        </div>
                        <div class="modal-body flex-grow-1 vertical-wizard">
                            <div class="info-container table-responsive pt-25">
                                <h5 class="mb-2"><small>Cohort Status</small>:<br>
                                    <span class="fw-bolder badge p-25" :class="selectedChild.status == 'Incomplete' ? 'badge-light-warning' : 'badge-light-success'">{{ selectedChild.status }}</span>
                                </h5>
                                <table class="table">
                                    <thead>
                                        <th>Visit</th>
                                        <th>Eligibility</th>
                                        <th>Drug</th>
                                        <th>Redose Count</th>
                                        <th>Redose Reason</th>
                                        <th>Collected Date</th>
                                    </thead>
                                    <tbody>
                                        <tr v-for="(g, i) in childDetails" :key="i">
                                            <td>{{ checkIfEmpty(g.period) }}</td>
                                            <td>
                                                <span class="fw-bolder badge p-25" :class="g.eligibility == 'Eligible' ? 'badge-light-success' : 'badge-light-danger'">{{ g.eligibility }}</span>
                                                <small v-if="g.eligibility != 'NA'" class="d-block text-danger"><span class="fw-bolder">{{ capitalize(g.not_eligible_reason) }}</span></small>
                                            </td>
                                            <td>{{ checkIfEmpty(g.drug) }}</td>
                                            <td>{{ checkIfEmpty(g.redose_count) }}</td>
                                            <td>{{ checkIfEmpty(g.redose_reason) }}</td>
                                            <td>{{ displayDayMonthYear(g.collected_date) }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="reset" class="btn btn-secondary" data-dismiss="modal" @click="hideViewChildAdminDetails()">Close</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    `,
};

useApp({ template: `<div><page-body/></div>` })
    .component('page-body', PageBody)
    .component('child_list', ChildList)
    .mount('#app');
