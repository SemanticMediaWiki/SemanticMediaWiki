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
			[
				'smwgDeclarationProperties' => [ '_PVAL' ],
				'smwgCacheType'  => 'hash',
				'smwgEnableUpdateJobs' => false
			]
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
			->setMethods( [ 'getPropertyValues' ] )
			->getMockForAbstractClass();

		$store->expects( $this->atLeastOnce() )
			->method( 'getPropertyValues' )
			->will( $this->returnCallback( [ $this, 'doComparePropertyValuesOnCallback' ] ) );

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
		$subject  = [
			DIWikiPage::newFromText( __METHOD__ )
		];

		// Multiple
		$subjects = [
			DIWikiPage::newFromText( __METHOD__ . 'm-0' ),
			DIWikiPage::newFromText( __METHOD__ . 'm-1' ),
			DIWikiPage::newFromText( __METHOD__ . 'm-2' )
		];

		return [
			//  $mockedStoreValues, $dataValues, $settings,               $expected
			[ $subjects, [],   [ '_PVAL', '_LIST' ], [ 'diff' => true,  'job' => true ] ],
			[ [],   $subjects, [ '_PVAL', '_LIST' ], [ 'diff' => true,  'job' => true ] ],
			[ $subject,  $subjects, [ '_PVAL', '_LIST' ], [ 'diff' => true,  'job' => true ] ],
			[ $subject,  [],   [ '_PVAL', '_LIST' ], [ 'diff' => true,  'job' => true ] ],
			[ $subject,  [],   [ '_PVAL'          ], [ 'diff' => true,  'job' => true ] ],
			[ $subjects, $subjects, [ '_PVAL'          ], [ 'diff' => false, 'job' => false ] ],
			[ $subject,  $subject,  [ '_PVAL'          ], [ 'diff' => false, 'job' => false ] ],
			[ $subjects, $subjects, [ '_PVAL', '_LIST' ], [ 'diff' => true,  'job' => true ] ],
			[ $subject,  $subject,  [ '_PVAL', '_LIST' ], [ 'diff' => true,  'job' => true ] ]
		];
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
		return $property->getKey() === '_LIST' ? [] : $this->mockedStoreValues;
	}

}
