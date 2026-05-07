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
      page: "allocation", //  page by name training | session | participant | attendance ...
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

            <div v-show="page == 'allocation'">
                <necard_movement/>
            </div>
     
        </div>
    </div>
    `,
});

Vue.component("necard_movement", {
  data: function () {
    return {
      tableData: [],
      checkToggle: false,
      filterState: false,
      filters: false,
      permission: getPermission(per, "enetcard_unlock"),
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
        aLength: [10, 20, 50, 100, 150, 200],
        filterParam: {
          movementType: "Forward",
        },
      },
      currentWardBalance: {
        wardName: "",
        balance: 0,
        disbursed: 0,
        received: 0,
      },
      wardMovementForm: {
        totalNetcard: 1,
        wardMoveBtn: "",
        wardMoveModal: false,
        lgaid: "",
        wardid: "",
        wardName: "",
        wardBalance: "",
      },
      movementForm: {
        geoLevel: "",
        geoLevelId: 0,
      },
      geoIndicator: {
        state: 50,
        currentLevelId: 0,
        lga: "",
        cluster: "",
        ward: "",
      },
      geoLevelData: [],
      sysDefaultData: [],
      lgaLevelData: [],
      clusterLevelData: [],
      wardLevelData: [],
      lgaNetBalancesData: [],
      wardNetBalancesData: [],
      hhmBalanacesData: [],
      isLgabalance: true,
      isHHMbalance: true,
      allStatistics: {
        stateBalance: 0,
        lgaBalance: 0,
        wardBalance: 0,
        mobilizer: 0,
      },
    };
  },
  mounted() {
    /*  Manages events Listening    */
    this.getsysDefaultDataSettings();
    // this.getLgasNetBalances();
    // this.loadTableData();
    // this.getAllStat();
    EventBus.$on("g-event-update", this.loadTableData);
    // $('#wardMovement').modal('show');
    $("#todo-search").on("keyup", function () {
      var value = $(this).val().toLowerCase();
      $("#moveTable tbody tr").filter(function () {
        $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
      });
    });
    $("#todo-search1").on("keyup", function () {
      var value = $(this).val().toLowerCase();
      $("#moveTable1 tbody tr").filter(function () {
        $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
      });
    });

    // Tooltip Initialization
    $('[data-toggle="tooltip"]').tooltip({
      container: "body",
    });
    this.scroll();
  },
  methods: {
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
            self.getLgasLevel(response.data.data[0].stateid);
            //  Set preventDefault();
            self.movementForm.geoLevel = "state";
            self.movementForm.geoLevelId = response.data.data[0].stateid;
            // self.stateMovementForm.stateid = response.data.data[0].stateid;
          }

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
    getLgasNetBalances() {
      /*  Manages the loading of Geo Level data */
      var self = this;
      var url = common.DataService;
      overlay.show();

      axios
        .post(url + "?qid=206")
        .then(function (response) {
          self.lgaNetBalancesData = response.data.data; //All Data
          overlay.hide();
        })
        .catch(function (error) {
          overlay.hide();
          alert.Error("ERROR", error);
        });
    },
    getWardLevel(event) {
      /*  Manages the loading of Geo Level data */
      var self = this;
      var url = common.DataService;
      overlay.show();
      self.wardMovementForm.wardid = "";
      axios
        .get(url + "?qid=gen005&e=" + self.wardMovementForm.lgaid)
        .then(function (response) {
          self.wardLevelData = response.data.data; //All Data
          overlay.hide();
        })
        .catch(function (error) {
          overlay.hide();
          alert.Error("ERROR", error);
        });
    },
    getWardData(event) {
      /*  Get ward list with balances with lgaid */
      var self = this;
      var url = common.DataService;
      overlay.show();
      self.wardMovementForm.wardid = "";
      self.wardMovementForm.lgaid =
        event.target.options[event.target.options.selectedIndex].value;
      axios
        // .get(url + "?qid=207&lgaid=" + self.wardMovementForm.lgaid)
        .get(
          url +
            "?qid=gen005&lgaid=" +
            self.wardMovementForm.lgaid +
            "&e=" +
            self.wardMovementForm.lgaid
        )
        .then(function (response) {
          self.wardNetBalancesData = response.data.data; //All Data
          self.wardLevelData = response.data.data; //All Data
          overlay.hide();
        })
        .catch(function (error) {
          overlay.hide();
          alert.Error("ERROR", error);
        });
    },
    getHhmBalances(event) {
      /*  Get HHM Balances with Wardid */

      let self = this;
      self.wardMovementForm.wardName = event.target.options[
        event.target.options.selectedIndex
      ].text
        .trim()
        .replace(",", "")
        .split("-")[0];
      self.wardMovementForm.wardBalance =
        event.target.options[event.target.options.selectedIndex].text
          .trim()
          .replace(",", "")
          .split("-")[1] == ""
          ? 0
          : parseInt(
              event.target.options[event.target.options.selectedIndex].text
                .trim()
                .replace(",", "")
                .split("-")[1]
            );

      self.currentWardBalance.wardName =
        event.target.options[event.target.options.selectedIndex].text;

      self.getHHMOfflineBalancesList();
      self.getCurrentWardBalance();
      overlay.show();
    },
    refreshData() {
      if (this.currentWardBalance.wardName != "") {
        this.getHHMOfflineBalancesList();
      }
    },
    getHHMOfflineBalancesList() {
      let self = this;
      let url = common.DataService;
      let current_endpoint = "216";
      overlay.show();
      axios
        .get(
          url +
            "?qid=" +
            current_endpoint +
            "&wardid=" +
            self.wardMovementForm.wardid
        )
        .then(function (response) {
          self.hhmBalanacesData = response.data.data; //All Data
          overlay.hide();
        })
        .catch(function (error) {
          overlay.hide();
          alert.Error("ERROR", error);
        });
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
    capitalize(word) {
      if (word) {
        const loweredCase = word.toLowerCase();
        return word[0].toUpperCase() + loweredCase.slice(1);
      } else {
        return word;
      }
    },
    formatNumber(num) {
      return parseInt(num).toLocaleString();
    },
    scroll() {
      // if it is not touch device
      var sidebarMenuList = $(".main-body");
      if (!$.app.menu.is_touch_device()) {
        if (sidebarMenuList.length > 0) {
          for (i = 0; i < sidebarMenuList.length; ++i) {
            var sidebarListScrollbar = new PerfectScrollbar(
              sidebarMenuList[i],
              {
                theme: "dark",
              }
            );
          }
        }
      }
      // if it is a touch device
      else {
        sidebarMenuList.css("overflow", "scroll");
      }
    },
    onlyNumber($event) {
      //console.log($event.keyCode); //keyCodes value
      let keyCode = $event.keyCode ? $event.keyCode : $event.which;
      if ((keyCode < 48 || keyCode > 57) && keyCode == 46) {
        // 46 is dot
        $event.preventDefault();
      }
    },
    getCurrentWardBalance() {
      let self = this;
      var url = common.DataService;
      axios
        .get(url + "?qid=214&wardid=" + self.wardMovementForm.wardid)
        .then(function (response) {
          // console.log(response.data.data);
          self.currentWardBalance.balance = response.data.data[0]["balance"]
            ? parseInt(response.data.data[0]["balance"])
            : 0; //All Data
          self.wardMovementForm.wardBalance = self.currentWardBalance.balance;
          self.currentWardBalance.received = response.data.data[0]["received"]
            ? parseInt(response.data.data[0]["received"])
            : 0; //All Data
          self.currentWardBalance.disbursed = response.data.data[0]["disbursed"]
            ? parseInt(response.data.data[0]["disbursed"])
            : 0; //All Data
          overlay.hide();
        })
        .catch(function (error) {
          overlay.hide();
          alert.Error("ERROR", error);
        });
    },
    unlockNetcardFromDevice(userid, device_serial, total) {
      let url = common.DataService;
      let requester_userid = document.getElementById("v_g_id").value;
      let self = this;
      if (total <= 0) {
        alert.Error(
          "Zero Balance",
          "You don't have an e-Netcard Residing on this device"
        );
      } else {
        $.confirm({
          title: "WARNING!",
          content:
            "Are you sure you want to Unlock <b>" +
            total +
            "</b> e-Netcard on the Device with Serial <b>" +
            device_serial +
            "</b>?",
          buttons: {
            delete: {
              text: "Unlock e-Netcard",
              btnClass: "btn btn-danger mr-1 text-capitalize",
              action: function () {
                //Attempt Unlock
                axios
                  .post(
                    url +
                      "?qid=215&device_serial=" +
                      device_serial +
                      "&userid=" +
                      userid +
                      "&requester_userid=" +
                      requester_userid
                  )
                  .then(function (response) {
                    if (response.data.result_code == "200") {
                      self.refreshData();
                      self.getCurrentWardBalance();
                      alert.Success(
                        "Success",
                        "<b>" +
                          response.data.total +
                          "</b> e-Netcards has been successfully Unlocked on Device with Serial No: <b>" +
                          device_serial +
                          "</b>"
                      );
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
      }
    },
  },
  template: `

        <div class="row" id="basic-table" v-cloak>

            <div class="col-md-12 col-sm-12 col-12 mb-1">
                <h2 class="content-header-title header-txt float-left mb-0">e-Netcard</h2>
                <div class="breadcrumb-wrapper">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../netcard">Home</a></li>
                        <li class="breadcrumb-item active">e-Netcard Unlock</li>
                    </ol>
                </div>
            </div>

            
            <div class="col-md-12 col-sm-12 col-12" v-if="permission.permission_value ==3">
                <div class="card p-0">
                    <div class="card-body p-0">
                        
                        <div class="allot mt-0">
                            <div class="left-side">
                                <h6 class="mb-1">HHM Balances</h6>
                                <div>
                                    <div class="form-group">
                                        <label class="form-label">Choose LGA</label>
                                        <select required class="form-control" @change="getWardLevel($event)" v-model="wardMovementForm.lgaid">
                                            <option value="" selected>Choose LGA to View</option>
                                            <option v-for="(lga, i) in lgaLevelData" :value="lga.lgaid">{{lga.lga}}</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label class="form-label">Choose a Ward</label>
                                        <select class="form-control" v-model="wardMovementForm.wardid" @change="getHhmBalances($event)">
                                            <option value="" selected>Choose a Ward</option>
                                            <option v-for="(ward, i) in wardLevelData" :value="ward.wardid">{{ward.ward}}</option>
                                        </select>
                                    </div> 

                                    <div class="e-details pt-2" v-if="wardMovementForm.wardid !=''">
                                      <small class="mt-3 "><span class="font-weight-bolder text-primary" v-text="currentWardBalance.wardName"></span> e-Netcard Details</small>
                                      <hr class="invoice-spacing mt-0">
                                      <div class="invoice-terms mt-1">
                                          <div class="d-flex justify-content-between">
                                              <label class="invoice-terms-title mb-0" for="paymentTerms">Total Received: </label>
                                              <div class="custom-control badge badge-light-success" v-text="currentWardBalance.received"></div>
                                          </div>
                                      </div>

                                      <div class="invoice-terms mt-1">
                                          <div class="d-flex justify-content-between">
                                              <label class="invoice-terms-title mb-0" for="paymentTerms">Total Disbursed: </label>
                                              <div class="custom-control badge badge-light-info"  v-text="currentWardBalance.disbursed"></div>
                                          </div>
                                      </div>
                                      <div class="invoice-terms mt-1">
                                        <hr class="my-50">
                                          <div class="d-flex justify-content-between">
                                              <label class="invoice-terms-title mb-0" for="paymentTerms">Balance: </label>
                                              <div class="custom-control badge badge-light-warning" v-text="currentWardBalance.balance"></div>
                                          </div>
                                      </div>

                                    </div>

                                    
                                </div>

                            </div>
                            <div class="right-side">
                                <div class="allot-mobile-form">
                                    <div class="form-group mb-50">
                                        <label class="form-label">Choose LGA</label>
                                        <select required class="form-control" @change="getWardLevel($event)" v-model="wardMovementForm.lgaid">
                                            <option value="" selected>Choose LGA to View</option>
                                            <option v-for="(lga, i) in lgaLevelData" :value="lga.lgaid">{{lga.lga}}</option>
                                        </select>
                                    </div>

                                    <div class="form-group mb-50">
                                        <label class="form-label">Choose a Ward</label>
                                        <select class="form-control" v-model="wardMovementForm.wardid" @change="getHhmBalances($event)">
                                            <option value="" selected>Choose a Ward</option>
                                            <option v-for="(ward, i) in wardLevelData" :value="ward.wardid">{{ward.ward}}</option>
                                        </select>
                                    </div>   
                                    
                                    <div class="e-details pt-1" v-if="wardMovementForm.wardid !=''">
                                      <small class="mt-3"><span class="font-weight-bolder text-primary" v-text="currentWardBalance.wardName"></span> e-Netcard Details</small>
                                      <hr class="invoice-spacing mt-0">
                                      <div class="invoice-terms mt-1">
                                          <div class="d-flex justify-content-between">
                                              <label class="invoice-terms-title mb-0" for="paymentTerms">Total Received: </label>
                                              <div class="custom-control badge badge-light-success" v-text="currentWardBalance.received"></div>
                                          </div>
                                      </div>

                                      <div class="invoice-terms mt-1">
                                          <div class="d-flex justify-content-between">
                                              <label class="invoice-terms-title mb-0" for="paymentTerms">Total Disbursed: </label>
                                              <div class="custom-control badge badge-light-info"  v-text="currentWardBalance.disbursed"></div>
                                          </div>
                                      </div>
                                      <div class="invoice-terms mt-1">
                                        <hr class="my-50">
                                          <div class="d-flex justify-content-between">
                                              <label class="invoice-terms-title mb-0" for="paymentTerms">Balance: </label>
                                              <div class="custom-control badge badge-light-warning" v-text="currentWardBalance.balance"></div>
                                          </div>
                                      </div>

                                    </div>

                                    
                                </div>
                                <!-- Todo search starts -->
                                <div class="app-fixed-search d-flex align-items-center">

                                    <div class="d-flex align-content-center justify-content-between w-100">
                                        <div class="input-group input-group-merge">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i data-feather="search" class="text-muted"></i></span>
                                            </div>
                                            <input type="text" class="form-control search" id="todo-search1" placeholder="Search HHM" aria-label="Search..." aria-describedby="todo-search1" />
                                            <!-- 
                                            -->
                                            <div class="input-group-append">
                                              <button class="btn" type="button" @click="refreshData()">
                                                  <i class="feather icon-refresh-cw text-primary"></i>
                                              </button>
                                            </div>
                                        </div>
                                    </div>
                                    
                                </div>
                                
                                <!-- Todo search ends -->
                                <div class="main-body">
                                    <div v-if="isLgabalance == true" class="mt-0">
                                        <table class="table table-hover scroll-now" id="moveTable1">
                                            <thead>
                                                <tr>
                                                    <th>Login ID</th>
                                                    <th>Fullname</th>
                                                    <th>Location</th>
                                                    <th>HHM Balance</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody class="">
                                                <tr v-for="(g, i) in hhmBalanacesData">
                                                    <td>{{g.loginid}}</td>
                                                    <td v-html="g.fullname? g.fullname :'Not Assigned'"></td>
                                                    <td>
                                                      <i class="feather " :class="g.device_serial? 'icon-smartphone bg-light-info rounded' : 'icon-cloud  bg-light-success rounded'"></i>  <small v-html="g.device_serial? ' ('+g.device_serial+')' : ' (Online)'"></small>
                                                    </td>
                                                    <td>{{g.balance}}</td>
                                                    <td>
                                                      <button v-if="permission.permission_value ==3" type="button" @click="unlockNetcardFromDevice(g.userid, g.device_serial, g.balance)" class="btn btn-sm btn-primary p-50 waves-float waves-effect">
                                                        <i class="feather icon-unlock mr-25"></i>
                                                          <span>Unlock</span>
                                                      </button>

                                                      <button v-else type="button" class="btn btn-sm btn-Secondary p-50 waves-float waves-effect">
                                                        <i class="feather icon-unlock mr-25"></i>
                                                          <span>Unlock</span>
                                                      </button>
                                                    </td>
                                                </tr>
                                                                                                    
                                                <tr v-if="hhmBalanacesData.length == 0"><td class="text-center text-info pt-4 pb-4" colspan="5"><small>No Ward Choosen/No Pending e-Netcard on devices  <b class="text-primary" v-text="wardMovementForm.wardName? ' in '+ wardMovementForm.wardName +' Ward' : ''"> </b> </small></td></tr>

                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                            </div>

                        </div>
                    
                    </div>
                </div>
            </div>

            
            <!--/ Stats Horizontal Card -->

            <div class="col-md-12 col-sm-12 col-12" v-else>
              <h6 class="text-center text-info pt-4 pb-4">You don't have permission to view this page</h6>
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
