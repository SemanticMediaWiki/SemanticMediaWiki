<?php

namespace SMW\Tests\SQLStore\ChangeOp;

use SMW\DIWikiPage;
use SMW\SQLStore\ChangeOp\ChangeDiff;

/**
 * @covers \SMW\SQLStore\ChangeOp\ChangeDiff
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class ChangeDiffTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			ChangeDiff::class,
			new ChangeDiff( DIWikiPage::newFromText( 'Foo' ), [], [], [] )
		);
	}

	public function testGetSubject() {

		$subject = DIWikiPage::newFromText( 'Foo' );
		$instance = new ChangeDiff(
			$subject,
			[],
			[],
			[]
		);

		$this->assertEquals(
			$subject,
			$instance->getSubject()
		);
	}

	public function testGetPropertyList() {

		$instance = new ChangeDiff(
			DIWikiPage::newFromText( 'Foo' ),
			[],
			[],
			[ 'Foo' => 42 ]
		);

		$this->assertEquals(
			[ 'Foo' => 42 ],
			$instance->getPropertyList()
		);

		$this->assertEquals(
			[ 42 => 'Foo' ],
			$instance->getPropertyList( true )
		);
	}

	public function testGetPropertyList_SortById() {

		$instance = new ChangeDiff(
			DIWikiPage::newFromText( 'Foo' ),
			[],
			[],
			[ 'Foo' => [ '_id' => 42, '_type' => '_foo' ] ]
		);

		$this->assertEquals(
			[ 42 => [ '_key' => 'Foo', '_type' => '_foo' ] ],
			$instance->getPropertyList( 'id' )
		);
	}

	public function testSave() {

		$cache = $this->getMockBuilder( '\Onoi\Cache\Cache' )
			->disableOriginalConstructor()
			->getMock();

		$tableChangeOp = $this->getMockBuilder( '\SMW\SQLStore\ChangeOp\TableChangeOp' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new ChangeDiff(
			DIWikiPage::newFromText( 'Foo' ),
			[ $tableChangeOp ],
			[],
			[ 'Foo' => 42 ]
		);

		$cache->expects( $this->once() )
			->method( 'save' )
			->with(
				$this->stringContains( ChangeDiff::CACHE_NAMESPACE ),
				$this->equalTo( $instance->serialize() ) );

		$instance->save( $cache );
	}

	public function testFetch() {

		$subject = DIWikiPage::newFromText( 'Foo' );

		$cache = $this->getMockBuilder( '\Onoi\Cache\Cache' )
			->disableOriginalConstructor()
			->getMock();

		$tableChangeOp = $this->getMockBuilder( '\SMW\SQLStore\ChangeOp\TableChangeOp' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new ChangeDiff(
			DIWikiPage::newFromText( 'Foo' ),
			[ $tableChangeOp ],
			[],
			[ 'Foo' => 42 ]
		);

		$cache->expects( $this->once() )
			->method( 'fetch' )
			->will( $this->returnValue( $instance->serialize() ) );

		$this->assertEquals(
			$instance,
			ChangeDiff::fetch( $cache, $subject )
		);
	}

	public function testChangeList() {

		$instance = new ChangeDiff(
			DIWikiPage::newFromText( 'Foo' ),
			[],
			[],
			[]
		);

		$instance->setChangeList( 'Foo', [ '42', 1001 ] );

		$this->assertEquals(
			[ '42', 1001 ],
			$instance->getChangeListByType( 'Foo' )
		);
	}

	public function testAssociatedRev() {

		$instance = new ChangeDiff(
			DIWikiPage::newFromText( 'Foo' ),
			[],
			[],
			[]
		);

		$instance->setAssociatedRev( 42 );

		$this->assertEquals(
			42,
			$instance->getAssociatedRev()
		);
	}

	public function FetchFromCache() {

		$changeDiff = ChangeDiff::fetch(
			\SMW\ApplicationFactory::getInstance()->getCache(),
			DIWikiPage::newFromText( 'DifferentSort' )
		);

		$this->assertInstanceOf(
			ChangeDiff::class,
			$changeDiff
		);
	}

}
