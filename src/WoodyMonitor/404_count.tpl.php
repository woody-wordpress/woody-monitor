<?php

header('X-VC-TTL: 0');
header('Content-Type: text/plain; charset=UTF-8');
foreach ($data['sites'] as $site) {
    print sprintf('wp_404_count{database="%s"} %s', $site['site_key'], $site['404_count']) . "\n";
}
