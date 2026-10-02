<?php

require dirname(__DIR__) . '/proxy/config.php';
require dirname(__DIR__) . '/includes/cms-bootstrap.php';

$menu = cms_curl_get_json(CMS_API_URL . '/menus/branch/' . BRANCH_ID);
$pages = cms_curl_get_json(CMS_API_URL . '/pages?branch_id=' . BRANCH_ID);
$byId = [];
foreach ($pages['data'] ?? [] as $p) {
    $byId[(string) $p['id']] = $p['slug'];
}

function walkMenu(array $items, array $byId): void
{
    foreach ($items as $item) {
        $url = trim($item['url'] ?? '', '/');
        $lid = (string) ($item['linkable_id'] ?? '');
        if ($url && $lid && str_contains($item['linkable_type'] ?? '', 'Page')) {
            $menuSlug = $byId[$lid] ?? '';
            $resolved = cmsResolveSlugFromPath($url);
            if ($menuSlug && $resolved && $menuSlug !== $resolved) {
                echo "MISMATCH: /$url menu=$menuSlug resolved=$resolved\n";
            }
            $phpFile = dirname(__DIR__) . "/$url.php";
            if (is_file($phpFile) && preg_match("/cms_page_slug\s*=\s*['\"]([^'\"]+)/", file_get_contents($phpFile), $m)) {
                if ($m[1] !== $menuSlug) {
                    echo "PHP STALE: $url.php uses {$m[1]} but menu=$menuSlug\n";
                }
            }
        }
        walkMenu($item['children'] ?? [], $byId);
    }
}

walkMenu($menu['data'][0]['menu_items'] ?? [], $byId);
