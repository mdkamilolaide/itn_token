/**
 * Distribution / DP List — Vue 3 Composition API in place.
 * Two components — page-body and dashboard_container.
 *
 * Pick LGA → load DPs (qid=401a) → bulk-download badges via DpBadgeService.
 */

const { ref, reactive, onMounted } = Vue;
const { useApp, useFormat, safeMessage } = window.utils;

const PageBody = {
    setup() {
        const page = ref('dashboard');
        const permission = ref(
            (typeof getPermission === 'function')
                ? (getPermission(typeof per !== 'undefined' ? per : null, 'distribution') || { permission_value: 0 })
                : { permission_value: 0 }
        );
        return { page, permission };
    },
    template: `
        <div>
            <div class="content-body">
                <dashboard_container v-if="permission.permission_value > 1" />
                <div class="alert alert-danger" v-else>
                    <div class="alert-body">
                        <strong>Access Denied!</strong> You don't have permission to access this page.
                    </div>
                </div>
            </div>
        </div>
    `,
};

const DashboardContainer = {
    setup() {
        const fmtUtils = useFormat();

        const geoIndicator = reactive({ state: 50, currentLevelId: 0, lga: '', ward: '' });
        const checkToggle = ref(false);
        const geoLevelData = ref([]);
        const sysDefaultData = ref({});
        const lgaLevelData = ref([]);
        const wardLevelData = ref([]);
        const tableData = ref([]);
        const bulkUserForm = reactive({ geoLevel: '', geoLevelId: 0, mobilizationDate: '' });

        const getAllStat = () => {
            var endpoints = [common.DataService + '?qid=401a&lgaid=' + geoIndicator.lga];
            overlay.show();
            Promise.all(endpoints.map(e => axios.get(e))).then(
                axios.spread((...allData) => {
                    tableData.value = (allData[0] && allData[0].data && allData[0].data.data) || [];
                    overlay.hide();
                })
            ).catch(error => {
                overlay.hide();
                alert.Error('ERROR', safeMessage(error));
            });
        }
        const getsysDefaultDataSettings = () => {
            overlay.show();
            axios.get(common.DataService + '?qid=gen007')
                .then(response => {
                    if (response.data.data && response.data.data.length > 0) {
                        sysDefaultData.value = response.data.data[0];
                        getLgasLevel(response.data.data[0].stateid);
                        bulkUserForm.geoLevel = 'state';
                        bulkUserForm.geoLevelId = response.data.data[0].stateid;
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
        const getWardLevel = () => {
            overlay.show();
            bulkUserForm.geoLevelId = '';
            axios.get(common.DataService + '?qid=gen005&e=' + geoIndicator.lga)
                .then(response => {
                    wardLevelData.value = (response.data && response.data.data) || [];
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
        const checkedBg = (p) => { return p != '' ? 'bg-select' : ''; };
        const selectedID = () => { return tableData.value.filter(r => r.pick).map(r => r.dpid); };

        const downloadDpBadge = (i) => {
            overlay.show();
            var row = tableData.value[i];
            if (!row) { overlay.hide(); return; }
            var url = common.DpBadgeService +
                '?qid=002&guid=' + row.guid +
                '&geo_string=' + encodeURIComponent(row.geo_string) +
                '&title=' + encodeURIComponent(row.title);
            var iframe = document.createElement('iframe');
            iframe.style.display = 'none';
            iframe.src = url;
            document.body.appendChild(iframe);
            setTimeout(() => {
                overlay.hide();
                document.body.removeChild(iframe);
            }, 5000);
        }
        const downloadBadges = () => {
            overlay.show();
            if (parseInt(selectedID().length) > 0) {
                window.open(common.DpBadgeService + '?qid=001&e=' + selectedID(), '_parent');
            } else {
                alert.Error('Badge Download Failed', 'No user DP List');
            }
            overlay.hide();
        }
        const loadDp = () => {
            if (geoIndicator.lga == '') {
                alert.Error('Error', 'Kindly choose a LGA and Ward');
                return;
            }
            getAllStat();
        }

        onMounted(() => {
            getGeoLevel();
            getsysDefaultDataSettings();
            $('#dp-search').on('keyup', function () {
                var value = $(this).val().toLowerCase();
                $('#dpTable tbody tr').filter(function () {
                    $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
                });
            });
        });

        return {
            geoIndicator, checkToggle, geoLevelData, sysDefaultData,
            lgaLevelData, wardLevelData, tableData, bulkUserForm,
            getAllStat, getsysDefaultDataSettings, getGeoLevel,
            getLgasLevel, getWardLevel,
            selectAll, uncheckAll, selectToggle, checkedBg, selectedID,
            downloadDpBadge, downloadBadges, loadDp,
            capitalize: fmtUtils.capitalize,
        };
    },
    template: `
        <div>
            <div class="row">
                <div class="col-12 mb-2">
                    <h2 class="content-header-title header-txt float-left mb-0">Distribution</h2>
                    <div class="breadcrumb-wrapper">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item active">Distribution Point List</li>
                        </ol>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="alert bg-light-primary pt-1 pb-1">
                        <div class="col-12">
                            <div class="row">
                                <div class="col-12 col-lg8 col-md-8 col-sm-12">
                                    <div class="form-group">
                                        <label class="form-label" for="user-role">LGA List</label>
                                        <select id="user-role" class="form-control" @change="loadDp()" v-model="geoIndicator.lga">
                                            <option value="" selected="selected">Select a LGA</option>
                                            <option v-for="lga in lgaLevelData" :key="lga.lgaid" :value="lga.lgaid">{{ lga.lga }}</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-12 col-lg-4 col-md-4 col-sm-12">
                                    <label class="form-label" for="user-role">DP Name</label>
                                    <div class="input-group date_filter">
                                        <input type="text" id="dp-search" v-model="bulkUserForm.mobilizationDate" class="form-control date" placeholder="Search using DP Name" />
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
                            <th width="60px">
                                <div class="custom-control custom-checkbox checkbox">
                                    <input type="checkbox" class="custom-control-input" :checked="checkToggle" @change="selectToggle()" id="all-check" />
                                    <label class="custom-control-label" for="all-check"></label>
                                </div>
                            </th>
                            <th>Distribution Point Name</th>
                            <th>DP Location</th>
                            <th class="text-right">
                                <a href="javascript:void(0)" data-toggle="tooltip" data-placement="top" title="Downlaod All" class="btn btn-primary btn-sm p-50" @click="downloadBadges()"><i class="feather icon-download-cloud"></i></a>
                            </th>
                        </thead>
                        <tbody>
                            <tr v-for="(g, i) in tableData" :key="g.dpid || i">
                                <td>
                                    <div class="custom-control custom-checkbox checkbox">
                                        <input type="checkbox" class="custom-control-input" :id="g.dpid" v-model="g.pick" />
                                        <label class="custom-control-label" :for="g.dpid"></label>
                                    </div>
                                </td>
                                <td>{{ g.title }}</td>
                                <td>{{ g.geo_string }}</td>
                                <td class="text-right">
                                    <a href="javascript:void(0)" class="btn btn-primary btn-sm p-50" @click="downloadDpBadge(i)"><i class="feather icon-download"></i></a>
                                </td>
                            </tr>
                            <tr v-if="tableData.length == 0"><td class="text-center pt-2" colspan="5"><small>No Ward Choosen or DP List Empty</small></td></tr>
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
