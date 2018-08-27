<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-swoole for the canonical source repository
 * @copyright Copyright (c) 2018 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-swoole/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace ZendTest\Expressive\Swoole\StaticResourceHandler;

use PHPUnit\Framework\TestCase;
use Swoole\Http\Request;
use Zend\Expressive\Swoole\StaticResourceHandler\MethodNotAllowedMiddleware;
use Zend\Expressive\Swoole\StaticResourceHandler\ResponseValues;

class MethodNotAllowedMiddlewareTest extends TestCase
{
    public function setUp()
    {
        $this->request = $this->prophesize(Request::class)->reveal();
    }

    public function alwaysAllowedMethods() : array
    {
        return [
            'GET'     => ['GET'],
            'HEAD'    => ['HEAD'],
            'OPTIONS' => ['OPTIONS'],
        ];
    }

    public function neverAllowedMethods() : array
    {
        return [
            'POST'   => ['POST'],
            'PATCH'  => ['PATCH'],
            'PUT'    => ['PUT'],
            'DELETE' => ['DELETE'],
        ];
    }

    /**
     * @dataProvider alwaysAllowedMethods
     */
    public function testMiddlewareDoesNothingForAllowedMethods(string $method)
    {
        $this->request->server = [
            'request_method' => $method,
        ];
        $response = new ResponseValues();
        $next = function ($request, $filename) use ($response) {
            return $response;
        };
        $middleware = new MethodNotAllowedMiddleware();

        $test = $middleware($this->request, '/does/not/matter', $next);

        $this->assertSame($response, $test);
    }

    /**
     * @dataProvider neverAllowedMethods
     */
    public function testMiddlewareReturns405ResponseWithAllowHeaderAndNoContentForDisallowedMethods(string $method)
    {
        $this->request->server = [
            'request_method' => $method,
        ];
        $next = function ($request, $filename) {
            $this->fail('Should not have reached next()');
        };
        $middleware = new MethodNotAllowedMiddleware();

        $response = $middleware($this->request, '/does/not/matter', $next);

        $this->assertInstanceOf(ResponseValues::class, $response);
        $this->assertSame(405, $response->getStatus());
        $this->assertFalse($response->shouldSendContent());
        $headers = $response->getHeaders();
        $this->assertArrayHasKey('Allow', $headers);
        $this->assertSame('GET, HEAD, OPTIONS', $headers['Allow']);
    }
}
