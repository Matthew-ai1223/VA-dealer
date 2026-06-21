<?php
/**
 * Lead model — CRM, capture, tracking, analytics
 */
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/helpers.php';

class Lead
{
    private PDO $db;

    public const STATUSES = ['new', 'contacted', 'interested', 'negotiating', 'closed_won', 'closed_lost'];
    public const SOURCES = ['website', 'nairaland', 'instagram', 'facebook', 'whatsapp'];
    public const INQUIRY_TYPES = ['request_info', 'book_inspection', 'request_callback', 'whatsapp'];

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function create(array $data): int
    {
        $sql = 'INSERT INTO leads (
            full_name, phone_number, email, car_id, interested_vehicle, budget,
            inquiry_type, source, status, message, assigned_to
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $data['full_name'],
            $data['phone_number'],
            $data['email'] ?? null,
            $data['car_id'] ?? null,
            $data['interested_vehicle'],
            $data['budget'] ?? null,
            $data['inquiry_type'] ?? 'request_info',
            $data['source'] ?? 'website',
            $data['status'] ?? 'new',
            $data['message'] ?? null,
            $data['assigned_to'] ?? null,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM leads WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? $this->formatRow($row) : null;
    }

    public function getAll(array $filters = [], int $page = 1, int $perPage = 20): array
    {
        $where = ['1=1'];
        $params = [];

        if (!empty($filters['status'])) {
            $where[] = 'status = ?';
            $params[] = $filters['status'];
        }
        if (!empty($filters['source'])) {
            $where[] = 'source = ?';
            $params[] = $filters['source'];
        }
        if (!empty($filters['search'])) {
            $where[] = '(full_name LIKE ? OR phone_number LIKE ? OR interested_vehicle LIKE ? OR email LIKE ?)';
            $term = '%' . $filters['search'] . '%';
            $params = array_merge($params, [$term, $term, $term, $term]);
        }

        $whereSql = implode(' AND ', $where);
        $offset = max(0, ($page - 1) * $perPage);

        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM leads WHERE {$whereSql}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $sql = "SELECT * FROM leads WHERE {$whereSql} ORDER BY created_at DESC LIMIT {$perPage} OFFSET {$offset}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return [
            'items' => array_map([$this, 'formatRow'], $stmt->fetchAll()),
            'total' => $total,
            'page'  => $page,
            'pages' => max(1, (int) ceil($total / $perPage)),
        ];
    }

    public function updateStatus(int $id, string $status, ?string $assignedTo = null): bool
    {
        if (!in_array($status, self::STATUSES, true)) {
            return false;
        }

        $sql = 'UPDATE leads SET status = ?, assigned_to = COALESCE(?, assigned_to) WHERE id = ?';
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$status, $assignedTo, $id]);
    }

    public function addNote(int $leadId, string $adminUsername, string $note): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO lead_notes (lead_id, admin_username, note) VALUES (?, ?, ?)'
        );
        $stmt->execute([$leadId, $adminUsername, trim($note)]);
        return (int) $this->db->lastInsertId();
    }

    public function getNotes(int $leadId): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM lead_notes WHERE lead_id = ? ORDER BY created_at DESC'
        );
        $stmt->execute([$leadId]);
        return $stmt->fetchAll();
    }

    public function logActivity(string $type, ?int $carId = null, ?int $leadId = null, ?array $meta = null): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO lead_activities (lead_id, car_id, activity_type, meta, ip_address) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $leadId,
            $carId,
            $type,
            $meta ? json_encode($meta, JSON_UNESCAPED_UNICODE) : null,
            $_SERVER['REMOTE_ADDR'] ?? null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function getActivities(int $leadId): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM lead_activities WHERE lead_id = ? ORDER BY created_at DESC'
        );
        $stmt->execute([$leadId]);
        return array_map(function ($row) {
            $row['meta'] = parseJsonField($row['meta'] ?? null);
            return $row;
        }, $stmt->fetchAll());
    }

    public function getOverviewStats(): array
    {
        $stmt = $this->db->query(
            "SELECT
                COUNT(*) AS total,
                SUM(status = 'new') AS new_leads,
                SUM(status = 'interested') AS interested,
                SUM(status = 'negotiating') AS negotiating,
                SUM(status = 'closed_won') AS closed_won
             FROM leads"
        );
        return $stmt->fetch() ?: [];
    }

    public function getSourceStats(): array
    {
        $stmt = $this->db->query(
            "SELECT source, COUNT(*) AS count,
                SUM(status = 'closed_won') AS won
             FROM leads GROUP BY source ORDER BY count DESC"
        );
        $rows = $stmt->fetchAll();
        foreach ($rows as &$row) {
            $row['conversion_rate'] = $row['count'] > 0
                ? round(((int) $row['won'] / (int) $row['count']) * 100, 1)
                : 0;
        }
        return $rows;
    }

    public function getWhatsAppStats(): array
    {
        $stmt = $this->db->query(
            "SELECT car_id, COUNT(*) AS clicks
             FROM lead_activities
             WHERE activity_type = 'whatsapp_click' AND car_id IS NOT NULL
             GROUP BY car_id ORDER BY clicks DESC LIMIT 10"
        );
        return $stmt->fetchAll();
    }

    /** Vehicles with the most detail-page / modal views */
    public function getMostViewedVehicles(int $limit = 10): array
    {
        $limit = max(1, min(50, $limit));
        $stmt = $this->db->query(
            "SELECT car_id, COUNT(*) AS total
             FROM lead_activities
             WHERE activity_type = 'vehicle_viewed' AND car_id IS NOT NULL
             GROUP BY car_id ORDER BY total DESC LIMIT {$limit}"
        );
        return $stmt->fetchAll();
    }

    /** Vehicles with the most interest + WhatsApp clicks */
    public function getMostClickedVehicles(int $limit = 10): array
    {
        $limit = max(1, min(50, $limit));
        $stmt = $this->db->query(
            "SELECT car_id, COUNT(*) AS total
             FROM lead_activities
             WHERE activity_type IN ('interest_click', 'whatsapp_click') AND car_id IS NOT NULL
             GROUP BY car_id ORDER BY total DESC LIMIT {$limit}"
        );
        return $stmt->fetchAll();
    }

    public function countNewSince(?string $since = null): int
    {
        if ($since) {
            $stmt = $this->db->prepare('SELECT COUNT(*) FROM leads WHERE created_at >= ?');
            $stmt->execute([$since]);
        } else {
            $stmt = $this->db->query('SELECT COUNT(*) FROM leads');
        }
        return (int) $stmt->fetchColumn();
    }

    private function formatRow(array $row): array
    {
        $row['budget_formatted'] = isset($row['budget']) && $row['budget'] !== null
            ? formatPrice((float) $row['budget'])
            : null;
        $row['status_label'] = ucwords(str_replace('_', ' ', $row['status']));
        $row['source_label'] = ucfirst($row['source']);
        $row['inquiry_label'] = ucwords(str_replace('_', ' ', $row['inquiry_type']));
        return $row;
    }
}
