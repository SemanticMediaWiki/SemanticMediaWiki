<?php

namespace SMW\Test;

use SMW\SimpleDictionary;

/**
 * Tests for the SimpleDictionary class
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * @covers \SMW\SimpleDictionary
 * @covers \SMW\ObjectStorage
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 */
class SimpleDictionaryTest extends SemanticMediaWikiTestCase {

	/**
	 * Returns the name of the class to be tested
	 *
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\SimpleDictionary';
	}

	/**
	 * Helper method that returns a SimpleDictionary object
	 *
	 * @since 1.9
	 *
	 * @param array $SimpleDictionary
	 *
	 * @return SimpleDictionary
	 */
	private function newInstance( array $chapter = array() ) {
		return new SimpleDictionary( $chapter );
	}

	/**
	 * @test SimpleDictionary::__construct
	 *
	 * @since 1.9
	 */
	public function testConstructor() {
		$this->assertInstanceOf( $this->getClass(), $this->newInstance() );
	}

	/**
	 * @test SimpleDictionary::has
	 *
	 * @since 1.9
	 */
	public function testInvalidArgumentExceptionHas() {

		$this->setExpectedException( 'InvalidArgumentException' );
		$this->assertInternalType( 'string', $this->newInstance()->has( 9001 ) );
	}

	/**
	 * @test SimpleDictionary::set
	 *
	 * @since 1.9
	 */
	public function testInvalidArgumentExceptionSet() {

		$this->setExpectedException( 'InvalidArgumentException' );
		$this->assertInternalType( 'string', $this->newInstance()->set( 9001, 'lila' ) );
	}

	/**
	 * @test SimpleDictionary::remove
	 *
	 * @since 1.9
	 */
	public function testInvalidArgumentExceptionRemove() {

		$this->setExpectedException( 'InvalidArgumentException' );
		$this->assertInternalType( 'string', $this->newInstance()->remove( 9001 ) );
	}

	/**
	 * @test SimpleDictionary::get
	 *
	 * @since 1.9
	 */
	public function testOutOfBoundsException() {

		$this->setExpectedException( 'OutOfBoundsException' );
		$this->assertInternalType( 'string', $this->newInstance()->get( 'lala' ) );
	}

	/**
	 * @test SimpleDictionary::get
	 * @test SimpleDictionary::set
	 * @test SimpleDictionary::toArray
	 * @test SimpleDictionary::remove
	 * @dataProvider arrayDataProvider
	 *
	 * @since 1.9
	 */
	public function testRoundTrip( $setup, $expected ) {

		$newValue = $this->getRandomString();
		$instance = $this->newInstance( $setup['array'] );

		// Get
		$this->assertInternalType( $expected['type'], $instance->get( $setup['key'] ) );

		// toArray
		$this->assertEquals( $expected['result'], $instance->toArray() );

		// Set
		$instance->set( $setup['key'], $newValue );
		$this->assertEquals( $newValue, $instance->get( $setup['key'] ) );

		// Remove
		$instance->remove( $setup['key'] );

		$this->assertFalse(
			$instance->has( $setup['key'] ),
			'asserts that detach/removal will result alwasy in false'
		);

		$instance->remove( $setup['key'] );

		$this->assertFalse(
			$instance->has( $setup['key'] ),
			'asserts that detach/removal will result alwasy in false'
		);

	}

	/**
	 * @test SimpleDictionary::merge
	 * @test SimpleDictionary::toArray
	 *
	 * @since 1.9
	 */
	public function testMerge() {

		$SimpleDictionary = $this->newInstance( array( 'lila' => 'lila' ) );
		$this->assertEquals( 'lila' , $SimpleDictionary->get( 'lila' ) );

		$mergeable = $this->newInstance( array( 'lila' => array( 'lula', 9001 ) ) );
		$this->assertEquals( array( 'lula', 9001 ), $mergeable->get( 'lila' ) );

		$SimpleDictionary->merge( $mergeable->toArray() );
		$this->assertEquals( array( 'lula', 9001 ), $SimpleDictionary->get( 'lila' ) );

	}

	/*
	 * @since 1.9
	public function testRuntimeComparison() {

		echo "\n";

		$counter = 1;
		$s = array();
		$time = microtime( true );

		for( $x = 0; $x < $counter; $x++ ) {
			$s[] = array( "name"=>"Adam", "age"=> 35 );
		};

		echo 'N-Array, Memory: ' . memory_get_peak_usage() . ' Execution time: ' . ( microtime( true ) - $time ) . "\n";
		unset( $s );
		$time = microtime( true );

		$s = array();
		$h = $this->newInstance();

		for( $x = 0; $x < $counter; $x++ ) {
			$h->set( "name", "Adam" )->set( "age", 35 );
			$s[] = $h->toArray();
		};

		echo 'H-Array, Memory: ' . memory_get_peak_usage() . ' Execution time: ' . ( microtime( true ) - $time ) . "\n";
		unset( $s );
		$time = microtime( true );

		$s = array();
		$o = new \ArrayObject;

		for( $x = 0; $x < $counter; $x++ ) {
			$o->offsetSet( "name", "Adam" );
			$o->offsetSet( "age", 35 );
			$s[] = $o->getArrayCopy();
		};

		echo 'O-Array, Memory: ' . memory_get_peak_usage() . ' Execution time: ' . ( microtime( true ) - $time ) . "\n";

		$this->assertTrue( true );

	}
	 */

	/**
	 * @return array
	 */
	public function arrayDataProvider() {

		$provider = array();

		// #0 string
		$key  = $this->newRandomString();
		$test = array( $key => $this->newRandomString() );

		$provider[] = array(
			array(
				'key'   => $key,
				'array' => $test
			),
			array(
				'type'   => 'string',
				'result' => $test
			)
		);

		// #1 array
		$key  = $this->newRandomString();
		$new  = array( rand( 10, 200 ), array( $this->newRandomString() ) );
		$test = array( $key => array_merge( $test, $new ) );

		$provider[] = array(
			array(
				'key'   => $key,
				'array' => $test
			),
			array(
				'type'   => 'array',
				'result' => $test
			)
		);

		// #2 null
		$key  = $this->newRandomString();
		$test = array( $key => null );

		$provider[] = array(
			array(
				'key'   => $key,
				'array' => $test
			),
			array(
				'type'   => 'null',
				'result' => $test
			)
		);

		// #3 array()
		$key  = $this->newRandomString();
		$test = array( $key => array() );

		$provider[] = array(
			array(
				'key'   => $key,
				'array' => $test
			),
			array(
				'type'   => 'array',
				'result' => $test
			)
		);

		// #4 objects
		$key  = $this->newRandomString();
		$test = array( $key => array( new SimpleDictionary( array( 'Foo' => 'Bar' ) ), new SimpleDictionary() ) );

		$provider[] = array(
			array(
				'key'   => $key,
				'array' => $test
			),
			array(
				'type'   => 'array',
				'result' => $test
			)
		);

		return $provider;
	}

}
