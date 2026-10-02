<?php

require dirname(__DIR__) . '/proxy/config.php';
require dirname(__DIR__) . '/includes/cms-bootstrap.php';

$slug = 'mpd-page-khalasiline';
$page = cmsFetchPageBySlug($slug, true);
$data = $page['data'] ?? [];
echo 'slug=' . ($data['slug'] ?? 'missing') . PHP_EOL;
echo 'sections=' . count($data['sections'] ?? []) . PHP_EOL;
echo 'published=' . (cmsPageIsPublished($page) ? 'yes' : 'no') . PHP_EOL;

$resolved = cmsResolveSlugFromPath('mandatory-public-disclosure');
echo 'resolved_mpd=' . ($resolved ?? 'null') . PHP_EOL;
