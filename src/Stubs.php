<?php
namespace Stubs;

use Stubs\Stub;
use Stubs\StubMatch;
use Stubs\StubBuilder;

class Stubs
{
    private static $stubs = [];
    private static $originals = [];
    private static $expectedStubs = [];
    // Retired stubs: stubs that have reached their expected call count but are kept for strict call count enforcement
    private static $retiredStubs = [];

    public static function stub($className)
    {
        if (!$className || !class_exists($className)) {
            throw new \InvalidArgumentException("Class does not exist: " . var_export($className, true));
        }
        return new StubBuilder($className);
    }

    public static function registerStub($className, $method, Stub $stub)
    {
        self::$stubs[$className][$method][] = $stub;
        self::applyStub($className, $method);
    }

    public static function registerExpectedStub($stub) {
        self::$expectedStubs[] = $stub;
    }

    public static function verifyExpectedStubs() {
        foreach (self::$expectedStubs as $stub) {
            $stub->wasExpectedButNotCalled();
        }
        self::$expectedStubs = [];
    }

    public static function clearStubs()
    {
        foreach (self::$originals as $className => $methods) {
            foreach ($methods as $method => $original) {
                if (function_exists('runkit7_method_remove')) {
                    runkit7_method_remove($className, $method);
                }
                if (function_exists('runkit7_method_add')) {
                    runkit7_method_add($className, $method, $original['args'], $original['body'], $original['flags']);
                }
            }
        }
        self::$stubs = [];
        self::$originals = [];
        self::$expectedStubs = [];
        self::$retiredStubs = [];
    }

    private static function applyStub($className, $method)
    {
        $reflection = new \ReflectionClass($className);
        if (!$reflection->hasMethod($method)) {
            throw new \InvalidArgumentException("Method does not exist: $className::$method");
        }
        $refMethod = $reflection->getMethod($method);
        if ($refMethod->isPrivate()) {
            throw new \InvalidArgumentException("Cannot stub private method: $className::$method");
        }
        $flags = 0;
        if ($refMethod->isStatic()) {
            $flags |= RUNKIT7_ACC_STATIC;
        }
        if ($refMethod->isPublic()) {
            $flags |= RUNKIT7_ACC_PUBLIC;
        } elseif ($refMethod->isProtected()) {
            $flags |= RUNKIT7_ACC_PROTECTED;
        }
        $args = self::getMethodArgs($refMethod);
        $methods = array_map(function($m) { return $m->getName(); }, $reflection->getMethods());
        if (!isset(self::$originals[$className][$method])) {
            self::$originals[$className][$method] = [
                'args' => $args,
                'body' => self::getMethodBody($className, $method),
                'flags' => $flags
            ];
            if (function_exists('runkit7_method_rename')) {
                if (in_array($method, $methods) && !in_array("__original_" . $method, $methods)) {
                    runkit7_method_rename($className, $method, "__original_" . $method);
                }
            }
        }
        if (function_exists('runkit7_method_remove')) {
            @runkit7_method_remove($className, $method);
        }
        if (function_exists('runkit7_method_add')) {
            runkit7_method_add($className, $method, $args, self::generateStubBody($className, $method), $flags);
        }
    }

    private static function getMethodArgs(\ReflectionMethod $method)
    {
        $params = [];
        foreach ($method->getParameters() as $param) {
            $str = '';
            if ($param->hasType()) {
                $str .= $param->getType() . ' ';
            }
            if ($param->isPassedByReference()) {
                $str .= '&';
            }
            if ($param->isVariadic()) {
                $str .= '...';
            }
            $str .= '$' . $param->getName();
            if ($param->isDefaultValueAvailable()) {
                $str .= ' = ' . var_export($param->getDefaultValue(), true);
            }
            $params[] = $str;
        }
        return implode(', ', $params);
    }

    private static function getMethodBody($className, $method)
    {
        // Not possible to get original body, so we just call the renamed method
        $reflection = new \ReflectionMethod($className, $method);
        $args = [];
        foreach ($reflection->getParameters() as $param) {
            $args[] = '$' . $param->getName();
        }
        if ($reflection->isStatic()) {
            $call = 'self::__original_' . $method . '(' . implode(', ', $args) . ')';
        } else {
            $call = '$this->__original_' . $method . '(' . implode(', ', $args) . ')';
        }
        return 'return ' . $call . ';';
    }

    private static function generateStubBody($className, $method)
    {
        return 'return \\Stubs\\Stubs::invokeStub("' . $className . '", "' . $method . '", func_get_args(), isset($this) ? $this : null);';
    }

    public static function invokeStub($className, $method, $args, $obj)
    {
        if (!isset(self::$stubs[$className][$method]) || empty(self::$stubs[$className][$method])) {
            // Strict: always throw if no stub matches
            throw new \Exception("No stub found for {$className}::{$method} with args: " . var_export($args, true));
        }
        /** @var Stub $stub */
        $stub = self::$stubs[$className][$method][0];
        try {
            $result = $stub->invoke($args);
            // Only remove if we just reached the expected count (and did not throw)
            if (!$stub->anyTimes && $stub->getMinTimes() === null && $stub->expectedTimes !== null && $stub->actualTimes == $stub->expectedTimes) {
                array_shift(self::$stubs[$className][$method]);
            }
            return $result;
        } catch (\Exception $e) {
            // Do not remove the stub if it threw (too many calls)
            throw $e;
        }
    }

    // Matchers
    public static function match_any() { return StubMatch::any(); }
    public static function match_text($text) { return StubMatch::text($text); }
    public static function match_regex($regex) { return StubMatch::regex($regex); }
    public static function match_array($arr) { return StubMatch::arr($arr); }
    public static function match_object($obj, $props = null) { return StubMatch::obj($obj, $props); }
    public static function match_callback($cb) { return StubMatch::callback($cb); }
} 