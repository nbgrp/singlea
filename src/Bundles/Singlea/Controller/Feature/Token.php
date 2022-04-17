<?php declare(strict_types=1);
// SPDX-License-Identifier: BSD-3-Clause

namespace SingleA\Bundles\Singlea\Controller\Feature;

use Psr\Log\LoggerInterface;
use SingleA\Bundles\Singlea\EventListener\TicketListener;
use SingleA\Bundles\Singlea\Service\Realm\RealmResolverInterface;
use SingleA\Bundles\Singlea\Service\Tokenization\PayloadComposerInterface;
use SingleA\Bundles\Singlea\Service\UserAttributes\UserAttributesManagerInterface;
use SingleA\Contracts\PayloadFetcher\FetcherConfigInterface;
use SingleA\Contracts\Tokenization\TokenizerConfigInterface;
use SingleA\Contracts\Tokenization\TokenizerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

#[AsController]
final class Token
{
    /**
     * @param iterable<TokenizerInterface> $tokenizers
     */
    public function __construct(
        private readonly iterable $tokenizers,
    ) {}

    public function __invoke(
        ?TokenizerConfigInterface $tokenizerConfig,
        ?FetcherConfigInterface $fetcherConfig,
        Request $request,
        RealmResolverInterface $realmResolver,
        UserAttributesManagerInterface $userAttributesManager,
        PayloadComposerInterface $payloadComposer,
        ?LoggerInterface $logger = null,
    ): Response {
        if (!$tokenizerConfig) {
            throw new AccessDeniedHttpException('Token creation is not available to the client.');
        }

        /** @var string $ticket */
        $ticket = $request->attributes->get(TicketListener::TICKET_ATTRIBUTE);
        $realm = $realmResolver->resolve($request);

        $userAttributes = $userAttributesManager->find($realm, $ticket);
        if (!$userAttributes) {
            throw new AccessDeniedHttpException('There is no user cache.');
        }

        try {
            $tokenizer = $this->getTokenizer($tokenizerConfig);
            $identifier = $userAttributes->getIdentifier();
            $attributes = $userAttributes->getAttributes();

            $response = new Response($tokenizer->tokenize(
                $identifier,
                $payloadComposer->compose($attributes, $tokenizerConfig, $fetcherConfig),
                $tokenizerConfig,
            ));
            $response->setClientTtl($userAttributes->getTtl() ?? $tokenizerConfig->getTtl() ?? 0);

            return $response;
        } catch (\Throwable $exception) {
            $logger?->critical('Token generation failed due to an unexpected error.');

            throw new ServiceUnavailableHttpException(message: 'Unable to create token.', previous: $exception);
        }
    }

    private function getTokenizer(TokenizerConfigInterface $config): TokenizerInterface
    {
        foreach ($this->tokenizers as $tokenizer) {
            if ($tokenizer->supports($config)) {
                return $tokenizer;
            }
        }

        throw new \RuntimeException('Tokenizer for config of type '.$config::class.' not configured.');
    }
}
