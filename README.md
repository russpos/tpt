# tpt - Tiny PHP Tests #

`tpt` is a BDD micro-framework for PHP - heavily inspired by BDD tools such as [RSpec](https://github.com/dchelimsky/rspec) for
Ruby and [Jasmine](https://github.com/pivotal/jasmine) for JavaScript. After not working in
PHP for a few months, I needed to write a PHP class for a recent project and found myself really missing the RSpec / Jasmine style
of test writing.  `tpt` is a lightweight testing solution.  While not as feature-rich and complex as more de-facto projects
such as `SimpleTest` and `PHPUnit`, `tpt`'s small size and easy syntax makes it an ideal solution for quick and small projects.

## Usage ##
The best way to really understand the usage of `tpt` is to look at the tests, as they are very self documenting. Here's
a quick overview of how we'd use this:

    class WhenMakingAssertions extends TPTest {

        public function beforeEach() {
            $this->obj = new DummyClass();
            $this->arr = array('foo' => 'bar', 'baz' => 'barf');
        }

        public function itShouldTestTruth() {
            $this->expect(45)->toBeTruthy();
        }

        public function itShouldTestFalseness() {
            $this->expect(0)->toBeFalsy();
            $this->expect(array())->toBeFalsy();
        }

        public function itShouldTestEquality() {
            $this->expect(123)->toEqual(123);
            $this->expect(123)->toEqual('123');
        }

        public function itShouldTestIdentical() {
            $this->expect(123)->toBe(123);
            $this->expect(123)->not->toBe('123');
        }

        public function itShouldTestHavingIndex() {
            $this->expect($this->arr)->toHave('foo');
        }

        public function itShouldTestCount() {
            $this->expect($this->arr)->toHaveCount(2);
            $this->expect($this->arr)->not->toHaveCount(20);
        }

        public function itShouldTestForMethods() {
            $this->expect($this->obj)->toHaveMethod('foo');
        }
    }


Tests are then executed as soon as an instance of this class is created:

    new WhenMakingAssertions();

You can also create mock classes and then make assertions about them

    class WhenMocking extends TPTest {
        public function beforeEach() {
            $this->mock = TPTMock::get('SomeClass',
                array('mockMe' => 10), // Methods to mock
                array(4, 4) // Constructor args
            );
        }

        public function itShouldBeCorrectType() {
            $this->expect($this->mock)->toBeInstanceOf('SomeClass');
        }

        public function itShouldHaveCorrectProduct() {
            $this->expect($this->mock->sum)->toEqual(8);
            $this->expect($this->mock->product)->toEqual(16);
        }

        public function itShouldNotCallMocks() {
            $result = $this->mock->division(20);
            $this->expect($this->mock)->toHaveCalled('mockMe', 1);
            $this->expect($this->mock)->toHaveCalledWith('mockMe', array(8, 20));
            $this->expect($result)->toEqual(200);
        }
    }

## Callbacks ##

Test classes can optionally any of the 4 callback methods to hook into the test suite:

 * __beforeAll__ - Runs before all test methods
 * __beforeEach__ - Runs before each individual test method
 * __afterEach__ - Runs after each individual test method
 * __afterAll__ - Runs after all the test methods have completed

You can then run this program from the commandline to print results:

    $ (~) php my_test.php

    When making assertions - 11/11
    =====================
    -> All tests passed!

If you want colored verbose output, simply set the `verbose` flag to true (or any value that evaluates to true).

    new WhenMakingAssertions(array('verbose' => true));

Your tests will now have colored, verbose output:

    $ (~) php t.php

    When making assertions - 11/11
    =====================
    PASS : When making assertions, it should test truth : Expected 45 to be truthy
    PASS : When making assertions, it should test falseness : Expected 0 to be falsy
    PASS : When making assertions, it should test falseness : Expected Array[0] to be falsy
    PASS : When making assertions, it should test equality : Expected 123 to equal 123
    PASS : When making assertions, it should test equality : Expected 123 to equal "123"
    PASS : When making assertions, it should test identical : Expected 123 to be 123
    PASS : When making assertions, it should test identical : Expected 123 not to be "123"
    PASS : When making assertions, it should test having index : Expected Array[2] to have "foo"
    PASS : When making assertions, it should test count : Expected Array[2] to have count 2
    PASS : When making assertions, it should test count : Expected Array[2] not to have count 20
    PASS : When making assertions, it should test for methods : Expected instance of DummyClass to have method "foo"
    -> All tests passed!

### Matchers ###

Currently, the following matchers are provided:

 * __toBeTruthy__ - Test if the subject evaluates to true
 * __toBeFalsy__ Test if the subject evaluates to false
 * __toBe__ - Tests if the subject is identical to the given value
 * __toEqual__ - Tests if the subject is equivalent to the given value
 * __toHave__ - Tests if the subject has the given index ([])
 * __toHaveCount__ - Tests if the subject's `count()` matches the provided value
 * __toHaveMethod__ - Tests if the subject has a method with the given value (String) name
 * __toBeInstanceOf__ - Tests if the subject is an instance of the given class name (String).  Note: does not work with parent classes.
