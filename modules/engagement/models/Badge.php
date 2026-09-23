<?php
namespace App\Models;

class Badge extends BaseModel
{
    public function all()
    {
        return $this->execute('SELECT * FROM eer_badges')->fetchAll();
    }

    public function find($id)
    {
        return $this->execute('SELECT * FROM eer_badges WHERE eer_badge_id = :id', ['id' => $id])->fetch();
    }

    public function create($data)
    {
        $nextBadgeId = (int)$this->execute(
            'SELECT COALESCE(MAX(eer_badge_id), 0) + 1 FROM eer_badges'
        )->fetchColumn();

        $sql = 'INSERT INTO eer_badges
            (eer_badge_id, name, description, icon, tier, points_value, category, requirement_type, requirement_value, status)
            VALUES (:badge_id, :name, :description, :icon, :tier, :points_value, :category, :requirement_type, :requirement_value, :status)';
        $params = [
            'badge_id' => $nextBadgeId,
            'name' => trim((string)($data['name'] ?? '')),
            'description' => trim((string)($data['description'] ?? '')),
            'icon' => $this->iconForCategory($data['category'] ?? 'achievement'),
            'tier' => $data['tier'] ?? 'bronze',
            'points_value' => $this->pointsForTier($data['tier'] ?? 'bronze'),
            'category' => trim((string)($data['category'] ?? 'achievement')),
            'requirement_type' => 'manual',
            'requirement_value' => null,
            'status' => $data['status'] ?? 'active'
        ];
        $this->execute($sql, $params);
        return $this->db->lastInsertId();
    }

    private function pointsForTier($tier)
    {
        return [
            'bronze' => 10,
            'silver' => 25,
            'gold' => 50,
            'platinum' => 100
        ][strtolower((string)$tier)] ?? 10;
    }

    private function iconForCategory($category)
    {
        return [
            'achievement' => 'fas fa-star',
            'performance' => 'fas fa-chart-line',
            'teamwork' => 'fas fa-users',
            'service' => 'fas fa-hands-helping'
        ][strtolower((string)$category)] ?? 'fas fa-medal';
    }
}
