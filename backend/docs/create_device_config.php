<?php
// 创建设备跳转配置表和后台配置API
$pdo = new PDO("mysql:host=127.0.0.1;dbname=tllos_mall;charset=utf8mb4", "tllos", "TllosMall2026Secure");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// 检查system_configs表是否有设备跳转配置
echo "=== 检查现有配置 ===\n";
$stmt = $pdo->query("SELECT * FROM system_configs WHERE `group` = 'device_redirect' OR `key` LIKE '%device%' OR `key` LIKE '%redirect%' OR `key` LIKE '%home%'");
$configs = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($configs as $c) {
    echo "  {$c['key']} = {$c['value']}\n";
}

if (empty($configs)) {
    echo "  无设备跳转配置，开始创建...\n";
    
    // 插入默认配置
    $defaultConfigs = [
        ['group' => 'device_redirect', 'key' => 'pc_redirect_url', 'value' => '/pc/', 'name' => 'PC端跳转地址', 'description' => 'PC浏览器用户访问根目录时跳转的地址'],
        ['group' => 'device_redirect', 'key' => 'mobile_redirect_url', 'value' => '/pc/', 'name' => '手机端跳转地址', 'description' => '手机浏览器用户访问根目录时跳转的地址（H5端）'],
        ['group' => 'device_redirect', 'key' => 'tablet_redirect_url', 'value' => '/pc/', 'name' => '平板端跳转地址', 'description' => '平板浏览器用户访问根目录时跳转的地址'],
        ['group' => 'device_redirect', 'key' => 'auto_redirect_enabled', 'value' => '1', 'name' => '启用自动跳转', 'description' => '是否启用设备自动识别跳转功能'],
        ['group' => 'device_redirect', 'key' => 'default_redirect_url', 'value' => '/pc/', 'name' => '默认跳转地址', 'description' => '无法识别设备类型时的默认跳转地址'],
    ];
    
    foreach ($defaultConfigs as $config) {
        $stmt = $pdo->prepare("INSERT INTO system_configs (`group`, `key`, `value`, `name`, `description`, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())");
        $stmt->execute([$config['group'], $config['key'], $config['value'], $config['name'], $config['description']]);
        echo "  ✅ 创建配置: {$config['key']} = {$config['value']}\n";
    }
}

echo "\n=== 配置创建完成 ===\n";
echo "配置组: device_redirect\n";
echo "配置项: pc_redirect_url, mobile_redirect_url, tablet_redirect_url, auto_redirect_enabled, default_redirect_url\n";
