<?php declare(strict_types=1);
// SPDX-License-Identifier: BSD-3-Clause

namespace SingleA\Bundles\Singlea\Tests\Request;

use PHPUnit\Framework\TestCase;
use SingleA\Bundles\Singlea\EventListener\RealmListener;
use SingleA\Bundles\Singlea\Request\RealmRequestMatcher;
use Symfony\Component\HttpFoundation\Request;

/**
 * @covers \SingleA\Bundles\Singlea\Request\RealmRequestMatcher
 *
 * @internal
 */
final class RealmRequestMatcherTest extends TestCase
{
    public function testFor(): void
    {
        self::assertSame('SingleA\Bundles\Singlea\Request\RealmRequestMatcher.test', RealmRequestMatcher::for('test'));
    }

    public function testMatches(): void
    {
        $request = Request::create('');
        $request->attributes->set(RealmListener::REALM_ATTRIBUTE, 'test');

        $testMatcher = new RealmRequestMatcher('test');
        $mainMatcher = new RealmRequestMatcher('main');

        self::assertTrue($testMatcher->matches($request));
        self::assertFalse($mainMatcher->matches($request));
    }
}
