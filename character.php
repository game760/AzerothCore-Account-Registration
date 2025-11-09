<?php
include 'db_config.php';

// 获取搜索参数
$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
$capitalize = isset($_GET['capitalize']) && $_GET['capitalize'] === 'on';

// 如果需要首字母大写且搜索词不为空
if ($capitalize && !empty($searchTerm)) {
    $firstChar = substr($searchTerm, 0, 1);
    // 检查第一个字符是否是字母
    if (ctype_alpha($firstChar)) {
        $searchTerm = ucfirst($firstChar) . substr($searchTerm, 1);
    }
}

// 构建查询条件
$whereConditions = [];

// 如果有搜索词，添加角色名模糊搜索（忽略在线状态）
if (!empty($searchTerm)) {
    $whereConditions[] = "name LIKE :searchTerm";
} else {
    // 无搜索时应用在线状态过滤
    if ($displayConfig['show_online_only']) {
        $whereConditions[] = "{$displayConfig['online_status_field']} = 1";
    }
}

// 组合WHERE子句
$whereClause = '';
if (!empty($whereConditions)) {
    $whereClause = "WHERE " . implode(' AND ', $whereConditions);
}

// PDO查询
try {
    $stmt = $charsConn->prepare("SELECT guid, name, level, class, race, zone, xp, {$displayConfig['online_status_field']} 
                                FROM characters 
                                $whereClause
                                ORDER BY level DESC, xp DESC 
                                LIMIT :maxRanking");
    
    // 绑定参数
    $stmt->bindParam(':maxRanking', $displayConfig['max_ranking'], PDO::PARAM_INT);
    
    // 绑定搜索参数（如果有）
    if (!empty($searchTerm)) {
        $stmt->bindValue(':searchTerm', "%{$searchTerm}%", PDO::PARAM_STR);
    }
    
    $stmt->execute();
    $result = $stmt->fetchAll();
    $rank = 1;
} catch (PDOException $e) {
    die("查询角色数据失败: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AzerothCore 角色排行榜</title>
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
        .container { max-width: 1200px; margin: 30px auto; padding: 0 20px; }
        h1 { color: var(--wow-gold); text-align: center; margin-bottom: 30px; }
        .rank-table { width: 100%; border-collapse: collapse; background: #222; border-radius: 8px; overflow: hidden; }
        .rank-table th { background: #333; padding: 12px; text-align: left; color: #ddd; font-weight: bold; }
        .rank-table td { padding: 12px; border-bottom: 1px solid #333; }
        .rank-table tr:hover { background: #2a2a2a; }
        .rank-number { color: var(--wow-gold); font-weight: bold; }
        .faction-alliance { color: #69ccf0; }
        .faction-horde { color: #ff6b6b; }
        .faction-neutral { color: #aaa; }
        .status-online { color: #0f0; }
        .status-offline { color: #aaa; }
        .char-link { color: #fff; text-decoration: none; }
        .char-link:hover { color: var(--wow-gold); text-decoration: underline; }
        
        /* 搜索框样式 */
        .search-container { text-align: center; margin-bottom: 20px; }
        .search-input { 
            padding: 8px 15px; 
            width: 300px; 
            border: 2px solid #333; 
            border-radius: 20px; 
            background: #222; 
            color: #fff; 
            font-size: 16px;
        }
        .search-button { 
            padding: 8px 15px; 
            border: none; 
            border-radius: 20px; 
            background: var(--wow-gold); 
            color: #000; 
            font-weight: bold; 
            cursor: pointer; 
            margin-left: 10px;
        }
        .search-button:hover { 
            background: #e6c200;
        }
        .capitalize-option {
            margin-left: 10px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
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
            <li><a href="index.php"><i class="fa fa-home"></i> 首页</a></li>
            <li><a href="account.php"><i class="fa fa-user-plus"></i> 账号注册</a></li>
            <li><a href="character.php" class="active"><i class="fa fa-list-ol"></i> 英雄榜</a></li>
            <li><a href="downloads.php"><i class="fa fa-download"></i> 下载专区</a></li>
            <li><a href="downloads.php"><i class="fa fa-comments"></i> 论坛</a></li>
        </ul>
    </div>
</nav>

<div class="container">
    <h1>AzerothCore 角色排行榜 - 前<?php echo $displayConfig['max_ranking']; ?>名</h1>
    
    <!-- 搜索表单 -->
    <div class="search-container">
        <form method="get" action="character.php">
            <input type="text" name="search" class="search-input" 
                   placeholder="搜索角色名..." 
                   value="<?php echo htmlspecialchars($searchTerm); ?>">
            <button type="submit" class="search-button"><i class="fa fa-search"></i> 搜索</button>
            <?php if (!empty($searchTerm)): ?>
                <a href="character.php" class="search-button" style="background: #555; color: #fff;">清除搜索</a>
            <?php endif; ?>
            <label class="capitalize-option">
                <input type="checkbox" name="capitalize" id="capitalize" <?php echo $capitalize ? 'checked' : ''; ?>>
                <b style="color:#ffffff">智能搜索</b>
            </label>
        </form>
    </div>
    
    <table class="rank-table">
        <tr>
            <th>排名</th>
            <th>角色名(查看装备)</th>
            <th>等级</th>
            <th>职业</th>
            <th>种族</th>
            <th>区域</th>
            <th>状态</th>
        </tr>
        <?php foreach ($result as $char) : ?>
            <?php
            // 获取职业信息
            $classInfo = $classes[$char['class']] ?? ['name' => '未知', 'color' => '#fff'];
            // 获取种族信息
            $raceInfo = $races[$char['race']] ?? ['name' => '未知', 'faction' => '中立'];
            // 获取区域名称
            $zoneName = $zones[$char['zone']] ?? '未知区域';
            // 状态文本
            $statusText = $char[$displayConfig['online_status_field']] ? '在线' : '离线';
            $statusClass = $char[$displayConfig['online_status_field']] ? 'status-online' : 'status-offline';
            // 阵营样式
            $factionClass = 'faction-' . strtolower($raceInfo['faction']);
            ?>
            <tr>
                <td class="rank-number"><?= $rank++ ?></td>
                <td>
                    <a href="equipment.php?guid=<?= $char['guid'] ?>" class="char-link" style="color: <?= $classInfo['color'] ?>;">
                        <b><?= htmlspecialchars($char['name']) ?></b>
                    </a>
                </td>
                <td><?= $char['level'] ?></td>
                <td style="color: <?= $classInfo['color'] ?>; font-weight: bold;"><?= $classInfo['name'] ?></td>
                <td class="<?= $factionClass ?>"><?= $raceInfo['name'] ?>（<?= $raceInfo['faction'] ?>）</td>
                <td><?= $zoneName ?></td>
                <td class="<?= $statusClass ?>"><i class="fa fa-circle"></i> <?= $statusText ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($result)) : ?>
            <tr>
                <td colspan="7" style="text-align: center; padding: 20px; color: #aaa;">
                    <?php echo !empty($searchTerm) ? "没有找到匹配 '$searchTerm' 的角色" : "暂无角色数据"; ?>
                </td>
            </tr>
        <?php endif; ?>
    </table>
</div>
</body>
</html>