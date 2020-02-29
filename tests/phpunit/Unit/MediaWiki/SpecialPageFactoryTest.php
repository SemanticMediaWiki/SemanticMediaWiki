<?php

namespace SMW\Tests\MediaWiki;

use SMW\MediaWiki\SpecialPageFactory;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\MediaWiki\SpecialPageFactory
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since   3.2
 *
 * @author mwjames
 */
class SpecialPageFactoryTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	private $store;

	protected function setUp() : void {
		parent::setUp();

		$this->store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			SpecialPageFactory::class,
			new SpecialPageFactory( $this->store )
		);
	}

	public function testGetPage() {

		if ( method_exists( '\MediaWiki\Special\SpecialPageFactory', 'getPage' ) ) {
			$this->markTestSkipped( 'Using the SpecialPageFactory::getPage' );
		}

		$instance = new SpecialPageFactory(
			$this->store
		);

		$this->assertEquals(
			'',
			$instance->getPage( 'foo' )
		);
	}

	public function testGetPage_SpecialPageFactory() {

		if ( !method_exists( '\MediaWiki\Special\SpecialPageFactory', 'getPage' ) ) {
			$this->markTestSkipped( 'Using the \SpecialPageFactory::getPage' );
		}

		$specialPageFactory = $this->getMockBuilder( '\MediaWiki\Special\SpecialPageFactory' )
			->disableOriginalConstructor()
			->getMock();

		$specialPageFactory->expects( $this->once() )
			->method( 'getPage' )
			->will( $this->returnValue( 'Foo' ) );

		$instance = new SpecialPageFactory(
			$this->store,
			$specialPageFactory
		);

		$this->assertEquals(
			'Foo',
			$instance->getPage( 'foo' )
		);
	}

	public function testGetLocalNameFor() {

		if ( method_exists( '\MediaWiki\Special\SpecialPageFactory', 'getLocalNameFor' ) ) {
			$this->markTestSkipped( 'Using the SpecialPageFactory::getLocalNameFor' );
		}

		$instance = new SpecialPageFactory(
			$this->store
		);

		$this->assertInternalType(
			'string',
			$instance->getLocalNameFor( 'SMWAdmin' )
		);
	}

	public function testGetLocalNameFor_SpecialPageFactory() {

		if ( !method_exists( '\MediaWiki\Special\SpecialPageFactory', 'getLocalNameFor' ) ) {
			$this->markTestSkipped( 'Using the \SpecialPageFactory::getLocalNameFor' );
		}

		$specialPageFactory = $this->getMockBuilder( '\MediaWiki\Special\SpecialPageFactory' )
			->disableOriginalConstructor()
			->getMock();

		$specialPageFactory->expects( $this->once() )
			->method( 'getLocalNameFor' )
			->will( $this->returnValue( 'Foo' ) );

		$instance = new SpecialPageFactory(
			$this->store,
			$specialPageFactory
		);

		$this->assertEquals(
			'Foo',
			$instance->getLocalNameFor( 'foo' )
		);
	}

	public function testCanConstructSpecialPendingTaskList() {

		$instance = new SpecialPageFactory(
			$this->store
		);

		$this->assertInstanceOf(
			'\SMW\MediaWiki\Specials\SpecialPendingTaskList',
			$instance->newSpecialPendingTaskList()
		);
	}

}
