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
            "filterState": false,
            "filters": false,
            "userGroup": [],
            "tableOptions": {
                "total": 1, //Total record 
                "pageLength": 1, //Total 
                "perPage": 10,
                "currentPage": 1,
                "orderDir": "desc", // (asc|desc)
                "orderField": 0, //(Order fields)
                "limitStart": 0, //(currentPage - 1) * perPage
                "isNext": false,
                "isPrev": false,
                "aLength": [10, 20, 50, 100],
                "filterParam": {
                    "userid": "",
                    "loginid": "",
                    "platform": "",
                    "module": "",
                    "result": ""



                }
            }

        }
    },
    mounted() {
        /*  Manages events Listening    */
        this.loadTableData();
    },
    methods: {
        autocomplete(inp, arr) {
            /*the autocomplete function takes two arguments,
            the text field element and an array of possible autocompleted values:*/
            var currentFocus;
            /*execute a function when someone writes in the text field:*/
            inp.addEventListener("input", function(e) {
                var a, b, i, val = this.value;
                /*close any already open lists of autocompleted values*/
                closeAllLists();
                if (!val) { return false; }
                currentFocus = -1;
                /*create a DIV element that will contain the items (values):*/
                a = document.createElement("DIV");
                a.setAttribute("id", this.id + "autocomplete-list");
                a.setAttribute("class", "autocomplete-items");
                /*append the DIV element as a child of the autocomplete container:*/
                this.parentNode.appendChild(a);
                /*for each item in the array...*/
                for (i = 0; i < arr.length; i++) {
                    /*check if the item starts with the same letters as the text field value:*/
                    if (arr[i].substr(0, val.length).toUpperCase() == val.toUpperCase()) {
                        /*create a DIV element for each matching element:*/
                        b = document.createElement("DIV");
                        /*make the matching letters bold:*/
                        b.innerHTML = "<strong>" + arr[i].substr(0, val.length) + "</strong>";
                        b.innerHTML += arr[i].substr(val.length);
                        /*insert a input field that will hold the current array item's value:*/
                        b.innerHTML += "<input type='hidden' value='" + arr[i] + "'>";
                        /*execute a function when someone clicks on the item value (DIV element):*/
                        b.addEventListener("click", function(e) {
                            /*insert the value for the autocomplete text field:*/
                            inp.value = this.getElementsByTagName("input")[0].value;
                            /*close the list of autocompleted values,
                            (or any other open lists of autocompleted values:*/
                            closeAllLists();
                        });
                        a.appendChild(b);
                    }
                }
            });
            /*execute a function presses a key on the keyboard:*/
            inp.addEventListener("keydown", function(e) {
                var x = document.getElementById(this.id + "autocomplete-list");
                if (x) x = x.getElementsByTagName("div");
                if (e.keyCode == 40) {
                    /*If the arrow DOWN key is pressed,
                    increase the currentFocus variable:*/
                    currentFocus++;
                    /*and and make the current item more visible:*/
                    addActive(x);
                } else if (e.keyCode == 38) { //up
                    /*If the arrow UP key is pressed,
                    decrease the currentFocus variable:*/
                    currentFocus--;
                    /*and and make the current item more visible:*/
                    addActive(x);
                } else if (e.keyCode == 13) {
                    /*If the ENTER key is pressed, prevent the form from being submitted,*/
                    e.preventDefault();
                    if (currentFocus > -1) {
                        /*and simulate a click on the "active" item:*/
                        if (x) x[currentFocus].click();
                    }
                }
            });

            function addActive(x) {
                /*a function to classify an item as "active":*/
                if (!x) return false;
                /*start by removing the "active" class on all items:*/
                removeActive(x);
                if (currentFocus >= x.length) currentFocus = 0;
                if (currentFocus < 0) currentFocus = (x.length - 1);
                /*add class "autocomplete-active":*/
                x[currentFocus].classList.add("autocomplete-active");
            }

            function removeActive(x) {
                /*a function to remove the "active" class from all autocomplete items:*/
                for (var i = 0; i < x.length; i++) {
                    x[i].classList.remove("autocomplete-active");
                }
            }

            function closeAllLists(elmnt) {
                /*close all autocomplete lists in the document,
                except the one passed as an argument:*/
                var x = document.getElementsByClassName("autocomplete-items");
                for (var i = 0; i < x.length; i++) {
                    if (elmnt != x[i] && elmnt != inp) {
                        x[i].parentNode.removeChild(x[i]);
                    }
                }
            }
            /*execute a function when someone clicks in the document:*/
            document.addEventListener("click", function(e) {
                closeAllLists(e.target);
            });
        },
        loadTableData() {
            /*  Manages the loading of table data */
            var self = this;
            var url = common.TableService;
            overlay.show();

            axios.get(url + "?qid=501&draw=" + self.tableOptions.currentPage + "&order_column=" + self.tableOptions.orderField + "&length=" + self.tableOptions.perPage + "&start=" + self.tableOptions.limitStart + "&order_dir=" + self.tableOptions.orderDir +
                    "&uid=" + self.tableOptions.filterParam.userid + "&lid=" + self.tableOptions.filterParam.loginid + "&pla=" + self.tableOptions.filterParam.platform +
                    "&mod=" + self.tableOptions.filterParam.module + "&res=" + self.tableOptions.filterParam.result)
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
            checkFill += (this.tableOptions.filterParam.loginid != "") ? 1 : 0;
            checkFill += (this.tableOptions.filterParam.userid != "") ? 1 : 0;
            checkFill += (this.tableOptions.filterParam.platform != "") ? 1 : 0;
            checkFill += (this.tableOptions.filterParam.module != "") ? 1 : 0;
            checkFill += (this.tableOptions.filterParam.result != "") ? 1 : 0;

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
            this.tableOptions.filterParam.loginid = "";
            this.tableOptions.filterParam.userid = "";
            this.tableOptions.filterParam.platform = "";
            this.tableOptions.filterParam.module = "";
            this.tableOptions.filterParam.result = "";

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
        refreshData() {
            this.paginationDefault();
            this.loadTableData();
        }

    },
    computed: {

    },
    template: `

        <div class="row" id="basic-table">

            <div class="col-md-8 col-sm-12 col-12 mb-0">
                <h2 class="content-header-title header-txt float-left mb-0">System Admin</h2>
                <div class="breadcrumb-wrapper">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../admin/log">Home</a></li>
                        <li class="breadcrumb-item active">Activity Log</li>
                    </ol>
                </div>
            </div>

            <div class="col-md-4 col-sm-12 col-12 text-md-right text-right d-md-block">
                <div class="btn-group mr-1">
                    <button type="button" class="btn btn-outline-primary round searchBtn" @click="refreshData()" data-toggle="tooltip" data-placement="top" title="Refresh">
                        <i class="feather icon-refresh-cw"></i>         
                    </button>  
                    <button type="button" class="btn btn-outline-primary round searchBtn" @click="toggleFilter()" data-toggle="tooltip" data-placement="top" title="Filter">
                        <i class="feather" :class="filterState ? 'icon-x' : 'icon-filter'"></i>               
                    </button>
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
                                <div class="col-12 col-sm-6 col-md-4 col-lg-4">
                                    <div class="form-group">
                                        <label>Activity Result</label>
                                        <select name="active" v-model="tableOptions.filterParam.result" class="form-control active">
                                            <option value="">All</option>
                                            <option value="success">Success</option>
                                            <option value="failed">Failed</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-12 col-sm-6 col-md-4 col-lg-4">
                                    <div class="form-group">
                                        <label>Login ID</label>
                                        <input type="text" v-model="tableOptions.filterParam.loginid" class="form-control login-id" id="login-id" placeholder="Login ID" name="loginid" />
                                    </div>
                                </div>
                                <div class="col-12 col-sm-6 col-md-4 col-lg-4">
                                    <div class="form-group">
                                        <label>Platform</label>
                                        <select name="active" v-model="tableOptions.filterParam.platform" class="form-control active">
                                            <option value="">All</option>
                                            <option value="web">Web</option>
                                            <option value="pos">POS</option>
                                            <option value="mobile">Mobile</option>
                                            <option value="pos|mobile">POS|Mobile</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-12 col-sm-6 col-md-4 col-lg-4">
                                    <div class="form-group autocomplete">
                                        <label>Module</label>
                                        <input autocomplete="off" type="text" @focus="loadAuto()" id="user_group" v-model="tableOptions.filterParam.module" class="form-control module" placeholder="Module" name="module" />
                                    </div>
                                </div>
                                <div class="col-12 col-sm-6 col-md-4 col-lg-4">
                                    <div class="form-group">
                                        <label>User ID</label>
                                        <input type="number" id="phoneno" v-model="tableOptions.filterParam.userid" class="form-control userid" placeholder="User ID" name="userid" />
                                    </div>
                                </div>
                                <div class="col-12 col-sm-6 col-md-4 col-lg-4">
                                    <div class="form-group mt-2 text-right">
                                        <button type="button" class="btn mt-25 btn-md btn-primary"  @click="applyFilter()">Apply Filters</button>
                                    </div>
                                </div>
                                
                            </div>
                        </form>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th @click="sort(0)" width="60px">
                                        #
                                        <i class="feather icon-chevron-up sort-up" :class="(tableOptions.orderField == 0 && tableOptions.orderDir =='asc')? 'active-sort': ''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 0 && tableOptions.orderDir =='desc')? 'active-sort': ''"></i>
                                    </th>
                                    <th @click="sort(9)">
                                        Date and Time
                                        <i class="feather icon-chevron-up sort-up" :class="(tableOptions.orderField == 9 && tableOptions.orderDir =='asc')? 'active-sort': ''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 9 && tableOptions.orderDir =='desc')? 'active-sort': ''"></i>
                                    </th>
                                    <th @click="sort(2)">
                                        Account
                                        <i class="feather icon-chevron-up sort-up" :class="(tableOptions.orderField == 2 && tableOptions.orderDir =='asc')? 'active-sort': ''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 2 && tableOptions.orderDir =='desc')? 'active-sort': ''"></i>
                                    </th>
                                    <th @click="sort(7)">
                                        Description
                                        <i class="feather icon-chevron-up sort-up" :class="(tableOptions.orderField == 7 && tableOptions.orderDir =='asc')? 'active-sort': ''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 7 && tableOptions.orderDir =='desc')? 'active-sort': ''"></i>
                                    </th>
                                    <th @click="sort(5)">
                                        Module
                                        <i class="feather icon-chevron-up sort-up" :class="(tableOptions.orderField == 5 && tableOptions.orderDir =='asc')? 'active-sort': ''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 5 && tableOptions.orderDir =='desc')? 'active-sort': ''"></i>
                                    </th>
                                    <th @click="sort(8)">
                                        Status
                                        <i class="feather icon-chevron-up sort-up" :class="(tableOptions.orderField == 8 && tableOptions.orderDir =='asc')? 'active-sort': ''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 8 && tableOptions.orderDir =='desc')? 'active-sort': ''"></i>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(g, i) in tableData">
                                    <td>{{g.id}}</td>
                                    <td>{{g.created}}</td>
                                    <td>
                                        <div class="d-flex justify-content-left align-items-center">
                                            <div class="d-flex flex-column">
                                                <span class="user_name text-truncate text-body">
                                                    <span class="fw-bolder" v-text="g.fullname? capitalize(g.fullname): 'Unknown User'"></span>
                                                </span>
                                                <small class="emp_post text-primary" v-html="g.loginid? g.loginid: ''"></small>
                                                <small class="emp_post text-info" v-html="g.platform? g.platform: ''"></small>
                                                <small class="emp_post text-muted" v-html="g.ip? g.ip: ''"></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{g.description}}</td>
                                    <td> {{ capitalize(g.module) }}</td>
                                    <td><span class="badge rounded-pill font-small-1" :class="g.result=='success'? 'bg-success' : 'bg-danger'">{{g.result=='success'? 'Success' : 'Failed'}}</span></td>
                                </tr>
                                <tr v-if="tableData.length == 0"><td class="text-center pt-2" colspan="6"><small>No Activity Log</small></td></tr>

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