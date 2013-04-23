<?php

/**
 * TPTest
 *
 * A single TestCase in the TPT framework.
 * Copyright (C) 2011 by Russ Posluszny
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @author Russ Posluszny <russ@russposluszny.com>
 * @version 0.0.1
 */
class TPTest {

    public $assertions = 0;
    public $failures   = array();
    public $passes     = array();

    public function beforeEach() {}
    public function afterEach()  {}
    public function beforeAll()  {}
    public function afterAll()   {}

    /**
     * expect
     *
     * Creates a new Expectation object for the given item
     *
     * @param mixed $item
     * @access protected
     * @return void
     */
    protected function expect($item) {
        return new Expectation($item, $this);
    }

    /**
     * __construct
     *
     * Constructor for this class. Executes the beforeAll() callback, followed by
     * each test case, then runs the afterAll() callback to clean up.
     * @access public
     * @return void
     */
    public function __construct($opts=array()) {
        $verbose = !empty($opts['verbose']);
        $this->beforeAll();
        $this->_runTests(get_class_methods($this));
        $this->afterAll();
        $this->summarize($verbose);
    }

    /**
     * summarize
     *
     * Generate a summary of this test case
     * @param {Boolean} $verbose Should we summarize with a verbose output? Defaults to false
     * @access public
     * @return void
     */
    public function summarize($verbose=false) {
        $failures = sizeOf($this->failures);
        $passes = $this->assertions - $failures;
        $case = ucfirst($this->toWords(get_class($this)));
        echo "\n{$case} - {$passes}/{$this->assertions}\n=====================\n";
        foreach ($this->failures as $fail) {
            echo $fail;
        }
        if ($verbose) {
          foreach ($this->passes as $pass) {
              echo $pass;
          }
        }
        if (empty($this->failures)) echo "  -> All tests passed!\n";
    }

    /**
     * _runTests
     *
     * Runs the tests in this test case. Test cases must start with the word "it"
     *
     * @param {Array} $methods List of methods that are potentially test cases
     * @access protected
     * @return void
     */
    protected function _runTests($methods) {
        foreach ($methods as $method) {
            if (strpos($method, 'it') !== false)
                $this->_runTest($method);
        }
    }

    /**
     * toWords
     *
     * Returns a prettified version of the phrase passed in.
     * @param {String} $called The name of the called method that is being converted to
     *    words.
     * @access public
     * @return {String} Prettified version of the method name passed in
     */
    public function toWords($called) {
        return strtolower(preg_replace('/(?!^)[[:upper:]]/',' \0', $called));
    }

    /**
     * assert
     *
     * Appends the given assertion.
     * @param mixed $val
     * @access public
     * @return void
     */
    public function assert($val) {
        $this->assertions[] = $val;
    }

    /**
     * _runTest
     *
     * Runs the given method name as a test
     *
     * @param {String} $method Name of the method to execute as a test
     * @access protected
     * @return void
     */
    protected function _runTest($method) {
        $this->method = $method;
        $this->beforeEach();
        $this->{$method}();
        $this->afterEach();
    }
}

/**
 * Expectation
 *
 * Represents one single expectation, of which you can make an assertion
 * against.
 *
 * @package
 * @version $id$
 */
class Expectation {

    /**
     * __construct
     *
     * Creates a new expection.
     *
     * @param mixed $subject  The subject of this expectation.  This is the item you are
     *    going to make assertions against.
     * @param {TPTest} $tester Instance of TPTest that is making this assertion.
     * @access public
     * @return void
     */
    public function __construct($subject, $tester) {
        $this->subject = $subject;
        $this->tester = $tester;
        $this->invert = false;
    }

    /**
     * __get
     *
     * Dynamically return properties of this expectation.  Generally, this is
     * only used to provide the "not" attribute, which simply flips the result
     * of any chained assertion.
     *
     * @param {String} $thing Name of the thing being retrieved.
     * @access public
     * @return void
     */
    public function __get($thing) {
        if ($thing == 'not') return $this->inverse();
        call_user_func(array($this, $thing));
    }

    /**
     * __call
     *
     * Calls matcher dynaically.
     * @param {String} $called Name of method being called
     * @param {Array} $args List of arguments being passed to the matcher
     * @throws UnknownMatcher Throws exception if unknown matcher is encountered
     * @access public
     * @return void
     */
    public function __call($called, $args) {
        $method = '_'.$called;
        if (method_exists($this, $method)) {
            $val = call_user_func_array(array($this, $method), $args);
        } else {
            throw new UnknownMatcher("Unknown matcher: $method");
        }
        $val = ($this->invert) ? !$val : $val;
        $this->tester->assertions++;
        $desc = $this->buildDescription($called, $args);

        if (!$val) {
            $this->tester->failures[] = $this->red('FAIL').$desc;
        } else {
            $this->tester->passes[] = $this->green('PASS').$desc;
        }
    }

    /**
     * buildDescription
     *
     * Builds a string describing the action being asserted. Description strings are usually
     * of the format:
     *    When doing something general, it does something specific: Expected subject to match value
     *
     * @param mixed $called   The matcher that was called
     * @param mixed $args     The args provided to the matcher
     * @access protected
     * @return void
     */
    protected function buildDescription($called, $args) {
        $text = (($this->invert) ? 'not ' : '').$this->tester->toWords($called);
        $when = ucfirst($this->tester->toWords(get_class($this->tester)));
        $it   = $this->tester->toWords($this->tester->method);
        $args = (empty($args)) ? "" : $this->displayValue($args[0]);

        return " : $when, $it : Expected {$this->displayValue($this->subject)} {$text} {$args}\n";
    }

    /**
     * displayValue
     *
     * Returns the displayable version of a given value.
     * @param mixed $val Object that we need a string representation of
     * @access protected
     * @return {String} Returns human-readable representation of this value
     */
    protected function displayValue($val) {
        if (is_object($val)) {
            if (isset($val->__act_as)) {
                $name = $val->__act_as;
            } else {
                $name = get_class($name);
            }
        }
        if (is_array($val))     return "Array[".sizeOf($val)."]";
        if ($val === false)     return "false";
        if ($val === true)      return "true";
        if ($val === null)      return "null";
        if (is_string($val))    return "\"{$val}\"";
        if (is_numeric($val))   return $val;
        if (is_object($val))    return "instance of ".$name;
        return "unknown";
    }

    /**
     * green
     *
     * Returns string in the color green
     * @param {String} $text
     * @access protected
     * @return {String}
     */
    protected function green($text) {
        return chr(27).'[0;32m'.$text.chr(27).'[0m'.chr(27);
    }

    /**
     * red
     *
     * Returns string in the color red
     * @param {String} $text
     * @access protected
     * @return {String}
     */
    protected function red($text) {
        return chr(27).'[0;31m'.$text.chr(27).'[0m'.chr(27);
    }

    /**
     * inverse
     *
     * Inverts the value of matchers, and then returns itself for easy chaining.
     *
     * @access protected
     * @return void
     */
    protected function inverse() {
        $this->invert = true;
        return $this;
    }


    /** Matchers **/

    public function _toBeTruthy() {
        return $this->subject == true;
    }

    public function _toBeFalsy() {
        return $this->subject == false;
    }

    public function _toBe($val) {
        return $this->subject === $val;
    }

    public function _toEqual($val) {
        return $this->subject == $val;
    }

    public function _toHave($val) {
        return isset($this->subject[$val]);
    }

    public function _toHaveCount($val) {
        return count($this->subject) == $val;
    }

    public function _toHaveMethod($val) {
        return method_exists($this->subject, $val);
    }

    public function _toBeInstanceOf($val) {
        return $this->subject instanceof $val;
    }

    public function _toHaveCalled($method, $times) {
        $class = get_class($this->subject);
        $calls = $class::$__calls[$method];
        return $times == count($calls);
    }

    public function _toHaveCalledWith($method, $args, $time=0) {
        $class = get_class($this->subject);
        $calls = $class::$__calls[$method];
        return $calls[$time] == $args;
    }
}

/**
 * Generate mock classes that can be stubbed and asserted.
 */
class TPTMock {

    /**
     * Used to handle tracking calls to a mock object
     * 
     * @param mixed $instance The object instance being called against
     * @param string $method The method we are recording a call for
     * @param array $args  The arguments that are being called with
     * @static
     * @return mixed
     */
    public static function call($instance, $method, $args) {
        $class = get_class($instance);
        if (isset($class::$__stubs[$method])) {
            if (empty($class::$__calls[$method])) {
                $class::$__calls[$method] = array();
            }
            $class::$__calls[$method][] = $args;
            return $class::$__stubs[$method];
        }
    }

    /**
     * Returns an instance of a newly mocked class
     * 
     * @param string $class  Name of class to mock
     * @param array $methods_to_stub Array of methods to stub, with their return values
     * @param array $args Arguments to pass to the constructor
     * @static
     * @access public
     * @return mixed
     */
    public static function get($class, $methods_to_stub=array(), $args=array()) {
        $klass = self::getReflection($class, $methods_to_stub);
        return $klass->newInstanceArgs($args);
    }

    /**
     * Returns a ReflectionClass object about the newly mocked class
     *
     * @param string $class  Name of class to mock
     * @param array $methods_to_stub Array of methods to stub, with their return values
     * @static
     * @access public
     * @return ReflectionClass
     */
    public static function getReflection($class, $methods_to_stub=array()) {
        $class_name = "__".$class."_".rand();
        $methods = array();
        foreach ($methods_to_stub as $method => $args) {
            $methods[] = <<<EOF
    public function $method() {
        return TPTMock::call(\$this, '$method', func_get_args());
    }

EOF;
        }
        $methods = implode("\n\n", $methods);
        $def = <<<EOF
class $class_name extends $class {
    public \$__act_as = '$class';
    public static \$__stubs = array();
    public static \$__calls = array();

    $methods

}
EOF;

        // Ugly! 
        eval($def);
        $class_name::$__stubs = $methods_to_stub;
        $klass = new ReflectionClass($class_name);
        return $klass;
    }
}

class TPTException extends Exception { }
class UnknownMatcher extends TPTException { }

