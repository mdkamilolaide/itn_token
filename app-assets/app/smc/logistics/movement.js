/**
 * SMC / Logistics / Movement — Vue 3 Composition API in place.
 * Single root view: page-movement-page.
 *
 * Pick a visit → load movements (qid=1137). Click a row → fetch
 * movement details (qid=1138) and open the Shipment List modal.
 * Per-shipment ePOD PDF preview/download (qid=1136a + pdfMake).
 */

const { ref, reactive, computed, watch, onMounted, onBeforeUnmount } = Vue;
const { useApp, useFormat, bus, safeMessage } = window.utils;

const appState = Vue.reactive({
  pageState: { page: "movement-page", title: "" },
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
  movementData: [],
  periodData: [],
  currentPeriodId: "",
  currentProductCode: "",
  receiptHeader: "",
  filterText: "",
  statusFilter: "",
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

const PageMovementPage = {
  setup() {
    const fmtUtils = useFormat();

    const searchState = ref(false);
    const searchText = ref("");
    const movementType = ref("Forward");
    const checkToggle = ref(false);
    const conveyorData = ref([]);
    const transporterData = ref([]);
    const movementForm = reactive({
      periodId: "",
      transporterId: "",
      movementTitle: "",
      shipmentListIds: [],
      conveyorId: "",
      userid: appState.userId,
    });
    const movementItemDetails = ref([]);

    const closeMovementModal = () => {
      $("#movementModal").modal("hide");
    };
    const resetMovementForm = () => {
      closeMovementModal();
      movementForm.movementTitle = "";
      movementForm.shipmentListIds = [];
      movementForm.conveyorId = "";
      movementForm.transporterId = "";
      loadMovement();
      selectToggle();
    };

    const getTransporterAndConveyor = async () => {
      var endpoints = [
        common.DataService + "?qid=gen014",
        common.DataService + "?qid=gen015",
      ];
      try {
        overlay.show();
        var responses = await Promise.all(endpoints.map((e) => axios.get(e)));
        transporterData.value =
          (responses[0] && responses[0].data && responses[0].data.data) || [];
        conveyorData.value =
          (responses[1] && responses[1].data && responses[1].data.data) || [];
      } catch (error) {
        alert.Error("ERROR", safeMessage(error));
      } finally {
        overlay.hide();
      }
    };

    const getAllPeriodLists = () => {
      overlay.show();
      axios
        .get(common.DataService + "?qid=1004")
        .then((response) => {
          appState.periodData = (response.data && response.data.data) || [];
          getActivePeriodId(appState.periodData);
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
          var d = response.data && response.data.data && response.data.data[0];
          appState.receiptHeader = (d && d.logo) || "";
          overlay.hide();
        })
        .catch((error) => {
          overlay.hide();
          alert.Error("ERROR", safeMessage(error));
        });
    };
    const getActivePeriodId = (periodData) => {
      var active = (periodData || []).find((p) => p.active === 1);
      if (active) {
        appState.currentPeriodId = active.periodid;
        loadMovement();
      }
    };

    const loadMovement = async () => {
      if (!appState.currentPeriodId) {
        alert.Error("ERROR", "Please select a visit");
        return;
      }
      var data = { periodId: appState.currentPeriodId };
      overlay.show();
      try {
        var response = await axios.post(
          common.DataService + "?qid=1137",
          JSON.stringify(data),
        );
        if (response.data.result_code !== 200) {
          alert.Error("ERROR", response.data.message);
          return;
        }
        appState.movementData = response.data.data || [];
        searchState.value = true;
      } catch (error) {
        alert.Error("ERROR", safeMessage(error));
      } finally {
        overlay.hide();
      }
    };

    const selectAll = () => {
      (filteredMovementData.value || []).forEach((item) => {
        if (item.shipment_status === "Pending") item.pick = true;
      });
    };
    const uncheckAll = () => {
      (filteredMovementData.value || []).forEach((item) => {
        item.pick = false;
      });
    };
    const selectToggle = () => {
      if (checkToggle.value === false) {
        selectAll();
        checkToggle.value = true;
      } else {
        uncheckAll();
        checkToggle.value = false;
      }
    };
    const checkedBg = (p) => {
      return p != "" ? "bg-select" : "";
    };

    const resetCheckTable = () => {
      appState.movementData = [];
      searchState.value = false;
    };
    const resetForm = () => {
      resetCheckTable();
      appState.currentPeriodId = "";
      appState.currentProductCode = "";
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
    var debouncedStatusFilter = debounce((val) => {
      appState.statusFilter = val;
    }, 1);
    watch(searchText, (val) => {
      debouncedSearch(val);
    });
    const filterUsingStatus = (keyword) => {
      appState.statusFilter = keyword;
    };

    const filteredMovementData = computed(() => {
      var data = appState.movementData || [];
      var keyword = (appState.filterText || "").toLowerCase().trim();
      var status = (appState.statusFilter || "").toLowerCase().trim();
      if (status) {
        data = data.filter(
          (item) =>
            item.shipment_status &&
            item.shipment_status.toLowerCase().trim().includes(status),
        );
      }
      if (keyword) {
        data = data.filter(
          (item) =>
            (item.title && item.title.toLowerCase().includes(keyword)) ||
            (item.transporter &&
              item.transporter.toLowerCase().includes(keyword)) ||
            (item.transporter_phone &&
              item.transporter_phone.toLowerCase().includes(keyword)) ||
            (item.entered_by &&
              item.entered_by.toLowerCase().includes(keyword)) ||
            (item.entered_by_loginid &&
              item.entered_by_loginid.toLowerCase().includes(keyword)) ||
            (item.conveyor_loginid &&
              item.conveyor_loginid.toLowerCase().includes(keyword)) ||
            (item.conveyor_fullname &&
              item.conveyor_fullname.toLowerCase().includes(keyword)) ||
            (item.conveyor_phone &&
              item.conveyor_phone.toLowerCase().includes(keyword)) ||
            (item.updated && item.updated.toLowerCase().includes(keyword)),
        );
      }
      return data
        .slice()
        .sort((a, b) => new Date(b.updated || 0) - new Date(a.updated || 0));
    });
    const selectedItems = computed(() =>
      (filteredMovementData.value || []).filter((i) => i.pick),
    );
    const selectedID = computed(() =>
      (filteredMovementData.value || [])
        .filter((i) => i.pick)
        .map((i) => i.shipment_id),
    );
    const totalCheckedBox = computed(() => {
      var total = (selectedItems.value || []).length;
      var el = document.getElementById("total-selected");
      if (el) {
        if (total > 0) {
          el.innerHTML =
            '<span class="badge badge-primary btn-icon"><span class="badge badge-success">' +
            total +
            "</span> Item Selected</span>";
        } else {
          el.replaceChildren();
        }
      }
      return total > 0;
    });

    const getMovementDetails = (movementId) => {
      overlay.show();
      axios
        .post(
          common.DataService + "?qid=1138",
          JSON.stringify({ movementId: movementId }),
        )
        .then((response) => {
          overlay.hide();
          if (response.data.result_code === 200) {
            movementItemDetails.value = response.data.data || [];
            $("#movementModal").modal("show");
          } else {
            alert.Error("ERROR", response.data.message);
          }
        })
        .catch((error) => {
          overlay.hide();
          alert.Error("ERROR", safeMessage(error));
        });
    };
    const getShipmentItem = (shipmentId, preview) => {
      overlay.show();
      axios
        .post(
          common.DataService + "?qid=1136a",
          JSON.stringify({ shipmentId: shipmentId }),
        )
        .then((response) => {
          overlay.hide();
          if (response.data.result_code === 200) {
            generateShipmentPDF(response.data.data, preview);
          } else {
            alert.Error("ERROR", response.data.message);
          }
        })
        .catch((error) => {
          overlay.hide();
          alert.Error("ERROR", safeMessage(error));
        });
    };

    const generateShipmentPDF = (data, options) => {
      options = options || { preview: true };
      var shipment = data && data.shipment && data.shipment[0];
      var items = (data && data.items) || [];
      var approval = data && data.approvals && data.approvals[0];
      var destString = (shipment && shipment.destination_string) || "Facility";
      if (!shipment || items.length === 0) {
        alert.Error("ERROR", "No data available for PDF generation.");
        return;
      }
      var todayDate = displayDateLong(new Date().toISOString(), true, false);
      var logoDataURL = "data:image/svg+xml;base64," + appState.receiptHeader;
      if (typeof pdfMake === "undefined") return;
      var docDefinition = {
        pageSize: "A4",
        pageOrientation: "landscape",
        pageMargins: [40, 60, 40, 60],
        images: { logo: logoDataURL },
        content: [
          {
            text: "Electronic Proof of Delivery (ePOD)",
            fontSize: 14,
            bold: true,
            alignment: "center",
            margin: [0, 0, 0, 20],
          },
          {
            text: "Shipment No: " + shipment.shipment_no,
            margin: [0, 0, 0, 10],
          },
          {
            table: {
              headerRows: 1,
              widths: ["auto", "*", "*", "auto", "auto", "auto", "auto"],
              body: [
                [
                  "#",
                  "Product Name",
                  "Product Code",
                  "Batch",
                  "Expiry",
                  "Unit",
                  "Quantity",
                ],
              ].concat(
                items.map((item, i) => [
                  i + 1,
                  item.product_name,
                  item.product_code,
                  item.batch,
                  displayDateLong(item.expiry, false, false),
                  item.unit,
                  item.secondary_qty,
                ]),
              ),
            },
          },
        ],
      };
      try {
        if (options.preview) {
          pdfMake.createPdf(docDefinition).getBlob((blob) => {
            var url = URL.createObjectURL(blob);
            var a = document.createElement("a");
            a.href = url;
            a.target = "_blank";
            a.rel = "noopener noreferrer";
            a.click();
            setTimeout(() => {
              URL.revokeObjectURL(url);
            }, 10000);
          });
        } else {
          pdfMake
            .createPdf(docDefinition)
            .download("ePod_" + destString + "_" + todayDate + ".pdf");
        }
      } catch (e) {
        console.error("[smc/logistics/movement] PDF render failed:", e);
      }
    };

    onMounted(() => {
      getAllPeriodLists();
      getReceiptHeader();
      getTransporterAndConveyor();
      bus.on("g-event-reset-form", resetForm);
    });
    onBeforeUnmount(() => {
      bus.off("g-event-reset-form", resetForm);
    });

    return {
      appState,
      searchState,
      searchText,
      movementType,
      checkToggle,
      conveyorData,
      transporterData,
      movementForm,
      movementItemDetails,
      filteredMovementData,
      selectedItems,
      selectedID,
      totalCheckedBox,
      closeMovementModal,
      resetMovementForm,
      getTransporterAndConveyor,
      getAllPeriodLists,
      getReceiptHeader,
      getActivePeriodId,
      loadMovement,
      selectAll,
      uncheckAll,
      selectToggle,
      checkedBg,
      resetCheckTable,
      resetForm,
      filterUsingStatus,
      getMovementDetails,
      getShipmentItem,
      generateShipmentPDF,
      capitalize: fmtUtils.capitalize,
      displayDate: (d, fullDate, withTime) => {
        return displayDateLong(d, fullDate, withTime);
      },
      convertStringNumberToFigures: fmtUtils.convertStringNumberToFigures,
    };
  },
  template: `
        <div class="row">
            <div class="col-md-8 col-sm-12 col-12 mb-0">
                <h2 class="content-header-title header-txt float-left mb-0">SMC</h2>
                <div class="breadcrumb-wrapper">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../smc/logistics">Logistics</a></li>
                        <li class="breadcrumb-item active">Movement Management.</li>
                    </ol>
                    <span id="total-selected"></span>
                </div>
            </div>

            <div class="col-12 mt-1">
                <div class="card">
                    <div class="card-body">
                        <div class="col-12 py-75">
                            <label>Choose Period</label>
                            <div class="d-flex flex-nowrap align-items-center">
                                <select @change="resetCheckTable" v-model="appState.currentPeriodId" class="form-control period mr-1">
                                    <option value="">Choose Period</option>
                                    <option v-for="(g, i) in appState.periodData" :key="g.periodid" :value="g.periodid">{{ g.title }}</option>
                                </select>
                                <button type="button" class="btn btn-primary border mb-25 waves-button px-sm-1" @click="loadMovement()">
                                    <span>Load Movement</span>
                                    <i class="feather icon-send ml-50"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-datatable">
                        <template v-if="searchState">
                            <div class="d-flex flex-wrap align-items-center justify-content-between px-1">
                                <div class="col-12 col-sm-12 col-md-6 col-lg-6 py-75 d-flex-block justify-content-end align-items-center">
                                    <div class="d-flex flex-nowrap justify-content-end">
                                        <input type="text" v-model="searchText" placeholder="Search" class="form-control w-100 mb-25" style="min-width: 0;" />
                                    </div>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th class="px-25">#</th>
                                            <th class="px-1">Title</th>
                                            <th class="px-1">Entered</th>
                                            <th class="px-1">Transporter</th>
                                            <th class="px-1">Conveyor</th>
                                            <th class="px-1">Conveyor Phone</th>
                                            <th class="px-1">Updated</th>
                                            <th class="px-1">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="(item, index) in filteredMovementData" :key="item.movement_id || index">
                                            <td class="px-25">{{ index + 1 }}</td>
                                            <td class="px-1">{{ item.title }}</td>
                                            <td class="px-1">
                                                <div class="d-flex flex-column text-wrap">
                                                    <span class="fw-bolder">{{ item.entered_by }}</span>
                                                    <span class="font-small-2 text-muted">{{ item.entered_by_loginid }}</span>
                                                </div>
                                            </td>
                                            <td class="px-1">
                                                <div class="d-flex flex-column text-wrap">
                                                    <span class="fw-bolder">{{ item.transporter }}</span>
                                                    <span class="font-small-2 text-muted">{{ item.transporter_phone }}</span>
                                                </div>
                                            </td>
                                            <td class="px-1">
                                                <div class="d-flex flex-column text-wrap">
                                                    <span class="fw-bolder">{{ item.conveyor_fullname }}</span>
                                                    <span class="font-small-2 text-muted">{{ item.conveyor_loginid }}</span>
                                                </div>
                                            </td>
                                            <td class="px-1">{{ item.conveyor_phone }}</td>
                                            <td class="px-1">{{ displayDate(item.updated, false, true) }}</td>
                                            <td class="px-1">
                                                <button class="btn btn-sm btn-primary p-50" @click="getMovementDetails(item.movement_id)"><i class="feather icon-eye"></i></button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </template>
                        <div v-if="filteredMovementData.length == 0 && searchState == true">
                            <div class="text-center mt-2 alert p-2"><small>No Search Match/ Facility With Issue/Inbound</small></div>
                        </div>
                    </div>
                </div>
                <div class="mb-50"></div>
            </div>

            <!-- Shipment List Modal -->
            <div class="modal fade" id="movementModal" tabindex="-1" role="dialog" aria-hidden="false" data-backdrop="static" data-keyboard="false">
                <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
                    <div class="modal-content" id="createMovementForm">
                        <div class="modal-header">
                            <h5 class="modal-title">Shipment List</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        </div>
                        <div class="modal-body">
                            <div class="table-responsive">
                                <table class="table mt-2">
                                    <thead>
                                        <tr>
                                            <th class="px-25">#</th>
                                            <th class="px-25">Shipment No.</th>
                                            <th class="px-25">Origin</th>
                                            <th class="px-25">Destination</th>
                                            <th class="px-25">Quantity</th>
                                            <th class="px-25">Status</th>
                                            <th class="px-25"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="(item, index) in movementItemDetails" :key="item.shipment_id || index">
                                            <td class="px-25">{{ index + 1 }}</td>
                                            <td class="px-25">
                                                <div class="d-flex flex-column text-wrap">
                                                    <span class="fw-bolder">{{ item.shipment_no }}</span>
                                                    <span class="font-small-2" :class="item.shipment_type=='Forward' ? 'text-primary' : 'text-muted'">{{ item.shipment_type }} <i class="feather" :class="item.shipment_type=='Forward' ? 'icon-arrow-right' : 'icon-arrow-left'"></i></span>
                                                </div>
                                            </td>
                                            <td class="px-25">
                                                <div class="d-flex flex-column text-wrap">
                                                    <span class="fw-bolder">{{ item.origin_string }}</span>
                                                    <span class="font-small-2 text-muted">{{ item.origin_location_type }}</span>
                                                </div>
                                            </td>
                                            <td class="px-25">
                                                <div class="d-flex flex-column text-wrap">
                                                    <span class="fw-bolder">{{ item.destination_string }}</span>
                                                    <span class="font-small-2 text-muted">{{ item.destination_location_type }}</span>
                                                </div>
                                            </td>
                                            <td class="px-25">{{ convertStringNumberToFigures(item.total_value) }}</td>
                                            <td class="px-25"><span class="badge p-50" :class="item.shipment_status=='Processing' ? 'badge-light-success' : 'badge-light-secondary'">{{ item.shipment_status }}</span></td>
                                            <td class="px-25">
                                                <button type="button" @click="getShipmentItem(item.shipment_id, { preview: true })" class="btn btn-sm btn-primary p-50"><i class="feather icon-eye"></i></button>
                                                <button type="button" @click="getShipmentItem(item.shipment_id, { preview: false })" class="btn btn-sm btn-primary ml-75 p-50"><i class="feather icon-printer"></i></button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>

            <iframe id="pdfPreview" style="width:100%; height:600px; display:none;"></iframe>
        </div>
    `,
};

useApp({
  template: `
        <div>
            <div v-show="appState.pageState.page == 'table'"></div>
            <div v-show="appState.pageState.page == 'movement-page'"><page-movement-page/></div>
        </div>
    `,
  setup() {
    return { appState };
  },
})
  .component("page-movement-page", PageMovementPage)
  .mount("#app");
