<?php

class NhsApiClient {

    private $baseUrl = "https://api.nhs.uk"; // Example base
    private $apiKey  = "YOUR_NHS_API_KEY";

    private function request($endpoint, $params = []) {
        $url = $this->baseUrl . $endpoint . "?" . http_build_query($params);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                "subscription-key: {$this->apiKey}",
                "Accept: application/json"
            ]
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }

    public function searchMedication($query) {
        return $this->request("/medicines", ["q" => $query]);
    }

    public function getMedicationDetails($id) {
        return $this->request("/medicines/$id");
    }

    public function searchConditions($query) {
        return $this->request("/conditions", ["q" => $query]);
    }
}
