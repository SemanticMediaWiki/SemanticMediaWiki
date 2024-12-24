<?php

namespace SMW\Tests\MediaWiki\Specials\Ask;

use Html;
use SMW\MediaWiki\Specials\Ask\ErrorWidget;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\MediaWiki\Specials\Ask\ErrorWidget
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class ErrorWidgetTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

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

	/**
	 * @dataProvider queryErrorProvider
	 */
	public function testGetFormattedQueryErrorElement( $errors, $expected ) {
		$query = $this->getMockBuilder( '\SMWQuery' )
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
			'',
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
