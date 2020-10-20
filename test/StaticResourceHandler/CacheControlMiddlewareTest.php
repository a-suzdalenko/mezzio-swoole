<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-swoole/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-swoole/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\Swoole\StaticResourceHandler;

use Mezzio\Swoole\Exception\InvalidArgumentException;
use Mezzio\Swoole\StaticResourceHandler\CacheControlMiddleware;
use Mezzio\Swoole\StaticResourceHandler\StaticResourceResponse;
use MezzioTest\Swoole\AssertResponseTrait;
use PHPUnit\Framework\TestCase;
use Swoole\Http\Request;

class CacheControlMiddlewareTest extends TestCase
{
    use AssertResponseTrait;

    public function testConstructorRaisesExceptionForInvalidRegexKey()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cache-Control regex');
        new CacheControlMiddleware([
            'not-a regex' => [],
        ]);
    }

    public function testConstructorRaisesExceptionForNonArrayDirectives()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('must be an array of strings');
        new CacheControlMiddleware([
            '/\.js$/' => 'this-is-invalid',
        ]);
    }

    public function testConstructorRaisesExceptionForNonStringDirective()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('each must be a string');
        new CacheControlMiddleware([
            '/\.js$/' => [42],
        ]);
    }

    public function testConstructorRaisesExceptionForInvalidDirective()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cache-Control directive');
        new CacheControlMiddleware([
            '/\.js$/' => ['this-is-not-valid'],
        ]);
    }

    public function testMiddlewareDoesNothingIfPathDoesNotMatchAnyDirectives()
    {
        $middleware = new CacheControlMiddleware([
            '/\.txt$/' => [
                'public',
                'no-transform',
            ],
        ]);

        $request         = $this->createMock(Request::class);
        $request->server = [
            'request_uri' => '/some/path.html',
        ];

        $next = static function ($request, $filename) {
            return new StaticResourceResponse();
        };

        $response = $middleware($request, 'some/path.html', $next);

        $this->assertStatus(200, $response);
        $this->assertHeaderNotExists('Cache-Control', $response);
        $this->assertShouldSendContent($response);
    }

    public function testMiddlewareAddsCacheControlHeaderIfPathMatchesADirective()
    {
        $middleware = new CacheControlMiddleware([
            '/\.txt$/' => [
                'public',
                'no-transform',
            ],
        ]);

        $request         = $this->createMock(Request::class);
        $request->server = [
            'request_uri' => '/some/path.txt',
        ];

        $next = static function ($request, $filename) {
            return new StaticResourceResponse();
        };

        $response = $middleware($request, 'some/path.html', $next);

        $this->assertStatus(200, $response);
        $this->assertHeaderExists('Cache-Control', $response);
        $this->assertHeaderSame('public, no-transform', 'Cache-Control', $response);
        $this->assertShouldSendContent($response);
    }
}
