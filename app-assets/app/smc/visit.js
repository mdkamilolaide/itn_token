/**
 * SMC / Visit — Vue 3 Composition API in place.
 * Two components — page-body and visit_list.
 *
 * SMC visit-period CRUD:
 *   - qid=1004 list, qid=1000 create, qid=1001 update, qid=1002 delete,
 *     qid=1003 activate/deactivate.
 */

const { ref, reactive, onMounted, onBeforeUnmount } = Vue;
const { useApp, useFormat, bus, safeMessage } = window.utils;

const PageBody = {
    setup() {
        const page = ref('visit');
        const gotoPageHandler = (data) => { page.value = data && data.page; };
        onMounted(() => { bus.on('g-event-goto-page', gotoPageHandler); });
        onBeforeUnmount(() => { bus.off('g-event-goto-page', gotoPageHandler); });
        return { page };
    },
    template: `
        <div>
            <div class="content-body">
                <div v-show="page == 'visit'"><visit_list/></div>
            </div>
        </div>
    `,
};

const VisitList = {
    setup() {
        const fmtUtils = useFormat();

        const tableData = ref([]);
        const url = ref(window.common && window.common.TableService);
        const permission = ref(
            (typeof getPermission === 'function')
                ? (getPermission(typeof per !== 'undefined' ? per : null, 'smc') || { permission_value: 0 })
                : { permission_value: 0 }
        );
        const errors = ref([]);
        const visitBtn = ref('');
        const addVisitModal = ref(false);
        const visitForm = reactive({
            period_id: '', period_title: '', start_date: '', end_date: '', period_pos: '',
        });
        let startFp = null;
        let endFp = null;

        const loadTableData = () => {
            overlay.show();
            axios.get(common.DataService + '?qid=1004')
                .then(response => {
                    tableData.value = (response.data && response.data.data) || [];
                    overlay.hide();
                })
                .catch(error => {
                    overlay.hide();
                    alert.Error('ERROR', safeMessage(error));
                });
        }
        const displayMonthDay = (d) => {
            var date = new Date(d);
            return date.toLocaleString('en-us', { year: 'numeric', month: 'long', day: 'numeric', hour12: true });
        }

        const deActivateVisit = (period_id, status, period_pos) => {
            var title = (tableData.value[period_pos] || {}).title || '';
            var btn_class, response_txt, message, bnt_text;
            if (status == '1') {
                message = 'Are you sure you want to Deactivate the Visit with Title: <b>' + title + '</b>?';
                bnt_text = 'Deactivate';
                btn_class = ' btn-danger ';
                response_txt = 'Visit with Title: <b>' + title + '</b> Successfully Deactivated';
            } else {
                message = 'Are you sure you want to Activate Visit with Title: <b>' + title + '</b>?';
                bnt_text = 'Activate';
                btn_class = ' btn-success ';
                response_txt = 'Visit with Title: <b>' + title + '</b> Successfully Activated';
            }
            $.confirm({
                title: 'WARNING!', content: message,
                buttons: {
                    delete: {
                        text: bnt_text, btnClass: 'btn mr-1' + btn_class,
                        action: () => {
                            axios.post(common.DataService + '?qid=1003&period_id=' + period_id)
                                .then(response => {
                                    overlay.hide();
                                    if (response.data.result_code == '200') {
                                        loadTableData();
                                        alert.Success('SUCCESS', response_txt);
                                    } else {
                                        alert.Error('ERROR', 'Unable to De/Activate Visit with Title: <b>' + title + '</b>');
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

        const showAddVisitModal = () => {
            resetVisitForm();
            $('#addNewVisit').modal('show');
            addVisitModal.value = true;
            visitBtn.value = 'Create';
        }
        const hideAddVisitModal = () => {
            resetVisitForm();
            addVisitModal.value = false;
            visitBtn.value = '';
            $('#addNewVisit').modal('hide');
        }
        const onSubmitCreateVisit = (action) => {
            if (action == 'Create') {
                overlay.show();
                axios.post(common.DataService + '?qid=1000', JSON.stringify(visitForm))
                    .then(response => {
                        if (response.data.result_code == '201') {
                            resetVisitForm();
                            addVisitModal.value = false;
                            $('#addNewVisit').modal('hide');
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
                updateVisit();
            }
        }
        const resetVisitForm = () => {
            try { if (startFp && startFp.clear) startFp.clear(); } catch (e) {}
            try { if (endFp && endFp.clear) endFp.clear(); } catch (e) {}
            visitBtn.value = '';
            visitForm.period_title = '';
            visitForm.start_date = '';
            visitForm.end_date = '';
            visitForm.period_pos = '';
            overlay.hide();
        }
        const refreshData = () => { loadTableData(); };
        const editVisit = (period_id, period_pos) => {
            overlay.show();
            addVisitModal.value = true;
            visitBtn.value = 'Update';
            visitForm.period_id = period_id;
            var row = tableData.value[period_pos] || {};
            visitForm.period_title = row.title;
            visitForm.start_date = row.start_date;
            visitForm.end_date = row.end_date;
            visitForm.period_pos = period_pos;
            overlay.hide();
        }
        const deleteVisit = (period_id, period_pos) => {
            var title = (tableData.value[period_pos] || {}).title || '';
            $.confirm({
                title: 'WARNING!',
                content: 'Are you sure you want to Delete the Visit with Title: <b>' + title + '</b>?',
                buttons: {
                    delete: {
                        text: 'Delete', btnClass: 'btn mr-1 btn-danger ',
                        action: () => {
                            axios.post(common.DataService + '?qid=1002&period_id=' + period_id)
                                .then(response => {
                                    overlay.hide();
                                    if (response.data.result_code == '200') {
                                        loadTableData();
                                        alert.Success('SUCCESS', 'Visit with Title: <b>' + title + '</b> Successfully Deleted');
                                    } else {
                                        alert.Error('ERROR', 'Unable to Delete Visit with Title <b>' + title + '</b>');
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
        const updateVisit = () => {
            var title = (tableData.value[visitForm.period_pos] || {}).title || '';
            $.confirm({
                title: 'WARNING!',
                content: 'Are you sure you want to Update a Visit with Title: <b>' + title + '</b>?',
                buttons: {
                    delete: {
                        text: 'Update', btnClass: 'btn btn-danger mr-1 text-capitalize',
                        action: () => {
                            axios.post(common.DataService + '?qid=1001', JSON.stringify(visitForm))
                                .then(response => {
                                    if (response.data.result_code == '200') {
                                        resetVisitForm();
                                        addVisitModal.value = false;
                                        $('#addNewVisit').modal('hide');
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
                        resetVisitForm();
                        addVisitModal.value = false;
                        $('#addNewVisit').modal('hide');
                        overlay.hide();
                    },
                },
            });
        }

        onMounted(() => {
            loadTableData();
            bus.on('g-event-update', loadTableData);
            try {
                if ($('#start-date').length && typeof $.fn.flatpickr === 'function') {
                    startFp = $('#start-date').flatpickr({
                        altInput: true, altFormat: 'F j, Y', dateFormat: 'Y-m-d', minDate: 'today',
                        onChange: (d, dateStr) => { visitForm.start_date = dateStr; },
                    });
                }
                if ($('#end-date').length && typeof $.fn.flatpickr === 'function') {
                    endFp = $('#end-date').flatpickr({
                        altInput: true, altFormat: 'F j, Y', dateFormat: 'Y-m-d', minDate: 'today',
                        onChange: (d, dateStr) => { visitForm.end_date = dateStr; },
                    });
                }
            } catch (e) {}
        });
        onBeforeUnmount(() => {
            bus.off('g-event-update', loadTableData);
            try { if (startFp && startFp.destroy) startFp.destroy(); } catch (e) {}
            try { if (endFp && endFp.destroy) endFp.destroy(); } catch (e) {}
        });

        return {
            tableData, url, permission, errors, visitBtn, addVisitModal, visitForm,
            loadTableData, displayMonthDay, deActivateVisit,
            showAddVisitModal, hideAddVisitModal, onSubmitCreateVisit,
            resetVisitForm, refreshData, editVisit, deleteVisit, updateVisit,
            capitalize: fmtUtils.capitalize,
            displayDate: fmtUtils.displayDate,
        };
    },
    template: `
        <div class="row" id="basic-table">
            <div class="col-md-8 col-sm-12 col-12 mb-0">
                <h2 class="content-header-title header-txt float-left mb-0">SMC</h2>
                <div class="breadcrumb-wrapper">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../smc">Home</a></li>
                        <li class="breadcrumb-item active">Visit Mgmt.</li>
                    </ol>
                </div>
            </div>

            <div class="col-md-4 col-sm-12 col-12 text-md-right text-right d-md-block">
                <div class="btn-group mr-1">
                    <button v-if="permission.permission_value > 2" type="button" @click="showAddVisitModal()" data-target="#addNewVisit" data-toggle="tooltip" data-placement="top" title="Create Visit" class="btn btn-outline-primary round"><i data-feather='plus'></i></button>
                    <button type="button" class="btn btn-outline-primary round searchBtn" @click="refreshData()" data-toggle="tooltip" data-placement="top" title="Refresh"><i class="feather icon-refresh-cw"></i></button>
                </div>
            </div>

            <div class="col-12 mt-1">
                <div class="card">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th width="60px" class="pr-0 pl-0">#</th>
                                    <th>Visit Title</th>
                                    <th>Start/End Date</th>
                                    <th>Created Date</th>
                                    <th>Updated Date</th>
                                    <th>Status</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(g, i) in tableData" :key="g.periodid || i">
                                    <td>{{ i + 1 }}</td>
                                    <td><a href="#" class="user_name text-truncate text-body">{{ g.title }}</a></td>
                                    <td>
                                        <div class="d-flex flex-column">
                                            <div>{{ displayMonthDay(g.start_date) }}</div>
                                            <div>{{ displayMonthDay(g.end_date) }}</div>
                                        </div>
                                    </td>
                                    <td>{{ displayDate(g.created) }}</td>
                                    <td>{{ displayDate(g.updated) }}</td>
                                    <td><span class="badge rounded-pill font-small-1" :class="g.active == 1 ? 'bg-success' : 'bg-danger'">{{ g.active == 1 ? 'Active' : 'Inactive' }}</span></td>
                                    <td>
                                        <div class="dropdown">
                                            <button type="button" class="btn btn-sm dropdown-toggle hide-arrow" data-toggle="dropdown"><i class="feather icon-more-vertical"></i></button>
                                            <div class="dropdown-menu">
                                                <a v-if="permission.permission_value == 3" class="dropdown-item" href="javascript:void(0);" @click="deActivateVisit(g.periodid, g.active, i)"><i class="feather" :class="g.active == '1' ? 'icon-x-circle' : 'icon-check-circle'"></i> {{ g.active == '1' ? 'Deactivate' : 'Activate' }}</a>
                                                <a v-if="permission.permission_value >= 2" class="dropdown-item" href="javascript:void(0);" data-toggle="modal" data-target="#addNewVisit" @click="editVisit(g.periodid, i)"><i class="feather icon-edit"></i> Edit</a>
                                                <a v-if="permission.permission_value == 3" class="dropdown-item" href="javascript:void(0);" @click="deleteVisit(g.periodid, i)"><i class="feather icon-trash-2"></i> Delete</a>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="tableData.length == 0"><td class="text-center pt-2" colspan="7"><small>No Visit Added</small></td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="modal modal-slide-in" id="addNewVisit" tabindex="-1" role="dialog" aria-hidden="true">
                <div class="modal-dialog modal-scrollable modal-xl">
                    <form class="add-new-user modal-content pt-0" @submit.stop.prevent="onSubmitCreateVisit(visitBtn)">
                        <button type="button" class="close" data-dismiss="modal" @click="hideAddVisitModal()">×</button>
                        <div class="modal-header mb-1"><h5 class="modal-title">{{ visitBtn }} Visit</h5></div>
                        <div class="modal-body flex-grow-1">
                            <div class="form-group">
                                <label class="form-label">Visit Title</label>
                                <input required v-model="visitForm.period_title" class="form-control" placeholder="Visit Title" />
                            </div>
                            <div class="form-group" id="start">
                                <label class="form-label">Start Date</label>
                                <input required v-model="visitForm.start_date" id="start-date" class="form-control date" placeholder="Start Date" />
                            </div>
                            <div class="form-group" id="end">
                                <label class="form-label">End Date</label>
                                <input required v-model="visitForm.end_date" id="end-date" class="form-control date" placeholder="End Date" />
                            </div>
                            <div class="mt-2">
                                <button type="submit" class="btn btn-primary mr-1 data-submit">{{ visitBtn }}</button>
                                <button type="reset" class="btn btn-outline-secondary" data-dismiss="modal" @click="hideAddVisitModal()">Cancel</button>
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
    .component('visit_list', VisitList)
    .mount('#app');
