<?php

header('Content-Type: text/plain; charset=UTF-8');
foreach ($data['sites'] as $site) {
    print sprintf('wp_async_count{database="%s"} %s', $site['site_key'], $site['failed']) . "\n";
}
