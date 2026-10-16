
<?php

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../classes/Application.php';
require_once __DIR__ . '/../classes/Education.php';
require_once __DIR__ . '/../classes/Experience.php';
require_once __DIR__ . '/../classes/Documents.php';


/**
 * =========================================================
 * HRMS RECRUITMENT UPLOAD DIRECTORY
 * Works relative to the deployed project directory.
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
 *
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
    /*
     * Accept both jobId and job_id.
     * The form should still send the actual selected job ID.
     */
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

    /*
     * Support alternate field names for applicant details.
     */
    $aliases = [
        'firstName'      => ['first_name'],
        'middleName'     => ['middle_name'],
        'lastName'       => ['last_name'],
        'yearGraduated'  => ['year_graduated'],
        'civilStatus'    => ['civil_status'],
        'currentAddress' => ['current_address'],
        'permanentAddress' => ['permanent_address'],
        'birthplace'     => ['birthPlace'],
        'birthdate'      => ['birthDate'],
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
 * uploads/{applicationId}/
 *
 * The directory is created only when a document is uploaded.
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

    if (!is_dir($applicantDir) || !is_writable($applicantDir)) {
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
 * Supports both:
 *   resume / diploma / tor / certs / other
 *
 * and:
 *   apResume / apDiploma / apTor / apCerts / apOther
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
 * UPLOAD DOCUMENT
 *
 * Physical path:
 *   HRMS_RECRUITMENT_UPLOADS/{applicationId}/{filename}
 *
 * Database path:
 *   uploads/{applicationId}/{filename}
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
            $uploadErrors[$errorCode] ??
                "Failed to upload {$documentType}. Error code: {$errorCode}"
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

    $allowedExtensions = ['pdf', 'doc', 'docx'];

    if (!in_array($extension, $allowedExtensions, true)) {
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
     * Prevent overwriting a file with the same name.
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
         * Create the application first to obtain its ID.
         *
         * Keep this argument order synchronized with
         * Application::create().
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
         * Upload documents.
         *
         * Supports both regular field names and ap-prefixed
         * field names from the application form.
         *
         * The applicant directory is created only when
         * an actual file is uploaded.
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

            if ($file === null) {
                continue;
            }

            if (
                ($file['error'] ?? UPLOAD_ERR_NO_FILE)
                === UPLOAD_ERR_NO_FILE
            ) {
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
             * Keep the application's resume column synchronized.
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
            'applicant_folder' => empty($uploadedDocuments)
                ? null
                : 'uploads/' . $applicationId . '/',
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
