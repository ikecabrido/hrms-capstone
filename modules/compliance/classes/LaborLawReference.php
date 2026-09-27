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
            $words = array_values(array_filter(array_map('trim', explode(' ', $filters['search'])), function ($word) {
                return $word !== '' && preg_match('/[a-zA-Z0-9]/', $word);
            }));
            if (!empty($words)) {
                $sql .= ' AND (';
                $first = true;
                foreach ($words as $i => $word) {
                    if (!$first) $sql .= ' OR ';
                    $sql .= '(' .
                        'r.title LIKE :search' . $i .
                        ' OR r.short_title LIKE :search' . $i .
                        ' OR r.reference_number LIKE :search' . $i .
                        ' OR r.keywords LIKE :search' . $i .
                        ' OR r.issuing_authority LIKE :search' . $i .
                        ' OR r.description LIKE :search' . $i .
                    ')';
                    $params[':search' . $i] = '%' . $word . '%';
                    $first = false;
                }
                $sql .= ')';
            }
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

    public function searchReferencesForAssistant(string $query, int $limit = 5): array
    {
        $query = trim($query);
        if ($query === '') {
            return [];
        }

        $words = preg_split('/\s+/', $query, -1, PREG_SPLIT_NO_EMPTY);
        if (empty($words)) {
            return [];
        }

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
                (
        ";

        $scoreParts = [];
        $params = [];
        $whereParts = [];
        $idx = 0;
        foreach ($words as $word) {
            $idx++;
            $p = ":w{$idx}";
            $params[$p] = "%{$word}%";
            $scoreParts[] = "(CASE WHEN r.title LIKE {$p} THEN 5 ELSE 0 END)";
            $scoreParts[] = "(CASE WHEN r.keywords LIKE {$p} THEN 4 ELSE 0 END)";
            $scoreParts[] = "(CASE WHEN r.short_title LIKE {$p} THEN 3 ELSE 0 END)";
            $scoreParts[] = "(CASE WHEN r.reference_number LIKE {$p} THEN 2 ELSE 0 END)";
            $scoreParts[] = "(CASE WHEN r.description LIKE {$p} THEN 1 ELSE 0 END)";
            $scoreParts[] = "(CASE WHEN r.summary LIKE {$p} THEN 1 ELSE 0 END)";
            $whereParts[] = "(r.title LIKE {$p} OR r.keywords LIKE {$p} OR r.short_title LIKE {$p} OR r.reference_number LIKE {$p} OR r.description LIKE {$p} OR r.summary LIKE {$p})";
        }

        $sql .= implode(" + ", $scoreParts);
        $sql .= ") AS relevance FROM {$this->table} r LEFT JOIN {$this->categoriesTable} c ON c.id = r.category_id WHERE ";
        $sql .= implode(" AND ", $whereParts);
        $sql .= " HAVING relevance > 0 ORDER BY relevance DESC, r.date_issued DESC, r.id DESC LIMIT " . (int) $limit;

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getFallbackInfo(string $topic): array
    {
        $map = [
            'salary' => [
                'agency' => 'National Wages and Productivity Commission',
                'url' => 'https://nwpc.dole.gov.ph/',
                'why' => 'The NWPC sets and oversees regional minimum wages and wage-order policies.'
            ],
            'leave' => [
                'agency' => 'Department of Labor and Employment',
                'url' => 'https://dole.gov.ph/',
                'why' => 'DOLE administers leave laws and can clarify specific leave entitlements.'
            ],
            'safety' => [
                'agency' => 'Occupational Safety and Health Center',
                'url' => 'https://oshc.dole.gov.ph/',
                'why' => 'The OSHC handles workplace safety standards and occupational health concerns.'
            ],
            'harassment' => [
                'agency' => 'Department of Labor and Employment',
                'url' => 'https://dole.gov.ph/',
                'why' => 'DOLE handles workplace harassment complaints and can provide guidance.'
            ],
            'termination' => [
                'agency' => 'National Labor Relations Commission',
                'url' => 'https://nlrc.dole.gov.ph/',
                'why' => 'The NLRC handles labor disputes, termination cases, and separation pay issues.'
            ],
            'contributions' => [
                'agency' => 'Department of Labor and Employment',
                'url' => 'https://dole.gov.ph/',
                'why' => 'DOLE oversees compliance with government contribution requirements.'
            ],
            'foreign' => [
                'agency' => 'Department of Labor and Employment',
                'url' => 'https://dole.gov.ph/',
                'why' => 'DOLE governs Alien Employment Permits and foreign worker regulations.'
            ],
            'contract' => [
                'agency' => 'Department of Labor and Employment',
                'url' => 'https://dole.gov.ph/',
                'why' => 'DOLE can provide guidance on employment contract requirements.'
            ],
            'documents' => [
                'agency' => 'Department of Labor and Employment',
                'url' => 'https://dole.gov.ph/',
                'why' => 'DOLE oversees employment documentation and onboarding requirements.'
            ],
            'labor_relations' => [
                'agency' => 'Department of Labor and Employment',
                'url' => 'https://dole.gov.ph/',
                'why' => 'DOLE handles labor relations, unions, and collective bargaining matters.'
            ],
            'privacy' => [
                'agency' => 'National Privacy Commission',
                'url' => 'https://privacy.gov.ph/',
                'why' => 'The NPC oversees data privacy compliance and personal data protection.'
            ],
            'mental_health' => [
                'agency' => 'Department of Labor and Employment',
                'url' => 'https://dole.gov.ph/',
                'why' => 'DOLE can provide guidance on workplace mental health programs.'
            ],
            'benefits' => [
                'agency' => 'Department of Labor and Employment',
                'url' => 'https://dole.gov.ph/',
                'why' => 'DOLE administers statutory benefits and can clarify benefit entitlements.'
            ],
            'working_hours' => [
                'agency' => 'Department of Labor and Employment',
                'url' => 'https://dole.gov.ph/',
                'why' => 'DOLE enforces working hours, overtime, and rest day rules.'
            ],
            'general' => [
                'agency' => 'Department of Labor and Employment',
                'url' => 'https://dole.gov.ph/',
                'why' => 'DOLE is the primary agency for labor and employment concerns.'
            ],
        ];

        return $map[$topic] ?? $map['general'];
    }

    public static function getAgencyForQuery(string $query): array
    {
        $lower = strtolower($query);

        if (preg_match('/minimum wage|wage order|salary rate|pay rate|compensation rate|nwpc/', $lower)) {
            return [
                'agency' => 'National Wages and Productivity Commission',
                'url' => 'https://nwpc.dole.gov.ph/',
                'why' => 'The NWPC sets regional minimum wages and issues Wage Orders.'
            ];
        }

        if (preg_match('/sss|social security/', $lower)) {
            return [
                'agency' => 'Social Security System',
                'url' => 'https://www.sss.gov.ph/',
                'why' => 'SSS manages social security contributions and benefits.'
            ];
        }

        if (preg_match('/philhealth|health insurance/', $lower)) {
            return [
                'agency' => 'PhilHealth',
                'url' => 'https://www.philhealth.gov.ph/',
                'why' => 'PhilHealth manages health insurance contributions and coverage.'
            ];
        }

        if (preg_match('/pag-?ibig|housing/', $lower)) {
            return [
                'agency' => 'Pag-IBIG Fund',
                'url' => 'https://www.pagibigfund.gov.ph/',
                'why' => 'Pag-IBIG manages housing fund contributions and benefits.'
            ];
        }

        if (preg_match('/bir|tax|income tax|withholding tax/', $lower)) {
            return [
                'agency' => 'Bureau of Internal Revenue',
                'url' => 'https://www.bir.gov.ph/',
                'why' => 'The BIR administers national taxes, including withholding tax on compensation.'
            ];
        }

        if (preg_match('/labor dispute|filing|case|complaint|nlrc|arbitration|appeal|separation pay dispute|termination dispute/', $lower)) {
            return [
                'agency' => 'National Labor Relations Commission',
                'url' => 'https://nlrc.dole.gov.ph/',
                'why' => 'The NLRC adjudicates labor disputes and employment cases.'
            ];
        }

        if (preg_match('/conciliation|mediation|sena|single entry|dispute resolution/', $lower)) {
            return [
                'agency' => 'National Conciliation and Mediation Board',
                'url' => 'https://ncmb.gov.ph/',
                'why' => 'The NCMB handles labor conciliation and mediation.'
            ];
        }

        if (preg_match('/safety|injury|accident|ppe|hazard|occupational|ecc|employees compensation/', $lower)) {
            return [
                'agency' => 'Occupational Safety and Health Center',
                'url' => 'https://oshc.dole.gov.ph/',
                'why' => 'The OSHC handles workplace safety standards and occupational health concerns.'
            ];
        }

        if (preg_match('/data privacy|personal data|breach|consent|privacy/', $lower)) {
            return [
                'agency' => 'National Privacy Commission',
                'url' => 'https://privacy.gov.ph/',
                'why' => 'The NPC oversees data privacy compliance and personal data protection.'
            ];
        }

        if (preg_match('/legal query|legal opinion|legal advice/', $lower)) {
            return [
                'agency' => 'DOLE Legal Query Portal',
                'url' => 'https://query.dole.gov.ph/',
                'why' => 'The DOLE Legal Query Portal provides legal opinions on labor matters.'
            ];
        }

        return [
            'agency' => 'Department of Labor and Employment',
            'url' => 'https://dole.gov.ph/',
            'why' => 'DOLE is the primary agency for labor and employment concerns.'
        ];
    }

    public function isLikelyOutdated(array $reference): bool
    {
        $status = strtolower($reference['status'] ?? '');
        if (in_array($status, ['repealed', 'superseded', 'archived'], true)) {
            return true;
        }

        if (!empty($reference['date_issued'])) {
            $year = (int) date('Y', strtotime($reference['date_issued']));
            if ($year < 2018) {
                return true;
            }
        }

        $lower = strtolower(($reference['summary'] ?? '') . ' ' . ($reference['remarks'] ?? ''));
        if (preg_match('/(historical|amended|superseded|repealed|no longer current|outdated|not current|replaced by|should not be used as current basis)/i', $lower)) {
            return true;
        }

        return false;
    }
}
