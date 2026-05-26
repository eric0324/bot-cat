<?php
/**
 * @package BotCat\Tests\Unit\Channel
 */

declare(strict_types=1);

namespace BotCat\Tests\Unit\Channel;

use BotCat\Channel\CredentialEncryptor;
use BotCat\Tests\TestCase;

/**
 * Covers Requirement: Credentials at rest are encrypted.
 */
final class CredentialEncryptorTest extends TestCase
{
    public function testEncryptedValueIsNotPlaintext(): void
    {
        $enc = new CredentialEncryptor('site-A-auth-key-32-chars-padding-AAA');
        $cipher = $enc->encrypt('s3cret-token');

        $this->assertNotSame('s3cret-token', $cipher);
        $this->assertStringStartsWith('botcat1:', $cipher, 'ciphertext should carry scheme identifier');
    }

    public function testEncryptDecryptRoundTripsToOriginal(): void
    {
        $enc = new CredentialEncryptor('site-A-auth-key-32-chars-padding-AAA');
        $cipher = $enc->encrypt('s3cret-token');

        $this->assertSame('s3cret-token', $enc->decrypt($cipher));
    }

    public function testDecryptionWithDifferentKeyReturnsNull(): void
    {
        $alice = new CredentialEncryptor('alice-key-32-chars-padding-AAA-xyz');
        $cipher = $alice->encrypt('s3cret-token');

        $bob = new CredentialEncryptor('bob-key-32-chars-padding-BBB-uvw-zz');
        $this->assertNull($bob->decrypt($cipher), 'wrong key must not decrypt successfully');
    }

    public function testEmptyStringPassesThroughBothWays(): void
    {
        $enc = new CredentialEncryptor('site-A-auth-key-32-chars-padding-AAA');

        $this->assertSame('', $enc->encrypt(''));
        $this->assertSame('', $enc->decrypt(''));
    }

    public function testGarbledCiphertextReturnsNull(): void
    {
        $enc = new CredentialEncryptor('site-A-auth-key-32-chars-padding-AAA');

        $this->assertNull($enc->decrypt('botcat1:not-valid-base64-+++'));
        $this->assertNull($enc->decrypt('no-prefix-at-all'));
    }
}
