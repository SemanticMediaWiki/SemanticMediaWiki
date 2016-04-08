<?php

namespace SMW\Tests;

use SMW\DeferredRequestDispatchManager;
use SMW\DIWikiPage;

/**
 * @covers \SMW\DeferredRequestDispatchManager
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since   2.3
 *
 * @author mwjames
 */
class DeferredRequestDispatchManagerTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$httpRequest = $this->getMockBuilder( '\Onoi\HttpRequest\SocketRequest' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\DeferredRequestDispatchManager',
			new DeferredRequestDispatchManager( $httpRequest )
		);
	}

	/**
	 * @dataProvider dispatchableJobProvider
	 */
	public function testDispatchJobFor( $type, $deferredJobRequestState, $parameters = array() ) {

		$httpRequest = $this->getMockBuilder( '\Onoi\HttpRequest\SocketRequest' )
			->disableOriginalConstructor()
			->getMock();

		$httpRequest->expects( $this->any() )
			->method( 'ping' )
			->will( $this->returnValue( true ) );

		$instance = new DeferredRequestDispatchManager( $httpRequest );
		$instance->reset();
		$instance->setEnabledHttpDeferredJobRequestState( $deferredJobRequestState );

		$this->assertTrue(
			$instance->dispatchJobRequestFor( $type, DIWikiPage::newFromText( __METHOD__ )->getTitle(), $parameters )
		);
	}

	public function testDispatchParserCachePurgeJob() {

		$httpRequest = $this->getMockBuilder( '\Onoi\HttpRequest\SocketRequest' )
			->disableOriginalConstructor()
			->getMock();

		$httpRequest->expects( $this->once() )
			->method( 'ping' )
			->will( $this->returnValue( true ) );

		$instance = new DeferredRequestDispatchManager( $httpRequest );
		$instance->reset();
		$instance->setEnabledHttpDeferredJobRequestState( true );

		$parameters = array( 'idlist' => '1|2' );
		$title = DIWikiPage::newFromText( __METHOD__ )->getTitle();

		$this->assertTrue(
			$instance->dispatchParserCachePurgeJobFor( $title, $parameters )
		);
	}

	/**
	 * @dataProvider preliminaryCheckProvider
	 */
	public function testPreliminaryCheckForType( $type, $parameters = array() ) {

		$httpRequest = $this->getMockBuilder( '\Onoi\HttpRequest\SocketRequest' )
			->disableOriginalConstructor()
			->getMock();

		$httpRequest->expects( $this->any() )
			->method( 'ping' )
			->will( $this->returnValue( true ) );

		$instance = new DeferredRequestDispatchManager( $httpRequest );
		$instance->reset();

		$this->assertNull(
			$instance->dispatchJobRequestFor( $type, DIWikiPage::newFromText( __METHOD__ )->getTitle(), $parameters )
		);
	}

	public function dispatchableJobProvider() {

		$provider[] = array(
			'SMW\UpdateJob',
			false
		);

		$provider[] = array(
			'SMW\UpdateJob',
			true
		);

		$provider[] = array(
			'SMW\ParserCachePurgeJob',
			false,
			array( 'idlist' => '1|2' )
		);

		$provider[] = array(
			'SMW\ParserCachePurgeJob',
			true,
			array( 'idlist' => '1|2' )
		);

		return $provider;
	}

	public function preliminaryCheckProvider() {

		$provider[] = array(
			'SMW\ParserCachePurgeJob',
			array()
		);


		$provider[] = array(
			'UnknownJob'
		);

		return $provider;
	}

}
