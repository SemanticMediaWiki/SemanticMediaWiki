<?php

namespace SMW\Tests\MediaWiki\Page\ListBuilder;

use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\MediaWiki\Page\ListBuilder\ItemListBuilder;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\MediaWiki\Page\ListBuilder\ItemListBuilder
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class ItemListBuilderTest extends \PHPUnit\Framework\TestCase {

	private $store;
	private $stringValidator;

	protected function setUp(): void {
		parent::setUp();

		$this->stringValidator = TestEnvironment::newValidatorFactory()->newStringValidator();

		$this->store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			ItemListBuilder::class,
			new ItemListBuilder( $this->store )
		);
	}

	public function testCreateEmptyList() {
		$requestOptions = $this->getMockBuilder( '\SMW\RequestOptions' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new ItemListBuilder( $this->store );

		$property = new DIProperty( 'Foo' );
		$dataItem = new DIWikiPage( 'Bar', NS_MAIN );

		$this->assertSame(
			'',
			$instance->buildHTML( $property, $dataItem, $requestOptions )
		);
	}

	public function testCreateHtml() {
		$sortLetter = $this->getMockBuilder( '\SMW\SortLetter' )
			->disableOriginalConstructor()
			->getMock();

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->onlyMethods( [ 'getPropertySubjects', 'service' ] )
			->getMockForAbstractClass();

		$store->expects( $this->any() )
			->method( 'service' )
			->with( 'SortLetter' )
			->willReturn( $sortLetter );

		$store->expects( $this->once() )
			->method( 'getPropertySubjects' )
			->willReturn( [ DIWikiPage::newFromText( __METHOD__ ) ] );

		$requestOptions = $this->getMockBuilder( '\SMW\RequestOptions' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new ItemListBuilder( $store );
		$instance->setListLimit( 10 );

		$property = new DIProperty( 'Foo' );
		$dataItem = new DIWikiPage( 'Bar', NS_MAIN );

		$this->stringValidator->assertThatStringContains(
			[
				'title="SMW\Tests\MediaWiki\Page\ListBuilder\ItemListBuilderTest::testCreateHtml'
			],
			$instance->buildHTML( $property, $dataItem, $requestOptions )
		);
	}

}
