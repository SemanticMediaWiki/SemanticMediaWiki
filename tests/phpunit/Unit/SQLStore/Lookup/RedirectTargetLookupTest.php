<?php

namespace SMW\Tests\SQLStore\Lookup;

use SMW\DIWikiPage;
use SMW\SQLStore\Lookup\RedirectTargetLookup;

/**
 * @covers \SMW\SQLStore\Lookup\RedirectTargetLookup
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class RedirectTargetLookupTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$circularReferenceGuard = $this->getMockBuilder( '\SMW\Utils\CircularReferenceGuard' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\SQLStore\Lookup\RedirectTargetLookup',
			new RedirectTargetLookup( $store, $circularReferenceGuard )
		);
	}

	public function testFindRedirectTargetOnValidDataItem() {

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->setMethods( [ 'getRedirectTarget' ] )
			->getMockForAbstractClass();

		$store->expects( $this->atLeastOnce() )
			->method( 'getRedirectTarget' );

		$circularReferenceGuard = $this->getMockBuilder( '\SMW\Utils\CircularReferenceGuard' )
			->disableOriginalConstructor()
			->getMock();

		$circularReferenceGuard->expects( $this->atLeastOnce() )
			->method( 'isCircular' )
			->will( $this->returnValue( false ) );

		$instance = new RedirectTargetLookup(
			$store,
			$circularReferenceGuard
		);

		$instance->findRedirectTarget( DIWikiPage::newFromText( 'Foo' ) );
	}

	public function testFindRedirectTargetOnInvalidDataItem() {

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->setMethods( [ 'getRedirectTarget' ] )
			->getMockForAbstractClass();

		$store->expects( $this->never() )
			->method( 'getRedirectTarget' );

		$circularReferenceGuard = $this->getMockBuilder( '\SMW\Utils\CircularReferenceGuard' )
			->disableOriginalConstructor()
			->getMock();

		$circularReferenceGuard->expects( $this->never() )
			->method( 'isCircular' );

		$instance = new RedirectTargetLookup(
			$store,
			$circularReferenceGuard
		);

		$instance->findRedirectTarget( 'foo' );
	}

	public function testFindRedirectTargetOnSelfReferencedDataItem() {

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->setMethods( [ 'getRedirectTarget' ] )
			->getMockForAbstractClass();

		$store->expects( $this->never() )
			->method( 'getRedirectTarget' );

		$circularReferenceGuard = $this->getMockBuilder( '\SMW\Utils\CircularReferenceGuard' )
			->disableOriginalConstructor()
			->getMock();

		$circularReferenceGuard->expects( $this->atLeastOnce() )
			->method( 'isCircular' )
			->will( $this->returnValue( true ) );

		$instance = new RedirectTargetLookup(
			$store,
			$circularReferenceGuard
		);

		$instance->findRedirectTarget( DIWikiPage::newFromText( 'Foo' ) );
	}

}
