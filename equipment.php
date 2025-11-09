<?php
include 'db_config.php';

// 获取角色GUID
$guid = $_GET['guid'] ?? 0;
if (!$guid || !is_numeric($guid)) {
    header('Location: character.php');
    exit;
}

// 查询角色基础信息
try {
    $stmt = $charsConn->prepare("SELECT name, level, class, race, zone, {$displayConfig['online_status_field']}
                                FROM characters 
                                WHERE guid = :guid");
    $stmt->bindParam(':guid', $guid, PDO::PARAM_INT);
    $stmt->execute();
    $char = $stmt->fetch();
    if (!$char) {
        header('Location: character.php');
        exit;
    }
} catch (PDOException $e) {
    die("查询角色信息失败: " . $e->getMessage());
}

// 获取角色装备信息（完善版本）
try {
    // 1. 从character_inventory表查询slot在0-18范围的记录
    $stmt = $charsConn->prepare("SELECT item, slot FROM character_inventory WHERE guid = :guid AND slot BETWEEN 0 AND 18");
    $stmt->bindParam(':guid', $guid, PDO::PARAM_INT);
    $stmt->execute();
    $inventoryItems = $stmt->fetchAll();

    // 2. 收集需要查询的item_instance.guid（排除空值）
    $itemGuids = [];
    foreach ($inventoryItems as $item) {
        if ($item['item'] > 0) { // 过滤无效物品
            $itemGuids[] = $item['item'];
        }
    }

    // 3. 从item_instance表批量查询对应的itemEntry
    $itemInstanceMap = [];
    if (!empty($itemGuids)) {
        $placeholders = implode(',', array_fill(0, count($itemGuids), '?'));
        $stmt = $charsConn->prepare("SELECT guid, itemEntry FROM item_instance WHERE guid IN ($placeholders)");
        $stmt->execute($itemGuids);
        $itemInstances = $stmt->fetchAll();
        
        // 构建映射关系：item_instance.guid => itemEntry
        foreach ($itemInstances as $instance) {
            $itemInstanceMap[$instance['guid']] = $instance['itemEntry'];
        }
    }

    // 4. 格式化装备数据（按槽位存储itemEntry）
    $equipSlots = [];
    foreach ($inventoryItems as $item) {
        $slot = $item['slot'];
        $itemGuid = $item['item'];
        $equipSlots[$slot] = $itemInstanceMap[$itemGuid] ?? 0;
    }

    // 5. 收集所有itemEntry，查询item_template获取displayid和quality
    $itemEntries = array_filter($equipSlots); // 过滤0值（未装备）
    $itemDataMap = []; // itemEntry => [displayid, quality, name]
    if (!empty($itemEntries)) {
        $placeholders = implode(',', array_fill(0, count($itemEntries), '?'));
        $stmt = $worldConn->prepare("SELECT entry, displayid, quality, name FROM item_template WHERE entry IN ($placeholders)");
        $stmt->execute(array_values($itemEntries));
        $templates = $stmt->fetchAll();
        
        foreach ($templates as $tpl) {
            $itemDataMap[$tpl['entry']] = [
                'displayid' => $tpl['displayid'],
                'quality' => $tpl['quality'],
                'name' => $tpl['name']
            ];
        }
    }

    // 6. 收集所有displayid，查询item_display_info获取icon_name
    $displayIds = [];
    foreach ($itemDataMap as $data) {
        $displayIds[] = $data['displayid'];
    }
    $displayIconMap = []; // displayid => icon_name
    if (!empty($displayIds)) {
        $placeholders = implode(',', array_fill(0, count($displayIds), '?'));
        $stmt = $worldConn->prepare("SELECT ID, icon_name FROM item_display_info WHERE ID IN ($placeholders)");
        $stmt->execute($displayIds);
        $displayInfos = $stmt->fetchAll();
        
        foreach ($displayInfos as $info) {
           $displayIconMap[$info['ID']] = strtolower($info['icon_name']);
        }
    }

} catch (PDOException $e) {
    die("查询装备信息失败: " . $e->getMessage());
}

// 调整装备槽位分布：左边8个，右边8个，下方4个
$equipmentSlots = [
    // 左侧装备 (8个)
    ['name' => '头部', 'slot' => 0, 'position' => 'left'],
    ['name' => '颈部', 'slot' => 1, 'position' => 'left'],
    ['name' => '肩部', 'slot' => 2, 'position' => 'left'],
    ['name' => '背部', 'slot' => 14, 'position' => 'left'],
    ['name' => '胸部', 'slot' => 4, 'position' => 'left'],
    ['name' => '衬衣', 'slot' => 3, 'position' => 'left'],
    ['name' => '战袍', 'slot' => 18, 'position' => 'left'],
    ['name' => '手腕', 'slot' => 8, 'position' => 'left'],
    
   // 右侧装备 (8个)
    ['name' => '手', 'slot' => 9, 'position' => 'right'],
    ['name' => '腰部', 'slot' => 5, 'position' => 'right'],
    ['name' => '腿部', 'slot' => 6, 'position' => 'right'],
    ['name' => '脚部', 'slot' => 7, 'position' => 'right'],
    ['name' => '手指1', 'slot' => 10, 'position' => 'right'],
    ['name' => '手指2', 'slot' => 11, 'position' => 'right'],
    ['name' => '饰品1', 'slot' => 12, 'position' => 'right'],
    ['name' => '饰品2', 'slot' => 13, 'position' => 'right'],
    
    // 下方装备 (3个)
   ['name' => '主手', 'slot' => 15, 'position' => 'bottom'],
    ['name' => '副手', 'slot' => 16, 'position' => 'bottom'],
    ['name' => '远程', 'slot' => 17, 'position' => 'bottom'],
];

// 基础信息映射
$classInfo = $classes[$char['class']] ?? ['name' => '未知', 'color' => '#fff'];
$raceInfo = $races[$char['race']] ?? ['name' => '未知', 'faction' => '中立'];
$zoneName = $zones[$char['zone']] ?? '未知区域';
$statusText = $char[$displayConfig['online_status_field']] ? '在线' : '离线';
$statusClass = $char[$displayConfig['online_status_field']] ? 'status-online' : 'status-offline';
$factionClass = 'faction-' . strtolower($raceInfo['faction']);

// 定义装备位置渲染配置
$positionConfigs = [
    'left' => [
        'container_class' => 'slot-column slot-left',
        'grid_class' => 'slot-grid',
        'parent_container' => 'equipment-container'
    ],
    'right' => [
        'container_class' => 'slot-column slot-right',
        'grid_class' => 'slot-grid',
        'parent_container' => 'equipment-container'
    ],
    'bottom' => [
        'container_class' => 'slot-bottom',
        'grid_class' => 'slot-grid-bottom',
        'parent_container' => ''
    ]
];
?>
<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>角色装备面板 - <?= htmlspecialchars($char['name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/font-awesome@4.7.0/css/font-awesome.min.css" rel="stylesheet">
    <style>
    :root { 
        --wow-gold: #ffd700; 
        --common: #999999;           /* 0-普通 */
        --uncommon: #9d9d9d;         /* 1-优秀（绿色） */
        --rare: #1eff00;             /* 2-精良（蓝色） */
        --epic: #0070dd;             /* 3-稀有（紫色） */
        --legendary: #a335ee;        /* 4-史诗（橙色） */
        --artifact: #ff8000;         /* 5-传说 */
        --heirloom: #e6cc80;         /* 6-神器 */
}
        body { 
            margin: 0; 
            padding: 0; 
            background: #000; 
            color: #fff; 
            font-family: Arial, sans-serif; 
            transform: scale(1);
            transform-origin: top center;
            width: 100%;
            min-height: 100vh;
        }
        .wow-nav { background: #1a1a1a; padding: 15px 0; }
        .nav-container { max-width: 1200px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; padding: 0 20px; }
        .nav-logo { color: var(--wow-gold); font-size: 24px; font-weight: bold; text-decoration: none; display: flex; align-items: center; gap: 10px; }
        .nav-links { list-style: none; display: flex; gap: 30px; margin: 0; padding: 0; }
        .nav-links a { color: #fff; text-decoration: none; font-size: 16px; display: flex; align-items: center; gap: 5px; }
        .nav-links a.active { color: var(--wow-gold); }
        .container { max-width: 1200px; margin: 30px auto; padding: 0 20px; }
        .back-link { color: #69ccf0; text-decoration: none; display: inline-block; margin-bottom: 20px; }
        .back-link:hover { color: var(--wow-gold); text-decoration: underline; }

        /* 角色信息头部样式 */
        .char-header {
            display: flex;
            align-items: center;
            background: #222;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
        }
        .char-avatar {
            margin-right: 20px;
            font-size: 64px;
        }
        .char-info-main {
            flex: 1;
        }
        .char-name {
            margin: 0 0 15px 0;
            font-size: 28px;
        }
        .char-details {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }
        .char-detail-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        


        /* 中间角色模型布局 */
        .equipment-container { 
            display: flex; 
            gap: 20px; 
            margin-bottom: 30px;
            align-items: flex-start;
            width: 100%;
        }

        .slot-column {
            flex: 1;
            background: #222; 
            border-radius: 8px; 
            padding: 20px;
            min-width: 0;
        }

        .char-model-center {
            flex: 1.5; /* 中间区域更宽，适合3D模型 */
            background: #222; 
            border-radius: 8px; 
            padding: 20px; 
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-width: 0;
            min-height: 657px; /* 给3D模型足够高度 */
        }

        /* 装备槽网格布局 */
        .slot-grid-bottom {
            display: flex; 
            justify-content: center;
            gap: 30px; /* 减少间距让三个装备靠得更近 */
        }

        .slot-grid { 
            display: grid; 
            grid-template-columns: 1fr; /* 单列垂直排列 */
            gap: 15px; 
            justify-items: center; /* 装备槽居中显示 */
        }

        /* 装备槽样式 */
        .equip-slot {
            text-align: center;
            padding: 0;
            background: #333;
            border-radius: 6px;
            width: 64px;
            height: 64px;
            position: relative;
        }
        
        .slot-title {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            margin: 0;
            font-size: 10px;
            color: #ccc;
            background: rgba(0, 0, 0, 0.5);
            padding: 1px 0;
            border-bottom-left-radius: 4px;
            border-bottom-right-radius: 4px;
        }
        
        .item-icon-container {
            width: 64px;
            height: 64px;
            margin: 0;
        }
        
        .item-icon {
            width: 100%;
            height: 100%;
            border: 2px solid transparent;
            border-radius: 4px;
        }

        /* 装备品质边框  */
        .quality-0 .item-icon { border-color: var(--common); }   /* 普通(白色)*/
        .quality-1 .item-icon { border-color: var(--uncommon); }       /* 优秀(绿色) */
        .quality-2 .item-icon { border-color: var(--rare); }       /* 精良(蓝色) */
        .quality-3 .item-icon { border-color: var(--epic); }  /* 稀有(紫色) */
        .quality-4 .item-icon { border-color: var(--legendary); }   /* 史诗(橙色) */
        .quality-5 .item-icon { border-color: var(--artifact); }   /* 传说 */
        .quality-6 .item-icon { border-color: var(--heirloom); }     /* 神器 */
        
        .item-link { 
            display: block; 
            text-decoration: none;
        }
        .item-name { 
            display: none;
        }
        
        .slot-bottom {
            background: #222;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
            text-align: center; /* 让下方装备居中显示 */
        }
        
        .footer { 
            text-align: center; 
            margin: 30px 0; 
            color: #aaa; 
            font-size: 14px; 
        }
        .footer a { 
            color: #69ccf0; 
            text-decoration: none; 
        }
        .footer a:hover { 
            color: var(--wow-gold); 
        }
        .faction-alliance { color: #69ccf0; }
        .faction-horde { color: #ff6b6b; }
        .faction-neutral { color: #aaa; }
        .status-online { color: #0f0; }
        .status-offline { color: #aaa; }
        
        /* 响应式调整 */
        @media (max-width: 992px) {
            .equipment-container {
                flex-direction: column;
            }
            
            .char-header {
                flex-direction: column;
                text-align: center;
            }
            
            .char-avatar {
                margin-right: 0;
                margin-bottom: 15px;
            }
            
            .char-details {
                justify-content: center;
            }
            
            .level-progress-container {
                margin-left: auto;
                margin-right: auto;
            }
        }
        
        @media (max-width: 576px) {
            .slot-grid-bottom {
                flex-direction: column;
                align-items: center;
                gap: 15px;
            }
            
            .nav-links {
                gap: 15px;
            }
            
            .char-details {
                gap: 10px 15px;
            }
        }
    </style>
    <script src="https://wowgaming.altervista.org/aowow/static/widgets/power.js"></script>
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

    <div class="container">
        <a href="character.php" class="back-link"><i class="fa fa-arrow-left"></i> 返回排行榜</a>
        
        <!-- 角色信息头部（上方人物姓名等信息） -->
        <div class="char-header">
            <div class="char-avatar">
                <i class="fa fa-user-circle-o" style="color: <?php echo htmlspecialchars($classInfo['color']); ?>;"></i>
            </div>
            <div class="char-info-main">
                <h1 class="char-name" style="color: <?php echo htmlspecialchars($classInfo['color']); ?>">
                    <?php echo htmlspecialchars($char['name']); ?>
                </h1>
                <div class="char-details">
                    <div class="char-detail-item">
                        <i class="fa fa-signal"></i>
                        <span>等级 <?php echo htmlspecialchars($char['level']); ?></span>
                    </div>
                    <div class="char-detail-item">
                        <i class="fa fa-briefcase"></i>
                        <span class="char-class" style="color: <?php echo htmlspecialchars($classInfo['color']); ?>">
                            <?php echo htmlspecialchars($classInfo['name']); ?>
                        </span>
                    </div>
                    <div class="char-detail-item">
                        <i class="fa fa-flag"></i>
                        <span class="<?= $factionClass ?>">
                            <?php echo htmlspecialchars($raceInfo['name']); ?>（<?php echo htmlspecialchars($raceInfo['faction']); ?>）
                        </span>
                    </div>
                    <div class="char-detail-item">
                        <i class="fa fa-circle"></i>
                        <span class="<?= $statusClass ?>"><?php echo $statusText; ?></span>
                    </div>
                    <div class="char-detail-item">
                        <i class="fa fa-map-marker"></i>
                        <span>当前区域：<?php echo htmlspecialchars($zoneName); ?></span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 装备容器布局 -->
        <div class="equipment-container">
            <?php foreach (['left', 'right'] as $pos): ?>
                <?php $config = $positionConfigs[$pos]; ?>
                <div class="<?= $config['container_class'] ?>">
                    <div class="<?= $config['grid_class'] ?>">
                        <?php foreach ($equipmentSlots as $def): ?>
                            <?php if ($def['position'] !== $pos) continue; ?>
                            <?php
                            // 装备槽渲染逻辑
                            $slot = $def['slot'];
                            $itemId = $equipSlots[$slot] ?? 0;
                            $itemData = $itemId ? ($itemDataMap[$itemId] ?? []) : [];
                            $displayId = !empty($itemData) ? $itemData['displayid'] : 0;
                            $quality = !empty($itemData) ? $itemData['quality'] : -1;
                            $itemName = !empty($itemData) ? $itemData['name'] : '未装备';
                            $icoName = $displayId ? ($displayIconMap[$displayId] ?? '') : '';
                            $iconUrl = $icoName 
                                ? $equipConfig['icons']['base_url'] . $icoName . $equipConfig['icons']['extension']
                                : $equipConfig['icons']['default'];
                            $itemLink = $equipConfig['itemConfig']['show_link'] && $itemId 
                                ? sprintf($equipConfig['itemConfig']['base_url'], $itemId)
                                : 'javascript:void(0)';
                            $qualityClass = $quality >= 0 ? 'quality-' . $quality : 'quality-none';
                            ?>
                            <div class="equip-slot <?= $qualityClass ?>">
                                <div class="slot-title"><?php echo $def['name']; ?></div>
                                <a href="<?php echo htmlspecialchars($itemLink); ?>" 
                                   class="item-link" 
                                   target="<?= $equipConfig['itemConfig']['target'] ?>"
                                   <?php if ($itemId): ?>data-wowhead="item=<?= $itemId ?>"<?php endif; ?>>
                                    <div class="item-icon-container">
                                        <img src="<?php echo htmlspecialchars($iconUrl); ?>" 
                                             alt="<?php echo $def['name']; ?>装备" 
                                             class="item-icon">
                                    </div>
                                    <div class="item-name"><?php echo htmlspecialchars($itemName); ?></div>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <?php if ($pos === 'left'): ?>
                    <!-- 中间角色形象区域 -->
                    <div class="char-model-center">
                        <div class="model-placeholder">
                            <!-- 这里是角色3D形象的位置 -->
                            <i class="fa fa-user-circle-o fa-10x" style="color: <?php echo $classInfo['color']; ?>;"></i>
                            <p style="margin-top: 20px;">角色模型</p>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>

        <!-- 下方装备区域 -->
        <?php $bottomConfig = $positionConfigs['bottom']; ?>
        <div class="<?= $bottomConfig['container_class'] ?>">
            <div class="<?= $bottomConfig['grid_class'] ?>">
                <?php foreach ($equipmentSlots as $def): ?>
                    <?php if ($def['position'] !== 'bottom') continue; ?>
                    <?php
                    // 装备槽渲染逻辑
                    $slot = $def['slot'];
                    $itemId = $equipSlots[$slot] ?? 0;
                    $itemData = $itemId ? ($itemDataMap[$itemId] ?? []) : [];
                    $displayId = !empty($itemData) ? $itemData['displayid'] : 0;
                    $quality = !empty($itemData) ? $itemData['quality'] : -1;
                    $itemName = !empty($itemData) ? $itemData['name'] : '未装备';
                    $icoName = $displayId ? ($displayIconMap[$displayId] ?? '') : '';
                    $iconUrl = $icoName 
                        ? $equipConfig['icons']['base_url'] . $icoName . $equipConfig['icons']['extension']
                        : $equipConfig['icons']['default'];
                    $itemLink = $equipConfig['itemConfig']['show_link'] && $itemId 
                        ? sprintf($equipConfig['itemConfig']['base_url'], $itemId)
                        : 'javascript:void(0)';
                    $qualityClass = $quality >= 0 ? 'quality-' . $quality : 'quality-none';
                    ?>
                    <div class="equip-slot <?= $qualityClass ?>">
                        <div class="slot-title"><?php echo $def['name']; ?></div>
                        <a href="<?php echo htmlspecialchars($itemLink); ?>" 
                           class="item-link" 
                           target="<?= $equipConfig['itemConfig']['target'] ?>"
                           <?php if ($itemId): ?>data-wowhead="item=<?= $itemId ?>"<?php endif; ?>>
                            <div class="item-icon-container">
                                <img src="<?php echo htmlspecialchars($iconUrl); ?>" 
                                     alt="<?php echo $def['name']; ?>装备" 
                                     class="item-icon">
                            </div>
                            <div class="item-name"><?php echo htmlspecialchars($itemName); ?></div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
      
        
        <div class="footer">
            <p>数据来源于 AzerothCore 服务器 | 装备信息由<a href="https://wowgaming.altervista.org/aowow/" target="_blank">wowgaming</a>提供</p>
        </div>
    </div>

    <script>
    var aowow_tooltips = {
        "colorlinks": true,
        "iconizelinks": false,
        "renamelinks": false ,
        "gems": true,
        "sock": true,
        "pcs": true,
        "ench": true,
    };
    </script>
</body>
</html>