<?php

header('X-VC-TTL: 0');
header('Content-Type: application/json; charset=UTF-8');
$return = [];
foreach ($data['sites'] as $site) {
    if (empty($site['failed'])) {
        continue;
    }

    $return[$site['site_key']] = $site['failed'];
}

print json_encode($return, JSON_THROW_ON_ERROR);
