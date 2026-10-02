<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/environment.php';
require_once __DIR__ . '/proxy/config.php';
$GLOBALS['cms_api_load_flags'] = ['careers' => true];
require_once __DIR__ . '/includes/apis.php';
require_once __DIR__ . '/includes/api-adapters.php';
require_once __DIR__ . '/includes/cms-page-helpers.php';
$GLOBALS['cms_careers_seo_page'] = cmsFetchKhalasilinePage('careers-page-khalasiline');
$GLOBALS['cms_job_apply_site_name'] = 'Allenhouse Khalasiline';
require_once __DIR__ . '/shared/includes/job-apply-ui.php';

cmsRunJobApplyPage(isset($jobs_data) && is_array($jobs_data) ? $jobs_data : null);
