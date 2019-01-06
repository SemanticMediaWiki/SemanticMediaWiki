<?php

namespace SMW\Tests\Page;

use SMW\Page\ListBuilder;
use SMW\DIWikiPage;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\Page\ListBuilder
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class ListBuilderTest extends \PHPUnit_Framework_TestCase {

	private $store;
	private $Collator;

	protected function setUp() {
		parent::setUp();

		$this->collator = $this->getMockBuilder( '\SMW\MediaWiki\Collator' )
			->disableOriginalConstructor()
			->getMock();

		$this->store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			ListBuilder::class,
			new ListBuilder( $this->store )
		);
	}

	public function testGetList() {

		$this->store->expects( $this->once() )
			->method( 'getWikiPageSortKey' )
			->will( $this->returnValue( 'FOO' ) );

		$this->collator->expects( $this->once() )
			->method( 'getFirstLetter' )
			->will( $this->returnValue( 'F' ) );

		$instance = new ListBuilder(
			$this->store,
			$this->collator
		);

		$this->assertArrayHasKey(
			'F',
			$instance->getList( [ DIWikiPage::newFromText( 'Foo' ) ] )
		);
	}

	public function testGetList_Sorted() {

		$list = [
			DIWikiPage::newFromText( 'Foo' ),
			DIWikiPage::newFromText( 'ABC' )
		];

		$this->store->expects( $this->at( 0 ) )
			->method( 'getWikiPageSortKey' )
			->will( $this->returnValue( 'FOO' ) );

		$this->collator->expects( $this->at( 0 ) )
			->method( 'getFirstLetter' )
			->will( $this->returnValue( 'F' ) );

		$this->store->expects( $this->at( 1 ) )
			->method( 'getWikiPageSortKey' )
			->will( $this->returnValue( 'Abc' ) );

		$this->collator->expects( $this->at( 1 ) )
			->method( 'getFirstLetter' )
			->will( $this->returnValue( 'A' ) );

		$instance = new ListBuilder(
			$this->store,
			$this->collator
		);

		$this->assertEquals(
			[ 'A', 'F' ],
			array_keys( $instance->getList( $list ) )
		);
	}

	public function testGetColumnList() {

		$this->store->expects( $this->once() )
			->method( 'getWikiPageSortKey' )
			->will( $this->returnValue( 'FOO' ) );

		$this->collator->expects( $this->once() )
			->method( 'getFirstLetter' )
			->will( $this->returnValue( 'F' ) );

		$instance = new ListBuilder(
			$this->store,
			$this->collator
		);

		$instance->setLinker( null );

		$stringValidator = TestEnvironment::newValidatorFactory()->newStringValidator();

		$stringValidator->assertThatStringContains(
			[
				'<div class="smw-columnlist-container" dir="ltr"><div class="smw-column" style="width:100%;" dir="ltr">',
				'<div class="smw-column-header">F</div>',
				'<ul><li>Foo&#160;<span class="smwbrowse">'
			],
			$instance->getColumnList( [ DIWikiPage::newFromText( 'Foo' ) ] )
		);
	}

	public function testGetColumnList_ItemFormatter() {

		$this->store->expects( $this->once() )
			->method( 'getWikiPageSortKey' )
			->will( $this->returnValue( 'FOO' ) );

		$this->collator->expects( $this->once() )
			->method( 'getFirstLetter' )
			->will( $this->returnValue( 'F' ) );

		$instance = new ListBuilder(
			$this->store,
			$this->collator
		);

		$instance->setItemFormatter( function( $dataValue, $linker ) {
			return 'Bar';
		} );

		$stringValidator = TestEnvironment::newValidatorFactory()->newStringValidator();

		$stringValidator->assertThatStringContains(
			[
				'<div class="smw-columnlist-container" dir="ltr"><div class="smw-column" style="width:100%;" dir="ltr">',
				'<ul><li>Bar</li></ul></div>'
			],
			$instance->getColumnList( [ DIWikiPage::newFromText( 'Foo' ) ] )
		);
	}

}
