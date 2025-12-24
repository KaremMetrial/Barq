<?php

namespace App\Services;

use Exception;
use Twilio\Rest\Client;
use Illuminate\Support\Facades\Log;
use Twilio\Exceptions\TwilioException;

class TwilioSmsService
{
    protected Client $client;
    protected string $whatsappFrom;

    public function __construct()
    {
        $sid = config('services.twilio.sid');
        $token = config('services.twilio.token');
        $this->whatsappFrom = config('services.twilio.whatsapp_from');

        if (!$sid || !$token || !$this->whatsappFrom) {
            throw new \InvalidArgumentException('Twilio credentials are missing.');
        }

        $this->client = new Client($sid, $token);
    }

    /**
     * Send OTP via WhatsApp
     */
    public function sendOtp(string $receiverNumber, string $otp): bool
    {
        try {
            // Ensure the receiver number is properly formatted for WhatsApp
            $formattedNumber = $this->formatWhatsAppNumber($receiverNumber);

            // Try sending with content template first (if configured)
            try {
                $message = $this->client->messages->create(
                    $formattedNumber,
                    [
                        'from' => $this->whatsappFrom,
                        'contentSid' => 'HX229f5a04fd0510ce1b071852155d3e75', // Your template SID
                        'contentVariables' => json_encode([
                            "1" => $otp
                        ]),
                    ]
                );
            } catch (TwilioException $e) {
                // If content template fails, fall back to simple message
                Log::warning('Twilio content template failed, falling back to simple message', [
                    'error' => $e->getMessage(),
                    'to' => $formattedNumber
                ]);

                $message = $this->client->messages->create(
                    $formattedNumber,
                    [
                        'from' => $this->whatsappFrom,
                        'body' => "Your OTP code is: {$otp}. This code will expire in 10 minutes."
                    ]
                );
            }

            Log::info('Twilio WhatsApp OTP sent successfully', [
                'to' => $formattedNumber,
                'sid' => $message->sid ?? 'unknown'
            ]);

            return true;

        } catch (TwilioException $e) {
            Log::error('Twilio WhatsApp OTP failed', [
                'error' => $e->getMessage(),
                'to' => $receiverNumber,
                'formatted_number' => $this->formatWhatsAppNumber($receiverNumber)
            ]);
            return false;

        } catch (Exception $e) {
            Log::error('Unexpected WhatsApp error', [
                'error' => $e->getMessage(),
                'to' => $receiverNumber,
                'formatted_number' => $this->formatWhatsAppNumber($receiverNumber)
            ]);
            return false;
        }
    }

    /**
     * Format phone number for WhatsApp API
     */
    private function formatWhatsAppNumber(string $number): string
    {
        // Remove any whitespace, parentheses, dashes
        $cleanNumber = preg_replace('/[^0-9+]/', '', $number);

        // Ensure it starts with +
        if (substr($cleanNumber, 0, 1) !== '+') {
            // If it doesn't start with +, assume it's missing the country code
            // This is a basic assumption - you might want to make this configurable
            if (strlen($cleanNumber) === 10) {
                // US number without country code
                $cleanNumber = '+1' . $cleanNumber;
            } else {
                // Add your default country code here if needed
                // For example, if you're targeting Egypt: $cleanNumber = '+20' . $cleanNumber;
            }
        }

        return "whatsapp:" . $cleanNumber;
    }
}
