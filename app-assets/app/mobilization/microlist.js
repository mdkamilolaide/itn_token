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
            <dashboard_container/>
        </div>
    </div>
    `,
});

Vue.component("dashboard_container", {
  data: function () {
    return {
      geoIndicator: {
        state: 50,
        currentLevelId: 0,
        lga: "",
        lgaName: "",
      },
      checkToggle: false,
      sysDefaultData: [],
      lgaLevelData: [],
      wardLevelData: [],
      tableData: [],
      bulkUserForm: {
        geoLevel: "",
        geoLevelId: 0,
      },
    };
  },
  mounted() {
    /*  Manages events Listening    */
    this.getsysDefaultDataSettings();
    $("#dp-search").on("keyup", function () {
      var value = $(this).val().toLowerCase();
      $("#dpTable tbody tr").filter(function () {
        $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
      });
    });
  },
  methods: {
    getAllStat() {
      var url = common.DataService;
      var self = this;
      overlay.show();

      if (self.geoIndicator.lga == "") {
        overlay.hide();
        alert.Error("Error", "No LGA was Selected");
      } else {
        var endpoints = [
          url + "?qid=303&lgaid=" + self.geoIndicator.lga, //Get All DP in an LGA Using the LGA ID
        ];
        // Return our response in the allData variable as an array
        Promise.all(endpoints.map((endpoint) => axios.get(endpoint))).then(
          axios.spread((...allData) => {
            console.log(allData[0].data.data);
            self.tableData = allData[0].data.data;
            overlay.hide();
          })
        );
      }
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
            self.bulkUserForm.geoLevel = "state";
            self.bulkUserForm.geoLevelId = response.data.data[0].stateid;
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
    capitalize(word) {
      if (word) {
        const loweredCase = word.toLowerCase();
        return word[0].toUpperCase() + loweredCase.slice(1);
      } else {
        return word;
      }
    },
    async exportMicroPosition() {
      var self = this;

      if (self.geoIndicator.lga == "") {
        overlay.hide();
        alert.Error("Error", "No LGA was Selected");
      } else if (self.tableData.length < 1) {
        alert.Error(
          "No Data",
          "No DP Data to Download for " + self.geoIndicator.lgaName + " LGA"
        );
      } else {
        // Count URL
        var veriUrl = "qid=305&lgaid=" + self.geoIndicator.lga;

        // Data Downlaod
        var dlString = "qid=304&lgaid=" + self.geoIndicator.lga;

        var today = new Date();
        var date =
          today.getDate() +
          "-" +
          (today.getMonth() + 1) +
          "-" +
          today.getFullYear();
        var time =
          today.getHours() +
          ":" +
          today.getMinutes() +
          ":" +
          today.getSeconds();
        var dateTime = date + " " + time;

        var filename =
          this.geoIndicator.lgaName +
          " Micro Positioning List (" +
          dateTime +
          ")";
        overlay.show();

        //  count export data
        let count = new Promise((resolve, reject) => {
          $.ajax({
            url: common.DataService,
            type: "POST",
            data: veriUrl,
            dataType: "json",
            success: function (data) {
              resolve(data.total);
            },
          });
        });
        let result = await count; //  wait till the promise resolves (*)
        var downloadMax = common.ExportDownloadLimit;

        if (parseInt(result) > downloadMax) {
          //  stop download
          alert.Error(
            "Download Error",
            "Unable to download data because it has exceeded download limit, download limit is " +
              downloadMax
          );
        } else if (parseInt(result) == 0) {
          alert.Error("Download Error", "No data found");
        } else {
          alert.Info("DOWNLOADING...", "Downloading " + result + " record(s)");
          //  Else continue download data
          var options = {
            fileName: filename,
          };

          let dl = new Promise((resolve, reject) => {
            $.ajax({
              url: common.DataService,
              type: "POST",
              data: dlString,
              success: function (data) {
                resolve(data);
              },
            });
          });

          let outcome = await dl; //  Wait till downloaded
          var exportData = JSON.parse(outcome);
          Jhxlsx.export(exportData, options);
        }
      }

      overlay.hide();
    },
    setLgaName(event) {
      this.geoIndicator.lgaName =
        event.target.options[event.target.options.selectedIndex].text;
    },
  },
  computed: {},
  template: `
        <div>
            <div class="row">
                <div class="col-12 mb-2">
                    <h2 class="content-header-title header-txt float-left mb-0">Mobilization</h2>
                    <div class="breadcrumb-wrapper">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="../mobilization">Dashboard</a></li>
                            <li class="breadcrumb-item active">Micro Positioning List</li>
                        </ol>
                    </div>
                </div>
            </div>
            
            <div class="row">
            
                <div class="col-12">
                    <div class="alert bg-light-primary pt-1 pb-1">
              
                        <div class="col-12">
                            <div class="row">
                                <div class="col-8 col-sm-9 col-md-10 col-lg-10">
                                    <div class="form-group">
                                        <label class="form-label" for="user-role">Choose LGA</label>
                                        <select id="user-role" class="form-control" v-model="geoIndicator.lga" @change="setLgaName($event)">
                                            <option  value="" selected="selected">Select a LGA</option>
                                            <option v-for="lga in lgaLevelData" :value="lga.lgaid">{{lga.lga}}</option>
                                        </select>
                                    </div>
                                </div> 

                                <div class="col-4 col-sm-3 col-md-2 col-lg-2 text-right">
                                    <div class="form-group"> 
                                        <button class="btn mt-2 btn-primary pl-1 pr-1" @click="getAllStat()" type="button">Load</button>
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
                            <th>LGA</th>
                            <th>Ward</th>
                            <th>Distribution Point Name</th>
                            <th>Population</th>
                            <th>Allocated Net</th>
                            <th>Net in Bales</th>
                            <th>Adjustment</th>
                            <th class="text-right" width="60px">
                                <a href="javascript:void(0)" data-toggle="tooltip" data-placement="top" title="Downlaod All" class="btn btn-primary btn-sm p-50" @click="exportMicroPosition()"><i class="feather icon-download-cloud"></i></a>
                            </th>
                        </thead>
                        <tbody>
                            <tr v-for="(g, i) in tableData">
                                <td>{{ i+1 }}</td>
                                <td>{{ g.lga }}</td>
                                <td>{{ g.ward }}</td>
                                <td>{{ g.dp }}</td>
                                <td>{{ g.family_size }}</td>
                                <td>{{ g.allocated_net }}</td>
                                <td>{{ g.in_bales }}</td>
                                <td>{{ g.difference }}</td>
                                <td></td>
                            </tr>
                            <tr v-if="tableData.length == 0"><td class="text-center pt-2" colspan="9"><small>No Data Currently Available or LGA Not Selected</small></td></tr>
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
