<?php
namespace Stubs;

class StubBuilder {
    private $className;
    private $currentStub = null;
    private $stubs = [];

    public function __construct($className) {
        $this->className = $className;
    }

    public function method($method) {
        $stub = new Stub($this->className);
        $stub->setMethod($method);
        $this->currentStub = $stub;
        $this->stubs[] = $stub;
        \Stubs\Stubs::registerStub($this->className, $method, $stub);
        \Stubs\Stubs::registerExpectedStub($stub);
        return $this;
    }

    public function with(...$args) {
        if ($this->currentStub) {
            $this->currentStub->with(...$args);
        }
        return $this;
    }

    public function returns($value = null) {
        if ($this->currentStub) {
            $this->currentStub->returns($value);
        }
        return $this;
    }

    public function raiseException(\Throwable $exception) {
        if ($this->currentStub) {
            $this->currentStub->raiseException($exception);
        }
        return $this;
    }

    public function times($n) {
        if ($this->currentStub) {
            $this->currentStub->times($n);
        }
        return $this;
    }

    public function once() {
        if ($this->currentStub) {
            $this->currentStub->once();
        }
        return $this;
    }

    public function anyTimes() {
        if ($this->currentStub) {
            $this->currentStub->anyTimes();
        }
        return $this;
    }

    public function twice() {
        if ($this->currentStub) {
            $this->currentStub->twice();
        }
        return $this;
    }

    public function exactly($n) {
        if ($this->currentStub) {
            $this->currentStub->exactly($n);
        }
        return $this;
    }

    public function atLeast($n) {
        if ($this->currentStub) {
            $this->currentStub->atLeast($n);
        }
        return $this;
    }
} 