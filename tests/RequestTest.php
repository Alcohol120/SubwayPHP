<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Subway\Request;

final class RequestTest extends TestCase {

    public function test_construct() : void {
        // Fill request properties
        $req = new Request('http://example.com/foo/bar?foo=1&bar=2#foo');
        $this->assertSame('http://example.com', $req->origin);
        $this->assertSame([ 'foo', 'bar' ], $req->segments);
        $this->assertSame([ 'foo' => '1', 'bar' => '2' ], $req->keys);
        $this->assertSame('#foo', $req->anchor);

        $req = new Request('/foo/bar?foo=1&bar=2#foo');
        $this->assertSame('', $req->origin);
        $this->assertSame([ 'foo', 'bar' ], $req->segments);
        $this->assertSame([ 'foo' => '1', 'bar' => '2' ], $req->keys);
        $this->assertSame('#foo', $req->anchor);
    }

    public function test_segment() : void {
        // Return specific segment
        $this->assertSame('foo', (new Request('/foo/bar'))->segment(1));
        $this->assertSame('bar', (new Request('/foo/bar'))->segment(2));
        // Return empty string for undefined segment
        $this->assertSame('', (new Request('/foo/bar'))->segment(3));
    }

    public function test_key() : void {
        // Return specific key
        $this->assertSame('1', (new Request('/foo/bar?foo=1&bar=2'))->key('foo'));
        // Return empty string for undefined key
        $this->assertSame('', (new Request('/foo/bar?foo=1'))->key('bar'));
    }

    public function test_getUrl() : void {
        $method = new ReflectionMethod(Request::class, 'getUrl');
        $method->setAccessible(true);
        // Return full URL
        $this->assertSame('http://example.com/foo/bar?foo=1#bar', $method->invoke(new Request('http://example.com/foo/bar?foo=1#bar')));
    }

    public function test_getPath() : void {
        $method = new ReflectionMethod(Request::class, 'getPath');
        $method->setAccessible(true);
        // Return path
        $this->assertSame('foo/bar', $method->invoke(new Request('http://example.com/foo/bar?foo=1#bar')));
        // Return empty path
        $this->assertSame('', $method->invoke(new Request('http://example.com/?foo=1#bar')));
        $this->assertSame('', $method->invoke(new Request('http://example.com?foo=1#bar')));
    }

    public function test_getQuery() : void {
        $method = new ReflectionMethod(Request::class, 'getQuery');
        $method->setAccessible(true);
        // Return query
        $this->assertSame('foo=1&bar=2', $method->invoke(new Request('http://example.com/foo/bar?foo=1&bar=2#bar')));
        // Return empty query
        $this->assertSame('', $method->invoke(new Request('http://example.com/foo/bar?#bar')));
        $this->assertSame('', $method->invoke(new Request('http://example.com/foo/bar#bar')));
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