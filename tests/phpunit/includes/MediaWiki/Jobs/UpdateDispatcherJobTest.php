<?php

namespace SMW\Tests\MediaWiki\Jobs;

use SMW\MediaWiki\Jobs\UpdateDispatcherJob;
use SMW\ExtensionContext;
use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\SerializerFactory;
use SMW\SemanticData;
use SMW\Settings;

use Title;
use ReflectionClass;

/**
 * @covers \SMW\MediaWiki\Jobs\UpdateDispatcherJob
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class UpdateDispatcherJobTest extends \PHPUnit_Framework_TestCase {

	/** @var DIProperty */
	protected $expectedProperty;

	/** @var DIWikiPage[] */
	protected $expectedSubjects;

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
		$this->assertNull( $this->acquireInstance()->pushToJobQueue() );
	}

	public function testJobRunOnMainNamespace() {

		$title = Title::newFromText( __METHOD__, NS_MAIN );

		$this->assertTrue(
			$this->acquireInstance( $title )->run()
		);
	}

	public function testJobRunOnPropertyNamespace() {

		$title = Title::newFromText( __METHOD__, SMW_NS_PROPERTY );

		$this->assertTrue(
			$this->acquireInstance( $title )->run()
		);
	}

	/**
	 * @dataProvider subjectDataProvider
	 */
	public function testRunJobOnMockWithOutParameters( $setup, $expected ) {

		$this->expectedProperty = $setup['property'];
		$this->expectedSubjects = $setup['subjects'];

		$instance = $this->acquireInstance(
			$setup['title'],
			$setup['properties']
		);

		$instance->run();
		$this->assertJobsAndJobCount( $expected['count'], $instance );
	}

	/**
	 * @dataProvider subjectDataProvider
	 */
	public function testRunJobOnMockWithParameters( $setup, $expected ) {

		$semanticData = SerializerFactory::serialize(
			new SemanticData( DIWikiPage::newFromTitle( $setup['title'] )
		) );

		$additionalJobQueueParameters = array(
			'semanticData' => $semanticData
		);

		$this->expectedProperty = $setup['property'];
		$this->expectedSubjects = $setup['subjects'];

		$instance = $this->acquireInstance(
			$setup['title'],
			$setup['properties'],
			$additionalJobQueueParameters
		);

		$instance->run();
		$this->assertJobsAndJobCount( $expected['count'], $instance );
	}

	protected function assertJobsAndJobCount( $count, $instance ) {

		$reflector = new ReflectionClass( '\SMW\MediaWiki\Jobs\UpdateDispatcherJob' );
		$jobs = $reflector->getProperty( 'jobs' );
		$jobs->setAccessible( true );

		$result = $jobs->getValue( $instance );

		$this->assertInternalType( 'array', $result );
		$this->assertCount( $count, $result );

		foreach ( $result as $job ) {
			$this->assertEquals( 'SMW\UpdateJob', $job->getType() );
		}
	}

	/**
	 * @return array
	 */
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
	 * @return UpdateDispatcherJob
	 */
	private function acquireInstance( Title $title = null, $properties = array(), $parameters = array() ) {

		if ( $title === null ) {
			$title = Title::newFromText( __METHOD__ );
		}

		$mockStore = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->setMethods( array(
				'getAllPropertySubjects',
				'getProperties',
				'getInProperties',
				'getPropertySubjects' ) )
			->getMockForAbstractClass();

		$mockStore->expects( $this->any() )
			->method( 'getAllPropertySubjects' )
			->will( $this->returnCallback( array( $this, 'mockStoreAllPropertySubjectsCallback' ) ) );

		$mockStore->expects( $this->any() )
			->method( 'getProperties' )
			->will( $this->returnValue( $properties ) );

		$mockStore->expects( $this->any() )
			->method( 'getInProperties' )
			->will( $this->returnValue( $properties ) );

		$mockStore->expects( $this->any() )
			->method( 'getPropertySubjects' )
			->will( $this->returnValue( array() ) );

		$settings = Settings::newFromArray( array(
			'smwgCacheType'        => 'hash',
			'smwgEnableUpdateJobs' => false
		) );

		$context   = new ExtensionContext();

		$container = $context->getDependencyBuilder()->getContainer();
		$container->registerObject( 'Store', $mockStore );
		$container->registerObject( 'Settings', $settings );

		$instance = new UpdateDispatcherJob( $title, $parameters );
		$instance->invokeContext( $context );
		$instance->setJobQueueEnabledState( false );

		return $instance;
	}

	/**
	 * Returns an array of DIWikiPage objects if the expected property
	 * and the argument property are identical
	 *
	 * @see Store::getAllPropertySubjects
	 *
	 * @return DIWikiPage[]
	 */
	public function mockStoreAllPropertySubjectsCallback( DIProperty $property, $requestoptions = null ) {
		return $this->expectedProperty == $property ? $this->expectedSubjects : array();
	}

	protected function makeSubjectFromText( $text ) {
		return DIWikiPage::newFromTitle( Title::newFromText( $text ) );
	}

}
