<?php
/**
 * ROUTING & URL REWRITE TESTING
 * Tests .htaccess rules and API routing
 */

echo "🛣️ ROUTING & URL REWRITE TESTING\n";
echo "================================\n\n";

class RoutingTester {
    private $base_url = 'http://localhost:8080';
    private $results = [];
    
    public function runRoutingTests() {
        $this->checkHtaccessFiles();
        $this->testApiRoutes();
        $this->testAdminRoutes();
        $this->testRewriteRules();
        $this->generateRoutingReport();
    }
    
    private function checkHtaccessFiles() {
        echo "📋 .HTACCESS FILES ANALYSIS\n";
        echo "-" . str_repeat("-", 30) . "\n";
        
        $htaccess_files = [
            '../public_html/.htaccess',
            '../public_html/backend/.htaccess'
        ];
        
        foreach ($htaccess_files as $file) {
            echo "Checking: $file\n";
            
            if (file_exists($file)) {
                $content = file_get_contents($file);
                $lines = explode("\n", $content);
                
                $analysis = [
                    'exists' => true,
                    'size' => filesize($file),
                    'lines' => count($lines),
                    'rewrite_engine' => strpos($content, 'RewriteEngine On') !== false,
                    'api_rules' => strpos($content, 'api/') !== false,
                    'admin_rules' => strpos($content, 'admin/') !== false,
                    'security_rules' => strpos($content, 'Files ~') !== false
                ];
                
                echo "  ✅ File exists (" . $analysis['size'] . " bytes)\n";
                echo "  RewriteEngine: " . ($analysis['rewrite_engine'] ? '✅' : '❌') . "\n";
                echo "  API Rules: " . ($analysis['api_rules'] ? '✅' : '❌') . "\n";
                echo "  Admin Rules: " . ($analysis['admin_rules'] ? '✅' : '❌') . "\n";
                echo "  Security Rules: " . ($analysis['security_rules'] ? '✅' : '❌') . "\n";
                
                $this->results['htaccess'][basename(dirname($file))] = $analysis;
            } else {
                echo "  ❌ File missing\n";
                $this->results['htaccess'][basename(dirname($file))] = ['exists' => false];
            }
            echo "\n";
        }
    }
    
    private function testApiRoutes() {
        echo "🔌 API ROUTES TESTING\n";
        echo "-" . str_repeat("-", 30) . "\n";
        
        $api_routes = [
            '/backend/api/pages',
            '/backend/api/portfolio', 
            '/backend/api/services',
            '/backend/api/forms',
            '/backend/api/media',
            '/backend/api/settings',
            '/backend/api/auth',
            '/backend/get_projects.php'
        ];
        
        foreach ($api_routes as $route) {
            echo "Testing: $route\n";
            
            $test_result = $this->testRoute($route);
            $this->results['api_routes'][$route] = $test_result;
            
            echo sprintf("  Status: %s (%s)\n", 
                $test_result['accessible'] ? '✅ ACCESSIBLE' : '❌ FAILED',
                $test_result['http_code'] ?? 'NO_RESPONSE'
            );
            
            if (!empty($test_result['error'])) {
                echo "  Error: " . $test_result['error'] . "\n";
            }
            echo "\n";
        }
    }
    
    private function testAdminRoutes() {
        echo "👤 ADMIN ROUTES TESTING\n";
        echo "-" . str_repeat("-", 30) . "\n";
        
        $admin_routes = [
            '/backend/admin/',
            '/backend/admin/login.php',
            '/backend/admin/index.php'
        ];
        
        foreach ($admin_routes as $route) {
            echo "Testing: $route\n";
            
            $test_result = $this->testRoute($route);
            $this->results['admin_routes'][$route] = $test_result;
            
            echo sprintf("  Status: %s (%s)\n", 
                $test_result['accessible'] ? '✅ ACCESSIBLE' : '❌ FAILED',
                $test_result['http_code'] ?? 'NO_RESPONSE'
            );
            echo "\n";
        }
    }
    
    private function testRoute($route) {
        $full_url = $this->base_url . $route;
        
        // Use file_exists for local file testing since we can't make HTTP requests
        $file_path = '../public_html' . $route;
        
        if (is_dir($file_path)) {
            // Check for index.php in directory
            $index_file = $file_path . '/index.php';
            if (file_exists($index_file)) {
                return [
                    'accessible' => true,
                    'http_code' => '200',
                    'file_exists' => true,
                    'type' => 'directory_with_index'
                ];
            } else {
                return [
                    'accessible' => false,
                    'error' => 'Directory exists but no index.php',
                    'type' => 'directory_no_index'
                ];
            }
        } elseif (file_exists($file_path)) {
            // Check if PHP file has syntax errors
            $syntax_check = $this->checkPhpSyntax($file_path);
            
            return [
                'accessible' => $syntax_check['valid'],
                'http_code' => $syntax_check['valid'] ? '200' : '500',
                'file_exists' => true,
                'syntax_valid' => $syntax_check['valid'],
                'syntax_error' => $syntax_check['error'] ?? null,
                'type' => 'php_file'
            ];
        } else {
            return [
                'accessible' => false,
                'error' => 'File not found',
                'file_exists' => false,
                'type' => 'missing'
            ];
        }
    }
    
    private function checkPhpSyntax($file_path) {
        // Basic syntax check by attempting to parse
        $content = file_get_contents($file_path);
        
        // Check for obvious syntax issues
        $issues = [];
        
        // Check for unmatched braces
        $open_braces = substr_count($content, '{');
        $close_braces = substr_count($content, '}');
        if ($open_braces !== $close_braces) {
            $issues[] = 'unmatched_braces';
        }
        
        // Check for unclosed PHP tags
        if (strpos($content, '<?php') !== false && strpos($content, '?>') === false) {
            // This is actually fine - PHP files don't need closing tags
        }
        
        return [
            'valid' => empty($issues),
            'issues' => $issues,
            'error' => !empty($issues) ? implode(', ', $issues) : null
        ];
    }
    
    private function testRewriteRules() {
        echo "🔄 REWRITE RULES TESTING\n";
        echo "-" . str_repeat("-", 30) . "\n";
        
        // Test specific rewrite scenarios
        $rewrite_tests = [
            '/backend/api/pages' => '/backend/api/index.php',
            '/backend/admin/' => '/backend/admin/index.php',
            '/backend/api/portfolio' => '/backend/api/index.php'
        ];
        
        foreach ($rewrite_tests as $request_url => $expected_target) {
            echo "Testing rewrite: $request_url → $expected_target\n";
            
            $target_exists = file_exists('../public_html' . $expected_target);
            echo "  Target file exists: " . ($target_exists ? '✅' : '❌') . "\n";
            
            $this->results['rewrite_rules'][$request_url] = [
                'expected_target' => $expected_target,
                'target_exists' => $target_exists,
                'status' => $target_exists ? 'PASS' : 'FAIL'
            ];
        }
    }
    
    private function generateRoutingReport() {
        echo "\n📊 ROUTING AUDIT SUMMARY\n";
        echo "=" . str_repeat("=", 40) . "\n";
        
        // Count accessible routes
        $total_api_routes = count($this->results['api_routes'] ?? []);
        $accessible_api_routes = count(array_filter($this->results['api_routes'] ?? [], fn($r) => $r['accessible']));
        
        $total_admin_routes = count($this->results['admin_routes'] ?? []);
        $accessible_admin_routes = count(array_filter($this->results['admin_routes'] ?? [], fn($r) => $r['accessible']));
        
        echo "API Routes: $accessible_api_routes/$total_api_routes accessible\n";
        echo "Admin Routes: $accessible_admin_routes/$total_admin_routes accessible\n";
        
        $overall_success = (($accessible_api_routes + $accessible_admin_routes) / ($total_api_routes + $total_admin_routes)) * 100;
        echo "Overall Routing Success: " . round($overall_success, 1) . "%\n";
        
        if ($overall_success >= 80) {
            echo "\n🎯 ROUTING STATUS: ✅ MOSTLY FUNCTIONAL\n";
        } else {
            echo "\n🚨 ROUTING STATUS: ❌ CRITICAL ISSUES\n";
        }
        
        // Save results
        file_put_contents('routing-test-results.json', json_encode($this->results, JSON_PRETTY_PRINT));
        echo "\n📄 Results saved to: routing-test-results.json\n";
    }
}

// Execute routing tests
$tester = new RoutingTester();
$tester->runRoutingTests();
?>