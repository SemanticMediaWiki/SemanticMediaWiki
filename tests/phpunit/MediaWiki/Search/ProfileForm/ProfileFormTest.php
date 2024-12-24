<?php

namespace SMW\Tests\MediaWiki\Search\ProfileForm;

use SMW\MediaWiki\Search\ProfileForm\ProfileForm;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\MediaWiki\Search\ProfileForm\ProfileForm
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class ProfileFormTest extends \PHPUnit\Framework\TestCase {

	private $store;
	private $specialSearch;
	private $requestContext;
	private $outputPage;
	private $webRequest;
	private $user;
	private $stringValidator;

	protected function setUp(): void {
		$this->stringValidator = TestEnvironment::newValidatorFactory()->newStringValidator();

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
			->willReturn( $this->outputPage );

		$this->requestContext->expects( $this->any() )
			->method( 'getRequest' )
			->willReturn( $this->webRequest );

		$this->requestContext->expects( $this->any() )
			->method( 'getUser' )
			->willReturn( $this->user );
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			ProfileForm::class,
			new ProfileForm( $this->store, $this->specialSearch )
		);
	}

	public function testIsValidProfile() {
		$this->assertFalse(
			ProfileForm::isValidProfile( 'foo' )
		);
	}

	public function testAddProfile() {
		$profile = [];
		$options = [
			'default_namespaces' => []
		];

		ProfileForm::addProfile( SMW_SPECIAL_SEARCHTYPE, $profile, $options );

		$this->assertArrayHasKey(
			'smw',
			$profile
		);
	}

	public function testBuildForm() {
		$form = '';
		$opts = [];

		$this->store->expects( $this->any() )
			->method( 'getPropertySubjects' )
			->willReturn( [] );

		$searchEngine = $this->getMockBuilder( '\SMW\MediaWiki\Search\ExtendedSearchEngine' )
			->disableOriginalConstructor()
			->getMock();

		$searchEngine->expects( $this->any() )
			->method( 'getErrors' )
			->willReturn( [] );

		$this->specialSearch->expects( $this->any() )
			->method( 'getSearchEngine' )
			->willReturn( $searchEngine );

		$this->specialSearch->expects( $this->any() )
			->method( 'getNamespaces' )
			->willReturn( [] );

		$this->specialSearch->expects( $this->any() )
			->method( 'getUser' )
			->willReturn( $this->user );

		$this->specialSearch->expects( $this->any() )
			->method( 'getContext' )
			->willReturn( $this->requestContext );

		$instance = new ProfileForm(
			$this->store,
			$this->specialSearch
		);

		$instance->buildForm( $form, $opts );

		$expected = [
			'<fieldset id="smw-searchoptions">',
			'<input type="hidden" name="ns-list">',
			'<div class="smw-search-options">',
			'<div class="smw-search-sort"><button type="button" id="smw-search-sort" class="smw-selectmenu-button is-disabled" name="sort"',
		];

		// MW 1.39-1.40 produces self-closing tag, which is invalid HTML
		$actual = str_replace( '/>', '>', $form );

		$this->stringValidator->assertThatStringContains(
			$expected,
			$actual
		);
	}

	public function testGetPrefixMap() {
		$data = [
			'term_parser' => [
				'prefix' => [
					'Foo' => []
				]
			]
		];

		$map = ProfileForm::getPrefixMap( $data );

		$this->assertArrayHasKey(
			'Foo',
			$map
		);
	}

}
