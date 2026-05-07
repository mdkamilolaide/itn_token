const EventBus = new Vue();

window.eventBusMixin = {
  mounted() {
    // Handle reset form event
    if (typeof this.resetPageHandler === "function") {
      this.boundResetPageHandler = this.resetPageHandler.bind(this);
      EventBus.$on("g-event-reset-form", this.boundResetPageHandler);
    }

    // Handle refresh data event
    if (typeof this.refreshDataHandler === "function") {
      this.boundRefreshDataHandler = this.refreshDataHandler.bind(this);
      EventBus.$on("g-event-refresh-page", this.boundRefreshDataHandler);
    }

    // Handle refresh data event
    if (typeof this.goToPageDataHandler === "function") {
      this.boundGotoPageDataHandler = this.goToPageDataHandler.bind(this);
      EventBus.$on("g-event-goto-page", this.boundGotoPageDataHandler);
    }
  },

  beforeDestroy() {
    if (this.boundResetPageHandler) {
      EventBus.$off("g-event-reset-form", this.boundResetPageHandler);
    }
    if (this.boundRefreshDataHandler) {
      EventBus.$off("g-event-refresh-page", this.boundRefreshDataHandler);
    }
    if (this.boundGotoPageDataHandler) {
      EventBus.$off("g-event-goto-page", this.boundGotoPageDataHandler);
    }
  },
};

const createPageState = () => ({
  // page: "create-inbound",
  page: "table",
  title: "",
});

// Centralized reactive state
const appState = Vue.observable({
  pageState: createPageState(),
  permission: getPermission(per, "smc"),
  userId: (currentUserId = document.getElementById("v_g_id").value),
  geoLevelForm: {
    geoLevel: "",
    geoLevelId: 0,
  },
  defaultStateId: "",
  sysDefaultData: [],
  productData: [],
  lgaData: [],
  cmsLocationMaster: [],
  inBoundData: [],

  periodData: [],
  currentPeriodId: "",
  currentLgaId: "",
  selectedLgaKey: "",
  facilityTitles: "",
  level: "lga",
});

Vue.mixin({
  methods: {
    displayDate(d, fullDate = false, withTime = true) {
      const date = new Date(d);
      const options = {
        year: "numeric",
        month: fullDate ? "long" : "short",
        day: "numeric",
        ...(withTime && {
          hour: "2-digit",
          minute: "2-digit",
          hour12: true,
        }),
      };
      return date.toLocaleString("en-US", options);
    },
    capitalize(word) {
      if (word) {
        const loweredCase = word.toLowerCase();
        return word[0].toUpperCase() + loweredCase.slice(1);
      } else {
        return word;
      }
    },
    capitalizeEachWords(text) {
      if (!text) return text;
      return text
        .toLowerCase()
        .split(" ")
        .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
        .join(" ");
    },
    formatNumber(num) {
      let data = num ? parseInt(num) : 0;
      return data ? data.toLocaleString() : 0;
    },
    numbersOnlyWithoutDot(event) {
      const allowedKeys = [
        "Backspace",
        "Delete",
        "ArrowLeft",
        "ArrowRight",
        "Tab",
        "Escape",
        "Home",
        "End",
      ];

      // Allow control keys
      if (allowedKeys.includes(event.key)) return;

      // Block if not a digit (0–9)
      if (!/^\d$/.test(event.key)) {
        event.preventDefault();
      }
    },
    validatePaste(event) {
      const pasteData = (event.clipboardData || window.clipboardData).getData(
        "text"
      );
      if (!/^\d+$/.test(pasteData)) {
        event.preventDefault();
      }
    },
    convertStringNumberToFigures(d, forceFloat = false) {
      const num = d ? Number(d) : 0;

      if (isNaN(num)) return "0";

      return num.toLocaleString("en-US", {
        minimumFractionDigits: forceFloat || !Number.isInteger(num) ? 2 : 0,
        maximumFractionDigits: forceFloat || !Number.isInteger(num) ? 2 : 0,
      });
    },
    setSelectedLga() {
      const key = appState.selectedLgaKey;
      // if (!key) return;

      const selectedLga = appState.lgaData[key];
      if (!selectedLga) return;

      appState.facilityTitles = selectedLga.lga;
      appState.currentLgaId = selectedLga.lgaid;
      EventBus.$emit("g-event-reset-form");
    },
  },
});

Vue.component("page-table", {
  mixins: [eventBusMixin],
  data: function () {
    return {
      appState,
      tableData: [],
      roleListData: [],
      geoData: [],
      checkToggle: false,
      filterState: false,
      filters: false,
      tableOptions: {
        total: 1, //Total record
        pageLength: 1, //Total
        perPage: 10,
        currentPage: 1,
        orderDir: "desc", // (asc|desc)
        orderField: 15, //(Order fields)
        limitStart: 0, //(currentPage - 1) * perPage
        isNext: false,
        isPrev: false,
        aLength: [10, 20, 50, 100, 150, 200],
        filterParam: {
          gid: appState.currentLgaId,
          glv: appState.level,
          lga_name: appState.facilityTitles,
        },
      },
      geoLevelData: [],
    };
  },
  mounted() {
    /*  Manages events Listening    */
    this.getProductMaster();
    // this.getGeoLocation();
    this.loadTableData();
    EventBus.$on("g-event-refresh-page", this.reloadTableListOnUpdate);
  },
  methods: {
    reloadTableListOnUpdate(data = {}) {
      this.paginationDefault();
      this.loadTableData();
    },
    loadTableData() {
      /*  Manages the loading of table data */
      var self = this;
      var url = common.TableService;
      overlay.show();

      axios
        .get(
          url +
            "?qid=802&draw=" +
            self.tableOptions.currentPage +
            "&order_column=" +
            self.tableOptions.orderField +
            "&length=" +
            self.tableOptions.perPage +
            "&start=" +
            self.tableOptions.limitStart +
            "&order_dir=" +
            self.tableOptions.orderDir +
            "&gid=" +
            appState.currentLgaId +
            "&glv=lga"
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
      this.resetSelected();
      /*  Manages the selections of checked or selected data object */
      this.tableOptions.currentPage += 1;
      this.paginationDefault();
      this.loadTableData();
    },
    prevPage() {
      this.resetSelected();
      /*  Manages the selections of checked or selected data object */
      this.tableOptions.currentPage -= 1;
      this.paginationDefault();
      this.loadTableData();
    },
    resetSelected() {
      this.uncheckAll();
      this.checkToggle = false;
      this.totalCheckedBox();
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
        this.tableOptions.total / this.tableOptions.perPage
      );

      // Page Limit
      this.tableOptions.limitStart = Math.ceil(
        (this.tableOptions.currentPage - 1) * this.tableOptions.perPage
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
      this.resetSelected();
      this.tableOptions.currentPage = 1;
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
      if (appState.currentLgaId != "") {
        this.tableOptions.filterParam.gid = appState.currentLgaId;
        this.tableOptions.filterParam.glv = appState.level;
        this.tableOptions.filterParam.lga_name = appState.facilityTitles;
      }
      let checkFill = 0;
      checkFill += this.tableOptions.filterParam.gid != "" ? 1 : 0;
      checkFill += this.tableOptions.filterParam.level != "" ? 1 : 0;

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
      console.log(column_name);
      // this.tableOptions.filterParam + '.' + column_name == "";
      this.tableOptions.filterParam[column_name] = "";
      if (column_name == "lga_name") {
        this.tableOptions.filterParam.gid =
          this.tableOptions.filterParam.glv =
          this.tableOptions.filterParam.lga_name =
          appState.selectedLgaKey =
            "";
      }
      if (column_name == "role") {
        this.tableOptions.filterParam.role_id =
          this.tableOptions.filterParam.role = "";
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
      // $(".select2").val("").trigger("change");
      this.tableOptions.filterParam.gid =
        this.tableOptions.filterParam.level =
        this.tableOptions.filterParam.lga_name =
        appState.selectedLgaKey =
          "";

      this.paginationDefault();
      this.loadTableData();
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
    refreshData() {
      this.paginationDefault();
      this.loadTableData();
    },
    totalCheckedBox() {
      let total = this.selectedID().length;
      if (this.selectedID().length > 0) {
        document.getElementById(
          "total-selected"
        ).innerHTML = `<span id="selected-counter" class="badge badge-primary btn-icon"><span class="badge badge-success">${total}</span> Selected</span>`;
      } else {
        document.getElementById("total-selected").replaceChildren();
      }
    },
    checkIfAndReturnEmpty(data) {
      if (data === null || data === "") {
        return "";
      } else {
        return data;
      }
    },
    goToCreateIssue() {
      appState.pageState.page = "create-inbound";
      appState.currentPeriodId = "";
      appState.currentLgaId = "";
      appState.facilityTitles = "";
      appState.selectedLgaKey = "";
      EventBus.$emit("g-event-goto-page");
    },
    getProductMaster() {
      var url = common.DataService;
      overlay.show();

      axios
        .get(url + "?qid=gen011")
        .then(function (response) {
          let data = response.data.data.sort((a, b) =>
            a.product_code.localeCompare(b.product_code)
          );

          appState.productData = data; //All Data
          overlay.hide();
        })
        .catch(function (error) {
          overlay.hide();
          alert.Error("ERROR", error);
        });
    },
  },
  computed: {},
  template: `

        <div class="row">
            <div class="col-md-8 col-sm-12 col-12 mb-0">
                <h2 class="content-header-title header-txt float-left mb-0">SMC</h2>
                <div class="breadcrumb-wrapper">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../smc/logistics">Logistics</a></li>
                        <li class="breadcrumb-item active">Inbound </li>
                    </ol>
                    <span id="total-selected"></span>
                </div>
                
            </div>

            <div class="col-md-4 col-sm-12 col-12 text-md-right text-right d-md-block">
                <div class="btn-group">
                    <button type="button" class="btn btn-outline-primary round searchBtn" @click="refreshData()" data-toggle="tooltip" data-placement="top" title="Refresh">
                        <i class="feather icon-refresh-cw"></i>         
                    </button>       
                    <button type="button" class="btn btn-outline-primary round searchBtn" @click="toggleFilter()" data-toggle="tooltip" data-placement="top" title="Filter">
                        <i class="feather" :class="filterState ? 'icon-x' : 'icon-filter'"></i>               
                    </button>
                    <button v-if="appState.permission.permission_value >=2" type="button"
                        class="btn btn-outline-primary round" data-toggle="tooltip" data-placement="top" title="Create Issue" @click="goToCreateIssue()">
                        <i class="feather icon-plus"></i>             
                    </button>
                </div>
            </div>
            <div class="col-12" v-if="filters">
                <div class="col-12 filter-bar">
                    <span class="badge badge-dark filter-box" @click="removeSingleFilter(i)" v-for="(filterParam, i) in tableOptions.filterParam" v-if="filterParam.length > 0 && i != 'glv' && i != 'geo_level_id'&& i != 'role_id'">{{capitalize(i)}}: {{filterParam}} <i class="feather icon-x"></i></span>
                    <a href="#" class="float-right clear-filter" @click="clearAllFilter()">Clear</a>
                </div>
            </div>

            <div class="col-12 mt-1">
                <div class="card  custom-select-down">
                    <div class="card-body py-1" v-show="filterState">
                        <form id="filterForm">
                            <div class="row">
                                    <div class="col-12 col-md-5 col-sm-12 col-lg-5">
                                        <div class="form-group">
                                            <label>Choose LGA to Populate Issue</label>
                                            <select class="form-control" v-model="appState.selectedLgaKey" @change="setSelectedLga($event)" placeholder="Choose LGA">
                                                <option value="">Choose LGA</option>
                                                <option v-for="(g, i) in appState.lgaData" :value="i">{{ g.lga }}</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-12 col-md-5 col-sm-12 col-lg-5">
                                        <div class="form-group">
                                            <label>Visit</label>
                                            <select v-model="appState.currentPeriodId" class="form-control period" id="period">
                                              <option value="">Choose Visit</option>
                                              <option v-for="(g, i) in appState.periodData" :value="g.periodid">{{ g.title }}</option>
                                            </select>
                                        </div>
                                    </div>
                                <div class="col-12 col-sm-12 col-md-2 col-lg-2">
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
                                    <th width="60px">
                                        <div class="custom-control custom-checkbox checkbox">
                                            <input type="checkbox" class="custom-control-input" :checked="checkToggle" @change="selectToggle(), totalCheckedBox()" id="all-check" />
                                            <label class="custom-control-label" for="all-check"></label>
                                        </div>
                                    </th>
                                    -->
                                    <th class="px-1">#</th>
                                    <th class="pl-1" @click="sort(4)" >
                                        CMS Details
                                        <i class="feather icon-chevron-up sort-up" :class="(tableOptions.orderField == 4 && tableOptions.orderDir =='asc')? 'active-sort': ''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 4 && tableOptions.orderDir =='desc')? 'active-sort': ''"></i>
                                    </th>
                                  <th @click="sort(2)" class="pl-1">
                                      Product
                                      <i class="feather icon-chevron-up sort-up" :class="(tableOptions.orderField == 2 && tableOptions.orderDir =='asc')? 'active-sort': ''"></i>
                                      <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 2 && tableOptions.orderDir =='desc')? 'active-sort': ''"></i>
                                  </th>
                                  <th @click="sort(9)" class="pl-1">
                                      Previous Qty
                                      <i class="feather icon-chevron-up sort-up" :class="(tableOptions.orderField == 9 && tableOptions.orderDir =='asc')? 'active-sort': ''"></i>
                                      <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 9 && tableOptions.orderDir =='desc')? 'active-sort': ''"></i>
                                  </th>
                                  <th @click="sort(10)" class="pl-1">
                                        Current Qty
                                      <i class="feather icon-chevron-up sort-up" :class="(tableOptions.orderField == 10 && tableOptions.orderDir =='asc')? 'active-sort': ''"></i>
                                      <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 10 && tableOptions.orderDir =='desc')? 'active-sort': ''"></i>
                                  </th>
                                  <th @click="sort(11)" class="pl-1">
                                        Total Qty
                                      <i class="feather icon-chevron-up sort-up" :class="(tableOptions.orderField == 11 && tableOptions.orderDir =='asc')? 'active-sort': ''"></i>
                                      <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 11 && tableOptions.orderDir =='desc')? 'active-sort': ''"></i>
                                  </th>
                                  <th @click="sort(5)" class="pl-1">
                                        Batch
                                      <i class="feather icon-chevron-up sort-up" :class="(tableOptions.orderField == 5 && tableOptions.orderDir =='asc')? 'active-sort': ''"></i>
                                      <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 5 && tableOptions.orderDir =='desc')? 'active-sort': ''"></i>
                                  </th>
                                  <th @click="sort(6)" class="pl-1">
                                        Expiry
                                      <i class="feather icon-chevron-up sort-up" :class="(tableOptions.orderField == 6 && tableOptions.orderDir =='asc')? 'active-sort': ''"></i>
                                      <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 6 && tableOptions.orderDir =='desc')? 'active-sort': ''"></i>
                                  </th>
                                  <th @click="sort(7)" class="pl-1">
                                        Rate (&#8358;)
                                      <i class="feather icon-chevron-up sort-up" :class="(tableOptions.orderField == 7 && tableOptions.orderDir =='asc')? 'active-sort': ''"></i>
                                      <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 7 && tableOptions.orderDir =='desc')? 'active-sort': ''"></i>
                                  </th>
                                  <th @click="sort(8)" class="pl-1">
                                        Unit
                                      <i class="feather icon-chevron-up sort-up" :class="(tableOptions.orderField == 8 && tableOptions.orderDir =='asc')? 'active-sort': ''"></i>
                                      <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 8 && tableOptions.orderDir =='desc')? 'active-sort': ''"></i>
                                  </th>
                                  <th @click="sort(15)" class="pl-1">
                                        Created
                                      <i class="feather icon-chevron-up sort-up" :class="(tableOptions.orderField == 15 && tableOptions.orderDir =='asc')? 'active-sort': ''"></i>
                                      <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 15 && tableOptions.orderDir =='desc')? 'active-sort': ''"></i>
                                  </th>
                                    <!--
                                    <th class="text-center">Actions</th>
                                    -->
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(item, rowIndex) in tableData">
                                    <!--
                                    <tr v-for="(g, i) in tableData" :class="checkedBg(g.pick)">
                                    <td>
                                        <div class="custom-control custom-checkbox checkbox">
                                            <input type="checkbox" class="custom-control-input" :id="g.loginid" v-model="g.pick" @change="totalCheckedBox()" />
                                            <label class="custom-control-label" :for="g.loginid"></label>
                                        </div>
                                    </td>
                                    -->
                                    <td class="px-1">{{rowIndex+1}}</td>
                                    <td class="px-1">                                     
                                        <div class="d-flex justify-content-left align-items-center">
                                            <div class="d-flex flex-column">
                                                <span class="user_name text-wrap text-body">
                                                    <span class="fw-bolder">{{ item.cms_name }}</span>
                                                </span>
                                                <span class="font-small-2 text-muted">{{ item.location_type }}</span>
                                            </div>
                                        </div>
                                     </td>
                                    
                                    <td class="px-1">
                                        <div class="d-flex justify-content-left align-items-center">
                                            <div class="d-flex flex-column">
                                                <span class="user_name text-wrap text-body">
                                                    <span class="fw-bolder">{{ item.product_name }}</span>
                                                </span>
                                                <small class="emp_post font-small-2 text-muted">{{ item.product_code }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    
                                    <td class="px-1">
                                        <div class="d-flex justify-content-left align-items-center">
                                            <div class="d-flex flex-column">
                                                <small class="emp_post text-left">
                                                    <span class="text-muted w-text">Primary:</span>
                                                    <span class="fw-bolder">{{ convertStringNumberToFigures(item.previous_primary_qty) }}</span>
                                                </small>
                                                <small class="emp_post text-left">
                                                    <span class="text-muted w-text">Secondary:</span>
                                                    <span class="fw-bolder">{{ convertStringNumberToFigures(item.previous_secondary_qty) }}</span>
                                                </small>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-1">
                                        <div class="d-flex justify-content-left align-items-center">
                                            <div class="d-flex flex-column">
                                                <small class="emp_post text-left">
                                                    <span class="text-muted w-text">Primary:</span>
                                                    <span class="fw-bolder">{{ convertStringNumberToFigures(item.current_primary_qty) }}</span>
                                                </small>
                                                <small class="emp_post text-left">
                                                    <span class="text-muted w-text">Secondary:</span>
                                                    <span class="fw-bolder">{{ convertStringNumberToFigures(item.current_secondary_qty) }}</span>
                                                </small>
                                            </div>
                                        </div>
                                     </td>
                                    <td class="px-1">
                                        <div class="d-flex justify-content-left align-items-center">
                                            <div class="d-flex flex-column">
                                                <small class="emp_post text-left">
                                                    <span class="text-muted w-text">Primary:</span>
                                                    <span class="fw-bolder">{{ convertStringNumberToFigures(item.total_primary_qty) }}</span>
                                                </small>
                                                <small class="emp_post text-left">
                                                    <span class="text-muted w-text">Secondary:</span>
                                                    <span class="fw-bolder">{{ convertStringNumberToFigures(item.total_secondary_qty) }}</span>
                                                </small>
                                            </div>
                                        </div>
                                     </td>
                                    <td class="px-1">{{ item.batch }}</td>
                                    <td class="px-1">{{ displayDate(item.expiry, false, false) }}</td>
                                    <td class="px-1">{{ convertStringNumberToFigures(item.rate) }}</td>
                                    <td class="px-1">{{ item.unit }}</td>
                                    <td class="px-1">{{ displayDate(item.created, false, true) }}</td>
                                    <!--
                                    <td class="px-1 text-center">
                                      <span class="text-primary font-medium-2 font-weight-bold mr-2"><i class="feather icon-edit"></i></span>
                                    </td>
                                    -->
                                </tr>
                                <tr v-if="tableData.length == 0"><td class="text-center pt-2" colspan="7"><small>No User Added</small></td></tr>
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

Vue.component("page-create-issue", {
  mixins: [eventBusMixin],
  data: function () {
    return {
      appState,
      tableData: [],
      checkToggle: false,
      filterState: false,
      filters: false,
      tableOptions: {
        total: 1, //Total record
        pageLength: 1, //Total
        perPage: 10,
        currentPage: 1,
        orderDir: "asc", // (asc|desc)
        orderField: 0, //(Order fields)
        limitStart: 0, //(currentPage - 1) * perPage
        isNext: false,
        isPrev: false,
        aLength: [10, 20, 50, 100, 150, 200],
        filterParam: {
          user_status: "",
          loginid: "",
          fullname: "",
          user_group: "",
          phoneno: "",
          geo_level: "",
          geo_level_id: "",
          geo_string: "",
          bank_status: "",
          role_id: "",
          role: "",
        },
      },
      facilityData: [],
      tempFacilityData: [],
      isUpdated: false,
    };
  },
  mounted() {
    /*  Manages events Listening    */
    this.getSysDefaultDataSettings();
    this.getAllPeriodLists();
    this.getCmsLocationMaster();

    EventBus.$on("g-event-reset-form", this.resetForm);
    EventBus.$on("g-event-goto-page", this.addInbound);
  },
  methods: {
    validateInboundForm() {
      const $form = $("#inboundForm");

      // Destroy previous validator if it exists
      if ($form.data("validator")) {
        $form.validate().destroy();
      }

      // Build rules dynamically
      const rules = {};
      appState.inBoundData.forEach((item, index) => {
        rules[`product_code[${index}]`] = { required: true };
        rules[`expiry_date[${index}]`] = { required: true, date: true };
        rules[`rate[${index}]`] = { required: true, number: true };
        rules[`secondary_qty[${index}]`] = { required: true, number: true };
        rules[`unit[${index}]`] = { required: true };
        rules[`batch[${index}]`] = { required: true };
        rules[`location_id[${index}]`] = { required: true, number: true };
        // Add other fields as needed
      });

      $form.validate({
        ignore: [], // already good
        rules,
        errorPlacement(error, element) {
          const $el = $(element);
          if (
            $el.hasClass("flatpickr-input") &&
            $el.attr("type") === "hidden"
          ) {
            error.insertAfter($el.next("input[type='text']"));
          } else {
            error.insertAfter($el);
          }
        },
        highlight: function (element) {
          const $el = $(element);

          if (
            $el.hasClass("flatpickr-input") &&
            $el.attr("type") === "hidden"
          ) {
            // Find all classes like expiry_date_0 on hidden input
            const classes = element.className.split(/\s+/);
            // Filter to find the expiry_date_x class pattern
            const targetClass = classes.find((cls) =>
              /^expiry_date_\d+$/.test(cls)
            );

            if (targetClass) {
              // Select the visible input with the same expiry_date_x class but type="text"
              const $visibleInput = $("input." + targetClass + "[type='text']");
              if ($visibleInput.length) {
                $visibleInput.addClass("is-invalid");
                return;
              }
            }
            // fallback: just add to hidden input
            $el.addClass("is-invalid");
          } else {
            $el.addClass("is-invalid");
          }
        },

        unhighlight: function (element) {
          const $el = $(element);

          if (
            $el.hasClass("flatpickr-input") &&
            $el.attr("type") === "hidden"
          ) {
            const classes = element.className.split(/\s+/);
            const targetClass = classes.find((cls) =>
              /^expiry_date_\d+$/.test(cls)
            );

            if (targetClass) {
              const $visibleInput = $("input." + targetClass + "[type='text']");
              if ($visibleInput.length) {
                $visibleInput.removeClass("is-invalid");
                return;
              }
            }
            $el.removeClass("is-invalid");
          } else {
            $el.removeClass("is-invalid");
          }
        },
      });
    },
    goToInboundTable() {
      appState.pageState.page = "table";
      appState.inBoundData = [];
    },
    getSysDefaultDataSettings() {
      /*  Manages the loading of System default settings */
      var self = this;
      var url = common.DataService;
      overlay.show();

      axios
        .get(url + "?qid=gen007")
        .then(function (response) {
          if (response.data.data.length > 0) {
            appState.sysDefaultData = response.data.data[0]; //All Data
            self.getAllLga(response.data.data[0].stateid);
            //  Set preventDefault();
            appState.geoLevelForm.geoLevel = "state";
            appState.geoLevelForm.geoLevelId = response.data.data[0].stateid;
            appState.defaultStateId = response.data.data[0].stateid;
          }

          overlay.hide();
        })
        .catch(function (error) {
          overlay.hide();
          alert.Error("ERROR", error);
        });
    },
    getAllLga(stateid) {
      /*  Manages the loading of Geo Level data */
      var self = this;
      var url = common.DataService;
      overlay.show();

      axios
        .post(url + "?qid=gen003", JSON.stringify(stateid))
        .then(function (response) {
          appState.lgaData = response.data.data; //All Data
          overlay.hide();
        })
        .catch(function (error) {
          overlay.hide();
          alert.Error("ERROR", error);
        });
    },
    async getCmsLocationMaster() {
      overlay.show();

      try {
        const response = await axios.post(`${common.DataService}?qid=gen012`);

        appState.cmsLocationMaster = response.data?.data;
      } catch (error) {
        alert.Error("ERROR", error?.message || "An error occurred");
      } finally {
        overlay.hide();
      }
    },
    submitInboundCreation() {
      const $form = $(".inboundForm");
      if (!$form.valid()) {
        return alert.Error("Please fill all required fields.");
      }

      let self = this;
      let url = common.DataService;
      overlay.show();
      axios
        .post(url + "?qid=1130", JSON.stringify(appState.inBoundData))
        .then(function (response) {
          overlay.hide();
          if (response.data.result_code == "200") {
            EventBus.$emit("g-event-refresh-page");
            alert.Success("SUCCESS", response.data.message);
            self.goToInboundTable();
          } else {
            alert.Error("ERROR", response.data.message);
          }
        })
        .catch(function (error) {
          overlay.hide();
          alert.Error("ERROR", error);
        });
    },
    resetForm() {
      this.facilityData = [];
      this.tempFacilityData = [];
    },
    getAllPeriodLists() {
      /*  Manages the loading of Geo Level data */
      var self = this;
      var url = common.DataService;
      overlay.show();

      axios
        .get(url + "?qid=1004")
        .then(function (response) {
          appState.periodData = response.data.data; //All Data
          overlay.hide();
        })
        .catch(function (error) {
          overlay.hide();
          alert.Error("ERROR", error);
        });
    },
    resetInboundCreation() {
      this.facilityData = JSON.parse(JSON.stringify(this.tempFacilityData));
    },
    cancelInboundCreation() {
      if (this.isUpdated) {
        $.confirm({
          title: "WARNING!",
          content: "Are you sure you want to discard the changes made?",
          buttons: {
            discard: {
              text: "Discard",
              btnClass: "btn btn-danger mr-1",
              action: () => this.goToInboundTable(),
            },
            cancel: () => {}, // no-op
          },
        });
      } else {
        this.goToInboundTable();
      }
    },
    addInbound() {
      const $form = $(".inboundForm");
      if (!$form.valid()) {
        return alert.Error("Please fill all required fields.");
      }
      appState.inBoundData.push({
        product_code: "",
        product_name: "",
        location_type: "",
        location_id: "",
        batch: "",
        expiry_date: "",
        rate: "",
        unit: "",
        primary_qty: "",
        secondary_qty: "",
        userid: appState.userId,
      });

      this.$nextTick(() => {
        const index = appState.inBoundData.length - 1;
        flatpickr(this.$refs["expiry_date" + index], {
          altInput: true,
          altFormat: "F j, Y",
          dateFormat: "Y-m-d",
          onChange: (selectedDates, dateStr) => {
            appState.inBoundData[index].expiry_date = dateStr;
          },
        });
        //Run the validation function
        this.validateInboundForm();
      });
    },
    deleteItem(index) {
      appState.inBoundData.splice(index, 1);
    },
  },
  computed: {},
  watch: {
    "appState.inBoundData": {
      handler(newVal) {
        const packSize = 50;

        newVal.forEach((item) => {
          const secondaryQty = Number(item.secondary_qty);
          item.primary_qty = isNaN(secondaryQty) ? 0 : secondaryQty * packSize;

          if (item.location_id && !item.location_type) {
            const cms = appState.cmsLocationMaster.find(
              (loc) => loc.location_id === item.location_id
            );
            item.location_type = cms?.location_type || "";
          }

          if (item.product_code && !item.product_name) {
            const product = appState.productData.find(
              (prod) => prod.product_code === item.product_code
            );
            item.product_name = product?.name || "";
          }
        });
      },
      deep: true,
    },
  },
  template: `

        <div class="row">
            <div class="col-md-8 col-sm-12 col-12 mb-0">
                <h2 class="content-header-title header-txt float-left mb-0">SMC</h2>
                <div class="breadcrumb-wrapper">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../smc/logistics">Logistics</a></li>
                        <li class="breadcrumb-item"><a href="javascript:void(0)" @click="cancelInboundCreation()">Inbound</a></li>
                        <li class="breadcrumb-item active">Create Inbound</li>
                    </ol>
                </div>
                
            </div>
 

            <div class="col-12 mt-1">

                <div class="card">
                    <div class="card-header">
                        <button class="btn pl-0 pr-50 py-50 waves-effect"  @click="cancelInboundCreation()">
                            <i class="feather icon-chevron-left"></i> Back
                        </button>
                        <!--
                        <h5 class="card-title text-primary">Create Issue</h5>
                        -->
                    </div>

                    <form class="card-body inboundForm" id="inboundForm" @submit.prevent="submitInboundCreation()">
 
                        <div v-for="(item, index) in appState.inBoundData" :key="index" class="card border mb-3 shadow shadow-sm border-lighten-2">
                            <div class="card-body inbound-item">
                                <button type="reset" v-if="index!=0" class="close-btn shadow active waves-effect waves-float waves-light" @click="deleteItem(index)">
                                   <span>x</span>
                                </button>
                                <div class="row">
                                    <div class="col-3 col-md-3 col-sm-3 col-lg-3">
                                        <div class="form-group">
                                            <label>*Choose Product</label>
                                            <select class="form-control" placeholder="Choose Product" v-model="item.product_code" :name="'product_code[' + index + ']'" :class="'product_code[' + index + ']'">
                                                <option value="">Choose Product</option>
                                                <option v-for="(g, i) in appState.productData" :value="g.product_code">{{ g.name }}</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-3 col-md-3 col-sm-3 col-lg-3">
                                        <div class="form-group">
                                            <label>*Choose CMS Location</label>
                                            <select class="form-control" v-model="item.location_id" :class="'location_id[' + index + ']'" :name="'location_id[' + index + ']'" v-for="g in appState.cmsLocationMaster" placeholder="Choose CMS Location">
                                                <option value="">Select CMS Location</option>
                                                <option v-for="(g, i) in appState.cmsLocationMaster" :value="g.location_id">{{ g.cms_name }}</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-3 col-md-3 col-sm-3 col-lg-3">
                                        <div class="form-group">
                                            <label>*Batch No</label>
                                            <input type="text" v-model="item.batch" :class="'batch[' + index + ']'" :name="'batch[' + index + ']'" placeholder="Batch No" class="form-control" />
                                        </div>
                                    </div>

                                    <div class="col-3 col-md-3 col-sm-3 col-lg-3">
                                        <div class="form-group">
                                            <label>*Expiring Date</label>
                                            <input type="text" v-model="item.expiry_date" :ref="'expiry_date' + index" placeholder="Expiring Date" :class="'form-control date expiry_date_' + index" :name="'expiry_date[' + index + ']'"  />
                                        </div>
                                    </div>

                                    <div class="col-3 col-md-3 col-sm-3 col-lg-3">
                                        <div class="form-group">
                                            <label>*Rate (&#8358;)</label>
                                            <input type="text"
                                            @paste="validatePaste($event)"
                                             v-model="item.rate" :class="'rate[' + index + ']'" :name="'rate[' + index + ']'" placeholder="Rate" class="form-control" />
                                        </div>
                                    </div>
                                    
                                    <div class="col-3 col-md-3 col-sm-3 col-lg-3">
                                        <div class="form-group">
                                            <label>*Unit (1X50)</label>
                                            <input type="text" placeholder="Unit" :name="'unit[' + index + ']'"  :class="'unit[' + index + ']'" v-model="item.unit" class="form-control" />
                                        </div>
                                    </div>

                                    <div class="col-3 col-md-3 col-sm-3 col-lg-3">
                                        <div class="form-group">
                                            <label>*Quantity</label>
                                            <input type="text"
                                            @paste="validatePaste($event)"
                                             @keypress="numbersOnlyWithoutDot" :class="'secondary_qty[' + index + ']'" :name="'secondary_qty[' + index + ']'" placeholder="Quantity" v-model="item.secondary_qty" class="form-control" />
                                        </div>
                                    </div>


                                </div>
                            </div>
                        </div>

                        <div class="col-12 px-0 justify-content-end text-right">
                          <button @click="addInbound()" type="button" class="btn btn-outline-primary btn-md btn-add-new waves-effect waves-float waves-light">
                              <i class="feather icon-plus"></i>
                            <span class="align-middle">Add Inbound</span>
                          </button>
                          <button v-if="appState.inBoundData.length >0" type="submit" class="btn btn-primary ml-1 btn-md btn-add-new waves-effect waves-float waves-light">
                              <i class="feather icon-plus"></i>
                            <span class="align-middle">Submit Inbound</span>
                          </button>
                        </div>
                    
                        
                    </form>

                    <div class="mb-50"></div>
                </div>
            </div>



        </div>
    `,
});

var vm = new Vue({
  mixins: [eventBusMixin],
  el: "#app",
  data: function () {
    return {
      appState,
    };
  },

  methods: {},
  template: `
        <div>
            <div v-show="appState.pageState.page == 'table'">
                <page-table/>
            </div>
            <div v-show="appState.pageState.page == 'create-inbound'">
                <page-create-issue />
            </div>
        </div>
      `,
});
