<?php

namespace SMW\Tests\MediaWiki\Connection;

use SMW\MediaWiki\Connection\OptionsBuilder;

/**
 * @covers \SMW\MediaWiki\Connection\OptionsBuilder
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class OptionsBuilderTest extends \PHPUnit_Framework_TestCase {

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

		$this->assertInternalType(
			'array',
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

		$this->assertInternalType(
			'string',
			OptionsBuilder::toString( $options )
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
