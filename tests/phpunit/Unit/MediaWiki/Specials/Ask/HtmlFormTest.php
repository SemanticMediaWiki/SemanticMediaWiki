<?php

namespace SMW\Tests\MediaWiki\Specials\Ask;

use SMW\MediaWiki\Specials\Ask\HtmlForm;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\MediaWiki\Specials\Ask\HtmlForm
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class HtmlFormTest extends \PHPUnit_Framework_TestCase {

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

		$urlArgs = $this->getMockBuilder( '\SMW\MediaWiki\Specials\Ask\UrlArgs' )
			->disableOriginalConstructor()
			->getMock();

		$query = $this->getMockBuilder( '\SMWQuery' )
			->disableOriginalConstructor()
			->getMock();

		$query->expects( $this->once() )
			->method( 'getExtraPrintouts' )
			->will( $this->returnValue( [] ) );

		$query->expects( $this->once() )
			->method( 'getSortKeys' )
			->will( $this->returnValue( [] ) );

		$queryResult = $this->getMockBuilder( '\SMWQueryResult' )
			->disableOriginalConstructor()
			->getMock();

		$queryResult->expects( $this->atLeastOnce() )
			->method( 'getQuery' )
			->will( $this->returnValue( $query ) );

		$instance = new HtmlForm( $title );
		$instance->isEditMode( true );

 		$text = '';

		$this->assertInternalType(
			'string',
			$instance->getForm( $urlArgs, $queryResult, $text )
		);
	}

}
