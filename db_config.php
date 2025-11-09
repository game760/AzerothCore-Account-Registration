<?php
// 数据库通用配置
$dbHost = 'localhost';
$dbUser = 'root';
$dbPass = '123456';

// PDO数据库连接函数
function getDbConnection($host, $user, $pass, $dbName) {
    try {
        $dsn = "mysql:host=$host;dbname=$dbName;charset=utf8mb4";
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        return $pdo;
    } catch (PDOException $e) {
        die("数据库连接失败 ($dbName): " . $e->getMessage());
    }
}

// 数据库连接实例（循环创建减少重复）
$dbNames = ['acore_auth', 'acore_characters', 'acore_world'];
list($authConn, $charsConn, $worldConn) = array_map(
    fn($name) => getDbConnection($dbHost, $dbUser, $dbPass, $name),
    $dbNames
);

// 职业映射
$classes = [
    1 => ['name' => '战士', 'color' => '#C79C6E'],
    2 => ['name' => '圣骑士', 'color' => '#F58CBA'],
    3 => ['name' => '猎人', 'color' => '#ABD473'],
    4 => ['name' => '潜行者', 'color' => '#FFF569'],
    5 => ['name' => '牧师', 'color' => '#FFFFFF'],
    6 => ['name' => '死亡骑士', 'color' => '#C41F3B'],
    7 => ['name' => '萨满祭司', 'color' => '#0070DE'],
    8 => ['name' => '法师', 'color' => '#69CCF0'],
    9 => ['name' => '术士', 'color' => '#9482C9'],
    10 => ['name' => '武僧', 'color' => '#00FF96'],
    11 => ['name' => '德鲁伊', 'color' => '#FF7D0A'],
    12 => ['name' => '恶魔猎手', 'color' => '#A330C9']
];

// 种族映射
$races = [
    1 => ['name' => '人类', 'faction' => '联盟'],
    2 => ['name' => '兽人', 'faction' => '部落'],
    3 => ['name' => '矮人', 'faction' => '联盟'],
    4 => ['name' => '暗夜精灵', 'faction' => '联盟'],
    5 => ['name' => '亡灵', 'faction' => '部落'],
    6 => ['name' => '牛头人', 'faction' => '部落'],
    7 => ['name' => '侏儒', 'faction' => '联盟'],
    8 => ['name' => '巨魔', 'faction' => '部落'],
    9 => ['name' => '地精', 'faction' => '部落'],
    10 => ['name' => '血精灵', 'faction' => '部落'],
    11 => ['name' => '德莱尼', 'faction' => '联盟'],
    22 => ['name' => '狼人', 'faction' => '联盟'],
    24 => ['name' => '熊猫人', 'faction' => '中立'],
    25 => ['name' => '熊猫人', 'faction' => '联盟'],
    26 => ['name' => '熊猫人', 'faction' => '部落']
];

// 装备配置
$equipConfig = [
    'icons' => [
        'base_url' => 'https://wowgaming.altervista.org/aowow/static/images/wow/icons/large/',
        'extension' => '.jpg',
        'default' => './images/empty_slot.ico'
    ],
    'itemConfig' => [
        'base_url' => 'https://cn.altervista.org/aowow/?item=%d',
        'target' => '_blank',
        'show_link' => true
    ],
    'quality' => [
        0 => ['name' => '普通', 'color' => '#ffffff'],
        1 => ['name' => '优秀', 'color' => '#9d9d9d'],
        2 => ['name' => '精良', 'color' => '#1eff00'],
        3 => ['name' => '稀有', 'color' => '#0070dd'],
        4 => ['name' => '史诗', 'color' => '#a335ee'],
        5 => ['name' => '传说', 'color' => '#ff8000'],
        6 => ['name' => '神奇', 'color' => '#e6cc80'],
    ]
];

// 显示配置
$displayConfig = [
    'max_ranking' => 100,
    'show_online_only' => true,
    'online_status_field' => 'online'
];

// 区域映射（包含巫妖王之怒版本）
$zones = [
    // 经典旧世 - 户外区域
    1 => '艾尔文森林', 2 => '提瑞斯法林地', 3 => '杜隆塔尔', 4 => '丹莫罗', 5 => '莫高雷',
    8 => '赤脊山', 10 => '洛克莫丹', 11 => '西部荒野', 12 => '暮色森林', 14 => '银松森林',
    15 => '贫瘠之地', 16 => '石爪山脉', 17 => '黑海岸', 28 => '暴风城', 33 => '奥格瑞玛',
    36 => '铁炉堡', 38 => '雷霆崖', 40 => '达纳苏斯', 41 => '幽暗城', 44 => '悲伤沼泽',
    45 => '尘泥沼泽', 46 => '千针石林', 47 => '塔纳利斯', 50 => '安戈洛环形山', 51 => '希利苏斯',
    65 => '西瘟疫之地', 66 => '东瘟疫之地', 67 => '辛特兰', 68 => '奥特兰克山脉', 70 => '燃烧平原',
    71 => '灼热峡谷', 79 => '湿地', 106 => '阿拉希高地', 110 => '荒芜之地', 117 => '费伍德森林',
    118 => '灰谷', 120 => '海加尔山', 130 => '诅咒之地', 139 => '冬泉谷', 141 => '月光林地',
    148 => '逆风小径',
    // 外域 - 户外区域
    163 => '刀锋山', 165 => '纳格兰', 169 => '影月谷', 170 => '泰罗卡森林', 171 => '赞加沼泽',
    172 => '地狱火半岛', 173 => '虚空风暴',
    // 巫妖王之怒 - 诺森德户外区域
    490 => '北风苔原', 491 => '嚎风峡湾', 492 => '龙骨荒野', 493 => '灰熊丘陵', 495 => '祖达克',
    496 => '索拉查盆地', 497 => '冰冠冰川', 498 => '风暴峭壁', 499 => '晶歌森林', 500 => '达拉然',
    // 经典旧世副本
    22 => '怒焰裂谷', 48 => '影牙城堡', 70 => '风暴要塞', 90 => '黑石山', 109 => '死亡矿井',
    129 => '哀嚎洞穴', 189 => '血色修道院', 209 => '剃刀沼泽', 229 => '诺莫瑞根', 230 => '通灵学院',
    249 => '奥达曼', 269 => '斯坦索姆', 289 => '剃刀高地', 309 => '祖尔法拉克', 329 => '玛拉顿',
    349 => '神庙', 389 => '黑石深渊', 409 => '厄运之槌', 429 => '黑石塔', 469 => '通灵学院',
    489 => '纳克萨玛斯（经典）', 531 => '安其拉废墟', 532 => '安其拉神殿',
    // 外域副本
    544 => '地狱火城墙', 545 => '鲜血熔炉', 546 => '破碎大厅', 550 => '奴隶围栏', 552 => '幽暗沼泽',
    553 => '蒸汽地窟', 554 => '生态船', 555 => '能源舰', 556 => '禁魔监狱', 557 => '暗影迷宫',
    558 => '奥金顿地穴', 559 => '法力陵墓', 560 => '塞泰克大厅', 564 => '卡拉赞', 565 => '格鲁尔的巢穴',
    568 => '玛瑟里顿的巢穴', 580 => '祖阿曼', 585 => '太阳之井高地',
    // 巫妖王之怒副本
    574 => '古达克', 575 => '岩石大厅', 576 => '闪电大厅', 578 => '乌特加德城堡', 579 => '乌特加德之巅',
    580 => '魔环', 585 => '达克萨隆要塞', 595 => '安卡赫特：古代王国', 599 => '艾卓-尼鲁布', 601 => '净化斯坦索姆',
    615 => '奥妮克希亚的巢穴', 616 => '纳克萨玛斯（WLK）', 624 => '黑曜石圣殿', 625 => '永恒之眼', 631 => '阿尔卡冯的宝库',
    632 => '十字军试炼', 633 => '冰冠堡垒', 649 => '奥杜尔', 650 => '红玉圣殿'
];



// 服务器信息
$serverinfo = [
    'website' => 'wow.example.com',
    'type' => 'PVP',
    'version' => '3.3.5a (12340)',
    'realmlist' => 'logon.example.com',
    'expansion' => 2, // 0:经典, 1:TBC, 2:WLK
];

// 客户端下载信息
$client = [
    'name' => 'WLK 3.3.5a',
    'url' => 'https://download.example.com/client/wlk-335a.zip',
    'size' => '15.2 GB',
    'desc' => '完整客户端'
];

// 邮箱限制配置（新增）
$emailConfig = [
    'min_length' => 8,               // 邮箱最小长度
    'max_length' => 20,             // 邮箱最大长度
    'allowed_domains' => [],         // 允许的邮箱域名（空数组表示不限制）
    // 'allowed_domains' => ['example.com', 'company.org'], // 示例：仅允许特定域名
    'blocked_domains' => ['spam.com', 'tempmail.cn'], // 禁止的邮箱域名
    'allow_special_chars' => true    // 是否允许特殊字符（除标准邮箱格式外）
];

// 验证码配置（新增）
$captchaConfig = [
    'type' => 'cf_turnstile', // captcha = 数字字母验证码, cf_turnstile = Cloudflare人机验证
    'cf_turnstile' => [
        'site_key' => '0x4AAAAAAB-FxL5eQ_9xEv9x', 
        'secret_key' => '0x4AAAAAAB-FxMm4VPN_2QjSdRbl7Rf2AT4', 
    ],
    'captcha' => [
        'length' => 6,      // 验证码长度
        'width' => 150,     // 图片宽度
        'height' => 50,     // 图片高度
        'expire' => 300     // 有效期(秒)
    ]
];

?>