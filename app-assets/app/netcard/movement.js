const EventBus = new Vue();
/*
 *      EVENT HANDLED BY EventBus
 *
 *      - g-event-change-page (Emit multiple - to change page)
 * 		EventBus.$emit('g-event-change-page', 2); 	- Event Fire
 *		EventBus.$on('g-event-change-page', this.gotoPageHandler)  - Event receiver
		gotoPageHandler(data){
            overlay.show();
            this.page = data;
            overlay.hide();
        },						-	Event Handler
 *  
 */

// g-event-goto-page
// page: 'training', //  page by name training | session | participant | ...
// g-event-update

Vue.component("page-body", {
  data: function () {
    return {
      page: "movement", //  page by name training | session | participant | attendance ...
    };
  },
  mounted() {
    /*  Manages events Listening    */
    EventBus.$on("g-event-goto-page", this.gotoPageHandler);
  },
  methods: {
    gotoPageHandler(data) {
      this.page = data.page;
    },
  },
  template: `
    <div>

        <div class="content-body">

            <div v-show="page == 'movement'">
                <necard_movement/>
            </div>

        </div>
    </div>
    `,
});

Vue.component("necard_movement", {
  data: function () {
    return {
      tableData: [],
      checkToggle: false,
      filterState: false,
      filters: false,
      permission: getPermission(per, "enetcard"),
      url: common.TableService,
      tableOptions: {
        total: 1, //Total record
        pageLength: 1, //Total
        perPage: 10,
        currentPage: 1,
        orderDir: "desc", // (asc|desc)
        orderField: 0, //(Order fields)
        limitStart: 0, //(currentPage - 1) * perPage
        isNext: false,
        isPrev: false,
        aLength: [10, 20, 50, 100, 150],
        filterParam: {
          movementType: "",
        },
      },
      stateMovementForm: {
        stateMoveModal: false,
        stateid: "",
        lgaid: "",
        totalNetcard: 1,
        lgaName: "",
      },
      lgaMovementForm: {
        totalNetcard: 1,
        lgaMoveBtn: "",
        lgaMoveModal: false,
        originid: "",
        originName: "",
        originBalance: 0,
        destinationid: "",
        destinationName: "",
      },
      wardMovementForm: {
        totalNetcard: 1,
        wardMoveBtn: "",
        wardMoveModal: false,
        originid: "",
        originName: "",
        originBalance: 0,
        destinationid: "",
        destinationName: "",
      },
      movementForm: {
        geoLevel: "",
        geoLevelId: 0,
      },
      geoIndicator: {
        state: 50,
        currentLevelId: 0,
        lga: "",
        cluster: "",
        ward: "",
      },
      geoLevelData: [],
      sysDefaultData: [],
      lgaLevelData: [],
      clusterLevelData: [],
      wardLevelData: [],
      lgaNetBalancesData: [],
      wardNetBalancesData: [],
      isLgabalance: true,
      allStatistics: {
        stateBalance: 0,
        lgaBalance: 0,
        wardBalance: 0,
        beneficiary: 0,
        total: 0,
      },
    };
  },
  mounted() {
    /*  Manages events Listening    */
    this.getsysDefaultDataSettings();
    this.getLgasNetBalances();
    this.loadTableData();
    this.getAllStat();
    EventBus.$on("g-event-update", this.loadTableData);
  },
  methods: {
    loadTableData() {
      /*  Manages the loading of table data */
      var self = this;
      var url = common.TableService;
      overlay.show();

      axios
        .get(
          url +
            "?qid=201&draw=" +
            self.tableOptions.currentPage +
            "&order_column=" +
            self.tableOptions.orderField +
            "&length=" +
            self.tableOptions.perPage +
            "&start=" +
            self.tableOptions.limitStart +
            "&order_dir=" +
            self.tableOptions.orderDir +
            "&mt=" +
            self.tableOptions.filterParam.movementType
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
      let checkFill = 0;
      checkFill += this.tableOptions.filterParam.movementType != "" ? 1 : 0;

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
      this.tableOptions.filterParam.title =
        this.tableOptions.filterParam.trainingid =
        this.tableOptions.filterParam.active =
          "";
      this.paginationDefault();
      this.loadTableData();
    },
    showStateMoveModal() {
      overlay.show();
      this.stateMovementForm.totalNetcard = 1;
      this.stateMovementForm.stateMoveModal = true;
      this.stateMovementForm.lgaName = "";
      overlay.hide();
    },
    hideStateMoveModal() {
      overlay.show();
      this.stateMovementForm.lgaid = "";
      this.stateMovementForm.totalNetcard = 1;
      this.stateMovementForm.lgaName = "";
      $("#stateMove").modal("hide");
      this.stateMovementForm.stateMoveModal = false;
      this.wardLevelData = [];
      overlay.hide();
    },
    setLgaName(event) {
      this.stateMovementForm.lgaName =
        event.target.options[event.target.options.selectedIndex].text;
    },
    transferFromStateToLGA() {
      var self = this;
      var url = common.DataService;
      // overlay.show();
      //Validate Form

      if (
        parseInt(self.stateMovementForm.totalNetcard) > 0 &&
        parseInt(self.stateMovementForm.totalNetcard) <=
          parseInt(self.allStatistics.stateBalance)
      ) {
        $.confirm({
          title: "WARNING!",
          content:
            "Are you sure you want to transfer <b>" +
            self.stateMovementForm.totalNetcard +
            "</b> e-Netcard to <b>" +
            self.stateMovementForm.lgaName +
            "</b> LGA?",
          buttons: {
            delete: {
              text: "transfer",
              btnClass: "btn btn-danger mr-1 text-capitalize",
              action: function () {
                //Attempt Update
                axios
                  .post(
                    url +
                      "?qid=202&total=" +
                      self.stateMovementForm.totalNetcard +
                      "&stateid=" +
                      self.stateMovementForm.stateid +
                      "&lgaid=" +
                      self.stateMovementForm.lgaid +
                      "&id=" +
                      $("#v_g_id").val()
                  )
                  .then(function (response) {
                    if (response.data.result_code == 200) {
                      self.hideStateMoveModal();
                      self.refreshData();
                      alert.Success(
                        "Success",
                        response.data.message +
                          " has been moved from State to " +
                          self.stateMovementForm.lgaName +
                          " LGA"
                      );
                      overlay.hide();
                    } else {
                      overlay.hide();
                      alert.Error("Error", response.data.message);
                    }
                    // Unable to create new record
                  })
                  .catch(function (error) {
                    alert.Error("ERROR", error);
                    overlay.hide();
                  });
              },
            },
            cancel: function () {
              // Do nothing
              overlay.hide();
            },
          },
        });
      } else {
        alert.Error(
          "ERROR",
          "You can't transfer more than " +
            self.allStatistics.stateBalance +
            " available e-Netcards"
        );

        overlay.hide();
      }
    },
    showLgaMoveModal() {
      overlay.show();
      this.lgaMovementForm.lgaMoveModal = true;
      this.lgaMovementForm.lgaMoveBtn = "Forward";
      this.lgaMovementForm.totalNetcard = 1;
      this.lgaMovementForm.originName = "";
      this.lgaMovementForm.destinationName = "";
      overlay.hide();
    },
    hideLgaMoveModal() {
      overlay.show();
      this.lgaMovementForm.lgaMoveModal = false;
      this.lgaMovementForm.lgaMoveBtn = "";
      this.lgaMovementForm.destinationid = "";
      this.lgaMovementForm.originid = "";
      this.lgaMovementForm.totalNetcard = 1;
      this.lgaMovementForm.originName = "";
      this.lgaMovementForm.destinationName = "";
      this.geoIndicator.lga = "";
      this.wardNetBalancesData = [];
      this.wardLevelData = [];
      $("#lgaMove").modal("hide");
      overlay.hide();
    },
    setLgaOriginName(event) {
      if (this.lgaMovementForm.lgaMoveBtn == "Forward") {
        this.lgaMovementForm.destinationid = "";
        this.lgaMovementForm.destinationName = "";
      }
      this.getWardLevel();
      this.geoIndicator.lga = this.lgaMovementForm.originid;
      this.lgaMovementForm.originName =
        event.target.options[event.target.options.selectedIndex].text;

      let origin = event.target.options[event.target.options.selectedIndex].text
        .trim()
        .replace(",", "")
        .split("-");
      //Select Last value with number;
      this.lgaMovementForm.originBalance =
        origin[origin.length - 1] == ""
          ? 0
          : parseInt(origin[origin.length - 1]);
    },
    setLgaDestinationName(event) {
      if (this.lgaMovementForm.lgaMoveBtn == "Forward") {
        this.lgaMovementForm.destinationName =
          event.target.options[event.target.options.selectedIndex].text;
      }
    },
    setLgaReverseVariable() {
      this.lgaMovementForm.lgaMoveBtn = "Reverse";
      this.lgaMovementForm.destinationid = this.sysDefaultData.stateid;
      this.lgaMovementForm.destinationName = this.sysDefaultData.state;
    },
    lgaTransfer(transfer_type) {
      var self = this;
      var url = common.DataService;
      // overlay.show();

      var origin_name = self.lgaMovementForm.originName.split(" - ")[0];
      if (
        parseInt(self.lgaMovementForm.totalNetcard) <=
        parseInt(self.lgaMovementForm.originBalance)
      ) {
        if (transfer_type == "Reverse") {
          $.confirm({
            title: "WARNING!",
            content:
              "Are you sure you want to reverse <b>" +
              self.lgaMovementForm.totalNetcard +
              "</b> e-Netcard from <b>" +
              origin_name +
              "</b> to <b>" +
              self.lgaMovementForm.destinationName +
              "</b> State?",
            buttons: {
              delete: {
                text: transfer_type,
                btnClass: "btn btn-danger mr-1 text-capitalize",
                action: function () {
                  //Attempt Update
                  axios
                    .post(
                      url +
                        "?qid=203&total=" +
                        self.lgaMovementForm.totalNetcard +
                        "&originid=" +
                        self.lgaMovementForm.originid +
                        "&destinationid=" +
                        self.lgaMovementForm.destinationid +
                        "&id=" +
                        $("#v_g_id").val()
                    )
                    .then(function (response) {
                      if (response.data.result_code == "200") {
                        alert.Success(
                          "Success",
                          response.data.message +
                            " Netcards has been reversed successfully from <b>" +
                            origin_name +
                            "</b> LGA to <b>" +
                            self.lgaMovementForm.destinationName +
                            "</b>"
                        );
                        self.hideLgaMoveModal();
                        self.refreshData();
                        overlay.hide();
                      } else {
                        overlay.hide();
                        alert.Error("Error", response.data.message);
                      }
                      // Unable to create new record
                    })
                    .catch(function (error) {
                      alert.Error("ERROR", error);
                      overlay.hide();
                    });
                },
              },
              cancel: function () {
                // Do nothing
                overlay.hide();
              },
            },
          });
        } else {
          $.confirm({
            title: "WARNING!",
            content:
              "Are you sure you want to Transfer <b>" +
              self.lgaMovementForm.totalNetcard +
              "</b> e-Netcards from <b>" +
              origin_name +
              " </b> LGA  to <b>" +
              self.lgaMovementForm.destinationName +
              "</b> Ward?",
            buttons: {
              delete: {
                text: transfer_type,
                btnClass: "btn btn-danger mr-1 text-capitalize",
                action: function () {
                  //Attempt Update
                  axios
                    .post(
                      url +
                        "?qid=204&total=" +
                        self.lgaMovementForm.totalNetcard +
                        "&originid=" +
                        self.lgaMovementForm.originid +
                        "&destinationid=" +
                        self.lgaMovementForm.destinationid +
                        "&id=" +
                        $("#v_g_id").val()
                    )
                    .then(function (response) {
                      if (response.data.result_code == "200") {
                        alert.Success(
                          "Success",
                          "<b>" +
                            response.data.message +
                            "</b> e-Netcards has been transfered successfully from <b>" +
                            origin_name +
                            "</b> LGA to <b>" +
                            self.lgaMovementForm.destinationName +
                            "</b> Ward"
                        );
                        self.hideLgaMoveModal();
                        self.refreshData();
                        overlay.hide();
                      } else {
                        overlay.hide();
                        alert.Error("Error", response.data.message);
                      }
                      // Unable to create new record
                    })
                    .catch(function (error) {
                      alert.Error("ERROR", error);
                      overlay.hide();
                    });
                },
              },
              cancel: function () {
                // Do nothing
                overlay.hide();
              },
            },
          });
        }
      } else {
        alert.Error(
          "ERROR",
          "You can't transfer more than " +
            self.lgaMovementForm.originBalance +
            " e-Netcard"
        );
        overlay.hide();
      }
    },
    showWardMoveModal() {
      overlay.show();
      this.wardMovementForm.wardMoveModal = true;
      this.wardMovementForm.wardMoveBtn = "Forward";
      this.wardMovementForm.totalNetcard = 1;
      this.wardMovementForm.originName = "";
      this.wardMovementForm.destinationName = "";
      this.wardMovementForm.destinationid = "";
      overlay.hide();
    },
    hideWardMoveModal() {
      overlay.show();
      $("#wardMove").modal("hide");
      this.wardMovementForm.wardMoveModal = false;
      this.wardMovementForm.wardMoveBtn = "";
      this.wardMovementForm.destinationid = "";
      this.wardMovementForm.originid = "";
      this.wardMovementForm.totalNetcard = 1;
      this.wardMovementForm.originName = "";
      this.wardMovementForm.destinationName = "";
      this.wardMovementForm.originBalance = 0;
      this.geoIndicator.lga = "";
      this.wardLevelData = [];
      this.wardNetBalancesData = [];
      overlay.hide();
    },
    setWardOriginName(event) {
      this.wardMovementForm.originName =
        event.target.options[event.target.options.selectedIndex].text;
      this.wardMovementForm.originBalance =
        event.target.options[event.target.options.selectedIndex].text
          .trim()
          .replace(",", "")
          .split("-")[1] == ""
          ? 0
          : parseInt(
              event.target.options[event.target.options.selectedIndex].text
                .trim()
                .replace(",", "")
                .split("-")[1]
            );
    },
    setWardDestinationName(event) {
      this.wardMovementForm.originBalance = 0;
      this.wardMovementForm.originid = "";
      this.getWardsNetBalances();
      this.wardMovementForm.destinationName = "";
      this.wardMovementForm.destinationName =
        event.target.options[event.target.options.selectedIndex].text;
    },
    wardTransfer() {
      var self = this;
      var url = common.DataService;
      // overlay.show();

      var origin_name = self.wardMovementForm.originName.split(" - ")[0];
      if (
        parseInt(self.wardMovementForm.totalNetcard) <=
        parseInt(self.wardMovementForm.originBalance)
      ) {
        $.confirm({
          title: "WARNING!",
          content:
            "Are you sure you want to reverse <b>" +
            self.wardMovementForm.totalNetcard +
            "</b> e-Netcard from <b>" +
            origin_name +
            "</b> Ward to <b>" +
            self.wardMovementForm.destinationName +
            "</b> LGA?",
          buttons: {
            delete: {
              text: "Reverse e-Netcard",
              btnClass: "btn btn-danger mr-1 text-capitalize",
              action: function () {
                //Attempt Update
                axios
                  .post(
                    url +
                      "?qid=205&total=" +
                      self.wardMovementForm.totalNetcard +
                      "&originid=" +
                      self.wardMovementForm.originid +
                      "&destinationid=" +
                      self.wardMovementForm.destinationid +
                      "&id=" +
                      $("#v_g_id").val()
                  )
                  .then(function (response) {
                    if (response.data.result_code == "200") {
                      alert.Success(
                        "Success",
                        "<b>" +
                          response.data.message +
                          "</b> Netcards has been reversed successfully from <b>" +
                          origin_name +
                          "</b> Ward to <b>" +
                          self.wardMovementForm.destinationName +
                          "</b> LGA"
                      );
                      self.hideWardMoveModal();
                      self.refreshData();
                      overlay.hide();
                    } else {
                      overlay.hide();
                      alert.Error("Error", response.data.message);
                    }
                    // Unable to create new record
                  })
                  .catch(function (error) {
                    alert.Error("ERROR", error);
                    overlay.hide();
                  });
              },
            },
            cancel: function () {
              // Do nothing
              overlay.hide();
            },
          },
        });
      } else {
        alert.Error(
          "ERROR",
          "You can't reverse more than " +
            self.wardMovementForm.originBalance +
            " e-Netcard"
        );
        overlay.hide();
      }
    },
    goToSessionLists(trainingid) {
      EventBus.$emit("g-event-goto-page", {
        trainingid: trainingid,
        page: "session",
      });
    },
    goToParticipantList(trainingid) {
      EventBus.$emit("g-event-goto-page", {
        trainingid: trainingid,
        page: "participant",
      });
    },
    getsysDefaultDataSettings() {
      /*  Manages the loading of System default settings */
      var self = this;
      var url = common.DataService;
      overlay.show();

      axios
        .get(url + "?qid=gen007")
        .then(function (response) {
          if (response.data.data.length > 0) {
            self.sysDefaultData = response.data.data[0]; //All Data
            self.getLgasLevel(response.data.data[0].stateid);
            //  Set preventDefault();
            self.movementForm.geoLevel = "state";
            self.movementForm.geoLevelId = response.data.data[0].stateid;
            self.stateMovementForm.stateid = response.data.data[0].stateid;
          }

          overlay.hide();
        })
        .catch(function (error) {
          overlay.hide();
          alert.Error("ERROR", error);
        });
    },
    getLgasLevel(stateid) {
      /*  Manages the loading of Geo Level data */
      var self = this;
      var url = common.DataService;
      overlay.show();

      axios
        .post(url + "?qid=gen003", JSON.stringify(stateid))
        .then(function (response) {
          self.lgaLevelData = response.data.data; //All Data
          overlay.hide();
        })
        .catch(function (error) {
          overlay.hide();
          alert.Error("ERROR", error);
        });
    },
    getLgasNetBalances() {
      /*  Manages the loading of Geo Level data */
      var self = this;
      var url = common.DataService;
      overlay.show();

      axios
        .post(url + "?qid=206")
        .then(function (response) {
          self.lgaNetBalancesData = response.data.data; //All Data
          overlay.hide();
        })
        .catch(function (error) {
          overlay.hide();
          alert.Error("ERROR", error);
        });
    },
    getClusterLevel() {
      /*  Manages the loading of Geo Level data */
      var self = this;
      var url = common.DataService;
      overlay.show();

      axios
        .get(url + "?qid=gen004&e=" + self.geoIndicator.cluster)
        .then(function (response) {
          self.clusterLevelData = response.data.data; //All Data
          overlay.hide();
        })
        .catch(function (error) {
          overlay.hide();
          alert.Error("ERROR", error);
        });
    },
    getWardLevel() {
      /*  Manages the loading of Geo Level data */
      var self = this;
      var url = common.DataService;
      overlay.show();

      axios
        .get(url + "?qid=gen005&e=" + self.lgaMovementForm.originid)
        .then(function (response) {
          self.wardLevelData = response.data.data; //All Data
          overlay.hide();
        })
        .catch(function (error) {
          overlay.hide();
          alert.Error("ERROR", error);
        });
    },
    getWardsNetBalances() {
      /*  Manages the loading of Netcard Balances */
      var self = this;
      var url = common.DataService;
      overlay.show();

      axios
        .get(url + "?qid=207&lgaid=" + self.wardMovementForm.destinationid)
        .then(function (response) {
          self.wardNetBalancesData = response.data.data; //All Data
          overlay.hide();
        })
        .catch(function (error) {
          overlay.hide();
          alert.Error("ERROR", error);
        });
    },
    getWardData(event) {
      /*  Manages the loading of Netcard Balances */
      var self = this;
      var url = common.DataService;
      overlay.show();
      axios
        .get(
          url +
            "?qid=207&lgaid=" +
            event.target.options[event.target.options.selectedIndex].value
        )
        .then(function (response) {
          self.wardNetBalancesData = response.data.data; //All Data
          overlay.hide();
        })
        .catch(function (error) {
          overlay.hide();
          alert.Error("ERROR", error);
        });
    },
    refreshData() {
      this.paginationDefault();
      this.loadTableData();
      this.getLgasNetBalances();
      this.getAllStat();
    },
    getAllStat() {
      var url = common.DataService;
      var self = this;
      var endpoints = [url + "?qid=201", url + "?qid=201a"];

      // Return our response in the allData variable as an array
      Promise.all(endpoints.map((endpoint) => axios.get(endpoint))).then(
        axios.spread((...allData) => {
          overlay.show();
          //get All Stat
          self.allStatistics.total = parseInt(allData[1].data.data[0].total);

          //Check if this location state|lga|ward are with balances
          const stat = self.extractTotals(allData[0]?.data?.data);

          Object.assign(self.allStatistics, {
            stateBalance: stat?.state ?? 0,
            lgaBalance: stat?.lga ?? 0,
            wardBalance: stat?.ward ?? 0,
            mobilizer: stat?.mobilizer ?? 0,
            beneficiary: stat?.beneficiary ?? 0,
          });

          overlay.hide();
        })
      );
    },
    extractTotals(data) {
      // Default values
      let state = 0;
      let lga = 0;
      let ward = 0;
      let mobilizer = 0;
      let beneficiary = 0;

      if (Array.isArray(data)) {
        data.forEach((item) => {
          if (item && typeof item === "object") {
            if (item.location === "state") {
              state = item.total ?? 0;
            } else if (item.location === "lga") {
              lga = item.total ?? 0;
            } else if (item.location === "ward") {
              ward = item.total ?? 0;
            } else if (item.location === "mobilizer") {
              mobilizer = item.total ?? 0;
            } else if (item.location === "beneficiary") {
              beneficiary = item.total ?? 0;
            }
          }
        });
      }

      return { state, lga, ward, mobilizer, beneficiary };
    },
    displayDate(d) {
      let date = new Date(d);
      let options = {
        year: "numeric",
        month: "long",
        day: "numeric",
        hour12: true,
        hour: "2-digit",
        minute: "2-digit",
        second: "2-digit",
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
    formatNumber(num) {
      return parseInt(num).toLocaleString();
    },
    onlyNumber($event) {
      //console.log($event.keyCode); //keyCodes value
      let keyCode = $event.keyCode ? $event.keyCode : $event.which;
      if ((keyCode < 48 || keyCode > 57) && keyCode == 46) {
        // 46 is dot
        $event.preventDefault();
      }
    },
  },
  template: `

        <div class="row" id="basic-table" v-cloak>

            <div class="col-md-12 col-sm-12 col-12 mb-1">
                <h2 class="content-header-title header-txt float-left mb-0">e-Netcard</h2>
                <div class="breadcrumb-wrapper">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../netcard">Home</a></li>
                        <li class="breadcrumb-item active">e-Netcard Movement</li>
                    </ol>
                </div>
            </div>

            <!-- Stats Horizontal Card -->
            <div class="col-lg-3 col-sm-6 col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <span>Total e-Netcard</span>
                            <div class="avatar bg-light-primary p-50 m-0 mt--1">
                                <div class="avatar-content">
                                    <i data-feather="database" class="font-medium-4"></i>
                                </div>
                            </div>
                        </div>
                        <div class="d-block justify-content-between align-items-end mt-50 pt-25">
                            <div class="role-heading d-block pb-2">
                                <h4 class="fw-bolder pb-50" v-cloak>{{formatNumber(allStatistics.total)}}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-sm-6 col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <span>State Balance</span>
                            <div class="avatar bg-light-primary p-50 m-0 mt--1">
                                <div class="avatar-content">
                                    <i data-feather="globe" class="font-medium-4"></i>
                                </div>
                            </div>
                        </div>
                        <div class="d-block justify-content-between align-items-end mt-50 pt-25">
                            <div class="role-heading d-block">
                                <h4 class="fw-bolder" v-cloak>{{formatNumber(allStatistics.stateBalance)}}</h4>
                                <a v-if="permission.permission_value ==3" href="javascript:void(0);"  data-backdrop="static" data-keyboard="false" class="state-move-modal float-right btn btn-sm btn-primary" @click="showStateMoveModal()" data-toggle="modal" data-target="#stateMove">
                                    <small class="fw-bolder">Transfer</small>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

           <div class="col-lg-3 col-sm-6 col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <span>LGAs Balance</span>
                            <div class="avatar bg-light-primary p-50 m-0 mt--1">
                                <div class="avatar-content">
                                    <i data-feather="grid" class="font-medium-4"></i>
                                </div>
                            </div>
                        </div>
                        <div class="d-block justify-content-between align-items-end mt-50 pt-25">
                            <div class="role-heading d-block">
                                <h4 class="fw-bolder" v-cloak>{{formatNumber(allStatistics.lgaBalance)}}</h4>
                                <div class="text-right">
                                    <a href="javascript:void(0);" data-backdrop="static" @click="isLgabalance=true" data-keyboard="false" class="lga-details-modal btn btn-sm btn-outline-primary mr-50" data-toggle="modal" data-target="#viewDetails">
                                        <small class="fw-bolder">View</small>
                                    </a>
                                    <a v-if="permission.permission_value ==3" href="javascript:void(0);" data-backdrop="static" data-keyboard="false" class="lga-move-modal btn btn-sm btn-primary" @click="showLgaMoveModal()" data-toggle="modal" data-target="#lgaMove">
                                        <small class="fw-bolder">Transfer</small>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

           <div class="col-lg-3 col-sm-6 col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <span>Wards Balance</span>
                            <div class="avatar bg-light-primary p-50 m-0 mt--1">
                                <div class="avatar-content">
                                    <i data-feather="target" class="font-medium-4"></i>
                                </div>
                            </div>
                        </div>
                        <div class="d-block justify-content-between align-items-end mt-50 pt-25">
                            <div class="role-heading d-block">
                                <h4 class="fw-bolder" v-cloak>{{formatNumber(allStatistics.wardBalance)}}</h4>
                                <div class="text-right">
                                    <a href="javascript:void(0);" data-backdrop="static" @click="isLgabalance=false" data-keyboard="false" class="ward-move-modal btn btn-sm btn-outline-primary mr-50" data-toggle="modal" data-target="#viewDetails">
                                        <small class="fw-bolder">View</small>
                                    </a>
                                    <a v-if="permission.permission_value ==3" href="javascript:void(0);" data-backdrop="static" data-keyboard="false" class="ward-move-modal btn btn-sm btn-primary" @click="showWardMoveModal()" data-toggle="modal" data-target="#wardMove">
                                        <small class="fw-bolder">Reverse</small>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            
            <!--/ Stats Horizontal Card -->

            <div class="col-md-7 col-sm-7 col-7 mb-0">
                <h4 class="font-medium-1 float-left mb-0">Movement Transactions</h4>
            </div>
            <div class="col-md-5 col-sm-5 col-5 text-md-right text-right d-md-block">
                <div class="btn-group mr-1">
                    <button type="button" class="btn btn-outline-primary round searchBtn" data-toggle="tooltip" data-placement="top" title="Refresh Page" @click="refreshData()">
                        <i class="feather icon-refresh-cw"></i>         
                    </button>  
                    <button type="button" class="btn btn-outline-primary round searchBtn" data-toggle="tooltip" data-placement="top" title="Filter" @click="toggleFilter()">
                        <i class="feather" :class="filterState ? 'icon-x' : 'icon-filter'"></i>               
                    </button>
                </div>
            </div>

            <div class="col-12" v-if="filters">
                <div class="col-12 filter-bar">
                    <span class="badge badge-dark filter-box" @click="removeSingleFilter(i)" v-for="(filterParam, i) in tableOptions.filterParam" v-if="filterParam.length > 0">{{capitalize(i)}}: {{filterParam}} <i class="feather icon-x"></i></span>
                    <a href="#" class="float-right clear-filter" @click="clearAllFilter()">Clear</a>
                </div>
            </div>

            <div class="col-12 mt-1">
                <div class="card">
                    <div class="card-body py-1" v-show="filterState">
                        <form id="filterForm">
                            <div class="row">
                                <div class="col-9">
                                    <div class="form-group">
                                        <label>Movement Type</label>
                                        <select name="active" v-model="tableOptions.filterParam.movementType" class="form-control active select2">
                                            <option value="" selected>All</option>
                                            <option value="forward">Forward Movement</option>
                                            <option value="reverse">Reverse Movement</option>
                                        </select>
                                    </div>
                                </div> 
                                <div class="col-3">
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
                                    <th style="padding-right: 2px !important;">#</th>
                                    <th @click="sort(7)">
                                        Transfer By
                                        <i class="feather icon-chevron-up sort-up" :class="(tableOptions.orderField == 7 && tableOptions.orderDir =='asc')? 'active-sort': ''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 7 && tableOptions.orderDir =='desc')? 'active-sort': ''"></i>
                                    </th>
                                    <th @click="sort(2)">
                                        Movement Type
                                        <i class="feather icon-chevron-up sort-up" :class="(tableOptions.orderField == 2 && tableOptions.orderDir =='asc')? 'active-sort': ''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 2 && tableOptions.orderDir =='desc')? 'active-sort': ''"></i>
                                    </th>
                                    <th @click="sort(4)">
                                        Origin
                                        <i class="feather icon-chevron-up sort-up" :class="(tableOptions.orderField == 4 && tableOptions.orderDir =='asc')? 'active-sort': ''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 4 && tableOptions.orderDir =='desc')? 'active-sort': ''"></i>
                                    </th>
                                    <th @click="sort(6)">
                                        Destination
                                        <i class="feather icon-chevron-up sort-up" :class="(tableOptions.orderField == 6 && tableOptions.orderDir =='asc')? 'active-sort': ''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 6 && tableOptions.orderDir =='desc')? 'active-sort': ''"></i>
                                    </th>
                                    <th @click="sort(1)" style="padding-left: 5px !important; padding-right: 10px !important">
                                        Total
                                        <i class="feather icon-chevron-up sort-up" :class="(tableOptions.orderField == 1 && tableOptions.orderDir =='asc')? 'active-sort': ''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 1 && tableOptions.orderDir =='desc')? 'active-sort': ''"></i>
                                    </th>
                                    <th @click="sort(8)">
                                        Date
                                        <i class="feather icon-chevron-up sort-up" :class="(tableOptions.orderField == 8 && tableOptions.orderDir =='asc')? 'active-sort': ''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 8 && tableOptions.orderDir =='desc')? 'active-sort': ''"></i>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(g, i) in tableData" :class="checkedBg(g.pick)">
                                    <td style="padding-right: 2px !important;">{{i+1}}</td>
                                    <td>
                                        {{g.user_fullname}}
                                    </td>
                                    <td>
                                        <div class="d-flex justify-content-left align-items-center">
                                            <div class="d-flex flex-column">
                                                <a href="#" class="user_name text-truncate text-body">
                                                    <span class="fw-bolder text-primary">{{capitalize(g.move_type)}}</span>
                                                </a>
                                                <small class="emp_post text-muted">{{capitalize(g.origin_level)}} <i class="feather icon-arrow-right"></i> {{capitalize(g.destination_level)}}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        {{capitalize(g.origin)}}
                                    </td>
                                    <td>
                                        {{capitalize(g.destination)}}
                                    </td>
                                    <td style="padding-left: 5px !important; padding-right: 10px !important">{{g.total.toLocaleString()}}</td>
                                    <td>
                                        {{displayDate(g.created)}}
                                    </td>
                                </tr>
                                <tr v-if="tableData.length == 0"><td class="text-center pt-2" colspan="7"><small>No Training Added</small></td></tr>

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

            
            <!-- Details Modal Starts -->
            <div class="modal fade modal-primary" id="viewDetails" tabindex="-1" role="dialog" aria-labelledby="exampleModalScrollableTitle" aria-hidden="true">
                <div class="modal-dialog modal-dialog-scrollable modal-lg" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalScrollableTitle" v-html="isLgabalance == true? 'LGA e-Netcard Balances': 'Ward e-Netcard Balances'" cloak></h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close" @click="wardNetBalancesData = []">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                           
                            <div v-if="isLgabalance == true" class="table-responsive mt-3">
                                <table class="table table-bordered table-hover">
                                    <thead>
                                        <tr>
                                            <th style="padding-right: 2px !important;">#</th>
                                            <th>LGA Name</th>
                                            <th>Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="(g, i) in lgaNetBalancesData">
                                            <td>{{i+1}}</td>
                                            <td>{{g.lga}}</td>
                                            <td>{{g.total}}</td>
                                        </tr>
                                        <tr v-if="lgaNetBalancesData.length == 0"><td class="text-center pt-4 pb-4" colspan="3"><small>No LGA with Balances</small></td></tr>

                                    </tbody>
                                </table>
                            </div>
                            <div v-else class="table-responsive mt-1">
                            
                                <div class="form-group">
                                    <label class="form-label" for="user-role">Choose LGA to View Ward Balances</label>
                                    <select  required class="form-control" @change="getWardData($event)">
                                        <option value="" selected>Choose LGA to View</option>
                                        <option v-for="(lga, i) in lgaLevelData" :value="lga.lgaid">{{lga.lga}}</option>
                                    </select>
                                </div>

                                <table class="table table-bordered table-hover">
                                    <thead>
                                        <tr>
                                            <th style="padding-right: 2px !important;">#</th>
                                            <th>Ward Name</th>
                                            <th>Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="(g, i) in wardNetBalancesData">
                                            <td>{{i+1}}</td>
                                            <td>{{g.ward}}</td>
                                            <td>{{g.total}}</td>
                                        </tr>
                                        <tr v-if="wardNetBalancesData.length == 0"><td class="text-center font-small-3 pt-4 pb-4" colspan="3"><span class="text-info">No Ward with Balances</span> or <span class="text-warning">Kindly check if LGA was selected</span></td></tr>

                                    </tbody>
                                </table>
                            </div>

                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-primary" data-dismiss="modal" @click="wardNetBalancesData = []">Close</button>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Details Modal Ends -->


            <!-- Modal to Move State Netcard starts-->
            <div class="modal modal-slide-in move modal-primary" id="stateMove" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-scrollable modal-xl">
                    <form class="add-new-user modal-content pt-0" @submit.stop.prevent="transferFromStateToLGA()" id="state-form">
                        <button type="reset" class="close" @click="hideStateMoveModal()" data-dismiss="modal">×</button>
                        <div class="modal-header mb-1">
                            <h5 class="modal-title font-weight-bolder" id="exampleModalLabel">State Movement</h5>
                        </div>
                        <div class="modal-body flex-grow-1 vertical-wizard">
                            <!--
                            <div class="form-group pb-50">
                                <div class="form-group">
                                    <label class="form-label full" for="total-user">Total Netcard to Transfer</label>
                                    <input type="number" id="state-spin" required v-model="stateMovementForm.totalNetcard" value="" class="touchspin-min-max-total total-spin t-state" />
                                </div>
                                <div class="input-group mt-2 mb-1 full">
                                    <input type="number" id="state-spin" required v-model="stateMovementForm.totalNetcard" value="" class="touchspin-min-max-total total-spin t-state" />
                                </div>
                            </div>
                            -->

                            <div class="form-group">
                                <label class="form-label full" for="total-user">Total Netcard to Transfer</label>
                                <input type="number" id="state-spin" placeholder="Total Netcard to Transfer" @keypress="onlyNumber($event)" required v-model="stateMovementForm.totalNetcard" value="" class="form-control" />
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="user-role">Originating State</label>
                                <select  placeholder="Select Geo Level" class="form-control" v-model="stateMovementForm.stateid">
                                    <option :value="sysDefaultData.stateid" :selected="sysDefaultData.stateid == stateMovementForm.stateid">{{sysDefaultData.state}}</option>
                                </select>
                            </div>
                        
                            <div class="form-group">
                                <label class="form-label" for="user-role">Destination LGA</label>
                                <select  required class="form-control" v-model="stateMovementForm.lgaid" @change="setLgaName($event)">
                                    <option value="" :selected="stateMovementForm.lgaid ==''">Choose a Destination LGA</option>
                                    <option v-for="lga in lgaLevelData" :value="lga.lgaid">{{lga.lga}}</option>
                                </select>
                            </div>

                            <div class="mt-4">
                                <button type="submit" class="btn btn-primary mr-1 data-submit">Transfer eNetCard</button>
                                <button type="reset" class="btn btn-outline-secondary" data-dismiss="modal" @click="hideStateMoveModal()">Cancel</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <!-- Modal to Move State Netcard Ends-->


            <!-- Modal to Move LGA Netcard starts-->
            <div class="modal modal-slide-in move" id="lgaMove" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-scrollable modal-xl">
                    <form class="add-new-user modal-content pt-0" @submit.stop.prevent="lgaTransfer(lgaMovementForm.lgaMoveBtn)">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close" @click="hideLgaMoveModal()">×</button>
                        <div class="modal-header mb-1">
                            <h5 v-if="lgaMovementForm.lgaMoveBtn =='Forward'" class="modal-title font-weight-bolder text-capitalize" id="exampleModalLabel">Transfer e-Netcard From <span class="text-info">LGA </span> to <span class="text-success">Ward</span></h5>
                            <h5 v-else class="modal-title font-weight-bolder text-capitalize" id="exampleModalLabel">Reverse e-Netcard from <span class="text-info">LGA </span> to <span class="text-success">State</span></h5>
                        </div>
                        <div class="modal-body flex-grow-1 vertical-wizard mt-1">

                            <div class="bs-stepper vertical vertical-wizard-example">
                                <div class="bs-stepper-header mb-50">
                                    <label class="form-label">Kindly Check an Options</label>
                                    <div class="step" :class="lgaMovementForm.lgaMoveBtn == 'Forward'? 'active': ''" data-target="#account-details-vertical">
                                        <button type="button" class="step-trigger" aria-selected="true" @click="lgaMovementForm.lgaMoveBtn = 'Forward'">
                                            <span class="bs-stepper-box"><i class="feather" :class="lgaMovementForm.lgaMoveBtn == 'Forward'? 'icon-check': 'icon-x'"></i></span>
                                            <span class="bs-stepper-label">
                                                <span class="bs-stepper-title">Forward Movement</span>
                                                <span class="bs-stepper-subtitle">From State to LGA</span>
                                            </span>
                                        </button>
                                    </div>

                                    <div class="step" :class="lgaMovementForm.lgaMoveBtn == 'Reverse'? 'active': ''" data-target="#personal-info-vertical">
                                        <button type="button" class="step-trigger" aria-selected="false"  @click="setLgaReverseVariable()">
                                            <span class="bs-stepper-box"><i class="feather" :class="lgaMovementForm.lgaMoveBtn == 'Reverse'? 'icon-check': 'icon-x'"></i></span>
                                            <span class="bs-stepper-label">
                                                <span class="bs-stepper-title">Reverse Movement</span>
                                                <span class="bs-stepper-subtitle">From LGA to State</span>
                                            </span>
                                        </button>
                                    </div>
                                    
                                </div>
                            
                            </div>

                            <!--
                            <label class="form-label full" for="total-user">Number of Netcard to Move</label>
                            <div class="input-group mb-1 mt-2 full">
                                <input type="number" required v-model="lgaMovementForm.totalNetcard" class="touchspin-min-max total-spin" />
                            </div>
                            -->

                            
                            <!-- Reverse Block: Starts -->
                            <div v-if="lgaMovementForm.lgaMoveBtn == 'Reverse'">
                                <div class="form-group">
                                    <label class="form-label" for="user-role">Originating LGA</label>
                                    <select  required class="form-control" v-model="lgaMovementForm.originid" @change="setLgaOriginName($event)">
                                        <option value="" selected>Choose a LGA to Reverse From</option>
                                        <option v-for="(lga, i) in lgaNetBalancesData" :value="lga.lgaid">{{lga.lga}} - {{lga.total}}</option>
                                    </select>
                                    <label><b class="text-danger">Origin Balance:</b> {{formatNumber(parseInt(lgaMovementForm.originBalance))}}</label>
                                </div>

                                <div class="form-group">
                                    <label class="form-label" for="user-role">Destination State</label>
                                    <select   class="form-control" v-model="lgaMovementForm.destinationid" @change="setLgaDestinationName($event)">
                                        <option :value="sysDefaultData.stateid" :selected="sysDefaultData.stateid == 'lgaMovementForm.destinationid'? 'selected': ''">{{sysDefaultData.state}}</option>
                                    </select>
                                </div>
                            </div>
                            <!-- Reverse Block: Ends -->

                            <!-- Froward Block: Starts -->

                            <div v-else>
                                <div class="form-group">
                                    <label class="form-label" for="user-role">Originating LGA</label>
                                    <select  required class="form-control" v-model="lgaMovementForm.originid" @change="setLgaOriginName($event)">
                                        <option value="" selected>Choose a LGA to Transfer From</option>
                                        <option v-for="(lga, i) in lgaNetBalancesData" :value="lga.lgaid">{{lga.lga}} - {{lga.total}}</option>
                                    </select>
                                    <label><b class="text-danger">Origin Balance:</b> {{formatNumber(parseInt(lgaMovementForm.originBalance))}}</label>
                                </div>

                                <div class="form-group">
                                    <label class="form-label" for="user-role">Destination Ward</label>
                                    <select class="form-control" v-model="lgaMovementForm.destinationid" @change="setLgaDestinationName($event)">
                                        <option value="" selected>Choose a Ward to Transfer To</option>
                                        <option v-for="g in wardLevelData" :value="g.wardid">{{g.ward}}</option>
                                    </select>
                                </div>

                            </div>
                            <!-- Froward Block: Ends -->

                            <div class="form-group">
                                <label class="form-label" for="total-user">Total Netcards to Move</label>
                                <input type="number" required v-model="lgaMovementForm.totalNetcard" @keypress="onlyNumber($event)" placeholder="Total Netcards to Move" class="form-control" />
                            </div>

                            <div class="mt-2">
                                <button type="submit" class="btn btn-primary mr-1 data-submit">{{lgaMovementForm.lgaMoveBtn}} eNetCard</button>
                                <button type="reset" class="btn btn-outline-secondary" data-dismiss="modal" @click="hideLgaMoveModal()">Cancel</button>
                            </div>

                        </div>
                    </form>
                </div>
            </div>
            <!-- Modal to Move LGA Netcard Ends-->


            <!-- Modal to Move Netcard FROM LGA to Ward Starts-->

            <!-- Modal to Move LGA Netcard starts-->
            <div class="modal modal-slide-in move" id="wardMove" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-scrollable modal-xl">
                    <form class="add-new-user modal-content pt-0" @submit.stop.prevent="wardTransfer()">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close" @click="hideWardMoveModal()">×</button>
                        <div class="modal-header mb-1">
                            <h5 class="modal-title font-weight-bolder text-capitalize" id="exampleModalLabel">Reverse e-Netcard From <span class="text-info">Ward </span> to <span class="text-success">LGA</span></h5>
                        </div>
                        <div class="modal-body flex-grow-1 vertical-wizard mt-1">

                            <div>
                                <div class="form-group">
                                    <label class="form-label" for="user-role">Destination LGA</label>
                                    <select  required class="form-control" v-model="wardMovementForm.destinationid" @change="setWardDestinationName($event)">
                                        <option value="" selected>Choose a LGA to Transfer To</option>
                                        <option v-for="lga in lgaLevelData" :value="lga.lgaid">{{lga.lga}}</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label class="form-label" for="user-role">Originating Ward</label>
                                    <select class="form-control" v-model="wardMovementForm.originid" @change="setWardOriginName($event)">
                                        <option value="" selected>Choose a Ward to Reverse From</option>
                                        <option v-for="g in wardNetBalancesData" :value="g.wardid">{{g.ward}} - {{g.total}}</option>
                                    </select>
                                    <label><b class="text-danger">Origin Balance:</b> {{formatNumber(parseInt(wardMovementForm.originBalance))}}</label>
                                </div>

                            </div>

                            <div class="form-group">
                                <label class="form-label" for="total-user">Total Netcards to Move</label>
                                <input type="number" required placeholder="Total Netcards to Move" @keypress="onlyNumber($event)" v-model="wardMovementForm.totalNetcard" class="form-control" />
                            </div>

                            <div class="mt-2">
                                <button type="submit" class="btn btn-primary mr-1 data-submit">Reverse eNetCard</button>
                                <button type="reset" class="btn btn-outline-secondary" data-dismiss="modal" @click="hideWardMoveModal()">Cancel</button>
                            </div>

                        </div>
                    </form>
                </div>
            </div>
            <!-- Modal to Move Netcard FROM LGA to Ward Ends-->

        </div>
    `,
});

var vm = new Vue({
  el: "#app",
  data: {},
  methods: {},
  template: `
        <div>
            <page-body/>
        </div>
    `,
});
