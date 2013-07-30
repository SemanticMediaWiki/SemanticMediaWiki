<?php

namespace SMW\Test;

use SMW\PropertySubjectsUpdateDispatcherJob;
use SMW\DIProperty;

use Title;

/**
 * Tests for the PropertySubjectsUpdateDispatcherJob class
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * @covers \SMW\PropertySubjectsUpdateDispatcherJob
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 */
class PropertySubjectsUpdateDispatcherJobTest extends SemanticMediaWikiTestCase {

	/** @var DIProperty */
	protected $property;

	/** @var DIWikiPage[] */
	protected $subjects;

	/**
	 * Returns the name of the class to be tested
	 *
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\PropertySubjectsUpdateDispatcherJob';
	}

	/**
	 * Helper method that returns a PropertySubjectsUpdateDispatcherJob object
	 *
	 * @since 1.9
	 *
	 * @param Title|null $title
	 *
	 * @return PropertySubjectsUpdateDispatcherJob
	 */
	private function getInstance( Title $title = null ) {
		return new PropertySubjectsUpdateDispatcherJob( $title === null ? $this->getTitle() : $title );
	}

	/**
	 * @test PropertySubjectsUpdateDispatcherJob::__construct
	 *
	 * @since 1.9
	 */
	public function testConstructor() {
		$this->assertInstanceOf( $this->getClass(), $this->getInstance() );
	}

	/**
	 * @test PropertySubjectsUpdateDispatcherJob::push
	 *
	 * Just verify that the push method is accessible
	 * without inserting any real job
	 *
	 * @since 1.9
	 */
	public function testPush() {
		$this->assertNull( $this->getInstance()->push() );
	}

	/**
	 * @test PropertySubjectsUpdateDispatcherJob::run
	 *
	 * @since 1.9
	 */
	public function testRun() {

		$title = $this->getTitle( SMW_NS_PROPERTY );

		// Set-up expected property, accessible in the mock callback
		$this->property = DIProperty::newFromUserLabel( $title->getText() );

		// Set-up expected "raw" subjects to be returned (plus duplicate)
		$duplicate = $this->newSubject();
		$this->subjects = array(
			$duplicate,
			$this->newSubject(),
			$this->newSubject(),
			$duplicate,
			$this->newSubject()
		);
		$count = count( $this->subjects ) - 1; // eliminate duplicate count

		$mockStore = $this->newMockObject( array(
			'getAllPropertySubjects' => array( $this, 'mockStoreAllPropertySubjectsCallback' ),
			'getPropertySubjects'    => array()
		) )->getMockStore();

		$instance = $this->getInstance( $title );
		$instance->setStore( $mockStore );

		// Disable dispatch jobs to avoid test
		// jobs being inserted
		$instance->disable()->run();

		// Get access to protected jobs property
		$reflector = $this->newReflector();
		$jobs = $reflector->getProperty( 'jobs' );
		$jobs->setAccessible( true );

		$result = $jobs->getValue( $instance );

		$this->assertInternalType( 'array', $result );
		$this->assertCount( $count, $result );

		foreach ( $result as $job ) {
			$this->assertInstanceOf( 'SMW\UpdateJob', $job );
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

}
