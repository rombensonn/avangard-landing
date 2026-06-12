<?php
declare(strict_types=1);

header('Content-Type: application/xml; charset=UTF-8');

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$baseUrl = $scheme . '://' . $host . '/';
$lastmod = date('Y-m-d');
$urls = [
    ['loc' => $baseUrl, 'changefreq' => 'weekly', 'priority' => '1.0'],
    ['loc' => $baseUrl . 'privacy.html', 'changefreq' => 'monthly', 'priority' => '0.4'],
    ['loc' => $baseUrl . 'terms.html', 'changefreq' => 'monthly', 'priority' => '0.4'],
];

echo '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <?php foreach ($urls as $url): ?>
    <url>
        <loc><?= htmlspecialchars($url['loc'], ENT_XML1 | ENT_QUOTES, 'UTF-8'); ?></loc>
        <lastmod><?= htmlspecialchars($lastmod, ENT_XML1 | ENT_QUOTES, 'UTF-8'); ?></lastmod>
        <changefreq><?= htmlspecialchars($url['changefreq'], ENT_XML1 | ENT_QUOTES, 'UTF-8'); ?></changefreq>
        <priority><?= htmlspecialchars($url['priority'], ENT_XML1 | ENT_QUOTES, 'UTF-8'); ?></priority>
    </url>
    <?php endforeach; ?>
</urlset>
