<?php
// rss.php – feed RSS untuk portal berita
require_once 'includes/config.php';
require_once 'includes/functions.php';

header('Content-Type: application/rss+xml; charset=UTF-8');

$stmt = $pdo->query("
    SELECT a.title, a.slug, a.excerpt, a.content, a.created_at,
           u.username AS author_name, c.name AS category_name, a.featured_image
    FROM articles a
    LEFT JOIN users u ON a.author_id = u.id
    LEFT JOIN categories c ON a.category_id = c.id
    WHERE a.status = 'published'
    ORDER BY a.created_at DESC
    LIMIT 20
");

echo '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:content="http://purl.org/rss/1.0/modules/content/">
    <channel>
        <title>NusaRoteMalole - Portal Berita & Informasi Rote</title> 
        <link><?= BASE_URL ?></link>
        <description>Portal berita & informasi terkini seputar Rote, NTT.</description>
        <language>id-ID</language>
        <pubDate><?= date('r') ?></pubDate>
        <lastBuildDate><?= date('r') ?></lastBuildDate>
        <atom:link href="<?= BASE_URL ?>rss.php" rel="self" type="application/rss+xml"/>
        
        <image>
            <url><?= BASE_URL ?>assets/image/1.png</url>
            <title>NusaRoteMalole</title> 
            <link><?= BASE_URL ?></link>
        </image>
        <?php while ($a = $stmt->fetch()): ?>
        <?php
            $link = BASE_URL . 'article.php?slug=' . $a['slug'];
            $pubDate = date('r', strtotime($a['created_at']));
            $description = $a['excerpt'] ?: substr(strip_tags($a['content']), 0, 300) . '...';
        ?>
        <item>
            <title><![CDATA[<?= $a['title'] ?>]]></title>
            <link><?= $link ?></link>
            <guid isPermaLink="true"><?= $link ?></guid>
            <pubDate><?= $pubDate ?></pubDate>
            <author><?= htmlspecialchars($a['author_name']) ?> (Good Dillak)</author>
            <category><?= htmlspecialchars($a['category_name'] ?? 'Uncategorized') ?></category>
            <description><![CDATA[<?= $description ?>]]></description>
            <content:encoded><![CDATA[
                <?php if ($a['featured_image']): ?>
                    <img src="<?= BASE_URL . $a['featured_image'] ?>" style="max-width: 100%; height: auto; display: block; margin-bottom: 10px;" alt="<?= htmlspecialchars($a['title']) ?>">
                <?php endif; ?>
                <?= $a['content'] ?>
            ]]></content:encoded>
        </item>
        <?php endwhile; ?>
    </channel>
</rss>