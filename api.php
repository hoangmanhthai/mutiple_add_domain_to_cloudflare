<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $apiKey = $_POST['apiKey'] ?? '';
    $accountId = $_POST['accountId'] ?? '';
    $domains = explode("\n", str_replace("\r", "", $_POST['domains'] ?? ''));
    if (empty($email) || empty($apiKey) || empty($accountId) || empty($domains)) {
        echo "Please fill in all the fields.";
        exit;
    }
    $resultOutput = '';
    foreach ($domains as $domain) {
        $domain = trim($domain);
        if (empty($domain)) continue;

        // Gọi API Cloudflare
        $url = 'https://api.cloudflare.com/client/v4/zones';
        $data = [
            'account' => ['id' => $accountId],
            'name' => $domain,
            'type' => 'full'
        ];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            "X-Auth-Email: $email",
            "X-Auth-Key: $apiKey"
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            $resultOutput .= "Curl error for domain $domain: " . curl_error($ch) . "<br>";
        } else {
            $responseArray = json_decode($response, true);
            if (isset($responseArray['success']) && $responseArray['success'] === true) {
                $nameServers = $responseArray['result']['name_servers'] ?? [];
                $resultOutput .= "Domain: <b$domain<b><br>";
                $resultOutput .= "Name Servers: " . implode(", ", $nameServers) . "<br><br>";
            } else {
                $resultOutput .= "Failed to process domain $domain: " . json_encode($responseArray['errors']) . "<br>";
            }
        }
        curl_close($ch);
    }
    echo $resultOutput;
} else {
    echo "Invalid request method.";
}
