<?php

namespace SMW\Tests\MediaWiki\Specials\Ask;

use SMW\MediaWiki\Specials\Ask\HtmlForm;
use SMW\Tests\TestEnvironment;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\MediaWiki\Specials\Ask\HtmlForm
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class HtmlFormTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	public function testCanConstruct() {
		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			HtmlForm::class,
			new HtmlForm( $title )
		);
	}

	public function testGetForm_IsEditMode() {
		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$urlArgs = $this->getMockBuilder( '\SMW\Utils\UrlArgs' )
			->disableOriginalConstructor()
			->getMock();

		$query = $this->getMockBuilder( '\SMWQuery' )
			->disableOriginalConstructor()
			->getMock();

		$query->expects( $this->once() )
			->method( 'getExtraPrintouts' )
			->willReturn( [] );

		$query->expects( $this->once() )
			->method( 'getSortKeys' )
			->willReturn( [] );

		$queryResult = $this->getMockBuilder( '\SMWQueryResult' )
			->disableOriginalConstructor()
			->getMock();

		$queryResult->expects( $this->atLeastOnce() )
			->method( 'getQuery' )
			->willReturn( $query );

		$instance = new HtmlForm( $title );
		$instance->isEditMode( true );

		$log = [];

		$this->assertIsString(

			$instance->getForm( $urlArgs, $queryResult, $log )
		);
	}

}
