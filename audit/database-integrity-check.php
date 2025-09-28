<?php
/**
 * DATABASE INTEGRITY CHECK
 * Validates database structure and data consistency
 */

echo "🗄️ DATABASE INTEGRITY CHECK\n";
echo "===========================\n\n";

class DatabaseIntegrityChecker {
    private $conn;
    private $results = [];
    
    public function __construct() {
        // Try to establish database connection
        try {
            // First try to include the config
            $config_files = [
                '../public_html/backend/config/database.php',
                '../public_html/config/database.php'
            ];
            
            $config_loaded = false;
            foreach ($config_files as $config_file) {
                if (file_exists($config_file)) {
                    require_once $config_file;
                    $config_loaded = true;
                    break;
                }
            }
            
            if ($config_loaded && class_exists('Database')) {
                $database = new Database();
                $this->conn = $database->getConnection();
            }
        } catch (Exception $e) {
            echo "❌ Database connection failed: " . $e->getMessage() . "\n";
            $this->conn = null;
        }
    }
    
    public function runIntegrityCheck() {
        if (!$this->conn) {
            echo "❌ Cannot proceed - no database connection\n";
            $this->results['connection'] = ['status' => 'FAILED', 'error' => 'No connection'];
            return;
        }
        
        $this->checkTableStructure();
        $this->validateCoreData();
        $this->checkDataConsistency();
        $this->analyzeRelationships();
        $this->generateIntegrityReport();
    }
    
    private function checkTableStructure() {
        echo "📋 TABLE STRUCTURE CHECK\n";
        echo "-" . str_repeat("-", 30) . "\n";
        
        $expected_tables = [
            'users' => 'User authentication and roles',
            'pages' => 'Dynamic page management',
            'page_elements' => 'Page content elements',
            'media' => 'Media library',
            'portfolio_projects' => 'Portfolio showcase',
            'portfolio_images' => 'Project image galleries',
            'services' => 'Service offerings',
            'service_packages' => 'Service pricing packages',
            'blog_posts' => 'Blog content',
            'testimonials' => 'Client testimonials',
            'form_submissions' => 'Form data and leads',
            'newsletter_subscribers' => 'Email subscribers',
            'site_settings' => 'Configuration settings'
        ];
        
        try {
            // Get all tables
            $stmt = $this->conn->query("SHOW TABLES");
            $existing_tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            echo "Found " . count($existing_tables) . " tables in database\n\n";
            
            foreach ($expected_tables as $table => $description) {
                $exists = in_array($table, $existing_tables);
                echo sprintf("%-20s: %s - %s\n", $table, $exists ? '✅ EXISTS' : '❌ MISSING', $description);
                
                if ($exists) {
                    // Get table info
                    $stmt = $this->conn->query("DESCRIBE `$table`");
                    $columns = $stmt->fetchAll();
                    
                    $this->results['table_structure'][$table] = [
                        'exists' => true,
                        'column_count' => count($columns),
                        'columns' => array_column($columns, 'Field'),
                        'status' => 'EXISTS'
                    ];
                } else {
                    $this->results['table_structure'][$table] = [
                        'exists' => false,
                        'status' => 'MISSING'
                    ];
                }
            }
        } catch (Exception $e) {
            echo "❌ Error checking tables: " . $e->getMessage() . "\n";
            $this->results['table_structure'] = ['error' => $e->getMessage()];
        }
    }
    
    private function validateCoreData() {
        echo "\n📊 CORE DATA VALIDATION\n";
        echo "-" . str_repeat("-", 30) . "\n";
        
        $core_tables = [
            'users' => 'SELECT COUNT(*) as count FROM users WHERE role = "admin"',
            'pages' => 'SELECT COUNT(*) as count FROM pages WHERE is_published = 1',
            'portfolio_projects' => 'SELECT COUNT(*) as count FROM portfolio_projects',
            'media' => 'SELECT COUNT(*) as count FROM media',
            'testimonials' => 'SELECT COUNT(*) as count FROM testimonials WHERE is_published = 1'
        ];
        
        foreach ($core_tables as $table => $query) {
            try {
                $stmt = $this->conn->query($query);
                $result = $stmt->fetch();
                $count = $result['count'];
                
                echo sprintf("%-20s: %d records\n", $table, $count);
                
                $this->results['core_data'][$table] = [
                    'record_count' => $count,
                    'has_data' => $count > 0,
                    'status' => 'CHECKED'
                ];
                
                // Additional checks for specific tables
                if ($table === 'users' && $count === 0) {
                    echo "  ⚠️ No admin users found - system may be inaccessible\n";
                }
                
                if ($table === 'pages' && $count === 0) {
                    echo "  ⚠️ No published pages found\n";
                }
                
            } catch (Exception $e) {
                echo sprintf("%-20s: ❌ ERROR - %s\n", $table, $e->getMessage());
                $this->results['core_data'][$table] = [
                    'error' => $e->getMessage(),
                    'status' => 'ERROR'
                ];
            }
        }
    }
    
    private function checkDataConsistency() {
        echo "\n🔗 DATA CONSISTENCY CHECK\n";
        echo "-" . str_repeat("-", 30) . "\n";
        
        $consistency_checks = [
            'portfolio_featured_images' => [
                'query' => 'SELECT COUNT(*) as count FROM portfolio_projects p LEFT JOIN media m ON p.featured_image = m.id WHERE p.featured_image IS NOT NULL AND m.id IS NULL',
                'description' => 'Portfolio projects with missing featured images'
            ],
            'portfolio_project_images' => [
                'query' => 'SELECT COUNT(*) as count FROM portfolio_images pi LEFT JOIN media m ON pi.media_id = m.id WHERE m.id IS NULL',
                'description' => 'Portfolio images with missing media files'
            ],
            'blog_featured_images' => [
                'query' => 'SELECT COUNT(*) as count FROM blog_posts b LEFT JOIN media m ON b.featured_image = m.id WHERE b.featured_image IS NOT NULL AND m.id IS NULL',
                'description' => 'Blog posts with missing featured images'
            ]
        ];
        
        foreach ($consistency_checks as $check_name => $check) {
            try {
                $stmt = $this->conn->query($check['query']);
                $result = $stmt->fetch();
                $inconsistent_count = $result['count'];
                
                echo sprintf("%-30s: %s (%d issues)\n", 
                    $check['description'],
                    $inconsistent_count === 0 ? '✅ CONSISTENT' : '⚠️ ISSUES FOUND',
                    $inconsistent_count
                );
                
                $this->results['data_consistency'][$check_name] = [
                    'inconsistent_records' => $inconsistent_count,
                    'status' => $inconsistent_count === 0 ? 'PASS' : 'ISSUES_FOUND'
                ];
                
            } catch (Exception $e) {
                echo sprintf("%-30s: ❌ ERROR - %s\n", $check['description'], $e->getMessage());
                $this->results['data_consistency'][$check_name] = [
                    'error' => $e->getMessage(),
                    'status' => 'ERROR'
                ];
            }
        }
    }
    
    private function analyzeRelationships() {
        echo "\n🔗 RELATIONSHIP ANALYSIS\n";
        echo "-" . str_repeat("-", 30) . "\n";
        
        $relationships = [
            'portfolio_to_media' => [
                'query' => 'SELECT COUNT(DISTINCT p.id) as projects, COUNT(DISTINCT m.id) as media_files FROM portfolio_projects p LEFT JOIN media m ON p.featured_image = m.id',
                'description' => 'Portfolio projects to media relationship'
            ],
            'users_to_content' => [
                'query' => 'SELECT COUNT(DISTINCT u.id) as users, COUNT(DISTINCT p.id) as pages FROM users u LEFT JOIN pages p ON u.id = p.created_by',
                'description' => 'Users to created content relationship'
            ]
        ];
        
        foreach ($relationships as $rel_name => $rel) {
            try {
                $stmt = $this->conn->query($rel['query']);
                $result = $stmt->fetch();
                
                echo $rel['description'] . ":\n";
                foreach ($result as $key => $value) {
                    echo "  $key: $value\n";
                }
                
                $this->results['relationships'][$rel_name] = $result;
                
            } catch (Exception $e) {
                echo $rel['description'] . ": ❌ ERROR - " . $e->getMessage() . "\n";
                $this->results['relationships'][$rel_name] = ['error' => $e->getMessage()];
            }
            echo "\n";
        }
    }
    
    private function generateIntegrityReport() {
        echo "📊 DATABASE INTEGRITY SUMMARY\n";
        echo "=" . str_repeat("=", 40) . "\n";
        
        $tables_exist = count(array_filter($this->results['table_structure'] ?? [], fn($t) => $t['exists'] ?? false));
        $total_expected = 13; // Number of expected tables
        
        $data_consistent = count(array_filter($this->results['data_consistency'] ?? [], fn($c) => $c['status'] === 'PASS'));
        $total_consistency_checks = count($this->results['data_consistency'] ?? []);
        
        echo "Tables Present: $tables_exist/$total_expected\n";
        echo "Data Consistency: $data_consistent/$total_consistency_checks checks passed\n";
        
        $integrity_score = $total_expected > 0 ? ($tables_exist / $total_expected) * 100 : 0;
        echo "Database Integrity Score: " . round($integrity_score, 1) . "%\n";
        
        if ($integrity_score >= 90) {
            echo "\n🎯 DATABASE STATUS: ✅ EXCELLENT INTEGRITY\n";
        } elseif ($integrity_score >= 70) {
            echo "\n⚠️ DATABASE STATUS: 🟡 GOOD WITH MINOR ISSUES\n";
        } else {
            echo "\n🚨 DATABASE STATUS: ❌ CRITICAL INTEGRITY ISSUES\n";
        }
        
        // Save results
        file_put_contents('database-integrity-results.json', json_encode($this->results, JSON_PRETTY_PRINT));
        echo "\n📄 Results saved to: database-integrity-results.json\n";
    }
}

// Execute database integrity check
$checker = new DatabaseIntegrityChecker();
$checker->runIntegrityCheck();
?>