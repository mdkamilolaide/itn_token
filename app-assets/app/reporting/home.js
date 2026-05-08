/**
 * Reporting / Home — Vue 3 Composition API in place.
 * Two components — page-body and reporting_lists.
 *
 * Hub of all 14 system reports (activity, mobilization, distribution).
 * Single modal switches between training-id picker and single/range date
 * picker depending on the report type, then triggers an Excel export.
 */

const { ref, reactive, onMounted, onBeforeMount } = Vue;
const { useApp, useFormat, safeMessage } = window.utils;

const PageBody = {
    setup() { return {}; },
    template: `
        <div>
            <div class="content-body">
                <reporting_lists/>
            </div>
        </div>
    `,
};

const ReportingLists = {
    setup() {
        const fmtUtils = useFormat();

        const geoIndicator = reactive({ geoLevel: '', geoLevelId: 0 });
        const permission = ref(
            (typeof getPermission === 'function')
                ? (getPermission(typeof per !== 'undefined' ? per : null, 'reporting') || { permission_value: 0 })
                : { permission_value: 0 }
        );
        const checkToggle = ref(false);
        const tableData = ref([]);
        const searchReport = ref('');
        const dateTitle = ref('');
        const report = reactive({
            reportState: 0, reportTitle: '', reportName: '', reportModule: '',
            reportDate: '', startDate: '', endDate: '',
        });
        const trainingListData = ref([]);
        const trainingForm = reactive({ trainingId: 0, trainingName: '' });

        function getAllTrainingLists() {
            overlay.show();
            axios.get(common.DataService + '?qid=104a&gl=' + geoIndicator.geoLevel + '&glid=' + geoIndicator.geoLevelId)
                .then(function (response) {
                    trainingListData.value = (response.data && response.data.data) || [];
                    overlay.hide();
                })
                .catch(function (error) {
                    overlay.hide();
                    alert.Error('ERROR', safeMessage(error));
                });
        }

        function openModal(reportType) {
            switch (reportType) {
                case 'participantList':
                    report.reportTitle = 'Filter to a <b>Specific Training</b> to Download Participants List';
                    report.reportName = 'Participants List';
                    report.reportState = 1;
                    report.reportModule = 'activity';
                    break;
                case 'bankVerificationStatus':
                    report.reportTitle = 'Filter to a <b>Specific Training</b> to Download Participants Bank Verification Status Report';
                    report.reportName = 'Bank Verification Status';
                    report.reportState = 2;
                    report.reportModule = 'activity';
                    break;
                case 'unCapturedParticipant':
                    report.reportTitle = 'Filter to a <b>Specific Training</b> to Download Uncaptured Participants Report';
                    report.reportName = 'Uncaptured Participants';
                    report.reportState = 3;
                    report.reportModule = 'activity';
                    break;
                case 'mobilizationPerLGA':
                    report.reportTitle = 'Filter to a <b>Specific Training</b> to Download Uncaptured Participants Report';
                    report.reportName = 'Mobilization Per LGA';
                    report.reportState = 4;
                    report.reportModule = 'mobilization';
                    downloadReport();
                    break;
                case 'mobilizationPerDP':
                    report.reportTitle = 'Filter to a <b>Specific Training</b> to Download Uncaptured Participants Report';
                    report.reportName = 'Mobilization Per DP';
                    report.reportState = 5;
                    report.reportModule = 'mobilization';
                    downloadReport();
                    break;
                case 'mobilizationPerLGADated':
                    report.reportTitle = 'Choose <b>a Date</b> to Download the Mobilization Report Per LGA';
                    report.reportName = 'Mobilization Per LGA in a specified Date';
                    report.reportState = 6;
                    report.reportModule = 'mobilization';
                    chooseSingleDate();
                    break;
                case 'mobilizationPerDPDated':
                    report.reportTitle = 'Choose <b>a Date</b> to Download the Mobilization Report Per DP';
                    report.reportName = 'Mobilization Per DP in a specified Date';
                    report.reportState = 7;
                    report.reportModule = 'mobilization';
                    chooseSingleDate();
                    break;
                case 'mobilizationPerLGADateRange':
                    report.reportTitle = 'Choose <b>a Date Range</b> to Download the Mobilization Report Per LGA';
                    report.reportName = 'Mobilization Per DP Report with Date Range';
                    report.reportState = 8;
                    report.reportModule = 'mobilization';
                    chooseDateRange();
                    break;
                case 'distributionPerLGA':
                    report.reportTitle = '';
                    report.reportName = 'Distribution Per LGA Report';
                    report.reportState = 9;
                    report.reportModule = 'distribution';
                    downloadReport();
                    break;
                case 'distributionPerDP':
                    report.reportTitle = 'Choose <b>a Date</b> to Download the Distribution Report Per LGA';
                    report.reportName = 'Distribution Per DP Report';
                    report.reportState = 10;
                    report.reportModule = 'distribution';
                    downloadReport();
                    break;
                case 'distributionPerLGADated':
                    report.reportTitle = 'Choose <b>a Date</b> to Download the Distribution Report Per LGA';
                    report.reportName = 'Distribution Per LGA for a specified Date';
                    report.reportState = 11;
                    report.reportModule = 'distribution';
                    chooseSingleDate();
                    break;
                case 'distributionPerLGADateRange':
                    report.reportTitle = 'Choose <b>a Date Range</b> to Download the Distribution Report Per LGA';
                    report.reportName = 'Distribution Per LGA Report with Date Range';
                    report.reportState = 12;
                    report.reportModule = 'distribution';
                    chooseDateRange();
                    break;
                case 'distributionPerDPDated':
                    report.reportTitle = 'Choose <b>a Date</b> to Download the Distribution Report Per DP';
                    report.reportName = 'Distribution Per DP for a specified Date';
                    report.reportState = 13;
                    report.reportModule = 'distribution';
                    chooseSingleDate();
                    break;
                case 'distributionPerDPDateRange':
                    report.reportTitle = 'Choose <b>a Date Range</b> to Download the Distribution Report Per DP';
                    report.reportName = 'Distribution Per DP Report with Date Range';
                    report.reportState = 14;
                    report.reportModule = 'distribution';
                    chooseDateRange();
                    break;
                default:
                    report.reportTitle = 'End_Process_Forms_';
            }
        }

        async function downloadReport() {
            var randomInt = Math.floor(Math.random() * 100) + 1;
            var fileName, dlString;
            var geoString = '&gl=' + geoIndicator.geoLevel + '&glid=' + geoIndicator.geoLevelId;

            if (trainingForm.trainingId == 0 && report.reportModule == 'activity') {
                alert.Error('Error', 'Kindly Select a Training to Download from');
                return;
            }
            if (String(report.reportDate).indexOf('to') !== -1) {
                var dates = report.reportDate.split(' to ');
                report.startDate = dates[0].replace(/\s/g, '');
                report.endDate = dates[1].replace(/\s/g, '');
            } else {
                report.startDate = report.endDate = report.reportDate;
            }

            switch (report.reportState) {
                case 1: fileName = report.reportName + '_Report_' + randomInt; dlString = 'qid=401' + geoString + '&tid=' + trainingForm.trainingId; break;
                case 2: fileName = report.reportName + '_Report_' + randomInt; dlString = 'qid=402' + geoString + '&tid=' + trainingForm.trainingId; break;
                case 3: fileName = report.reportName + '_Report_' + randomInt; dlString = 'qid=403' + geoString + '&tid=' + trainingForm.trainingId; break;
                case 4: fileName = report.reportName + '_Report_' + randomInt; dlString = 'qid=501' + geoString; break;
                case 5: fileName = report.reportName + '_Report_' + randomInt; dlString = 'qid=502' + geoString; break;
                case 6: fileName = report.reportName + '_Report_' + randomInt; dlString = 'qid=503' + geoString + '&date=' + report.reportDate; break;
                case 7: fileName = report.reportName + '_Report_' + randomInt; dlString = 'qid=504' + geoString + '&date=' + report.reportDate; break;
                case 8: fileName = report.reportName + '_Report_' + randomInt; dlString = 'qid=505' + geoString + '&startDate=' + report.startDate + '&endDate=' + report.endDate; break;
                case 9: fileName = report.reportName + '_Report_' + randomInt; dlString = 'qid=601' + geoString + '&date=' + report.reportDate; break;
                case 10: fileName = report.reportName + '_Report_' + randomInt; dlString = 'qid=602' + geoString + '&date=' + report.reportDate; break;
                case 11: fileName = report.reportName + '_Report_' + randomInt; dlString = 'qid=603' + geoString + '&date=' + report.reportDate; break;
                case 12: fileName = report.reportName + '_Report_' + randomInt; dlString = 'qid=604' + geoString + '&startDate=' + report.startDate + '&endDate=' + report.endDate; break;
                case 13: fileName = report.reportName + '_Report_' + randomInt; dlString = 'qid=605' + geoString + '&date=' + report.reportDate; break;
                case 14: fileName = report.reportName + '_Report_' + randomInt; dlString = 'qid=606' + geoString + '&startDate=' + report.startDate + '&endDate=' + report.endDate; break;
                default: fileName = 'Other_Report_' + randomInt; dlString = 'qid=705';
            }

            overlay.show();
            alert.Info('DOWNLOADING...', 'Downloading record(s)');
            try {
                var outcome = await downloadData(dlString);
                var exportData = JSON.parse(outcome);
                if (window.Jhxlsx && typeof window.Jhxlsx.export === 'function') {
                    window.Jhxlsx.export(exportData, { fileName: fileName });
                }
                overlay.hide();
                dismissOnClick();
            } catch (error) {
                console.error(error);
                alert.Error('Download Error', safeMessage(error));
                overlay.hide();
            }
        }

        function downloadData(dlString) {
            return new Promise(function (resolve, reject) {
                $.ajax({
                    url: common.ExportService, type: 'POST', data: dlString,
                    success: function (data) { resolve(data); },
                    error: function (error) { reject(error); },
                });
            });
        }

        function dismissOnClick() {
            trainingForm.trainingId = 0;
            trainingForm.trainingName = '';
            report.reportTitle = '';
            report.reportName = '';
            report.reportModule = '';
            dateTitle.value = '';
            report.reportDate = '';
            report.startDate = '';
            report.endDate = '';
            report.reportState = 0;
            $('#trainingListModal').modal('hide');
        }

        function chooseSingleDate() {
            dateTitle.value = 'Choose Date to download the Report';
            try { $('.date').flatpickr({ altInput: true, altFormat: 'F j, Y', dateFormat: 'Y-m-d' }); } catch (e) {}
        }
        function chooseDateRange() {
            dateTitle.value = 'Choose Date Range to Download the Report';
            try { $('.date').flatpickr({ altInput: true, altFormat: 'F j, Y', dateFormat: 'Y-m-d', mode: 'range' }); } catch (e) {}
        }
        function autoUpdateTableRowNo() {
            var allTableRow = document.querySelectorAll('tr td:first-child');
            allTableRow.forEach(function (element, i) { element.innerHTML = i + 1; });
        }

        onBeforeMount(function () {
            geoIndicator.geoLevel = $('#v_g_geo_level').val();
            geoIndicator.geoLevelId = $('#v_g_geo_level_id').val();
        });
        onMounted(function () {
            $('#form-search').on('keyup', function () {
                var value = $(this).val().toLowerCase();
                $('#dpTable tbody tr').filter(function () {
                    $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
                });
            });
            getAllTrainingLists();
            autoUpdateTableRowNo();
        });

        return {
            geoIndicator, permission, checkToggle, tableData, searchReport,
            dateTitle, report, trainingListData, trainingForm,
            getAllTrainingLists, openModal, downloadReport, dismissOnClick,
            chooseSingleDate, chooseDateRange, autoUpdateTableRowNo,
            capitalize: fmtUtils.capitalize,
        };
    },
    template: `
        <div>
            <div class="row">
                <div class="col-md-12 col-sm-12 col-12 mb-2">
                    <h2 class="content-header-title header-txt float-left mb-0">Reports</h2>
                    <div class="breadcrumb-wrapper">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="./">Home</a></li>
                            <li class="breadcrumb-item active">Report List</li>
                        </ol>
                    </div>
                </div>

                <div class="col-12">
                    <div class="alert bg-light-primary pt-1 pb-1">
                        <div class="col-12">
                            <div class="row">
                                <div class="col-12">
                                    <div class="input-group date_filter">
                                        <input type="text" id="form-search" v-model="searchReport" class="form-control date" placeholder="Search using Report Name" />
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
                            <th width="60px" class="text-center"></th>
                        </thead>
                        <tbody>
                            <tr v-if="permission.permission_value >= 1">
                                <td>1</td>
                                <td>Activity Participant List</td>
                                <td><span class="badge badge-light-success">Activity</span></td>
                                <td class="text-center"><a href="javascript:void(0)" @click="openModal('participantList')" class="btn btn-sm btn-primary btn-sm p-25" data-backdrop="static" data-keyboard="false" data-toggle="modal" data-target="#trainingListModal"><i class="feather icon-download"></i></a></td>
                            </tr>
                            <tr v-if="permission.permission_value >= 1">
                                <td>2</td>
                                <td>Bank verification status Report</td>
                                <td><span class="badge badge-light-success">Activity</span></td>
                                <td class="text-center"><a href="javascript:void(0)" @click="openModal('bankVerificationStatus')" class="btn btn-sm btn-primary btn-sm p-25" data-backdrop="static" data-keyboard="false" data-toggle="modal" data-target="#trainingListModal"><i class="feather icon-download"></i></a></td>
                            </tr>
                            <tr v-if="permission.permission_value >= 1">
                                <td>3</td>
                                <td>Uncaptured Participants Report</td>
                                <td><span class="badge badge-light-success">Activity</span></td>
                                <td class="text-center"><a href="javascript:void(0)" @click="openModal('unCapturedParticipant')" class="btn btn-sm btn-primary btn-sm p-25" data-backdrop="static" data-keyboard="false" data-toggle="modal" data-target="#trainingListModal"><i class="feather icon-download"></i></a></td>
                            </tr>
                            <tr v-if="permission.permission_value >= 2">
                                <td>4</td>
                                <td>Aggregate Mobilization Per LGA Report</td>
                                <td><span class="badge badge-light-success">Mobilization</span></td>
                                <td class="text-center"><a href="javascript:void(0)" @click="openModal('mobilizationPerLGA')" class="btn btn-sm btn-primary btn-sm p-25"><i class="feather icon-download"></i></a></td>
                            </tr>
                            <tr v-if="permission.permission_value >= 2">
                                <td>5</td>
                                <td>Aggregate Mobilization Per DP Report</td>
                                <td><span class="badge badge-light-success">Mobilization</span></td>
                                <td class="text-center"><a href="javascript:void(0)" @click="openModal('mobilizationPerDP')" class="btn btn-sm btn-primary btn-sm p-25"><i class="feather icon-download"></i></a></td>
                            </tr>
                            <tr v-if="permission.permission_value >= 1">
                                <td>6</td>
                                <td>Mobilization by LGA in a Dated Report</td>
                                <td><span class="badge badge-light-success">Mobilization</span></td>
                                <td class="text-center"><a href="javascript:void(0)" @click="openModal('mobilizationPerLGADated')" class="btn btn-sm btn-primary btn-sm p-25" data-backdrop="static" data-keyboard="false" data-toggle="modal" data-target="#trainingListModal"><i class="feather icon-download"></i></a></td>
                            </tr>
                            <tr v-if="permission.permission_value >= 1">
                                <td>7</td>
                                <td>Mobilization by DP in a Dated Report</td>
                                <td><span class="badge badge-light-success">Mobilization</span></td>
                                <td class="text-center"><a href="javascript:void(0)" @click="openModal('mobilizationPerDPDated')" class="btn btn-sm btn-primary btn-sm p-25" data-backdrop="static" data-keyboard="false" data-toggle="modal" data-target="#trainingListModal"><i class="feather icon-download"></i></a></td>
                            </tr>
                            <tr v-if="permission.permission_value == 3">
                                <td>8</td>
                                <td>Mobilization by LGA Report using Date Range</td>
                                <td><span class="badge badge-light-success">Mobilization</span></td>
                                <td class="text-center"><a href="javascript:void(0)" @click="openModal('mobilizationPerLGADateRange')" class="btn btn-sm btn-primary btn-sm p-25" data-backdrop="static" data-keyboard="false" data-toggle="modal" data-target="#trainingListModal"><i class="feather icon-download"></i></a></td>
                            </tr>
                            <tr v-if="permission.permission_value == 3">
                                <td>9</td>
                                <td>Aggregate Distribution Report per LGA</td>
                                <td><span class="badge badge-light-success">Distribution</span></td>
                                <td class="text-center"><a href="javascript:void(0)" @click="openModal('distributionPerLGA')" class="btn btn-sm btn-primary btn-sm p-25"><i class="feather icon-download"></i></a></td>
                            </tr>
                            <tr v-if="permission.permission_value == 3">
                                <td>10</td>
                                <td>Aggregate Distribution Per DP Report</td>
                                <td><span class="badge badge-light-success">Distribution</span></td>
                                <td class="text-center"><a href="javascript:void(0)" @click="openModal('distributionPerDP')" class="btn btn-sm btn-primary btn-sm p-25"><i class="feather icon-download"></i></a></td>
                            </tr>
                            <tr v-if="permission.permission_value >= 1">
                                <td>11</td>
                                <td>Distribution by DP in a Dated Report</td>
                                <td><span class="badge badge-light-success">Distribution</span></td>
                                <td class="text-center"><a href="javascript:void(0)" @click="openModal('distributionPerLGADated')" class="btn btn-sm btn-primary btn-sm p-25" data-backdrop="static" data-keyboard="false" data-toggle="modal" data-target="#trainingListModal"><i class="feather icon-download"></i></a></td>
                            </tr>
                            <tr v-if="permission.permission_value == 3">
                                <td>12</td>
                                <td>Distribution by LGA Report using Date Range</td>
                                <td><span class="badge badge-light-success">Distribution</span></td>
                                <td class="text-center"><a href="javascript:void(0)" @click="openModal('distributionPerLGADateRange')" class="btn btn-sm btn-primary btn-sm p-25" data-backdrop="static" data-keyboard="false" data-toggle="modal" data-target="#trainingListModal"><i class="feather icon-download"></i></a></td>
                            </tr>
                            <tr v-if="permission.permission_value >= 1">
                                <td>13</td>
                                <td>Distribution by DP Report using a Specified Date or Day</td>
                                <td><span class="badge badge-light-success">Distribution</span></td>
                                <td class="text-center"><a href="javascript:void(0)" @click="openModal('distributionPerDPDated')" class="btn btn-sm btn-primary btn-sm p-25" data-backdrop="static" data-keyboard="false" data-toggle="modal" data-target="#trainingListModal"><i class="feather icon-download"></i></a></td>
                            </tr>
                            <tr v-if="permission.permission_value == 3">
                                <td>14</td>
                                <td>Distribution by DP Report using Date Range</td>
                                <td><span class="badge badge-light-success">Distribution</span></td>
                                <td class="text-center"><a href="javascript:void(0)" @click="openModal('distributionPerDPDateRange')" class="btn btn-sm btn-primary btn-sm p-25" data-backdrop="static" data-keyboard="false" data-toggle="modal" data-target="#trainingListModal"><i class="feather icon-download"></i></a></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

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
                                <div v-if="report.reportModule == 'activity'">
                                    <label>Select a Training:</label>
                                    <div class="form-group">
                                        <select name="role" v-model="trainingForm.trainingId" class="form-control role">
                                            <option value="0">Select a Training</option>
                                            <option v-for="t in trainingListData" :value="t.trainingid" :key="t.trainingid">{{ t.title }}</option>
                                        </select>
                                    </div>
                                </div>
                                <div v-show="report.reportModule == 'mobilization' || report.reportModule == 'distribution'">
                                    <div class="form-group">
                                        <label class="form-label full" for="mob-date">{{ dateTitle }}</label>
                                        <input id="date" v-model="report.reportDate" type="date" placeholder="Mobilization Date Range" class="form-control date mob-date" name="mob_date" />
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
        </div>
    `,
};

useApp({ template: `<div><page-body/></div>` })
    .component('page-body', PageBody)
    .component('reporting_lists', ReportingLists)
    .mount('#app');
