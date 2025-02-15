<?php

namespace SMW\Tests\SQLStore\EntityStore;

use SMW\DIWikiPage;
use SMW\Services\ServicesFactory as ApplicationFactory;
use SMW\SQLStore\EntityStore\SubobjectListFinder;

/**
 * @covers \SMW\SQLStore\EntityStore\SubobjectListFinder
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class SubobjectListFinderTest extends \PHPUnit\Framework\TestCase {

	private $iteratorFactory;

	public function setUp(): void {
		parent::setUp();

		$this->iteratorFactory = ApplicationFactory::getInstance()->getIteratorFactory();
	}

	public function testCanConstruct() {
		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$iteratorFactory = $this->getMockBuilder( '\SMW\IteratorFactory' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			SubobjectListFinder::class,
			new SubobjectListFinder( $store, $iteratorFactory )
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
			->willReturn( [] );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->setMethods( [ 'getConnection' ] )
			->getMock();

		$store->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$instance = new SubobjectListFinder(
			$store,
			$this->iteratorFactory
		);

		$this->assertInstanceOf(
			'\SMW\Iterators\MappingIterator',
			$instance->find( $subject )
		);
	}

	/**
	 * @dataProvider subjectProvider
	 */
	public function testIterateOn( $subject ) {
		$row = new \stdClass;
		$row->smw_id = 42;
		$row->smw_sortkey = 'sort';
		$row->smw_sort = 'SORT';
		$row->smw_subobject = '10000000001';

		$expected = [
			'Foo', 0, '', 'sort', '10000000001'
		];

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->atLeastOnce() )
			->method( 'select' )
			->with(
				$this->anything(),
				$this->anything(),
				'smw_title= AND smw_namespace= AND smw_iw= AND smw_subobject!=' )
			->willReturn( [ $row ] );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->setMethods( [ 'getConnection', 'getDataItemHandlerForDIType' ] )
			->getMock();

		$store->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$instance = new SubobjectListFinder(
			$store,
			$this->iteratorFactory
		);

		foreach ( $instance->find( $subject ) as $v ) {
			$this->assertEquals( 42, $v->getId() );
		}
	}

	public function subjectProvider() {
		$provider[] = [
			DIWikiPage::newFromText( 'Foo' )
		];

		$provider[] = [
			DIWikiPage::newFromText( 'Bar', SMW_NS_PROPERTY )
		];

		$provider[] = [
			DIWikiPage::newFromText( 'Modification date', SMW_NS_PROPERTY )
		];

		return $provider;
	}

}
