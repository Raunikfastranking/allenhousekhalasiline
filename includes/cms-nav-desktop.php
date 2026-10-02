<?php

if (!function_exists('cmsNavItems')) {
    require_once __DIR__ . '/cms-bootstrap.php';
}

$items = $khalasilineNavItems ?? cmsNavItems();
renderCmsNavMenu($items, false, 'ghaziabad');
