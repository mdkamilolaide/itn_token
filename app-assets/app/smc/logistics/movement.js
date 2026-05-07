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
  page: "movement-page",
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
  movementData: [],

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

Vue.component("page-movement-page", {
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
      movementItemDetails: [],
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
          self.getActivePeriodId(response.data.data);
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
    async loadMovement() {
      if (!appState.currentPeriodId) {
        alert.Error("ERROR", "Please select a visit");
        return;
      }

      const url = common.DataService;
      const data = { periodId: appState.currentPeriodId };

      overlay.show();

      try {
        const { data: response } = await axios.post(
          `${url}?qid=1137`,
          JSON.stringify(data)
        );
        if (response.result_code !== 200) {
          alert.Error("ERROR", response.message);
          return;
        }

        // Success: update state
        appState.movementData = response.data;
        this.searchState = true;
      } catch (error) {
        alert.Error("ERROR", error);
      } finally {
        overlay.hide();
      }
    },
    selectAll() {
      /*  Manages all check box selection checked */
      if (this.filteredMovementData.length > 0) {
        for (let i = 0; i < this.filteredMovementData.length; i++) {
          if (this.filteredMovementData[i].shipment_status === "Pending") {
            this.filteredMovementData[i].pick = true;
          }
        }
      }
    },
    uncheckAll() {
      /*  Manages unchecking of all check box checked */
      if (this.filteredMovementData.length > 0) {
        for (let i = 0; i < this.filteredMovementData.length; i++) {
          this.filteredMovementData[i].pick = false;
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
      appState.movementData = [];
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
    formatForFilename(input) {
      const parts = input.split(" > ").map((part) => part.trim());

      const labels = ["STATE", "LGA", "WARD", "LOCATION"]; // Extendable
      let formattedParts = [];

      for (let i = 0; i < parts.length; i++) {
        const label = labels[i] || `LEVEL${i + 1}`; // Fallback label
        formattedParts.push(
          `${parts[i].replace(/[<>:"/\\|?*]/g, "")}_${label}`
        );
      }

      // Join parts with underscores
      return formattedParts.join("_");
    },
    generateShipmentPDF(data, options = { preview: true }) {
      const shipment = data.shipment?.[0];
      const items = data.items || [];
      const approval = data.approvals?.[0];
      const destString = shipment?.destination_string || "Facility";

      if (!shipment || items.length === 0) {
        alert("ERROR: No data available for PDF generation.");
        return;
      }

      const todayDate = this.displayDate(new Date().toISOString(), true, false);
      const logoDataURL = `data:image/svg+xml;base64,${appState.receiptHeader}`;

      const content = [];

      // Title
      content.push({
        text: "Electronic Proof of Delivery (ePOD)",
        fontSize: 14,
        style: "header",
        alignment: "center",
        margin: [0, 0, 0, 20],
      });

      // Shipment Info
      content.push({
        columns: [
          {
            stack: [
              {
                text: [
                  { text: "Shipment No: ", bold: true },
                  { text: shipment.shipment_no },
                ],
                style: "tableBodyFont8",
              },
              {
                text: [
                  { text: "Type: ", bold: true },
                  { text: shipment.shipment_type },
                ],
                style: "tableBodyFont8",
              },
              {
                text: [
                  { text: "Delivery Status: ", bold: true },
                  { text: shipment.shipment_status },
                ],
                style: "tableBodyFont8",
              },
              {
                text: [
                  { text: "Created: ", bold: true },
                  { text: this.displayDate(shipment.created, true, false) },
                ],
                style: "tableBodyFont8",
              },
              {
                text: [
                  { text: "Total Quantity: ", bold: true },
                  { text: shipment.total_qty },
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
                  { text: shipment.origin_string },
                ],
                style: "tableBodyFont8",
              },
              {
                text: [
                  { text: "Destination: ", bold: true },
                  { text: shipment.destination_string },
                ],
                style: "tableBodyFont8",
              },
              {
                text: [{ text: "Unit: ", bold: true }, { text: shipment.unit }],
                style: "tableBodyFont8",
              },
            ],
          },
        ],
        margin: [0, 0, 0, 20],
      });

      // Items Table
      content.push(
        { text: "Items", style: "subheader" },
        {
          table: {
            headerRows: 1,
            widths: ["auto", "*", "*", "auto", "auto", "auto", "auto"],
            body: [
              [
                { text: "#", style: "tableHeader" },
                { text: "Product Name", style: "tableHeader" },
                { text: "Product Code", style: "tableHeader" },
                { text: "Batch", style: "tableHeader" },
                { text: "Expiry", style: "tableHeader" },
                { text: "Unit", style: "tableHeader" },
                { text: "Quantity", style: "tableHeader" },
              ],
              ...items.map((item, i) => [
                { text: i + 1, style: "tableBodyFont8" },
                { text: item.product_name, style: "tableBodyFont8" },
                { text: item.product_code, style: "tableBodyFont8" },
                { text: item.batch, style: "tableBodyFont8" },
                {
                  text: this.displayDate(item.expiry, true, false),
                  style: "tableBodyFont8",
                },
                { text: item.unit, style: "tableBodyFont8" },
                { text: item.secondary_qty, style: "tableBodyFont8" },
              ]),
            ],
          },
          layout: "lightHorizontalLines",
          margin: [0, 0, 0, 30],
        }
      );

      // Approvals
      content.push({
        text: "Approvals",
        style: "subheader",
        margin: [0, 10, 0, 10],
      });

      const getSignatureBlock = (
        label,
        name,
        designation,
        phone,
        date,
        lat,
        long,
        serial,
        version,
        base64sig
      ) => {
        return {
          stack: [
            { text: label, bold: true, margin: [0, 0, 0, 10] },

            {
              text: [{ text: "Name: ", bold: true }, { text: name || "N/A" }],
              style: "tableBodyFont8",
            },
            {
              text: [
                { text: "Designation: ", bold: true },
                { text: designation || "N/A" },
              ],
              style: "tableBodyFont8",
            },
            {
              text: [{ text: "Phone: ", bold: true }, { text: phone || "N/A" }],
              style: "tableBodyFont8",
            },
            {
              text: [
                { text: "Date: ", bold: true },
                { text: this.displayDate(date, true, false) },
              ],
              style: "tableBodyFont8",
            },
            {
              text: [
                { text: "Location: ", bold: true },
                { text: `${lat}, ${long}` },
              ],
              style: "tableBodyFont8",
            },
            {
              text: [
                { text: "Device: ", bold: true },
                { text: `${serial} | App: ${version}` },
              ],
              style: "tableBodyFont8",
            },

            ...(base64sig?.length > 50
              ? [
                  {
                    image: `data:image/png;base64,${base64sig}`,
                    width: 180,
                    height: 60,
                    margin: [0, 10, 0, 0],
                  },
                ]
              : [
                  {
                    text: "No signature available",
                    italics: true,
                    color: "gray",
                    margin: [0, 10, 0, 0],
                  },
                ]),
          ],
        };
      };

      content.push({
        columns: [
          getSignatureBlock(
            "Origin Approval",
            approval?.source_name,
            approval?.source_designation,
            approval?.source_phone,
            approval?.source_approve_date,
            approval?.source_latitude,
            approval?.source_longitude,
            approval?.source_device_serial,
            approval?.source_app_version,
            approval?.source_signature
          ),
          getSignatureBlock(
            "Conveyor Approval",
            approval?.conveyor_name,
            approval?.conveyor_designation,
            approval?.conveyor_phone,
            approval?.conveyor_approve_date,
            approval?.conveyor_latitude,
            approval?.conveyor_longitude,
            approval?.conveyor_device_serial,
            approval?.conveyor_app_version,
            approval?.conveyor_signature
          ),
          getSignatureBlock(
            "Destination Approval",
            approval?.destination_name,
            approval?.destination_designation,
            approval?.destination_phone,
            approval?.destination_approve_date,
            approval?.destination_latitude,
            approval?.destination_longitude,
            approval?.destination_device_serial,
            approval?.destination_app_version,
            approval?.destination_signature
          ),
        ],
      });

      // Build the PDF
      const docDefinition = {
        pageSize: "A4",
        pageOrientation: "landscape", // 👈 landscape mode
        pageMargins: [40, 60, 40, 60],
        images: { logo: logoDataURL },
        header: (currentPage, pageCount) => ({
          columns: [
            { image: "logo", width: 80, margin: [40, 10, 0, 0] },
            {
              text: "ePOD Document",
              alignment: "center",
              fontSize: 12,
              margin: [0, 25, 0, 0],
            },
            {
              text: `Page ${currentPage} of ${pageCount}`,
              alignment: "right",
              fontSize: 9,
              margin: [0, 25, 40, 0],
            },
          ],
        }),
        footer: (currentPage, pageCount) => ({
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
        }),
        content,
        styles: {
          header: { fontSize: 14, bold: true },
          subheader: { fontSize: 12, bold: true },
          tableHeader: { bold: true, fontSize: 10, fillColor: "#eeeeee" },
          tableBodyFont8: { fontSize: 10, lineHeight: 1.4 },
        },
      };

      if (options.preview) {
        const iframe = document.getElementById("pdfPreview");
        if (!iframe) {
          alert(
            "PDF preview iframe not found. Please add <iframe id='pdfPreview'></iframe> in your HTML."
          );
          return;
        }
        pdfMake.createPdf(docDefinition).getBlob((blob) => {
          const url = URL.createObjectURL(blob);
          const a = document.createElement("a");
          a.href = url;
          a.target = "_blank";
          a.rel = "noopener noreferrer";
          a.click();
          setTimeout(() => URL.revokeObjectURL(url), 10000);
        });
      } else {
        this.formatForFilename(destString);
        pdfMake
          .createPdf(docDefinition)
          .download(`ePod_${destString}_${todayDate}.pdf`);
      }
    },
    generateShipmentPDFBulk(dataArray, options = { preview: true }) {
      if (!Array.isArray(dataArray) || dataArray.length === 0) {
        alert("ERROR: No shipment data found.");
        return;
      }

      const todayDate = this.displayDate(
        new Date().toLocaleDateString(),
        true,
        false
      );
      const logoDataURL = `data:image/svg+xml;base64,${appState.receiptHeader}`;
      const content = [];

      dataArray.forEach((data, index) => {
        const shipment = data.shipment?.[0];
        const items = data.items || [];
        const approval = data.approvals?.[0];
        const destString =
          shipment?.destination_string || `Shipment ${index + 1}`;

        if (!shipment || items.length === 0) return;

        content.push({
          text: `Shipment ${index + 1}: ${shipment.shipment_no}`,
          style: "header",
          margin: [120, 0, 0, 10],
        });

        content.push({
          columns: [
            {
              stack: [
                {
                  text: [
                    { text: "Shipment No: ", bold: true },
                    { text: shipment.shipment_no },
                  ],
                  style: "tableBodyFont8",
                },
                {
                  text: [
                    { text: "Type: ", bold: true },
                    { text: shipment.shipment_type },
                  ],
                  style: "tableBodyFont8",
                },
                {
                  text: [
                    { text: "Delivery Status: ", bold: true },
                    { text: shipment.shipment_status },
                  ],
                  style: "tableBodyFont8",
                },
                {
                  text: [
                    { text: "Created: ", bold: true },
                    { text: this.displayDate(shipment.created, true, true) },
                  ],
                  style: "tableBodyFont8",
                },
                {
                  text: [
                    { text: "Total Quantity: ", bold: true },
                    { text: shipment.total_qty },
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
                    { text: shipment.origin_string },
                  ],
                  style: "tableBodyFont8",
                },
                {
                  text: [
                    { text: "Destination: ", bold: true },
                    { text: shipment.destination_string },
                  ],
                  style: "tableBodyFont8",
                },
                {
                  text: [
                    { text: "Unit: ", bold: true },
                    { text: shipment.unit },
                  ],
                  style: "tableBodyFont8",
                },
              ],
            },
          ],
          margin: [0, 0, 0, 20],
        });

        content.push(
          { text: "Items", style: "subheader" },
          {
            table: {
              headerRows: 1,
              widths: ["auto", "*", "*", "auto", "auto", "auto", "auto"],
              body: [
                [
                  { text: "#", style: "tableHeader" },
                  { text: "Product Name", style: "tableHeader" },
                  { text: "Product Code", style: "tableHeader" },
                  { text: "Batch", style: "tableHeader" },
                  { text: "Expiry", style: "tableHeader" },
                  { text: "Unit", style: "tableHeader" },
                  { text: "Quantity", style: "tableHeader" },
                ],
                ...items.map((item, i) => [
                  { text: i + 1, style: "tableBodyFont8" },
                  { text: item.product_name, style: "tableBodyFont8" },
                  { text: item.product_code, style: "tableBodyFont8" },
                  { text: item.batch, style: "tableBodyFont8" },
                  {
                    text: this.displayDate(item.expiry, false, false),
                    style: "tableBodyFont8",
                  },
                  { text: item.unit, style: "tableBodyFont8" },
                  { text: item.secondary_qty, style: "tableBodyFont8" },
                ]),
              ],
            },
            layout: "lightHorizontalLines",
            margin: [0, 0, 0, 20],
          }
        );

        if (approval) {
          content.push({
            text: "Approvals",
            style: "subheader",
            margin: [0, 10, 0, 10],
          });

          content.push({
            columns: [
              {
                stack: this.renderApproval("Origin Approval", {
                  name: approval.source_name,
                  designation: approval.source_designation,
                  phone: approval.source_phone,
                  date: approval.source_approve_date,
                  lat: approval.source_latitude,
                  long: approval.source_longitude,
                  serial: approval.source_device_serial,
                  version: approval.source_app_version,
                  base64sig: approval.source_signature,
                }),
              },
              {
                stack: this.renderApproval("Conveyor Approval", {
                  name: approval.conveyor_name,
                  designation: approval.conveyor_designation,
                  phone: approval.conveyor_phone,
                  date: approval.conveyor_approve_date,
                  lat: approval.conveyor_latitude,
                  long: approval.conveyor_longitude,
                  serial: approval.conveyor_device_serial,
                  version: approval.conveyor_app_version,
                  base64sig: approval.conveyor_signature,
                }),
              },
              {
                stack: this.renderApproval("Destination Approval", {
                  name: approval.destination_name,
                  designation: approval.destination_designation,
                  phone: approval.destination_phone,
                  date: approval.destination_approve_date,
                  lat: approval.destination_latitude,
                  long: approval.destination_longitude,
                  serial: approval.destination_device_serial,
                  version: approval.destination_app_version,
                  base64sig: approval.destination_signature,
                }),
              },
            ],
            columnGap: 10,
            margin: [0, 0, 0, 20],
          });
        }

        if (index < dataArray.length - 1) {
          content.push({ text: "", pageBreak: "after" });
        }
      });

      const docDefinition = {
        pageSize: "A4",
        pageOrientation: "landscape",
        pageMargins: [40, 60, 40, 60],
        images: { logo: logoDataURL },
        header: (currentPage, pageCount) => ({
          columns: [
            { image: "logo", width: 80, margin: [40, 10, 0, 0] },
            {
              text: "ePOD Document",
              alignment: "center",
              fontSize: 12,
              margin: [0, 25, 0, 0],
            },
            {
              text: `Page ${currentPage} of ${pageCount}`,
              alignment: "right",
              fontSize: 9,
              margin: [0, 25, 40, 0],
            },
          ],
        }),
        footer: (currentPage, pageCount) => ({
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
        }),
        content,
        styles: {
          header: { fontSize: 14, bold: true },
          subheader: { fontSize: 13, bold: true },
          tableHeader: { bold: true, fontSize: 10, fillColor: "#eeeeee" },
          tableBodyFont8: { fontSize: 10, lineHeight: 1.4 },
        },
      };

      if (options.preview) {
        const iframe = document.getElementById("pdfPreview");
        if (!iframe) return alert("PDF preview iframe not found.");
        pdfMake.createPdf(docDefinition).getBlob((blob) => {
          const url = URL.createObjectURL(blob);
          const a = document.createElement("a");
          a.href = url;
          a.target = "_blank";
          a.rel = "noopener noreferrer";
          a.click();
          setTimeout(() => URL.revokeObjectURL(url), 10000);
        });
      } else {
        pdfMake
          .createPdf(docDefinition)
          .download(`ePOD_Multiple_${todayDate}.pdf`);
      }
    },

    boldLabelText(label, value) {
      return {
        text: [{ text: `${label} `, bold: true }, { text: value || "N/A" }],
        style: "tableBodyFont8",
      };
    },

    renderApproval(
      label,
      { name, designation, phone, date, lat, long, serial, version, base64sig }
    ) {
      return [
        { text: label, bold: true, margin: [0, 0, 0, 10] },
        this.boldLabelText("Name:", name),
        this.boldLabelText("Designation:", designation),
        this.boldLabelText("Phone:", phone),
        this.boldLabelText("Date:", this.displayDate(date, true, true)),
        this.boldLabelText("Location:", `${lat}, ${long}`),
        this.boldLabelText("Device:", `${serial} | App: ${version}`),
        ...(base64sig?.length > 50
          ? [
              {
                image: `data:image/png;base64,${base64sig}`,
                width: 120,
                height: 40,
                margin: [0, 10, 0, 0],
              },
            ]
          : [
              {
                text: "No signature available",
                italics: true,
                color: "gray",
                margin: [0, 10, 0, 0],
              },
            ]),
      ];
    },
    debounce(func, delay) {
      let timeout;
      return function (...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(this, args), delay);
      };
    },
    filterUsingStatus(keyword) {
      appState.statusFilter = keyword;
    },
    getShipmentItem(shipmentId, preview) {
      const url = common.DataService;
      const data = { shipmentId: shipmentId };
      overlay.show();
      axios
        .post(url + "?qid=1136a", JSON.stringify(data))
        .then((response) => {
          overlay.hide();
          // console.log(response.data, "Shipment Details Data");
          if (response.data.result_code === 200) {
            this.generateShipmentPDF(response.data.data, preview);
            // this.generateShipmentPDFBulk(response.data.data, preview);
          } else {
            alert.Error("ERROR", response.data.message);
          }
        })
        .catch((error) => {
          overlay.hide();
          alert.Error("ERROR", error);
        });
    },
    getActivePeriodId(periodData) {
      const activePeriod = periodData.find((period) => period.active === 1);
      if (activePeriod) {
        appState.currentPeriodId = activePeriod.periodid;
        this.loadMovement();
      }
    },
    getMovementDetails(movementId) {
      const self = this;
      const url = common.DataService;
      const data = { movementId: movementId };
      overlay.show();
      axios
        .post(url + "?qid=1138", JSON.stringify(data))
        .then((response) => {
          overlay.hide();
          if (response.data.result_code === 200) {
            self.movementItemDetails = response.data.data;

            $("#movementModal").modal("show");

            // console.table(self.movementItemDetails);
            // console.log(self.movementItemDetails);
          } else {
            alert.Error("ERROR", response.data.message);
          }
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
      if (this.filteredMovementData?.length && newVal.length === 0) {
        this.closeMovementModal();
      }
    },
  },
  computed: {
    filteredMovementData() {
      let data = appState.movementData || [];
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
            item.title?.toLowerCase().includes(keyword) ||
            item.transporter?.toLowerCase().includes(keyword) ||
            item.transporter_phone?.toLowerCase().includes(keyword) ||
            item.entered_by?.toLowerCase().includes(keyword) ||
            item.entered_by_loginid?.toLowerCase().includes(keyword) ||
            item.conveyor_loginid?.toLowerCase().includes(keyword) ||
            item.conveyor_fullname?.toLowerCase().includes(keyword) ||
            item.conveyor_phone?.toLowerCase().includes(keyword) ||
            item.updated?.toLowerCase().includes(keyword)
          );
        });
      }

      // return data;
      // Sort by updated field (descending)
      return data.sort((a, b) => {
        const dateA = new Date(a.updated || 0);
        const dateB = new Date(b.updated || 0);
        return dateB - dateA;
      });
    },
    groupDataByFacility() {
      const grouped = this.filteredMovementData.reduce((acc, item) => {
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
    selectedItems() {
      return this.filteredMovementData?.filter((item) => item.pick) || [];
    },
    selectedID() {
      if (!this.filteredMovementData || this.filteredMovementData.length == 0) {
        return [];
      }

      return this.filteredMovementData
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
                              <select @change="resetCheckTable" v-model="appState.currentPeriodId"
                                  class="form-control period mr-1" id="period">
                                  <option value="">Choose Period</option>
                                  <option v-for="(g, i) in appState.periodData" :value="g.periodid">{{ g.title }}
                                  </option>
                              </select>
                                <button type="button"
                                  class="btn btn-primary border mb-25 waves-button px-sm-1"
                                  @click="loadMovement()">
                                  <span class=""> Load Movement</span>
                                  <i class="feather icon-send ml-50"></i>
                                </button>
                          </div>
                          
                      </div>
                  </div>
              </div>


              <div class="card">
                  <div class="card-datatable">
                      <template v-if="this.searchState">

                          <div class="d-flex flex-wrap align-items-center justify-content-between px-1">
                            <!-- Toggle Buttons and Print -->
                            <!--
                            <div class="col-12 col-sm-12 col-md-6 col-lg-6 py-75">
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
                            </div>
                            -->

                            <!-- Search -->
                            <div class="col-12 col-sm-12 col-md-6 col-lg-6 py-75 d-flex-block justify-content-end align-items-center">
                                <!-- Search Input -->
                                <div class="d-flex flex-nowrap justify-content-end">
                                  <input type="text" v-model="searchText" placeholder="Search"
                                    class="form-control w-100 mb-25" style="min-width: 0;" />
                                </div>
                                <button type="button"
                                  class="btn d-none btn-outline-secondary pl-1 border mb-25 waves-button px-sm-1"
                                  @click="generatePDF()">
                                  <i class="feather icon-printer"></i>
                                   <span class="d-none d-md-inline d-lg-inline-flex"> Print</span>
                                </button>
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
                                      <tr v-for="(item, index) in filteredMovementData">
                                          <td class="px-25">{{index+1}}</td>
                                          <td class="px-1">{{item.title}}</td>
                                          <td class="px-1">
                                              <div class="d-flex justify-content-left align-items-center">
                                                  <div class="d-flex flex-column text-wrap">
                                                      <span class="user_name text-wrap text-body">
                                                          <span class="fw-bolder">{{ item.entered_by }}</span>
                                                      </span>
                                                      <span class="font-small-2 text-muted">{{ item.entered_by_loginid}}</span>
                                                  </div>
                                              </div>
                                          </td>
                                          <td class="px-1">
                                              <div class="d-flex justify-content-left align-items-center">
                                                  <div class="d-flex flex-column text-wrap">
                                                      <span class="user_name text-wrap text-body">
                                                          <span class="fw-bolder">{{ item.transporter }}</span>
                                                      </span>
                                                      <span class="font-small-2 text-muted">{{ item.transporter_phone}}</span>
                                                  </div>
                                              </div>
                                          </td>
                                          <td class="px-1">
                                              <div class="d-flex justify-content-left align-items-center">
                                                  <div class="d-flex flex-column text-wrap">
                                                      <span class="user_name text-wrap text-body">
                                                          <span class="fw-bolder">{{ item.conveyor_fullname }}</span>
                                                      </span>
                                                      <span class="font-small-2 text-muted">
                                                          {{ item.conveyor_loginid }} 
                                                      </span>
                                                  </div>
                                              </div>
                                          </td>
                                          <td class="px-1">{{item.conveyor_phone}}</td>
                                          <td class="px-1">{{displayDate(item.updated, false, true)}}</td>
                                          <td class="px-1">
                                              <button class="btn btn-sm btn-primary p-50" @click="getMovementDetails(item.movement_id)"><i class="feather icon-eye"></i></button>
                                          </td>
                                      </tr>

                                  </tbody>

                              </table>
                          </div>

                      </template>
                      <div v-if="filteredMovementData.length == 0 && searchState == true">
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
              <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
                  <div class="modal-content" id="createMovementForm">
                      <div class="modal-header">
                          <h5 class="modal-title" id="movementModalTitle">Shipment List</h5>
                          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                              <span aria-hidden="true">&times;</span>
                          </button>
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
                                      <tr v-for="(item, index) in movementItemDetails">
                                          <td class="px-25">{{index+1}} </td>
                                          <td class="px-25">
                                              <div class="d-flex justify-content-left align-items-center">
                                                  <div class="d-flex flex-column text-wrap">
                                                      <span class="user_name text-wrap text-body">
                                                          <span class="fw-bolder">{{ item.shipment_no }}</span>
                                                      </span>
                                                      <span class="font-small-2 "
                                                          :class="item.shipment_type=='Forward'? 'text-primary': 'text-muted'">
                                                          {{ item.shipment_type }} <i class="feather" :class="item.shipment_type=='Forward'? 'icon-arrow-right': 'icon-arrow-left'"></i>
                                                      </span>
                                                  </div>
                                              </div>
                                          </td>
                                          <td class="px-25">
                                              <div class="d-flex justify-content-left align-items-center">
                                                  <div class="d-flex flex-column text-wrap">
                                                      <span class="user_name text-wrap text-body">
                                                          <span class="fw-bolder">{{ item.origin_string }}</span>
                                                      </span>
                                                      <span class="font-small-2 text-muted">{{ item.origin_location_type }}</span>
                                                  </div>
                                              </div>
                                          </td>
                                          <td class="px-25">
                                              <div class="d-flex justify-content-left align-items-center">
                                                  <div class="d-flex flex-column text-wrap">
                                                      <span class="user_name text-wrap text-body">
                                                          <span class="fw-bolder">{{item.destination_string}}</span>
                                                      </span>
                                                      <span class="font-small-2 text-muted">{{ item.destination_location_type }}</span>
                                                  </div>
                                              </div>
                                          </td>
                                          <td class="px-25">{{convertStringNumberToFigures(item.total_value)}}</td>
                                          <td class="px-25">
                                              <span class="badge p-50" :class="item.shipment_status=='Processing'?  'badge-light-success': ' badge-light-secondary'">{{item.shipment_status}}</span>
                                          </td>
                                          <td class="px-25">
                                              <button type="button" @click="getShipmentItem(item.shipment_id, { preview: true })" class="btn btn-sm btn-primary p-50">
                                                  <i class="feather icon-eye"></i>
                                              </button>
                                              <button type="button" @click="getShipmentItem(item.shipment_id, { preview: false })" class="btn btn-sm btn-primary ml-75 p-50">
                                                  <i class="feather icon-printer"></i>
                                              </button>
                                          </td>
                                      </tr>

                                  </tbody>

                              </table>




                          </div>


                      </div>
                      <div class="modal-footer">
                          <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                          <!-- <button type="button" class="btn btn-primary pl-1">Print</button> -->
                      </div>
                  </div>
              </div>
          </div>
          <!-- Modal : Ends-->

          <iframe id="pdfPreview" style="width:100%; height:600px; display:none;"></iframe>


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
            <div v-show="appState.pageState.page == 'movement-page'">
                <page-movement-page />
            </div>
        </div>
      `,
});
