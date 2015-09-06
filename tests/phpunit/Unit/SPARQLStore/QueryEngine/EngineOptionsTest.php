<?php

namespace SMW\Tests\SPARQLStore\QueryEngine;

use SMW\SPARQLStore\QueryEngine\EngineOptions;

/**
 * @covers \SMW\SPARQLStore\QueryEngine\EngineOptions
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class EngineOptionsTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\SPARQLStore\QueryEngine\EngineOptions',
			new EngineOptions()
		);
	}

	/**
	 * @dataProvider initialSettingsProvider
	 */
	public function testInitialState( $setting, $expected ) {

		$instance = new EngineOptions();

		$this->assertInternalType(
			$expected,
			$instance->get( $setting )
		);
	}

	public function testAddOption() {

		$instance = new EngineOptions();

		$this->assertFalse(
			$instance->has( 'Foo' )
		);

		$instance->set( 'Foo', 42 );

		$this->assertEquals(
			42,
			$instance->get( 'Foo' )
		);
	}

	public function testUnregisteredKeyThrowsException() {

		$instance = new EngineOptions();

		$this->setExpectedException( 'InvalidArgumentException' );
		$instance->get( 'Foo' );
	}

	public function initialSettingsProvider() {

		$provider[] = array(
			'smwgIgnoreQueryErrors',
			'boolean'
		);

		$provider[] = array(
			'smwgQSortingSupport',
			'boolean'
		);

		$provider[] = array(
			'smwgQRandSortingSupport',
			'boolean'
		);

		$provider[] = array(
			'smwgQSubpropertyDepth',
			'integer'
		);

		$provider[] = array(
			'smwgQSubcategoryDepth',
			'integer'
		);

		$provider[] = array(
			'smwgSparqlQFeatures',
			'integer'
		);

		return $provider;
	}

}

