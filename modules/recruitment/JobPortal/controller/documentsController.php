<?php

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../classes/Documents.php';
require_once __DIR__ . '/../classes/Application.php';


/**
 * =========================================================
 * HRMS RECRUITMENT UPLOAD DIRECTORY
 * =========================================================
 *
 * documentController.php belongs to:
 *
 * C:\xampp\htdocs\JobPortal\
 *
 * But recruitment documents belong to:
 *
 * C:\xampp\htdocs\hrms-capstone\modules\recruitment\uploads\
 *
 * IMPORTANT:
 *
 * Do NOT use:
 *
 * __DIR__ . '/uploads/'
 *
 * because that would point to JobPortal.
 */
define(
    'HRMS_RECRUITMENT_UPLOADS',
    'C:\\xampp\\htdocs\\hrms-capstone\\modules\\recruitment\\uploads'
);


/**
 * =========================================================
 * JSON RESPONSE
 * =========================================================
 */
function respond($data, int $status = 200): void
{
    http_response_code($status);

    echo json_encode(
        $data,
        JSON_UNESCAPED_UNICODE |
            JSON_UNESCAPED_SLASHES
    );

    exit;
}


/**
 * =========================================================
 * INPUT VALUE
 * =========================================================
 */
function inputValue(
    array $data,
    string $key,
    $default = ''
) {
    if (!isset($data[$key])) {
        return $default;
    }

    if (is_string($data[$key])) {
        return trim($data[$key]);
    }

    return $data[$key];
}


/**
 * =========================================================
 * SANITIZE FILE NAME
 * =========================================================
 */
function sanitizeFileName(string $fileName): string
{
    /**
     * Remove any directory information.
     */
    $fileName = basename($fileName);


    /**
     * Get extension.
     */
    $extension = strtolower(
        pathinfo(
            $fileName,
            PATHINFO_EXTENSION
        )
    );


    /**
     * Get base filename.
     */
    $baseName = pathinfo(
        $fileName,
        PATHINFO_FILENAME
    );


    /**
     * Replace unsafe characters.
     */
    $baseName = preg_replace(
        '/[^a-zA-Z0-9_-]/',
        '_',
        $baseName
    );


    /**
     * Collapse repeated underscores.
     */
    $baseName = preg_replace(
        '/_+/',
        '_',
        $baseName
    );


    /**
     * Remove leading/trailing underscores.
     */
    $baseName = trim(
        $baseName,
        '_'
    );


    /**
     * Fallback.
     */
    if ($baseName === '') {
        $baseName = 'document';
    }


    /**
     * Rebuild filename.
     */
    if ($extension !== '') {
        return $baseName . '.' . $extension;
    }


    return $baseName;
}


/**
 * =========================================================
 * CREATE APPLICANT DIRECTORY
 * =========================================================
 */
function createApplicantDirectory(
    int $applicationId
): string {

    if ($applicationId <= 0) {
        throw new RuntimeException(
            'Invalid application ID.'
        );
    }


    /**
     * Main uploads directory.
     */
    $uploadRoot =
        HRMS_RECRUITMENT_UPLOADS;


    /**
     * Create root directory if missing.
     */
    if (!is_dir($uploadRoot)) {

        if (
            !mkdir(
                $uploadRoot,
                0755,
                true
            ) &&
            !is_dir($uploadRoot)
        ) {
            throw new RuntimeException(
                'Unable to create recruitment uploads directory: ' .
                    $uploadRoot
            );
        }
    }


    /**
     * Applicant directory.
     *
     * Example:
     *
     * C:\xampp\htdocs\hrms-capstone\modules\
     * recruitment\uploads\14\
     */
    $applicantDir =
        $uploadRoot .
        DIRECTORY_SEPARATOR .
        $applicationId;


    /**
     * Create applicant directory.
     */
    if (!is_dir($applicantDir)) {

        if (
            !mkdir(
                $applicantDir,
                0755,
                true
            ) &&
            !is_dir($applicantDir)
        ) {
            throw new RuntimeException(
                'Unable to create applicant directory: ' .
                    $applicantDir
            );
        }
    }


    /**
     * Verify.
     */
    if (!is_dir($applicantDir)) {
        throw new RuntimeException(
            'Applicant directory could not be created: ' .
                $applicantDir
        );
    }


    /**
     * Verify writable.
     */
    if (!is_writable($applicantDir)) {
        throw new RuntimeException(
            'Applicant directory is not writable: ' .
                $applicantDir
        );
    }


    return $applicantDir;
}


/**
 * =========================================================
 * UPLOAD DOCUMENT
 * =========================================================
 */
function uploadDocument(
    Documents $documentsModel,
    int $applicationId,
    string $documentType,
    array $file
): array {

    /**
     * -----------------------------------------------------
     * 1. Upload error
     * -----------------------------------------------------
     */
    if (
        !isset($file['error']) ||
        $file['error'] !== UPLOAD_ERR_OK
    ) {

        $errorCode =
            $file['error'] ?? 'unknown';

        throw new RuntimeException(
            "Failed to upload {$documentType}. " .
                "Upload error code: {$errorCode}"
        );
    }


    /**
     * -----------------------------------------------------
     * 2. Validate uploaded file
     * -----------------------------------------------------
     */
    if (
        !isset($file['tmp_name']) ||
        !is_uploaded_file($file['tmp_name'])
    ) {

        throw new RuntimeException(
            "Invalid uploaded file for {$documentType}."
        );
    }


    /**
     * -----------------------------------------------------
     * 3. Original filename
     * -----------------------------------------------------
     */
    $originalName =
        basename(
            $file['name'] ?? ''
        );


    if ($originalName === '') {
        throw new RuntimeException(
            "The {$documentType} file has no filename."
        );
    }


    /**
     * -----------------------------------------------------
     * 4. File size
     * -----------------------------------------------------
     */
    $fileSize =
        isset($file['size'])
        ? (int) $file['size']
        : 0;


    /**
     * -----------------------------------------------------
     * 5. Extension
     * -----------------------------------------------------
     */
    $extension =
        strtolower(
            pathinfo(
                $originalName,
                PATHINFO_EXTENSION
            )
        );


    /**
     * -----------------------------------------------------
     * 6. Allowed file extensions
     * -----------------------------------------------------
     */
    $allowedExtensions = [
        'pdf',
        'doc',
        'docx'
    ];


    if (
        !in_array(
            $extension,
            $allowedExtensions,
            true
        )
    ) {

        throw new RuntimeException(
            "Invalid {$documentType} file type. " .
                "Only PDF, DOC, and DOCX files are allowed."
        );
    }


    /**
     * -----------------------------------------------------
     * 7. Maximum file size
     * -----------------------------------------------------
     *
     * 10 MB.
     */
    $maxFileSize =
        10 * 1024 * 1024;


    if ($fileSize > $maxFileSize) {

        throw new RuntimeException(
            "{$documentType} file is too large. " .
                "Maximum file size is 10 MB."
        );
    }


    /**
     * -----------------------------------------------------
     * 8. Create applicant directory
     * -----------------------------------------------------
     */
    $applicantDir =
        createApplicantDirectory(
            $applicationId
        );


    /**
     * -----------------------------------------------------
     * 9. Sanitize filename
     * -----------------------------------------------------
     */
    $newFileName =
        sanitizeFileName(
            $originalName
        );


    /**
     * -----------------------------------------------------
     * 10. Build physical destination
     * -----------------------------------------------------
     */
    $destination =
        $applicantDir .
        DIRECTORY_SEPARATOR .
        $newFileName;


    /**
     * -----------------------------------------------------
     * 11. Prevent duplicate filename
     * -----------------------------------------------------
     */
    if (file_exists($destination)) {

        $baseName =
            pathinfo(
                $newFileName,
                PATHINFO_FILENAME
            );

        $fileExtension =
            pathinfo(
                $newFileName,
                PATHINFO_EXTENSION
            );


        $newFileName =
            $baseName .
            '_' .
            date('Ymd_His') .
            '_' .
            bin2hex(
                random_bytes(4)
            );


        if ($fileExtension !== '') {
            $newFileName .=
                '.' .
                $fileExtension;
        }


        $destination =
            $applicantDir .
            DIRECTORY_SEPARATOR .
            $newFileName;
    }


    /**
     * -----------------------------------------------------
     * 12. Move uploaded file
     * -----------------------------------------------------
     */
    if (
        !move_uploaded_file(
            $file['tmp_name'],
            $destination
        )
    ) {

        throw new RuntimeException(
            "Unable to move uploaded {$documentType} " .
                "file to: {$destination}"
        );
    }


    /**
     * -----------------------------------------------------
     * 13. Verify physical file
     * -----------------------------------------------------
     */
    if (!file_exists($destination)) {

        throw new RuntimeException(
            'Uploaded file could not be verified: ' .
                $destination
        );
    }


    /**
     * -----------------------------------------------------
     * 14. Database-relative path
     * -----------------------------------------------------
     *
     * CORRECT:
     *
     * uploads/14/resume.pdf
     *
     * WRONG:
     *
     * C:\xampp\htdocs\hrms-capstone\...
     *
     * WRONG:
     *
     * hrms-capstone/modules/recruitment/...
     *
     * WRONG:
     *
     * uploads/resumes/14/...
     */
    $relativePath =
        'uploads/' .
        $applicationId .
        '/' .
        $newFileName;


    /**
     * -----------------------------------------------------
     * 15. Save database metadata
     * -----------------------------------------------------
     */
    try {

        $documentId =
            $documentsModel->create(
                $applicationId,
                $documentType,
                $originalName,
                $relativePath,
                $fileSize
            );
    } catch (Throwable $e) {

        /**
         * Remove physical file if database
         * operation failed.
         */
        if (file_exists($destination)) {
            @unlink($destination);
        }

        throw $e;
    }


    /**
     * -----------------------------------------------------
     * 16. Synchronize application resume
     * -----------------------------------------------------
     */
    if ($documentType === 'resume') {

        $applicationModel =
            new Application();


        $updated =
            $applicationModel->updateResumePath(
                $applicationId,
                $relativePath
            );


        if (!$updated) {

            /**
             * Remove DB document record and physical
             * file if the resume path cannot be updated.
             */
            try {
                $documentsModel->delete(
                    (int) $documentId
                );
            } catch (Throwable $deleteError) {
                error_log(
                    'Unable to rollback document record: ' .
                        $deleteError->getMessage()
                );
            }


            if (file_exists($destination)) {
                @unlink($destination);
            }


            throw new RuntimeException(
                'Resume uploaded, but rao_applications.resume ' .
                    'could not be updated.'
            );
        }
    }


    /**
     * -----------------------------------------------------
     * 17. Return information
     * -----------------------------------------------------
     */
    return [

        'document_id' =>
        $documentId,

        'application_id' =>
        $applicationId,

        'document_type' =>
        $documentType,

        'original_name' =>
        $originalName,

        'file_name' =>
        $newFileName,

        'file_path' =>
        $relativePath,

        'file_extension' =>
        $extension,

        'file_size' =>
        $fileSize,

        'physical_path' =>
        $destination
    ];
}


/**
 * =========================================================
 * DELETE PHYSICAL DOCUMENT
 * =========================================================
 */
function deletePhysicalDocument(
    string $filePath
): bool {

    /**
     * Only allow relative recruitment upload paths.
     *
     * Expected:
     *
     * uploads/14/resume.pdf
     */
    $filePath =
        str_replace(
            '\\',
            '/',
            trim($filePath)
        );


    if ($filePath === '') {
        return false;
    }


    /**
     * Security:
     *
     * Do not allow:
     *
     * ../
     *
     * absolute Windows paths
     *
     * unrelated directories.
     */
    if (
        strpos($filePath, '..') !== false ||
        preg_match(
            '/^[A-Za-z]:\//',
            $filePath
        )
    ) {
        throw new RuntimeException(
            'Invalid document path.'
        );
    }


    /**
     * Must start with uploads/.
     */
    if (
        strpos(
            $filePath,
            'uploads/'
        ) !== 0
    ) {
        throw new RuntimeException(
            'Invalid recruitment document path.'
        );
    }


    /**
     * Remove uploads/ prefix.
     */
    $relativeFile =
        substr(
            $filePath,
            strlen('uploads/')
        );


    /**
     * Build physical path.
     */
    $physicalPath =
        HRMS_RECRUITMENT_UPLOADS .
        DIRECTORY_SEPARATOR .
        str_replace(
            '/',
            DIRECTORY_SEPARATOR,
            $relativeFile
        );


    /**
     * Verify it is actually inside the recruitment
     * uploads directory.
     */
    $rootRealPath =
        realpath(
            HRMS_RECRUITMENT_UPLOADS
        );


    $fileRealPath =
        file_exists($physicalPath)
        ? realpath($physicalPath)
        : false;


    if (
        $rootRealPath === false ||
        $fileRealPath === false
    ) {
        return false;
    }


    $rootPrefix =
        rtrim(
            $rootRealPath,
            DIRECTORY_SEPARATOR
        ) .
        DIRECTORY_SEPARATOR;


    if (
        strpos(
            $fileRealPath,
            $rootPrefix
        ) !== 0
    ) {
        throw new RuntimeException(
            'Document is outside the recruitment upload directory.'
        );
    }


    return @unlink(
        $fileRealPath
    );
}


/**
 * =========================================================
 * MODELS
 * =========================================================
 */

$documentsModel =
    new Documents();

$applicationModel =
    new Application();


/**
 * =========================================================
 * REQUEST INFORMATION
 * =========================================================
 */

$method =
    $_SERVER['REQUEST_METHOD'] ?? 'GET';

$action =
    $_GET['action'] ?? '';


try {

    /**
     * =====================================================
     * UPLOAD DOCUMENT
     *
     * POST:
     *
     * documentController.php?action=upload
     *
     * =====================================================
     */
    if (
        $method === 'POST' &&
        (
            $action === 'upload' ||
            $action === 'documents'
        )
    ) {

        /**
         * Application ID.
         */
        $applicationId =
            (int) (
                $_POST['application_id'] ??
                $_POST['applicationId'] ??
                0
            );


        if ($applicationId <= 0) {

            respond([
                'success' =>
                false,

                'message' =>
                'Invalid application ID.'
            ], 400);
        }


        /**
         * Make sure application exists.
         */
        $application =
            $applicationModel->findById(
                $applicationId
            );


        if (!$application) {

            respond([
                'success' =>
                false,

                'message' =>
                'Application not found.'
            ], 404);
        }


        /**
         * Supported document fields.
         */
        $documentTypes = [
            'resume',
            'diploma',
            'tor',
            'certs',
            'other'
        ];


        $uploadedDocuments = [];


        /**
         * Process each possible document.
         */
        foreach (
            $documentTypes
            as $documentType
        ) {

            /**
             * Skip fields that do not exist.
             */
            if (
                !isset(
                    $_FILES[$documentType]
                )
            ) {
                continue;
            }


            /**
             * Skip empty file field.
             */
            if (
                ($_FILES[$documentType]['error'] ?? null)
                === UPLOAD_ERR_NO_FILE
            ) {
                continue;
            }


            /**
             * Upload.
             */
            $uploaded =
                uploadDocument(
                    $documentsModel,
                    $applicationId,
                    $documentType,
                    $_FILES[$documentType]
                );


            $uploadedDocuments[$documentType] =
                $uploaded;
        }


        /**
         * No files uploaded.
         */
        if (empty($uploadedDocuments)) {

            respond([
                'success' =>
                false,

                'message' =>
                'No documents were uploaded.'
            ], 400);
        }


        respond([
            'success' =>
            true,

            'message' =>
            'Documents uploaded successfully.',

            'application_id' =>
            $applicationId,

            'applicant_folder' =>
            'uploads/' .
                $applicationId .
                '/',

            'documents' =>
            $uploadedDocuments
        ], 201);
    }


    /**
     * =====================================================
     * LIST DOCUMENTS
     *
     * GET:
     *
     * documentController.php?action=list
     * &application_id=14
     *
     * =====================================================
     */
    if (
        $method === 'GET' &&
        (
            $action === 'list' ||
            $action === 'documents'
        )
    ) {

        $applicationId =
            (int) (
                $_GET['application_id'] ??
                $_GET['applicationId'] ??
                0
            );


        if ($applicationId <= 0) {

            respond([
                'success' =>
                false,

                'message' =>
                'Invalid application ID.'
            ], 400);
        }


        $documents =
            $documentsModel->getByApplicationId(
                $applicationId
            );


        respond([
            'success' =>
            true,

            'application_id' =>
            $applicationId,

            'documents' =>
            $documents
        ]);
    }


    /**
     * =====================================================
     * VIEW DOCUMENT
     *
     * GET:
     *
     * documentController.php?action=view
     * &document_id=1
     *
     * =====================================================
     */
    if (
        $method === 'GET' &&
        $action === 'view'
    ) {

        $documentId =
            (int) (
                $_GET['document_id'] ??
                $_GET['documentId'] ??
                0
            );


        if ($documentId <= 0) {

            respond([
                'success' =>
                false,

                'message' =>
                'Invalid document ID.'
            ], 400);
        }


        $document =
            $documentsModel->findById(
                $documentId
            );


        if (!$document) {

            respond([
                'success' =>
                false,

                'message' =>
                'Document not found.'
            ], 404);
        }


        $filePath =
            $document['file_path'] ?? '';


        /**
         * Convert stored forward-slash path
         * to Windows path.
         */
        $filePath =
            str_replace(
                '/',
                DIRECTORY_SEPARATOR,
                $filePath
            );


        /**
         * Validate path format.
         */
        if (
            strpos(
                str_replace(
                    DIRECTORY_SEPARATOR,
                    '/',
                    $filePath
                ),
                'uploads/'
            ) !== 0
        ) {

            respond([
                'success' =>
                false,

                'message' =>
                'Invalid document path.'
            ], 400);
        }


        /**
         * Remove uploads prefix.
         */
        $relativeFile =
            substr(
                str_replace(
                    '/',
                    DIRECTORY_SEPARATOR,
                    $filePath
                ),
                strlen(
                    'uploads' .
                        DIRECTORY_SEPARATOR
                )
            );


        /**
         * Physical file.
         */
        $physicalPath =
            HRMS_RECRUITMENT_UPLOADS .
            DIRECTORY_SEPARATOR .
            $relativeFile;


        /**
         * File must exist.
         */
        if (
            !file_exists($physicalPath) ||
            !is_file($physicalPath)
        ) {

            respond([
                'success' =>
                false,

                'message' =>
                'Physical document file was not found.',

                'file_path' =>
                $filePath,

                'physical_path' =>
                $physicalPath
            ], 404);
        }


        /**
         * Detect MIME type.
         */
        $mimeType =
            function_exists('mime_content_type')
            ? mime_content_type($physicalPath)
            : 'application/octet-stream';


        /**
         * Safe MIME whitelist.
         */
        $allowedMimeTypes = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        ];


        if (
            !in_array(
                $mimeType,
                $allowedMimeTypes,
                true
            )
        ) {
            $mimeType =
                'application/octet-stream';
        }


        /**
         * Return file to browser.
         */
        header(
            'Content-Type: ' .
                $mimeType
        );

        header(
            'Content-Length: ' .
                filesize($physicalPath)
        );

        header(
            'Content-Disposition: inline; filename="' .
                basename($physicalPath) .
                '"'
        );

        header(
            'X-Content-Type-Options: nosniff'
        );


        readfile(
            $physicalPath
        );

        exit;
    }


    /**
     * =====================================================
     * DELETE DOCUMENT
     *
     * POST:
     *
     * documentController.php?action=delete
     *
     * =====================================================
     */
    if (
        $method === 'POST' &&
        $action === 'delete'
    ) {

        $documentId =
            (int) (
                $_POST['document_id'] ??
                $_POST['documentId'] ??
                0
            );


        if ($documentId <= 0) {

            respond([
                'success' =>
                false,

                'message' =>
                'Invalid document ID.'
            ], 400);
        }


        /**
         * Get document first so we know its physical
         * location.
         */
        $document =
            $documentsModel->findById(
                $documentId
            );


        if (!$document) {

            respond([
                'success' =>
                false,

                'message' =>
                'Document not found.'
            ], 404);
        }


        /**
         * Delete physical file.
         */
        $physicalDeleted =
            deletePhysicalDocument(
                $document['file_path'] ?? ''
            );


        /**
         * Delete database record.
         */
        $databaseDeleted =
            $documentsModel->delete(
                $documentId
            );


        if (!$databaseDeleted) {

            respond([
                'success' =>
                false,

                'message' =>
                'Physical file was processed, but the document database record could not be deleted.',

                'physical_deleted' =>
                $physicalDeleted
            ], 500);
        }


        respond([
            'success' =>
            true,

            'message' =>
            'Document deleted successfully.',

            'document_id' =>
            $documentId,

            'physical_deleted' =>
            $physicalDeleted
        ]);
    }


    /**
     * =====================================================
     * UNKNOWN ACTION
     * =====================================================
     */
    respond([
        'success' =>
        false,

        'message' =>
        'Invalid document request.'
    ], 404);
} catch (Throwable $e) {

    /**
     * Log detailed error.
     */
    error_log(
        'Recruitment Document API Error: ' .
            $e->getMessage()
    );


    /**
     * Return JSON error.
     */
    respond([
        'success' =>
        false,

        'message' =>
        $e->getMessage()
    ], 500);
}
