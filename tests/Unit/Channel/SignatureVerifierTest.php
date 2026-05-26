<?php
/**
 * @package BotCat\Tests\Unit\Channel
 */

declare(strict_types=1);

namespace BotCat\Tests\Unit\Channel;

use BotCat\Channel\SignatureVerifier;
use BotCat\Tests\TestCase;

/**
 * Covers Requirement: Signature verification on every webhook hit.
 */
final class SignatureVerifierTest extends TestCase
{
    private const SECRET = 'channel-secret-value';

    public function testValidSignatureIsAccepted(): void
    {
        $body = '{"events":[{"type":"follow"}]}';
        $sig  = base64_encode(hash_hmac('sha256', $body, self::SECRET, true));

        $verifier = new SignatureVerifier(self::SECRET);

        $this->assertTrue($verifier->verify($body, $sig));
    }

    public function testTamperedBodyIsRejected(): void
    {
        $body = '{"events":[{"type":"follow"}]}';
        $sig  = base64_encode(hash_hmac('sha256', $body, self::SECRET, true));

        $verifier = new SignatureVerifier(self::SECRET);
        $tampered = '{"events":[{"type":"unfollow"}]}';

        $this->assertFalse($verifier->verify($tampered, $sig));
    }

    public function testMissingSignatureIsRejected(): void
    {
        $verifier = new SignatureVerifier(self::SECRET);

        $this->assertFalse($verifier->verify('{}', ''));
        $this->assertFalse($verifier->verify('{}', null));
    }

    public function testWrongSecretIsRejected(): void
    {
        $body = '{"events":[{"type":"follow"}]}';
        $sig  = base64_encode(hash_hmac('sha256', $body, 'other-secret', true));

        $verifier = new SignatureVerifier(self::SECRET);

        $this->assertFalse($verifier->verify($body, $sig));
    }
}
