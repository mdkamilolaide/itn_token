Vue.component("page-body", {
    data: function () {
        return {
            page: "dashboard", //  page by name dashbaord | result | ...
        };
    },
    mounted() {
        /*  Manages events Listening    */
    },
    methods: {},
    template: `
    <div>
    <div class="content-body">
        <dashboard_container/>
    </div>
    </div>
    `,
});

Vue.component("dashboard_container", {
    data: function () {
        return {
            totalUser: "",
            userStatus: {
                totalActiveUser: "",
                totalInactiveUser: "",
            },
            totalGroup: "",
            groupData: [],
            userGroupData: [],
            gender: {
                male: 0,
                female: 0,
                others: 0,
            },
            geoUserDistribution: {
                state: 0,
                lga: 0,
                cluster: 0,
                ward: 0,
                dp: 0,
            },
        };
    },
    mounted() {
        /*  Manages events Listening    */
        this.getAllStat();
    },
    methods: {
        getAllStat() {
            var url = common.DataService;
            var self = this;
            var endpoints = [
                url + "?qid=020", //Get Total Users [0]
                url + "?qid=021", //Get Active and Inactive Users [1]
                url + "?qid=022", //Get Geo Statistics distribution of users [2]
                url + "?qid=023", //Get User Counts by Group [3]
                url + "?qid=024", //Get Total User Group [4]
                url + "?qid=025", //Get Gender Count [5]
                url + "?qid=010", //Get User Group Data [6]
            ];

            // Return our response in the allData variable as an array
            Promise.all(endpoints.map((endpoint) => axios.get(endpoint))).then(
                axios.spread((...allData) => {
                    overlay.show();

                    // Total Users
                    self.totalUser = parseInt(allData[0].data.total_user[0].total).toLocaleString();

                    // User Active Status
                    self.userStatus.totalActiveUser = parseInt(allData[1].data.data[0].active).toLocaleString();
                    self.userStatus.totalInactiveUser = parseInt(allData[1].data.data[0].inactive).toLocaleString();

                    // Geo Distribution
                    // console.log(allData[2].data.data);
                    allData[2].data.data.map((stat) => {
                        self.geoUserDistribution.state = stat["geo_level"] == "state" ? parseInt(stat["total"]).toLocaleString() : self.geoUserDistribution.state;
                        self.geoUserDistribution.lga = stat["geo_level"] == "lga" ? parseInt(stat["total"]).toLocaleString() : self.geoUserDistribution.lga;
                        self.geoUserDistribution.cluster = stat["geo_level"] == "cluster" ? parseInt(stat["total"]).toLocaleString() : self.geoUserDistribution.cluster;
                        self.geoUserDistribution.ward = stat["geo_level"] == "ward" ? parseInt(stat["total"]).toLocaleString() : self.geoUserDistribution.ward;
                        self.geoUserDistribution.dp = stat["geo_level"] == "dp" ? parseInt(stat["total"]).toLocaleString() : self.geoUserDistribution.dp;
                    });

                    //Group Data
                    self.totalGroup = allData[4].data.data[0].total ? parseInt(allData[4].data.data[0].total).toLocaleString() : 0;

                    // Gender
                    allData[5].data.data.map((stat) => {
                        self.gender.others = stat["gender"] == null ? parseInt(stat["total"]).toLocaleString() : self.gender.others;
                        self.gender.male = stat["gender"] == "Male" ? parseInt(stat["total"]).toLocaleString() : self.gender.male;
                        self.gender.female = stat["gender"] == "Female" ? parseInt(stat["total"]).toLocaleString() : self.gender.female;
                    });

                    // User Group List
                    self.userGroupData = allData[6].data.data;

                    overlay.hide();
                })
            );
        },
    },
    computed: {},
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
                <!-- Split Screen into 2: Begin -->

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

                    <div class="card" v-if="userGroupData.length >0">
                        <div class="table table-borderless table-hover table-nowrap table-centered m-0" style="height: calc(100% - 20px) !important">
                            <table class="table">
                                <thead class="table-light">
                                    <th width="50px">#</th>
                                    <th>Group Name</th>
                                </thead>
                                <tbody>
                                    <tr v-for="(g, i) in userGroupData">
                                        <td>{{i+1}}</td>
                                        <td>{{g.user_group}}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>

                <!-- Split Screen into 2: End -->

            </div>
 


        </div>
    `,
});
var vm = new Vue({
    el: "#app",
    data: {},
    methods: {},
    template: `
        <div>
            <page-body/>
        </div>
    `,
});
