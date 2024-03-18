<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Subway\Member;
use Subway\Middleware;

class MyMember1 extends Member {

    public string $_name = '';
    public array $_segments = [];
    public array $_middleware = [];

    public function __construct(string $path) {
        parent::__construct($path);
    }

}

class MyMiddleware3 extends Middleware {}

final class MemberTest extends TestCase {

    public function test_name() : void {
        $member = new MyMember1('');
        // Should set name
        $member->name('foo');
        $this->assertSame('foo', $member->_name);
        // Should return itself
        $this->assertSame($member, $member->name('foo'));
    }

    public function test_middleware() : void {
        $middleware1 = new MyMiddleware3();
        $middleware2 = new MyMiddleware3();
        // Should add single middleware
        $member = new MyMember1('');
        $member->middleware($middleware1);
        $this->assertSame([ $middleware1 ], $member->_middleware);
        // Should add multiple middleware
        $member = new MyMember1('');
        $member->middleware($middleware1, $middleware2);
        $this->assertSame([ $middleware1, $middleware2 ], $member->_middleware);
        // Should return itself
        $member = new MyMember1('');
        $this->assertSame($member, $member->middleware($middleware1));
    }

    public function test_fill() : void {
        $method = new ReflectionMethod(MyMember1::class, 'fill');
        $method->setAccessible(true);
        // It creates zero segments
        $member = new MyMember1('');
        $method->invoke($member, '');
        $this->assertSame(0, count($member->_segments));
        // It creates segments
        $member = new MyMember1('');
        $method->invoke($member, 'one/two');
        $this->assertSame(2, count($member->_segments));
    }

    public function test_clearPath() : void {
        $method = new ReflectionMethod(MyMember1::class, 'clearPath');
        $method->setAccessible(true);
        // Return clean path
        $this->assertSame('foo', $method->invoke(null, ' /foo/ '));
        $this->assertSame('foo', $method->invoke(null, '/foo/'));
        $this->assertSame('foo/bar', $method->invoke(null, '/foo///bar/'));
    }

}