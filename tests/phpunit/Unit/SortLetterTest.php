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
class SortLetterTest extends \PHPUnit_Framework_TestCase {

	private $store;
	private $collator;

	protected function setUp() {

		$this->store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->setMethods( [ 'getWikiPageSortKey' ] )
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
			->will( $this->returnValue( \SMWDataItem::TYPE_WIKIPAGE ) );

		$this->store->expects( $this->once() )
			->method( 'getWikiPageSortKey' )
			->will( $this->returnValue( 'Foo' ) );

		$this->collator->expects( $this->once() )
			->method( 'getFirstLetter' )
			->will( $this->returnValue( 'F' ) );

		$instance = new SortLetter(
			$this->store,
			$this->collator
		);

		$instance->getFirstLetter( $dataItem );
	}

}
