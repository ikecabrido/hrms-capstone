<?php

require_once __DIR__ . '/../models/DocumentationModel.php';

class DocumentationController extends ExitManagementController
{
    private DocumentationModel $documentationModel;

    public function __construct()
    {
        parent::__construct();
        $this->documentationModel = new DocumentationModel();
    }

    /**
     * Upload document
     */
    public function uploadDocument(array $data): array
    {
        try {
            error_log("=== DOCUMENT UPLOAD START ===");
            error_log("POST data: " . json_encode($data));
            error_log("FILES data: " . json_encode(array_keys($_FILES)));
            
            // Validate required fields
            $required = ['employee_id', 'document_type', 'title'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    error_log("Document validation failed: $field is required. Data: " . json_encode($data));
                    return ['success' => false, 'message' => "Field '$field' is required"];
                }
            }

            if (!empty($data['exit_case_type']) || !empty($data['exit_case_id'])) {
                if (empty($data['exit_case_type']) || empty($data['exit_case_id'])) {
                    return ['success' => false, 'message' => 'Both exit_case_type and exit_case_id are required when linking to an exit case'];
                }
                if (!in_array($data['exit_case_type'], ['resignation', 'termination'], true)) {
                    return ['success' => false, 'message' => 'Invalid exit_case_type'];
                }
                $data['exit_case_id'] = (int)$data['exit_case_id'];
            }

            // Handle file upload if present
            $filePath = $data['file_path'] ?? null;
            
            if (isset($_FILES['document_file']) && $_FILES['document_file']['error'] === UPLOAD_ERR_OK) {
                error_log("File upload detected: " . $_FILES['document_file']['name']);
                
                // Create documents directory if it doesn't exist
                $uploadDir = __DIR__ . '/../uploads/documents/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                    error_log("Created upload directory: $uploadDir");
                }

                $fileName = basename($_FILES['document_file']['name']);
                $filePath = 'uploads/documents/' . time() . '_' . $fileName;
                $fullPath = __DIR__ . '/../' . $filePath;

                error_log("Moving file to: $fullPath");
                
                // Move uploaded file
                if (!move_uploaded_file($_FILES['document_file']['tmp_name'], $fullPath)) {
                    error_log("Failed to move uploaded file from " . $_FILES['document_file']['tmp_name'] . " to: $fullPath");
                    return ['success' => false, 'message' => 'Failed to save uploaded file'];
                }
                error_log("File uploaded successfully to: $fullPath");
            } elseif (isset($_FILES['document_file'])) {
                error_log("File upload error code: " . $_FILES['document_file']['error']);
                return ['success' => false, 'message' => 'File upload error: ' . $_FILES['document_file']['error']];
            }

            // If no file was uploaded and no file_path provided, it's an error
            if (empty($filePath)) {
                error_log("No file provided - filePath is empty");
                return ['success' => false, 'message' => 'No file provided'];
            }

            $data['file_path'] = $filePath;

            // Set uploaded_by from session if available
            if (!isset($data['uploaded_by'])) {
                $data['uploaded_by'] = $_SESSION['employee_id'] ?? null;
            }

            error_log("About to insert document with data: " . json_encode($data));
            error_log("Will insert status='active' for this document");
            
            $documentId = $this->documentationModel->createDocument($data);
            
            if (!$documentId) {
                error_log("Document creation failed - returned 0 or false");
                return ['success' => false, 'message' => 'Failed to create document record'];
            }
            
            error_log("Document created with ID: $documentId, should be queryable now");

            return [
                'success' => true,
                'message' => 'Document uploaded successfully',
                'document_id' => $documentId
            ];
        } catch (Exception $e) {
            error_log("Document upload exception: " . $e->getMessage() . " | Trace: " . $e->getTraceAsString());
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    /**
     * Update document
     */
    public function updateDocument(array $data): array
    {
        try {
            if (empty($data['document_id'])) {
                return ['success' => false, 'message' => 'Document ID is required'];
            }

            // Validate required fields
            $required = ['employee_id', 'document_type', 'title'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    return ['success' => false, 'message' => "Field '$field' is required"];
                }
            }

            if (!empty($data['exit_case_type']) || !empty($data['exit_case_id'])) {
                if (empty($data['exit_case_type']) || empty($data['exit_case_id'])) {
                    return ['success' => false, 'message' => 'Both exit_case_type and exit_case_id are required when linking to an exit case'];
                }
                if (!in_array($data['exit_case_type'], ['resignation', 'termination'], true)) {
                    return ['success' => false, 'message' => 'Invalid exit_case_type'];
                }
                $data['exit_case_id'] = (int)$data['exit_case_id'];
            }

            $success = $this->documentationModel->updateDocument($data['document_id'], $data);

            if ($success) {
                return [
                    'success' => true,
                    'message' => 'Document updated successfully'
                ];
            } else {
                return ['success' => false, 'message' => 'Failed to update document'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Get employee documents
     */
    public function getEmployeeDocuments(string $employeeId): array
    {
        // Accept alphanumeric employee identifiers (some deployments use codes instead of numeric IDs)
        return $this->documentationModel->getDocumentsByEmployee($employeeId);
    }

    /**
     * Get documents linked to a specific exit case
     */
    public function getDocumentsByExitCase(string $exitCaseType, int $exitCaseId): array
    {
        return $this->documentationModel->getDocumentsByExitCase($exitCaseType, $exitCaseId);
    }

    /**
     * Check required documents status
     */
    public function checkRequiredDocuments(int $employeeId): array
    {
        return $this->documentationModel->checkRequiredDocuments($employeeId);
    }

    /**
     * Generate clearance checklist
     */
    public function generateClearanceChecklist(int $employeeId): array
    {
        return $this->documentationModel->generateClearanceChecklist($employeeId);
    }

    /**
     * Complete clearance
     */
    public function completeClearance(int $employeeId, string $department, int $completedBy): array
    {
        try {
            $success = $this->documentationModel->completeClearance($employeeId, $department, $completedBy);

            if ($success) {
                return [
                    'success' => true,
                    'message' => 'Clearance completed successfully'
                ];
            } else {
                return ['success' => false, 'message' => 'Failed to complete clearance'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Delete document
     */
    public function deleteDocument(int $documentId): array
    {
        try {
            $success = $this->documentationModel->deleteDocument($documentId);

            if ($success) {
                return [
                    'success' => true,
                    'message' => 'Document deleted successfully'
                ];
            } else {
                return ['success' => false, 'message' => 'Failed to delete document'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Get document types
     */
    public function getDocumentTypes(): array
    {
        return $this->documentationModel->getDocumentTypes();
    }

    /**
     * Get all documents with optional status filter
     */
    public function getDocuments(string $status = null): array
    {
        return $this->documentationModel->getAllDocuments($status);
    }

    /**
     * Get single document by ID
     */
    public function getDocument(int $documentId): array
    {
        $document = $this->documentationModel->getDocumentById($documentId);
        if (!$document) {
            return ['error' => 'Document not found'];
        }
        return $document;
    }

    /**
     * View document (return file path and document details)
     */
    public function viewDocument(int $documentId): array
    {
        try {
            $document = $this->documentationModel->getDocumentById($documentId);
            
            if (!$document) {
                return ['success' => false, 'message' => 'Document not found'];
            }

            // Return document details including file path for frontend to display
            return [
                'success' => true,
                'id' => $document['id'],
                'employee_id' => $document['employee_id'],
                'employee_name' => $document['employee_name'],
                'document_type' => $document['document_type'],
                'title' => $document['title'],
                'file_path' => $document['file_path'],
                'uploaded_by_name' => $document['uploaded_by_name'],
                'created_at' => $document['created_at'],
                'message' => 'Document retrieved successfully'
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    /**
     * Serve document file for inline preview/download.
     * Outputs headers and streams file content directly.
     */
    public function serveDocument(int $documentId): void
    {
        try {
            $document = $this->documentationModel->getDocumentById($documentId);
            if (!$document) {
                http_response_code(404);
                echo 'Document not found';
                exit;
            }

            // Resolve full path relative to exit_management directory
            $relative = $document['file_path'];
            $fullPath = realpath(__DIR__ . '/../' . $relative);
            if (!$fullPath || !file_exists($fullPath)) {
                http_response_code(404);
                echo 'File not found on server';
                exit;
            }

            // Basic security: ensure file is under the project directory
            $allowedBase = realpath(__DIR__ . '/../');
            if (strpos($fullPath, $allowedBase) !== 0) {
                http_response_code(403);
                echo 'Forbidden';
                exit;
            }

            // Determine mime type
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $fullPath) ?: 'application/octet-stream';
            finfo_close($finfo);

            // Send headers to force inline rendering when possible
            header('Content-Type: ' . $mime);
            header('Content-Length: ' . filesize($fullPath));
            header('Content-Disposition: inline; filename="' . basename($fullPath) . '"');
            header('Cache-Control: private, must-revalidate');

            // Stream file
            readfile($fullPath);
            exit;
        } catch (Exception $e) {
            http_response_code(500);
            error_log('Error serving document: ' . $e->getMessage());
            echo 'Error serving document';
            exit;
        }
    }

    /**
     * Download document (force file download)
     */
    public function downloadDocument(int $documentId): array
    {
        try {
            $document = $this->documentationModel->getDocumentById($documentId);
            
            if (!$document) {
                return ['success' => false, 'message' => 'Document not found'];
            }

            // Return file path for frontend to handle download
            // Frontend should use this file_path to download the file
            return [
                'success' => true,
                'file_path' => $document['file_path'],
                'title' => $document['title'],
                'message' => 'Document ready for download'
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    /**
     * Handle AJAX requests for documentation
     */
    public function handleAjaxRequest(string $action, array $data = []): array
    {
        switch ($action) {
            case 'upload_document':
            case 'upload_documentation':
            case 'submit_document':
                return $this->uploadDocument($data);

            case 'update_document':
            case 'update_documentation':
                return $this->updateDocument($data);

            case 'get_employee_documents':
                // pass through the provided employee identifier (may be alphanumeric)
                return $this->getEmployeeDocuments($data['employee_id'] ?? '');

            case 'check_required_documents':
                return $this->checkRequiredDocuments($data['employee_id'] ?? 0);

            case 'generate_clearance_checklist':
                return $this->generateClearanceChecklist($data['employee_id'] ?? 0);

            case 'complete_clearance':
                return $this->completeClearance(
                    $data['employee_id'] ?? 0,
                    $data['department'] ?? '',
                    $data['completed_by'] ?? 0
                );

            case 'delete_document':
                return $this->deleteDocument($data['document_id'] ?? 0);

            case 'get_document':
                return $this->getDocument($data['document_id'] ?? 0);

            case 'get_document_types':
                return $this->getDocumentTypes();

            case 'get_documents':
                return $this->documentationModel->getAllDocuments(
                    $data['status'] ?? null,
                    $data['page'] ?? 1,
                    $data['limit'] ?? 10,
                    $data['search'] ?? ''
                );

            case 'get_documents_by_exit_case':
                return $this->getDocumentsByExitCase($data['exit_case_type'] ?? '', (int)($data['exit_case_id'] ?? 0));

            case 'view_document':
                return $this->viewDocument($data['document_id'] ?? 0);

            case 'download_document':
                return $this->downloadDocument($data['document_id'] ?? 0);

            case 'archive_document':
                return $this->archiveDocument($data['document_id'] ?? 0);

            case 'unarchive_document':
                return $this->unarchiveDocument($data['document_id'] ?? 0);

            case 'get_document_details':
                return $this->getDocumentDetails($data['document_id'] ?? 0);

            default:
                return parent::handleAjaxRequest($action, $data);
        }
    }

    /**
     * Archive document
     */
    public function archiveDocument(int $documentId): array
    {
        try {
            $archiveReason = $_POST['archive_reason'] ?? 'Manual archive';
            $success = $this->documentationModel->archiveDocument($documentId, $archiveReason);

            if ($success) {
                return [
                    'success' => true,
                    'message' => 'Document archived successfully'
                ];
            } else {
                return ['success' => false, 'message' => 'Failed to archive document'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Unarchive document
     */
    public function unarchiveDocument(int $documentId): array
    {
        try {
            $success = $this->documentationModel->unarchiveDocument($documentId);

            if ($success) {
                return [
                    'success' => true,
                    'message' => 'Document unarchived successfully'
                ];
            } else {
                return ['success' => false, 'message' => 'Failed to unarchive document'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Get document details for archiving
     */
    private function getDocumentDetails(int $documentId): array
    {
        try {
            if (empty($documentId)) {
                return [
                    'success' => false,
                    'message' => 'Document ID is required'
                ];
            }

            $document = $this->documentationModel->getDocument($documentId);

            if (!$document) {
                return [
                    'success' => false,
                    'message' => 'Document not found'
                ];
            }

            // Get employee name
            $employee = $this->documentationModel->getEmployeeById($document['employee_id']);

            return [
                'success' => true,
                'data' => [
                    'id' => $document['id'],
                    'employee_id' => $document['employee_id'],
                    'employee_name' => $employee ? $employee['first_name'] . ' ' . $employee['last_name'] : 'Unknown',
                    'document_type' => $document['document_type'],
                    'title' => $document['title'],
                    'created_at' => $document['created_at']
                ]
            ];
        } catch (Exception $e) {
            error_log("Error getting document details: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'An error occurred while retrieving document details'
            ];
        }
    }
}