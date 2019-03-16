<?php

namespace SMW\Tests\Property;

use SMW\DIProperty;
use SMW\DIWikiPage;
use SMWDIBlob as DIBlob;
use SMW\Property\ChangePropagationNotifier;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\Property\ChangePropagationNotifier
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class ChangePropagationNotifierTest extends \PHPUnit_Framework_TestCase {

	protected $mockedStoreValues;
	private $semanticData;
	private $serializerFactory;
	private $store;
	private $testEnvironment;

	protected function setUp() {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment(
			[
				'smwgChangePropagationWatchlist' => [ '_PVAL' ],
				'smwgMainCacheType'  => 'hash',
				'smwgEnableUpdateJobs' => false
			]
		);

		$semanticDataSerializer = $this->getMockBuilder( '\SMW\Serializers\SemanticDataSerializer' )
			->disableOriginalConstructor()
			->getMock();

		$this->serializerFactory = $this->getMockBuilder( '\SMW\SerializerFactory' )
			->disableOriginalConstructor()
			->getMock();

		$this->serializerFactory->expects( $this->any() )
			->method( 'newSemanticDataSerializer' )
			->will( $this->returnValue( $semanticDataSerializer ) );

		$this->store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->testEnvironment->registerObject( 'Store', $this->store );
	}

	protected function tearDown() {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			ChangePropagationNotifier::class,
			new ChangePropagationNotifier( $this->store, $this->serializerFactory )
		);
	}

	/**
	 * @dataProvider dataItemDataProvider
	 */
	public function testDetectChangesOnProperty( $mockedStoreValues, $dataValues, $propertiesToCompare, $expected ) {

		if ( !method_exists( 'JobQueueGroup', 'lazyPush' ) ) {
			$this->markTestSkipped( 'JobQueueGroup::lazyPush is not supported.' );
		}

		$subject = new DIWikiPage( __METHOD__, SMW_NS_PROPERTY );

		$this->detectChanges(
			$subject,
			$mockedStoreValues,
			$dataValues,
			$propertiesToCompare,
			$expected
		);
	}

	/**
	 * @dataProvider dataItemDataProvider
	 */
	public function testDetectChangesOnCategory( $mockedStoreValues, $dataValues, $propertiesToCompare, $expected ) {

		if ( !method_exists( 'JobQueueGroup', 'lazyPush' ) ) {
			$this->markTestSkipped( 'JobQueueGroup::lazyPush is not supported.' );
		}

		$subject = new DIWikiPage( __METHOD__, NS_CATEGORY );

		$this->detectChanges(
			$subject,
			$mockedStoreValues,
			$dataValues,
			$propertiesToCompare,
			$expected
		);
	}

	public function detectChanges( $subject, $mockedStoreValues, $dataValues, $propertiesToCompare, $expected ) {

		$this->mockedStoreValues = $mockedStoreValues;

		$expectedToRun = $expected['job'] ? $this->atLeastOnce() : $this->never();

		$jobQueueGroup = $this->getMockBuilder( '\JobQueueGroup' )
			->disableOriginalConstructor()
			->getMock();

		$jobQueueGroup->expects( $expectedToRun )
			->method( 'lazyPush' );

		$this->testEnvironment->registerObject( 'JobQueueGroup', $jobQueueGroup );

		$semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$store = $this->getMockBuilder( 'SMW\Store' )
			->disableOriginalConstructor()
			->setMethods( [ 'getPropertyValues', 'getSemanticData' ] )
			->getMockForAbstractClass();

		$store->expects( $this->any() )
			->method( 'getSemanticData' )
			->will( $this->returnValue( $semanticData ) );

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

		$instance = new ChangePropagationNotifier(
			$store,
			$this->serializerFactory
		);

		$instance->setPropertyList( $propertiesToCompare );

		$instance->checkAndNotify( $semanticData );

		$this->assertEquals(
			$expected['diff'],
			$instance->hasDiff()
		);
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
			[ $subject,  $subject,  [ '_PVAL', '_LIST' ], [ 'diff' => true,  'job' => true ] ],
			[ [ new DIBlob( '>100') ],  [ new DIBlob( '&gt;100') ],  [ '_PVAL', '_PVAL' ], [ 'diff' => false,  'job' => false ] ]
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
