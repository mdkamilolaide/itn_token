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
                "filterParameters": {
                    "loginid": "",
                    "firstname": ""
                }

            }
        }
    },
    mounted() {
        /*  Manages events Listening    */
        this.loadTableData();
    },

    methods: {
        loadTableData() {
            /*  Manages the loading of table data */
            var self = this;
            var url = common.TableService;
            overlay.show();
            axios.get(url + "?qid=200&draw=" + self.tableOptions.currentPage + "&order_column=" + self.tableOptions.orderField + "&length=" + self.tableOptions.perPage + "&start=" + self.tableOptions.limitStart + "&order_dir=" + self.tableOptions.orderDir)
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
            checkFill += (this.tableOptions.filterParameters.firstname != "") ? 1 : 0;
            checkFill += (this.tableOptions.filterParameters.loginid != "") ? 1 : 0;

            if (checkFill > 0) {
                this.toggleFilter();
                this.filters = true;
                this.loadTableData();
            } else {
                alert.Error("ERROR", "Invalid required data");
                return;
            }

        },
        clearAllFilter() {
            this.filters = false;
            this.tableOptions.filterParameters.loginid = this.tableOptions.filterParameters.firstname = "";
        },
        capitalize(word) {
            if (word) {
                const loweredCase = word.toLowerCase();
                return word[0].toUpperCase() + loweredCase.slice(1);
            } else {
                return word;
            }
        }

    },
    computed: {

    },
    template: `

    <div class="row" id="basic-table">

        <div class="col-8">
            <div v-if="filters">
                <span class="badge badge-dark filter-box" v-for="(filterParam, i) in tableOptions.filterParameters" v-if="filterParam.length > 0">{{capitalize(i)}}: {{filterParam}} <i class="feather icon-x"></i></span>
            </div>
        </div>
        
        <div class="col-md-4 col-12 text-md-right text-right d-md-block">
            <div class="btn-group">
                <button type="button" data-toggle="modal" data-target="#addNewUser" class="btn btn-sm btn-outline-primary round"><i data-feather='plus'></i> Add</button>
                
                <button type="button" class="btn btn-sm btn-outline-primary round searchBtn" @click="toggleFilter()">
                    <i class="feather" :class="filterState ? 'icon-x' : 'icon-filter'"></i>               
                </button>
                
                <button class="btn btn-sm btn-outline-primary round dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    Actions
                </button>
                <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                    <a class="dropdown-item" href="javascript:void(0);">Action 1</a>
                    <a class="dropdown-item" href="javascript:void(0);">Action 2</a>
                    <a class="dropdown-item" href="javascript:void(0);">Action 3</a>
                </div>
            </div>
        </div>

        <div class="col-12 mt-2">
            <div class="card">
                <div class="card-body py-1" v-show="filterState">
                    <form id="filterForm">
                        <div class="row">
                            <div class="col-3 col-md-3">
                                <div class="form-group">
                                    <input type="text" v-model="tableOptions.filterParameters.loginid" class="form-control login-id" id="login-id" placeholder="Login ID" name="loginid" />
                                </div>
                            </div>
                            <div class="col-3 col-md-3">
                                <div class="form-group">
                                    <input type="text" id="firstname" v-model="tableOptions.filterParameters.firstname" class="form-control firstname" placeholder="First Name" name="firstname" />
                                </div>
                            </div>
                            <div class="col-3 col-md-3">
                                <div class="form-group">
                                    <select id="user-role" class="form-control">
                                        <option value="">User Role</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-3 col-md-3 text-right">
                                    <button type="button" class="btn btn-md btn-outline-primary" @click="clearAllFilter()">Clear</button>
                                    <button type="button" class="btn btn-md btn-primary"  @click="applyFilter()">Apply Filters</button>
                            </div>
                            
                        </div>
                    </form>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>

                                    <div class="custom-control custom-checkbox checkbox">
                                        <input type="checkbox" class="custom-control-input" @change="selectToggle()" id="all-check" />
                                        <label class="custom-control-label" for="all-check"></label>
                                    </div>
                                </th>
                                <th @click="sort(1)">
                                    Login ID
                                    <i class="feather icon-chevron-up sort-up" :class="(tableOptions.orderField == 1 && tableOptions.orderDir =='asc')? 'active-sort': ''"></i>
                                    <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 1 && tableOptions.orderDir =='desc')? 'active-sort': ''"></i>
                                </th>
                                <th @click="sort(6)">
                                    First Name
                                    <i class="feather icon-chevron-up sort-up" :class="(tableOptions.orderField == 6 && tableOptions.orderDir =='asc')? 'active-sort': ''"></i>
                                    <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 6 && tableOptions.orderDir =='desc')? 'active-sort': ''"></i>
                                </th>
                                <th @click="sort(7)">
                                    Last Name
                                    <i class="feather icon-chevron-up sort-up" :class="(tableOptions.orderField == 7 && tableOptions.orderDir =='asc')? 'active-sort': ''"></i>
                                    <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 7 && tableOptions.orderDir =='desc')? 'active-sort': ''"></i>
                                </th>
                                <th @click="sort(5)">
                                    Role
                                    <i class="feather icon-chevron-up sort-up" :class="(tableOptions.orderField == 5 && tableOptions.orderDir =='asc')? 'active-sort': ''"></i>
                                    <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 5 && tableOptions.orderDir =='desc')? 'active-sort': ''"></i>
                                </th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="g in tableData" :class="checkedBg(g.pick)">
                                <td>
                                    <div class="custom-control custom-checkbox checkbox">
                                        <input type="checkbox" class="custom-control-input" :id="g.loginid" v-model="g.pick" />
                                        <label class="custom-control-label" :for="g.loginid"></label>
                                    </div>
                                </td>
                                <td>{{g.loginid}}</td>
                                <td>{{g.first}}</td>
                                <td>{{g.last}}</td>
                                <td>{{g.role}}</td>
                                <td>
                                    <div class="dropdown">
                                        <button type="button" class="btn btn-sm dropdown-toggle hide-arrow" data-toggle="dropdown">
                                            <i class="feather icon-more-vertical"></i>
                                        </button>
                                        <div class="dropdown-menu">
                                            <a class="dropdown-item" href="javascript:void(0);">
                                                <i class="feather icon-edit-2 mr-50"></i>
                                                <span>Edit</span>
                                            </a>
                                            <a class="dropdown-item" href="javascript:void(0);">
                                                <i class="feather icon-trash mr-50"></i>
                                                <span>Delete</span>
                                            </a>
                                        </div>
                                    </div>
                                </td>
                            </tr>
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


                    
                    <div>
                    
                    </div>
                </div>

                <div class="mb-50"></div>
            </div>
        </div>

        <!-- Modal to add new user starts-->
        <div class="modal modal-slide-in new-user-modal fade" id="addNewUser">
            <div class="modal-dialog modal-scrollable modal-xl">
                <form class="add-new-user modal-content pt-0">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">×</button>
                    <div class="modal-header mb-1">
                        <h5 class="modal-title" id="exampleModalLabel">New User</h5>
                    </div>
                    <div class="modal-body flex-grow-1">
                        <div class="form-group">
                            <label class="form-label" for="basic-icon-default-fullname">Full Name</label>
                            <input type="text" class="form-control dt-full-name" id="basic-icon-default-fullname" placeholder="John Doe" name="user-fullname" aria-label="John Doe" aria-describedby="basic-icon-default-fullname2" />
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="basic-icon-default-uname">Username</label>
                            <input type="text" id="basic-icon-default-uname" class="form-control dt-uname" placeholder="Web Developer" aria-label="jdoe1" aria-describedby="basic-icon-default-uname2" name="user-name" />
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="basic-icon-default-email">Email</label>
                            <input type="text" id="basic-icon-default-email" class="form-control dt-email" placeholder="john.doe@example.com" aria-label="john.doe@example.com" aria-describedby="basic-icon-default-email2" name="user-email" />
                            <small class="form-text text-muted"> You can use letters, numbers & periods </small>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="user-role">User Role</label>
                            <select id="user-role" class="form-control">
                                <option value="subscriber">Subscriber</option>
                                <option value="editor">Editor</option>
                                <option value="maintainer">Maintainer</option>
                                <option value="author">Author</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <div class="form-group mb-2">
                            <label class="form-label" for="user-plan">Select Plan</label>
                            <select id="user-plan" class="form-control">
                                <option value="basic">Basic</option>
                                <option value="enterprise">Enterprise</option>
                                <option value="company">Company</option>
                                <option value="team">Team</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary mr-1 data-submit">Submit</button>
                        <button type="reset" class="btn btn-outline-secondary" data-dismiss="modal">Cancel</button>
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