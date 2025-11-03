<?php
/**
 * 魔兽世界账号注册系统 - 配置文件
 */
return [
    // 数据库配置
    'db' => [
        'host' => 'localhost',
        'name' => 'acore_auth',
        'user' => 'root',
        'pass' => 'password',
        'charset' => 'utf8mb4'
    ],
    // 游戏版本 (0:经典, 1:TBC, 2:WLK)
    'expansion' => 2,
    // Cloudflare Turnstile验证配置
    'turnstile' => [
        'site_key' => '0x4AAAAAAB-FxL5eQ_9xEv9x', // 替换为你的站点密钥
        'secret_key' => '0x4AAAAAAB-FxMm4VPN_2QjSdRbl7Rf2AT4' // 替换为你的密钥
    ],
    // 服务器信息
    'realm_info' => [
        'website' => 'wow.example.com',
        'population' => '中',
        'type' => 'PVP',
        'version' => '3.3.5a (12340)', 
        'realmlist' => 'logon.example.com'
    ],
    // 客户端下载信息
    'downloads' => [
        'client' => [
            'name' => 'WLK 3.3.5a 客户端',
            'url' => 'https://download.example.com/client/wlk-335a.zip',
            'size' => '15.2 GB',
            'desc' => '完整客户端'
        ]
    ],
    // 语言设置
    'language' => [
        'default' => 'zh',
        'param' => 'lang',
        'available' => ['zh', 'en']
    ],
    // 多语言文本
    'text' => [
        'zh' => [
            'name' => '艾泽拉斯守护者', 
            'title' => '%s - 账号注册',
            'realm_type' => '服务器类型',
            'population' => '人口',
            'address' => '地址',
            'register' => '账号注册',
            'username' => '用户名',
            'username_hint' => '3-16位，含字母、数字和下划线',
            'username_available' => '用户名可用',
            'email' => '邮箱地址',
            'email_hint' => '请输入有效的邮箱地址',
            'email_available' => '邮箱可用',
            'password' => '密码',
            'password_length' => '至少8个字符',
            'password_char' => '包含特殊字符(!@#$%^&*)',
            'password_confirm' => '确认密码',
            'submit_btn' => '创建账号',
            'terms_agree' => '注册即即表示您同意我们的',
            'terms' => '服务条款',
            'privacy' => '和隐私政策',
            'server_info' => '服务器介绍',
            'client_download' => '客户端下载',
            'realmlist_config' => 'Realmlist配置',
            'realmlist_hint' => '客户端登录前需配置Realmlist文件：',
            'copy' => '复制',
            'copied' => '已复制',
            'download' => '下载',
            'success_title' => '注册成功！',
            'success_msg' => '您的账号已创建，请，请请返回客户端登录游戏。',
            'back_home' => '返回首页',
            'error_title' => '错误提示',
            'username_exists' => '该用户名已被注册',
            'username_invalid' => '用户名格式不正确',
            'email_exists' => '该邮箱已被注册',
            'email_invalid' => '请输入有效的邮箱',
            'password_invalid' => '密码需至少8位且包含特殊字符(!@#$%^&*)',
            'password_mismatch' => '两次次密码不一致',
            'captcha_empty' => '请完成验证',
            'captcha_invalid' => '验证未通过，请重试',
            'register_failed' => '注册失败: 系统错误，请稍后重试',
            'verify_human' => '请完成人机验证', 
            'required_empty' => '必填项', 
            'description' => '基于AzerothCore的魔兽世界3.3.5a私人服务器，提供完整的巫妖王之怒游戏内容与体验。', 
            'tips1' => '完整WLK 3.3.5a内容', 
            'tips2' => '稳定服务器性能',
            'tips3' => '定期更新维护',
            'tips4' => '公平游戏环境',
        ],
        'en' => [
            'name'=>'Guardian of Azeroth', 
            'title' => '%s - Account Registration',
            'realm_type' => 'Realm Type',
            'population' => 'Population',
            'address' => 'Address',
            'register' => 'Account Registration',
            'username' => 'Username',
            'username_hint' => '3-16 characters, letters, numbers and underscores',
            'username_available' => 'Username available',
            'email' => 'Email Address',
            'email_hint' => 'Please enter a valid email address',
            'email_available' => 'Email available',
            'password' => 'Password',
            'password_length' => 'At least 8 characters',
            'password_char' => 'Include special characters(!@#$%^&*)',
            'password_confirm' => 'Confirm Password',
            'submit_btn' => 'Create Account',
            'terms_agree' => 'By registering, you agree to our',
            'terms' => 'Terms of Service',
            'privacy' => 'and Privacy Policy',
            'server_info' => 'Server Information',
            'client_download' => 'Client Downloads',
            'realmlist_config' => 'Realmlist Configuration',
            'realmlist_hint' => 'Configure realmlist before logging in:',
            'copy' => 'Copy',
            'copied' => 'Copied',
            'download' => 'Download',
            'success_title' => 'Registration Successful!',
            'success_msg' => 'Your account has been created. Please return to the client to log in.',
            'back_home' => 'Back to Home',
            'error_title' => 'Error Message',
            'username_exists' => 'This username is already registered',
            'username_invalid' => 'Invalid username format',
            'email_exists' => 'This email is already registered',
            'email_invalid' => 'Please enter a valid email',
            'password_invalid' => 'Password must be at least 8 characters and include special characters(!@#$%^&*)',
            'password_mismatch' => 'Passwords do not match',
            'captcha_empty' => 'Please complete verification',
            'captcha_invalid' => 'Verification failed, please try again',
            'register_failed' => 'Registration failed: System error, please try again later',
            'verify_human' => 'Please complete human verification', 
            'required_empty' => 'required field', 
            'Description'=>'Based on Azeroth Core World of Warcraft 3.3.5a private server, it provides complete content and experience for the Wrath of the Lich King game. ', 
            'tips1' => 'Complete WLK 3.3.5a content ',
            'tips2' => 'Stable server performance ',
            'tips3' => 'Regular update and maintenance ',
            'tips4' => 'Fair gaming environment '
        ]
    ]
];