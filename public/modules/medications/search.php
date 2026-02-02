<?php
require_once "../../../app/services/NhsApiClient.php";

$q = $_GET['q'] ?? "";
$api = new NhsApiClient();

$results = $api->searchMedication($q);

// Normalise output
$out = [];
foreach ($results as $r) {
    $out[] = [
        "id" => $r["id"] ?? null,
        "name" => $r["name"] ?? "Unknown"
    ];
}

echo json_encode($out);
