<?php

require dirname(__DIR__) . '/proxy/config.php';

$menu = cms_curl_get_json(CMS_API_URL . '/menus/branch/' . BRANCH_ID);
if (!is_array($menu)) {
    echo "menu fetch failed\n";
    exit(1);
}

function walk(array $items, int $depth = 0): void
{
    foreach ($items as $item) {
        $url = trim($item['url'] ?? '', '/');
        $name = $item['name'] ?? '';
        $type = $item['linkable_type'] ?? '';
        echo str_repeat('  ', $depth) . "$name => /$url type=$type id=" . ($item['linkable_id'] ?? '') . "\n";
        walk($item['children'] ?? [], $depth + 1);
    }
}
walk($menu['data'][0]['menu_items'] ?? []);
