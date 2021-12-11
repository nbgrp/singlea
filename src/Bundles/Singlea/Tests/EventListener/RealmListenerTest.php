<?php declare(strict_types=1);
// SPDX-License-Identifier: BSD-3-Clause

namespace SingleA\Bundles\Singlea\Tests\EventListener;

use PHPUnit\Framework\TestCase;
use SingleA\Bundles\Singlea\EventListener\RealmListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * @covers \SingleA\Bundles\Singlea\EventListener\RealmListener
 *
 * @internal
 */
final class RealmListenerTest extends TestCase
{
    /**
     * @dataProvider setRequestAttributeProvider
     */
    public function testSetRequestAttribute(Request $request, string $expected): void
    {
        $event = new RequestEvent($this->createStub(KernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST);
        $listener = new RealmListener('realm', 'main');

        $listener->setRequestAttribute($event);

        self::assertSame($expected, $event->getRequest()->attributes->get('_singlea_realm'));
    }

    public function setRequestAttributeProvider(): \Generator
    {
        yield 'Main' => [
            'request' => Request::create('', parameters: ['realm' => 'main']),
            'expected' => 'main',
        ];

        yield 'Second' => [
            'request' => Request::create('', parameters: ['realm' => 'second']),
            'expected' => 'second',
        ];

        yield 'Default' => [
            'request' => Request::create(''),
            'expected' => 'main',
        ];
    }
}
