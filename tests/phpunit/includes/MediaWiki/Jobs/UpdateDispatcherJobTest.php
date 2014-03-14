<?php

namespace SMW\Test;

use SMW\UpdateDispatcherJob;
use SMW\ExtensionContext;
use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\SerializerFactory;
use SMW\SemanticData;

use Title;

/**
 * @covers \SMW\UpdateDispatcherJob
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
class UpdateDispatcherJobTest extends SemanticMediaWikiTestCase {

	/** @var DIProperty */
	protected $property;

	/** @var DIWikiPage[] */
	protected $subjects;

	/**
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\UpdateDispatcherJob';
	}

	/**
	 * @since 1.9
	 *
	 * @return UpdateDispatcherJob
	 */
	private function newInstance( Title $title = null, $properties = array(), $parameters = array() ) {

		if ( $title === null ) {
			$title = $this->newTitle();
		}

		$mockStore = $this->newMockBuilder()->newObject( 'Store', array(
			'getAllPropertySubjects' => array( $this, 'mockStoreAllPropertySubjectsCallback' ),
			'getPropertySubjects'    => array(),
			'getProperties'          => $properties,
			'getInProperties'        => $properties
		) );

		$settings = $this->newSettings( array(
			'smwgCacheType'        => 'hash',
			'smwgEnableUpdateJobs' => false
		) );

		$context   = new ExtensionContext();

		$container = $context->getDependencyBuilder()->getContainer();
		$container->registerObject( 'Store', $mockStore );
		$container->registerObject( 'Settings', $settings );

		$instance = new UpdateDispatcherJob( $title, $parameters );
		$instance->invokeContext( $context );

		return $instance;
	}

	/**
	 * @since 1.9
	 */
	public function testConstructor() {
		$this->assertInstanceOf( $this->getClass(), $this->newInstance() );
	}

	/**
	 * Just verify that the push method is accessible without inserting any real job
	 *
	 * @since 1.9
	 */
	public function testPush() {
		$this->assertNull( $this->newInstance()->push() );
	}

	/**
	 * @since 1.9
	 */
	public function testRunOnDB() {

		$this->assertTrue(
			$this->newInstance( $this->newTitle( SMW_NS_PROPERTY ) )->disable()->run(),
			'assert that run() always returns true'
		);

		$this->assertTrue(
			$this->newInstance( $this->newTitle( NS_MAIN ) )->disable()->run(),
			'assert that run() always returns true'
		);

	}

	/**
	 * @dataProvider subjectDataProvider
	 *
	 * @since 1.9
	 */
	public function testRunJobOnMockWithOutParameters( $setup, $expected ) {
		$this->runJobTestOnMock( $setup, $expected, array() );
	}

	/**
	 * @dataProvider subjectDataProvider
	 *
	 * @since 1.9.0.2
	 */
	public function testRunJobOnMockWithParameters( $setup, $expected ) {

		$parameters = array(
			'semanticData' => SerializerFactory::serialize(
			new SemanticData( DIWikiPage::newFromTitle( $setup['title'] )
		) ) );

		$this->runJobTestOnMock( $setup, $expected, $parameters );
	}

	/**
	 * @since 1.9
	 */
	protected function runJobTestOnMock( $setup, $expected, $parameters = array() ) {

		// Set-up expected property to be accessible in the mock callback
		$this->property = $setup['property'];

		// Set-up expected "raw" subjects to be returned (plus duplicate)
		$this->subjects = $setup['subjects'];

		$instance = $this->newInstance( $setup['title'], $setup['properties'] );

		// For tests disable distribution of jobs into the "real" JobQueue
		$instance->disable()->run();

		$this->assertJobsAndJobCount( $expected['count'], $instance );

	}

	/**
	 * @since 1.9
	 */
	protected function assertJobsAndJobCount( $count, $instance ) {

		$reflector = $this->newReflector();
		$jobs = $reflector->getProperty( 'jobs' );
		$jobs->setAccessible( true );

		$result = $jobs->getValue( $instance );

		$this->assertInternalType(
			'array',
			$result,
			'asserts that the job result property is of type array'
		);

		$this->assertCount(
			$count,
			$result,
			'asserts the amount of available job entries'
		);

		foreach ( $result as $job ) {
			$this->assertInstanceOf(
				'SMW\UpdateJob',
				$job,
				'asserts that the job instance is of type \SMW\UpdateJob'
			);
		}

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
		return $this->property == $property ? $this->subjects : array();
	}

	/**
	 * @return array
	 */
	public function subjectDataProvider() {

		$provider = array();

		$duplicate = $this->newSubject();
		$subjects = array(
			$duplicate,
			$this->newSubject(),
			$this->newSubject(),
			$duplicate,
			$this->newSubject()
		);

		$count = count( $subjects ) - 1; // eliminate duplicate count
		$title = $this->newTitle( SMW_NS_PROPERTY );
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

		$title = $this->newTitle( NS_MAIN );
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
}
