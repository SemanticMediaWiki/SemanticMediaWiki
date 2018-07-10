<?php

namespace SMW\Tests\MediaWiki;

use SMW\MediaWiki\DatabaseHelper;

/**
 * @covers \SMW\MediaWiki\DatabaseHelper
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class DatabaseHelperTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {
		$this->assertInstanceOf(
			'\SMW\MediaWiki\DatabaseHelper',
			new DatabaseHelper()
		);
	}

	/**
	 * @dataProvider optionsProvider
	 */
	public function testMakeSelectOptions( $options ) {

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInternalType(
			'array',
			DatabaseHelper::makeSelectOptions( $connection, $options )
		);
	}

	/**
	 * @dataProvider optionsProvider
	 */
	public function testToString( $options ) {

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInternalType(
			'string',
			DatabaseHelper::toString( $options )
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
