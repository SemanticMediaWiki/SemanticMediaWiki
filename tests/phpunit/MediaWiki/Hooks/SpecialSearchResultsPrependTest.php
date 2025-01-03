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
class SpecialSearchResultsPrependTest extends \PHPUnit\Framework\TestCase {

	private $preferenceExaminer;
	private $messageLocalizer;

	protected function setUp(): void {
		$this->preferenceExaminer = $this->getMockBuilder( '\SMW\MediaWiki\Preference\PreferenceExaminer' )
			->disableOriginalConstructor()
			->getMock();

		$this->messageLocalizer = $this->getMockBuilder( '\SMW\Localizer\MessageLocalizer' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$specialSearch = $this->getMockBuilder( '\SpecialSearch' )
			->disableOriginalConstructor()
			->getMock();

		$outputPage = $this->getMockBuilder( '\OutputPage' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			SpecialSearchResultsPrepend::class,
			new SpecialSearchResultsPrepend( $this->preferenceExaminer, $specialSearch, $outputPage )
		);
	}

	public function testProcess() {
		$this->preferenceExaminer->expects( $this->at( 1 ) )
			->method( 'hasPreferenceOf' )
			->with( 'smw-prefs-general-options-suggester-textinput' )
			->willReturn( true );

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
			->willReturn( $search );

		$outputPage = $this->getMockBuilder( '\OutputPage' )
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
		$this->preferenceExaminer->expects( $this->at( 2 ) )
			->method( 'hasPreferenceOf' )
			->with( 'smw-prefs-general-options-disable-search-info' )
			->willReturn( true );

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
			->willReturn( $search );

		$outputPage = $this->getMockBuilder( '\OutputPage' )
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
