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
 * @since 1.9
 *
 * @author mwjames
 */
class DeleteSubjectJobTest extends SemanticMediaWikiTestCase {

	protected $deleteSubjectWasCalled = false;
	protected $titleToBeDeleted = null;

	/**
	 * @return string|false
	 */
	public function getClass() {
		return 'SMW\DeleteSubjectJob';
	}

	/**
	 * @since 1.9
	 *
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
			'deleteSubject' => array( $this, 'mockStoreDeleteSubjectCallback' )
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

	/**
	 * @since 1.9
	 */
	public function testCanConstructor() {
		$this->assertInstanceOf( $this->getClass(), $this->newInstance() );
	}

	/**
	 * @dataProvider settingsProvider
	 *
	 * @since 1.9
	 */
	public function testExecuteOnMockStore( $setup, $expected ) {

		$this->titleToBeDeleted = $setup['title'];
		$instance = $this->newInstance( $setup['title'], $setup['settings'] );

		$this->assertTrue( $instance->disable()->execute() );
		$this->assertEquals( $expected['deleteSubjectWasCalled'], $this->deleteSubjectWasCalled );
		$this->assertJobsAndJobCount( $expected['count'], $instance );

		unset( $this->deleteSubjectWasCalled );
		unset( $this->titleToBeDeleted );
	}

	/**
	 * @since 1.9
	 */
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
				'count' => 0,
				'deleteSubjectWasCalled' => true
			)
		);

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
				'count' => 1,
				'deleteSubjectWasCalled' => false
			)
		);

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
				'count' => 0,
				'deleteSubjectWasCalled' => true
			)
		);

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
				'count' => 0,
				'deleteSubjectWasCalled' => true
			)
		);

		return $provider;
	}

}
