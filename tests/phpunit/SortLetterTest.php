<?php

namespace SMW\Tests;

use SMW\SortLetter;

/**
 * @covers \SMW\SortLetter
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since   3.1
 *
 * @author mwjames
 */
class SortLetterTest extends \PHPUnit\Framework\TestCase {

	private $store;
	private $collator;

	protected function setUp(): void {
		$this->store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->onlyMethods( [ 'getWikiPageSortKey' ] )
			->getMockForAbstractClass();

		$this->collator = $this->getMockBuilder( '\SMW\MediaWiki\Collator' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			SortLetter::class,
			new SortLetter( $this->store, $this->collator )
		);
	}

	public function testFindFirstLetter() {
		$dataItem = $this->getMockBuilder( '\SMW\DIWikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$dataItem->expects( $this->any() )
			->method( 'getDIType' )
			->willReturn( \SMWDataItem::TYPE_WIKIPAGE );

		$this->store->expects( $this->once() )
			->method( 'getWikiPageSortKey' )
			->willReturn( 'Foo' );

		$this->collator->expects( $this->once() )
			->method( 'getFirstLetter' )
			->willReturn( 'F' );

		$instance = new SortLetter(
			$this->store,
			$this->collator
		);

		$instance->getFirstLetter( $dataItem );
	}

}
