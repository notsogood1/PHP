<?php
// ==============================
// Full PHP Proxy (CSS, JS, Images)
// ==============================

// Get the target URL
if (!isset($_GET['url'])) {
    die("Usage: proxy.php?url=https://example.com");
}

$url = filter_var($_GET['url'], FILTER_SANITIZE_URL);
if (!filter_var($url, FILTER_VALIDATE_URL)) {
    die("Invalid URL.");
}

// Parse base URL
$parsedUrl = parse_url($url);
$base = $parsedUrl['scheme'] . "://" . $parsedUrl['host'];

// Fetch content
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, false);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
$content = curl_exec($ch);
curl_close($ch);

// Rewrite href, src, and url(...) in CSS to proxy
$content = preg_replace_callback(
    '/(href|src)=["\'](.*?)["\']/i',
    function($matches) use ($base) {
        $attr = $matches[1];
        $link = $matches[2];

        if (strpos($link, 'javascript:') === 0 || strpos($link, '#') === 0) {
            return $matches[0];
        }

        if (!preg_match('#^https?://#i', $link)) {
            $link = rtrim($base, '/') . '/' . ltrim($link, '/');
        }

        $link = 'proxy.php?url=' . urlencode($link);
        return $attr . '="' . $link . '"';
    },
    $content
);

// Rewrite CSS url(...) references
$content = preg_replace_callback(
    '/url\((.*?)\)/i',
    function($matches) use ($base) {
        $link = trim($matches[1], '\'"');
        if (!preg_match('#^https?://#i', $link)) {
            $link = rtrim($base, '/') . '/' . ltrim($link, '/');
        }
        return 'url(proxy.php?url=' . urlencode($link) . ')';
    },
    $content
);

echo $content;
