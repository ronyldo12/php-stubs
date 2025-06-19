<?php
namespace Stubs;

class StubMatch
{
    private $type;
    private $value;
    private $props;
    private $callback;

    private function __construct($type, $value = null, $props = null, $callback = null)
    {
        $this->type = $type;
        $this->value = $value;
        $this->props = $props;
        $this->callback = $callback;
    }

    public static function any()
    {
        return new self('any');
    }
    public static function text($text)
    {
        return new self('text', $text);
    }
    public static function regex($regex)
    {
        return new self('regex', $regex);
    }
    public static function arr($arr)
    {
        return new self('array', $arr);
    }
    public static function obj($obj, $props = null)
    {
        return new self('object', $obj, $props);
    }
    public static function callback($cb)
    {
        return new self('callback', null, null, $cb);
    }
    public static function exact($args)
    {
        return new self('exact', $args);
    }

    public function match($args)
    {
        switch ($this->type) {
            case 'any':
                return true;
            case 'text':
                return isset($args[0]) && strpos($args[0], $this->value) !== false;
            case 'regex':
                return isset($args[0]) && preg_match($this->value, $args[0]);
            case 'array':
                return isset($args[0]) && $args[0] == $this->value;
            case 'object':
                if (!isset($args[0]) || !is_object($args[0])) return false;
                if ($this->props) {
                    foreach ($this->props as $k => $v) {
                        if (!property_exists($args[0], $k)) return false;
                        $actual = $args[0]->$k;
                        if ($v instanceof self) {
                            if (!$v->match([$actual])) return false;
                        } else {
                            if ($actual !== $v) return false;
                        }
                    }
                    return true;
                }
                return $args[0] == $this->value;
            case 'callback':
                return call_user_func($this->callback, ...$args);
            case 'exact':
                return $args === $this->value;
        }
        return false;
    }
} 