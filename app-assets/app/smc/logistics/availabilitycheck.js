/**
 * SMC / Logistics / Availability Check — Vue 3 Composition API in place.
 * Two components — the root app (page switcher) and page-availability-check.
 *
 * Pick a visit period + a product, hit qid=1131, see pass/fail per facility.
 */

const { ref, reactive, computed, onMounted, onBeforeUnmount } = Vue;
const { useApp, useFormat, bus, safeMessage } = window.utils;

const appState = Vue.reactive({
    pageState: { page: 'availability-check', title: '' },
    permission: (typeof getPermission === 'function')
        ? (getPermission(typeof per !== 'undefined' ? per : null, 'smc') || { permission_value: 0 })
        : { permission_value: 0 },
    userId: (function () { var el = document.getElementById('v_g_id'); return el ? el.value : ''; })(),
    geoLevelForm: { geoLevel: '', geoLevelId: 0 },
    defaultStateId: '',
    sysDefaultData: [],
    productData: [],
    lgaData: [],
    checkData: [],
    periodData: [],
    currentPeriodId: '',
    currentProductCode: '',
});

const PageAvailabilityCheck = {
    setup() {
        const fmtUtils = useFormat();
        const searchState = ref(false);

        function getAllPeriodLists() {
            overlay.show();
            axios.get(common.DataService + '?qid=1004')
                .then(function (response) {
                    appState.periodData = (response.data && response.data.data) || [];
                    overlay.hide();
                })
                .catch(function (error) {
                    overlay.hide();
                    alert.Error('ERROR', safeMessage(error));
                });
        }
        function getProductMaster() {
            overlay.show();
            axios.get(common.DataService + '?qid=gen011')
                .then(function (response) {
                    var data = ((response.data && response.data.data) || []).slice().sort(function (a, b) {
                        return a.product_code.localeCompare(b.product_code);
                    });
                    appState.productData = data;
                    overlay.hide();
                })
                .catch(function (error) {
                    overlay.hide();
                    alert.Error('ERROR', safeMessage(error));
                });
        }
        function checkProductAvailability() {
            if (appState.currentPeriodId == '') { alert.Error('ERROR', 'Please select a visit'); return; }
            if (appState.currentProductCode == '') { alert.Error('ERROR', 'Please select a product'); return; }
            var data = { periodid: appState.currentPeriodId, product_code: appState.currentProductCode };
            overlay.show();
            axios.post(common.DataService + '?qid=1131', JSON.stringify(data))
                .then(function (response) {
                    overlay.hide();
                    if (response.data.result_code == '200') {
                        appState.checkData = response.data.data || [];
                        searchState.value = true;
                    } else {
                        alert.Error('ERROR', response.data.message);
                    }
                })
                .catch(function (error) {
                    overlay.hide();
                    alert.Error('ERROR', safeMessage(error));
                });
        }
        function resetCheckTable() {
            appState.checkData = [];
            searchState.value = false;
        }
        function resetForm() {
            resetCheckTable();
            appState.currentPeriodId = '';
            appState.currentProductCode = '';
        }

        const validitySummary = computed(function () {
            var pass = 0, fail = 0;
            (appState.checkData || []).forEach(function (item) {
                if (item.status === 'pass') pass++;
                else if (item.status === 'fail') fail++;
            });
            var total = pass + fail;
            var passPercentage = total > 0 ? ((pass / total) * 100).toFixed(1) : '0.0';
            return { pass: pass, fail: fail, total: total, passPercentage: passPercentage };
        });

        onMounted(function () {
            getAllPeriodLists();
            getProductMaster();
            bus.on('g-event-reset-form', resetForm);
        });
        onBeforeUnmount(function () {
            bus.off('g-event-reset-form', resetForm);
        });

        return {
            appState, searchState, validitySummary,
            getAllPeriodLists, getProductMaster, checkProductAvailability,
            resetCheckTable, resetForm,
            capitalize: fmtUtils.capitalize,
            formatNumber: fmtUtils.formatNumber,
            convertStringNumberToFigures: fmtUtils.convertStringNumberToFigures,
            progressBarWidth: fmtUtils.progressBarWidth,
            progressBarStatus: fmtUtils.progressBarStatus,
        };
    },
    template: `
        <div class="row">
            <div class="col-md-8 col-sm-12 col-12 mb-0">
                <h2 class="content-header-title header-txt float-left mb-0">SMC</h2>
                <div class="breadcrumb-wrapper">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../smc/logistics">Logistics</a></li>
                        <li class="breadcrumb-item active">Availability Check</li>
                    </ol>
                </div>
            </div>

            <div class="col-12 mt-1">
                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-12 col-md-5 col-sm-12 col-lg-5">
                                <div class="form-group">
                                    <label>Origin</label>
                                    <select class="form-control" id="cms" placeholder="CMS"><option value="CMS" selected>CMS</option></select>
                                </div>
                            </div>
                            <div class="col-12 col-md-2 col-sm-12 col-lg-2 justify-content-center align-items-center">
                                <div class="form-group middle-icon d-flex justify-content-center align-items-center">
                                    <div class="transfer-circle d-flex d-sm-none d-md-none justify-content-center align-items-center"><div class="d-flex flex-column"><i class="feather icon-arrow-up"></i><i class="feather icon-arrow-down"></i></div></div>
                                    <div class="transfer-circle d-none d-sm-flex d-md-flex justify-content-center align-items-center"><div class="d-flex flex-row"><i class="feather icon-arrow-left"></i><i class="feather icon-arrow-right"></i></div></div>
                                </div>
                            </div>
                            <div class="col-12 col-md-5 col-sm-12 col-lg-5">
                                <div class="form-group">
                                    <label>Destination</label>
                                    <select class="form-control period" id="destination"><option value="Facility" selected>Facility</option></select>
                                </div>
                            </div>
                            <div class="col-12 col-md-5 col-sm-12 col-lg-5">
                                <div class="form-group">
                                    <label>Visit</label>
                                    <select @change="resetCheckTable" v-model="appState.currentPeriodId" class="form-control period" id="period">
                                        <option value="">Choose Period</option>
                                        <option v-for="(g, i) in appState.periodData" :key="g.periodid" :value="g.periodid">{{ g.title }}</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-12 col-md-5 col-sm-12 col-lg-5 offset-lg-2 offset-md-2">
                                <div class="form-group">
                                    <label>*Choose Product</label>
                                    <select @change="resetCheckTable" class="form-control" placeholder="Choose Product" v-model="appState.currentProductCode">
                                        <option value="">Choose Product</option>
                                        <option v-for="(g, i) in appState.productData" :key="g.product_code" :value="g.product_code">{{ g.name }}</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-12 text-right">
                                <div class="form-group mb-0 mt-1">
                                    <button type="button" v-if="appState.currentPeriodId !== '' || appState.currentProductCode !== ''" style="max-width: 180px !important" class="btn btn-secondary mr-1 form-control" @click="resetForm()">Reset <i class="feather icon-corner-up-left ml-1 text-right"></i></button>
                                    <button type="button" style="max-width: 180px !important" class="btn btn-primary form-control" @click="checkProductAvailability()">Check <i class="feather icon-send ml-1 text-right"></i></button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card" v-if="appState.checkData.length > 0">
                    <div class="card-body">
                        <div class="row mx-0 mb-1 shadow-sm border-light py-50 border-lighten-1">
                            <div class="col-12 col-sm-4">
                                <div class="media" style="align-items: center;">
                                    <div class="media-left"><span class="badge badge-primary p-50"><i class="feather icon-list"></i></span></div>
                                    <div class="media-body px-1">
                                        <h6 class="media-heading font-medium-2 font-weight-bolder" style="margin: 0;">{{ convertStringNumberToFigures(validitySummary.total) }}</h6>
                                        <small class="text-muted">Total Facilities</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-sm-4">
                                <div class="media" style="align-items: center;">
                                    <div class="media-left"><span class="badge badge-success p-50"><i class="feather icon-check-square"></i></span></div>
                                    <div class="media-body px-1">
                                        <h6 class="media-heading font-medium-2 font-weight-bolder" style="margin: 0;">{{ convertStringNumberToFigures(validitySummary.pass) }}</h6>
                                        <small class="text-muted">{{ progressBarWidth(validitySummary.total, validitySummary.pass) }} Passed</small>
                                        <div class="progress w-100 me-3" style="height: 6px;">
                                            <div class="progress-bar" :class="progressBarStatus(validitySummary.total, validitySummary.pass)" :style="{ width: progressBarWidth(validitySummary.total, validitySummary.pass) }" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-sm-4">
                                <div class="media" style="align-items: center;">
                                    <div class="media-left"><span class="badge badge-danger p-50"><i class="feather icon-x-square"></i></span></div>
                                    <div class="media-body px-1">
                                        <h6 class="media-heading font-medium-2 font-weight-bolder" style="margin: 0;">{{ convertStringNumberToFigures(validitySummary.fail) }}</h6>
                                        <small class="text-muted">{{ progressBarWidth(validitySummary.total, validitySummary.fail) }} Failed</small>
                                        <div class="progress w-100 me-3" style="height: 6px;">
                                            <div class="progress-bar" :class="progressBarStatus(validitySummary.total, validitySummary.fail)" :style="{ width: progressBarWidth(validitySummary.total, validitySummary.fail) }" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th class="px-1">#</th>
                                        <th class="px-1">Origin</th>
                                        <th class="px-1">Destination</th>
                                        <th class="px-1">Product</th>
                                        <th class="px-1">Allocated Qty.</th>
                                        <th class="px-1">Available Qty.</th>
                                        <th class="px-1">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(item, index) in appState.checkData" :key="index">
                                        <td class="px-1">{{ index + 1 }}</td>
                                        <td class="px-1">{{ item.cms_name }}</td>
                                        <td class="px-1">{{ item.geo_string }}</td>
                                        <td class="px-1">
                                            <div class="d-flex justify-content-left align-items-center">
                                                <div class="d-flex flex-column">
                                                    <span class="user_name text-wrap text-body"><span class="fw-bolder">{{ item.product_name }}</span></span>
                                                    <span class="font-small-2 text-muted">{{ item.product_code }}</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-1">{{ item.allocated_qty }}</td>
                                        <td class="px-1">{{ item.available_qty }}</td>
                                        <td class="px-1"><span class="badge" :class="item.status == 'pass' ? 'bg-light-success' : 'bg-light-danger'">{{ item.status == 'pass' ? 'Pass' : 'Failed' }}</span></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div v-if="appState.checkData.length == 0 && searchState == true" class="text-center mt-2 alert p-2"><small>No Facility With Issue/Inbound</small></div>
                    </div>
                </div>
                <div class="mb-50"></div>
            </div>
        </div>
    `,
};

useApp({
    template: `
        <div>
            <div v-show="appState.pageState.page == 'table'"></div>
            <div v-show="appState.pageState.page == 'availability-check'"><page-availability-check/></div>
        </div>
    `,
    setup() { return { appState }; },
})
    .component('page-availability-check', PageAvailabilityCheck)
    .mount('#app');
