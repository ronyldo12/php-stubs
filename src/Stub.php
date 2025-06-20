<?php
namespace Stubs;

class Stub
{
    private $className;
    private $method;
    private $matcher;
    private $returnValue;
    private $exceptionToRaise = null;
    public $expectedTimes = 1;
    public $actualTimes = 0;
    public $anyTimes = false;
    private $file;
    private $line;
    private $minTimes = null;

    public function __construct($className)
    {
        $this->className = $className;
        $bt = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $this->file = isset($bt[1]['file']) ? $bt[1]['file'] : null;
        $this->line = isset($bt[1]['line']) ? $bt[1]['line'] : null;
    }

    public function with(...$args)
    {
        $this->matcher = count($args) === 1 && $args[0] instanceof StubMatch ? $args[0] : StubMatch::exact($args);
        return $this;
    }

    public function returns($value = null)
    {
        $this->returnValue = $value;
        // No need to register again; stub is already registered in method()
        return $this;
    }

    public function raiseException(\Throwable $exception)
    {
        $this->exceptionToRaise = $exception;
        return $this;
    }

    public function times($n)
    {
        $this->expectedTimes = $n;
        $this->minTimes = null;
        $this->anyTimes = false;
        return $this;
    }

    public function once()
    {
        return $this->times(1);
    }

    public function twice()
    {
        return $this->times(2);
    }

    public function exactly($n)
    {
        return $this->times($n);
    }

    public function atLeast($n)
    {
        $this->minTimes = $n;
        $this->anyTimes = true;
        $this->expectedTimes = null;
        return $this;
    }

    public function anyTimes()
    {
        $this->anyTimes = true;
        $this->minTimes = null;
        $this->expectedTimes = null;
        return $this;
    }

    public function invoke($args)
    {
        // Strict: always throw if call count exceeded
        if ($this->expectedTimes !== null && $this->actualTimes >= $this->expectedTimes) {
            $msg = "Stub for {$this->className}::{$this->method} at {$this->file}:{$this->line} called more times than expected.\nExpected times: {$this->expectedTimes}\nActual times: " . ($this->actualTimes + 1);
            throw new \Exception($msg);
        }
        $this->actualTimes++;
        if ($this->matcher && !$this->matcher->match($args)) {
            $expected = $this->getMatcherExpectationString($this->matcher);
            $got = var_export($args, true);
            $msg = "Stub argument mismatch for {$this->className}::{$this->method} at {$this->file}:{$this->line}\nExpected: {$expected}\nGot: {$got}";
            throw new \Exception($msg);
        }
        if ($this->exceptionToRaise !== null) {
            $msg = "Stub exception for {$this->className}::{$this->method} at {$this->file}:{$this->line}: " . $this->exceptionToRaise->getMessage();
            $e = $this->exceptionToRaise;
            $e = new \Exception($msg, 0, $e);
            throw $e;
        }
        return array_key_exists('returnValue', get_object_vars($this)) ? $this->returnValue : null;
    }

    public function wasExpectedButNotCalled()
    {
        if ($this->anyTimes && $this->minTimes === null) {
            // anyTimes: always pass
            return false;
        }
        if ($this->minTimes !== null) {
            if ($this->actualTimes < $this->minTimes) {
                $msg = "Expected stub was not called at least {$this->minTimes} times: {$this->className}::{$this->method} at {$this->file}:{$this->line}\nActual times: {$this->actualTimes}";
                throw new \Exception($msg);
            }
            return false;
        }
        if ($this->expectedTimes !== null && $this->actualTimes !== $this->expectedTimes) {
            $msg = "Expected stub was not called expected times: {$this->className}::{$this->method} at {$this->file}:{$this->line}\nExpected times: {$this->expectedTimes}\nActual times: {$this->actualTimes}";
            throw new \Exception($msg);
        }
        return false;
    }

    public function setMethod($method) {
        $this->method = $method;
    }

    public function getMatcher() {
        return $this->matcher;
    }

    private function getMatcherExpectationString($matcher) {
        if ($matcher instanceof \Stubs\StubMatch) {
            $type = (new \ReflectionClass($matcher))->getProperty('type');
            $type->setAccessible(true);
            $typeValue = $type->getValue($matcher);
            if ($typeValue === 'object') {
                $propsProp = (new \ReflectionClass($matcher))->getProperty('props');
                $propsProp->setAccessible(true);
                $propsArr = $propsProp->getValue($matcher);
                if (is_array($propsArr)) {
                    $props = [];
                    foreach ($propsArr as $k => $v) {
                        if ($v instanceof \Stubs\StubMatch) {
                            $props[$k] = $this->getMatcherExpectationString($v);
                        } else {
                            $props[$k] = $v;
                        }
                    }
                    return 'object with props: ' . var_export($props, true);
                }
            }
            if ($typeValue === 'any') {
                return 'any value';
            }
        }
        return var_export($matcher, true);
    }

    public function getMinTimes() {
        return $this->minTimes;
    }
} 