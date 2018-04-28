<?php

namespace SMW\Tests\MediaWiki\Search;

use SMW\MediaWiki\Search\SearchProfileForm;

/**
 * @covers \SMW\MediaWiki\Search\SearchProfileForm
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class SearchProfileFormTest extends \PHPUnit_Framework_TestCase {

	private $store;
	private $specialSearch;
	private $requestContext;
	private $outputPage;
	private $webRequest;
	private $user;

	protected function setUp() {

		$this->store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->specialSearch = $this->getMockBuilder( '\SpecialSearch' )
			->disableOriginalConstructor()
			->getMock();

		$this->outputPage = $this->getMockBuilder( '\OutputPage' )
			->disableOriginalConstructor()
			->getMock();

		$this->webRequest = $this->getMockBuilder( '\WebRequest' )
			->disableOriginalConstructor()
			->getMock();

		$this->user = $this->getMockBuilder( '\User' )
			->disableOriginalConstructor()
			->getMock();

		$this->requestContext = $this->getMockBuilder( '\RequestContext' )
			->disableOriginalConstructor()
			->getMock();

		$this->requestContext->expects( $this->any() )
			->method( 'getOutput' )
			->will( $this->returnValue( $this->outputPage ) );

		$this->requestContext->expects( $this->any() )
			->method( 'getRequest' )
			->will( $this->returnValue( $this->webRequest ) );

		$this->requestContext->expects( $this->any() )
			->method( 'getUser' )
			->will( $this->returnValue( $this->user ) );
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			SearchProfileForm::class,
			new SearchProfileForm( $this->store, $this->specialSearch )
		);
	}

	public function testAddProfile() {

		$profile = [];

		SearchProfileForm::addProfile( 'SMWSearch', $profile );

		$this->assertArrayHasKey(
			'smw',
			$profile
		);
	}

	public function testGetForm() {

		$form = '';
		$opts = [];

		$this->store->expects( $this->any() )
			->method( 'getPropertySubjects' )
			->will( $this->returnValue( [] ) );

		$searchEngine = $this->getMockBuilder( '\SMW\MediaWiki\Search\Search' )
			->disableOriginalConstructor()
			->getMock();

		$searchEngine->expects( $this->any() )
			->method( 'getErrors' )
			->will( $this->returnValue( [] ) );

		$this->specialSearch->expects( $this->any() )
			->method( 'getSearchEngine' )
			->will( $this->returnValue( $searchEngine ) );

		$this->specialSearch->expects( $this->any() )
			->method( 'getNamespaces' )
			->will( $this->returnValue( [] ) );

		$this->specialSearch->expects( $this->any() )
			->method( 'getUser' )
			->will( $this->returnValue( $this->user ) );

		$this->specialSearch->expects( $this->any() )
			->method( 'getContext' )
			->will( $this->returnValue( $this->requestContext ) );

		$instance = new SearchProfileForm(
			$this->store,
			$this->specialSearch
		);

		$instance->getForm( $form, $opts );

		$expected = [
			'<fieldset id="smw-searchoptions">',
			'<input type="hidden" name="ns-list"/>',
			'<div id="smw-query" style="display:none;"></div>',
			'<div class="smw-search-options">',
			'<div id="smw-search-sort" class="smw-select" style="margin-right:10px;">',
		];

		$this->assertContains(
			implode( '', $expected ),
			$form
		);
	}

}
