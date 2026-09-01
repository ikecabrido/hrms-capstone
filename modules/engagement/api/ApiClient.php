<?php
namespace App\Api;

class ApiClient
{
    private $baseUrl;

    public function __construct($baseUrl = null)
    {
        if ($baseUrl === null) {
            $this->baseUrl = 'http://localhost/capstone_hr_management_system/engagement_relations/api/index.php';
        } else {
            $this->baseUrl = rtrim($baseUrl, '/');
        }
    }

    public function request($path, $method = 'GET', $payload = [])
    {
        $normalizedPath = ltrim($path, '/');
        if ($normalizedPath === '') {
            $url = $this->baseUrl;
        } elseif (strpos($this->baseUrl, '.php') !== false) {
            if (strpos($normalizedPath, '?') === 0 || strpos($normalizedPath, 'index.php') === 0) {
                $url = rtrim($this->baseUrl, '/') . '/' . $normalizedPath;
            } else {
                $url = $this->baseUrl . (strpos($this->baseUrl, '?') === false ? '?' : '&') . $normalizedPath;
            }
        } else {
            $url = rtrim($this->baseUrl, '/') . '/' . $normalizedPath;
        }

        $ch = curl_init();

        $headers = ['Accept: application/json'];

        if (in_array($method, ['POST', 'PUT', 'PATCH'])) {
            $body = json_encode($payload);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            $headers[] = 'Content-Type: application/json';
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        } else {
            if (!empty($payload)) {
                $url .= (strpos($url, '?') === false ? '?' : '&') . http_build_query($payload);
            }
        }

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new \Exception('ApiClient cURL error: ' . $error);
        }

        $decoded = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('ApiClient response JSON parse error: ' . json_last_error_msg());
        }

        return $decoded;
    }

    public function get($path, $params = [])
    {
        return $this->request($path, 'GET', $params);
    }

    public function post($path, $data = [])
    {
        return $this->request($path, 'POST', $data);
    }
}

