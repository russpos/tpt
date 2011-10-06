<?php
require("tpt/tpt.php");

class DummyClass {

    public function foo() {

    }
}

class WhenUsingCallbacks extends TPTest {


    public function beforeAll() {
        $this->value++;
    }

    public function beforeEach() {
        $this->list = array();
    }

    public function itShouldCallBeforeEachOnce() {
        $this->expect($this->list)->toHaveCount(0);
    }

    public function itShouldCallBeforeAllOnce() {
        $this->expect($this->value)->toBe(1);
    }
}

class WhenMakingAssertions extends TPTest {

    public function beforeEach() {
        $this->obj = new DummyClass();
        $this->arr = array('foo' => 'bar', 'baz' => 'barf');
    }

  public function itShouldTestTruth() {
        $this->expect(45)->toBeTruthy();
        $this->expect(true)->toBeTruthy();
        $this->expect('bar')->toBeTruthy();
        $this->expect(array('foo'))->toBeTruthy();
    }

    public function itShouldTestFalseness() {
        $this->expect(0)->toBeFalsy();
        $this->expect(false)->toBeFalsy();
        $this->expect(null)->toBeFalsy();
        $this->expect('')->toBeFalsy();
        $this->expect(array())->toBeFalsy();
    }

    public function itShouldInvertAssertions() {
        $this->expect(false)->not->toBeTruthy();
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
        $this->expect($this->arr)->not->toHave('bazz');
    }

    public function itShouldTestCount() {
        $this->expect($this->arr)->toHaveCount(2);
        $this->expect($this->arr)->not->toHaveCount(20);
    }

    public function itShouldTestForMethods() {
        $this->expect($this->obj)->toHaveMethod('foo');
    }

    public function itShouldThrow() {
        $exception = null;
        try {
            $this->expect('foo')->toDie();
        } catch (UnknownMatcher $e) {
          $exception = $e;
        }
        $this->expect($e)->toBeInstanceOf("UnknownMatcher");
    }

}


new WhenUsingCallbacks(array('verbose' => true));
new WhenMakingAssertions(array('verbose' => true));
?>
