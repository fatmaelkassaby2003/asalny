<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class FirebaseService
{
    protected $projectId;
    protected $credentials;

    public function __construct()
    {
        // Initialization moved to lazy loading to prevent 500 error on instantiation
    }

    protected function getCredentials()
    {
        if ($this->credentials) {
            return $this->credentials;
        }

        $path = storage_path('app/firebase-credentials.json');
        
        if (!file_exists($path)) {
            throw new \Exception("Firebase credentials file not found at: $path");
        }

        $content = file_get_contents($path);
        
        if (!$content) {
             throw new \Exception("Firebase credentials file is empty");
        }

        $this->credentials = json_decode($content, true);
        
        if (!$this->credentials || !isset($this->credentials['project_id'])) {
            throw new \Exception("Invalid Firebase credentials JSON");
        }

        $this->projectId = $this->credentials['project_id'];
        
        return $this->credentials;
    }

    /**
     * Get OAuth 2.0 access token for Firebase
     */
    protected function getAccessToken(): string
    {
        return Cache::remember('firebase_access_token', 3000, function () {
            // Ensure credentials are loaded
            $credentials = $this->getCredentials();
            
            // Create JWT
            $now = time();
            $jwtHeader = base64_encode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
            
            $jwtClaim = base64_encode(json_encode([
                'iss' => $credentials['client_email'],
                'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                'aud' => 'https://oauth2.googleapis.com/token',
                'exp' => $now + 3600,
                'iat' => $now,
            ]));

            // Sign with private key
            $signatureInput = "$jwtHeader.$jwtClaim";
            $privateKey = openssl_pkey_get_private($this->credentials['private_key']);
            openssl_sign($signatureInput, $signature, $privateKey, OPENSSL_ALGO_SHA256);
            $jwtSignature = base64_encode($signature);

            $jwt = "$jwtHeader.$jwtClaim.$jwtSignature";

            // Exchange JWT for access token
            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]);

            return $response->json()['access_token'];
        });
    }

    /**
     * Send notification to a single device
     */
    public function sendToUser(string $fcmToken, string $title, string $body, ?array $data = null): bool
    {
        try {
            $accessToken = $this->getAccessToken();
            
            $message = [
                'message' => [
                    'token' => $fcmToken,
                    'notification' => [
                        'title' => $title,
                        'body' => $body,
                    ],
                ],
            ];

            if ($data) {
                $message['message']['data'] = $data;
            }

            $response = Http::withHeaders([
                'Authorization' => "Bearer $accessToken",
                'Content-Type' => 'application/json',
            ])->post(
                "https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send",
                $message
            );

            if ($response->successful()) {
                Log::info('📱 FCM notification sent', [
                    'token' => substr($fcmToken, 0, 20) . '...',
                    'title' => $title,
                ]);
                return true;
            } else {
                Log::error('❌ FCM send failed', [
                    'status' => $response->status(),
                    'error' => $response->json(),
                ]);
                return false;
            }
        } catch (\Exception $e) {
            Log::error('❌ FCM exception', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Send notification to multiple devices
     */
    public function sendToMultiple(array $fcmTokens, string $title, string $body, ?array $data = null): array
    {
        $results = [];
        
        foreach ($fcmTokens as $token) {
            $results[$token] = $this->sendToUser($token, $title, $body, $data);
        }

        return $results;
    }

    /**
     * Send notification to a topic
     */
    public function sendToTopic(string $topic, string $title, string $body, ?array $data = null): bool
    {
        try {
            $accessToken = $this->getAccessToken();
            
            $message = [
                'message' => [
                    'topic' => $topic,
                    'notification' => [
                        'title' => $title,
                        'body' => $body,
                    ],
                ],
            ];

            if ($data) {
                $message['message']['data'] = $data;
            }

            $response = Http::withHeaders([
                'Authorization' => "Bearer $accessToken",
                'Content-Type' => 'application/json',
            ])->post(
                "https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send",
                $message
            );

            if ($response->successful()) {
                Log::info('📱 FCM topic notification sent', [
                    'topic' => $topic,
                    'title' => $title,
                ]);
                return true;
            }

            return false;
        } catch (\Exception $e) {
            Log::error('❌ FCM topic send failed', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
