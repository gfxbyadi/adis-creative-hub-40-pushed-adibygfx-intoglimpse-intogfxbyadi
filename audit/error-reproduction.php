<?php
/**
 * ERROR REPRODUCTION TESTING
 * Systematically reproduces reported errors
 */

echo "🐛 ERROR REPRODUCTION TESTING\n";
echo "=============================\n\n";

class ErrorReproducer {
    private $results = [];
    private $base_path = '../public_html';
    
    public function reproduceReportedErrors() {
        $this->test500Errors();
        $this->testRequireOnceFailures();
        $this->testUndefinedMethodErrors();
        $this->testRoutingErrors();
        $this->generateErrorReport();
    }
    
    private function test500Errors() {
        echo "💥 500 ERROR REPRODUCTION\n";
        echo "-" . str_repeat("-", 30) . "\n";
        
        $potential_500_sources = [
            '/backend/api/index.php' => 'Main API router',
            '/backend/admin/index.php' => 'Admin panel',
            '/backend/get_projects.php' => 'Portfolio endpoint'
        ];
        
        foreach ($potential_500_sources as $file => $description) {
            echo "Testing: $file - $description\n";
            
            $full_path = $this->base_path . $file;
            
            if (!file_exists($full_path)) {
                echo "  ❌ File not found\n";
                $this->results['500_errors'][$file] = [
                    'exists' => false,
                    'error_type' => 'file_not_found',
                    'status' => 'FAIL'
                ];
                continue;
            }
            
            // Analyze file for potential 500 error causes
            $content = file_get_contents($full_path);
            $issues = [];
            
            // Check for syntax errors
            if (!$this->validatePhpSyntax($content)) {
                $issues[] = 'syntax_error';
            }
            
            // Check for missing includes
            if (preg_match_all('/require_once\s+[\'"]([^\'"]+)[\'"]/', $content, $matches)) {
                foreach ($matches[1] as $include_path) {
                    $resolved_path = dirname($full_path) . '/' . $include_path;
                    if (!file_exists($resolved_path)) {
                        $issues[] = "missing_include: $include_path";
                    }
                }
            }
            
            // Check for undefined variables
            if (strpos($content, '$method') !== false && strpos($content, '$method = ') === false) {
                $issues[] = 'undefined_method_variable';
            }
            
            // Check for database dependency without connection check
            if (strpos($content, 'new Database()') !== false && strpos($content, 'getConnection()') === false) {
                $issues[] = 'unchecked_database_connection';
            }
            
            echo "  Issues found: " . (empty($issues) ? '✅ NONE' : '⚠️ ' . count($issues)) . "\n";
            
            if (!empty($issues)) {
                foreach ($issues as $issue) {
                    echo "    • $issue\n";
                }
            }
            
            $this->results['500_errors'][$file] = [
                'exists' => true,
                'issues' => $issues,
                'issue_count' => count($issues),
                'status' => empty($issues) ? 'PASS' : 'POTENTIAL_500'
            ];
            
            echo "\n";
        }
    }
    
    private function testRequireOnceFailures() {
        echo "📁 REQUIRE_ONCE FAILURE TESTING\n";
        echo "-" . str_repeat("-", 30) . "\n";
        
        // Find all require_once statements and test them
        $php_files = $this->findPhpFiles($this->base_path);
        $require_failures = [];
        
        foreach ($php_files as $file) {
            $content = file_get_contents($file);
            $relative_path = str_replace($this->base_path, '', $file);
            
            if (preg_match_all('/require_once\s*\(?[\'"]([^\'"]+)[\'"]/', $content, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $include_path = $match[1];
                    
                    // Test different resolution contexts
                    $resolution_tests = [
                        'relative_to_file' => dirname($file) . '/' . $include_path,
                        'relative_to_root' => $this->base_path . '/' . $include_path,
                        'absolute_path' => $include_path
                    ];
                    
                    $resolved = false;
                    foreach ($resolution_tests as $context => $test_path) {
                        if (file_exists($test_path)) {
                            $resolved = true;
                            break;
                        }
                    }
                    
                    if (!$resolved) {
                        $require_failures[] = [
                            'file' => $relative_path,
                            'include_path' => $include_path,
                            'statement' => $match[0],
                            'resolution_attempts' => $resolution_tests
                        ];
                    }
                }
            }
        }
        
        echo "Total require_once statements analyzed: " . $this->countRequireStatements($php_files) . "\n";
        echo "Failed resolutions: " . count($require_failures) . "\n\n";
        
        foreach ($require_failures as $failure) {
            echo "❌ " . $failure['file'] . "\n";
            echo "   Statement: " . $failure['statement'] . "\n";
            echo "   Path: " . $failure['include_path'] . "\n";
            echo "   Tried resolving to:\n";
            foreach ($failure['resolution_attempts'] as $context => $path) {
                echo "     $context: $path " . (file_exists($path) ? '✅' : '❌') . "\n";
            }
            echo "\n";
        }
        
        $this->results['require_failures'] = [
            'total_statements' => $this->countRequireStatements($php_files),
            'failed_resolutions' => count($require_failures),
            'failures' => $require_failures,
            'status' => empty($require_failures) ? 'PASS' : 'FAIL'
        ];
    }
    
    private function testUndefinedMethodErrors() {
        echo "🔍 UNDEFINED METHOD VARIABLE TESTING\n";
        echo "-" . str_repeat("-", 30) . "\n";
        
        $method_errors = [];
        $php_files = $this->findPhpFiles($this->base_path);
        
        foreach ($php_files as $file) {
            $content = file_get_contents($file);
            $lines = explode("\n", $content);
            $relative_path = str_replace($this->base_path, '', $file);
            
            $method_defined = false;
            $method_used = false;
            
            foreach ($lines as $line_num => $line) {
                // Check if $method is defined
                if (preg_match('/\$method\s*=/', $line)) {
                    $method_defined = true;
                }
                
                // Check if $method is used
                if (preg_match('/\$method(?!\s*=)/', $line)) {
                    $method_used = true;
                    
                    if (!$method_defined) {
                        $method_errors[] = [
                            'file' => $relative_path,
                            'line' => $line_num + 1,
                            'context' => trim($line),
                            'issue' => 'method_used_before_definition'
                        ];
                    }
                }
            }
        }
        
        echo "Files using \$method: " . count(array_filter($php_files, function($file) {
            return strpos(file_get_contents($file), '$method') !== false;
        })) . "\n";
        
        echo "Undefined \$method errors: " . count($method_errors) . "\n\n";
        
        foreach ($method_errors as $error) {
            echo "❌ " . $error['file'] . ":" . $error['line'] . "\n";
            echo "   Context: " . $error['context'] . "\n";
            echo "   Issue: " . $error['issue'] . "\n\n";
        }
        
        $this->results['undefined_method'] = [
            'total_errors' => count($method_errors),
            'errors' => $method_errors,
            'status' => empty($method_errors) ? 'PASS' : 'FAIL'
        ];
    }
    
    private function testRoutingErrors() {
        echo "🛣️ ROUTING ERROR TESTING\n";
        echo "-" . str_repeat("-", 30) . "\n";
        
        // Test common routing scenarios that might fail
        $routing_scenarios = [
            '/backend/admin' => 'Admin panel access (should redirect to index.php)',
            '/backend/api/pages' => 'API endpoint routing',
            '/backend/config/config.php' => 'Config file access (should be blocked)'
        ];
        
        foreach ($routing_scenarios as $url => $description) {
            echo "Testing: $url - $description\n";
            
            $file_path = $this->base_path . $url;
            
            if (is_dir($file_path)) {
                $has_index = file_exists($file_path . '/index.php');
                echo "  Directory: ✅ EXISTS\n";
                echo "  Index file: " . ($has_index ? '✅ EXISTS' : '❌ MISSING') . "\n";
                
                $this->results['routing_errors'][$url] = [
                    'type' => 'directory',
                    'has_index' => $has_index,
                    'status' => $has_index ? 'PASS' : 'FAIL'
                ];
            } elseif (file_exists($file_path)) {
                echo "  File: ✅ EXISTS\n";
                $this->results['routing_errors'][$url] = [
                    'type' => 'file',
                    'exists' => true,
                    'status' => 'PASS'
                ];
            } else {
                echo "  ❌ NOT FOUND\n";
                $this->results['routing_errors'][$url] = [
                    'type' => 'missing',
                    'exists' => false,
                    'status' => 'FAIL'
                ];
            }
            echo "\n";
        }
    }
    
    private function findPhpFiles($directory) {
        $files = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));
        
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }
        
        return $files;
    }
    
    private function countRequireStatements($files) {
        $count = 0;
        foreach ($files as $file) {
            $content = file_get_contents($file);
            $count += preg_match_all('/(?:require|include)(?:_once)?\s*\(?[\'"]/', $content);
        }
        return $count;
    }
    
    private function validatePhpSyntax($content) {
        // Basic syntax validation
        $open_braces = substr_count($content, '{');
        $close_braces = substr_count($content, '}');
        
        return $open_braces === $close_braces;
    }
    
    private function generateErrorReport() {
        echo "📊 ERROR REPRODUCTION SUMMARY\n";
        echo "=" . str_repeat("=", 40) . "\n";
        
        $total_500_sources = count($this->results['500_errors'] ?? []);
        $clean_500_sources = count(array_filter($this->results['500_errors'] ?? [], fn($e) => $e['status'] === 'PASS'));
        
        $require_failures = $this->results['require_failures']['failed_resolutions'] ?? 0;
        $method_errors = $this->results['undefined_method']['total_errors'] ?? 0;
        
        echo "500 Error Sources: " . ($total_500_sources - $clean_500_sources) . "/$total_500_sources problematic\n";
        echo "Require_once Failures: $require_failures\n";
        echo "Undefined \$method Errors: $method_errors\n";
        
        $total_errors = ($total_500_sources - $clean_500_sources) + $require_failures + $method_errors;
        
        if ($total_errors === 0) {
            echo "\n🎯 ERROR STATUS: ✅ NO CRITICAL ERRORS FOUND\n";
        } elseif ($total_errors <= 5) {
            echo "\n⚠️ ERROR STATUS: 🟡 MINOR ISSUES FOUND\n";
        } else {
            echo "\n🚨 ERROR STATUS: ❌ CRITICAL ERRORS NEED FIXING\n";
        }
        
        // Save results
        file_put_contents('error-reproduction-results.json', json_encode($this->results, JSON_PRETTY_PRINT));
        echo "\n📄 Results saved to: error-reproduction-results.json\n";
    }
}

// Execute error reproduction tests
$reproducer = new ErrorReproducer();
$reproducer->reproduceReportedErrors();
?>