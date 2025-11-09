<?php
/**
 * 魔兽世界账号注册系统 - 主页面
 */
// 数据库配置
require_once 'db_config.php';
// 检查PHP扩展
require_once 'core.php';
checkPhpExtensions();
// 初始化会话
session_start();
// 数据库连接（使用PDO）
$pdo = $authConn;

// 生成验证码图片的处理（新增）
if (isset($_GET['action']) && $_GET['action'] === 'generate_captcha' && $captchaConfig['type'] === 'captcha') {
    $captcha = generateCaptcha($captchaConfig['captcha']['length']);
    $_SESSION['captcha'] = $captcha;
    $_SESSION['captcha_expire'] = time() + $captchaConfig['captcha']['expire'];
    createCaptchaImage(
        $captcha, 
        $captchaConfig['captcha']['width'], 
        $captchaConfig['captcha']['height']
    );
    exit;
}

// 处理AJAX请求
handleAjaxRequest($pdo);
// 处理表单提交
$formResult = handleFormSubmission($pdo, $serverinfo);
extract($formResult);
?>
<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>艾泽拉斯守护者 - 账号注册</title>
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
        .wow-container { max-width: 1200px; margin: 30px auto; padding: 0 20px; }
        .bg-overlay { background: #1a1a1a; border-radius: 8px; padding: 30px; }
        .wow-header { text-align: center; margin-bottom: 30px; }
        .wow-title { color: var(--wow-gold); margin: 0; font-size: 32px; }
        .server-status { display: flex; justify-content: center; gap: 30px; margin-bottom: 30px; flex-wrap: wrap; }
        .status-item { color: #aaa; font-size: 14px; }
        .status-item span { color: #fff; margin-left: 5px; }
        .content-section { display: flex; gap: 30px; }
        .form-section { flex: 0 0 60%; max-width:350px; margin: 0 auto; }
        .info-section { flex: 1; }
        .form-section h2, .info-section h2 { color: var(--wow-gold); margin-top: 0; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; color: #ddd; }
        .form-group input { width: 100%; padding: 10px; border: 1px solid #333; border-radius: 4px; background: #222; color: #fff; }
        .form-group small { color: #aaa; font-size: 12px; display: block; margin-top: 5px; }
        .submit-btn { width: 100%; padding: 12px; background: var(--wow-gold); color: #000; border: none; border-radius: 4px; font-size: 16px; font-weight: bold; cursor: pointer; transition: background 0.3s; }
        .submit-btn:hover { background: #e6c200; }
        .download-box { background: #222; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
        .download-title { color: #fff; margin-top: 0; margin-bottom: 15px; }
        .download-info { margin: 0 0 10px 0; color: #ddd; display: flex; align-items: center; gap: 10px; }
        .download-btn { display: inline-block; padding: 10px 20px; background: #0070de; color: #fff; text-decoration: none; border-radius: 4px; transition: background 0.3s; }
        .download-btn:hover { background: #005bb5; }
        .realmlist-box { background: #333; padding: 15px; border-radius: 4px; margin: 10px 0; display: flex; align-items: center; gap: 10px; }
        .realmlist-box pre { margin: 0; color: #0f0; font-size: 14px; flex: 1; }
        .copy-btn { padding: 8px 15px; background: #333; border: 1px solid #555; color: #fff; border-radius: 4px; cursor: pointer; }
        .copy-btn:hover { background: #444; }
        
        /* 错误提示样式 */
        .error-message { background: #3b0b0b; padding: 10px; border-radius: 4px; margin-bottom: 20px; color: #ff6b6b; }
        
        /* 成功提示框样式 */
        .success-modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s ease, visibility 0.3s ease;
        }
        
        .success-modal-overlay.active {
            opacity: 1;
            visibility: visible;
        }
        
        .success-modal {
            background: #1a1a1a;
            border: 2px solid var(--wow-gold);
            border-radius: 8px;
            padding: 30px;
            width: 90%;
            max-width: 500px;
            text-align: center;
            transform: translateY(-20px);
            transition: transform 0.3s ease;
        }
        
        .success-modal-overlay.active .success-modal {
            transform: translateY(0);
        }
        
        .success-modal .icon {
            font-size: 60px;
            color: var(--wow-gold);
            margin-bottom: 20px;
        }
        
        .success-modal h2 {
            color: var(--wow-gold);
            margin-top: 0;
            margin-bottom: 15px;
        }
        
        .success-modal p {
            color: #ddd;
            margin-bottom: 25px;
            font-size: 16px;
        }
        
        .success-modal .close-btn {
            background: var(--wow-gold);
            color: #000;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .success-modal .close-btn:hover {
            background: #e6c200;
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
                <li><a href="index.php" class="active"><i class="fa fa-home"></i> 首页</a></li>
                <li><a href="account.php"><i class="fa fa-user-plus"></i> 账号注册</a></li>
                <li><a href="character.php"><i class="fa fa-list-ol"></i> 英雄榜</a></li>
                <li><a href="downloads.php"><i class="fa fa-download"></i> 下载专区</a></li>
                <li><a href="downloads.php"><i class="fa fa-comments"></i> 论坛</a></li>
            </ul>
        </div>
    </nav>

<div class="wow-container">
    <div class="bg-overlay">

        <!-- 注册表单区域 -->
        <div class="content-section">
            <!-- 注册表单 -->
            <div class="form-section">
                <h2>账号注册</h2>
                <!-- 显示全局错误 -->
                <?php if (!empty($errors['global'])) : ?>
                    <div class="error-message">
                        <?php foreach ($errors['global'] as $err) : ?>
                            <p style="margin: 0;"><?= $err ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <form id="registrationForm" method="post">
                    <!-- 用户名 -->
                    <div class="form-group">
                        <label for="username">用户名</label>
                        <input type="text" id="username" name="username" required value="<?= htmlspecialchars($username) ?>">
                        <small>3-16位，含字母、数字和下划线</small>
                        <?php if (!empty($errors['username'])) : ?>
                            <small style="color: #ff6b6b;"><?= implode(' ', $errors['username']) ?></small>
                        <?php else : ?>
                            <small id="username-error" style="display: none;"></small>
                        <?php endif; ?>
                    </div>
                    
                    <!-- 邮箱 -->
                    <div class="form-group">
                        <label for="email">邮箱</label>
                        <input type="email" id="email" name="email" required value="<?= htmlspecialchars($email) ?>">
                        <small>用于账号找回和验证</small>
                        <?php if (!empty($errors['email'])) : ?>
                            <small style="color: #ff6b6b;"><?= implode(' ', $errors['email']) ?></small>
                        <?php else : ?>
                            <small id="email-error" style="display: none;"></small>
                        <?php endif; ?>
                    </div>
                    
                    <!-- 密码 -->
                    <div class="form-group">
                        <label for="password">密码</label>
                        <input type="password" id="password" name="password" required>
                        <small>至少8位，含特殊字符（!@#$%^&*）</small>
                        <?php if (!empty($errors['password'])) : ?>
                            <small style="color: #ff6b6b;"><?= implode(' ', $errors['password']) ?></small>
                        <?php else : ?>
                            <small id="password-error" style="display: none;"></small>
                        <?php endif; ?>
                    </div>
                    
                    <!-- 确认密码 -->
                    <div class="form-group">
                        <label for="password_confirm">确认密码</label>
                        <input type="password" id="password_confirm" name="password_confirm" required>
                        <small>请再次输入密码</small>
                        <?php if (!empty($errors['password_confirm'])) : ?>
                            <small style="color: #ff6b6b;"><?= implode(' ', $errors['password_confirm']) ?></small>
                        <?php else : ?>
                            <small id="password_confirm-error" style="display: none;"></small>
                        <?php endif; ?>
                    </div>
                    
                    <!-- 验证码（新增） -->
                    <?php if ($captchaConfig['type'] === 'captcha') : ?>
                    <div class="form-group">
                        <label for="captcha">验证码</label>
                        <div style="display: flex; gap: 10px; align-items: center;">
                            <input type="text" id="captcha" name="captcha" required placeholder="请输入图片中的字符" style="flex: 1;">
                            <img src="?action=generate_captcha" alt="验证码" 
                                 style="width: <?= $captchaConfig['captcha']['width'] ?>px; 
                                        height: <?= $captchaConfig['captcha']['height'] ?>px; 
                                        cursor: pointer; border: 1px solid #333; border-radius: 4px;" 
                                 onclick="this.src='?action=generate_captcha&t=' + Math.random()">
                        </div>
                        <small>点击图片可刷新验证码</small>
                        <?php if (!empty($errors['captcha'])) : ?>
                            <small style="color: #ff6b6b;"><?= implode(' ', $errors['captcha']) ?></small>
                        <?php else : ?>
                            <small id="captcha-error" style="display: none;"></small>
                        <?php endif; ?>
                    </div>
                    <?php elseif ($captchaConfig['type'] === 'cf_turnstile') : ?>
                    <div class="form-group">
                        <label>人机验证</label>
                        <div class="cf-turnstile" data-sitekey="<?= htmlspecialchars($captchaConfig['cf_turnstile']['site_key']) ?>"></div>
                        <?php if (!empty($errors['captcha'])) : ?>
                            <small style="color: #ff6b6b;"><?= implode(' ', $errors['captcha']) ?></small>
                        <?php endif; ?>
                        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
                    </div>
                    <?php endif; ?>
                    
                    <!-- 提交按钮 -->
                    <button type="submit" class="submit-btn">创建账号</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- 注册成功提示框 -->
<?php if ($success) : ?>
<div class="success-modal-overlay active" id="successModal">
    <div class="success-modal">
        <div class="icon">
            <i class="fa fa-check-circle"></i>
        </div>
        <h2>恭喜您成功创建账号！</h2>
        <p>现在前往下载页面安装客户端或复制Realmlist配置，<br>使用您注册的账号登录游戏，开始艾泽拉斯冒险之旅。</p>
        <button class="close-btn" onclick="document.getElementById('successModal').classList.remove('active')">
            开始游戏
        </button>
    </div>
</div>
<?php endif; ?>

<script>

    // 密码验证函数
    function validatePassword(password) {
        const minLength = password.length >= 8;
        const hasSpecialChar = /[!@#$%^&*]/.test(password);
        return {
            valid: minLength && hasSpecialChar,
            minLength,
            hasSpecialChar
        };
    }

    // 确认密码验证
    function validatePasswordConfirm(password, confirm) {
        return password === confirm && confirm.trim() !== '';
    }

    // 实时验证用户名和邮箱（AJAX）
    const usernameInput = document.getElementById('username');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    const passwordConfirmInput = document.getElementById('password_confirm');
    const captchaInput = document.getElementById('captcha');
    
    // 用户名验证
    usernameInput.addEventListener('blur', function() {
        const username = this.value.trim();
        if (username) {
            checkField('check_username', 'username', username);
        } else {
            hideError('username');
        }
    });
    
    // 邮箱验证
    emailInput.addEventListener('blur', function() {
        const email = this.value.trim();
        if (email) {
            checkField('check_email', 'email', email);
        } else {
            hideError('email');
        }
    });

    // 密码实时验证
    passwordInput.addEventListener('input', function() {
        const password = this.value;
        const result = validatePassword(password);
        const errorEl = document.getElementById('password-error');
        
        if (password.trim() === '') {
            hideError('password');
            return;
        }
        
        if (!result.valid) {
            let message = [];
            if (!result.minLength) message.push('密码长度至少8位');
            if (!result.hasSpecialChar) message.push('需包含特殊字符(!@#$%^&*)');
            showError('password', message.join('，'), '#ff6b6b');
        } else {
            showError('password', '密码格式有效', '#4CAF50');
            // 触发确认密码验证
            if (passwordConfirmInput.value) {
                checkPasswordConfirm();
            }
        }
    });
    
    // 确认密码验证
    passwordConfirmInput.addEventListener('input', checkPasswordConfirm);
    
    function checkPasswordConfirm() {
        const password = passwordInput.value;
        const confirm = passwordConfirmInput.value;
        const errorEl = document.getElementById('password_confirm-error');
        
        if (confirm.trim() === '') {
            hideError('password_confirm');
            return;
        }
        
        if (!validatePasswordConfirm(password, confirm)) {
            showError('password_confirm', '两次密码不一致', '#ff6b6b');
        } else {
            showError('password_confirm', '密码一致', '#4CAF50');
        }
    }
    
    // AJAX字段验证
    function checkField(action, field, value) {
        fetch('', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=${action}&${field}=${encodeURIComponent(value)}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.valid) {
                showError(field, data.message, '#4CAF50');
            } else {
                showError(field, data.message, '#ff6b6b');
            }
        }).catch(error => {
            console.error('验证请求失败:', error);
        });
    }
    
    // 显示错误信息
    function showError(field, message, color) {
        const errorEl = document.getElementById(`${field}-error`);
        if (errorEl) {
            errorEl.textContent = message;
            errorEl.style.color = color;
            errorEl.style.display = 'block';
        }
    }
    
    // 隐藏错误信息
    function hideError(field) {
        const errorEl = document.getElementById(`${field}-error`);
        if (errorEl) {
            errorEl.textContent = '';
            errorEl.style.display = 'none';
        }
    }

    // 表单提交前最终验证
    document.getElementById('registrationForm').addEventListener('submit', function(e) {
        let isValid = true;
        
        // 验证用户名
        const username = usernameInput.value.trim();
        if (!username || !/^[a-zA-Z0-9_]{3,16}$/.test(username)) {
            isValid = false;
            showError('username', '用户名需3-16位，含字母、数字和下划线', '#ff6b6b');
        }
        
        // 验证邮箱
        const email = emailInput.value.trim();
        if (!email || !isValidEmail(email)) {
            isValid = false;
            showError('email', '邮箱格式不正确', '#ff6b6b');
        }
        
        // 密码最终验证
        const password = passwordInput.value;
        const passwordResult = validatePassword(password);
        if (!passwordResult.valid) {
            isValid = false;
            let message = [];
            if (!passwordResult.minLength) message.push('密码长度至少8位');
            if (!passwordResult.hasSpecialChar) message.push('需包含特殊字符(!@#$%^&*)');
            showError('password', message.join('，'), '#ff6b6b');
        }
        
        // 确认密码最终验证
        if (!validatePasswordConfirm(password, passwordConfirmInput.value)) {
            isValid = false;
            showError('password_confirm', '两次密码不一致', '#ff6b6b');
        }

        // 验证码验证（仅数字字母类型）
        <?php if ($captchaConfig['type'] === 'captcha') : ?>
        const captchaValue = captchaInput.value.trim();
        if (!captchaValue) {
            isValid = false;
            showError('captcha', '请输入验证码', '#ff6b6b');
        }
        <?php endif; ?>
        
        if (!isValid) {
            e.preventDefault();
            // 滚动到第一个错误字段
            const firstError = document.querySelector('[style*="color: #ff6b6b"]');
            if (firstError) {
                firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }
    });
    
    // 邮箱格式验证
    function isValidEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }
    
    // 点击模态框外部关闭
    document.getElementById('successModal')?.addEventListener('click', function(e) {
        if (e.target === this) {
            this.classList.remove('active');
        }
    });
});


</script>
</body>
</html>