<?php

namespace SMW\Tests\MediaWiki\Specials\Ask;

use SMW\MediaWiki\Specials\Ask\HtmlContentBuilder;

/**
 * @covers \SMW\MediaWiki\Specials\Ask\HtmlContentBuilder
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class HtmlContentBuilderTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\MediaWiki\Specials\Ask\HtmlContentBuilder',
			new HtmlContentBuilder()
		);
	}

	/**
	 * @dataProvider queryErrorProvider
	 */
	public function testGetFormattedErrorString( $errors, $expected ) {

		$query = $this->getMockBuilder( '\SMWQuery' )
			->disableOriginalConstructor()
			->getMock();

		$query->expects( $this->atLeastOnce() )
			->method( 'getErrors' )
			->will( $this->returnValue( $errors ) );

		$instance = new HtmlContentBuilder();

		$this->assertEquals(
			$expected,
			$instance->getFormattedErrorString( $query )
		);
	}

	public function queryErrorProvider() {

		$provider[] = array(
			'',
			''
		);

		$provider[] = array(
			array( 'Foo' ),
			'<div class="smw-callout smw-callout-error">Foo</div>'
		);

		$provider[] = array(
			array( 'Foo', 'Bar' ),
			'<div class="smw-callout smw-callout-error"><li>Foo</li><li>Bar</li></div>'
		);

		$provider[] = array(
			array( 'Foo', array( 'Bar' ) ),
			'<div class="smw-callout smw-callout-error"><li>Foo</li><li>Bar</li></div>'
		);

		// Filter duplicate
		$provider[] = array(
			array( 'Foo', array( 'Bar' ), 'Bar' ),
			'<div class="smw-callout smw-callout-error"><li>Foo</li><li>Bar</li></div>'
		);

		return $provider;
	}

}
