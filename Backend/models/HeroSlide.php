<?php
/**
 * HeroSlide model - handles database operations for custom home page hero slides
 */
require_once __DIR__ . '/../lib/db.php';

class HeroSlide
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
            $this->db->query("SELECT 1 FROM hero_slides LIMIT 1");
        } catch (Throwable $e) {
            $sql = "CREATE TABLE IF NOT EXISTS hero_slides (
              id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
              image_path VARCHAR(255) NOT NULL,
              title VARCHAR(255) NOT NULL,
              brand VARCHAR(100) DEFAULT '',
              year INT UNSIGNED DEFAULT NULL,
              link VARCHAR(255) DEFAULT '',
              sort_order INT DEFAULT 0,
              created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB;";
            $this->db->exec($sql);
        }
    }

    /**
     * Get all hero slides sorted by order
     */
    public function getAll(): array
    {
        $stmt = $this->db->query("SELECT * FROM hero_slides ORDER BY sort_order ASC, id ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get slide by ID
     */
    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM hero_slides WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Add new slide
     */
    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO hero_slides (image_path, title, brand, year, link, sort_order) 
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $data['image_path'],
            $data['title'],
            $data['brand'] ?? '',
            !empty($data['year']) ? (int)$data['year'] : null,
            $data['link'] ?? '',
            (int)($data['sort_order'] ?? 0)
        ]);
        return (int)$this->db->lastInsertId();
    }

    /**
     * Update existing slide
     */
    public function update(int $id, array $data): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE hero_slides 
             SET image_path = ?, title = ?, brand = ?, year = ?, link = ?, sort_order = ? 
             WHERE id = ?"
        );
        return $stmt->execute([
            $data['image_path'],
            $data['title'],
            $data['brand'] ?? '',
            !empty($data['year']) ? (int)$data['year'] : null,
            $data['link'] ?? '',
            (int)($data['sort_order'] ?? 0),
            $id
        ]);
    }

    /**
     * Delete slide
     */
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM hero_slides WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
