<?php

namespace SMW\Tests;

use SMW\PropertySpecificationChangeNotifier;
use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\Settings;
use SMW\ApplicationFactory;

/**
 * @covers \SMW\PropertySpecificationChangeNotifier
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class PropertySpecificationChangeNotifierTest extends \PHPUnit_Framework_TestCase {

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

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->applicationFactory->registerObject( 'Store', $store );
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
			'\SMW\PropertySpecificationChangeNotifier',
			new PropertySpecificationChangeNotifier( $store, $semanticData )
		);
	}

	/**
	 * @dataProvider dataItemDataProvider
	 */
	public function testDetectChanges( $storeValues, $dataValues, $propertiesToCompare, $expected ) {

		$this->storeValues = $storeValues;

		$subject = new DIWikiPage( __METHOD__, SMW_NS_PROPERTY );

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
			->setMethods( array( 'getPropertyValues' ) )
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

		$instance = new PropertySpecificationChangeNotifier( $store, $semanticData );
		$instance->setPropertiesToCompare( $propertiesToCompare );
		$instance->compareForListedSpecification();

		$this->assertEquals(
			$expected['diff'],
			$instance->hasDiff()
		);
	}

	public function dataItemDataProvider() {

		// Single
		$subject  = array(
			DIWikiPage::newFromText( __METHOD__ )
		);

		// Multiple
		$subjects = array(
			DIWikiPage::newFromText( __METHOD__ . 'm-0' ),
			DIWikiPage::newFromText( __METHOD__ . 'm-1' ),
			DIWikiPage::newFromText( __METHOD__ . 'm-2' )
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
	// @codingStandardsIgnoreStart phpcs, ignore --sniffs=Generic.CodeAnalysis.UnusedFunctionParameter
	public function mockStorePropertyValuesCallback( $subject, DIProperty $property, $requestoptions = null ) { // @codingStandardsIgnoreEnd
		return $property->getKey() === '_LIST' ? array() : $this->storeValues;
	}

}
