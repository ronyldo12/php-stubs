<?php
use PHPUnit\Framework\TestCase;
use Stubs\Stubs;

class StubTargetClass {
    public function foo() { return 'original_foo'; }
    public function bar($x) { return 'original_bar_' . $x; }
    public function baz($a, $b = 42) { return "baz_{$a}_{$b}"; }
    public function noArgs() { return "noArgs"; }
    public function returnsNull() { return null; }
    public function arr($a) { return $a; }
    public function obj($o) { return $o; }
    public static function jazStatic(){ return "original_jaz_static"; }
    private static function mazPrivateStatic(){ return "original_private_maz_static"; }
}

// Test suite for the Stubs PHP library
// Each test demonstrates a feature or usage pattern

class StubTest extends TestCase {
    // Clean up all stubs after each test
    protected function tearDown(): void {
        \Stubs\Stubs::clearStubs();
    }

    // Test basic method stubbing and return values
    public function testStubMethodReturns() {
        Stubs::stub(StubTargetClass::class)
            ->method('foo')->returns('stubbed_foo');
        Stubs::stub(StubTargetClass::class)
            ->method('bar')->returns(new StubTargetClass());
        $obj = new StubTargetClass();
        $this->assertEquals('stubbed_foo', $obj->foo());
        $this->assertInstanceOf(StubTargetClass::class, $obj->bar('anything'));
    }

    // Test stubbing with argument matchers
    public function testStubWithArgumentMatch() {
        Stubs::stub(StubTargetClass::class)
            ->method('bar')->with('abc')->returns('stubbed_bar');
        $obj = new StubTargetClass();
        $this->assertEquals('stubbed_bar', $obj->bar('abc'));
    }

    // Test that a stub throws if called with wrong arguments
    public function testStubWithWrongArgumentThrows() {
        $this->expectException(Exception::class);
        Stubs::stub(StubTargetClass::class)
            ->method('bar')->with('expected')->returns('stubbed_bar');
        $obj = new StubTargetClass();
        $obj->bar('wrong');
    }

    // Test match_any matcher: matches any argument
    public function testStubWithMatchAny() {
        Stubs::stub(StubTargetClass::class)
            ->method('bar')->with(Stubs::match_any())->returns('any_value');
        $obj = new StubTargetClass();
        $this->assertEquals('any_value', $obj->bar('anything'));
        $this->expectException(Exception::class);
        $obj->bar('something else'); // should throw, no fallback
    }

    // Test match_text matcher: matches if argument contains text
    public function testStubWithMatchText() {
        Stubs::stub(StubTargetClass::class)
            ->method('bar')->with(Stubs::match_text('needle'))->returns('matched_text');
        $obj = new StubTargetClass();
        $this->assertEquals('matched_text', $obj->bar('find the needle here'));
        Stubs::stub(StubTargetClass::class)
            ->method('bar')->with(Stubs::match_text('needle'))->returns('matched_text');
        $this->expectException(Exception::class);
        $obj->bar('no match');
    }

    // Test match_regex matcher: matches regex
    public function testStubWithMatchRegex() {
        Stubs::stub(StubTargetClass::class)
            ->method('bar')->with(Stubs::match_regex('/^foo\d+$/'))->returns('matched_regex');
        $obj = new StubTargetClass();
        $this->assertEquals('matched_regex', $obj->bar('foo123'));
        Stubs::stub(StubTargetClass::class)
            ->method('bar')->with(Stubs::match_regex('/^foo\d+$/'))->returns('matched_regex');
        $this->expectException(Exception::class);
        $obj->bar('bar123');
    }

    // Test match_array matcher: matches arrays
    public function testStubWithMatchArray() {
        $expected = ['a' => 1, 'b' => 2];
        Stubs::stub(StubTargetClass::class)
            ->method('arr')->with(Stubs::match_array($expected))->returns('matched_array');
        $obj = new StubTargetClass();
        $this->assertEquals('matched_array', $obj->arr(['a' => 1, 'b' => 2]));
        Stubs::stub(StubTargetClass::class)
            ->method('arr')->with(Stubs::match_array($expected))->returns('matched_array');
        $this->expectException(Exception::class);
        $obj->arr(['a' => 1]);
    }

    // Test match_object matcher: matches objects by value or property
    public function testStubWithMatchObject() {
        $expected = (object)['x' => 1, 'y' => 2];
        Stubs::stub(StubTargetClass::class)
            ->method('obj')->with(Stubs::match_object($expected))->returns('matched_object');
        $obj = new StubTargetClass();
        $this->assertEquals('matched_object', $obj->obj((object)['x' => 1, 'y' => 2]));
        Stubs::stub(StubTargetClass::class)
            ->method('obj')->with(Stubs::match_object($expected, ["x"=>1]))->returns('matched_object');
        $this->expectException(Exception::class);
        $obj->obj((object)['j' => 1]);
    }

    // Test match_callback matcher: matches with a custom callback
    public function testStubWithMatchCallback() {
        Stubs::stub(StubTargetClass::class)
            ->method('bar')->with(Stubs::match_callback(function($x) { return $x === 'special'; }))->returns('matched_callback');
        $obj = new StubTargetClass();
        $this->assertEquals('matched_callback', $obj->bar('special'));
        Stubs::stub(StubTargetClass::class)
            ->method('bar')->with(Stubs::match_callback(function($x) { return $x === 'special'; }))->returns('matched_callback');
        $this->expectException(Exception::class);
        $obj->bar('not_special');
    }

    // Test stubbing with multiple arguments
    public function testStubWithMultipleWith() {
        Stubs::stub(StubTargetClass::class)
            ->method('baz')->with('a', 99)->returns('multi_with')->twice();
        $obj = new StubTargetClass();
        $this->assertEquals('multi_with', $obj->baz('a', 99));
        $this->assertEquals('multi_with', $obj->baz('a', 99));
    }

    // Test stubbing with no matcher but with arguments
    public function testStubWithNoWithButArgs() {
        Stubs::stub(StubTargetClass::class)
            ->method('baz')->returns('no_with');
        $obj = new StubTargetClass();
        $this->assertEquals('no_with', $obj->baz('x', 1));
        Stubs::stub(StubTargetClass::class)
            ->method('baz')->returns('no_with');
        $this->assertEquals('no_with', $obj->baz('y'));
    }

    // Test stubbing a method to return null
    public function testStubWithNullReturn() {
        Stubs::stub(StubTargetClass::class)
            ->method('returnsNull')->returns(null);
        $obj = new StubTargetClass();
        $this->assertNull($obj->returnsNull());
    }

    // Test stubbing a method with no arguments
    public function testStubWithNoArgs() {
        Stubs::stub(StubTargetClass::class)
            ->method('noArgs')->returns('stubbed_noArgs');
        $obj = new StubTargetClass();
        $this->assertEquals('stubbed_noArgs', $obj->noArgs());
    }

    // Test multiple stubs for the same method (overriding)
    public function testStubMultipleTimes() {
        Stubs::stub(StubTargetClass::class)
            ->method('foo')->returns('first');
        Stubs::stub(StubTargetClass::class)
            ->method('foo')->returns('second');
        $obj = new StubTargetClass();
        $this->assertEquals('first', $obj->foo());
        $this->assertEquals('second', $obj->foo());
        $this->expectException(Exception::class);
        $obj->foo(); // should throw, no fallback
    }

    // Test restoring the original method after stubbing
    public function testStubRestoreOriginal() {
        Stubs::stub(StubTargetClass::class)
            ->method('foo')->returns('stubbed_foo');
        $obj = new StubTargetClass();
        $this->assertEquals('stubbed_foo', $obj->foo());
        Stubs::clearStubs();
        $this->assertEquals('original_foo', $obj->foo());
    }

    // Test stubbing a non-existent method throws
    public function testStubWithInvalidMethod() {
        $this->expectException(InvalidArgumentException::class);
        Stubs::stub(StubTargetClass::class)
            ->method('notAMethod')->returns('should_fail');
    }

    // Test stubbing with a null class throws
    public function testStubWithNullClass() {
        $this->expectException(InvalidArgumentException::class);
        Stubs::stub(null)->method('foo')->returns('should_fail');
    }

    // Test stubbing with no returns (should return null)
    public function testStubWithNoReturns() {
        Stubs::stub(StubTargetClass::class)
            ->method('foo');
        $obj = new StubTargetClass();
        $this->assertNull($obj->foo());
    }

    // Test multiple stubs and clearing them
    public function testStubWithMultipleStubsAndClear() {
        Stubs::stub(StubTargetClass::class)
            ->method('foo')->returns('stubbed_foo');
        Stubs::stub(StubTargetClass::class)
            ->method('bar');
        Stubs::stub(StubTargetClass::class)
            ->method('bar')->returns('stubbed_bar');
        $obj = new StubTargetClass();
        $this->assertEquals('stubbed_foo', $obj->foo());
        $this->assertNull($obj->bar('x'));
        $this->assertEquals('stubbed_bar', $obj->bar('x'));
        Stubs::clearStubs();
        $this->assertEquals('original_foo', $obj->foo());
        $this->assertEquals('original_bar_x', $obj->bar('x'));
    }

    // Test stubbing static methods
    public function testStubStaticMethods() {
        Stubs::stub(StubTargetClass::class)
            ->method('jazStatic')->returns('stubbed_jazStatic');
 
        $this->assertEquals('stubbed_jazStatic', StubTargetClass::jazStatic());
    }

    // Test that stubbing a private static method throws
    public function testStubStaticPrivateMethods() {
        $this->expectException(InvalidArgumentException::class);
        Stubs::stub(StubTargetClass::class)
            ->method('mazPrivateStatic')->returns('stubbed_maz_private_static');
    }

    // Test stubbing a method to raise an exception
    public function testStubRaiseException() {
        Stubs::stub(StubTargetClass::class)
            ->method('foo')->raiseException(new \Exception('stub error'));
        $obj = new StubTargetClass();
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('stub error');
        $obj->foo();
    }

    // Test times: stub must be called exactly the specified number of times
    public function testStubExpectCallTimes() {
        // Should pass when called once
        Stubs::stub(StubTargetClass::class)
            ->method('foo')->returns('stubbed_foo')->times(1);
        $obj = new StubTargetClass();
        $obj->foo();
        \Stubs\Stubs::verifyExpectedStubs();

        // Should pass when called twice
        Stubs::stub(StubTargetClass::class)
            ->method('foo')->returns('stubbed_foo')->times(2);
        $obj = new StubTargetClass();
        $obj->foo();
        $obj->foo();
        \Stubs\Stubs::verifyExpectedStubs();

        // Should fail if called fewer times
        Stubs::stub(StubTargetClass::class)
            ->method('foo')->returns('stubbed_foo')->times(2);
        $obj = new StubTargetClass();
        $obj->foo();
        try {
            \Stubs\Stubs::verifyExpectedStubs();
            $this->fail('Expected exception for not enough calls');
        } catch (\Exception $e) {
            $this->assertStringContainsString('Expected times: 2', $e->getMessage());
            $this->assertStringContainsString('Actual times: 1', $e->getMessage());
        }
    }

     // Should fail if called more times
    public function testStubExpectCallTimesShouldFailIfCalledMoreTimes() {

    Stubs::stub(StubTargetClass::class)
        ->method('foo')->returns('stubbed_foo')->times(1);
    $obj = new StubTargetClass();
    $obj->foo();
    try {
        $obj->foo();
        $this->fail('Expected exception for too many calls');
    } catch (\Exception $e) {
        $this->assertStringContainsString('No stub found for StubTargetClass::foo', $e->getMessage());
    }
}

    // Test once(): stub must be called exactly once
    public function testStubExpectCallOnce() {
        Stubs::stub(StubTargetClass::class)
            ->method('foo')->returns('stubbed_foo')->once();
        $obj = new StubTargetClass();
        $this->assertEquals('stubbed_foo', $obj->foo());
        \Stubs\Stubs::verifyExpectedStubs();
    }

    // Test anyTimes(): stub can be called any number of times
    public function testStubExpectCallAnyTimes() {
        Stubs::stub(StubTargetClass::class)
            ->method('foo')->returns('stubbed_foo')->anyTimes();
        $obj = new StubTargetClass();
        $this->assertEquals('stubbed_foo', $obj->foo());
        $this->assertEquals('stubbed_foo', $obj->foo());
        $this->assertEquals('stubbed_foo', $obj->foo());
        \Stubs\Stubs::verifyExpectedStubs();
    }

    // Test match_object with matcher as property value (advanced usage)
    public function testStubWithMatchObjectAndMatcherProp() {
        $expected = (object)['x' => 1, 'y' => 2];
        $obj = new StubTargetClass();
        // Should match any value for 'x', regardless of 'y'
        Stubs::stub(StubTargetClass::class)
            ->method('obj')->with(Stubs::match_object($expected, ['x' => Stubs::match_any()]))->returns('matched_object_any_x');
        $this->assertEquals('matched_object_any_x', $obj->obj((object)['x' => 999, 'y' => 2]));
        Stubs::stub(StubTargetClass::class)
            ->method('obj')->with(Stubs::match_object($expected, ['x' => Stubs::match_any()]))->returns('matched_object_any_x');
        $this->assertEquals('matched_object_any_x', $obj->obj((object)['x' => 999, 'y' => 999]));
        // Should not match if 'x' is missing
        Stubs::stub(StubTargetClass::class)
            ->method('obj')->with(Stubs::match_object($expected, ['x' => Stubs::match_any()]))->returns('matched_object_any_x')->twice();
        try {
            $obj->obj((object)['y' => 2]);
            $this->fail('Expected exception for argument mismatch');
        } catch (\Exception $e) {
            $this->assertStringContainsString('Stub argument mismatch for StubTargetClass::obj', $e->getMessage());
            $this->assertStringContainsString("'x' => 'any value'", $e->getMessage());
            $this->assertStringContainsString("'y' => 2", $e->getMessage());
        }
        // how the previous stub did not match, it should not be called again
        $this->assertEquals('matched_object_any_x', $obj->obj((object)['x' => 123, 'y' => 2]));
        // Should not match if y is not 2, so should throw an exception for argument mismatch
        Stubs::stub(StubTargetClass::class)
            ->method('obj')->with(Stubs::match_object($expected, ['x' => Stubs::match_any(), 'y' => 2]))->returns('matched_object_any_x_y_2');
        try {
            $obj->obj((object)['x' => 123, 'y' => 3]);
            $this->fail('Expected exception for argument mismatch');
        } catch (\Exception $e) {
            $this->assertStringContainsString('Stub argument mismatch for StubTargetClass::obj', $e->getMessage());
            $this->assertStringContainsString("'x' => 'any value'", $e->getMessage());
            $this->assertStringContainsString("'y' => 2", $e->getMessage());
            $this->assertStringContainsString("'x' => 123", $e->getMessage());
            $this->assertStringContainsString("'y' => 3", $e->getMessage());
        }
    }

    public function testStubExpectCallTwice() {
        Stubs::stub(StubTargetClass::class)
            ->method('foo')->returns('stubbed_foo')->twice();
        $obj = new StubTargetClass();
        $obj->foo();
        $obj->foo();
        $this->expectException(Exception::class);
        $obj->foo(); // should throw
    }

    public function testStubExpectCallExactly() {
        Stubs::stub(StubTargetClass::class)
            ->method('foo')->returns('stubbed_foo')->exactly(3);
        $obj = new StubTargetClass();
        $obj->foo();
        $obj->foo();
        $obj->foo();
        $this->expectException(Exception::class);
        $obj->foo(); // should throw
    }

    public function testStubExpectCallAtLeast() {
        Stubs::stub(StubTargetClass::class)
            ->method('foo')->returns('stubbed_foo')->atLeast(2);
        $obj = new StubTargetClass();
        $obj->foo();
        $obj->foo();
        $obj->foo(); // allowed
        $obj->foo(); // allowed
        \Stubs\Stubs::verifyExpectedStubs(); // should not throw
        // Now test too few calls
        \Stubs\Stubs::clearStubs();
        Stubs::stub(StubTargetClass::class)
            ->method('foo')->returns('stubbed_foo')->atLeast(3);
        $obj = new StubTargetClass();
        $obj->foo();
        $obj->foo();
        try {
            \Stubs\Stubs::verifyExpectedStubs();
            $this->fail('Expected exception for not enough calls');
        } catch (\Exception $e) {
            $this->assertStringContainsString('at least 3', $e->getMessage());
            $this->assertStringContainsString('Actual times: 2', $e->getMessage());
        }
    }
} 