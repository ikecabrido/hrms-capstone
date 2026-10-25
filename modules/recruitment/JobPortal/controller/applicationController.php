
<?php

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../classes/Application.php';
require_once __DIR__ . '/../classes/Education.php';
require_once __DIR__ . '/../classes/Experience.php';
require_once __DIR__ . '/../classes/Documents.php';

/**
 * =========================================================
 * HRMS RECRUITMENT UPLOAD DIRECTORY
 * Physical path: modules/recruitment/uploads/
 * =========================================================
 */
define(
    'HRMS_RECRUITMENT_UPLOADS',
    dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads'
);

/**
 * =========================================================
 * JSON RESPONSE
 * =========================================================
 */
function respond(array $data, int $status = 200): void
{
    http_response_code($status);

    echo json_encode(
        $data,
        JSON_UNESCAPED_UNICODE |
            JSON_UNESCAPED_SLASHES |
            JSON_INVALID_UTF8_SUBSTITUTE
    );

    exit;
}

/**
 * =========================================================
 * READ REQUEST DATA
 * Supports JSON, multipart/form-data, and URL-encoded data.
 * =========================================================
 */
function requestData(): array
{
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

    if (stripos($contentType, 'application/json') !== false) {
        $raw = file_get_contents('php://input');

        if ($raw === false || trim($raw) === '') {
            return [];
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    return $_POST;
}

/**
 * =========================================================
 * NORMALIZE FIELD NAMES
 * =========================================================
 */
function normalizeRequestData(array $data): array
{
    if (
        !isset($data['jobId']) ||
        trim((string) $data['jobId']) === ''
    ) {
        $data['jobId'] =
            $data['job_id']
            ?? $_POST['jobId']
            ?? $_POST['job_id']
            ?? $_GET['jobId']
            ?? $_GET['job_id']
            ?? '';
    }

    $aliases = [
        'firstName'             => ['first_name'],
        'middleName'            => ['middle_name'],
        'lastName'              => ['last_name'],
        'yearGraduated'         => ['year_graduated'],
        'civilStatus'           => ['civil_status'],
        'currentAddress'        => ['current_address'],
        'permanentAddress'      => ['permanent_address'],
        'birthplace'            => ['birthPlace'],
        'birthdate'              => ['birthDate'],
        'experienceDescription' => ['experience_description']
    ];

    foreach ($aliases as $canonical => $alternatives) {
        if (
            !isset($data[$canonical]) ||
            trim((string) $data[$canonical]) === ''
        ) {
            foreach ($alternatives as $alternative) {
                if (
                    isset($data[$alternative]) &&
                    trim((string) $data[$alternative]) !== ''
                ) {
                    $data[$canonical] = $data[$alternative];
                    break;
                }
            }
        }
    }

    return $data;
}

/**
 * =========================================================
 * INPUT HELPER
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

    return is_string($data[$key])
        ? trim($data[$key])
        : $data[$key];
}

/**
 * =========================================================
 * SANITIZE FILE NAME
 * =========================================================
 */
function sanitizeFileName(string $fileName): string
{
    $fileName = basename($fileName);

    $extension = strtolower(
        pathinfo($fileName, PATHINFO_EXTENSION)
    );

    $baseName = pathinfo(
        $fileName,
        PATHINFO_FILENAME
    );

    $baseName = preg_replace(
        '/[^a-zA-Z0-9_-]/',
        '_',
        $baseName
    );

    $baseName = preg_replace('/_+/', '_', $baseName);
    $baseName = trim($baseName, '_');

    if ($baseName === '') {
        $baseName = 'document';
    }

    return $extension !== ''
        ? $baseName . '.' . $extension
        : $baseName;
}

/**
 * =========================================================
 * CREATE APPLICANT UPLOAD DIRECTORY
 *
 * Physical path:
 * uploads/{applicationId}/
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

    $uploadRoot = HRMS_RECRUITMENT_UPLOADS;

    if (!is_dir($uploadRoot)) {
        if (
            !mkdir($uploadRoot, 0755, true) &&
            !is_dir($uploadRoot)
        ) {
            throw new RuntimeException(
                'Unable to create recruitment uploads directory.'
            );
        }
    }

    if (!is_writable($uploadRoot)) {
        throw new RuntimeException(
            'Recruitment uploads directory is not writable.'
        );
    }

    $applicantDir = $uploadRoot .
        DIRECTORY_SEPARATOR .
        $applicationId;

    if (!is_dir($applicantDir)) {
        if (
            !mkdir($applicantDir, 0755, true) &&
            !is_dir($applicantDir)
        ) {
            throw new RuntimeException(
                'Unable to create applicant upload directory.'
            );
        }
    }

    if (
        !is_dir($applicantDir) ||
        !is_writable($applicantDir)
    ) {
        throw new RuntimeException(
            'Applicant upload directory is not writable.'
        );
    }

    return $applicantDir;
}

/**
 * =========================================================
 * NORMALIZE UPLOADED FILES
 *
 * Supports:
 * resume / diploma / tor / certs / other
 * apResume / apDiploma / apTor / apCerts / apOther
 * =========================================================
 */
function getApplicantFile(string $documentType): ?array
{
    $aliases = [
        'resume'  => ['resume', 'apResume'],
        'diploma' => ['diploma', 'apDiploma'],
        'tor'     => ['tor', 'apTor'],
        'certs'   => ['certs', 'apCerts'],
        'other'   => ['other', 'apOther']
    ];

    foreach ($aliases[$documentType] ?? [$documentType] as $key) {
        if (isset($_FILES[$key])) {
            return $_FILES[$key];
        }
    }

    return null;
}

/**
 * =========================================================
 * VALIDATE REQUIRED RESUME / CV
 *
 * Resume is required.
 * Diploma, TOR, certificates, and other documents are optional.
 * =========================================================
 */
function validateRequiredResume(): void
{
    $file = getApplicantFile('resume');

    if ($file === null) {
        respond([
            'success' => false,
            'message' => 'Please attach your resume / CV.'
        ], 400);
    }

    $errorCode = $file['error'] ?? UPLOAD_ERR_NO_FILE;

    if ($errorCode === UPLOAD_ERR_NO_FILE) {
        respond([
            'success' => false,
            'message' => 'Please attach your resume / CV.'
        ], 400);
    }

    $uploadErrors = [
        UPLOAD_ERR_INI_SIZE =>
        'Your resume exceeds the server upload limit.',
        UPLOAD_ERR_FORM_SIZE =>
        'Your resume exceeds the form upload limit.',
        UPLOAD_ERR_PARTIAL =>
        'Your resume was only partially uploaded. Please try again.',
        UPLOAD_ERR_NO_TMP_DIR =>
        'The server temporary upload directory is missing.',
        UPLOAD_ERR_CANT_WRITE =>
        'The server could not save your uploaded resume.',
        UPLOAD_ERR_EXTENSION =>
        'A server extension stopped the resume upload.'
    ];

    if ($errorCode !== UPLOAD_ERR_OK) {
        respond([
            'success' => false,
            'message' => $uploadErrors[$errorCode]
                ?? 'The resume upload failed. Please select your file again.'
        ], 400);
    }

    $tmpName = $file['tmp_name'] ?? '';
    $originalName = basename($file['name'] ?? '');
    $fileSize = (int) ($file['size'] ?? 0);

    if (
        $tmpName === '' ||
        !is_uploaded_file($tmpName)
    ) {
        respond([
            'success' => false,
            'message' => 'The uploaded resume is invalid. Please select it again.'
        ], 400);
    }

    if ($originalName === '') {
        respond([
            'success' => false,
            'message' => 'The resume file has no filename.'
        ], 400);
    }

    if ($fileSize <= 0) {
        respond([
            'success' => false,
            'message' => 'The uploaded resume is empty.'
        ], 400);
    }

    $maxFileSize = 10 * 1024 * 1024;

    if ($fileSize > $maxFileSize) {
        respond([
            'success' => false,
            'message' => 'Resume / CV must not exceed 10 MB.'
        ], 400);
    }

    $extension = strtolower(
        pathinfo($originalName, PATHINFO_EXTENSION)
    );

    if (!in_array($extension, ['pdf', 'doc', 'docx'], true)) {
        respond([
            'success' => false,
            'message' => 'Resume / CV must be a PDF, DOC, or DOCX file.'
        ], 400);
    }
}

/**
 * =========================================================
 * UPLOAD DOCUMENT
 *
 * Physical path:
 * uploads/{applicationId}/{filename}
 *
 * Database path:
 * uploads/{applicationId}/{filename}
 * =========================================================
 */
function uploadDocument(
    Documents $documentsModel,
    int $applicationId,
    string $documentType,
    array $file
): array {
    $errorCode = $file['error'] ?? UPLOAD_ERR_NO_FILE;

    if ($errorCode === UPLOAD_ERR_NO_FILE) {
        throw new RuntimeException(
            "No file was selected for {$documentType}."
        );
    }

    if ($errorCode !== UPLOAD_ERR_OK) {
        $uploadErrors = [
            UPLOAD_ERR_INI_SIZE =>
            'The uploaded file exceeds the server upload limit.',
            UPLOAD_ERR_FORM_SIZE =>
            'The uploaded file exceeds the form upload limit.',
            UPLOAD_ERR_PARTIAL =>
            'The file was only partially uploaded.',
            UPLOAD_ERR_NO_TMP_DIR =>
            'The server temporary upload directory is missing.',
            UPLOAD_ERR_CANT_WRITE =>
            'The server could not write the uploaded file.',
            UPLOAD_ERR_EXTENSION =>
            'A server extension stopped the upload.'
        ];

        throw new RuntimeException(
            $uploadErrors[$errorCode]
                ?? "Failed to upload {$documentType}. Error code: {$errorCode}"
        );
    }

    $tmpName = $file['tmp_name'] ?? '';

    if (
        $tmpName === '' ||
        !is_uploaded_file($tmpName)
    ) {
        throw new RuntimeException(
            "Invalid uploaded file for {$documentType}."
        );
    }

    $originalName = basename($file['name'] ?? '');

    if ($originalName === '') {
        throw new RuntimeException(
            "The {$documentType} file has no filename."
        );
    }

    $fileSize = (int) ($file['size'] ?? 0);
    $maxFileSize = 10 * 1024 * 1024;

    if ($fileSize <= 0) {
        throw new RuntimeException(
            "The {$documentType} file is empty."
        );
    }

    if ($fileSize > $maxFileSize) {
        throw new RuntimeException(
            "{$documentType} exceeds the 10 MB limit."
        );
    }

    $extension = strtolower(
        pathinfo($originalName, PATHINFO_EXTENSION)
    );

    if (!in_array($extension, ['pdf', 'doc', 'docx'], true)) {
        throw new RuntimeException(
            "Invalid {$documentType} file type. Only PDF, DOC, and DOCX are allowed."
        );
    }

    $applicantDir = createApplicantUploadDirectory(
        $applicationId
    );

    $newFileName = sanitizeFileName($originalName);

    $destination = $applicantDir .
        DIRECTORY_SEPARATOR .
        $newFileName;

    /*
     * Prevent overwriting an existing file.
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

        $newFileName = $baseName .
            '_' .
            date('Ymd_His') .
            '_' .
            bin2hex(random_bytes(4));

        if ($fileExtension !== '') {
            $newFileName .= '.' . $fileExtension;
        }

        $destination = $applicantDir .
            DIRECTORY_SEPARATOR .
            $newFileName;
    }

    if (!move_uploaded_file($tmpName, $destination)) {
        throw new RuntimeException(
            "Unable to save the {$documentType} file."
        );
    }

    $relativePath = 'uploads/' .
        $applicationId .
        '/' .
        $newFileName;

    try {
        $documentId = $documentsModel->create(
            $applicationId,
            $documentType,
            $originalName,
            $relativePath,
            $fileSize
        );
    } catch (Throwable $e) {
        if (file_exists($destination)) {
            @unlink($destination);
        }

        throw $e;
    }

    return [
        'document_id'    => $documentId,
        'document_type'  => $documentType,
        'original_name'  => $originalName,
        'file_name'      => $newFileName,
        'file_path'      => $relativePath,
        'file_extension' => $extension,
        'file_size'      => $fileSize
    ];
}

/**
 * =========================================================
 * INITIALIZE MODELS
 * =========================================================
 */
$applicationModel = new Application();
$educationModel = new Education();
$experienceModel = new Experience();
$documentsModel = new Documents();

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    /**
     * =====================================================
     * GET APPLICATIONS
     * =====================================================
     */
    if ($method === 'GET' && $action === 'applications') {
        $applications = $applicationModel->getAll();

        foreach ($applications as &$application) {
            $application['full_name'] = trim(
                ($application['first_name'] ?? '') . ' ' .
                    ($application['middle_name'] ?? '') . ' ' .
                    ($application['last_name'] ?? '')
            );
        }

        unset($application);

        respond([
            'success' => true,
            'data' => $applications
        ]);
    }

    /**
     * =====================================================
     * CREATE APPLICATION
     * =====================================================
     */
    if ($method === 'POST' && $action === 'applications') {
        $data = normalizeRequestData(requestData());

        /**
         * Required text fields.
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

        foreach ($requiredFields as $field) {
            if (
                !array_key_exists($field, $data) ||
                trim((string) $data[$field]) === ''
            ) {
                respond([
                    'success' => false,
                    'message' => "Missing required field: {$field}"
                ], 400);
            }
        }

        /**
         * REQUIRED DOCUMENT VALIDATION.
         *
         * This runs BEFORE the application is created.
         * Only Resume / CV is required.
         */
        validateRequiredResume();

        /**
         * Validate job ID.
         */
        $jobId = filter_var(
            $data['jobId'],
            FILTER_VALIDATE_INT
        );

        if ($jobId === false || $jobId <= 0) {
            respond([
                'success' => false,
                'message' => 'Invalid job ID. Please select a valid job before submitting.'
            ], 400);
        }

        /**
         * Check whether the job is still accepting applications.
         */
        if (!$applicationModel->canApply((int) $jobId)) {
            respond([
                'success' => false,
                'message' => 'This job is no longer accepting applications.'
            ], 409);
        }

        /**
         * Personal information.
         */
        $firstName = inputValue($data, 'firstName');
        $middleName = inputValue($data, 'middleName');
        $lastName = inputValue($data, 'lastName');

        $email = inputValue($data, 'email');
        $contact = inputValue($data, 'contact');
        $gender = inputValue($data, 'gender');
        $birthplace = inputValue($data, 'birthplace');
        $birthdate = inputValue($data, 'birthdate');

        if (
            $birthdate === '' ||
            $birthdate === '0000-00-00'
        ) {
            $birthdate = null;
        }

        $age = (int) inputValue($data, 'age', 0);
        $civilStatus = inputValue($data, 'civilStatus');
        $citizenship = inputValue($data, 'citizenship');
        $religion = inputValue($data, 'religion');

        $address = inputValue($data, 'address');

        $currentAddress = inputValue(
            $data,
            'currentAddress',
            $address
        );

        $permanentAddress = inputValue(
            $data,
            'permanentAddress',
            $address
        );

        $summary = inputValue($data, 'summary');

        /**
         * Education information.
         */
        $school = inputValue($data, 'school');
        $degree = inputValue($data, 'degree');
        $yearGraduated = inputValue($data, 'yearGraduated');

        /**
         * Experience information.
         */
        $company = inputValue($data, 'company');
        $position = inputValue($data, 'position');
        $startDate = inputValue($data, 'startDate');
        $endDate = inputValue($data, 'endDate');

        $experienceDescription = inputValue(
            $data,
            'experienceDescription'
        );

        /**
         * Normalize optional date fields.
         */
        if ($startDate === '') {
            $startDate = null;
        }

        if ($endDate === '') {
            $endDate = null;
        }

        /**
         * Create the application to obtain its ID.
         *
         * Argument order is preserved from your original code.
         */
        $applicationId = $applicationModel->create(
            (int) $jobId,
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

        $applicationId = (int) $applicationId;

        if ($applicationId <= 0) {
            throw new RuntimeException(
                'Application was created but no valid application ID was returned.'
            );
        }

        /**
         * Save education information.
         */
        $educationModel->create(
            $applicationId,
            $school,
            $degree,
            $yearGraduated
        );

        /**
         * Save experience only when information was supplied.
         */
        if (
            $company !== '' ||
            $position !== '' ||
            $startDate !== null ||
            $endDate !== null ||
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
         * UPLOAD DOCUMENTS
         *
         * Resume is required.
         * All other document types are optional.
         * =================================================
         */
        $uploadedDocuments = [];

        $documentTypes = [
            'resume',
            'diploma',
            'tor',
            'certs',
            'other'
        ];

        foreach ($documentTypes as $documentType) {
            $file = getApplicantFile($documentType);

            /*
             * Skip absent optional documents.
             */
            if ($file === null) {
                if ($documentType === 'resume') {
                    throw new RuntimeException(
                        'Please attach your resume / CV.'
                    );
                }

                continue;
            }

            $fileError = $file['error'] ?? UPLOAD_ERR_NO_FILE;

            /*
             * Skip optional documents that were not selected.
             * Resume cannot be skipped.
             */
            if ($fileError === UPLOAD_ERR_NO_FILE) {
                if ($documentType === 'resume') {
                    throw new RuntimeException(
                        'Please attach your resume / CV.'
                    );
                }

                continue;
            }

            $uploaded = uploadDocument(
                $documentsModel,
                $applicationId,
                $documentType,
                $file
            );

            $uploadedDocuments[$documentType] = $uploaded;

            /**
             * Update the application's resume path.
             */
            if ($documentType === 'resume') {
                $updated = $applicationModel->updateResumePath(
                    $applicationId,
                    $uploaded['file_path']
                );

                if (!$updated) {
                    throw new RuntimeException(
                        'Resume was uploaded, but the application resume path could not be updated.'
                    );
                }
            }
        }

        /**
         * Success response.
         */
        respond([
            'success' => true,
            'message' => 'Application submitted successfully.',
            'application_id' => $applicationId,
            'applicant_folder' => 'uploads/' . $applicationId . '/',
            'documents' => $uploadedDocuments
        ], 201);
    }

    /**
     * =====================================================
     * UNKNOWN ACTION
     * =====================================================
     */
    respond([
        'success' => false,
        'message' => 'Invalid request.'
    ], 404);
} catch (Throwable $e) {
    error_log(
        'Recruitment Applications API Error: ' .
            $e->getMessage()
    );

    respond([
        'success' => false,
        'message' => $e->getMessage()
    ], 500);
}
