<?php
/**
 * STATIC CODE ANALYSIS
 * Analyzes PHP files for include/require path issues
 */

echo "🔍 STATIC CODE ANALYSIS\n";
echo "======================\n\n";

class StaticCodeAnalyzer {
    private $results = [];
    private $base_path = '../public_html';
    
    public function analyzeProject() {
        echo "📂 SCANNING PROJECT FILES\n";
        echo "-" . str_repeat("-", 30) . "\n";
        
        $this->scanDirectory($this->base_path);
        $this->analyzeIncludes();
        $this->checkPathResolution();
        $this->generateReport();
    }
    
    private function scanDirectory($dir, $relative_path = '') {
        $files = scandir($dir);
        
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;
            
            $full_path = $dir . '/' . $file;
            $rel_path = $relative_path . '/' . $file;
            
            if (is_dir($full_path)) {
                $this->scanDirectory($full_path, $rel_path);
            } elseif (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
                $this->analyzePhpFile($full_path, $rel_path);
            }
        }
    }
    
    private function analyzePhpFile($file_path, $relative_path) {
        if (!file_exists($file_path)) return;
        
        $content = file_get_contents($file_path);
        $lines = explode("\n", $content);
        
        $file_analysis = [
            'path' => $relative_path,
            'full_path' => $file_path,
            'size' => filesize($file_path),
            'includes' => [],
            'potential_issues' => [],
            'line_count' => count($lines)
        ];
        
        // Analyze each line for includes/requires
        foreach ($lines as $line_num => $line) {
            $line = trim($line);
            
            // Check for include/require statements
            if (preg_match('/(?:include|require)(?:_once)?\s*\(?[\'"]([^\'"]+)[\'"]/', $line, $matches)) {
                $include_path = $matches[1];
                $file_analysis['includes'][] = [
                    'line' => $line_num + 1,
                    'statement' => $line,
                    'path' => $include_path,
                    'type' => $this->getIncludeType($line),
                    'potential_issue' => $this->checkIncludePath($include_path, $file_path)
                ];
            }
            
            // Check for undefined variable usage
            if (preg_match('/\$method/', $line) && !preg_match('/\$method\s*=/', $line)) {
                $file_analysis['potential_issues'][] = [
                    'line' => $line_num + 1,
                    'type' => 'undefined_variable',
                    'variable' => '$method',
                    'context' => $line
                ];
            }
        }
        
        $this->results['files'][] = $file_analysis;
        
        echo sprintf("📄 %-50s: %d lines, %d includes\n", 
            substr($relative_path, 0, 50), 
            count($lines), 
            count($file_analysis['includes'])
        );
    }
    
    private function getIncludeType($line) {
        if (strpos($line, 'require_once') !== false) return 'require_once';
        if (strpos($line, 'require') !== false) return 'require';
        if (strpos($line, 'include_once') !== false) return 'include_once';
        if (strpos($line, 'include') !== false) return 'include';
        return 'unknown';
    }
    
    private function checkIncludePath($include_path, $current_file) {
        $issues = [];
        
        // Check for relative path issues
        if (strpos($include_path, '../') === 0) {
            $issues[] = 'relative_path_traversal';
        }
        
        // Check for missing __DIR__ usage
        if (!strpos($include_path, '__DIR__') && strpos($include_path, '/') !== 0) {
            $issues[] = 'missing_dir_constant';
        }
        
        // Check if path exists relative to current file
        $resolved_path = dirname($current_file) . '/' . $include_path;
        if (!file_exists($resolved_path)) {
            $issues[] = 'file_not_found';
        }
        
        return $issues;
    }
    
    private function analyzeIncludes() {
        echo "\n🔗 INCLUDE/REQUIRE ANALYSIS\n";
        echo "-" . str_repeat("-", 30) . "\n";
        
        $total_includes = 0;
        $problematic_includes = 0;
        
        foreach ($this->results['files'] as $file) {
            foreach ($file['includes'] as $include) {
                $total_includes++;
                
                if (!empty($include['potential_issue'])) {
                    $problematic_includes++;
                    echo sprintf("⚠️  %s:%d - %s\n", 
                        $file['path'], 
                        $include['line'], 
                        implode(', ', $include['potential_issue'])
                    );
                    echo "    Statement: " . $include['statement'] . "\n";
                    echo "    Path: " . $include['path'] . "\n\n";
                }
            }
        }
        
        echo "Total includes found: $total_includes\n";
        echo "Problematic includes: $problematic_includes\n";
        
        $this->results['include_analysis'] = [
            'total_includes' => $total_includes,
            'problematic_includes' => $problematic_includes,
            'success_rate' => $total_includes > 0 ? round((($total_includes - $problematic_includes) / $total_includes) * 100, 1) : 100
        ];
    }
    
    private function checkPathResolution() {
        echo "\n🛣️ PATH RESOLUTION ANALYSIS\n";
        echo "-" . str_repeat("-", 30) . "\n";
        
        $path_issues = [];
        
        // Check common problematic patterns
        $problematic_patterns = [
            'config/config.php' => 'Should use __DIR__ for config files',
            'classes/' => 'Should use absolute paths for class includes',
            '../' => 'Relative path traversal can fail in different contexts'
        ];
        
        foreach ($this->results['files'] as $file) {
            foreach ($file['includes'] as $include) {
                foreach ($problematic_patterns as $pattern => $issue) {
                    if (strpos($include['path'], $pattern) !== false) {
                        $path_issues[] = [
                            'file' => $file['path'],
                            'line' => $include['line'],
                            'path' => $include['path'],
                            'issue' => $issue,
                            'severity' => $this->getIssueSeverity($pattern)
                        ];
                    }
                }
            }
        }
        
        foreach ($path_issues as $issue) {
            echo sprintf("%s %s:%d\n", 
                $issue['severity'] === 'high' ? '🚨' : '⚠️',
                $issue['file'],
                $issue['line']
            );
            echo "    Path: " . $issue['path'] . "\n";
            echo "    Issue: " . $issue['issue'] . "\n\n";
        }
        
        $this->results['path_resolution'] = [
            'total_issues' => count($path_issues),
            'high_severity' => count(array_filter($path_issues, fn($i) => $i['severity'] === 'high')),
            'medium_severity' => count(array_filter($path_issues, fn($i) => $i['severity'] === 'medium')),
            'issues' => $path_issues
        ];
    }
    
    private function getIssueSeverity($pattern) {
        $high_severity = ['config/config.php', 'classes/'];
        return in_array($pattern, $high_severity) ? 'high' : 'medium';
    }
    
    private function generateReport() {
        echo "\n📊 STATIC ANALYSIS SUMMARY\n";
        echo "=" . str_repeat("=", 40) . "\n";
        
        $total_files = count($this->results['files']);
        $files_with_issues = 0;
        
        foreach ($this->results['files'] as $file) {
            if (!empty($file['potential_issues']) || 
                array_filter($file['includes'], fn($inc) => !empty($inc['potential_issue']))) {
                $files_with_issues++;
            }
        }
        
        echo "Total PHP files analyzed: $total_files\n";
        echo "Files with potential issues: $files_with_issues\n";
        echo "Include statements analyzed: " . $this->results['include_analysis']['total_includes'] . "\n";
        echo "Problematic includes: " . $this->results['include_analysis']['problematic_includes'] . "\n";
        echo "Path resolution issues: " . $this->results['path_resolution']['total_issues'] . "\n";
        
        $overall_status = ($files_with_issues / $total_files) < 0.2 ? 'GOOD' : 'NEEDS_ATTENTION';
        echo "\nOverall Code Quality: $overall_status\n";
        
        // Save detailed results
        file_put_contents('static-analysis-results.json', json_encode($this->results, JSON_PRETTY_PRINT));
        echo "\n📄 Detailed results saved to: static-analysis-results.json\n";
    }
}

// Execute static code analysis
$analyzer = new StaticCodeAnalyzer();
$analyzer->analyzeProject();
?>