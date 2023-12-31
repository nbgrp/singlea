<?php
// SPDX-License-Identifier: BSD-3-Clause

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use SingleA\Bundles\Singlea;
use SingleA\Contracts;

return static function (ContainerConfigurator $container): void {
    $src = \dirname(__DIR__, 2);
    $container->services()
        ->defaults()
            ->autoconfigure()

        ->load('SingleA\\Bundles\\Singlea\\', $src.'/*')
            ->exclude($src.'/{DependencyInjection,Event,Resources}/')

        ->set(Singlea\ArgumentResolver\FeatureConfigResolver::class)
            ->args([
                service(Singlea\FeatureConfig\ConfigRetrieverInterface::class),
            ])

        ->set(Singlea\Command\Client\Oldest::class)
            ->args([
                service(Contracts\Persistence\ClientManagerInterface::class),
            ])

        ->set(Singlea\Command\Client\Purge::class)
            ->args([
                tagged_iterator('singlea.config_manager'),
                service(Contracts\Persistence\ClientManagerInterface::class),
            ])

        ->set(Singlea\Command\Client\Register::class)
            ->args([
                service(Singlea\Service\Client\RegistrationServiceInterface::class),
            ])

        ->set(Singlea\Command\Client\Remove::class)
            ->args([
                tagged_iterator('singlea.config_manager'),
                service(Contracts\Persistence\ClientManagerInterface::class),
            ])

        ->set(Singlea\Command\User\Logout::class)
            ->args([
                service(Singlea\Service\UserAttributes\UserAttributesManagerInterface::class),
            ])

        ->set(Singlea\Controller\Feature\Token::class)
            ->args([
                tagged_iterator('singlea.tokenizer'),
            ])

        ->set(Singlea\EventListener\ClientListener::class)
            ->args([
                param('singlea.client.id_query_parameter'),
                param('singlea.client.secret_query_parameter'),
                service(Contracts\Persistence\ClientManagerInterface::class),
                service(\Psr\Log\LoggerInterface::class)->nullOnInvalid(),
            ])

        ->set(Singlea\EventListener\ExceptionListener::class)
            ->args([
                param('kernel.debug'),
                service(\Symfony\Component\HttpFoundation\RequestStack::class),
            ])

        ->set(Singlea\EventListener\LoginListener::class)
            ->args([
                param('singlea.ticket.cookie_name'),
                param('singlea.ticket.ttl'),
                param('singlea.ticket.domain'),
                param('singlea.ticket.samesite'),
                param('singlea.authentication.sticky_session'),
                service(Singlea\Service\Realm\RealmResolverInterface::class),
                service(Singlea\Service\UserAttributes\UserAttributesManagerInterface::class),
                service(\Psr\Log\LoggerInterface::class)->nullOnInvalid(),
            ])

        ->set(Singlea\EventListener\RealmListener::class)
            ->args([
                param('singlea.realm.query_parameter'),
                param('singlea.realm.default'),
                service('parameter_bag'),
            ])

        ->set(Singlea\EventListener\TicketListener::class)
            ->args([
                param('singlea.ticket.header'),
                service(\Psr\Log\LoggerInterface::class)->nullOnInvalid(),
            ])

        ->set(Singlea\EventListener\Security\LogoutListener::class)
            ->args([
                param('singlea.ticket.cookie_name'),
                param('singlea.ticket.domain'),
                param('singlea.ticket.samesite'),
                service(Singlea\Service\Authentication\AuthenticationServiceInterface::class),
                service(Singlea\Service\Realm\RealmResolverInterface::class),
                service(Singlea\Service\UserAttributes\UserAttributesManagerInterface::class),
                service(\Psr\Log\LoggerInterface::class)->nullOnInvalid(),
            ])

        ->set(Singlea\EventListener\Security\SuccessfulLoginListener::class)
            ->args([
                param('singlea.ticket.cookie_name'),
                service(Singlea\Service\Realm\RealmResolverInterface::class),
                service(Singlea\Service\UserAttributes\UserAttributesManagerInterface::class),
                service(\Symfony\Contracts\EventDispatcher\EventDispatcherInterface::class),
                service(\Psr\Log\LoggerInterface::class)->nullOnInvalid(),
            ])

        ->set(Singlea\FeatureConfig\ConfigRetrieverInterface::class, Singlea\FeatureConfig\ConfigRetriever::class)
            ->args([
                tagged_iterator('singlea.config_manager'),
            ])

        ->set(Singlea\Security\GrantedVoter::class)
            ->args([
                param('singlea.client.trusted_clients'),
                param('singlea.client.trusted_registrars'),
                param('singlea.client.registration_ticket_header'),
                service(\Symfony\Component\HttpFoundation\RequestStack::class),
                service(Singlea\FeatureConfig\ConfigRetrieverInterface::class),
                service(Singlea\Service\Signature\SignatureServiceInterface::class),
                service(Singlea\Service\Client\RegistrationTicketManagerInterface::class)->nullOnInvalid(),
                service(\Psr\Log\LoggerInterface::class)->nullOnInvalid(),
            ])

        ->set(Singlea\Service\Authentication\AuthenticationServiceInterface::class, Singlea\Service\Authentication\AuthenticationService::class)
            ->args([
                param('singlea.authentication.redirect_uri_query_parameter'),
                service(Singlea\Service\Realm\RealmResolverInterface::class),
                service(Singlea\Service\UserAttributes\UserAttributesManagerInterface::class),
                service(\Symfony\Contracts\EventDispatcher\EventDispatcherInterface::class),
            ])

        ->set(Singlea\Service\Client\RegistrationServiceInterface::class, Singlea\Service\Client\RegistrationService::class)
            ->args([
                tagged_iterator('singlea.config_factory'),
                tagged_iterator('singlea.config_manager'),
                service(Contracts\Persistence\ClientManagerInterface::class),
                service(\Psr\Log\LoggerInterface::class)->nullOnInvalid(),
            ])

        ->set(Singlea\Service\Marshaller\FeatureConfigMarshallerFactory::class)
            ->args([
                param('singlea.marshaller.use_igbinary'),
            ])

        ->set(Contracts\Marshaller\FeatureConfigEncryptorInterface::class, Singlea\Service\Marshaller\SodiumFeatureConfigEncryptor::class)
            ->args([
                param('singlea.encryption.client_keys'),
            ])

        ->set(Singlea\Service\Realm\RealmResolverInterface::class, Singlea\Service\Realm\RealmResolver::class)
            ->args([
                service('security.firewall.map'),
            ])

        ->set(Singlea\Service\Signature\SignatureServiceInterface::class, Singlea\Service\Signature\SignatureService::class)
            ->args([
                param('singlea.signature.request_ttl'),
                param('singlea.signature.timestamp_query_parameter'),
                param('singlea.signature.signature_query_parameter'),
                param('singlea.signature.extra_exclude_query_parameters'),
                service(\Psr\Log\LoggerInterface::class)->nullOnInvalid(),
            ])

        ->set(Singlea\Service\Tokenization\PayloadComposerInterface::class, Singlea\Service\Tokenization\PayloadComposer::class)
            ->args([
                tagged_iterator('singlea.payload_fetcher'),
                service(\Symfony\Contracts\EventDispatcher\EventDispatcherInterface::class),
                service(\Psr\Log\LoggerInterface::class)->nullOnInvalid(),
            ])

        ->set(Singlea\Service\UserAttributes\UserAttributesMarshallerInterface::class, Singlea\Service\UserAttributes\UserAttributesMarshaller::class)
            ->args([
                param('singlea.encryption.user_keys'),
                param('singlea.user_attributes.use_igbinary'),
            ])

        ->set(Singlea\Service\UserAttributes\UserAttributesManagerInterface::class, Singlea\Service\UserAttributes\UserAttributesManager::class)
            ->args([
                abstract_arg('cache pools'),
                service(Singlea\Service\UserAttributes\UserAttributesMarshallerInterface::class),
                service(\Psr\Log\LoggerInterface::class)->nullOnInvalid(),
            ])
    ;
};
