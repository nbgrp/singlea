<?php declare(strict_types=1);
// SPDX-License-Identifier: BSD-3-Clause

namespace SingleA\Bundles\Singlea\Security;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;

#[AutoconfigureTag('security.expression_language_provider')]
final class ExpressionLanguageProvider implements ExpressionFunctionProviderInterface
{
    /**
     * @psalm-suppress MixedInferredReturnType, MixedReturnStatement, MixedMethodCall
     */
    public function getFunctions(): array
    {
        return [
            new ExpressionFunction(
                'is_valid_signature',
                static fn (string $object = 'null'): string => sprintf('$auth_checker->isGranted("SINGLEA_SIGNATURE", %s)', $object),
                static fn (array $variables): bool => $variables['auth_checker']->isGranted('SINGLEA_SIGNATURE', $variables['request']),
            ),

            new ExpressionFunction(
                'is_valid_ticket',
                static fn (string $object = 'null'): string => sprintf('$auth_checker->isGranted("SINGLEA_TICKET", %s)', $object),
                static fn (array $variables): bool => $variables['auth_checker']->isGranted('SINGLEA_TICKET', $variables['request']),
            ),

            new ExpressionFunction(
                'is_valid_client_ip',
                static fn (string $object = 'null'): string => sprintf('$auth_checker->isGranted("CLIENT_IP", %s)', $object),
                static fn (array $variables): bool => $variables['auth_checker']->isGranted('CLIENT_IP', $variables['request']),
            ),

            new ExpressionFunction(
                'is_valid_registration_ip',
                static fn (string $object = 'null'): string => sprintf('$auth_checker->isGranted("REGISTRATION_IP", %s)', $object),
                static fn (array $variables): bool => $variables['auth_checker']->isGranted('REGISTRATION_IP', $variables['request']),
            ),

            new ExpressionFunction(
                'is_valid_registration_ticket',
                static fn (string $object = 'null'): string => sprintf('$auth_checker->isGranted("REGISTRATION_TICKET", %s)', $object),
                static fn (array $variables): bool => $variables['auth_checker']->isGranted('REGISTRATION_TICKET', $variables['request']),
            ),
        ];
    }
}
