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
// g-event-update-user

Vue.component("page-body", {
  data: function () {
    return {
      page: "dashboard", //  page by name home | result | ...
    };
  },
  mounted() {
    /*  Manages events Listening    */
    const tableContainer = document.querySelectorAll(".lgaAggregate");
    if (tableContainer) {
      tableContainer.forEach(function (element) {
        new PerfectScrollbar(element);
      });
    }
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

                <div v-show="page == 'dashboard'">
                    <mobilization_dashboard/>
                    <lga_aggregate_mobilization_dashboard/>
                </div>

            </div>
        </div>
    `,
});

Vue.component("apexchart", VueApexCharts);
// User List Page
Vue.component("mobilization_dashboard", {
  data: function () {
    return {
      topMobilizationStat: {
        eNetcard: 0,
        hhMobilized: 0,
        familySize: 0,
      },
      statData: {
        tableData: [],
        chartData: [],
        dataIndex: "",
      },
      chartStates: ["hhMobilized", "eNetcard", "family_size"],
      chartCurrentTab: 0,
      chartFilter: {
        lgaid: "",
        lgaName: "",
        wardid: "",
        wardName: "",
        dpid: "",
        dpName: "",
        date: "",
        chartLevel: 0,
      },
      lgaMobilizationAggregate: {
        mobilizationData: [],
        mobDateData: [],
        dateFilter: "",
        lgaIdFilter: 0,
        lgaNameFilter: "",
      },
      series: [],
      allChartData: [],
      chartOptions: {
        xaxis: {
          title: {
            text: "",
          },
        },
      },
    };
  },
  beforeMount() {
    this.getDailyTopSummary();
  },
  mounted() {
    /*  Manages events Listening    */
    this.getTopListStatistics();
    // EventBus.$on("g-event-goto-page", this.gotoPageHandler);
  },
  methods: {
    async getTopListStatistics() {
      try {
        const url = common.DataService;
        overlay.show();

        const response = await axios.get(url + "?qid=750");

        const allData = response.data.data[0];

        // Update statistics
        this.topMobilizationStat.eNetcard = this.convertStringNumberToFigures(
          allData.netcards
        );
        this.topMobilizationStat.hhMobilized =
          this.convertStringNumberToFigures(allData.households);
        this.topMobilizationStat.familySize = this.convertStringNumberToFigures(
          allData.family_size
        );

        overlay.hide();
      } catch (error) {
        overlay.hide();
        alert.Error("ERROR", error);
      }
    },
    async getDailyTopSummary() {
      //Get all Top Summary Daily
      try {
        const url = common.DataService;
        overlay.show();

        const response = await axios.get(url + "?qid=751");

        const allData = response.data;
        this.statData.tableData = allData.table;
        this.statData.chartData = allData.chart;
        this.statData.chartData.xAxisLabel = "Days";
        this.chartFilter.chartLevel = allData.level;

        this.statData.chartData[1] = this.statData.chartData[1].map((item) =>
          this.convertToDateMonthDay(item)
        );

        this.plotChart();

        overlay.hide();
      } catch (error) {
        overlay.hide();
        alert.Error("ERROR", error);
      }
    },
    plotChart() {
      const yAxislabel = this.statData.chartData[0][this.chartCurrentTab].name;
      const xAxisLabel = this.statData.chartData.xAxisLabel;

      const options = {
        chart: {
          type: "bar",
        },
        colors: "#7367f0",
        xaxis: {
          categories: [],
          title: {
            text: xAxisLabel,
            style: {
              color: "#6e6b7b",
              fontWeight: "bold",
            },
            offsetY: 10,
          },
        },
        yaxis: {
          title: {
            text: yAxislabel,
            style: {
              color: "#6e6b7b",
              fontWeight: "bold",
            },
          },

          labels: {
            formatter: function (val) {
              return parseInt(val).toLocaleString();
            },
            // show: !1,
          },
        },
        plotOptions: {
          bar: {
            dataLabels: {
              position: "top", // Place data labels on top of the bars
            },
          },
        },
        dataLabels: {
          style: {
            colors: ["#000"], // Change value color on bars here
          },
          enabled: true,
          formatter: function (val) {
            return parseInt(val).toLocaleString();
            // return parseInt(val) + "k";
          },
          offsetY: -20,
          style: {
            fontSize: "12px",
            colors: ["#000"],
          },
        },
        noData: {
          text: "No data available, kindly refresh",
          align: "center",
          verticalAlign: "middle",
          offsetX: 0,
          offsetY: 0,
          style: {
            color: "#333",
            fontSize: "14px",
          },
        },
      };

      this.chartOptions = options;

      const newData = {
        categories: this.statData.chartData[1],
      };

      this.series = [this.statData.chartData[0][this.chartCurrentTab]];
      this.chartOptions.xaxis.categories.splice(
        0,
        this.chartOptions.xaxis.categories.length,
        ...newData.categories
      );
    },
    loadNewChart(d) {
      this.chartCurrentTab = d;
      this.plotChart();
    },
    convertToDateMonthDay(date) {
      let monthDay = new Date(date).toLocaleDateString("en-US", {
        month: "short",
        day: "2-digit",
      });
      return monthDay;
    },
    checkIfDateIsToday(date) {
      const today = new Date().toISOString().slice(0, 10); // Get today's date in "YYYY-MM-DD" format

      if (date === today) {
        return "Today";
      } else {
        // Parse the given date string
        const parsedDate = new Date(date);

        // Format the date to "Jan. 1st, 2010" using the toLocaleDateString() method
        const formattedDate = parsedDate.toLocaleDateString("en-US", {
          year: "numeric",
          month: "short",
          day: "2-digit",
        });

        return formattedDate; // Output: "Jan 20, 2010"
      }
    },
    isValidDate(dateString) {
      const regex = /^\d{4}-\d{2}-\d{2}$/;
      return regex.test(dateString)
        ? this.convertToDateMonthDay(dateString)
        : dateString;
    },
    convertStringNumberToFigures(d) {
      let data = d ? parseInt(d) : 0;
      return data ? data.toLocaleString() : 0;
    },
    generateStatData(i = "") {
      // Refresh Top List
      this.getTopListStatistics();
      this.statData.dataIndex = i;

      const { date, lgaid, wardid, dpid } = this.chartFilter;
      const tableData = this.statData.tableData[this.statData.dataIndex];

      if (!isNaN(i)) {
        if (!date && !lgaid && !wardid && !dpid) {
          this.chartFilter.date = tableData.title;
          this.getDailySummaryPerDate();
        } else if (date && !lgaid && !wardid && !dpid) {
          this.chartFilter.lgaid = tableData.lgaid;
          this.chartFilter.lgaName = tableData.title;
          this.getDailySummaryPerWard();
        } else if (date && lgaid && (!wardid || wardid) && !dpid) {
          this.chartFilter.wardName = tableData.title;
          this.chartFilter.wardid = tableData.wardid;
          if (this.chartFilter.chartLevel != 3) {
            this.getDailySummaryPerDatePerDp();
          }
        } else {
          this.getDailyTopSummary();
        }
      } else {
        if (date && lgaid && !wardid && !dpid) {
          this.getDailySummaryPerWard();
        } else if (date && lgaid && wardid && (dpid == "" || dpid != "")) {
          this.getDailySummaryPerDatePerDp();
        } else if (date && !lgaid && !wardid && (dpid == "" || dpid != "")) {
          this.getDailySummaryPerDate();
        } else {
          this.getDailyTopSummary();
        }
      }
    },
    refresh() {
      this.getTopListStatistics();
      // console.log("this.chartFilter.chartLevel");
      if (this.chartFilter.chartLevel == 0) {
        this.getDailyTopSummary();
      } else if (this.chartFilter.chartLevel == 1) {
        this.getDailySummaryPerDate();
      } else if (this.chartFilter.chartLevel == 2) {
        this.getDailySummaryPerWard();
      } else if (this.chartFilter.chartLevel == 3) {
        this.getDailySummaryPerDatePerDp();
      } else {
        this.getDailyTopSummary();
      }
    },
    dailyStatBreadCrum(state) {
      this.statData.dataIndex = document
        .querySelector(".data-index-" + state)
        .getAttribute("data-index");

      if (state == 1) {
        this.chartFilter.lgaid =
          this.chartFilter.wardid =
          this.chartFilter.dpid =
            "";
        this.getDailySummaryPerDate();
      } else if (state == 2) {
        this.chartFilter.wardid = this.chartFilter.dpid = "";
        this.getDailySummaryPerWard();
      } else if (state == 3) {
        this.chartFilter.wardid = this.chartFilter.dpid = "";
        this.getDailySummaryPerDatePerDp();
      } else {
        this.chartFilter.date =
          this.chartFilter.lgaid =
          this.chartFilter.wardid =
          this.chartFilter.dpid =
            "";
        this.getDailyTopSummary();
      }
    },
    async getDailySummaryPerDate(action = "") {
      await this.fetchData("753", {
        date: this.chartFilter.date,
        xAxisLabel: "LGAS",
      });
      let list = document.querySelector(".data-index-1");
      if (list) {
        list.setAttribute("data-index", this.statData.dataIndex);
      }
    },
    async getDailySummaryPerWard() {
      await this.fetchData("754", {
        date: this.chartFilter.date,
        lgaid: this.chartFilter.lgaid,
        xAxisLabel: "Wards",
      });
      let list = document.querySelector(".data-index-2");
      if (list) {
        list.setAttribute("data-index", this.statData.dataIndex);
      }
    },
    async getDailySummaryPerDatePerDp() {
      await this.fetchData("755", {
        date: this.chartFilter.date,
        wardid: this.chartFilter.wardid,
        xAxisLabel: "DPs",
      });
    },
    async fetchData(queryId, params) {
      try {
        const url = common.DataService;
        overlay.show();

        const response = await axios.get(`${url}?qid=${queryId}`, {
          params: params,
        });

        const allData = response.data;
        this.statData.tableData = allData.table;
        this.statData.chartData = allData.chart;
        this.statData.chartData.xAxisLabel = params.xAxisLabel;
        this.chartFilter.chartLevel = allData.level;

        this.statData.chartData[1] = this.statData.chartData[1].map(
          (item) => item
        );

        this.plotChart();

        overlay.hide();
      } catch (error) {
        overlay.hide();
        alert.Error("ERROR", error);
      }
    },
    capitalizeWords(str) {
      return str
        .toLowerCase()
        .split(" ")
        .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
        .join(" ");
    },
  },
  computed: {},
  template: `
        <div>
            <div class="row">
                <div class="col-12 mb-2">
                    <h2 class="content-header-title header-txt float-left mb-0">Dashboard</h2>
                    <div class="breadcrumb-wrapper">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="../dashboard">Home</a></li>
                            <li class="breadcrumb-item active">Mobilization</li>
                        </ol>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <!-- Split Screen into 2: Begin -->
                <div class="col-sm-12 col-md-12 col-lg-12 col-12">
                    <div class="row">

                        <div class="col-sm-6 col-md-4 col-lg-4 col-12">
                            <div class="card">
                                <div class="card-header d-flex align-items-center justify-content-between">
                                    <div>
                                        <h3 class="fw-bolder mb-75" v-text="topMobilizationStat.hhMobilized"></h3>
                                        <span class="card-text">HH Mobilized</span>
                                    </div>
                                    <div class="avatar bg-light-primary p-50 m-0">
                                        <div class="avatar-content">
                                            <i data-feather="home" class="font-medium-4"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-sm-6 col-md-4 col-lg-4 col-12">
                            <div class="card">
                                <div class="card-header d-flex align-items-center justify-content-between">
                                    <div>
                                        <h3 class="fw-bolder mb-75" v-text="topMobilizationStat.familySize"></h3>
                                        <span>Family Size</span>
                                    </div>
                                    <div class="avatar bg-light-primary p-50 m-0">
                                        <div class="avatar-content">
                                            <i data-feather="users" class="font-medium-4"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-sm-6 col-md-4 col-lg-4 col-12">
                            <div class="card">
                                <div class="card-header d-flex align-items-center justify-content-between">
                                    <div>
                                        <h3 class="fw-bolder mb-75" v-text="topMobilizationStat.eNetcard"></h3>
                                        <span>e-Netcard Issued</span>
                                    </div>
                                    <div class="avatar bg-light-primary p-50 m-0">
                                        <div class="avatar-content">
                                            <i data-feather="credit-card" class="font-medium-4"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!--
                        <div class="col-sm-6 col-md-3 col-lg-3 col-12">
                            <div class="card">
                                <div class="card-header d-flex align-items-center justify-content-between">
                                    <div>
                                        <h3 class="fw-bolder mb-75" v-text=""></h3>
                                        <span>Avg. e-Netcard Per HH</span>
                                    </div>
                                    <div class="avatar bg-light-info p-50 m-0">
                                        <div class="avatar-content">
                                            <i data-feather="pocket" class="font-medium-4"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        -->

                    </div>
                    
                </div>


                <div class="col-12 mt-2 pl-0 pr-0 pb-0">
                    <div class="breadcrumb-wrapper reporting-dashboard d-flex justify-content-between">
                        <ol class="breadcrumb pt-75">
                            <li class="breadcrumb-item data-index-0" data-index="0" :class="chartFilter.chartLevel == 0? 'active' : ''" v-if="chartFilter.chartLevel >= 0" @click="dailyStatBreadCrum(0)">Daily mobilization Report</li>
                            <li class="breadcrumb-item data-index-1" data-index="" :class="chartFilter.chartLevel == 1? 'active' : ''" v-if="chartFilter.chartLevel >= 1" @click="dailyStatBreadCrum(1)"><span>{{convertToDateMonthDay(chartFilter.date)}}</span>, LGAs Mobilization</li>
                            <li class="breadcrumb-item data-index-2" data-index="" :class="chartFilter.chartLevel == 2? 'active' : ''" v-if="chartFilter.chartLevel >= 2" @click="dailyStatBreadCrum(2)">{{ capitalizeWords(chartFilter.lgaName)+' LGA, Wards' }} </li>
                            <li class="breadcrumb-item data-index-3" data-index="" :class="chartFilter.chartLevel == 3? 'active' : ''" v-if="chartFilter.chartLevel >= 3" @click="dailyStatBreadCrum(3)">{{ capitalizeWords(chartFilter.wardName)+ ' Ward, DPs' }}  </li>
                        </ol>
                        <div class="dropdown">
                            <button class="btn tb-primary" @click="refresh()" type="button" data-toggle="dropdown" aria-haspopup="true"
                                aria-expanded="false">
                                <i class="ti ti-refresh ti-sm text-muted"></i>
                            </button>
                        </div>
                    </div>

                </div>

                <!-- LGA Mobilization Aggregate  -->
                <div class="col-12 col-xl-6 mb-0">
                  <div class="card" style="height: 550px !important;">

                    <div class="table-responsive lgaAggregate">
                      <table class="table table-fixed border-top table-striped table-hover table-hover-animation" id="stat-table">
                        <thead class="border-bottom">
                          <tr>
                            <th colspan="2">{{chartOptions.xaxis.title.text}}</th>
                            <th>HH Mobilized</th>
                            <th>e-Netcard Issued</th>
                            <th>Family Size</th>
                          </tr>
                        </thead>
                        <tbody>
                          <tr @click="generateStatData(i)" v-for="(g, i) in (statData.tableData)" :key="g.title">
                            <td style="padding-left: 1rem !important; padding-right: .2rem !important;"><i class="ti ti-circle-plus text-primary" v-if="chartFilter.chartLevel < 3"></i></td>
                            <td style="padding-left: .4rem !important;">{{ isValidDate(capitalizeWords(g.title))}}</td>
                            <td>{{convertStringNumberToFigures(g.households)}}</td>
                            <td>{{convertStringNumberToFigures(g.netcards)}}</td>
                            <td>{{convertStringNumberToFigures(g.family_size)}}</td>
                          </tr>
                          <tr v-if="statData.tableData.length == 0"><td class="text-center pt-4 pb-4" colspan="5"><small>No Data Available, Kindly Refresh</small></td></tr>
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>
                <!-- LGA Mobilization Aggregate: End -->


                <!-- Daily Mobilization Reports Tabs-->
                <div class="col-12 col-xl-6 mb-0">
                  <div class="card"  style="height: 550px !important;">

                    <div class="card-body">
                              
                      <ul class="nav nav-tabs widget-nav-tabs pb-1 gap-4 mx-25 d-flex flex-nowrap" role="tablist">
                        <li class="nav-item" @click="loadNewChart(0)">
                          <a href="javascript:void(0);" class="nav-link btn active d-flex flex-column align-items-center justify-content-center" role="tab" data-toggle="tab" data-target="#hh-mobilized-id" aria-controls="hh-mobilized-id" aria-selected="true">
                            <div class="badge bg-label-secondary rounded p-50 pb-75"><i class="ti ti-home ti-sm"></i></div>
                            <h6 class="tab-widget-title mb-0 mt-50">HH Mobilized</h6>
                          </a>
                        </li>
                        <li class="nav-item" @click="loadNewChart(1)">
                          <a href="javascript:void(0);" class="nav-link btn d-flex flex-column align-items-center justify-content-center" role="tab" data-toggle="tab" data-target="#e-netcard-issued-id" aria-controls="e-netcard-issued-id" aria-selected="false">
                            <div class="badge bg-label-secondary rounded p-50 pb-75"><i class="ti ti-cash ti-sm"></i></div>
                            <h6 class="tab-widget-title mb-0 mt-50">e-Netcard Issued</h6>
                          </a>
                        </li>
                        <li class="nav-item" @click="loadNewChart(2)">
                          <a href="javascript:void(0);" class="nav-link btn d-flex flex-column align-items-center justify-content-center" role="tab" data-toggle="tab" data-target="#family-size-id" aria-controls="family-size-id" aria-selected="false">
                            <div class="badge bg-label-secondary rounded p-50 pb-75"><i class="ti ti-users ti-sm"></i></div>
                            <h6 class="tab-widget-title mb-0 mt-50">Family Size</h6>
                          </a>
                        </li>
                      </ul>
                      
                      <div class="tab-content p-0 ms-0 ms-sm-2">
                        <div class="tab-pane fade show active" id="hh-mobilized-id" role="tabpanel">
                          <apexchart height="410" type="bar" :options="chartOptions" :series="series"></apexchart>
                        </div>
                      </div>

                    </div>
                  </div>
                </div>
                <!-- Daily Mobilization Split Screen into 2: End -->

            </div>
 


        </div>
    `,
});

Vue.component("lga_aggregate_mobilization_dashboard", {
  data: function () {
    return {
      statData: {
        tableData: [],
        chartData: [],
        dataIndex: "",
      },
      chartCurrentTab: 0,
      chartFilter: {
        lgaid: "",
        lgaName: "",
        wardid: "",
        wardName: "",
        dpid: "",
        dpName: "",
        date: "",
        chartLevel: 0,
      },
      series: [],
      allChartData: [],
      chartOptions: {
        xaxis: {
          title: {
            text: "",
          },
        },
      },
    };
  },
  beforeMount() {
    this.getAggregateByLocation();
  },
  mounted() {
    /*  Manages events Listening    */
    this.getAggregateByLocation();
    // EventBus.$on("g-event-goto-page", this.gotoPageHandler);
  },
  methods: {
    async getAggregateByLocation() {
      //Get all Top Summary Daily
      try {
        const url = common.DataService;
        overlay.show();

        const response = await axios.get(url + "?qid=752");

        const allData = response.data;
        this.statData.tableData = allData.table;
        this.statData.chartData = allData.chart;
        this.statData.chartData.xAxisLabel = "LGAs";
        this.chartFilter.chartLevel = allData.level;

        this.statData.chartData[1] = this.statData.chartData[1].map(
          (item) => item
        );

        this.plotAggregateChart();

        overlay.hide();
      } catch (error) {
        overlay.hide();
        alert.Error("ERROR", error);
      }
    },
    plotAggregateChart() {
      const yAxislabel = this.statData.chartData[0][this.chartCurrentTab].name;
      const xAxisLabel = this.statData.chartData.xAxisLabel;

      const options = {
        chart: {
          type: "bar",
        },
        colors: "#7367f0",
        xaxis: {
          categories: [],
          title: {
            text: xAxisLabel,
            style: {
              color: "#6e6b7b",
              fontWeight: "bold",
            },
            offsetY: 10,
          },
        },
        yaxis: {
          title: {
            text: yAxislabel,
            style: {
              color: "#6e6b7b",
              fontWeight: "bold",
            },
          },

          labels: {
            formatter: function (val) {
              return parseInt(val).toLocaleString();
            },
            // show: !1,
          },
        },
        plotOptions: {
          bar: {
            dataLabels: {
              position: "top", // Place data labels on top of the bars
            },
          },
        },
        dataLabels: {
          style: {
            colors: ["#000"], // Change value color on bars here
          },
          enabled: true,
          formatter: function (val) {
            return parseInt(val).toLocaleString();
            // return parseInt(val) + "k";
          },
          offsetY: -20,
          style: {
            fontSize: "12px",
            colors: ["#000"],
          },
        },
        noData: {
          text: "No data available, kindly refresh",
          align: "center",
          verticalAlign: "middle",
          offsetX: 0,
          offsetY: 0,
          style: {
            color: "#333",
            fontSize: "14px",
          },
        },
      };

      this.chartOptions = options;

      const newData = {
        categories: this.statData.chartData[1],
      };

      this.series = [this.statData.chartData[0][this.chartCurrentTab]];
      this.chartOptions.xaxis.categories.splice(
        0,
        this.chartOptions.xaxis.categories.length,
        ...newData.categories
      );
    },
    loadAggregateNewChart(d) {
      this.chartCurrentTab = d;
      this.plotAggregateChart();
    },
    convertStringNumberToFigures(d) {
      let data = d ? parseInt(d) : 0;
      return data ? data.toLocaleString() : 0;
    },
    generateAggregateStatData(i = "") {
      // refreshAggregatePage Top List
      this.statData.dataIndex = i;

      const { date, lgaid, wardid, dpid } = this.chartFilter;
      const tableData = this.statData.tableData[this.statData.dataIndex];

      if (!isNaN(i)) {
        if (!lgaid && !wardid && !dpid) {
          this.chartFilter.lgaid = tableData.lgaid;
          this.chartFilter.lgaName = tableData.title;
          this.getAggregateSummaryPerWard();
        } else if (lgaid && !wardid && !dpid) {
          this.chartFilter.wardid = tableData.wardid;
          this.chartFilter.wardName = tableData.title;
          this.getAggregateSummaryPerDp();
        } else if (wardid && !dpid && this.chartLevel != 2) {
          this.chartFilter.dpName = tableData.title;
          this.chartFilter.dpid = tableData.dpid;
          this.getAggregateSummaryPerDp();
        } else {
          // this.getAggregateByLocation();
        }
      } else {
        if (lgaid && !wardid && !dpid) {
          this.getAggregateSummaryPerWard();
        } else if (wardid && (dpid == "" || dpid != "")) {
          this.getAggregateSummaryPerDp();
        } else if (!lgaid && !wardid && (dpid == "" || dpid != "")) {
          this.getAggregateByLocation();
        }
      }
    },
    refreshAggregatePage() {
      // console.log("this.chartFilter.chartLevel");
      if (this.chartFilter.chartLevel == 0) {
        this.getAggregateByLocation();
      } else if (this.chartFilter.chartLevel == 1) {
        this.getAggregateSummaryPerWard();
      } else if (this.chartFilter.chartLevel == 2) {
        this.getAggregateSummaryPerDp();
      } else {
        this.getAggregateByLocation();
      }
    },
    aggregateStatBreadCrum(state) {
      this.statData.dataIndex = document
        .querySelector(".data-index-" + state)
        .getAttribute("data-index");

      if (state == 1) {
        this.chartFilter.wardid = this.chartFilter.dpid = "";
        this.getAggregateSummaryPerWard();
      } else if (state == 2) {
        this.chartFilter.wardid = this.chartFilter.dpid = "";
        this.getAggregateSummaryPerDp();
      } else {
        this.chartFilter.lgaid =
          this.chartFilter.wardid =
          this.chartFilter.dpid =
            "";
        this.getAggregateByLocation();
      }
    },
    async getAggregateSummaryPerWard() {
      await this.fetchData("756", {
        lgaid: this.chartFilter.lgaid,
        xAxisLabel: "Wards",
      });
      let list = document.querySelector(".data-index-1");
      if (list) {
        list.setAttribute("data-index", this.statData.dataIndex);
      }
    },
    async getAggregateSummaryPerDp() {
      await this.fetchData("757", {
        wardid: this.chartFilter.wardid,
        xAxisLabel: "DPs",
      });
    },
    async fetchData(queryId, params) {
      try {
        const url = common.DataService;
        overlay.show();

        const response = await axios.get(`${url}?qid=${queryId}`, {
          params: params,
        });

        const allData = response.data;
        this.statData.tableData = allData.table;
        this.statData.chartData = allData.chart;
        this.statData.chartData.xAxisLabel = params.xAxisLabel;
        this.chartFilter.chartLevel = allData.level;

        this.statData.chartData[1] = this.statData.chartData[1].map(
          (item) => item
        );

        this.plotAggregateChart();

        overlay.hide();
      } catch (error) {
        overlay.hide();
        alert.Error("ERROR", error);
      }
    },
    capitalizeWords(str) {
      return str
        .toLowerCase()
        .split(" ")
        .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
        .join(" ");
    },
  },
  computed: {},
  template: `
        <div>
            <div class="row">
                <!-- Split Screen into 2: Begin -->
                


                <div class="col-12 mt-1 pl-0 pr-0 pb-0">
                    <div class="breadcrumb-wrapper reporting-dashboard d-flex justify-content-between">
                        <ol class="breadcrumb pt-75">
                            <li class="breadcrumb-item data-index-0" data-index="0" :class="chartFilter.chartLevel == 0? 'active' : ''" v-if="chartFilter.chartLevel >= 0" @click="aggregateStatBreadCrum(0)">LGA Mobilization Report</li>
                            <li class="breadcrumb-item data-index-1" data-index="" :class="chartFilter.chartLevel == 1? 'active' : ''" v-if="chartFilter.chartLevel >= 1" @click="aggregateStatBreadCrum(1)"><span>{{capitalizeWords(chartFilter.lgaName)}}</span> LGA, Wards Mobilization</li>
                            <li class="breadcrumb-item data-index-2" data-index="" :class="chartFilter.chartLevel == 2? 'active' : ''" v-if="chartFilter.chartLevel >= 2" @click="aggregateStatBreadCrum(2)">{{ capitalizeWords(chartFilter.wardName)+' Ward, Dps' }} </li>
                        </ol>
                        <div class="dropdown">
                            <button class="btn tb-primary" @click="refreshAggregatePage()" type="button" data-toggle="dropdown" aria-haspopup="true"
                                aria-expanded="false">
                                <i class="ti ti-refresh ti-sm text-muted"></i>
                            </button>
                        </div>
                    </div>

                </div>

                <!-- LGA Mobilization Aggregate  -->
                <div class="col-12 col-xl-6 mb-4">
                  <div class="card" style="height: 550px !important;">

                    <div class="table-responsive lgaAggregate">
                      <table class="table table-fixed border-top table-striped table-hover table-hover-animation" id="stat-table">
                        <thead class="border-bottom">
                          <tr>
                            <th colspan="2">{{chartOptions.xaxis.title.text}}</th>
                            <th>HH Mobilized</th>
                            <th>e-Netcard Issued</th>
                            <th>Family Size</th>
                          </tr>
                        </thead>
                        <tbody>
                          <tr @click="generateAggregateStatData(i)" v-for="(g, i) in (statData.tableData)" :key="g.title">
                          <td style="padding-left: 1rem !important; padding-right: .2rem !important;"><i class="ti ti-circle-plus text-primary" v-if="chartFilter.chartLevel < 2"></i></td>
                            <td style="padding-left: .4rem !important;">{{ capitalizeWords(g.title)}}</td>
                            <td>{{convertStringNumberToFigures(g.households)}}</td>
                            <td>{{convertStringNumberToFigures(g.netcards)}}</td>
                            <td>{{convertStringNumberToFigures(g.family_size)}}</td>
                          </tr>
                          <tr v-if="statData.tableData.length == 0"><td class="text-center pt-4 pb-4" colspan="5"><small>No Data Available, Kindly Refresh</small></td></tr>
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>
                <!-- LGA Mobilization Aggregate: End -->


                <!-- Daily Mobilization Reports Tabs-->
                <div class="col-12 col-xl-6 mb-4">
                  <div class="card"  style="height: 550px !important;">

                    <div class="card-body">
                              
                      <ul class="nav nav-tabs widget-nav-tabs pb-1 gap-4 mx-25 d-flex flex-nowrap" role="tablist">
                        <li class="nav-item" @click="loadAggregateNewChart(0)">
                          <a href="javascript:void(0);" class="nav-link btn active d-flex flex-column align-items-center justify-content-center" role="tab" data-toggle="tab" data-target="#hh-mobilized-id-aggregate" aria-controls="hh-mobilized-id-aggregate" aria-selected="true">
                            <div class="badge bg-label-secondary rounded p-50 pb-75"><i class="ti ti-home ti-sm"></i></div>
                            <h6 class="tab-widget-title mb-0 mt-50">HH Mobilized</h6>
                          </a>
                        </li>
                        <li class="nav-item" @click="loadAggregateNewChart(1)">
                          <a href="javascript:void(0);" class="nav-link btn d-flex flex-column align-items-center justify-content-center" role="tab" data-toggle="tab" data-target="#e-netcard-issued-id-aggregate" aria-controls="e-netcard-issued-id-aggregate" aria-selected="false">
                            <div class="badge bg-label-secondary rounded p-50 pb-75"><i class="ti ti-cash ti-sm"></i></div>
                            <h6 class="tab-widget-title mb-0 mt-50">e-Netcard Issued</h6>
                          </a>
                        </li>
                        <li class="nav-item" @click="loadAggregateNewChart(2)">
                          <a href="javascript:void(0);" class="nav-link btn d-flex flex-column align-items-center justify-content-center" role="tab" data-toggle="tab" data-target="#family-size-id-aggregate" aria-controls="family-size-id-aggregate" aria-selected="false">
                            <div class="badge bg-label-secondary rounded p-50 pb-75"><i class="ti ti-users ti-sm"></i></div>
                            <h6 class="tab-widget-title mb-0 mt-50">Family Size</h6>
                          </a>
                        </li>
                      </ul>
                      
                      <div class="tab-content p-0 ms-0 ms-sm-2">
                        <div class="tab-pane fade show active" id="hh-mobilized-id-aggregate" role="tabpanel">
                          <apexchart height="410" type="bar" :options="chartOptions" :series="series"></apexchart>
                        </div>
                      </div>

                    </div>
                  </div>
                </div>
                <!-- Daily Mobilization Split Screen into 2: End -->

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
