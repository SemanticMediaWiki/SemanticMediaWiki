<?php

namespace SMW\Tests;

use SMW\Tests\Util\SemanticDataFactory;

use SMW\StoreUpdater;
use SMW\Application;
use SMw\Settings;
use SMW\DIWikiPage;

/**
 * @covers \SMW\StoreUpdater
 *
 * @group SMW
 * @group SMWExtension
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class StoreUpdaterTest  extends \PHPUnit_Framework_TestCase {

	private $application;
	private $semanticDataFactory;

	protected function setUp() {
		parent::setUp();

		$this->application = Application::getInstance();
		$this->semanticDataFactory = new SemanticDataFactory();

		$settings = Settings::newFromArray( array(
			'smwgPageSpecialProperties'       => array(),
			'smwgEnableUpdateJobs'            => false,
			'smwgNamespacesWithSemanticLinks' => array( NS_MAIN => true )
		) );

		$this->application->registerObject( 'Settings', $settings );

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->application->registerObject( 'Store', $store );
	}

	protected function tearDown() {
		$this->application->clear();

		parent::tearDown();
	}

	public function testCanConstruct() {

		$semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\StoreUpdater',
			new StoreUpdater( $semanticData )
		);
	}

	public function testDoUpdateForDefaultSettings() {

		$semanticData = $this->semanticDataFactory->newEmptySemanticData( __METHOD__ );

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->application->registerObject( 'Store', $store );

		$instance = new StoreUpdater( $semanticData );
		$this->assertTrue( $instance->doUpdate() );
	}

	/**
	 * @dataProvider updateJobStatusProvider
	 */
	public function testDoUpdateForValidRevision( $updateJobStatus ) {

		$semanticData = $this->semanticDataFactory->newEmptySemanticData( __METHOD__ );

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->setMethods( array( 'updateData' ) )
			->getMockForAbstractClass();

		$store->expects( $this->once() )
			->method( 'updateData' );

		$this->application->registerObject( 'Store', $store );

		$revision = $this->getMockBuilder( '\Revision' )
			->disableOriginalConstructor()
			->getMock();

		$wikiPage = $this->getMockBuilder( '\WikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$wikiPage->expects( $this->atLeastOnce() )
			->method( 'getRevision' )
			->will( $this->returnValue( $revision ) );

		$pageCreator = $this->getMockBuilder( '\SMW\MediaWiki\PageCreator' )
			->disableOriginalConstructor()
			->getMock();

		$pageCreator->expects( $this->atLeastOnce() )
			->method( 'createPage' )
			->will( $this->returnValue( $wikiPage ) );

		$this->application->registerObject( 'PageCreator', $pageCreator );

		$instance = new StoreUpdater( $semanticData );
		$instance->setUpdateJobsEnabledState( $updateJobStatus );

		$this->assertTrue( $instance->doUpdate() );
	}

	/**
	 * @dataProvider updateJobStatusProvider
	 */
	public function testDoUpdateForNullRevision( $updateJobStatus ) {

		$semanticData = $this->semanticDataFactory->newEmptySemanticData( __METHOD__ );

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->setMethods( array( 'clearData' ) )
			->getMockForAbstractClass();

		$store->expects( $this->once() )
			->method( 'clearData' )
			->with( $this->equalTo( $semanticData->getSubject() ) );

		$this->application->registerObject( 'Store', $store );

		$wikiPage = $this->getMockBuilder( '\WikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$pageCreator = $this->getMockBuilder( '\SMW\MediaWiki\PageCreator' )
			->disableOriginalConstructor()
			->getMock();

		$pageCreator->expects( $this->atLeastOnce() )
			->method( 'createPage' )
			->will( $this->returnValue( $wikiPage ) );

		$this->application->registerObject( 'PageCreator', $pageCreator );

		$instance = new StoreUpdater( $semanticData );
		$instance->setUpdateJobsEnabledState( $updateJobStatus );

		$this->assertTrue( $instance->doUpdate() );
	}

	public function testDoUpdateForTitleInUnknownNs() {

		$wikiPage = new DIWikiPage(
			'Foo',
			-32768, // This namespace does not exist
			''
		);

		$semanticData = $this->semanticDataFactory->setSubject( $wikiPage )->newEmptySemanticData();
		$instance = new StoreUpdater( $semanticData );

		$this->assertInternalType( 'boolean', $instance->doUpdate() );
	}

	public function testDoUpdateForSpecialPage() {

		$wikiPage = new DIWikiPage(
			'Foo',
			NS_SPECIAL,
			''
		);

		$semanticData = $this->semanticDataFactory->setSubject( $wikiPage )->newEmptySemanticData();
		$instance = new StoreUpdater( $semanticData );

		$this->assertFalse( $instance->doUpdate() );
	}

	public function updateJobStatusProvider() {

		$provider = array(
			array( true ),
			array( false )
		);

		return $provider;
	}

}
