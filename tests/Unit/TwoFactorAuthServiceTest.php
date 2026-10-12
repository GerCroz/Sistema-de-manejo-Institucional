<?php

namespace Tests\Unit;

use App\Services\TwoFactorAuthService;
use PHPUnit\Framework\TestCase;

class TwoFactorAuthServiceTest extends TestCase
{
    public function test_can_generate_secret_and_verify_otp(): void
    {
        $service = new TwoFactorAuthService;
        $secret = $service->generateSecretKey();

        $this->assertSame(16, strlen($secret));

        $otp = $service->getOtp($secret);
        $this->assertSame(6, strlen($otp));
        $this->assertTrue(ctype_digit($otp));

        $this->assertTrue($service->verify($secret, $otp));
        $this->assertFalse($service->verify($secret, '000000'));
    }

    public function test_can_generate_recovery_codes(): void
    {
        $service = new TwoFactorAuthService;
        $codes = $service->generateRecoveryCodes(8);

        $this->assertCount(8, $codes);
        $this->assertMatchesRegularExpression('/^[A-Z0-9]{5}-[A-Z0-9]{5}$/', $codes[0]);
    }
}
