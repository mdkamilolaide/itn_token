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
// page: 'training', //  page by name training | session | participant | ...
// g-event-update

Vue.component("page-body", {
  data: function () {
    return {
      page: "training", //  page by name training | session | participant | attendance ...
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

            <div v-show="page == 'training'">
                <training_list/>
            </div>

            <div v-show="page == 'session'">
                <training_session/>
            </div>

            <div v-show="page == 'participant'">
                <participant_list/>
            </div>

            <div v-show="page == 'attendance'">
                <attendance_list/>
            </div>
        </div>
    </div>
    `,
});

Vue.component("training_list", {
  data: function () {
    return {
      tableData: [],
      checkToggle: false,
      filterState: false,
      filters: false,
      url: common.TableService,
      tableOptions: {
        total: 1, //Total record
        pageLength: 1, //Total
        perPage: 10,
        currentPage: 1,
        orderDir: "desc", // (asc|desc)
        orderField: 0, //(Order fields)
        limitStart: 0, //(currentPage - 1) * perPage
        isNext: false,
        isPrev: false,
        aLength: [10, 20, 50, 100],
        filterParam: {
          trainingid: "",
          title: "",
          active: "",
        },
      },
      permission: getPermission(per, "activity"),
      errors: [],
      trainingBtn: "",
      addTrainingModal: false,
      trainingForm: {
        training_id: "",
        title: "",
        description: "",
        start_date: "2022-03-21",
        end_date: "2022-03-23",
        geoLevel: "state",
        geoLevelId: 0,
      },
      geoIndicator: {
        state: 50,
        currentLevelId: 0,
        ward: "",
      },
      geoLevelData: [],
      sysDefaultData: [],
      lgaLevelData: [],
      clusterLevelData: [],
      wardLevelData: [],
      dpLevelData: [],
    };
  },
  mounted() {
    /*  Manages events Listening    */
    this.getGeoLevel();
    this.getsysDefaultDataSettings();
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
      var url = common.TableService;
      overlay.show();

      axios
        .get(
          url +
            "?qid=101&draw=" +
            self.tableOptions.currentPage +
            "&order_column=" +
            self.tableOptions.orderField +
            "&length=" +
            self.tableOptions.perPage +
            "&start=" +
            self.tableOptions.limitStart +
            "&order_dir=" +
            self.tableOptions.orderDir +
            "&ac=" +
            self.tableOptions.filterParam.active +
            "&id=" +
            self.tableOptions.filterParam.trainingid +
            "&tr=" +
            self.tableOptions.filterParam.title,
        )
        .then(function (response) {
          self.tableData = response.data.data; //All Data
          self.tableOptions.total = response.data.recordsTotal; //Total Records
          if (self.tableOptions.currentPage == 1) {
            self.paginationDefault();
          }
          overlay.hide();
        })
        .catch(function (error) {
          overlay.hide();
          alert.Error("ERROR", error);
        });
    },
    selectAll() {
      /*  Manages all check box selection checked */
      if (this.tableData.length > 0) {
        for (let i = 0; i < this.tableData.length; i++) {
          this.tableData[i].pick = true;
        }
      }
    },
    uncheckAll() {
      /*  Manages unchecking of all check box checked */
      if (this.tableData.length > 0) {
        for (let i = 0; i < this.tableData.length; i++) {
          this.tableData[i].pick = false;
        }
      }
    },
    selectToggle() {
      /*  Manages all check box checking and unchecking  */
      if (this.checkToggle == false) {
        this.selectAll();
        this.checkToggle = true;
      } else {
        this.uncheckAll();
        this.checkToggle = false;
      }
    },
    checkedBg(pickOne) {
      /*  Manages the checking of a checkbox */
      return pickOne != "" ? "bg-select" : "";
    },
    toggleFilter() {
      /*  Manages the toggling of a filter box */
      if (this.filterState === false) {
        this.filters = false;
      }
      return (this.filterState = !this.filterState);
    },
    selectedItems() {
      /*  Manages the selections of checkedor selected data object */
      let selectedItems = [];
      if (this.tableData.length > 0) {
        for (let i = 0; i < this.tableData.length; i++) {
          if (this.tableData[i].pick) {
            selectedItems.push(this.tableData[i]);
          }
        }
      }
      return selectedItems;
    },
    selectedID() {
      /*  Manages the selections of checkedor selected data object */
      let selectedIds = [];
      if (this.tableData.length > 0) {
        for (let i = 0; i < this.tableData.length; i++) {
          if (this.tableData[i].pick) {
            selectedIds.push(this.tableData[i].userid);
          }
        }
      }
      return selectedIds;
    },
    nextPage() {
      /*  Manages the selections of checked or selected data object */
      this.tableOptions.currentPage += 1;
      this.paginationDefault();
      this.loadTableData();
    },
    prevPage() {
      /*  Manages the selections of checked or selected data object */
      this.tableOptions.currentPage -= 1;
      this.paginationDefault();
      this.loadTableData();
    },
    currentPage() {
      this.paginationDefault();
      if (this.tableOptions.currentPage < 1) {
        alert.Error("ERROR", "The Page requested doesn't exist");
      } else if (this.tableOptions.currentPage > this.tableOptions.pageLength) {
        alert.Error("ERROR", "The Page requested doesn't exist");
      } else {
        this.loadTableData();
      }
    },
    paginationDefault() {
      //  total page
      this.tableOptions.pageLength = Math.ceil(
        this.tableOptions.total / this.tableOptions.perPage,
      );

      // Page Limit
      this.tableOptions.limitStart = Math.ceil(
        (this.tableOptions.currentPage - 1) * this.tableOptions.perPage,
      );

      //  Next
      if (
        this.tableOptions.currentPage < this.tableOptions.pageLength &&
        this.tableOptions.currentPage != this.tableOptions.pageLength
      ) {
        this.tableOptions.isNext = true;
      } else {
        this.tableOptions.isNext = false;
      }

      // Previous
      if (this.tableOptions.currentPage > 1) {
        this.tableOptions.isPrev = true;
      } else {
        this.tableOptions.isPrev = false;
      }
    },
    changePerPage(val) {
      this.tableOptions.perPage = val;
      this.paginationDefault();
      this.loadTableData();
    },
    sort(col) {
      if (this.tableOptions.orderField === col) {
        this.tableOptions.orderDir =
          this.tableOptions.orderDir === "asc" ? "desc" : "asc";
      } else {
        this.tableOptions.orderField = col;
      }

      this.paginationDefault();
      this.loadTableData();
    },
    applyFilter() {
      // Check if any filter fields are filled
      let checkFill = 0;
      checkFill += this.tableOptions.filterParam.title != "" ? 1 : 0;
      checkFill += this.tableOptions.filterParam.trainingid != "" ? 1 : 0;
      checkFill += this.tableOptions.filterParam.active != "" ? 1 : 0;

      if (checkFill > 0) {
        this.toggleFilter();
        this.filters = true;
        this.paginationDefault();
        this.loadTableData();
      } else {
        alert.Error("ERROR", "Invalid required data");
        return;
      }
    },
    removeSingleFilter(column_name) {
      // this.tableOptions.filterParam + '.' + column_name == "";
      this.tableOptions.filterParam[column_name] = "";
      let g = 0;
      for (let i in this.tableOptions.filterParam) {
        if (this.tableOptions.filterParam[i] != "") {
          g++;
        }
      }
      if (g == 0) {
        this.filters = false;
      }
      this.paginationDefault();
      this.loadTableData();
    },
    clearAllFilter() {
      this.filters = false;
      this.tableOptions.filterParam.title =
        this.tableOptions.filterParam.trainingid =
        this.tableOptions.filterParam.active =
        this.tableOptions.filterParam.geo_level =
        this.tableOptions.filterParam.geo_level_id =
        this.tableOptions.filterParam.geo_string =
          "";

      this.paginationDefault();
      this.loadTableData();
    },
    capitalize(word) {
      if (word) {
        const loweredCase = word.toLowerCase();
        return word[0].toUpperCase() + loweredCase.slice(1);
      } else {
        return word;
      }
    },
    deActivateUser(training_id, status, ui_id) {
      var self = this;
      var url = common.DataService;
      var message = "";
      var bnt_text = "";

      // overlay.show();
      if (status == "1") {
        message =
          "Are you sure you want to Deactivate the Activity with ID <b>" +
          ui_id +
          "</b>? <br><br>Make sure you are sure that you want to deactivate the Activity.";
        bnt_text = "Deactivate";
        btn_class = " btn-danger ";
        response_txt =
          "Activity with ID <b>" + ui_id + "</b> Successfully Deactivated";
      } else {
        message =
          "Are you sure you want to Activate an Activity with ID <b>" +
          ui_id +
          "</b>? <br><br>Make sure you are sure that you want to activate the Activity.";
        bnt_text = "Activate";
        btn_class = " btn-success ";
        response_txt =
          "Activity with ID <b>" + ui_id + "</b> Successfully Activated";
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
                .post(url + "?qid=103&e=" + training_id)
                .then(function (response) {
                  overlay.hide();
                  if (response.data.result_code == "200") {
                    self.loadTableData();
                    alert.Success("SUCCESS", response_txt);
                  } else {
                    alert.Error(
                      "ERROR",
                      "Unable to De/Activate Activity with ID <b>" +
                        ui_id +
                        "</b> at the moment please try again later",
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
    showaddTrainingModal() {
      this.resettrainingForm();
      $("#addNewTraining").modal("show");
      this.addTrainingModal = true;
      this.trainingBtn = "Create";
    },
    hideaddTrainingModal() {
      this.resettrainingForm();
      this.addTrainingModal = false;
      this.trainingBtn = "";
      $("#addNewTraining").modal("hide");
    },
    goToSessionLists(trainingid) {
      EventBus.$emit("g-event-goto-page", {
        trainingid: trainingid,
        page: "session",
      });
    },
    goToParticipantList(trainingid) {
      EventBus.$emit("g-event-goto-page", {
        trainingid: trainingid,
        page: "participant",
      });
    },
    getsysDefaultDataSettings() {
      /*  Manages the loading of System default settings */
      var self = this;
      var url = common.DataService;
      overlay.show();

      axios
        .get(url + "?qid=gen007")
        .then(function (response) {
          if (response.data.data.length > 0) {
            self.sysDefaultData = response.data.data[0]; //All Data
            // self.getLgasLevel(response.data.data[0].stateid);
            //  Set preventDefault();
            self.trainingForm.geoLevel = "state";
            self.trainingForm.geoLevelId = response.data.data[0].stateid;
            // self.trainingForm.geoLevelId = 0;
          }

          overlay.hide();
        })
        .catch(function (error) {
          overlay.hide();
          alert.Error("ERROR", error);
        });
    },
    getGeoLevel() {
      /*  Manages the loading of Geo Level data */
      var self = this;
      var url = common.DataService;
      overlay.show();

      axios
        .get(url + "?qid=gen001")
        .then(function (response) {
          self.geoLevelData = response.data.data; //All Data
          // self.tableOptions.total = response.data.recordsTotal; //Total Records
          overlay.hide();
        })
        .catch(function (error) {
          overlay.hide();
          alert.Error("ERROR", error);
        });
    },
    getLgasLevel(stateid) {
      /*  Manages the loading of Geo Level data */
      var self = this;
      var url = common.DataService;
      overlay.show();

      axios
        .post(url + "?qid=gen003", JSON.stringify(stateid))
        .then(function (response) {
          self.lgaLevelData = response.data.data; //All Data
          overlay.hide();
        })
        .catch(function (error) {
          overlay.hide();
          alert.Error("ERROR", error);
        });
    },
    getClusterLevel() {
      /*  Manages the loading of Geo Level data */
      var self = this;
      var url = common.DataService;
      overlay.show();

      axios
        .get(url + "?qid=gen004&e=" + self.geoIndicator.cluster)
        .then(function (response) {
          self.clusterLevelData = response.data.data; //All Data
          overlay.hide();
        })
        .catch(function (error) {
          overlay.hide();
          alert.Error("ERROR", error);
        });
    },
    getWardLevel() {
      /*  Manages the loading of Geo Level data */
      var self = this;
      var url = common.DataService;
      overlay.show();

      axios
        .get(url + "?qid=gen005&e=" + self.geoIndicator.lga)
        .then(function (response) {
          self.wardLevelData = response.data.data; //All Data
          overlay.hide();
        })
        .catch(function (error) {
          overlay.hide();
          alert.Error("ERROR", error);
        });
    },
    getDpLevel() {
      /*  Manages the loading of Geo Level data */
      var self = this;
      var url = common.DataService;
      overlay.show();

      axios
        .get(url + "?qid=gen006")
        .then(function (response) {
          self.dpLevel = response.data.data; //All Data
          overlay.hide();
        })
        .catch(function (error) {
          overlay.hide();
          alert.Error("ERROR", error);
        });
    },
    changeGeoLevel(event) {
      // console.log(Object.values(tableData).indexOf(event.target.options[event.target.options.selectedIndex].value));
      // this.trainingForm.geoLevelId = event.target.options[event.target.options.selectedIndex].value;

      if (
        this.trainingForm.geoLevel == "country" ||
        this.trainingForm.geoLevel == "dp"
      ) {
        alert.Error(
          "ERROR",
          "Invalid Geo-Level selected, please select a valid Geo-Level",
        );
      } else if (this.trainingForm.geoLevel == "state") {
        //  State here
      } else if (this.trainingForm.geoLevel == "lga") {
        //  Lga here
      } else if (this.trainingForm.geoLevel == "cluster") {
        //  Cluster here
      } else if (this.trainingForm.geoLevel == "ward") {
        // Ward here
      }
    },
    onSubmitCreateTraining(action) {
      var self = this;
      var url = common.DataService;
      if (action == "Create") {
        //Validate Form
        overlay.show();
        axios
          .post(url + "?qid=101", JSON.stringify(self.trainingForm))
          .then(function (response) {
            if (response.data.result_code == "201") {
              self.resettrainingForm();
              self.addTrainingModal = false;
              $("#addNewTraining").modal("hide");
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
        this.updateTraining();
      }
    },
    resettrainingForm() {
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

      this.trainingBtn = "";
      this.trainingForm.training_id = "";
      this.trainingForm.title = "";
      this.trainingForm.description = "";
      this.trainingForm.start_date = "";
      this.trainingForm.end_date = "";
      this.trainingForm.geoLevel = "state";
      this.trainingForm.geoLevelId = 0;
      // this.getsysDefaultDataSettings();
      overlay.hide();
    },
    refreshData() {
      this.paginationDefault();
      this.loadTableData();
    },
    editTraining(training_id, training_pos) {
      overlay.show();
      this.addTrainingModal = true;
      this.trainingBtn = "Update";
      this.trainingForm.training_id = training_id;
      this.trainingForm.title = this.tableData[training_pos].title;
      this.trainingForm.description = this.tableData[training_pos].description;
      const options = {
        year: "numeric",
        month: "long",
        day: "numeric",
      };
      this.trainingForm.start_date = this.tableData[training_pos].db_start_date;
      this.trainingForm.end_date = this.tableData[training_pos].db_end_date;

      let start_d = new Date(this.tableData[training_pos].db_start_date);
      let end_d = new Date(this.tableData[training_pos].db_end_date);
      $("#start .form-control.date.form-control.input").val(
        start_d.toLocaleString("en-us", options),
      );
      $("#end .form-control.date.form-control.input").val(
        end_d.toLocaleString("en-us", options),
      );

      this.trainingForm.geoLevel = this.tableData[training_pos].geo_location;
      this.trainingForm.geoLevelId = this.tableData[training_pos].location_id;
      overlay.hide();
    },
    updateTraining() {
      var self = this;
      var url = common.DataService;
      // overlay.show();

      $.confirm({
        title: "WARNING!",
        content:
          "Are you sure you want to Update an Activity with ID:" +
          self.trainingForm.training_id +
          "?",
        buttons: {
          delete: {
            text: "Update",
            btnClass: "btn btn-danger mr-1 text-capitalize",
            action: function () {
              //Attempt Update
              axios
                .post(url + "?qid=102", JSON.stringify(self.trainingForm))
                .then(function (response) {
                  if (response.data.result_code == "201") {
                    self.resettrainingForm();
                    self.addTrainingModal = false;
                    $("#addNewTraining").modal("hide");
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
            self.resettrainingForm();
            self.addParticipantModal = false;
            $("#addNewTraining").modal("hide");
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
                <h2 class="content-header-title header-txt float-left mb-0">Activity Mgmt.</h2>
                <div class="breadcrumb-wrapper">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../activity">Home</a></li>
                        <li class="breadcrumb-item active">Activity List</li>
                    </ol>
                </div>
            </div>

            <div class="col-md-4 col-sm-12 col-12 text-md-right text-right d-md-block">
                <div class="btn-group mr-1">
                    <button v-if="permission.permission_value > 2" type="button" @click="showaddTrainingModal()" data-target="#addNewTraining" data-toggle="tooltip" data-placement="top" title="Create an Activity" class="btn btn-outline-primary round"><i data-feather='plus'></i></button>
                    <button type="button" class="btn btn-outline-primary round searchBtn" @click="refreshData()" data-toggle="tooltip" data-placement="top" title="Refresh">
                        <i class="feather icon-refresh-cw"></i>         
                    </button>  
                    <button type="button" class="btn btn-outline-primary round searchBtn" @click="toggleFilter()" data-toggle="tooltip" data-placement="top" title="Filter">
                        <i class="feather" :class="filterState ? 'icon-x' : 'icon-filter'"></i>               
                    </button>
                    
                    <!--
                    <button class="btn btn-outline-primary round dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        Actions
                    </button>
                    <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                            <a v-if="permission.permission_value > 2" class="dropdown-item" href="javascript:void(0);" @click="">De/Activate User</a>
                            <a class="dropdown-item" href="javascript:void(0);">Download Badge</a>
                    </div>
                    -->

                </div>
            </div>
            <div class="col-12" v-if="filters">
                <div class="col-12 filter-bar">
                    <span class="badge badge-dark filter-box" @click="removeSingleFilter(i)" v-for="(filterParam, i) in tableOptions.filterParam" v-if="filterParam.length > 0">{{capitalize(i)}}: {{filterParam}} <i class="feather icon-x"></i></span>
                    <a href="#" class="float-right clear-filter" @click="clearAllFilter()">Clear</a>
                </div>
            </div>

            <div class="col-12 mt-1">
                <div class="card">
                    <div class="card-body py-1" v-show="filterState">
                        <form id="filterForm">
                            <div class="row">
                                <div class="col-12 col-sm-6 col-md-3 col-lg-3">
                                    <div class="form-group">
                                        <label>Activity Status</label>
                                        <select name="active" v-model="tableOptions.filterParam.active" class="form-control active">
                                            <option value="">All Activity</option>
                                            <option value="active">Active Activity</option>
                                            <option value="inactive">Inactive Activity</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-12 col-sm-6 col-md-3 col-lg-3">
                                    <div class="form-group">
                                        <label>Activity ID</label>
                                        <input type="text" v-model="tableOptions.filterParam.trainingid" class="form-control training-id" id="training-id" placeholder="Activity ID" name="trainingid" />
                                    </div>
                                </div>
                                <div class="col-12 col-sm-6 col-md-3 col-lg-3">
                                    <div class="form-group">
                                        <label>Activity Title</label>
                                        <input type="text" id="title" v-model="tableOptions.filterParam.title" class="form-control title" placeholder="Activity Title" name="title" />
                                    </div>
                                </div>
                                <div class="col-12 col-sm-6 col-md-3 col-lg-3">
                                    <div class="form-group mt-2 text-right">
                                        <button type="button" class="btn btn-md btn-primary"  @click="applyFilter()">Apply Filters</button>
                                    </div>
                                </div>
                                
                            </div>
                        </form>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <!--
                                    <th width="60px" style="padding-right: 2px !important;">

                                        <div class="custom-control custom-checkbox checkbox">
                                            <input type="checkbox" class="custom-control-input" @change="selectToggle()" id="all-check" />
                                            <label class="custom-control-label" for="all-check"></label>
                                        </div>
                                    </th>
                                    -->
                                    <th @click="sort(0)" width="60px">
                                        ID
                                        <i class="feather icon-chevron-up sort-up" :class="(tableOptions.orderField == 0 && tableOptions.orderDir =='asc')? 'active-sort': ''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 0 && tableOptions.orderDir =='desc')? 'active-sort': ''"></i>
                                    </th>
                                    <th @click="sort(1)">
                                        Activities
                                        <i class="feather icon-chevron-up sort-up" :class="(tableOptions.orderField == 1 && tableOptions.orderDir =='asc')? 'active-sort': ''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 1 && tableOptions.orderDir =='desc')? 'active-sort': ''"></i>
                                    </th>
                                    <th @click="sort(2)">
                                        Location
                                        <i class="feather icon-chevron-up sort-up" :class="(tableOptions.orderField == 2 && tableOptions.orderDir =='asc')? 'active-sort': ''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 2 && tableOptions.orderDir =='desc')? 'active-sort': ''"></i>
                                    </th>
                                    <th @click="sort(9)" width="100px">
                                        Participants
                                        <i class="feather icon-chevron-up sort-up" :class="(tableOptions.orderField == 9 && tableOptions.orderDir =='asc')? 'active-sort': ''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 9 && tableOptions.orderDir =='desc')? 'active-sort': ''"></i>
                                    </th>
                                    <th @click="sort(7)">
                                        Start/End Date
                                        <i class="feather icon-chevron-up sort-up" :class="(tableOptions.orderField == 7 && tableOptions.orderDir =='asc')? 'active-sort': ''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 7 && tableOptions.orderDir =='desc')? 'active-sort': ''"></i>
                                    </th>
                                    <th @click="sort(5)" style="padding-left: 5px !important; padding-right: 10px !important">
                                        Status
                                        <i class="feather icon-chevron-up sort-up" :class="(tableOptions.orderField == 5 && tableOptions.orderDir =='asc')? 'active-sort': ''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 5 && tableOptions.orderDir =='desc')? 'active-sort': ''"></i>
                                    </th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(g, i) in tableData" :class="checkedBg(g.pick)">
                                    <!--
                                    <td  style="padding-right: 2px !important;">
                                        <div class="custom-control custom-checkbox checkbox">
                                            <input type="checkbox" class="custom-control-input" :id="g.trainingid" v-model="g.pick" />
                                            <label class="custom-control-label" :for="g.trainingid"></label>
                                        </div>
                                    </td>
                                    -->
                                    <td>{{g.ui_id}}</td>
                                    <td>
                                        <div class="d-flex justify-content-left align-items-center">
                                            <div class="d-flex flex-column">
                                                <a href="#" class="user_name text-truncate text-body">
                                                    <span class="fw-bolder">{{g.title}}</span>
                                                </a>
                                                <small class="emp_post text-muted">{{g.description}}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{capitalize(g.geo_location)}}</td>
                                    <td>{{g.participant_count}}</td>
                                    <td>
                                        <div class="d-flex justify-content-left align-items-center">
                                            <div class="d-flex flex-column">
                                                <small class="emp_post text-muted">{{g.start_date}}</small>
                                                <small class="emp_post text-muted">{{g.end_date}}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td style="padding-left: 5px !important; padding-right: 10px !important"><span class="badge rounded-pill font-small-1" :class="g.active==1? 'bg-success' : 'bg-danger'">{{g.active==1? 'Active' : 'Inactive'}}</span></td>
                                    <td>
                                        <div class="dropdown">
                                            <button type="button" class="btn btn-sm dropdown-toggle hide-arrow" data-toggle="dropdown">
                                                <i class="feather icon-more-vertical"></i>
                                            </button>
                                            <div class="dropdown-menu">
                                                <a class="dropdown-item" href="javascript:void(0);" @click="goToSessionLists(g.trainingid)"><i class="feather icon-clock"></i> Sessions</a>
                                                <a class="dropdown-item" href="javascript:void(0);" @click="goToParticipantList(g.trainingid)"><i class="feather icon-eye"></i> Participants</a>
                                                <a  v-if="permission.permission_value ==3" class="dropdown-item" href="javascript:void(0);" @click="deActivateUser(g.trainingid, g.active, g.ui_id)"><i class="feather" :class="g.active == '1'? 'icon-x-circle' : 'icon-check-circle'"></i> {{g.active == '1'? ' Deactivate ' : ' Activate '}}</a>
                                                <a v-if="permission.permission_value >=2" class="dropdown-item" href="javascript:void(0);" data-toggle="modal" data-target="#addNewTraining" @click="editTraining(g.trainingid, i)"><i class="feather icon-edit"></i> Edit</a>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="tableData.length == 0"><td class="text-center pt-2" colspan="8"><small>No Activity Added</small></td></tr>

                            </tbody>
                        </table>

                    </div>

                    <div class="card-footer">
                        <div class="content-fluid">
                            <div class="row">
                                <div class="col-12 col-xl-4 col-md-4 col-sm-5">
                                    <div class="dropdown sort-dropdown mb-1 mb-sm-0">
                                        <button class="btn filter-btn btn-primary dropdown-toggle border text-dark" type="button" id="tablePaginationDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                            {{tableOptions.limitStart+1}} - {{tableOptions.limitStart+tableData.length}} of {{tableOptions.total}} 
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-right" aria-labelledby="tablePaginationDropdown">
                                            <a @click="changePerPage(g)" v-for="g in tableOptions.aLength" class="dropdown-item" href="javascript:void(0);">{{g}}</a>
                                        </div>
                                    </div>                            
                                </div>

                                <div class="col-12 col-xl-8 col-md-8 col-sm-7 text-right text-pag">
                                    
                                    <div class="btn-group">
                                        <button type="button" @click="prevPage()" class="btn btn-sm btn-primary round btn-page-block-overlay" :disabled="tableOptions.isPrev? false: true">
                                            <i data-feather='chevron-left'></i> Prev
                                        </button>
                                        
                                        <input @keyup.13="currentPage()" class="btn btn-page-block-overlay btn-sm btn-outline-primary pagination-input" type="number" v-model.number="tableOptions.currentPage" :max="tableOptions.pageLength" />
                                        
                                        <button class="btn btn-outline-primary btn-page-block-overlay border-l-0">
                                            <small class="form-text text-primary">  of {{this.tableOptions.pageLength}} </small>
                                        </button>
                                        
                                        <button type="button" @click="nextPage()" class="btn btn-sm btn-primary round"  :disabled="tableOptions.isNext? false: true">
                                            Next <i data-feather='chevron-right'></i>
                                        </button>
                                        
                                    </div>

                                </div>
                            </div>
                        </div>

                    </div>

                    <div class="mb-50"></div>
                </div>
            </div>

            <!-- Modal to add new training starts-->
            <div class="modal modal-slide-in" id="addNewTraining" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-scrollable modal-xl">
                    <form class="add-new-user modal-content pt-0" @submit.stop.prevent="onSubmitCreateTraining(trainingBtn)">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close" @click="hideaddTrainingModal()">×</button>
                        <div class="modal-header mb-1">
                            <h5 class="modal-title" id="exampleModalLabel">{{trainingBtn}} Activity</h5>
                        </div>
                        <div class="modal-body flex-grow-1">
                            <div class="form-group">
                                <label class="form-label" for="total-user">Activity Title</label>
                                <input required v-model="trainingForm.title" class="form-control" placeholder="Activity Title" />
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="group-name">Description</label>
                                <textarea v-model="trainingForm.description" class="form-control" placeholder="Activity Description"></textarea>
                            </div>

                            <div class="form-group" id="start">
                                <label class="form-label" for="total-user">Start Date</label>
                                <input required v-model="trainingForm.start_date" id="start-date" class="form-control date" placeholder="Start Date" />
                            </div>

                            <div class="form-group" id="end">
                                <label class="form-label" for="total-user">End Date</label>
                                <input required  v-model="trainingForm.end_date" id="end-date" class="form-control date" placeholder="End Date" />
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="user-role">Geo Level</label>
                                <select id="user-role" @change="changeGeoLevel($event)" class="form-control" v-model="trainingForm.geoLevel">
                                    <option v-for="geo in geoLevelData" :value="geo.geo_level" :selected="geo.geo_level === trainingForm.geoLevel">{{capitalize(geo.geo_level)}}</option>
                                </select>
                            </div>

                            <div class="mt-2">
                                <button type="submit" class="btn btn-primary mr-1 data-submit">{{trainingBtn}}</button>
                                <button type="reset" class="btn btn-outline-secondary" data-dismiss="modal" @click="hideaddTrainingModal()">Cancel</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <!-- Modal to add new training Ends-->


        </div>
    `,
});

// Training Session List Page
Vue.component("training_session", {
  data: function () {
    return {
      tableData: [],
      addSessionModal: false,
      sessionBtn: "",
      sessionForm: {
        trainingid: "",
        sessionid: "",
        title: "",
        date: "",
        altdate: "",
      },
      permission: getPermission(per, "activity"),
    };
  },
  mounted() {
    EventBus.$on("g-event-goto-page", this.gotoPageHandler);
    this.loadTableData();
    $(".date").flatpickr({
      altInput: true,
      altFormat: "F j, Y",
      dateFormat: "Y-m-d",
      minDate: "today",
    });
  },
  methods: {
    gotoPageHandler(data) {
      this.sessionForm.trainingid = data.trainingid;
      this.sessionForm.sessionid = data.sessionid;
      this.sessionForm.title = data.title;
      this.loadTableData();
    },
    goToTrainingList() {
      EventBus.$emit("g-event-goto-page", { page: "training", trainingid: "" });
    },
    goToAttendanceList(sessionid, title) {
      EventBus.$emit("g-event-goto-page", {
        page: "attendance",
        trainingid: this.sessionForm.trainingid,
        sessionid: sessionid,
        title: title,
      });
    },
    loadTableData() {
      /*  Manages the loading of table data */
      var self = this;
      var url = common.DataService;
      overlay.show();

      axios
        .get(url + "?qid=104&e=" + self.sessionForm.trainingid)
        .then(function (response) {
          self.tableData = response.data.data; //All Data
          overlay.hide();
        })
        .catch(function (error) {
          overlay.hide();
          alert.Error("ERROR", error);
        });
    },
    resetForm() {
      $("#d")
        .flatpickr({
          altInput: true,
          altFormat: "F j, Y",
          dateFormat: "Y-m-d",
          minDate: "today",
        })
        .clear();

      this.sessionBtn = "";
      this.sessionForm.title = "";
      this.sessionForm.sessionid = "";
      this.sessionForm.date = "";
      overlay.hide();
    },
    refreshData() {
      this.loadTableData();
    },
    capitalize(word) {
      if (word) {
        const loweredCase = word.toLowerCase();
        return word[0].toUpperCase() + loweredCase.slice(1);
      } else {
        return word;
      }
    },
    showSessionModal() {
      this.resetForm();
      this.addSessionModal = true;
      this.sessionBtn = "Create";
      $("#addSession").modal("show");

      // this.sessionForm.trainingid = this.trainingid;
    },
    hideaddSessionModal() {
      this.resetForm();
      this.addSessionModal = false;
      this.sessionBtn = "";
      $("#addSession").modal("hide");
    },
    onSubmitCreateSession(action) {
      var self = this;
      var url = common.DataService;
      if (action == "Create") {
        //Validate
        if (this.sessionForm.date != "") {
          overlay.show();
          axios
            .post(url + "?qid=105", JSON.stringify(self.sessionForm))
            .then(function (response) {
              if (response.data.result_code == "201") {
                self.resetForm();
                self.addSessionModal = false;
                $("#addSession").modal("hide");
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
          alert.Error(
            "Required Fields",
            "All fields are required to be filled",
          );
        }
      } else {
        this.updateSession();
      }
    },
    editSession(session_id, session_pos) {
      overlay.show();
      this.addSessionModal = true;
      this.sessionBtn = "Update";
      // this.sessionForm.trainingid = this.trainingid;
      this.sessionForm.sessionid = session_id;
      this.sessionForm.title = this.tableData[session_pos].title;
      this.sessionForm.date = this.tableData[session_pos].session_date;
      let date = new Date(this.tableData[session_pos].session_date);
      let options = {
        year: "numeric",
        month: "long",
        day: "numeric",
      };
      $(".form-control.date.form-control.input").val(
        date.toLocaleString("en-us", options),
      );
      overlay.hide();
    },
    updateSession() {
      var self = this;
      var url = common.DataService;
      // overlay.show();

      $.confirm({
        title: "WARNING!",
        content: "Are you sure you want to Update the Activity Session?",
        buttons: {
          delete: {
            text: "Update",
            btnClass: "btn btn-danger mr-1 text-capitalize",
            action: function () {
              //Attempt Update
              axios
                .post(url + "?qid=106", JSON.stringify(self.sessionForm))
                .then(function (response) {
                  if (response.data.result_code == "201") {
                    self.resetForm();
                    self.addSessionModal = false;
                    $("#addSession").modal("hide");
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
            self.resetForm();
            self.addSessionModal = false;
            $("#addSession").modal("hide");
            overlay.hide();
          },
        },
      });
    },
    deleteSession(session_id) {
      var url = common.DataService;
      var self = this;
      $.confirm({
        title: "WARNING!",
        content: "Are you sure you want to Delete an Activity Session?",
        buttons: {
          delete: {
            text: "Delete",
            btnClass: "btn btn-danger mr-1 text-capitalize",
            action: function () {
              //Attempt Update
              axios
                .post(url + "?qid=107&e=" + session_id)
                .then(function (response) {
                  if (response.data.result_code == "200") {
                    self.resetForm();
                    self.addSessionModal = false;
                    $("#addSession").modal("hide");
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
            self.resetForm();
            self.addSessionModal = false;
            $("#addSession").modal("hide");
            overlay.hide();
          },
        },
      });
    },
    async downlodAttendance(session_id, title) {
      var sid = session_id;

      var veriUrl = "qid=115&sid=" + sid;
      var dlString = "qid=102&id=" + sid;
      var filename = title + " Attendance - (" + sid + ")";
      overlay.show();

      //  count export data
      let count = new Promise((resolve, reject) => {
        $.ajax({
          url: common.DataService,
          type: "POST",
          data: veriUrl,
          dataType: "json",
          success: function (data) {
            resolve(data.total);
          },
        });
      });
      let result = await count; //  wait till the promise resolves (*)
      var downloadMax = common.ExportDownloadLimit;

      if (parseInt(result) > downloadMax) {
        //  stop download
        alert.Error(
          "Download Error",
          "Unable to download data because it has exceeded download limit, download limit is " +
            downloadMax,
        );
      } else if (parseInt(result) == 0) {
        alert.Error("Download Error", "No data found");
      } else {
        alert.Info("DOWNLOADING...", "Downloading " + result + " record(s)");
        //  Else continue download data
        var options = {
          fileName: filename,
        };

        let dl = new Promise((resolve, reject) => {
          $.ajax({
            url: common.ExportService,
            type: "POST",
            data: dlString,
            success: function (data) {
              resolve(data);
            },
          });
        });

        let outcome = await dl; //  Wait till downloaded
        var exportData = JSON.parse(outcome);
        Jhxlsx.export(exportData, options);
      }

      overlay.hide();
    },
  },
  template: `
        <div class="row">
            <div class="col-md-8 col-sm-12 col-12 mb-0">
                <h2 class="content-header-title header-txt float-left mb-0">Activity Mgmt.</h2>
                <div class="breadcrumb-wrapper">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../activity">Home</a></li>
                        <li class="breadcrumb-item"><a href="javascript:void(0);" @click="goToTrainingList()">Activity List</a></li>
                        <li class="breadcrumb-item active">Sessions</li>
                    </ol>
                </div>
            </div>

            <div class="col-md-4 col-sm-12 col-12 text-md-right text-right d-md-block">
                <div class="btn-group mr-1"> 
                    <button v-if="permission.permission_value > 2" type="button" @click="showSessionModal()" data-target="#addSession" data-toggle="tooltip" data-placement="top" title="Create New Session" class="btn btn-outline-primary round"><i data-feather='plus'></i></button>

                    <button type="button" class="btn btn-outline-primary round searchBtn" @click="refreshData()" data-toggle="tooltip" data-placement="top" title="Refresh">
                        <i class="feather icon-refresh-cw"></i>         
                    </button>       
                </div>
            </div>

            <!-- User Sidebar -->

            <div class="col-12 mt-2">
                <div class="card">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th width="60px">#</th>
                                    <th>Session Title</th>
                                    <th>Session Date</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(g, i) in tableData">
                                    <td>{{i+1}}</td>
                                    <td>{{g.title}}</td>
                                    <td>{{g.session_date}}</td>
                                    <td>
                                        <div class="dropdown">
                                            <button type="button" class="btn btn-sm dropdown-toggle hide-arrow" data-toggle="dropdown">
                                                <i class="feather icon-more-vertical"></i>
                                            </button>
                                            <div class="dropdown-menu">
                                                <a class="dropdown-item" href="javascript:void(0);" @click="goToAttendanceList(g.sessionid, g.title)"><i class="feather icon-eye"></i> Attendance</a>
                                                <a  v-if="permission.permission_value >=2" class="dropdown-item" href="javascript:void(0);" @click="downlodAttendance(g.sessionid, g.title)"><i class="feather icon-download"></i> Download Attendance</a>
                                                <a v-if="permission.permission_value >=2" class="dropdown-item" href="javascript:void(0);" @click="editSession(g.sessionid, i)" data-toggle="modal" data-target="#addSession"><i class="feather icon-edit"></i> Edit</a>
                                                <a v-if="permission.permission_value ==3" class="dropdown-item" href="javascript:void(0);" @click="deleteSession(g.sessionid)"><i class="feather icon-delete"></i> Delete</a>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="tableData.length == 0"><td class="text-center pt-2" colspan="4"><small>No Activity session Added</small></td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Modal to add new training starts-->
            <div class="modal modal-slide-in" id="addSession" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-scrollable modal-xl">
                    <form class="add-new-user modal-content pt-0" @submit.stop.prevent="onSubmitCreateSession(sessionBtn)">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close" @click="hideaddSessionModal()">×</button>
                        <div class="modal-header mb-1">
                            <h5 class="modal-title" id="exampleModalLabel" v-text="(sessionBtn=='Create')? 'Create New Activity Session': 'Edit Activity Session'"></h5>
                        </div>
                        <div class="modal-body flex-grow-1">
                            <div class="form-group">
                                <label class="form-label" for="total-user">Session Title</label>
                                <input required v-model="sessionForm.title" class="form-control" placeholder="Session Title" />
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="total-user">Session Date</label>
                                <input required v-model="sessionForm.date" id="d"  class="form-control date" placeholder="Session Date" />
                            </div>
                 
                            <div class="mt-2">
                                <button type="submit" class="btn btn-primary mr-1 data-submit">{{sessionBtn}}</button>
                                <button type="reset" class="btn btn-outline-secondary" data-dismiss="modal" @click="hideaddSessionModal()">Cancel</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <!-- Modal to add new training Ends-->


        </div>
    `,
});

// Training Participant List Page
Vue.component("participant_list", {
  data: function () {
    return {
      tableData: [],
      groupData: [],
      geoData: [],
      addBulkParticipantModal: false,
      participantBtn: "",
      participantForm: {
        trainingid: "",
        group_name: "",
        session_id: "",
      },
      permission: getPermission(per, "activity"),
      checkToggle: false,
      filterState: false,
      filters: false,
      url: common.TableService,
      tableOptions: {
        total: 1, //Total record
        pageLength: 1, //Total
        perPage: 10,
        currentPage: 1,
        orderDir: "asc", // (asc|desc)
        orderField: 1, //(Order fields)
        limitStart: 0, //(currentPage - 1) * perPage
        isNext: false,
        isPrev: false,
        aLength: [10, 20, 50, 100, 200, 300],
        filterParam: {
          name: "",
          loginid: "",
          geo_level: "",
          geo_level_id: "",
        },
      },
    };
  },
  mounted() {
    EventBus.$on("g-event-goto-page", this.gotoPageHandler);
    // this.loadTableData();
    this.getGeoLocation();
    this.getGroupData();
    let self = this;

    var select = $(".select2");
    select.each(function () {
      var $this = $(this);
      $this.wrap('<div class="position-relative"></div>');
      $this
        .select2({
          // the following code is used to disable x-scrollbar when click in select input and
          // take 100% width in responsive also
          dropdownAutoWidth: true,
          width: "100%",
          dropdownParent: $this.parent(),
        })
        .on("change", function () {
          self.setLocation(this.value);
        });
    });
    $(".select2-selection__arrow").html(
      '<i class="feather icon-chevron-down"></i>',
    );
    // self.tableOptions.filterParam.geo_level = document.getElementById("v_g_geo_level").value;
    // self.tableOptions.filterParam.geo_level_id = document.getElementById("v_g_geo_level_id").value;
  },
  methods: {
    gotoPageHandler(data) {
      this.participantForm.trainingid = data.trainingid;
      this.loadTableData();
    },
    goToTrainingList() {
      EventBus.$emit("g-event-goto-page", { page: "training", trainingid: "" });
    },
    loadTableData() {
      /*  Manages the loading of table data */
      var self = this;
      var url = common.TableService;
      overlay.show();
      // $geo_level = CleanData('gl');
      // $geo_level_id = CleanData('glid');
      axios
        .get(
          url +
            "?qid=102&draw=" +
            self.tableOptions.currentPage +
            "&order_column=" +
            self.tableOptions.orderField +
            "&length=" +
            self.tableOptions.perPage +
            "&start=" +
            self.tableOptions.limitStart +
            "&order_dir=" +
            self.tableOptions.orderDir +
            "&na=" +
            self.tableOptions.filterParam.name +
            "&id=" +
            self.participantForm.trainingid +
            "&lo=" +
            self.tableOptions.filterParam.loginid +
            "&gl=" +
            self.tableOptions.filterParam.geo_level +
            "&glid=" +
            self.tableOptions.filterParam.geo_level_id,
        )
        .then(function (response) {
          self.tableData = response.data.data; //All Data
          self.tableOptions.total = response.data.recordsTotal; //Total Records
          if (self.tableOptions.currentPage == 1) {
            self.paginationDefault();
          }
          overlay.hide();
        })
        .catch(function (error) {
          overlay.hide();
          alert.Error("ERROR", error);
        });
    },
    selectAll() {
      /*  Manages all check box selection checked */
      if (this.tableData.length > 0) {
        for (let i = 0; i < this.tableData.length; i++) {
          this.tableData[i].pick = true;
        }
      }
    },
    uncheckAll() {
      /*  Manages unchecking of all check box checked */
      if (this.tableData.length > 0) {
        for (let i = 0; i < this.tableData.length; i++) {
          this.tableData[i].pick = false;
        }
      }
    },
    selectToggle() {
      /*  Manages all check box checking and unchecking  */
      if (this.checkToggle == false) {
        this.selectAll();
        this.checkToggle = true;
      } else {
        this.uncheckAll();
        this.checkToggle = false;
      }
    },
    checkedBg(pickOne) {
      /*  Manages the checking of a checkbox */
      return pickOne != "" ? "bg-select" : "";
    },
    toggleFilter() {
      /*  Manages the toggling of a filter box */
      if (this.filterState === false) {
        this.filters = false;
      }
      return (this.filterState = !this.filterState);
    },
    selectedItems() {
      /*  Manages the selections of checkedor selected data object */
      let selectedItems = [];
      if (this.tableData.length > 0) {
        for (let i = 0; i < this.tableData.length; i++) {
          if (this.tableData[i].pick) {
            selectedItems.push(this.tableData[i]);
          }
        }
      }
      return selectedItems;
    },
    selectedID() {
      /*  Manages the selections of checkedor selected data object */
      let selectedIds = [];
      if (this.tableData.length > 0) {
        for (let i = 0; i < this.tableData.length; i++) {
          if (this.tableData[i].pick) {
            selectedIds.push(this.tableData[i].participant_id);
          }
        }
      }
      return selectedIds;
    },
    selectedUserID() {
      /*  Manages the selections of checkedor selected data object */
      let selectedIds = [];
      if (this.tableData.length > 0) {
        for (let i = 0; i < this.tableData.length; i++) {
          if (this.tableData[i].pick) {
            selectedIds.push(this.tableData[i].userid);
          }
        }
      }
      return selectedIds;
    },
    nextPage() {
      /*  Manages the selections of checked or selected data object */
      this.tableOptions.currentPage += 1;
      this.paginationDefault();
      this.loadTableData();
    },
    prevPage() {
      /*  Manages the selections of checked or selected data object */
      this.tableOptions.currentPage -= 1;
      this.paginationDefault();
      this.loadTableData();
    },
    currentPage() {
      this.paginationDefault();
      if (this.tableOptions.currentPage < 1) {
        alert.Error("ERROR", "The Page requested doesn't exist");
      } else if (this.tableOptions.currentPage > this.tableOptions.pageLength) {
        alert.Error("ERROR", "The Page requested doesn't exist");
      } else {
        this.loadTableData();
      }
    },
    paginationDefault() {
      //  total page
      this.tableOptions.pageLength = Math.ceil(
        this.tableOptions.total / this.tableOptions.perPage,
      );

      // Page Limit
      this.tableOptions.limitStart = Math.ceil(
        (this.tableOptions.currentPage - 1) * this.tableOptions.perPage,
      );

      //  Next
      if (
        this.tableOptions.currentPage < this.tableOptions.pageLength &&
        this.tableOptions.currentPage != this.tableOptions.pageLength
      ) {
        this.tableOptions.isNext = true;
      } else {
        this.tableOptions.isNext = false;
      }

      // Previous
      if (this.tableOptions.currentPage > 1) {
        this.tableOptions.isPrev = true;
      } else {
        this.tableOptions.isPrev = false;
      }
    },
    changePerPage(val) {
      this.tableOptions.perPage = val;
      this.paginationDefault();
      this.loadTableData();
    },
    sort(col) {
      if (this.tableOptions.orderField === col) {
        this.tableOptions.orderDir =
          this.tableOptions.orderDir === "asc" ? "desc" : "asc";
      } else {
        this.tableOptions.orderField = col;
      }

      this.paginationDefault();
      this.loadTableData();
    },
    applyFilter() {
      // Check if any filter fields are filled
      let checkFill = 0;
      checkFill += this.tableOptions.filterParam.loginid != "" ? 1 : 0;
      checkFill += this.tableOptions.filterParam.name != "" ? 1 : 0;
      checkFill += this.tableOptions.filterParam.geo_level != "" ? 1 : 0;
      checkFill += this.tableOptions.filterParam.geo_level_id != "" ? 1 : 0;

      if (checkFill > 0) {
        this.toggleFilter();
        this.filters = true;
        this.paginationDefault();
        this.loadTableData();
      } else {
        alert.Error("ERROR", "Invalid required data");
        return;
      }
    },
    removeSingleFilter(column_name) {
      // this.tableOptions.filterParam + '.' + column_name == "";
      this.tableOptions.filterParam[column_name] = "";
      if (column_name == "geo_string") {
        this.tableOptions.filterParam.geo_level =
          this.tableOptions.filterParam.geo_level_id =
          this.tableOptions.filterParam.geo_string =
            "";
      }
      let g = 0;
      for (let i in this.tableOptions.filterParam) {
        if (this.tableOptions.filterParam[i] != "") {
          g++;
        }
      }
      if (g == 0) {
        this.filters = false;
      }
      this.paginationDefault();
      this.loadTableData();
    },
    clearAllFilter() {
      this.filters = false;
      this.tableOptions.filterParam.loginid =
        this.tableOptions.filterParam.name = "";
      this.paginationDefault();
      this.loadTableData();
    },
    capitalize(word) {
      if (word) {
        const loweredCase = word.toLowerCase();
        return word[0].toUpperCase() + loweredCase.slice(1);
      } else {
        return word;
      }
    },
    checkIfEmpty(data) {
      if (data === null || data === "") {
        return "Nil";
      } else {
        return data;
      }
    },
    showParticipantModal() {
      this.resetForm();
      this.addParticipantModal = true;
      this.participantBtn = "Create";
      $("#addParticipant").modal("show");
      var self = this;
      var select1 = $(".select1");
      select1.each(function () {
        var $this = $(this);
        $this.wrap('<div class="position-relative"></div>');
        $this
          .select2({
            // the following code is used to disable x-scrollbar when click in select input and
            // take 100% width in responsive also
            dropdownAutoWidth: true,
            width: "100%",
            dropdownParent: $this.parent(),
          })
          .on("change", function () {
            // self.setLocation(this.value);
            self.participantForm.group_name = this.value;
          });
      });
      $(".select2-selection__arrow").html(
        '<i class="feather icon-chevron-down"></i>',
      );
      // this.sessionForm.trainingid = this.trainingid;
    },
    getGroupData() {
      var self = this;
      var url = common.DataService;
      overlay.show();

      axios
        .get(url + "?qid=010")
        .then(function (response) {
          self.groupData = response.data.data; //All Data
          // self.tableOptions.total = response.data.recordsTotal; //Total Records
          overlay.hide();
        })
        .catch(function (error) {
          overlay.hide();
          alert.Error("ERROR", error);
        });
    },
    hideParticipantModal() {
      this.resetForm();
      this.addParticipantModal = false;
      this.participantBtn = "";
      this.participantForm.group_name = "";
      $("#addParticipant").modal("hide");
    },
    resetForm() {
      this.participantBtn = "";
      for (var i = 0; i < this.participantForm.length; i++) {
        this.participantForm[i] = "";
      }
      $("#d")
        .flatpickr({
          altInput: true,
          altFormat: "F j, Y",
          dateFormat: "Y-m-d",
          minDate: "today",
        })
        .clear();
      overlay.hide();
    },
    refreshData() {
      this.loadTableData();
    },
    removeParticipant(action, login_id) {
      // action | Single Participant ID
      var self = this;
      var url = common.DataService;
      // overlay.show();

      if (action != "all") {
        var participantData = {
          trainingid: self.participantForm.trainingid,
          selectedid: Array(action),
        };

        $.confirm({
          title: "WARNING!",
          content:
            "Are you sure you want to Remove  <b>" +
            login_id +
            "</b> from the Activity?",
          buttons: {
            delete: {
              text: "Remove",
              btnClass: "btn btn-danger mr-1 text-capitalize",
              action: function () {
                //Attempt Update
                axios
                  .post(url + "?qid=109", JSON.stringify(participantData))
                  .then(function (response) {
                    // console.log(response.data);
                    if (response.data.result_code == "200") {
                      self.loadTableData();
                      EventBus.$emit("g-event-update", {});
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
              overlay.hide();
            },
          },
        });
      } else {
        var participantData = {
          trainingid: self.participantForm.trainingid,
          selectedid: self.selectedID(),
        };
        if (self.selectedID().length > 0) {
          $.confirm({
            title: "WARNING!",
            content:
              "Are you sure you want to Remove  <b>" +
              self.selectedID().length +
              "</b> participants from the Activity?",
            buttons: {
              delete: {
                text: "Remove",
                btnClass: "btn btn-danger mr-1 text-capitalize",
                action: function () {
                  //Attempt Update
                  axios
                    .post(url + "?qid=109", JSON.stringify(participantData))
                    .then(function (response) {
                      //   console.log(response.data);
                      if (response.data.result_code == "200") {
                        EventBus.$emit("g-event-update", {});
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
                overlay.hide();
              },
            },
          });
        } else {
          alert.Error("Error", "No Participant Selected");
          overlay.hide();
        }
      }
    },
    addParticipant() {
      var url = common.DataService;
      var self = this;
      $.confirm({
        title: "WARNING!",
        content:
          "Are you sure you want to Add all the user in <b>" +
          self.participantForm.group_name +
          " to the Activity</b>?",
        buttons: {
          delete: {
            text: "Add Group",
            btnClass: "btn btn-danger mr-1 text-capitalize",
            action: function () {
              //Attempt Update

              axios
                .post(url + "?qid=110", JSON.stringify(self.participantForm))
                .then(function (response) {
                  if (response.data.result_code == "200") {
                    self.resetForm();
                    self.addBulkParticipantModal = false;
                    $("#addParticipant").modal("hide");
                    EventBus.$emit("g-event-update", {});
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
            self.resetForm();
            self.addBulkParticipantModal = false;
            $("#addParticipant").modal("hide");
            overlay.hide();
          },
        },
      });
    },
    async exportParticipant() {
      var t_id = this.participantForm.trainingid;

      var veriUrl = "qid=114&tid=" + t_id;
      var dlString = "qid=101&id=" + t_id;
      var filename =
        "Participant List (Activity ID - " +
        String(t_id).padStart(3, "0") +
        ")";
      overlay.show();

      //  count export data
      let count = new Promise((resolve, reject) => {
        $.ajax({
          url: common.DataService,
          type: "POST",
          data: veriUrl,
          dataType: "json",
          success: function (data) {
            resolve(data.total);
          },
        });
      });
      let result = await count; //  wait till the promise resolves (*)
      var downloadMax = common.ExportDownloadLimit;

      if (parseInt(result) > downloadMax) {
        //  stop download
        alert.Error(
          "Download Error",
          "Unable to download data because it has exceeded download limit, download limit is " +
            downloadMax,
        );
      } else if (parseInt(result) == 0) {
        alert.Error("Download Error", "No data found");
      } else {
        alert.Info("DOWNLOADING...", "Downloading " + result + " record(s) ");
        //  Else continue download data
        var options = {
          fileName: filename,
        };

        let dl = new Promise((resolve, reject) => {
          $.ajax({
            url: common.ExportService,
            type: "POST",
            data: dlString,
            success: function (data) {
              resolve(data);
            },
          });
        });

        let outcome = await dl; //  Wait till downloaded
        var exportData = JSON.parse(outcome);
        Jhxlsx.export(exportData, options);
      }

      overlay.hide();
    },
    downloadBadge(userid) {
      let url = common.BadgeService;
      overlay.show();
      window.open(url + "?qid=002&e=" + userid, "_parent");
      overlay.hide();
    },
    downloadBadges() {
      // : href = "url+'?qid=003&e='+selectedID()"
      let url = common.BadgeService;
      //   console.log(this.selectedUserID());

      overlay.show();
      if (parseInt(this.selectedUserID().length) > 0) {
        window.open(url + "?qid=003&e=" + this.selectedUserID(), "_parent");
      } else {
        alert.Error("Badge Download Failed", "No user selected");
      }
      overlay.hide();
    },
    getGeoLocation() {
      /*  Manages the loading of Geo Level data */
      var self = this;
      var url = common.DataService;
      overlay.show();

      axios
        .get(url + "?qid=gen009")
        .then(function (response) {
          self.geoData = response.data.data; //All Data
          // self.tableOptions.total = response.data.recordsTotal; //Total Records
          overlay.hide();
        })
        .catch(function (error) {
          overlay.hide();
          alert.Error("ERROR", error);
        });
    },
    setLocation(select_index) {
      let i = select_index ? select_index : 0;
      this.tableOptions.filterParam.geo_level = this.geoData[i].geo_level;
      this.tableOptions.filterParam.geo_level_id = this.geoData[i].geo_level_id;
      this.tableOptions.filterParam.geo_string = this.geoData[i].geo_string;
    },
  },
  template: `
        <div class="row">
            <div class="col-md-8 col-sm-12 col-12 mb-0">
                <h2 class="content-header-title header-txt float-left mb-0">Activity Mgmt.</h2>
                <div class="breadcrumb-wrapper">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../users">Home</a></li>
                        <li class="breadcrumb-item"><a href="javascript:void(0);" @click="goToTrainingList()">Activity List</a></li>
                        <li class="breadcrumb-item active">Participants List</li>
                    </ol>
                </div>
            </div>

            <div class="col-md-4 col-sm-12 col-12 text-md-right text-right d-md-block">
                <div class="btn-group mr-1">
                    <button v-if="permission.permission_value >= 2" type="button" @click="showParticipantModal()" data-toggle="tooltip" data-placement="top" title="Create Participant" data-target="#addParticipant" class="btn btn-outline-primary round"><i data-feather='plus'></i></button>
                    <button type="button" class="btn btn-outline-primary round searchBtn" @click="refreshData()" data-placement="top" title="Refresh" data-toggle="tooltip" >
                        <i class="feather icon-refresh-cw"></i>         
                    </button>  
                    <button type="button" class="btn btn-outline-primary round searchBtn" @click="toggleFilter()" data-toggle="tooltip" data-placement="top" title="Filter">
                        <i class="feather" :class="filterState ? 'icon-x' : 'icon-filter'"></i>               
                    </button>
                    
                    <button class="btn btn-outline-primary round dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        Actions
                    </button>
                    <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                        <a v-if="permission.permission_value ==3" class="dropdown-item" href="javascript:void(0);" @click="removeParticipant('all', '')">Remove Participant</a>
                        <a class="dropdown-item" href="javascript:void(0)" @click="downloadBadges()">Download Badge</a>
                        <a v-if="permission.permission_value >=2" class="dropdown-item" href="javascript:void(0);" @click="exportParticipant()">Export Participant</a>
                    </div>
                </div>
            </div>

            <!-- User Sidebar -->

            <div class="col-12" v-if="filters">
                <div class="col-12 filter-bar">
                    <span class="badge badge-dark filter-box" @click="removeSingleFilter(i)" v-for="(filterParam, i) in tableOptions.filterParam" v-if="filterParam.length > 0 && i != 'geo_level' && i != 'geo_level_id'">{{capitalize(i)}}: {{filterParam}} <i class="feather icon-x"></i></span>
                    <a href="#" class="float-right clear-filter" @click="clearAllFilter()">Clear</a>
                </div>
            </div>

            <div class="col-12 mt-1">
                <div class="card custom-select-down">
                    <div class="card-body py-1" v-show="filterState">
                        <form id="filterForm">
                            <div class="row">
                                <div class="col-12 col-sm-6 col-md-6 col-lg-3">
                                    <div class="form-group">
                                        <label>Login ID</label>
                                        <input type="text" v-model="tableOptions.filterParam.loginid" class="form-control training-id" id="loginid-id" placeholder="Login ID" name="loginid" />
                                    </div>
                                </div>
                                <div class="col-12 col-sm-6 col-md-6 col-lg-3">
                                    <div class="form-group">
                                        <label>Fullname</label>
                                        <input type="text" id="title" v-model="tableOptions.filterParam.name" class="form-control fullname" placeholder="Fullname" name="fullname" />
                                    </div>
                                </div>
                                <div class="col-12 col-sm-6 col-md-6 col-lg-3">
                                    <div class="form-group">
                                        <label>Geo Location</label>
                                        <select class="select2 form-control" @change="setLocation()" placeholder="Geo Location">
                                            <option value="">Select Geo Location</option>
                                            <option v-for="(g, i) in geoData" :value="i">{{ g.geo_string }}</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-12 col-sm-6 col-md-6 col-lg-3">
                                    <div class="form-group mt-2 text-right">
                                        <button type="button" class="btn btn-md btn-primary"  @click="applyFilter()">Apply Filters</button>
                                    </div>
                                </div>
                                
                            </div>
                        </form>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th width="60px" style="padding-right: 2px !important;">
                                        <div class="custom-control custom-checkbox checkbox">
                                            <input type="checkbox" class="custom-control-input" @change="selectToggle()" id="all-check1" />
                                            <label class="custom-control-label" for="all-check1"></label>
                                        </div>
                                    </th>
                                    <th @click="sort(1)">
                                        Login ID
                                        <i class="feather icon-chevron-up sort-up" :class="(tableOptions.orderField == 1 && tableOptions.orderDir =='asc')? 'active-sort': ''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 1 && tableOptions.orderDir =='desc')? 'active-sort': ''"></i>
                                    </th>
                                    <th @click="sort(2)">
                                        Fullname
                                        <i class="feather icon-chevron-up sort-up" :class="(tableOptions.orderField == 2 && tableOptions.orderDir =='asc')? 'active-sort': ''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 2 && tableOptions.orderDir =='desc')? 'active-sort': ''"></i>
                                    </th>
                                    <th>Username</th>
                                    <th>User Group</th>
                                    <th @click="sort(9)">
                                        Geo Location
                                        <i class="feather icon-chevron-up sort-up" :class="(tableOptions.orderField == 9 && tableOptions.orderDir =='asc')? 'active-sort': ''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 9 && tableOptions.orderDir =='desc')? 'active-sort': ''"></i>
                                    </th>
                                    <th @click="sort(10)" style="padding-left: 5px !important; padding-right: 10px !important">
                                        Status
                                        <i class="feather icon-chevron-up sort-up" :class="(tableOptions.orderField == 10 && tableOptions.orderDir =='asc')? 'active-sort': ''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 10 && tableOptions.orderDir =='desc')? 'active-sort': ''"></i>
                                    </th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(g, i) in tableData" :class="checkedBg(g.pick)">
                                    <td style="padding-right: 2px !important;">
                                        <div class="custom-control custom-checkbox checkbox">
                                            <input type="checkbox" class="custom-control-input" :id="g.participant_id" v-model="g.pick" />
                                            <label class="custom-control-label" :for="g.participant_id"></label>
                                        </div>
                                    </td>
                                    <td>{{g.loginid}}</td>
                                    <td>{{g.first}} {{g.middle}} {{checkIfEmpty(g.last)}}</td>
                                    <td>{{g.username}}</td>
                                    <td>{{g.user_group}}</td>
                                    <td>{{g.geo_string}}</td>
                                    <td style="padding-left: 5px !important; padding-right: 10px !important"><span class="badge rounded-pill font-small-1" :class="g.active==1? 'bg-success' : 'bg-danger'">{{g.active==1? 'Active' : 'Inactive'}}</span></td>
                                    <td>
                                        <div class="dropdown">
                                            <button type="button" class="btn btn-sm dropdown-toggle hide-arrow" data-toggle="dropdown">
                                                <span class="feather icon-more-vertical"></span>
                                            </button>
                                            <div class="dropdown-menu">
                                                <a v-if="permission.permission_value ==3" class="dropdown-item" href="javascript:void(0);" @click="removeParticipant(g.participant_id, g.loginid)">
                                                    <span class="feather icon-delete mr-50"></span> Remove
                                                </a>
                                                <a class="dropdown-item" href="javascript:void(0);" @click="downloadBadge(g.userid)">
                                                    <i class="feather icon-download mr-50"></i> Download Badge
                                                </a>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="tableData.length == 0"><td class="text-center pt-2" colspan="7"><small>No Participant Addded</small></td></tr>

                            </tbody>
                        </table>

                    </div>

                    <div class="card-footer">
                        <div class="content-fluid">
                            <div class="row">
                                <div class="col-12 col-xl-4 col-md-4 col-sm-5">
                                    <div class="dropdown sort-dropdown mb-1 mb-sm-0">
                                        <button class="btn filter-btn btn-primary dropdown-toggle border text-dark" type="button" id="tablePaginationDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                            {{tableOptions.limitStart+1}} - {{tableOptions.limitStart+tableData.length}} of {{tableOptions.total}} 
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-right" aria-labelledby="tablePaginationDropdown">
                                            <a @click="changePerPage(g)" v-for="g in tableOptions.aLength" class="dropdown-item" href="javascript:void(0);">{{g}}</a>
                                        </div>
                                    </div>                            
                                </div>

                                <div class="col-12 col-xl-8 col-md-8 col-sm-7 text-right text-pag">
                                    
                                    <div class="btn-group">
                                        <button type="button" @click="prevPage()" class="btn btn-sm btn-primary round btn-page-block-overlay" :disabled="tableOptions.isPrev? false: true">
                                            <i data-feather='chevron-left'></i> Prev
                                        </button>
                                        
                                        <input @keyup.13="currentPage()" class="btn btn-page-block-overlay btn-sm btn-outline-primary pagination-input" type="number" v-model.number="tableOptions.currentPage" :max="tableOptions.pageLength" />
                                        
                                        <button class="btn btn-outline-primary btn-page-block-overlay border-l-0">
                                            <small class="form-text text-primary">  of {{this.tableOptions.pageLength}} </small>
                                        </button>
                                        
                                        <button type="button" @click="nextPage()" class="btn btn-sm btn-primary round"  :disabled="tableOptions.isNext? false: true">
                                            Next <i data-feather='chevron-right'></i>
                                        </button>
                                        
                                    </div>

                                </div>
                            </div>
                        </div>

                    </div>

                    <div class="mb-50"></div>
                </div>
            </div>

            <!-- Add User Group to Training: Start -->
            <div class="modal fade text-left" id="addParticipant" tabindex="-1" role="dialog" aria-labelledby="myModalLabel33" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h4 class="modal-title" id="myModalLabel33">Add Group to Activity</h4>
                            <button type="button" class="close" @click="hideParticipantModal()" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>

                        <form method="POST" @submit.stop.prevent="addParticipant()">
                            <div class="modal-body correct-font custom-select-down">
                                <div class="alert alert-warning p-1 alert-dismissible">
                                    <p>Kindly Note that adding a user group as participants means you are adding all the users in the user group as part of the Activity</p>
                                </div>
                                <label>Choose User Group :</label>
                                <div class="form-group">
                                    <select name="role" v-model="participantForm.group_name" class="form-control role select1">
                                        <option value="" selected>Select a User group to Add</option>
                                        <option v-for="(g, i) in groupData" :value="g.user_group" :key="i">{{g.user_group}}</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="modal-footer">
                                <div class="text-center">
                                    <button type="submit" class="btn btn-primary mt-2 me-1 mr-1 waves-effect waves-float waves-light">Add Participants</button>
                                    <button type="reset" @click="hideParticipantModal()" class="btn btn-outline-secondary mt-2 waves-effect" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close">Discard</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <!-- Add User Group to Training: End -->


        </div>
    `,
});

// Training Session List Page
Vue.component("attendance_list", {
  data: function () {
    return {
      tableData: [],
      geoData: [],
      sessionid: "",
      trainingid: "",
      title: "",
      permission: getPermission(per, "activity"),
      searchTxt: "",
      checkToggle: false,
      filterState: false,
      filters: false,
      url: common.TableService,
      tableOptions: {
        total: 1, //Total record
        pageLength: 1, //Total
        perPage: 10,
        currentPage: 1,
        orderDir: "desc", // (asc|desc)
        orderField: 0, //(Order fields)
        limitStart: 0, //(currentPage - 1) * perPage
        isNext: false,
        isPrev: false,
        aLength: [10, 20, 50, 100, 200, 300],
        filterParam: {
          geo_level: "",
          geo_level_id: "",
        },
      },
    };
  },
  mounted() {
    this.getGeoLocation();
    var select = $(".select2");
    let self = this;
    select.each(function () {
      var $this = $(this);
      $this.wrap('<div class="position-relative"></div>');
      $this
        .select2({
          // the following code is used to disable x-scrollbar when click in select input and
          // take 100% width in responsive also
          dropdownAutoWidth: true,
          width: "100%",
          dropdownParent: $this.parent(),
        })
        .on("change", function () {
          self.setLocation(this.value);
        });
    });
    $(".select2-selection__arrow").html(
      '<i class="feather icon-chevron-down"></i>',
    );
    EventBus.$on("g-event-goto-page", this.gotoPageHandler);
    this.loadTableData();
  },
  methods: {
    gotoPageHandler(data) {
      this.trainingid = data.trainingid;
      this.sessionid = data.sessionid;
      this.title = data.title;
      this.loadTableData();
    },
    goToTrainingList() {
      EventBus.$emit("g-event-goto-page", {
        page: "training",
        trainingid: this.trainingid,
      });
    },
    goToSessionLists(trainingid) {
      EventBus.$emit("g-event-goto-page", {
        trainingid: trainingid,
        page: "session",
        sessionid: "",
      });
    },
    filterEarliestInLatestOut(data) {
      const result = {};

      data.forEach((entry) => {
        const key = entry.userid;

        if (!result[key]) {
          result[key] = {};
        }

        const time = new Date(entry.collected).getTime();

        if (entry.at_type === "clock-in") {
          if (
            !result[key]["clock-in"] ||
            time < new Date(result[key]["clock-in"].collected).getTime()
          ) {
            result[key]["clock-in"] = entry;
          }
        }

        if (entry.at_type === "clock-out") {
          if (
            !result[key]["clock-out"] ||
            time > new Date(result[key]["clock-out"].collected).getTime()
          ) {
            result[key]["clock-out"] = entry;
          }
        }
      });

      return Object.values(result).flatMap((types) => Object.values(types));
    },
    loadTableData() {
      /*  Manages the loading of table data */

      var self = this;
      var url = common.TableService;
      overlay.show();

      axios
        .get(
          url +
            "?qid=103&draw=" +
            self.tableOptions.currentPage +
            "&order_column=" +
            self.tableOptions.orderField +
            "&length=" +
            self.tableOptions.perPage +
            "&start=" +
            self.tableOptions.limitStart +
            "&order_dir=" +
            self.tableOptions.orderDir +
            "&se=" +
            self.sessionid +
            "&gl=" +
            self.tableOptions.filterParam.geo_level +
            "&glid=" +
            self.tableOptions.filterParam.geo_level_id,
        )
        .then(function (response) {
          self.tableData = self.filterEarliestInLatestOut(response.data.data); //All Data
          self.tableOptions.total = response.data.recordsTotal; //Total Records
          if (self.tableOptions.currentPage == 1) {
            self.paginationDefault();
          }
          overlay.hide();
        })
        .catch(function (error) {
          overlay.hide();
          alert.Error("ERROR", error);
        });
    },
    refreshData() {
      this.loadTableData();
    },
    nextPage() {
      /*  Manages the selections of checked or selected data object */
      this.tableOptions.currentPage += 1;
      this.paginationDefault();
      this.loadTableData();
    },
    prevPage() {
      /*  Manages the selections of checked or selected data object */
      this.tableOptions.currentPage -= 1;
      this.paginationDefault();
      this.loadTableData();
    },
    currentPage() {
      this.paginationDefault();
      if (this.tableOptions.currentPage < 1) {
        alert.Error("ERROR", "The Page requested doesn't exist");
      } else if (this.tableOptions.currentPage > this.tableOptions.pageLength) {
        alert.Error("ERROR", "The Page requested doesn't exist");
      } else {
        this.loadTableData();
      }
    },
    paginationDefault() {
      //  total page
      this.tableOptions.pageLength = Math.ceil(
        this.tableOptions.total / this.tableOptions.perPage,
      );

      // Page Limit
      this.tableOptions.limitStart = Math.ceil(
        (this.tableOptions.currentPage - 1) * this.tableOptions.perPage,
      );

      //  Next
      if (
        this.tableOptions.currentPage < this.tableOptions.pageLength &&
        this.tableOptions.currentPage != this.tableOptions.pageLength
      ) {
        this.tableOptions.isNext = true;
      } else {
        this.tableOptions.isNext = false;
      }

      // Previous
      if (this.tableOptions.currentPage > 1) {
        this.tableOptions.isPrev = true;
      } else {
        this.tableOptions.isPrev = false;
      }
    },
    changePerPage(val) {
      let maxPerPage = Math.ceil(this.tableOptions.total / val);
      if (maxPerPage < this.tableOptions.currentPage) {
        this.tableOptions.currentPage = maxPerPage;
      }
      this.tableOptions.perPage = val;
      this.paginationDefault();
      this.loadTableData();
    },
    sort(col) {
      if (this.tableOptions.orderField === col) {
        this.tableOptions.orderDir =
          this.tableOptions.orderDir === "asc" ? "desc" : "asc";
      } else {
        this.tableOptions.orderField = col;
      }

      this.paginationDefault();
      this.loadTableData();
    },
    applyFilter() {
      // Check if any filter fields are filled
      let checkFill = 0;
      checkFill += this.tableOptions.filterParam.geo_level_id != "" ? 1 : 0;

      if (checkFill > 0) {
        this.toggleFilter();
        this.filters = true;
        this.paginationDefault();
        this.loadTableData();
      } else {
        alert.Error("ERROR", "Invalid required data");
        return;
      }
    },
    removeSingleFilter(column_name) {
      // this.tableOptions.filterParam + '.' + column_name == "";
      this.tableOptions.filterParam[column_name] = "";
      let g = 0;
      for (let i in this.tableOptions.filterParam) {
        if (this.tableOptions.filterParam[i] != "") {
          g++;
        }
      }
      if (g == 0) {
        this.filters = false;
      }
      this.paginationDefault();
      this.loadTableData();
    },
    clearAllFilter() {
      this.filters = false;
      $(".select2").val("").trigger("change");
      this.tableOptions.filterParam.geo_level =
        this.tableOptions.filterParam.geo_level_id = "";
      this.paginationDefault();
      this.loadTableData();
    },
    toggleFilter() {
      /*  Manages the toggling of a filter box */
      if (this.filterState === false) {
        this.filters = false;
      }
      return (this.filterState = !this.filterState);
    },
    capitalize(word) {
      if (word) {
        const loweredCase = word.toLowerCase();
        return word[0].toUpperCase() + loweredCase.slice(1);
      } else {
        return word;
      }
    },
    deleteSession(session_id) {
      var url = common.DataService;
      var self = this;
      $.confirm({
        title: "WARNING!",
        content: "Are you sure you want to Delete a Activity Session?",
        buttons: {
          delete: {
            text: "Delete",
            btnClass: "btn btn-danger mr-1 text-capitalize",
            action: function () {
              //Attempt Update
              axios
                .post(url + "?qid=107&e=" + session_id)
                .then(function (response) {
                  if (response.data.result_code == "200") {
                    self.resetForm();
                    self.addSessionModal = false;
                    $("#addSession").modal("hide");
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
            self.resetForm();
            self.addSessionModal = false;
            $("#addSession").modal("hide");
            overlay.hide();
          },
        },
      });
    },
    async downlodAttendance(session_id, title) {
      var sid = session_id;

      var veriUrl =
        "qid=115&sid=" +
        sid +
        "&gl=" +
        this.tableOptions.filterParam.geo_level +
        "&glid=" +
        this.tableOptions.filterParam.geo_level_id;
      var dlString =
        "qid=102&id=" +
        sid +
        "&gl=" +
        this.tableOptions.filterParam.geo_level +
        "&glid=" +
        this.tableOptions.filterParam.geo_level_id;
      var filename = title + " Attendance - (" + sid + ")";
      overlay.show();

      //  count export data
      let count = new Promise((resolve, reject) => {
        $.ajax({
          url: common.DataService,
          type: "POST",
          data: veriUrl,
          dataType: "json",
          success: function (data) {
            resolve(data.total);
          },
        });
      });
      let result = await count; //  wait till the promise resolves (*)
      var downloadMax = common.ExportDownloadLimit;

      if (parseInt(result) > downloadMax) {
        //  stop download
        alert.Error(
          "Download Error",
          "Unable to download data because it has exceeded download limit, download limit is " +
            downloadMax,
        );
      } else if (parseInt(result) == 0) {
        alert.Error("Download Error", "No data found");
      } else {
        alert.Info("DOWNLOADING...", "Downloading " + result + " record(s)");
        //  Else continue download data
        var options = {
          fileName: filename,
        };

        let dl = new Promise((resolve, reject) => {
          $.ajax({
            url: common.ExportService,
            type: "POST",
            data: dlString,
            success: function (data) {
              resolve(data);
            },
          });
        });

        let outcome = await dl; //  Wait till downloaded
        var exportData = JSON.parse(outcome);
        Jhxlsx.export(exportData, options);
      }

      overlay.hide();
    },
    searchAttendance() {
      // searchTxt
      var search = this.searchTxt;

      var table = this.tableData.filter((obj) => {
        var flag = false;
        Object.values(obj).forEach((val) => {
          if (String(val).indexOf(search) > -1) {
            flag = true;
            return;
          }
        });
        if (flag) return obj;
      });

      this.tableData = table;
    },
    checkIfEmpty() {
      if (this.searchTxt.length < 1) {
        this.refreshData();
      }
    },
    getGeoLocation() {
      /*  Manages the loading of Geo Level data */
      var self = this;
      var url = common.DataService;
      overlay.show();

      axios
        .get(url + "?qid=gen009")
        .then(function (response) {
          self.geoData = response.data.data; //All Data
          // self.tableOptions.total = response.data.recordsTotal; //Total Records
          overlay.hide();
        })
        .catch(function (error) {
          overlay.hide();
          alert.Error("ERROR", error);
        });
    },
    setLocation(select_index) {
      this.tableOptions.filterParam.geo_level =
        this.geoData[select_index].geo_level;
      this.tableOptions.filterParam.geo_level_id =
        this.geoData[select_index].geo_level_id;
      // this.tableOptions.filterParam.geo_string = this.geoData[select_index].title;
    },
  },
  template: `
        <div class="row">
            <div class="col-md-8 col-sm-12 col-12 mb-0">
                <h2 class="content-header-title header-txt float-left mb-0">Activity Mgmt.</h2>
                <div class="breadcrumb-wrapper">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../training">Home</a></li>
                        <li class="breadcrumb-item"><a href="javascript:void(0);" @click="goToTrainingList()">Activity List</a></li>
                        <li class="breadcrumb-item"><a href="javascript:void(0);" @click="goToSessionLists(trainingid)">Sessions</a></li>
                        <li class="breadcrumb-item active">{{ title }} Attendance</li>
                    </ol>
                </div>
            </div>

            <div class="col-md-4 col-sm-12 col-12 text-md-right text-right text-md-right d-md-block">
                <div class="btn-group mr-1"> 
                    <button type="button" class="btn btn-outline-primary round searchBtn" @click="toggleFilter()" data-toggle="tooltip" data-placement="top" title="Filter">
                        <i class="feather" :class="filterState ? 'icon-x' : 'icon-filter'"></i>               
                    </button>
                    <button type="button" class="btn btn-outline-primary round searchBtn" @click="refreshData()">
                        <i class="feather icon-refresh-cw"></i>         
                    </button>   
                    <button v-if="permission.permission_value >=2" class="btn-icon btn btn-primary round" type="button" @click="downlodAttendance(sessionid, title)">Download <i data-feather='download-cloud'></i></button>
                </div>
            </div>
            <div class="col-12" v-if="filters">
                <div class="col-12 filter-bar">
                    <span class="badge badge-dark filter-box" @click="clearAllFilter()" v-for="(filterParam, i) in tableOptions.filterParam" v-if="filterParam.length > 0 && i != 'geo_level_id'">{{capitalize(i)}}: {{filterParam}} <i class="feather icon-x"></i></span>
                    <a href="#" class="float-right clear-filter" @click="clearAllFilter()">Clear</a>
                </div>
            </div>

            <div class="col-12 mt-1">
                <div class="card custom-select-down">
                    <div class="card-body py-1" v-show="filterState">
                        <form id="filterForm">
                            <div class="row">
                                <div class="col-12 col-sm-6 col-md-8 col-lg-8">
                                    <div class="form-group">
                                        <label>Geo Location</label>
                                        <select class="select2 form-control" @change="setLocation()" placeholder="Geo Location">
                                            <option value="">Select Geo Location</option>
                                            <option v-for="(g, i) in geoData" :value="i">{{ g.geo_string }}</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-12 col-sm-6 col-md-4 col-lg-4">
                                    <div class="form-group mt-2 text-right">
                                        <button type="button" class="btn btn-md btn-primary"  @click="applyFilter()">Apply Filters</button>
                                    </div>
                                </div>
                                
                            </div>
                        </form>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th width="60px">#</th>
                                    <th @click="sort(1)">
                                        Login ID
                                        <i class="feather icon-chevron-up sort-up" :class="(tableOptions.orderField == 1 && tableOptions.orderDir =='asc')? 'active-sort': ''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 1 && tableOptions.orderDir =='desc')? 'active-sort': ''"></i>
                                    </th>
                                    <th @click="sort(8)">
                                        Fullname
                                        <i class="feather icon-chevron-up sort-up" :class="(tableOptions.orderField == 8 && tableOptions.orderDir =='asc')? 'active-sort': ''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 8 && tableOptions.orderDir =='desc')? 'active-sort': ''"></i>
                                    </th>
                                    <th @click="sort(5)">
                                        Phone No
                                        <i class="feather icon-chevron-up sort-up" :class="(tableOptions.orderField == 5 && tableOptions.orderDir =='asc')? 'active-sort': ''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 5 && tableOptions.orderDir =='desc')? 'active-sort': ''"></i>
                                    </th>
                                    <th @click="sort(4)">
                                        Attendant Type
                                        <i class="feather icon-chevron-up sort-up" :class="(tableOptions.orderField == 4 && tableOptions.orderDir =='asc')? 'active-sort': ''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 4 && tableOptions.orderDir =='desc')? 'active-sort': ''"></i>
                                    </th>
                                    <th @click="sort(12)">
                                        Date & Time
                                        <i class="feather icon-chevron-up sort-up" :class="(tableOptions.orderField == 12 && tableOptions.orderDir =='asc')? 'active-sort': ''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 12 && tableOptions.orderDir =='desc')? 'active-sort': ''"></i>
                                    </th>
                                    <th>Bio Auth</th>

                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(g, i) in tableData">
                                    <td>{{i+1}}</td>
                                    <td>{{g.loginid}}</td>
                                    <td>{{g.fullname}}</td>
                                    <td>{{g.phone}}</td>
                                    <td>{{g.at_type}}</td>
                                    <td>{{g.collected}}</td>
                                    <td>{{g.bio_auth}}</td>
                                </tr>
                                <tr v-if="tableData.length == 0"><td class="text-center pt-2" colspan="7"><small>No Attendance Taken</small></td></tr>
                            </tbody>
                        </table>

                    </div>

                    <div class="card-footer">
                        <div class="content-fluid">
                            <div class="row">
                                <div class="col-12 col-xl-4 col-md-4 col-sm-5">
                                    <div class="dropdown sort-dropdown mb-1 mb-sm-0">
                                        <button class="btn filter-btn btn-primary dropdown-toggle border text-dark" type="button" id="tablePaginationDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                            {{tableOptions.limitStart+1}} - {{tableOptions.limitStart+tableData.length}} of {{tableOptions.total}} 
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-right" aria-labelledby="tablePaginationDropdown">
                                            <a @click="changePerPage(g)" v-for="g in tableOptions.aLength" class="dropdown-item" href="javascript:void(0);">{{g}}</a>
                                        </div>
                                    </div>                            
                                </div>

                                <div class="col-12 col-xl-8 col-md-8 col-sm-7 text-right text-pag">
                                    
                                    <div class="btn-group">
                                        <button type="button" @click="prevPage()" class="btn btn-sm btn-primary round btn-page-block-overlay" :disabled="tableOptions.isPrev? false: true">
                                            <i data-feather='chevron-left'></i> Prev
                                        </button>
                                        
                                        <input @keyup.13="currentPage()" class="btn btn-page-block-overlay btn-sm btn-outline-primary pagination-input" type="number" v-model.number="tableOptions.currentPage" :max="tableOptions.pageLength" />
                                        
                                        <button class="btn btn-outline-primary btn-page-block-overlay border-l-0">
                                            <small class="form-text text-primary">  of {{this.tableOptions.pageLength}} </small>
                                        </button>
                                        
                                        <button type="button" @click="nextPage()" class="btn btn-sm btn-primary round"  :disabled="tableOptions.isNext? false: true">
                                            Next <i data-feather='chevron-right'></i>
                                        </button>
                                        
                                    </div>

                                </div>
                            </div>
                        </div>

                    </div>

                    <div class="mb-50"></div>
                </div>
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
