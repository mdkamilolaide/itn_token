/**
 * Admin / Provision submodule — Vue 3 Composition API in place.
 * Single-card form: pick Never Expire / Expire, optional date, then download badge.
 * Uses Flatpickr (loaded via system_structure.json's submodule.provision deps).
 */

const { ref, onMounted, onBeforeUnmount } = Vue;
const { useApp, useFormat } = window.utils;

/* ------------------------------------------------------------------ */
const PageBody = {
  setup() {
    const page = ref("home");
    return { page };
  },
  template: `
        <div>
            <div class="content-body">
                <sample_table/>
            </div>
        </div>
    `,
};

/* ------------------------------------------------------------------ */
const SampleTable = {
  setup() {
    const filterState = ref(0);
    const expiringDate = ref("");

    let flatpickrInstance = null;

    const resetDate = () => {
      if (filterState.value == 0) {
        expiringDate.value = "";
        if (
          flatpickrInstance &&
          typeof flatpickrInstance.clear === "function"
        ) {
          flatpickrInstance.clear();
        }
      }
    };

    const downloadBadge = (date) => {
      overlay.show();
      var url = common.DpBadgeService;
      window.open(url + "?qid=003&date=" + date, "_parent");
      overlay.hide();
    };

    onMounted(() => {
      // flatpickr is loaded via the submodule.provision deps; guard anyway.
      var $el = $("#date");
      if ($el.length && typeof $el.flatpickr === "function") {
        flatpickrInstance = $el.flatpickr({
          altInput: true,
          altFormat: "F j, Y",
          dateFormat: "Y-m-d",
          minDate: "today",
          onChange: (selectedDates, dateStr) => {
            expiringDate.value = dateStr;
          },
        });
      }
    });

    onBeforeUnmount(() => {
      if (
        flatpickrInstance &&
        typeof flatpickrInstance.destroy === "function"
      ) {
        try {
          flatpickrInstance.destroy();
        } catch (e) {
          /* swallow */
        }
        flatpickrInstance = null;
      }
    });

    return { filterState, expiringDate, resetDate, downloadBadge };
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
                                <div class="col-12" v-show="filterState == 1">
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
    `,
};

useApp({ template: `<div><page-body/></div>` })
  .component("page-body", PageBody)
  .component("sample_table", SampleTable)
  .mount("#app");
