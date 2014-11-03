<?php

namespace SMW\Tests;

use SMW\PropertyTypeDiffFinder;
use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\Settings;
use SMW\ApplicationFactory;

use Title;

/**
 * @covers \SMW\PropertyTypeDiffFinder
 *
 *
 * @group SMW
 * @group SMWExtension
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class PropertyTypeDiffFinderTest extends \PHPUnit_Framework_TestCase {

	/** @var DIWikiPage[] */
	protected $storeValues;

	private $applicationFactory;

	protected function setUp() {
		parent::setUp();

		$this->applicationFactory = ApplicationFactory::getInstance();

		$settings = Settings::newFromArray( array(
			'smwgDeclarationProperties' => array( '_PVAL' ),
			'smwgCacheType'  => 'hash',
			'smwgEnableUpdateJobs' => false
		) );

		$this->applicationFactory->registerObject( 'Settings', $settings );
	}

	protected function tearDown() {
		$this->applicationFactory->clear();

		parent::tearDown();
	}

	public function testCanConstruct() {

		$store = $this->getMockBuilder( 'SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\PropertyTypeDiffFinder',
			new PropertyTypeDiffFinder( $store, $semanticData )
		);
	}

	/**
	 * @dataProvider dataItemDataProvider
	 */
	public function testDetectChanges( $storeValues, $dataValues, $settings, $expected ) {

		$this->storeValues = $storeValues;

		$subject = DIWikiPage::newFromTitle( Title::newFromText( __METHOD__, SMW_NS_PROPERTY ) );

		$settings = Settings::newFromArray( array(
			'smwgDeclarationProperties' => $settings
		) );

		$this->applicationFactory->registerObject( 'Settings', $settings );

		$updateDispatcherJob = $this->getMockBuilder( 'SMW\MediaWiki\Jobs\UpdateDispatcherJob' )
			->disableOriginalConstructor()
			->getMock();

		$expectedToRun = $expected['job'] ? $this->once() : $this->never();

		$updateDispatcherJob->expects( $expectedToRun )
			->method( 'run' )
			->will( $this->returnValue( $subject ) );

		$jobFactory = $this->getMockBuilder( 'SMW\MediaWiki\Jobs\JobFactory' )
			->disableOriginalConstructor()
			->getMock();

		$jobFactory->expects( $this->any() )
			->method( 'newUpdateDispatcherJob' )
			->will( $this->returnValue( $updateDispatcherJob ) );

		$this->applicationFactory->registerObject( 'JobFactory', $jobFactory );

		$store = $this->getMockBuilder( 'SMW\Store' )
			->disableOriginalConstructor()
			->setMethods( array(
				'getPropertyValues' ) )
			->getMockForAbstractClass();

		$store->expects( $this->atLeastOnce() )
			->method( 'getPropertyValues' )
			->will( $this->returnCallback( array( $this, 'mockStorePropertyValuesCallback' ) ) );

		$semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$semanticData->expects( $this->atLeastOnce() )
			->method( 'getSubject' )
			->will( $this->returnValue( $subject ) );

		$semanticData->expects( $this->atLeastOnce() )
			->method( 'getPropertyValues' )
			->will( $this->returnValue( $dataValues ) );

		$instance = new PropertyTypeDiffFinder( $store, $semanticData );
		$instance->findDiff();

		$this->assertEquals(
			$subject->getTitle(),
			$instance->getTitle()
		);

		$this->assertEquals(
			$expected['diff'],
			$instance->hasDiff()
		);
	}

	public function dataItemDataProvider() {

		// Single
		$subject  = array(
			DIWikiPage::newFromTitle( Title::newFromText( __METHOD__ ) )
		);

		// Multiple
		$subjects = array(
			DIWikiPage::newFromTitle( Title::newFromText( __METHOD__ . 'm-0' ) ),
			DIWikiPage::newFromTitle( Title::newFromText( __METHOD__ . 'm-1' ) ),
			DIWikiPage::newFromTitle( Title::newFromText( __METHOD__ . 'm-2' ) )
		);

		return array(
			//  $storeValues, $dataValues, $settings,               $expected
			array( $subjects, array(),   array( '_PVAL', '_LIST' ), array( 'diff' => true,  'job' => true ) ),
			array( array(),   $subjects, array( '_PVAL', '_LIST' ), array( 'diff' => true,  'job' => true ) ),
			array( $subject,  $subjects, array( '_PVAL', '_LIST' ), array( 'diff' => true,  'job' => true ) ),
			array( $subject,  array(),   array( '_PVAL', '_LIST' ), array( 'diff' => true,  'job' => true ) ),
			array( $subject,  array(),   array( '_PVAL'          ), array( 'diff' => true,  'job' => true ) ),
			array( $subjects, $subjects, array( '_PVAL'          ), array( 'diff' => false, 'job' => false ) ),
			array( $subject,  $subject,  array( '_PVAL'          ), array( 'diff' => false, 'job' => false ) ),
			array( $subjects, $subjects, array( '_PVAL', '_LIST' ), array( 'diff' => true,  'job' => true ) ),
			array( $subject,  $subject,  array( '_PVAL', '_LIST' ), array( 'diff' => true,  'job' => true ) )
		);
	}

	/**
	 * Returns an array of SMWDataItem and simulates an alternating
	 * existencance of return values ('_LIST')
	 *
	 * @see Store::getPropertyValues
	 *
	 * @return SMWDataItem[]
	 */
	public function mockStorePropertyValuesCallback( $subject, DIProperty $property, $requestoptions = null ) {
		return $property->getKey() === '_LIST' ? array() : $this->storeValues;
	}

}
