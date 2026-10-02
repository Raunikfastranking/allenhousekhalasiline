<?php
require_once dirname(__DIR__) . '/proxy/config.php';

$endpoint = 'boards/' . SCHOOL_ID . '/' . BRANCH_ID;
$boards = null;
if (function_exists('cms_curl_get_json') && defined('CMS_API_URL')) {
    $boards = cms_curl_get_json(rtrim(CMS_API_URL, '/') . '/' . $endpoint);
}
if (!is_array($boards)) {
    $boards = cms_fetch_json_endpoint($endpoint);
}

$rows = [];
$isList = false;
if (function_exists('array_is_list')) {
    $isList = array_is_list($boards ?? null);
} elseif (is_array($boards)) {
    $isList = array_keys($boards) === range(0, count($boards) - 1);
}
if (is_array($boards['data']['data'] ?? null)) {
    $rows = $boards['data']['data'];
} elseif (is_array($boards['data'] ?? null)) {
    $rows = $boards['data'];
} elseif ($isList) {
    $rows = $boards;
}

echo "<option value='' disabled selected>Select Board / Curriculum</option>";
if ($rows === []) {
    echo "<option value='' disabled>Boards unavailable</option>";
    return;
}

foreach ($rows as $board) {
    if (!is_array($board)) {
        continue;
    }
    $id = trim((string) ($board['board_id'] ?? $board['id'] ?? ''));
    $name = trim((string) ($board['board_name'] ?? $board['name'] ?? ''));
    if ($id === '' || $name === '') {
        continue;
    }
    echo '<option value="' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '">'
        . htmlspecialchars($name, ENT_QUOTES, 'UTF-8')
        . '</option>';
}