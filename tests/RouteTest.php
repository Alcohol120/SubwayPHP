<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Subway\Route;
use Subway\Segment;
use Subway\Request;

final class RouteTest extends TestCase {

    public function test_inGroup() : void {
        // return true
        $route = new Route('', [ 'foo', 'bar' ], [], [], function () {});
        $this->assertSame(true, $route->inGroup('foo'));
        $this->assertSame(true, $route->inGroup('bar'));
        $this->assertSame(true, $route->inGroup('foo', 'bar'));
        // Return false
        $route = new Route('', [], [], [], function () {});
        $this->assertSame(false, $route->inGroup('foo'));
        $this->assertSame(false, $route->inGroup('bar'));
        $this->assertSame(false, $route->inGroup('foo', 'bar'));
    }

    public function test_estimate() : void {
        // Return -1 if no matches pattern
        $this->assertSame(-1, RouteTest::getRoute('foo/bar')->estimate(new Request('bar')));
        $this->assertSame(-1, RouteTest::getRoute('foo/bar?/second')->estimate(new Request('foo')));
        // Return 0 for index route
        $this->assertSame(0, RouteTest::getRoute('')->estimate(new Request('')));
        $this->assertSame(0, RouteTest::getRoute('')->estimate(new Request('foo')));
        // Return rate with simple segments
        $this->assertSame(6, RouteTest::getRoute('foo/bar')->estimate(new Request('foo/bar')));
        $this->assertSame(5, RouteTest::getRoute('foo/{any:i}')->estimate(new Request('foo/1')));
        $this->assertSame(5, RouteTest::getRoute('foo/{any:a}')->estimate(new Request('foo/s-s')));
        $this->assertSame(5, RouteTest::getRoute('foo/{any:[a-z0-9]{4}}')->estimate(new Request('foo/bb22')));
        $this->assertSame(4, RouteTest::getRoute('foo/{any}')->estimate(new Request('foo/bar')));

        $this->assertSame(3, RouteTest::getRoute('foo/bar?')->estimate(new Request('foo')));
        $this->assertSame(3, RouteTest::getRoute('foo/{any:i}?')->estimate(new Request('foo')));
        $this->assertSame(3, RouteTest::getRoute('foo/{any:a}?')->estimate(new Request('foo')));
        $this->assertSame(3, RouteTest::getRoute('foo/{any:[a-z0-9]{4}}?')->estimate(new Request('foo')));
        $this->assertSame(3, RouteTest::getRoute('foo/{any}?')->estimate(new Request('foo')));

        $this->assertSame(3, RouteTest::getRoute('foo/bar?')->estimate(new Request('foo/n')));
        $this->assertSame(3, RouteTest::getRoute('foo/{any:i}?')->estimate(new Request('foo/n')));
        $this->assertSame(3, RouteTest::getRoute('foo/{any:a}?')->estimate(new Request('foo/1')));
        $this->assertSame(3, RouteTest::getRoute('foo/{any:[a-z0-9]{4}}?')->estimate(new Request('foo/n')));
        // Return rate with complicated optional segments
        $this->assertSame(6, RouteTest::getRoute('first/second?/third')->estimate(new Request('first/third')));
        $this->assertSame(9, RouteTest::getRoute('first/second?/third')->estimate(new Request('first/second/third')));

        $this->assertSame(6, RouteTest::getRoute('first/{any}?/second')->estimate(new Request('first/second')));
        $this->assertSame(6, RouteTest::getRoute('first/{any}?/{any}?/second')->estimate(new Request('first/second')));
        $this->assertSame(7, RouteTest::getRoute('first/{any}?/{any}?/{any}?/second')->estimate(new Request('first/test/second')));

        $this->assertSame(7, RouteTest::getRoute('{any}?/{v1:i}?/second/third?/{v2:[a-z]+}')->estimate(new Request('1/second/third')));

        $this->assertSame(4, RouteTest::getRoute('first/{any}?/{any}?/end?')->estimate(new Request('first/second')));

        $this->assertSame(5, RouteTest::getRoute('{any1}?/{any2}?/{int:i}?/{any3}?/end')->estimate(new Request('1/end')));
    }

    public function test_getUrl() : void {
        // Return uri path
        $this->assertSame('/foo/bar', RouteTest::getRoute('foo/bar')->getUrl());
        $this->assertSame('/foo/bar', RouteTest::getRoute('foo/{bar}')->getUrl([ 'bar' => 'bar' ]));
        $this->assertSame('/foo/bar/url', RouteTest::getRoute('foo/{bar}/{opt}?/{url:a}')->getUrl([ 'bar' => 'bar', 'url' => 'url' ]));
        $this->assertSame('/foo/bar/2', RouteTest::getRoute('foo/{bar}/{opt}?/{num:i}')->getUrl([ 'bar' => 'bar', 'num' => '2' ]));
        $this->assertSame('/foo/bar/opt/2', RouteTest::getRoute('foo/{bar}/{opt}?/{num:i}')->getUrl([ 'bar' => 'bar', 'opt' => 'opt', 'num' => '2' ]));
        $this->assertSame('/foo/bar-1', RouteTest::getRoute('foo/{bar:[a-z]+-[0-9]+}')->getUrl([ 'bar' => 'bar-1' ]));
        // Return null
        $this->assertSame(null, RouteTest::getRoute('foo/{bar}')->getUrl());
        $this->assertSame(null, RouteTest::getRoute('foo/{bar:i}')->getUrl([ 'bar' => 'bar' ]));
        $this->assertSame(null, RouteTest::getRoute('foo/{bar:a}')->getUrl([ 'bar' => '1' ]));
        $this->assertSame(null, RouteTest::getRoute('foo/{bar:[a-z]+}')->getUrl([ 'bar' => '1' ]));
    }

    private static function getRoute(string $path) : Route {
        $segments = [];
        $paths = explode('/', $path);
        for($i = 0; $i < count($paths); $i++) if($paths[$i]) $segments[] = new Segment($paths[$i]);
        return new Route('', [], $segments, [], function () {});
    }

}