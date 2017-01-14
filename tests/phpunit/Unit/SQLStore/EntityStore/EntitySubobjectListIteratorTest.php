<?php

namespace SMW\Tests\SQLStore\EntityStore;

use SMW\SQLStore\EntityStore\EntitySubobjectListIterator;
use SMW\ApplicationFactory;
use SMW\DIWikiPage;

/**
 * @covers \SMW\SQLStore\EntityStore\EntitySubobjectListIterator
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class EntitySubobjectListIteratorTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$iteratorFactory = $this->getMockBuilder( '\SMW\IteratorFactory' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\SQLStore\EntityStore\EntitySubobjectListIterator',
			new EntitySubobjectListIterator( $store, $iteratorFactory )
		);
	}

	/**
	 * @dataProvider subjectProvider
	 */
	public function testNewMappingIterator( $subject ) {

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->atLeastOnce() )
			->method( 'select' )
			->will( $this->returnValue( array() ) );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->setMethods( array( 'getConnection' ) )
			->getMock();

		$store->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->will( $this->returnValue( $connection ) );

		$instance = new EntitySubobjectListIterator(
			$store,
			ApplicationFactory::getInstance()->getIteratorFactory()
		);

		$instance->newListIteratorFor( $subject );

		$this->assertInstanceOf(
			'\SMW\Iterators\MappingIterator',
			$instance->getIterator()
		);
	}

	/**
	 * @dataProvider subjectProvider
	 */
	public function testIterateOn( $subject ) {

		$row = new \stdClass;
		$row->smw_id = 42;
		$row->smw_sortkey = 'sort';
		$row->smw_subobject = '10000000001';

		$expected = array(
			'Foo', 0, '', 'sort', '10000000001'
		);

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->atLeastOnce() )
			->method( 'select' )
			->will( $this->returnValue( array( $row ) ) );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->setMethods( array( 'getConnection', 'getDataItemHandlerForDIType' ) )
			->getMock();

		$store->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->will( $this->returnValue( $connection ) );

		$instance = new EntitySubobjectListIterator(
			$store,
			ApplicationFactory::getInstance()->getIteratorFactory()
		);

		$instance->newListIteratorFor( $subject );

		foreach ( $instance as $v ) {
			$this->assertEquals( 42, $v->getId() );
		}
	}

	public function testMissingIteratorInstanceThrowsExcetion() {

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$iteratorFactory = $this->getMockBuilder( '\SMW\IteratorFactory' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new EntitySubobjectListIterator(
			$store,
			$iteratorFactory
		);

		$this->setExpectedException( 'RuntimeException' );
		foreach ( $instance as $v ) {
		}
	}

	public function subjectProvider() {

		$provider[] = array(
			DIWikiPage::newFromText( 'Foo' )
		);

		$provider[] = array(
			DIWikiPage::newFromText( 'Bar', SMW_NS_PROPERTY )
		);

		$provider[] = array(
			DIWikiPage::newFromText( 'Modification date', SMW_NS_PROPERTY )
		);

		return $provider;
	}

}
