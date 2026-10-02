<?php
$apiUrl = "https://allenp.superhouseerp.com/api/form-enquiry/get-active-sessions";
$response = file_get_contents($apiUrl);

if ($response === FALSE) {
    exit; // silent fail for frontend
}

$data = json_decode($response, true);
if (!is_array($data)) {
    exit;
}
 
foreach ($data as $item) {
    if (!empty($item['Session'])) {
        echo '<option value="' . htmlspecialchars($item['Session']) . '">'
            . htmlspecialchars($item['Session']) .
        '</option>';
    }
}
?>