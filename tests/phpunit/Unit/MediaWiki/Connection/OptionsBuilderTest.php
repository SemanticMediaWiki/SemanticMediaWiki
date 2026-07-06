<?php

namespace SMW\Tests\Unit\MediaWiki\Connection;

use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\Connection\Database;
use SMW\MediaWiki\Connection\OptionsBuilder;

/**
 * @covers \SMW\MediaWiki\Connection\OptionsBuilder
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class OptionsBuilderTest extends TestCase {

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
		$connection = $this->getMockBuilder( Database::class )
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
		$connection = $this->getMockBuilder( Database::class )
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
