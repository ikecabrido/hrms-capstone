
<?php

require_once __DIR__ . '/../database/db.php';

class Documents
{
    private $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    /**
     * Create a document record.
     *
     * document_name  = original filename uploaded by applicant
     * file_name      = actual generated filename stored on server
     * file_path      = relative path from recruitment module
     * file_extension = pdf/doc/docx
     * file_size      = file size in bytes
     *
     * @param int         $applicationId
     * @param string      $documentType
     * @param string      $fileName
     * @param string|null $filePath
     * @param int|null    $fileSize
     *
     * @return int
     */
    public function create(
        int $applicationId,
        string $documentType,
        string $fileName,
        ?string $filePath = null,
        ?int $fileSize = null
    ) {
        /**
         * If no path was supplied,
         * use the filename.
         */
        $filePath = $filePath ?: $fileName;

        /**
         * Get extension from the original filename.
         *
         * Example:
         * sample_resume.pdf
         * becomes:
         * pdf
         */
        $fileExtension = strtolower(
            pathinfo(
                $fileName,
                PATHINFO_EXTENSION
            )
        );

        /**
         * Get only the actual filename
         * from the relative path.
         *
         * Example:
         *
         * uploads/2/resume/resume_20260922_140012_a1b2c3d4.pdf
         *
         * becomes:
         *
         * resume_20260922_140012_a1b2c3d4.pdf
         */
        $storedFileName = basename($filePath);

        /**
         * Insert document record.
         */
        $stmt = $this->db->prepare('
            INSERT INTO rao_documents
            (
                application_id,
                document_type,
                document_name,
                file_name,
                file_path,
                file_extension,
                file_size,
                uploaded_at
            )
            VALUES
            (
                :application_id,
                :document_type,
                :document_name,
                :file_name,
                :file_path,
                :file_extension,
                :file_size,
                NOW()
            )
        ');

        $stmt->execute([
            ':application_id' =>
            $applicationId,

            ':document_type' =>
            $documentType,

            // Original filename
            ':document_name' =>
            $fileName,

            // Actual generated filename
            ':file_name' =>
            $storedFileName,

            // Relative server path
            ':file_path' =>
            $filePath,

            // Extension
            ':file_extension' =>
            $fileExtension !== ''
                ? $fileExtension
                : null,

            // Size in bytes
            ':file_size' =>
            $fileSize
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Get all documents belonging to an application.
     *
     * @param int $applicationId
     * @return array
     */
    public function findByApplicationId(
        int $applicationId
    ) {
        $stmt = $this->db->prepare('
            SELECT *
            FROM rao_documents
            WHERE application_id = ?
            ORDER BY document_id ASC
        ');

        $stmt->execute([
            $applicationId
        ]);

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    /**
     * Get one document by ID.
     *
     * @param int $documentId
     * @return array|false
     */
    public function findById(
        int $documentId
    ) {
        $stmt = $this->db->prepare('
            SELECT *
            FROM rao_documents
            WHERE document_id = ?
            LIMIT 1
        ');

        $stmt->execute([
            $documentId
        ]);

        return $stmt->fetch(
            PDO::FETCH_ASSOC
        );
    }

    /**
     * Delete one document.
     *
     * @param int $documentId
     * @return bool
     */
    public function delete(
        int $documentId
    ) {
        $stmt = $this->db->prepare(
            'DELETE FROM rao_documents WHERE document_id = ?'
        );

        return $stmt->execute([
            $documentId
        ]);
    }

    /**
     * Delete all documents belonging to an application.
     *
     * @param int $applicationId
     * @return bool
     */
    public function deleteByApplicationId(
        int $applicationId
    ) {
        $stmt = $this->db->prepare(
            'DELETE FROM rao_documents WHERE application_id = ?'
        );

        return $stmt->execute([
            $applicationId
        ]);
    }
    /**
     * =========================================================
     * GET DOCUMENTS BY APPLICATION ID
     * =========================================================
     */
    public function getByApplicationId(int $applicationId): array
    {
        if ($applicationId <= 0) {
            return [];
        }

        $stmt = $this->db->prepare("
        SELECT
            document_id,
            application_id,
            document_type,
            document_name,
            file_name,
            file_path,
            file_extension,
            file_size,
            uploaded_at
        FROM rao_documents
        WHERE application_id = :application_id
        ORDER BY uploaded_at DESC, document_id DESC
    ");

        $stmt->execute([
            ':application_id' => $applicationId
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
