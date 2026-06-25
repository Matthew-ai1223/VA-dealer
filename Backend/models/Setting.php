<?php
/**
 * Setting model - handles key-value site configurations
 */
require_once __DIR__ . '/../lib/db.php';

class Setting
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
        $this->ensureTableExists();
    }

    private function ensureTableExists(): void
    {
        try {
            $this->db->query("SELECT 1 FROM settings LIMIT 1");
        } catch (Throwable $e) {
            $sql = "CREATE TABLE IF NOT EXISTS settings (
              id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
              setting_key VARCHAR(100) NOT NULL UNIQUE,
              setting_value TEXT DEFAULT NULL,
              updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB;";
            $this->db->exec($sql);
        }
    }

    /**
     * Get all settings as key-value array
     */
    public function getAll(): array
    {
        $stmt = $this->db->query("SELECT setting_key, setting_value FROM settings");
        $results = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $results[$row['setting_key']] = $row['setting_value'];
        }
        return $results;
    }

    /**
     * Get a specific setting
     */
    public function get(string $key, $default = null)
    {
        $stmt = $this->db->prepare("SELECT setting_value FROM settings WHERE setting_key = ? LIMIT 1");
        $stmt->execute([$key]);
        $row = $stmt->fetch();
        return $row ? $row['setting_value'] : $default;
    }

    /**
     * Set a setting value
     */
    public function set(string $key, ?string $value): bool
    {
        $stmt = $this->db->prepare(
            "INSERT INTO settings (setting_key, setting_value) 
             VALUES (?, ?) 
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)"
        );
        return $stmt->execute([$key, $value]);
    }
}
