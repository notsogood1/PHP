<?php
// ============================================
// Secure PHP Proxy for GitHub Codespaces
// ============================================

// --- Security: require a secret key ---
$SECRET_KEY = "mysecret"; // change this to anything you like

if (!isset($_GET['key']) || $_GET['key'] !== $SECRET_KEY) {
    http_response_code(403);
    die("Access denied: invalid key");
}

// --- URL validation ---
if (!isset($_GET['url'])) {
    http_response_code(400);
    die("No URL provided. Usage: proxy.php?key=SECRET&url=https://example.com");
}

$url = filter_var($_GET['url'], FILTER_SANITIZE_URL);

if (!filter_var($url, FILTER_VALIDATE_URL)) {
    http_response_code(400);
    die("Invalid URL.");
}

// --- Fetch with cURL ---
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);      
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

$result = curl_exec($ch);

if (!curl_errno($ch)) {
    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $headers = substr($result, 0, $header_size);
    $body = substr($result, $header_size);

    $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    if ($contentType) {
        header("Content-Type: $contentType");
    }

    echo $body; // Show full page/API response
    exit;
} else {
    http_response_code(500);
    echo "Proxy Error: " . curl_error($ch);
}
curl_close($ch);
