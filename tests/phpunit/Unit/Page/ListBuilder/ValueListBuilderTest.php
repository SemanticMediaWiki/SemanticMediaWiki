<?php

namespace SMW\Tests\Page\ListBuilder;

use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\Page\ListBuilder\ValueListBuilder;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\Page\ListBuilder\ValueListBuilder
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class ValueListBuilderTest extends \PHPUnit_Framework_TestCase {

	private $store;
	private $testEnvironment;
	private $stringValidator;

	protected function setUp() {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment( [ 'smwgCompactLinkSupport' => false ] );
		$this->stringValidator = $this->testEnvironment->newValidatorFactory()->newStringValidator();

		$this->store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();
	}

	protected function tearDown() {
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

		$property = new DIProperty( 'Foo' );
		$dataItem = new DIWikiPage( 'Bar', NS_MAIN );

		$this->assertEquals(
			'',
			$instance->createHtml( $property, $dataItem, [] )
		);
	}

	public function testCreateHtml() {

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->setMethods( [ 'getAllPropertySubjects', 'getPropertyValues', 'getWikiPageSortKey' ] )
			->getMockForAbstractClass();

		$store->expects( $this->once() )
			->method( 'getAllPropertySubjects' )
			->will( $this->returnValue( [ DIWikiPage::newFromText( __METHOD__ ) ] ) );

		$store->expects( $this->once() )
			->method( 'getWikiPageSortKey' )
			->will( $this->returnValue( 'Bar' ) );

		$store->expects( $this->once() )
			->method( 'getPropertyValues' )
			->will( $this->returnValue( [ DIWikiPage::newFromText( 'Bar' ) ] ) );

		$instance = new ValueListBuilder( $store );
		$instance->setLanguageCode( 'en' );

		$property = new DIProperty( 'Foo' );
		$dataItem = new DIWikiPage( 'Bar', NS_MAIN );

		$this->stringValidator->assertThatStringContains(
			[
				'<div class="smw-table-row header-row"><div class="smw-table-cell header-title"><div id="B">B</div>',
				'title="SMW\Tests\Page\ListBuilder\ValueListBuilderTest::testCreateHtml',
				'<span class="smwsearch">.*:Foo/Bar">+</a>'
			],
			$instance->createHtml( $property, $dataItem, [ 'limit' => 10 ] )
		);
	}

}
