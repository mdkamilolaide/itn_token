/**
 * Activity / List submodule — Vue 3 Composition API in place.
 * Five components — page-body (routes), training_list, training_session,
 * participant_list, attendance_list.
 *
 * EventBus events (preserved names):
 *   g-event-goto-page    — routes between training, session, participant, attendance views
 *   g-event-update       — reloads training_list after a participant change
 */

const { ref, reactive, onMounted, onBeforeUnmount } = Vue;
const { useApp, useFormat, bus, safeMessage } = window.utils;

/* ------------------------------------------------------------------ */
/* page-body                                                            */
/* ------------------------------------------------------------------ */
const PageBody = {
    setup() {
        const page = ref('training');
        const gotoPageHandler = (data) => { page.value = data.page; };
        onMounted(() => { bus.on('g-event-goto-page', gotoPageHandler); });
        onBeforeUnmount(() => { bus.off('g-event-goto-page', gotoPageHandler); });
        return { page };
    },
    template: `
        <div>
            <div class="content-body">
                <div v-show="page == 'training'"><training_list/></div>
                <div v-show="page == 'session'"><training_session/></div>
                <div v-show="page == 'participant'"><participant_list/></div>
                <div v-show="page == 'attendance'"><attendance_list/></div>
            </div>
        </div>
    `,
};

/* ------------------------------------------------------------------ */
/* training_list                                                        */
/* ------------------------------------------------------------------ */
const TrainingList = {
    setup() {
        const fmtUtils = useFormat();

        const tableData = ref([]);
        const checkToggle = ref(false);
        const filterState = ref(false);
        const filters = ref(false);
        const url = ref(window.common && window.common.TableService);
        const tableOptions = reactive({
            total: 1, pageLength: 1, perPage: 10, currentPage: 1,
            orderDir: 'desc', orderField: 0, limitStart: 0,
            isNext: false, isPrev: false,
            aLength: [10, 20, 50, 100],
            filterParam: { trainingid: '', title: '', active: '' },
        });
        const permission = ref(
            (typeof getPermission === 'function')
                ? (getPermission(typeof per !== 'undefined' ? per : null, 'activity') || { permission_value: 0 })
                : { permission_value: 0 }
        );
        const errors = ref([]);
        const trainingBtn = ref('');
        const addTrainingModal = ref(false);
        const trainingForm = reactive({
            training_id: '', title: '', description: '',
            start_date: '2022-03-21', end_date: '2022-03-23',
            geoLevel: 'state', geoLevelId: 0,
        });
        const geoIndicator = reactive({ state: 50, currentLevelId: 0, ward: '' });
        const geoLevelData = ref([]);
        const sysDefaultData = ref({});
        const lgaLevelData = ref([]);
        const clusterLevelData = ref([]);
        const wardLevelData = ref([]);
        const dpLevelData = ref([]);
        const defaultStateId = ref(0);

        let dateFlatpickr = null;
        let startDateFlatpickr = null;
        let endDateFlatpickr = null;

        const loadTableData = () => {
            overlay.show();
            var u = common.TableService;
            axios.get(
                u +
                '?qid=101&draw=' + tableOptions.currentPage +
                '&order_column=' + tableOptions.orderField +
                '&length=' + tableOptions.perPage +
                '&start=' + tableOptions.limitStart +
                '&order_dir=' + tableOptions.orderDir +
                '&ac=' + tableOptions.filterParam.active +
                '&id=' + tableOptions.filterParam.trainingid +
                '&tr=' + tableOptions.filterParam.title
            )
                .then(response => {
                    var d = response && response.data;
                    tableData.value = Array.isArray(d && d.data) ? d.data : [];
                    tableOptions.total = (d && d.recordsTotal) || 0;
                    if (tableOptions.currentPage == 1) paginationDefault();
                    overlay.hide();
                })
                .catch(error => {
                    overlay.hide();
                    alert.Error('ERROR', safeMessage(error));
                });
        }

        const selectAll = () => { for (var i = 0; i < tableData.value.length; i++) tableData.value[i].pick = true; };
        const uncheckAll = () => { for (var i = 0; i < tableData.value.length; i++) tableData.value[i].pick = false; };
        const selectToggle = () => {
            if (checkToggle.value === false) { selectAll(); checkToggle.value = true; }
            else                              { uncheckAll(); checkToggle.value = false; }
        }
        const checkedBg = (pickOne) => { return pickOne != '' ? 'bg-select' : ''; };
        const toggleFilter = () => {
            if (filterState.value === false) filters.value = false;
            return (filterState.value = !filterState.value);
        }
        const selectedItems = () => { return tableData.value.filter(r => r.pick); };
        const selectedID = () => { return tableData.value.filter(r => r.pick).map(r => r.userid); };

        const paginationDefault = () => {
            tableOptions.pageLength = Math.ceil(tableOptions.total / tableOptions.perPage);
            tableOptions.limitStart = Math.ceil((tableOptions.currentPage - 1) * tableOptions.perPage);
            tableOptions.isNext = tableOptions.currentPage < tableOptions.pageLength;
            tableOptions.isPrev = tableOptions.currentPage > 1;
        }
        const nextPage = () => { tableOptions.currentPage += 1; paginationDefault(); loadTableData(); };
        const prevPage = () => { tableOptions.currentPage -= 1; paginationDefault(); loadTableData(); };
        const currentPage = () => {
            paginationDefault();
            if (tableOptions.currentPage < 1)                            alert.Error('ERROR', "The Page requested doesn't exist");
            else if (tableOptions.currentPage > tableOptions.pageLength) alert.Error('ERROR', "The Page requested doesn't exist");
            else                                                         loadTableData();
        }
        const changePerPage = (val) => {
            tableOptions.perPage = val;
            paginationDefault();
            loadTableData();
        }
        const sort = (col) => {
            if (tableOptions.orderField === col) tableOptions.orderDir = tableOptions.orderDir === 'asc' ? 'desc' : 'asc';
            else                                  tableOptions.orderField = col;
            paginationDefault();
            loadTableData();
        }
        const applyFilter = () => {
            var checkFill = 0;
            checkFill += tableOptions.filterParam.title      != '' ? 1 : 0;
            checkFill += tableOptions.filterParam.trainingid != '' ? 1 : 0;
            checkFill += tableOptions.filterParam.active     != '' ? 1 : 0;
            if (checkFill > 0) {
                toggleFilter();
                filters.value = true;
                paginationDefault();
                loadTableData();
            } else {
                alert.Error('ERROR', 'Invalid required data');
            }
        }
        const removeSingleFilter = (column_name) => {
            tableOptions.filterParam[column_name] = '';
            var g = 0;
            for (var k in tableOptions.filterParam) {
                if (tableOptions.filterParam[k] != '') g++;
            }
            if (g == 0) filters.value = false;
            paginationDefault();
            loadTableData();
        }
        const clearAllFilter = () => {
            filters.value = false;
            tableOptions.filterParam.title = '';
            tableOptions.filterParam.trainingid = '';
            tableOptions.filterParam.active = '';
            paginationDefault();
            loadTableData();
        }
        const refreshData = () => { paginationDefault(); loadTableData(); };

        const deActivateUser = (training_id, status, ui_id) => {
            var u = common.DataService;
            var message, btn_text, btn_class, response_txt;
            if (status == '1') {
                message = 'Are you sure you want to Deactivate the Activity with ID <b>' + ui_id + '</b>?';
                btn_text = 'Deactivate';
                btn_class = ' btn-danger ';
                response_txt = 'Activity with ID <b>' + ui_id + '</b> Successfully Deactivated';
            } else {
                message = 'Are you sure you want to Activate an Activity with ID <b>' + ui_id + '</b>?';
                btn_text = 'Activate';
                btn_class = ' btn-success ';
                response_txt = 'Activity with ID <b>' + ui_id + '</b> Successfully Activated';
            }
            $.confirm({
                title: 'WARNING!',
                content: message,
                buttons: {
                    delete: {
                        text: btn_text,
                        btnClass: 'btn mr-1' + btn_class,
                        action: () => {
                            axios.post(u + '?qid=103&e=' + training_id)
                                .then(response => {
                                    overlay.hide();
                                    if (response.data.result_code == '200') {
                                        loadTableData();
                                        alert.Success('SUCCESS', response_txt);
                                    } else {
                                        alert.Error('ERROR', 'Unable to De/Activate Activity with ID <b>' + ui_id + '</b>');
                                    }
                                })
                                .catch(error => {
                                    overlay.hide();
                                    alert.Error('ERROR', safeMessage(error));
                                });
                        },
                    },
                    cancel: () => { overlay.hide(); },
                },
            });
        }

        const showaddTrainingModal = () => {
            resettrainingForm();
            $('#addNewTraining').modal('show');
            addTrainingModal.value = true;
            trainingBtn.value = 'Create';
        }
        const hideaddTrainingModal = () => {
            resettrainingForm();
            addTrainingModal.value = false;
            trainingBtn.value = '';
            $('#addNewTraining').modal('hide');
        }
        const goToSessionLists = (trainingid) => {
            bus.emit('g-event-goto-page', { trainingid: trainingid, page: 'session' });
        }
        const goToParticipantList = (trainingid) => {
            bus.emit('g-event-goto-page', { trainingid: trainingid, page: 'participant' });
        }

        const getsysDefaultDataSettings = () => {
            overlay.show();
            axios.get(common.DataService + '?qid=gen007')
                .then(response => {
                    if (response.data.data && response.data.data.length > 0) {
                        sysDefaultData.value = response.data.data[0];
                        trainingForm.geoLevel = 'state';
                        trainingForm.geoLevelId = response.data.data[0].stateid;
                    }
                    overlay.hide();
                })
                .catch(error => {
                    overlay.hide();
                    alert.Error('ERROR', safeMessage(error));
                });
        }
        const getGeoLevel = () => {
            overlay.show();
            axios.get(common.DataService + '?qid=gen001')
                .then(response => {
                    geoLevelData.value = (response.data && response.data.data) || [];
                    overlay.hide();
                })
                .catch(error => {
                    overlay.hide();
                    alert.Error('ERROR', safeMessage(error));
                });
        }
        const getLgasLevel = (stateid) => {
            overlay.show();
            axios.post(common.DataService + '?qid=gen003', JSON.stringify(stateid))
                .then(response => {
                    lgaLevelData.value = (response.data && response.data.data) || [];
                    overlay.hide();
                })
                .catch(error => {
                    overlay.hide();
                    alert.Error('ERROR', safeMessage(error));
                });
        }
        const getClusterLevel = () => {
            overlay.show();
            axios.get(common.DataService + '?qid=gen004&e=' + (geoIndicator.cluster || ''))
                .then(response => {
                    clusterLevelData.value = (response.data && response.data.data) || [];
                    overlay.hide();
                })
                .catch(error => {
                    overlay.hide();
                    alert.Error('ERROR', safeMessage(error));
                });
        }
        const getWardLevel = () => {
            overlay.show();
            axios.get(common.DataService + '?qid=gen005&e=' + (geoIndicator.lga || ''))
                .then(response => {
                    wardLevelData.value = (response.data && response.data.data) || [];
                    overlay.hide();
                })
                .catch(error => {
                    overlay.hide();
                    alert.Error('ERROR', safeMessage(error));
                });
        }
        const getDpLevel = () => {
            overlay.show();
            axios.get(common.DataService + '?qid=gen006')
                .then(response => {
                    dpLevelData.value = (response.data && response.data.data) || [];
                    overlay.hide();
                })
                .catch(error => {
                    overlay.hide();
                    alert.Error('ERROR', safeMessage(error));
                });
        }
        const changeGeoLevel = () => {
            if (trainingForm.geoLevel == 'country' || trainingForm.geoLevel == 'dp') {
                alert.Error('ERROR', 'Invalid Geo-Level selected, please select a valid Geo-Level');
            }
        }

        const onSubmitCreateTraining = (action) => {
            var u = common.DataService;
            if (action == 'Create') {
                overlay.show();
                axios.post(u + '?qid=101', JSON.stringify(trainingForm))
                    .then(response => {
                        if (response.data.result_code == '201') {
                            resettrainingForm();
                            addTrainingModal.value = false;
                            $('#addNewTraining').modal('hide');
                            loadTableData();
                            alert.Success('Success', response.data.message);
                            overlay.hide();
                        } else {
                            overlay.hide();
                            alert.Error('Error', response.data.message);
                        }
                    })
                    .catch(error => {
                        alert.Error('ERROR', safeMessage(error));
                        overlay.hide();
                    });
            } else {
                updateTraining();
            }
        }
        const resettrainingForm = () => {
            try {
                if (startDateFlatpickr && startDateFlatpickr.clear) startDateFlatpickr.clear();
                if (endDateFlatpickr && endDateFlatpickr.clear) endDateFlatpickr.clear();
            } catch (e) {}
            trainingBtn.value = '';
            trainingForm.training_id = '';
            trainingForm.title = '';
            trainingForm.description = '';
            trainingForm.start_date = '';
            trainingForm.end_date = '';
            trainingForm.geoLevel = 'state';
            trainingForm.geoLevelId = 0;
            overlay.hide();
        }
        const editTraining = (training_id, training_pos) => {
            overlay.show();
            addTrainingModal.value = true;
            trainingBtn.value = 'Update';
            trainingForm.training_id = training_id;
            var row = tableData.value[training_pos] || {};
            trainingForm.title = row.title;
            trainingForm.description = row.description;
            trainingForm.start_date = row.db_start_date;
            trainingForm.end_date = row.db_end_date;
            trainingForm.geoLevel = row.geo_location;
            trainingForm.geoLevelId = row.location_id;
            overlay.hide();
        }
        const updateTraining = () => {
            var u = common.DataService;
            $.confirm({
                title: 'WARNING!',
                content: 'Are you sure you want to Update an Activity with ID:' + trainingForm.training_id + '?',
                buttons: {
                    delete: {
                        text: 'Update',
                        btnClass: 'btn btn-danger mr-1 text-capitalize',
                        action: () => {
                            axios.post(u + '?qid=102', JSON.stringify(trainingForm))
                                .then(response => {
                                    if (response.data.result_code == '201') {
                                        resettrainingForm();
                                        addTrainingModal.value = false;
                                        $('#addNewTraining').modal('hide');
                                        loadTableData();
                                        alert.Success('Success', response.data.message);
                                        overlay.hide();
                                    } else {
                                        overlay.hide();
                                        alert.Error('Error', response.data.message);
                                    }
                                })
                                .catch(error => {
                                    alert.Error('ERROR', safeMessage(error));
                                    overlay.hide();
                                });
                        },
                    },
                    cancel: () => {
                        resettrainingForm();
                        $('#addNewTraining').modal('hide');
                        overlay.hide();
                    },
                },
            });
        }

        onMounted(() => {
            getGeoLevel();
            getsysDefaultDataSettings();
            loadTableData();
            bus.on('g-event-update', loadTableData);

            // Initialize flatpickr for both date inputs.
            try {
                if ($('#start-date').length && typeof $.fn.flatpickr === 'function') {
                    startDateFlatpickr = $('#start-date').flatpickr({
                        altInput: true, altFormat: 'F j, Y', dateFormat: 'Y-m-d', minDate: 'today',
                        onChange: (selectedDates, dateStr) => { trainingForm.start_date = dateStr; },
                    });
                }
                if ($('#end-date').length && typeof $.fn.flatpickr === 'function') {
                    endDateFlatpickr = $('#end-date').flatpickr({
                        altInput: true, altFormat: 'F j, Y', dateFormat: 'Y-m-d', minDate: 'today',
                        onChange: (selectedDates, dateStr) => { trainingForm.end_date = dateStr; },
                    });
                }
            } catch (e) { /* swallow */ }
        });
        onBeforeUnmount(() => {
            bus.off('g-event-update', loadTableData);
            try { if (startDateFlatpickr && startDateFlatpickr.destroy) startDateFlatpickr.destroy(); } catch (e) {}
            try { if (endDateFlatpickr   && endDateFlatpickr.destroy)   endDateFlatpickr.destroy(); }   catch (e) {}
        });

        return {
            tableData, checkToggle, filterState, filters, url, tableOptions,
            permission, errors, trainingBtn, addTrainingModal, trainingForm,
            geoIndicator, geoLevelData, sysDefaultData, lgaLevelData,
            clusterLevelData, wardLevelData, dpLevelData, defaultStateId,
            loadTableData, selectAll, uncheckAll, selectToggle, checkedBg,
            toggleFilter, selectedItems, selectedID,
            nextPage, prevPage, currentPage, paginationDefault, changePerPage,
            sort, applyFilter, removeSingleFilter, clearAllFilter, refreshData,
            deActivateUser, showaddTrainingModal, hideaddTrainingModal,
            goToSessionLists, goToParticipantList,
            getsysDefaultDataSettings, getGeoLevel, getLgasLevel, getClusterLevel,
            getWardLevel, getDpLevel, changeGeoLevel,
            onSubmitCreateTraining, resettrainingForm, editTraining, updateTraining,
            capitalize: fmtUtils.capitalize,
            formatNumber: fmtUtils.formatNumber,
            displayDate: fmtUtils.displayDate,
        };
    },
    template: `
        <div class="row" id="basic-table">
            <div class="col-md-8 col-sm-12 col-12 mb-0">
                <h2 class="content-header-title header-txt float-left mb-0">Activity Mgmt.</h2>
                <div class="breadcrumb-wrapper">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../activity">Home</a></li>
                        <li class="breadcrumb-item active">Activity List</li>
                    </ol>
                </div>
            </div>

            <div class="col-md-4 col-sm-12 col-12 text-md-right text-right d-md-block">
                <div class="btn-group mr-1">
                    <button v-if="permission.permission_value > 2" type="button" @click="showaddTrainingModal()" data-target="#addNewTraining" data-toggle="tooltip" data-placement="top" title="Create an Activity" class="btn btn-outline-primary round"><i data-feather='plus'></i></button>
                    <button type="button" class="btn btn-outline-primary round searchBtn" @click="refreshData()" data-toggle="tooltip" data-placement="top" title="Refresh">
                        <i class="feather icon-refresh-cw"></i>
                    </button>
                    <button type="button" class="btn btn-outline-primary round searchBtn" @click="toggleFilter()" data-toggle="tooltip" data-placement="top" title="Filter">
                        <i class="feather" :class="filterState ? 'icon-x' : 'icon-filter'"></i>
                    </button>
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

            <div class="col-12 mt-1">
                <div class="card">
                    <div class="card-body py-1" v-show="filterState">
                        <form id="filterForm">
                            <div class="row">
                                <div class="col-12 col-sm-6 col-md-3 col-lg-3">
                                    <div class="form-group">
                                        <label>Activity Status</label>
                                        <select v-model="tableOptions.filterParam.active" class="form-control active">
                                            <option value="">All Activity</option>
                                            <option value="active">Active Activity</option>
                                            <option value="inactive">Inactive Activity</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-12 col-sm-6 col-md-3 col-lg-3">
                                    <div class="form-group">
                                        <label>Activity ID</label>
                                        <input type="text" v-model="tableOptions.filterParam.trainingid" class="form-control training-id" id="training-id" placeholder="Activity ID" name="trainingid" />
                                    </div>
                                </div>
                                <div class="col-12 col-sm-6 col-md-3 col-lg-3">
                                    <div class="form-group">
                                        <label>Activity Title</label>
                                        <input type="text" id="title" v-model="tableOptions.filterParam.title" class="form-control title" placeholder="Activity Title" name="title" />
                                    </div>
                                </div>
                                <div class="col-12 col-sm-6 col-md-3 col-lg-3">
                                    <div class="form-group mt-2 text-right">
                                        <button type="button" class="btn btn-md btn-primary" @click="applyFilter()">Apply Filters</button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th @click="sort(0)" width="60px">ID
                                        <i class="feather icon-chevron-up sort-up"   :class="(tableOptions.orderField == 0 && tableOptions.orderDir =='asc' )? 'active-sort':''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 0 && tableOptions.orderDir =='desc')? 'active-sort':''"></i>
                                    </th>
                                    <th @click="sort(1)">Activities
                                        <i class="feather icon-chevron-up sort-up"   :class="(tableOptions.orderField == 1 && tableOptions.orderDir =='asc' )? 'active-sort':''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 1 && tableOptions.orderDir =='desc')? 'active-sort':''"></i>
                                    </th>
                                    <th @click="sort(2)">Location
                                        <i class="feather icon-chevron-up sort-up"   :class="(tableOptions.orderField == 2 && tableOptions.orderDir =='asc' )? 'active-sort':''"></i>
                                        <i class="feather icon-chevron-down sort-down" :class="(tableOptions.orderField == 2 && tableOptions.orderDir =='desc')? 'active-sort':''"></i>
                                    </th>
                                    <th @click="sort(9)" width="100px">Participants</th>
                                    <th @click="sort(7)">Start/End Date</th>
                                    <th @click="sort(5)">Status</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(g, i) in tableData" :key="g.trainingid || i" :class="checkedBg(g.pick)">
                                    <td>{{ g.ui_id }}</td>
                                    <td>
                                        <div class="d-flex justify-content-left align-items-center">
                                            <div class="d-flex flex-column">
                                                <a href="#" class="user_name text-truncate text-body">
                                                    <span class="fw-bolder">{{ g.title }}</span>
                                                </a>
                                                <small class="emp_post text-muted">{{ g.description }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{ capitalize(g.geo_location) }}</td>
                                    <td>{{ g.participant_count }}</td>
                                    <td>
                                        <div class="d-flex justify-content-left align-items-center">
                                            <div class="d-flex flex-column">
                                                <small class="emp_post text-muted">{{ g.start_date }}</small>
                                                <small class="emp_post text-muted">{{ g.end_date }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><span class="badge rounded-pill font-small-1" :class="g.active == 1 ? 'bg-success' : 'bg-danger'">{{ g.active == 1 ? 'Active' : 'Inactive' }}</span></td>
                                    <td>
                                        <div class="dropdown">
                                            <button type="button" class="btn btn-sm dropdown-toggle hide-arrow" data-toggle="dropdown">
                                                <i class="feather icon-more-vertical"></i>
                                            </button>
                                            <div class="dropdown-menu">
                                                <a class="dropdown-item" href="javascript:void(0);" @click="goToSessionLists(g.trainingid)"><i class="feather icon-clock"></i> Sessions</a>
                                                <a class="dropdown-item" href="javascript:void(0);" @click="goToParticipantList(g.trainingid)"><i class="feather icon-eye"></i> Participants</a>
                                                <a v-if="permission.permission_value == 3" class="dropdown-item" href="javascript:void(0);" @click="deActivateUser(g.trainingid, g.active, g.ui_id)"><i class="feather" :class="g.active == '1' ? 'icon-x-circle' : 'icon-check-circle'"></i> {{ g.active == '1' ? ' Deactivate ' : ' Activate ' }}</a>
                                                <a v-if="permission.permission_value >= 2" class="dropdown-item" href="javascript:void(0);" data-toggle="modal" data-target="#addNewTraining" @click="editTraining(g.trainingid, i)"><i class="feather icon-edit"></i> Edit</a>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="tableData.length === 0"><td class="text-center pt-2" colspan="8"><small>No Activity Added</small></td></tr>
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
                                        <button type="button" @click="prevPage()" class="btn btn-sm btn-primary round btn-page-block-overlay" :disabled="!tableOptions.isPrev"><i data-feather='chevron-left'></i> Prev</button>
                                        <input @keyup.enter="currentPage()" class="btn btn-page-block-overlay btn-sm btn-outline-primary pagination-input" type="number" v-model.number="tableOptions.currentPage" :max="tableOptions.pageLength" />
                                        <button class="btn btn-outline-primary btn-page-block-overlay border-l-0">
                                            <small class="form-text text-primary"> of {{ tableOptions.pageLength }}</small>
                                        </button>
                                        <button type="button" @click="nextPage()" class="btn btn-sm btn-primary round" :disabled="!tableOptions.isNext">Next <i data-feather='chevron-right'></i></button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mb-50"></div>
                </div>
            </div>

            <!-- Modal to add new training -->
            <div class="modal modal-slide-in" id="addNewTraining" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-scrollable modal-xl">
                    <form class="add-new-user modal-content pt-0" @submit.stop.prevent="onSubmitCreateTraining(trainingBtn)">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close" @click="hideaddTrainingModal()">×</button>
                        <div class="modal-header mb-1">
                            <h5 class="modal-title" id="exampleModalLabel">{{ trainingBtn }} Activity</h5>
                        </div>
                        <div class="modal-body flex-grow-1">
                            <div class="form-group">
                                <label class="form-label">Activity Title</label>
                                <input required v-model="trainingForm.title" class="form-control" placeholder="Activity Title" />
                            </div>
                            <div class="form-group">
                                <label class="form-label">Description</label>
                                <textarea v-model="trainingForm.description" class="form-control" placeholder="Activity Description"></textarea>
                            </div>
                            <div class="form-group" id="start">
                                <label class="form-label">Start Date</label>
                                <input required v-model="trainingForm.start_date" id="start-date" class="form-control date" placeholder="Start Date" />
                            </div>
                            <div class="form-group" id="end">
                                <label class="form-label">End Date</label>
                                <input required v-model="trainingForm.end_date" id="end-date" class="form-control date" placeholder="End Date" />
                            </div>
                            <div class="form-group">
                                <label class="form-label">Geo Level</label>
                                <select @change="changeGeoLevel($event)" class="form-control" v-model="trainingForm.geoLevel">
                                    <option v-for="geo in geoLevelData" :value="geo.geo_level" :key="geo.geo_level">{{ capitalize(geo.geo_level) }}</option>
                                </select>
                            </div>
                            <div class="mt-2">
                                <button type="submit" class="btn btn-primary mr-1 data-submit">{{ trainingBtn }}</button>
                                <button type="reset" class="btn btn-outline-secondary" data-dismiss="modal" @click="hideaddTrainingModal()">Cancel</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    `,
};

/* ------------------------------------------------------------------ */
/* training_session                                                     */
/* ------------------------------------------------------------------ */
const TrainingSession = {
    setup() {
        const fmtUtils = useFormat();

        const tableData = ref([]);
        const addSessionModal = ref(false);
        const sessionBtn = ref('');
        const sessionForm = reactive({
            trainingid: '', sessionid: '', title: '', date: '', altdate: '',
        });
        const permission = ref(
            (typeof getPermission === 'function')
                ? (getPermission(typeof per !== 'undefined' ? per : null, 'activity') || { permission_value: 0 })
                : { permission_value: 0 }
        );

        let dateFlatpickr = null;

        const gotoPageHandler = (data) => {
            sessionForm.trainingid = data.trainingid;
            sessionForm.sessionid = data.sessionid;
            sessionForm.title = data.title;
            loadTableData();
        }
        const goToTrainingList = () => {
            bus.emit('g-event-goto-page', { page: 'training', trainingid: '' });
        }
        const goToAttendanceList = (sessionid, title) => {
            bus.emit('g-event-goto-page', {
                page: 'attendance',
                trainingid: sessionForm.trainingid,
                sessionid: sessionid,
                title: title,
            });
        }

        const loadTableData = () => {
            overlay.show();
            axios.get(common.DataService + '?qid=104&e=' + sessionForm.trainingid)
                .then(response => {
                    tableData.value = (response.data && response.data.data) || [];
                    overlay.hide();
                })
                .catch(error => {
                    overlay.hide();
                    alert.Error('ERROR', safeMessage(error));
                });
        }

        const resetForm = () => {
            try { if (dateFlatpickr && dateFlatpickr.clear) dateFlatpickr.clear(); } catch (e) {}
            sessionBtn.value = '';
            sessionForm.title = '';
            sessionForm.sessionid = '';
            sessionForm.date = '';
            overlay.hide();
        }
        const refreshData = () => { loadTableData(); };

        const showSessionModal = () => {
            resetForm();
            addSessionModal.value = true;
            sessionBtn.value = 'Create';
            $('#addSession').modal('show');
        }
        const hideaddSessionModal = () => {
            resetForm();
            addSessionModal.value = false;
            sessionBtn.value = '';
            $('#addSession').modal('hide');
        }
        const onSubmitCreateSession = (action) => {
            var u = common.DataService;
            if (action == 'Create') {
                if (sessionForm.date != '') {
                    overlay.show();
                    axios.post(u + '?qid=105', JSON.stringify(sessionForm))
                        .then(response => {
                            if (response.data.result_code == '201') {
                                resetForm();
                                addSessionModal.value = false;
                                $('#addSession').modal('hide');
                                loadTableData();
                                alert.Success('Success', response.data.message);
                                overlay.hide();
                            } else {
                                overlay.hide();
                                alert.Error('Error', response.data.message);
                            }
                        })
                        .catch(error => {
                            alert.Error('ERROR', safeMessage(error));
                            overlay.hide();
                        });
                } else {
                    alert.Error('Required Fields', 'All fields are required to be filled');
                }
            } else {
                updateSession();
            }
        }
        const editSession = (session_id, session_pos) => {
            overlay.show();
            addSessionModal.value = true;
            sessionBtn.value = 'Update';
            sessionForm.sessionid = session_id;
            var row = tableData.value[session_pos] || {};
            sessionForm.title = row.title;
            sessionForm.date = row.session_date;
            overlay.hide();
        }
        const updateSession = () => {
            var u = common.DataService;
            $.confirm({
                title: 'WARNING!',
                content: 'Are you sure you want to Update the Activity Session?',
                buttons: {
                    delete: {
                        text: 'Update',
                        btnClass: 'btn btn-danger mr-1 text-capitalize',
                        action: () => {
                            axios.post(u + '?qid=106', JSON.stringify(sessionForm))
                                .then(response => {
                                    if (response.data.result_code == '201') {
                                        resetForm();
                                        addSessionModal.value = false;
                                        $('#addSession').modal('hide');
                                        loadTableData();
                                        alert.Success('Success', response.data.message);
                                        overlay.hide();
                                    } else {
                                        overlay.hide();
                                        alert.Error('Error', response.data.message);
                                    }
                                })
                                .catch(error => {
                                    alert.Error('ERROR', safeMessage(error));
                                    overlay.hide();
                                });
                        },
                    },
                    cancel: () => {
                        resetForm();
                        addSessionModal.value = false;
                        $('#addSession').modal('hide');
                        overlay.hide();
                    },
                },
            });
        }
        const deleteSession = (session_id) => {
            var u = common.DataService;
            $.confirm({
                title: 'WARNING!',
                content: 'Are you sure you want to Delete an Activity Session?',
                buttons: {
                    delete: {
                        text: 'Delete',
                        btnClass: 'btn btn-danger mr-1 text-capitalize',
                        action: () => {
                            axios.post(u + '?qid=107&e=' + session_id)
                                .then(response => {
                                    if (response.data.result_code == '200') {
                                        resetForm();
                                        addSessionModal.value = false;
                                        $('#addSession').modal('hide');
                                        loadTableData();
                                        alert.Success('Success', response.data.message);
                                        overlay.hide();
                                    } else {
                                        overlay.hide();
                                        alert.Error('Error', response.data.message);
                                    }
                                })
                                .catch(error => {
                                    alert.Error('ERROR', safeMessage(error));
                                    overlay.hide();
                                });
                        },
                    },
                    cancel: () => {
                        resetForm();
                        $('#addSession').modal('hide');
                        overlay.hide();
                    },
                },
            });
        }

        const downlodAttendance = async (session_id, title) => {
            var sid = session_id;
            var veriUrl = 'qid=115&sid=' + sid;
            var dlString = 'qid=102&id=' + sid;
            var filename = title + ' Attendance - (' + sid + ')';
            overlay.show();

            var count = await new Promise(resolve => {
                $.ajax({
                    url: common.DataService, type: 'POST', data: veriUrl, dataType: 'json',
                    success: (data) => { resolve(data.total); },
                });
            });
            var downloadMax = (window.common && window.common.ExportDownloadLimit) || 25000;
            if (parseInt(count) > downloadMax) {
                alert.Error('Download Error', 'Unable to download data because it has exceeded download limit, download limit is ' + downloadMax);
            } else if (parseInt(count) == 0) {
                alert.Error('Download Error', 'No data found');
            } else {
                alert.Info('DOWNLOADING...', 'Downloading ' + count + ' record(s)');
                var outcome = await new Promise(resolve => {
                    $.ajax({
                        url: common.ExportService, type: 'POST', data: dlString,
                        success: (data) => { resolve(data); },
                    });
                });
                var exportData = JSON.parse(outcome);
                if (window.Jhxlsx && typeof window.Jhxlsx.export === 'function') {
                    window.Jhxlsx.export(exportData, { fileName: filename });
                }
            }
            overlay.hide();
        }

        onMounted(() => {
            bus.on('g-event-goto-page', gotoPageHandler);
            loadTableData();
            try {
                if ($('#d').length && typeof $.fn.flatpickr === 'function') {
                    dateFlatpickr = $('#d').flatpickr({
                        altInput: true, altFormat: 'F j, Y', dateFormat: 'Y-m-d', minDate: 'today',
                        onChange: (selectedDates, dateStr) => { sessionForm.date = dateStr; },
                    });
                }
            } catch (e) {}
        });
        onBeforeUnmount(() => {
            bus.off('g-event-goto-page', gotoPageHandler);
            try { if (dateFlatpickr && dateFlatpickr.destroy) dateFlatpickr.destroy(); } catch (e) {}
        });

        return {
            tableData, addSessionModal, sessionBtn, sessionForm, permission,
            gotoPageHandler, goToTrainingList, goToAttendanceList, loadTableData,
            resetForm, refreshData, showSessionModal, hideaddSessionModal,
            onSubmitCreateSession, editSession, updateSession, deleteSession,
            downlodAttendance,
            capitalize: fmtUtils.capitalize,
            formatNumber: fmtUtils.formatNumber,
        };
    },
    template: `
        <div class="row">
            <div class="col-md-8 col-sm-12 col-12 mb-0">
                <h2 class="content-header-title header-txt float-left mb-0">Activity Mgmt.</h2>
                <div class="breadcrumb-wrapper">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../activity">Home</a></li>
                        <li class="breadcrumb-item"><a href="javascript:void(0);" @click="goToTrainingList()">Activity List</a></li>
                        <li class="breadcrumb-item active">Sessions</li>
                    </ol>
                </div>
            </div>
            <div class="col-md-4 col-sm-12 col-12 text-md-right text-right d-md-block">
                <div class="btn-group mr-1">
                    <button v-if="permission.permission_value > 2" type="button" @click="showSessionModal()" data-target="#addSession" data-toggle="tooltip" data-placement="top" title="Create New Session" class="btn btn-outline-primary round"><i data-feather='plus'></i></button>
                    <button type="button" class="btn btn-outline-primary round searchBtn" @click="refreshData()" data-toggle="tooltip" data-placement="top" title="Refresh">
                        <i class="feather icon-refresh-cw"></i>
                    </button>
                </div>
            </div>

            <div class="col-12 mt-2">
                <div class="card">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr><th width="60px">#</th><th>Session Title</th><th>Session Date</th><th></th></tr>
                            </thead>
                            <tbody>
                                <tr v-for="(g, i) in tableData" :key="g.sessionid || i">
                                    <td>{{ i + 1 }}</td>
                                    <td>{{ g.title }}</td>
                                    <td>{{ g.session_date }}</td>
                                    <td>
                                        <div class="dropdown">
                                            <button type="button" class="btn btn-sm dropdown-toggle hide-arrow" data-toggle="dropdown">
                                                <i class="feather icon-more-vertical"></i>
                                            </button>
                                            <div class="dropdown-menu">
                                                <a class="dropdown-item" href="javascript:void(0);" @click="goToAttendanceList(g.sessionid, g.title)"><i class="feather icon-eye"></i> Attendance</a>
                                                <a v-if="permission.permission_value >= 2" class="dropdown-item" href="javascript:void(0);" @click="downlodAttendance(g.sessionid, g.title)"><i class="feather icon-download"></i> Download Attendance</a>
                                                <a v-if="permission.permission_value >= 2" class="dropdown-item" href="javascript:void(0);" @click="editSession(g.sessionid, i)" data-toggle="modal" data-target="#addSession"><i class="feather icon-edit"></i> Edit</a>
                                                <a v-if="permission.permission_value == 3" class="dropdown-item" href="javascript:void(0);" @click="deleteSession(g.sessionid)"><i class="feather icon-delete"></i> Delete</a>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="tableData.length === 0"><td class="text-center pt-2" colspan="4"><small>No Activity session Added</small></td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="modal modal-slide-in" id="addSession" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-scrollable modal-xl">
                    <form class="add-new-user modal-content pt-0" @submit.stop.prevent="onSubmitCreateSession(sessionBtn)">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close" @click="hideaddSessionModal()">×</button>
                        <div class="modal-header mb-1">
                            <h5 class="modal-title" id="exampleModalLabel" v-text="(sessionBtn=='Create')? 'Create New Activity Session' : 'Edit Activity Session'"></h5>
                        </div>
                        <div class="modal-body flex-grow-1">
                            <div class="form-group">
                                <label class="form-label">Session Title</label>
                                <input required v-model="sessionForm.title" class="form-control" placeholder="Session Title" />
                            </div>
                            <div class="form-group">
                                <label class="form-label">Session Date</label>
                                <input required v-model="sessionForm.date" id="d" class="form-control date" placeholder="Session Date" />
                            </div>
                            <div class="mt-2">
                                <button type="submit" class="btn btn-primary mr-1 data-submit">{{ sessionBtn }}</button>
                                <button type="reset" class="btn btn-outline-secondary" data-dismiss="modal" @click="hideaddSessionModal()">Cancel</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    `,
};

/* ------------------------------------------------------------------ */
/* participant_list                                                     */
/* ------------------------------------------------------------------ */
const ParticipantList = {
    setup() {
        const fmtUtils = useFormat();

        const tableData = ref([]);
        const groupData = ref([]);
        const geoData = ref([]);
        const addBulkParticipantModal = ref(false);
        const participantBtn = ref('');
        const participantForm = reactive({
            trainingid: '', group_name: '', session_id: '',
        });
        const permission = ref(
            (typeof getPermission === 'function')
                ? (getPermission(typeof per !== 'undefined' ? per : null, 'activity') || { permission_value: 0 })
                : { permission_value: 0 }
        );
        const checkToggle = ref(false);
        const filterState = ref(false);
        const filters = ref(false);
        const url = ref(window.common && window.common.TableService);
        const tableOptions = reactive({
            total: 1, pageLength: 1, perPage: 10, currentPage: 1,
            orderDir: 'asc', orderField: 1, limitStart: 0,
            isNext: false, isPrev: false,
            aLength: [10, 20, 50, 100, 200, 300],
            filterParam: { name: '', loginid: '', geo_level: '', geo_level_id: '' },
        });

        const gotoPageHandler = (data) => {
            participantForm.trainingid = data.trainingid;
            loadTableData();
        }
        const goToTrainingList = () => {
            bus.emit('g-event-goto-page', { page: 'training', trainingid: '' });
        }

        const loadTableData = () => {
            overlay.show();
            var u = common.TableService;
            axios.get(
                u +
                '?qid=102&draw=' + tableOptions.currentPage +
                '&order_column=' + tableOptions.orderField +
                '&length=' + tableOptions.perPage +
                '&start=' + tableOptions.limitStart +
                '&order_dir=' + tableOptions.orderDir +
                '&na=' + tableOptions.filterParam.name +
                '&id=' + participantForm.trainingid +
                '&lo=' + tableOptions.filterParam.loginid +
                '&gl=' + tableOptions.filterParam.geo_level +
                '&glid=' + tableOptions.filterParam.geo_level_id
            )
                .then(response => {
                    var d = response && response.data;
                    tableData.value = Array.isArray(d && d.data) ? d.data : [];
                    tableOptions.total = (d && d.recordsTotal) || 0;
                    if (tableOptions.currentPage == 1) paginationDefault();
                    overlay.hide();
                })
                .catch(error => {
                    overlay.hide();
                    alert.Error('ERROR', safeMessage(error));
                });
        }

        const selectAll = () => { for (var i = 0; i < tableData.value.length; i++) tableData.value[i].pick = true; };
        const uncheckAll = () => { for (var i = 0; i < tableData.value.length; i++) tableData.value[i].pick = false; };
        const selectToggle = () => {
            if (checkToggle.value === false) { selectAll(); checkToggle.value = true; }
            else                              { uncheckAll(); checkToggle.value = false; }
        }
        const checkedBg = (pickOne) => { return pickOne != '' ? 'bg-select' : ''; };
        const toggleFilter = () => {
            if (filterState.value === false) filters.value = false;
            return (filterState.value = !filterState.value);
        }
        const selectedItems = () => { return tableData.value.filter(r => r.pick); };
        const selectedID = () => { return tableData.value.filter(r => r.pick).map(r => r.participant_id); };
        const selectedUserID = () => { return tableData.value.filter(r => r.pick).map(r => r.userid); };

        const paginationDefault = () => {
            tableOptions.pageLength = Math.ceil(tableOptions.total / tableOptions.perPage);
            tableOptions.limitStart = Math.ceil((tableOptions.currentPage - 1) * tableOptions.perPage);
            tableOptions.isNext = tableOptions.currentPage < tableOptions.pageLength;
            tableOptions.isPrev = tableOptions.currentPage > 1;
        }
        const nextPage = () => { tableOptions.currentPage += 1; paginationDefault(); loadTableData(); };
        const prevPage = () => { tableOptions.currentPage -= 1; paginationDefault(); loadTableData(); };
        const currentPage = () => {
            paginationDefault();
            if (tableOptions.currentPage < 1)                            alert.Error('ERROR', "The Page requested doesn't exist");
            else if (tableOptions.currentPage > tableOptions.pageLength) alert.Error('ERROR', "The Page requested doesn't exist");
            else                                                         loadTableData();
        }
        const changePerPage = (val) => { tableOptions.perPage = val; paginationDefault(); loadTableData(); };
        const sort = (col) => {
            if (tableOptions.orderField === col) tableOptions.orderDir = tableOptions.orderDir === 'asc' ? 'desc' : 'asc';
            else                                  tableOptions.orderField = col;
            paginationDefault();
            loadTableData();
        }
        const applyFilter = () => {
            var checkFill = 0;
            checkFill += tableOptions.filterParam.loginid    != '' ? 1 : 0;
            checkFill += tableOptions.filterParam.name       != '' ? 1 : 0;
            checkFill += tableOptions.filterParam.geo_level  != '' ? 1 : 0;
            checkFill += tableOptions.filterParam.geo_level_id != '' ? 1 : 0;
            if (checkFill > 0) {
                toggleFilter();
                filters.value = true;
                paginationDefault();
                loadTableData();
            } else {
                alert.Error('ERROR', 'Invalid required data');
            }
        }
        const removeSingleFilter = (column_name) => {
            tableOptions.filterParam[column_name] = '';
            if (column_name == 'geo_string') {
                tableOptions.filterParam.geo_level = '';
                tableOptions.filterParam.geo_level_id = '';
            }
            var g = 0;
            for (var k in tableOptions.filterParam) {
                if (tableOptions.filterParam[k] != '') g++;
            }
            if (g == 0) filters.value = false;
            paginationDefault();
            loadTableData();
        }
        const clearAllFilter = () => {
            filters.value = false;
            tableOptions.filterParam.loginid = '';
            tableOptions.filterParam.name = '';
            paginationDefault();
            loadTableData();
        }

        const checkIfEmpty = (data) => { return data === null || data === '' ? 'Nil' : data; };

        const showParticipantModal = () => {
            resetForm();
            addBulkParticipantModal.value = true;
            participantBtn.value = 'Create';
            $('#addParticipant').modal('show');
        }
        const getGroupData = () => {
            overlay.show();
            axios.get(common.DataService + '?qid=010')
                .then(response => {
                    groupData.value = (response.data && response.data.data) || [];
                    overlay.hide();
                })
                .catch(error => {
                    overlay.hide();
                    alert.Error('ERROR', safeMessage(error));
                });
        }
        const hideParticipantModal = () => {
            resetForm();
            addBulkParticipantModal.value = false;
            participantBtn.value = '';
            participantForm.group_name = '';
            $('#addParticipant').modal('hide');
        }
        const resetForm = () => {
            participantBtn.value = '';
            participantForm.group_name = '';
            participantForm.session_id = '';
            overlay.hide();
        }
        const refreshData = () => { loadTableData(); };

        const removeParticipant = (action, login_id) => {
            var u = common.DataService;
            var participantData;
            if (action != 'all') {
                participantData = {
                    trainingid: participantForm.trainingid,
                    selectedid: [action],
                };
                $.confirm({
                    title: 'WARNING!',
                    content: 'Are you sure you want to Remove <b>' + login_id + '</b> from the Activity?',
                    buttons: {
                        delete: {
                            text: 'Remove',
                            btnClass: 'btn btn-danger mr-1 text-capitalize',
                            action: () => {
                                axios.post(u + '?qid=109', JSON.stringify(participantData))
                                    .then(response => {
                                        if (response.data.result_code == '200') {
                                            loadTableData();
                                            bus.emit('g-event-update', {});
                                            alert.Success('Success', response.data.message);
                                            overlay.hide();
                                        } else {
                                            overlay.hide();
                                            alert.Error('Error', response.data.message);
                                        }
                                    })
                                    .catch(error => {
                                        alert.Error('ERROR', safeMessage(error));
                                        overlay.hide();
                                    });
                            },
                        },
                        cancel: () => { overlay.hide(); },
                    },
                });
            } else {
                var ids = selectedID();
                participantData = { trainingid: participantForm.trainingid, selectedid: ids };
                if (ids.length > 0) {
                    $.confirm({
                        title: 'WARNING!',
                        content: 'Are you sure you want to Remove <b>' + ids.length + '</b> participants from the Activity?',
                        buttons: {
                            delete: {
                                text: 'Remove',
                                btnClass: 'btn btn-danger mr-1 text-capitalize',
                                action: () => {
                                    axios.post(u + '?qid=109', JSON.stringify(participantData))
                                        .then(response => {
                                            if (response.data.result_code == '200') {
                                                bus.emit('g-event-update', {});
                                                loadTableData();
                                                alert.Success('Success', response.data.message);
                                                overlay.hide();
                                            } else {
                                                overlay.hide();
                                                alert.Error('Error', response.data.message);
                                            }
                                        })
                                        .catch(error => {
                                            alert.Error('ERROR', safeMessage(error));
                                            overlay.hide();
                                        });
                                },
                            },
                            cancel: () => { overlay.hide(); },
                        },
                    });
                } else {
                    alert.Error('Error', 'No Participant Selected');
                    overlay.hide();
                }
            }
        }
        const addParticipant = () => {
            var u = common.DataService;
            $.confirm({
                title: 'WARNING!',
                content: 'Are you sure you want to Add all the user in <b>' + participantForm.group_name + '</b> to the Activity?',
                buttons: {
                    delete: {
                        text: 'Add Group',
                        btnClass: 'btn btn-danger mr-1 text-capitalize',
                        action: () => {
                            axios.post(u + '?qid=110', JSON.stringify(participantForm))
                                .then(response => {
                                    if (response.data.result_code == '200') {
                                        resetForm();
                                        addBulkParticipantModal.value = false;
                                        $('#addParticipant').modal('hide');
                                        bus.emit('g-event-update', {});
                                        loadTableData();
                                        alert.Success('Success', response.data.message);
                                        overlay.hide();
                                    } else {
                                        overlay.hide();
                                        alert.Error('Error', response.data.message);
                                    }
                                })
                                .catch(error => {
                                    alert.Error('ERROR', safeMessage(error));
                                    overlay.hide();
                                });
                        },
                    },
                    cancel: () => {
                        resetForm();
                        addBulkParticipantModal.value = false;
                        $('#addParticipant').modal('hide');
                        overlay.hide();
                    },
                },
            });
        }

        const exportParticipant = async () => {
            var t_id = participantForm.trainingid;
            var veriUrl = 'qid=114&tid=' + t_id;
            var dlString = 'qid=101&id=' + t_id;
            var filename = 'Participant List (Activity ID - ' + String(t_id).padStart(3, '0') + ')';
            overlay.show();

            var count = await new Promise(resolve => {
                $.ajax({
                    url: common.DataService, type: 'POST', data: veriUrl, dataType: 'json',
                    success: (data) => { resolve(data.total); },
                });
            });
            var downloadMax = (window.common && window.common.ExportDownloadLimit) || 25000;
            if (parseInt(count) > downloadMax) {
                alert.Error('Download Error', 'Unable to download data because it has exceeded download limit, download limit is ' + downloadMax);
            } else if (parseInt(count) == 0) {
                alert.Error('Download Error', 'No data found');
            } else {
                alert.Info('DOWNLOADING...', 'Downloading ' + count + ' record(s)');
                var outcome = await new Promise(resolve => {
                    $.ajax({
                        url: common.ExportService, type: 'POST', data: dlString,
                        success: (data) => { resolve(data); },
                    });
                });
                var exportData = JSON.parse(outcome);
                if (window.Jhxlsx && typeof window.Jhxlsx.export === 'function') {
                    window.Jhxlsx.export(exportData, { fileName: filename });
                }
            }
            overlay.hide();
        }

        const downloadBadge = (userid) => {
            var u = common.BadgeService;
            overlay.show();
            window.open(u + '?qid=002&e=' + userid, '_parent');
            overlay.hide();
        }
        const downloadBadges = () => {
            var u = common.BadgeService;
            overlay.show();
            if (parseInt(selectedUserID().length) > 0) {
                window.open(u + '?qid=003&e=' + selectedUserID(), '_parent');
            } else {
                alert.Error('Badge Download Failed', 'No user selected');
            }
            overlay.hide();
        }

        const getGeoLocation = () => {
            overlay.show();
            axios.get(common.DataService + '?qid=gen009')
                .then(response => {
                    geoData.value = (response.data && response.data.data) || [];
                    overlay.hide();
                })
                .catch(error => {
                    overlay.hide();
                    alert.Error('ERROR', safeMessage(error));
                });
        }
        const setLocation = (select_index) => {
            var i = select_index ? select_index : 0;
            var row = geoData.value[i];
            if (!row) return;
            tableOptions.filterParam.geo_level = row.geo_level;
            tableOptions.filterParam.geo_level_id = row.geo_level_id;
            tableOptions.filterParam.geo_string = row.geo_string;
        }

        onMounted(() => {
            bus.on('g-event-goto-page', gotoPageHandler);
            getGeoLocation();
            getGroupData();

            // jQuery select2 init for the filter geo dropdown.
            try {
                var select = $('.select2');
                select.each(function () {
                    var $this = $(this);
                    $this.wrap('<div class="position-relative"></div>');
                    $this.select2({
                        dropdownAutoWidth: true,
                        width: '100%',
                        dropdownParent: $this.parent(),
                    }).on('change', function () { setLocation(this.value); });
                });
                $('.select2-selection__arrow').html('<i class="feather icon-chevron-down"></i>');
            } catch (e) {}
        });
        onBeforeUnmount(() => {
            bus.off('g-event-goto-page', gotoPageHandler);
        });

        return {
            tableData, groupData, geoData, addBulkParticipantModal, participantBtn,
            participantForm, permission, checkToggle, filterState, filters,
            url, tableOptions,
            gotoPageHandler, goToTrainingList, loadTableData,
            selectAll, uncheckAll, selectToggle, checkedBg, toggleFilter,
            selectedItems, selectedID, selectedUserID,
            nextPage, prevPage, currentPage, paginationDefault, changePerPage,
            sort, applyFilter, removeSingleFilter, clearAllFilter,
            checkIfEmpty, showParticipantModal, getGroupData, hideParticipantModal,
            resetForm, refreshData,
            removeParticipant, addParticipant, exportParticipant,
            downloadBadge, downloadBadges, getGeoLocation, setLocation,
            capitalize: fmtUtils.capitalize,
            formatNumber: fmtUtils.formatNumber,
            displayDate: fmtUtils.displayDate,
        };
    },
    template: `
        <div class="row">
            <div class="col-md-8 col-sm-12 col-12 mb-0">
                <h2 class="content-header-title header-txt float-left mb-0">Activity Mgmt.</h2>
                <div class="breadcrumb-wrapper">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../users">Home</a></li>
                        <li class="breadcrumb-item"><a href="javascript:void(0);" @click="goToTrainingList()">Activity List</a></li>
                        <li class="breadcrumb-item active">Participants List</li>
                    </ol>
                </div>
            </div>

            <div class="col-md-4 col-sm-12 col-12 text-md-right text-right d-md-block">
                <div class="btn-group mr-1">
                    <button v-if="permission.permission_value >= 2" type="button" @click="showParticipantModal()" data-toggle="tooltip" data-placement="top" title="Create Participant" data-target="#addParticipant" class="btn btn-outline-primary round"><i data-feather='plus'></i></button>
                    <button type="button" class="btn btn-outline-primary round searchBtn" @click="refreshData()" data-placement="top" title="Refresh" data-toggle="tooltip">
                        <i class="feather icon-refresh-cw"></i>
                    </button>
                    <button type="button" class="btn btn-outline-primary round searchBtn" @click="toggleFilter()" data-toggle="tooltip" data-placement="top" title="Filter">
                        <i class="feather" :class="filterState ? 'icon-x' : 'icon-filter'"></i>
                    </button>
                    <button class="btn btn-outline-primary round dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Actions</button>
                    <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                        <a v-if="permission.permission_value == 3" class="dropdown-item" href="javascript:void(0);" @click="removeParticipant('all', '')">Remove Participant</a>
                        <a class="dropdown-item" href="javascript:void(0)" @click="downloadBadges()">Download Badge</a>
                        <a v-if="permission.permission_value >= 2" class="dropdown-item" href="javascript:void(0);" @click="exportParticipant()">Export Participant</a>
                    </div>
                </div>
            </div>

            <div class="col-12" v-if="filters">
                <div class="col-12 filter-bar">
                    <template v-for="(filterParam, i) in tableOptions.filterParam" :key="i">
                        <span class="badge badge-dark filter-box" v-if="String(filterParam).length > 0 && i != 'geo_level' && i != 'geo_level_id'" @click="removeSingleFilter(i)">
                            {{ capitalize(i) }}: {{ filterParam }} <i class="feather icon-x"></i>
                        </span>
                    </template>
                    <a href="#" class="float-right clear-filter" @click="clearAllFilter()">Clear</a>
                </div>
            </div>

            <div class="col-12 mt-1">
                <div class="card custom-select-down">
                    <div class="card-body py-1" v-show="filterState">
                        <form id="filterForm">
                            <div class="row">
                                <div class="col-12 col-sm-6 col-md-6 col-lg-3"><div class="form-group"><label>Login ID</label><input type="text" v-model="tableOptions.filterParam.loginid" class="form-control training-id" id="loginid-id" placeholder="Login ID" name="loginid" /></div></div>
                                <div class="col-12 col-sm-6 col-md-6 col-lg-3"><div class="form-group"><label>Fullname</label><input type="text" id="title" v-model="tableOptions.filterParam.name" class="form-control fullname" placeholder="Fullname" name="fullname" /></div></div>
                                <div class="col-12 col-sm-6 col-md-6 col-lg-3">
                                    <div class="form-group">
                                        <label>Geo Location</label>
                                        <select class="select2 form-control" @change="setLocation()" placeholder="Geo Location">
                                            <option value="">Select Geo Location</option>
                                            <option v-for="(g, i) in geoData" :key="i" :value="i">{{ g.geo_string }}</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-12 col-sm-6 col-md-6 col-lg-3"><div class="form-group mt-2 text-right"><button type="button" class="btn btn-md btn-primary" @click="applyFilter()">Apply Filters</button></div></div>
                            </div>
                        </form>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th width="60px" style="padding-right: 2px !important;">
                                        <div class="custom-control custom-checkbox checkbox">
                                            <input type="checkbox" class="custom-control-input" @change="selectToggle()" id="all-check1" />
                                            <label class="custom-control-label" for="all-check1"></label>
                                        </div>
                                    </th>
                                    <th @click="sort(1)">Login ID</th>
                                    <th @click="sort(2)">Fullname</th>
                                    <th>Username</th>
                                    <th>User Group</th>
                                    <th @click="sort(9)">Geo Location</th>
                                    <th @click="sort(10)">Status</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(g, i) in tableData" :key="g.participant_id || i" :class="checkedBg(g.pick)">
                                    <td style="padding-right: 2px !important;">
                                        <div class="custom-control custom-checkbox checkbox">
                                            <input type="checkbox" class="custom-control-input" :id="g.participant_id" v-model="g.pick" />
                                            <label class="custom-control-label" :for="g.participant_id"></label>
                                        </div>
                                    </td>
                                    <td>{{ g.loginid }}</td>
                                    <td>{{ g.first }} {{ g.middle }} {{ checkIfEmpty(g.last) }}</td>
                                    <td>{{ g.username }}</td>
                                    <td>{{ g.user_group }}</td>
                                    <td>{{ g.geo_string }}</td>
                                    <td><span class="badge rounded-pill font-small-1" :class="g.active == 1 ? 'bg-success' : 'bg-danger'">{{ g.active == 1 ? 'Active' : 'Inactive' }}</span></td>
                                    <td>
                                        <div class="dropdown">
                                            <button type="button" class="btn btn-sm dropdown-toggle hide-arrow" data-toggle="dropdown">
                                                <span class="feather icon-more-vertical"></span>
                                            </button>
                                            <div class="dropdown-menu">
                                                <a v-if="permission.permission_value == 3" class="dropdown-item" href="javascript:void(0);" @click="removeParticipant(g.participant_id, g.loginid)">
                                                    <span class="feather icon-delete mr-50"></span> Remove
                                                </a>
                                                <a class="dropdown-item" href="javascript:void(0);" @click="downloadBadge(g.userid)">
                                                    <i class="feather icon-download mr-50"></i> Download Badge
                                                </a>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="tableData.length === 0"><td class="text-center pt-2" colspan="8"><small>No Participant Added</small></td></tr>
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
                                        <button type="button" @click="prevPage()" class="btn btn-sm btn-primary round btn-page-block-overlay" :disabled="!tableOptions.isPrev"><i data-feather='chevron-left'></i> Prev</button>
                                        <input @keyup.enter="currentPage()" class="btn btn-page-block-overlay btn-sm btn-outline-primary pagination-input" type="number" v-model.number="tableOptions.currentPage" :max="tableOptions.pageLength" />
                                        <button class="btn btn-outline-primary btn-page-block-overlay border-l-0">
                                            <small class="form-text text-primary"> of {{ tableOptions.pageLength }}</small>
                                        </button>
                                        <button type="button" @click="nextPage()" class="btn btn-sm btn-primary round" :disabled="!tableOptions.isNext">Next <i data-feather='chevron-right'></i></button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mb-50"></div>
                </div>
            </div>

            <!-- Add User Group to Training -->
            <div class="modal fade text-left" id="addParticipant" tabindex="-1" role="dialog" aria-labelledby="myModalLabel33" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h4 class="modal-title" id="myModalLabel33">Add Group to Activity</h4>
                            <button type="button" class="close" @click="hideParticipantModal()" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <form method="POST" @submit.stop.prevent="addParticipant()">
                            <div class="modal-body correct-font custom-select-down">
                                <div class="alert alert-warning p-1 alert-dismissible">
                                    <p>Kindly Note that adding a user group as participants means you are adding all the users in the user group as part of the Activity</p>
                                </div>
                                <label>Choose User Group:</label>
                                <div class="form-group">
                                    <select v-model="participantForm.group_name" class="form-control role select1">
                                        <option value="">Select a User group to Add</option>
                                        <option v-for="(g, i) in groupData" :value="g.user_group" :key="i">{{ g.user_group }}</option>
                                    </select>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <div class="text-center">
                                    <button type="submit" class="btn btn-primary mt-2 me-1 mr-1 waves-effect waves-float waves-light">Add Participants</button>
                                    <button type="reset" @click="hideParticipantModal()" class="btn btn-outline-secondary mt-2 waves-effect" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close">Discard</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    `,
};

/* ------------------------------------------------------------------ */
/* attendance_list                                                      */
/* ------------------------------------------------------------------ */
const AttendanceList = {
    setup() {
        const fmtUtils = useFormat();

        const tableData = ref([]);
        const geoData = ref([]);
        const sessionid = ref('');
        const trainingid = ref('');
        const title = ref('');
        const permission = ref(
            (typeof getPermission === 'function')
                ? (getPermission(typeof per !== 'undefined' ? per : null, 'activity') || { permission_value: 0 })
                : { permission_value: 0 }
        );
        const searchTxt = ref('');
        const checkToggle = ref(false);
        const filterState = ref(false);
        const filters = ref(false);
        const url = ref(window.common && window.common.TableService);
        const tableOptions = reactive({
            total: 1, pageLength: 1, perPage: 10, currentPage: 1,
            orderDir: 'desc', orderField: 0, limitStart: 0,
            isNext: false, isPrev: false,
            aLength: [10, 20, 50, 100, 200, 300],
            filterParam: { geo_level: '', geo_level_id: '' },
        });

        const gotoPageHandler = (data) => {
            trainingid.value = data.trainingid;
            sessionid.value = data.sessionid;
            title.value = data.title;
            loadTableData();
        }
        const goToTrainingList = () => {
            bus.emit('g-event-goto-page', { page: 'training', trainingid: trainingid.value });
        }
        const goToSessionLists = (tid) => {
            bus.emit('g-event-goto-page', { trainingid: tid, page: 'session', sessionid: '' });
        }

        const filterEarliestInLatestOut = (data) => {
            var result = {};
            data.forEach(entry => {
                var key = entry.userid;
                if (!result[key]) result[key] = {};
                var time = new Date(entry.collected).getTime();
                if (entry.at_type === 'clock-in') {
                    if (!result[key]['clock-in'] || time < new Date(result[key]['clock-in'].collected).getTime()) {
                        result[key]['clock-in'] = entry;
                    }
                }
                if (entry.at_type === 'clock-out') {
                    if (!result[key]['clock-out'] || time > new Date(result[key]['clock-out'].collected).getTime()) {
                        result[key]['clock-out'] = entry;
                    }
                }
            });
            return Object.values(result).flatMap(types => Object.values(types));
        }

        const loadTableData = () => {
            overlay.show();
            var u = common.TableService;
            axios.get(
                u +
                '?qid=103&draw=' + tableOptions.currentPage +
                '&order_column=' + tableOptions.orderField +
                '&length=' + tableOptions.perPage +
                '&start=' + tableOptions.limitStart +
                '&order_dir=' + tableOptions.orderDir +
                '&se=' + sessionid.value +
                '&gl=' + tableOptions.filterParam.geo_level +
                '&glid=' + tableOptions.filterParam.geo_level_id
            )
                .then(response => {
                    var d = response && response.data;
                    tableData.value = filterEarliestInLatestOut(Array.isArray(d && d.data) ? d.data : []);
                    tableOptions.total = (d && d.recordsTotal) || 0;
                    if (tableOptions.currentPage == 1) paginationDefault();
                    overlay.hide();
                })
                .catch(error => {
                    overlay.hide();
                    alert.Error('ERROR', safeMessage(error));
                });
        }

        const refreshData = () => { loadTableData(); };
        const nextPage = () => { tableOptions.currentPage += 1; paginationDefault(); loadTableData(); };
        const prevPage = () => { tableOptions.currentPage -= 1; paginationDefault(); loadTableData(); };
        const currentPage = () => {
            paginationDefault();
            if (tableOptions.currentPage < 1)                            alert.Error('ERROR', "The Page requested doesn't exist");
            else if (tableOptions.currentPage > tableOptions.pageLength) alert.Error('ERROR', "The Page requested doesn't exist");
            else                                                         loadTableData();
        }
        const paginationDefault = () => {
            tableOptions.pageLength = Math.ceil(tableOptions.total / tableOptions.perPage);
            tableOptions.limitStart = Math.ceil((tableOptions.currentPage - 1) * tableOptions.perPage);
            tableOptions.isNext = tableOptions.currentPage < tableOptions.pageLength;
            tableOptions.isPrev = tableOptions.currentPage > 1;
        }
        const changePerPage = (val) => {
            var maxPerPage = Math.ceil(tableOptions.total / val);
            if (maxPerPage < tableOptions.currentPage) tableOptions.currentPage = maxPerPage;
            tableOptions.perPage = val;
            paginationDefault();
            loadTableData();
        }
        const sort = (col) => {
            if (tableOptions.orderField === col) tableOptions.orderDir = tableOptions.orderDir === 'asc' ? 'desc' : 'asc';
            else                                  tableOptions.orderField = col;
            paginationDefault();
            loadTableData();
        }
        const applyFilter = () => {
            var checkFill = tableOptions.filterParam.geo_level_id != '' ? 1 : 0;
            if (checkFill > 0) {
                toggleFilter();
                filters.value = true;
                paginationDefault();
                loadTableData();
            } else {
                alert.Error('ERROR', 'Invalid required data');
            }
        }
        const removeSingleFilter = (column_name) => {
            tableOptions.filterParam[column_name] = '';
            var g = 0;
            for (var k in tableOptions.filterParam) {
                if (tableOptions.filterParam[k] != '') g++;
            }
            if (g == 0) filters.value = false;
            paginationDefault();
            loadTableData();
        }
        const clearAllFilter = () => {
            filters.value = false;
            try { $('.select2').val('').trigger('change'); } catch (e) {}
            tableOptions.filterParam.geo_level = '';
            tableOptions.filterParam.geo_level_id = '';
            paginationDefault();
            loadTableData();
        }
        const toggleFilter = () => {
            if (filterState.value === false) filters.value = false;
            return (filterState.value = !filterState.value);
        }

        const deleteSession = (session_id) => {
            var u = common.DataService;
            $.confirm({
                title: 'WARNING!',
                content: 'Are you sure you want to Delete a Activity Session?',
                buttons: {
                    delete: {
                        text: 'Delete',
                        btnClass: 'btn btn-danger mr-1 text-capitalize',
                        action: () => {
                            axios.post(u + '?qid=107&e=' + session_id)
                                .then(response => {
                                    if (response.data.result_code == '200') {
                                        $('#addSession').modal('hide');
                                        loadTableData();
                                        alert.Success('Success', response.data.message);
                                        overlay.hide();
                                    } else {
                                        overlay.hide();
                                        alert.Error('Error', response.data.message);
                                    }
                                })
                                .catch(error => {
                                    alert.Error('ERROR', safeMessage(error));
                                    overlay.hide();
                                });
                        },
                    },
                    cancel: () => {
                        $('#addSession').modal('hide');
                        overlay.hide();
                    },
                },
            });
        }

        const downlodAttendance = async (session_id, t) => {
            var sid = session_id;
            var veriUrl = 'qid=115&sid=' + sid + '&gl=' + tableOptions.filterParam.geo_level + '&glid=' + tableOptions.filterParam.geo_level_id;
            var dlString = 'qid=102&id=' + sid + '&gl=' + tableOptions.filterParam.geo_level + '&glid=' + tableOptions.filterParam.geo_level_id;
            var filename = t + ' Attendance - (' + sid + ')';
            overlay.show();

            var count = await new Promise(resolve => {
                $.ajax({
                    url: common.DataService, type: 'POST', data: veriUrl, dataType: 'json',
                    success: (data) => { resolve(data.total); },
                });
            });
            var downloadMax = (window.common && window.common.ExportDownloadLimit) || 25000;
            if (parseInt(count) > downloadMax) {
                alert.Error('Download Error', 'Unable to download data because it has exceeded download limit, download limit is ' + downloadMax);
            } else if (parseInt(count) == 0) {
                alert.Error('Download Error', 'No data found');
            } else {
                alert.Info('DOWNLOADING...', 'Downloading ' + count + ' record(s)');
                var outcome = await new Promise(resolve => {
                    $.ajax({
                        url: common.ExportService, type: 'POST', data: dlString,
                        success: (data) => { resolve(data); },
                    });
                });
                var exportData = JSON.parse(outcome);
                if (window.Jhxlsx && typeof window.Jhxlsx.export === 'function') {
                    window.Jhxlsx.export(exportData, { fileName: filename });
                }
            }
            overlay.hide();
        }

        const searchAttendance = () => {
            var search = searchTxt.value;
            var table = tableData.value.filter(obj => {
                var flag = false;
                Object.values(obj).forEach(val => {
                    if (String(val).indexOf(search) > -1) flag = true;
                });
                if (flag) return obj;
            });
            tableData.value = table;
        }
        const checkIfEmpty = () => {
            if (searchTxt.value.length < 1) refreshData();
        }

        const getGeoLocation = () => {
            overlay.show();
            axios.get(common.DataService + '?qid=gen009')
                .then(response => {
                    geoData.value = (response.data && response.data.data) || [];
                    overlay.hide();
                })
                .catch(error => {
                    overlay.hide();
                    alert.Error('ERROR', safeMessage(error));
                });
        }
        const setLocation = (select_index) => {
            var i = select_index || 0;
            var row = geoData.value[i];
            if (!row) return;
            tableOptions.filterParam.geo_level = row.geo_level;
            tableOptions.filterParam.geo_level_id = row.geo_level_id;
        }

        onMounted(() => {
            getGeoLocation();
            try {
                var select = $('.select2');
                select.each(function () {
                    var $this = $(this);
                    $this.wrap('<div class="position-relative"></div>');
                    $this.select2({
                        dropdownAutoWidth: true,
                        width: '100%',
                        dropdownParent: $this.parent(),
                    }).on('change', function () { setLocation(this.value); });
                });
                $('.select2-selection__arrow').html('<i class="feather icon-chevron-down"></i>');
            } catch (e) {}
            bus.on('g-event-goto-page', gotoPageHandler);
            loadTableData();
        });
        onBeforeUnmount(() => {
            bus.off('g-event-goto-page', gotoPageHandler);
        });

        return {
            tableData, geoData, sessionid, trainingid, title, permission,
            searchTxt, checkToggle, filterState, filters, url, tableOptions,
            gotoPageHandler, goToTrainingList, goToSessionLists,
            filterEarliestInLatestOut, loadTableData, refreshData,
            nextPage, prevPage, currentPage, paginationDefault, changePerPage,
            sort, applyFilter, removeSingleFilter, clearAllFilter, toggleFilter,
            deleteSession, downlodAttendance, searchAttendance, checkIfEmpty,
            getGeoLocation, setLocation,
            capitalize: fmtUtils.capitalize,
            formatNumber: fmtUtils.formatNumber,
            displayDate: fmtUtils.displayDate,
        };
    },
    template: `
        <div class="row">
            <div class="col-md-8 col-sm-12 col-12 mb-0">
                <h2 class="content-header-title header-txt float-left mb-0">Activity Mgmt.</h2>
                <div class="breadcrumb-wrapper">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../training">Home</a></li>
                        <li class="breadcrumb-item"><a href="javascript:void(0);" @click="goToTrainingList()">Activity List</a></li>
                        <li class="breadcrumb-item"><a href="javascript:void(0);" @click="goToSessionLists(trainingid)">Sessions</a></li>
                        <li class="breadcrumb-item active">{{ title }} Attendance</li>
                    </ol>
                </div>
            </div>

            <div class="col-md-4 col-sm-12 col-12 text-md-right text-right text-md-right d-md-block">
                <div class="btn-group mr-1">
                    <button type="button" class="btn btn-outline-primary round searchBtn" @click="toggleFilter()" data-toggle="tooltip" data-placement="top" title="Filter">
                        <i class="feather" :class="filterState ? 'icon-x' : 'icon-filter'"></i>
                    </button>
                    <button type="button" class="btn btn-outline-primary round searchBtn" @click="refreshData()">
                        <i class="feather icon-refresh-cw"></i>
                    </button>
                    <button v-if="permission.permission_value >= 2" class="btn-icon btn btn-primary round" type="button" @click="downlodAttendance(sessionid, title)">Download <i data-feather='download-cloud'></i></button>
                </div>
            </div>

            <div class="col-12" v-if="filters">
                <div class="col-12 filter-bar">
                    <template v-for="(filterParam, i) in tableOptions.filterParam" :key="i">
                        <span class="badge badge-dark filter-box" v-if="String(filterParam).length > 0 && i != 'geo_level_id'" @click="clearAllFilter()">
                            {{ capitalize(i) }}: {{ filterParam }} <i class="feather icon-x"></i>
                        </span>
                    </template>
                    <a href="#" class="float-right clear-filter" @click="clearAllFilter()">Clear</a>
                </div>
            </div>

            <div class="col-12 mt-1">
                <div class="card custom-select-down">
                    <div class="card-body py-1" v-show="filterState">
                        <form id="filterForm">
                            <div class="row">
                                <div class="col-12 col-sm-6 col-md-8 col-lg-8">
                                    <div class="form-group">
                                        <label>Geo Location</label>
                                        <select class="select2 form-control" @change="setLocation()" placeholder="Geo Location">
                                            <option value="">Select Geo Location</option>
                                            <option v-for="(g, i) in geoData" :key="i" :value="i">{{ g.geo_string }}</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-12 col-sm-6 col-md-4 col-lg-4">
                                    <div class="form-group mt-2 text-right">
                                        <button type="button" class="btn btn-md btn-primary" @click="applyFilter()">Apply Filters</button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th width="60px">#</th>
                                    <th @click="sort(1)">Login ID</th>
                                    <th @click="sort(8)">Fullname</th>
                                    <th @click="sort(5)">Phone No</th>
                                    <th @click="sort(4)">Attendant Type</th>
                                    <th @click="sort(12)">Date & Time</th>
                                    <th>Bio Auth</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(g, i) in tableData" :key="g.attendant_id || g.userid + '-' + g.at_type">
                                    <td>{{ i + 1 }}</td>
                                    <td>{{ g.loginid }}</td>
                                    <td>{{ g.fullname }}</td>
                                    <td>{{ g.phone }}</td>
                                    <td>{{ g.at_type }}</td>
                                    <td>{{ g.collected }}</td>
                                    <td>{{ g.bio_auth }}</td>
                                </tr>
                                <tr v-if="tableData.length === 0"><td class="text-center pt-2" colspan="7"><small>No Attendance Taken</small></td></tr>
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
                                        <button type="button" @click="prevPage()" class="btn btn-sm btn-primary round btn-page-block-overlay" :disabled="!tableOptions.isPrev"><i data-feather='chevron-left'></i> Prev</button>
                                        <input @keyup.enter="currentPage()" class="btn btn-page-block-overlay btn-sm btn-outline-primary pagination-input" type="number" v-model.number="tableOptions.currentPage" :max="tableOptions.pageLength" />
                                        <button class="btn btn-outline-primary btn-page-block-overlay border-l-0">
                                            <small class="form-text text-primary"> of {{ tableOptions.pageLength }}</small>
                                        </button>
                                        <button type="button" @click="nextPage()" class="btn btn-sm btn-primary round" :disabled="!tableOptions.isNext">Next <i data-feather='chevron-right'></i></button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mb-50"></div>
                </div>
            </div>
        </div>
    `,
};

/* ------------------------------------------------------------------ */
/* Mount                                                              */
/* ------------------------------------------------------------------ */
useApp({ template: `<div><page-body/></div>` })
    .component('page-body', PageBody)
    .component('training_list', TrainingList)
    .component('training_session', TrainingSession)
    .component('participant_list', ParticipantList)
    .component('attendance_list', AttendanceList)
    .mount('#app');
