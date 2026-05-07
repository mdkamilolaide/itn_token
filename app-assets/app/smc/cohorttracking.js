const EventBus = new Vue();
/*
 *      EVENT HANDLED BY EventBus
 *
 *      - g-event-change-page (Emit multiple - to change page)
 * 		EventBus.$emit('g-event-change-page', 2); 	- Event Fire
 *		EventBus.$on('g-event-change-page', this.gotoPageHandler)  - Event receiver
		gotoPageHandler(data){
            overlay.show();
            this.page = data;
            overlay.hide();
        },						-	Event Handler
 *  
 */

// g-event-goto-page
// g-event-update-user

Vue.component("page-body", {
  data: function () {
    return {
      page: "list", //  page by name home | result | ...
    };
  },
  mounted() {
    /*  Manages events Listening    */
    EventBus.$on("g-event-goto-page", this.gotoPageHandler);
  },
  methods: {
    gotoPageHandler(data) {
      this.page = data.page;
    },
  },
  template: `
        <div>

            <div class="content-body">

                <div v-show="page == 'list'">
                    <child_list/>
                </div>

            </div>
        </div>
    `,
});

// User List Page
Vue.component("child_list", {
  data: function () {
    return {
      url: common.DataService,
      filterUrl: "",
      tableData: [],
      childDetails: [],
      reportLevel: 1,
      filterId: "",
      lgaId: "",
      lgaName: "",
      wardId: "",
      wardName: "",
      dpId: "",
      dpName: "",
      selectedChild: [],
    };
  },
  mounted() {
    /* Manages event listening */
    this.loadTableData(0, "");
  },
  methods: {
    loadTableData(filterId, title) {
      let reportLevel = this.reportLevel;

      if (reportLevel == 1) {
        this.filterId = 0;
        this.loadCohortData(this.url + "?qid=1005");
      } else if (reportLevel == 2) {
        this.lgaId = filterId;
        this.lgaName = title != "" ? title : this.lgaName;
        this.loadCohortData(this.url + "?qid=1006&filterId=" + this.lgaId);
      } else if (reportLevel == 3) {
        this.wardId = filterId;
        this.wardName = title != "" ? title : this.wardName;
        this.loadCohortData(this.url + "?qid=1007&filterId=" + this.wardId);
      } else if (reportLevel == 4) {
        this.dpId = filterId;
        this.dpName = title != "" ? title : this.dpName;
        this.loadCohortData(this.url + "?qid=1008&filterId=" + this.dpId);
      }
    },
    async loadCohortData(url) {
      try {
        overlay.show();
        this.filterUrl = url;
        const response = await axios.get(url);
        if (response.data.result_code == 200) {
          // console.log(response.data);
          this.tableData = response.data.data;
          this.reportLevel = response.data.level;
        } else {
          this.tableData = [];
          alert.Error("ERROR", response.data.message);
        }
        overlay.hide();
      } catch (error) {
        overlay.hide();
        alert.Error("ERROR", error);
      }
    },
    refreshData() {
      this.loadCohortData(this.filterUrl);
    },
    controlBreadCrum(filterId, reportLevel, title) {
      this.reportLevel = reportLevel;
      this.loadTableData(filterId, title);
    },
    capitalize(word) {
      if (word) {
        const loweredCase = word.toLowerCase();
        return word[0].toUpperCase() + loweredCase.slice(1);
      } else {
        return word;
      }
    },
    displayDate(d) {
      let date = new Date(d);
      let options = {
        year: "numeric",
        month: "long",
        day: "numeric",
        hour12: true,
        hour: "2-digit",
        minute: "2-digit",
        second: "2-digit",
      };
      return date.toLocaleString("en-us", options);
    },
    displayDayMonthYear(d) {
      let date = new Date(d);
      let options = {
        year: "numeric",
        month: "long",
        day: "numeric",
      };
      return date.toLocaleString("en-us", options);
    },
    convertStringNumberToFigures(d) {
      let data = d ? parseInt(d) : 0;
      return data ? data.toLocaleString() : 0;
    },
    async viewChildAdminDetails(beneficiary_id, id) {
      overlay.show();
      this.selectedChild = this.tableData[id];
      try {
        overlay.show();
        let url = this.url + "?qid=1009&bid=" + beneficiary_id;
        const response = await axios.get(url);
        if (response.data.result_code == 200) {
          // console.log(response.data);
          this.childDetails = response.data.data;
        } else {
          this.childDetails = selectedChild = [];
          alert.Error("ERROR", response.data.message);
        }
        overlay.hide();
      } catch (error) {
        overlay.hide();
        alert.Error("ERROR", error);
      }

      $("#childAdminDetails").modal("show");
      overlay.hide();
    },
    hideViewChildAdminDetails() {
      overlay.show();
      this.selectedChild = [];
      $("#childAdminDetails").modal("hide");
      let g = 0;
      for (let i in this.childDetails) {
        this.childDetails[i] = "";
        g++;
      }
      overlay.hide();
    },
    checkIfEmpty(data) {
      if (data === null || data === "") {
        return "Nil";
      } else {
        return data;
      }
    },
  },

  computed: {},
  template: `

        <div class="row" id="basic-table">
            <div class="col-md-10 col-sm-10 col-12 mb-0">
                <h2 class="content-header-title header-txt float-left mb-0">SMC</h2>
                <div class="breadcrumb-wrapper reporting-dashboard">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../smc">Home</a></li>
                        <li class="breadcrumb-item" v-if="reportLevel>1" :class="reportLevel==2? 'active': ''" @click="controlBreadCrum(0, 1, '')">LGA Cohort Tracking</li>
                        <li class="breadcrumb-item" v-if="reportLevel>2" :class="reportLevel==3? 'active': ''" @click="controlBreadCrum(lgaId, 2, lgaName)">{{lgaName}} LGA Wards</li>
                        <li class="breadcrumb-item" v-if="reportLevel>3" :class="reportLevel==4? 'active': ''" @click="controlBreadCrum(wardId, 3, wardName)">{{wardName}} Wards DPs</li>
                        <li class="breadcrumb-item" v-if="reportLevel>4" :class="reportLevel==5? 'active': ''" @click="controlBreadCrum(dpId, 4, dpName)">{{dpName}} Child DPs</li>
                    </ol>
                </div>
            </div>

            <div class="col-md-2 col-sm-2 col-12 text-md-right text-right d-md-block">
                <div class="btn-group">
                    <button type="button" class="btn btn-outline-primary round searchBtn" @click="refreshData()" data-toggle="tooltip" data-placement="top" title="Refresh">
                        <i class="feather icon-refresh-cw"></i>         
                    </button>       
                </div>
            </div>

            <div class="col-12 mt-1">
                <div class="card custom-select-down">
                    
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr v-if="reportLevel<5">
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
                                    <th class="text-left" colspan="1" rowspan="1" style="padding-left: .4rem !important; padding-right: .4rem !important; width:45px;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="reportLevel<5" v-for="g in tableData" @click="loadTableData(g.id, g.title)">
                                    <td style="padding-left: 1rem !important; padding-right: .2rem !important; width:34px"><i class="ti ti-circle-plus text-primary"></i></td>
                                    <td style="padding-left: .4rem !important;">{{ capitalize(g.title) }}</td>
                                    <td>
                                      <small class="fw-bolder">{{g.period}}</small>
                                    </td>
                                    <td>{{g.ineligible}}</td>
                                    <td>{{g.incomplete}}</td>
                                    <td>{{g.complete}}</td>
                                    <td>{{ parseInt(g.complete) + parseInt(g.incomplete) }}</td>
                                </tr>

                                <tr v-if="reportLevel == 5" v-for="(g, i) in tableData">
                                    <td style="padding-left: 1rem !important; padding-right: .2rem !important; width:34px">{{i+1}}</td>
                                    <td style="padding-left: .4rem !important;">
                                      <div class="d-flex justify-content-left align-items-center">
                                          <div class="d-flex flex-column">
                                              <span class="user_name text-truncate text-body">
                                                  <span class="fw-bolder">{{ capitalize(g.name) }}</span>
                                              </span>
                                          </div>
                                      </div>
                                    </td>
                                    <td class="text-center"><span class="badge badge-light-primary">{{g.beneficiary_id}}</span></td>
                                    <td class="text-center">
                                      <small class="fw-bolder">{{g.total}}</small>
                                    </td>
                                    <td class="text-center">{{g.period}}</td>
                                    <td>
                                        <div class="d-flex justify-content-center text-center align-items-center">
                                            <div class="d-flex flex-column">
                                                <span class="user_name text-truncate text-body">
                                                  <span class="fw-bolder badge p-25" :class="g.status =='Incomplete'? 'badge-light-warning':'badge-light-success'">{{g.status}}</span>
                                                </span>
                                            </div>
                                        </div>
                                    </td>
                                    <td style="padding-left: .4rem !important; width:45px; padding-right: .4rem !important;">
                                      <a href="javascript:void(0);" data-toggle="modal" data-target="#geoLevelModal" data-backdrop="static" data-keyboard="false" @click="viewChildAdminDetails(g.beneficiary_id, i)"><i class="btn ti ti-eye mx-2 ti-sm"></i></a>
                                    </td>
                                </tr>
                                <tr v-if="tableData.length == 0"><td class="text-center pt-2" colspan="6"><small>No Cohort Tracking Data</small></td></tr>
                            </tbody>
                        </table>

                    </div>
                    <div class="mb-50"></div>

                    
                </div>
            </div>

           <!-- Modal to Show Child Administrations details starts-->
            <div class="modal fade modal-primary" id="childAdminDetails" tabindex="-1" role="dialog" aria-labelledby="childDetails" aria-hidden="true">
                <div class="modal-dialog modal-scrollable modal-lg">
                    <form class="modal-content pt-0" @submit.stop.prevent="" id="state-form">
                        <div class="modal-header mb-0-">
                          <h5 class="modal-title font-weight-bolder" id="childDetails">{{selectedChild.name}}, Cohort Tracking Details <span class="badge badge-light-success">{{selectedChild.beneficiary_id}}</span></h5>
                          <button type="reset" class="close" @click="hideViewChildAdminDetails()" data-dismiss="modal">×</button>
                        </div>                        

                        <div class="modal-body flex-grow-1 vertical-wizard">
                            <div class="info-container table-responsive pt-25">
                            <h5 class="mb-2"><small>Cohohort Status</small>:<br><span class="fw-bolder badge p-25" :class="selectedChild.status =='Incomplete'? 'badge-light-warning':'badge-light-success'">{{selectedChild.status}}</span></h5>
                                <table class="table">
                                    <thead>
                                        <th>Visit</th>
                                        <th>Eleigibility</th>
                                        <th>Drug</th>
                                        <th>Redose Count</th>
                                        <th>Redose Reason</th>
                                        <th>Collected Date</th>
                                    </thead>
                                    <tbody>
                                        <tr v-for="(g, i) in childDetails">
                                            <td>{{checkIfEmpty(g.period)}}</td>
                                            <td>
                                                <div class="d-flex justify-content-left text-left align-items-left">
                                                    <div class="d-flex flex-column">
                                                        <span class="user_name text-truncate text-body">
                                                          <span class="fw-bolder badge p-25" :class="g.eligibility =='Eligible'? 'badge-light-success':'badge-light-danger'">
                                                            {{g.eligibility}}
                                                          </span>
                                                          <small v-if="g.eligibility !='NA'" class="emp_post d-block text-danger"><span class="fw-bolder">{{ capitalize(g.not_eligible_reason) }}</span></small>
                                                        </span>
                                                    </div>
                                                </div>

                                            </td>
                                            <td>{{checkIfEmpty(g.drug)}}</td>
                                            <td>{{checkIfEmpty(g.redose_count)}}</td>
                                            <td>{{checkIfEmpty(g.redose_reason)}}</td>
                                            <td>{{displayDayMonthYear(g.collected_date)}}</td>
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
            <!-- Modal to Show Child Administrations details Ends-->

            <!-- Change Geo Level Modal: Ends -->

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
