<?php

namespace SMW\Tests\MediaWiki\Hooks;

use SMW\MediaWiki\Hooks\SpecialSearchResultsPrepend;

/**
 * @covers \SMW\MediaWiki\Hooks\SpecialSearchResultsPrepend
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class SpecialSearchResultsPrependTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$specialSearch = $this->getMockBuilder( '\SpecialSearch' )
			->disableOriginalConstructor()
			->getMock();

		$outputPage = $this->getMockBuilder( '\OutputPage' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			SpecialSearchResultsPrepend::class,
			new SpecialSearchResultsPrepend( $specialSearch, $outputPage )
		);
	}

	public function testProcess() {

		$search = $this->getMockBuilder( '\SMWSearch' )
			->disableOriginalConstructor()
			->getMock();

		$user = $this->getMockBuilder( '\User' )
			->disableOriginalConstructor()
			->getMock();

		$specialSearch = $this->getMockBuilder( '\SpecialSearch' )
			->disableOriginalConstructor()
			->getMock();

		$specialSearch->expects( $this->atLeastOnce() )
			->method( 'getSearchEngine' )
			->will( $this->returnValue( $search ) );

		$outputPage = $this->getMockBuilder( '\OutputPage' )
			->disableOriginalConstructor()
			->getMock();

		$outputPage->expects( $this->atLeastOnce() )
			->method( 'getUser' )
			->will( $this->returnValue( $user ) );

		$outputPage->expects( $this->atLeastOnce() )
			->method( 'addHtml' );

		$instance = new SpecialSearchResultsPrepend(
			$specialSearch,
			$outputPage
		);

		$this->assertTrue(
			$instance->process( '' )
		);
	}

}
