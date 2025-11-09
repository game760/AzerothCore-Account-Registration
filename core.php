<?php
/**
 * 魔兽世界账号注册系统 - 核心功能
 */
 
 // 数据库配置
require_once 'db_config.php';

// SRP6加密算法实现
function calculateSRP6Verifier($username, $password, $salt) {
    $g = gmp_init(7);
    $N = gmp_init('894B645E89E1535BBDAD5B8B290650530801B18EBFBF5E8FAB3C82872A3E9BB7', 16);
    $h1 = sha1(strtoupper($username . ':' . $password), TRUE);
    $h2 = sha1($salt . $h1, TRUE);
    $h2 = gmp_import($h2, 1, GMP_LSW_FIRST);
    $verifier = gmp_powm($g, $h2, $N);
    return str_pad(gmp_export($verifier, 1, GMP_LSW_FIRST), 32, chr(0), STR_PAD_RIGHT);
}

// 生成盐值和验证值
function generateSaltAndVerifier($username, $password) {
    $salt = random_bytes(32);
    return [$salt, calculateSRP6Verifier($username, $password, $salt)];
}

// 检查用户名是否存在
function checkUsernameExists($pdo, $username) {
    $stmt = $pdo->prepare("SELECT id FROM account WHERE username = :username");
    $stmt->bindParam(':username', $username, PDO::PARAM_STR);
    $stmt->execute();
    return $stmt->rowCount() > 0;
}

// 检查邮箱是否存在
function checkEmailExists($pdo, $email) {
    $stmt = $pdo->prepare("SELECT id FROM account WHERE email = :email");
    $stmt->bindParam(':email', $email, PDO::PARAM_STR);
    $stmt->execute();
    return $stmt->rowCount() > 0;
}

// 创建账号
function createAccount($pdo, $config, $username, $email, $password, $clientIp) {
    try {
        list($salt, $verifier) = generateSaltAndVerifier($username, $password);
        $stmt = $pdo->prepare("
            INSERT INTO account 
            (username, salt, verifier, email, joindate, last_ip, expansion, mutetime, locale)
            VALUES (:username, :salt, :verifier, :email, NOW(), :clientIp, :expansion, 0, 3)
        ");
        $stmt->execute([
            ':username' => $username,
            ':salt' => $salt,
            ':verifier' => $verifier,
            ':email' => $email,
            ':clientIp' => $clientIp,
            ':expansion' => $config['expansion']
        ]);
        return true;
    } catch (PDOException $e) {
        error_log("创建账号失败: " . $e->getMessage());
        throw new Exception("数据库操作失败");
    }
}

// 处理AJAX请求
function handleAjaxRequest($pdo) {
    global $emailConfig;
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['action'])) {
        return;
    }
    $response = ['valid' => false, 'message' => ''];
    switch ($_POST['action']) {
        case 'check_email':
            $email = trim($_POST['email'] ?? '');
            $emailErrors = [];

            if (empty($email)) {
                $emailErrors[] = '邮箱不能为空';
            } else {
                $emailLen = strlen($email);
                if ($emailLen < $emailConfig['min_length'] || $emailLen > $emailConfig['max_length']) {
                    $emailErrors[] = "邮箱长度需在{$emailConfig['min_length']}-{$emailConfig['max_length']}位之间";
                }
                
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $emailErrors[] = '邮箱格式不正确';
                }
                
                if (!$emailConfig['allow_special_chars'] && preg_match('/[^a-zA-Z0-9@._-]/', $email)) {
                    $emailErrors[] = '邮箱包含不允许的特殊字符';
                }
                
                $domain = substr(strrchr($email, "@"), 1);
                if ($domain) {
                    if (in_array($domain, $emailConfig['blocked_domains'])) {
                        $emailErrors[] = "禁止使用{$domain}域名的邮箱";
                    }
                    if (!empty($emailConfig['allowed_domains']) && !in_array($domain, $emailConfig['allowed_domains'])) {
                        $allowed = implode('、', $emailConfig['allowed_domains']);
                        $emailErrors[] = "仅允许使用以下域名的邮箱：{$allowed}";
                    }
                }
            }

            if (empty($emailErrors)) {
                $response = checkEmailExists($pdo, $email)
                    ? ['valid' => false, 'message' => '邮箱已被注册']
                    : ['valid' => true, 'message' => '邮箱可用'];
            } else {
                $response['message'] = implode('，', $emailErrors);
            }
            break;
            
        case 'check_username':
            $username = trim($_POST['username'] ?? '');
            if (preg_match('/^[a-zA-Z0-9_]{3,16}$/', $username)) {
                $response = checkUsernameExists($pdo, $username)
                    ? ['valid' => false, 'message' => '用户名已被占用']
                    : ['valid' => true, 'message' => '用户名可用'];
            } else {
                $response['message'] = '用户名需3-16位，含字母、数字和下划线';
            }
            break;
    }
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// 生成数字字母验证码（新增）
function generateCaptcha($length = 6) {
    $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $captcha = '';
    for ($i = 0; $i < $length; $i++) {
        $captcha .= $chars[rand(0, strlen($chars) - 1)];
    }
    return $captcha;
}

// 创建验证码图片（新增）
function createCaptchaImage($captcha, $width = 150, $height = 50) {
    $image = imagecreatetruecolor($width, $height);
    
    // 设置背景色
    $bgColor = imagecolorallocate($image, 245, 245, 245);
    imagefill($image, 0, 0, $bgColor);
    
    // 添加干扰线
    for ($i = 0; $i < 5; $i++) {
        $lineColor = imagecolorallocate($image, rand(100, 200), rand(100, 200), rand(100, 200));
        imageline($image, rand(0, $width), rand(0, $height), rand(0, $width), rand(0, $height), $lineColor);
    }
    
    // 添加干扰点
    for ($i = 0; $i < 100; $i++) {
        $pointColor = imagecolorallocate($image, rand(50, 150), rand(50, 150), rand(50, 150));
        imagesetpixel($image, rand(0, $width), rand(0, $height), $pointColor);
    }
    
    // 绘制验证码
    $fontSize = 20;
    $fontFile = __DIR__ . '/fonts/arial.ttf'; // 确保存在该字体文件
    
    // 检查字体文件是否存在，不存在则使用默认字体
    if (!file_exists($fontFile)) {
        $fontFile = 5; // 使用GD库内置字体
    }
    
    $textColors = [
        imagecolorallocate($image, 30, 30, 30),
        imagecolorallocate($image, 100, 30, 30),
        imagecolorallocate($image, 30, 100, 30),
        imagecolorallocate($image, 30, 30, 100)
    ];
    
    $charWidth = $width / strlen($captcha);
    for ($i = 0; $i < strlen($captcha); $i++) {
        $char = $captcha[$i];
        $x = $i * $charWidth + 5;
        $y = $height / 2 + 8;
        $angle = rand(-20, 20);
        $color = $textColors[rand(0, count($textColors) - 1)];
        
        if (is_numeric($fontFile)) {
            imagestring($image, $fontFile, $x, $y - 15, $char, $color);
        } else {
            imagettftext($image, $fontSize, $angle, $x, $y, $color, $fontFile, $char);
        }
    }
    
    // 输出图片
    header('Content-Type: image/png');
    imagepng($image);
    imagedestroy($image);
}

// 验证数字字母验证码（新增）
function verifyCaptcha($userInput) {
    if (!isset($_SESSION['captcha']) || !isset($_SESSION['captcha_expire'])) {
        return false;
    }
    
    // 检查是否过期
    if (time() > $_SESSION['captcha_expire']) {
        unset($_SESSION['captcha'], $_SESSION['captcha_expire']);
        return false;
    }
    
    // 验证输入
    $isValid = strtolower($userInput) === strtolower($_SESSION['captcha']);
    unset($_SESSION['captcha'], $_SESSION['captcha_expire']); // 验证后立即失效
    return $isValid;
}

// 验证Cloudflare Turnstile（新增）
function verifyCfTurnstile($response, $secretKey) {
    if (empty($response)) {
        return false;
    }
    
    $verifyUrl = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
    $data = [
        'secret' => $secretKey,
        'response' => $response,
        'remoteip' => $_SERVER['REMOTE_ADDR'] ?? ''
    ];
    
    $options = [
        'http' => [
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query($data)
        ]
    ];
    
    $context  = stream_context_create($options);
    $result = @file_get_contents($verifyUrl, false, $context);
    
    if ($result === false) {
        return false;
    }
    
    $json = json_decode($result);
    return $json && $json->success === true;
}

// 处理表单提交（更新）
function handleFormSubmission($pdo, $config) {
    global $emailConfig, $captchaConfig; // 引入验证码配置
    
    $fieldErrors = [
        'global' => [], 'username' => [], 'email' => [], 
        'password' => [], 'password_confirm' => [], 'captcha' => []
    ];
    $success = false;
    $username = $email = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';
        $clientIp = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'];
        $clientIp = filter_var(explode(',', $clientIp)[0], FILTER_VALIDATE_IP) ?: $clientIp;
        $hasError = false;

        // 验证用户名
        if (empty($username)) {
            $fieldErrors['username'][] = '用户名不能为空';
            $hasError = true;
        } elseif (!preg_match('/^[a-zA-Z0-9_]{3,16}$/', $username)) {
            $fieldErrors['username'][] = '用户名需3-16位，含字母、数字和下划线';
            $hasError = true;
        } elseif (checkUsernameExists($pdo, $username)) {
            $fieldErrors['username'][] = '用户名已被占用';
            $hasError = true;
        }

        // 验证邮箱
        if (empty($email)) {
            $fieldErrors['email'][] = '邮箱不能为空';
            $hasError = true;
        } else {
            $emailLen = strlen($email);
            if ($emailLen < $emailConfig['min_length'] || $emailLen > $emailConfig['max_length']) {
                $fieldErrors['email'][] = "邮箱长度需在{$emailConfig['min_length']}-{$emailConfig['max_length']}位之间";
                $hasError = true;
            }
            
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $fieldErrors['email'][] = '邮箱格式不正确';
                $hasError = true;
            }
            
            if (!$emailConfig['allow_special_chars'] && preg_match('/[^a-zA-Z0-9@._-]/', $email)) {
                $fieldErrors['email'][] = '邮箱包含不允许的特殊字符';
                $hasError = true;
            }
            
            $domain = substr(strrchr($email, "@"), 1);
            if ($domain) {
                if (in_array($domain, $emailConfig['blocked_domains'])) {
                    $fieldErrors['email'][] = "禁止使用{$domain}域名的邮箱";
                    $hasError = true;
                }
                if (!empty($emailConfig['allowed_domains']) && !in_array($domain, $emailConfig['allowed_domains'])) {
                    $allowed = implode('、', $emailConfig['allowed_domains']);
                    $fieldErrors['email'][] = "仅允许使用以下域名的邮箱：{$allowed}";
                    $hasError = true;
                }
            }
            
            if (!isset($fieldErrors['email']) && checkEmailExists($pdo, $email)) {
                $fieldErrors['email'][] = '邮箱已被注册';
                $hasError = true;
            }
        }

        // 验证密码
        if (trim($password) === '') {
            $fieldErrors['password'][] = '密码不能为空';
            $hasError = true;
        } else {
            if (strlen($password) < 8 || !preg_match('/[!@#$%^&*]/', $password)) {
                $fieldErrors['password'][] = '密码需至少8位，含特殊字符（!@#$%^&*）';
                $hasError = true;
            } else {
                if (trim($passwordConfirm) === '') {
                    $fieldErrors['password_confirm'][] = '确认密码不能为空';
                    $hasError = true;
                } elseif ($password !== $passwordConfirm) {
                    $fieldErrors['password_confirm'][] = '两次密码不一致';
                    $hasError = true;
                }
            }
        }

        // 验证码验证（新增）
        if ($captchaConfig['type'] === 'captcha') {
            $userCaptcha = trim($_POST['captcha'] ?? '');
            if (empty($userCaptcha)) {
                $fieldErrors['captcha'][] = '请输入验证码';
                $hasError = true;
            } elseif (!verifyCaptcha($userCaptcha)) {
                $fieldErrors['captcha'][] = '验证码不正确或已过期';
                $hasError = true;
            }
        } elseif ($captchaConfig['type'] === 'cf_turnstile') {
            $turnstileResponse = $_POST['cf-turnstile-response'] ?? '';
            if (empty($turnstileResponse)) {
                $fieldErrors['captcha'][] = '请完成人机验证';
                $hasError = true;
            } elseif (!verifyCfTurnstile($turnstileResponse, $captchaConfig['cf_turnstile']['secret_key'])) {
                $fieldErrors['captcha'][] = '人机验证失败，请重试';
                $hasError = true;
            }
        }

        // 创建账号
        if (!$hasError) {
            try {
                if (createAccount($pdo, $config, $username, $email, $password, $clientIp)) {
                    $success = true;
                    $username = $email = '';
                }
            } catch (Exception $e) {
                error_log("注册错误: " . $e->getMessage());
                $fieldErrors['global'][] = '注册失败，请稍后重试';
                $hasError = true;
            }
        }
    }
    return [
        'success' => $success,
        'errors' => $fieldErrors,
        'username' => $username,
        'email' => $email
    ];
}

// 检查PHP扩展依赖
function checkPhpExtensions() {
    $requiredExtensions = [
        'pdo_mysql' => 'PDO MySQL 扩展（数据库连接）',
        'openssl' => 'OpenSSL 扩展（安全加密）',
        'json' => 'JSON 扩展（AJAX数据处理）',
        'gmp' => 'GMP 扩展（SRP6加密）',
        'filter' => 'Filter 扩展（输入验证）',
        'gd' => 'GD 扩展（验证码图片生成）' // 新增验证码所需扩展
    ];
    $missingExtensions = [];
    foreach ($requiredExtensions as $ext => $desc) {
        if (!extension_loaded($ext)) {
            $missingExtensions[] = "- <strong>{$ext}</strong>：{$desc}";
        }
    }
    if (!empty($missingExtensions)) {
        $missingList = implode("\n", $missingExtensions);
        header('Content-Type: text/html; charset=utf-8');
        echo <<<HTML
        <!DOCTYPE html>
        <html lang="zh-CN">
        <head>
            <meta charset="UTF-8">
            <title>服务器环境错误</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
            <link href="https://cdn.jsdelivr.net/npm/font-awesome@4.7.0/css/font-awesome.min.css" rel="stylesheet">
        </head>
        <body class="bg-dark text-light min-vh-100 d-flex justify-content-center align-items-center p-4">
            <div class="card bg-secondary/20 border-danger border-2 rounded-3 shadow-lg w-100 max-w-md">
                <div class="card-body p-5">
                    <div class="text-danger text-center mb-4">
                        <i class="fa fa-exclamation-triangle fa-3x"></i>
                    </div>
                    <h2 class="text-center text-danger mb-4">缺少必要PHP扩展</h2>
                    <p class="text-light/80 mb-3">无法运行魔兽世界账号注册系统，缺少以下扩展：</p>
                    <ul class="list-unstyled text-danger mb-0">
                        {$missingList}
                    </ul>
                </div>
            </div>
        </body>
        </html>
        HTML;
        exit;
    }
}
?>