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
  page: "shipment-page",
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
  shipmentData: [],

  periodData: [],
  currentPeriodId: "",
  currentProductCode: "",
  receiptHeader: "",
  filterText: "",
  statusFilter: "",
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

Vue.component("page-shipment-page", {
  mixins: [eventBusMixin],
  data: function () {
    return {
      appState,
      searchState: false,
      searchText: "",
      movementType: "Forward",
      checkToggle: false,
      conveyorData: [],
      transporterData: [],
      movementForm: {
        periodId: "",
        transporterId: "",
        movementTitle: "",
        shipmentListIds: [],
        conveyorId: "",
        userid: appState.userId,
      },
      selectedShipment: "",
    };
  },
  mounted() {
    /*  Manages events Listening    */
    this.getAllPeriodLists();
    this.getReceiptHeader();
    this.getTransporterAndConveyor();

    /**
     * @description
     * This function is called when the component is mounted.
     * It loads the shipment data for test purposes.
     */
    // appState.currentPeriodId = 1;
    // this.loadShipment();
    // $("#movementModal").modal("show");

    // this ends the event listener

    EventBus.$on("g-event-reset-form", this.resetForm);
  },
  methods: {
    resetMovementForm() {
      this.closeMovementModal();
      this.movementForm.movementTitle = "";
      this.movementForm.shipmentListIds = [];
      this.movementForm.conveyorId = "";
      this.movementForm.transporterId = "";

      this.loadShipment();
      this.selectToggle();
    },
    closeMovementModal() {
      $("#movementModal").modal("hide");
    },
    removeSelectedMovement(item) {
      item.pick = false;

      const hasRemainingSelection = this.selectedID.length > 0;

      if (!hasRemainingSelection) {
        this.selectToggle();
      }

      this.movementForm.shipmentListIds = this.selectedID;
    },
    async getTransporterAndConveyor() {
      const url = common.DataService;
      const endpoints = [`${url}?qid=gen014`, `${url}?qid=gen015`];

      try {
        overlay.show();

        const [transporter, conveyor] = await Promise.all(
          endpoints.map((endpoint) => axios.get(endpoint))
        );

        // Use transporter (qid=gen014) for tableData and pagination
        this.transporterData = transporter.data.data;

        // Use conveyor (qid=gen015) as needed
        this.conveyorData = conveyor.data.data;
      } catch (error) {
        console.error("Error loading data:", error);
        alert.Error(
          "ERROR",
          error.message || "An error occurred, kindly refresh"
        );
      } finally {
        overlay.hide();
      }
    },
    initializeMovement() {
      if (this.totalCheckedBox == false) {
        alert.Error(
          "ERROR",
          "Please select at least one Shipment item to create movement."
        );
        return;
      }
      this.movementForm.shipmentListIds = this.selectedID;
      this.movementForm.periodId = appState.currentPeriodId;

      $("#movementModal").modal("show");
    },
    validateMovementForm() {
      const isValid = $("#createMovementForm")
        .validate({
          rules: {
            movementTitle: {
              required: true,
              minlength: 2,
              maxlength: 255,
            },
            transporter: {
              required: true,
            },
            conveyor: {
              required: true,
            },
          },
          messages: {
            movementTitle: {
              required: "Please enter the movement title",
              minlength: "Title must be at least 2 characters",
              maxlength: "Title must be less than 255 characters",
            },
            transporter: {
              required: "Please select Transporter",
            },
            conveyor: {
              required: "Please select Conveyor",
            },
          },
          errorPlacement: function (error, element) {
            // If element is Select2
            if (element.hasClass("select2-hidden-accessible")) {
              error.insertAfter(element.next(".select2")); // Place error after Select2 container
            } else {
              error.insertAfter(element); // Default placement
            }
          },
          highlight: function (element) {
            if ($(element).hasClass("select2-hidden-accessible")) {
              $(element)
                .next(".select2")
                .find(".select2-selection")
                .addClass("is-invalid");
            } else {
              $(element).addClass("is-invalid");
            }
          },
          unhighlight: function (element) {
            if ($(element).hasClass("select2-hidden-accessible")) {
              $(element)
                .next(".select2")
                .find(".select2-selection")
                .removeClass("is-invalid");
            } else {
              $(element).removeClass("is-invalid");
            }
          },
        })
        .form(); // .form() returns true if valid

      return isValid;
    },
    createMovement() {
      if (!this.validateMovementForm()) {
        // Form is invalid
        alert.Error("*Required Fields", "All fields are required");
        return;
      }

      let data = {
        periodId: this.movementForm.periodId,
        transporterId: parseInt(this.movementForm.transporterId),
        title: this.movementForm.movementTitle,
        shipmentIds: this.movementForm.shipmentListIds,
        conveyorId: parseInt(this.movementForm.conveyorId),
      };

      let self = this;
      let url = common.DataService;
      overlay.show();
      axios
        .post(url + "?qid=1135", JSON.stringify(data))
        .then(function (response) {
          overlay.hide();
          if (response.data.result_code === 200) {
            // EventBus.$emit("g-event-refresh-page");
            alert.Success("SUCCESS", response.data.message);
            console.log(response.data.data);

            self.resetMovementForm();
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
    async loadShipment() {
      if (!appState.currentPeriodId) {
        alert.Error("ERROR", "Please select a visit");
        return;
      }

      const url = common.DataService;
      const data = { periodid: appState.currentPeriodId };

      overlay.show();

      try {
        const { data: res1133 } = await axios.post(
          `${url}?qid=1133`,
          JSON.stringify(data)
        );
        if (res1133.result_code !== 200) {
          alert.Error("ERROR", res1133.message);
          return;
        }

        const { data: res1134 } = await axios.post(
          `${url}?qid=1134`,
          JSON.stringify(data)
        );
        if (res1134.result_code !== 200) {
          alert.Error("ERROR", res1134.message);
          return;
        }

        // Success: update state
        appState.shipmentData = res1134.data;
        this.searchState = true;
      } catch (error) {
        alert.Error("ERROR", error);
      } finally {
        overlay.hide();
      }
    },
    selectAll() {
      /*  Manages all check box selection checked */
      if (this.filteredStockData.length > 0) {
        for (let i = 0; i < this.filteredStockData.length; i++) {
          if (this.filteredStockData[i].shipment_status === "Pending") {
            this.filteredStockData[i].pick = true;
          }
        }
      }
    },
    uncheckAll() {
      /*  Manages unchecking of all check box checked */
      if (this.filteredStockData.length > 0) {
        for (let i = 0; i < this.filteredStockData.length; i++) {
          this.filteredStockData[i].pick = false;
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
    resetCheckTable() {
      appState.shipmentData = [];
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
            fontSize: 10,
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
            lineHeight: 1.5,
          },
        },
      };

      pdfMake
        .createPdf(docDefinition)
        .download("Stock_Batch_order_" + todayDate + ".pdf");
    },
    generateShipmentPDF(
      data,
      destination,
      origin_string,
      shipmentNo,
      shipmentType,
      status,
      isOpen
    ) {
      const content = [];
      if (!data || data.length === 0) {
        alert("ERROR: No data available for PDF generation.");
        return;
      }

      let todayDate = this.displayDate(
        new Date().toLocaleDateString(),
        true,
        true
      );
      // Replace this with your actual base64 string (include prefix)
      (logoDataURL = `data:image/svg+xml;base64,${appState.receiptHeader}`), // or PNG/JPEG
        content.push(
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
          /*
          {
            text: "STOCK DETAILS",
            fontSize: 10,
            alignment: "center",
            margin: [0, 0, 0, 20],
          },
          */

          {
            columns: [
              {
                stack: [
                  {
                    text: [
                      { text: "Shipment No: ", bold: true },
                      { text: shipmentNo || "N/A" },
                    ],
                    style: "tableBodyFont8",
                  },
                  {
                    text: [
                      { text: "Shipment Type: ", bold: true },
                      { text: shipmentType },
                    ],
                    style: "tableBodyFont8",
                  },
                  {
                    text: [{ text: "Status: ", bold: true }, { text: status }],
                    style: "tableBodyFont8",
                  },
                  {
                    text: [
                      { text: "Created: ", bold: true },
                      { text: todayDate || "N/A" },
                    ],
                    style: "tableBodyFont8",
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
                    style: "tableBodyFont8",
                  },
                  {
                    text: [
                      { text: "Destination: ", bold: true },
                      { text: destination || "N/A" },
                    ],
                    style: "tableBodyFont8",
                  },
                ],
              },
            ],
            margin: [0, 0, 0, 20],
          },
          {
            text: "Shipment Items",
            style: "subheader",
            margin: [0, 0, 0, 10],
          },

          {
            table: {
              headerRows: 1,
              widths: ["auto", "*", "*", "*", "*", "*"],
              body: [
                [
                  { text: "#", style: "tableHeader" },
                  { text: "Product Name", style: "tableHeader" },
                  { text: "Product Code", style: "tableHeader" },
                  { text: "Batch", style: "tableHeader" },
                  { text: "Expiry", style: "tableHeader" },
                  { text: "Quantity", style: "tableHeader" },
                ],
                ...data.map((item, i) => [
                  { text: i + 1, style: "tableBodyFont8" },
                  { text: item.product_name, style: "tableBodyFont8" },
                  { text: item.product_code, style: "tableBodyFont8" },
                  { text: item.batch, style: "tableBodyFont8" },
                  {
                    text: this.displayDate(item.expiry, false, false),
                    style: "tableBodyFont8",
                  },
                  { text: item.secondary_qty, style: "tableBodyFont8" },
                ]),
              ],
            },
            layout: "lightHorizontalLines",
            margin: [0, 0, 0, 20],
          }
        );

      const docDefinition = {
        pageSize: "A4",
        pageOrientation: "landscape",
        pageMargins: [40, 60, 40, 60],
        images: {
          logo: logoDataURL,
        },
        header: function (currentPage, pageCount) {
          return {
            columns: [
              {
                image: "logo",
                width: 100,
                margin: [40, 10, 0, 0],
              },
              {
                text: `Page ${currentPage} of ${pageCount}`,
                alignment: "right",
                fontSize: 9,
                margin: [0, 25, 40, 0],
              },
            ],
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
              { text: todayDate, alignment: "center", fontSize: 9 },
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
            fontSize: 14,
            bold: true,
          },
          subheader: {
            fontSize: 10,
            bold: true,
          },
          tableHeader: {
            bold: true,
            fontSize: 10,
            fillColor: "#eeeeee",
          },
          tableBodyFont8: {
            fontSize: 10,
            lineHeight: 1.5,
          },
        },
      };

      /*
      if (!isOpen) {
        // Preview in iframe
        const iframe = document.getElementById("pdfPreview");
        // Hide iframe while generating PDF
        iframe.style.display = "none";
        iframe.src = "";
        if (!iframe) {
          alert(
            "PDF preview iframe not found. Please add <iframe id='pdfPreview'></iframe> in your HTML."
          );
          return;
        }
        iframe.style.display = "none"; // hide while loading
        pdfMake.createPdf(docDefinition).getBlob((blob) => {
          const url = URL.createObjectURL(blob);
          iframe.src = url;
          iframe.style.display = "block"; // show iframe after ready
        });
      } else {
      */
      pdfMake
        .createPdf(docDefinition)
        .download(destination + "_Stock_details_" + todayDate + ".pdf");
      /** }*/
    },
    debounce(func, delay) {
      let timeout;
      return function (...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(this, args), delay);
      };
    },
    toggleMovementType() {
      this.movementType =
        this.movementType === "Forward" ? "Reverse" : "Forward";
    },
    filterUsingStatus(keyword) {
      appState.statusFilter = keyword;
    },
    getShipmentItem(
      shipmentId,
      destination,
      origin_string,
      shipmentNo,
      shipmentType,
      status,
      isOpen
    ) {
      const url = common.DataService;
      const data = { shipmentId: shipmentId };
      overlay.show();
      axios
        .post(url + "?qid=1136", JSON.stringify(data))
        .then((response) => {
          if (response.data.result_code === 200) {
            this.generateShipmentPDF(
              response.data.data,
              destination,
              origin_string,
              shipmentNo,
              shipmentType,
              status,
              isOpen
            );
          } else {
            alert.Error("ERROR", response.data.message);
          }
          overlay.hide();
        })
        .catch((error) => {
          overlay.hide();
          alert.Error("ERROR", error);
        });
    },
  },
  created() {
    this.debouncedSearch = this.debounce((val) => {
      appState.filterText = val;
    }, 300); // 300ms is ideal for typing

    this.debouncedStatusFilter = this.debounce((val) => {
      appState.statusFilter = val;
    }, 1);
  },
  watch: {
    searchText(val) {
      this.debouncedSearch(val);
    },
    statusFilter(val) {
      this.debouncedStatusFilter(val);
    },
    selectedItems(newVal) {
      if (this.filteredStockData?.length && newVal.length === 0) {
        this.closeMovementModal();
      }
    },
  },
  computed: {
    filteredStockData() {
      let data = appState.shipmentData || [];
      const keyword = appState.filterText?.toLowerCase().trim();
      const status = appState.statusFilter?.toLowerCase().trim();
      if (status) {
        data = data.filter((item) =>
          item.shipment_status?.toLowerCase().trim().includes(status)
        );
      }

      if (keyword) {
        data = data.filter((item) => {
          return (
            item.shipment_no?.toLowerCase().includes(keyword) ||
            item.shipment_status?.toLowerCase().includes(keyword) ||
            item.destination_location_type?.toLowerCase().includes(keyword) ||
            item.origin_string?.toLowerCase().includes(keyword) ||
            item.destination_string?.toLowerCase().includes(keyword)
          );
        });
      }

      return data;
    },
    groupDataByFacility() {
      const grouped = this.filteredStockData.reduce((acc, item) => {
        const key = `${item.destination_id}||${item.destination_string}`;
        if (!acc[key]) {
          acc[key] = {
            lgaid: item.lgaid,
            destination_string: item.destination_string,
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
    totalCheckedBox() {
      let total = this.selectedItems?.length;
      if (total > 0) {
        document.getElementById(
          "total-selected"
        ).innerHTML = `<span id="selected-counter" class="badge badge-primary btn-icon"><span class="badge badge-success">${total}</span> Item Selected</span>`;
        return true;
      } else {
        document.getElementById("total-selected").replaceChildren();
        return false;
      }
    },
    conveyorOptions() {
      return this.conveyorData.map((c) => ({
        id: c.userid,
        text: `${c.fullname} (${c.loginid})`,
      }));
    },
    transporterOptions() {
      return this.transporterData.map((t) => ({
        id: t.transporter_id,
        text: `${t.transporter} (${t.poc})`,
      }));
    },
    selectedItems() {
      return this.filteredStockData?.filter((item) => item.pick) || [];
    },
    selectedID() {
      if (!this.filteredStockData || this.filteredStockData.length == 0) {
        return [];
      }

      return this.filteredStockData
        .filter((item) => item.pick)
        .map((item) => item.shipment_id);
    },
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
                                  <select class="form-control" id="cms" placeholder="CMS">
                                      <option value="CMS" selected>CMS</option>
                                  </select>
                              </div>
                          </div>

                          <div class="col-md-2 col-sm-12 col-lg-2  justify-content-center align-items-center">

                              <div class="form-group middle-icon d-flex justify-content-center align-items-center">


                                  <div @click="toggleMovementType"
                                      class="badge px-1 py-75 d-flex d-sm-flex d-md-none bg-label-primary text-white justify-content-center align-items-center"
                                      style="cursor: pointer;">
                                      <div class="d-flex flex-row justify-content-center text-center w-100">

                                          <div class="custom-control custom-checkbox w-100">
                                              <input type="checkbox" @click="toggleMovementType"
                                                  class="custom-control-input d-none" id="movementToggle"
                                                  :checked="movementType === 'Forward'" />
                                              <span class="custom-control-label w-100 m-0 font-weight-normal font-small-4"
                                                  for="movementToggle">
                                                  {{ movementType }}
                                              </span>
                                              <i class="feather"
                                                  :class="movementType=='Forward'? 'icon-arrow-down': 'icon-arrow-up'"></i>
                                          </div>

                                      </div>
                                  </div>

                                  <div @click="toggleMovementType"
                                      class="badge px-1 py-75 d-none d-sm-none d-md-flex bg-label-primary text-white justify-content-center align-items-center"
                                      style="cursor: pointer;">
                                      <div class="d-flex flex-row justify-content-center text-center w-100">

                                          <div class="custom-control custom-checkbox w-100">
                                              <input type="checkbox" @click="toggleMovementType"
                                                  class="custom-control-input d-none" id="movementToggle"
                                                  :checked="movementType === 'Forward'" />

                                              <span class="custom-control-label w-100 m-0 font-weight-normal font-small-4"
                                                  for="movementToggle">
                                                  {{ movementType }}
                                              </span>
                                              <i class="feather"
                                                  :class="movementType=='Forward'? 'icon-arrow-right': 'icon-arrow-left'"></i>
                                          </div>

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
                                          <button type="button" class="btn btn-outline-secondary btn-block"
                                              @click="resetForm()">
                                              Reset <i class="feather icon-corner-up-left"></i>
                                          </button>
                                      </div>
                                      <div :class="appState.currentPeriodId !== '' ? 'col-xs-6' : 'col-xs-12'">
                                          <button type="button" class="btn btn-outline-primary btn-block"
                                              @click="loadShipment()">
                                              Load Shipment <i class="feather icon-send"></i>
                                          </button>
                                      </div>
                                  </div>
                              </div>
                          </div>

                      </div>
                  </div>

              </div>

              <div class="card">
                  <div class="card-datatable">
                      <template v-if="this.searchState ==true">

                          <div class="d-flex flex-wrap align-items-center justify-content-between px-0">
                            <!-- Toggle Buttons and Print -->
                            <div class="d-flex flex-wrap col-sm-12 col-12 col-md-6 col-lg-6 py-75">
                                <div class="btn-group btn-group-toggle mr-1 mb-25" data-toggle="buttons">
                                    <label class="btn btn-primary active" @click="filterUsingStatus('')">
                                      <input type="radio" name="check_options" id="option1" checked /> All
                                    </label>
                                    <label class="btn btn-primary" @click="filterUsingStatus('Pending')">
                                      <input type="radio" name="check_options" id="option2" /> Pending
                                    </label>
                                    <label class="btn btn-primary" @click="filterUsingStatus('Processing')">
                                      <input type="radio" name="check_options" id="option3" /> Processing
                                    </label>
                                </div>
                                <button type="button"
                                  class="btn btn-outline-secondary border mb-25 waves-button px-sm-1"
                                  @click="generatePDF()">
                                  <i class="feather icon-printer"></i>
                                   <span class="d-none d-md-inline d-lg-inline-flex"> Print</span>
                                </button>
                            </div>

                            <!-- Search -->
                            <div class="col-12 col-sm-12 col-md-6 col-lg-6 py-75">
                              <div class="d-flex flex-nowrap align-items-center">
                                <!-- Search Input -->
                                <input type="text" v-model="searchText" placeholder="Search"
                                  class="form-control mr-1 mb-25" style="min-width: 0;" />

                                <!-- Create Movement Button -->
                                <button :disabled="!totalCheckedBox" type="button" @click="initializeMovement()" id="create-movement-btn"
                                  class="btn btn-primary px-1 mb-25 waves-button waves-float waves-light"
                                  style="white-space: nowrap;">
                                  Create Movement <i class="feather icon-plus-circle ms-1"></i>
                                </button>
                              </div>
                            </div>


                           
                          </div>


           

                          <div class="table-responsive">

                              <table class="table table-hover">
                                  <thead>
                                      <tr>
                                          <th class="px-25">
                                              <div class="custom-control custom-checkbox checkbox">
                                                  <input type="checkbox" class="custom-control-input" :checked="checkToggle"
                                                      @change="selectToggle()" id="all-check" />
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
                                      <tr v-for="(item, index) in filteredStockData" :class="checkedBg(item.pick)">
                                          <td class="px-25">
                                              <div class="custom-control custom-checkbox checkbox">
                                                  <input type="checkbox" class="custom-control-input" :id="item.shipment_id"
                                                      :disabled="item.shipment_status==='Processing'" v-model="item.pick" />
                                                  <label class="custom-control-label" :for="item.shipment_id"></label>
                                              </div>
                                          </td>
                                          <td class="px-1">
                                              <div class="d-flex justify-content-left align-items-center">
                                                  <div class="d-flex flex-column">
                                                      <span class="user_name text-wrap text-body">
                                                          <span class="fw-bolder">{{ item.origin_string }}</span>
                                                      </span>
                                                      <span class="font-small-2 text-muted">{{ item.origin_location_type}}</span>
                                                  </div>
                                              </div>
                                          </td>
                                          <td class="px-1">
                                              <div class="d-flex justify-content-left align-items-center">
                                                  <div class="d-flex flex-column text-wrap">
                                                      <span class="user_name text-wrap text-body">
                                                          <span class="fw-bolder">{{ item.destination_string }}</span>
                                                      </span>
                                                      <span class="font-small-2 text-muted">{{ item.destination_location_type}}</span>
                                                  </div>
                                              </div>
                                          </td>
                                          <td class="px-1">
                                              
                                              <span class="badge p-50" :class="item.shipment_status=='Processing'?  'badge-light-success': ' badge-light-secondary'">{{item.shipment_status}}</span>
                                          </td>
                                          <td class="px-1">
                                              <div class="d-flex justify-content-left align-items-center">
                                                  <div class="d-flex flex-column text-wrap">
                                                      <span class="user_name text-wrap text-body">
                                                          <span class="fw-bolder">{{ item.shipment_no }}</span>
                                                      </span>
                                                      <span class="font-small-2" :class="movementType=='Forward'? 'text-primary': 'text-muted'">
                                                          {{ item.shipment_type }} <i class="feather" :class="movementType=='Forward'? 'icon-arrow-right': 'icon-arrow-left'"></i>
                                                      </span>
                                                  </div>
                                              </div>
                                          </td>
                                          <td class="px-1">{{convertStringNumberToFigures(item.total_qty) }} ({{item.unit}})
                                          </td>
                                          <td class="px-1">{{convertStringNumberToFigures(item.total_value)}}</td>
                                          <td class="px-1">{{convertStringNumberToFigures(item.rate)}}</td>
                                          <td class="px-1">
                                              <button class="btn btn-sm btn-primary p-50" @click="getShipmentItem(item.shipment_id, item.destination_string, item.origin_string, item.shipment_no, item.shipment_type, item.shipment_status, true)"><i class="feather icon-printer"></i></button>
                                          </td>
                                      </tr>

                                  </tbody>

                              </table>
                          </div>

                      </template>
                      <div v-if="filteredStockData.length == 0 && searchState == true">
                          <div class="text-center mt-2 alert p-2">
                              <small> No Search Match/ Facility With Issue/Inbound</small>
                          </div>
                      </div>
                  </div>
              </div>


              <div class="mb-50"></div>

          </div>


          <!-- Modal : Start-->
          <div class="modal fade" id="movementModal" tabindex="-1" role="dialog" aria-hidden="false"
          data-backdrop="static" data-keyboard="false">
              <div class="modal-dialog modal-lg" role="document">
                  <form class="modal-content" @submit.prevent="createMovement()" id="createMovementForm">
                      <div class="modal-header">
                          <h5 class="modal-title" id="movementModalTitle">Create Movement</h5>
                          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                              <span aria-hidden="true">&times;</span>
                          </button>
                      </div>
                      <div class="modal-body">
                          <div class="border border-lighten-1 p-1 rounded mb-1">
                              <div class="row">
                                  <div class="form-group col-12">
                                      <label>*Title</label>
                                      <input class="form-control movementTitle" name="movementTitle" placeholder="Title" id="movementTitle" v-model="movementForm.movementTitle">
                                  </div>
                                  <div class="form-group col-6">
                                      <label>*Transporter</label>
                                      <select2-dropdown name="transporter" class="form-control transporter" id="transporter" :options="transporterOptions"
                                          v-model="movementForm.transporterId" placeholder="Choose Transporter"
                                          :clearable="true"></select2-dropdown>
                                  </div>
                                  <div class="form-group col-6">
                                      <label>*Conveyor</label>
                                      <select2-dropdown class="form-control conveyor" name="conveyor" id="conveyor" :options="conveyorOptions"
                                          v-model="movementForm.conveyorId" placeholder="Choose Conveyor"
                                          :clearable="true"></select2-dropdown>
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
                                      <tr v-for="(item, index) in selectedItems">
                                          <td class="px-25">
                                              <div class="custom-control custom-checkbox checkbox">
                                                  <input type="checkbox" class="custom-control-input"
                                                      :id="'cb-' + item.shipment_id" :checked="item.pick" @click.prevent />
                                                  <label class="custom-control-label" :for="'cb-' + item.shipment_id"
                                                      @dblclick.stop.prevent="removeSelectedMovement(item)"></label>
                                              </div>
                                          </td>
                                          <td class="px-25">
                                              <div class="d-flex justify-content-left align-items-center">
                                                  <div class="d-flex flex-column text-wrap">
                                                      <span class="user_name text-wrap text-body">
                                                          <span class="fw-bolder">{{ item.destination_string }}</span>
                                                      </span>
                                                      <span class="font-small-2 text-muted">{{ item.destination_location_type
                                                          }}</span>
                                                  </div>
                                              </div>
                                          </td>
                                          <td class="px-25">
                                              <div class="d-flex justify-content-left align-items-center">
                                                  <div class="d-flex flex-column text-wrap">
                                                      <span class="user_name text-wrap text-body">
                                                          <span class="fw-bolder">{{ item.shipment_no }}</span>
                                                      </span>
                                                      <span class="font-small-2 "
                                                          :class="movementType=='Forward'? 'text-primary': 'text-muted'">
                                                          {{ item.shipment_type }}
                                                          <i class="feather"
                                                              :class="movementType=='Forward'? 'icon-arrow-right': 'icon-arrow-left'"></i>
                                                      </span>
                                                  </div>
                                              </div>
                                          </td>
                                          <td class="px-25">{{convertStringNumberToFigures(item.total_qty) }} ({{item.unit}})
                                          </td>
                                          <td class="px-25">{{convertStringNumberToFigures(item.total_value)}}</td>
                                      </tr>

                                  </tbody>

                              </table>




                          </div>


                      </div>
                      <div class="modal-footer">
                          <button type="button" class="btn btn-secondary mr-1" data-dismiss="modal">Cancel</button>
                          <button type="submit" class="btn btn-primary" >Create Movement</button>
                      </div>
                  </form>
              </div>
          </div>
          <!-- Modal : Ends-->



      </div>
    `,
});

Vue.component("select2-dropdown", {
  props: {
    value: [String, Number],
    options: {
      type: Array,
      required: true,
    },
    placeholder: {
      type: String,
      default: "Select an option",
    },
    clearable: {
      type: Boolean,
      default: true,
    },
  },
  template: `
    <select class="form-control" ref="select">
      <option :value="null">{{ placeholder }}</option>
    </select>
  `,
  mounted() {
    this.initializeSelect2();
  },
  methods: {
    initializeSelect2() {
      const $select = $(this.$refs.select);

      $select.wrap('<div class="position-relative"></div>');
      $select
        .select2({
          data: this.options,
          placeholder: this.placeholder,
          dropdownAutoWidth: true,

          allowClear: this.clearable,
          width: "100%",
          dropdownParent: $(this.$el).parent(),
        })
        .val(this.value)
        .trigger("change")
        .on("change", () => {
          this.$emit("input", $select.val());
        });

      this.$nextTick(() => {
        $select
          .next(".select2-container")
          .find(".select2-selection__arrow")
          .html('<i class="feather icon-chevron-down"></i>');
      });
    },
    destroySelect2() {
      const $select = $(this.$refs.select);
      $select.off().select2("destroy");
    },
  },
  watch: {
    value(newVal) {
      const $select = $(this.$refs.select);
      if ($select.val() !== newVal) {
        $select.val(newVal).trigger("change");
      }
      const $selection = $select.next(".select2").find(".select2-selection");
      const selectId = $select.attr("id");

      if (newVal) {
        $selection.removeClass("is-invalid");
        // Remove validation error span if it exists
        $(`#${selectId}-error`).remove();
      } else {
        $selection.addClass("is-invalid");
      }
    },
    options: {
      handler() {
        this.destroySelect2();
        this.$nextTick(() => this.initializeSelect2());
      },
      deep: true,
    },
  },
  destroyed() {
    this.destroySelect2();
  },
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
            <div v-show="appState.pageState.page == 'shipment-page'">
                <page-shipment-page />
            </div>

        </div>
      `,
});
