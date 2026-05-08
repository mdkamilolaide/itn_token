/**
 * Activity / Dashboard submodule — Vue 3 Composition API in place.
 * Stat cards (total, active/inactive activities, total sessions).
 * Fires 3 parallel API calls (qid=111..113) at mount via axios.spread.
 */

const { ref, reactive, onMounted } = Vue;
const { useApp, useFormat, fmt: utilsFmt } = window.utils;

/* ------------------------------------------------------------------ */
const PageBody = {
    setup() {
        const page = ref('dashboard');
        return { page };
    },
    template: `
        <div>
            <div class="content-body">
                <dashboard_container/>
            </div>
        </div>
    `,
};

/* ------------------------------------------------------------------ */
const DashboardContainer = {
    setup() {
        const totalTraining = ref('');
        const trainingStatus = reactive({ active: '', inactive: '' });
        const totalSessions = ref('');

        const fmt = utilsFmt;

        function getAllStat() {
            var url = common.DataService;
            var endpoints = [
                url + '?qid=111', // Total Training [0]
                url + '?qid=112', // Active and Inactive Users [1]
                url + '?qid=113', // Geo Statistics distribution of users [2]
            ];

            Promise.all(endpoints.map(function (e) { return axios.get(e); })).then(
                axios.spread(function (...allData) {
                    overlay.show();

                    var totalRow = allData[0]?.data?.data?.[0];
                    totalTraining.value = totalRow ? fmt(totalRow.total) : '0';

                    var statusRow = allData[1]?.data?.data?.[0];
                    trainingStatus.active   = statusRow ? fmt(statusRow.active)   : '0';
                    trainingStatus.inactive = statusRow ? fmt(statusRow.inactive) : '0';

                    var sessionRow = allData[2]?.data?.data?.[0];
                    totalSessions.value = sessionRow ? fmt(sessionRow.total) : '0';

                    overlay.hide();
                })
            ).catch(function (error) {
                overlay.hide();
                console.error('[activity/dashboard] getAllStat error:', error);
            });
        }

        onMounted(function () {
            getAllStat();
        });

        return { totalTraining, trainingStatus, totalSessions, getAllStat };
    },
    template: `
        <div>
            <div class="row">
                <div class="col-12 mb-2">
                    <h2 class="content-header-title header-txt float-left mb-0">Activity Mgmt.</h2>
                    <div class="breadcrumb-wrapper">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item active">Home</li>
                        </ol>
                    </div>
                </div>
            </div>

            <div class="row mb-50">
                <div class="col-lg-3 col-sm-6 col-12">
                    <a href="./activity/list" class="card">
                        <div class="card-header d-flex align-items-center justify-content-between">
                            <div>
                                <h3 class="fw-bolder mb-75" v-text="totalTraining"></h3>
                                <span>Total Activity</span>
                            </div>
                            <div class="avatar bg-light-primary p-50 m-0">
                                <div class="avatar-content">
                                    <i data-feather="users" class="font-medium-4"></i>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-lg-3 col-sm-6 col-12">
                    <a href="./activity/list" class="card">
                        <div class="card-header d-flex align-items-center justify-content-between">
                            <div>
                                <h3 class="fw-bolder mb-75" v-text="trainingStatus.active"></h3>
                                <span>Active Activity</span>
                            </div>
                            <div class="avatar bg-light-primary p-50 m-0">
                                <div class="avatar-content">
                                    <i data-feather="user-check" class="font-medium-4"></i>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-lg-3 col-sm-6 col-12">
                    <a href="./activity/list" class="card">
                        <div class="card-header d-flex align-items-center justify-content-between">
                            <div>
                                <h3 class="fw-bolder mb-75" v-text="trainingStatus.inactive"></h3>
                                <span>Inactive Activity</span>
                            </div>
                            <div class="avatar bg-light-primary p-50 m-0">
                                <div class="avatar-content">
                                    <i data-feather="user-x" class="font-medium-4"></i>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-lg-3 col-sm-6 col-12">
                    <a href="./activity/list" class="card">
                        <div class="card-header d-flex align-items-center justify-content-between">
                            <div>
                                <h3 class="fw-bolder mb-75" v-text="totalSessions"></h3>
                                <span>Total Session</span>
                            </div>
                            <div class="avatar bg-light-primary p-50 m-0">
                                <div class="avatar-content">
                                    <i data-feather="user-plus" class="font-medium-4"></i>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    `,
};

useApp({ template: `<div><page-body/></div>` })
    .component('page-body', PageBody)
    .component('dashboard_container', DashboardContainer)
    .mount('#app');
