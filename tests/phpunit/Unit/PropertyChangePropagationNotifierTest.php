<?php

namespace SMW\Tests;

use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\PropertyChangePropagationNotifier;

/**
 * @covers \SMW\PropertyChangePropagationNotifier
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class PropertyChangePropagationNotifierTest extends \PHPUnit_Framework_TestCase {

	protected $mockedStoreValues;
	private $semanticData;
	private $serializerFactory;
	private $store;
	private $testEnvironment;

	protected function setUp() {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment(
			array(
				'smwgChangePropagationWatchlist' => array( '_PVAL' ),
				'smwgCacheType'  => 'hash',
				'smwgEnableUpdateJobs' => false
			)
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
			PropertyChangePropagationNotifier::class,
			new PropertyChangePropagationNotifier( $this->store, $this->serializerFactory )
		);
	}

	/**
	 * @dataProvider dataItemDataProvider
	 */
	public function testDetectChanges( $mockedStoreValues, $dataValues, $propertiesToCompare, $expected ) {

		if ( !method_exists( 'JobQueueGroup', 'lazyPush' ) ) {
			$this->markTestSkipped( 'JobQueueGroup::lazyPush is not supported.' );
		}

		$this->mockedStoreValues = $mockedStoreValues;

		$subject = new DIWikiPage( __METHOD__, SMW_NS_PROPERTY );

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
			->setMethods( array( 'getPropertyValues', 'getSemanticData' ) )
			->getMockForAbstractClass();

		$store->expects( $this->any() )
			->method( 'getSemanticData' )
			->will( $this->returnValue( $semanticData ) );

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

		$instance = new PropertyChangePropagationNotifier(
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
