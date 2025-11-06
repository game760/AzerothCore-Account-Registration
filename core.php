<?php
/**
 * 魔兽世界账号注册系统 - 核心功能
 */

// 数据库连接函数
function getDbConnection($config) {
    try {
        $dsn = "mysql:host={$config['db']['host']};dbname={$config['db']['name']};charset={$config['db']['charset']}";
        return new PDO(
            $dsn,
            $config['db']['user'],
            $config['db']['pass'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );
    } catch (PDOException $e) {
        error_log("数据库连接错误: " . $e->getMessage());
        die("数据库连接失败，请检查配置");
    }
}

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

function verifyTurnstile($response, $secretKey) {
    // 严格空值检测
    if (empty($response) || !is_string($response) || trim($response) === '') {
        error_log("Turnstile验证失败: 空响应值");
        return false;
    }
    
    // 验证密钥是否配置
    if (empty($secretKey)) {
        error_log("Turnstile验证失败: 未配置密钥");
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
            'content' => http_build_query($data),
            'timeout' => 10, // 设置超时时间
        ]
    ];
    
    $context = stream_context_create($options);
    
    // 错误抑制并捕获异常
    try {
        $result = @file_get_contents($verifyUrl, false, $context);
        if ($result === FALSE) {
            throw new Exception("验证请求失败");
        }
        
        $json = json_decode($result);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("验证响应解析失败: " . json_last_error_msg());
        }
        
        // 检查Cloudflare返回的错误码
        if (!$json->success && !empty($json->{'error-codes'})) {
            error_log("Turnstile验证失败: " . implode(', ', $json->{'error-codes'}));
        }
        
        return $json->success === true;
    } catch (Exception $e) {
        error_log("Turnstile验证异常: " . $e->getMessage());
        return false;
    }
}

// 检查用户名是否存在
function checkUsernameExists($pdo, $username) {
    $stmt = $pdo->prepare("SELECT id FROM account WHERE username = ?");
    $stmt->execute([$username]);
    return $stmt->rowCount() > 0;
}

// 检查邮箱是否存在
function checkEmailExists($pdo, $email) {
    $stmt = $pdo->prepare("SELECT id FROM account WHERE email = ?");
    $stmt->execute([$email]);
    return $stmt->rowCount() > 0;
}

// 创建账号
function createAccount($pdo, $config, $username, $email, $password, $clientIp) {
    list($salt, $verifier) = generateSaltAndVerifier($username, $password);
    $stmt = $pdo->prepare("
        INSERT INTO account 
        (username, salt, verifier, email, joindate, last_ip, expansion, mutetime, locale)
        VALUES (?, ?, ?, ?, NOW(), ?, ?, 0, 3)
    ");
    return $stmt->execute([
        $username, $salt, $verifier, $email, $clientIp, $config['expansion']
    ]);
}

// 处理AJAX请求
function handleAjaxRequest($pdo, $text) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['action'])) {
        return;
    }

    $response = ['valid' => false, 'message' => ''];
    switch ($_POST['action']) {
        case 'check_email':
            $email = trim($_POST['email'] ?? '');
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $response = checkEmailExists($pdo, $email)
                    ? ['valid' => false, 'message' => $text['email_exists']]
                    : ['valid' => true, 'message' => $text['email_available']];
            } else {
                $response['message'] = $text['email_invalid'];
            }
            break;
        
        case 'check_username':
            $username = trim($_POST['username'] ?? '');
            if (preg_match('/^[a-zA-Z0-9_]{3,16}$/', $username)) {
                $response = checkUsernameExists($pdo, $username)
                    ? ['valid' => false, 'message' => $text['username_exists']]
                    : ['valid' => true, 'message' => $text['username_available']];
            } else {
                $response['message'] = $text['username_invalid'];
            }
            break;
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// 处理表单提交
function handleFormSubmission($pdo, $config, $text) {
    $fieldErrors = [
        'global' => [], 'username' => [], 'email' => [], 
        'password' => [], 'password_confirm' => [], 'turnstile' => []
    ];
    $success = false;
    $username = $email = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';
        $turnstileResponse = $_POST['cf-turnstile-response'] ?? '';
        $clientIp = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'];
        $clientIp = filter_var(explode(',', $clientIp)[0], FILTER_VALIDATE_IP) ?: $clientIp;

        $hasError = false;

        // 验证用户名
        if (empty($username)) {
            $fieldErrors['username'][] = $text['username'] . $text['captcha_empty'];
            $hasError = true;
        } elseif (!preg_match('/^[a-zA-Z0-9_]{3,16}$/', $username)) {
            $fieldErrors['username'][] = $text['username_invalid'];
            $hasError = true;
        } elseif (checkUsernameExists($pdo, $username)) {
            $fieldErrors['username'][] = $text['username_exists'];
            $hasError = true;
        }

        // 验证邮箱
        if (empty($email)) {
            $fieldErrors['email'][] = $text['email'] . $text['captcha_empty'];
            $hasError = true;
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $fieldErrors['email'][] = $text['email_invalid'];
            $hasError = true;
        } elseif (checkEmailExists($pdo, $email)) {
            $fieldErrors['email'][] = $text['email_exists'];
            $hasError = true;
        }

        // 验证密码
        if (empty($password)) {
            $fieldErrors['password'][] = $text['password'] . $text['captcha_empty'];
            $hasError = true;
        } elseif (strlen($password) < 8 || !preg_match('/[!@#$%^&*]/', $password)) {
            $fieldErrors['password'][] = $text['password_invalid'];
            $hasError = true;
        }

        // 验证确认密码
        if (empty($passwordConfirm)) {
            $fieldErrors['password_confirm'][] = $text['password_confirm'] . $text['captcha_empty'];
            $hasError = true;
        } elseif ($password !== $passwordConfirm) {
            $fieldErrors['password_confirm'][] = $text['password_mismatch'];
            $hasError = true;
        }

// 人机验证的部分
if (empty($turnstileResponse)) {
    // 只记录错误日志，不返回前端错误信息
    error_log("Turnstile验证失败: 空响应值");
    $hasError = true;
} elseif (strlen($turnstileResponse) < 10) { // 简单长度验证
    error_log("Turnstile验证失败: 响应值长度不足");
    $hasError = true;
} elseif (!verifyTurnstile($turnstileResponse, $config['turnstile']['secret_key'])) {
    error_log("Turnstile验证失败: 验证未通过");
    $hasError = true;
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
                $fieldErrors['global'][] = $text['register_failed'];
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
        'filter' => 'Filter 扩展（输入验证）'
    ];

    $missingExtensions = [];
    foreach ($requiredExtensions as $ext => $desc) {
        if (!extension_loaded($ext)) {
            $missingExtensions[] = "- <strong>{$ext}</strong>：{$desc}";
        }
    }

    if (!empty($missingExtensions)) {
        //  implode the missing extensions first before the heredoc
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
