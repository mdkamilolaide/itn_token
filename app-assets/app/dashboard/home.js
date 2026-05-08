/**
 * Dashboard module — Vue 3 Composition API in place.
 * Three components: page-body (routes), user_list (file-manager shell with
 * jstree placeholder), user_details (read/edit user form).
 *
 * EventBus events (preserved names):
 *   g-event-goto-page    — routes between list and detail views
 *   g-event-update-user  — emitted after a successful update / activation
 */

const { ref, reactive, computed, onMounted, onBeforeUnmount } = Vue;
const { useApp, useFormat, bus, safeMessage, dataQuery } = window.utils;

/* ------------------------------------------------------------------ */
/* page-body                                                           */
/* ------------------------------------------------------------------ */
const PageBody = {
    setup() {
        const page = ref('list');

        function gotoPageHandler(data) {
            page.value = data.page;
        }

        onMounted(function () {
            bus.on('g-event-goto-page', gotoPageHandler);
        });
        onBeforeUnmount(function () {
            bus.off('g-event-goto-page', gotoPageHandler);
        });

        return { page };
    },
    template: `
        <div>
            <div class="content-body">
                <div v-show="page == 'list'">
                    <user_list/>
                </div>
                <div v-show="page == 'detail'">
                    <user_details/>
                </div>
            </div>
        </div>
    `,
};

/* ------------------------------------------------------------------ */
/* user_list — file-manager-style sidebar with jstree placeholder      */
/* ------------------------------------------------------------------ */
const UserList = {
    setup() {
        const url = ref(window.common && window.common.BadgeService);
        const geoLevelForm = reactive({
            geoLevel: '',
            geoLevelId: 0,
            currentUserLoginid: '',
            userid: '',
        });
        const geoIndicator = reactive({
            state: 50,
            currentLevelId: 0,
            lga: '',
            cluster: '',
            ward: '',
        });

        function reloadUserListOnUpdate() { /* placeholder hook */ }

        function initJstree() {
            var filesTreeView = $('.my-drive');
            if (!filesTreeView.length || !$.fn || !$.fn.jstree) return;
            filesTreeView
                .jstree({
                    core: {
                        check_callback: true,
                        themes: { dots: true },
                        data: {
                            url: '../../../app-assets/data/jstree-data.json',
                            dataType: 'json',
                            data: function (node) { return { id: node.id }; },
                        },
                    },
                    plugins: ['types'],
                    types: {
                        default: { icon: 'far fa-folder' },
                        html: { icon: 'fab fa-html5 text-danger' },
                        css: { icon: 'fab fa-css3-alt text-info' },
                        img: { icon: 'far fa-file-image text-success' },
                        js: { icon: 'fab fa-node-js text-warning' },
                    },
                })
                .bind('select_node.jstree', function (e, data) {
                    return data.instance.open_node(data.node);
                })
                .on('changed.jstree', function (e, data) {
                    var i, j, r = [];
                    var t = [];
                    for (i = 0, j = data.selected.length; i < j; i++) {
                        r.push(data.instance.get_node(data.selected[i]).id);
                        t.push(data.instance.get_node(data.selected[i]));
                    }
                    console.log('Selected: ' + r.join(', '));
                    if (t[0] && t[0].original) console.log(t[0].original.geo_level_id);
                });
        }

        onMounted(function () {
            initJstree();
            bus.on('g-event-update-user', reloadUserListOnUpdate);
        });
        onBeforeUnmount(function () {
            bus.off('g-event-update-user', reloadUserListOnUpdate);
        });

        return { url, geoLevelForm, geoIndicator };
    },
    template: `
        <div class="row" id="basic-table">
            <div class="col-12 mt-1">
                <div class="file-manager-application">
                    <div class="content-overlay"></div>
                    <div class="header-navbar-shadow"></div>
                    <div class="content-area-wrapper container-xxl p-0 mb-1">
                        <div class="sidebar-left">
                            <div class="sidebar">
                                <div class="sidebar-file-manager">
                                    <div class="sidebar-inner">
                                        <div class="dropdown dropdown-actions left-ctr">
                                            <h4 class="text-primary">Metrics</h4>
                                        </div>
                                        <div class="sidebar-list">
                                            <div class="list-group">
                                                <div class="my-drive"></div>
                                                <div class="jstree-ajax"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="content-right">
                            <div class="content-wrapper container-xxl p-0">
                                <div class="content-header row"></div>
                                <div class="content-body">
                                    <div class="body-content-overlay"></div>
                                    <div class="file-manager-main-content">
                                        <div class="file-manager-content-header d-flex justify-content-between align-items-center">
                                            <div class="d-flex align-items-center">
                                                <div class="sidebar-toggle d-block d-xl-none float-left align-middle ml-1">
                                                    <i data-feather="menu" class="font-medium-5"></i>
                                                </div>
                                                <div class="input-group input-group-merge shadow-none m-0 flex-grow-1">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text border-0">
                                                            <i data-feather="search"></i>
                                                        </span>
                                                    </div>
                                                    <input type="text" class="form-control files-filter border-0 bg-transparent" placeholder="Search" />
                                                </div>
                                            </div>
                                            <div class="d-flex align-items-center">
                                                <div class="btn-group btn-group-toggle view-toggle ml-50" data-toggle="buttons">
                                                    <label class="btn btn-outline-primary p-50 btn-sm active">
                                                        <input type="radio" name="view-btn-radio" data-view="grid" checked />
                                                        <i data-feather="rotate-cw"></i>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="file-manager-content-body">
                                            <div class="drives">
                                                <div class="row">
                                                    <div class="col-12">
                                                        <h6 class="files-section-title mb-75">Drives</h6>
                                                        The content here
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `,
};

/* ------------------------------------------------------------------ */
/* user_details — read / edit form                                     */
/* ------------------------------------------------------------------ */
const UserDetails = {
    setup() {
        const fmtUtils = useFormat();

        const userid = ref('');
        const userDetails = ref(true);
        const user_status = ref('');
        const bankListData = ref([]);
        const roleListData = ref([]);
        const permission = ref(
            (typeof getPermission === 'function')
                ? getPermission(typeof per !== 'undefined' ? per : null, 'users') || { permission_value: 0 }
                : { permission_value: 0 }
        );
        const userData = reactive({
            baseData: {},
            financeData: {},
            identityData: {},
            roleData: {},
        });

        function gotoPageHandler(data) {
            if (!data || data.page !== 'detail') return;
            userDetails.value = true;
            userid.value = data.userid;
            user_status.value = data.user_status;
            getUserDetails();
        }

        function goToList() {
            bus.emit('g-event-goto-page', { page: 'list', userid: userid.value });
        }

        function getUserDetails() {
            overlay.show();
            axios.get(common.DataService + '?qid=005&e=' + userid.value)
                .then(function (response) {
                    var d = response.data || {};
                    userData.baseData     = (d.base     && d.base[0])     || {};
                    userData.financeData  = (d.finance  && d.finance[0])  || {};
                    userData.identityData = (d.identity && d.identity[0]) || {};
                    userData.roleData     = (d.role     && d.role[0])     || {};
                    overlay.hide();
                })
                .catch(function (error) {
                    overlay.hide();
                    alert.Error('ERROR', safeMessage(error));
                });
        }

        function getBankLists() {
            overlay.show();
            axios.get(common.DataService + '?qid=gen008')
                .then(function (response) {
                    bankListData.value = (response.data && response.data.data) || [];
                    overlay.hide();
                })
                .catch(function (error) {
                    overlay.hide();
                    alert.Error('ERROR', safeMessage(error));
                });
        }

        function getRoleList() {
            overlay.show();
            axios.get(common.DataService + '?qid=007')
                .then(function (response) {
                    roleListData.value = (response.data && response.data.data) || [];
                    overlay.hide();
                })
                .catch(function (error) {
                    overlay.hide();
                    alert.Error('ERROR', safeMessage(error));
                });
        }

        function discardUpdate() {
            $.confirm({
                title: 'WARNING!',
                content:
                    '<p>Are you sure you want to discard the changes? </p><br>Discarding the changes means you will lose all changes made',
                buttons: {
                    delete: {
                        text: 'Discard Changes',
                        btnClass: 'btn btn-warning mr-1',
                        action: function () {
                            getUserDetails();
                            userDetails.value = true;
                            overlay.hide();
                        },
                    },
                    close: {
                        text: 'Cancel',
                        btnClass: 'btn btn-outline-secondary',
                        action: function () { overlay.hide(); },
                    },
                },
            });
        }

        function updateUserProfile() {
            var updateFormData = {
                userid:       userid.value,
                roleid:       userData.baseData.roleid,
                first:        userData.identityData.first,
                middle:       userData.identityData.middle,
                last:         userData.identityData.last,
                gender:       userData.identityData.gender,
                email:        userData.identityData.email,
                phone:        userData.identityData.phone,
                bank_name:    userData.financeData.bank_name,
                account_name: userData.financeData.account_name,
                account_no:   userData.financeData.account_no,
                bank_code:    userData.financeData.bank_code,
                bio_feature:  '',
            };
            var url = common.DataService;
            $.confirm({
                title: 'WARNING!',
                content:
                    '<p>Are you sure you want to Update the User? </p><br>Updating the User profile means you are changing the user permissions and details',
                buttons: {
                    delete: {
                        text: 'Update Details',
                        btnClass: 'btn btn-warning mr-1',
                        action: function () {
                            axios.post(url + '?qid=006', JSON.stringify(updateFormData))
                                .then(function (response) {
                                    overlay.hide();
                                    if (response.data.result_code == '200') {
                                        bus.emit('g-event-update-user', {});
                                        userDetails.value = true;
                                        alert.Success('SUCCESS', response.data.total + ' User Updated');
                                    } else {
                                        alert.Error('ERROR', 'User update failed');
                                    }
                                })
                                .catch(function (error) {
                                    overlay.hide();
                                    alert.Error('ERROR', safeMessage(error));
                                });
                        },
                    },
                    close: {
                        text: 'Cancel',
                        btnClass: 'btn btn-outline-secondary',
                        action: function () { overlay.hide(); },
                    },
                },
            });
        }

        function checkIfEmpty(data) {
            return data === null || data === '' || data === undefined ? 'Nil' : data;
        }

        function userActivationDeactivation(actionid) {
            var selectedId = [actionid];
            overlay.show();
            axios.post(common.DataService + '?qid=001', JSON.stringify(selectedId))
                .then(function (response) {
                    overlay.hide();
                    if (response.data.result_code == '200') {
                        bus.emit('g-event-update-user', {});
                        user_status.value = String(user_status.value) === '1' ? 0 : 1;
                        alert.Success('SUCCESS', 'User De/Activation Successful');
                    } else {
                        alert.Error('ERROR', 'User De/Activation failed');
                    }
                })
                .catch(function (error) {
                    overlay.hide();
                    alert.Error('ERROR', safeMessage(error));
                });
        }

        function changeRole(event) {
            userData.baseData.role = event.target.options[event.target.options.selectedIndex].text;
        }
        function changeBank(event) {
            userData.financeData.bank_name = event.target.options[event.target.options.selectedIndex].text;
        }

        function downloadBadge(id) {
            overlay.show();
            window.open(common.BadgeService + '?qid=002&e=' + id, '_parent');
            overlay.hide();
        }

        function numbersOnlyWithoutDot(evt) {
            var e = evt || window.event;
            var charCode = e.which || e.keyCode;
            if (charCode > 31 && (charCode < 48 || charCode > 57)) e.preventDefault();
            return true;
        }

        onMounted(function () {
            bus.on('g-event-goto-page', gotoPageHandler);
            getRoleList();
            getBankLists();
        });
        onBeforeUnmount(function () {
            bus.off('g-event-goto-page', gotoPageHandler);
        });

        return {
            // state
            userid, userDetails, user_status,
            bankListData, roleListData, permission, userData,
            // methods
            gotoPageHandler, goToList, discardUpdate,
            getUserDetails, getBankLists, getRoleList,
            updateUserProfile, checkIfEmpty,
            userActivationDeactivation, changeRole, changeBank,
            downloadBadge, numbersOnlyWithoutDot,
            // utility methods (returned so templates work unchanged)
            displayDate: fmtUtils.displayDate,
            capitalize: fmtUtils.capitalize,
            capitalizeEachWords: fmtUtils.capitalizeEachWords,
            formatNumber: fmtUtils.formatNumber,
            convertStringNumberToFigures: fmtUtils.convertStringNumberToFigures,
            fmt: fmtUtils.fmt,
        };
    },
    template: `
        <div class="row">
            <div class="col-md-12 col-sm-12 col-12 mb-1">
                <h2 class="content-header-title header-txt float-left mb-0">Users</h2>
                <div class="breadcrumb-wrapper">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../users">Home</a></li>
                        <li class="breadcrumb-item"><a href="javascript:void(0);" @click="goToList()">Users List</a></li>
                        <li v-if="userDetails" class="breadcrumb-item active">User Details</li>
                        <li v-else class="breadcrumb-item active">User Update</li>
                    </ol>
                </div>
            </div>

            <!-- User Sidebar -->
            <div class="col-xl-4 col-lg-5 col-md-5 order-1 order-md-0 sidebar-sticky">
                <div class="card">
                    <div class="card-body">
                        <div class="user-avatar-section">
                            <div class="d-flex align-items-center flex-column">
                                <img class="img-fluid rounded mt-3 mb-2" :src="'/app-assets/images/avatar.png'" height="110" width="110" alt="User avatar">
                                <div class="user-info text-center">
                                    <h4 v-html="userData.baseData.loginid"></h4>
                                    <span class="badge bg-light-primary" v-html="userData.baseData.role"></span>
                                </div>
                            </div>
                        </div>
                        <div class="fw-bolder border-bottom font-small-2 pb-20 mb-1 mt-1 text-center">{{ userData.baseData.geo_string }}</div>
                        <div class="info-container">
                            <ul class="list-unstyled pl-2">
                                <li class="mb-75"><span class="fw-bolder me-25">Username:</span><span v-html="userData.baseData.username"></span></li>
                                <li class="mb-75"><span class="fw-bolder me-25">User Group:</span><span v-html="userData.baseData.user_group"></span></li>
                                <li class="mb-75">
                                    <span class="fw-bolder me-25">Status:</span>
                                    <span class="badge" :class="user_status==1? 'bg-light-success' : 'bg-light-danger'">{{ user_status==1? 'Active' : 'Inactive' }}</span>
                                </li>
                            </ul>
                            <div class="justify-content-center pt-2 form-group">
                                <button class="btn btn-primary mb-1 form-control suspend-user waves-effect" @click="downloadBadge(userid)"><i class="feather icon-download"></i> Download Badge</button>
                                <button v-if="userDetails && permission.permission_value >=2" @click="userDetails = false" class="btn btn-outline-primary mb-1 form-control suspend-user waves-effect"><i class="feather icon-edit-2"></i>  Edit</button>
                                <button v-if="permission.permission_value ==3" class="btn form-control suspend-user waves-effect" :class="user_status== 1 ? 'btn-danger' : 'btn-success'" @click="userActivationDeactivation(userid)"><i class="feather" :class="user_status== 1 ? 'icon-user-x' : 'icon-user-check'"></i> {{ user_status==1? ' Deactivate' : ' Activate' }}</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- User Content -->
            <div class="col-xl-8 col-lg-7 col-md-7 order-0 order-md-1">
                <div v-if="userDetails">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title mb-50">Details</h4>
                            <button v-if="permission.permission_value >=2" class="btn btn-primary btn-sm waves-effect waves-float waves-light" @click="userDetails = false">
                                <i class="feather icon-edit-2"></i> <span> Edit</span>
                            </button>
                        </div>
                        <div class="card-body row">
                            <div class="col-12">
                                <table class="table">
                                    <tr>
                                        <td class="user-detail-txt"><label class="d-block text-primary">Firstname</label>{{ checkIfEmpty(userData.identityData.first) }}</td>
                                        <td class="user-detail-txt"><label class="d-block text-primary">Middle</label>{{ checkIfEmpty(userData.identityData.middle) }}</td>
                                    </tr>
                                    <tr>
                                        <td class="user-detail-txt"><label class="d-block text-primary">Lastname</label>{{ checkIfEmpty(userData.identityData.last) }}</td>
                                        <td class="user-detail-txt"><label class="d-block text-primary">Gender</label>{{ checkIfEmpty(userData.identityData.gender) }}</td>
                                    </tr>
                                    <tr>
                                        <td class="user-detail-txt"><label class="d-block text-primary">Phone No</label>{{ checkIfEmpty(userData.identityData.phone) }}</td>
                                        <td class="user-detail-txt"><label class="d-block text-primary">Email</label>{{ checkIfEmpty(userData.identityData.email) }}</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header"><h4 class="card-title mb-50">Finance</h4></div>
                        <div class="card-body row">
                            <div class="col-12">
                                <table class="table">
                                    <tr><td colspan="2" class="user-detail-txt"><label class="d-block text-primary">Account Name</label>{{ checkIfEmpty(userData.financeData.account_name) }}</td></tr>
                                    <tr>
                                        <td class="user-detail-txt"><label class="d-block text-primary">Account Number</label>{{ checkIfEmpty(userData.financeData.account_no) }}</td>
                                        <td class="user-detail-txt"><label class="d-block text-primary">Bank Name</label>{{ checkIfEmpty(userData.financeData.bank_name) }}</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header"><h4 class="card-title mb-50">Role</h4></div>
                        <div class="card-body row">
                            <div class="col-12">
                                <table class="table">
                                    <tr><td colspan="2" class="user-detail-txt"><label class="d-block text-primary">Role</label>{{ checkIfEmpty(userData.baseData.role) }}</td></tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <form method="POST" @submit.stop.prevent="updateUserProfile()" v-else>
                    <div class="card">
                        <div class="card-header"><h4 class="card-title mb-50">Details</h4></div>
                        <div class="card-body row">
                            <div class="col-6">
                                <div class="form-group">
                                    <label>First Name</label>
                                    <input type="text" id="firstname" v-model="userData.identityData.first" class="form-control firstname" placeholder="First Name" name="firstname" />
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label>Middle Name</label>
                                    <input type="text" id="middlename" v-model="userData.identityData.middle" class="form-control middlename" placeholder="Middle Name" name="middlename" />
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label>Lastname</label>
                                    <input type="text" id="lastname" v-model="userData.identityData.last" class="form-control lastname" placeholder="Last Name" name="lastname" />
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label>Gender</label>
                                    <select name="gender" v-model="userData.identityData.gender" class="form-control">
                                        <option value="Male">Male</option>
                                        <option value="Female">Female</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label>Phone No</label>
                                    <input type="text" id="phoneno" maxlength="11" v-model="userData.identityData.phone" @keypress="numbersOnlyWithoutDot" class="form-control phoneno" placeholder="Phone No" name="phoneno" />
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label>Email</label>
                                    <input type="email" id="email" v-model="userData.identityData.email" class="form-control email" placeholder="Email" name="email" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header"><h4 class="card-title mb-50">Finance</h4></div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-12">
                                    <div class="form-group">
                                        <label>Account Name</label>
                                        <input type="text" id="account_name" v-model="userData.financeData.account_name" class="form-control account_name" placeholder="Account Name" name="account_name" />
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="form-group">
                                        <label>Account Number</label>
                                        <input type="text" id="account_no" @keypress="numbersOnlyWithoutDot" maxlength="10" v-model="userData.financeData.account_no" class="form-control account_no" placeholder="Account Number" name="account_no" />
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="form-group">
                                        <label>Bank Name</label>
                                        <select name="bank_code" v-model="userData.financeData.bank_code" @change="changeBank($event)" class="form-control bank_code select2">
                                            <option v-for="b in bankListData" :key="b.bank_code" :value="b.bank_code">{{ b.bank_name }}</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header"><h4 class="card-title mb-50">Role</h4></div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-12">
                                    <div class="form-group">
                                        <label>Role</label>
                                        <select name="role" :disabled="permission.permission_value <3" v-model="userData.baseData.roleid" @change="changeRole($event)" class="form-control role select2">
                                            <option v-for="r in roleListData" :value="r.roleid" :key="r.roleid">{{ r.role }}</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-6">
                            <button type="button" @click="discardUpdate()" class="btn btn-outline-secondary form-control mt-2 waves-effect">Cancel</button>
                        </div>
                        <div class="col-6">
                            <button v-if="permission.permission_value >=2" class="btn btn-primary form-control mt-2 waves-effect waves-float waves-light">Update Details</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    `,
};

/* ------------------------------------------------------------------ */
/* Mount                                                              */
/* ------------------------------------------------------------------ */
useApp({ template: `<div><page-body/></div>` })
    .component('page-body', PageBody)
    .component('user_list', UserList)
    .component('user_details', UserDetails)
    .mount('#app');
