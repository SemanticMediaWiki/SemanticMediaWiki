<?php

namespace SMW\Tests\MediaWiki\Specials\Ask;

use SMW\MediaWiki\Specials\Ask\ErrorFormWidget;

/**
 * @covers \SMW\MediaWiki\Specials\Ask\ErrorFormWidget
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class ErrorFormWidgetTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\MediaWiki\Specials\Ask\ErrorFormWidget',
			new ErrorFormWidget()
		);
	}

	/**
	 * @dataProvider queryErrorProvider
	 */
	public function testGetFormattedQueryErrorElement( $errors, $expected ) {

		$query = $this->getMockBuilder( '\SMWQuery' )
			->disableOriginalConstructor()
			->getMock();

		$query->expects( $this->atLeastOnce() )
			->method( 'getErrors' )
			->will( $this->returnValue( $errors ) );

		$instance = new ErrorFormWidget();

		$this->assertEquals(
			$expected,
			$instance->getFormattedQueryErrorElement( $query )
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
			'<div class="smw-callout smw-callout-error"><ul><li>Foo</li><li>Bar</li></ul></div>'
		);

		$provider[] = array(
			array( 'Foo', array( 'Bar' ) ),
			'<div class="smw-callout smw-callout-error"><ul><li>Foo</li><li>Bar</li></ul></div>'
		);

		// Filter duplicate
		$provider[] = array(
			array( 'Foo', array( 'Bar' ), 'Bar' ),
			'<div class="smw-callout smw-callout-error"><ul><li>Foo</li><li>Bar</li></ul></div>'
		);

		return $provider;
	}

}
