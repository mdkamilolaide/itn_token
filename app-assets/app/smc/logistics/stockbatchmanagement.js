/**
 * SMC / Logistics / Stock-Batch Mgmt — Vue 3 Composition API in place.
 * Pick a visit, hit qid=1132, render facility-grouped stock batch table
 * with a Print-to-PDF action via pdfMake (vendor lib loaded by the page).
 */

const { ref, reactive, computed, watch, onMounted, onBeforeUnmount } = Vue;
const { useApp, useFormat, bus, safeMessage } = window.utils;

const appState = Vue.reactive({
  pageState: { page: "availability-check", title: "" },
  permission:
    typeof getPermission === "function"
      ? getPermission(typeof per !== "undefined" ? per : null, "smc") || {
          permission_value: 0,
        }
      : { permission_value: 0 },
  userId: (() => {
    var el = document.getElementById("v_g_id");
    return el ? el.value : "";
  })(),
  geoLevelForm: { geoLevel: "", geoLevelId: 0 },
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

const displayDateLong = (d, fullDate, withTime) => {
  if (fullDate === undefined) fullDate = false;
  if (withTime === undefined) withTime = true;
  var date = new Date(d);
  var options = {
    year: "numeric",
    month: fullDate ? "long" : "short",
    day: "numeric",
  };
  if (withTime) {
    options.hour = "2-digit";
    options.minute = "2-digit";
    options.hour12 = true;
  }
  return date.toLocaleString("en-US", options);
};

const PageAvailabilityCheck = {
  setup() {
    const fmtUtils = useFormat();
    const searchState = ref(false);
    const searchText = ref("");

    const getAllPeriodLists = () => {
      overlay.show();
      axios
        .get(common.DataService + "?qid=1004")
        .then((response) => {
          appState.periodData = (response.data && response.data.data) || [];
          overlay.hide();
        })
        .catch((error) => {
          overlay.hide();
          alert.Error("ERROR", safeMessage(error));
        });
    };
    const getReceiptHeader = () => {
      overlay.show();
      axios
        .get(common.DataService + "?qid=gen0013")
        .then((response) => {
          var data =
            response.data && response.data.data && response.data.data[0];
          appState.receiptHeader = (data && data.logo) || "";
          overlay.hide();
        })
        .catch((error) => {
          overlay.hide();
          alert.Error("ERROR", safeMessage(error));
        });
    };
    const generateBatchStock = () => {
      if (appState.currentPeriodId == "") {
        alert.Error("ERROR", "Please select a visit");
        return;
      }
      var data = { periodid: appState.currentPeriodId };
      overlay.show();
      axios
        .post(common.DataService + "?qid=1132", JSON.stringify(data))
        .then((response) => {
          overlay.hide();
          if (response.data.result_code == "200") {
            appState.stockBatchData = response.data.data || [];
            searchState.value = true;
          } else {
            alert.Error("ERROR", response.data.message);
          }
        })
        .catch((error) => {
          overlay.hide();
          var msg =
            (error &&
              error.response &&
              error.response.data &&
              error.response.data.message) ||
            safeMessage(error);
          alert.Error("ERROR", msg);
        });
    };
    const resetCheckTable = () => {
      appState.stockBatchData = [];
      searchState.value = false;
    };
    const resetForm = () => {
      resetCheckTable();
      appState.currentPeriodId = "";
      appState.currentProductCode = "";
    };

    const filteredStockData = computed(() => {
      var keyword = (appState.filterText || "").toLowerCase().trim();
      if (!keyword) return appState.stockBatchData || [];
      return (appState.stockBatchData || []).filter(
        (item) =>
          (item.product_name &&
            item.product_name.toLowerCase().includes(keyword)) ||
          (item.batch && item.batch.toLowerCase().includes(keyword)) ||
          (item.origin_string &&
            item.origin_string.toLowerCase().includes(keyword)) ||
          (item.destination_string &&
            item.destination_string.toLowerCase().includes(keyword)),
      );
    });
    const groupDataByFacility = computed(() => {
      var grouped = filteredStockData.value.reduce((acc, item) => {
        var key = item.lgaid + "||" + item.lga;
        if (!acc[key])
          acc[key] = { lgaid: item.lgaid, lga: item.lga, items: [] };
        var updatedItem = Object.assign({}, item, {
          rate: fmtUtils.convertStringNumberToFigures(item.rate),
          secondary_qty: fmtUtils.convertStringNumberToFigures(
            item.secondary_qty,
          ),
          expiry: displayDateLong(item.expiry, false, false),
        });
        acc[key].items.push(updatedItem);
        return acc;
      }, {});
      return Object.values(grouped).sort((a, b) => a.lga.localeCompare(b.lga));
    });

    const generatePDF = () => {
      var content = [];
      var data = groupDataByFacility.value;
      if (!data || data.length === 0) {
        alert.Error("ERROR", "No data available for PDF generation.");
        return;
      }
      var todayDate = displayDateLong(
        new Date().toLocaleDateString(),
        false,
        true,
      );
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
                  { text: "Destination", style: "tableHeader" },
                  { text: "Product", style: "tableHeader", noWrap: true },
                  { text: "Code", style: "tableHeader" },
                  { text: "Batch", style: "tableHeader" },
                  { text: "Expiry Date", style: "tableHeader", noWrap: true },
                  { text: "Quantity", style: "tableHeader" },
                ],
              ].concat(
                group.items.map((item, i) => [
                  { text: i + 1, style: "tableBodyFont8" },
                  { text: item.destination_string, style: "tableBodyFont8" },
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
              ),
            },
            layout: "lightHorizontalLines",
            margin: [0, 0, 0, 20],
          },
        );
        if (index < data.length - 1)
          content.push({ text: "", pageBreak: "after" });
      });

      var docDefinition = {
        pageSize: "A4",
        pageOrientation: "landscape",
        pageMargins: [40, 60, 40, 60],
        images: {
          logoDataURL: "data:image/svg+xml;base64," + appState.receiptHeader,
        },
        header: (currentPage, pageCount) => {
          return {
            columns: [
              currentPage === 1
                ? { image: "logoDataURL", width: 80 }
                : { text: "", width: 80 },
              {
                text: "Page " + currentPage + " of " + pageCount,
                alignment: "right",
                margin: [0, 15, 0, 0],
                fontSize: 9,
              },
            ],
            margin: [40, 20, 40, 0],
          };
        },
        footer: (currentPage, pageCount) => {
          return {
            columns: [
              {
                text: "Generated by Ipolongo System",
                alignment: "left",
                fontSize: 9,
              },
              { text: todayDate, alignment: "center", fontSize: 9 },
              {
                text: "Page " + currentPage + " of " + pageCount,
                alignment: "right",
                fontSize: 9,
              },
            ],
            margin: [40, 0, 40, 20],
          };
        },
        content: content,
        styles: {
          header: { fontSize: 12, bold: true },
          subheader: { fontSize: 10, margin: [0, 2] },
          tableHeader: {
            bold: true,
            fillColor: "#eeeeee",
            fontSize: 10,
            margin: [0, 2, 0, 2],
          },
          tableBodyFont8: { fontSize: 8, lineHeight: 1.2 },
        },
      };
      if (typeof pdfMake !== "undefined") {
        pdfMake
          .createPdf(docDefinition)
          .download("Stock_Batch_order_" + todayDate + ".pdf");
      }
    };

    const debounce = (fn, delay) => {
      var timeout;
      return function () {
        var args = arguments;
        clearTimeout(timeout);
        timeout = setTimeout(() => {
          fn.apply(null, args);
        }, delay);
      };
    };
    var debouncedSearch = debounce((val) => {
      appState.filterText = val;
    }, 300);
    watch(searchText, (val) => {
      debouncedSearch(val);
    });

    onMounted(() => {
      getAllPeriodLists();
      getReceiptHeader();
      bus.on("g-event-reset-form", resetForm);
    });
    onBeforeUnmount(() => {
      bus.off("g-event-reset-form", resetForm);
    });

    return {
      appState,
      searchState,
      searchText,
      filteredStockData,
      groupDataByFacility,
      getAllPeriodLists,
      getReceiptHeader,
      generateBatchStock,
      resetCheckTable,
      resetForm,
      generatePDF,
      displayDate: (d, fullDate, withTime) => {
        return displayDateLong(d, fullDate, withTime);
      },
      convertStringNumberToFigures: fmtUtils.convertStringNumberToFigures,
      capitalize: fmtUtils.capitalize,
    };
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
                                    <select class="form-control"><option value="CMS" selected>CMS</option></select>
                                </div>
                            </div>
                            <div class="col-md-2 col-sm-12 col-lg-2 justify-content-center align-items-center">
                                <div class="form-group middle-icon d-flex justify-content-center align-items-center">
                                    <div class="transfer-circle d-flex d-sm-flex d-md-none bg-label-primary text-white justify-content-center align-items-center"><div class="d-flex flex-column"><i class="feather icon-arrow-down"></i></div></div>
                                    <div class="transfer-circle d-none d-sm-none d-md-flex bg-label-primary text-white justify-content-center align-items-center"><div class="d-flex flex-row"><i class="feather icon-arrow-right"></i></div></div>
                                </div>
                            </div>
                            <div class="col-md-5 col-sm-12 col-lg-5">
                                <div class="form-group">
                                    <label>Destination</label>
                                    <select class="form-control period"><option value="Facility" selected>Facility</option></select>
                                </div>
                            </div>
                            <div class="col-12 col-md-12 col-sm-12 col-lg-5">
                                <div class="form-group">
                                    <label>Visit</label>
                                    <select @change="resetCheckTable" v-model="appState.currentPeriodId" class="form-control period">
                                        <option value="">Choose Period</option>
                                        <option v-for="(g, i) in appState.periodData" :key="g.periodid" :value="g.periodid">{{ g.title }}</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-xs-12 col-sm-12 col-md-12 col-lg-5 offset-lg-2">
                                <div class="form-group col-12 justify-content-lg-end text-right">
                                    <label class="control-label d-flex">&nbsp;</label>
                                    <div class="row justify-content-end align-content-end">
                                        <div class="col-xs-6" v-if="appState.currentPeriodId !== ''" style="padding-right: 5px;">
                                            <button type="button" class="btn btn-secondary btn-block" @click="resetForm()">Reset <i class="feather icon-corner-up-left"></i></button>
                                        </div>
                                        <div :class="appState.currentPeriodId !== '' ? 'col-xs-6' : 'col-xs-12'">
                                            <button type="button" class="btn btn-primary btn-block" @click="generateBatchStock()">Generate Batch <i class="feather icon-send"></i></button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card" v-if="searchState == true">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-7">
                                <button type="button" style="max-width: 160px" class="btn btn-deafult border waves-button btn-block mb-1" @click="generatePDF()"><i class="feather icon-printer"></i> Print</button>
                            </div>
                            <div class="col-5">
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
                                    <tr v-for="(item, index) in filteredStockData" :key="index">
                                        <td class="px-1">{{ index + 1 }}</td>
                                        <td class="px-1">
                                            <div class="d-flex flex-column">
                                                <span class="fw-bolder">{{ item.origin_string }}</span>
                                                <span class="font-small-2 text-muted">{{ item.origin_type }}</span>
                                            </div>
                                        </td>
                                        <td class="px-1">
                                            <div class="d-flex flex-column">
                                                <span class="fw-bolder">{{ item.destination_string }}</span>
                                                <span class="font-small-2 text-muted">{{ item.destination_type }}</span>
                                            </div>
                                        </td>
                                        <td class="px-1">
                                            <div class="d-flex flex-column">
                                                <span class="fw-bolder">{{ item.product_name }}</span>
                                                <span class="font-small-2 text-muted">{{ item.product_code }}</span>
                                            </div>
                                        </td>
                                        <td class="px-1">{{ convertStringNumberToFigures(item.secondary_qty) }}</td>
                                        <td class="px-1">{{ item.unit }}</td>
                                        <td class="px-1">{{ item.batch }}</td>
                                        <td class="px-1">{{ displayDate(item.expiry, false, false) }}</td>
                                        <td class="px-1">{{ convertStringNumberToFigures(item.rate) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div v-if="filteredStockData.length == 0 && searchState == true" class="text-center mt-2 alert p-2"><small>No Search Match/ Facility With Issue/Inbound</small></div>
                    </div>
                </div>
                <div class="mb-50"></div>
            </div>
        </div>
    `,
};

useApp({
  template: `
        <div>
            <div v-show="appState.pageState.page == 'table'"></div>
            <div v-show="appState.pageState.page == 'availability-check'"><page-availability-check/></div>
        </div>
    `,
  setup() {
    return { appState };
  },
})
  .component("page-availability-check", PageAvailabilityCheck)
  .mount("#app");
