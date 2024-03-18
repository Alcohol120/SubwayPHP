<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Subway\Segment;
use Subway\ESegmentType;

final class SegmentTest extends TestCase {

    public function test_estimate() : void {
        // return 3
        $this->assertSame(3, (new Segment('foo'))->estimate('foo'));
        $this->assertSame(3, (new Segment('foo?'))->estimate('foo'));
        // return 2
        $this->assertSame(2, (new Segment('{foo:a}'))->estimate('foo'));
        $this->assertSame(2, (new Segment('{foo:a}?'))->estimate('foo'));
        $this->assertSame(2, (new Segment('{foo:i}'))->estimate('1'));
        $this->assertSame(2, (new Segment('{foo:i}?'))->estimate('1'));
        $this->assertSame(2, (new Segment('{foo:[0-9]{2}}'))->estimate('00'));
        $this->assertSame(2, (new Segment('{foo:[0-9]{2}}?'))->estimate('00'));
        // return 1
        $this->assertSame(1, (new Segment('{foo}'))->estimate('any'));
        $this->assertSame(1, (new Segment('{foo}?'))->estimate('any'));
        // return 0
        $this->assertSame(0, (new Segment('optional?'))->estimate(''));
        $this->assertSame(0, (new Segment('optional?'))->estimate('foo'));
        $this->assertSame(0, (new Segment('{optional}?'))->estimate(''));
        $this->assertSame(0, (new Segment('{optional:i}?'))->estimate('foo'));
        // return -1
        $this->assertSame(-1, (new Segment('foo'))->estimate('a'));
        $this->assertSame(-1, (new Segment('{foo:a}'))->estimate('1'));
        $this->assertSame(-1, (new Segment('{foo:i}'))->estimate('a'));
        $this->assertSame(-1, (new Segment('{foo:[0-9]{2}}'))->estimate('2'));
    }

    public function test_getType() : void {
        $method = new ReflectionMethod(Segment::class, 'getType');
        $method->setAccessible(true);
        // Return COMMON type
        $this->assertSame(ESegmentType::COMMON, $method->invoke(null, 'common'));
        // Return ANY type
        $this->assertSame(ESegmentType::ANY, $method->invoke(null, '{any}'));
        // Return ALPHA type
        $this->assertSame(ESegmentType::ALPHA, $method->invoke(null, '{str:a}'));
        // Return INTEGER type
        $this->assertSame(ESegmentType::INTEGER, $method->invoke(null, '{int:i}'));
        // Return PATTERN type
        $this->assertSame(ESegmentType::PATTERN, $method->invoke(null, '{reg:[a-z]+}'));
    }

    public function test_getName() : void {
        $method = new ReflectionMethod(Segment::class, 'getName');
        $method->setAccessible(true);
        // Return property name
        $this->assertSame('', $method->invoke(null, ''));
        $this->assertSame('common', $method->invoke(null, 'common'));
        $this->assertSame('foo', $method->invoke(null, '{foo}'));
        $this->assertSame('bar', $method->invoke(null, '{bar:i}'));
        $this->assertSame('reg', $method->invoke(null, '{reg:[a-z]+}'));
    }

    public function test_getPattern() : void {
        $method = new ReflectionMethod(Segment::class, 'getPattern');
        $method->setAccessible(true);
        // Return NULL
        $this->assertSame(null, $method->invoke(null, 'common'));
        $this->assertSame(null, $method->invoke(null, '{bar:i}'));
        // Return regexp pattern
        $this->assertSame('/[a-z]+/i', $method->invoke(null, '{foo:[a-z]+}'));
    }

    public function test_getOptional() : void {
        $method = new ReflectionMethod(Segment::class, 'getOptional');
        $method->setAccessible(true);
        // Return true
        $this->assertSame(true, $method->invoke(null, 'common-optional?'));
        // Return false
        $this->assertSame(false, $method->invoke(null, 'common-optional'));
    }

}