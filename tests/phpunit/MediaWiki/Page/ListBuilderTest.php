<?php

namespace SMW\Tests\MediaWiki\Page;

use SMW\DIWikiPage;
use SMW\MediaWiki\Page\ListBuilder;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\MediaWiki\Page\ListBuilder
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class ListBuilderTest extends \PHPUnit\Framework\TestCase {

	private $store;
	private $sortLetter;

	protected function setUp(): void {
		parent::setUp();

		$this->sortLetter = $this->getMockBuilder( '\SMW\SortLetter' )
			->disableOriginalConstructor()
			->getMock();

		$this->store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->any() )
			->method( 'service' )
			->with( 'SortLetter' )
			->willReturn( $this->sortLetter );
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			ListBuilder::class,
			new ListBuilder( $this->store )
		);
	}

	public function testGetList() {
		$this->sortLetter->expects( $this->once() )
			->method( 'getFirstLetter' )
			->willReturn( 'F' );

		$instance = new ListBuilder(
			$this->store
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

		$this->sortLetter->expects( $this->at( 0 ) )
			->method( 'getFirstLetter' )
			->willReturn( 'F' );

		$this->sortLetter->expects( $this->at( 1 ) )
			->method( 'getFirstLetter' )
			->willReturn( 'A' );

		$instance = new ListBuilder(
			$this->store
		);

		$this->assertEquals(
			[ 'A', 'F' ],
			array_keys( $instance->getList( $list ) )
		);
	}

	public function testGetColumnList() {
		$this->sortLetter->expects( $this->once() )
			->method( 'getFirstLetter' )
			->willReturn( 'F' );

		$instance = new ListBuilder(
			$this->store
		);

		$instance->setLinker( null );

		$stringValidator = TestEnvironment::newValidatorFactory()->newStringValidator();

		$stringValidator->assertThatStringContains(
			[
				'<div class="smw-columnlist-container" dir="ltr"><div class="smw-column-responsive" style="width:100%;columns:1 20em;" dir="ltr">',
				'<div class="smw-column-header">F</div>',
				'<ul><li>Foo&#160;<span class="smwbrowse">'
			],
			$instance->getColumnList( [ DIWikiPage::newFromText( 'Foo' ) ] )
		);
	}

	public function testGetColumnList_ItemFormatter() {
		$this->sortLetter->expects( $this->once() )
			->method( 'getFirstLetter' )
			->willReturn( 'F' );

		$instance = new ListBuilder(
			$this->store
		);

		$instance->setItemFormatter( static function ( $dataValue, $linker ) {
			return 'Bar';
		} );

		$stringValidator = TestEnvironment::newValidatorFactory()->newStringValidator();

		$stringValidator->assertThatStringContains(
			[
				'<div class="smw-columnlist-container" dir="ltr"><div class="smw-column-responsive" style="width:100%;columns:1 20em;" dir="ltr">',
				'<ul><li>Bar</li></ul></div>'
			],
			$instance->getColumnList( [ DIWikiPage::newFromText( 'Foo' ) ] )
		);
	}

}
