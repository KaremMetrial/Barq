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
            $this->client->messages->create(
                "whatsapp:" . $receiverNumber,
                [
                    'from' => $this->whatsappFrom,
                    'contentSid' => 'HX229f5a04fd0510ce1b071852155d3e75',
                    'contentVariables' => json_encode([
                        "1" => $otp
                    ]),
                ]
            );

            return true;

        } catch (TwilioException $e) {
            Log::error('Twilio WhatsApp OTP failed', [
                'error' => $e->getMessage(),
                'to' => $receiverNumber
            ]);
            return false;

        } catch (Exception $e) {
            Log::error('Unexpected WhatsApp error', [
                'error' => $e->getMessage(),
                'to' => $receiverNumber
            ]);
            return false;
        }
    }
}
