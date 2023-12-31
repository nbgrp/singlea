<?php
// SPDX-License-Identifier: BSD-3-Clause

declare(strict_types=1);

namespace SingleA\Bundles\Singlea\Tests\Controller\Feature;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use SingleA\Bundles\Singlea\Controller\Feature\Token;
use SingleA\Bundles\Singlea\Service\Realm\RealmResolverInterface;
use SingleA\Bundles\Singlea\Service\Tokenization\PayloadComposerInterface;
use SingleA\Bundles\Singlea\Service\UserAttributes\UserAttributesItem;
use SingleA\Bundles\Singlea\Service\UserAttributes\UserAttributesManagerInterface;
use SingleA\Bundles\Singlea\Tests\Service\TestTokenizerConfig;
use SingleA\Contracts\PayloadFetcher\FetcherConfigInterface;
use SingleA\Contracts\Tokenization\TokenizerConfigInterface;
use SingleA\Contracts\Tokenization\TokenizerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

/**
 * @covers \SingleA\Bundles\Singlea\Controller\Feature\Token
 *
 * @internal
 */
final class TokenTest extends TestCase
{
    public function testSuccessfulToken(): void
    {
        $request = Request::create('');
        $request->attributes->set('__ticket', 'ticket-value');

        $realmResolver = $this->createMock(RealmResolverInterface::class);
        $realmResolver
            ->expects(self::once())
            ->method('resolve')
            ->with($request)
            ->willReturn('test')
        ;

        $userAttributes = new UserAttributesItem('tester', ['foo' => 'bar'], null);
        $userAttributesManager = $this->createMock(UserAttributesManagerInterface::class);
        $userAttributesManager
            ->expects(self::once())
            ->method('find')
            ->with('test', 'ticket-value')
            ->willReturn($userAttributes)
        ;

        $tokenizerConfig = new TestTokenizerConfig(300, null);
        $fetcherConfig = $this->createStub(FetcherConfigInterface::class);

        $payloadComposer = $this->createMock(PayloadComposerInterface::class);
        $payloadComposer
            ->expects(self::once())
            ->method('compose')
            ->with(
                ['foo' => 'bar'],
                $tokenizerConfig,
                $fetcherConfig,
            )
            ->willReturn(['some' => 'data'])
        ;

        $tokenizer = $this->createMock(TokenizerInterface::class);
        $tokenizer
            ->expects(self::once())
            ->method('supports')
            ->with($tokenizerConfig)
            ->willReturn(true)
        ;
        $tokenizer
            ->expects(self::once())
            ->method('tokenize')
            ->with(
                'tester',
                ['some' => 'data'],
                $tokenizerConfig,
            )
            ->willReturn('token-content')
        ;

        $response = (new Token([$tokenizer]))(
            $tokenizerConfig,
            $fetcherConfig,
            $request,
            $realmResolver,
            $userAttributesManager,
            $payloadComposer,
        );

        self::assertSame('token-content', $response->getContent());
        self::assertSame(300, $response->getMaxAge());
    }

    public function testUnavailableTokenization(): void
    {
        $request = Request::create('');

        $realmResolver = $this->createMock(RealmResolverInterface::class);
        $realmResolver
            ->expects(self::never())
            ->method('resolve')
        ;

        $userAttributesManager = $this->createStub(UserAttributesManagerInterface::class);
        $payloadComposer = $this->createStub(PayloadComposerInterface::class);

        $controller = new Token([]);

        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('Token creation is not available to the client.');

        $controller(
            null,
            null,
            $request,
            $realmResolver,
            $userAttributesManager,
            $payloadComposer,
        );
    }

    public function testNoUserAttributes(): void
    {
        $request = Request::create('');
        $request->attributes->set('__ticket', 'ticket-value');

        $realmResolver = $this->createMock(RealmResolverInterface::class);
        $realmResolver
            ->expects(self::once())
            ->method('resolve')
            ->with($request)
            ->willReturn('test')
        ;

        $userAttributesManager = $this->createMock(UserAttributesManagerInterface::class);
        $userAttributesManager
            ->expects(self::once())
            ->method('find')
            ->with('test', 'ticket-value')
            ->willReturn(null)
        ;

        $payloadComposer = $this->createStub(PayloadComposerInterface::class);

        $tokenizerConfig = $this->createStub(TokenizerConfigInterface::class);

        $controller = new Token([]);

        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('There is no user cache.');

        $controller(
            $tokenizerConfig,
            null,
            $request,
            $realmResolver,
            $userAttributesManager,
            $payloadComposer,
        );
    }

    public function testNoTokenizer(): void
    {
        $request = Request::create('');
        $request->attributes->set('__ticket', 'ticket-value');

        $realmResolver = $this->createMock(RealmResolverInterface::class);
        $realmResolver
            ->expects(self::once())
            ->method('resolve')
            ->with($request)
            ->willReturn('test')
        ;

        $userAttributes = new UserAttributesItem('tester', ['foo' => 'bar'], 600);
        $userAttributesManager = $this->createMock(UserAttributesManagerInterface::class);
        $userAttributesManager
            ->expects(self::once())
            ->method('find')
            ->with('test', 'ticket-value')
            ->willReturn($userAttributes)
        ;

        $payloadComposer = $this->createStub(PayloadComposerInterface::class);

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects(self::once())
            ->method('critical')
        ;

        $tokenizerConfig = $this->createStub(TokenizerConfigInterface::class);

        $controller = new Token([]);

        $this->expectException(ServiceUnavailableHttpException::class);
        $this->expectExceptionMessage('Unable to create token.');

        $controller(
            $tokenizerConfig,
            null,
            $request,
            $realmResolver,
            $userAttributesManager,
            $payloadComposer,
            $logger,
        );
    }
}
