# PHP Stubs

A flexible, simple, and powerful PHP stubbing library for unit testing, built on the runkit7 extension. Easily stub instance and static methods, use advanced argument matchers, and set call expectations. Designed for seamless integration with PHPUnit and PHP 7.4+.

## Requirements
- PHP 7.4 or higher
- [runkit7 extension](https://github.com/runkit7/runkit7)
- PHPUnit (for running tests)

## Installation

Install via Composer:

```
composer require --dev ronyldo12/php-stubs
```

> **Note:** You must have the runkit7 extension installed and enabled in your PHP environment.

## Usage Patterns

### 1. Basic Method Stubbing
Stub a method to return a specific value:
```php
Stubs::stub(MyClass::class)
    ->method('foo')->returns('stubbed value');
$obj = new MyClass();
$obj->foo(); // returns 'stubbed value'
```

### 2. Stubbing with Argument Matchers
Stub a method to return a value only for specific arguments:
```php
Stubs::stub(MyClass::class)
    ->method('bar')->with('abc')->returns('stubbed_bar');
$obj = new MyClass();
$obj->bar('abc'); // returns 'stubbed_bar'
```

#### Supported Matchers
- `Stubs::match_any()` — matches any argument
- `Stubs::match_text($text)` — matches if argument contains text
- `Stubs::match_regex($pattern)` — matches regex
- `Stubs::match_array($array)` — matches array
- `Stubs::match_object($object, $props = null)` — matches an object by value or by specific properties
- `Stubs::match_callback($callable)` — custom matcher

Example:
```php
Stubs::stub(MyClass::class)
    ->method('bar')->with(Stubs::match_regex('/^foo\d+$/'))->returns('matched_regex');
$obj->bar('foo123'); // returns 'matched_regex'
```

#### Matching Object Properties Example

You can match an object by its properties using `Stubs::match_object`. Pass an example object and an array of properties to match:

```php
$expected = (object)['x' => 1, 'y' => 2];
Stubs::stub(MyClass::class)
    ->method('foo')->with(Stubs::match_object($expected, ['x' => 1]))->returns('matched_object');

$obj = new MyClass();
$result = $obj->foo((object)['x' => 1, 'y' => 999]); // returns 'matched_object' (matches 'x' only)
$result2 = $obj->foo((object)['x' => 2, 'y' => 2]); // does not match, falls back to original or throws
```

If you omit the second argument, the matcher will compare all properties of the object:

```php
$expected = (object)['x' => 1, 'y' => 2];
Stubs::stub(MyClass::class)
    ->method('foo')->with(Stubs::match_object($expected))->returns('matched_object');
$obj = new MyClass();
$obj->foo((object)['x' => 1, 'y' => 2]); // returns 'matched_object'
$obj->foo((object)['x' => 1]); // does not match
```

### 3. Stubbing Static Methods
Stub static methods just like instance methods:
```php
Stubs::stub(MyClass::class)
    ->method('staticFoo')->returns('stubbed_static');
MyClass::staticFoo(); // returns 'stubbed_static'
```

### 4. Stubbing Methods to Return Null
```php
Stubs::stub(MyClass::class)
    ->method('returnsNull')->returns(null);
$obj = new MyClass();
$obj->returnsNull(); // returns null
```

### 5. Stubbing to Raise Exceptions
```php
Stubs::stub(MyClass::class)
    ->method('foo')->raiseException(new \Exception('error!'));
$obj = new MyClass();
// $obj->foo(); // throws Exception with message 'error!'
```

### 6. Enforcing Expected Calls
Require that a stubbed method must be called:
```php
Stubs::stub(MyClass::class)
    ->method('foo')->expectCall()->returns('stubbed value');
$obj = new MyClass();
$obj->foo(); // OK
```
If not called, the test fails with a detailed exception message including file and line.

### 7. Multiple Stubs and Overriding
You can stub the same method multiple times; each call uses the next stub:
```php
Stubs::stub(MyClass::class)->method('foo')->returns('first');
Stubs::stub(MyClass::class)->method('foo')->returns('second');
$obj = new MyClass();
$obj->foo(); // 'first'
$obj->foo(); // 'second'
// $obj->foo(); // throws Exception: no stub found (no fallback)
```

### 8. Error Reporting
If a stub is not called as expected, or arguments do not match, or you call a stubbed method more times than allowed, the exception message will include:
- The class and method
- The file and line where the stub was set up

Example error:
```
Expected stub was not called: MyClass::foo at /path/to/test.php:42
```

### 9. Call Count Expectations (once, twice, exactly, atLeast, anyTimes)
You can require that a stubbed method must be called a specific number of times:

```php
// Must be called exactly once
Stubs::stub(MyClass::class)
    ->method('foo')->returns('stubbed')->once();

// Must be called exactly twice
Stubs::stub(MyClass::class)
    ->method('foo')->returns('stubbed')->twice();

// Must be called exactly n times
Stubs::stub(MyClass::class)
    ->method('foo')->returns('stubbed')->exactly(3);

// Must be called at least n times (extra calls allowed)
Stubs::stub(MyClass::class)
    ->method('foo')->returns('stubbed')->atLeast(2);

// Can be called any number of times (including zero)
Stubs::stub(MyClass::class)
    ->method('foo')->returns('stubbed')->anyTimes();
```

- If the method is called more than allowed (for once, twice, exactly), an exception is thrown immediately.
- If the method is called fewer than required (for once, twice, exactly, atLeast), an exception is thrown at verification.
- For atLeast, extra calls are allowed.
- For anyTimes, any number of calls is allowed.
- **There is no fallback to the original implementation.** If you call a stubbed method with no matching stub or with wrong arguments, an exception is thrown.

## Supported Matchers
- `Stubs::match_any()`
- `Stubs::match_text($text)`
- `Stubs::match_regex($pattern)`
- `Stubs::match_array($array)`
- `Stubs::match_object($object, $props = null)`
- `Stubs::match_callback($callable)`

## Running Tests

To run the test suite locally, you have two options:

### 1. Using Make (Recommended)

This will run the tests inside a Docker container with all dependencies:

```
make test
```

### 2. Manually with Composer and PHPUnit

1. Make sure you have the required dependencies installed:
   - PHP 7.4 or higher
   - [runkit7 extension](https://github.com/runkit7/runkit7)
   - PHPUnit

2. Install Composer dependencies (if you haven't already):

   ```
   composer install
   ```

3. Run the tests:

   ```
   ./vendor/bin/phpunit
   ```

## Contributing

Contributions are welcome! To contribute to this project:

1. **Fork** the repository on GitHub.
2. **Clone** your fork to your local machine.
3. **Create a new branch** for your feature or bugfix:
   ```
   git checkout -b my-feature
   ```
4. **Make your changes** and add tests if applicable.
5. **Commit** your changes with a clear message.
6. **Push** to your fork:
   ```
   git push origin my-feature
   ```
7. **Open a Pull Request** on GitHub and describe your changes.

Please ensure your code follows the existing style and passes all tests. Thank you for helping improve this project!

## License
MIT 