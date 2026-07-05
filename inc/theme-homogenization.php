<?php

if (! defined("ABSPATH")) {
    exit;
}

function onedown_homogenization_html_class($output, $doctype)
{
    if (! _pz("homogenization_enabled", false)) {
        return $output;
    }

    $params_raw = _pz("homogenization_params", "theme\nskin\nlayout\ncolor\nstyle");
    $count      = (int) _pz("homogenization_count", 2);

    $params = array_filter(array_map("trim", explode("\n", $params_raw)));
    $params = array_values($params);

    if (empty($params)) {
        return $output;
    }

    $selected = array();
    $total    = count($params);
    $pick     = min($count, $total);

    if ($pick > 1) {
        $keys = (array) array_rand($params, $pick);
    } else {
        $keys = array(array_rand($params, 1));
    }

    foreach ($keys as $key) {
        $param     = $params[$key];
        $suffix    = substr(wp_hash(uniqid(mt_rand(), true), "nonce"), 0, 4);
        $selected[] = $param . "-" . $suffix;
    }

    $output .= " class=\"" . esc_attr(implode(" ", $selected)) . "\"";

    return $output;
}

add_filter("language_attributes", "onedown_homogenization_html_class", 10, 2);
