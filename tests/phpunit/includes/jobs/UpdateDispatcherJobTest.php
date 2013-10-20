<?php

namespace SMW\Test;

use SMW\UpdateDispatcherJob;
use SMW\BaseContext;
use SMW\DIProperty;

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
	private function newInstance( Title $title = null ) {

		if ( $title === null ) {
			$title = $this->newTitle();
		}

		$mockStore = $this->newMockBuilder()->newObject( 'Store', array(
			'getAllPropertySubjects' => array( $this, 'mockStoreAllPropertySubjectsCallback' ),
			'getPropertySubjects'    => array()
		) );

		$settings = $this->newSettings( array(
			'smwgCacheType'        => 'hash',
			'smwgEnableUpdateJobs' => false
		) );

		$context   = new BaseContext();

		$container = $context->getDependencyBuilder()->getContainer();
		$container->registerObject( 'Store', $mockStore );
		$container->registerObject( 'Settings', $settings );

		$instance = new UpdateDispatcherJob( $title );
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
	}

	/**
	 * @dataProvider subjectDataProvider
	 *
	 * @since 1.9
	 */
	public function testRunOnMockObjects( $setup, $expected ) {

		// Set-up expected property to be accessible in the mock callback
		$this->property = DIProperty::newFromUserLabel( $setup['title']->getText() );

		// Set-up expected "raw" subjects to be returned (plus duplicate)
		$this->subjects = $setup['subjects'];

		$instance = $this->newInstance( $setup['title'] );

		// For tests disable distribution of jobs into the "real" JobQueue
		$instance->disable()->run();

		// Get access to protected jobs property
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
			$expected['count'],
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

		$title = $this->newTitle( SMW_NS_PROPERTY );

		$duplicate = $this->newSubject();
		$subjects = array(
			$duplicate,
			$this->newSubject(),
			$this->newSubject(),
			$duplicate,
			$this->newSubject()
		);

		$count = count( $subjects ) - 1; // eliminate duplicate count

		$provider[] = array(
			array(
				'title'    => $title,
				'subjects' => $subjects
			),
			array(
				'count' => $count
			)
		);

		return $provider;
	}
}
