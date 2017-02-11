<?php

namespace SMW\Tests;

use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\PropertySpecificationChangeNotifier;

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

	protected $mockedStoreValues;
	private $testEnvironment;

	protected function setUp() {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment(
			array(
				'smwgDeclarationProperties' => array( '_PVAL' ),
				'smwgCacheType'  => 'hash',
				'smwgEnableUpdateJobs' => false
			)
		);

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

		$store = $this->getMockBuilder( 'SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->assertInstanceOf(
			'\SMW\PropertySpecificationChangeNotifier',
			new PropertySpecificationChangeNotifier( $store )
		);
	}

	/**
	 * @dataProvider dataItemDataProvider
	 */
	public function testDetectChanges( $mockedStoreValues, $dataValues, $propertiesToCompare, $expected ) {

		$this->mockedStoreValues = $mockedStoreValues;

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
			->method( 'newByType' )
			->will( $this->returnValue( $updateDispatcherJob ) );

		$this->testEnvironment->registerObject( 'JobFactory', $jobFactory );

		$store = $this->getMockBuilder( 'SMW\Store' )
			->disableOriginalConstructor()
			->setMethods( array( 'getPropertyValues' ) )
			->getMockForAbstractClass();

		$store->expects( $this->atLeastOnce() )
			->method( 'getPropertyValues' )
			->will( $this->returnCallback( array( $this, 'doComparePropertyValuesOnCallback' ) ) );

		$semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$semanticData->expects( $this->atLeastOnce() )
			->method( 'getSubject' )
			->will( $this->returnValue( $subject ) );

		$semanticData->expects( $this->atLeastOnce() )
			->method( 'getPropertyValues' )
			->will( $this->returnValue( $dataValues ) );

		$instance = new PropertySpecificationChangeNotifier(
			$store
		);

		$instance->setPropertyList( $propertiesToCompare );
		$instance->detectChangesOn( $semanticData );

		$this->assertEquals(
			$expected['diff'],
			$instance->hasDiff()
		);

		$instance->notify();
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
			//  $mockedStoreValues, $dataValues, $settings,               $expected
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
	public function doComparePropertyValuesOnCallback( $subject, DIProperty $property, $requestoptions = null ) { // @codingStandardsIgnoreEnd
		return $property->getKey() === '_LIST' ? array() : $this->mockedStoreValues;
	}

}
