<?php

namespace App\Services;

use OTPHP\TOTP;

class OtpService
{
    protected $secret;
    protected $otp;

    public function __construct()
    {
        $this->secret = env('OTP_SECRET');
        $this->otp = TOTP::create($this->secret, 60);
        $this->otp->setDigits(6);
    }

   
    public function generateTOTP()
    {
        $currentOtp = $this->otp->now();
        return $currentOtp;
    }

   
    public function verifyTOTP($userInput)
    {
        $isValid = $this->otp->verify($userInput, time(), 2);
        return $isValid;
    }
}
