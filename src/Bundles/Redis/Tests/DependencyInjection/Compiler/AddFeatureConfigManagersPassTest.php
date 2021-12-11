<?php declare(strict_types=1);
// SPDX-License-Identifier: BSD-3-Clause

namespace SingleA\Bundles\Redis\Tests\DependencyInjection\Compiler;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use SingleA\Bundles\Redis\DependencyInjection\Compiler\AddFeatureConfigManagersPass;
use SingleA\Bundles\Redis\FeatureConfigManagerFactory;
use SingleA\Contracts\FeatureConfig\FeatureConfigInterface;
use SingleA\Contracts\Marshaller\FeatureConfigEncryptorInterface;
use SingleA\Contracts\Marshaller\FeatureConfigMarshallerInterface;
use SingleA\Contracts\Persistence\FeatureConfigManagerInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

/**
 * @covers \SingleA\Bundles\Redis\DependencyInjection\Compiler\AddFeatureConfigManagersPass
 * @covers \SingleA\Bundles\Redis\FeatureConfigManagerFactory
 *
 * @internal
 */
final class AddFeatureConfigManagersPassTest extends TestCase
{
    public function testSuccessfulProcess(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('singlea_redis.config_managers', [
            'signature' => [
                'key' => 'signature',
                'config_marshaller' => 'singlea.signature_marshaller',
                'required' => true,
            ],
            'tokenizer' => [
                'key' => 'tokenizer',
                'config_marshaller' => 'singlea.tokenizer_marshaller',
                'required' => false,
            ],
        ]);
        $container->setDefinition(FeatureConfigManagerFactory::class, new Definition(FeatureConfigManagerFactory::class));

        $signatureConfig = $this->createStub(FeatureConfigInterface::class);
        $tokenizerConfig = $this->createStub(FeatureConfigInterface::class);

        $redisClient = $this->createMock(\Redis::class);
        $redisClient
            ->expects(self::exactly(2))
            ->method('hExists')
            ->withConsecutive(
                [self::equalTo('signature'), self::equalTo('id1')],
                [self::equalTo('tokenizer'), self::equalTo('id2')],
            )
            ->willReturnOnConsecutiveCalls(
                true,
                false,
            )
        ;
        $redisClient
            ->expects(self::exactly(2))
            ->method('hSet')
            ->withConsecutive(
                [self::equalTo('signature'), self::equalTo('id1'), self::equalTo('encrypted-signature-config-marshalled-with-signature-secret')],
                [self::equalTo('tokenizer'), self::equalTo('id2'), self::equalTo('encrypted-tokenizer-config-marshalled-with-tokenizer-secret')],
            )
        ;
        $redisClient
            ->expects(self::exactly(2))
            ->method('hGet')
            ->withConsecutive(
                ['signature', 'id1'],
                ['tokenizer', 'id2'],
            )
            ->willReturnOnConsecutiveCalls(
                'encrypted-signature-config-marshalled-with-signature-secret',
                false,
            )
        ;
        $redisClient
            ->expects(self::once())
            ->method('hDel')
            ->with('signature', 'id1', 'id2')
            ->willReturn(1)
        ;
        $container->set('singlea_redis.snc_redis_client', $redisClient);

        $signatureMarshaller = $this->createMock(FeatureConfigMarshallerInterface::class);
        $signatureMarshaller
            ->expects(self::once())
            ->method('supports')
            ->with('signature-class')
            ->willReturn(false)
        ;
        $signatureMarshaller
            ->expects(self::once())
            ->method('marshall')
            ->with($signatureConfig)
            ->willReturn('signature-config-marshalled')
        ;
        $signatureMarshaller
            ->expects(self::once())
            ->method('unmarshall')
            ->with('signature-config-marshalled')
            ->willReturn($signatureConfig)
        ;
        $container->set('singlea.signature_marshaller', $signatureMarshaller);

        $tokenizerMarshaller = $this->createMock(FeatureConfigMarshallerInterface::class);
        $tokenizerMarshaller
            ->expects(self::once())
            ->method('supports')
            ->with(FeatureConfigInterface::class)
            ->willReturn(true)
        ;
        $tokenizerMarshaller
            ->expects(self::once())
            ->method('marshall')
            ->with($tokenizerConfig)
            ->willReturn('tokenizer-config-marshalled')
        ;
        $tokenizerMarshaller
            ->expects(self::never())
            ->method('unmarshall')
        ;
        $container->set('singlea.tokenizer_marshaller', $tokenizerMarshaller);

        $encryptor = $this->createMock(FeatureConfigEncryptorInterface::class);
        $encryptor
            ->expects(self::exactly(2))
            ->method('encrypt')
            ->withConsecutive(
                ['signature-config-marshalled', 'signature-secret'],
                ['tokenizer-config-marshalled', 'tokenizer-secret'],
            )
            ->willReturnCallback(static fn (string $marshalled, string $secret): string => 'encrypted-'.$marshalled.'-with-'.$secret)
        ;
        $encryptor
            ->expects(self::once())
            ->method('decrypt')
            ->with('encrypted-signature-config-marshalled-with-signature-secret')
            ->willReturn('signature-config-marshalled')
        ;
        $container->set(FeatureConfigEncryptorInterface::class, $encryptor);

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects(self::exactly(3))
            ->method('debug')
        ;
        $container->set(LoggerInterface::class, $logger);

        (new AddFeatureConfigManagersPass())->process($container);

        $container->getDefinition('singlea.feature_config_manager.signature')
            ->setPublic(true)
        ;
        $container->getDefinition('singlea.feature_config_manager.tokenizer')
            ->setPublic(true)
        ;
        $container->compile();

        $signatureConfigManager = $container->get('singlea.feature_config_manager.signature');
        $tokenizerConfigManager = $container->get('singlea.feature_config_manager.tokenizer');

        self::assertInstanceOf(FeatureConfigManagerInterface::class, $signatureConfigManager);
        self::assertTrue($signatureConfigManager->isRequired());
        self::assertFalse($signatureConfigManager->supports('signature-class'));
        self::assertTrue($signatureConfigManager->exists('id1'));
        $signatureConfigManager->persist('id1', $signatureConfig, 'signature-secret');
        self::assertSame($signatureConfig, $signatureConfigManager->find('id1', 'signature-secret'));
        self::assertSame(1, $signatureConfigManager->remove('id1', 'id2'));

        self::assertInstanceOf(FeatureConfigManagerInterface::class, $tokenizerConfigManager);
        self::assertFalse($tokenizerConfigManager->isRequired());
        self::assertTrue($tokenizerConfigManager->supports(FeatureConfigInterface::class));
        self::assertFalse($tokenizerConfigManager->exists('id2'));
        $tokenizerConfigManager->persist('id2', $tokenizerConfig, 'tokenizer-secret');
        self::assertNull($tokenizerConfigManager->find('id2', 'tokenizer-secret'));
        self::assertSame(0, $tokenizerConfigManager->remove());
    }

    public function testInvalidSettingsProcess(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('singlea_redis.config_managers', [
            'signature' => false,
        ]);

        $pass = new AddFeatureConfigManagersPass();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Config manager "signature" settings must be present as an array.');

        $pass->process($container);
    }

    public function testSettingsWithoutKeyValueProcess(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('singlea_redis.config_managers', [
            'signature' => [
                'config_marshaller' => 'singlea.signature_marshaller',
            ],
        ]);

        $pass = new AddFeatureConfigManagersPass();

        $this->expectException(\UnderflowException::class);
        $this->expectExceptionMessage('Config manager "signature" settings has no key value.');

        $pass->process($container);
    }

    public function testSettingsWithoutConfigMarshallerValueProcess(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('singlea_redis.config_managers', [
            'signature' => [
                'key' => 'signature',
            ],
        ]);

        $pass = new AddFeatureConfigManagersPass();

        $this->expectException(\UnderflowException::class);
        $this->expectExceptionMessage('Config manager "signature" settings has no config_marshaller value.');

        $pass->process($container);
    }
}
