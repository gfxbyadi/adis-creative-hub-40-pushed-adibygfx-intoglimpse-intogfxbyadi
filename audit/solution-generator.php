<?php
/**
 * SOLUTION DEVELOPMENT
 * Generates fixes for identified issues
 */

echo "🔧 SOLUTION DEVELOPMENT\n";
echo "======================\n\n";

class SolutionGenerator {
    private $fixes = [];
    
    public function generateSolutions() {
        $this->loadAuditResults();
        $this->generatePathFixes();
        $this->generateMethodVariableFixes();
        $this->generateConfigurationFixes();
        $this->generateSecurityFixes();
        $this->prioritizeFixes();
        $this->outputSolutions();
    }
    
    private function loadAuditResults() {
        echo "📊 LOADING AUDIT RESULTS\n";
        echo "-" . str_repeat("-", 30) . "\n";
        
        $result_files = [
            'static-analysis-results.json',
            'routing-test-results.json',
            'error-reproduction-results.json',
            'security-audit-results.json'
        ];
        
        $loaded_results = 0;
        foreach ($result_files as $file) {
            if (file_exists($file)) {
                $data = json_decode(file_get_contents($file), true);
                $this->analyzeResultsForFixes($file, $data);
                $loaded_results++;
                echo "✅ Loaded: $file\n";
            } else {
                echo "⚠️ Missing: $file\n";
            }
        }
        
        echo "Loaded $loaded_results audit result files\n\n";
    }
    
    private function analyzeResultsForFixes($source_file, $data) {
        // Analyze each result file for fixable issues
        switch ($source_file) {
            case 'static-analysis-results.json':
                $this->extractPathIssues($data);
                break;
            case 'error-reproduction-results.json':
                $this->extractErrorFixes($data);
                break;
            case 'security-audit-results.json':
                $this->extractSecurityFixes($data);
                break;
        }
    }
    
    private function extractPathIssues($data) {
        if (isset($data['files'])) {
            foreach ($data['files'] as $file) {
                foreach ($file['includes'] ?? [] as $include) {
                    if (!empty($include['potential_issue'])) {
                        $this->fixes[] = [
                            'type' => 'path_fix',
                            'priority' => 'high',
                            'file' => $file['path'],
                            'line' => $include['line'],
                            'issue' => $include['potential_issue'],
                            'current_path' => $include['path'],
                            'suggested_fix' => $this->generatePathFix($include['path'], $file['path'])
                        ];
                    }
                }
            }
        }
    }
    
    private function extractErrorFixes($data) {
        // Extract fixes for undefined $method errors
        if (isset($data['undefined_method']['errors'])) {
            foreach ($data['undefined_method']['errors'] as $error) {
                $this->fixes[] = [
                    'type' => 'undefined_variable',
                    'priority' => 'high',
                    'file' => $error['file'],
                    'line' => $error['line'],
                    'variable' => '$method',
                    'suggested_fix' => '$method = $_SERVER[\'REQUEST_METHOD\'];'
                ];
            }
        }
        
        // Extract fixes for require_once failures
        if (isset($data['require_failures']['failures'])) {
            foreach ($data['require_failures']['failures'] as $failure) {
                $this->fixes[] = [
                    'type' => 'require_fix',
                    'priority' => 'high',
                    'file' => $failure['file'],
                    'current_path' => $failure['include_path'],
                    'suggested_fix' => $this->generateRequireFix($failure['include_path'])
                ];
            }
        }
    }
    
    private function extractSecurityFixes($data) {
        // Extract security-related fixes
        if (isset($data['config_access'])) {
            foreach ($data['config_access'] as $file => $info) {
                if (isset($info['security_status']) && $info['security_status'] === 'VULNERABLE') {
                    $this->fixes[] = [
                        'type' => 'security_fix',
                        'priority' => 'medium',
                        'file' => $file,
                        'issue' => 'unprotected_config_file',
                        'suggested_fix' => 'Add .htaccess protection to directory'
                    ];
                }
            }
        }
    }
    
    private function generatePathFix($current_path, $file_path) {
        // Generate proper path fix using __DIR__
        if (strpos($current_path, 'config/') !== false) {
            return "__DIR__ . '/../config/" . basename($current_path) . "'";
        }
        
        if (strpos($current_path, 'classes/') !== false) {
            return "__DIR__ . '/../classes/" . basename($current_path) . "'";
        }
        
        return "__DIR__ . '/' . $current_path";
    }
    
    private function generateRequireFix($include_path) {
        // Generate proper require_once statement
        if (strpos($include_path, 'config') !== false) {
            return "require_once __DIR__ . '/../config/" . basename($include_path) . "';";
        }
        
        if (strpos($include_path, 'classes') !== false) {
            return "require_once __DIR__ . '/../classes/" . basename($include_path) . "';";
        }
        
        return "require_once __DIR__ . '/' . $include_path;";
    }
    
    private function generatePathFixes() {
        echo "🛣️ GENERATING PATH FIXES\n";
        echo "-" . str_repeat("-", 30) . "\n";
        
        $path_fixes = array_filter($this->fixes, fn($f) => $f['type'] === 'path_fix');
        
        echo "Path fixes needed: " . count($path_fixes) . "\n";
        
        foreach ($path_fixes as $fix) {
            echo "📄 " . $fix['file'] . ":" . $fix['line'] . "\n";
            echo "   Current: " . $fix['current_path'] . "\n";
            echo "   Fix: " . $fix['suggested_fix'] . "\n\n";
        }
    }
    
    private function generateMethodVariableFixes() {
        echo "🔍 GENERATING METHOD VARIABLE FIXES\n";
        echo "-" . str_repeat("-", 30) . "\n";
        
        $method_fixes = array_filter($this->fixes, fn($f) => $f['type'] === 'undefined_variable');
        
        echo "Method variable fixes needed: " . count($method_fixes) . "\n";
        
        foreach ($method_fixes as $fix) {
            echo "📄 " . $fix['file'] . ":" . $fix['line'] . "\n";
            echo "   Add: " . $fix['suggested_fix'] . "\n\n";
        }
    }
    
    private function generateConfigurationFixes() {
        echo "⚙️ GENERATING CONFIGURATION FIXES\n";
        echo "-" . str_repeat("-", 30) . "\n";
        
        // Generate database configuration fix
        $config_fix = [
            'type' => 'config_fix',
            'priority' => 'critical',
            'file' => '/backend/config/database.php',
            'issue' => 'database_connection_configuration',
            'fix_code' => $this->generateDatabaseConfigFix()
        ];
        
        $this->fixes[] = $config_fix;
        
        echo "📄 Database Configuration Fix:\n";
        echo $config_fix['fix_code'] . "\n\n";
    }
    
    private function generateDatabaseConfigFix() {
        return '<?php
/**
 * FIXED Database Configuration
 * Proper error handling and connection management
 */

class Database {
    private $host = \'localhost\';
    private $db_name = \'u720615217_portfolio_db\';
    private $username = \'u720615217_portfolio\';
    private $password = \'your_password_here\';
    private $conn;

    public function getConnection() {
        if ($this->conn !== null) {
            return $this->conn;
        }
        
        try {
            $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4";
            $this->conn = new PDO(
                $dsn,
                $this->username,
                $this->password,
                array(
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                )
            );
            
            return $this->conn;
            
        } catch(PDOException $e) {
            error_log("Database connection error: " . $e->getMessage());
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
    }
    
    public function testConnection() {
        try {
            $conn = $this->getConnection();
            $stmt = $conn->query("SELECT 1");
            return $stmt !== false;
        } catch (Exception $e) {
            return false;
        }
    }
}

// Global database instance
try {
    $database = new Database();
    $db_connection = $database->getConnection();
} catch (Exception $e) {
    error_log("Failed to initialize database: " . $e->getMessage());
    $db_connection = null;
}
?>';
    }
    
    private function generateSecurityFixes() {
        echo "🛡️ GENERATING SECURITY FIXES\n";
        echo "-" . str_repeat("-", 30) . "\n";
        
        // Generate .htaccess security fix
        $htaccess_fix = [
            'type' => 'security_fix',
            'priority' => 'high',
            'file' => '/backend/.htaccess',
            'issue' => 'enhanced_security_rules',
            'fix_code' => $this->generateHtaccessSecurityFix()
        ];
        
        $this->fixes[] = $htaccess_fix;
        
        echo "📄 Enhanced .htaccess Security Rules Generated\n\n";
    }
    
    private function generateHtaccessSecurityFix() {
        return '# ENHANCED SECURITY CONFIGURATION
# Blocks access to sensitive files and directories

# Security Headers
Header always set X-Content-Type-Options nosniff
Header always set X-Frame-Options DENY
Header always set X-XSS-Protection "1; mode=block"
Header always set Referrer-Policy "strict-origin-when-cross-origin"

# Block sensitive files
<Files ~ "^\.">
    Order allow,deny
    Deny from all
</Files>

<Files ~ "\.sql$">
    Order allow,deny
    Deny from all
</Files>

<Files ~ "\.log$">
    Order allow,deny
    Deny from all
</Files>

<Files "composer.json">
    Order allow,deny
    Deny from all
</Files>

<Files "composer.lock">
    Order allow,deny
    Deny from all
</Files>

# API Routing with proper error handling
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^api/(.*)$ api/index.php [QSA,L]

# Admin Routes
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^admin/(.*)$ admin/index.php [QSA,L]

# Block direct access to class files
RewriteRule ^classes/.*$ - [F,L]
RewriteRule ^config/.*$ - [F,L]';
    }
    
    private function prioritizeFixes() {
        echo "📋 PRIORITIZING FIXES\n";
        echo "-" . str_repeat("-", 30) . "\n";
        
        // Sort fixes by priority
        usort($this->fixes, function($a, $b) {
            $priority_order = ['critical' => 0, 'high' => 1, 'medium' => 2, 'low' => 3];
            return $priority_order[$a['priority']] <=> $priority_order[$b['priority']];
        });
        
        $priority_counts = array_count_values(array_column($this->fixes, 'priority'));
        
        foreach ($priority_counts as $priority => $count) {
            echo sprintf("%-10s: %d fixes\n", strtoupper($priority), $count);
        }
        
        echo "\nTotal fixes generated: " . count($this->fixes) . "\n\n";
    }
    
    private function outputSolutions() {
        echo "💡 SOLUTION RECOMMENDATIONS\n";
        echo "=" . str_repeat("=", 40) . "\n";
        
        $critical_fixes = array_filter($this->fixes, fn($f) => $f['priority'] === 'critical');
        $high_fixes = array_filter($this->fixes, fn($f) => $f['priority'] === 'high');
        
        if (!empty($critical_fixes)) {
            echo "🚨 CRITICAL FIXES (Apply Immediately):\n\n";
            foreach ($critical_fixes as $fix) {
                $this->outputFix($fix);
            }
        }
        
        if (!empty($high_fixes)) {
            echo "⚠️ HIGH PRIORITY FIXES:\n\n";
            foreach ($high_fixes as $fix) {
                $this->outputFix($fix);
            }
        }
        
        // Save all fixes
        file_put_contents('solution-recommendations.json', json_encode($this->fixes, JSON_PRETTY_PRINT));
        echo "\n📄 All solutions saved to: solution-recommendations.json\n";
    }
    
    private function outputFix($fix) {
        echo "📄 File: " . $fix['file'] . "\n";
        echo "🔍 Issue: " . ($fix['issue'] ?? $fix['type']) . "\n";
        
        if (isset($fix['line'])) {
            echo "📍 Line: " . $fix['line'] . "\n";
        }
        
        echo "💡 Fix:\n";
        if (isset($fix['fix_code'])) {
            echo "```php\n" . $fix['fix_code'] . "\n```\n";
        } else {
            echo $fix['suggested_fix'] . "\n";
        }
        echo "\n";
    }
}

// Execute solution generation
$generator = new SolutionGenerator();
$generator->generateSolutions();
?>