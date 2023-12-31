<?php
// SPDX-License-Identifier: BSD-3-Clause

declare(strict_types=1);

namespace SingleA\Bundles\Singlea\Tests\Service\UserAttributes;

use PHPUnit\Framework\TestCase;
use SingleA\Bundles\Singlea\Service\UserAttributes\UserAttributesMarshaller;

/**
 * @covers \SingleA\Bundles\Singlea\Service\UserAttributes\UserAttributesMarshaller
 *
 * @internal
 */
final class UserAttributesMarshallerTest extends TestCase
{
    /**
     * @dataProvider provideInvalidKeysCases
     */
    public function testInvalidKeys(mixed $keys, string $expectedMessage): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedMessage);

        new UserAttributesMarshaller($keys);
    }

    public function provideInvalidKeysCases(): iterable
    {
        yield 'Invalid keys type' => [
            'keys' => 'key',
            'expectedMessage' => 'User keys must be provided as an array.',
        ];

        yield 'Empty keys' => [
            'keys' => [],
            'expectedMessage' => 'At least one user attributes key must be provided.',
        ];
    }

    /**
     * @dataProvider provideMarshallCases
     */
    public function testMarshall(array $keys, bool $useIgbinarySerialize, string $ticket, string $expected): void
    {
        $marshaller = new UserAttributesMarshaller($keys, $useIgbinarySerialize);
        $marshalled = $marshaller->marshall(['foo' => 'bar'], $ticket);

        self::assertSame($expected, base64_encode($marshalled));
    }

    public function provideMarshallCases(): iterable
    {
        yield 'Basic' => [
            'keys' => [base64_decode('1O5/GLhgoNlJM9X16CVJR1K8hjI5TtWQz0Ubj3hIwtA=', true)],
            'useIgbinarySerialize' => false,
            'ticket' => base64_decode('ttlr9I1DHLNnPKJQDLXh5nNpH/gUV94C', true),
            'expected' => 'VKgiw5u+Xen1XivnjY+dHHw8JJGbtf9vRrehywkeDJMCe/veX4SJUElR',
        ];

        yield 'Igbinary' => [
            'keys' => [base64_decode('1O5/GLhgoNlJM9X16CVJR1K8hjI5TtWQz0Ubj3hIwtA=', true)],
            'useIgbinarySerialize' => true,
            'ticket' => base64_decode('ttlr9I1DHLNnPKJQDLXh5nNpH/gUV94C', true),
            'expected' => 'keoPsoibsKGNm6Vxw7etqB0GFan0x9RfGvqotWVeVpI=',
        ];
    }

    /**
     * @dataProvider provideUnmarshallCases
     */
    public function testUnmarshall(array $keys, bool $useIgbinarySerialize, string $marshalled, string $ticket, array $expected): void
    {
        $marshaller = new UserAttributesMarshaller($keys, $useIgbinarySerialize);
        $unmarshalled = $marshaller->unmarshall($marshalled, $ticket);

        self::assertSame($expected, $unmarshalled);
    }

    public function provideUnmarshallCases(): iterable
    {
        yield 'Basic' => [
            'keys' => [
                base64_decode('1O5/GLhgoNlJM9X16CVJR1K8hjI5TtWQz0Ubj3hIwtA=', true),
                base64_decode('NOKe/gLlkVNtcVmRSBX6uny0lgJBKT7eI0csqMrtRck=', true),
            ],
            'useIgbinarySerialize' => false,
            'marshalled' => base64_decode('DFCvKZoOco1Di1MuOZ3Z8tMhyaYBPPHy5+CeH8Yrae+Qg+gq5e+0igX3', true),
            'ticket' => base64_decode('IVsgA+jzydkmq46lnAj3SLY5VX2UMwLf', true),
            'expected' => ['bar' => 'foo'],
        ];

        yield 'Igbinary' => [
            'keys' => [
                base64_decode('1O5/GLhgoNlJM9X16CVJR1K8hjI5TtWQz0Ubj3hIwtA=', true),
                base64_decode('NOKe/gLlkVNtcVmRSBX6uny0lgJBKT7eI0csqMrtRck=', true),
            ],
            'useIgbinarySerialize' => true,
            'marshalled' => base64_decode('Akr4GU26b+wMlKZdULmTZbIb+J5uTtrCv6OOb7dvPfM=', true),
            'ticket' => base64_decode('IVsgA+jzydkmq46lnAj3SLY5VX2UMwLf', true),
            'expected' => ['bar' => 'foo'],
        ];
    }

    public function testUnmarshallInvalidType(): void
    {
        $marshaller = new UserAttributesMarshaller(
            [base64_decode('1O5/GLhgoNlJM9X16CVJR1K8hjI5TtWQz0Ubj3hIwtA=', true)],
            false,
        );

        $marshalled = base64_decode('2hN7OXnMIQHOUyHef3HROW48JpHCoKozXq4=', true);
        $ticket = base64_decode('ttlr9I1DHLNnPKJQDLXh5nNpH/gUV94C', true);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Unmarshalled data type must be an array, get string.');

        $marshaller->unmarshall($marshalled, $ticket);
    }

    public function testUnmarshallUnknownClass(): void
    {
        $marshaller = new UserAttributesMarshaller(
            [base64_decode('1O5/GLhgoNlJM9X16CVJR1K8hjI5TtWQz0Ubj3hIwtA=', true)],
            false,
        );

        $marshalled = base64_decode('mfHNpD+8BWEeYCZnnjZvpVI8JJja5JAyF/uo0whgdIxZMrveB9XBCQ8=', true);
        $ticket = base64_decode('ttlr9I1DHLNnPKJQDLXh5nNpH/gUV94C', true);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Class Unknown\\Clazz not found. Maybe you forgot to require necessary feature package?');

        $marshaller->unmarshall($marshalled, $ticket);
    }

    public function testCannotDecrypt(): void
    {
        $marshaller = new UserAttributesMarshaller(
            [base64_decode('1O5/GLhgoNlJM9X16CVJR1K8hjI5TtWQz0Ubj3hIwtA=', true)],
            false,
        );

        $marshalled = base64_decode('2hN7OXnMIQHOUyHef3HROW48JpHCoKozXq4=', true);
        $ticket = base64_decode('IVsgA+jzydkmq46lnAj3SLY5VX2UMwLf', true);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot decrypt user attributes.');

        $marshaller->unmarshall($marshalled, $ticket);
    }
}
