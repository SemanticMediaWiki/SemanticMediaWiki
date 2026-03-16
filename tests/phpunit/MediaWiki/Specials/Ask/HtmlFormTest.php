<?php

namespace SMW\Tests\MediaWiki\Specials\Ask;

use MediaWiki\Title\Title;
use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\Specials\Ask\HtmlForm;
use SMW\Query\QueryResult;
use SMW\Utils\UrlArgs;

/**
 * @covers \SMW\MediaWiki\Specials\Ask\HtmlForm
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class HtmlFormTest extends TestCase {

	public function testCanConstruct() {
		$title = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			HtmlForm::class,
			new HtmlForm( $title )
		);
	}

	public function testGetForm_IsEditMode() {
		$title = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();

		$urlArgs = $this->getMockBuilder( UrlArgs::class )
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

		$queryResult = $this->getMockBuilder( QueryResult::class )
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
