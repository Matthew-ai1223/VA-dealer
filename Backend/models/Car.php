<?php
/**
 * Car model - handles all database operations for listings
 * Stage 2: add lead association and WhatsApp automation hooks
 */
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/helpers.php';

class Car
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Get cars with optional filters
     */
    public function getAll(array $filters = [], bool $adminView = false): array
    {
        $sql = 'SELECT * FROM cars WHERE 1=1';
        $params = [];

        if (!$adminView) {
            $sql .= ' AND status = ?';
            $params[] = 'available';
        } elseif (!empty($filters['status'])) {
            $sql .= ' AND status = ?';
            $params[] = $filters['status'];
        }

        if (!empty($filters['brand'])) {
            $sql .= ' AND brand = ?';
            $params[] = $filters['brand'];
        }

        if (!empty($filters['model'])) {
            $sql .= ' AND model LIKE ?';
            $params[] = '%' . $filters['model'] . '%';
        }

        if (!empty($filters['year'])) {
            $sql .= ' AND year = ?';
            $params[] = (int) $filters['year'];
        }

        if (isset($filters['min_price']) && $filters['min_price'] !== '') {
            $sql .= ' AND price >= ?';
            $params[] = (float) $filters['min_price'];
        }

        if (isset($filters['max_price']) && $filters['max_price'] !== '') {
            $sql .= ' AND price <= ?';
            $params[] = (float) $filters['max_price'];
        }

        if (!empty($filters['search'])) {
            $sql .= ' AND (title LIKE ? OR brand LIKE ? OR model LIKE ? OR description LIKE ?)';
            $term = '%' . $filters['search'] . '%';
            $params = array_merge($params, [$term, $term, $term, $term]);
        }

        if (!empty($filters['featured'])) {
            $sql .= ' AND featured = 1';
        }

        $sql .= ' ORDER BY created_at DESC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $this->formatRows($stmt->fetchAll());
    }

    public function getById(int $id, bool $adminView = false): ?array
    {
        $sql = 'SELECT * FROM cars WHERE id = ?';
        $params = [$id];

        if (!$adminView) {
            $sql .= ' AND status = ?';
            $params[] = 'available';
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();

        return $row ? $this->formatRow($row) : null;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO cars (title, brand, model, year, price, description, specs, images, status, featured)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );

        $stmt->execute([
            $data['title'],
            $data['brand'],
            $data['model'],
            (int) $data['year'],
            (float) $data['price'],
            $data['description'] ?? '',
            json_encode($data['specs'] ?? []),
            json_encode($data['images'] ?? []),
            $data['status'] ?? 'available',
            !empty($data['featured']) ? 1 : 0,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE cars SET title = ?, brand = ?, model = ?, year = ?, price = ?,
             description = ?, specs = ?, images = ?, status = ?, featured = ?
             WHERE id = ?'
        );

        return $stmt->execute([
            $data['title'],
            $data['brand'],
            $data['model'],
            (int) $data['year'],
            (float) $data['price'],
            $data['description'] ?? '',
            json_encode($data['specs'] ?? []),
            json_encode($data['images'] ?? []),
            $data['status'] ?? 'available',
            !empty($data['featured']) ? 1 : 0,
            $id,
        ]);
    }

    public function delete(int $id): ?array
    {
        $car = $this->getById($id, true);
        if (!$car) {
            return null;
        }

        $stmt = $this->db->prepare('DELETE FROM cars WHERE id = ?');
        $stmt->execute([$id]);

        return $car;
    }

    public function getBrands(): array
    {
        $stmt = $this->db->query(
            "SELECT DISTINCT brand FROM cars WHERE status = 'available' ORDER BY brand ASC"
        );
        return array_column($stmt->fetchAll(), 'brand');
    }

    public function getYears(): array
    {
        $stmt = $this->db->query(
            "SELECT DISTINCT year FROM cars WHERE status = 'available' ORDER BY year DESC"
        );
        return array_column($stmt->fetchAll(), 'year');
    }

    public function countAll(string $status = ''): int
    {
        if ($status) {
            $stmt = $this->db->prepare('SELECT COUNT(*) FROM cars WHERE status = ?');
            $stmt->execute([$status]);
        } else {
            $stmt = $this->db->query('SELECT COUNT(*) FROM cars');
        }
        return (int) $stmt->fetchColumn();
    }

    private function formatRows(array $rows): array
    {
        return array_map([$this, 'formatRow'], $rows);
    }

    private function formatRow(array $row): array
    {
        $row['specs'] = json_decode($row['specs'] ?? '[]', true) ?: [];
        $row['images'] = json_decode($row['images'] ?? '[]', true) ?: [];
        $row['image_urls'] = getCarImageUrls($row['images']);
        $row['image_count'] = count($row['images']);
        $row['price_formatted'] = formatPrice((float) $row['price']);
        $row['primary_image'] = !empty($row['images'][0])
            ? getImageUrl($row['images'][0])
            : getImageUrl(null);
        $row['whatsapp_url'] = whatsappLink($row['title'], (float) $row['price'], (string) $row['id']);
        return $row;
    }
}
