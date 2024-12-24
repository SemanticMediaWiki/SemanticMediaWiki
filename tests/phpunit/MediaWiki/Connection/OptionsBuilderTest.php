<?php

namespace SMW\Tests\MediaWiki\Connection;

use SMW\MediaWiki\Connection\OptionsBuilder;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\MediaWiki\Connection\OptionsBuilder
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class OptionsBuilderTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	public function testCanConstruct() {
		$this->assertInstanceOf(
			OptionsBuilder::class,
			new OptionsBuilder()
		);
	}

	/**
	 * @dataProvider optionsProvider
	 */
	public function testMakeSelectOptions( $options ) {
		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertIsArray(

			OptionsBuilder::makeSelectOptions( $connection, $options )
		);
	}

	/**
	 * @dataProvider optionsProvider
	 */
	public function testToString( $options ) {
		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertIsString(

			OptionsBuilder::toString( $options )
		);
	}

	public function optionsProvider() {
		$provider[] = [
			[ 'FOR UPDATE' ]
		];

		$provider[] = [
			[ 'GROUP BY' => [ 'Foo', 'Bar' ] ]
		];

		$provider[] = [
			[ 'ORDER BY' => [ 'Foo', 'Bar' ] ]
		];

		$provider[] = [
			[
				'GROUP BY' => [ 'Foo', 'Bar' ],
				'ORDER BY' => [ 'Foo', 'Bar' ]
			]
		];

		return $provider;
	}
}
