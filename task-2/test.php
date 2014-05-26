<?php
require_once 'di.php';

class TestReceiver {
	public $test_derp;
	public $test_merp;
	public $test_camelcase;
	public $test_simple;

	public $test1;
	public $test2;
	public $test3;

	public function set_DeRp( $derp ) {
		$this->test_derp = $derp;
	}

	public function set_merp( $merp ) {
		$this->test_merp = $merp;
	}

	public function setCamelCase( $x ) {
		$this->test_camelcase = $x;
	}

	public function setSimple($x) {
		$this->test_simple = $x;
	}
}

class TestProvider {
	private $_value;
	function __construct ($value) {
		$this->_value = $value;
	}
	function provide_test1 () {
		return 'test1';
	}

	function provide_test2 () {
		return 'test2';
	}

	function provide_test3 () {
		return $this->_value;
	}
}

DI::register(['derp', 'camelcase'], function ($what, $obj) {
	assert( in_array($what, ['derp', 'camelcase']) );
	assert( $obj !== NULL );
	return $what;
});

DI::register('simple', function () {
	return 'simple';
});

DI::register('error', function () {
	throw new Exception();
});

assert( DI::give('simple') === 'simple' );

$x = DI::inject(new TestReceiver());
assert( $x->test_derp === 'derp' );
assert( $x->test_merp === NULL );
assert( $x->test_camelcase === 'camelcase' );
assert( $x->test_simple === 'simple' );
assert( $x->test1 === NULL );
assert( $x->test2 === NULL );
assert( $x->test3 === NULL );

DI::register(new TestProvider('derp'));
assert( DI::give('test3') === 'derp' );

$y = DI::inject(new TestReceiver());
assert( $y->test1 === 'test1' );
assert( $y->test2 === 'test2' );
assert( $y->test3 === 'derp' );

$z_obj = new TestReceiver();
$z_obj->test2 = 'DERP';
$z = DI::inject($z_obj);
assert( $z->test1 === 'test1' );
assert( $z->test2 === 'DERP' );
assert( $z->test3 === 'derp' );
