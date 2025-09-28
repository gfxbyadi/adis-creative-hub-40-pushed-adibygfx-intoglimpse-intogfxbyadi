<?php
/**
 * SECURITY & PERMISSIONS AUDIT
 * Tests file access controls and security configurations
 */

echo "🛡️ SECURITY & PERMISSIONS AUDIT\n";
echo "===============================\n\n";

class SecurityAuditor {
    private $base_path = '../public_html';
    private $results = [];
    
    public function runSecurityAudit() {
        $this->testConfigFileAccess();
        $this->testDirectoryPermissions();
        $this->testUploadSecurity();
        $this->analyzeHtaccessSecurity();
        $this->checkSensitiveFileExposure();
        $this->generateSecurityReport();
    }
    
    private function testConfigFileAccess() {
        echo "🔒 CONFIG FILE ACCESS TESTING\n";
        echo "-" . str_repeat("-", 30) . "\n";
        
        $sensitive_files = [
            '/backend/config/config.php' => 'Main configuration',
            '/backend/config/database.php' => 'Database configuration',
            '/backend/classes/Auth.php' => 'Authentication class',
            '/backend/composer.json' => 'Composer dependencies',
            '/backend/composer.lock' => 'Composer lock file'
        ];
        
        foreach ($sensitive_files as $file => $description) {
            $full_path = $this->base_path . $file;
            echo "Testing: $file - $description\n";
            
            if (file_exists($full_path)) {
                // Check if file is readable (should be protected by .htaccess)
                $readable = is_readable($full_path);
                $size = filesize($full_path);
                
                // Check for .htaccess protection
                $htaccess_path = dirname($full_path) . '/.htaccess';
                $protected = file_exists($htaccess_path);
                
                echo "  File exists: ✅ ($size bytes)\n";
                echo "  Readable: " . ($readable ? '✅' : '❌') . "\n";
                echo "  .htaccess protection: " . ($protected ? '✅' : '❌') . "\n";
                
                $this->results['config_access'][$file] = [
                    'exists' => true,
                    'size' => $size,
                    'readable' => $readable,
                    'protected' => $protected,
                    'security_status' => $protected ? 'SECURE' : 'VULNERABLE'
                ];
            } else {
                echo "  ❌ File not found\n";
                $this->results['config_access'][$file] = [
                    'exists' => false,
                    'status' => 'MISSING'
                ];
            }
            echo "\n";
        }
    }
    
    private function testDirectoryPermissions() {
        echo "📁 DIRECTORY PERMISSIONS CHECK\n";
        echo "-" . str_repeat("-", 30) . "\n";
        
        $directories = [
            '/backend/uploads' => ['required_perms' => '755', 'should_be_writable' => true],
            '/backend/exports' => ['required_perms' => '755', 'should_be_writable' => true],
            '/backend/admin/logs' => ['required_perms' => '755', 'should_be_writable' => true],
            '/backend/config' => ['required_perms' => '755', 'should_be_writable' => false],
            '/backend/classes' => ['required_perms' => '755', 'should_be_writable' => false]
        ];
        
        foreach ($directories as $dir => $requirements) {
            $full_path = $this->base_path . $dir;
            echo "Checking: $dir\n";
            
            if (file_exists($full_path)) {
                $perms = fileperms($full_path);
                $octal_perms = substr(sprintf('%o', $perms), -3);
                $readable = is_readable($full_path);
                $writable = is_writable($full_path);
                
                echo "  Permissions: $octal_perms (required: " . $requirements['required_perms'] . ")\n";
                echo "  Readable: " . ($readable ? '✅' : '❌') . "\n";
                echo "  Writable: " . ($writable ? '✅' : '❌') . "\n";
                
                $correct_perms = $octal_perms === $requirements['required_perms'];
                $correct_writable = $writable === $requirements['should_be_writable'];
                
                $this->results['directory_permissions'][$dir] = [
                    'exists' => true,
                    'current_perms' => $octal_perms,
                    'required_perms' => $requirements['required_perms'],
                    'readable' => $readable,
                    'writable' => $writable,
                    'correct_permissions' => $correct_perms,
                    'correct_writable' => $correct_writable,
                    'status' => ($correct_perms && $correct_writable) ? 'PASS' : 'FAIL'
                ];
            } else {
                echo "  ❌ Directory not found\n";
                $this->results['directory_permissions'][$dir] = [
                    'exists' => false,
                    'status' => 'MISSING'
                ];
            }
            echo "\n";
        }
    }
    
    private function testUploadSecurity() {
        echo "📤 UPLOAD SECURITY CHECK\n";
        echo "-" . str_repeat("-", 30) . "\n";
        
        $upload_dir = $this->base_path . '/backend/uploads';
        
        if (file_exists($upload_dir)) {
            // Check for .htaccess in uploads directory
            $upload_htaccess = $upload_dir . '/.htaccess';
            $has_htaccess = file_exists($upload_htaccess);
            
            echo "Upload directory: ✅ EXISTS\n";
            echo "Upload .htaccess: " . ($has_htaccess ? '✅' : '❌') . "\n";
            
            if ($has_htaccess) {
                $htaccess_content = file_get_contents($upload_htaccess);
                $blocks_php = strpos($htaccess_content, 'php') !== false;
                echo "Blocks PHP execution: " . ($blocks_php ? '✅' : '❌') . "\n";
            }
            
            // Check for any existing uploaded files
            $files = glob($upload_dir . '/*');
            echo "Uploaded files count: " . count($files) . "\n";
            
            $this->results['upload_security'] = [
                'directory_exists' => true,
                'has_htaccess' => $has_htaccess,
                'blocks_php_execution' => $has_htaccess && isset($blocks_php) ? $blocks_php : false,
                'file_count' => count($files),
                'status' => $has_htaccess ? 'SECURE' : 'VULNERABLE'
            ];
        } else {
            echo "❌ Upload directory not found\n";
            $this->results['upload_security'] = [
                'directory_exists' => false,
                'status' => 'MISSING'
            ];
        }
    }
    
    private function analyzeHtaccessSecurity() {
        echo "\n🔐 .HTACCESS SECURITY ANALYSIS\n";
        echo "-" . str_repeat("-", 30) . "\n";
        
        $htaccess_files = [
            '/backend/.htaccess' => 'Backend security rules',
            '/.htaccess' => 'Root security rules'
        ];
        
        foreach ($htaccess_files as $file => $description) {
            $full_path = $this->base_path . $file;
            echo "Analyzing: $file - $description\n";
            
            if (file_exists($full_path)) {
                $content = file_get_contents($full_path);
                
                $security_features = [
                    'blocks_sensitive_files' => strpos($content, 'Files ~') !== false,
                    'has_security_headers' => strpos($content, 'X-Content-Type-Options') !== false,
                    'blocks_sql_files' => strpos($content, '\.sql$') !== false,
                    'blocks_log_files' => strpos($content, '\.log$') !== false,
                    'has_compression' => strpos($content, 'mod_deflate') !== false,
                    'has_caching' => strpos($content, 'mod_expires') !== false
                ];
                
                foreach ($security_features as $feature => $present) {
                    echo sprintf("    %-25s: %s\n", ucwords(str_replace('_', ' ', $feature)), $present ? '✅' : '❌');
                }
                
                $this->results['htaccess_security'][$file] = $security_features;
            } else {
                echo "  ❌ File not found\n";
                $this->results['htaccess_security'][$file] = ['exists' => false];
            }
            echo "\n";
        }
    }
    
    private function checkSensitiveFileExposure() {
        echo "🕵️ SENSITIVE FILE EXPOSURE CHECK\n";
        echo "-" . str_repeat("-", 30) . "\n";
        
        $sensitive_patterns = [
            '*.sql' => 'SQL files',
            '*.log' => 'Log files', 
            'composer.*' => 'Composer files',
            'config.php' => 'Configuration files',
            '.env*' => 'Environment files'
        ];
        
        foreach ($sensitive_patterns as $pattern => $description) {
            echo "Scanning for: $pattern - $description\n";
            
            $found_files = $this->findFilesByPattern($this->base_path, $pattern);
            
            echo "  Found " . count($found_files) . " files\n";
            
            foreach ($found_files as $file) {
                $relative_path = str_replace($this->base_path, '', $file);
                echo "    📄 $relative_path\n";
                
                // Check if file is in a protected directory
                $in_backend = strpos($relative_path, '/backend/') !== false;
                $has_htaccess = file_exists(dirname($file) . '/.htaccess');
                
                echo "      Protected: " . ($in_backend && $has_htaccess ? '✅' : '❌') . "\n";
            }
            
            $this->results['sensitive_files'][$pattern] = [
                'count' => count($found_files),
                'files' => array_map(fn($f) => str_replace($this->base_path, '', $f), $found_files),
                'all_protected' => count($found_files) === 0 || $this->allFilesProtected($found_files)
            ];
            echo "\n";
        }
    }
    
    private function findFilesByPattern($directory, $pattern) {
        $files = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));
        
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $filename = $file->getFilename();
                
                // Convert pattern to regex
                $regex_pattern = str_replace(['*', '.'], ['.*', '\.'], $pattern);
                
                if (preg_match("/^$regex_pattern$/", $filename)) {
                    $files[] = $file->getPathname();
                }
            }
        }
        
        return $files;
    }
    
    private function allFilesProtected($files) {
        foreach ($files as $file) {
            $dir = dirname($file);
            if (!file_exists($dir . '/.htaccess')) {
                return false;
            }
        }
        return true;
    }
    
    private function generateSecurityReport() {
        echo "📊 SECURITY AUDIT SUMMARY\n";
        echo "=" . str_repeat("=", 40) . "\n";
        
        $total_checks = 0;
        $passed_checks = 0;
        $vulnerabilities = [];
        
        // Count results
        foreach ($this->results as $category => $items) {
            if (is_array($items)) {
                foreach ($items as $item => $data) {
                    if (isset($data['status'])) {
                        $total_checks++;
                        if (in_array($data['status'], ['PASS', 'SECURE'])) {
                            $passed_checks++;
                        } else {
                            $vulnerabilities[] = "$category: $item";
                        }
                    }
                }
            }
        }
        
        $security_score = $total_checks > 0 ? round(($passed_checks / $total_checks) * 100, 1) : 0;
        
        echo "Total Security Checks: $total_checks\n";
        echo "Passed: $passed_checks\n";
        echo "Failed: " . ($total_checks - $passed_checks) . "\n";
        echo "Security Score: $security_score%\n";
        
        if (!empty($vulnerabilities)) {
            echo "\n🚨 VULNERABILITIES FOUND:\n";
            foreach ($vulnerabilities as $vuln) {
                echo "  • $vuln\n";
            }
        }
        
        if ($security_score >= 90) {
            echo "\n🎯 SECURITY STATUS: ✅ EXCELLENT\n";
        } elseif ($security_score >= 70) {
            echo "\n⚠️ SECURITY STATUS: 🟡 GOOD WITH IMPROVEMENTS NEEDED\n";
        } else {
            echo "\n🚨 SECURITY STATUS: ❌ CRITICAL VULNERABILITIES\n";
        }
        
        // Save results
        file_put_contents('security-audit-results.json', json_encode($this->results, JSON_PRETTY_PRINT));
        echo "\n📄 Results saved to: security-audit-results.json\n";
    }
}

// Execute security audit
$auditor = new SecurityAuditor();
$auditor->runSecurityAudit();
?>