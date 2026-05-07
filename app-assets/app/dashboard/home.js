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
                    <user_list/>
                </div>

                <div v-show="page == 'detail'">
                    <user_details/>
                </div>

            </div>
        </div>
    `,
});

// User List Page
Vue.component("user_list", {
  data: function () {
    return {
      url: common.BadgeService,
      geoLevelForm: {
        geoLevel: "",
        geoLevelId: 0,
        currentUserLoginid: "",
        userid: "",
      },
      geoIndicator: {
        state: 50,
        currentLevelId: 0,
        lga: "",
        cluster: "",
        ward: "",
      }
    };
  },
  mounted() {
    /*  Manages events Listening    */
    // Manipulate the UI for Map to sit
    /*

        var element1 = document.querySelector("body");
        element1.classList.remove("menu-expanded");
        element1.classList.add("menu-collapsed");
        document.querySelector(".main-menu, .navbar-header").classList.remove("expanded");
    */
    // document.querySelector('.nav.navbar-nav.align-items-center.ml-auto').remove();
    var filesTreeView = $(".my-drive");
    // Files Treeview
    if (filesTreeView.length) {
      filesTreeView.jstree({
          core: {
            "check_callback": true,
          themes: {
            dots: true,
          },
          data: {
            url: "../../../app-assets/data/jstree-data.json",
            dataType: "json",
            data: function (node) {
              return {
                id: node.id,
              };
            },
          },
        },
        plugins: ['types'],
        types: {
          default: {
            icon: "far fa-folder",
          },
          html: {
            icon: "fab fa-html5 text-danger",
          },
          css: {
            icon: "fab fa-css3-alt text-info",
          },
          img: {
            icon: "far fa-file-image text-success",
          },
          js: {
            icon: "fab fa-node-js text-warning",
          },
        },
      }).bind("select_node.jstree", function (e, data) {
          return data.instance.open_node(data.node);
      }).on('changed.jstree', function (e, data) {
          var i, j, r = [];
          var t = [];
            for(i = 0, j = data.selected.length; i < j; i++) {
                r.push(data.instance.get_node(data.selected[i]).id);
                t.push(data.instance.get_node(data.selected[i]));
            }
            // $('#event_result').html('Selected: ' + r.join(', '));
            console.log('Selected: ' + r.join(', '));
            console.log(t[0].original.geo_level_id);
        }).jstree();
        
        // $('#jstree')
        // listen for event
        // filesTreeView.on('changed.jstree', function (e, data) {
        //     var i, j, r = [];
        //     for(i = 0, j = data.selected.length; i < j; i++) {
        //         r.push(data.instance.get_node(data.selected[i]).id);
        //     }
        //     // $('#event_result').html('Selected: ' + r.join(', '));
        //     console.log('Selected: ' + r.join(', '));
        // })
        // create the instance
        // .jstree();
    }
    EventBus.$on("g-event-update-user", this.reloadUserListOnUpdate);
  },
  methods: {},
  computed: {},
  template: `

        <div class="row" id="basic-table">

            <div class="col-12 mt-1">

                <div class="file-manager-application">
                    <div class="content-overlay"></div>
                    <div class="header-navbar-shadow"></div>
                    <div class="content-area-wrapper container-xxl p-0 mb-1">
                        <div class="sidebar-left">
                            <div class="sidebar">
                                <div class="sidebar-file-manager">
                                    <div class="sidebar-inner">
                                        <!-- sidebar menu links starts -->
                                        <!-- add file button -->
                                        <div class="dropdown dropdown-actions left-ctr">
                                            <h4 class="text-primary">Metrics</h4>
                                        </div>
                                        <!-- add file button ends -->

                                        <!-- sidebar list items starts  -->
                                        <div class="sidebar-list">
                                            <!-- links for file manager sidebar -->
                                            <div class="list-group">
                                                <div class="my-drive"></div>
                                                <div class="jstree-ajax"></div>
                                            </div>
                                            
                                        </div>
                                        <!-- side bar list items ends  -->
                                        <!-- sidebar menu links ends -->
                                    </div>
                                </div>

                            </div>
                        </div>
                        <div class="content-right">
                            <div class="content-wrapper container-xxl p-0">
                                <div class="content-header row">
                                </div>
                                <div class="content-body">
                                    <!-- overlay container -->
                                    <div class="body-content-overlay"></div>

                                    <!-- file manager app content starts -->
                                    <div class="file-manager-main-content">
                                        <!-- search area start -->
                                        <div class="file-manager-content-header d-flex justify-content-between align-items-center">
                                            <div class="d-flex align-items-center">
                                                <div class="sidebar-toggle d-block d-xl-none float-left align-middle ml-1">
                                                    <i data-feather="menu" class="font-medium-5"></i>
                                                </div>
                                                <div class="input-group input-group-merge shadow-none m-0 flex-grow-1">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text border-0">
                                                            <i data-feather="search"></i>
                                                        </span>
                                                    </div>
                                                    <input type="text" class="form-control files-filter border-0 bg-transparent" placeholder="Search" />
                                                </div>
                                            </div>
                                            <div class="d-flex align-items-center">
                                                <div class="btn-group btn-group-toggle view-toggle ml-50" data-toggle="buttons">
                                                    <label class="btn btn-outline-primary p-50 btn-sm active">
                                                        <input type="radio" name="view-btn-radio" data-view="grid" checked />
                                                        <i data-feather="rotate-cw"></i>
                                                    </label>
                                                    <!--
                                                    <label class="btn btn-outline-primary p-50 btn-sm">
                                                        <input type="radio" name="view-btn-radio" data-view="list" />
                                                        <i data-feather="list"></i>
                                                    </label>
                                                    -->
                                                </div>
                                            </div>
                                        </div>
                                        <!-- search area ends here -->

                                        <div class="file-manager-content-body">
                                            <!-- drives area starts-->
                                            <div class="drives">
                                                <div class="row">
                                                    <div class="col-12">
                                                        <h6 class="files-section-title mb-75">Drives</h6>
                                                        The content here
                                                    </div>
                                                    
                                                </div>
                                            </div>
                                            <!-- drives area ends-->

                                            
                                        </div>
                                    </div>
                                    <!-- file manager app content ends -->

                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            

            </div>

        </div>
    `,
});

// User Details Page
Vue.component("user_details", {
  data: function () {
    return {
      userid: "",
      userDetails: true,
      user_status: "",
      bankListData: [],
      roleListData: [],
      permission: getPermission(per, "users"),
      userData: {
        baseData: [],
        financeData: [],
        identityData: [],
        roleData: [],
      },
    };
  },
  mounted() {
    EventBus.$on("g-event-goto-page", this.gotoPageHandler);
    this.getRoleList();
    this.getBankLists();
  },
  methods: {
    gotoPageHandler(data) {
      this.userDetails = true;
      this.userid = data.userid;
      this.user_status = data.user_status;
      this.getUserDetails();
    },
    goToList() {
      EventBus.$emit("g-event-goto-page", {
        page: "list",
        userid: this.userid,
      });
    },
    discardUpdate() {
      var self = this;
      $.confirm({
        title: "WARNING!",
        content:
          "<p>Are you sure you want to discard the changes? </p><br>Discarding the changes means you will loss all changes made",
        buttons: {
          delete: {
            text: "Discard Changes",
            btnClass: "btn btn-warning mr-1",
            action: function () {
              //Attempt Delete
              self.getUserDetails();
              self.userDetails = true;
              overlay.hide();
            },
          },
          close: {
            text: "Cancel",
            btnClass: "btn btn-outline-secondary",
            action: function () {
              // Do nothing
              overlay.hide();
            },
          },
        },
      });
    },
    getUserDetails() {
      /*  Get User Details using userid */
      var self = this;
      var url = common.DataService;
      overlay.show();

      axios
        .get(url + "?qid=005&e=" + self.userid)
        .then(function (response) {
          self.userData.baseData = response.data.base[0]; //All Data
          self.userData.financeData = response.data.finance[0]; //Total Records
          self.userData.identityData = response.data.identity[0]; //Total Records
          self.userData.roleData = response.data.role[0]; //Total Records
          overlay.hide();
        })
        .catch(function (error) {
          overlay.hide();
          alert.Error("ERROR", error);
        });
    },
    getBankLists() {
      /*  Get User Details using userid */
      var self = this;
      var url = common.DataService;
      overlay.show();

      axios
        .get(url + "?qid=gen008")
        .then(function (response) {
          self.bankListData = response.data.data; //All Data
          overlay.hide();
        })
        .catch(function (error) {
          overlay.hide();
          alert.Error("ERROR", error);
        });
    },
    updateUserProfile() {
      var updateFormData = {
        userid: this.userid,
        roleid: this.userData.baseData.roleid,
        first: this.userData.identityData.first,
        middle: this.userData.identityData.middle,
        last: this.userData.identityData.last,
        gender: this.userData.identityData.gender,
        email: this.userData.identityData.email,
        phone: this.userData.identityData.phone,
        bank_name: this.userData.financeData.bank_name,
        account_name: this.userData.financeData.account_name,
        account_no: this.userData.financeData.account_no,
        bank_code: this.userData.financeData.bank_code,
        bio_feature: "",
      };
      /*  Get User Details using userid */
      var self = this;
      var url = common.DataService;
      // overlay.show();
      $.confirm({
        title: "WARNING!",
        content:
          "<p>Are you sure you want to Update the User? </p><br>Updating the User profile means you are changing the user permissions and details",
        buttons: {
          delete: {
            text: "Update Details",
            btnClass: "btn btn-warning mr-1",
            action: function () {
              //Attempt Delete
              axios
                .post(url + "?qid=006", JSON.stringify(updateFormData))
                .then(function (response) {
                  if (response.data.result_code == "200") {
                    overlay.hide();
                    EventBus.$emit("g-event-update-user", {});
                    self.userDetails = true;
                    alert.Success(
                      "SUCCESS",
                      response.data.total + " User Updated"
                    );
                  } else {
                    overlay.hide();
                    alert.Error("ERROR", "User De/Activation failed");
                  }
                })
                .catch(function (error) {
                  overlay.hide();
                  alert.Error("ERROR", error);
                });
            },
          },
          close: {
            text: "Cancel",
            btnClass: "btn btn-outline-secondary",
            action: function () {
              // Do nothing
              overlay.hide();
            },
          },
        },
      });
    },
    getRoleList() {
      /*  Get User Details using userid */
      var self = this;
      var url = common.DataService;
      overlay.show();

      axios
        .get(url + "?qid=007")
        .then(function (response) {
          self.roleListData = response.data.data; //All Data
          overlay.hide();
        })
        .catch(function (error) {
          overlay.hide();
          alert.Error("ERROR", error);
        });
    },
    checkIfEmpty(data) {
      if (data === null || data === "") {
        return "Nil";
      } else {
        return data;
      }
    },
    userActivationDeactivation(actionid) {
      var self = this;
      let selectedId = [actionid];

      var url = common.DataService;
      overlay.show();

      axios
        .post(url + "?qid=001", JSON.stringify(selectedId))
        .then(function (response) {
          overlay.hide();
          if (response.data.result_code == "200") {
            EventBus.$emit("g-event-update-user", {});
            self.user_status == "1"
              ? (self.user_status = 0)
              : (self.user_status = 1);
            alert.Success("SUCCESS", "User De/Activation Successful");
          } else {
            alert.Error("ERROR", "User De/Activation failed");
          }
          overlay.hide();
        })
        .catch(function (error) {
          overlay.hide();
          alert.Error("ERROR", error);
        });
    },
    changeRole(event) {
      this.userData.baseData.role =
        event.target.options[event.target.options.selectedIndex].text;
    },
    changeBank(event) {
      this.userData.financeData.bank_name =
        event.target.options[event.target.options.selectedIndex].text;
    },
    downloadBadge(userid) {
      overlay.show();
      window.open(common.BadgeService + "?qid=002&e=" + userid, "_parent");
      overlay.hide();
    },
    numbersOnlyWithoutDot(evt) {
      evt = evt ? evt : window.event;
      var charCode = evt.which ? evt.which : evt.keyCode;
      if (charCode > 31 && (charCode < 48 || charCode > 57)) {
        evt.preventDefault();
      } else {
        return true;
      }
    },
  },
  template: `
        <div class="row">
            <div class="col-md-12 col-sm-12 col-12 mb-1">
                <h2 class="content-header-title header-txt float-left mb-0">Users</h2>
                <div class="breadcrumb-wrapper">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../users">Home</a></li>
                        <li class="breadcrumb-item"><a href="javascript:void(0);" @click="goToList()">Users List</a></li>
                        <li v-if="userDetails" class="breadcrumb-item active">User Details</li>
                        <li v-else class="breadcrumb-item active">User Update</li>
                    </ol>
                </div>
            </div>

            <!-- User Sidebar -->
            <div class="col-xl-4 col-lg-5 col-md-5 order-1 order-md-0 sidebar-sticky">
                <!-- User Card -->
                <div class="card">
                    <div class="card-body">
                        <div class="user-avatar-section">
                            <div class="d-flex align-items-center flex-column">
                                <img class="img-fluid rounded mt-3 mb-2" src="../app-assets/images/avatar.png" height="110" width="110" alt="User avatar">
                                <div class="user-info text-center">
                                    <h4 v-html="userData.baseData.loginid"></h4>
                                    <span class="badge bg-light-primary" v-html="userData.baseData.role"></span>
                                </div>
                            </div>
                        </div>
                        <div class="fw-bolder border-bottom font-small-2 pb-20 mb-1 mt-1 text-center">{{userData.baseData.geo_string}}</div>
                        <div class="info-container">
                            <ul class="list-unstyled pl-2">
                                <li class="mb-75">
                                    <span class="fw-bolder me-25">Username:</span>
                                    <span v-html="userData.baseData.username"></span>
                                </li>
                                <li class="mb-75">
                                    <span class="fw-bolder me-25">User Group:</span>
                                    <span v-html="userData.baseData.user_group"></span>
                                </li>
                                <li class="mb-75">
                                    <span class="fw-bolder me-25">Status:</span>
                                    <span class="badge " :class="user_status==1? 'bg-light-success' : 'bg-light-danger'">{{user_status==1? 'Active' : 'Inactive'}}</span>
                                </li>
                            </ul>

                            <div class="justify-content-center pt-2 form-group">
                                <button class="btn btn-primary mb-1 form-control suspend-user waves-effect" @click="downloadBadge(userid)"><i class="feather icon-download"></i> Download Badge</button>
                                <button v-if="userDetails && permission.permission_value >=2" @click="userDetails = false" class="btn btn-outline-primary mb-1 form-control suspend-user waves-effect"><i class="feather icon-edit-2"></i>  Edit</button>
                                <button v-if="permission.permission_value ==3" class="btn form-control suspend-user waves-effect" :class="user_status== 1 ? 'btn-danger' : 'btn-success'" @click="userActivationDeactivation(userid)"><i class="feather" :class="user_status== 1 ? 'icon-user-x' : 'icon-user-check'"></i> {{user_status==1? ' Deactivate' : ' Activate'}}</button>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- /User Left Sidebar -->
            </div>
            <!--/ User Sidebar -->

            <!-- User Content -->
            <div class="col-xl-8 col-lg-7 col-md-7 order-0 order-md-1">
                <!-- User Details: Start -->
                <div v-if="userDetails">
                    <!-- User Details -->
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title mb-50">Details</h4>
                            <button v-if="permission.permission_value >=2" class="btn btn-primary btn-sm waves-effect waves-float waves-light" @click="userDetails = false">
                                <i class="feather icon-edit-2"></i> <span> Edit</span>
                            </button>
                        </div>
                        <div class="card-body row">
                            <div class="col-12">
                                <table class="table">
                                    <tr>

                                        <td class="user-detail-txt">
                                            <label class="d-block text-primary">Firstname</label>
                                            {{checkIfEmpty(userData.identityData.first)}}
                                        </td>
                                        <td class="user-detail-txt">
                                            <label class="d-block text-primary">Middle</label>
                                            {{checkIfEmpty(userData.identityData.middle)}}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="user-detail-txt">
                                            <label class="d-block text-primary">Lastname</label>
                                            {{checkIfEmpty(userData.identityData.last)}}
                                        </td>
                                        <td class="user-detail-txt">
                                            <label class="d-block text-primary">Gender</label>
                                            {{checkIfEmpty(userData.identityData.gender)}}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="user-detail-txt">
                                            <label class="d-block text-primary">Phone No</label>
                                            {{checkIfEmpty(userData.identityData.phone)}}
                                        </td>
                                        <td class="user-detail-txt">
                                            <label class="d-block text-primary">Email</label>
                                            {{checkIfEmpty(userData.identityData.email)}}
                                        </td>
                                    </tr>

                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title mb-50">Finance</h4>
                        </div>
                        <div class="card-body row">
                            <div class="col-12">
                                <table class="table">
                                    <tr>
                                        <td colspan="2" class="user-detail-txt">
                                            <label class="d-block text-primary">Account Name</label>
                                            {{checkIfEmpty(userData.financeData.account_name)}}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="user-detail-txt">
                                            <label class="d-block text-primary">Account Number</label>
                                            {{checkIfEmpty(userData.financeData.account_no)}}
                                        </td>
                                        <td class="user-detail-txt">
                                            <label class="d-block text-primary">Bank Name</label>
                                            {{checkIfEmpty(userData.financeData.bank_name)}}
                                        </td>
                                    </tr>

                                </table>
                            </div>
                        </div>
                    </div> 

                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title mb-50">Role</h4>
                        </div>
                        <div class="card-body row">
                            <div class="col-12">
                                <table class="table">
                                    <tr>
                                        <td colspan="2" class="user-detail-txt">
                                            <label class="d-block text-primary">Role</label>
                                            {{checkIfEmpty(userData.baseData.role)}}
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div> 
              
                </div>
                <!-- User Details: End -->

                <!-- User Details Form -->
                <form method="POST" @submit.stop.prevent="updateUserProfile()" v-else>
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title mb-50">Details</h4>
                        </div>
                        <div class="card-body row">
                            <div class="col-6">
                                <div class="form-group">
                                    <label>First Name</label>
                                    <input type="text" id="firstname" v-model="userData.identityData.first" class="form-control firstname" placeholder="First Name" name="firstname" />
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label>Middle Name</label>
                                    <input type="text" id="middlename" v-model="userData.identityData.middle" class="form-control middlename" placeholder="Middle Name" name="middlename" />
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label>Lastname</label>
                                    <input type="text" id="lastname" v-model="userData.identityData.last" class="form-control lastname" placeholder="Last Name" name="lastname" />
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label>Gender</label>
                                    <select name="gender" v-model="userData.identityData.gender" class="form-control">
                                        <option :selected="userData.identityData.gender == 'Male'">Male</option>
                                        <option :selected="userData.identityData.gender == 'Female'">Female</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label>Phone No</label>
                                    <input type="text" id="phoneno" maxlength="11" v-model="userData.identityData.phone" @keypress="numbersOnlyWithoutDot" class="form-control phoneno" placeholder="Phone No" name="phoneno" />
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label>Email</label>
                                    <input type="email" id="email" v-model="userData.identityData.email" class="form-control email" placeholder="Email" name="email" />
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- / User Details Form -->

                    <!--  Finance -->
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title mb-50">Finance</h4>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-12">
                                    <div class="form-group">
                                        <label>Account Name</label>
                                        <input type="text" id="account_name" v-model="userData.financeData.account_name" class="form-control account_name" placeholder="Account Name" name="account_name" />
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="form-group">
                                        <label>Account Number</label>
                                        <input type="text" id="account_no" @keypress="numbersOnlyWithoutDot" maxlength="10" v-model="userData.financeData.account_no" class="form-control account_no" placeholder="Account Number" name="account_no" />
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="form-group">
                                        <label>Bank Name</label>
                                        <select name="bank_code" v-model="userData.financeData.bank_code" @change="changeBank($event)" class="form-control bank_code select2">
                                            <option v-for="b in bankListData" :value="b.bank_code" :selected="b.bank_code == userData.financeData.bank_code">{{b.bank_name}}</option>
                                        </select>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>

                    <!--  User Role and Priviledge -->
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title mb-50">Role</h4>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-12">
                                    <div class="form-group">
                                        <label>Role</label>
                                        <select name="role" :disabled="permission.permission_value <3" v-model="userData.baseData.roleid" @change="changeRole($event)" class="form-control role select2">
                                            <option v-for="r in roleListData" :value="r.roleid" :key="r.roleid" :selected="r.roleid == userData.baseData.roleid">{{r.role}}</option>
                                        </select>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-6">
                            <button type="button" @click="discardUpdate()" class="btn btn-outline-secondary form-control mt-2 waves-effect">Cancel</button>
                        </div>
                        <div class="col-6">
                            <button v-if="permission.permission_value >=2" class="btn btn-primary form-control mt-2 waves-effect waves-float waves-light">Update Details</button>
                        </div>
                    </div>


                </form>
                <!--/ Billing Finance -->
            </div>
            <!--/ User Content -->
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
