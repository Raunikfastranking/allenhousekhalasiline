<?php

require dirname(__DIR__) . '/proxy/config.php';
require dirname(__DIR__) . '/includes/cms-bootstrap.php';

$menu = cms_curl_get_json(CMS_API_URL . '/menus/branch/' . BRANCH_ID);
$pages = cms_curl_get_json(CMS_API_URL . '/pages?branch_id=' . BRANCH_ID);
$byId = [];
foreach ($pages['data'] ?? [] as $p) {
    if (!empty($p['slug'])) {
        $byId[(string) $p['id']] = $p;
    }
}

function walkMenu(array $items, array $byId): void
{
    foreach ($items as $item) {
        $url = trim($item['url'] ?? '', '/');
        if ($url === '' || $url === '#' || str_starts_with($url, 'http')) {
            walkMenu($item['children'] ?? [], $byId);
            continue;
        }
        $lid = (string) ($item['linkable_id'] ?? '');
        $slug = $byId[$lid]['slug'] ?? '';
        $phpFile = dirname(__DIR__) . '/' . $url . '.php';
        $hasPhp = is_file($phpFile);
        if (!$hasPhp && $slug !== '') {
            echo "/$url => $slug (cms-page route)\n";
        }
        walkMenu($item['children'] ?? [], $byId);
    }
}

walkMenu($menu['data'][0]['menu_items'] ?? [], $byId);
