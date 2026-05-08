/**
 * Device / Registry submodule — Vue 3 Composition API in place.
 * Device list (qid=601), bulk de/activate (qid=502), delete (qid=503),
 * update modal (qid=504), QR scanner via ZXing for app/imei/sim serials,
 * details modal.
 */

const { ref, reactive, onMounted } = Vue;
const { useApp, useFormat, safeMessage } = window.utils;

/* ------------------------------------------------------------------ */
const PageBody = {
    setup() {
        const page = ref('home');
        return { page };
    },
    template: `
        <div>
            <div class="content-body">
                <sample_table/>
            </div>
        </div>
    `,
};

/* ------------------------------------------------------------------ */
const SampleTable = {
    setup() {
        const fmtUtils = useFormat();

        const tableData = ref([]);
        const filterState = ref(false);
        const filters = ref(false);
        const checkToggle = ref(false);
        const userGroup = ref([]);
        const permission = ref(
            (typeof getPermission === 'function')
                ? (getPermission(typeof per !== 'undefined' ? per : null, 'device') || { permission_value: 0 })
                : { permission_value: 0 }
        );
        const tableOptions = reactive({
            total: 1,
            pageLength: 1,
            perPage: 10,
            currentPage: 1,
            orderDir: 'desc',
            orderField: 0,
            limitStart: 0,
            isNext: false,
            isPrev: false,
            aLength: [10, 20, 50, 100],
            filterParam: { status: '', serial_no: '' },
        });

        const currentField = ref('');
        const appSerialState = ref(false);
        const deviceDetailsForm = reactive({
            appSerial: '',
            imeiOne: '',
            imeiTwo: '',
            deviceSerial: '',
            networkType: 'MTN',
            simCardSerialNo: '',
        });
        const showCamera = ref(false);
        const deviceDetails = ref({});

        function loadTableData() {
            overlay.show();
            var url = common.TableService;
            axios.get(
                url +
                '?qid=601&draw=' + tableOptions.currentPage +
                '&order_column=' + tableOptions.orderField +
                '&length=' + tableOptions.perPage +
                '&start=' + tableOptions.limitStart +
                '&order_dir=' + tableOptions.orderDir +
                '&act=' + tableOptions.filterParam.status +
                '&sno=' + tableOptions.filterParam.serial_no
            )
                .then(function (response) {
                    var d = response && response.data;
                    tableData.value = Array.isArray(d && d.data) ? d.data : [];
                    tableOptions.total = (d && d.recordsTotal) || 0;
                    if (tableOptions.currentPage == 1) paginationDefault();
                    overlay.hide();
                })
                .catch(function (error) {
                    overlay.hide();
                    alert.Error('ERROR', safeMessage(error));
                });
        }

        function selectAll() {
            for (var i = 0; i < tableData.value.length; i++) tableData.value[i].pick = true;
        }
        function uncheckAll() {
            for (var i = 0; i < tableData.value.length; i++) tableData.value[i].pick = false;
        }
        function selectToggle() {
            if (checkToggle.value === false) { selectAll(); checkToggle.value = true; }
            else                              { uncheckAll(); checkToggle.value = false; }
        }
        function checkedBg(pickOne) { return pickOne != '' ? 'bg-select' : ''; }

        function toggleFilter() {
            if (filterState.value === false) filters.value = false;
            return (filterState.value = !filterState.value);
        }

        function selectedItems() {
            return tableData.value.filter(function (r) { return r.pick; });
        }
        function selectedID() {
            return tableData.value.filter(function (r) { return r.pick; }).map(function (r) { return r.serial_no; });
        }

        function paginationDefault() {
            tableOptions.pageLength = Math.ceil(tableOptions.total / tableOptions.perPage);
            tableOptions.limitStart = Math.ceil((tableOptions.currentPage - 1) * tableOptions.perPage);
            tableOptions.isNext = tableOptions.currentPage < tableOptions.pageLength;
            tableOptions.isPrev = tableOptions.currentPage > 1;
        }

        function nextPage() { tableOptions.currentPage += 1; paginationDefault(); loadTableData(); }
        function prevPage() { tableOptions.currentPage -= 1; paginationDefault(); loadTableData(); }
        function currentPage() {
            paginationDefault();
            if (tableOptions.currentPage < 1)                            alert.Error('ERROR', "The Page requested doesn't exist");
            else if (tableOptions.currentPage > tableOptions.pageLength) alert.Error('ERROR', "The Page requested doesn't exist");
            else                                                         loadTableData();
        }
        function changePerPage(val) {
            var maxPerPage = Math.ceil(tableOptions.total / val);
            if (maxPerPage < tableOptions.currentPage) tableOptions.currentPage = maxPerPage;
            tableOptions.perPage = val;
            paginationDefault();
            loadTableData();
        }
        function sort(col) {
            if (tableOptions.orderField === col) {
                tableOptions.orderDir = tableOptions.orderDir === 'asc' ? 'desc' : 'asc';
            } else {
                tableOptions.orderField = col;
            }
            paginationDefault();
            loadTableData();
        }
        function applyFilter() {
            var checkFill = 0;
            checkFill += tableOptions.filterParam.status    != '' ? 1 : 0;
            checkFill += tableOptions.filterParam.serial_no != '' ? 1 : 0;
            if (checkFill > 0) {
                toggleFilter();
                filters.value = true;
                paginationDefault();
                loadTableData();
            } else {
                alert.Error('ERROR', 'Invalid required data');
            }
        }
        function removeSingleFilter(column_name) {
            tableOptions.filterParam[column_name] = '';
            var g = 0;
            for (var k in tableOptions.filterParam) {
                if (tableOptions.filterParam[k] != '') g++;
            }
            if (g == 0) filters.value = false;
            paginationDefault();
            loadTableData();
        }
        function clearAllFilter() {
            filters.value = false;
            tableOptions.filterParam.status = '';
            tableOptions.filterParam.serial_no = '';
            paginationDefault();
            loadTableData();
        }
        function refreshData() { paginationDefault(); loadTableData(); }

        function deviceActivationDeactivation(serial_no, active_status) {
            var url = common.DataService;
            var message, btn_text, btn_class, response_txt;
            if (active_status == '1') {
                message = 'Are you sure you want to Deactivate the Device with Serial No <b>' + serial_no + '</b>? <br><br>Make sure you are sure that you want to deactivate the device.';
                btn_text = 'Deactivate';
                btn_class = ' btn-danger ';
                response_txt = 'Device with Serial No <b>' + serial_no + '</b> Successfully Deactivated';
            } else {
                message = 'Are you sure you want to Activate the Training with Serial No <b>' + serial_no + '</b>? <br><br>Make sure you are sure that you want to activate the device.';
                btn_text = 'Activate';
                btn_class = ' btn-success ';
                response_txt = 'Device with Serial No <b>' + serial_no + '</b> Successfully Activated';
            }
            $.confirm({
                title: 'WARNING!',
                content: message,
                buttons: {
                    delete: {
                        text: btn_text,
                        btnClass: 'btn mr-1' + btn_class,
                        action: function () {
                            axios.post(url + '?qid=501&sn=' + serial_no)
                                .then(function (response) {
                                    if (response.data.result_code == '200') {
                                        loadTableData();
                                        alert.Success('SUCCESS', response_txt);
                                    } else {
                                        alert.Error('ERROR', "Unable to De/Activate Device with Serial No <b>" + serial_no + "</b> at the moment please try again later");
                                    }
                                })
                                .catch(function (error) {
                                    alert.Error('ERROR', safeMessage(error));
                                });
                        },
                    },
                    cancel: function () {},
                },
            });
        }

        function bulkDeActivateDevice() {
            var ids = selectedID();
            if (ids.length < 1) {
                alert.Error('ERROR', 'No Device selected');
                return;
            }
            var url = common.DataService;
            $.confirm({
                title: 'WARNING!',
                content: 'Are you sure you want to De/Activate <b>' + ids.length + '</b> Devices? <br><br>Make sure you know what you are doing before you De/Activate the Devices',
                buttons: {
                    delete: {
                        text: 'De/Activate',
                        btnClass: 'btn btn-danger mr-1',
                        action: function () {
                            axios.post(url + '?qid=502', JSON.stringify(ids))
                                .then(function (response) {
                                    overlay.hide();
                                    if (response.data.result_code == '200') {
                                        loadTableData();
                                        alert.Success('SUCCESS', response.data.total + ' Devices De/activated');
                                    } else {
                                        alert.Error('ERROR', 'Device De/Activation failed');
                                    }
                                })
                                .catch(function (error) {
                                    overlay.hide();
                                    alert.Error('ERROR', safeMessage(error));
                                });
                        },
                    },
                    cancel: function () { overlay.hide(); },
                },
            });
        }

        function deleteDevice(serial_no, state) {
            var url = common.DataService;
            var message;
            var selected_data;
            if (state == 'all') {
                var ids = selectedID();
                if (ids.length < 1) {
                    alert.Error('ERROR', 'No Device selected');
                    return;
                }
                selected_data = JSON.stringify(ids);
                message = 'Are you sure you want to Delete <b>' + ids.length + '</b> Devices? <br><br>Make sure you know what you are doing before you De/Activate the Devices';
            } else {
                selected_data = JSON.stringify([serial_no]);
                message = 'Are you sure you want to Delete device with Serial No: <b>' + serial_no + '</b>? <br><br>Make sure you know what you are doing before you De/Activate the Devices';
            }
            $.confirm({
                title: 'WARNING!',
                content: message,
                buttons: {
                    delete: {
                        text: 'Delete',
                        btnClass: 'btn btn-danger mr-1',
                        action: function () {
                            axios.post(url + '?qid=503', selected_data)
                                .then(function (response) {
                                    overlay.hide();
                                    if (response.data.result_code == '200') {
                                        loadTableData();
                                        alert.Success('SUCCESS', response.data.total + ' Devices Removed');
                                    } else {
                                        alert.Error('ERROR', 'Device Removal failed');
                                    }
                                })
                                .catch(function (error) {
                                    overlay.hide();
                                    alert.Error('ERROR', safeMessage(error));
                                });
                        },
                    },
                    cancel: function () { overlay.hide(); },
                },
            });
        }

        function showUpdateDeviceModal(update_state, index) {
            resetForm();
            if (update_state === 'bulk') {
                appSerialState.value = true;
            } else {
                appSerialState.value = false;
                var row = tableData.value[index] || {};
                deviceDetailsForm.imeiOne = row.imei1;
                deviceDetailsForm.imeiTwo = row.imei2;
                deviceDetailsForm.deviceSerial = row.phone_serial;
                deviceDetailsForm.simCardSerialNo = row.sim_serial;
                deviceDetailsForm.networkType = row.sim_network;
                deviceDetailsForm.appSerial = update_state;
            }
            $('#updateDeviceDetails').modal({ backdrop: 'static', keyboard: false });
        }
        function hideUpdateDeviceModal() {
            resetForm();
            $('#updateDeviceDetails').modal('hide');
        }
        function resetForm() {
            deviceDetailsForm.appSerial = '';
            deviceDetailsForm.imeiOne = '';
            deviceDetailsForm.imeiTwo = '';
            deviceDetailsForm.deviceSerial = '';
            deviceDetailsForm.simCardSerialNo = '';
            overlay.hide();
        }
        function updateDeviceDetails() {
            var url = common.DataService;
            overlay.show();
            axios.post(url + '?qid=504', JSON.stringify(deviceDetailsForm))
                .then(function (response) {
                    if (response.data.result_code == '200') {
                        hideUpdateDeviceModal();
                        alert.Success('Success', response.data.data);
                        loadTableData();
                        overlay.hide();
                    } else {
                        overlay.hide();
                        alert.Error('Error', response.data.data);
                    }
                })
                .catch(function (error) {
                    alert.Error('ERROR', safeMessage(error));
                    overlay.hide();
                });
        }

        function startCamera(field_name, action) {
            showCamera.value = true;
            currentField.value = field_name;
            if (typeof ZXing === 'undefined') {
                alert.Error('ERROR', 'QR scanner library (ZXing) is not loaded');
                showCamera.value = false;
                return;
            }
            var codeReader = new ZXing.BrowserMultiFormatReader();
            if (action === 'stop') {
                codeReader.reset();
                var video = document.querySelector('video');
                if (video && video.srcObject) {
                    var tracks = video.srcObject.getTracks();
                    if (tracks[0]) tracks[0].stop();
                    tracks.forEach(function (t) { t.stop(); });
                }
                showCamera.value = false;
            } else {
                codeReader.decodeFromVideoDevice(null, 'webcam-preview', function (result, err) {
                    var current = currentField.value;
                    if (result) {
                        deviceDetailsForm[current] = (field_name === 'appSerial') ? result.text.split('|')[0].trim() : result.text;
                        codeReader.reset();
                        showCamera.value = false;
                    }
                    if (err) {
                        if (err instanceof ZXing.NotFoundException)   console.log('No QR code found.');
                        if (err instanceof ZXing.ChecksumException)   console.log("A code was found, but it's read value was not valid.");
                        if (err instanceof ZXing.FormatException)     console.log('A code was found, but it was in an invalid format.');
                    }
                });
            }
        }

        function showDeviceDetailsModal(i) {
            deviceDetails.value = tableData.value[i] || {};
            $('#deviceDetails').modal('show');
        }
        function hideDeviceDetailsModal() {
            overlay.show();
            $('#deviceDetails').modal('hide');
            overlay.hide();
        }

        function checkIfEmpty(data) {
            return data === null || data === '' || data === undefined ? 'Nil' : data;
        }
        function displayDate(d) {
            var date = new Date(d);
            return date.toLocaleString('en-us', {
                year: 'numeric', month: 'long', day: 'numeric',
                hour12: true, hour: '2-digit', minute: '2-digit', second: '2-digit',
            });
        }

        onMounted(function () {
            loadTableData();
        });

        return {
            // state
            tableData, filterState, filters, checkToggle, userGroup, permission,
            tableOptions, currentField, appSerialState, deviceDetailsForm,
            showCamera, deviceDetails,
            // methods
            loadTableData, selectAll, uncheckAll, selectToggle, checkedBg,
            toggleFilter, selectedItems, selectedID,
            nextPage, prevPage, currentPage, paginationDefault, changePerPage,
            sort, applyFilter, removeSingleFilter, clearAllFilter, refreshData,
            deviceActivationDeactivation, bulkDeActivateDevice, deleteDevice,
            showUpdateDeviceModal, hideUpdateDeviceModal, resetForm, updateDeviceDetails,
            startCamera, showDeviceDetailsModal, hideDeviceDetailsModal,
            checkIfEmpty, displayDate,
            // utility methods
            capitalize: fmtUtils.capitalize,
            formatNumber: fmtUtils.formatNumber,
        };
    },
    template: `
        <div class="row" id="basic-table">

            <div class="col-md-8 col-sm-12 col-12 mb-0">
                <h2 class="content-header-title header-txt float-left mb-0">Device</h2>
                <div class="breadcrumb-wrapper">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../device">Home</a></li>
                        <li class="breadcrumb-item active">Device Registry</li>
                    </ol>
                </div>
            </div>

            <div class="col-md-4 col-sm-12 col-12 text-md-right text-right d-md-block">
                <div class="btn-group mr-1">
                    <button v-if="permission.permission_value >= 2" type="button" @click="showUpdateDeviceModal('bulk', '')" data-target="#updateDeviceDetails" data-toggle="tooltip" data-placement="top" title="Update Device Details" class="btn btn-outline-primary round"><i data-feather='plus'></i></button>
                    <button type="button" class="btn btn-outline-primary round searchBtn" @click="refreshData()" data-toggle="tooltip" data-placement="top" title="Refresh">
                        <i class="feather icon-refresh-cw"></i>
                    </button>
                    <button type="button" class="btn btn-outline-primary round searchBtn" @click="toggleFilter()" data-toggle="tooltip" data-placement="top" title="Filter">
                        <i class="feather" :class="filterState ? 'icon-x' : 'icon-filter'"></i>
                    </button>
                    <button class="btn btn-outline-primary round dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        Actions
                    </button>
                    <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                        <a v-if="permission.permission_value >=2" class="dropdown-item" href="javascript:void(0);" @click="bulkDeActivateDevice()">De/Activate Devices</a>
                        <a v-if="permission.permission_value ==3" class="dropdown-item" href="javascript:void(0);" @click="deleteDevice('', 'all')">Remove Devices</a>
                    </div>
                </div>
            </div>

            <div class="col-12" v-if="filters">
                <div class="col-12 filter-bar">
                    <template v-for="(filterParam, i) in tableOptions.filterParam" :key="i">
                        <span class="badge badge-dark filter-box" v-if="String(filterParam).length > 0" @click="removeSingleFilter(i)">
                            {{ capitalize(i) }}: {{ filterParam }} <i class="feather icon-x"></i>
                        </span>
                    </template>
                    <a href="#" class="float-right clear-filter" @click="clearAllFilter()">Clear</a>
                </div>
            </div>

            <div class="col-12 mt-1" v-if="permission.permission_value >=1">
                <div class="card">
                    <div class="card-body py-1" v-show="filterState">
                        <form id="filterForm">
                            <div class="row">
                                <div class="col-12 col-sm-6 col-md-4 col-lg-4">
                                    <div class="form-group">
                                        <label>Device Status</label>
                                        <select v-model="tableOptions.filterParam.status" class="form-control active">
                                            <option value="">All</option>
                                            <option value="1">Active</option>
                                            <option value="0">Inactive</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-12 col-sm-6 col-md-4 col-lg-4">
                                    <div class="form-group">
                                        <label>App Device Serial No.</label>
                                        <input type="text" v-model="tableOptions.filterParam.serial_no" class="form-control serial_no" id="serial_no" placeholder="Serial Number" name="serial_no" />
                                    </div>
                                </div>
                                <div class="col-12 col-sm-6 col-md-4 col-lg-4">
                                    <div class="form-group mt-2 text-right">
                                        <button type="button" class="btn mt-25 btn-md btn-primary" @click="applyFilter()">Apply Filters</button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th width="60px" style="padding-right: 2px !important;">
                                        <div class="custom-control custom-checkbox checkbox">
                                            <input type="checkbox" class="custom-control-input" @change="selectToggle()" id="all-check" />
                                            <label class="custom-control-label" for="all-check"></label>
                                        </div>
                                    </th>
                                    <th @click="sort(1)">Device ID
                                        <i class="feather icon-chevron-up sort-up"   :class="(tableOptions.orderField == 1 && tableOptions.orderDir =='asc' )? 'active-sort':''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 1 && tableOptions.orderDir =='desc')? 'active-sort':''"></i>
                                    </th>
                                    <th @click="sort(2)">Device Description
                                        <i class="feather icon-chevron-up sort-up"   :class="(tableOptions.orderField == 2 && tableOptions.orderDir =='asc' )? 'active-sort':''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 2 && tableOptions.orderDir =='desc')? 'active-sort':''"></i>
                                    </th>
                                    <th @click="sort(4)">App Serial No
                                        <i class="feather icon-chevron-up sort-up"   :class="(tableOptions.orderField == 4 && tableOptions.orderDir =='asc' )? 'active-sort':''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 4 && tableOptions.orderDir =='desc')? 'active-sort':''"></i>
                                    </th>
                                    <th @click="sort(4)">IME1</th>
                                    <th @click="sort(4)">IME2</th>
                                    <th @click="sort(6)">Status
                                        <i class="feather icon-chevron-up sort-up"   :class="(tableOptions.orderField == 6 && tableOptions.orderDir =='asc' )? 'active-sort':''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 6 && tableOptions.orderDir =='desc')? 'active-sort':''"></i>
                                    </th>
                                    <th @click="sort(7)">Last Time Connected
                                        <i class="feather icon-chevron-up sort-up"   :class="(tableOptions.orderField == 7 && tableOptions.orderDir =='asc' )? 'active-sort':''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 7 && tableOptions.orderDir =='desc')? 'active-sort':''"></i>
                                    </th>
                                    <th @click="sort(8)">Created
                                        <i class="feather icon-chevron-up sort-up"   :class="(tableOptions.orderField == 8 && tableOptions.orderDir =='asc' )? 'active-sort':''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 8 && tableOptions.orderDir =='desc')? 'active-sort':''"></i>
                                    </th>
                                    <th @click="sort(9)">Updated
                                        <i class="feather icon-chevron-up sort-up"   :class="(tableOptions.orderField == 9 && tableOptions.orderDir =='asc' )? 'active-sort':''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 9 && tableOptions.orderDir =='desc')? 'active-sort':''"></i>
                                    </th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(g, i) in tableData" :key="g.serial_no || i" :class="checkedBg(g.pick)">
                                    <td style="padding-right: 2px !important;">
                                        <div class="custom-control custom-checkbox checkbox">
                                            <input type="checkbox" class="custom-control-input" :id="g.serial_no" v-model="g.pick" />
                                            <label class="custom-control-label" :for="g.serial_no"></label>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex justify-content-left align-items-center">
                                            <div class="d-flex flex-column">
                                                <span class="user_name text-truncate text-body">
                                                    <span class="fw-bolder">{{ g.device_id }}</span>
                                                </span>
                                                <small class="emp_post text-primary" v-html="g.phone_serial ? g.phone_serial : 'No Device Serial'"></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex justify-content-left align-items-center">
                                            <div class="d-flex flex-column">
                                                <span class="user_name text-truncate text-body">
                                                    <span class="fw-bolder" v-text="g.device_name ? capitalize(g.device_name) : 'Unknown Device'"></span>
                                                </span>
                                                <small class="emp_post text-primary" v-html="g.device_type ? g.device_type : ''"></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{ checkIfEmpty(g.serial_no) }}</td>
                                    <td>{{ checkIfEmpty(g.imei1) }}</td>
                                    <td>{{ checkIfEmpty(g.imei2) }}</td>
                                    <td><span class="badge rounded-pill font-small-1" :class="g.active=='1'? 'bg-success' : 'bg-danger'">{{ g.active=='1'? 'Active' : 'Inactive' }}</span></td>
                                    <td>{{ g.connected }}</td>
                                    <td>{{ g.created }}</td>
                                    <td>{{ g.updated }}</td>
                                    <td>
                                        <div class="dropdown">
                                            <button type="button" class="btn btn-sm dropdown-toggle hide-arrow" data-toggle="dropdown">
                                                <span class="feather icon-more-vertical"></span>
                                            </button>
                                            <div class="dropdown-menu">
                                                <a v-if="permission.permission_value ==3" class="dropdown-item" href="javascript:void(0);" @click="deviceActivationDeactivation(g.serial_no, g.active)"><i class="feather" :class="g.active == '1'? 'icon-x-circle' : 'icon-check-circle'"></i> {{ g.active == '1'? ' Deactivate ' : ' Activate ' }}</a>
                                                <a v-if="permission.permission_value ==3" class="dropdown-item" href="javascript:void(0);" @click="deleteDevice(g.serial_no, '')"><i class="feather icon-delete"></i> Remove</a>
                                                <a v-if="permission.permission_value >=2" class="dropdown-item" href="javascript:void(0);" @click="showUpdateDeviceModal(g.serial_no, i)"><i class="feather icon-arrow-up"></i> Edit Device</a>
                                                <a v-if="permission.permission_value >=1" class="dropdown-item" href="javascript:void(0);" @click="showDeviceDetailsModal(i)"><i class="feather icon-eye"></i> Details</a>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="tableData.length === 0"><td class="text-center pt-2" colspan="11"><small>No Device Registered</small></td></tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="card-footer">
                        <div class="content-fluid">
                            <div class="row">
                                <div class="col-12 col-xl-4 col-md-4 col-sm-5">
                                    <div class="dropdown sort-dropdown mb-1 mb-sm-0">
                                        <button class="btn filter-btn btn-primary dropdown-toggle border text-dark" type="button" id="tablePaginationDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                            {{ tableOptions.limitStart + 1 }} - {{ tableOptions.limitStart + tableData.length }} of {{ tableOptions.total }}
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-right" aria-labelledby="tablePaginationDropdown">
                                            <a @click="changePerPage(g)" v-for="g in tableOptions.aLength" :key="g" class="dropdown-item" href="javascript:void(0);">{{ g }}</a>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 col-xl-8 col-md-8 col-sm-7 text-right text-pag">
                                    <div class="btn-group">
                                        <button type="button" @click="prevPage()" class="btn btn-sm btn-primary round btn-page-block-overlay" :disabled="!tableOptions.isPrev">
                                            <i data-feather='chevron-left'></i> Prev
                                        </button>
                                        <input @keyup.enter="currentPage()" class="btn btn-page-block-overlay btn-sm btn-outline-primary pagination-input" type="number" v-model.number="tableOptions.currentPage" :max="tableOptions.pageLength" />
                                        <button class="btn btn-outline-primary btn-page-block-overlay border-l-0">
                                            <small class="form-text text-primary"> of {{ tableOptions.pageLength }}</small>
                                        </button>
                                        <button type="button" @click="nextPage()" class="btn btn-sm btn-primary round" :disabled="!tableOptions.isNext">
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

            <div class="col-md-12 col-sm-12 col-12" v-else>
                <h6 class="text-center text-info pt-4 pb-4">You don't have permission to view this page</h6>
            </div>

            <!-- Update Device Details Modal -->
            <div class="modal fade text-left" id="updateDeviceDetails" tabindex="-1" role="dialog" aria-labelledby="myModalLabel33" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h4 class="modal-title" id="myModalLabel33">Update Device Details</h4>
                            <button type="button" v-show="showCamera === false" @click="hideUpdateDeviceModal()" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                            <button type="button" v-show="showCamera === true" @click="startCamera('appSerial', 'stop')" class="close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <form method="POST" @submit.stop.prevent="updateDeviceDetails()">
                            <div class="modal-body" v-show="showCamera === true">
                                <video id="webcam-preview" style="width: 100% !important; height: auto !important; display: flex !important; position: relative !important;"></video>
                            </div>
                            <div class="modal-body" v-show="showCamera === false">
                                <div>
                                    <div v-if="appSerialState === true">
                                        <label>*Ipolongo/App Serial</label>
                                        <div class="input-group mb-1">
                                            <input type="text" required v-model="deviceDetailsForm.appSerial" class="form-control" aria-label="Ipolongo App Serial No." aria-describedby="basic-addon2" placeholder="App Serial No." />
                                            <div class="input-group-append" @click="startCamera('appSerial', '')">
                                                <button type="button" class="input-group-text"><i class="feather icon-camera"></i></button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mb-1" v-else>
                                        <div class="alert alert-warning py-50 px-1">*Ipolongo/App Serial: <span class="badge badge-primary">{{ deviceDetailsForm.appSerial }}</span></div>
                                        <input type="hidden" :disabled="true" required v-model="deviceDetailsForm.appSerial" class="form-control" aria-label="Ipolongo App Serial No." placeholder="App Serial No." />
                                    </div>
                                </div>
                                <div>
                                    <label>*IMEI 1</label>
                                    <div class="input-group mb-1">
                                        <input type="text" id="imei1" required v-model="deviceDetailsForm.imeiOne" class="form-control" placeholder="IMEI 1" />
                                        <div class="input-group-append" @click="startCamera('imeiOne', '')">
                                            <button type="button" class="input-group-text"><i data-feather="camera"></i></button>
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <label>IMEI 2</label>
                                    <div class="input-group mb-1">
                                        <input type="text" id="imei2" v-model="deviceDetailsForm.imeiTwo" class="form-control" placeholder="IMEI 2" />
                                        <div class="input-group-append" @click="startCamera('imeiTwo', '')">
                                            <button type="button" class="input-group-text"><i data-feather="camera"></i></button>
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <label>*Device Serial (POS/Phone)</label>
                                    <div class="input-group mb-1">
                                        <input type="text" id="deviceSerial" required v-model="deviceDetailsForm.deviceSerial" class="form-control" placeholder="*Device Serial" />
                                        <div class="input-group-append" @click="startCamera('deviceSerial', '')">
                                            <button type="button" class="input-group-text"><i data-feather="camera"></i></button>
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <label>*Network Type</label>
                                    <div class="input-group mb-1">
                                        <select v-model="deviceDetailsForm.networkType" class="form-control">
                                            <option value="MTN">MTN</option>
                                            <option value="AIRTEL">Airtel</option>
                                        </select>
                                    </div>
                                </div>
                                <div>
                                    <label>*Sim Card Serial No.</label>
                                    <div class="input-group mb-1">
                                        <input type="text" id="simCardSerialNo" v-model="deviceDetailsForm.simCardSerialNo" class="form-control" placeholder="*Sim Card Serial No." />
                                        <div class="input-group-append" @click="startCamera('simCardSerialNo', '')">
                                            <button type="button" class="input-group-text"><i data-feather="camera"></i></button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <div class="text-center" v-show="showCamera === false">
                                    <button type="submit" class="btn btn-primary mr-1 mb-1 me-1 waves-effect waves-float waves-light">Save Details</button>
                                    <button type="reset" class="btn btn-outline-secondary mb-1 waves-effect" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close">Discard</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Device Details Modal -->
            <div class="modal modal-slide-in move modal-primary" id="deviceDetails" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-scrollable modal-xl">
                    <form class="add-new-user modal-content pt-0" id="state-form">
                        <button type="reset" class="close" @click="hideDeviceDetailsModal()" data-dismiss="modal">×</button>
                        <div class="modal-header mb-1">
                            <h5 class="modal-title font-weight-bolder" id="exampleModalLabel">{{ checkIfEmpty(deviceDetails.serial_no) }} Device Details</h5>
                        </div>
                        <div class="modal-body flex-grow-1 vertical-wizard">
                            <div class="info-container pt-25">
                                <table class="table" id="distribution-list">
                                    <tr><td class="user-detail-txt" colspan="2"><label class="d-block text-primary">Device Name</label>{{ checkIfEmpty(deviceDetails.device_name) }}</td></tr>
                                    <tr><td class="user-detail-txt" colspan="2"><label class="d-block text-primary">Device Type</label>{{ checkIfEmpty(deviceDetails.device_type) }}</td></tr>
                                    <tr><td class="user-detail-txt" colspan="2"><label class="d-block text-primary">Device ID</label>{{ checkIfEmpty(deviceDetails.device_id) }}</td></tr>
                                    <tr><td class="user-detail-txt" colspan="2"><label class="d-block text-primary">Device Serial No.</label>{{ checkIfEmpty(deviceDetails.phone_serial) }}</td></tr>
                                    <tr><td class="user-detail-txt" colspan="2"><label class="d-block text-primary">IMEI 1</label>{{ checkIfEmpty(deviceDetails.imei1) }}</td></tr>
                                    <tr><td class="user-detail-txt" colspan="2"><label class="d-block text-primary">IMEI 2</label>{{ checkIfEmpty(deviceDetails.imei2) }}</td></tr>
                                    <tr><td class="user-detail-txt" colspan="2"><label class="d-block text-primary">Sim Card Serial No.</label>{{ checkIfEmpty(deviceDetails.sim_serial) }}</td></tr>
                                    <tr>
                                        <td class="user-detail-txt"><label class="d-block text-primary">Sim Network</label>
                                            <span class="badge rounded-pill" :class="(deviceDetails.sim_network=='MTN')? 'bg-light-warning' : (deviceDetails.sim_network=='AIRTEL')? 'bg-light-danger' : (deviceDetails.sim_network=='GLO')? 'bg-light-success' : 'bg-light-info'">{{ checkIfEmpty(deviceDetails.sim_network) }}</span>
                                        </td>
                                        <td class="user-detail-txt"><label class="d-block text-primary">Device Status</label>
                                            <span class="badge rounded-pill font-small-1" :class="deviceDetails.active=='1'? 'bg-success' : 'bg-danger'">{{ deviceDetails.active=='1'? 'Active' : 'Inactive' }}</span>
                                        </td>
                                    </tr>
                                </table>
                                <div class="justify-content-center mb-50 form-group text-right">
                                    <hr>
                                    <button type="reset" class="btn btn-secondary" data-dismiss="modal" @click="hideDeviceDetailsModal()">Close</button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    `,
};

useApp({ template: `<div><page-body/></div>` })
    .component('page-body', PageBody)
    .component('sample_table', SampleTable)
    .mount('#app');
