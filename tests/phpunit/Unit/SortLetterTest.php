<?php

namespace SMW\Tests\Unit;

use PHPUnit\Framework\TestCase;
use SMW\DataItems\DataItem;
use SMW\DataItems\WikiPage;
use SMW\MediaWiki\Collator;
use SMW\SortLetter;
use SMW\Store;

/**
 * @covers \SMW\SortLetter
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since   3.1
 *
 * @author mwjames
 */
class SortLetterTest extends TestCase {

	private $store;
	private $collator;

	protected function setUp(): void {
		$this->store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->setMethods( [ 'getWikiPageSortKey' ] )
			->getMockForAbstractClass();

		$this->collator = $this->getMockBuilder( Collator::class )
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
		$dataItem = $this->getMockBuilder( WikiPage::class )
			->disableOriginalConstructor()
			->getMock();

		$dataItem->expects( $this->any() )
			->method( 'getDIType' )
			->willReturn( DataItem::TYPE_WIKIPAGE );

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
