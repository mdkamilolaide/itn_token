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
            <monitoring_lists/>
        </div>
    </div>
    `,
});

Vue.component("monitoring_lists", {
  data: function () {
    return {
      geoIndicator: {
        state: 50,
        currentLevelId: 0,
        lga: "",
        ward: "",
      },
      checkToggle: false,
      geoLevelData: [],
      sysDefaultData: [],
      lgaLevelData: [],
      wardLevelData: [],
      tableData: [],
      bulkUserForm: {
        geoLevel: "",
        geoLevelId: 0,
        mobilizationDate: "",
      },
    };
  },
  mounted() {
    /*  Manages events Listening    */
    $("#form-search").on("keyup", function () {
      var value = $(this).val().toLowerCase();
      $("#dpTable tbody tr").filter(function () {
        $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
      });
    });
    this.getMonitoringToolsList();
  },
  methods: {
    async getMonitoringToolsList() {
      /*  Manages the loading of System default settings */
      var self = this;
      var url = common.DataService;
      overlay.show();

      axios
        .get(url + "?qid=700")
        .then(function (response) {
          console.log(response.data);
          if (response.data.data.length > 0) {
            self.tableData = response.data.data;
          }

          overlay.hide();
        })
        .catch(function (error) {
          overlay.hide();
          alert.Error("ERROR", error);
        });
    },
    capitalize(word) {
      if (word) {
        const loweredCase = word.toLowerCase();
        return word[0].toUpperCase() + loweredCase.slice(1);
      } else {
        return word;
      }
    },
    async downloadReport(i, total) {
      const now = new Date();

      const day = now.getDate().toString().padStart(2, "0");
      const month = now.toLocaleString("default", { month: "short" });
      let hours = now.getHours();
      const minutes = now.getMinutes().toString().padStart(2, "0");
      const seconds = now.getSeconds().toString().padStart(2, "0");
      const gmt = hours >= 12 ? "PM" : "AM";

      hours = hours % 12 || 12; // Convert 0 to 12 for 12-hour clock
      hours = hours.toString().padStart(2, "0");

      const today = `${day}-${month}-${hours}_${minutes}_${seconds}_${gmt}`;

      let fileName, dlString;

      switch (i) {
        case 1:
          fileName = `i-9a_Forms_${total}_${today}`;
          dlString = "qid=701";
          break;
        case 2:
          fileName = `i-9b_Forms_${total}_${today}`;
          dlString = "qid=702";
          break;
        case 3:
          fileName = `i-9c_Forms_${total}_${today}`;
          dlString = "qid=703";
          break;
        case 4:
          fileName = `5_Percent_Revisit_Forms_${total}_${today}`;
          dlString = "qid=704";
          break;
        case 5:
          fileName = `End_Process_Forms_${total}_${today}`;
          dlString = "qid=705";
          break;
        case 7:
          fileName = `SMC_Supervisory_CDD_Forms_${total}_${today}`;
          dlString = "qid=706";
          break;
        case 8:
          fileName = `SMC_Supervisory_HFW_Forms_${total}_${today}`;
          dlString = "qid=707";
          break;
        default:
          fileName = `End_Process_Forms_${total}_${today}`;
          dlString = "qid=705";
      }

      overlay.show();

      const downloadMax = common.ExportDownloadLimit;
      const totalNum = parseInt(total);

      if (totalNum > downloadMax) {
        alert.Error(
          "Download Error",
          `Unable to download data because it has exceeded the download limit. The download limit is ${downloadMax}.`
        );
        overlay.hide(); // Hide immediately on error
        return;
      }

      if (totalNum === 0) {
        alert.Error("Download Error", "No data found");
        overlay.hide(); // Hide immediately on error
        return;
      }

      alert.Info("DOWNLOADING...", `Downloading ${total} record(s)`);

      try {
        const outcome = await this.downloadData(dlString);
        const exportData = JSON.parse(outcome);
        const options = { fileName };
        Jhxlsx.export(exportData, options);
      } catch (error) {
        console.error(error);
        alert.Error("Download Error", error);
      } finally {
        overlay.hide(); // Hide after download attempt completes
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
      console.log(selectedItems);
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
  },
  computed: {},
  template: `
        <div>

        
        
            <div class="row">
                <div class="col-md-12 col-sm-12 col-12 mb-2">
                    <h2 class="content-header-title header-txt float-left mb-0">Monitoring Tool</h2>
                    <div class="breadcrumb-wrapper">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="./">Home</a></li>
                            <li class="breadcrumb-item active">Monitoring Tools</li>
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
                                        <input type="text" id="form-search" v-model="bulkUserForm.mobilizationDate" class="form-control date" placeholder="Search using Monitoring tool Name" />
                                        <div class="input-group-append">
                                            <button class="btn btn-primary pl-1 pr-1" @click="loadDp()" type="button">Load</button>
                                        </div>
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
                            <th>Monitoring Tools Name</th>
                            <th>Total Form Filled</th>
                            <th width="60px" class="text-center">
                                <a href="javascript:void(0)" data-toggle="tooltip" data-placement="top" title="Downlaod All" class="btn btn-primary btn-sm p-50"><i class="feather icon-download-cloud"></i></a>
                            </th>
                        </thead>
                        <tbody>
                            <tr v-for="(g, i) in tableData">
                                <td>{{i+1}}</td>
                                <td>{{g.name}}</td>
                                <td>{{g.total}}</td>
                                <td class="text-center">
                                    <a href="javascript:void(0)" class="btn btn-sm btn-primary btn-sm p-25" @click="downloadReport(g.sn, g.total)"><i class="feather icon-download"></i></a>
                                </td>
                            </tr>
                            <tr v-if="tableData.length == 0"><td class="text-center pt-2" colspan="5"><small>No Monitoring Tools Added</small></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
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
