Vue.component('page-body', {
    data: function() {
        return {
            page: 'home', //  page by name home | result | ...
        }
    },
    mounted() {
        /*  Manages events Listening    */

    },
    methods: {

    },
    template: `
    <div>

        <div class="content-body">
            <sample_table/>
        </div>
    </div>
    `
});


Vue.component('sample_table', {
    data: function() {
        return {
            "filterState": 0,
            "expiringDate": "",
        }
    },
    mounted() {
        /*  Manages events Listening    */
        $('.date').flatpickr({
            altInput: true,
            altFormat: "F j, Y",
            dateFormat: "Y-m-d",
            minDate: "today"
        });
    },
    methods: {
        resetDate() {
            if (this.filterState == 0) {
                this.expiringDate = "";
                $('#date').flatpickr({
                    altInput: true,
                    altFormat: "F j, Y",
                    dateFormat: "Y-m-d"
                }).clear();
            }
        },
        downloadBadge(date) {
            console.log(date);

            overlay.show();
            var url = common.DpBadgeService;
            window.open(url + "?qid=003&date=" + date, '_parent');
            overlay.hide();
        },
    },
    computed: {

    },
    template: `

        <div class="row" id="basic-table">

            <div class="col-md-12 col-sm-12 col-12 mb-0">
                <h2 class="content-header-title header-txt float-left mb-0">System Admin</h2>
                <div class="breadcrumb-wrapper">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../admin/log">Home</a></li>
                        <li class="breadcrumb-item active">Provision Device</li>
                    </ol>
                </div>
            </div>

            <div class="col-lg-4 col-md-6 col-12 mt-2">
                <div class="card card-payment">
                    <div class="card-header">
                        <h5 class="text-center text-primary">Provision Device</h5>
                    </div>
                    <div class="card-body">
                        <form action="javascript:void(0);" class="form">
                            <div class="row">
                                <div class="col-12">
                                    <div class="form-group mb-2">
                                        <label for="expire-status">Expiration Period</label>
                                        <select class="form-control expire-status" v-model="filterState" @change="resetDate()">
                                            <option value="0">Never Expire</option>
                                            <option value="1">Expire</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-12" v-show="filterState==1">
                                    <div class="form-group mb-2">
                                        <label for="dater">Select Expiring Date</label>
                                        <input type="text" v-model="expiringDate" id="date" class="form-control date" placeholder="Expiring Date">
                                    </div>
                                </div> 
                                <div class="col-12 mt-2">
                                    <button type="button" class="btn btn-primary btn-block waves-effect waves-float waves-light" @click="downloadBadge(expiringDate)" href="javascript:void(0)">Download Badge</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>


        </div>

    `
});
var vm = new Vue({
    el: "#app",
    data: {},
    methods: {

    },
    template: `
        <div>
            <page-body/>
        </div>
    `
});