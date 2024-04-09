<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Subway\Group;
use Subway\Endpoint;
use Subway\Segment;
use Subway\Route;
use Subway\Middleware;

class MyGroup1 extends Group {

    public string $_name = '';
    public array $_segments = [];
    public array $_middleware = [];
    public array $_members = [];

}

class MyMiddleware2 extends Middleware {}

final class GroupTest extends TestCase {

    public function test_group() : void {
        // Add group member
        $group = new MyGroup1('', null);
        $group->group('', function () {});
        $this->assertSame(1, count($group->_members));
        $this->assertInstanceOf(Group::class, $group->_members[0]);
        // Call children callback
        $group = new MyGroup1('', null);
        $called = false;
        $group->group('', function () use(&$called) { $called = true; });
        $this->assertSame(true, $called);
        // Return added group
        $group = new MyGroup1('', null);
        $child = $group->group('', function () {});
        $this->assertInstanceOf(Group::class, $child);
        $this->assertNotSame($child, $group);
    }

    public function test_route() : void {
        // Add endpoint member
        $group = new MyGroup1('', null);
        $group->route('get', '', function () {});
        $this->assertSame(1, count($group->_members));
        $this->assertInstanceOf(Endpoint::class, $group->_members[0]);
        // Return added endpoint
        $group = new MyGroup1('', null);
        $child = $group->route('get', '', function () {});
        $this->assertInstanceOf(Endpoint::class, $child);
    }

    public function test_getRoutes() : void {
        // Return routes
        $group = new MyGroup1('first', null);
        $group->group('second', function ($g) {
            $g->route('get', 'end', function () {});
        });
        $group->route('get', 'end', function () {});
        $routes = $group->getRoutes();
        $this->assertSame(2, count($routes));
        $this->assertInstanceOf(Route::class, $routes[0]);
        $this->assertInstanceOf(Route::class, $routes[1]);
    }

    public function test_joinProps() : void {
        $method = new ReflectionMethod(MyGroup1::class, 'joinProps');
        $method->setAccessible(true);
        // Return properties of itself
        $group = new MyGroup1('', null);
        $segment = new Segment('foo');
        $middleware = new MyMiddleware2();
        $group->_name = 'foo';
        $group->_segments = [ $segment ];
        $group->_middleware = [ $middleware ];
        $props = $method->invoke($group);
        $this->assertSame([ 'foo' ], $props['groups']);
        $this->assertSame([ $segment ], $props['segments']);
        $this->assertSame([ $middleware ], $props['middleware']);
        // Return merged properties
        $group = new MyGroup1('', null);
        $segment1 = new Segment('foo');
        $segment2 = new Segment('bar');
        $middleware1 = new MyMiddleware2();
        $middleware2 = new MyMiddleware2();
        $group->_name = 'second';
        $group->_segments = [ $segment2 ];
        $group->_middleware = [ $middleware2 ];
        $props = $method->invoke($group, [
            'groups' => [ 'first' ],
            'segments' => [ $segment1 ],
            'middleware' => [ $middleware1 ],
        ]);
        $this->assertSame([ 'first', 'second' ], $props['groups']);
        $this->assertSame([ $segment1, $segment2 ], $props['segments']);
        $this->assertSame([ $middleware1, $middleware2 ], $props['middleware']);
    }

}