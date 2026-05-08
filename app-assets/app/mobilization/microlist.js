/**
 * Mobilization / Microlist — Vue 3 Composition API in place.
 * Two components — page-body and dashboard_container.
 * dashboard_container drives a single LGA filter, loads DPs in that LGA,
 * and exports the micro-positioning workbook via Excel.
 */

const { ref, reactive, onMounted } = Vue;
const { useApp, useFormat, safeMessage } = window.utils;

const PageBody = {
    setup() { return {}; },
    template: `
        <div>
            <div class="content-body">
                <dashboard_container/>
            </div>
        </div>
    `,
};

const DashboardContainer = {
    setup() {
        const fmtUtils = useFormat();

        const geoIndicator = reactive({
            state: 50, currentLevelId: 0, lga: '', lgaName: '',
        });
        const checkToggle = ref(false);
        const sysDefaultData = ref([]);
        const lgaLevelData = ref([]);
        const wardLevelData = ref([]);
        const tableData = ref([]);
        const bulkUserForm = reactive({ geoLevel: '', geoLevelId: 0 });

        function getsysDefaultDataSettings() {
            overlay.show();
            axios.get(common.DataService + '?qid=gen007')
                .then(function (response) {
                    if (response.data.data && response.data.data.length > 0) {
                        sysDefaultData.value = response.data.data[0];
                        getLgasLevel(response.data.data[0].stateid);
                        bulkUserForm.geoLevel = 'state';
                        bulkUserForm.geoLevelId = response.data.data[0].stateid;
                    }
                    overlay.hide();
                })
                .catch(function (error) {
                    overlay.hide();
                    alert.Error('ERROR', safeMessage(error));
                });
        }
        function getLgasLevel(stateid) {
            overlay.show();
            axios.post(common.DataService + '?qid=gen003', JSON.stringify(stateid))
                .then(function (response) {
                    lgaLevelData.value = (response.data && response.data.data) || [];
                    overlay.hide();
                })
                .catch(function (error) {
                    overlay.hide();
                    alert.Error('ERROR', safeMessage(error));
                });
        }
        function getAllStat() {
            overlay.show();
            if (geoIndicator.lga == '') {
                overlay.hide();
                alert.Error('Error', 'No LGA was Selected');
                return;
            }
            var endpoints = [common.DataService + '?qid=303&lgaid=' + geoIndicator.lga];
            Promise.all(endpoints.map(function (e) { return axios.get(e); })).then(
                axios.spread(function (...allData) {
                    tableData.value = (allData[0] && allData[0].data && allData[0].data.data) || [];
                    overlay.hide();
                })
            ).catch(function (error) {
                overlay.hide();
                alert.Error('ERROR', safeMessage(error));
            });
        }

        async function exportMicroPosition() {
            if (geoIndicator.lga == '') {
                overlay.hide();
                alert.Error('Error', 'No LGA was Selected');
                return;
            }
            if (tableData.value.length < 1) {
                alert.Error('No Data', 'No DP Data to Download for ' + geoIndicator.lgaName + ' LGA');
                return;
            }
            var veriUrl = 'qid=305&lgaid=' + geoIndicator.lga;
            var dlString = 'qid=304&lgaid=' + geoIndicator.lga;
            var today = new Date();
            var date = today.getDate() + '-' + (today.getMonth() + 1) + '-' + today.getFullYear();
            var time = today.getHours() + ':' + today.getMinutes() + ':' + today.getSeconds();
            var filename = geoIndicator.lgaName + ' Micro Positioning List (' + date + ' ' + time + ')';
            overlay.show();

            var count = await new Promise(function (resolve) {
                $.ajax({
                    url: common.DataService, type: 'POST', data: veriUrl, dataType: 'json',
                    success: function (data) { resolve(data.total); },
                });
            });
            var downloadMax = (window.common && window.common.ExportDownloadLimit) || 25000;
            if (parseInt(count) > downloadMax) {
                alert.Error('Download Error', 'Unable to download data because it has exceeded download limit, download limit is ' + downloadMax);
            } else if (parseInt(count) == 0) {
                alert.Error('Download Error', 'No data found');
            } else {
                alert.Info('DOWNLOADING...', 'Downloading ' + count + ' record(s)');
                var outcome = await new Promise(function (resolve) {
                    $.ajax({
                        url: common.DataService, type: 'POST', data: dlString,
                        success: function (data) { resolve(data); },
                    });
                });
                var exportData = JSON.parse(outcome);
                if (window.Jhxlsx && typeof window.Jhxlsx.export === 'function') {
                    window.Jhxlsx.export(exportData, { fileName: filename });
                }
            }
            overlay.hide();
        }

        function setLgaName(event) {
            geoIndicator.lgaName = event.target.options[event.target.options.selectedIndex].text;
        }

        onMounted(function () {
            getsysDefaultDataSettings();
            $('#dp-search').on('keyup', function () {
                var value = $(this).val().toLowerCase();
                $('#dpTable tbody tr').filter(function () {
                    $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
                });
            });
        });

        return {
            geoIndicator, checkToggle, sysDefaultData, lgaLevelData,
            wardLevelData, tableData, bulkUserForm,
            getsysDefaultDataSettings, getLgasLevel, getAllStat,
            exportMicroPosition, setLgaName,
            capitalize: fmtUtils.capitalize,
            formatNumber: fmtUtils.formatNumber,
        };
    },
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
                                            <option value="" selected="selected">Select a LGA</option>
                                            <option v-for="lga in lgaLevelData" :value="lga.lgaid" :key="lga.lgaid">{{ lga.lga }}</option>
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
                            <tr v-for="(g, i) in tableData" :key="g.dpid || i">
                                <td>{{ i + 1 }}</td>
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
};

useApp({ template: `<div><page-body/></div>` })
    .component('page-body', PageBody)
    .component('dashboard_container', DashboardContainer)
    .mount('#app');
