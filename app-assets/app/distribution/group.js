Vue.component('page-body', {
    data: function() {
        return {
            page: 'home', //  page by name home | result | ...
        }
    },
    mounted() {
        /*  Manages events Listening    */

    },
    methods: {

    },
    template: `
    <div>

        <div class="content-body">
            <sample_table/>
        </div>
    </div>
    `
});


Vue.component('sample_table', {
    data: function() {
        return {
            "tableData": [],
            "checkToggle": false,
            "filterState": false,
            "filters": false,
            "url": common.BadgeService,
            "tableOptions": {
                "total": 1, //Total record 
                "pageLength": 1, //Total 
                "perPage": 10,
                "currentPage": 1,
                "orderDir": "asc", // (asc|desc)
                "orderField": 0, //(Order fields)
                "limitStart": 0, //(currentPage - 1) * perPage
                "isNext": false,
                "isPrev": false,
                "aLength": [10, 20, 50, 100],
                "filterParam": {
                    "usergroup": ""
                }
            },
            "errors": [],
            "bulkUserModal": false,
            "bulkUserForm": {
                "totalUser": 1,
                "groupName": "",
                "password": "",
                "geoLevel": "",
                "geoLevelId": 0
            },
            "geoIndicator": {
                "state": 50,
                "currentLevelId": 0,
                "lga": "",
                "cluster": "",
                "ward": ""
            },
            "geoLevelData": [],
            "sysDefaultData": [],
            "lgaLevelData": [],
            "clusterLevelData": [],
            "wardLevelData": [],
            "dpLevelData": [],

        }
    },
    mounted() {
        /*  Manages events Listening    */
        this.getGeoLevel();
        this.getsysDefaultDataSettings();
        this.loadTableData();
    },
    methods: {
        loadTableData() {
            /*  Manages the loading of table data */
            var self = this;
            var url = common.TableService;
            overlay.show();

            axios.get(url + "?qid=002&draw=" + self.tableOptions.currentPage + "&order_column=" + self.tableOptions.orderField + "&length=" + self.tableOptions.perPage + "&start=" + self.tableOptions.limitStart + "&order_dir=" + self.tableOptions.orderDir + "&gr=" + self.tableOptions.filterParam.usergroup)
                .then(function(response) {

                    self.tableData = response.data.data; //All Data
                    self.tableOptions.total = response.data.recordsTotal; //Total Records
                    if (self.tableOptions.currentPage == 1) {
                        self.paginationDefault();
                    }
                    overlay.hide();
                })
                .catch(function(error) {
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
            return this.filterState = !this.filterState;
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
            this.tableOptions.pageLength = Math.ceil(this.tableOptions.total / this.tableOptions.perPage);

            // Page Limit
            this.tableOptions.limitStart = Math.ceil((this.tableOptions.currentPage - 1) * this.tableOptions.perPage);

            //  Next
            if (this.tableOptions.currentPage < this.tableOptions.pageLength &&
                this.tableOptions.currentPage != this.tableOptions.pageLength) {
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
                this.tableOptions.orderDir = this.tableOptions.orderDir === "asc" ? "desc" : "asc";
            } else {
                this.tableOptions.orderField = col;
            }

            this.paginationDefault();
            this.loadTableData();
        },
        applyFilter() {
            // Check if any filter fields are filled
            let checkFill = 0;
            checkFill += (this.tableOptions.filterParam.usergroup != "") ? 1 : 0;

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
        clearAllFilter() {
            this.filters = false;
            this.tableOptions.filterParam.usergroup = "";
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
        activateUserByGroup(group) {

            var self = this;
            var url = common.DataService;
            // overlay.show();

            $.confirm({
                title: 'WARNING!',
                content: 'Are you sure you want to Activate all the Users in <b>' + group + '</b> group? <br><br>Make sure you are sure that you want to activate all the user in this group.',
                buttons: {
                    delete: {
                        text: 'Activate All',
                        btnClass: 'btn btn-danger mr-1',
                        action: function() {
                            //Attempt Delete
                            axios.post(url + "?qid=004&e=" + group)
                                .then(function(response) {

                                    overlay.hide();
                                    if (response.data.result_code == "201") {
                                        self.loadTableData();
                                        alert.Success("SUCCESS", response.data.group + " user group has been activated successfully");
                                    } else {
                                        alert.Error("ERROR", "Unable to activate " + response.data.group + " at the moment please try again later");
                                    }
                                    overlay.hide();

                                })
                                .catch(function(error) {
                                    overlay.hide();
                                    alert.Error("ERROR", error);

                                });
                        }
                    },
                    cancel: function() {
                        // Do nothing
                        overlay.hide();
                    }
                }
            });


        },
        deactivateUserByGroup(group) {

            var self = this;
            var url = common.DataService;
            // overlay.show();
            $.confirm({
                title: 'WARNING!',
                content: 'Are you sure you want to Detivate all the Users in <b>' + group + '</b> group? <br><br>Make sure you are sure that you want to deactivate all the user in this group, deactivating users means you want to deny them access to the system.',
                buttons: {
                    delete: {
                        text: 'Dectivate All',
                        btnClass: 'btn btn-danger mr-1',
                        action: function() {
                            //Attempt Delete
                            axios.post(url + "?qid=003&e=" + group)
                                .then(function(response) {

                                    overlay.hide();
                                    if (response.data.result_code == "201") {
                                        self.loadTableData();
                                        alert.Success("SUCCESS", response.data.group + " user group has been deactivated successfully");
                                    } else {
                                        alert.Error("ERROR", "Unable to deactivate " + response.data.group + " at the moment please try again later.");
                                    }
                                    overlay.hide();

                                })
                                .catch(function(error) {
                                    overlay.hide();
                                    alert.Error("ERROR", error);

                                });
                        }
                    },
                    cancel: function() {
                        // Do nothing
                        overlay.hide();
                    }
                }
            });

        },
        showBulkUserModal() {
            this.bulkUserModal = true;
        },
        hideBulkUserModal() {
            this.bulkUserModal = false;
        },
        getsysDefaultDataSettings() {
            /*  Manages the loading of System default settings */
            var self = this;
            var url = common.DataService;
            overlay.show();

            axios.get(url + "?qid=gen007")
                .then(function(response) {
                    if (response.data.data.length > 0) {
                        self.sysDefaultData = response.data.data[0]; //All Data
                        self.getLgasLevel(response.data.data[0].stateid);
                        //  Set preventDefault();
                        self.bulkUserForm.geoLevel = 'state';
                        self.bulkUserForm.geoLevelId = response.data.data[0].stateid;
                    }

                    overlay.hide();
                })
                .catch(function(error) {
                    overlay.hide();
                    alert.Error("ERROR", error);
                });
        },
        getGeoLevel() {
            /*  Manages the loading of Geo Level data */
            var self = this;
            var url = common.DataService;
            overlay.show();

            axios.get(url + "?qid=gen001")
                .then(function(response) {
                    self.geoLevelData = response.data.data; //All Data
                    // self.tableOptions.total = response.data.recordsTotal; //Total Records
                    overlay.hide();
                })
                .catch(function(error) {
                    overlay.hide();
                    alert.Error("ERROR", error);
                });
        },
        getLgasLevel(stateid) {
            /*  Manages the loading of Geo Level data */
            var self = this;
            var url = common.DataService;
            overlay.show();

            axios.post(url + "?qid=gen003", JSON.stringify(stateid))
                .then(function(response) {
                    self.lgaLevelData = response.data.data; //All Data
                    overlay.hide();
                })
                .catch(function(error) {
                    overlay.hide();
                    alert.Error("ERROR", error);
                });

        },
        getClusterLevel() {
            /*  Manages the loading of Geo Level data */
            var self = this;
            var url = common.DataService;
            overlay.show();

            axios.get(url + "?qid=gen004&e=" + self.geoIndicator.cluster)
                .then(function(response) {
                    self.clusterLevelData = response.data.data; //All Data
                    overlay.hide();
                })
                .catch(function(error) {
                    overlay.hide();
                    alert.Error("ERROR", error);
                });

        },
        getWardLevel() {
            /*  Manages the loading of Geo Level data */
            var self = this;
            var url = common.DataService;
            overlay.show();

            axios.get(url + "?qid=gen005&e=" + self.geoIndicator.lga)
                .then(function(response) {
                    self.wardLevelData = response.data.data; //All Data
                    overlay.hide();
                })
                .catch(function(error) {
                    overlay.hide();
                    alert.Error("ERROR", error);
                });

        },
        getDpLevel() {
            /*  Manages the loading of Geo Level data */
            var self = this;
            var url = common.DataService;
            overlay.show();

            axios.get(url + "?qid=gen006")
                .then(function(response) {
                    self.dpLevel = response.data.data; //All Data
                    overlay.hide();
                })
                .catch(function(error) {
                    overlay.hide();
                    alert.Error("ERROR", error);
                });

        },
        changeGeoLevel() {

            if (this.bulkUserForm.geoLevel == "country" || this.bulkUserForm.geoLevel == "dp") {
                alert.Error("ERROR", "Invalid Geo-Level selected, please select a valid Geo-Level");
            } else if (this.bulkUserForm.geoLevel == "state") {
                //  State here

            } else if (this.bulkUserForm.geoLevel == "lga") {
                //  Lga here
            } else if (this.bulkUserForm.geoLevel == "cluster") {
                //  Cluster here
            } else if (this.bulkUserForm.geoLevel == "ward") {
                // Ward here
            }
        },
        onSubmitBulkUserCreation() {
            //Validate Form
            var self = this;
            var url = common.DataService;
            overlay.show();
            axios.post(url + "?qid=002", JSON.stringify(self.bulkUserForm))
                .then(function(response) {
                    if (response.data.result_code == '201') {
                        self.resetBulkUserForm();
                        self.bulkUserModal = false;
                        $('#addNewUser').modal('hide');
                        self.loadTableData();
                        alert.Success("Success", response.data.total + " Users Created Successfully");
                        overlay.hide();
                    } else {
                        overlay.hide();
                        alert.Error("Error", "Users Creation Failed, Kindly check your input fields");
                    }
                    // Unable to create new record

                })
                .catch(function(error) {
                    alert.Error("ERROR", error);
                    overlay.hide();
                });

        },
        resetBulkUserForm() {
            this.bulkUserForm.totalUser = 1;
            this.bulkUserForm.groupName = "";
            this.bulkUserForm.password = "";
            this.getsysDefaultDataSettings();
            overlay.hide();

        },
        refreshData() {
            this.paginationDefault();
            this.loadTableData();
        },
        downloadGroupBadge(user_group) {
            overlay.show();
            window.popup = window.open(this.url + "?qid=001&e=" + user_group, '_parent');
            overlay.hide();
        }

    },
    computed: {

    },
    template: `

        <div class="row" id="basic-table">

            <div class="col-md-8 col-sm-12 col-12 mb-0">
                <h2 class="content-header-title header-txt float-left mb-0">Users</h2>
                <div class="breadcrumb-wrapper">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../users">Home</a></li>
                        <li class="breadcrumb-item active">User Group </li>
                    </ol>
                </div>
            </div>

            <div class="col-md-4 col-sm-12 col-12 text-md-right text-right d-md-block">
                <div class="btn-group mr-1">
                    <button type="button" @click="showBulkUserModal()" data-toggle="modal" data-target="#addNewUser" class="btn btn-outline-primary round"><i data-feather='user-plus'></i> </button>
                    <button type="button" class="btn btn-outline-primary round searchBtn" @click="refreshData()">
                        <i class="feather icon-refresh-cw"></i>         
                    </button>  
                    <button type="button" class="btn btn-outline-primary round searchBtn" @click="toggleFilter()">
                        <i class="feather" :class="filterState ? 'icon-x' : 'icon-filter'"></i>               
                    </button>
                    
                    <button class="btn btn-outline-primary round dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        Actions
                    </button>
                    <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                        <a class="dropdown-item" href="javascript:void(0);" @click="">De/Activate User</a>
                        <a class="dropdown-item" href="javascript:void(0);">Download Badge</a>
                    </div>
                </div>
            </div>
            <div class="col-12" v-if="filters">
                <div class="col-12 filter-bar">
                    <span class="badge badge-dark filter-box" v-for="(filterParam, i) in tableOptions.filterParam" v-if="filterParam.length > 0">{{capitalize(i)}}: {{filterParam}} <i class="feather icon-x"></i></span>
                    <a href="#" class="float-right clear-filter" @click="clearAllFilter()">Clear</a>
                </div>
            </div>

            <div class="col-12 mt-1">
                <div class="card">
                    <div class="card-body py-1" v-show="filterState">
                        <form id="filterForm">
                            <div class="row">
                                <div class="col-9 col-md-9">
                                    <div class="form-group">
                                        <input type="text" v-model="tableOptions.filterParam.usergroup" class="form-control usergroup" id="usergroup" placeholder="User Group Name" name="usergroup" />
                                    </div>
                                </div>
                                <div class="col-3 col-md-3 text-right">
                                    <button type="button" class="btn btn-md btn-primary"  @click="applyFilter()">Apply Filters</button>
                                </div>
                                
                            </div>
                        </form>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th width="60px">

                                        <div class="custom-control custom-checkbox checkbox">
                                            <input type="checkbox" class="custom-control-input" @change="selectToggle()" id="all-check" />
                                            <label class="custom-control-label" for="all-check"></label>
                                        </div>
                                    </th>
                                    <th @click="sort(2)">
                                        User Group Name
                                        <i class="feather icon-chevron-up sort-up" :class="(tableOptions.orderField == 2 && tableOptions.orderDir =='asc')? 'active-sort': ''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 2 && tableOptions.orderDir =='desc')? 'active-sort': ''"></i>
                                    </th>
                                    <th @click="sort(1)">
                                        Total Users
                                        <i class="feather icon-chevron-up sort-up" :class="(tableOptions.orderField == 1 && tableOptions.orderDir =='asc')? 'active-sort': ''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 1 && tableOptions.orderDir =='desc')? 'active-sort': ''"></i>
                                    </th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(g, i) in tableData" :class="checkedBg(g.pick)">
                                    <td>
                                        <div class="custom-control custom-checkbox checkbox">
                                            <input type="checkbox" class="custom-control-input" :id="g.user_group" v-model="g.pick" />
                                            <label class="custom-control-label" :for="g.user_group"></label>
                                        </div>
                                    </td>
                                    <td>{{g.user_group}}</td>
                                    <td>{{parseInt(g.total).toLocaleString()}}</td>
                                    <td>
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
                                    </td>
                                </tr>
                                <tr v-if="tableData.length == 0"><td class="text-center pt-2" colspan="4"><small>No User Group Added</small></td></tr>

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

            <!-- Modal to add new user starts-->
            <div v-if="bulkUserModal" class="modal modal-slide-in new-user-modal" :class="bulkUserModal? 'show' : 'fade'" id="addNewUser" style="display: block; padding-right: 17px;">
                <div class="modal-dialog modal-scrollable modal-xl">
                    <form id="add-new-user" class="add-new-user modal-content pt-0" @submit.stop.prevent="onSubmitBulkUserCreation()">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close" @click="hideBulkUserModal()">×</button>
                        <div class="modal-header mb-1">
                            <h5 class="modal-title" id="exampleModalLabel">Create New User</h5>
                        </div>
                        <div class="modal-body flex-grow-1">
                            <div class="form-group">
                                <label class="form-label" for="total-user">Total Number of User</label>
                                <input type="number" id="total-user" class="form-control total-user" required v-model="bulkUserForm.totalUser" placeholder="Total Number of User" name="total-user" />
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="group-name">Group Name</label>
                                <input type="text" class="form-control" required id="group-name" v-model="bulkUserForm.groupName" placeholder="Group Name" name="group-name" />
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="password">Password</label>
                                <input type="text" required id="password" class="form-control password" v-model="bulkUserForm.password" placeholder="Password" name="password" />
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="user-role">Geo Level</label>
                                <select id="user-role" @change="changeGeoLevel()" class="form-control" v-model="bulkUserForm.geoLevel">
                                    <option v-for="geo in geoLevelData" :value="geo.geo_level">{{(geo.geo_level)}}</option>
                                </select>
                            </div>

                            <div class="form-group" v-if="bulkUserForm.geoLevel == 'state'">
                                <label class="form-label" for="user-role">State</label>
                                <select id="user-role" placeholder="Select Geo Level" class="form-control" v-model="bulkUserForm.geoLevelId">
                                    <option  :value="sysDefaultData.stateid">{{sysDefaultData.state}}</option>
                                </select>
                            </div>

                            <div class="form-group" v-if="bulkUserForm.geoLevel == 'lga'">
                                <label class="form-label" for="user-role">LGA List</label>
                                <select id="user-role" class="form-control" v-model="bulkUserForm.geoLevelId">
                                    <option v-for="lga in lgaLevelData" :value="lga.lgaid">{{lga.lga}}</option>
                                </select>
                            </div>

                            <div v-if="bulkUserForm.geoLevel == 'cluster'">
                                <div class="form-group">
                                    <label class="form-label" for="user-role">LGA List</label>
                                    <select id="user-role" class="form-control" @change="getClusterLevel()" v-model="geoIndicator.cluster">
                                        <option v-for="lga in lgaLevelData" :value="lga.lgaid">{{lga.lga}}</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label" for="user-role">Cluster</label>
                                    <select class="form-control"  v-model="bulkUserForm.geoLevelId">
                                        <option v-for="g in clusterLevelData" :value="g.clusterid">{{g.cluster}}</option>
                                    </select>
                                </div>
                            </div>

                            <div v-if="bulkUserForm.geoLevel == 'ward'">
                                <div class="form-group">
                                    <label class="form-label" for="user-role">LGA List</label>
                                    <select id="user-role" class="form-control" @change="getWardLevel()"  v-model="geoIndicator.lga">
                                        <option v-for="lga in lgaLevelData" :value="lga.lgaid">{{lga.lga}}</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label" for="user-role">Ward</label>
                                    <select class="form-control" v-model="bulkUserForm.geoLevelId">
                                        <option v-for="g in wardLevelData" :value="g.wardid">{{g.ward}}</option>
                                    </select>
                                </div>
                            </div>
                            <div class="mt-2">
                                <button type="submit" class="btn btn-primary mr-1 data-submit">Save</button>
                                <button type="reset" class="btn btn-outline-secondary" data-dismiss="modal" @click="hideBulkUserModal()">Cancel</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <!-- Modal to add new user Ends-->


        </div>

    `
});
var vm = new Vue({
    el: "#app",
    data: {},
    methods: {

    },
    template: `
        <div>
            <page-body/>
        </div>
    `
});