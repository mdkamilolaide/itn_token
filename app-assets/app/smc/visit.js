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
// page: 'visit', //  page by name visit | session | participant | ...
// g-event-update

Vue.component("page-body", {
  data: function () {
    return {
      page: "visit", //  page by name visit | session | participant | attendance ...
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

            <div v-show="page == 'visit'">
                <visit_list/>
            </div>
            
        </div>
    </div>
    `,
});

Vue.component("visit_list", {
  data: function () {
    return {
      tableData: [],
      checkToggle: false,
      filterState: false,
      filters: false,
      url: common.TableService,
      permission: getPermission(per, "smc"),
      errors: [],
      visitBtn: "",
      addVisitModal: false,
      visitForm: {
        period_id: "",
        period_title: "",
        start_date: "",
        end_date: "",
        period_pos: "",
      },
    };
  },
  mounted() {
    /*  Manages events Listening    */
    this.loadTableData();
    EventBus.$on("g-event-update", this.loadTableData);
    $(".date").flatpickr({
      altInput: true,
      altFormat: "F j, Y",
      dateFormat: "Y-m-d",
      minDate: "today",
    });
  },
  methods: {
    loadTableData() {
      /*  Manages the loading of table data */
      var self = this;
      var url = common.DataService;
      overlay.show();

      axios
        .get(url + "?qid=1004")
        .then(function (response) {
          // console.log(response.data.data);
          self.tableData = response.data.data; //All Data
          overlay.hide();
        })
        .catch(function (error) {
          overlay.hide();
          alert.Error("ERROR", error);
        });
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
    displayMonthDay(d) {
      let date = new Date(d);
      let options = {
        year: "numeric",
        month: "long",
        day: "numeric",
        hour12: true,
      };
      return date.toLocaleString("en-us", options);
    },
    deActivateVisit(period_id, status, period_pos) {
      // deActivateVisit(g.periodid, g.active, i);
      var self = this;
      var url = common.DataService;
      var message = "";
      var bnt_text = "";
      let title = this.tableData[period_pos].title;

      // overlay.show();
      if (status == "1") {
        message =
          "Are you sure you want to Deactivate the Visit with Title: <b>" +
          title +
          "</b>? <br><br>Be sure you that you want to deactivate the Visit.";
        bnt_text = "Deactivate";
        btn_class = " btn-danger ";
        response_txt =
          "Visit with Title: <b>" + title + "</b> Successfully Deactivated";
      } else {
        message =
          "Are you sure you want to Activate Visit with Title: <b>" +
          title +
          "</b>? <br><br> Be Sure that you want to activate the Visit.";
        bnt_text = "Activate";
        btn_class = " btn-success ";
        response_txt =
          "Visit with Title: <b>" + title + "</b> Successfully Activated";
      }

      $.confirm({
        title: "WARNING!",
        content: message,
        buttons: {
          delete: {
            text: bnt_text,
            btnClass: "btn mr-1" + btn_class,
            action: function () {
              //Attempt Delete
              axios
                .post(url + "?qid=1003&period_id=" + period_id)
                .then(function (response) {
                  overlay.hide();
                  if (response.data.result_code == "200") {
                    self.loadTableData();
                    alert.Success("SUCCESS", response_txt);
                  } else {
                    alert.Error(
                      "ERROR",
                      "Unable to De/Activate Visit with Title: <b>" +
                        title +
                        "</b> at the moment please try again later"
                    );
                  }
                  overlay.hide();
                })
                .catch(function (error) {
                  overlay.hide();
                  alert.Error("ERROR", error);
                });
            },
          },
          cancel: function () {
            // Do nothing
            overlay.hide();
          },
        },
      });
    },
    showAddVisitModal() {
      this.resetVisitForm();
      $("#addNewVisit").modal("show");
      this.addVisitModal = true;
      this.visitBtn = "Create";
    },
    hideAddVisitModal() {
      this.resetVisitForm();
      this.addVisitModal = false;
      this.visitBtn = "";
      $("#addNewVisit").modal("hide");
    },
    goToSessionLists(trainingid) {
      EventBus.$emit("g-event-goto-page", {
        trainingid: trainingid,
        page: "session",
      });
    },
    onSubmitCreateVisit(action) {
      var self = this;
      var url = common.DataService;
      if (action == "Create") {
        //Validate Form
        overlay.show();
        axios
          .post(url + "?qid=1000", JSON.stringify(self.visitForm))
          .then(function (response) {
            if (response.data.result_code == "201") {
              self.resetVisitForm();
              self.addVisitModal = false;
              $("#addNewVisit").modal("hide");
              self.loadTableData();
              alert.Success("Success", response.data.message);
              overlay.hide();
            } else {
              overlay.hide();
              alert.Error("Error", response.data.message);
            }
            // Unable to create new record
          })
          .catch(function (error) {
            alert.Error("ERROR", error);
            overlay.hide();
          });
      } else {
        this.updateVisit();
      }
    },
    resetVisitForm() {
      $("#start-date")
        .flatpickr({
          altInput: true,
          altFormat: "F j, Y",
          dateFormat: "Y-m-d",
          minDate: "today",
        })
        .clear();

      $("#end-date")
        .flatpickr({
          altInput: true,
          altFormat: "F j, Y",
          dateFormat: "Y-m-d",
          minDate: "today",
        })
        .clear();

      this.visitBtn = "";
      this.visitForm.title = "";
      this.visitForm.period_title = "";
      this.visitForm.start_date = "";
      this.visitForm.end_date = "";
      this.visitForm.period_pos = "";
      // this.getsysDefaultDataSettings();
      overlay.hide();
    },
    refreshData() {
      this.loadTableData();
    },
    editVisit(period_id, period_pos) {
      overlay.show();
      this.addVisitModal = true;
      this.visitBtn = "Update";
      this.visitForm.period_id = period_id;
      this.visitForm.period_title = this.tableData[period_pos].title;
      const options = {
        year: "numeric",
        month: "long",
        day: "numeric",
      };
      this.visitForm.start_date = this.tableData[period_pos].start_date;
      this.visitForm.end_date = this.tableData[period_pos].end_date;

      this.visitForm.period_pos = period_pos;

      let start_d = new Date(this.tableData[period_pos].start_date);
      let end_d = new Date(this.tableData[period_pos].end_date);
      $("#start .form-control.date.form-control.input").val(
        start_d.toLocaleString("en-us", options)
      );
      $("#end .form-control.date.form-control.input").val(
        end_d.toLocaleString("en-us", options)
      );

      overlay.hide();
    },
    deleteVisit(period_id, period_pos) {
      // deActivateVisit(g.periodid, g.active, i);
      var self = this;
      var url = common.DataService;
      var message = "";
      var bnt_text = "";
      let title = this.tableData[period_pos].title;

      // overlay.show();
      message =
        "Are you sure you want to Delete the Visit with Title: <b>" +
        title +
        "</b>? <br><br>.";
      bnt_text = "Delete";
      btn_class = " btn-danger ";
      response_txt =
        "Visit with Title: <b>" + title + "</b> Successfully Deleted";

      $.confirm({
        title: "WARNING!",
        content: message,
        buttons: {
          delete: {
            text: bnt_text,
            btnClass: "btn mr-1" + btn_class,
            action: function () {
              //Attempt Delete
              axios
                .post(url + "?qid=1002&period_id=" + period_id)
                .then(function (response) {
                  overlay.hide();
                  if (response.data.result_code == "200") {
                    self.loadTableData();
                    alert.Success("SUCCESS", response_txt);
                  } else {
                    alert.Error(
                      "ERROR",
                      "Unable to Delete Visit with Title <b>" +
                        title +
                        "</b> at the moment please try again later"
                    );
                  }
                  overlay.hide();
                })
                .catch(function (error) {
                  overlay.hide();
                  alert.Error("ERROR", error);
                });
            },
          },
          cancel: function () {
            // Do nothing
            overlay.hide();
          },
        },
      });
    },
    updateVisit() {
      var self = this;
      var url = common.DataService;
      // overlay.show();

      let title = this.tableData[this.visitForm.period_pos].title;

      $.confirm({
        title: "WARNING!",
        content:
          "Are you sure you want to Update a Visit with Title: <b>" +
          title +
          "</b>?",
        buttons: {
          delete: {
            text: "Update",
            btnClass: "btn btn-danger mr-1 text-capitalize",
            action: function () {
              //Attempt Update
              axios
                .post(url + "?qid=1001", JSON.stringify(self.visitForm))
                .then(function (response) {
                  if (response.data.result_code == "200") {
                    self.resetVisitForm();
                    self.addVisitModal = false;
                    $("#addNewVisit").modal("hide");
                    self.loadTableData();
                    alert.Success("Success", response.data.message);
                    overlay.hide();
                  } else {
                    overlay.hide();
                    alert.Error("Error", response.data.message);
                  }
                  // Unable to create new record
                })
                .catch(function (error) {
                  alert.Error("ERROR", error);
                  overlay.hide();
                });
            },
          },
          cancel: function () {
            // Do nothing
            self.resetVisitForm();
            // self.addParticipantModal = false;
            self.addVisitModal = false;
            $("#addNewVisit").modal("hide");
            overlay.hide();
          },
        },
      });
    },
  },
  computed: {},
  template: `

        <div class="row" id="basic-table">

            <div class="col-md-8 col-sm-12 col-12 mb-0">
                <h2 class="content-header-title header-txt float-left mb-0">SMC</h2>
                <div class="breadcrumb-wrapper">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../smc">Home</a></li>
                        <li class="breadcrumb-item active">Visit Mgmt.</li>
                    </ol>
                </div>
            </div>

            <div class="col-md-4 col-sm-12 col-12 text-md-right text-right d-md-block">
                <div class="btn-group mr-1">
                    <button v-if="permission.permission_value > 2" type="button" @click="showAddVisitModal()" data-target="#addNewVisit" data-toggle="tooltip" data-placement="top" title="Create Visit" class="btn btn-outline-primary round"><i data-feather='plus'></i></button>
                    <button type="button" class="btn btn-outline-primary round searchBtn" @click="refreshData()" data-toggle="tooltip" data-placement="top" title="Refresh">
                      <i class="feather icon-refresh-cw"></i>         
                    </button>  
                </div>
            </div>

            <div class="col-12 mt-1">
                <div class="card">

                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr> 
                                    <th width="60px" class="pr-0 pl-0">#</th>
                                    <th>Visit Title</th>
                                    <th>Start/End Date</th>
                                    <th>Created Date</th>
                                    <th>Updated Date</th>
                                    <th style="padding-left: 5px !important; padding-right: 10px !important">Status</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(g, i) in tableData">
                                    <td>{{i+1}}</td>
                                    <td><a href="#" class="user_name text-truncate text-body">{{ g.title }}</a></a></td>
                                    <td>
                                        <div class="d-flex justify-content-left align-items-center">
                                            <div class="d-flex flex-column">
                                                <div class="emp_post">{{displayMonthDay(g.start_date)}}</div>
                                                <span></span>
                                                <div class="emp_post">{{displayMonthDay(g.end_date)}}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{ displayDate(g.created) }}</td>
                                    <td>{{ displayDate(g.updated) }}</td>
                                    <td style="padding-left: 5px !important; padding-right: 10px !important"><span class="badge rounded-pill font-small-1" :class="g.active==1? 'bg-success' : 'bg-danger'">{{g.active==1? 'Active' : 'Inactive'}}</span></td>
                                    <td>
                                        <div class="dropdown">
                                            <button type="button" class="btn btn-sm dropdown-toggle hide-arrow" data-toggle="dropdown">
                                                <i class="feather icon-more-vertical"></i>
                                            </button>
                                            <div class="dropdown-menu">
                                                <a  v-if="permission.permission_value ==3" class="dropdown-item" href="javascript:void(0);" @click="deActivateVisit(g.periodid, g.active, i)"><i class="feather" :class="g.active == '1'? 'icon-x-circle' : 'icon-check-circle'"></i> {{g.active == '1'? ' Deactivate ' : ' Activate '}}</a>
                                                <a v-if="permission.permission_value >=2" class="dropdown-item" href="javascript:void(0);" data-toggle="modal" data-target="#addNewVisit" @click="editVisit(g.periodid, i)"><i class="feather icon-edit"></i> Edit</a>
                                                <a v-if="permission.permission_value ==3" class="dropdown-item" href="javascript:void(0);" @click="deleteVisit(g.periodid, i)"><i class="feather icon-trash-2"></i> Delete</a>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="tableData.length == 0"><td class="text-center pt-2" colspan="7"><small>No Visit Added</small></td></tr>

                            </tbody>
                        </table>

                    </div>

                </div>
            </div>

            <!-- Modal to add new visit starts-->
            <div class="modal modal-slide-in" id="addNewVisit" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-scrollable modal-xl">
                    <form class="add-new-user modal-content pt-0" @submit.stop.prevent="onSubmitCreateVisit(visitBtn)">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close" @click="hideAddVisitModal()">×</button>
                        <div class="modal-header mb-1">
                            <h5 class="modal-title" id="exampleModalLabel">{{visitBtn}} Visit</h5>
                        </div>
                        <div class="modal-body flex-grow-1">
                            <div class="form-group">
                                <label class="form-label" for="total-user">Visit Title</label>
                                <input required v-model="visitForm.period_title" class="form-control" placeholder="Visit Title" />
                            </div>

                            <div class="form-group" id="start">
                                <label class="form-label" for="total-user">Start Date</label>
                                <input required v-model="visitForm.start_date" id="start-date" class="form-control date" placeholder="Start Date" />
                            </div>

                            <div class="form-group" id="end">
                                <label class="form-label" for="total-user">End Date</label>
                                <input required  v-model="visitForm.end_date" id="end-date" class="form-control date" placeholder="End Date" />
                            </div>

                            <div class="mt-2">
                                <button type="submit" class="btn btn-primary mr-1 data-submit">{{visitBtn}}</button>
                                <button type="reset" class="btn btn-outline-secondary" data-dismiss="modal" @click="hideAddVisitModal()">Cancel</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <!-- Modal to add new visit Ends-->


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
