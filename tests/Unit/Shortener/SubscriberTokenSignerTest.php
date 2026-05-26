<?php
/**
 * @package BotCat\Tests\Unit\Shortener
 */

declare(strict_types=1);

namespace BotCat\Tests\Unit\Shortener;

use BotCat\Shortener\SubscriberTokenSigner;
use BotCat\Tests\TestCase;

final class SubscriberTokenSignerTest extends TestCase
{
    public function testSignVerifyRoundTrips(): void
    {
        $signer = new SubscriberTokenSigner('site-key');

        $token = $signer->sign(42);
        $this->assertSame(42, $signer->verify($token));
    }

    public function testTokenIsUrlSafe(): void
    {
        $token = (new SubscriberTokenSigner('site-key'))->sign(42);

        $this->assertMatchesRegularExpression('/^[A-Za-z0-9_-]+$/', $token);
    }

    public function testTamperedTokenFailsVerification(): void
    {
        $signer = new SubscriberTokenSigner('site-key');
        $token  = $signer->sign(42);

        // Flip a single character.
        $tampered = $token[0] === 'A' ? 'B' . substr($token, 1) : 'A' . substr($token, 1);

        $this->assertNull($signer->verify($tampered));
    }

    public function testTokenSignedByOtherKeyDoesNotVerify(): void
    {
        $alice = new SubscriberTokenSigner('alice-key');
        $bob   = new SubscriberTokenSigner('bob-key');

        $this->assertNull($bob->verify($alice->sign(42)));
    }

    public function testEmptyOrMalformedTokenReturnsNull(): void
    {
        $signer = new SubscriberTokenSigner('site-key');

        $this->assertNull($signer->verify(''));
        $this->assertNull($signer->verify('garbage'));
        $this->assertNull($signer->verify('no.dot.expected'));
    }
}
