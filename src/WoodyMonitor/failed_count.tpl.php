<?php

header('X-VC-TTL: 0');
header('Content-Type: text/plain; charset=UTF-8');
foreach ($data['sites'] as $site) {
    print sprintf('wp_failed_count{database="%s"} %s', $site['site_key'], $site['failed']) . "\n";
}
