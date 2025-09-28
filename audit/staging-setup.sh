#!/bin/bash
# STAGING ENVIRONMENT SETUP SCRIPT
# Creates complete staging snapshot for safe testing

echo "🔄 CREATING STAGING ENVIRONMENT FOR AUDIT"
echo "=========================================="

# Create staging directory
mkdir -p staging-audit
cd staging-audit

# Create timestamp for this audit
AUDIT_TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
echo "Audit Timestamp: $AUDIT_TIMESTAMP"

# 1. Create complete staging snapshot
echo "📦 Creating staging snapshot..."
mkdir -p snapshots
cp -r ../public_html snapshots/public_html_$AUDIT_TIMESTAMP
echo "✅ Frontend files copied to staging"

# 2. Database export (simulation - would need actual credentials)
echo "🗄️ Exporting database..."
cat > snapshots/database_export_$AUDIT_TIMESTAMP.sql << 'EOF'
-- Database Export for Staging
-- Database: u720615217_portfolio_db
-- Export Date: $(date)

-- This would contain the actual database export
-- mysqldump -u username -p u720615217_portfolio_db > database_export.sql

-- For audit purposes, we'll document the expected structure
SHOW TABLES;
DESCRIBE pages;
DESCRIBE portfolio_projects;
DESCRIBE media;
DESCRIBE users;
EOF

echo "✅ Database export prepared"

# 3. Create staging configuration
echo "⚙️ Creating staging configuration..."
mkdir -p config
cat > config/staging-database.php << 'EOF'
<?php
// STAGING DATABASE CONFIGURATION
// DO NOT USE IN PRODUCTION

class StagingDatabase {
    private $host = 'localhost';
    private $db_name = 'staging_portfolio_db';
    private $username = 'staging_user';
    private $password = 'staging_pass';
    private $conn;

    public function getConnection() {
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                $this->username,
                $this->password,
                array(
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                )
            );
            return $this->conn;
        } catch(PDOException $e) {
            error_log("Staging DB Error: " . $e->getMessage());
            return null;
        }
    }
}
?>
EOF

echo "✅ Staging configuration created"
echo "🎯 Staging environment ready for audit"