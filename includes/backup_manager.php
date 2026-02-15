<?php
/**
 * Portable Data Engine (PDE) - مدير البيانات المحمول
 * هذا الملحق مسؤول عن جعل قاعدة البيانات تنتقل تلقائياً مع الكود
 */

class BackupManager {
    private $pdo;
    private $backupDir;
    private $backupFile;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->backupDir = dirname(__DIR__) . '/database/.portable';
        $this->backupFile = $this->backupDir . '/portable_data.sql';
        
        if (!is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0755, true);
            // حماية المجلد بملف .htaccess
            file_put_contents($this->backupDir . '/.htaccess', "Deny from all");
        }
    }

    /**
     * تصدير قاعدة البيانات بالكامل إلى ملف SQL
     */
    public function export() {
        try {
            $tables = [];
            $result = $this->pdo->query("SHOW TABLES");
            while ($row = $result->fetch(PDO::FETCH_NUM)) {
                $tables[] = $row[0];
            }

            $sql = "-- Portable Data Backup\n";
            $sql .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
            $sql .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";

            foreach ($tables as $table) {
                // 구조 (Schema)
                $res = $this->pdo->query("SHOW CREATE TABLE $table");
                $showCreate = $res->fetch(PDO::FETCH_ASSOC);
                $sql .= "DROP TABLE IF EXISTS `$table`;\n";
                $sql .= $showCreate['Create Table'] . ";\n\n";

                // البيانات (Data)
                $res = $this->pdo->query("SELECT * FROM $table");
                while ($row = $res->fetch(PDO::FETCH_ASSOC)) {
                    $keys = array_map(function($k) { return "`$k`"; }, array_keys($row));
                    $values = array_map(function($v) { 
                        if ($v === null) return "NULL";
                        return $this->pdo->quote($v); 
                    }, array_values($row));
                    
                    $sql .= "INSERT INTO `$table` (" . implode(', ', $keys) . ") VALUES (" . implode(', ', $values) . ");\n";
                }
                $sql .= "\n";
            }

            $sql .= "SET FOREIGN_KEY_CHECKS = 1;\n";
            
            return file_put_contents($this->backupFile, $sql) !== false;
        } catch (Exception $e) {
            logError("Backup Export Failed: " . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * استيراد قاعدة البيانات من الملف المحمول
     */
    public function import() {
        if (!file_exists($this->backupFile)) return false;

        try {
            $sql = file_get_contents($this->backupFile);
            // تنفيذ الاستعلامات (قد تكون ضخمة، نقسمها إذا لزم الأمر)
            $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
            $this->pdo->exec($sql);
            $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
            return true;
        } catch (Exception $e) {
            logError("Backup Import Failed: " . $e->getMessage(), 'critical');
            return false;
        }
    }

    public function getBackupPath() {
        return $this->backupFile;
    }

    public function hasBackup() {
        return file_exists($this->backupFile);
    }
}
