(function onLoadScript() {
    // Manipulate the UI for Map to sit
    var element1 = document.querySelector('body');
    element1.classList.remove('menu-expanded');
    element1.classList.add('menu-collapsed');
    document.querySelector('.main-menu, .navbar-header').classList.remove('expanded');
    document.querySelector('.nav.navbar-nav.align-items-center.ml-auto').remove();
})();

//Get Current URL Datas
function getUrlVar(name) {
    let url = window.location.href;
    let paramaters = (new URL(url)).searchParams;
    return paramaters.get(name);
}


//  initialize and add the map
async function runMap() {
    //  the map

    var action = getUrlVar("action");
    var mob_date = getUrlVar('mob_date') == null ? '' : getUrlVar('mob_date');
    var start_date = mob_date.split(" to ")[0] == undefined ? '' : mob_date.split(" to ")[0];
    var end_date = mob_date.split(" to ")[1] == undefined ? '' : mob_date.split(" to ")[1];

    // Await the default state lookup so the qid=606 fallback below has a
    // valid stateid when this is the first visit (localStorage is empty).
    try {
        const sysResp = await axios.get(common.DataService + "?qid=gen007");
        if (sysResp.data && sysResp.data.data && sysResp.data.data.length > 0) {
            const stateId = sysResp.data.data[0].stateid;
            localStorage.setItem("state_id", stateId);
            const stateEl = document.querySelector('#state_id');
            if (stateEl) stateEl.value = stateId;
        }
    } catch (error) {
        alert.Error("ERROR", "Check your Internet, kindly Refresh your browser " + error);
    }

    if (action === null) {
        var today = new Date();
        var date_now = today.getFullYear() + '-' + String((today.getMonth() + 1)).padStart(2, '0') + '-' + String(today.getDate()).padStart(2, '0');
        var url = 'qid=606&stateid=' + localStorage.getItem("state_id") + '&s_date=' + date_now;

    } else if (action == 1) {
        // Load Mobilization Data by Mobilizer ID and Ward ID\
        let wardid = getUrlVar('wardid');
        let mobilizerid = getUrlVar('mob');

        //URL Data for the End Point
        var url = 'qid=601&mob=' + mobilizerid + '&wardid=' + wardid + '&s_date=' + start_date + '&e_date=' + end_date;
    } else if (action == 2) {
        // Load Data by DP
        let dpid = getUrlVar('dp');
        let wardid = getUrlVar('wardid');

        //URL Data for the End Point
        var url = 'qid=603&dpid=' + dpid + '&wardid=' + wardid + '&s_date=' + start_date + '&e_date=' + end_date;
    } else if (action == 3) {
        // Load Data by Ward
        let mob_date = getUrlVar('mob_date');
        let wardid = getUrlVar('ward');

        //URL Data for the End Point
        var url = 'qid=604&wardid=' + wardid + '&s_date=' + mob_date;
    } else if (action == 4) {
        // Load Data by LGA
        let lgaid = getUrlVar('lga');

        //URL Data for the End Point
        var url = 'qid=605&lgaid=' + lgaid + '&s_date=' + mob_date;
    } else if (action == 5) {
        // Load Data by State
        let stateid = getUrlVar('stateid');

        //URL Data for the End Point
        var url = 'qid=606&stateid=' + stateid + '&s_date=' + mob_date;
    }

    //Load function based on the choosen Options
    overlay.show();

    //  get all Mobilization Data
    let mob_geo_data = new Promise((resolve, reject) => {

        $.ajax({
            url: common.DataService,
            type: "POST",
            data: url,
            dataType: 'json',
            success: function(data) {
                loadLGA();
                resolve(data.data)
            }
        });

    });


    let mob_result = await mob_geo_data; //  wait till the promise resolves (*)

    // Defensive defaults — if the API didn't return a usable map block
    // (no data for the selected geo, or no rows at all), centre on Abuja
    // and let the (empty) marker loop run a no-op so overlay.hide() runs.
    var mapBlock = (mob_result && mob_result.map) || {};
    var lat = parseFloat(mapBlock.lat);
    var lng = parseFloat(mapBlock.lng);
    var zoom = parseInt(mapBlock.zoom);
    if (!isFinite(lat) || !isFinite(lng)) { lat = 9.0765; lng = 7.3986; }
    if (!isFinite(zoom)) zoom = 7;

    var mapEl = document.getElementById("map");
    if (!mapEl) { overlay.hide(); return; }

    var map;
    try {
        map = new google.maps.Map(mapEl, {
            zoom: zoom,
            center: { lat: lat, lng: lng },
            mapTypeId: google.maps.MapTypeId.ROADMAP,
            streetViewControl: false,
            rotateControl: true,
            zoomControl: true,
        });
    } catch (e) {
        console.error('[map] Google Maps construction failed:', e);
        overlay.hide();
        return;
    }

    // Add Marker
    var markers = (mob_result && Array.isArray(mob_result.mob_data)) ? mob_result.mob_data : [];
    for (var a = 0; a < markers.length; a++) {
        try { addMarker(markers[a]); } catch (e) { console.warn('[map] addMarker failed:', e); }
    }

    function addMarker(coords) {
        var marker = new google.maps.Marker({
            position: { lat: parseFloat(coords.lat), lng: parseFloat(coords.lng) },
            map: map
            // ,animation: google.maps.Animation.BOUNCE
        });

        var infoWindow = new google.maps.InfoWindow({
            content: 'Name: <b>' + coords.household + '</b><br>Phone: <b>' + coords.hoh_phone + '</b><br>Gender: <b>' + coords.hoh_gender + '</b><br>Family size: <b>' + coords.family_size + '</b><br>Allocated Net: <b>' + coords.allocated_net + '</b><br>e-Token Serial: <b>' + coords.etoken_serial + '</b><br>HH Mobilizer: <b>' + coords.mobilizer + '</b><br>Date: <b>' + coords.collected_date + '</b> '
        });

        marker.addListener('click', function() {
            infoWindow.open(map, marker);
        });
    }

    overlay.hide();
}

async function loadLGA() {

    await axios.post(common.DataService + "?qid=gen003", JSON.stringify(localStorage.getItem("state_id")))
        .then(function(response) {
            const allLga = response.data.data;
            // Select all LGA Filters
            var select = document.querySelectorAll('.filter-lga');
            // Add Options to all the filters
            for (let i = 0; i < select.length; i++) {
                // Create the option element with value
                for (index in allLga) {
                    select[i].options[select[i].options.length] = new Option(allLga[index].lga, allLga[index].lgaid);
                }
            }

        })
        .catch(function(error) {
            overlay.hide();
            alert.Error("ERROR", "Check your Internet, kindly Refresh your browser " + error);
        });
}

async function loadWard(lgaid) {
    /*  Manages the loading of Geo Level data */
    var self = this;
    var url = common.DataService;
    overlay.show();

    await axios
        .get(url + "?qid=gen005&e=" + lgaid)
        .then(function(response) {

            const wardData = response.data.data; //All Ward Data
            // Select all Ward Filters
            var select = document.querySelectorAll('.filter-ward');

            // Reset Mobilizer List Select on LGA Change
            var mob_select = document.querySelectorAll('.filter-mob');
            for (let i = 0; i < mob_select.length; i++) {
                // Remove all Select Options on change
                mob_select[i].options.length = 1;
            }

            for (let i = 0; i < select.length; i++) {
                // Remove all Select Options on change
                select[i].options.length = 1;

                // Add Option or Create the option element with value
                for (index in wardData) {
                    select[i].options[select[i].options.length] = new Option(wardData[index].ward, wardData[index].wardid);
                }
            }
            overlay.hide();
        })
        .catch(function(error) {
            overlay.hide();
            alert.Error("ERROR", error);
        });
}

async function loadMobilizerPerWard(wardid) {
    // Load all Mobilizer using Ward ID

    var self = this;
    var url = common.DataService;
    overlay.show();
    await axios.get(url + "?qid=027&wardid=" + wardid)
        .then(function(response) {

            const mobilizerData = response.data.data; //All Ward Data

            // Select all Ward Filters
            var select = document.querySelectorAll('.filter-mob');
            // Add Options to all the filters
            for (let i = 0; i < select.length; i++) {
                // Remove all Select Options on change
                select[i].options.length = 1;

                // Create the option element with value
                for (index in mobilizerData) {
                    select[i].options[select[i].options.length] = new Option(mobilizerData[index].first + ' ' + mobilizerData[index].last + '  (' + mobilizerData[index].loginid + ')', mobilizerData[index].loginid);
                }
            }

            overlay.hide();
        })
        .catch(function(error) {
            overlay.hide();
            alert.Error("ERROR", error);
        });
}

async function loadDpPerWard(wardid) {
    // Load all Mobilizer using Ward ID

    var self = this;
    var url = common.DataService;
    overlay.show();
    await axios.get(url + "?qid=gen006&wardid=" + wardid)
        .then(function(response) {

            const dpData = response.data.data; //All Ward Data
            // console.log(dpData)
            // Select all Ward Filters
            var select = document.querySelectorAll('.filter-dp');
            // Add Options to all the filters
            for (let i = 0; i < select.length; i++) {
                // Remove all Select Options on change
                select[i].options.length = 1;

                // Create the option element with value
                for (index in dpData) {
                    select[i].options[select[i].options.length] = new Option(dpData[index].dp, dpData[index].dpid);
                }
            }

            overlay.hide();
        })
        .catch(function(error) {
            overlay.hide();
            alert.Error("ERROR", error);
        });
}

(function loadDefaultFilter() {
    // $('#filter-modal').modal('show');
    $('.date').flatpickr({
        altInput: true,
        altFormat: "F j, Y",
        dateFormat: "Y-m-d",
        mode: "range"
    });

    $('.single_date').flatpickr({
        altInput: true,
        altFormat: "F j, Y",
        dateFormat: "Y-m-d"
    });


    var self = this;
    // Default Form Presentation based on the choosen Filter
    document.getElementById('map-filter-0').classList.remove('d-none');

    // Check onchange of the Filtering Options 
    document.getElementById('filterOptions').onchange = function() {
        let current_option = this.value;

        let all_filter_box = document.querySelectorAll('.filter-inputs');
        //Hide all Box
        for (let i = 0; i < all_filter_box.length; i++) {
            all_filter_box[i].classList.add('d-none');
        }

        // Unhide the selected Form Box
        let current_box = document.getElementById('map-filter-' + current_option);
        current_box.classList.remove('d-none'); // Unhide Selected Filter Option boxDecorationBreak:

    };

    // Don't Send Empty Values
    $('form').submit(function(e) {
        var emptyinputs = $(this).find('select').filter(function() {
            return !$.trim(this.value).length;
        }).prop('disabled', true);

        var emptyinputs = $(this).find('input').filter(function() {
            return !$.trim(this.value).length;
        }).prop('disabled', true);
    });

})();