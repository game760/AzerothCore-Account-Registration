<?php
/**
 * 魔兽世界下载专区
 */
// 加载配置
require_once 'core.php';
require_once 'db_config.php';
?>
<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>艾泽拉斯守护者 - 下载专区</title>
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
        .page-title { color: var(--wow-gold); text-align: center; margin-bottom: 40px; font-size: 32px; }
        .download-section { background: #1a1a1a; border-radius: 8px; padding: 30px; margin-bottom: 30px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3); }
        .section-title { color: var(--wow-gold); margin-top: 0; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #333; display: flex; align-items: center; gap: 10px; }
        .download-card { background: #222; border-radius: 8px; padding: 25px; margin-bottom: 25px; transition: transform 0.3s ease, box-shadow 0.3s ease; }
        .download-card:hover { transform: translateY(-5px); box-shadow: 0 6px 12px rgba(0, 0, 0, 0.4); }
        .download-header { display: flex; align-items: center; gap: 15px; margin-bottom: 15px; }
        .download-icon { width: 60px; height: 60px; background: rgba(255, 215, 0, 0.1); border-radius: 50%; display: flex; justify-content: center; align-items: center; font-size: 24px; color: var(--wow-gold); }
        .download-title { margin: 0; color: #fff; font-size: 20px; }
        .download-info { color: #ddd; margin: 0 0 15px 0; line-height: 1.6; }
        .download-meta { display: flex; flex-wrap: wrap; gap: 20px; margin-bottom: 20px; color: #aaa; font-size: 14px; }
        .meta-item { display: flex; align-items: center; gap: 5px; }
        .download-btn { display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px; background: var(--wow-gold); color: #000; text-decoration: none; border-radius: 4px; font-weight: bold; transition: background 0.3s; }
        .download-btn:hover { background: #e6c200; }
        .realmlist-box { background: #333; padding: 15px; border-radius: 4px; margin: 15px 0; display: flex; align-items: center; gap: 10px; }
        .realmlist-box pre { margin: 0; color: #0f0; font-size: 14px; flex: 1; }
        .copy-btn { padding: 8px 15px; background: #333; border: 1px solid #555; color: #fff; border-radius: 4px; cursor: pointer; }
        .copy-btn:hover { background: #444; }
        .plugin-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; margin-top: 20px; }
        .plugin-tag { display: inline-block; padding: 3px 8px; background: #333; border-radius: 4px; font-size: 12px; color: #ddd; margin-right: 5px; margin-bottom: 5px; }
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
                <li><a href="character.php"><i class="fa fa-list-ol"></i> 英雄榜</a></li>
                <li><a href="downloads.php" class="active"><i class="fa fa-download"></i> 下载专区</a></li>
                <li><a href="downloads.php"><i class="fa fa-comments"></i> 论坛</a></li>            
            </ul>
        </div>
    </nav>

    <div class="container">
        <h1 class="page-title">艾泽拉斯守护者 - 下载专区</h1>

        <!-- 游戏客户端下载 -->
        <div class="download-section">
            <h2 class="section-title"><i class="fa fa-gamepad"></i> 游戏客户端</h2>
            
            <div class="download-card">
                <div class="download-header">
                    <div class="download-icon">
                        <i class="fa fa-archive"></i>
                    </div>
                    <h3 class="download-title">魔兽世界 3.3.5a 客户端</h3>
                </div>
                
                <p class="download-info">
                    完整的巫妖王之怒 3.3.5a 客户端，已优化适配本服务器，包含所有游戏内容和地图数据。
                    下载后请按照安装说明进行配置，即可连接服务器进行游戏。
                </p>
                
                <div class="download-meta">
                    <div class="meta-item"><i class="fa fa-file"></i> 文件大小：<?= htmlspecialchars($client['size']) ?></div>
                    <div class="meta-item"><i class="fa fa-check-circle"></i> 版本：3.3.5a (12340)</div>
                    <div class="meta-item"><i class="fa fa-clock-o"></i> 最后更新：2023-11-15</div>
                    <div class="meta-item"><i class="fa fa-shield"></i> 安全认证：已通过</div>
                </div>
                
                <a href="<?= htmlspecialchars($client['url']) ?>" class="download-btn">
                    <i class="fa fa-download"></i> 立即下载客户端
                </a>
            </div>
        </div>

        <!-- Realmlist配置 -->
        <div class="download-section">
            <h2 class="section-title"><i class="fa fa-cogs"></i> 服务器配置</h2>
            
            <div class="download-card">
                <div class="download-header">
                    <div class="download-icon">
                        <i class="fa fa-file-text"></i>
                    </div>
                    <h3 class="download-title">Realmlist 配置</h3>
                </div>
                
                <p class="download-info">
                    客户端登录前需配置Realmlist文件，以确保能够正确连接到我们的服务器。
                    请复制以下内容替换您客户端中的realmlist.wtf文件。
                </p>
                
                <div class="realmlist-box">
                    <pre id="realmlistText">set realmlist <?= htmlspecialchars($serverinfo['realmlist']) ?></pre>
                    <button onclick="copyRealmlist(this)" class="copy-btn">
                        <i class="fa fa-copy"></i> 复制
                    </button>
                </div>
                
                <p class="download-info">
                    <i class="fa fa-folder-open"></i> 文件路径：World of Warcraft\Data\zhCN\realmlist.wtf
                </p>
            </div>
        </div>

        <!-- 插件下载 -->
        <div class="download-section">
            <h2 class="section-title"><i class="fa fa-puzzle-piece"></i> 推荐插件</h2>
            
            <p class="download-info" style="margin-bottom: 20px;">
                以下是我们推荐的游戏插件，这些插件经过测试与本服务器兼容，能够提升您的游戏体验。
                所有插件均为免费开源软件，安全可靠。
            </p>
            
            <div class="plugin-grid">
                <!-- 插件1 -->
                <div class="download-card">
                    <div class="download-header">
                        <div class="download-icon">
                            <i class="fa fa-map"></i>
                        </div>
                        <h3 class="download-title">大脚插件335a</h3>
                    </div>
                    
                    <div>
                        <span class="plugin-tag">战斗增强</span>
                        <span class="plugin-tag">界面美化</span>
                        <span class="plugin-tag">团队协作</span>
                        <span class="plugin-tag">任务提示</span>
                    </div>
                    
                    <p class="download-info">
                        为魔兽3.3.5打造，修复旧版本兼容性 BUG，优化 ICC 等核心副本适配，兼容环境好，错误率低。​
                    </p>
                    
                    <div class="download-meta">
                        <div class="meta-item"><i class="fa fa-file"></i> 大小：20.5MB</div>
                        <div class="meta-item"><i class="fa fa-calendar"></i> 更新：2023-10-28</div>
                    </div>
                    
                    <a href="#" class="download-btn">
                        <i class="fa fa-download"></i> 下载插件
                    </a>
                </div>

                </div>
            </div>
        </div>
    </div>

   <script>
        // 修复复制Realmlist配置功能
        document.addEventListener('DOMContentLoaded', function() {
            window.copyRealmlist = function(btn) {
                // 获取要复制的文本
                const realmlistElement = document.getElementById('realmlistText');
                const textToCopy = realmlistElement.textContent.trim();
                
                // 方法1: 使用Clipboard API (现代浏览器)
                if (navigator.clipboard) {
                    navigator.clipboard.writeText(textToCopy)
                        .then(() => showCopyFeedback(btn, true))
                        .catch(() => fallbackCopy(btn, textToCopy));
                } else {
                    // 方法2: 兼容旧浏览器的复制方法
                    fallbackCopy(btn, textToCopy);
                }
            };

            // 显示复制反馈
            function showCopyFeedback(btn, isSuccess) {
                const originalContent = btn.innerHTML;
                btn.innerHTML = isSuccess ? '<i class="fa fa-check"></i> 已复制' : '<i class="fa fa-times"></i> 复制失败';
                btn.style.background = isSuccess ? '#28a745' : '#dc3545';
                
                setTimeout(() => {
                    btn.innerHTML = originalContent;
                    btn.style.background = ''; // 恢复默认样式
                }, 2000);
            }

            // 备用复制方法
            function fallbackCopy(btn, text) {
                // 创建临时文本区域
                const textarea = document.createElement('textarea');
                textarea.value = text;
                textarea.style.position = 'fixed'; // 避免滚动问题
                document.body.appendChild(textarea);
                
                // 选中并复制
                textarea.select();
                const isSuccess = document.execCommand('copy');
                
                // 清理临时元素
                document.body.removeChild(textarea);
                
                // 显示反馈
                showCopyFeedback(btn, isSuccess);
            }
        });
    </script>
</body>
</html>