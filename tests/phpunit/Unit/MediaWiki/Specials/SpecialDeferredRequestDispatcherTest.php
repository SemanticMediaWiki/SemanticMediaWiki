<?php

namespace SMW\Tests\MediaWiki\Specials;

use SMW\Tests\Utils\UtilityFactory;
use SMW\MediaWiki\Specials\SpecialDeferredRequestDispatcher;
use SMW\ApplicationFactory;
use Title;

/**
 * @covers \SMW\MediaWiki\Specials\SpecialDeferredRequestDispatcher
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.3
 *
 * @author mwjames
 */
class SpecialDeferredRequestDispatcherTest extends \PHPUnit_Framework_TestCase {

	private $applicationFactory;
	private $stringValidator;

	protected function setUp() {
		parent::setUp();

		$this->applicationFactory = ApplicationFactory::getInstance();

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$store->expects( $this->any() )
			->method( 'getPropertySubjects' )
			->will( $this->returnValue( array() ) );

		$this->applicationFactory->registerObject( 'Store', $store );

		$this->stringValidator = UtilityFactory::getInstance()->newValidatorFactory()->newStringValidator();
	}

	protected function tearDown() {
		$this->applicationFactory->clear();
		parent::tearDown();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\MediaWiki\Specials\SpecialDeferredRequestDispatcher',
			new SpecialDeferredRequestDispatcher()
		);
	}

	public function testGetTargetURL() {

		$this->assertContains(
			':DeferredRequestDispatcher',
			SpecialDeferredRequestDispatcher::getTargetURL()
		);
	}

	public function testGetSessionToken() {

		$this->assertInternalType(
			'string',
			SpecialDeferredRequestDispatcher::getSessionToken( 'Foo' )
		);

		$this->assertNotSame(
			SpecialDeferredRequestDispatcher::getSessionToken( 'Bar' ),
			SpecialDeferredRequestDispatcher::getSessionToken( 'Foo' )
		);
	}

	public function testValidPostAsyncUpdateJob() {

		if ( version_compare( $GLOBALS['wgVersion'], '1.20', '<' ) ) {
			$this->markTestSkipped( "Skipping test because of missing method" );
		}

		$timestamp =  time();

		$parameters = json_encode( array(
			'async-job' => array( 'type' => 'SMW\UpdateJob', 'title' => 'Foo' ),
			'timestamp' => $timestamp,
			'sessionToken' => SpecialDeferredRequestDispatcher::getSessionToken( $timestamp ),
		) );

		$instance = new SpecialDeferredRequestDispatcher();
		$instance->disallowToModifyHttpHeader();

		$instance->getContext()->setRequest(
			new \FauxRequest( array( 'parameters' => $parameters ), true )
		);

		$this->assertTrue(
			$instance->execute( '' )
		);
	}

	public function testValidPostAsyncParserCachePurgeJob() {

		if ( version_compare( $GLOBALS['wgVersion'], '1.20', '<' ) ) {
			$this->markTestSkipped( "Skipping test because of missing method" );
		}

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->getMockForAbstractClass();

		$store->expects( $this->any() )
			->method( 'getPropertySubjects' )
			->will( $this->returnValue( array() ) );

		$this->applicationFactory->registerObject( 'Store', $store );

		$timestamp =  time();

		$parameters = json_encode( array(
			'async-job' => array( 'type' => 'SMW\ParserCachePurgeJob', 'title' => 'Foo' ),
			'timestamp' => $timestamp,
			'sessionToken' => SpecialDeferredRequestDispatcher::getSessionToken( $timestamp ),
			'idlist' => array( 1, 2 )
		) );

		$instance = new SpecialDeferredRequestDispatcher();
		$instance->disallowToModifyHttpHeader();

		$instance->getContext()->setRequest(
			new \FauxRequest( array( 'parameters' => $parameters ), true )
		);

		$this->assertTrue(
			$instance->execute( '' )
		);
	}

	public function testInvalidPostSessionToken() {

		if ( version_compare( $GLOBALS['wgVersion'], '1.20', '<' ) ) {
			$this->markTestSkipped( "Skipping test because of missing method" );
		}

		$timestamp =  time();

		$parameters = json_encode( array(
			'timestamp' => $timestamp,
			'sessionToken' => SpecialDeferredRequestDispatcher::getSessionToken( 'Foo' )
		) );

		$instance = new SpecialDeferredRequestDispatcher();
		$instance->disallowToModifyHttpHeader();

		$instance->getContext()->setRequest(
			new \FauxRequest( array( 'parameters' => $parameters ), true )
		);

		$this->assertNull(
			$instance->execute( '' )
		);
	}

	public function testGetRequestForAsyncJob() {

		if ( version_compare( $GLOBALS['wgVersion'], '1.20', '<' ) ) {
			$this->markTestSkipped( "Skipping test because of missing method" );
		}

		$request = array();

		$instance = new SpecialDeferredRequestDispatcher();
		$instance->disallowToModifyHttpHeader();

		$instance->getContext()->setRequest(
			new \FauxRequest( $request, false )
		);

		$this->assertNull(
			$instance->execute( '' )
		);
	}

}
