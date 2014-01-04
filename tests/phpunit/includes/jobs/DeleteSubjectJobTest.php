<?php

namespace SMW\Test;

use SMW\ExtensionContext;
use SMW\DeleteSubjectJob;

use Title;

/**
 * @covers \SMW\DeleteSubjectJob
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 *
 * @licence GNU GPL v2+
 * @since 1.9.0.1
 *
 * @author mwjames
 */
class DeleteSubjectJobTest extends SemanticMediaWikiTestCase {

	/* @var boolean */
	protected $deleteSubjectWasCalled = false;

	/* @var Title|null */
	protected $titleToBeDeleted = null;

	/**
	 * @return string|false
	 */
	public function getClass() {
		return 'SMW\DeleteSubjectJob';
	}

	/**
	 * @return DeleteSubjectJob
	 */
	private function newInstance( Title $title = null, $settings = array() ) {

		if ( $title === null ) {
			$title = $this->newTitle();
		}

		$defaultSettings = array(
			'smwgCacheType'        => 'hash',
			'smwgEnableUpdateJobs' => false,
			'smwgDeleteSubjectAsDeferredJob' => false,
			'smwgDeleteSubjectWithAssociatesRefresh' => false
		);

		$settings  = $this->newSettings( array_merge( $defaultSettings, $settings ) );

		$mockStore = $this->newMockBuilder()->newObject( 'Store', array(
			'deleteSubject' => array( $this, 'mockStoreDeleteSubjectCallback' ),
			'getProperties' => array()
		) );

		$context   = new ExtensionContext();

		$container = $context->getDependencyBuilder()->getContainer();
		$container->registerObject( 'Store', $mockStore );
		$container->registerObject( 'Settings', $settings );

		$parameter = array(
			'asDeferredJob' => $settings->get( 'smwgDeleteSubjectAsDeferredJob' ),
			'withRefresh'   => $settings->get( 'smwgDeleteSubjectWithAssociatesRefresh' )
		);

		$instance = new DeleteSubjectJob( $title, $parameter );
		$instance->invokeContext( $context );

		return $instance;
	}

	public function testCanConstructor() {
		$this->assertInstanceOf( $this->getClass(), $this->newInstance() );
	}

	/**
	 * @dataProvider settingsProvider
	 */
	public function testExecuteOnMockStore( $setup, $expected ) {

		$this->titleToBeDeleted = $setup['title'];
		$instance = $this->newInstance( $setup['title'], $setup['settings'] );

		$this->assertTrue( $instance->disable()->execute() );
		$this->assertEquals( $expected['deleteSubjectWasCalled'], $this->deleteSubjectWasCalled );
		$this->assertJobsAndJobCount( $expected['jobCount'], $instance );

		unset( $this->deleteSubjectWasCalled );
		unset( $this->titleToBeDeleted );
	}

	public function assertJobsAndJobCount( $count, $instance ) {

		$reflector = $this->newReflector();
		$jobs = $reflector->getProperty( 'jobs' );
		$jobs->setAccessible( true );

		$result = $jobs->getValue( $instance );

		$this->assertInternalType(
			'array',
			$result,
			'Asserts that the job result property is of type array'
		);

		$this->assertCount(
			$count,
			$result,
			'Asserts the amount of available job entries'
		);

		foreach ( $result as $job ) {
			$this->assertInstanceOf(
				$this->getClass(),
				$job,
				"Asserts that the job instance is of type {$this->getClass()}"
			);

			$this->assertTrue( $job->hasParameter( 'asDeferredJob' ) );
			$this->assertTrue( $job->hasParameter( 'withRefresh' ) );

		}

	}

	/**
	 * @see Store::deleteSubject
	 */
	public function mockStoreDeleteSubjectCallback( Title $title ) {
		$this->deleteSubjectWasCalled = $this->titleToBeDeleted === $title;
	}

	/**
	 * @return array
	 */
	public function settingsProvider() {

		$provider = array();

		#0
		$provider[] = array(
			array(
				'title'    => $this->newTitle( NS_MAIN ),
				'settings' => array(
					'smwgEnableUpdateJobs' => true,
					'smwgDeleteSubjectAsDeferredJob' => false,
					'smwgDeleteSubjectWithAssociatesRefresh' => false
				)
			),
			array(
				'jobCount' => 0,
				'deleteSubjectWasCalled' => true
			)
		);

		#1
		$provider[] = array(
			array(
				'title'    => $this->newTitle( NS_MAIN ),
				'settings' => array(
					'smwgEnableUpdateJobs' => true,
					'smwgDeleteSubjectAsDeferredJob' => true,
					'smwgDeleteSubjectWithAssociatesRefresh' => true
				)
			),
			array(
				'jobCount' => 1,
				'deleteSubjectWasCalled' => false
			)
		);

		#2
		$provider[] = array(
			array(
				'title'    => $this->newTitle( NS_MAIN ),
				'settings' => array(
					'smwgEnableUpdateJobs' => false,
					'smwgDeleteSubjectAsDeferredJob' => true,
					'smwgDeleteSubjectWithAssociatesRefresh' => true
				)
			),
			array(
				'jobCount' => 0,
				'deleteSubjectWasCalled' => true
			)
		);

		#3
		$provider[] = array(
			array(
				'title'    => $this->newTitle( NS_MAIN ),
				'settings' => array(
					'smwgEnableUpdateJobs' => false,
					'smwgDeleteSubjectAsDeferredJob' => true,
					'smwgDeleteSubjectWithAssociatesRefresh' => false
				)
			),
			array(
				'jobCount' => 0,
				'deleteSubjectWasCalled' => true
			)
		);

		return $provider;
	}

}
