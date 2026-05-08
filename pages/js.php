<?php

#   JS loader
$module = CleanData('module');
$subdomain = CleanData("submodule");

# Dual-mode loader: when the current module is in module_v3, swap the Vue 2
# global build for the Vue 3 global build. Everything else (vendors.min.js,
# axios, toastr, common.js, utils.js, module-specific component files) stays
# the same. With $config_system_structure['module_v3'] = [] (the default),
# this is a no-op and behaviour is bit-identical to baseline.
$module_v3 = isset($config_system_structure['module_v3']) && is_array($config_system_structure['module_v3'])
    ? $config_system_structure['module_v3']
    : [];
$is_v3_module = in_array($module, $module_v3, true);
$vue3_path = "app-assets/vendors/third-parties/vue/vue.global.prod.js";

#
if (in_array($module, $config_modules)) {
    //  Load default js
    if (count($config_js_general)) {
        foreach ($config_js_general as $item) {
            // For v3 modules, swap Vue 2 (vue.js) for Vue 3 global build.
            if ($is_v3_module && strpos($item, '/vue/vue.js') !== false) {
                echo "<script src=" . $config_pre_append_link . $vue3_path . "></script>\r\n";
                continue;
            }
            echo "<script src=" . $config_pre_append_link . $item . "></script>\r\n";
        }
    }
    #   Load module specific js
    if (count($config_js_structure[$module])) {
        $submodule = CleanData("submodule");
        if ($submodule) {
            //  load subdomain
            if (count($config_js_structure[$module]["submodule"])) {
                //Check if Subdomain exists before rendering
                if (array_key_exists($subdomain, $config_js_structure[$module]["submodule"])) {
                    foreach ($config_js_structure[$module]["submodule"][$submodule] as $item) {
                        echo "<script src=" . $config_pre_append_link . $item . '?' . ttCoder(12) . "></script>\r\n";
                    }
                }
            }
        } else {
            //  main domain
            foreach ($config_js_structure[$module]["module"] as $item) {
                echo "<script src=" . $config_pre_append_link . $item . '?' . ttCoder(12) . "></script>\r\n";
            }
        }
        //  load domain


    }
} else {
    #   load default  only
    if (count($config_js_general)) {
        foreach ($config_js_general as $item) {
            if ($is_v3_module && strpos($item, '/vue/vue.js') !== false) {
                echo "<script src=" . $config_pre_append_link . $vue3_path . "></script>\r\n";
                continue;
            }
            echo "<script src=" . $config_pre_append_link . $item . "></script>\r\n";
        }
    }
}

echo $extra_script;
?>
<script>
    $(window).on('load', function() {
        if (feather) {
            feather.replace({
                width: 14,
                height: 14
            });
        }
    });
    const getUserProfile = (userId) => {
        if (!userId) return;

        const url = `${common.DataService}?qid=005&e=${userId}`;
        overlay.show();

        axios.get(url)
            .then(({
                data
            }) => {
                const base = data.base?.[0] || {};
                const finance = data.finance?.[0] || {};
                const role = data.role?.[0] || {};
                const identity = data.identity?.[0] || {};

                // Populate base data
                setText('9_first-name', identity?.first);
                setText('9_middle-name', identity?.middle);
                setText('9_last-name', identity?.last);
                setText('9_gender', identity?.gender);
                setText('9_phone', identity?.phone);
                setText('9_email', identity?.email);

                // Populate finance data
                setText('9_account-name', finance?.account_name);
                setText('9_account-no', finance?.account_no);
                setText('9_bank-name', finance?.bank_name);

                // Populate role data
                setText('9_login-id', base?.loginid);
                setText('9_role', base?.role);
                setText('9_geo-level', base?.geo_level);
                setText('9_geo-string', base?.geo_string);

                $('#view-user-details').modal({
                    backdrop: 'static',
                    keyboard: false
                }).modal('show');
            })
            .catch((error) => {
                alert(`Error loading user profile: ${error.message}`);
            })
            .finally(() => {
                overlay.hide();
            });
    };

    const setText = (id, value) => {
        const el = document.getElementById(id);
        if (el) el.textContent = value || '-';
    };

    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.view-profile-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const userId = this.getAttribute('data-user-id');
                getUserProfile(userId);
            });
        });
    });
</script>