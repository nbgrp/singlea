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
                static fn (): string => '$auth_checker->isGranted("SINGLEA_SIGNATURE")',
                static fn (array $variables): bool => $variables['auth_checker']->isGranted('SINGLEA_SIGNATURE'),
            ),

            new ExpressionFunction(
                'is_valid_ticket',
                static fn (): string => '$auth_checker->isGranted("SINGLEA_TICKET")',
                static fn (array $variables): bool => $variables['auth_checker']->isGranted('SINGLEA_TICKET'),
            ),

            new ExpressionFunction(
                'is_valid_client_ip',
                static fn (): string => '$auth_checker->isGranted("CLIENT_IP")',
                static fn (array $variables): bool => $variables['auth_checker']->isGranted('CLIENT_IP'),
            ),

            new ExpressionFunction(
                'is_valid_registration_ip',
                static fn (): string => '$auth_checker->isGranted("REGISTRATION_IP")',
                static fn (array $variables): bool => $variables['auth_checker']->isGranted('REGISTRATION_IP'),
            ),

            new ExpressionFunction(
                'is_valid_registration_ticket',
                static fn (): string => '$auth_checker->isGranted("REGISTRATION_TICKET")',
                static fn (array $variables): bool => $variables['auth_checker']->isGranted('REGISTRATION_TICKET'),
            ),
        ];
    }
}
