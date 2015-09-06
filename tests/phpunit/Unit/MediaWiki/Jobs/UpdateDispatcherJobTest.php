<?php

namespace SMW\Tests\MediaWiki\Jobs;

use SMW\MediaWiki\Jobs\UpdateDispatcherJob;
use SMW\ApplicationFactory;
use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\SemanticData;
use SMW\Settings;

use Title;

/**
 * @covers \SMW\MediaWiki\Jobs\UpdateDispatcherJob
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class UpdateDispatcherJobTest extends \PHPUnit_Framework_TestCase {

	protected $expectedProperty;
	protected $expectedSubjects;

	private $applicationFactory;

	protected function setUp() {
		parent::setUp();

		$this->applicationFactory = ApplicationFactory::getInstance();

		$settings = Settings::newFromArray( array(
			'smwgCacheType'        => 'hash',
			'smwgEnableUpdateJobs' => false
		) );

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->applicationFactory->registerObject( 'Store', $store );
		$this->applicationFactory->registerObject( 'Settings', $settings );
	}

	protected function tearDown() {
		$this->applicationFactory->clear();

		parent::tearDown();
	}

	public function testCanConstruct() {

		$title = $this->getMockBuilder( 'Title' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'SMW\MediaWiki\Jobs\UpdateDispatcherJob',
			new UpdateDispatcherJob( $title )
		);
	}

	public function testPushToJobQueue() {

		$title = $this->getMockBuilder( 'Title' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new UpdateDispatcherJob( $title, array() );
		$instance->setJobQueueEnabledState( false );

		$this->assertNull( $instance->pushToJobQueue() );
	}

	public function testJobRunOnMainNamespace() {

		$title = Title::newFromText( __METHOD__, NS_MAIN );

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->setMethods( array(
				'getProperties',
				'getInProperties' ) )
			->getMockForAbstractClass();

		$store->expects( $this->any() )
			->method( 'getProperties' )
			->will( $this->returnValue( array() ) );

		$store->expects( $this->any() )
			->method( 'getInProperties' )
			->will( $this->returnValue( array() ) );

		$this->applicationFactory->registerObject( 'Store', $store );

		$instance = new UpdateDispatcherJob( $title, array() );
		$instance->setJobQueueEnabledState( false );

		$this->assertTrue( $instance->run() );
	}

	public function testJobRunOnPropertyNamespace() {

		$title = Title::newFromText( __METHOD__, SMW_NS_PROPERTY );

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->setMethods( array(
				'getProperties',
				'getInProperties',
				'getAllPropertySubjects',
				'getPropertySubjects' ) )
			->getMockForAbstractClass();

		$store->expects( $this->any() )
			->method( 'getProperties' )
			->will( $this->returnValue( array() ) );

		$store->expects( $this->any() )
			->method( 'getInProperties' )
			->will( $this->returnValue( array() ) );

		$store->expects( $this->any() )
			->method( 'getAllPropertySubjects' )
			->will( $this->returnValue( array() ) );

		$store->expects( $this->any() )
			->method( 'getPropertySubjects' )
			->will( $this->returnValue( array() ) );

		$this->applicationFactory->registerObject( 'Store', $store );

		$instance = new UpdateDispatcherJob( $title, array() );
		$instance->setJobQueueEnabledState( false );

		$this->assertTrue( $instance->run() );
	}

	/**
	 * @dataProvider subjectDataProvider
	 */
	public function testRunJobOnMockWithOutParameters( $setup, $expected ) {

		$this->expectedProperty = $setup['property'];
		$this->expectedSubjects = $setup['subjects'];

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->setMethods( array(
				'getAllPropertySubjects',
				'getProperties',
				'getInProperties',
				'getPropertySubjects' ) )
			->getMockForAbstractClass();

		$store->expects( $this->any() )
			->method( 'getAllPropertySubjects' )
			->will( $this->returnCallback( array( $this, 'mockStoreAllPropertySubjectsCallback' ) ) );

		$store->expects( $this->any() )
			->method( 'getProperties' )
			->will( $this->returnValue( $setup['properties'] ) );

		$store->expects( $this->any() )
			->method( 'getInProperties' )
			->will( $this->returnValue( $setup['properties'] ) );

		$store->expects( $this->any() )
			->method( 'getPropertySubjects' )
			->will( $this->returnValue( array() ) );

		$this->applicationFactory->registerObject( 'Store', $store );

		$instance = new UpdateDispatcherJob( $setup['title'], array() );
		$instance->setJobQueueEnabledState( false );
		$instance->run();

		$this->assertEquals(
			$expected['count'],
			$instance->getJobCount()
		);
	}

	/**
	 * @dataProvider subjectDataProvider
	 */
	public function testRunJobOnMockWithParameters( $setup, $expected ) {

		$semanticData = $this->applicationFactory->newSerializerFactory()->newSemanticDataSerializer()->serialize(
			new SemanticData( DIWikiPage::newFromTitle( $setup['title'] )
		) );

		$additionalJobQueueParameters = array(
			'semanticData' => $semanticData
		);

		$this->expectedProperty = $setup['property'];
		$this->expectedSubjects = $setup['subjects'];

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->setMethods( array(
				'getAllPropertySubjects',
				'getProperties',
				'getInProperties',
				'getPropertySubjects' ) )
			->getMockForAbstractClass();

		$store->expects( $this->any() )
			->method( 'getAllPropertySubjects' )
			->will( $this->returnCallback( array( $this, 'mockStoreAllPropertySubjectsCallback' ) ) );

		$store->expects( $this->any() )
			->method( 'getProperties' )
			->will( $this->returnValue( $setup['properties'] ) );

		$store->expects( $this->any() )
			->method( 'getInProperties' )
			->will( $this->returnValue( $setup['properties'] ) );

		$store->expects( $this->any() )
			->method( 'getPropertySubjects' )
			->will( $this->returnValue( array() ) );

		$this->applicationFactory->registerObject( 'Store', $store );

		$instance = new UpdateDispatcherJob( $setup['title'], $additionalJobQueueParameters );
		$instance->setJobQueueEnabledState( false );
		$instance->run();

		$this->assertEquals(
			$expected['count'],
			$instance->getJobCount()
		);
	}

	public function subjectDataProvider() {

		$provider = array();

		$duplicate = $this->makeSubjectFromText( 'Foo' );

		$subjects = array(
			$duplicate,
			$this->makeSubjectFromText( 'Bar' ),
			$this->makeSubjectFromText( 'Baz' ),
			$duplicate,
			$this->makeSubjectFromText( 'Yon' )
		);

		$count = count( $subjects ) - 1; // eliminate duplicate count
		$title =  Title::newFromText( __METHOD__, SMW_NS_PROPERTY );
		$property = DIProperty::newFromUserLabel( $title->getText() );

		$provider[] = array(
			array(
				'title'      => $title,
				'subjects'   => $subjects,
				'property'   => $property,
				'properties' => array()
			),
			array(
				'count' => $count
			)
		);

		$title = Title::newFromText( __METHOD__, NS_MAIN );
		$property = DIProperty::newFromUserLabel( $title->getText() );

		$provider[] = array(
			array(
				'title'      => $title,
				'subjects'   => array( DIWikiPage::newFromTitle( $title ) ),
				'property'   => $property,
				'properties' => array( $property )
			),
			array(
				'count' => 1
			)
		);

		return $provider;
	}

	/**
	 * Returns an array of DIWikiPage objects if the expected property
	 * and the argument property are identical
	 *
	 * @see Store::getAllPropertySubjects
	 *
	 * @return DIWikiPage[]
	 */
	public function mockStoreAllPropertySubjectsCallback( DIProperty $property ) {
		return $this->expectedProperty == $property ? $this->expectedSubjects : array();
	}

	protected function makeSubjectFromText( $text ) {
		return DIWikiPage::newFromTitle( Title::newFromText( $text ) );
	}

}
