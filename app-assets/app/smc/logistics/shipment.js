/**
 * SMC / Logistics / Shipment — Vue 3 Composition API in place.
 * Two components — page-shipment-page and select2-dropdown.
 *
 * Pick a visit → load shipments (qid=1133/1134). Multi-select pending
 * shipments and group them into a Movement (qid=1135). Per-shipment
 * Issue & Receipt Voucher PDF (qid=1136 + pdfMake).
 */

const { ref, reactive, computed, watch, onMounted, onBeforeUnmount, nextTick } =
  Vue;
const { useApp, useFormat, bus, safeMessage } = window.utils;

const appState = Vue.reactive({
  pageState: { page: "shipment-page", title: "" },
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
  shipmentData: [],
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

/* ------------------------------------------------------------------ */
/* select2-dropdown                                                     */
/* ------------------------------------------------------------------ */
const Select2Dropdown = {
  props: {
    modelValue: { type: [String, Number], default: null },
    options: { type: Array, required: true },
    placeholder: { type: String, default: "Select an option" },
    clearable: { type: Boolean, default: true },
  },
  emits: ["update:modelValue"],
  setup(props, ctx) {
    const selectEl = ref(null);

    const initialize = () => {
      if (!selectEl.value) return;
      var $sel = $(selectEl.value);
      $sel.wrap('<div class="position-relative"></div>');
      $sel
        .select2({
          data: props.options,
          placeholder: props.placeholder,
          dropdownAutoWidth: true,
          allowClear: props.clearable,
          width: "100%",
          dropdownParent: $($sel[0]).parent(),
        })
        .val(props.modelValue)
        .trigger("change")
        .on("change", () => {
          ctx.emit("update:modelValue", $sel.val());
        });
      nextTick(() => {
        $sel
          .next(".select2-container")
          .find(".select2-selection__arrow")
          .html('<i class="feather icon-chevron-down"></i>');
      });
    };
    const destroy = () => {
      try {
        if (!selectEl.value) return;
        var $sel = $(selectEl.value);
        $sel.off().select2("destroy");
      } catch (e) {
        /* swallow */
      }
    };

    onMounted(() => {
      initialize();
    });
    onBeforeUnmount(() => {
      destroy();
    });

    watch(
      () => props.modelValue,
      (newVal) => {
        if (!selectEl.value) return;
        var $sel = $(selectEl.value);
        if ($sel.val() !== newVal) $sel.val(newVal).trigger("change");
        var $selection = $sel.next(".select2").find(".select2-selection");
        if (newVal) $selection.removeClass("is-invalid");
        else $selection.addClass("is-invalid");
      },
    );
    watch(
      () => props.options,
      () => {
        destroy();
        nextTick(initialize);
      },
      { deep: true },
    );

    return { selectEl };
  },
  template: `
        <select class="form-control" ref="selectEl">
            <option :value="null">{{ placeholder }}</option>
        </select>
    `,
};

/* ------------------------------------------------------------------ */
/* page-shipment-page                                                   */
/* ------------------------------------------------------------------ */
const PageShipmentPage = {
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
    const selectedShipment = ref("");

    const closeMovementModal = () => {
      $("#movementModal").modal("hide");
    };
    const resetMovementForm = () => {
      closeMovementModal();
      movementForm.movementTitle = "";
      movementForm.shipmentListIds = [];
      movementForm.conveyorId = "";
      movementForm.transporterId = "";
      loadShipment();
      selectToggle();
    };
    const removeSelectedMovement = (item) => {
      item.pick = false;
      if (selectedID.value.length === 0) selectToggle();
      movementForm.shipmentListIds = selectedID.value;
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

    const initializeMovement = () => {
      if (totalCheckedBox.value === false) {
        alert.Error(
          "ERROR",
          "Please select at least one Shipment item to create movement.",
        );
        return;
      }
      movementForm.shipmentListIds = selectedID.value;
      movementForm.periodId = appState.currentPeriodId;
      $("#movementModal").modal("show");
    };
    const validateMovementForm = () => {
      // jQuery-validate-driven validation (preserved for compatibility).
      try {
        return $("#createMovementForm")
          .validate({
            rules: {
              movementTitle: { required: true, minlength: 2, maxlength: 255 },
              transporter: { required: true },
              conveyor: { required: true },
            },
          })
          .form();
      } catch (e) {
        return true;
      }
    };
    const createMovement = () => {
      if (!validateMovementForm()) {
        alert.Error("*Required Fields", "All fields are required");
        return;
      }
      var data = {
        periodId: movementForm.periodId,
        transporterId: parseInt(movementForm.transporterId),
        title: movementForm.movementTitle,
        shipmentIds: movementForm.shipmentListIds,
        conveyorId: parseInt(movementForm.conveyorId),
      };
      overlay.show();
      axios
        .post(common.DataService + "?qid=1135", JSON.stringify(data))
        .then((response) => {
          overlay.hide();
          if (response.data.result_code === 200) {
            alert.Success("SUCCESS", response.data.message);
            resetMovementForm();
          } else {
            alert.Error("ERROR", response.data.message);
          }
        })
        .catch((error) => {
          overlay.hide();
          alert.Error("ERROR", safeMessage(error));
        });
    };

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
          var d = response.data && response.data.data && response.data.data[0];
          appState.receiptHeader = (d && d.logo) || "";
          overlay.hide();
        })
        .catch((error) => {
          overlay.hide();
          alert.Error("ERROR", safeMessage(error));
        });
    };

    const loadShipment = async () => {
      if (!appState.currentPeriodId) {
        alert.Error("ERROR", "Please select a visit");
        return;
      }
      var data = { periodid: appState.currentPeriodId };
      overlay.show();
      try {
        var res1133 = await axios.post(
          common.DataService + "?qid=1133",
          JSON.stringify(data),
        );
        if (res1133.data.result_code !== 200) {
          alert.Error("ERROR", res1133.data.message);
          return;
        }
        var res1134 = await axios.post(
          common.DataService + "?qid=1134",
          JSON.stringify(data),
        );
        if (res1134.data.result_code !== 200) {
          alert.Error("ERROR", res1134.data.message);
          return;
        }
        appState.shipmentData = res1134.data.data || [];
        searchState.value = true;
      } catch (error) {
        alert.Error("ERROR", safeMessage(error));
      } finally {
        overlay.hide();
      }
    };

    const selectAll = () => {
      (filteredStockData.value || []).forEach((item) => {
        if (item.shipment_status === "Pending") item.pick = true;
      });
    };
    const uncheckAll = () => {
      (filteredStockData.value || []).forEach((item) => {
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
      appState.shipmentData = [];
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
    watch(searchText, (val) => {
      debouncedSearch(val);
    });
    const toggleMovementType = () => {
      movementType.value =
        movementType.value === "Forward" ? "Reverse" : "Forward";
    };
    const filterUsingStatus = (keyword) => {
      appState.statusFilter = keyword;
    };

    const filteredStockData = computed(() => {
      var data = appState.shipmentData || [];
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
            (item.shipment_no &&
              item.shipment_no.toLowerCase().includes(keyword)) ||
            (item.shipment_status &&
              item.shipment_status.toLowerCase().includes(keyword)) ||
            (item.destination_location_type &&
              item.destination_location_type.toLowerCase().includes(keyword)) ||
            (item.origin_string &&
              item.origin_string.toLowerCase().includes(keyword)) ||
            (item.destination_string &&
              item.destination_string.toLowerCase().includes(keyword)),
        );
      }
      return data;
    });
    const conveyorOptions = computed(() =>
      (conveyorData.value || []).map((c) => ({
        id: c.userid,
        text: c.fullname + " (" + c.loginid + ")",
      })),
    );
    const transporterOptions = computed(() =>
      (transporterData.value || []).map((t) => ({
        id: t.transporter_id,
        text: t.transporter + " (" + t.poc + ")",
      })),
    );
    const selectedItems = computed(() =>
      (filteredStockData.value || []).filter((i) => i.pick),
    );
    const selectedID = computed(() =>
      (filteredStockData.value || [])
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

    const getShipmentItem = (
      shipmentId,
      destination,
      origin_string,
      shipmentNo,
      shipmentType,
      status,
      isOpen,
    ) => {
      overlay.show();
      axios
        .post(
          common.DataService + "?qid=1136",
          JSON.stringify({ shipmentId: shipmentId }),
        )
        .then((response) => {
          if (response.data.result_code === 200) {
            generateShipmentPDF(
              response.data.data,
              destination,
              origin_string,
              shipmentNo,
              shipmentType,
              status,
              isOpen,
            );
          } else {
            alert.Error("ERROR", response.data.message);
          }
          overlay.hide();
        })
        .catch((error) => {
          overlay.hide();
          alert.Error("ERROR", safeMessage(error));
        });
    };
    const generateShipmentPDF = (
      data,
      destination,
      origin_string,
      shipmentNo,
      shipmentType,
      status,
      isOpen,
    ) => {
      if (!data || data.length === 0) {
        alert.Error("ERROR", "No data available for PDF generation.");
        return;
      }
      if (typeof pdfMake === "undefined") return;
      var todayDate = displayDateLong(
        new Date().toLocaleDateString(),
        true,
        true,
      );
      var logoDataURL = "data:image/svg+xml;base64," + appState.receiptHeader;
      var docDefinition = {
        pageSize: "A4",
        pageOrientation: "landscape",
        pageMargins: [40, 60, 40, 60],
        images: { logo: logoDataURL },
        content: [
          {
            text: "FEDERAL MINISTRY OF HEALTH",
            fontSize: 12,
            bold: true,
            alignment: "center",
            margin: [0, 0, 0, 5],
          },
          {
            text: "NATIONAL MALARIA ELIMINATION PROGRAMME",
            fontSize: 12,
            alignment: "center",
            margin: [0, 0, 0, 5],
          },
          {
            text: "ISSUE AND RECEIPT VOUCHER",
            fontSize: 12,
            bold: true,
            alignment: "center",
            margin: [0, 0, 0, 30],
          },
          {
            columns: [
              {
                stack: [
                  {
                    text: [
                      { text: "Shipment No: ", bold: true },
                      { text: shipmentNo || "N/A" },
                    ],
                  },
                  {
                    text: [
                      { text: "Shipment Type: ", bold: true },
                      { text: shipmentType || "" },
                    ],
                  },
                  {
                    text: [
                      { text: "Status: ", bold: true },
                      { text: status || "" },
                    ],
                  },
                  {
                    text: [
                      { text: "Created: ", bold: true },
                      { text: todayDate },
                    ],
                  },
                ],
              },
              {
                stack: [
                  {
                    text: [
                      { text: "Origin: ", bold: true },
                      { text: origin_string || "N/A" },
                    ],
                  },
                  {
                    text: [
                      { text: "Destination: ", bold: true },
                      { text: destination || "N/A" },
                    ],
                  },
                ],
              },
            ],
            margin: [0, 0, 0, 20],
          },
          { text: "Shipment Items", bold: true, margin: [0, 0, 0, 10] },
          {
            table: {
              headerRows: 1,
              widths: ["auto", "*", "*", "*", "*", "*"],
              body: [
                [
                  "#",
                  "Product Name",
                  "Product Code",
                  "Batch",
                  "Expiry",
                  "Quantity",
                ],
              ].concat(
                data.map((item, i) => [
                  i + 1,
                  item.product_name,
                  item.product_code,
                  item.batch,
                  displayDateLong(item.expiry, false, false),
                  item.secondary_qty,
                ]),
              ),
            },
            layout: "lightHorizontalLines",
            margin: [0, 0, 0, 20],
          },
        ],
      };
      try {
        pdfMake
          .createPdf(docDefinition)
          .download(destination + "_Stock_details_" + todayDate + ".pdf");
      } catch (e) {
        console.error("[smc/logistics/shipment] PDF render failed:", e);
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
      selectedShipment,
      filteredStockData,
      conveyorOptions,
      transporterOptions,
      selectedItems,
      selectedID,
      totalCheckedBox,
      closeMovementModal,
      resetMovementForm,
      removeSelectedMovement,
      getTransporterAndConveyor,
      initializeMovement,
      createMovement,
      getAllPeriodLists,
      getReceiptHeader,
      loadShipment,
      selectAll,
      uncheckAll,
      selectToggle,
      checkedBg,
      resetCheckTable,
      resetForm,
      toggleMovementType,
      filterUsingStatus,
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
                        <li class="breadcrumb-item active">Shipment Management.</li>
                    </ol>
                    <span id="total-selected"></span>
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
                                    <div @click="toggleMovementType" class="badge px-1 py-75 bg-label-primary text-white" style="cursor: pointer;">
                                        <span class="font-weight-normal">{{ movementType }}</span>
                                        <i class="feather" :class="movementType=='Forward' ? 'icon-arrow-right' : 'icon-arrow-left'"></i>
                                    </div>
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
                                            <button type="button" class="btn btn-outline-secondary btn-block" @click="resetForm()">Reset <i class="feather icon-corner-up-left"></i></button>
                                        </div>
                                        <div :class="appState.currentPeriodId !== '' ? 'col-xs-6' : 'col-xs-12'">
                                            <button type="button" class="btn btn-outline-primary btn-block" @click="loadShipment()">Load Shipment <i class="feather icon-send"></i></button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-datatable">
                        <template v-if="searchState == true">
                            <div class="d-flex flex-wrap align-items-center justify-content-between px-0">
                                <div class="d-flex flex-wrap col-sm-12 col-12 col-md-6 col-lg-6 py-75">
                                    <div class="btn-group btn-group-toggle mr-1 mb-25" data-toggle="buttons">
                                        <label class="btn btn-primary active" @click="filterUsingStatus('')"><input type="radio" name="check_options" checked /> All</label>
                                        <label class="btn btn-primary" @click="filterUsingStatus('Pending')"><input type="radio" name="check_options" /> Pending</label>
                                        <label class="btn btn-primary" @click="filterUsingStatus('Processing')"><input type="radio" name="check_options" /> Processing</label>
                                    </div>
                                </div>
                                <div class="col-12 col-sm-12 col-md-6 col-lg-6 py-75">
                                    <div class="d-flex flex-nowrap align-items-center">
                                        <input type="text" v-model="searchText" placeholder="Search" class="form-control mr-1 mb-25" />
                                        <button :disabled="!totalCheckedBox" type="button" @click="initializeMovement()" class="btn btn-primary px-1 mb-25 waves-button" style="white-space: nowrap;">Create Movement <i class="feather icon-plus-circle ms-1"></i></button>
                                    </div>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th class="px-25">
                                                <div class="custom-control custom-checkbox checkbox">
                                                    <input type="checkbox" class="custom-control-input" :checked="checkToggle" @change="selectToggle()" id="all-check" />
                                                    <label class="custom-control-label" for="all-check"></label>
                                                </div>
                                            </th>
                                            <th class="px-1">Origin</th>
                                            <th class="px-1">Destination</th>
                                            <th class="px-1">Status</th>
                                            <th class="px-1">Shipment</th>
                                            <th class="px-1">Unit Qty.</th>
                                            <th class="px-1">Qty.</th>
                                            <th class="px-1">Rate</th>
                                            <th class="px-1"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="(item, index) in filteredStockData" :key="item.shipment_id || index" :class="checkedBg(item.pick)">
                                            <td class="px-25">
                                                <div class="custom-control custom-checkbox checkbox">
                                                    <input type="checkbox" class="custom-control-input" :id="item.shipment_id" :disabled="item.shipment_status==='Processing'" v-model="item.pick" />
                                                    <label class="custom-control-label" :for="item.shipment_id"></label>
                                                </div>
                                            </td>
                                            <td class="px-1">
                                                <div class="d-flex flex-column text-wrap">
                                                    <span class="fw-bolder">{{ item.origin_string }}</span>
                                                    <span class="font-small-2 text-muted">{{ item.origin_location_type }}</span>
                                                </div>
                                            </td>
                                            <td class="px-1">
                                                <div class="d-flex flex-column text-wrap">
                                                    <span class="fw-bolder">{{ item.destination_string }}</span>
                                                    <span class="font-small-2 text-muted">{{ item.destination_location_type }}</span>
                                                </div>
                                            </td>
                                            <td class="px-1"><span class="badge p-50" :class="item.shipment_status=='Processing' ? 'badge-light-success' : 'badge-light-secondary'">{{ item.shipment_status }}</span></td>
                                            <td class="px-1">
                                                <div class="d-flex flex-column text-wrap">
                                                    <span class="fw-bolder">{{ item.shipment_no }}</span>
                                                    <span class="font-small-2" :class="movementType=='Forward' ? 'text-primary' : 'text-muted'">{{ item.shipment_type }} <i class="feather" :class="movementType=='Forward' ? 'icon-arrow-right' : 'icon-arrow-left'"></i></span>
                                                </div>
                                            </td>
                                            <td class="px-1">{{ convertStringNumberToFigures(item.total_qty) }} ({{ item.unit }})</td>
                                            <td class="px-1">{{ convertStringNumberToFigures(item.total_value) }}</td>
                                            <td class="px-1">{{ convertStringNumberToFigures(item.rate) }}</td>
                                            <td class="px-1"><button class="btn btn-sm btn-primary p-50" @click="getShipmentItem(item.shipment_id, item.destination_string, item.origin_string, item.shipment_no, item.shipment_type, item.shipment_status, true)"><i class="feather icon-printer"></i></button></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </template>
                        <div v-if="filteredStockData.length == 0 && searchState == true">
                            <div class="text-center mt-2 alert p-2"><small>No Search Match/ Facility With Issue/Inbound</small></div>
                        </div>
                    </div>
                </div>
                <div class="mb-50"></div>
            </div>

            <!-- Create Movement Modal -->
            <div class="modal fade" id="movementModal" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false">
                <div class="modal-dialog modal-lg" role="document">
                    <form class="modal-content" @submit.prevent="createMovement()" id="createMovementForm">
                        <div class="modal-header">
                            <h5 class="modal-title">Create Movement</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        </div>
                        <div class="modal-body">
                            <div class="border border-lighten-1 p-1 rounded mb-1">
                                <div class="row">
                                    <div class="form-group col-12">
                                        <label>*Title</label>
                                        <input class="form-control" name="movementTitle" placeholder="Title" v-model="movementForm.movementTitle" />
                                    </div>
                                    <div class="form-group col-6">
                                        <label>*Transporter</label>
                                        <select2-dropdown name="transporter" :options="transporterOptions" v-model="movementForm.transporterId" placeholder="Choose Transporter" :clearable="true"></select2-dropdown>
                                    </div>
                                    <div class="form-group col-6">
                                        <label>*Conveyor</label>
                                        <select2-dropdown name="conveyor" :options="conveyorOptions" v-model="movementForm.conveyorId" placeholder="Choose Conveyor" :clearable="true"></select2-dropdown>
                                    </div>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover mt-1">
                                    <thead>
                                        <tr>
                                            <th class="px-25">#</th>
                                            <th class="px-25">Destination</th>
                                            <th class="px-25">Shipment</th>
                                            <th class="px-25">Unit Qty.</th>
                                            <th class="px-25">Qty.</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="(item, index) in selectedItems" :key="item.shipment_id">
                                            <td class="px-25">
                                                <div class="custom-control custom-checkbox checkbox">
                                                    <input type="checkbox" class="custom-control-input" :id="'cb-' + item.shipment_id" :checked="item.pick" @click.prevent />
                                                    <label class="custom-control-label" :for="'cb-' + item.shipment_id" @dblclick.stop.prevent="removeSelectedMovement(item)"></label>
                                                </div>
                                            </td>
                                            <td class="px-25">
                                                <div class="d-flex flex-column text-wrap">
                                                    <span class="fw-bolder">{{ item.destination_string }}</span>
                                                    <span class="font-small-2 text-muted">{{ item.destination_location_type }}</span>
                                                </div>
                                            </td>
                                            <td class="px-25">
                                                <div class="d-flex flex-column text-wrap">
                                                    <span class="fw-bolder">{{ item.shipment_no }}</span>
                                                    <span class="font-small-2" :class="movementType=='Forward' ? 'text-primary' : 'text-muted'">{{ item.shipment_type }} <i class="feather" :class="movementType=='Forward' ? 'icon-arrow-right' : 'icon-arrow-left'"></i></span>
                                                </div>
                                            </td>
                                            <td class="px-25">{{ convertStringNumberToFigures(item.total_qty) }} ({{ item.unit }})</td>
                                            <td class="px-25">{{ convertStringNumberToFigures(item.total_value) }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary mr-1" data-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Create Movement</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    `,
};

useApp({
  template: `
        <div>
            <div v-show="appState.pageState.page == 'table'"></div>
            <div v-show="appState.pageState.page == 'shipment-page'"><page-shipment-page/></div>
        </div>
    `,
  setup() {
    return { appState };
  },
})
  .component("select2-dropdown", Select2Dropdown)
  .component("page-shipment-page", PageShipmentPage)
  .mount("#app");
