<?php
/**
 * ENVIRONMENT & CONFIGURATION VERIFICATION
 * Checks PHP version, modules, and database connectivity
 */

echo "🔍 ENVIRONMENT & CONFIGURATION VERIFICATION\n";
echo "==========================================\n\n";

$audit_results = [];

// 1. PHP Environment Check
echo "📋 PHP ENVIRONMENT CHECK\n";
echo "-" . str_repeat("-", 30) . "\n";

$php_version = PHP_VERSION;
$php_major = PHP_MAJOR_VERSION;
$php_minor = PHP_MINOR_VERSION;

echo "PHP Version: $php_version\n";
echo "PHP Major: $php_major\n";
echo "PHP Minor: $php_minor\n";

$audit_results['php_environment'] = [
    'version' => $php_version,
    'major' => $php_major,
    'minor' => $php_minor,
    'compatible' => $php_major >= 7 && $php_minor >= 4,
    'status' => ($php_major >= 7 && $php_minor >= 4) ? 'PASS' : 'FAIL'
];

// 2. Required PHP Extensions
echo "\n📦 PHP EXTENSIONS CHECK\n";
echo "-" . str_repeat("-", 30) . "\n";

$required_extensions = [
    'pdo' => 'PDO Database Abstraction',
    'pdo_mysql' => 'MySQL PDO Driver',
    'json' => 'JSON Processing',
    'mbstring' => 'Multibyte String',
    'openssl' => 'OpenSSL Encryption',
    'curl' => 'cURL HTTP Client',
    'gd' => 'GD Image Processing',
    'fileinfo' => 'File Information'
];

$extensions_status = [];
foreach ($required_extensions as $ext => $description) {
    $loaded = extension_loaded($ext);
    echo sprintf("%-15s: %s - %s\n", $ext, $loaded ? '✅ LOADED' : '❌ MISSING', $description);
    $extensions_status[$ext] = $loaded;
}

$audit_results['php_extensions'] = $extensions_status;

// 3. Database Configuration Check
echo "\n🗄️ DATABASE CONFIGURATION CHECK\n";
echo "-" . str_repeat("-", 30) . "\n";

$config_files = [
    '../public_html/backend/config/config.php',
    '../public_html/backend/config/database.php',
    '../public_html/config/database.php'
];

$config_found = false;
$config_path = '';

foreach ($config_files as $file) {
    if (file_exists($file)) {
        $config_found = true;
        $config_path = $file;
        echo "✅ Config found: $file\n";
        break;
    } else {
        echo "❌ Config missing: $file\n";
    }
}

$audit_results['database_config'] = [
    'config_found' => $config_found,
    'config_path' => $config_path,
    'status' => $config_found ? 'PASS' : 'FAIL'
];

// 4. Database Connection Test
if ($config_found) {
    echo "\n🔌 DATABASE CONNECTION TEST\n";
    echo "-" . str_repeat("-", 30) . "\n";
    
    try {
        // Attempt to include config and test connection
        ob_start();
        include $config_path;
        $config_output = ob_get_clean();
        
        // Test if Database class exists
        if (class_exists('Database')) {
            $database = new Database();
            $connection = $database->getConnection();
            
            if ($connection) {
                echo "✅ Database connection successful\n";
                
                // Test basic query
                $stmt = $connection->query("SELECT 1 as test");
                $result = $stmt->fetch();
                echo "✅ Database query test: " . ($result['test'] == 1 ? 'PASS' : 'FAIL') . "\n";
                
                $audit_results['database_connection'] = [
                    'connection_status' => 'SUCCESS',
                    'query_test' => $result['test'] == 1,
                    'status' => 'PASS'
                ];
            } else {
                echo "❌ Database connection failed\n";
                $audit_results['database_connection'] = [
                    'connection_status' => 'FAILED',
                    'error' => 'Connection returned null',
                    'status' => 'FAIL'
                ];
            }
        } else {
            echo "❌ Database class not found in config\n";
            $audit_results['database_connection'] = [
                'connection_status' => 'FAILED',
                'error' => 'Database class not defined',
                'status' => 'FAIL'
            ];
        }
    } catch (Exception $e) {
        echo "❌ Database connection error: " . $e->getMessage() . "\n";
        $audit_results['database_connection'] = [
            'connection_status' => 'FAILED',
            'error' => $e->getMessage(),
            'status' => 'FAIL'
        ];
    }
}

// 5. File Permissions Check
echo "\n📁 FILE PERMISSIONS CHECK\n";
echo "-" . str_repeat("-", 30) . "\n";

$directories_to_check = [
    '../public_html/backend/uploads',
    '../public_html/backend/exports',
    '../public_html/backend/admin/logs'
];

$permissions_status = [];
foreach ($directories_to_check as $dir) {
    if (file_exists($dir)) {
        $perms = fileperms($dir);
        $readable = is_readable($dir);
        $writable = is_writable($dir);
        
        echo sprintf("%-30s: %s (R:%s W:%s)\n", 
            basename($dir), 
            substr(sprintf('%o', $perms), -4),
            $readable ? '✅' : '❌',
            $writable ? '✅' : '❌'
        );
        
        $permissions_status[$dir] = [
            'exists' => true,
            'permissions' => substr(sprintf('%o', $perms), -4),
            'readable' => $readable,
            'writable' => $writable,
            'status' => ($readable && $writable) ? 'PASS' : 'FAIL'
        ];
    } else {
        echo sprintf("%-30s: ❌ MISSING\n", basename($dir));
        $permissions_status[$dir] = [
            'exists' => false,
            'status' => 'FAIL'
        ];
    }
}

$audit_results['file_permissions'] = $permissions_status;

// 6. Generate Environment Report
echo "\n📊 ENVIRONMENT AUDIT SUMMARY\n";
echo "=" . str_repeat("=", 40) . "\n";

$total_checks = 0;
$passed_checks = 0;

foreach ($audit_results as $category => $results) {
    if (isset($results['status'])) {
        $total_checks++;
        if ($results['status'] === 'PASS') $passed_checks++;
    } else {
        foreach ($results as $item => $data) {
            if (isset($data['status'])) {
                $total_checks++;
                if ($data['status'] === 'PASS') $passed_checks++;
            }
        }
    }
}

$success_rate = $total_checks > 0 ? round(($passed_checks / $total_checks) * 100, 1) : 0;

echo "Total Checks: $total_checks\n";
echo "Passed: $passed_checks\n";
echo "Failed: " . ($total_checks - $passed_checks) . "\n";
echo "Success Rate: $success_rate%\n\n";

if ($success_rate >= 80) {
    echo "🎯 ENVIRONMENT STATUS: ✅ READY FOR TESTING\n";
} else {
    echo "🚨 ENVIRONMENT STATUS: ❌ CRITICAL ISSUES FOUND\n";
}

// Save results to JSON
file_put_contents('environment-audit-results.json', json_encode($audit_results, JSON_PRETTY_PRINT));
echo "\n📄 Results saved to: environment-audit-results.json\n";
?>