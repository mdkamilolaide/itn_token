Vue.component("page-body", {
  data: function () {
    return {
      page: "dashboard", //  page by name dashbaord | result | ...
      permission: getPermission(per, "distribution"),
    };
  },
  mounted() {
    /*  Manages events Listening    */
  },
  methods: {},
  template: `
    <div>
        <div class="content-body">
            <dashboard_container v-if="permission.permission_value >1" />
            <div class="alert alert-danger" v-else>
              <div class="alert-body">
                <strong>Access Denied!</strong> You don't have permission to access this page.
                </div>
            </div>
        </div>
    </div>
    `,
});

Vue.component("dashboard_container", {
  data: function () {
    return {
      geoIndicator: {
        state: 50,
        currentLevelId: 0,
        lga: "",
        ward: "",
      },
      checkToggle: false,
      geoLevelData: [],
      sysDefaultData: [],
      lgaLevelData: [],
      wardLevelData: [],
      tableData: [],
      bulkUserForm: {
        geoLevel: "",
        geoLevelId: 0,
        mobilizationDate: "",
      },
    };
  },
  mounted() {
    /*  Manages events Listening    */
    this.getGeoLevel();
    this.getsysDefaultDataSettings();
    $("#dp-search").on("keyup", function () {
      var value = $(this).val().toLowerCase();
      $("#dpTable tbody tr").filter(function () {
        $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
      });
    });
  },
  methods: {
    getAllStat() {
      var url = common.DataService;
      var self = this;
      var endpoints = [
        // url + "?qid=401&wardid=" + self.bulkUserForm.geoLevelId, //Get Gender Count [6]
        url + "?qid=401a&lgaid=" + self.geoIndicator.lga, //Get Gender Count [6]
        // self.geoIndicator.lga
      ];
      overlay.show();
      // Return our response in the allData variable as an array
      Promise.all(endpoints.map((endpoint) => axios.get(endpoint))).then(
        axios.spread((...allData) => {
          // console.log(allData[0].data.data)
          self.tableData = allData[0].data.data;
          overlay.hide();
        })
      );
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
            self.getLgasLevel(response.data.data[0].stateid);
            //  Set preventDefault();
            self.bulkUserForm.geoLevel = "state";
            self.bulkUserForm.geoLevelId = response.data.data[0].stateid;
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
    getWardLevel() {
      /*  Manages the loading of Geo Level data */
      var self = this;
      var url = common.DataService;
      overlay.show();
      this.bulkUserForm.geoLevelId = "";

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
    capitalize(word) {
      if (word) {
        const loweredCase = word.toLowerCase();
        return word[0].toUpperCase() + loweredCase.slice(1);
      } else {
        return word;
      }
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
      //   console.log(selectedItems);
      return selectedItems;
    },
    selectedID() {
      /*  Manages the selections of checkedor selected data object */
      let selectedIds = [];
      if (this.tableData.length > 0) {
        for (let i = 0; i < this.tableData.length; i++) {
          if (this.tableData[i].pick) {
            selectedIds.push(this.tableData[i].dpid);
          }
        }
      }
      return selectedIds;
    },
    downloadDpBadge(i) {
      overlay.show();

      const url = `${common.DpBadgeService}?qid=002&guid=${
        this.tableData[i].guid
      }&geo_string=${encodeURIComponent(
        this.tableData[i].geo_string
      )}&title=${encodeURIComponent(this.tableData[i].title)}`;

      // Create hidden iframe
      const iframe = document.createElement("iframe");
      iframe.style.display = "none";
      iframe.src = url;

      // Append to DOM
      document.body.appendChild(iframe);

      // Wait for a few seconds, then hide overlay and clean up
      setTimeout(() => {
        overlay.hide();
        document.body.removeChild(iframe);
      }, 5000); // Adjust this delay based on expected download readiness
    },
    downloadBadges() {
      overlay.show();
      var url = common.DpBadgeService;
      if (parseInt(this.selectedID().length) > 0) {
        window.open(url + "?qid=001&e=" + this.selectedID(), "_parent");
      } else {
        alert.Error("Badge Download Failed", "No user DP List");
      }
      overlay.hide();
    },
    loadDp() {
      if (this.geoIndicator.lga == "") {
        alert.Error("Error", "Kindly choose a LGA and Ward");
      } else {
        if (this.geoIndicator.lga != "") {
          this.getAllStat();
        } else {
          alert.Error("Error", "Kindly choose a Ward");
        }
      }
    },
  },
  computed: {},
  template: `
        <div>
            <div class="row">
                <div class="col-12 mb-2">
                    <h2 class="content-header-title header-txt float-left mb-0">Distribution</h2>
                    <div class="breadcrumb-wrapper">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item active">Distribution Point List</li>
                        </ol>
                    </div>
                </div>
            </div>
            
            <div class="row">
            
                <div class="col-12">
                    <div class="alert bg-light-primary pt-1 pb-1">
              
                        <div class="col-12">
                            <div class="row">
                                <div class="col-12 col-lg8 col-md-8 col-sm-12">
                                    <div class="form-group">
                                        <label class="form-label" for="user-role">LGA List</label>
                                        <!--<select id="user-role" class="form-control" @change="getWardLevel()" v-model="geoIndicator.lga"> -->
                                        <select id="user-role" class="form-control" @change="loadDp()" v-model="geoIndicator.lga">
                                            <option  value="" selected="selected">Select a LGA</option>
                                            <option v-for="lga in lgaLevelData" :value="lga.lgaid">{{lga.lga}}</option>
                                        </select>
                                    </div>
                                </div>
                                <!--
                                <div class="col-12 col-lg-4 col-md-4 col-sm-6">
                                    <div class="form-group">
                                        <label class="form-label" for="user-role">Ward</label>
                                        <select class="form-control" v-model="bulkUserForm.geoLevelId">
                                            <option v-for="g in wardLevelData" :value="g.wardid">{{g.ward}}</option>
                                        </select>
                                    </div>
                                </div>
                                -->

                                <div class="col-12 col-lg-4 col-md-4 col-sm-12">
                                    <label class="form-label" for="user-role">DP Name</label>
                                    <div class="input-group date_filter">
                                        <input type="text" id="dp-search" v-model="bulkUserForm.mobilizationDate" class="form-control date" placeholder="Search using DP Name" />
                                        <div class="input-group-append">
                                            <button class="btn btn-primary pl-1 pr-1" @click="loadDp()" type="button">Load</button>
                                        </div>
                                    </div>
                                </div>

                            </div>

                        </div>

                    </div>
                </div>

            </div>

            <div class="card">
                <div class="table-responsive">
                    <table class="table" id="dpTable">
                        <thead class="bg-light-primary">
                            <th width="60px">
                                <div class="custom-control custom-checkbox checkbox">
                                    <input type="checkbox" class="custom-control-input" :checked="checkToggle" @change="selectToggle()" id="all-check" />
                                    <label class="custom-control-label" for="all-check"></label>
                                </div>
                            </th>
                            <th>Distribution Point Name</th>
                            <th>DP Location</th>
                            <th class="text-right">
                                <a href="javascript:void(0)" data-toggle="tooltip" data-placement="top" title="Downlaod All" class="btn btn-primary btn-sm p-50" @click="downloadBadges()"><i class="feather icon-download-cloud"></i></a>
                            </th>
                        </thead>
                        <tbody>
                            <tr v-for="(g, i) in tableData">
                                <td>
                                    <div class="custom-control custom-checkbox checkbox">
                                        <input type="checkbox" class="custom-control-input" :id="g.dpid" v-model="g.pick" />
                                        <label class="custom-control-label" :for="g.dpid"></label>
                                    </div>
                                </td>
                                <td>{{g.title}}</td>
                                <td>{{g.geo_string}}</td>
                                <td class="text-right">
                                    <!--
                                    <div class="dropdown">
                                        <button type="button" class="btn btn-sm dropdown-toggle hide-arrow" data-toggle="dropdown">
                                            <i class="feather icon-more-vertical"></i>
                                        </button>
                                        <div class="dropdown-menu">
                                            <a class="dropdown-item" href="javascript:void(0);" @click="activateUserByGroup(g.user_group)">
                                                <i class="feather icon-user-check mr-50"></i>
                                                <span>Activate Users</span>
                                            </a>
                                            <a class="dropdown-item" href="javascript:void(0);" @click="deactivateUserByGroup(g.user_group)">
                                                <i class="feather icon-user-x mr-50"></i>
                                                <span>Deactivate Users</span>
                                            </a>
                                            <a class="dropdown-item" @click="downloadGroupBadge(g.user_group)" href="javascript:void(0)">
                                                <i class="feather icon-download mr-50"></i>
                                                <span>Download Badge</span>
                                            </a>
                                        </div>
                                    </div>
                                    -->
                                    <a href="javascript:void(0)" class="btn btn-primary btn-sm p-50" @click="downloadDpBadge(i)"><i class="feather icon-download"></i></a>
                                </td>
                            </tr>
                            <tr v-if="tableData.length == 0"><td class="text-center pt-2" colspan="5"><small>No Ward Choosen or DP List Empty</small></td></tr>
                        </tbody>
                    </table>
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
