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
  stockBatchData: [],

  periodData: [],
  currentPeriodId: "",
  currentProductCode: "",
  receiptHeader: "",
  filterText: "",
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

Vue.component("page-availability-check", {
  mixins: [eventBusMixin],
  data: function () {
    return {
      appState,
      searchState: false,
      searchText: "",
    };
  },
  mounted() {
    /*  Manages events Listening    */
    // this.getSysDefaultDataSettings();
    this.getAllPeriodLists();
    // this.getProductMaster();
    this.getReceiptHeader();

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
    getReceiptHeader() {
      /*  Manages the loading of Geo Level data */
      var self = this;
      var url = common.DataService;
      overlay.show();

      axios
        .get(url + "?qid=gen0013")
        .then(function (response) {
          appState.receiptHeader = response.data.data[0].logo; //All Data

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
    generateBatchStock() {
      if (appState.currentPeriodId == "") {
        alert.Error("ERROR", "Please select a visit");
        return;
      }

      let data = {
        periodid: appState.currentPeriodId,
      };

      let url = common.DataService;
      let self = this;
      overlay.show();
      axios
        .post(url + "?qid=1132", JSON.stringify(data))
        .then(function (response) {
          overlay.hide();
          if (response.data.result_code == "200") {
            appState.stockBatchData = response.data.data;
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
      appState.stockBatchData = [];
      this.searchState = false;
    },
    resetForm() {
      this.resetCheckTable();
      appState.currentPeriodId = "";
      appState.currentProductCode = "";
    },
    generatePDF() {
      const content = [];
      const data = this.groupDataByFacility;
      if (data.length === 0) {
        alert.Error("ERROR", "No data available for PDF generation.");
        return;
      }
      let todayDate = this.displayDate(
        new Date().toLocaleDateString(),
        false,
        true
      );
      const pageWidth = 842;
      const rightMargin = 40;
      const lineLength = 150;

      data.forEach((group, index) => {
        content.push(
          {
            text: "STOCK BATCH ORDER FOR " + group.lga + " LGA",
            fontSize: 12,
            style: "header",
            alignment: "center",
            margin: [0, 0, 0, 40],
          },

          {
            table: {
              headerRows: 1,
              widths: ["auto", "auto", "auto", "auto", "auto", "auto", "*"],
              body: [
                [
                  { text: "#", style: "tableHeader" },
                  { text: "Destination", style: "tableHeader", noWrap: false },
                  { text: "Product", style: "tableHeader", noWrap: true },
                  { text: "Code", style: "tableHeader" },
                  { text: "Batch", style: "tableHeader" },
                  { text: "Expiry Date", style: "tableHeader", noWrap: true },
                  { text: "Quantity", style: "tableHeader" },
                ],
                ...group.items.map((item, i) => [
                  { text: i + 1, style: "tableBodyFont8" },
                  {
                    text: item.destination_string,
                    noWrap: false,
                    style: "tableBodyFont8",
                  },
                  {
                    text: item.product_name,
                    style: "tableBodyFont8",
                    noWrap: true,
                  },
                  { text: item.product_code, style: "tableBodyFont8" },
                  { text: item.batch, style: "tableBodyFont8" },
                  { text: item.expiry, style: "tableBodyFont8", noWrap: true },
                  { text: item.secondary_qty, style: "tableBodyFont8" },
                ]),
              ],
            },
            layout: "lightHorizontalLines",
            margin: [0, 0, 0, 20],
          },

          ...(index < data.length - 1 ? [{ text: "", pageBreak: "after" }] : [])
        );
      });

      const docDefinition = {
        pageSize: "A4",
        pageOrientation: "landscape", // Force portrait (optional if default)
        pageMargins: [40, 60, 40, 60],
        images: {
          logoDataURL: `data:image/svg+xml;base64,${appState.receiptHeader}`, // or PNG/JPEG
        },
        header: function (currentPage, pageCount, pageSize) {
          return {
            columns: [
              currentPage === 1
                ? { image: "logoDataURL", width: 80 }
                : { text: "", width: 80 }, // empty space to maintain layout
              {
                text: `Page ${currentPage} of ${pageCount}`,
                alignment: "right",
                margin: [0, 15, 0, 0],
                fontSize: 9,
              },
            ],
            margin: [40, 20, 40, 0],
          };
        },

        footer: function (currentPage, pageCount) {
          return {
            columns: [
              {
                text: "Generated by Ipolongo System",
                alignment: "left",
                fontSize: 9,
              },
              {
                text: todayDate,
                alignment: "center",
                fontSize: 9,
              },
              {
                text: `Page ${currentPage} of ${pageCount}`,
                alignment: "right",
                fontSize: 9,
              },
            ],
            margin: [40, 0, 40, 20],
          };
        },
        content,
        styles: {
          header: {
            fontSize: 12,
            bold: true,
          },
          subheader: {
            fontSize: 10,
            margin: [0, 2],
          },
          tableHeader: {
            bold: true,
            fillColor: "#eeeeee",
            fontSize: 10,
            margin: [0, 2, 0, 2],
          },
          footer: {
            fontSize: 10,
            margin: [0, 10, 0, 0],
          },
          tableBody: {
            fontSize: 8,
            lineHeight: 1.2,
          },
        },
      };

      pdfMake
        .createPdf(docDefinition)
        .download("Stock_Batch_order_" + todayDate + ".pdf");
    },
    debounce(func, delay) {
      let timeout;
      return function (...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(this, args), delay);
      };
    },
  },
  created() {
    this.debouncedSearch = this.debounce((val) => {
      this.appState.filterText = val;
    }, 300); // 300ms is ideal for typing
  },
  watch: {
    searchText(val) {
      this.debouncedSearch(val);
    },
  },
  computed: {
    filteredStockData() {
      const keyword = appState.filterText?.toLowerCase().trim();
      if (!keyword) return appState.stockBatchData || [];

      return appState.stockBatchData.filter((item) => {
        return (
          item.product_name?.toLowerCase().includes(keyword) ||
          item.batch?.toLowerCase().includes(keyword) ||
          item.origin_string?.toLowerCase().includes(keyword) ||
          item.destination_string?.toLowerCase().includes(keyword)
        );
      });
    },
    groupDataByFacility() {
      const grouped = this.filteredStockData.reduce((acc, item) => {
        const key = `${item.lgaid}||${item.lga}`;
        if (!acc[key]) {
          acc[key] = {
            lgaid: item.lgaid,
            lga: item.lga,
            items: [],
          };
        }

        // Clone and update expiry using updateNewDate
        const updatedItem = {
          ...item,
          rate: this.convertStringNumberToFigures(item.rate),
          secondary_qty: this.convertStringNumberToFigures(item.secondary_qty),
          expiry: this.displayDate(item.expiry, false, false),
        };

        acc[key].items.push(updatedItem);
        return acc;
      }, {});

      return Object.values(grouped).sort((a, b) => a.lga.localeCompare(b.lga));
    },
  },

  template: `
              <div class="row">
                  <div class="col-md-8 col-sm-12 col-12 mb-0">
                      <h2 class="content-header-title header-txt float-left mb-0">SMC</h2>
                      <div class="breadcrumb-wrapper">
                          <ol class="breadcrumb">
                              <li class="breadcrumb-item"><a href="../smc/logistics">Logistics</a></li>
                              <li class="breadcrumb-item active">Stock-Batch MGT.</li>
                          </ol>
                      </div>

                  </div>


                  <div class="col-12 mt-1">

                      <div class="card">

                          <div class="card-body">
                              <div class="row">
                                  <div class="col-md-5 col-sm-12 col-lg-5">
                                      <div class="form-group">
                                          <label>Origin</label>
                                          <select class="form-control" id="cms" placeholder="CMS">
                                              <option value="CMS" selected>CMS</option>
                                          </select>
                                      </div>
                                  </div>

                                  <div class="col-md-2 col-sm-12 col-lg-2  justify-content-center align-items-center">

                                      <div class="form-group middle-icon d-flex justify-content-center align-items-center">
                                          <div class="transfer-circle d-flex d-sm-flex d-md-none bg-label-primary text-white justify-content-center align-items-center"
                                              onclick="swapLocations()">
                                              <div class="d-flex flex-column">
                                                  <i class="feather icon-arrow-down"></i>
                                              </div>
                                          </div>

                                          <div class="transfer-circle d-none d-sm-none d-md-flex bg-label-primary text-white justify-content-center align-items-center"
                                              onclick="swapLocations()">
                                              <div class="d-flex flex-row">
                                                  <i class="feather icon-arrow-right"></i>
                                              </div>
                                          </div>
                                      </div>
                                  </div>

                                  <div class="col-md-5 col-sm-12 col-lg-5">
                                      <div class="form-group">
                                          <label>Destination</label>
                                          <select class="form-control period" id="destination">
                                              <option value="Facility" selected>Facility</option>
                                          </select>
                                      </div>
                                  </div>

                                  <div class="col-12 col-md-12 col-sm-12 col-lg-5">
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

                                  <div class="col-xs-12 col-sm-12 col-md-12 col-lg-5 offset-lg-2">
                                      <div class="form-group col-12 justify-content-lg-end text-right">
                                          <label class="control-label d-flex ">&nbsp;</label>
                                          <div class="row justify-content-end align-content-end">
                                              <div class="col-xs-6" v-if="appState.currentPeriodId !== ''"
                                                  style="padding-right: 5px;">
                                                  <button type="button" class="btn btn-secondary btn-block" @click="resetForm()">
                                                      Reset <i class="feather icon-corner-up-left"></i>
                                                  </button>
                                              </div>
                                              <div :class="appState.currentPeriodId !== '' ? 'col-xs-6' : 'col-xs-12'">
                                                  <button type="button" class="btn btn-primary btn-block"
                                                      @click="generateBatchStock()">
                                                      Generate Batch <i class="feather icon-send"></i>
                                                  </button>
                                              </div>
                                          </div>
                                      </div>
                                  </div>

                              </div>
                          </div>

                      </div>

                      <div class="card" v-if="this.searchState ==true">
                          <div class="card-body">
                              <div class="row">
                                  <div class="col-7">
                                      <button type="button" style="max-width: 160px"
                                          class="btn btn-deafult border waves-button btn-block mb-1" @click="generatePDF()">
                                          <i class="feather icon-printer"></i> Print
                                      </button>
                                  </div>
                                  <div class=" col-5">
                                      <div class="form-group">
                                          <input type="text" v-model="searchText" placeholder="Search" class="form-control" />
                                      </div>
                                  </div>
                              </div>

                              <div class="table-responsive">

                                  <table class="table table-hover mt-1">
                                      <thead>
                                          <tr>
                                              <th class="px-1">#</th>
                                              <th class="px-1">Origin</th>
                                              <th class="px-1">Destination</th>
                                              <th class="px-1">Product</th>
                                              <th class="px-1">Qty.</th>
                                              <th class="px-1">Unit</th>
                                              <th class="px-1">Batch</th>
                                              <th class="px-1">Expiry Date</th>
                                              <th class="px-1">Rate</th>
                                          </tr>
                                      </thead>
                                      <tbody>
                                          <tr v-for="(item, index) in filteredStockData">
                                              <td class="px-1">{{index+1}}</td>
                                              <td class="px-1">
                                                  <div class="d-flex justify-content-left align-items-center">
                                                      <div class="d-flex flex-column">
                                                          <span class="user_name text-wrap text-body">
                                                              <span class="fw-bolder">{{ item.origin_string }}</span>
                                                          </span>
                                                          <span class="font-small-2 text-muted">{{ item.origin_type }}</span>
                                                      </div>
                                                  </div>
                                              </td>
                                              <td class="px-1">
                                                  <div class="d-flex justify-content-left align-items-center">
                                                      <div class="d-flex flex-column text-wrap">
                                                          <span class="user_name text-wrap text-body">
                                                              <span class="fw-bolder">{{ item.destination_string }}</span>
                                                          </span>
                                                          <span class="font-small-2 text-muted">{{ item.destination_type }}</span>
                                                      </div>
                                                  </div>
                                              </td>
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
                                              <td class="px-1">{{convertStringNumberToFigures(item.secondary_qty)}}</td>
                                              <td class="px-1">{{item.unit}}</td>
                                              <td class="px-1">{{item.batch}}</td>
                                              <td class="px-1">{{displayDate(item.expiry, false, false)}}</td>
                                              <td class="px-1">{{convertStringNumberToFigures(item.rate)}}</td>
                                          </tr>

                                      </tbody>

                                  </table>

                              </div>
                              <div v-if="filteredStockData.length == 0 && searchState == true" class="text-center mt-2 alert p-2">
                                  <small> No Search Match/ Facility With Issue/Inbound</small>
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
