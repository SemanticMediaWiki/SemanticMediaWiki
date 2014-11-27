<?php

namespace SMW\Tests;

use SMW\Tests\Utils\UtilityFactory;

use SMW\StoreUpdater;
use SMW\ApplicationFactory;
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

	private $applicationFactory;
	private $semanticDataFactory;

	protected function setUp() {
		parent::setUp();

		$this->applicationFactory = ApplicationFactory::getInstance();
		$this->semanticDataFactory = UtilityFactory::getInstance()->newSemanticDataFactory();

		$settings = array(
			'smwgPageSpecialProperties'       => array(),
			'smwgEnableUpdateJobs'            => false,
			'smwgNamespacesWithSemanticLinks' => array( NS_MAIN => true )
		);

		foreach ( $settings as $key => $value) {
			$this->applicationFactory->getSettings()->set( $key, $value );
		}

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->applicationFactory->registerObject( 'Store', $store );
	}

	protected function tearDown() {
		$this->applicationFactory->clear();

		parent::tearDown();
	}

	public function testCanConstruct() {

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\StoreUpdater',
			new StoreUpdater( $store, $semanticData )
		);
	}

	public function testDoUpdateForDefaultSettings() {

		$semanticData = $this->semanticDataFactory->newEmptySemanticData( __METHOD__ );

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$instance = new StoreUpdater( $store, $semanticData );
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

		$this->applicationFactory->registerObject( 'PageCreator', $pageCreator );

		$instance = new StoreUpdater( $store, $semanticData );
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

		$wikiPage = $this->getMockBuilder( '\WikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$pageCreator = $this->getMockBuilder( '\SMW\MediaWiki\PageCreator' )
			->disableOriginalConstructor()
			->getMock();

		$pageCreator->expects( $this->atLeastOnce() )
			->method( 'createPage' )
			->will( $this->returnValue( $wikiPage ) );

		$this->applicationFactory->registerObject( 'PageCreator', $pageCreator );

		$instance = new StoreUpdater( $store, $semanticData );
		$instance->setUpdateJobsEnabledState( $updateJobStatus );

		$this->assertTrue( $instance->doUpdate() );
	}

	public function testDoUpdateForTitleInUnknownNs() {

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$wikiPage = new DIWikiPage(
			'Foo',
			-32768, // This namespace does not exist
			''
		);

		$semanticData = $this->semanticDataFactory->setSubject( $wikiPage )->newEmptySemanticData();
		$instance = new StoreUpdater( $store, $semanticData );

		$this->assertInternalType( 'boolean', $instance->doUpdate() );
	}

	public function testDoUpdateForSpecialPage() {

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$wikiPage = new DIWikiPage(
			'Foo',
			NS_SPECIAL,
			''
		);

		$semanticData = $this->semanticDataFactory->setSubject( $wikiPage )->newEmptySemanticData();
		$instance = new StoreUpdater( $store, $semanticData );

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
