<?php

require_once __DIR__ . '/../../../database/db.php';

class LaborLawReference
{
    private $conn;
    private $table = 'lc_labor_law_references';
    private $categoriesTable = 'lc_labor_law_categories';

    public function __construct($pdo = null)
    {
        if ($pdo instanceof PDO) {
            $this->conn = $pdo;
        } else {
            $database = new Database();
            $this->conn = $database->getConnection();
        }
    }

    public function getAllCategories()
    {
        $stmt = $this->conn->prepare("
            SELECT id, name, sort_order
            FROM {$this->categoriesTable}
            WHERE is_active = 1
            ORDER BY sort_order ASC, name ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getReferences(array $filters = [])
    {
        $sql = "
            SELECT
                r.id,
                r.reference_type,
                r.reference_number,
                r.title,
                r.short_title,
                r.category_id,
                c.name AS category_name,
                r.description,
                r.date_issued,
                r.effectivity_date,
                r.issuing_authority,
                r.status,
                r.keywords,
                r.source_url,
                r.document_path,
                r.related_law,
                r.summary,
                r.remarks,
                r.created_by,
                r.updated_by,
                r.created_at,
                r.updated_at
            FROM {$this->table} r
            LEFT JOIN {$this->categoriesTable} c ON c.id = r.category_id
            WHERE 1=1
        ";
        $params = [];

        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $sql .= " AND (
                r.title LIKE :search
                OR r.short_title LIKE :search
                OR r.reference_number LIKE :search
                OR r.keywords LIKE :search
                OR r.issuing_authority LIKE :search
                OR r.description LIKE :search
            )";
            $params[':search'] = $search;
        }

        if (!empty($filters['reference_type'])) {
            $sql .= " AND r.reference_type = :reference_type";
            $params[':reference_type'] = $filters['reference_type'];
        }

        if (!empty($filters['category_id'])) {
            $sql .= " AND r.category_id = :category_id";
            $params[':category_id'] = (int) $filters['category_id'];
        }

        if (!empty($filters['status'])) {
            $sql .= " AND r.status = :status";
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['issuing_authority'])) {
            $sql .= " AND r.issuing_authority LIKE :issuing_authority";
            $params[':issuing_authority'] = '%' . $filters['issuing_authority'] . '%';
        }

        if (!empty($filters['year'])) {
            $sql .= " AND YEAR(r.date_issued) = :year";
            $params[':year'] = (int) $filters['year'];
        }

        $sql .= " ORDER BY CASE WHEN r.reference_number = 'PD 442' THEN 0 ELSE 1 END ASC, r.date_issued DESC, r.id DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getReferenceById($id)
    {
        $stmt = $this->conn->prepare("
            SELECT
                r.id,
                r.reference_type,
                r.reference_number,
                r.title,
                r.short_title,
                r.category_id,
                c.name AS category_name,
                r.description,
                r.date_issued,
                r.effectivity_date,
                r.issuing_authority,
                r.status,
                r.keywords,
                r.source_url,
                r.document_path,
                r.related_law,
                r.summary,
                r.remarks,
                r.created_by,
                r.updated_by,
                r.created_at,
                r.updated_at
            FROM {$this->table} r
            LEFT JOIN {$this->categoriesTable} c ON c.id = r.category_id
            WHERE r.id = :id
            LIMIT 1
        ");
        $stmt->execute([':id' => (int) $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function createReference(array $data)
    {
        $stmt = $this->conn->prepare("
            INSERT INTO {$this->table}
                (reference_type, reference_number, title, short_title, category_id,
                 description, date_issued, effectivity_date, issuing_authority, status,
                 keywords, source_url, document_path, related_law, summary, remarks,
                 created_by, updated_by)
            VALUES
                (:reference_type, :reference_number, :title, :short_title, :category_id,
                 :description, :date_issued, :effectivity_date, :issuing_authority, :status,
                 :keywords, :source_url, :document_path, :related_law, :summary, :remarks,
                 :created_by, :updated_by)
        ");

        $stmt->execute([
            ':reference_type'  => $data['reference_type'] ?? 'Other Legal Reference',
            ':reference_number' => !empty($data['reference_number']) ? $data['reference_number'] : null,
            ':title'           => $data['title'],
            ':short_title'     => !empty($data['short_title']) ? $data['short_title'] : null,
            ':category_id'     => !empty($data['category_id']) ? (int) $data['category_id'] : null,
            ':description'     => !empty($data['description']) ? $data['description'] : null,
            ':date_issued'     => !empty($data['date_issued']) ? $data['date_issued'] : null,
            ':effectivity_date' => !empty($data['effectivity_date']) ? $data['effectivity_date'] : null,
            ':issuing_authority' => !empty($data['issuing_authority']) ? $data['issuing_authority'] : null,
            ':status'          => $data['status'] ?? 'For Reference',
            ':keywords'        => !empty($data['keywords']) ? $data['keywords'] : null,
            ':source_url'      => !empty($data['source_url']) ? $data['source_url'] : null,
            ':document_path'   => !empty($data['document_path']) ? $data['document_path'] : null,
            ':related_law'     => !empty($data['related_law']) ? $data['related_law'] : null,
            ':summary'         => !empty($data['summary']) ? $data['summary'] : null,
            ':remarks'         => !empty($data['remarks']) ? $data['remarks'] : null,
            ':created_by'      => !empty($data['created_by']) ? (int) $data['created_by'] : null,
            ':updated_by'      => !empty($data['updated_by']) ? (int) $data['updated_by'] : null,
        ]);

        return (int) $this->conn->lastInsertId();
    }

    public function updateReference($id, array $data)
    {
        $fields = [];
        $params = [':id' => (int) $id];

        $map = [
            'reference_type'   => 'reference_type',
            'reference_number' => 'reference_number',
            'title'            => 'title',
            'short_title'      => 'short_title',
            'category_id'      => 'category_id',
            'description'      => 'description',
            'date_issued'      => 'date_issued',
            'effectivity_date' => 'effectivity_date',
            'issuing_authority'=> 'issuing_authority',
            'status'           => 'status',
            'keywords'         => 'keywords',
            'source_url'       => 'source_url',
            'document_path'    => 'document_path',
            'related_law'      => 'related_law',
            'summary'          => 'summary',
            'remarks'          => 'remarks',
            'updated_by'       => 'updated_by',
        ];

        foreach ($map as $key => $col) {
            if (array_key_exists($key, $data)) {
                $val = $data[$key];
                if ($val === '' || $val === null) {
                    $val = null;
                } elseif (in_array($key, ['category_id', 'updated_by'], true)) {
                    $val = !empty($val) ? (int) $val : null;
                }
                $fields[] = "`$col` = :$key";
                $params[":$key"] = $val;
            }
        }

        if (empty($fields)) {
            return false;
        }

        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute($params);
    }

    public function deleteReference($id)
    {
        $stmt = $this->conn->prepare("DELETE FROM {$this->table} WHERE id = :id");
        return $stmt->execute([':id' => (int) $id]);
    }

    public function getReferenceTypes()
    {
        return [
            'Republic Act',
            'Presidential Decree',
            'Labor Code Provision',
            'Department Order',
            'Department Advisory',
            'Labor Advisory',
            'Wage Order',
            'Implementing Rules and Regulations',
            'Memorandum',
            'Administrative Issuance',
            'DOLE Issuance',
            'Other Legal Reference',
        ];
    }

    public function getStatuses()
    {
        return [
            'Active',
            'Amended',
            'Superseded',
            'Repealed',
            'Archived',
            'For Reference',
        ];
    }
}
