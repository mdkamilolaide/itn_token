<?php

#   Css loader
$module = CleanData('module');

$rand = ($server_type === "Demo") ? "?" . rand(0, 1000) : "";
#
if (in_array($module, $config_modules)) {
    //  Load default css
    if (count($config_css_general)) {
        foreach ($config_css_general as $item) {
            echo "<link href='" . $config_pre_append_link . $item . $rand . "' rel='stylesheet' type='text/css' />\r\n";
        }
    }
    #   Load module specific css
    if (count($config_css_structure[$module])) {
        foreach ($config_css_structure[$module] as $item) {
            echo "<link href='" . $config_pre_append_link . $item . $rand . "' rel='stylesheet' type='text/css' />\r\n";
        }
    }
} else {
    #   load default  only
    if (count($config_css_general)) {
        foreach ($config_css_general as $item) {
            echo "<link href='" . $config_pre_append_link . $item . $rand . "' rel='stylesheet' type='text/css' />\r\n";
        }
    }
}
