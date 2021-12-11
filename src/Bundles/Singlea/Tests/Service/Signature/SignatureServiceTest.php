<?php declare(strict_types=1);
// SPDX-License-Identifier: BSD-3-Clause

namespace SingleA\Bundles\Singlea\Tests\Service\Signature;

use PHPUnit\Framework\TestCase;
use SingleA\Bundles\Singlea\FeatureConfig\Signature\SignatureConfig;
use SingleA\Bundles\Singlea\FeatureConfig\Signature\SignatureConfigInterface;
use SingleA\Bundles\Singlea\Service\Signature\SignatureService;
use Symfony\Component\HttpFoundation\Request;

/**
 * @covers \SingleA\Bundles\Singlea\Service\Signature\SignatureService
 *
 * @internal
 */
final class SignatureServiceTest extends TestCase
{
    private static string $privateKey = "-----BEGIN RSA PRIVATE KEY-----\nMIIBOgIBAAJBAN6/X16pqe5ueT/XmBi/UpIhL348m5LLdi6CKTNk1jvCVbihs/xi\nG7GBrHeru6rkT30DcMKjXLOmxVEDF0GtvScCAwEAAQJBAM+DnBa1m3FcjCr08GaF\nvygSMIu7bPg6ApTbgAS4OXmbGbbJ6geA+TRWrXcIxqH/hM+wS39Pk90fmqZLlXl7\nhXkCIQD9++QqQcJ/6c5Q0KqPOgR4fDsVgfpKrx0ECMRS2zNE6wIhAOCEAdvJi8+I\nQOd1c470e1ToZnaM3vpxK8m4BQXhlUm1AiB+xOY6bT4uaD2xOqWW/YdTt/YpowmR\nk1vxMosDLCOn5wIgNHIBsSLCewccCjVgehtYF/x1uumrSJtZHDTVT4tjgSUCIGO/\nVulHsXVLXFWuU6nSGJ9ZnloXe/M7JCRG2MgfGges\n-----END RSA PRIVATE KEY-----";
    private static string $publicKey = "-----BEGIN PUBLIC KEY-----\nMFwwDQYJKoZIhvcNAQEBBQADSwAwSAJBAN6/X16pqe5ueT/XmBi/UpIhL348m5LL\ndi6CKTNk1jvCVbihs/xiG7GBrHeru6rkT30DcMKjXLOmxVEDF0GtvScCAwEAAQ==\n-----END PUBLIC KEY-----";

    public function testValidCheck(): void {
        $timestamp = time() - 15;
        openssl_sign('4321.secret-value.'.$timestamp, $signature, self::$privateKey, \OPENSSL_ALGO_SHA224);

        $config = new SignatureConfig(\OPENSSL_ALGO_SHA224, self::$publicKey, 0);

        $request = Request::create('', parameters: [
            'timestamp' => $timestamp,
            'signature' => sodium_bin2base64($signature, \SODIUM_BASE64_VARIANT_URLSAFE),
            'client_id' => '4321',
            'secret' => 'secret-value',
        ]);

        $service = new SignatureService(30, 'timestamp', 'signature');

        self::assertNull($service->check($request, $config));
    }

    /**
     * @dataProvider failedCheckProvider
     */
    public function testFailedCheck(
        Request $request,
        SignatureConfigInterface $config,
        string $expectedMessage,
    ): void {
        $service = new SignatureService(20, 'ts', 'sg');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage($expectedMessage);

        $service->check($request, $config);
    }

    public function failedCheckProvider(): \Generator
    {
        yield 'No timestamp' => [
            'request' => Request::create(''),
            'config' => $this->createStub(SignatureConfigInterface::class),
            'expectedMessage' => 'Request does not contain timestamp.',
        ];

        yield 'No signature' => [
            'request' => Request::create('', parameters: [
                'ts' => 1644217738,
            ]),
            'config' => $this->createStub(SignatureConfigInterface::class),
            'expectedMessage' => 'Request does not contain signature.',
        ];

        yield 'Timed out' => [
            'request' => Request::create('', parameters: [
                'ts' => time() - 100,
                'sg' => '',
            ]),
            'config' => new SignatureConfig(\OPENSSL_ALGO_SHA256, self::$publicKey, 80),
            'expectedMessage' => 'Request timed out.',
        ];

        $timestamp = time() - 5;
        openssl_sign('4321.secret-value.'.$timestamp, $signature, self::$privateKey, \OPENSSL_ALGO_SHA256);

        yield 'Invalid signature' => [
            'request' => Request::create('', parameters: [
                'ts' => $timestamp,
                'sg' => sodium_bin2base64(strrev($signature), \SODIUM_BASE64_VARIANT_URLSAFE),
                'client_id' => '4321',
                'secret' => 'secret-value',
            ]),
            'config' => new SignatureConfig(\OPENSSL_ALGO_SHA256, self::$publicKey, 0),
            'expectedMessage' => 'Signature is invalid.',
        ];

        yield 'Verify error' => [
            'request' => Request::create('', parameters: [
                'ts' => $timestamp,
                'sg' => sodium_bin2base64($signature, \SODIUM_BASE64_VARIANT_URLSAFE),
                'client_id' => '4321',
                'secret' => 'secret-value',
            ]),
            'config' => new SignatureConfig(\OPENSSL_ALGO_SHA256, str_replace('-----BEGIN PUBLIC KEY-----', '', self::$publicKey), 0),
            'expectedMessage' => 'Error occurred while signature verify: openssl_verify(): Supplied key param cannot be coerced into a public key',
        ];
    }
}
