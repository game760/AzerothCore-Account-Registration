<?php
/**
 * 魔兽世界服务器首页 - 包含新闻和公告
 */
// 加载配置
require_once 'core.php';
require_once 'db_config.php';

// 从JSON文件读取新闻和公告数据
$jsonData = file_get_contents('news.json');
$data = json_decode($jsonData, true);

// 提取设置
$settings = $data['settings'];
$newsCount = $settings['news_count'];
$announcementCount = $settings['announcement_count'];
$truncateLength = $settings['content_truncate_length'];

// 分离新闻和公告
$newsItems = array_filter($data['items'], function($item) {
    return $item['type'] === 'news';
});

$announcements = array_filter($data['items'], function($item) {
    return $item['type'] === 'announcement';
});

// 排序函数：置顶的在前，然后按日期降序
usort($newsItems, function($a, $b) {
    if ($a['pinned'] != $b['pinned']) {
        return $b['pinned'] - $a['pinned'];
    }
    return strtotime($b['date']) - strtotime($a['date']);
});

usort($announcements, function($a, $b) {
    if ($a['pinned'] != $b['pinned']) {
        return $b['pinned'] - $a['pinned'];
    }
    return strtotime($b['date']) - strtotime($a['date']);
});

// 限制显示数量
$newsItems = array_slice($newsItems, 0, $newsCount);
$announcements = array_slice($announcements, 0, $announcementCount);

// 内容截断函数
function truncateContent($content, $length, $id, $type) {
    if (mb_strlen($content) <= $length) {
        return $content;
    }
    return mb_substr($content, 0, $length) . '... <a href="detail.php?type=' . $type . '&id=' . $id . '" class="read-more">查看详情</a>';
}
?>
<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AzerothCore - 魔兽世界私人服务器</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/font-awesome@4.7.0/css/font-awesome.min.css" rel="stylesheet">
    <style>
        :root { --wow-gold: #ffd700; }
        body { margin: 0; padding: 0; background: #000; color: #fff; font-family: Arial, sans-serif;  position: relative; min-height: 100vh; }
        body::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url('./images/bg.jpg');
            background-size: cover; /* 覆盖整个区域 */
            background-position: center; /* 居中显示 */
            background-attachment: fixed; /* 固定背景不随滚动移动 */
            opacity: 0.3; /* 透明度90% */
            z-index: -1; /* 确保背景在内容下方 */
        }
        .wow-nav { background: #1a1a1a; padding: 15px 0; }
        .nav-container { max-width: 1200px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; padding: 0 20px; }
        .nav-logo { color: var(--wow-gold); font-size: 24px; font-weight: bold; text-decoration: none; display: flex; align-items: center; gap: 10px; }
        .nav-links { list-style: none; display: flex; gap: 30px; margin: 0; padding: 0; }
        .nav-links a { color: #fff; text-decoration: none; font-size: 16px; display: flex; align-items: center; gap: 5px; }
        .nav-links a.active { color: var(--wow-gold); }
        .bg-overlay { max-width: 1200px; margin: 30px auto; padding: 0 20px; }
        .page-title { color: var(--wow-gold); border-bottom: 2px solid #333; padding-bottom: 10px; display: flex; align-items: center; gap: 10px; }
        .content-section { display: flex; gap: 30px; margin-top: 20px; }
        .status-card { background: #222; padding: 20px; border-radius: 8px; }
        .status-title { color: #fff; margin-top: 0; display: flex; align-items: center; gap: 10px; }
        .status-item { margin: 15px 0; display: flex; justify-content: space-between; }
        .status-label { color: #aaa; }
        .status-value { color: #fff; }
        .status-value.online { color: #0f0; }
        .main-content { flex: 2; }
        .sidebar { flex: 1; }
        .sidebar-section { background: #222; padding: 20px; border-radius: 8px; margin-bottom: 30px; }
        .sidebar-section h3 { color: var(--wow-gold); margin-top: 0; display: flex; align-items: center; gap: 10px; }
        .link-list { list-style: none; padding: 0; margin: 0; }
        .read-more { color: #69ccf0; text-decoration: none; transition: color 0.3s; }
        .read-more:hover { color: var(--wow-gold); }
    </style>
</head>
<body>
    <!-- 导航栏 -->
    <nav class="wow-nav">
        <div class="nav-container">
            <a href="index.php" class="nav-logo">
                <i class="fa fa-shield"></i>
                艾泽拉斯守护者
            </a>
            <ul class="nav-links">
                <li><a href="index.php" class="active"><i class="fa fa-home"></i> 首页</a></li>
                <li><a href="account.php"><i class="fa fa-user-plus"></i> 账号注册</a></li>
                <li><a href="character.php"><i class="fa fa-list-ol"></i> 英雄榜</a></li>
                <li><a href="downloads.php"><i class="fa fa-download"></i> 下载专区</a></li>
                <li><a href="downloads.php"><i class="fa fa-comments"></i> 论坛</a></li>
            </ul>
        </div>
    </nav>
    
    <!-- 服务器状态概览 -->
    <div class="bg-overlay">
        <h2 class="page-title"><i class="fa fa-server"></i> 服务器状态</h2>
        <div class="content-section">
            <div class="status-card" style="flex: 1; min-width: 300px;">
                <h3 class="status-title"><i class="fa fa-info-circle"></i> 服务器信息</h3>
                <div class="status-item">
                    <span class="status-label">状态：</span>
                    <span class="status-value online">在线</span>
                </div>
                <div class="status-item">
                    <span class="status-label">人口：</span>
                    <span class="status-value">中等</span>
                </div>
                <div class="status-item">
                    <span class="status-label">在线时间：</span>
                    <span class="status-value">99.8%</span>
                </div>
            </div>
            <div class="status-card" style="flex: 1; min-width: 300px;">
                <h3 class="status-title"><i class="fa fa-users"></i> 阵营统计</h3>
                <div class="status-item">
                    <span class="status-label">联盟：</span>
                    <span class="status-value">48%</span>
                </div>
                <div class="status-item">
                    <span class="status-label">部落：</span>
                    <span class="status-value">52%</span>
                </div>
                <div class="status-item">
                    <span class="status-label">总角色数：</span>
                    <span class="status-value">1,287</span>
                </div>
                <div class="status-item">
                    <span class="status-label">今日新角色：</span>
                    <span class="status-value">24</span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- 新闻和公告区域 -->
    <div class="bg-overlay">
        <h2 class="page-title"><i class="fa fa-newspaper-o"></i> 新闻与公告</h2>
        <div class="content-section">
            <!-- 新闻列表（左侧显示） -->
            <div class="main-content">
                <h3 style="color: var(--wow-gold); margin-top: 0; margin-bottom: 20px;"><i class="fa fa-newspaper-o"></i> 最新新闻</h3>
                <?php foreach ($newsItems as $item) : ?>
                    <div style="background: #222; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                        <div style="display: flex; gap: 20px; margin-bottom: 15px;">
                            <img src="<?= $item['image'] ?>" style="width: 150px; height: 100px; object-fit: cover; border-radius: 4px;" alt="<?= $item['title'] ?>">
                            <div>
                                <h4 style="margin: 0; color: #fff;"><?= $item['title'] ?> <?= $item['pinned'] ? '<span style="color: #f00;">[置顶]</span>' : '' ?></h4>
                                <p style="margin: 5px 0 0 0; color: #aaa; font-size: 14px;">
                                    <i class="fa fa-calendar"></i> <?= $item['date'] ?> | <i class="fa fa-user"></i> <?= $item['author'] ?>
                                </p>
                            </div>
                        </div>
                        <p style="color: #ddd; margin: 0; line-height: 1.6;">
                            <?= truncateContent($item['content'], $truncateLength, $item['id'], $item['type']) ?>
                        </p>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- 公告（右侧显示） -->
            <div class="sidebar">
                <div class="sidebar-section">
                    <h3><i class="fa fa-bullhorn"></i> 公告</h3>
                    <?php foreach ($announcements as $ann) : ?>
                        <div style="margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid #333;">
                            <h4 style="margin: 0 0 5px 0; color: #fff;"><?= $ann['title'] ?> <?= $ann['pinned'] ? '<span style="color: #f00;">[置顶]</span>' : '' ?></h4>
                            <p style="margin: 0 0 5px 0; color: #aaa; font-size: 14px;"><i class="fa fa-calendar"></i> <?= $ann['date'] ?> | <i class="fa fa-user"></i> <?= $ann['author'] ?></p>
                            <p style="margin: 0; color: #ddd; font-size: 14px; line-height: 1.5;">
                                <?= truncateContent($ann['content'], $truncateLength, $ann['id'], $ann['type']) ?>
                            </p>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- 快速链接 -->
                <div class="sidebar-section">
                    <h3><i class="fa fa-link"></i> 快速链接</h3>
                    <ul class="link-list">
                        <li style="margin-bottom: 10px;">
                            <a href="#" class="read-more" style="padding: 5px 10px; display: block;">
                                <i class="fa fa-book"></i> 服务器规则
                            </a>
                        </li>
                        <li style="margin-bottom: 10px;">
                            <a href="#" class="read-more" style="padding: 5px 10px; display: block;">
                                <i class="fa fa-question-circle"></i> 常见问题
                            </a>
                        </li>
                        <li>
                            <a href="#" class="read-more" style="padding: 5px 10px; display: block;">
                                <i class="fa fa-comments"></i> 论坛
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</body>
</html>