<?php

namespace SMW\Tests\MediaWiki\Jobs;

use SMW\ApplicationFactory;
use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\MediaWiki\Jobs\UpdateDispatcherJob;
use SMW\SemanticData;
use Title;
use SMW\Tests\TestEnvironment;

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
	private $semanticDataSerializer;
	private $testEnvironment;

	protected function setUp() {
		parent::setUp();

		$this->semanticDataSerializer = ApplicationFactory::getInstance()->newSerializerFactory()->newSemanticDataSerializer();

		$this->testEnvironment = new TestEnvironment( array(
			'smwgCacheType'        => 'hash',
			'smwgEnableUpdateJobs' => false
		) );

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->testEnvironment->registerObject( 'Store', $store );
	}

	protected function tearDown() {
		$this->testEnvironment->tearDown();
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
		$instance->isEnabledJobQueue( false );

		$this->assertNull( $instance->pushToJobQueue() );
	}

	public function testChunkedJobWithListOnValidMembers() {

		$title = $this->getMockBuilder( 'Title' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new UpdateDispatcherJob( $title, array(
			'job-list' => array(
				'Foo#0#' => true,
				'Bar#102#'
			)
		) );

		$instance->isEnabledJobQueue( false );
		$instance->run();

		$this->assertEquals(
			2,
			$instance->getJobCount()
		);
	}

	public function testChunkedJobWithListOnInvalidMembers() {

		$title = $this->getMockBuilder( 'Title' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new UpdateDispatcherJob( $title, array(
			'job-list' => array(
				'|nulltitle#0#' => true,
				'deserlizeerror#0' => true
			)
		) );

		$instance->isEnabledJobQueue( false );
		$instance->run();

		$this->assertEquals(
			0,
			$instance->getJobCount()
		);
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

		$this->testEnvironment->registerObject( 'Store', $store );

		$instance = new UpdateDispatcherJob( $title, array() );
		$instance->isEnabledJobQueue( false );

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

		$this->testEnvironment->registerObject( 'Store', $store );

		$instance = new UpdateDispatcherJob( $title, array() );
		$instance->isEnabledJobQueue( false );

		$this->assertTrue( $instance->run() );
	}

	public function testJobRunOnRestrictedPool() {

		$title = Title::newFromText( __METHOD__ );
		$subject = DIWikiPage::newFromText( 'Foo' );

		$semanticData = new SemanticData( $subject );
		$semanticData->addPropertyObjectValue( new DIProperty( '42' ), $subject );

		$parameters = array(
			'semanticData' => $this->semanticDataSerializer->serialize( $semanticData ),
			UpdateDispatcherJob::RESTRICTED_DISPATCH_POOL => true
		);

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->setMethods( array(
				'getAllPropertySubjects',
				) )
			->getMockForAbstractClass();

		$store->expects( $this->once() )
			->method( 'getAllPropertySubjects' )
			->will( $this->returnValue( array() ) );

		$this->testEnvironment->registerObject( 'Store', $store );

		$instance = new UpdateDispatcherJob( $title, $parameters );
		$instance->isEnabledJobQueue( false );

		$this->assertTrue(
			$instance->run()
		);
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

		$this->testEnvironment->registerObject( 'Store', $store );

		$instance = new UpdateDispatcherJob( $setup['title'], array() );
		$instance->isEnabledJobQueue( false );
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

		$semanticData = new SemanticData(
			DIWikiPage::newFromTitle( $setup['title'] )
		);

		$parameters = array(
			'semanticData' => $this->semanticDataSerializer->serialize( $semanticData )
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

		$this->testEnvironment->registerObject( 'Store', $store );

		$instance = new UpdateDispatcherJob( $setup['title'], $parameters );
		$instance->isEnabledJobQueue( false );
		$instance->run();

		$this->assertEquals(
			$expected['count'],
			$instance->getJobCount()
		);
	}

	public function subjectDataProvider() {

		$provider = array();

		$duplicate = DIWikiPage::newFromText( 'Foo' );

		$subjects = array(
			$duplicate,
			DIWikiPage::newFromText( 'Bar' ),
			DIWikiPage::newFromText( 'Baz' ),
			$duplicate,
			DIWikiPage::newFromText( 'Yon' ),
			DIWikiPage::newFromText( 'Yon' ),
			DIWikiPage::newFromText( __METHOD__, SMW_NS_PROPERTY )
		);

		$count = count( $subjects ) - 1; // eliminate duplicate count
		$title =  Title::newFromText( __METHOD__, SMW_NS_PROPERTY );
		$property = DIProperty::newFromUserLabel( $title->getText() );

		#0
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

		#1
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

}
