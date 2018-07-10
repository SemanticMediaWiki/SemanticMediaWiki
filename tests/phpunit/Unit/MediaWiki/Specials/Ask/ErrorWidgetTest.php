<?php

namespace SMW\Tests\MediaWiki\Specials\Ask;

use SMW\MediaWiki\Specials\Ask\ErrorWidget;

/**
 * @covers \SMW\MediaWiki\Specials\Ask\ErrorWidget
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class ErrorWidgetTest extends \PHPUnit_Framework_TestCase {

	public function testSessionFailure() {

		$this->assertInternalType(
			'string',
			ErrorWidget::sessionFailure()
		);
	}

	public function testNoScript() {

		$this->assertInternalType(
			'string',
			ErrorWidget::noScript()
		);
	}

	public function testNoResult() {

		$this->assertInternalType(
			'string',
			ErrorWidget::noResult()
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

		$this->assertEquals(
			$expected,
			ErrorWidget::queryError( $query )
		);
	}

	public function queryErrorProvider() {

		$provider[] = [
			'',
			''
		];

		$provider[] = [
			[ 'Foo' ],
			'<div id="result-error" class="smw-callout smw-callout-error">Foo</div>'
		];

		$provider[] = [
			[ 'Foo', 'Bar' ],
			'<div id="result-error" class="smw-callout smw-callout-error"><ul><li>Foo</li><li>Bar</li></ul></div>'
		];

		$provider[] = [
			[ 'Foo', [ 'Bar' ] ],
			'<div id="result-error" class="smw-callout smw-callout-error"><ul><li>Foo</li><li>Bar</li></ul></div>'
		];

		// Filter duplicate
		$provider[] = [
			[ 'Foo', [ 'Bar' ], 'Bar' ],
			'<div id="result-error" class="smw-callout smw-callout-error"><ul><li>Foo</li><li>Bar</li></ul></div>'
		];

		return $provider;
	}

}
