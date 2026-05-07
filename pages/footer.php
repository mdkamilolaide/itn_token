<footer class="footer footer-static footer-light">
    <p class="clearfix mb-0"><span class="float-md-left d-block d-md-inline-block mt-25">COPYRIGHT &copy; <?php echo date("Y"); ?> IPOLONGO SOLUTION</span></p>
</footer>
<button class="btn btn-primary btn-icon scroll-top" type="button"><i data-feather="arrow-up"></i></button>


<div class="modal modal-center" id="change-password">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content p-0">
            <div class="modal-header">
                <h5 class="modal-title text-primary">Change My Password</h5>
                <div class="modal-actions">
                    <a class="text-body" href="javascript:void(0);" data-dismiss="modal" aria-label="Close"><i data-feather="x"></i></a>
                </div>
            </div>
            <div class="modal-body flex-grow-1">
                <form class="validate-form" id="change-password" method="POST">
                    <div class="form-group">
                        <div class="d-flex justify-content-between">
                            <label for="login-password">Old Password</label>
                        </div>
                        <div class="input-group input-group-merge form-password-toggle">
                            <input type="password" autocomplete="current-password" class="form-control form-control-merge old_password" id="password" name="password" tabindex="2" placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;" />
                            <div class="input-group-append">
                                <span class="input-group-text cursor-pointer"><i data-feather="eye"></i></span>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="d-flex justify-content-between">
                            <label for="login-password">New Password</label>
                        </div>
                        <div class="input-group input-group-merge form-password-toggle">
                            <input type="password" autocomplete="new-password" class="form-control new_password" id="new_password" name="new_password" tabindex="2" placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;" />
                            <div class="input-group-append">
                                <span class="input-group-text cursor-pointer"><i data-feather="eye"></i></span>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="d-flex justify-content-between">
                            <label for="login-password">Confirm New Password</label>
                        </div>
                        <div class="input-group input-group-merge form-password-toggle">
                            <input type="password" autocomplete="new-password" class="form-control confirm_password" id="confirm_password" name="confirm_password" tabindex="2" placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;" />
                            <div class="input-group-append">
                                <span class="input-group-text cursor-pointer"><i data-feather="eye"></i></span>
                            </div>
                        </div>
                    </div>
                    <div class="form-group mt-2 text-right">
                        <button class="btn btn-primary" tabindex="4">Change Password</button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</div>
<!--/ compose email -->

<div class="modal modal-center" id="view-user-details">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content p-0">
            <div class="modal-header">
                <h5 class="modal-title text-primary">My Profile</h5>
                <div class="modal-actions">
                    <a class="text-body" href="javascript:void(0);" data-dismiss="modal" aria-label="Close">
                        <i data-feather="x"></i>
                    </a>
                </div>
            </div>
            <div class="modal-body">
                <!-- User Details Section -->
                <!-- User Details -->
                <div class="card">
                    <div class="card-header  py-50">
                        <h4 class="card-title mb-50">Details</h4>
                        <button class="btn btn-primary btn-sm waves-effect waves-float waves-light" id="edit-user-btn" style="display:none;">
                            <i class="feather icon-edit-2"></i>
                        </button>
                    </div>
                    <div class="card-body row">
                        <div class="col-12">
                            <table class="table">
                                <tr>
                                    <td class="user-detail-txt">
                                        <label class="d-block text-primary">Firstname</label>
                                        <span id="9_first-name"></span>
                                    </td>
                                    <td class="user-detail-txt">
                                        <label class="d-block text-primary">Middle</label>
                                        <span id="9_middle-name"></span>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="user-detail-txt">
                                        <label class="d-block text-primary">Lastname</label>
                                        <span id="9_last-name"></span>
                                    </td>
                                    <td class="user-detail-txt">
                                        <label class="d-block text-primary">Gender</label>
                                        <span id="9_gender"></span>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="user-detail-txt">
                                        <label class="d-block text-primary">Phone No</label>
                                        <span id="9_phone"></span>
                                    </td>
                                    <td class="user-detail-txt">
                                        <label class="d-block text-primary">Email</label>
                                        <span id="9_email"></span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Finance Display -->
                <div class="card">
                    <div class="card-header py-50">
                        <h4 class="card-title mb-50">Finance</h4>
                    </div>
                    <div class="card-body row">
                        <div class="col-12">
                            <table class="table">
                                <tr>
                                    <td colspan="2" class="user-detail-txt">
                                        <label class="d-block text-primary">Account Name</label>
                                        <span id="9_account-name"></span>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="user-detail-txt">
                                        <label class="d-block text-primary">Account Number</label>
                                        <span id="9_account-no"></span>
                                    </td>
                                    <td class="user-detail-txt">
                                        <label class="d-block text-primary">Bank Name</label>
                                        <span id="9_bank-name"></span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Role Display -->
                <div class="card">
                    <div class="card-header py-50">
                        <h4 class="card-title mb-50">Account Details</h4>
                    </div>
                    <div class="card-body row">
                        <div class="col-12">
                            <table class="table">
                                <tr>
                                    <td colspan="2" class="user-detail-txt">
                                        <label class="d-block text-primary">Role</label>
                                        <span id="9_role"></span>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="user-detail-txt">
                                        <label class="d-block text-primary">Login ID</label>
                                        <span id="9_login-id"></span>
                                    </td>
                                    <td class="user-detail-txt">
                                        <label class="d-block text-primary">Geo Level</label>
                                        <span id="9_geo-level"></span>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="2" class="user-detail-txt">
                                        <label class="d-block text-primary">Role</label>
                                        <span id="9_geo-string"></span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
            <!-- Modal Footer -->
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>