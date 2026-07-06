<?php

namespace SMW\Tests\Unit\MediaWiki\Hooks;

use MediaWiki\Output\OutputPage;
use MediaWiki\User\Options\UserOptionsLookup;
use MediaWiki\User\User;
use PHPUnit\Framework\TestCase;
use SMW\Localizer\MessageLocalizer;
use SMW\MediaWiki\Hooks\SpecialSearchResultsPrepend;
use SMW\MediaWiki\Search\ExtendedSearchEngine;
use SpecialSearch;

/**
 * @covers \SMW\MediaWiki\Hooks\SpecialSearchResultsPrepend
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class SpecialSearchResultsPrependTest extends TestCase {

	private $userOptionsLookup;
	private $user;
	private $messageLocalizer;

	protected function setUp(): void {
		$this->userOptionsLookup = $this->getMockBuilder( UserOptionsLookup::class )
			->disableOriginalConstructor()
			->getMock();

		$this->user = $this->getMockBuilder( User::class )
			->disableOriginalConstructor()
			->getMock();

		$this->messageLocalizer = $this->getMockBuilder( MessageLocalizer::class )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			SpecialSearchResultsPrepend::class,
			new SpecialSearchResultsPrepend( $this->userOptionsLookup )
		);
	}

	public function testProcess() {
		$this->userOptionsLookup->expects( $this->any() )
			->method( 'getOption' )
			->willReturnCallback( static function ( $user, $key ) {
				return $key === 'smw-prefs-general-options-suggester-textinput';
			} );

		$search = $this->getMockBuilder( ExtendedSearchEngine::class )
			->disableOriginalConstructor()
			->getMock();

		$specialSearch = $this->getMockBuilder( SpecialSearch::class )
			->disableOriginalConstructor()
			->getMock();

		$specialSearch->expects( $this->atLeastOnce() )
			->method( 'getSearchEngine' )
			->willReturn( $search );

		$outputPage = $this->getMockBuilder( OutputPage::class )
			->disableOriginalConstructor()
			->getMock();

		$outputPage->expects( $this->any() )
			->method( 'getUser' )
			->willReturn( $this->user );

		$outputPage->expects( $this->atLeastOnce() )
			->method( 'addHtml' );

		$instance = new SpecialSearchResultsPrepend(
			$this->userOptionsLookup
		);

		$instance->setMessageLocalizer(
			$this->messageLocalizer
		);

		$this->assertTrue(
			$instance->onSpecialSearchResultsPrepend( $specialSearch, $outputPage, '' )
		);
	}

	public function testProcess_DisabledInfo() {
		$this->userOptionsLookup->expects( $this->any() )
			->method( 'getOption' )
			->willReturnCallback( static function ( $user, $key ) {
				return $key === 'smw-prefs-general-options-disable-search-info';
			} );

		$search = $this->getMockBuilder( ExtendedSearchEngine::class )
			->disableOriginalConstructor()
			->getMock();

		$specialSearch = $this->getMockBuilder( SpecialSearch::class )
			->disableOriginalConstructor()
			->getMock();

		$specialSearch->expects( $this->atLeastOnce() )
			->method( 'getSearchEngine' )
			->willReturn( $search );

		$outputPage = $this->getMockBuilder( OutputPage::class )
			->disableOriginalConstructor()
			->getMock();

		$outputPage->expects( $this->any() )
			->method( 'getUser' )
			->willReturn( $this->user );

		$outputPage->expects( $this->never() )
			->method( 'addHtml' );

		$instance = new SpecialSearchResultsPrepend(
			$this->userOptionsLookup
		);

		$instance->setMessageLocalizer(
			$this->messageLocalizer
		);

		$instance->onSpecialSearchResultsPrepend( $specialSearch, $outputPage, '' );
	}

}
