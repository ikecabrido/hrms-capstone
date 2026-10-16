<?php

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../classes/Application.php';
require_once __DIR__ . '/../classes/Education.php';
require_once __DIR__ . '/../classes/Experience.php';
require_once __DIR__ . '/../classes/Documents.php';


/**
 * =========================================================
 * HRMS RECRUITMENT UPLOAD DIRECTORY
 * =========================================================
 *
 * IMPORTANT:
 *
 * This controller belongs to the JobPortal project:
 *
 * C:\xampp\htdocs\JobPortal\
 *
 * But uploaded recruitment documents must be stored
 * inside the separate hrms-capstone project:
 *
 * C:\xampp\htdocs\hrms-capstone\modules\recruitment\uploads\
 *
 * DO NOT use __DIR__ to build this path.
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
 * REQUEST DATA
 *
 * Supports:
 * - application/json
 * - multipart/form-data
 * - application/x-www-form-urlencoded
 * =========================================================
 */
function requestData(): array
{
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

    /**
     * JSON request.
     */
    if (
        stripos(
            $contentType,
            'application/json'
        ) !== false
    ) {
        $raw = file_get_contents('php://input');

        if (!$raw) {
            return [];
        }

        $data = json_decode(
            $raw,
            true
        );

        return is_array($data)
            ? $data
            : [];
    }

    /**
     * multipart/form-data and
     * application/x-www-form-urlencoded.
     */
    return $_POST;
}


/**
 * =========================================================
 * CLEAN INPUT
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
 *
 * Example:
 *
 * My Resume 2026.pdf
 *
 * becomes:
 *
 * My_Resume_2026.pdf
 * =========================================================
 */
function sanitizeFileName(string $fileName): string
{
    /**
     * Prevent directory traversal.
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
     * Get filename without extension.
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
     * Remove repeated underscores.
     */
    $baseName = preg_replace(
        '/_+/',
        '_',
        $baseName
    );

    /**
     * Remove underscores from beginning/end.
     */
    $baseName = trim(
        $baseName,
        '_'
    );

    /**
     * Fallback filename.
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
 * CREATE APPLICANT UPLOAD DIRECTORY
 * =========================================================
 *
 * Physical result:
 *
 * C:\xampp\htdocs\hrms-capstone\modules\recruitment\
 * uploads\{applicationId}\
 *
 * Example:
 *
 * C:\xampp\htdocs\hrms-capstone\modules\recruitment\
 * uploads\14\
 * =========================================================
 */
function createApplicantUploadDirectory(
    int $applicationId
): string {

    if ($applicationId <= 0) {
        throw new RuntimeException(
            'Invalid application ID for upload directory.'
        );
    }

    /**
     * Make sure the main recruitment uploads directory exists.
     */
    $uploadRoot = HRMS_RECRUITMENT_UPLOADS;

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
     * Applicant-specific directory.
     *
     * IMPORTANT:
     *
     * There is NO "resumes" folder.
     *
     * There are NO name folders.
     *
     * Correct:
     *
     * uploads\14\
     */
    $applicantDir =
        $uploadRoot .
        DIRECTORY_SEPARATOR .
        $applicationId;

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
                'Unable to create applicant upload directory: ' .
                    $applicantDir
            );
        }
    }

    /**
     * Final verification.
     */
    if (!is_dir($applicantDir)) {
        throw new RuntimeException(
            'Applicant upload directory does not exist: ' .
                $applicantDir
        );
    }

    /**
     * Check writable.
     */
    if (!is_writable($applicantDir)) {
        throw new RuntimeException(
            'Applicant upload directory is not writable: ' .
                $applicantDir
        );
    }

    return $applicantDir;
}


/**
 * =========================================================
 * UPLOAD DOCUMENT
 * =========================================================
 *
 * PHYSICAL LOCATION:
 *
 * C:\xampp\htdocs\hrms-capstone\modules\recruitment\
 * uploads\{applicationId}\filename.ext
 *
 * Example:
 *
 * C:\xampp\htdocs\hrms-capstone\modules\recruitment\
 * uploads\14\My_Resume.pdf
 *
 *
 * DATABASE PATH:
 *
 * uploads/14/My_Resume.pdf
 *
 * IMPORTANT:
 *
 * The Windows absolute path is NEVER stored in the
 * database.
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
     * 1. Validate upload error
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
     * 2. Validate temporary uploaded file
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
     * 3. Get original filename
     * -----------------------------------------------------
     */
    $originalName = basename(
        $file['name'] ?? ''
    );

    if ($originalName === '') {
        throw new RuntimeException(
            "The {$documentType} file has no filename."
        );
    }


    /**
     * -----------------------------------------------------
     * 4. Get file size
     * -----------------------------------------------------
     */
    $fileSize = isset($file['size'])
        ? (int) $file['size']
        : 0;


    /**
     * -----------------------------------------------------
     * 5. Get extension
     * -----------------------------------------------------
     */
    $extension = strtolower(
        pathinfo(
            $originalName,
            PATHINFO_EXTENSION
        )
    );


    /**
     * -----------------------------------------------------
     * 6. Allowed extensions
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
     * 7. Maximum file size = 10 MB
     * -----------------------------------------------------
     */
    $maxFileSize = 10 * 1024 * 1024;

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
        createApplicantUploadDirectory(
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
     * 11. Prevent filename collision
     * -----------------------------------------------------
     */
    if (file_exists($destination)) {

        $baseName = pathinfo(
            $newFileName,
            PATHINFO_FILENAME
        );

        $fileExtension = pathinfo(
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
            $newFileName .= '.' . $fileExtension;
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
     * IMPORTANT:
     *
     * Store only:
     *
     * uploads/14/resume.pdf
     *
     * NOT:
     *
     * C:\xampp\htdocs\...
     *
     * NOT:
     *
     * hrms-capstone/modules/recruitment/...
     *
     * NOT:
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
     * 15. Save document metadata
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
         * Database insert failed.
         *
         * Remove physical file to prevent
         * orphaned uploads.
         */
        if (file_exists($destination)) {
            @unlink($destination);
        }

        throw $e;
    }


    /**
     * -----------------------------------------------------
     * 16. Return upload information
     * -----------------------------------------------------
     */
    return [
        'document_id' =>
        $documentId,

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

        /**
         * Physical path is returned for debugging,
         * but is NOT stored in the database.
         */
        'physical_path' =>
        $destination
    ];
}


/**
 =========================================================
 * MODELS
 * =========================================================
 */

$applicationModel =
    new Application();

$educationModel =
    new Education();

$experienceModel =
    new Experience();

$documentsModel =
    new Documents();


/**
 * =========================================================
 * REQUEST
 * =========================================================
 */

$action =
    $_GET['action'] ?? '';


try {

    /**
     * =====================================================
     * GET APPLICATIONS
     * =====================================================
     */
    if (
        $_SERVER['REQUEST_METHOD'] === 'GET' &&
        $action === 'applications'
    ) {

        $applications =
            $applicationModel->getAll();


        foreach (
            $applications
            as &$application
        ) {

            $firstName =
                $application['first_name'] ?? '';

            $middleName =
                $application['middle_name'] ?? '';

            $lastName =
                $application['last_name'] ?? '';

            $application['full_name'] =
                trim(
                    $firstName .
                        ' ' .
                        $middleName .
                        ' ' .
                        $lastName
                );
        }

        unset($application);


        respond([
            'success' =>
            true,

            'data' =>
            $applications
        ]);
    }


    /**
     * =====================================================
     * CREATE APPLICATION
     * =====================================================
     */
    if (
        $_SERVER['REQUEST_METHOD'] === 'POST' &&
        $action === 'applications'
    ) {

        /**
         * -------------------------------------------------
         * Get request data
         * -------------------------------------------------
         */
        $data =
            requestData();


        /**
         * -------------------------------------------------
         * Required fields
         * -------------------------------------------------
         */
        $requiredFields = [
            'jobId',
            'firstName',
            'lastName',
            'age',
            'email',
            'contact',
            'address',
            'school',
            'degree',
            'yearGraduated'
        ];


        foreach (
            $requiredFields
            as $field
        ) {

            if (
                !isset($data[$field]) ||
                trim(
                    (string) $data[$field]
                ) === ''
            ) {

                respond([
                    'success' =>
                    false,

                    'message' =>
                    "Missing required field: {$field}"
                ], 400);
            }
        }


        /**
         * -------------------------------------------------
         * Job ID
         * -------------------------------------------------
         */
        $jobId =
            (int) $data['jobId'];


        if ($jobId <= 0) {

            respond([
                'success' =>
                false,

                'message' =>
                'Invalid job ID.'
            ], 400);
        }


        /**
         * -------------------------------------------------
         * Check vacancy
         * -------------------------------------------------
         */
        if (
            !$applicationModel->canApply(
                $jobId
            )
        ) {

            respond([
                'success' =>
                false,

                'message' =>
                'This job is no longer accepting applications.'
            ], 409);
        }


        /**
         * =================================================
         * PERSONAL INFORMATION
         * =================================================
         */

        $firstName =
            inputValue(
                $data,
                'firstName'
            );

        $middleName =
            inputValue(
                $data,
                'middleName'
            );

        $lastName =
            inputValue(
                $data,
                'lastName'
            );

        $email =
            inputValue(
                $data,
                'email'
            );

        $contact =
            inputValue(
                $data,
                'contact'
            );

        $gender =
            inputValue(
                $data,
                'gender'
            );

        $birthplace =
            inputValue(
                $data,
                'birthplace'
            );

        $birthdate =
            inputValue(
                $data,
                'birthdate'
            );


        /**
         * Empty date becomes NULL.
         */
        if (
            $birthdate === '' ||
            $birthdate === '0000-00-00'
        ) {
            $birthdate = null;
        }


        $age =
            (int) inputValue(
                $data,
                'age',
                0
            );

        $civilStatus =
            inputValue(
                $data,
                'civilStatus'
            );

        $citizenship =
            inputValue(
                $data,
                'citizenship'
            );

        $religion =
            inputValue(
                $data,
                'religion'
            );

        $address =
            inputValue(
                $data,
                'address'
            );

        $currentAddress =
            inputValue(
                $data,
                'currentAddress',
                $address
            );

        $permanentAddress =
            inputValue(
                $data,
                'permanentAddress',
                $address
            );

        $summary =
            inputValue(
                $data,
                'summary'
            );

        $coverLetter =
            inputValue(
                $data,
                'coverLetter'
            );


        /**
         * =================================================
         * CREATE APPLICATION
         * =================================================
         *
         * IMPORTANT:
         *
         * The order MUST match Application::create().
         *
         * The resume is intentionally empty here because
         * the application ID must first be generated before
         * we can create:
         *
         * uploads/{applicationId}/
         */
        $applicationId =
            $applicationModel->create(
                $jobId,
                $firstName,
                $middleName,
                $lastName,
                $email,
                $contact,
                $address,
                $summary,
                '',
                'Online Posting',
                $gender,
                $birthplace,
                $birthdate,
                $age,
                $civilStatus,
                $citizenship,
                $religion,
                $currentAddress,
                $permanentAddress
            );


        $applicationId =
            (int) $applicationId;


        if ($applicationId <= 0) {

            throw new RuntimeException(
                'Application was created but no valid application ID was returned.'
            );
        }


        /**
         * =================================================
         * CREATE APPLICANT UPLOAD FOLDER
         * =================================================
         *
         * EXACT PHYSICAL LOCATION:
         *
         * C:\xampp\htdocs\hrms-capstone\modules\
         * recruitment\uploads\{applicationId}\
         *
         * Example:
         *
         * C:\xampp\htdocs\hrms-capstone\modules\
         * recruitment\uploads\14\
         *
         * There is NO:
         *
         * uploads\resumes\
         *
         * and NO:
         *
         * uploads\FirstName_LastName\
         */
        $applicantFolder =
            createApplicantUploadDirectory(
                $applicationId
            );


        /**
         * =================================================
         * EDUCATION
         * =================================================
         */

        $school =
            inputValue(
                $data,
                'school'
            );

        $degree =
            inputValue(
                $data,
                'degree'
            );

        $yearGraduated =
            inputValue(
                $data,
                'yearGraduated'
            );


        $educationModel->create(
            $applicationId,
            $school,
            $degree,
            $yearGraduated
        );


        /**
         * =================================================
         * EXPERIENCE
         * =================================================
         */

        $company =
            inputValue(
                $data,
                'company'
            );

        $position =
            inputValue(
                $data,
                'position'
            );

        $startDate =
            inputValue(
                $data,
                'startDate'
            );

        $endDate =
            inputValue(
                $data,
                'endDate'
            );

        $experienceDescription =
            inputValue(
                $data,
                'experienceDescription'
            );


        /**
         * Only create experience when something
         * was entered.
         */
        if (
            $company !== '' ||
            $position !== '' ||
            $startDate !== '' ||
            $endDate !== '' ||
            $experienceDescription !== ''
        ) {

            $experienceModel->create(
                $applicationId,
                $company,
                $position,
                $startDate,
                $endDate,
                $experienceDescription
            );
        }


        /**
         * =================================================
         * DOCUMENT UPLOADS
         * =================================================
         *
         * Frontend field names:
         *
         * resume
         * diploma
         * tor
         * certs
         * other
         *
         * Physical result:
         *
         * uploads/
         *     {applicationId}/
         *         resume.pdf
         *         diploma.docx
         *         tor.pdf
         *         certs.pdf
         *         other.docx
         */

        $uploadedDocuments = [];


        $documentTypes = [
            'resume',
            'diploma',
            'tor',
            'certs',
            'other'
        ];


        foreach (
            $documentTypes
            as $documentType
        ) {

            /**
             * File was not submitted.
             */
            if (
                !isset(
                    $_FILES[$documentType]
                )
            ) {
                continue;
            }


            /**
             * User did not select a file.
             */
            if (
                ($_FILES[$documentType]['error'] ?? null)
                === UPLOAD_ERR_NO_FILE
            ) {
                continue;
            }


            /**
             * Upload actual file.
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


            /**
             * =================================================
             * KEEP rao_applications.resume SYNCHRONIZED
             * =================================================
             *
             * Example:
             *
             * rao_applications.resume
             *
             * uploads/14/My_Resume.pdf
             */
            if (
                $documentType === 'resume'
            ) {

                $updated =
                    $applicationModel
                    ->updateResumePath(
                        $applicationId,
                        $uploaded['file_path']
                    );


                if (!$updated) {

                    throw new RuntimeException(
                        'Resume uploaded successfully, ' .
                            'but the application resume path could not be updated.'
                    );
                }
            }
        }


        /**
         * =================================================
         * SUCCESS
         * =================================================
         */
        respond([
            'success' =>
            true,

            'message' =>
            'Application submitted successfully.',

            'application_id' =>
            $applicationId,

            /**
             * Relative path only.
             */
            'applicant_folder' =>
            'uploads/' .
                $applicationId .
                '/',

            /**
             * Useful for frontend/debugging.
             */
            'documents' =>
            $uploadedDocuments

        ], 201);
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
        'Invalid request.'
    ], 404);
} catch (Throwable $e) {

    /**
     * Server-side logging.
     */
    error_log(
        'Recruitment Applications API Error: ' .
            $e->getMessage()
    );


    /**
     * Return error to frontend.
     */
    respond([
        'success' =>
        false,

        'message' =>
        $e->getMessage()
    ], 500);
}
