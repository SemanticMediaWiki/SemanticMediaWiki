<?php

namespace SMW\Tests\MediaWiki\Page\ListBuilder;

use PHPUnit\Framework\TestCase;
use SMW\DataItems\Property;
use SMW\DataItems\Time;
use SMW\DataItems\WikiPage;
use SMW\MediaWiki\Page\ListBuilder\ValueListBuilder;
use SMW\SQLStore\EntityStore\PrefetchItemLookup;
use SMW\Store;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\MediaWiki\Page\ListBuilder\ValueListBuilder
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class ValueListBuilderTest extends TestCase {

	private $store;
	private $prefetchItemLookup;
	private $testEnvironment;
	private $stringValidator;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment( [ 'smwgCompactLinkSupport' => false ] );
		$this->stringValidator = $this->testEnvironment->newValidatorFactory()->newStringValidator();

		$this->store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->prefetchItemLookup = $this->getMockBuilder( PrefetchItemLookup::class )
			->disableOriginalConstructor()
			->getMock();
	}

	protected function tearDown(): void {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			ValueListBuilder::class,
			new ValueListBuilder( $this->store )
		);
	}

	public function testCreateEmptyList() {
		$instance = new ValueListBuilder( $this->store );

		$property = new Property( 'Foo' );
		$dataItem = new WikiPage( 'Bar', NS_MAIN );

		$this->assertSame(
			'',
			$instance->createHtml( $property, $dataItem, [] )
		);
	}

	public function testCreateHtml() {
		$subject = WikiPage::newFromText( __METHOD__ );

		$this->prefetchItemLookup->expects( $this->once() )
			->method( 'getPropertyValues' )
			->willReturn( [ $subject->getHash() => [ WikiPage::newFromText( 'Bar' ) ] ] );

		$store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->setMethods( [ 'getAllPropertySubjects', 'getPropertyValues', 'getWikiPageSortKey', 'service' ] )
			->getMockForAbstractClass();

		$store->expects( $this->once() )
			->method( 'getAllPropertySubjects' )
			->willReturn( [ $subject ] );

		$store->expects( $this->once() )
			->method( 'getWikiPageSortKey' )
			->willReturn( 'Bar' );

		$store->expects( $this->once() )
			->method( 'service' )
			->willReturn( $this->prefetchItemLookup );

		$instance = new ValueListBuilder( $store );
		$instance->setLanguageCode( 'en' );

		$property = new Property( 'Foo' );
		$dataItem = new WikiPage( 'Bar', NS_MAIN );

		$this->stringValidator->assertThatStringContains(
			[
				'<div class="smw-table-row header-row"><div class="smw-table-cell header-title"><div id="B">B</div>',
				'title="SMW\Tests\MediaWiki\Page\ListBuilder\ValueListBuilderTest::testCreateHtml',
				'<span class="smwbrowse">.*:Bar">+</a>'
			],
			$instance->createHtml( $property, $dataItem, [ 'limit' => 10 ] )
		);
	}

	public function testCreateHtml_TimeOffset() {
		$subject = WikiPage::newFromText( __METHOD__ );

		$this->prefetchItemLookup->expects( $this->once() )
			->method( 'getPropertyValues' )
			->willReturn( [ $subject->getHash() => [ new Time( Time::CM_GREGORIAN, 1970 ) ] ] );

		$store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->setMethods( [ 'getAllPropertySubjects', 'getPropertyValues', 'getWikiPageSortKey', 'service' ] )
			->getMockForAbstractClass();

		$store->expects( $this->once() )
			->method( 'getAllPropertySubjects' )
			->willReturn( [ $subject ] );

		$store->expects( $this->once() )
			->method( 'getWikiPageSortKey' )
			->willReturn( 'Bar' );

		$store->expects( $this->once() )
			->method( 'service' )
			->willReturn( $this->prefetchItemLookup );

		$instance = new ValueListBuilder( $store );
		$instance->setLanguageCode( 'en' );
		$instance->applyLocalTimeOffset( true );

		$property = new Property( 'Foo' );
		$property->setPropertyValueType( '_dat' );

		$dataItem = new WikiPage( 'Bar', NS_MAIN );

		$this->stringValidator->assertThatStringContains(
			[
				'<div class="smw-table-cell smwprops">1970&#160;&#160;'
			],
			$instance->createHtml( $property, $dataItem, [ 'limit' => 10 ] )
		);
	}

}
