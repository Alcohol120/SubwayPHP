<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Subway\Endpoint;
use Subway\Segment;
use Subway\Route;
use Subway\Middleware;


class MyEndpoint1 extends Endpoint {

    public string $_name = '';
    public array $_segments = [];
    public array $_middleware = [];

    public function __construct(string $method, string $path) {
        parent::__construct($method, $path, function () {});
    }

}

class MyMiddleware1 extends Middleware {}

final class EndpointTest extends TestCase {

    public function test_getRoute() : void {
        // Return route
        $endpoint = new Endpoint('get', '', function () {});
        $this->assertInstanceOf(Route::class, $endpoint->getRoute());
    }

    public function test_joinProps() : void {
        $method = new ReflectionMethod(MyEndpoint1::class, 'joinProps');
        $method->setAccessible(true);
        // Return properties of itself
        $endpoint = new MyEndpoint1('get', '');
        $segment = new Segment('foo');
        $middleware = new MyMiddleware1();
        $endpoint->_name = 'foo';
        $endpoint->_segments = [ $segment ];
        $endpoint->_middleware = [ $middleware ];
        $props = $method->invoke($endpoint);
        $this->assertSame('foo', $props['name']);
        $this->assertSame([], $props['groups']);
        $this->assertSame([ $segment ], $props['segments']);
        $this->assertSame([ $middleware ], $props['middleware']);
        // Return merged properties
        $endpoint = new MyEndpoint1('get', '');
        $segment1 = new Segment('foo');
        $segment2 = new Segment('bar');
        $middleware1 = new MyMiddleware1();
        $middleware2 = new MyMiddleware1();
        $endpoint->_name = 'second';
        $endpoint->_segments = [ $segment2 ];
        $endpoint->_middleware = [ $middleware2 ];
        $props = $method->invoke($endpoint, [
            'groups' => [ 'first' ],
            'segments' => [ $segment1 ],
            'middleware' => [ $middleware1 ],
        ]);
        $this->assertSame('second', $props['name']);
        $this->assertSame([ 'first' ], $props['groups']);
        $this->assertSame([ $segment1, $segment2 ], $props['segments']);
        $this->assertSame([ $middleware1, $middleware2 ], $props['middleware']);
    }

}