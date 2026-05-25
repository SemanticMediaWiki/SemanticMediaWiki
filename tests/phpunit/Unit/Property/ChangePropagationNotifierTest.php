<?php

namespace SMW\Tests\Unit\Property;

use PHPUnit\Framework\TestCase;
use SMW\DataItems\Blob;
use SMW\DataItems\DataItem;
use SMW\DataItems\Property;
use SMW\DataItems\WikiPage;
use SMW\DataModel\SemanticData;
use SMW\Property\ChangePropagationNotifier;
use SMW\SerializerFactory;
use SMW\Serializers\SemanticDataSerializer;
use SMW\Store;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\Property\ChangePropagationNotifier
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 1.9
 *
 * @author mwjames
 */
class ChangePropagationNotifierTest extends TestCase {

	protected $mockedStoreValues;
	private $semanticData;
	private $serializerFactory;
	private $store;
	private $testEnvironment;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment(
			[
				'smwgChangePropagationWatchlist' => [ '_PVAL' ],
				'smwgMainCacheType'  => 'hash',
				'smwgEnableUpdateJobs' => false
			]
		);

		$semanticDataSerializer = $this->getMockBuilder( SemanticDataSerializer::class )
			->disableOriginalConstructor()
			->getMock();

		$this->serializerFactory = $this->getMockBuilder( SerializerFactory::class )
			->disableOriginalConstructor()
			->getMock();

		$this->serializerFactory->expects( $this->any() )
			->method( 'newSemanticDataSerializer' )
			->willReturn( $semanticDataSerializer );

		$this->store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();
	}

	protected function tearDown(): void {
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
		$subject = new WikiPage( __METHOD__, SMW_NS_PROPERTY );

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
		$subject = new WikiPage( __METHOD__, NS_CATEGORY );

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

		$semanticData = $this->getMockBuilder( SemanticData::class )
			->setConstructorArgs( [ WikiPage::newFromText( 'Foo' ) ] )
			->getMock();

		$store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->setMethods( [ 'getPropertyValues', 'getSemanticData' ] )
			->getMockForAbstractClass();

		$store->expects( $this->any() )
			->method( 'getSemanticData' )
			->willReturn( $semanticData );

		$store->expects( $this->atLeastOnce() )
			->method( 'getPropertyValues' )
			->willReturnCallback( [ $this, 'doComparePropertyValuesOnCallback' ] );

		$semanticData = $this->getMockBuilder( SemanticData::class )
			->setConstructorArgs( [ WikiPage::newFromText( 'Foo' ) ] )
			->getMock();

		$semanticData->expects( $this->atLeastOnce() )
			->method( 'getSubject' )
			->willReturn( $subject );

		$semanticData->expects( $this->atLeastOnce() )
			->method( 'getPropertyValues' )
			->willReturn( $dataValues );

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
		$subject = [
			WikiPage::newFromText( __METHOD__ )
		];

		// Multiple
		$subjects = [
			WikiPage::newFromText( __METHOD__ . 'm-0' ),
			WikiPage::newFromText( __METHOD__ . 'm-1' ),
			WikiPage::newFromText( __METHOD__ . 'm-2' )
		];

		return [
			// $mockedStoreValues, $dataValues, $settings,               $expected
			[ $subjects, [], [ '_PVAL', '_LIST' ], [ 'diff' => true, 'job' => true ] ],
			[ [], $subjects, [ '_PVAL', '_LIST' ], [ 'diff' => true, 'job' => true ] ],
			[ $subject, $subjects, [ '_PVAL', '_LIST' ], [ 'diff' => true, 'job' => true ] ],
			[ $subject, [], [ '_PVAL', '_LIST' ], [ 'diff' => true, 'job' => true ] ],
			[ $subject, [], [ '_PVAL' ], [ 'diff' => true, 'job' => true ] ],
			[ $subjects, $subjects, [ '_PVAL' ], [ 'diff' => false, 'job' => false ] ],
			[ $subject, $subject, [ '_PVAL' ], [ 'diff' => false, 'job' => false ] ],
			[ $subjects, $subjects, [ '_PVAL', '_LIST' ], [ 'diff' => true, 'job' => true ] ],
			[ $subject, $subject, [ '_PVAL', '_LIST' ], [ 'diff' => true, 'job' => true ] ],
			[ [ new Blob( '>100' ) ], [ new Blob( '&gt;100' ) ], [ '_PVAL', '_PVAL' ], [ 'diff' => false, 'job' => false ] ]
		];
	}

	public function testDispatchedJobIncludesDiffKeysAndPreservesIsTypePropagation() {
		// _TYPE diffs (always-watched) and _PVAL diffs (from setPropertyList).
		// The dispatched job must carry both keys in diffKeys and have
		// isTypePropagation set to true, since _TYPE is the first diffing key.
		$this->mockedStoreValues = [ new Blob( 'old-value' ) ];

		$subject = new WikiPage( __METHOD__, SMW_NS_PROPERTY );

		$capturedJob = null;

		$jobQueueGroup = $this->getMockBuilder( '\JobQueueGroup' )
			->disableOriginalConstructor()
			->onlyMethods( [ 'lazyPush' ] )
			->getMock();
		$jobQueueGroup->expects( $this->atLeastOnce() )
			->method( 'lazyPush' )
			->willReturnCallback( static function ( $job ) use ( &$capturedJob ) {
				$capturedJob = $job;
			} );
		$this->testEnvironment->registerObject( 'JobQueueGroup', $jobQueueGroup );

		$store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->onlyMethods( [ 'getPropertyValues', 'getSemanticData' ] )
			->getMockForAbstractClass();

		$innerSemanticData = $this->getMockBuilder( SemanticData::class )
			->setConstructorArgs( [ WikiPage::newFromText( 'Foo' ) ] )
			->getMock();
		$store->method( 'getSemanticData' )->willReturn( $innerSemanticData );
		$store->method( 'getPropertyValues' )
			->willReturnCallback( [ $this, 'doComparePropertyValuesOnCallback' ] );

		$semanticData = $this->getMockBuilder( SemanticData::class )
			->setConstructorArgs( [ WikiPage::newFromText( 'Foo' ) ] )
			->getMock();
		$semanticData->method( 'getSubject' )->willReturn( $subject );
		$semanticData->method( 'getPropertyValues' )->willReturn( [
			new Blob( 'new-value-different-from-old' ),
		] );

		$instance = new ChangePropagationNotifier( $store, $this->serializerFactory );
		$instance->setPropertyList( [ '_PVAL' ] );
		$instance->checkAndNotify( $semanticData );

		$this->assertNotNull( $capturedJob );
		$this->assertContains( '_TYPE', $capturedJob->getParameter( 'diffKeys' ) );
		$this->assertContains( '_PVAL', $capturedJob->getParameter( 'diffKeys' ) );
		$this->assertTrue( $capturedJob->getParameter( 'isTypePropagation' ) );
	}

	public function testDispatchedJobOmitsIsTypePropagationForNonTypeDiff() {
		// _PVAL diffs but _TYPE (and other always-watched keys) do NOT diff.
		// The dispatched job must carry _PVAL in diffKeys but must NOT carry
		// isTypePropagation (getParameter returns false for absent keys).
		$matchingBlob = new Blob( 'same-value' );
		$this->mockedStoreValues = [ $matchingBlob ];

		$subject = new WikiPage( __METHOD__, SMW_NS_PROPERTY );

		$capturedJob = null;

		$jobQueueGroup = $this->getMockBuilder( '\JobQueueGroup' )
			->disableOriginalConstructor()
			->onlyMethods( [ 'lazyPush' ] )
			->getMock();
		$jobQueueGroup->expects( $this->atLeastOnce() )
			->method( 'lazyPush' )
			->willReturnCallback( static function ( $job ) use ( &$capturedJob ) {
				$capturedJob = $job;
			} );
		$this->testEnvironment->registerObject( 'JobQueueGroup', $jobQueueGroup );

		$store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->onlyMethods( [ 'getPropertyValues', 'getSemanticData' ] )
			->getMockForAbstractClass();

		$innerSemanticData = $this->getMockBuilder( SemanticData::class )
			->setConstructorArgs( [ WikiPage::newFromText( 'Foo' ) ] )
			->getMock();
		$store->method( 'getSemanticData' )->willReturn( $innerSemanticData );
		// Store (old values): return the matching blob for every property.
		$store->method( 'getPropertyValues' )
			->willReturnCallback( [ $this, 'doComparePropertyValuesOnCallback' ] );

		$semanticData = $this->getMockBuilder( SemanticData::class )
			->setConstructorArgs( [ WikiPage::newFromText( 'Foo' ) ] )
			->getMock();
		$semanticData->method( 'getSubject' )->willReturn( $subject );
		// New values: return the same blob for _TYPE/_CONV/_UNIT/_REDI (no diff),
		// but a different blob for _PVAL (diff).
		$semanticData->method( 'getPropertyValues' )
			->willReturnCallback( static function ( Property $property ) use ( $matchingBlob ) {
				return $property->getKey() === '_PVAL'
					? [ new Blob( 'different-value' ) ]
					: [ $matchingBlob ];
			} );

		$instance = new ChangePropagationNotifier( $store, $this->serializerFactory );
		$instance->setPropertyList( [ '_PVAL' ] );
		$instance->checkAndNotify( $semanticData );

		$this->assertNotNull( $capturedJob );
		$diffKeys = $capturedJob->getParameter( 'diffKeys' );
		$this->assertNotEmpty( $diffKeys );
		$this->assertNotContains( '_TYPE', $diffKeys );
		$this->assertContains( '_PVAL', $diffKeys );
		// getParameter returns false for absent keys; isTypePropagation must not be set.
		$this->assertFalse( $capturedJob->getParameter( 'isTypePropagation' ) );
	}

	public function testDispatchedJobIncludesDiffKeysForTypeOnlyDiff() {
		// Only _TYPE diffs; _PVAL and other always-watched keys do NOT diff.
		// diffKeys must equal ['_TYPE'] and isTypePropagation must be true.
		$matchingBlob = new Blob( 'same-value' );
		$this->mockedStoreValues = [ $matchingBlob ];

		$subject = new WikiPage( __METHOD__, SMW_NS_PROPERTY );

		$capturedJob = null;

		$jobQueueGroup = $this->getMockBuilder( '\JobQueueGroup' )
			->disableOriginalConstructor()
			->onlyMethods( [ 'lazyPush' ] )
			->getMock();
		$jobQueueGroup->expects( $this->atLeastOnce() )
			->method( 'lazyPush' )
			->willReturnCallback( static function ( $job ) use ( &$capturedJob ) {
				$capturedJob = $job;
			} );
		$this->testEnvironment->registerObject( 'JobQueueGroup', $jobQueueGroup );

		$store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->onlyMethods( [ 'getPropertyValues', 'getSemanticData' ] )
			->getMockForAbstractClass();

		$innerSemanticData = $this->getMockBuilder( SemanticData::class )
			->setConstructorArgs( [ WikiPage::newFromText( 'Foo' ) ] )
			->getMock();
		$store->method( 'getSemanticData' )->willReturn( $innerSemanticData );
		// Store (old values): return the matching blob for every property.
		$store->method( 'getPropertyValues' )
			->willReturnCallback( [ $this, 'doComparePropertyValuesOnCallback' ] );

		$semanticData = $this->getMockBuilder( SemanticData::class )
			->setConstructorArgs( [ WikiPage::newFromText( 'Foo' ) ] )
			->getMock();
		$semanticData->method( 'getSubject' )->willReturn( $subject );
		// New values: return a different blob for _TYPE (diff) but the same
		// blob for all other properties including _PVAL (no diff).
		$semanticData->method( 'getPropertyValues' )
			->willReturnCallback( static function ( Property $property ) use ( $matchingBlob ) {
				return $property->getKey() === '_TYPE'
					? [ new Blob( 'different-type' ) ]
					: [ $matchingBlob ];
			} );

		$instance = new ChangePropagationNotifier( $store, $this->serializerFactory );
		$instance->setPropertyList( [ '_PVAL' ] );
		$instance->checkAndNotify( $semanticData );

		$this->assertNotNull( $capturedJob );
		$this->assertSame( [ '_TYPE' ], $capturedJob->getParameter( 'diffKeys' ) );
		$this->assertTrue( $capturedJob->getParameter( 'isTypePropagation' ) );
	}

	/**
	 * Returns an array of DataItem and simulates an alternating
	 * existencance of return values ('_LIST')
	 *
	 * @see Store::getPropertyValues
	 *
	 * @return DataItem[]
	 */
	// @codingStandardsIgnoreStart phpcs, ignore --sniffs=Generic.CodeAnalysis.UnusedFunctionParameter
	public function doComparePropertyValuesOnCallback( $subject, Property $property, $requestoptions = null ) { // @codingStandardsIgnoreEnd
		return $property->getKey() === '_LIST' ? [] : $this->mockedStoreValues;
	}

}
