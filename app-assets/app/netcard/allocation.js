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
      page: "allocation", //  page by name training | session | participant | attendance ...
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

            <div v-show="page == 'allocation'">
                <necard_movement/>
            </div>
            <!--
                <div v-show="page == 'movement'">
                    <allocation_movement/>
                </div>
            -->

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
        aLength: [10, 20, 50, 100, 150, 200],
        filterParam: {
          movementType: "Forward",
          requester_loginid: "",
          mobilizer_loginid: "",
          request_date: "",
        },
      },
      currentWardBalance: {
        wardName: "",
        balance: 0,
        disbursed: 0,
        received: 0,
      },
      wardMovementForm: {
        totalNetcard: 1,
        wardMoveBtn: "",
        wardMoveModal: false,
        lgaid: "",
        wardid: "",
        wardName: "",
        wardBalance: "",
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
      hhmBalanacesData: [],
      isLgabalance: true,
      isHHMbalance: true,
      allStatistics: {
        stateBalance: 0,
        lgaBalance: 0,
        wardBalance: 0,
        mobilizer: 0,
        beneficiary: 0,
      },
    };
  },
  mounted() {
    /*  Manages events Listening    */
    $(".date").flatpickr({
      altInput: true,
      altFormat: "F j, Y",
      dateFormat: "Y-m-d",
    });
    this.getsysDefaultDataSettings();
    this.getLgasNetBalances();
    this.loadTableData();
    this.getAllStat();
    EventBus.$on("g-event-update", this.loadTableData);
    // $('#wardMovement').modal('show');
    $("#todo-search").on("keyup", function () {
      var value = $(this).val().toLowerCase();
      $("#moveTable tbody tr").filter(function () {
        $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
      });
    });
    $("#todo-search1").on("keyup", function () {
      var value = $(this).val().toLowerCase();
      $("#moveTable1 tbody tr").filter(function () {
        $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
      });
    });

    // Tooltip Initialization
    $('[data-toggle="tooltip"]').tooltip({
      container: "body",
    });
    this.scroll();
  },
  methods: {
    loadTableData() {
      /*  Manages the loading of table data */

      overlay.show();
      var self = this;
      var url = common.TableService;
      if (self.tableOptions.filterParam.movementType == "Reverse") {
        var endpoints = [
          url +
            "?qid=203&draw=" +
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
            self.tableOptions.filterParam.movementType +
            "&rid=" +
            self.tableOptions.filterParam.requester_loginid +
            "&mid=" +
            self.tableOptions.filterParam.mobilizer_loginid +
            "&rda=" +
            self.tableOptions.filterParam.request_date,
        ];
      } else if (self.tableOptions.filterParam.movementType == "Forward") {
        var endpoints = [
          url +
            "?qid=202&draw=" +
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
            self.tableOptions.filterParam.movementType +
            "&rid=" +
            self.tableOptions.filterParam.requester_loginid +
            "&mid=" +
            self.tableOptions.filterParam.mobilizer_loginid +
            "&rda=" +
            self.tableOptions.filterParam.request_date,
        ];
      } else {
        var endpoints = [
          url +
            "?qid=204&draw=" +
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
            self.tableOptions.filterParam.movementType +
            "&rid=" +
            self.tableOptions.filterParam.requester_loginid +
            "&mid=" +
            self.tableOptions.filterParam.mobilizer_loginid +
            "&rda=" +
            self.tableOptions.filterParam.request_date,
        ];
      }

      // Return our response in the allData variable as an array
      Promise.all(endpoints.map((endpoint) => axios.get(endpoint))).then(
        axios.spread((...allData) => {
          overlay.show();

          // Get table Data
          self.tableData = allData[0].data.data; //All Data
          self.tableOptions.total = allData[0].data.recordsTotal; //Total Records
          if (self.tableOptions.currentPage == 1) {
            self.paginationDefault();
          }

          overlay.hide();
        }),
      );
    },
    selectAll() {
      /*  Manages all check box selection checked */
      if (this.hhmBalanacesData.length > 0) {
        for (let i = 0; i < this.hhmBalanacesData.length; i++) {
          this.hhmBalanacesData[i].pick = true;
        }
      }
    },
    uncheckAll() {
      /*  Manages unchecking of all check box checked */
      if (this.hhmBalanacesData.length > 0) {
        for (let i = 0; i < this.hhmBalanacesData.length; i++) {
          this.hhmBalanacesData[i].pick = false;
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
      if (this.hhmBalanacesData.length > 0) {
        for (let i = 0; i < this.hhmBalanacesData.length; i++) {
          if (this.hhmBalanacesData[i].pick) {
            selectedItems.push(this.hhmBalanacesData[i]);
          }
        }
      }
      return selectedItems;
    },
    selectedItemsCount() {
      // if (this.wardMovementForm.wardMoveBtn == 'Reverse' && this.selectedItems().length + 1 > 1) {
      //     // alert.Error("Selection Error", "You can't Select more than <b>1</b> HHM for e-Netcard Reversal");
      // }
    },
    forwardReverseSelectedID() {
      /*  Manages the selections of checkedor selected data object */
      let selectedIds = [];
      let id = $("#v_g_id").val();
      if (this.hhmBalanacesData.length > 0) {
        for (let i = 0; i < this.hhmBalanacesData.length; i++) {
          if (this.hhmBalanacesData[i].pick) {
            // 'total'=>10, 'wardid'=>1, 'mobilizerid'=>4, 'userid'=>2
            selectedIds.push({
              total: this.wardMovementForm.totalNetcard,
              wardid: this.wardMovementForm.wardid,
              mobilizerid: this.hhmBalanacesData[i].userid,
              mobilizer_balance: this.hhmBalanacesData[i].balance,
              mobilizer_loginid: this.hhmBalanacesData[i].loginid,
              userid: id,
              device_serial: this.hhmBalanacesData[i].device_serial,
            });
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
        this.tableOptions.total / this.tableOptions.perPage,
      );

      // Page Limit
      this.tableOptions.limitStart = Math.ceil(
        (this.tableOptions.currentPage - 1) * this.tableOptions.perPage,
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
      // checkFill += this.tableOptions.filterParam.movementType != "" ? 1 : 0;
      checkFill +=
        this.tableOptions.filterParam.requester_loginid != "" ? 1 : 0;
      checkFill +=
        this.tableOptions.filterParam.mobilizer_loginid != "" ? 1 : 0;
      checkFill += this.tableOptions.filterParam.request_date != "" ? 1 : 0;

      if (checkFill > 0) {
        this.toggleFilter();
        this.filters = true;
        this.paginationDefault();
        this.loadTableData();
      } else {
        alert.Error("ERROR", "Atleast one Filter field must be filled");
        return;
      }
    },
    removeSingleFilter(column_name) {
      // this.tableOptions.filterParam + '.' + column_name == "";
      this.tableOptions.filterParam[column_name] = "";
      if (column_name == "request_date") {
        this.clearDate("request_date");
      }

      let g = 0;
      for (let i in this.tableOptions.filterParam) {
        if (this.tableOptions.filterParam[i] != "" && i != "movementType") {
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
      this.tableOptions.filterParam.requester_loginid =
        this.tableOptions.filterParam.mobilizer_loginid =
        this.tableOptions.filterParam.request_date =
          "";
      this.clearDate("request_date");
      this.paginationDefault();
      this.loadTableData();
    },
    clearDate(id) {
      $("#" + id)
        .flatpickr({
          altInput: true,
          altFormat: "F j, Y",
          dateFormat: "Y-m-d",
        })
        .clear();
    },
    showWardMoveModal(movement_type) {
      overlay.show();
      this.scroll();
      this.wardMovementForm.totalNetcard = 1;
      this.wardMovementForm.wardMoveBtn = movement_type;
      this.wardMovementForm.wardMoveModal = true;
      this.wardMovementForm.lgaid = "";
      this.wardMovementForm.wardid = "";
      this.wardMovementForm.wardName = "";
      this.wardMovementForm.wardBalance = "";
      overlay.hide();
    },
    hideWardMoveModal() {
      overlay.show();
      $("#wardMovement").modal("hide");
      this.wardMovementForm.totalNetcard = 0;
      this.wardMovementForm.wardMoveBtn = "";
      this.wardMovementForm.wardMoveModal = false;
      this.wardMovementForm.lgaid = "";
      this.wardMovementForm.wardid = "";
      this.wardMovementForm.wardName = "";
      this.wardMovementForm.wardBalance = "";
      this.geoIndicator.lga = "";
      this.hhmBalanacesData = [];
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
                .split("-")[1],
            );
    },
    setWardDestinationName(event) {
      this.wardMovementForm.originBalance = 0;
      this.wardMovementForm.originid = "";
      // this.getWardsNetBalances();
      this.wardMovementForm.destinationName = "";
      this.wardMovementForm.destinationName =
        event.target.options[event.target.options.selectedIndex].text;
    },
    wardTransfer() {
      var self = this;
      var url = common.DataService;
      // overlay.show();
      selectedId = self.forwardReverseSelectedID();

      if (parseInt(self.wardMovementForm.totalNetcard) > 0) {
        if (self.wardMovementForm.wardMoveBtn == "Forward") {
          if (
            typeof self.wardMovementForm.wardid !== "undefined" &&
            self.wardMovementForm.wardid &&
            typeof self.wardMovementForm.totalNetcard !== "undefined" &&
            self.wardMovementForm.totalNetcard
          ) {
            if (self.wardMovementForm.wardBalance > 0) {
              if (selectedId.length > 0) {
                let len = parseInt(selectedId.length);
                let checkIfSharable =
                  parseInt(self.wardMovementForm.wardBalance) /
                  parseInt(self.wardMovementForm.totalNetcard);
                let totalSharable = Math.floor(
                  self.wardMovementForm.wardBalance / len,
                );

                if (checkIfSharable >= selectedId.length) {
                  $.confirm({
                    title: "WARNING!",
                    content:
                      "Are you sure you want to allocate <b>" +
                      self.wardMovementForm.totalNetcard +
                      "</b> e-Netcard each to <b>" +
                      len +
                      "</b> selected HH Mobilizers<b>?",
                    buttons: {
                      delete: {
                        text: "Allocate e-Netcard",
                        btnClass: "btn btn-danger mr-1 text-capitalize",
                        action: function () {
                          //Attempt Update
                          axios
                            .post(url + "?qid=209", JSON.stringify(selectedId))
                            .then(function (response) {
                              if (response.data.result_code == "200") {
                                // self.hideWardMoveModal();
                                self.tableOptions.filterParam.movementType =
                                  "Forward";
                                self.refreshHHMList();
                                self.refreshData();
                                alert.Success(
                                  "Success",
                                  response.data.total +
                                    " e-Netcards has been successfully allocated to <b>" +
                                    len +
                                    "</b> HH Mobilizers",
                                );
                                self.wardMovementForm.totalNetcard = 1;
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
                    "Balance Exceeded",
                    "You don't have enough e-Netcard to allocate. You can only share <b>" +
                      totalSharable +
                      "</b> e-Netcard for the <b>" +
                      len +
                      "</b> selected HHM",
                  );
                }
              } else {
                alert.Error("Error", "No Mobilizer Selected for transfer");
              }
            } else {
              alert.Error("Error", "You don't have e-Netcard to transfer");
            }
          } else {
            alert.Error("Require Fields", "All fields are required");
          }
        } else {
          if (selectedId.length == 1) {
            // alert.Success("HHM Balance: ", selectedId[0].mobilizer_balance, selectedId[0].mobilizer_loginid)
            if (
              typeof self.wardMovementForm.wardid !== "undefined" &&
              self.wardMovementForm.wardid &&
              typeof self.wardMovementForm.totalNetcard !== "undefined" &&
              self.wardMovementForm.totalNetcard
            ) {
              if (selectedId[0].mobilizer_balance > 0) {
                if (selectedId.length > 0) {
                  let totalReversable = selectedId[0].mobilizer_balance
                    ? parseInt(selectedId[0].mobilizer_balance)
                    : 0;
                  // Check if user request total isnot greater than the available balance
                  if (self.wardMovementForm.totalNetcard <= totalReversable) {
                    //If the e-Netcard has been downloaded on a device
                    if (selectedId[0].device_serial != null) {
                      //
                      $.confirm({
                        title: "WARNING!",
                        content:
                          "Are you sure you want to Retract <b>" +
                          self.wardMovementForm.totalNetcard +
                          "</b> e-Netcard from HH Mobilizers with Login ID: <b>" +
                          selectedId[0].mobilizer_loginid +
                          "</b>?",
                        buttons: {
                          delete: {
                            text: "Reverse e-Netcard",
                            btnClass: "btn btn-danger mr-1 text-capitalize",
                            action: function () {
                              //Attempt Update
                              axios
                                .post(
                                  url + "?qid=210",
                                  JSON.stringify(selectedId),
                                )
                                .then(function (response) {
                                  if (response.data.result_code == "200") {
                                    alert.Success(
                                      "Success",
                                      response.data.total +
                                        " e-Netcards Reverse order has been successfully placed",
                                    );
                                    // self.hideWardMoveModal();
                                    self.tableOptions.filterParam.movementType =
                                      "Reverse";
                                    self.refreshHHMList();
                                    self.refreshData();
                                    self.wardMovementForm.totalNetcard = 1;
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
                    // If e-Netcard is still online
                    else {
                      //If the e-Netcard is still online
                      // console.log(selectedId);
                      $.confirm({
                        title: "WARNING!",
                        content:
                          "Are you sure you want to Retract <b>" +
                          self.wardMovementForm.totalNetcard +
                          "</b> e-Netcard <b>Online</b> from HH Mobilizers with Login ID: <b>" +
                          selectedId[0].mobilizer_loginid +
                          "</b>?",
                        buttons: {
                          delete: {
                            text: "Reverse e-Netcard",
                            btnClass: "btn btn-danger mr-1 text-capitalize",
                            action: function () {
                              //Attempt Update
                              axios
                                .post(
                                  url + "?qid=212",
                                  JSON.stringify(selectedId),
                                )
                                .then(function (response) {
                                  if (response.data.result_code == "200") {
                                    alert.Success(
                                      "Success",
                                      response.data.total +
                                        " Online e-Netcards Reverse successfull",
                                    );
                                    // self.hideWardMoveModal();
                                    self.tableOptions.filterParam.movementType =
                                      "ReverseOnline";
                                    self.refreshHHMList();
                                    self.refreshData();
                                    self.wardMovementForm.totalNetcard = 1;
                                    overlay.hide();
                                  } else if (
                                    response.data.result_code == "401"
                                  ) {
                                    alert.Success(
                                      "Error",
                                      response.data.message,
                                    );
                                    // self.hideWardMoveModal();
                                    self.refreshHHMList();
                                    self.tableOptions.filterParam.movementType =
                                      "ReverseOnline";
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
                      "HHM Balance Exceeded",
                      "Selected HHM doesn't enough e-Netcard. You can only reverse <b>" +
                        totalReversable +
                        "</b> e-Netcard from the selected HHM",
                    );
                  }
                } else {
                  alert.Error(
                    "HHM Selection Error",
                    "No Mobilizer Selected for Reverse Transaction",
                  );
                }
              } else {
                alert.Error(
                  "Error",
                  "HHM with Login ID: <b>" +
                    selectedId[0].mobilizer_loginid +
                    "</b> doesn't have e-Netcard balances to Reverse",
                );
              }
            } else {
              alert.Error("Require Fields", "All fields are required");
            }
          } else {
            alert.Error(
              "Error",
              "You must select <b>1</b> Household Mobilizer to <b>Reverse From</b>",
            );
          }
        }
      } else {
        alert.Error(
          "Error",
          "You can't " +
            self.wardMovementForm.wardMoveBtn +
            " <b>0</b> e-Netcard",
        );
      }

      overlay.hide();
    },
    goToMovement(movement_type) {
      EventBus.$emit("g-event-goto-page", {
        page: "movement",
        movementType: movement_type,
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
            // self.stateMovementForm.stateid = response.data.data[0].stateid;
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
    getWardLevel(event) {
      /*  Manages the loading of Geo Level data */
      var self = this;
      var url = common.DataService;
      overlay.show();
      self.wardMovementForm.wardid = "";
      axios
        .get(url + "?qid=gen005&e=" + self.wardMovementForm.lgaid)
        .then(function (response) {
          self.wardLevelData = response.data.data; //All Data
          overlay.hide();
        })
        .catch(function (error) {
          overlay.hide();
          alert.Error("ERROR", error);
        });
    },
    getWardData(event) {
      /*  Get ward list with balances with lgaid */
      var self = this;
      var url = common.DataService;
      overlay.show();
      self.wardMovementForm.wardid = "";
      self.wardMovementForm.lgaid =
        event.target.options[event.target.options.selectedIndex].value;
      axios
        // .get(url + "?qid=207&lgaid=" + self.wardMovementForm.lgaid)
        .get(
          url +
            "?qid=gen005&lgaid=" +
            self.wardMovementForm.lgaid +
            "&e=" +
            self.wardMovementForm.lgaid,
        )
        .then(function (response) {
          self.wardNetBalancesData = response.data.data; //All Data
          self.wardLevelData = response.data.data; //All Data
          overlay.hide();
        })
        .catch(function (error) {
          overlay.hide();
          alert.Error("ERROR", error);
        });
    },
    getHhmBalances(event) {
      /*  Get HHM Balances with Wardid */

      let self = this;
      let current_endpoint =
        self.wardMovementForm.wardMoveBtn == "Reverse" ? "208" : "211";
      self.wardMovementForm.wardName = event.target.options[
        event.target.options.selectedIndex
      ].text
        .trim()
        .replace(",", "")
        .split("-")[0];
      /*
        self.wardMovementForm.wardBalance =
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
      */
      self.currentWardBalance.wardName =
        event.target.options[event.target.options.selectedIndex].text;

      var url = common.DataService;
      overlay.show();
      self.getCurrentWardBalance();
      axios
        .get(
          url +
            "?qid=" +
            current_endpoint +
            "&wardid=" +
            self.wardMovementForm.wardid,
        )
        .then(function (response) {
          self.hhmBalanacesData = response.data.data; //All Data
          overlay.hide();
        })
        .catch(function (error) {
          overlay.hide();
          alert.Error("ERROR", error);
        });
    },
    HHMBalancesRefresh() {
      /*  Get HHM Balances with Wardid */

      let self = this;
      let current_endpoint =
        self.wardMovementForm.wardMoveBtn == "Reverse" ? "208" : "211";

      var url = common.DataService;
      overlay.show();
      if (self.wardMovementForm.wardid == "") {
        alert.Error("Ward Selection Error", "Please select a ward first");
        overlay.hide();
        return;
      }
      self.getCurrentWardBalance();
      axios
        .get(
          url +
            "?qid=" +
            current_endpoint +
            "&wardid=" +
            self.wardMovementForm.wardid,
        )
        .then(function (response) {
          self.hhmBalanacesData = response.data.data; //All Data
          overlay.hide();
        })
        .catch(function (error) {
          overlay.hide();
          alert.Error("ERROR", error);
        });
    },
    refreshHHMList() {
      /*  Get HHM Balances with Wardid */

      let self = this;
      let current_endpoint =
        self.wardMovementForm.wardMoveBtn == "Reverse" ? "208" : "211";

      var url = common.DataService;
      overlay.show();
      self.getCurrentWardBalance();
      axios
        .get(
          url +
            "?qid=" +
            current_endpoint +
            "&wardid=" +
            self.wardMovementForm.wardid,
        )
        .then(function (response) {
          self.hhmBalanacesData = response.data.data; //All Data
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
      var table_url = common.TableService;
      var self = this;
      var endpoints = [url + "?qid=201"];

      // Return our response in the allData variable as an array
      Promise.all(endpoints.map((endpoint) => axios.get(endpoint))).then(
        axios.spread((...allData) => {
          overlay.show();
          //Check if this location state|lga|ward are with balances
          for (let i = 0; i < allData[0].data.data.length; i++) {
            if (allData[0].data.data[i].location === "state") {
              self.allStatistics.stateBalance = allData[0].data.data[i].total
                ? allData[0].data.data[i].total
                : 0;
            } else if (allData[0].data.data[i].location === "lga") {
              self.allStatistics.lgaBalance = allData[0].data.data[i].total
                ? allData[0].data.data[i].total
                : 0;
            } else if (allData[0].data.data[i].location === "ward") {
              self.allStatistics.wardBalance = allData[0].data.data[i].total
                ? allData[0].data.data[i].total
                : 0;
            } else if (allData[0].data.data[i].location === "mobilizer") {
              self.allStatistics.mobilizer = allData[0].data.data[i].total
                ? allData[0].data.data[i].total
                : 0;
            } else if (allData[0].data.data[i].location === "beneficiary") {
              self.allStatistics.beneficiary = allData[0].data.data[i].total
                ? allData[0].data.data[i].total
                : 0;
            }
          }

          overlay.hide();
        }),
      );
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
    scroll() {
      // if it is not touch device
      var sidebarMenuList = $(".main-body");
      if (!$.app.menu.is_touch_device()) {
        if (sidebarMenuList.length > 0) {
          for (i = 0; i < sidebarMenuList.length; ++i) {
            var sidebarListScrollbar = new PerfectScrollbar(
              sidebarMenuList[i],
              {
                theme: "dark",
              },
            );
          }
        }
      }
      // if it is a touch device
      else {
        sidebarMenuList.css("overflow", "scroll");
      }
    },
    onlyNumber($event) {
      //console.log($event.keyCode); //keyCodes value
      let keyCode = $event.keyCode ? $event.keyCode : $event.which;
      if ((keyCode < 48 || keyCode > 57) && keyCode == 46) {
        // 46 is dot
        $event.preventDefault();
      }
    },
    hideHHMBalanceModal() {
      overlay.show();
      this.wardMovementForm.lgaid = "";
      this.wardMovementForm.wardid = "";
      $("#viewDetails").modal("hide");
      overlay.hide();
    },
    getCurrentWardBalance() {
      let self = this;
      var url = common.DataService;
      axios
        .get(url + "?qid=214&wardid=" + self.wardMovementForm.wardid)
        .then(function (response) {
          self.currentWardBalance.balance = response.data.data[0]["balance"]
            ? parseInt(response.data.data[0]["balance"])
            : 0; //All Data
          self.wardMovementForm.wardBalance = self.currentWardBalance.balance;
          self.currentWardBalance.received = response.data.data[0]["received"]
            ? parseInt(response.data.data[0]["received"])
            : 0; //All Data
          self.currentWardBalance.disbursed = response.data.data[0]["disbursed"]
            ? parseInt(response.data.data[0]["disbursed"])
            : 0; //All Data
          overlay.hide();
        })
        .catch(function (error) {
          overlay.hide();
          alert.Error("ERROR", error);
        });
    },
  },
  template: `

        <div class="row" id="basic-table" v-cloak>

            <div class="col-md-12 col-sm-12 col-12 mb-1">
                <h2 class="content-header-title header-txt float-left mb-0">e-Netcard</h2>
                <div class="breadcrumb-wrapper">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../netcard">Home</a></li>
                        <li class="breadcrumb-item active">e-Netcard Allocation</li>
                    </ol>
                </div>
            </div>

            <!-- Stats Horizontal Card -->
            <!-- <div class="col-lg-6 col-sm-6 col-12"> -->
            <div class="col-lg-4 col-sm-4 col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <span>Ward Balance</span>
                            <div class="avatar bg-light-primary p-50 m-0 mt--1">
                                <div class="avatar-content">
                                    <i data-feather="target" class="font-medium-4"></i>
                                </div>
                            </div>
                        </div>
                        <div class="d-block justify-content-between align-items-end mt-50 pt-25">
                            <div class="role-heading d-block">
                                <h4 class="fw-bolder" v-cloak>{{formatNumber(allStatistics.wardBalance)}}</h4>
                                <a v-if="permission.permission_value ==3" href="javascript:void(0);" data-backdrop="static" data-keyboard="false" class="state-move-modal float-right btn btn-sm btn-primary" @click="[wardMovementForm.wardMoveBtn = 'Forward']" data-toggle="modal" data-target="#wardMovement">
                                    <small class="fw-bolder">Transfer</small>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-sm-4 col-12">
            <!-- <div class="col-lg-6 col-sm-6 col-12"> -->
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <span>HHM Balance</span>
                            <div class="avatar bg-light-primary p-50 m-0 mt--1">
                                <div class="avatar-content">
                                    <i data-feather="users" class="font-medium-4"></i>
                                </div>
                            </div>
                        </div>
                        <div class="d-block justify-content-between align-items-end mt-50 pt-25">
                            <div class="role-heading d-block">
                                <h4 class="fw-bolder" v-cloak>{{formatNumber(allStatistics.mobilizer)}}</h4>
                                <div class="text-right">

                                    <a href="javascript:void(0);" data-backdrop="static" @click="isHHMbalance=true" data-keyboard="false" class="lga-details-modal btn btn-sm btn-outline-primary mr-50" data-toggle="modal" data-target="#viewDetails">
                                        <small class="fw-bolder">View</small>
                                    </a>

                                    <a v-if="permission.permission_value ==3" href="javascript:void(0);" data-backdrop="static" data-keyboard="false" class="lga-move-modal btn btn-sm btn-primary" @click="showWardMoveModal('Reverse')" data-toggle="modal" data-target="#wardMovement">
                                        <small class="fw-bolder">Reverse</small>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-sm-4 col-12">
            <!-- <div class="col-lg-6 col-sm-6 col-12"> -->
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <span>Household/Beneficiary</span>
                            <div class="avatar bg-light-primary p-50 m-0 mt--1">
                                <div class="avatar-content">
                                    <i data-feather="home" class="font-medium-4"></i>
                                </div>
                            </div>
                        </div>
                        <div class="d-block justify-content-between align-items-end mt-50 pt-25">
                            <div class="role-heading d-block">
                                <h4 class="fw-bolder" v-cloak>{{formatNumber(allStatistics.beneficiary)}}</h4>
                                <div class="text-right">

                                    <a href="../mobilization/list" class="btn btn-sm btn-primary">
                                        <small class="fw-bolder">View</small>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            
            
            <!--/ Stats Horizontal Card -->

            <div class="col-md-5 col-sm-6 col-6 mb-0">
                <div class="form-group">
                    <select class="form-control max-width-200" v-model="tableOptions.filterParam.movementType" @change="loadTableData()">
                        <option value="Forward">Allocation Transaction</option>
                        <option value="Reverse">Reverse Transaction</option>
                        <option value="ReverseOnline">Online Reverse Transaction</option>
                    </select>
                </div>
            </div>

            <div class="col-md-7 col-sm-6 col-6 text-md-right text-right d-md-block">
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
                    <span class="badge badge-dark filter-box" @click="removeSingleFilter(i)" v-for="(filterParam, i) in tableOptions.filterParam" v-if="filterParam.length > 0 && i !='movementType'">{{capitalize(i)}}: {{filterParam}} <i class="feather icon-x"></i></span>
                    <a href="#" class="float-right clear-filter" @click="clearAllFilter()">Clear</a>
                </div>
            </div>

            <div class="col-12 mt-1">
                <div class="card">
                    <div class="card-body py-1" v-show="filterState">
                        <form id="filterForm">
                            <div class="row">
                                <div class="col-12 col-sm-6 col-md-3 col-lg-3">
                                    <div class="form-group">
                                        <label>Requester Login ID</label> 
                                        <input type="text" v-model="tableOptions.filterParam.requester_loginid" class="form-control requester_loginid" id="requester_loginid" placeholder="Requester Login ID" name="requester_loginid" />
                                    </div>
                                </div>
                                <div class="col-12 col-sm-6 col-md-3 col-lg-3">
                                    <div class="form-group">
                                        <label>Mobilizer Login ID</label>
                                        <input type="text" v-model="tableOptions.filterParam.mobilizer_loginid" class="form-control mobilizer_loginid" id="mobilizer_loginid" placeholder="Mobilizer Login ID" name="mobilizer_loginid" />
                                    </div>
                                </div>   
                                <div class="col-12 col-sm-6 col-md-3 col-lg-3">
                                    <div class="form-group">
                                        <label>Request Date</label>
                                        <input type="text" v-model="tableOptions.filterParam.request_date" class="form-control date" id="request_date" placeholder="Request Date" name="request_date" />
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
                                <!-- Forward Transaction Header : Starts Here -->
                                <tr v-if="tableOptions.filterParam.movementType == 'Forward'">
                                    <th style="padding-right: 2px !important;">#</th>
                                    <th @click="sort(1)">
                                        Transfer By
                                        <i class="feather icon-chevron-up sort-up" :class="(tableOptions.orderField == 1 && tableOptions.orderDir =='asc')? 'active-sort': ''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 1 && tableOptions.orderDir =='desc')? 'active-sort': ''"></i>
                                    </th>
                                    <th @click="sort(4)">
                                        Origin
                                        <i class="feather icon-chevron-up sort-up" :class="(tableOptions.orderField == 4 && tableOptions.orderDir =='asc')? 'active-sort': ''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 4 && tableOptions.orderDir =='desc')? 'active-sort': ''"></i>
                                    </th>
                                    <th @click="sort(7)">
                                        HH Mobilizer
                                        <i class="feather icon-chevron-up sort-up" :class="(tableOptions.orderField == 7 && tableOptions.orderDir =='asc')? 'active-sort': ''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 7 && tableOptions.orderDir =='desc')? 'active-sort': ''"></i>
                                    </th>
                                    <th @click="sort(2)" style="padding-left: 5px !important; padding-right: 10px !important">
                                        Total
                                        <i class="feather icon-chevron-up sort-up" :class="(tableOptions.orderField == 2 && tableOptions.orderDir =='asc')? 'active-sort': ''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 2 && tableOptions.orderDir =='desc')? 'active-sort': ''"></i>
                                    </th>
                                    <th @click="sort(8)">
                                        Date
                                        <i class="feather icon-chevron-up sort-up" :class="(tableOptions.orderField == 8 && tableOptions.orderDir =='asc')? 'active-sort': ''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 8 && tableOptions.orderDir =='desc')? 'active-sort': ''"></i>
                                    </th>
                                </tr>
                                <!-- Forward Transaction Header : Ends Here -->

                                <!-- Reverse Transaction Header : Starts Here -->
                                <tr  v-if="tableOptions.filterParam.movementType == 'Reverse'">
                                    <th style="padding-right: 2px !important;">#</th>
                                    <th @click="sort(5)">
                                        Request By
                                        <i class="feather icon-chevron-up sort-up" :class="(tableOptions.orderField == 5 && tableOptions.orderDir =='asc')? 'active-sort': ''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 5 && tableOptions.orderDir =='desc')? 'active-sort': ''"></i>
                                    </th>
                                    <th @click="sort(7)">
                                        Total Request
                                        <i class="feather icon-chevron-up sort-up" :class="(tableOptions.orderField == 7 && tableOptions.orderDir =='asc')? 'active-sort': ''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 7 && tableOptions.orderDir =='desc')? 'active-sort': ''"></i>
                                    </th>
                                    <th @click="sort(1)">
                                        Request From
                                        <i class="feather icon-chevron-up sort-up" :class="(tableOptions.orderField == 1 && tableOptions.orderDir =='asc')? 'active-sort': ''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 1 && tableOptions.orderDir =='desc')? 'active-sort': ''"></i>
                                    </th>
                                    <th @click="sort(10)">
                                        Requested Date
                                        <i class="feather icon-chevron-up sort-up" :class="(tableOptions.orderField == 10 && tableOptions.orderDir =='asc')? 'active-sort': ''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 10 && tableOptions.orderDir =='desc')? 'active-sort': ''"></i>
                                    </th>
                                    <th @click="sort(9)" style="padding-left: 5px !important; padding-right: 10px !important">
                                        Status
                                        <i class="feather icon-chevron-up sort-up" :class="(tableOptions.orderField == 9 && tableOptions.orderDir =='asc')? 'active-sort': ''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 9 && tableOptions.orderDir =='desc')? 'active-sort': ''"></i>
                                    </th>
                                    <th @click="sort(8)">
                                        Total Fulfilled
                                        <i class="feather icon-chevron-up sort-up" :class="(tableOptions.orderField == 8 && tableOptions.orderDir =='asc')? 'active-sort': ''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 8 && tableOptions.orderDir =='desc')? 'active-sort': ''"></i>
                                    </th>
                                    <th @click="sort(11)">
                                        Fulfilled Date
                                        <i class="feather icon-chevron-up sort-up" :class="(tableOptions.orderField == 11 && tableOptions.orderDir =='asc')? 'active-sort': ''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 11 && tableOptions.orderDir =='desc')? 'active-sort': ''"></i>
                                    </th>
                                </tr>
                                <!-- Reverse Transaction Header : Ends Here -->
                                
                                <!-- Online Reverse Transaction Header : Starts Here -->
                                <tr  v-if="tableOptions.filterParam.movementType == 'ReverseOnline'">
                                    <th style="padding-right: 2px !important;">#</th>
                                    <th @click="sort(5)">
                                        Request By
                                        <i class="feather icon-chevron-up sort-up" :class="(tableOptions.orderField == 5 && tableOptions.orderDir =='asc')? 'active-sort': ''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 5 && tableOptions.orderDir =='desc')? 'active-sort': ''"></i>
                                    </th>
                                    <th @click="sort(1)">
                                        Request From
                                        <i class="feather icon-chevron-up sort-up" :class="(tableOptions.orderField == 1 && tableOptions.orderDir =='asc')? 'active-sort': ''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 1 && tableOptions.orderDir =='desc')? 'active-sort': ''"></i>
                                    </th>
                                    <th @click="sort(7)">
                                        Total Reversed
                                        <i class="feather icon-chevron-up sort-up" :class="(tableOptions.orderField == 7 && tableOptions.orderDir =='asc')? 'active-sort': ''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 7 && tableOptions.orderDir =='desc')? 'active-sort': ''"></i>
                                    </th>
                                    <th @click="sort(8)">
                                        Requested Date
                                        <i class="feather icon-chevron-up sort-up" :class="(tableOptions.orderField == 10 && tableOptions.orderDir =='asc')? 'active-sort': ''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 10 && tableOptions.orderDir =='desc')? 'active-sort': ''"></i>
                                    </th>
                                </tr>
                                <!-- Online Reverse Transaction Header : Ends Here -->

                            </thead>
                            <tbody>

                                <!-- Forward Transaction Body : Starts Here -->
                                <tr v-for="(g, i) in tableData" :class="checkedBg(g.pick)"  v-if="tableOptions.filterParam.movementType == 'Forward'">
                                    <td style="padding-right: 2px !important;">{{i+1}}</td>
                                    <td>
                                        {{g.transfer_by}}
                                    </td>
                                    <td>
                                        {{g.origin}}
                                    </td>
                                    <td>
                                        {{g.mobilizer}}
                                    </td>
                                    <td style="padding-left: 5px !important; padding-right: 10px !important">{{g.total? g.total.toLocaleString() : 0}}</td>
                                    <td>
                                        {{displayDate(g.created)}}
                                    </td>
                                </tr>
                                <tr v-if="tableData.length == 0 && tableOptions.filterParam.movementType == 'Forward'"><td class="text-center pt-2" colspan="6"><small>No Forward Transaction</small></td></tr>
                                <!-- Forward Transaction Body : Ends Here -->
                                
                                <!-- Reverse Transaction Body : Starts Here -->
                                <tr v-for="(g, i) in tableData" :class="checkedBg(g.pick)"  v-if="tableOptions.filterParam.movementType == 'Reverse'">
                                    <td style="padding-right: 2px !important;">{{g.orderid}}</td>
                                    <td>
                                        <div class="d-flex justify-content-left align-items-center">
                                            <div class="d-flex flex-column">
                                                <a href="#" class="user_name text-truncate text-body">
                                                    <span class="fw-bolder">{{g.requester}}</span>
                                                </a>
                                                <small class="emp_post text-muted">{{g.requester_loginid}} </small>
                                            </div>
                                        </div>
                                    </td>
                                    <td style="padding-left: 5px !important; padding-right: 10px !important">{{g.total_order? g.total_order.toLocaleString() : 0}}</td>
                                    <td>
                                        <div class="d-flex justify-content-left align-items-center">
                                            <div class="d-flex flex-column">
                                                <a href="#" class="user_name text-truncate text-body">
                                                    <span class="fw-bolder">{{g.mobilizer}}</span>
                                                </a>
                                                <small class="emp_post text-muted">{{g.mobilizer_loginid}} </small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        {{displayDate(g.created)}}
                                    </td>
                                    <td><span class="badge rounded-pill font-small-1" :class="g.status=='pending'? 'bg-danger' : 'bg-success'">{{g.status=='pending'? 'Pending' : 'Fulfilled'}}</span>  </td>
                            
                                    <td>{{g.total_fulfilment? g.total_fulfilment.toLocaleString() : 0}}</td>
                                    <td>
                                        {{g.fulfilled_date? displayDate(g.fulfilled_date) : 'Nil'}}
                                    </td>

                                </tr>
                                <tr v-if="tableData.length == 0 && tableOptions.filterParam.movementType == 'Reverse'"><td class="text-center pt-2" colspan="8"><small>No Reverse Order Added</small></td></tr>
                                <!-- Reverse Transaction Body : Ends Here -->

                                <!-- Online Reverse Transaction Body : Starts Here -->
                                <tr v-for="(g, i) in tableData" :class="checkedBg(g.pick)"  v-if="tableOptions.filterParam.movementType == 'ReverseOnline'">
                                    <td style="padding-right: 2px !important;">{{g.orderid}}</td>
                                    <td>
                                        <div class="d-flex justify-content-left align-items-center">
                                            <div class="d-flex flex-column">
                                                <a href="#" class="user_name text-truncate text-body">
                                                    <span class="fw-bolder">{{g.requester}}</span>
                                                </a>
                                                <small class="emp_post text-muted">{{g.requester_loginid}} </small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex justify-content-left align-items-center">
                                            <div class="d-flex flex-column">
                                                <a href="#" class="user_name text-truncate text-body">
                                                    <span class="fw-bolder">{{g.mobilizer}}</span>
                                                </a>
                                                <small class="emp_post text-muted">{{g.mobilizer_loginid}} </small>
                                            </div>
                                        </div>
                                    </td>
                                    <td style="padding-left: 5px !important; padding-right: 10px !important">{{g.amount? g.amount.toLocaleString() : 0}}</td>
                                    <td>
                                        {{displayDate(g.created)}}
                                    </td>

                                </tr>
                                <tr v-if="tableData.length == 0 && tableOptions.filterParam.movementType == 'ReverseOnline'"><td class="text-center pt-2" colspan="5"><small>No Online Reverse History</small></td></tr>
                                <!-- Online Reverse Transaction Body : Ends Here -->

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
            <div class="modal fade modal-primary ward-movement" id="wardMovement" tabindex="-1" role="dialog" aria-labelledby="exampleModalScrollableTitle" aria-hidden="true">
                <div class="modal-dialog modal-xl" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalScrollableTitle" v-html="wardMovementForm.wardMoveBtn == 'Forward'? 'Allocate e-Netcard To Household Mobilizer': 'Reverse e-Netcard From Household Mobilizer'" cloak></h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close" @click="[hideWardMoveModal(), wardNetBalancesData = [], hhmBalanacesData =[]]">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="allot">
                                <div class="left-side main-body">
                                    <h6 class="mb-1" v-html="wardMovementForm.wardMoveBtn == 'Forward'? 'Allocation Form': 'Reversal Form'"></h6>
                                    <div>
                                        <div class="form-group">
                                            <label class="form-label">Choose LGA</label>
                                            <select required class="form-control" @change="getWardData($event)" v-model="wardMovementForm.lgaid">
                                                <option value="" selected>Choose LGA to View</option>
                                                <option v-for="(lga, i) in lgaLevelData" :value="lga.lgaid">{{lga.lga}}</option>
                                            </select>
                                        </div>

                                        <div class="form-group">
                                            <label class="form-label">Choose HHM Ward</label>
                                            <select class="form-control" v-model="wardMovementForm.wardid" @change="getHhmBalances($event)">
                                                <option value="" selected>Choose a Ward</option>
                                                <!-- <option v-for="(ward, i) in wardNetBalancesData" :value="ward.wardid">{{ward.ward}} - {{ward.total}}</option> -->
                                                <option v-for="(ward, i) in wardNetBalancesData" :value="ward.wardid">{{ward.ward}}</option>
                                            </select>
                                        </div>

                                        <div class="form-group">
                                            <label class="form-label"v-html="wardMovementForm.wardMoveBtn == 'Forward'? 'Total No of Netcard to Allocate': 'Total No of Netcard to Reverse'"></label>
                                            <input type="number" class="form-control" @keypress="onlyNumber($event)" v-model="wardMovementForm.totalNetcard" placeholder="Total No of Netcard"/>
                                        </div>
                                        
                                        <div class="e-details pt-2" v-if="wardMovementForm.wardid !=''">
                                          <small class="mt-3 "><span class="font-weight-bolder text-primary" v-text="currentWardBalance.wardName"></span> e-Netcard Details</small>
                                          <hr class="invoice-spacing mt-0">
                                          <div class="invoice-terms mt-1">
                                              <div class="d-flex justify-content-between">
                                                  <label class="invoice-terms-title mb-0" for="paymentTerms">Total Received: </label>
                                                  <div class="custom-control badge badge-light-success" v-text="currentWardBalance.received"></div>
                                              </div>
                                          </div>

                                          <div class="invoice-terms mt-1">
                                              <div class="d-flex justify-content-between">
                                                  <label class="invoice-terms-title mb-0" for="paymentTerms">Total Disbursed: </label>
                                                  <div class="custom-control badge badge-light-info"  v-text="currentWardBalance.disbursed"></div>
                                              </div>
                                          </div>
                                          <div class="invoice-terms mt-1">
                                            <hr class="my-50">
                                              <div class="d-flex justify-content-between">
                                                  <label class="invoice-terms-title mb-0" for="paymentTerms">Balance: </label>
                                                  <div class="custom-control badge badge-light-warning" v-text="currentWardBalance.balance"></div>
                                              </div>
                                          </div>

                                        </div>

                                    </div>

                                </div>
                                <div class="right-side">
                                    <div class="allot-mobile-form main-body">
                                        <div class="form-group mb-50">
                                            <label class="form-label">Choose LGA</label>
                                            <select required class="form-control" @change="getWardData($event)" v-model="wardMovementForm.lgaid">
                                                <option value="" selected>Choose LGA to View</option>
                                                <option v-for="(lga, i) in lgaLevelData" :value="lga.lgaid">{{lga.lga}}</option>
                                            </select>
                                        </div>

                                        <div class="form-group mb-50">
                                            <label class="form-label">Choose HHM Ward</label>
                                            <select class="form-control" v-model="wardMovementForm.wardid" @change="getHhmBalances($event)">
                                                <option value="" selected>Choose a Ward</option>
                                                <option v-for="(ward, i) in wardNetBalancesData" :value="ward.wardid">{{ward.ward}}</option>
                                            </select>
                                        </div>

                                      
                                        <div class="form-group mb-50">
                                            <label class="form-label" v-html="wardMovementForm.wardMoveBtn == 'Forward'? 'Total No of Netcard to Allocate': 'Total No of Netcard to Reverse'"></label>
                                            <input type="number" class="form-control" @keypress="onlyNumber($event)" v-model="wardMovementForm.totalNetcard" placeholder="Total No of Netcard"/>
                                        </div>
                                        
                                       <div class="e-details pt-2" v-if="wardMovementForm.wardid !=''">
                                          <small class="mt-3 "><span class="font-weight-bolder text-primary" v-text="currentWardBalance.wardName"></span> e-Netcard Details</small>
                                          <hr class="invoice-spacing mt-0">
                                          <div class="invoice-terms mt-1">
                                              <div class="d-flex justify-content-between">
                                                  <label class="invoice-terms-title mb-0" for="paymentTerms">Total Received: </label>
                                                  <div class="custom-control badge badge-light-success" v-text="currentWardBalance.received"></div>
                                              </div>
                                          </div>

                                          <div class="invoice-terms mt-1">
                                              <div class="d-flex justify-content-between">
                                                  <label class="invoice-terms-title mb-0" for="paymentTerms">Total Disbursed: </label>
                                                  <div class="custom-control badge badge-light-info"  v-text="currentWardBalance.disbursed"></div>
                                              </div>
                                          </div>
                                          <div class="invoice-terms mt-1">
                                            <hr class="my-50">
                                              <div class="d-flex justify-content-between">
                                                  <label class="invoice-terms-title mb-0" for="paymentTerms">Balance: </label>
                                                  <div class="custom-control badge badge-light-warning" v-text="currentWardBalance.balance"></div>
                                              </div>
                                          </div>

                                        </div>

                                        
                                    </div>
                                    <!-- Todo search starts -->
                                    <div class="app-fixed-search d-flex align-items-center">

                                        <div class="d-flex align-content-center justify-content-between w-100">
                                            <div class="input-group input-group-merge">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text"><i data-feather="search" class="text-muted"></i></span>
                                                </div>
                                                <input type="text" class="form-control search" id="todo-search" placeholder="Search HHM" aria-label="Search..." aria-describedby="todo-search" />
                                                
                                                <div class="input-group-append">
                                                  <button type="button" class="btn btn-sm btn-outline-default searchBtn" data-toggle="tooltip" data-placement="top" title="Refresh Page" @click="HHMBalancesRefresh()">
                                                      <i class="feather icon-refresh-cw"></i>         
                                                  </button>  
                                                </div>


                                            </div>
                                        </div>
                                        
                                    </div>
                                    <!-- Todo search ends -->
                                    <div class="main-body">
                                        <div v-if="isLgabalance == true" class="mt-0">
                                            <table class="table table-hover scroll-now" id="moveTable">
                                                <thead>
                                                    <tr>
                                                        <th width="40px">
                                                            <div class="custom-control custom-checkbox checkbox">
                                                                <input type="checkbox" class="custom-control-input" @change="[selectToggle(), selectedItemsCount()]" id="all-check" />
                                                                <label class="custom-control-label" for="all-check"></label>
                                                            </div>
                                                        </th>
                                                        <th>Login ID</th>
                                                        <th>Fullname</th>
                                                        <th v-show="wardMovementForm.wardMoveBtn == 'Reverse'">Location</th>
                                                        <th>Total</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="">
                                                    <tr v-for="(g, i) in hhmBalanacesData" :class="checkedBg(g.pick)">
                                                        <td>
                                                            <div class="custom-control custom-checkbox checkbox">
                                                                <input type="checkbox" class="custom-control-input" @click="selectedItemsCount()" :id="i" v-model="g.pick" />
                                                                <label class="custom-control-label" :for="i"></label>
                                                            </div>
                                                        </td>
                                                        <td>{{g.loginid}}</td>
                                                        <td>{{g.fullname}}</td>
                                                        <td v-show="wardMovementForm.wardMoveBtn == 'Reverse'">
                                                          <i class="feather " :class="g.device_serial? 'icon-smartphone bg-light-info rounded' : 'icon-cloud  bg-light-success rounded'"></i>  <small v-html="g.device_serial? ' ('+g.device_serial+')' : ' (Online)'"></small>
                                                        </td>
                                                        <td>{{g.balance}}</td>
                                                    </tr>
                                                                                                        
                                                    <tr v-if="hhmBalanacesData.length == 0"><td class="text-center text-info pt-4 pb-4" colspan="4"><small>No Household Mobilizer Assigned to <b> {{wardMovementForm.wardName}} </b> </small></td></tr>

                                                </tbody>
                                            </table>
                                        </div>
                                    </div>

                                </div>

                            </div>

                        </div>
                        
                        <div class="modal-footer">
                            <div class="form-group">
                                <button type="button" data-dismiss="modal" aria-label="Close" @click="[hideWardMoveModal(), wardNetBalancesData = [], hhmBalanacesData =[]]" class="btn btn-outline-primary mr-1">Cancel</button>
                                <button type="button" @click="wardTransfer()" class="btn btn-primary" v-text="wardMovementForm.wardMoveBtn == 'Forward'? 'Allocate Netcard' : 'Reverse Netcard'">Allocate Netcard</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Details Modal Ends -->



            <!-- Details Modal Starts -->
            <div class="modal fade modal-primary ward-movement" id="viewDetails" tabindex="-1" role="dialog" aria-labelledby="exampleModalScrollableTitle" aria-hidden="true">
                <div class="modal-dialog modal-xl" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalScrollableTitle">HHM Balances</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close" @click="[wardLevelData = [], hhmBalanacesData =[], hideHHMBalanceModal()]">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="allot">
                                <div class="left-side">
                                    <h6 class="mb-1">HHM Balances</h6>
                                    <div>
                                        <div class="form-group">
                                            <label class="form-label">Choose LGA</label>
                                            <select required class="form-control" @change="getWardLevel($event)" v-model="wardMovementForm.lgaid">
                                                <option value="" selected>Choose LGA to View</option>
                                                <option v-for="(lga, i) in lgaLevelData" :value="lga.lgaid">{{lga.lga}}</option>
                                            </select>
                                        </div>

                                        <div class="form-group">
                                            <label class="form-label">Choose a Ward</label>
                                            <select class="form-control" v-model="wardMovementForm.wardid" @change="getHhmBalances($event)">
                                                <option value="" selected>Choose a Ward</option>
                                                <option v-for="(ward, i) in wardLevelData" :value="ward.wardid">{{ward.ward}}</option>
                                            </select>
                                        </div> 

                                       <div class="e-details pt-2" v-if="wardMovementForm.wardid !=''">
                                          <small class="mt-3 "><span class="font-weight-bolder text-primary" v-text="currentWardBalance.wardName"></span> e-Netcard Details</small>
                                          <hr class="invoice-spacing mt-0">
                                          <div class="invoice-terms mt-1">
                                              <div class="d-flex justify-content-between">
                                                  <label class="invoice-terms-title mb-0" for="paymentTerms">Total Received: </label>
                                                  <div class="custom-control badge badge-light-success" v-text="currentWardBalance.received"></div>
                                              </div>
                                          </div>

                                          <div class="invoice-terms mt-1">
                                              <div class="d-flex justify-content-between">
                                                  <label class="invoice-terms-title mb-0" for="paymentTerms">Total Disbursed: </label>
                                                  <div class="custom-control badge badge-light-info"  v-text="currentWardBalance.disbursed"></div>
                                              </div>
                                          </div>
                                          <div class="invoice-terms mt-1">
                                            <hr class="my-50">
                                              <div class="d-flex justify-content-between">
                                                  <label class="invoice-terms-title mb-0" for="paymentTerms">Balance: </label>
                                                  <div class="custom-control badge badge-light-warning" v-text="currentWardBalance.balance"></div>
                                              </div>
                                          </div>

                                        </div>

                                        
                                    </div>

                                </div>
                                <div class="right-side">
                                    <div class="allot-mobile-form">
                                        <div class="form-group mb-50">
                                            <label class="form-label">Choose LGA</label>
                                            <select required class="form-control" @change="getWardLevel($event)" v-model="wardMovementForm.lgaid">
                                                <option value="" selected>Choose LGA to View</option>
                                                <option v-for="(lga, i) in lgaLevelData" :value="lga.lgaid">{{lga.lga}}</option>
                                            </select>
                                        </div>

                                        <div class="form-group mb-50">
                                            <label class="form-label">Choose a Ward</label>
                                            <select class="form-control" v-model="wardMovementForm.wardid" @change="getHhmBalances($event)">
                                                <option value="" selected>Choose a Ward</option>
                                                <option v-for="(ward, i) in wardLevelData" :value="ward.wardid">{{ward.ward}}</option>
                                            </select>
                                        </div>   
                                        
                                       <div class="e-details pt-1" v-if="wardMovementForm.wardid !=''">
                                          <small class="mt-3"><span class="font-weight-bolder text-primary" v-text="currentWardBalance.wardName"></span> e-Netcard Details</small>
                                          <hr class="invoice-spacing mt-0">
                                          <div class="invoice-terms mt-1">
                                              <div class="d-flex justify-content-between">
                                                  <label class="invoice-terms-title mb-0" for="paymentTerms">Total Received: </label>
                                                  <div class="custom-control badge badge-light-success" v-text="currentWardBalance.received"></div>
                                              </div>
                                          </div>

                                          <div class="invoice-terms mt-1">
                                              <div class="d-flex justify-content-between">
                                                  <label class="invoice-terms-title mb-0" for="paymentTerms">Total Disbursed: </label>
                                                  <div class="custom-control badge badge-light-info"  v-text="currentWardBalance.disbursed"></div>
                                              </div>
                                          </div>
                                          <div class="invoice-terms mt-1">
                                            <hr class="my-50">
                                              <div class="d-flex justify-content-between">
                                                  <label class="invoice-terms-title mb-0" for="paymentTerms">Balance: </label>
                                                  <div class="custom-control badge badge-light-warning" v-text="currentWardBalance.balance"></div>
                                              </div>
                                          </div>

                                        </div>

                                        
                                    </div>
                                    <!-- Todo search starts -->
                                    <div class="app-fixed-search d-flex align-items-center">

                                        <div class="d-flex align-content-center justify-content-between w-100">
                                            <div class="input-group input-group-merge">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text"><i data-feather="search" class="text-muted"></i></span>
                                                </div>
                                                <input type="text" class="form-control search" id="todo-search1" placeholder="Search HHM" aria-label="Search..." aria-describedby="todo-search1" />
                                            </div>
                                        </div>
                                        
                                    </div>
                                    <!-- Todo search ends -->
                                    <div class="main-body">
                                        <div v-if="isLgabalance == true" class="mt-0">
                                            <table class="table table-hover scroll-now" id="moveTable1">
                                                <thead>
                                                    <tr>
                                                        <th width="40px">#</th>
                                                        <th>Login ID</th>
                                                        <th>Fullname</th>
                                                        <th>Geo Location</th>
                                                        <th>HHM Balance</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="">
                                                    <tr v-for="(g, i) in hhmBalanacesData" :class="checkedBg(g.pick)">
                                                        <td>{{i+1}}</td>
                                                        <td>{{g.loginid}}</td>
                                                        <td v-html="g.fullname? g.fullname :'Not Assigned'"></td>
                                                        <td>{{g.geo_string}}</td>
                                                        <td>{{g.balance}}</td>
                                                    </tr>
                                                                                                        
                                                    <tr v-if="hhmBalanacesData.length == 0"><td class="text-center text-info pt-4 pb-4" colspan="5"><small>No ward Choosen/No Household Mobilizer Assigned to <b> {{wardMovementForm.wardName}} </b> </small></td></tr>

                                                </tbody>
                                            </table>
                                        </div>
                                    </div>

                                </div>

                            </div>

                        </div>
                        
                        <div class="modal-footer">
                            <div class="form-group">
                                <button type="button" data-dismiss="modal" aria-label="Close" @click="[wardLevelData = [], hhmBalanacesData =[], hideHHMBalanceModal()]" class="btn btn-primary">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Details Modal Ends -->


            
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
