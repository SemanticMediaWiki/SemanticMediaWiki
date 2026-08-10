<?php

namespace SMW\Tests\Unit\MediaWiki\Specials\Ask;

use MediaWiki\Html\Html;
use PHPUnit\Framework\TestCase;
use SMW\Localizer\Message;
use SMW\MediaWiki\Specials\Ask\ErrorWidget;
use SMW\Query\Query;

/**
 * @covers \SMW\MediaWiki\Specials\Ask\ErrorWidget
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class ErrorWidgetTest extends TestCase {

	public function testSessionFailure() {
		$this->assertIsString(

			ErrorWidget::sessionFailure()
		);
	}

	public function testNoScript() {
		$this->assertIsString(

			ErrorWidget::noScript()
		);
	}

	public function testNoResult() {
		$this->assertIsString(

			ErrorWidget::noResult()
		);
	}

	public function testQueryErrorEscapesReflectedMarkup() {
		$query = $this->getMockBuilder( Query::class )
			->disableOriginalConstructor()
			->getMock();

		// `Message::encode` un-swaps %3C/%3E into < > after strip_tags, so a
		// crafted query chunk can smuggle live markup into the error stream.
		$error = Message::encode( [ '%3Eimg src=x onerror=alert(1)%3C' ] );

		$query->method( 'getErrors' )
			->willReturn( [ $error ] );

		$html = ErrorWidget::queryError( $query );

		$this->assertStringNotContainsString( '<img src=x onerror=alert(1)>', $html );
		$this->assertStringContainsString( '&lt;img src=x onerror=alert(1)&gt;', $html );
	}

	/**
	 * @dataProvider queryErrorProvider
	 */
	public function testGetFormattedQueryErrorElement( $errors, $expected ) {
		$query = $this->getMockBuilder( Query::class )
			->disableOriginalConstructor()
			->getMock();

		$query->expects( $this->atLeastOnce() )
			->method( 'getErrors' )
			->willReturn( $errors );

		$this->assertEquals(
			$expected,
			ErrorWidget::queryError( $query )
		);
	}

	/**
	 * Return an error box using core MW method
	 * This is required because the output is different depending on the MW version
	 *
	 * @param string $message
	 * @return $string HTML of the error message box
	 * @since 5.0.0
	 */
	private function getErrorMessageHTML( $message ) {
		return Html::errorBox( $message, '', 'smw-error-result-error' );
	}

	public function queryErrorProvider() {
		$provider[] = [
			[],
			''
		];

		$provider[] = [
			[ 'Foo' ],
			$this->getErrorMessageHTML( 'Foo' )
		];

		$provider[] = [
			[ 'Foo', 'Bar' ],
			$this->getErrorMessageHTML( '<ul><li>Foo</li><li>Bar</li></ul>' )
		];

		$provider[] = [
			[ 'Foo', [ 'Bar' ] ],
			$this->getErrorMessageHTML( '<ul><li>Foo</li><li>Bar</li></ul>' )
		];

		// Filter duplicate
		$provider[] = [
			[ 'Foo', [ 'Bar' ], 'Bar' ],
			$this->getErrorMessageHTML( '<ul><li>Foo</li><li>Bar</li></ul>' )
		];

		return $provider;
	}

}
