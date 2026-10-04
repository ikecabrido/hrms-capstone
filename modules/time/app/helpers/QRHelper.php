<?php
/**
 * QR Code Helper for Time & Attendance System
 * Manages QR token generation, validation, and expiry
 */

// Set PHP timezone to Philippines (UTC+8)
date_default_timezone_set('Asia/Manila');

require_once __DIR__ . '/../core/TimeDatabase.php';
require_once __DIR__ . '/Helper.php';

class QRHelper
{
    private $conn;
    private $token_expiry_minutes = 1; // Token valid for 1 minute

    public function __construct()
    {
        $db = TimeDatabase::getInstance();
        $this->conn = $db->getConnection();
    }

    /**
     * Generate a new QR token (1-minute expiry, single-use)
     * 
     * @param int $generated_by - User ID of HR who generated token
     * @param string $generated_for_date - Date for which token is valid
     * @return string - Generated token
     */
    public function generateToken($generated_by, $generated_for_date = null)
    {
        if (empty($generated_for_date)) {
            $generated_for_date = date("Y-m-d");
        }

        // Generate cryptographically secure random token
        $token = bin2hex(random_bytes(32));
        
        // Token expires 1 minute from NOW (use time() to ensure correct calculation)
        $expires_at = date("Y-m-d H:i:s", time() + (60 * $this->token_expiry_minutes));

        // Detect the server's actual IP address
        $ip_address = $this->getServerIP();

        $query = "INSERT INTO ta_attendance_tokens (token, generated_by, generated_for_date, expires_at, ip_address)
                  VALUES (:token, :generated_by, :generated_for_date, :expires_at, :ip_address)";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':token', $token);
        $stmt->bindParam(':generated_by', $generated_by);
        $stmt->bindParam(':generated_for_date', $generated_for_date);
        $stmt->bindParam(':expires_at', $expires_at);
        $stmt->bindParam(':ip_address', $ip_address);

        if ($stmt->execute()) {
            return $token;
        }

        return null;
    }

    /**
     * Get the server's actual IP address
     * Attempts multiple methods to detect the IP
     * 
     * @return string - Server IP address
     */
    public function getServerIP()
    {
        // Method 1: Try SERVER_ADDR first
        if (!empty($_SERVER['SERVER_ADDR']) && $_SERVER['SERVER_ADDR'] !== '127.0.0.1') {
            return $_SERVER['SERVER_ADDR'];
        }

        // Method 2: Try SERVER_NAME
        if (!empty($_SERVER['SERVER_NAME']) && $_SERVER['SERVER_NAME'] !== 'localhost' && !preg_match('/^127\./', $_SERVER['SERVER_NAME'])) {
            $ip = gethostbyname($_SERVER['SERVER_NAME']);
            if ($ip !== $_SERVER['SERVER_NAME'] && $ip !== '127.0.0.1') {
                return $ip;
            }
        }

        // Method 3: Try hostname detection
        $hostname = gethostname();
        if ($hostname !== false) {
            $ip = gethostbyname($hostname);
            if ($ip !== $hostname && $ip !== '127.0.0.1') {
                return $ip;
            }
        }

        // Method 4: Try to get from HTTP_HOST and extract IP
        if (!empty($_SERVER['HTTP_HOST'])) {
            $host = preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST']);
            if ($host && $host !== ':' && !preg_match('/^:+$/', $host)) {
                return $host;
            }
        }

        // Fallback
        return '127.0.0.1';
    }

    /**
     * Validate QR token
     * - Check if token exists
     * - Check if token has not been used
     * - Check if token has not expired
     * 
     * @param string $token - Token to validate
     * @return array|false - Token data if valid, false otherwise
     */
    public function validateToken($token)
    {
        // Simply trim whitespace, don't use htmlspecialchars (it can alter hex values)
        $token = trim($token);

        $query = "SELECT * FROM ta_attendance_tokens
                  WHERE token = :token
                  AND used = 0
                  AND expires_at >= NOW()
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':token', $token);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Debug logging
        error_log("QRHelper::validateToken() - Token: " . substr($token, 0, 16) . "... | Found: " . ($result ? "YES" : "NO"));
        if (!$result) {
            // Try to find out why it failed
            $debug_query = "SELECT token_id, used, expires_at, NOW() as check_time FROM ta_attendance_tokens WHERE token = :token";
            $debug_stmt = $this->conn->prepare($debug_query);
            $debug_stmt->bindParam(':token', $token);
            $debug_stmt->execute();
            $debug = $debug_stmt->fetch(PDO::FETCH_ASSOC);
            error_log("QRHelper::validateToken() - Debug: " . json_encode($debug));
        }
        
        return $result;
    }

    /**
     * Mark token as used (prevent reuse)
     * 
     * @param string $token - Token to mark as used
     * @param int $used_by - Employee ID who used the token
     * @return bool - Success status
     */
    public function markUsed($token, $used_by)
    {
        $query = "UPDATE ta_attendance_tokens 
                  SET used = 1, used_by = :used_by, used_at = NOW() 
                  WHERE token = :token";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':token', $token);
        $stmt->bindParam(':used_by', $used_by);

        return $stmt->execute();
    }

    /**
     * Get QR token details
     */
    public function getTokenDetails($token)
    {
        $query = "SELECT * FROM ta_attendance_tokens WHERE token = :token LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':token', $token);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Clean up expired tokens (for maintenance)
     */
    public function cleanupExpiredTokens()
    {
        $query = "DELETE FROM ta_attendance_tokens 
                  WHERE used = 0 AND expires_at < NOW()";
        $stmt = $this->conn->prepare($query);

        return $stmt->execute();
    }

    /**
     * Get unexpired token count for a date
     */
    public function getUnusedTokenCount($date = null)
    {
        if (empty($date)) {
            $date = date("Y-m-d");
        }

        $query = "SELECT COUNT(*) as count FROM ta_attendance_tokens
                  WHERE generated_for_date = :date
                  AND used = 0
                  AND expires_at >= NOW()";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':date', $date);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] ?? 0;
    }
}
