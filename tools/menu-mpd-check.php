<?php

require dirname(__DIR__) . '/proxy/config.php';
require dirname(__DIR__) . '/includes/cms-bootstrap.php';

$menu = cms_curl_get_json(CMS_API_URL . '/menus/branch/' . BRANCH_ID);
function walk(array $items, int $depth = 0): void
{
    foreach ($items as $item) {
        $url = trim($item['url'] ?? '', '/');
        $name = $item['name'] ?? '';
        if ($url !== '' && (stripos($url, 'mpd') !== false || stripos($url, 'mandatory') !== false || stripos($url, 'document') !== false || stripos($name, 'disclosure') !== false)) {
            echo str_repeat('  ', $depth) . "$name => /$url (linkable_id=" . ($item['linkable_id'] ?? '') . ")\n";
        }
        walk($item['children'] ?? [], $depth + 1);
    }
}
walk($menu['data'][0]['menu_items'] ?? []);

echo 'resolve mandatory-public-disclosure: ' . (cmsResolveSlugFromPath('mandatory-public-disclosure') ?? 'null') . PHP_EOL;
echo 'resolve documents-and-information: ' . (cmsResolveSlugFromPath('documents-and-information') ?? 'null') . PHP_EOL;
