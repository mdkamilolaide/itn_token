Vue.component("page-body", {
  data: function () {
    return {
      page: "dashboard", //  page by name dashbaord | result | ...
    };
  },
  mounted() {
    /*  Manages events Listening    */
  },
  methods: {},
  template: `
    <div>
        <div class="content-body">
            <reporting_lists/>
        </div>
    </div>
    `,
});

Vue.component("reporting_lists", {
  data: function () {
    return {
      geoIndicator: {
        geoLevel: "",
        geoLevelId: 0,
      },
      permission: getPermission(per, "mobilization"),
      checkToggle: false,
      tableData: [],
      searchReport: "",
      dateTitle: "",
      report: {
        reportState: 0,
        reportTitle: "",
        reportName: "",
        reportModule: "",
        reportDate: "",
        startDate: "",
        endDate: "",
      },
      trainingListData: [],
      trainingForm: {
        trainingId: 0,
        trainingName: "",
      },
    };
  },
  beforeMount() {
    this.geoIndicator.geoLevel = $("#v_g_geo_level").val();
    this.geoIndicator.geoLevelId = $("#v_g_geo_level_id").val();
  },
  mounted() {
    /*  Manages events Listening    */
    $("#form-search").on("keyup", function () {
      var value = $(this).val().toLowerCase();
      $("#dpTable tbody tr").filter(function () {
        $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
      });
    });

    this.getAllTrainingLists();
    this.autoUpdateTableRowNo();
  },
  methods: {
    getAllTrainingLists() {
      /*  Manages the loading of Geo Level data */
      var self = this;
      var url = common.DataService;
      overlay.show();

      axios
        .get(
          url +
            "?qid=104a&gl=" +
            this.geoIndicator.geoLevel +
            "&glid=" +
            this.geoIndicator.geoLevelId
        )
        .then(function (response) {
          self.trainingListData = response.data.data; //All Data
          overlay.hide();
        })
        .catch(function (error) {
          overlay.hide();
          alert.Error("ERROR", error);
        });
    },
    openModal(reportType) {
      switch (reportType) {
        case "participantList":
          this.report.reportTitle = `Filter to a <b>Specific Training</b> to Download Participants List`;
          this.report.reportName = `Participants List`;
          this.report.reportState = 1;
          this.report.reportModule = "activity";
          break;

        case "bankVerificationStatus":
          this.report.reportTitle = `Filter to a <b>Specific Training</b> to Download Participants Bank Verification Status Report`;
          this.report.reportName = `Bank Verification Status`;
          this.report.reportState = 2;
          this.report.reportModule = "activity";
          break;

        case "unCapturedParticipant":
          this.report.reportTitle = `Filter to a <b>Specific Training</b> to Download Uncaptured Participants Report`;
          this.report.reportName = `Uncaptured Participants`;
          this.report.reportState = 3;
          this.report.reportModule = "activity";
          break;

        case "mobilizationPerLGA":
          this.report.reportTitle = `Filter to a <b>Specific Training</b> to Download Uncaptured Participants Report`;
          this.report.reportName = `Mobilization Per LGA`;
          this.report.reportState = 4;
          this.report.reportModule = "mobilization";
          this.downloadReport();
          break;

        case "mobilizationPerDP":
          this.report.reportTitle = `Filter to a <b>Specific Training</b> to Download Uncaptured Participants Report`;
          this.report.reportName = `Mobilization Per DP`;
          this.report.reportState = 5;
          this.report.reportModule = "mobilization";
          this.downloadReport();
          break;

        case "mobilizationPerLGADated":
          this.report.reportTitle = `Choose <b>a Date</b> to Download the Mobilization Report Per LGA`;
          this.report.reportName = `Mobilization Per LGA in a specified Date`;
          this.report.reportState = 6;
          this.report.reportModule = "mobilization";
          this.chooseSingleDate();
          break;

        case "mobilizationPerDPDated":
          this.report.reportTitle = `Choose <b>a Date</b> to Download the Mobilization Report Per DP`;
          this.report.reportName = `Mobilization Per DP in a specified Date`;
          this.report.reportState = 7;
          this.report.reportModule = "mobilization";
          this.chooseSingleDate();
          break;

        case "mobilizationPerLGADateRange":
          this.report.reportTitle = `Choose <b>a Date Range</b> to Download the Mobilization Report Per LGA`;
          this.report.reportName = `Mobilization Per DP Report with Date Range`;
          this.report.reportState = 8;
          this.report.reportModule = "mobilization";
          this.chooseDateRange();
          break;

        case "distributionPerLGA":
          this.report.reportTitle = ``;
          this.report.reportName = `Distribution Per LGA Report`;
          this.report.reportState = 9;
          this.report.reportModule = "distribution";
          this.downloadReport();
          break;

        case "distributionPerDP":
          this.report.reportTitle = `Choose <b>a Date</b> to Download the Distribution Report Per LGA`;
          this.report.reportName = `Distribution Per DP Report`;
          this.report.reportState = 10;
          this.report.reportModule = "distribution";
          this.downloadReport();
          break;

        case "distributionPerLGADated":
          this.report.reportTitle = `Choose <b>a Date</b> to Download the Distribution Report Per LGA`;
          this.report.reportName = `Distribution Per LGA for a specified Date`;
          this.report.reportState = 11;
          this.report.reportModule = "distribution";
          this.chooseSingleDate();
          break;

        case "distributionPerLGADateRange":
          this.report.reportTitle = `Choose <b>a Date Range</b> to Download the Distribution Report Per LGA`;
          this.report.reportName = `Distribution Per LGA Report with Date Range`;
          this.report.reportState = 12;
          this.report.reportModule = "distribution";
          this.chooseDateRange();
          break;

        case "distributionPerDPDated":
          this.report.reportTitle = `Choose <b>a Date</b> to Download the Distribution Report Per DP`;
          this.report.reportName = `Distribution Per DP for a specified Date`;
          this.report.reportState = 13;
          this.report.reportModule = "distribution";
          this.chooseSingleDate();
          break;

        case "distributionPerDPDateRange":
          this.report.reportTitle = `Choose <b>a Date Range</b> to Download the Distribution Report Per DP`;
          this.report.reportName = `Distribution Per DP Report with Date Range`;
          this.report.reportState = 14;
          this.report.reportModule = "distribution";
          this.chooseDateRange();
          break;

        default:
          this.report.reportTitle = `End_Process_Forms_`;
      }
    },
    async downloadReport() {
      const min = 1;
      const max = 100;
      const randomInt = Math.floor(Math.random() * (max - min + 1)) + min;

      let fileName, dlString, geoString;
      geoString =
        "&gl=" +
        this.geoIndicator.geoLevel +
        "&glid=" +
        this.geoIndicator.geoLevelId;

      if (
        this.trainingForm.trainingId == 0 &&
        this.report.reportModule == "activity"
      ) {
        alert.Error("Error", "Kindly Select a Training to Download from");
        return;
      }

      if (this.report.reportDate.includes("to")) {
        let dates = this.report.reportDate.split(" to ");
        this.report.startDate = dates[0].replace(/\s/g, "");
        this.report.endDate = dates[1].replace(/\s/g, "");
      } else {
        this.report.startDate = this.report.endDate = this.report.reportDate;
      }

      switch (this.report.reportState) {
        case 1:
          fileName = this.report.reportName + `_Report_${randomInt}`;
          dlString =
            "qid=401" + geoString + "&tid=" + this.trainingForm.trainingId;
          break;

        case 2:
          fileName = this.report.reportName + `_Report_${randomInt}`;
          dlString =
            "qid=402" + geoString + "&tid=" + this.trainingForm.trainingId;
          break;

        case 3:
          fileName = this.report.reportName + `_Report_${randomInt}`;
          dlString =
            "qid=403" + geoString + "&tid=" + this.trainingForm.trainingId;
          break;

        case 4:
          fileName = this.report.reportName + `_Report_${randomInt}`;
          dlString = "qid=501" + geoString;
          break;

        case 5:
          fileName = this.report.reportName + `_Report_${randomInt}`;
          dlString = "qid=502" + geoString;
          break;

        case 6:
          fileName = this.report.reportName + `_Report_${randomInt}`;
          dlString = "qid=503" + geoString + "&date=" + this.report.reportDate;
          break;

        case 7:
          fileName = this.report.reportName + `_Report_${randomInt}`;
          dlString = "qid=504" + geoString + "&date=" + this.report.reportDate;
          break;

        case 8:
          fileName = this.report.reportName + `_Report_${randomInt}`;
          dlString =
            "qid=505" +
            geoString +
            "&startDate=" +
            this.report.startDate +
            "&endDate=" +
            this.report.endDate;
          break;

        case 9:
          fileName = this.report.reportName + `_Report_${randomInt}`;
          dlString = "qid=601" + geoString + "&date=" + this.report.reportDate;
          break;

        case 10:
          fileName = this.report.reportName + `_Report_${randomInt}`;
          dlString = "qid=602" + geoString + "&date=" + this.report.reportDate;
          break;

        case 11:
          fileName = this.report.reportName + `_Report_${randomInt}`;
          dlString = "qid=603" + geoString + "&date=" + this.report.reportDate;
          break;

        case 12:
          fileName = this.report.reportName + `_Report_${randomInt}`;
          dlString =
            "qid=604" +
            geoString +
            "&startDate=" +
            this.report.startDate +
            "&endDate=" +
            this.report.endDate;
          break;

        case 13:
          fileName = this.report.reportName + `_Report_${randomInt}`;
          dlString = "qid=605" + geoString + "&date=" + this.report.reportDate;
          break;

        case 14:
          fileName = this.report.reportName + `_Report_${randomInt}`;
          dlString =
            "qid=606" +
            geoString +
            "&startDate=" +
            this.report.startDate +
            "&endDate=" +
            this.report.endDate;
          break;

        default:
          fileName = `Other_Report_${randomInt}`;
          dlString = "qid=705";
      }

      overlay.show();

      // const downloadMax = common.ExportDownloadLimit;

      alert.Info("DOWNLOADING...", `Downloading  record(s)`);

      try {
        const outcome = await this.downloadData(dlString);
        const exportData = JSON.parse(outcome);
        const options = { fileName };
        Jhxlsx.export(exportData, options);
        overlay.hide();
        this.dismissOnClick();
      } catch (error) {
        console.error(error);
        alert.Error("Download Error", error);
        overlay.hide();
      }
    },
    downloadData(dlString) {
      return new Promise((resolve, reject) => {
        $.ajax({
          url: common.ExportService,
          type: "POST",
          data: dlString,
          success: function (data) {
            resolve(data);
          },
          error: function (error) {
            reject(error);
          },
        });
      });
    },
    dismissOnClick() {
      this.trainingForm.trainingId = 0;
      this.trainingForm.trainingName =
        this.report.reportTitle =
        this.report.reportName =
        this.report.reportModule =
        this.dateTitle =
        this.report.reportDate =
        this.report.startDate =
        this.report.endDate =
          "";
      this.report.reportState = 0;
      $("#trainingListModal").modal("hide");
    },
    chooseSingleDate() {
      this.dateTitle = "Choose Date to download the Report";

      $(".date").flatpickr({
        altInput: true,
        altFormat: "F j, Y",
        dateFormat: "Y-m-d",
      });
    },
    chooseDateRange() {
      this.dateTitle = "Choose Date Range to Download the Report";
      $(".date").flatpickr({
        altInput: true,
        altFormat: "F j, Y",
        dateFormat: "Y-m-d",
        mode: "range",
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
            selectedIds.push(this.tableData[i].dpid);
          }
        }
      }
      return selectedIds;
    },
    autoUpdateTableRowNo() {
      let allTableRow = document.querySelectorAll("tr td:first-child");
      allTableRow.forEach((element, i) => {
        element.innerHTML = i + 1;
      });
    },
  },
  computed: {},
  template: `
        <div>

           
            <div class="row">

            <div class="col-md-12 col-sm-12 col-12 mb-2">
                <h2 class="content-header-title header-txt float-left mb-0">Mobilization</h2>
                <div class="breadcrumb-wrapper">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../mobilization">Dashboard</a></li>
                        <li class="breadcrumb-item active">Report List</li>
                    </ol>
                </div>
            </div>

            
                <div class="col-12">
                    <div class="alert bg-light-primary pt-1 pb-1">
              
                        <div class="col-12">
                            <div class="row">
                                
                                <div class="col-12 ">
                                    <!-- <label class="form-label" for="user-role">Form Name</label> -->
                                    <div class="input-group date_filter">
                                        <input type="text" id="form-search" v-model="searchReport" class="form-control date" placeholder="Search using Report Name" />
                                        <!--
                                        <div class="input-group-append">
                                            <button class="btn btn-primary pl-1 pr-1" @click="loadDp()" type="button">Load</button>
                                        </div>
                                        -->
                                    </div>
                                </div>

                            </div>

                        </div>

                    </div>
                </div>

            </div>

            <div class="card">
                <div class="table-responsive">
                    <table class="table" id="dpTable">
                        <thead class="bg-light-primary">
                            <th width="60px">#</th>
                            <th>Report Lists</th>
                            <th>Module</th>
                            <th width="60px" class="text-center">
                                <!-- <a href="javascript:void(0)" data-toggle="tooltip" data-placement="top" title="Downlaod All" class="btn btn-primary btn-sm p-50"><i class="feather icon-download-cloud"></i></a> -->
                            </th>
                        </thead>
                        <tbody>   
                            <tr v-if="permission.permission_value >=2">
                                <td>4</td>
                                <td>Aggregate Mobilization Per LGA Report</td>
                                <td><span class="badge badge-light-success">Mobilization</span></td>
                                <td class="text-center">
                                    <a href="javascript:void(0)" @click="openModal('mobilizationPerLGA')" class="btn btn-sm btn-primary btn-sm p-25"><i class="feather icon-download"></i></a>
                                </td>
                            </tr>
                            <tr v-if="permission.permission_value >=2">
                                <td>5</td>
                                <td>Aggregate Mobilization Per DP Report</td>
                                <td><span class="badge badge-light-success">Mobilization</span></td>
                                <td class="text-center">
                                    <a href="javascript:void(0)" @click="openModal('mobilizationPerDP')" class="btn btn-sm btn-primary btn-sm p-25"><i class="feather icon-download"></i></a>
                                </td>
                            </tr>
                            <tr v-if="permission.permission_value >=1">
                                <td>6</td>
                                <td>Mobilization by LGA in a Dated Report</td>
                                <td><span class="badge badge-light-success">Mobilization</span></td>
                                <td class="text-center">
                                    <a href="javascript:void(0)" @click="openModal('mobilizationPerLGADated')" class="btn btn-sm btn-primary btn-sm p-25" data-backdrop="static" data-keyboard="false" data-toggle="modal" data-target="#trainingListModal"><i class="feather icon-download"></i></a>
                                </td>
                            </tr>
                            <tr v-if="permission.permission_value >=1">
                                <td>7</td>
                                <td>Mobilization by DP in a Dated Report</td>
                                <td><span class="badge badge-light-success">Mobilization</span></td>
                                <td class="text-center">
                                    <a href="javascript:void(0)" @click="openModal('mobilizationPerDPDated')" class="btn btn-sm btn-primary btn-sm p-25" data-backdrop="static" data-keyboard="false" data-toggle="modal" data-target="#trainingListModal"><i class="feather icon-download"></i></a>
                                </td>
                            </tr>
                            <tr v-if="permission.permission_value ==3">
                                <td>8</td>
                                <td>Mobilization by LGA Report using Date Range</td>
                                <td><span class="badge badge-light-success">Mobilization</span></td>
                                <td class="text-center">
                                    <a href="javascript:void(0)" @click="openModal('mobilizationPerLGADateRange')" class="btn btn-sm btn-primary btn-sm p-25" data-backdrop="static" data-keyboard="false" data-toggle="modal" data-target="#trainingListModal"><i class="feather icon-download"></i></a>
                                </td>
                            </tr>  
                            
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Training List Modal: Start -->
            <div class="modal fade text-left" id="trainingListModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel33" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <div class="alert alert-primary p-1">
                                <span class="text-primary bold" v-html="report.reportTitle"></span>
                            </div>
                            <button type="button" @click="dismissOnClick()" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <form method="POST" @submit.stop.prevent>
                            <div class="modal-body">
                                <div v-if="report.reportModule =='activity'">
                                  <label>Select a Training:</label>
                                  <div class="form-group">
                                      <select name="role" v-model="trainingForm.trainingId" class="form-control role">
                                          <option value="0" :selected="trainingForm.trainingId==0">Select a Training</option>
                                          <option v-for="t in trainingListData" :value="t.trainingid" :key="t.trainingid" :selected="t.trainingid">{{t.title}}</option>
                                      </select>
                                  </div>
                                </div>

                                <div v-show="report.reportModule =='mobilization' || report.reportModule =='distribution'">

                                  <div class="form-group">
                                      <label class="form-label full" for="mob-date">{{dateTitle}}</label>
                                      <input id="date" v-model="report.reportDate" type="date" placeholder="Mobilization Date Range" class="form-control date mob-date" name="mob_date">
                                  </div>

                                </div>

                            </div>
                            
                            <div class="modal-footer">
                                <div class="text-center">
                                    <button type="button" @click="downloadReport()" class="btn btn-primary mt-2 waves-effect waves-float waves-light">Download</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <!-- Training List Modal: End -->

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
