<?php

namespace SMW\Tests\Unit\SPARQLStore\QueryEngine;

use PHPUnit\Framework\TestCase;
use SMW\SPARQLStore\QueryEngine\EngineOptions;

/**
 * @covers \SMW\SPARQLStore\QueryEngine\EngineOptions
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.1
 *
 * @author mwjames
 */
class EngineOptionsTest extends TestCase {

	public function testCanConstruct() {
		$this->assertInstanceOf(
			EngineOptions::class,
			new EngineOptions()
		);
	}

	/**
	 * @dataProvider initialSettingsProvider
	 */
	public function testInitialState( $setting, $expected ) {
		$instance = new EngineOptions();

		$this->assertNotNull(
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

		$this->expectException( 'InvalidArgumentException' );
		$instance->get( 'Foo' );
	}

	public function initialSettingsProvider() {
		$provider[] = [
			'smwgIgnoreQueryErrors',
			'boolean'
		];

		$provider[] = [
			'smwgQSortFeatures',
			'integer'
		];

		$provider[] = [
			'smwgQSubpropertyDepth',
			'integer'
		];

		$provider[] = [
			'smwgQSubcategoryDepth',
			'integer'
		];

		$provider[] = [
			'smwgSparqlQFeatures',
			'integer'
		];

		return $provider;
	}

}
