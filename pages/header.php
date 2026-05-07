<!-- BEGIN: Header-->
<nav class="header-navbar navbar navbar-expand-lg align-items-center floating-nav navbar-light navbar-shadow">
    <div class="navbar-container d-flex content">
        <div class="bookmark-wrapper d-flex align-items-center">
            <ul class="nav navbar-nav d-xl-none">
                <li class="nav-item"><a class="nav-link menu-toggle" href="javascript:void(0);"><i class="ficon" data-feather="menu"></i></a></li>
            </ul>
        </div>
        <!--  User Data  -->
        <input type="hidden" id="v_g_id" value="<?php echo $v_g_id; ?>"><input type="hidden" id="v_g_fullname" value="<?php echo $v_g_fullname; ?>"><input type="hidden" id="v_g_prefix" value="<?php echo $config_pre_append_link; ?>"><input type="hidden" id="v_g_rolename" value="<?php echo $v_g_rolename; ?>"><input type="hidden" id="v_g_pass_change" value="<?php echo $v_g_pass_change; ?>"><input type="hidden" id="v_g_loginid" value="<?php echo $v_g_loginid; ?>"><input type="hidden" id="v_g_geo_level" value="<?php echo $v_g_geo_level; ?>"><input type="hidden" id="v_g_geo_level_id" value="<?php echo $v_g_geo_level_id; ?>"><input type="hidden" id="v_g_app_version" value="<?php echo $app_version; ?>">
        <ul class="nav navbar-nav align-items-center ml-auto">
            <li class="nav-item d-none d-lg-block switch-moon"><a class="nav-link nav-link-style"><i class="ficon" data-feather="moon"></i></a></li>
            <li class="nav-item dropdown dropdown-user"><a class="nav-link dropdown-toggle dropdown-user-link" id="dropdown-user" href="javascript:void(0);" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <div class="user-nav d-sm-flex d-none">
                        <span class="user-name font-weight-bolder"><?php echo $v_g_fullname; ?></span>
                        <span class="user-status"><?php echo $v_g_rolename; ?></span>
                    </div>
                    <span class="avatar">
                        <img class="round" src="<?php echo $config_pre_append_link; ?>app-assets/images/avatar.png" alt="avatar" height="40" width="40">
                        <span class="avatar-status-online"></span>
                    </span>
                </a>
                <div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdown-user">
                    <a class="dropdown-item" href="javascript:void(0);" data-backdrop="static" data-keyboard="false" data-toggle="modal" data-target="#view-user-details" onclick="getUserProfile(<?php echo $v_g_id; ?>)"><i class="mr-50" data-feather="user"></i> Profile</a>
                    <!--<a class="dropdown-item" href="javascript:void(0);"><i class="mr-50" data-feather="settings"></i> Settings</a> -->
                    <a class="dropdown-item" href="javascript:void(0);" data-backdrop="static" data-keyboard="false" data-toggle="modal" data-target="#change-password"><i class="mr-50" data-feather="lock"></i> Password</a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="<?php echo $config_pre_append_link . 'logout'; ?>"><i class="mr-50" data-feather="power"></i> Logout</a>
                </div>
            </li>
        </ul>
    </div>
</nav>
<!-- END: Header-->