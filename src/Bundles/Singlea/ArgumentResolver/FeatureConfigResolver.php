<?php
// SPDX-License-Identifier: BSD-3-Clause

declare(strict_types=1);

namespace SingleA\Bundles\Singlea\ArgumentResolver;

use SingleA\Bundles\Singlea\EventListener\ClientListener;
use SingleA\Bundles\Singlea\FeatureConfig\ConfigRetrieverInterface;
use SingleA\Contracts\FeatureConfig\FeatureConfigInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

/**
 * Allows resolving of client feature config into controller argument using the client id and secret
 * previously resolved by the ClientListener.
 *
 * @see ClientListener
 */
final class FeatureConfigResolver implements ValueResolverInterface
{
    public function __construct(
        private readonly ConfigRetrieverInterface $configRetriever,
    ) {}

    public function resolve(Request $request, ArgumentMetadata $argument): array
    {
        if (!is_subclass_of($argument->getType() ?? '', FeatureConfigInterface::class)) {
            return [];
        }

        /** @var class-string<FeatureConfigInterface> $configClass */
        $configClass = $argument->getType();
        /** @var string $clientId */
        $clientId = $request->attributes->get(ClientListener::CLIENT_ID_ATTRIBUTE);
        /** @var string $secret */
        $secret = $request->attributes->get(ClientListener::SECRET_ATTRIBUTE);

        $config = $this->configRetriever->find($configClass, $clientId, $secret); // @phan-suppress-current-line PhanTypeMismatchArgumentNullable
        if (!$config && !$argument->isNullable()) {
            throw new \RuntimeException('Argument "'.$argument->getName().'" cannot be resolved.');
        }

        return [$config];
    }
}
