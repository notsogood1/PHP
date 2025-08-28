<?php
// ==============================
// PHP Proxy for Codespaces
// ==============================

// Get the target URL
if (!isset($_GET['url'])) {
    die("Usage: proxy.php?url=https://example.com");
}

$url = filter_var($_GET['url'], FILTER_SANITIZE_URL);
if (!filter_var($url, FILTER_VALIDATE_URL)) {
    die("Invalid URL.");
}

// Fetch content using cURL
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, false);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

$content = curl_exec($ch);
curl_close($ch);

// Rewrite all links so they go through the proxy
$parsedUrl = parse_url($url);
$base = $parsedUrl['scheme'] . "://" . $parsedUrl['host'];

$content = preg_replace_callback(
    '/(href|src)=["\'](.*?)["\']/i',
    function($matches) use ($base) {
        $attr = $matches[1];
        $link = $matches[2];

        // Ignore anchors and javascript
        if (strpos($link, 'javascript:') === 0 || strpos($link, '#') === 0) {
            return $matches[0];
        }

        // Convert relative links to absolute
        if (!preg_match('#^https?://#i', $link)) {
            $link = rtrim($base, '/') . '/' . ltrim($link, '/');
        }

        // Rewrite through proxy
        $link = 'proxy.php?url=' . urlencode($link);

        return $attr . '="' . $link . '"';
    },
    $content
);

// Output content
echo $content;
