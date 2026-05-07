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
  },

  beforeDestroy() {
    if (this.boundResetPageHandler) {
      EventBus.$off("g-event-reset-form", this.boundResetPageHandler);
    }
    if (this.boundRefreshDataHandler) {
      EventBus.$off("g-event-refresh-page", this.boundRefreshDataHandler);
    }
  },
};

const createPageState = () => ({
  page: "availability-check",
  // page: "table",
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
  checkData: [],

  periodData: [],
  currentPeriodId: "",
  currentProductCode: "",
});

Vue.mixin({
  methods: {
    displayDate(d) {
      let date = new Date(d);
      let options = {
        year: "numeric",
        month: "long",
        day: "numeric",
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
    percentageUsed(issue, used) {
      const percentUsed = (parseFloat(used) / parseFloat(issue)) * 100;
      if (isNaN(percentUsed)) {
        return 0;
      }
      // Check if it's a whole number
      return Number.isInteger(percentUsed)
        ? percentUsed
        : percentUsed.toFixed(2);
    },
    progressBarWidth(issue, used) {
      return this.percentageUsed(issue, used) + "%";
    },
    progressBarStatus(issue, used) {
      let progress = this.percentageUsed(issue, used);
      let state = "warning";
      if (progress <= 30) {
        state = "danger";
      } else if (progress <= 60) {
        state = "warning";
      } else if (progress < 90) {
        state = "primary";
      } else if (progress > 90) {
        state = "success";
      }

      return "bg-" + state;
    },
  },
});

Vue.component("page-availability-check", {
  mixins: [eventBusMixin],
  data: function () {
    return {
      appState,
      searchState: false,
    };
  },
  mounted() {
    /*  Manages events Listening    */
    // this.getSysDefaultDataSettings();
    this.getAllPeriodLists();
    this.getProductMaster();

    EventBus.$on("g-event-reset-form", this.resetForm);
  },
  methods: {
    goToIssueTable() {
      appState.pageState.page = "table";
      this.facilityData = [];
      this.tempFacilityData = [];
      Object.assign(appState, {
        currentPeriodId: "",
        currentLgaId: "",
        facilityTitles: "",
        selectedLgaKey: "",
      });
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
    submitIssues() {
      if (appState.currentPeriodId == "") {
        alert.Error("ERROR", "Please select a visit");
        return;
      }
      let data = this.prepareIssues(this.facilityData);

      let self = this;
      let url = common.DataService;
      overlay.show();
      axios
        .post(url + "?qid=1129", JSON.stringify(data))
        .then(function (response) {
          overlay.hide();
          if (response.data.result_code == "200") {
            EventBus.$emit("g-event-refresh-page");
            alert.Success("SUCCESS", response.data.message);
            self.goToIssueTable();
          } else {
            alert.Error("ERROR", response.data.message);
          }
        })
        .catch(function (error) {
          overlay.hide();
          alert.Error("ERROR", error);
        });
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
    checkProductAvailability() {
      if (appState.currentPeriodId == "") {
        alert.Error("ERROR", "Please select a visit");
        return;
      }
      if (appState.currentProductCode == "") {
        alert.Error("ERROR", "Please select a product");
        return;
      }

      let data = {
        periodid: appState.currentPeriodId,
        product_code: appState.currentProductCode,
      };

      let url = common.DataService;
      let self = this;
      overlay.show();
      axios
        .post(url + "?qid=1131", JSON.stringify(data))
        .then(function (response) {
          overlay.hide();
          if (response.data.result_code == "200") {
            appState.checkData = response.data.data;
            self.searchState = true;
          } else {
            alert.Error("ERROR", response.data.message);
          }
        })
        .catch(function (error) {
          overlay.hide();
          alert.Error("ERROR", error);
        });
    },
    resetCheckTable() {
      appState.checkData = [];
      this.searchState = false;
    },
    resetForm() {
      this.resetCheckTable();
      appState.currentPeriodId = "";
      appState.currentProductCode = "";
    },
  },
  computed: {
    validitySummary() {
      const summary = { pass: 0, fail: 0 };

      appState?.checkData.forEach((item) => {
        if (item.status === "pass") summary.pass++;
        else if (item.status === "fail") summary.fail++;
      });

      const total = summary.pass + summary.fail;
      const passPercentage =
        total > 0 ? ((summary.pass / total) * 100).toFixed(1) : "0.0";

      return {
        ...summary,
        total,
        passPercentage,
      };
    },
  },

  template: `
<div class="row">
    <div class="col-md-8 col-sm-12 col-12 mb-0">
        <h2 class="content-header-title header-txt float-left mb-0">SMC</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../smc/logistics">Logistics</a></li>
                <li class="breadcrumb-item active">Availability Check</li>
            </ol>
        </div>

    </div>


    <div class="col-12 mt-1">

        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-12 col-md-5 col-sm-12 col-lg-5">
                        <div class="form-group">
                            <label>Origin</label>
                            <select class="form-control" id="cms" placeholder="CMS">
                                <option value="CMS" selected>CMS</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-12 col-md-2 col-sm-12 col-lg-2  justify-content-center align-items-center">

                        <div class="form-group middle-icon d-flex justify-content-center align-items-center">
                            <div
                                class="transfer-circle d-flex d-sm-none d-md-none justify-content-center align-items-center">
                                <div class="d-flex flex-column">
                                    <i class="feather icon-arrow-up"></i>
                                    <i class="feather icon-arrow-down"></i>
                                </div>
                            </div>

                            <div
                                class="transfer-circle d-none d-sm-flex d-md-flex justify-content-center align-items-center">
                                <div class="d-flex flex-row">
                                    <i class="feather icon-arrow-left"></i>
                                    <i class="feather icon-arrow-right"></i>
                                </div>
                            </div>
                        </div>

                    </div>

                    <div class="col-12 col-md-5 col-sm-12 col-lg-5">
                        <div class="form-group">
                            <label>Destination</label>
                            <select class="form-control period" id="destination">
                                <option value="Facility" selected>Facility</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-12 col-md-5 col-sm-12 col-lg-5">
                        <div class="form-group">
                            <label>Visit</label>
                            <select @change="resetCheckTable" v-model="appState.currentPeriodId"
                                class="form-control period" id="period">
                                <option value="">Choose Period</option>
                                <option v-for="(g, i) in appState.periodData" :value="g.periodid">{{ g.title }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <div class="col-12 col-md-5 col-sm-12 col-lg-5 offset-lg-2 offset-md-2">
                        <div class="form-group">
                            <label>*Choose Product</label>
                            <select @change="resetCheckTable" class="form-control" placeholder="Choose Product"
                                v-model="appState.currentProductCode">
                                <option value="">Choose Product</option>
                                <option v-for="(g, i) in appState.productData" :value="g.product_code">{{
                                    g.name}}</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-12 text-right">
                        <div class="form-group mb-0 mt-1">
                            <button type="button"
                                v-if="appState.currentPeriodId !=='' || appState.currentProductCode !==''"
                                style="max-width: 180px !important" class="btn btn-secondary mr-1 form-control"
                                @click="resetForm()">
                                Reset <i class="feather icon-corner-up-left ml-1 text-right"></i>
                            </button>

                            <button type="button" style="max-width: 180px !important"
                                class="btn btn-primary  form-control" @click="checkProductAvailability()">
                                Check <i class="feather icon-send ml-1 text-right"></i>
                            </button>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <div class="card" v-if="appState.checkData.length > 0">
            <div class="card-body">
                <div class="row mx-0 mb-1 shadow-sm border-light py-50 border-lighten-1">
                    <div class="col-12 col-sm-4">
                        <div class="media" style="align-items: center;">
                            <div class="media-left">
                                <span class="badge badge-primary p-50">
                                    <i class="feather icon-list"></i>
                                </span>
                            </div>
                            <div class="media-body px-1">
                                <h6 class="media-heading font-medium-2 font-weight-bolder" style="margin: 0;">{{
                                    convertStringNumberToFigures(validitySummary.total) }} </h6>
                                <small class="text-muted">Total Facilities</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-sm-4">
                        <div class="media" style="align-items: center;">
                            <div class="media-left">
                                <span class="badge badge-success  p-50">
                                    <i class="feather icon-check-square"></i>
                                </span>
                            </div>
                            <div class="media-body px-1">
                                <h6 class="media-heading font-medium-2 font-weight-bolder" style="margin: 0;">{{
                                    convertStringNumberToFigures(validitySummary.pass) }} </h6>
                                <small class="text-muted">{{progressBarWidth(validitySummary.total,
                                    validitySummary.pass)}} Passed</small>
                                <div class="progress w-100 me-3" style="height: 6px;">
                                    <div class="progress-bar"
                                        :class="progressBarStatus(validitySummary.total, validitySummary.pass)"
                                        :style="{ width: progressBarWidth(validitySummary.total, validitySummary.pass)}"
                                        :aria-valuenow="{ width: progressBarWidth(validitySummary.total, validitySummary.pass)}"
                                        aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-sm-4">
                        <div class="media" style="align-items: center;">
                            <div class="media-left">
                                <span class="badge badge-danger p-50">
                                    <i class="feather icon-x-square"></i>
                                </span>
                            </div>
                            <div class="media-body px-1">
                                <h6 class="media-heading font-medium-2 font-weight-bolder" style="margin: 0;">{{
                                    convertStringNumberToFigures(validitySummary.fail) }} </h6>
                                <small class="text-muted">{{progressBarWidth(validitySummary.total,
                                    validitySummary.fail)}} Failed</small>

                                <div class="progress w-100 me-3" style="height: 6px;">
                                    <div class="progress-bar"
                                        :class="progressBarStatus(validitySummary.total, validitySummary.fail)"
                                        :style="{ width: progressBarWidth(validitySummary.total, validitySummary.fail)}"
                                        :aria-valuenow="{ width: progressBarWidth(validitySummary.total, validitySummary.fail)}"
                                        aria-valuemin="0" aria-valuemax="100"></div>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
                <div class="table-responsive">

                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th class="px-1">#</th>
                                <th class="px-1">Origin</th>
                                <th class="px-1">Destination</th>
                                <th class="px-1">Product</th>
                                <th class="px-1">Allocated Qty.</th>
                                <th class="px-1">Available Qty.</th>
                                <th class="px-1">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(item, index) in appState.checkData">
                                <td class="px-1">{{index+1}}</td>
                                <td class="px-1">{{item.cms_name}}</td>
                                <td class="px-1">{{item.geo_string}}</td>
                                <td class="px-1">
                                    <div class="d-flex justify-content-left align-items-center">
                                        <div class="d-flex flex-column">
                                            <span class="user_name text-wrap text-body">
                                                <span class="fw-bolder">{{ item.product_name }}</span>
                                            </span>
                                            <span class="font-small-2 text-muted">{{ item.product_code }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-1">{{item.allocated_qty}}</td>
                                <td class="px-1">{{item.available_qty}}</td>
                                <td class="px-1">
                                    <span class="badge "
                                        :class="item?.status=='pass'? 'bg-light-success' : 'bg-light-danger'">{{item.status=='pass'?
                                        'Pass' : 'Failed'}}</span>
                                </td>
                            </tr>

                        </tbody>

                    </table>

                </div>
                <div v-if="appState.checkData.length == 0 && searchState == true" class="text-center mt-2 alert p-2">
                    <small> No Facility With Issue/Inbound</small>
                </div>

            </div>
        </div>
        <div class="mb-50"></div>
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
            </div>
            <div v-show="appState.pageState.page == 'availability-check'">
                <page-availability-check />
            </div>
        </div>
      `,
});
