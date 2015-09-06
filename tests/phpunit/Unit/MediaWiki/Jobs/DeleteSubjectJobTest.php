<?php

namespace SMW\Tests\MediaWiki\Jobs;

use SMW\MediaWiki\Jobs\DeleteSubjectJob;
use SMW\DIWikiPage;
use SMW\SemanticData;
use SMW\Settings;
use SMW\ApplicationFactory;

use Title;
use ReflectionClass;

/**
 * @covers \SMW\MediaWiki\Jobs\DeleteSubjectJob
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 1.9.1
 *
 * @author mwjames
 */
class DeleteSubjectJobTest extends \PHPUnit_Framework_TestCase {

	/* @var boolean */
	protected $deleteSubjectWasCalled = false;

	/* @var Title|null */
	protected $titlePlannedToBeDeleted = null;

	public function testCanConstruct() {

		$title = $this->getMockBuilder( 'Title' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'SMW\MediaWiki\Jobs\DeleteSubjectJob',
			new DeleteSubjectJob( $title )
		);
	}

	/**
	 * @dataProvider jobDefinitionProvider
	 */
	public function testExecuteOnMockStore( $parameters, $expected ) {

		$this->titlePlannedToBeDeleted = $parameters['title'];

		$instance = $this->acquireInstance(
			$parameters['title'],
			$parameters['settings']
		);

		$this->assertTrue( $instance->execute() );

		$this->assertEquals(
			$expected['deleteSubjectWasCalled'],
			$this->deleteSubjectWasCalled
		);

		$this->assertJobsAndJobCount( $expected['jobCount'], $instance );

		unset( $this->deleteSubjectWasCalled );
		unset( $this->titlePlannedToBeDeleted );
	}

	public function mockStoreDeleteSubjectCallback( Title $title ) {
		$this->deleteSubjectWasCalled = $this->titlePlannedToBeDeleted === $title;
	}

	public function jobDefinitionProvider() {

		$provider = array();

		#0
		$provider[] = array(
			array(
				'title'    => Title::newFromText( __METHOD__, NS_MAIN ),
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
				'title'    => Title::newFromText( __METHOD__, NS_MAIN ),
				'settings' => array(
					'smwgEnableUpdateJobs' => true,
					'smwgDeleteSubjectAsDeferredJob' => true,
					'smwgDeleteSubjectWithAssociatesRefresh' => true
				)
			),
			array(
				'jobCount' => 1,
				'deleteSubjectWasCalled' => true
			)
		);

		#2
		$provider[] = array(
			array(
				'title'    => Title::newFromText( __METHOD__, NS_MAIN ),
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
				'title'    => Title::newFromText( __METHOD__, NS_MAIN ),
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

	protected function assertJobsAndJobCount( $count, $instance ) {

		$reflector = new ReflectionClass( 'SMW\MediaWiki\Jobs\DeleteSubjectJob' );
		$jobs = $reflector->getProperty( 'jobs' );
		$jobs->setAccessible( true );

		$actualJobs = $jobs->getValue( $instance );

		$this->assertInternalType( 'array', $actualJobs );
		$this->assertCount( $count, $actualJobs );

		foreach ( $actualJobs as $job ) {
			$this->assertEquals( 'SMW\DeleteSubjectJob', $job->getType() );
			$this->assertTrue( $job->hasParameter( 'withAssociates' ) );
			$this->assertTrue( $job->hasParameter( 'asDeferredJob' ) );
			$this->assertTrue( $job->hasParameter( 'semanticData' ) );
		}
	}

	/**
	 * @return DeleteSubjectJob
	 */
	private function acquireInstance( Title $title = null, $settings = array() ) {

		if ( $title === null ) {
			$title = Title::newFromText( __METHOD__ );
		}

		$defaultSettings = array(
			'smwgCacheType'        => 'hash',
			'smwgEnableUpdateJobs' => false,
			'smwgDeleteSubjectAsDeferredJob' => false,
			'smwgDeleteSubjectWithAssociatesRefresh' => false
		);

		$settings = Settings::newFromArray( array_merge( $defaultSettings, $settings ) );

		$semanticData = new SemanticData( DIWikiPage::newFromTitle( $title ) );

		$mockStore = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->setMethods( array( 'deleteSubject', 'getSemanticData', 'getProperties', 'getInProperties' ) )
			->getMockForAbstractClass();

		$mockStore->expects( $this->once() )
			->method( 'deleteSubject' )
			->will( $this->returnCallback( array( $this, 'mockStoreDeleteSubjectCallback' ) ) );

		$mockStore->expects( $this->any() )
			->method( 'getSemanticData' )
			->will( $this->returnValue( $semanticData ) );

		$mockStore->expects( $this->any() )
			->method( 'getProperties' )
			->will( $this->returnValue( array() ) );

		$mockStore->expects( $this->any() )
			->method( 'getInProperties' )
			->will( $this->returnValue( array() ) );

		ApplicationFactory::getInstance()->registerObject( 'Store', $mockStore );
		ApplicationFactory::getInstance()->registerObject( 'Settings', $settings );

		$parameters = array(
			'asDeferredJob'  => $settings->get( 'smwgDeleteSubjectAsDeferredJob' ),
			'withAssociates' => $settings->get( 'smwgDeleteSubjectWithAssociatesRefresh' )
		);

		$instance = new DeleteSubjectJob( $title, $parameters );
		$instance->setJobQueueEnabledState( false );

		return $instance;
	}

}
