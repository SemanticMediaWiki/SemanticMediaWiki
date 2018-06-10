<?php

namespace SMW\Tests\Query\ResultPrinters\ListResultPrinter;

use SMW\Query\ResultPrinters\ListResultPrinter\ParameterDictionary;

/**
 * @covers \SMW\Query\ResultPrinters\ListResultPrinter\ParameterDictionary
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author Stephan Gambke
 */
class ParameterDictionaryTest extends \PHPUnit_Framework_TestCase {

	public function testSetGet() {

		$dict = new ParameterDictionary();

		$dict->set( 'foo', 'Derek' );
		$dict->set( 'bar', 'Devin' );
		$dict->set( 'foo', 'Chelsea' );

		$this->assertEquals( 'Chelsea', $dict->get( 'foo' ) );
		$this->assertEquals( 'Devin', $dict->get( 'bar' ) );
	}

	public function testSetArrayGet() {

		$dict = new ParameterDictionary();

		$dict->set( ['foo' => 'Derek', 'bar' => 'Devin' ] );
		$dict->set( ['foo' => 'Chelsea', 'baz' => 'Carolynn' ] );

		$this->assertEquals( 'Chelsea', $dict->get( 'foo' ) );
		$this->assertEquals( 'Devin', $dict->get( 'bar' ) );
		$this->assertEquals( 'Carolynn', $dict->get( 'baz' ) );
	}

	public function testSetDefaultGet() {

		$dict = new ParameterDictionary();

		$dict->setDefault( 'foo', 'Derek' );
		$dict->setDefault( 'bar', 'Devin' );
		$dict->setDefault( 'foo', 'Chelsea' );

		$this->assertEquals( 'Derek', $dict->get( 'foo' ) );
		$this->assertEquals( 'Devin', $dict->get( 'bar' ) );
	}

	public function testSetDefaultArrayGet() {

		$dict = new ParameterDictionary();

		$dict->set( ['foo' => 'Derek', 'bar' => 'Devin' ] );
		$dict->setDefault( ['foo' => 'Chelsea', 'baz' => 'Carolynn' ] );

		$this->assertEquals( 'Derek', $dict->get( 'foo' ) );
		$this->assertEquals( 'Devin', $dict->get( 'bar' ) );
		$this->assertEquals( 'Carolynn', $dict->get( 'baz' ) );
	}

}