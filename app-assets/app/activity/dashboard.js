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
      totalTraining: "",
      trainingStatus: {
        active: "",
        inactive: "",
      },
      totalSessions: "",
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
        url + "?qid=111", //Get Total Training [0]
        url + "?qid=112", //Get Active and Inactive Users [1]
        url + "?qid=113", //Get Geo Statistics distribution of users [2]
      ];

      // Return our response in the allData variable as an array
      Promise.all(endpoints.map((endpoint) => axios.get(endpoint))).then(
        axios.spread((...allData) => {
          overlay.show();
          self.totalTraining = parseInt(
            allData[0].data.data[0].total
          ).toLocaleString();
          self.trainingStatus.active = parseInt(
            allData[1].data.data[0].active
          ).toLocaleString();
          self.trainingStatus.inactive = parseInt(
            allData[1].data.data[0].inactive
          ).toLocaleString();
          self.totalSessions = allData[2].data.data[0].total.toLocaleString();
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
                    <h2 class="content-header-title header-txt float-left mb-0">Activity Mgmt.</h2>
                    <div class="breadcrumb-wrapper">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item active">Home</li>
                        </ol>
                    </div>
                </div>
            </div>
            
            <div class="row mb-50">
                <!-- Stats Horizontal Card -->
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
                <!--/ Stats Horizontal Card -->

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
