<?php
/**
 * 魔兽世界账号注册系统 - 主页面
 */

// 检查PHP扩展
require_once 'core.php';
checkPhpExtensions();

// 加载配置
$config = require_once 'config.php';

// 语言切换逻辑
$currentLang = $config['language']['default'];
$langParam = $config['language']['param'];
if (isset($_GET[$langParam]) && in_array($_GET[$langParam], $config['language']['available'])) {
    $currentLang = $_GET[$langParam];
}
$text = $config['text'][$currentLang];

// 生成语言切换链接
function getLangUrl($lang, $config) {
    $params = $_GET;
    $params[$config['language']['param']] = $lang;
    return $_SERVER['PHP_SELF'] . '?' . http_build_query($params);
}

// 初始化会话
session_start();

// 数据库连接
$pdo = getDbConnection($config);

// 处理AJAX请求
handleAjaxRequest($pdo, $text);

// 处理表单提交
$formResult = handleFormSubmission($pdo, $config, $text);
extract($formResult);
?>
<!DOCTYPE html>
<html lang="<?= $currentLang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= sprintf(htmlspecialchars($text['title']), htmlspecialchars($text['name'])) ?></title>
    <!-- 外部资源 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/font-awesome@4.7.0/css/font-awesome.min.css" rel="stylesheet">
    <!-- Cloudflare Turnstile -->
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    <style>
        /* 魔兽风格主题 */
        :root {
            --wow-gold: #c8aa6e;
            --wow-gold-light: #e6c57b;
            --wow-dark: #0f0f0f;
            --wow-red: #a80000;
            --wow-bg: #141414;
            --wow-card: #1a1a1a;
            --wow-border: #4a3c2a;
        }

body {
    /* 确保背景图正确加载并全屏显示 */
    background: url('./bg.jpg') no-repeat center center fixed;
    background-size: cover;
    /* 移除原有的背景色叠加，确保背景图清晰显示 */
    background-color: transparent;
    color: #f0f0f0;
    min-height: 100vh;
    padding: 20px 0;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

.bg-overlay {
    /* 设置90%透明度 */
    background-color: rgba(20, 20, 20, 0.9);
    border-radius: 8px;
    border: 1px solid var(--wow-border);
    box-shadow: 0 0 30px rgba(0, 0, 0, 0.8);
    overflow: hidden;
}

        .wow-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 15px;
        }

        .bg-overlay {
            background-color: rgba(20, 20, 20, 0.92);
            border-radius: 8px;
            border: 1px solid var(--wow-border);
            box-shadow: 0 0 30px rgba(0, 0, 0, 0.8);
            overflow: hidden;
        }

        .wow-header {
            background: linear-gradient(180deg, #2a2a2a 0%, #1a1a1a 100%);
            padding: 15px 30px;
            border-bottom: 1px solid var(--wow-border);
        }

        .wow-title {
            color: var(--wow-gold);
            text-shadow: 0 0 10px rgba(200, 170, 110, 0.3);
            margin: 0;
            font-weight: 700;
            letter-spacing: 0.5px;
            text-align: center;
        }

        .server-status {
            background-color: rgba(0, 0, 0, 0.3);
            padding: 15px 30px;
            border-bottom: 1px solid var(--wow-border);
            display: flex;
            flex-wrap: wrap;
            gap: 25px;
            justify-content: center;
            align-items: center;
        }

        .status-item {
            display: flex;
            align-items: center;
            font-size: 1rem;
            padding: 5px 10px;
            background-color: rgba(0, 0, 0, 0.2);
            border-radius: 4px;
        }

        .status-item i {
            color: var(--wow-gold);
            margin-right: 8px;
            width: 16px;
            text-align: center;
        }

        .population-indicator {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-right: 6px;
            background-color: #4CAF50;
        }

        .main-content {
            padding: 30px;
            display: flex;
            flex-wrap: wrap;
            gap: 30px;
            justify-content: center;
        }

        .wow-card {
            background-color: var(--wow-card);
            border: 1px solid var(--wow-border);
            border-radius: 6px;
            padding: 25px;
            transition: all 0.3s ease;
        }

        .wow-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.3);
            border-color: var(--wow-gold);
        }

        .card-title {
            color: var(--wow-gold);
            border-bottom: 1px solid var(--wow-border);
            padding-bottom: 10px;
            margin-top: 0;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
        }

        .card-title i {
            margin-right: 10px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        label {
            display: block;
            margin-bottom: 10px;
            color: #e0e0e0;
            font-weight: 500;
            font-size: 1.1rem;
        }

        .form-control {
            background-color: rgba(255, 255, 255, 0.08);
            border: 1px solid var(--wow-border);
            color: #f0f0f0;
            padding: 15px 15px;
            border-radius: 4px;
            transition: all 0.3s ease;
            font-size: 1.05rem;
            height: auto;
        }

        .form-control:focus {
            background-color: rgba(255, 255, 255, 0.12);
            border-color: var(--wow-gold);
            box-shadow: 0 0 0 3px rgba(200, 170, 110, 0.2);
            color: white;
            outline: none;
        }

        .btn-wow {
            background: linear-gradient(180deg, var(--wow-gold) 0%, #b09050 100%);
            color: #1a1a1a;
            border: none;
            padding: 12px 20px;
            border-radius: 4px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.05rem;
        }

        .btn-wow i {
            margin-right: 8px;
        }

        .btn-wow:hover {
            background: linear-gradient(180deg, var(--wow-gold-light) 0%, var(--wow-gold) 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
        }

        .error {
            color: #ff6b6b;
            font-size: 0.9rem;
            margin-top: 5px;
            display: flex;
            align-items: center;
        }

        .error i {
            margin-right: 5px;
            font-size: 0.9rem;
        }

        .success {
            color: #4ecdc4;
            font-size: 0.9rem;
            margin-top: 5px;
            display: flex;
            align-items: center;
        }

        .success i {
            margin-right: 5px;
            font-size: 0.9rem;
        }

        .hint {
            color: #a0a0a0;
            font-size: 0.9rem;
            margin-top: 5px;
        }

        .turnstile-container {
            margin: 15px 0;
            padding: 10px;
            border: 1px solid var(--wow-border);
            border-radius: 4px;
            background-color: rgba(255, 255, 255, 0.05);
            transition: border-color 0.3s ease;
        }
        
        .turnstile-container.error {
            border-color: #ff6b6b;
        }

        .lang-switch {
            text-align: right;
            margin-bottom: 10px;
        }

        .lang-switch a {
            color: var(--wow-gold);
            margin-left: 15px;
            text-decoration: none;
            font-size: 0.9rem;
            transition: all 0.2s ease;
            padding: 3px 6px;
            border-radius: 3px;
        }

        .lang-switch a:hover, .lang-switch a.active {
            color: var(--wow-gold-light);
            background-color: rgba(200, 170, 110, 0.1);
        }

        .download-box {
            background-color: rgba(0, 0, 0, 0.2);
            border: 1px solid var(--wow-border);
            border-radius: 4px;
            padding: 15px;
            margin-bottom: 15px;
        }

        .download-title {
            color: var(--wow-gold);
            margin-top: 0;
            margin-bottom: 10px;
            font-size: 1.1rem;
        }

        .download-info {
            font-size: 0.9rem;
            color: #b0b0b0;
            margin-bottom: 10px;
        }

        .download-info i {
            width: 16px;
            text-align: center;
            margin-right: 5px;
        }

        .realmlist-box {
            position: relative;
        }

        pre {
            background-color: rgba(0, 0, 0, 0.3);
            border: 1px solid var(--wow-border);
            border-radius: 4px;
            padding: 10px;
            margin-bottom: 10px;
            color: #e0e0e0;
            font-size: 0.9rem;
            overflow-x: auto;
        }

        @media (max-width: 768px) {
            .main-content {
                padding: 15px;
            }
            
            .server-status {
                padding: 15px;
                gap: 15px;
            }
            
            .wow-header {
                padding: 15px;
            }
            
            .form-section, .info-section {
                width: 100% !important;
            }
        }
    </style>
</head>
<body>
    <div class="wow-container">
        <div class="bg-overlay">
            <!-- 头部区域 -->
            <div class="wow-header">
                <div class="lang-switch">
                    <a href="<?= getLangUrl('zh', $config) ?>" <?= $currentLang === 'zh' ? 'class="active"' : '' ?>>
                        <i class="fa fa-language"></i> 中文
                    </a>
                    <a href="<?= getLangUrl('en', $config) ?>" <?= $currentLang === 'en' ? 'class="active"' : '' ?>>
                        <i class="fa fa-language"></i> English
                    </a>
                </div>
                <h1 class="wow-title">
                    <?= htmlspecialchars($text['name']) ?>
                </h1>
            </div>

            <!-- 服务器状态 -->
            <div class="server-status">
                <div class="status-item">
                    官网：<span><?= htmlspecialchars($config['realm_info']['website']) ?></span>
                </div>
                <div class="status-item">
                    状态: <?= htmlspecialchars($config['realm_info']['population']) ?>
                </div>
                <div class="status-item">
                    类型：<span><?= htmlspecialchars($config['realm_info']['type']) ?></span>
                </div>
                <div class="status-item">
                    版本：<span><?= htmlspecialchars($config['realm_info']['version']) ?></span>
                </div>
            </div>

            <!-- 主内容区 -->
            <div class="main-content">
                <!-- 注册表单区域 -->
                <div class="form-section" style="flex: 1; min-width: 400px;">
                    <?php if ($success): ?>
                        <div class="wow-card text-center">
                            <div class="mb-4 text-success">
                                <i class="fa fa-check-circle fa-5x"></i>
                            </div>
                            <h3 class="card-title">
                                <i class="fa fa-trophy"></i><?= htmlspecialchars($text['success_title']) ?>
                            </h3>
                            <p class="mb-4"><?= htmlspecialchars($text['success_msg']) ?></p>
                            <button class="btn-wow" onclick="window.location.reload()">
                                <i class="fa fa-home"></i><?= htmlspecialchars($text['back_home']) ?>
                            </button>
                        </div>
                    <?php else: ?>
                        <?php if (!empty($errors['global'])): ?>
                            <div class="alert alert-danger mb-4 p-3" style="background-color: rgba(168, 0, 0, 0.2); border: 1px solid #5c0000; color: #ff8a8a;">
                                <?php foreach ($errors['global'] as $error): ?>
                                    <p class="mb-0"><i class="fa fa-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?></p>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <div class="wow-card">
                            <h3 class="card-title">
                                <i class="fa fa-user-plus"></i><?= htmlspecialchars($text['register']) ?>
                            </h3>
                            <form method="post" id="registrationForm">
                                <!-- 用户名 -->
                                <div class="form-group">
                                    <label for="username">
                                        <i class="fa fa-user text-wow-gold"></i><?= htmlspecialchars($text['username']) ?>
                                    </label>
                                    <input type="text" id="username" name="username" 
                                        value="<?= htmlspecialchars($username) ?>" 
                                        class="form-control <?= !empty($errors['username']) ? 'is-invalid' : '' ?>">
                                    <div id="usernameError">
                                        <?php foreach ($errors['username'] as $error): ?>
                                            <div class="error"><i class="fa fa-times-circle"></i><?= htmlspecialchars($error) ?></div>
                                        <?php endforeach; ?>
                                        <div class="hint"><?= htmlspecialchars($text['username_hint']) ?></div>
                                    </div>
                                </div>

                                <!-- 邮箱 -->
                                <div class="form-group">
                                    <label for="email">
                                        <i class="fa fa-envelope text-wow-gold"></i><?= htmlspecialchars($text['email']) ?>
                                    </label>
                                    <input type="email" id="email" name="email" 
                                        value="<?= htmlspecialchars($email) ?>" 
                                        class="form-control <?= !empty($errors['email']) ? 'is-invalid' : '' ?>">
                                    <div id="emailError">
                                        <?php foreach ($errors['email'] as $error): ?>
                                            <div class="error"><i class="fa fa-times-circle"></i><?= htmlspecialchars($error) ?></div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <!-- 密码 -->
                                <div class="form-group">
                                    <label for="password">
                                        <i class="fa fa-lock text-wow-gold"></i><?= htmlspecialchars($text['password']) ?>
                                    </label>
                                    <input type="password" id="password" name="password" 
                                        class="form-control <?= !empty($errors['password']) ? 'is-invalid' : '' ?>">
                                    <div>
                                        <?php foreach ($errors['password'] as $error): ?>
                                            <div class="error"><i class="fa fa-times-circle"></i><?= htmlspecialchars($error) ?></div>
                                        <?php endforeach; ?>
                                        <div class="hint"><i class="fa fa-info-circle"></i><?= htmlspecialchars($text['password_length']) ?></div>
                                        <div class="hint"><i class="fa fa-info-circle"></i><?= htmlspecialchars($text['password_char']) ?></div>
                                    </div>
                                </div>

                                <!-- 确认密码 -->
                                <div class="form-group">
                                    <label for="password_confirm">
                                        <i class="fa fa-lock-open text-wow-gold"></i><?= htmlspecialchars($text['password_confirm']) ?>
                                    </label>
                                    <input type="password" id="password_confirm" name="password_confirm" 
                                        class="form-control <?= !empty($errors['password_confirm']) ? 'is-invalid' : '' ?>">
                                    <div>
                                        <?php foreach ($errors['password_confirm'] as $error): ?>
                                            <div class="error"><i class="fa fa-times-circle"></i><?= htmlspecialchars($error) ?></div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <!-- 人机验证 -->
                                <div class="form-group">
                                    <label>
                                        <i class="fa fa-shield text-wow-gold"></i><?= htmlspecialchars($text['verify_human']) ?>
                                    </label>
                                    <div class="turnstile-container <?= !empty($errors['turnstile']) ? 'error' : '' ?>">
                                        <div class="cf-turnstile" data-sitekey="<?= htmlspecialchars($config['turnstile']['site_key']) ?>" data-theme="dark"></div>
                                    </div>
                                </div>

                                <!-- 提交按钮 -->
                                <button type="submit" class="btn-wow w-100 mb-3">
                                    <i class="fa fa-check"></i><?= htmlspecialchars($text['submit_btn']) ?>
                                </button>

                                <!-- 服务条款 -->
                                <p class="text-center text-sm" style="color: #a0a0a0; margin: 0;">
                                    <?= htmlspecialchars($text['terms_agree']) ?> 
                                    <a href="#" style="color: var(--wow-gold);"> <?= htmlspecialchars($text['terms']) ?></a> 
                                    <?= htmlspecialchars($text['privacy']) ?>
                                </p>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- 右侧信息区域 -->
                <div class="info-section" style="flex: 0 0 350px; min-width: 300px;">
                    <!-- 服务器介绍 -->
                    <div class="wow-card">
                        <h3 class="card-title">
                            <i class="fa fa-info-circle"></i><?= htmlspecialchars($text['server_info']) ?>
                        </h3>
                        <p class="mb-3" style="line-height: 1.6;"><?= htmlspecialchars($text['description']) ?></p>
                        <ul class="list-unstyled" style="line-height: 1.6;">
                            <li class="mb-2"><i class="fa fa-check text-success me-2"></i><?= htmlspecialchars($text['tips1']) ?></li>
                            <li class="mb-2"><i class="fa fa-check text-success me-2"></i><?= htmlspecialchars($text['tips2']) ?></li>
                            <li class="mb-2"><i class="fa fa-check text-success me-2"></i><?= htmlspecialchars($text['tips3']) ?></li>
                            <li><i class="fa fa-check text-success me-2"></i><?= htmlspecialchars($text['tips4']) ?></li>
                        </ul>
                    </div>

                    <!-- 客户端下载 -->
                    <div class="wow-card">
                        <h3 class="card-title">
                            <i class="fa fa-download"></i><?= htmlspecialchars($text['client_download']) ?>
                        </h3>
                        <div class="download-box">
                            <h4 class="download-title"><?= htmlspecialchars($config['downloads']['client']['name']) ?></h4>
                            <div class="download-info">
                                <div><i class="fa fa-hdd-o"></i>大小: <?= htmlspecialchars($config['downloads']['client']['size']) ?></div>
                            </div>
                            <a href="<?= htmlspecialchars($config['downloads']['client']['url']) ?>" class="btn-wow w-100">
                                <i class="fa fa-download"></i><?= htmlspecialchars($text['download']) ?>
                            </a>
                        </div>
                    </div>

                    <!-- Realmlist配置 -->
                    <div class="wow-card">
                        <h3 class="card-title">
                            <i class="fa fa-cogs"></i><?= htmlspecialchars($text['realmlist_config']) ?>
                        </h3>
                        <p class="mb-2 text-sm" style="color: #b0b0b0; margin-bottom: 10px;"><?= htmlspecialchars($text['realmlist_hint']) ?></p>
                        <div class="realmlist-box">
                            <pre id="realmlistText">set realmlist <?= htmlspecialchars($config['realm_info']['realmlist']) ?></pre>
                            <button onclick="copyRealmlist(this)" class="btn-wow w-100">
                                <i class="fa fa-copy"></i><?= htmlspecialchars($text['copy']) ?>
                            </button>
                        </div>
                        <p class="text-xs" style="color: #888; margin-top: 10px; margin-bottom: 0;">
                            <?= htmlspecialchars($text['file']) ?>：<?= htmlspecialchars($text['path']) ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>

    // 页面加载完成后执行
    document.addEventListener('DOMContentLoaded', function() {
        // 清空用户名输入框
        const usernameInput = document.getElementById('username');
        if (usernameInput) usernameInput.value = '';
        
        // 清空邮箱输入框
        const emailInput = document.getElementById('email');
        if (emailInput) emailInput.value = '';
        
        // 清空密码输入框
        const passwordInput = document.getElementById('password');
        if (passwordInput) passwordInput.value = '';
        
        // 清空确认密码输入框
        const passwordConfirmInput = document.getElementById('password_confirm');
        if (passwordConfirmInput) passwordConfirmInput.value = '';
    });

        // 复制Realmlist配置
        function copyRealmlist(btn) {
            const text = document.getElementById('realmlistText').textContent.trim();
            navigator.clipboard.writeText(text).then(() => {
                const originalText = btn.innerHTML;
                btn.innerHTML = `<i class="fa fa-check"></i><?= htmlspecialchars($text['copied']) ?>`;
                btn.style.background = 'linear-gradient(180deg, #4CAF50 0%, #3d8b40 100%)';
                btn.style.color = 'white';
                setTimeout(() => {
                    btn.innerHTML = originalText;
                    btn.style.background = '';
                    btn.style.color = '';
                }, 2000);
            });
        }

        // 防抖函数
        function debounce(func, wait) {
            let timeout;
            return function() {
                const context = this;
                const args = arguments;
                clearTimeout(timeout);
                timeout = setTimeout(() => func.apply(context, args), wait);
            };
        }

        // 用户名实时验证
        const usernameInput = document.getElementById('username');
        usernameInput.addEventListener('input', debounce(function() {
            const username = this.value.trim();
            const errorEl = document.getElementById('usernameError');
            
            if (username.length < 3) {
                errorEl.innerHTML = `<div class="hint"><?= htmlspecialchars($text['username_hint']) ?></div>`;
                return;
            }

            fetch('', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `action=check_username&username=${encodeURIComponent(username)}`
            })
            .then(res => res.json())
            .then(data => {
                errorEl.innerHTML = data.valid 
                    ? `<div class="success"><i class="fa fa-check-circle"></i>${data.message}</div><div class="hint"><?= htmlspecialchars($text['username_hint']) ?></div>`
                    : `<div class="error"><i class="fa fa-times-circle"></i>${data.message}</div><div class="hint"><?= htmlspecialchars($text['username_hint']) ?></div>`;
            });
        }, 500));

        // 邮箱实时验证
        const emailInput = document.getElementById('email');
        emailInput.addEventListener('input', debounce(function() {
            const email = this.value.trim();
            const errorEl = document.getElementById('emailError');
            
            if (!email.includes('@')) {
                errorEl.innerHTML = '';
                return;
            }

            fetch('', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `action=check_email&email=${encodeURIComponent(email)}`
            })
            .then(res => res.json())
            .then(data => {
                errorEl.innerHTML = data.valid 
                    ? `<div class="success"><i class="fa fa-check-circle"></i>${data.message}</div>`
                    : `<div class="error"><i class="fa fa-times-circle"></i>${data.message}</div>`;
            });
        }, 500));


// 表单提交前验证
document.getElementById('registrationForm').addEventListener('submit', function(e) {
    // 阻止表单默认提交行为
    e.preventDefault();
    
    // 获取表单字段
    const username = document.getElementById('username').value.trim();
    const email = document.getElementById('email').value.trim();
    const password = document.getElementById('password').value;
    const passwordConfirm = document.getElementById('password_confirm').value;
    const turnstileResponse = document.querySelector('input[name="cf-turnstile-response"]')?.value || '';
    
    // 清空之前的错误提示
    document.querySelectorAll('.error').forEach(el => el.remove());
    
    let hasError = false;
    
    // 用户名验证
    if (username === '') {
        showError('username', '<?= htmlspecialchars($text['username']) ?><?= htmlspecialchars($text['required_empty']) ?>');
        hasError = true;
    }
    
    // 邮箱验证
    if (email === '') {
        showError('email', '<?= htmlspecialchars($text['email']) ?><?= htmlspecialchars($text['required_empty']) ?>');
        hasError = true;
    }
    
    // 密码验证
    if (password === '') {
        showError('password', '<?= htmlspecialchars($text['password']) ?><?= htmlspecialchars($text['required_empty']) ?>');
        hasError = true;
    }
    
    // 确认密码验证
    if (passwordConfirm === '') {
        showError('password_confirm', '<?= htmlspecialchars($text['password_confirm']) ?><?= htmlspecialchars($text['required_empty']) ?>');
        hasError = true;
    }
    
    // 人机验证
    if (turnstileResponse.trim() === '') {
        showTurnstileError('<?= htmlspecialchars($text['verify_human']) ?>');
        hasError = true;
    }
    
    // 如果没有错误则提交表单
    if (!hasError) {
        this.submit();
    }
});

// 显示字段错误提示
function showError(fieldId, message) {
    const field = document.getElementById(fieldId);
    const errorDiv = document.createElement('div');
    errorDiv.className = 'error';
    errorDiv.innerHTML = `<i class="fa fa-times-circle"></i>${message}`;
    
    // 将错误提示添加到字段下方
    if (fieldId === 'username') {
        document.getElementById('usernameError').prepend(errorDiv);
    } else {
        field.parentNode.appendChild(errorDiv);
    }
    
    // 高亮显示错误字段
    field.classList.add('is-invalid');
    
    // 移除错误高亮（当用户开始输入时）
    field.addEventListener('input', function removeError() {
        this.classList.remove('is-invalid');
        errorDiv.remove();
        this.removeEventListener('input', removeError);
    });
}

// 显示人机验证错误
function showTurnstileError(message) {
    const container = document.querySelector('.turnstile-container');
    const errorDiv = document.createElement('div');
    errorDiv.className = 'error';
    errorDiv.innerHTML = `<i class="fa fa-times-circle"></i>${message}`;
    
    container.classList.add('error');
    container.appendChild(errorDiv);
    
    // 当验证完成后移除错误提示
    const checkInterval = setInterval(() => {
        const response = document.querySelector('input[name="cf-turnstile-response"]')?.value || '';
        if (response) {
            container.classList.remove('error');
            errorDiv.remove();
            clearInterval(checkInterval);
        }
    }, 1000);
}

</script>
</body>
</html>