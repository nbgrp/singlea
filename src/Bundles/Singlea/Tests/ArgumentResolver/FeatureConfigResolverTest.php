<?php
// SPDX-License-Identifier: BSD-3-Clause

declare(strict_types=1);

namespace SingleA\Bundles\Singlea\Tests\ArgumentResolver;

use PHPUnit\Framework\TestCase;
use SingleA\Bundles\Singlea\ArgumentResolver\FeatureConfigResolver;
use SingleA\Bundles\Singlea\EventListener\ClientListener;
use SingleA\Bundles\Singlea\FeatureConfig\ConfigRetrieverInterface;
use SingleA\Bundles\Singlea\Tests\TestConfigInterface;
use SingleA\Contracts\FeatureConfig\FeatureConfigInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

/**
 * @covers \SingleA\Bundles\Singlea\ArgumentResolver\FeatureConfigResolver
 *
 * @internal
 */
final class FeatureConfigResolverTest extends TestCase
{
    /**
     * @dataProvider provideUnsuitableArgumentCases
     */
    public function testUnsuitableArgument(ArgumentMetadata $argument): void
    {
        $configResolver = new FeatureConfigResolver($this->createStub(ConfigRetrieverInterface::class));

        self::assertSame([], $configResolver->resolve(Request::create(''), $argument));
    }

    public function provideUnsuitableArgumentCases(): iterable
    {
        yield 'Not subclass' => [
            'argument' => new ArgumentMetadata('', FeatureConfigInterface::class, false, false, null),
            'expected' => false,
        ];

        yield 'Invalid type' => [
            'argument' => new ArgumentMetadata('', 'string', false, false, null),
            'expected' => false,
        ];

        yield 'No type' => [
            'argument' => new ArgumentMetadata('', null, false, false, null),
            'expected' => false,
        ];
    }

    public function testSuccessfulResolve(): void
    {
        $config = $this->createStub(TestConfigInterface::class);

        $configRetriever = $this->createMock(ConfigRetrieverInterface::class);
        $configRetriever
            ->expects(self::exactly(2))
            ->method('find')
            ->with(TestConfigInterface::class, 'client', 'secret')
            ->willReturnOnConsecutiveCalls(
                $config,
                null,
            )
        ;

        $request = Request::create('');
        $request->attributes->add([
            ClientListener::CLIENT_ID_ATTRIBUTE => 'client',
            ClientListener::SECRET_ATTRIBUTE => 'secret',
        ]);

        $argument = new ArgumentMetadata('', TestConfigInterface::class, false, false, null, true);

        $configResolver = new FeatureConfigResolver($configRetriever);

        $resolved = $configResolver->resolve($request, $argument);
        self::assertSame($config, $resolved[0]);

        $resolved = $configResolver->resolve($request, $argument);
        self::assertNull($resolved[0]);
    }

    public function testFailedResolve(): void
    {
        $configRetriever = $this->createMock(ConfigRetrieverInterface::class);
        $configRetriever
            ->expects(self::once())
            ->method('find')
            ->with(TestConfigInterface::class, 'client', 'secret')
            ->willReturn(null)
        ;

        $request = Request::create('');
        $request->attributes->add([
            ClientListener::CLIENT_ID_ATTRIBUTE => 'client',
            ClientListener::SECRET_ATTRIBUTE => 'secret',
        ]);

        $argument = new ArgumentMetadata('invalid', TestConfigInterface::class, false, false, null, false);

        $configResolver = new FeatureConfigResolver($configRetriever);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Argument "invalid" cannot be resolved.');

        $configResolver->resolve($request, $argument);
    }
}
