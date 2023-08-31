<?php

namespace SMW\Tests\MediaWiki;

use MediaWiki\MediaWikiServices;
use MediaWiki\Title\Title;
use SMW\MediaWiki\TitleFactory;
use SMW\Tests\PHPUnitCompat;
use Wikimedia\Rdbms\ILoadBalancer;

/**
 * @covers \SMW\MediaWiki\TitleFactory
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since   2.0
 *
 * @author mwjames
 */
class TitleFactoryTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	public function testCanConstruct() {

		$this->assertInstanceOf(
			TitleFactory::class,
			 new TitleFactory()
		);
	}

	public function testCreateTitleFromText() {

		$instance = new TitleFactory();

		$this->assertInstanceOf(
			'\Title',
			 $instance->newFromText( __METHOD__ )
		);
	}

	public function testNewFromID() {

		$instance = new TitleFactory();
		$title = $instance->newFromID( 9999999 );

		$this->assertTrue(
			$title === null || $title instanceof \Title
		);
	}

	public function testNewFromIDs() {

		$instance = new TitleFactory();

		$this->assertInternalType(
			'array',
			$instance->newFromIDs( [ 9999999 ] )
		);
	}

	public function testNewFromIDsEmpty() {

		$instance = new TitleFactory();
		$input = [];

		$out = $instance->newFromIDs( $input );

		$this->assertCount( 0, $out );

		$this->assertInternalType(
			'array',
			$out
		);
	}

	public function testNewFromIDsMocked() {
		$factoryMock = $this->getMockBuilder( '\MediaWiki\Title\TitleFactory' )
			->disableOriginalConstructor()
			->getMock();

		$factoryMock->expects( $this->once() )->method( 'newFromRow' )->willReturn(
			Title::makeTitle( NS_MAIN, 'Foo' )
		);

		$lbMock = $this->getMockBuilder( '\Wikimedia\Rdbms\ILoadBalancer' )
			->disableOriginalConstructor()
			->getMock();

		$conRef = $this->getMockBuilder( '\Wikimedia\Rdbms\DBConnRef' )
			->disableOriginalConstructor()
			->getMock();

		$conRef->expects( $this->once() )->method( 'select' )->willReturn( [
			1, 'Foo'
		] );

		$lbMock->method( 'getMaintenanceConnectionRef' )->willReturn( $conRef );

		$this->testEnvironment->redefineMediaWikiService( 'TitleFactory', fn() => $factoryMock );
		$this->testEnvironment->redefineMediaWikiService( 'DBLoadBalancer', fn() => $lbMock );

		$instance = new TitleFactory();
		$input = [];

		$out = $instance->newFromIDs( $input );

		$this->assertCount( 1, $out );

		$this->assertInternalType(
			'array',
			$out
		);

		$this->assertInstanceOf( Title::class, $out[0] );
	}

	public function testMakeTitleSafe() {

		$instance = new TitleFactory();

		$this->assertInstanceOf(
			'\Title',
			$instance->makeTitleSafe( NS_MAIN, 'Foo' )
		);
	}

}
