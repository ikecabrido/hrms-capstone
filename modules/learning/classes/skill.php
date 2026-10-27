<?php

include_once __DIR__ . '/../../../database/db.php';

class Skill
{
    public const DEFAULT_ICON = 'fas fa-star';

    public const ICON_OPTIONS = [
        'fas fa-star' => 'Star',
        'fas fa-lightbulb' => 'Ideas',
        'fas fa-graduation-cap' => 'Learning',
        'fas fa-book-open' => 'Reading',
        'fas fa-book' => 'Knowledge',
        'fas fa-brain' => 'Thinking',
        'fas fa-users' => 'Teamwork',
        'fas fa-comments' => 'Communication',
        'fas fa-handshake' => 'Collaboration',
        'fas fa-user-tie' => 'Leadership',
        'fas fa-chess' => 'Strategy',
        'fas fa-bullseye' => 'Goals',
        'fas fa-chart-column' => 'Analytics',
        'fas fa-calculator' => 'Numeracy',
        'fas fa-code' => 'Technology',
        'fas fa-computer' => 'Computing',
        'fas fa-network-wired' => 'Networking',
        'fas fa-screwdriver-wrench' => 'Technical',
        'fas fa-wrench' => 'Tools',
        'fas fa-pen-to-square' => 'Writing',
        'fas fa-palette' => 'Creativity',
        'fas fa-language' => 'Languages',
        'fas fa-headset' => 'Support',
        'fas fa-clock' => 'Time management',
        'fas fa-arrows-rotate' => 'Adaptability',
        'fas fa-check-double' => 'Quality',
        'fas fa-clipboard-check' => 'Compliance',
        'fas fa-shield-halved' => 'Security',
        'fas fa-scale-balanced' => 'Ethics',
        'fas fa-heart-pulse' => 'Wellbeing',
        'fas fa-leaf' => 'Sustainability',
        'fas fa-boxes-stacked' => 'Operations',
        'fas fa-briefcase' => 'Business',
        'fas fa-bolt' => 'Energy',
        'fas fa-fire' => 'Motivation',
        'fas fa-person-chalkboard' => 'Teaching',
        'fas fa-circle-check' => 'Achievement',
    ];

    private PDO $conn;

    public function __construct($pdo = null)
    {
        if ($pdo instanceof PDO) {
            $this->conn = $pdo;
            return;
        }

        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function getById(int $id): ?array
    {
        $sql = 'SELECT id, name, description, icon, suggested, status, created_at, date_updated FROM ld_skill WHERE id = :id LIMIT 1';
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $skill = $stmt->fetch(PDO::FETCH_ASSOC);

        return $skill ?: null;
    }

    public function getList(): array
    {
        $sql = 'SELECT id, name, description, icon, suggested, status FROM ld_skill ORDER BY name ASC';
        $stmt = $this->conn->query($sql);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function update(array $input): array
    {
        $skillId = (int) ($input['id'] ?? 0);
        $name = trim((string) ($input['name'] ?? ''));
        $description = $input['description'] ?? null;
        $suggested = !empty($input['suggested']);
        $status = trim((string) ($input['status'] ?? 'active'));

        if ($skillId <= 0) {
            return ['success' => false, 'message' => 'Skill ID is required.'];
        }

        if ($name === '') {
            return ['success' => false, 'message' => 'Skill name is required.'];
        }

        if (!in_array($status, ['active', 'archived'], true)) {
            $status = 'active';
        }

        $fields = [
            ':name' => $name,
            ':description' => $description,
            ':suggested' => $suggested ? 1 : 0,
            ':status' => $status,
            ':id' => $skillId,
        ];
        $iconSql = '';
        if (array_key_exists('icon', $input)) {
            $icon = trim((string) $input['icon']);
            if (!self::isValidIcon($icon)) {
                return ['success' => false, 'message' => 'Choose an icon from the available list.'];
            }
            $iconSql = ', icon = :icon';
            $fields[':icon'] = $icon;
        }

        $stmt = $this->conn->prepare('UPDATE ld_skill SET name = :name, description = :description, suggested = :suggested, status = :status' . $iconSql . ', date_updated = NOW() WHERE id = :id');
        $stmt->execute($fields);

        return ['success' => true, 'id' => $skillId, 'message' => 'Skill updated successfully'];
    }

    public function archive(int $id): array
    {
        if ($id <= 0) {
            return ['success' => false, 'message' => 'Skill ID is required.'];
        }

        $stmt = $this->conn->prepare("UPDATE ld_skill SET status = 'archived', date_updated = NOW() WHERE id = :id");
        $stmt->execute([':id' => $id]);

        return ['success' => true, 'id' => $id, 'message' => 'Skill archived successfully'];
    }

    public function create(array $input): array
    {
        $name = trim((string) ($input['name'] ?? ''));
        $description = trim((string) ($input['description'] ?? ''));
        $suggested = isset($input['suggested']) ? (int) $input['suggested'] : 0;
        $status = trim((string) ($input['status'] ?? 'active'));
        $icon = trim((string) ($input['icon'] ?? self::DEFAULT_ICON));

        if ($name === '') {
            return ['success' => false, 'message' => 'Skill name is required.'];
        }

        if (!in_array($status, ['active', 'archived'], true)) {
            $status = 'active';
        }
        if (!self::isValidIcon($icon)) {
            return ['success' => false, 'message' => 'Choose an icon from the available list.'];
        }

        // ld_skill.id is not AUTO_INCREMENT on every deployment (the imported
        // schema declares it as a plain primary key), so resolve the next id
        // ourselves instead of trusting lastInsertId(), which would return 0.
        $id = (int) ($input['id'] ?? 0);
        if ($id <= 0) {
            $id = ((int) $this->conn->query('SELECT COALESCE(MAX(id), 0) FROM ld_skill')->fetchColumn()) + 1;
        }

        $sql = 'INSERT INTO ld_skill (id, name, description, icon, suggested, status) VALUES (:id, :name, :description, :icon, :suggested, :status)';
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':id' => $id,
            ':name' => $name,
            ':description' => $description,
            ':icon' => $icon,
            ':suggested' => $suggested ? 1 : 0,
            ':status' => $status,
        ]);

        return [
            'success' => true,
            'message' => 'Skill created successfully.',
            'id' => $id,
        ];
    }

    public static function isValidIcon(string $icon): bool
    {
        return array_key_exists($icon, self::ICON_OPTIONS)
            || (strlen($icon) <= 64 && preg_match('/\Afas fa-[a-z0-9]+(?:-[a-z0-9]+)*\z/', $icon) === 1);
    }
}
