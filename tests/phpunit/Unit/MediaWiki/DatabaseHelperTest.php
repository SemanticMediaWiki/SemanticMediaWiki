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

	public function optionsProvider() {

		$provider[] = array(
			array( 'FOR UPDATE' )
		);

		$provider[] = array(
			array( 'GROUP BY' => array( 'Foo', 'Bar' ) )
		);

		$provider[] = array(
			array( 'ORDER BY' => array( 'Foo', 'Bar' ) )
		);

		$provider[] = array(
			array(
				'GROUP BY' => array( 'Foo', 'Bar' ),
				'ORDER BY' => array( 'Foo', 'Bar' )
			)
		);

		return $provider;
	}
}
