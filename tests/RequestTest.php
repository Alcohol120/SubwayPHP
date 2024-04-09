<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Subway\Request;

final class RequestTest extends TestCase {

    public function test_construct() : void {
        // Fill request properties
        $req = new Request('get', 'http://example.com/foo/bar?foo=1&bar=2#foo');
        $this->assertSame('http://example.com', $req->origin);
        $this->assertSame([ 'foo', 'bar' ], $req->segments);
        $this->assertSame([ 'foo' => '1', 'bar' => '2' ], $req->keys);

        $req = new Request('get', '/foo/bar?foo=1&bar=2#foo');
        $this->assertSame('', $req->origin);
        $this->assertSame([ 'foo', 'bar' ], $req->segments);
        $this->assertSame([ 'foo' => '1', 'bar' => '2' ], $req->keys);
    }

    public function test_segment() : void {
        // Return specific segment
        $this->assertSame('foo', (new Request('get', '/foo/bar'))->segment(1));
        $this->assertSame('bar', (new Request('get', '/foo/bar'))->segment(2));
        // Return empty string for undefined segment
        $this->assertSame('', (new Request('get', '/foo/bar'))->segment(3));
    }

    public function test_key() : void {
        // Return specific key
        $this->assertSame('1', (new Request('get', '/foo/bar?foo=1&bar=2'))->key('foo'));
        // Return empty string for undefined key
        $this->assertSame('', (new Request('get', '/foo/bar?foo=1'))->key('bar'));
    }

    public function test_param() : void {
        // Return specific param
        $this->assertSame('1', (new Request('get', '/foo/bar?foo=1&bar=2', [ 'foo' => '1' ]))->key('foo'));
        // Return empty string for undefined param
        $this->assertSame('', (new Request('get', '/foo/bar?foo=1', [ 'foo' => '1' ]))->key('bar'));
    }

    public function test_header() : void {
        // Return specific key
        $this->assertSame('1', (new Request('get', '/foo/bar?foo=1&bar=2', [ 'foo' => 1, 'bar' => 2 ]))->key('foo'));
        // Return empty string for undefined key
        $this->assertSame('', (new Request('get', '/foo/bar?foo=1', [ 'foo' => 1 ]))->key('bar'));
    }

    public function test_cookie() : void {
        // Return specific key
        $this->assertSame('1', (new Request('get', '/foo/bar?foo=1&bar=2', [], [], [ 'foo' => 1, 'bar' => 2 ]))->cookie('foo'));
        // Return empty string for undefined key
        $this->assertSame('', (new Request('get', '/foo/bar?foo=1', [], [], [ 'foo' => 1 ]))->key('bar'));
    }

    public function test_json() : void {
        // Return specific key
        $this->assertSame([ 'foo' => '1' ], (new Request('get', '/foo/bar?foo=1&bar=2', [], [], [], '{"foo":"1"}'))->json());
        // Return empty string for undefined key
        $this->assertSame([], (new Request('get', '/foo/bar?foo=1', [], [], [], ''))->json());
    }

    public function test_getBody() : void {
        $method = new ReflectionMethod(Request::class, 'getBody');
        $method->setAccessible(true);
        // Return request body
        $this->assertSame('foo', $method->invoke(new Request('get', 'http://example.com/foo/bar?foo=1', [], [], [], 'foo')));
    }

    public function test_getUrl() : void {
        $method = new ReflectionMethod(Request::class, 'getUrl');
        $method->setAccessible(true);
        // Return full URL
        $this->assertSame('http://example.com/foo/bar?foo=1', $method->invoke(new Request('get', 'http://example.com/foo/bar?foo=1')));
    }

    public function test_getPath() : void {
        $method = new ReflectionMethod(Request::class, 'getPath');
        $method->setAccessible(true);
        // Return path
        $this->assertSame('foo/bar', $method->invoke(new Request('get', 'http://example.com/foo/bar?foo=1')));
        // Return empty path
        $this->assertSame('', $method->invoke(new Request('get', 'http://example.com/?foo=1')));
        $this->assertSame('', $method->invoke(new Request('get', 'http://example.com?foo=1')));
    }

    public function test_getQuery() : void {
        $method = new ReflectionMethod(Request::class, 'getQuery');
        $method->setAccessible(true);
        // Return query
        $this->assertSame('foo=1&bar=2', $method->invoke(new Request('get', 'http://example.com/foo/bar?foo=1&bar=2')));
        // Return empty query
        $this->assertSame('', $method->invoke(new Request('get', 'http://example.com/foo/bar?')));
        $this->assertSame('', $method->invoke(new Request('get', 'http://example.com/foo/bar')));
    }

    public function test_parsePath() : void {
        $method = new ReflectionMethod(Request::class, 'parsePath');
        $method->setAccessible(true);
        // Return segments array
        $this->assertSame([ 'foo', 'bar' ], $method->invoke(null, ' //foo//bar// '));
        // Return empty array
        $this->assertSame([], $method->invoke(null, ' // '));
    }

    public function test_parseQuery() : void {
        $method = new ReflectionMethod(Request::class, 'parseQuery');
        $method->setAccessible(true);
        // Return query object
        $this->assertSame([ 'foo' => '1', 'bar' => '2' ], $method->invoke(null, ' ??&&foo=1&&bar=2&& '));
        // Return query keys with empty values
        $this->assertSame([ 'foo' => '', 'bar' => '' ], $method->invoke(null, 'foo&bar='));
        // Return empty object
        $this->assertSame([], $method->invoke(null, '?'));
    }

}