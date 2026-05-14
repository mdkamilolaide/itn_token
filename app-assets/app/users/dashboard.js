/**
 * Users / Dashboard submodule — Vue 3 Composition API in place.
 * Stat cards (totals, active/inactive, geo distribution) + group list.
 * Fires 7 parallel API calls (qid=020..025, 010) at mount via axios.spread.
 * Each is null-guarded so an empty endpoint renders 0 instead of crashing.
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
        const totalUser = ref('');
        const userStatus = reactive({ totalActiveUser: '', totalInactiveUser: '' });
        const totalGroup = ref('');
        const groupData = ref([]);
        const userGroupData = ref([]);
        const gender = reactive({ male: 0, female: 0, others: 0 });
        const geoUserDistribution = reactive({
            state: 0, lga: 0, cluster: 0, ward: 0, dp: 0,
        });

        const fmt = utilsFmt;

        const getAllStat = () => {
            var url = common.DataService;
            var endpoints = [
                url + '?qid=020', // Total Users [0]
                url + '?qid=021', // Active and Inactive Users [1]
                url + '?qid=022', // Geo Statistics distribution of users [2]
                url + '?qid=023', // User Counts by Group [3]
                url + '?qid=024', // Total User Group [4]
                url + '?qid=025', // Gender Count [5]
                url + '?qid=010', // User Group Data [6]
            ];

            Promise.all(endpoints.map(e => axios.get(e))).then(
                axios.spread((...allData) => {
                    overlay.show();

                    var totalUserRow = allData[0]?.data?.total_user?.[0];
                    totalUser.value = totalUserRow ? fmt(totalUserRow.total) : '0';

                    var statusRow = allData[1]?.data?.data?.[0];
                    userStatus.totalActiveUser   = statusRow ? fmt(statusRow.active)   : '0';
                    userStatus.totalInactiveUser = statusRow ? fmt(statusRow.inactive) : '0';

                    var geoRows = allData[2]?.data?.data || [];
                    geoRows.forEach(stat => {
                        if (stat['geo_level'] === 'state')   geoUserDistribution.state   = fmt(stat['total']);
                        if (stat['geo_level'] === 'lga')     geoUserDistribution.lga     = fmt(stat['total']);
                        if (stat['geo_level'] === 'cluster') geoUserDistribution.cluster = fmt(stat['total']);
                        if (stat['geo_level'] === 'ward')    geoUserDistribution.ward    = fmt(stat['total']);
                        if (stat['geo_level'] === 'dp')      geoUserDistribution.dp      = fmt(stat['total']);
                    });

                    var groupRow = allData[4]?.data?.data?.[0];
                    totalGroup.value = groupRow && groupRow.total ? fmt(groupRow.total) : 0;

                    var genderRows = allData[5]?.data?.data || [];
                    genderRows.forEach(stat => {
                        if (stat['gender'] == null)         gender.others = fmt(stat['total']);
                        if (stat['gender'] === 'Male')      gender.male   = fmt(stat['total']);
                        if (stat['gender'] === 'Female')    gender.female = fmt(stat['total']);
                    });

                    userGroupData.value = allData[6]?.data?.data || [];

                    overlay.hide();
                })
            ).catch(error => {
                overlay.hide();
                console.error('[users/dashboard] getAllStat error:', error);
            });
        }

        onMounted(() => {
            getAllStat();
        });

        return {
            totalUser, userStatus, totalGroup, groupData, userGroupData,
            gender, geoUserDistribution,
            getAllStat,
        };
    },
    template: `
        <div>
            <div class="row">
                <div class="col-12 mb-2">
                    <h2 class="content-header-title header-txt float-left mb-0">Users</h2>
                    <div class="breadcrumb-wrapper">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item active">Home</li>
                        </ol>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-sm-12 col-md-12 col-lg-12 col-12">
                    <div class="row">
                        <div class="col-sm-6 col-md-3 col-lg-3 col-12">
                            <div onClick="location.href='./users/list'" class="card">
                                <div class="card-header d-flex align-items-center justify-content-between">
                                    <div>
                                        <h3 class="fw-bolder mb-75" v-text="totalUser"></h3>
                                        <span class="card-text">Total Users</span>
                                    </div>
                                    <div class="avatar bg-light-primary p-50 m-0">
                                        <div class="avatar-content">
                                            <i data-feather="users" class="font-medium-4"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-sm-6 col-md-3 col-lg-3 col-12">
                            <div onClick="location.href='./users/list'" class="card">
                                <div class="card-header d-flex align-items-center justify-content-between">
                                    <div>
                                        <h3 class="fw-bolder mb-75" v-text="userStatus.totalActiveUser"></h3>
                                        <span>Active Users</span>
                                    </div>
                                    <div class="avatar bg-light-success p-50 m-0">
                                        <div class="avatar-content">
                                            <i data-feather="user-check" class="font-medium-4"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-sm-6 col-md-3 col-lg-3 col-12">
                            <div onClick="location.href='./users/list'" class="card">
                                <div class="card-header d-flex align-items-center justify-content-between">
                                    <div>
                                        <h3 class="fw-bolder mb-75" v-text="userStatus.totalInactiveUser"></h3>
                                        <span>Inactive Users</span>
                                    </div>
                                    <div class="avatar bg-light-danger p-50 m-0">
                                        <div class="avatar-content">
                                            <i data-feather="user-x" class="font-medium-4"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-sm-6 col-md-3 col-lg-3 col-12">
                            <div onClick="location.href='./users/list'" class="card">
                                <div class="card-header d-flex align-items-center justify-content-between">
                                    <div>
                                        <h3 class="fw-bolder mb-75" v-text="totalGroup"></h3>
                                        <span>Total Groups</span>
                                    </div>
                                    <div class="avatar bg-light-info p-50 m-0">
                                        <div class="avatar-content">
                                            <i data-feather="pocket" class="font-medium-4"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card col-12 geo-level-stat">
                        <ul class="row list-unstyled mb-0">
                            <li class="col-lg-3 col-sm-6 col-12">
                                <div class="card mb-0">
                                    <div class="card-header d-flex align-items-center justify-content-between">
                                        <div>
                                            <h3 class="fw-bolder mb-75" v-text="geoUserDistribution.state"></h3>
                                            <span>State Level</span>
                                        </div>
                                        <div class="avatar bg-light-primary p-50 m-0">
                                            <div class="avatar-content">
                                                <i data-feather="globe" class="font-medium-4"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </li>
                            <li class="col-lg-3 col-sm-6 col-12">
                                <div class="card mb-0">
                                    <div class="card-header d-flex align-items-center justify-content-between">
                                        <div>
                                            <h3 class="fw-bolder mb-75" v-text="geoUserDistribution.lga"></h3>
                                            <span>LGA Level</span>
                                        </div>
                                        <div class="avatar bg-light-primary p-50 m-0">
                                            <div class="avatar-content">
                                                <i data-feather="grid" class="font-medium-4"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </li>
                            <li class="col-lg-3 col-sm-6 col-12">
                                <div class="card mb-0">
                                    <div class="card-header d-flex align-items-center justify-content-between">
                                        <div>
                                            <h3 class="fw-bolder mb-75" v-text="geoUserDistribution.cluster"></h3>
                                            <span>Cluster Level</span>
                                        </div>
                                        <div class="avatar bg-light-primary p-50 m-0">
                                            <div class="avatar-content">
                                                <i data-feather="stop-circle" class="font-medium-4"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </li>
                            <li class="col-lg-3 col-sm-6 col-12">
                                <div class="card mb-0">
                                    <div class="card-header d-flex align-items-center justify-content-between">
                                        <div>
                                            <h3 class="fw-bolder mb-75" v-text="geoUserDistribution.ward"></h3>
                                            <span>Ward Level</span>
                                        </div>
                                        <div class="avatar bg-light-primary p-50 m-0">
                                            <div class="avatar-content">
                                                <i data-feather="flag" class="font-medium-4"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="col-sm-12 col-md-6 col-lg-6 col-12">
                    <div class="card" v-if="userGroupData.length > 0">
                        <div class="table table-borderless table-hover table-nowrap table-centered m-0" style="height: calc(100% - 20px) !important">
                            <table class="table">
                                <thead class="table-light">
                                    <tr><th width="50px">#</th><th>Group Name</th></tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(g, i) in userGroupData" :key="g.user_group || i">
                                        <td>{{ i + 1 }}</td>
                                        <td>{{ g.user_group }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `,
};

useApp({ template: `<div><page-body/></div>` })
    .component('page-body', PageBody)
    .component('dashboard_container', DashboardContainer)
    .mount('#app');
