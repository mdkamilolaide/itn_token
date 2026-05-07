<style>
    .app-content {
        padding-top: 0 !important;
        padding-left: 0 !important;
        width: calc(100vw) !important;
        margin-right: 15px !important;
    }

    html .content.app-content {
        padding: 0 !important;
        margin: 0 !important;
    }

    #map {
        height: calc(100vh - 47px) !important;
        margin-bottom: 0 !important;
        margin-top: 0 !important;
        box-shadow: none !important;
        margin-left: 80px;
        /* background-color: red; */
    }

    .header-navbar.navbar {
        display: none !important;
    }

    .header-navbar-shadow {
        display: none !important;
    }

    .container-xxl {
        max-width: calc(100vw) !important;
    }

    .app-content {
        margin: 0 !important;
        padding: 0 !important;
        min-height: calc(100vh - 70px) !important;
    }

    #filter_map {
        position: absolute !important;
        right: 10px !important;
        top: 53px;
        z-index: 999 !important;
        padding: 10px 14px 14px 10px;
        border-radius: 0 !important;
        box-shadow: rgb(255 255 255 / 95%) 0px 1px 4px -2px;
        overflow: hidden;
        text-align: center !important;
        vertical-align: middle;
    }

    #filter_map span {
        font-size: 1.4rem !important;
        font-weight: normal !important;
    }

    @media (max-width: 1200px) {
        .header-navbar.navbar {
            display: inline-block !important;
            width: 68px;
            min-height: 0 !important;
            top: 10px;
            position: absolute !important;
            box-shadow: rgb(0 0 0 / 30%) 0px 1px 4px -1px;
            left: 202px;
        }

        .header-navbar.floating-nav {
            position: absolute !important;
            margin: 0 !important;
            padding: 0 !important;
            width: 50px !important;
            border-radius: 0 !important;
            border-bottom-right-radius: 2px;
            border-top-right-radius: 2px;
            box-shadow: rgb(0 0 0 / 30%) 0px 1px 4px -1px;
        }

        html body .app-content {
            padding-top: 0 !important
        }

        .navbar-floating .navbar-container:not(.main-menu-content) {
            padding: 0.623rem 0.6rem;
        }

        #map {
            margin-left: 0px;
            /* background-color: red; */
        }
    }
</style>

<?php
// $privilege = $_SESSION[$instance_token.'privileges'];

$privi = 'mobilization';
if (IsPrivilegeInArray(json_decode($privilege, true), $privi)) {

?>
    <noscript>
        <strong>We're sorry, this app doesn't work properly without JavaScript enabled. Please enable it to continue.</strong>
    </noscript>
    <!-- Page container -->
    <section id="dashboard-analytics">
        <button class="btn btn-sm btn-round btn-primary" id="filter_map" data-backdrop="static" data-keyboard="false" data-toggle="modal" data-target="#filter-modal"><span class="feather icon-filter"></span></button>
        <div id="map">



        </div>

        <!-- Modal to Move State Netcard starts-->
        <div class="modal modal-slide-in move modal-primary" id="filter-modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-scrollable modal-xl">
                <div class="add-new-user modal-content pt-0" id="state-form">
                    <button type="reset" class="close" data-dismiss="modal">×</button>
                    <div class="modal-header mb-1">
                        <h5 class="modal-title font-weight-bolder" id="exampleModalLabel">Mobilization Map Filter</h5>
                    </div>
                    <div class="modal-body mt-0 flex-grow-1 vertical-wizard">
                        <div class="card mt-0 pb-0 mb-1" style="background: rgba(115,103,240,.12) !important; border-radius: 0.357rem!important;">
                            <div class="card-body">
                                <div class="form-group mb-0">
                                    <select placeholder="Filter Options" class="form-control filter-options" name="action" id="filterOptions">
                                        <option value="0">Choose Map Filtering Option</option>
                                        <option value="1">Filter By Mobilizer</option>
                                        <option value="2">Filter By DP</option>
                                        <option value="3">Filter By Ward</option>
                                        <option value="4">Filter By LGA</option>
                                        <option value="5">Filter By State</option>
                                    </select>
                                </div>
                            </div>

                        </div>


                        <div id="map-filter-0" class="filter-inputs d-none mt-3">

                            <div class="col-12 text-primary">
                                <p class="text-center"><small>No Filter Selected</small></p>
                            </div>
                        </div>

                        <form id="map-filter-1" class="filter-inputs d-none" method="get">

                            <div class="form-group mb-1">
                                <label class="form-label full" for="mob-lga">*Choose LGA</label>
                                <select placeholder="Filter Options" required class="form-control filter-lga" onchange="loadWard(this.value)" name="lga">
                                    <option value="">Choose LGA</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="form-label full" for="ward">*Choose Ward</label>
                                <select placeholder="Choose a Ward" required class="form-control filter-ward" onchange="loadMobilizerPerWard(this.value)" name="ward" id="ward">
                                    <option value="">Choose Ward</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="form-label full" for="ward">*Choose Mobilizer</label>
                                <select placeholder="Choose a Ward" required class="form-control filter-mob" name="mob" id="mob">
                                    <option value="">Choose Mobilizer</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="form-label full" for="mob-date">Mobilization Date Range</label>
                                <input type="date" placeholder="Mobilization Date Range" class="form-control date mob-date" name="mob_date">
                            </div>

                            <div class="mt-2 filter">
                                <button type="submit" name="action" value="1" class="btn btn-primary mr-1 data-submit">Load</button>
                                <button type="reset" class="btn btn-outline-secondary" data-dismiss="modal">Cancel</button>
                            </div>

                        </form>

                        <form id="map-filter-2" class="filter-inputs d-none">

                            <div class="form-group mb-1">
                                <label class="form-label full" for="mob-lga">*Choose LGA</label>
                                <select placeholder="Filter Options" required class="form-control filter-lga" onchange="loadWard(this.value)" name="lga">
                                    <option value="">Choose LGA</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="form-label full" for="ward">*Choose Ward</label>
                                <select placeholder="Choose a Ward" required class="form-control filter-ward" onchange="loadDpPerWard(this.value)" name="ward">
                                    <option value="">Choose Ward</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="form-label full" for="dp">*Choose DP</label>
                                <select placeholder="Choose a DP" required class="form-control filter-dp" name="dp" id="dp">
                                    <option value="">Choose DP</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="form-label full" for="mob-date">Mobilization Date Range</label>
                                <input type="date" placeholder="Mobilization Date Range" class="form-control date mob-date" name="mob_date">
                            </div>

                            <div class="mt-2 filter">
                                <button type="submit" name="action" value="2" class="btn btn-primary mr-1 data-submit">Load</button>
                                <button type="reset" class="btn btn-outline-secondary" data-dismiss="modal">Cancel</button>
                            </div>
                        </form>

                        <form id="map-filter-3" class="filter-inputs d-none">

                            <div class="form-group mb-1">
                                <label class="form-label full" for="mob-lga">*Choose LGA</label>
                                <select placeholder="Filter Options" required class="form-control filter-lga" onchange="loadWard(this.value)" name="lga">
                                    <option value="">Choose LGA</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="form-label full" for="ward">*Choose Ward</label>
                                <select placeholder="Choose a Ward" required class="form-control filter-ward" name="ward">
                                    <option value="">Choose Ward</option>
                                </select>
                            </div>

                            <div class="form-group date_filter">
                                <label class="form-label full" for="mob-date">*Mobilization Date</label>
                                <input type="date" placeholder="Mobilization Date" required class="form-control single_date mob-date" name="mob_date">
                            </div>

                            <div class="mt-2 filter">
                                <button type="submit" name="action" value="3" class="btn btn-primary mr-1 data-submit">Load</button>
                                <button type="reset" class="btn btn-outline-secondary" data-dismiss="modal">Cancel</button>
                            </div>

                        </form>

                        <form id="map-filter-4" class="filter-inputs d-none">

                            <div class="form-group mb-1">
                                <label class="form-label full" for="mob-lga">*Choose LGA</label>
                                <select placeholder="Filter Options" required class="form-control filter-lga" onchange="loadWard(this.value)" name="lga">
                                    <option value="">Choose LGA</option>
                                </select>
                            </div>

                            <div class="form-group date_filter">
                                <label class="form-label full" for="mob-date">*Mobilization Date</label>
                                <input type="date" placeholder="Mobilization Date" required class="form-control single_date mob-date" name="mob_date">
                            </div>

                            <div class="mt-2 filter">
                                <button type="submit" name="action" value="4" class="btn btn-primary mr-1 data-submit">Load</button>
                                <button type="reset" class="btn btn-outline-secondary" data-dismiss="modal">Cancel</button>
                            </div>

                        </form>

                        <form id="map-filter-5" class="filter-inputs d-none">

                            <div class="form-group date_filter">
                                <label class="form-label full" for="mob-date">*Mobilization Date</label>
                                <input type="date" placeholder="Mobilization Date" required class="form-control single_date mob-date" name="mob_date">
                                <input type="hidden" value="" name="stateid" id="state_id">
                            </div>

                            <div class="mt-2 filter">
                                <button type="submit" name="action" value="5" class="btn btn-primary mr-1 data-submit">Load</button>
                                <button type="reset" class="btn btn-outline-secondary" data-dismiss="modal">Cancel</button>
                            </div>
                        </form>

                    </div>
                </div>
            </div>
        </div>
        <!-- Modal to Move State Netcard Ends-->
    </section>
    <!-- Page container end -->
    <!-- Async script executes immediately and must be after any DOM elements used in callback. -->

    <?php
    $extra_script = "";
    $extra_script .= '<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyDNj94sO6Ly0yBQKStyUa2Vej7viKe4-54&callback=runMap&libraries=&v=weekly&loading=async" async></script>';
    $extra_script .= '<script src="' . $config_pre_append_link . 'app-assets/app/mobilization/map.js' . '"></script>';
    ?>


<?php
} else {
    error_msg("FATAL ERROR:: Privilege abuse fatal error, you did not have privilege to access this page, kindly logout and login again.");
}

?>