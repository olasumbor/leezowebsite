<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MailjetService
{
    protected string $apiKey;
    protected string $apiSecret;
    protected string $fromAddress;
    protected string $fromName;

    public function __construct()
    {
        $this->apiKey = env('MAILJET_API_KEY', '4ea087f5ff2c1e45fc29546fc2e2896e');
        $this->apiSecret = env('MAILJET_API_SECRET', '1860ef96b32ca8ffa61410804d9f7648');
        $this->fromAddress = env('MAIL_FROM_ADDRESS', 'dadaadeife@gmail.com');
        $this->fromName = env('MAIL_FROM_NAME', 'Leezo Foods');
    }

    /**
     * Send email using official Mailjet Template ID via Send API v3.1
     */
    public function sendTemplate(string $toEmail, string $toName, int $templateId, array $variables = [], string $subject = '')
    {
        try {
            $messageData = [
                'From' => [
                    'Email' => $this->fromAddress,
                    'Name' => $this->fromName,
                ],
                'To' => [
                    [
                        'Email' => $toEmail,
                        'Name' => $toName ?: $toEmail,
                    ]
                ],
                'TemplateID' => (int) $templateId,
                'TemplateLanguage' => true,
                'Variables' => (object) $variables,
            ];

            if (!empty($subject)) {
                $messageData['Subject'] = $subject;
            }

            $payload = [
                'Messages' => [$messageData]
            ];

            $response = Http::withBasicAuth($this->apiKey, $this->apiSecret)
                ->asJson()
                ->post('https://api.mailjet.com/v3.1/send', $payload);

            if ($response->successful()) {
                Log::info("Mailjet template {$templateId} successfully sent to {$toEmail}");
                return true;
            } else {
                Log::error("Mailjet API error sending template {$templateId} to {$toEmail}: " . $response->body());
                return false;
            }
        } catch (\Throwable $e) {
            Log::error("Mailjet exception for template {$templateId} to {$toEmail}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send email using Mailjet REST API with HTML content
     */
    public function sendRaw(string $toEmail, string $toName, string $subject, string $htmlPart, string $textPart = '')
    {
        try {
            $payload = [
                'Messages' => [
                    [
                        'From' => [
                            'Email' => $this->fromAddress,
                            'Name' => $this->fromName,
                        ],
                        'To' => [
                            [
                                'Email' => $toEmail,
                                'Name' => $toName ?: $toEmail,
                            ]
                        ],
                        'Subject' => $subject,
                        'HTMLPart' => $htmlPart,
                        'TextPart' => $textPart ?: strip_tags($htmlPart),
                    ]
                ]
            ];

            $response = Http::withBasicAuth($this->apiKey, $this->apiSecret)
                ->asJson()
                ->post('https://api.mailjet.com/v3.1/send', $payload);

            if ($response->successful()) {
                Log::info("Mailjet email '{$subject}' successfully sent to {$toEmail}");
                return true;
            } else {
                Log::error("Mailjet API error sending raw email to {$toEmail}: " . $response->body());
                return false;
            }
        } catch (\Throwable $e) {
            Log::error("Mailjet exception sending raw email to {$toEmail}: " . $e->getMessage());
            return false;
        }
    }
}
