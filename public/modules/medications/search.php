<?php
require_once "../../../app/services/NhsApiClient.php";

$api = new NhsApiClient();
$q = $_GET['q'] ?? "";

echo json_encode($api->searchMedication($q));
