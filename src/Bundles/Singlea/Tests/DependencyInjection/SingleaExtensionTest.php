<?php declare(strict_types=1);
// SPDX-License-Identifier: BSD-3-Clause

namespace SingleA\Bundles\Singlea\Tests\DependencyInjection;

use PHPUnit\Framework\TestCase;
use SingleA\Bundles\Singlea\Command;
use SingleA\Bundles\Singlea\Controller;
use SingleA\Bundles\Singlea\DependencyInjection\SingleaExtension;
use SingleA\Bundles\Singlea\EventListener;
use SingleA\Bundles\Singlea\FeatureConfig;
use SingleA\Bundles\Singlea\Security;
use SingleA\Bundles\Singlea\Service;
use SingleA\Contracts;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @covers \SingleA\Bundles\Singlea\DependencyInjection\SingleaExtension
 *
 * @internal
 */
final class SingleaExtensionTest extends TestCase
{
    public function testLoad(): void
    {
        $container = new ContainerBuilder();
        $extension = new SingleaExtension();

        $container->registerExtension($extension);
        $extension->load([
            'singlea' => [
                'ticket' => ['domain' => 'example.test'],
            ],
        ], $container);

        $services = [
            Command\Client\Purge::class,
            Command\Client\Register::class,
            Command\Client\Remove::class,

            Controller\Feature\Login::class,
            Controller\Feature\Token::class,

            EventListener\ExceptionListener::class,
            EventListener\RealmListener::class,
            EventListener\ClientListener::class,
            EventListener\TicketListener::class,
            EventListener\Security\SuccessfulLoginListener::class,
            EventListener\Security\LogoutListener::class,

            FeatureConfig\ConfigRetrieverInterface::class,

            Service\Authentication\AuthenticationServiceInterface::class,
            Service\Client\RegistrationServiceInterface::class,
            Service\Marshaller\FeatureConfigMarshallerFactory::class,
            Contracts\Marshaller\FeatureConfigEncryptorInterface::class,
            Service\Realm\RealmResolverInterface::class,
            Service\Signature\SignatureServiceInterface::class,
            Service\Tokenization\PayloadComposerInterface::class,
            Service\UserAttributes\UserAttributesMarshallerInterface::class,
            Service\UserAttributes\UserAttributesManagerInterface::class,

            Security\GrantedVoter::class,
        ];

        foreach ($services as $id) {
            self::assertTrue($container->hasDefinition($id));
        }

        $parameters = $container->getParameterBag()->all();
        $assertKeys = [
            'singlea.client.id_query_parameter',
            'singlea.client.secret_query_parameter',
            'singlea.client.trusted_clients',
            'singlea.client.trusted_registrars',
            'singlea.client.registration_ticket_header',

            'singlea.ticket.header',
            'singlea.ticket.cookie_name',
            'singlea.ticket.ttl',
            'singlea.ticket.domain',

            'singlea.authentication.sticky_session',
            'singlea.authentication.redirect_uri_query_parameter',

            'singlea.signature.request_ttl',
            'singlea.signature.signature_query_parameter',
            'singlea.signature.timestamp_query_parameter',
            'singlea.signature.extra_exclude_query_parameters',

            'singlea.encryption.client_keys',
            'singlea.encryption.user_keys',

            'singlea.realm.default',
            'singlea.realm.query_parameter',

            'singlea.marshaller.use_igbinary',

            'singlea.user_attributes.use_igbinary',
        ];

        foreach ($assertKeys as $key) {
            self::assertArrayHasKey($key, $parameters);
        }
    }
}
