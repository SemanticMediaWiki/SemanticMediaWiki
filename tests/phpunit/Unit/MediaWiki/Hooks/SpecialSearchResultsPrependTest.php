<?php

namespace SMW\Tests\Unit\MediaWiki\Hooks;

use MediaWiki\Output\OutputPage;
use MediaWiki\User\User;
use PHPUnit\Framework\TestCase;
use SMW\Localizer\MessageLocalizer;
use SMW\MediaWiki\Hooks\SpecialSearchResultsPrepend;
use SMW\MediaWiki\Preference\PreferenceExaminer;
use SMW\MediaWiki\Search\ExtendedSearchEngine;

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

	private $preferenceExaminer;
	private $messageLocalizer;

	protected function setUp(): void {
		$this->preferenceExaminer = $this->getMockBuilder( PreferenceExaminer::class )
			->disableOriginalConstructor()
			->getMock();

		$this->messageLocalizer = $this->getMockBuilder( MessageLocalizer::class )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$specialSearch = $this->getMockBuilder( '\SpecialSearch' )
			->disableOriginalConstructor()
			->getMock();

		$outputPage = $this->getMockBuilder( OutputPage::class )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			SpecialSearchResultsPrepend::class,
			new SpecialSearchResultsPrepend( $this->preferenceExaminer, $specialSearch, $outputPage )
		);
	}

	public function testProcess() {
		$this->preferenceExaminer->expects( $this->any() )
			->method( 'hasPreferenceOf' )
			->willReturnCallback( static function ( $key ) {
				return $key === 'smw-prefs-general-options-suggester-textinput';
			} );

		$search = $this->getMockBuilder( ExtendedSearchEngine::class )
			->disableOriginalConstructor()
			->getMock();

		$user = $this->getMockBuilder( User::class )
			->disableOriginalConstructor()
			->getMock();

		$specialSearch = $this->getMockBuilder( '\SpecialSearch' )
			->disableOriginalConstructor()
			->getMock();

		$specialSearch->expects( $this->atLeastOnce() )
			->method( 'getSearchEngine' )
			->willReturn( $search );

		$outputPage = $this->getMockBuilder( OutputPage::class )
			->disableOriginalConstructor()
			->getMock();

		$outputPage->expects( $this->atLeastOnce() )
			->method( 'addHtml' );

		$instance = new SpecialSearchResultsPrepend(
			$this->preferenceExaminer,
			$specialSearch,
			$outputPage
		);

		$instance->setMessageLocalizer(
			$this->messageLocalizer
		);

		$this->assertTrue(
			$instance->process( '' )
		);
	}

	public function testProcess_DisabledInfo() {
		$this->preferenceExaminer->expects( $this->any() )
			->method( 'hasPreferenceOf' )
			->willReturnCallback( static function ( $key ) {
				return $key === 'smw-prefs-general-options-disable-search-info';
			} );

		$search = $this->getMockBuilder( ExtendedSearchEngine::class )
			->disableOriginalConstructor()
			->getMock();

		$user = $this->getMockBuilder( User::class )
			->disableOriginalConstructor()
			->getMock();

		$specialSearch = $this->getMockBuilder( '\SpecialSearch' )
			->disableOriginalConstructor()
			->getMock();

		$specialSearch->expects( $this->atLeastOnce() )
			->method( 'getSearchEngine' )
			->willReturn( $search );

		$outputPage = $this->getMockBuilder( OutputPage::class )
			->disableOriginalConstructor()
			->getMock();

		$outputPage->expects( $this->never() )
			->method( 'addHtml' );

		$instance = new SpecialSearchResultsPrepend(
			$this->preferenceExaminer,
			$specialSearch,
			$outputPage
		);

		$instance->setMessageLocalizer(
			$this->messageLocalizer
		);

		$instance->process( '' );
	}

}
