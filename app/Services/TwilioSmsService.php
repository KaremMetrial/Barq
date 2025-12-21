<?php

namespace App\Services;

use Exception;
use Twilio\Rest\Client;
use Illuminate\Support\Facades\Log;
use Twilio\Exceptions\TwilioException;

class TwilioSmsService
{
    protected $client;
    protected $from;

    /**
     * TwilioSmsService constructor.
     * @throws \InvalidArgumentException
     */
    public function __construct()
    {
        $accountSid = config('services.twilio.sid');
        $authToken = config('services.twilio.token');
        $this->from = config('services.twilio.number');

        if (empty($accountSid) || empty($authToken) || empty($this->from)) {
            throw new \InvalidArgumentException('Twilio credentials are not configured.');
        }

        $this->client = new Client($accountSid, $authToken);
    }

    /**
     * Send a generic SMS.
     *
     * @param string $receiverNumber
     * @param string $message
     * @return bool
     */
    public function sendSms(string $receiverNumber, string $message): bool
    {
        try {
            $this->client->messages->create($receiverNumber, [
                'from' => $this->from,
                'body' => $message,
            ]);

            return true;
        } catch (TwilioException $e) {
            Log::error('Twilio SMS sending failed: ' . $e->getMessage(), [
                'to' => $receiverNumber
            ]);
            return false;
        } catch (Exception $e) {
            Log::error('An unexpected error occurred while sending SMS via Twilio: ' . $e->getMessage(), [
                'to' => $receiverNumber
            ]);
            return false;
        }
    }

    /**
     * Send an OTP code.
     *
     * @param string $receiverNumber
     * @param string $otp
     * @return bool
     */
    public function sendOtp(string $receiverNumber, string $otp): bool
    {
        // It's good practice to use language files for this message.
        // For simplicity here, it's hardcoded.
        $message = "Your verification code is: {$otp}";
        return $this->sendSms($receiverNumber, $message);
    }
}
