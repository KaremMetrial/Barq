<?php

namespace App\Services;

use Exception;
use Twilio\Rest\Client;
use Illuminate\Support\Facades\Log;
use Twilio\Exceptions\TwilioException;

class TwilioSmsService
{
    protected Client $client;
    protected string $from;

    public function __construct()
    {
        $sid = config('services.twilio.sid');
        $token = config('services.twilio.token');
        $this->from = config('services.twilio.number');

        if (!$sid || !$token || !$this->from) {
            throw new Exception('Twilio credentials are missing.');
        }

        $this->client = new Client($sid, $token);
    }

    /**
     * Send OTP via WhatsApp (Content Template)
     */
    public function sendOtp(string $phone, string $otp): bool
    {
        try {
            $to = $this->formatWhatsAppNumber($phone);

            $message = $this->client->messages->create(
                $to,
                [
                    'from' => 'whatsapp:+14155238886',
                    'contentSid' => 'HX229f5a04fd0510ce1b071852155d3e75',
                    'contentVariables' => json_encode([
                        "1" => (string) $otp
                    ]),
                ]
            );

            Log::info('WhatsApp OTP sent', [
                'to' => $to,
                'sid' => $message->sid
            ]);

            return true;

        } catch (TwilioException $e) {
            Log::error('Twilio WhatsApp error', [
                'message' => $e->getMessage(),
                'to' => $phone
            ]);
            return false;

        } catch (Exception $e) {
            Log::error('Unexpected WhatsApp error', [
                'message' => $e->getMessage(),
                'to' => $phone
            ]);
            return false;
        }
    }

    /**
     * Format phone number for WhatsApp
     */
    private function formatWhatsAppNumber(string $number): string
    {
        $clean = preg_replace('/[^0-9+]/', '', $number);

        if (!str_starts_with($clean, '+')) {
            // default country code (Egypt example)
            $clean = '+20' . $clean;
        }

        return 'whatsapp:' . $clean;
    }

}
