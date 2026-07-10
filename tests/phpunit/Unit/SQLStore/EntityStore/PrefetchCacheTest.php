<?php

namespace SMW\Tests\Unit\SQLStore\EntityStore;

use PHPUnit\Framework\TestCase;
use SMW\DataItems\Property;
use SMW\DataItems\WikiPage;
use SMW\RequestOptions;
use SMW\SQLStore\EntityStore\EntityIdManager;
use SMW\SQLStore\EntityStore\PrefetchCache;
use SMW\SQLStore\EntityStore\PrefetchItemLookup;
use SMW\SQLStore\SQLStore;
use SMW\StringCondition;

/**
 * @covers \SMW\SQLStore\EntityStore\PrefetchCache
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
class PrefetchCacheTest extends TestCase {

	private $store;
	private $prefetchItemLookup;
	private $requestOptions;

	protected function setUp(): void {
		$this->store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$this->prefetchItemLookup = $this->getMockBuilder( PrefetchItemLookup::class )
			->disableOriginalConstructor()
			->getMock();

		$this->requestOptions = new RequestOptions();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			PrefetchCache::class,
			new PrefetchCache( $this->store, $this->prefetchItemLookup )
		);
	}

	public function testCacheKeySeparatesDirectAndInverseProperty() {
		// Wikitext equivalent:
		// {{#ask: [[Category:Example]] |?Foo |?-Foo }}
		$requestOptions = new RequestOptions();
		$requestOptions->isChain = false;
		$requestOptions->isFirstChain = false;

		$this->assertNotSame(
			PrefetchCache::makeCacheKey( new Property( 'Foo' ), $requestOptions ),
			PrefetchCache::makeCacheKey( new Property( 'Foo', true ), $requestOptions )
		);
	}

	public function testCacheKeyUsesSelfIdentifyingContextMarkers() {
		$requestOptions = new RequestOptions();
		$requestOptions->isChain = false;
		$requestOptions->isFirstChain = false;

		$this->assertMatchesRegularExpression(
			'/^Foo#valueOptions:[a-f0-9]{32}$/',
			PrefetchCache::makeCacheKey( new Property( 'Foo' ), $requestOptions )
		);

		$this->assertMatchesRegularExpression(
			'/^Foo#isInverse#valueOptions:[a-f0-9]{32}$/',
			PrefetchCache::makeCacheKey( new Property( 'Foo', true ), $requestOptions )
		);

		$requestOptions->isChain = true;

		$this->assertMatchesRegularExpression(
			'/^Foo#isChain#valueOptions:[a-f0-9]{32}$/',
			PrefetchCache::makeCacheKey( new Property( 'Foo' ), $requestOptions )
		);

		$requestOptions->isFirstChain = true;

		$this->assertMatchesRegularExpression(
			'/^Foo#isChain#isInverse#isFirstChain#valueOptions:[a-f0-9]{32}$/',
			PrefetchCache::makeCacheKey( new Property( 'Foo', true ), $requestOptions )
		);
	}

	public function testCacheKeySeparatesRequestOptions() {
		// Wikitext equivalent:
		// {{#ask: [[Category:Example]] |?Foo|+order=asc |?Foo|+order=desc }}
		$ascendingOptions = new RequestOptions();
		$ascendingOptions->isChain = false;
		$ascendingOptions->isFirstChain = false;
		$ascendingOptions->sort = true;
		$ascendingOptions->ascending = true;

		$descendingOptions = new RequestOptions();
		$descendingOptions->isChain = false;
		$descendingOptions->isFirstChain = false;
		$descendingOptions->sort = true;
		$descendingOptions->ascending = false;

		$this->assertNotSame(
			PrefetchCache::makeCacheKey( new Property( 'Foo' ), $ascendingOptions ),
			PrefetchCache::makeCacheKey( new Property( 'Foo' ), $descendingOptions )
		);
	}

	public function testCacheKeySeparatesStringConditions() {
		$requestOptions = new RequestOptions();
		$requestOptions->isChain = false;
		$requestOptions->isFirstChain = false;

		$stringConditionOptions = clone $requestOptions;
		$stringConditionOptions->addStringCondition( 'Value', StringCondition::COND_PRE );

		$this->assertNotSame(
			PrefetchCache::makeCacheKey( new Property( 'Foo' ), $requestOptions ),
			PrefetchCache::makeCacheKey( new Property( 'Foo' ), $stringConditionOptions )
		);
	}

	public function testCacheKeyIgnoresInternalLookupOptions() {
		$requestOptions = new RequestOptions();
		$requestOptions->isChain = false;
		$requestOptions->isFirstChain = false;

		$lookupOptions = clone $requestOptions;
		$lookupOptions->exclude_limit = true;
		$lookupOptions->setOption( RequestOptions::PREFETCH_FINGERPRINT, 'subject-set' );
		$lookupOptions->setOption( 'NO_GROUPBY', true );
		$lookupOptions->setOption( 'NO_DISTINCT', true );
		$lookupOptions->setOption( 'ORDER BY', 'smw_sort' );
		$lookupOptions->setOption( 'GROUP BY', 'smw_id' );
		$lookupOptions->setOption( 'DISTINCT', true );

		$this->assertSame(
			PrefetchCache::makeCacheKey( new Property( 'Foo' ), $requestOptions ),
			PrefetchCache::makeCacheKey( new Property( 'Foo' ), $lookupOptions )
		);
	}

	public function testIsCachedUsesRequestOptionsCacheKey() {
		// Wikitext equivalent:
		// {{#ask: [[Category:Example]] |?Foo |?Bar.Foo }}
		$property = new Property( 'Foo' );
		$subject = WikiPage::newFromText( __METHOD__ );

		$idTable = $this->getMockBuilder( EntityIdManager::class )
			->disableOriginalConstructor()
			->getMock();

		$this->store->method( 'getObjectIds' )
			->willReturn( $idTable );

		$this->prefetchItemLookup->method( 'getPropertyValues' )
			->willReturn( [] );

		$chainOptions = new RequestOptions();
		$chainOptions->isChain = true;
		$chainOptions->isFirstChain = true;

		$directOptions = new RequestOptions();
		$directOptions->isChain = false;
		$directOptions->isFirstChain = false;

		$instance = new PrefetchCache(
			$this->store,
			$this->prefetchItemLookup
		);

		$instance->prefetch( [ $subject ], $property, $chainOptions );

		$this->assertTrue(
			$instance->isCached( $property, $chainOptions )
		);

		$this->assertFalse(
			$instance->isCached( $property, $directOptions )
		);
	}

	public function testCacheAndFetch() {
		$property = new Property( 'Foo' );
		$subject = WikiPage::newFromText( __METHOD__ );

		$expected = [
			WikiPage::newFromText( 'Bar' )
		];

		$idTable = $this->getMockBuilder( EntityIdManager::class )
			->disableOriginalConstructor()
			->getMock();

		$idTable->expects( $this->atLeastOnce() )
			->method( 'getSMWPageID' )
			->with( __METHOD__ )
			->willReturn( 42 );

		$this->store->expects( $this->atLeastOnce() )
			->method( 'getObjectIds' )
			->willReturn( $idTable );

		$this->prefetchItemLookup->expects( $this->atLeastOnce() )
			->method( 'getPropertyValues' )
			->willReturn( [ 42 => [ WikiPage::newFromText( 'Bar' ) ] ] );

		$instance = new PrefetchCache(
			$this->store,
			$this->prefetchItemLookup
		);

		$instance->prefetch( [ $subject ], $property, $this->requestOptions );

		$this->assertEquals(
			$expected,
			$instance->getPropertyValues( $subject, $property, $this->requestOptions )
		);
	}

	public function testClearResetsCachedValuesAndExecutedLookups() {
		$property = new Property( 'Pc' );
		$subject = WikiPage::newFromText( 'Subject' );

		$idTable = $this->getMockBuilder( EntityIdManager::class )
			->disableOriginalConstructor()
			->getMock();

		$this->store->method( 'getObjectIds' )
			->willReturn( $idTable );

		$this->prefetchItemLookup->expects( $this->exactly( 2 ) )
			->method( 'getPropertyValues' )
			->willReturn( [] );

		$instance = new PrefetchCache(
			$this->store,
			$this->prefetchItemLookup
		);

		$instance->prefetch( [ $subject ], $property, $this->requestOptions );

		$this->assertTrue(
			$instance->isCached( $property, $this->requestOptions )
		);

		$instance->clear();

		$this->assertFalse(
			$instance->isCached( $property, $this->requestOptions )
		);

		$instance->prefetch( [ $subject ], $property, $this->requestOptions );
	}

	public function testCacheMergeWithDifferentSubjectSets() {
		$property = new Property( 'Pm' );
		$subject1 = WikiPage::newFromText( 'Subject1' );
		$subject2 = WikiPage::newFromText( 'Subject2' );

		$idTable = $this->getMockBuilder( EntityIdManager::class )
			->disableOriginalConstructor()
			->getMock();

		$idTable->method( 'getSMWPageID' )
			->willReturnOnConsecutiveCalls( 101, 102 );

		$this->store->method( 'getObjectIds' )
			->willReturn( $idTable );

		$this->prefetchItemLookup->method( 'getPropertyValues' )
			->willReturnOnConsecutiveCalls(
				[ 101 => [ WikiPage::newFromText( 'Result1' ) ] ],
				[ 102 => [ WikiPage::newFromText( 'Result2' ) ] ]
			);

		$instance = new PrefetchCache(
			$this->store,
			$this->prefetchItemLookup
		);

		$instance->prefetch( [ $subject1 ], $property, $this->requestOptions );
		$instance->prefetch( [ $subject2 ], $property, $this->requestOptions );

		$this->assertEquals(
			[ WikiPage::newFromText( 'Result1' ) ],
			$instance->getPropertyValues( $subject1, $property, $this->requestOptions )
		);

		$this->assertEquals(
			[ WikiPage::newFromText( 'Result2' ) ],
			$instance->getPropertyValues( $subject2, $property, $this->requestOptions )
		);
	}

	public function testCacheAndExecutedLookupSetSeparateRequestOptions() {
		// Wikitext equivalent:
		// {{#ask: [[Category:Example]] |?Po|+order=asc |?Po|+order=desc }}
		$property = new Property( 'Po' );
		$subject = WikiPage::newFromText( 'Subject' );

		$idTable = $this->getMockBuilder( EntityIdManager::class )
			->disableOriginalConstructor()
			->getMock();

		$idTable->method( 'getSMWPageID' )
			->willReturn( 103 );

		$this->store->method( 'getObjectIds' )
			->willReturn( $idTable );

		$this->prefetchItemLookup->expects( $this->exactly( 2 ) )
			->method( 'getPropertyValues' )
			->willReturnCallback(
				static function ( array $subjects, Property $property, RequestOptions $requestOptions ): array {
					$value = $requestOptions->ascending ? 'Ascending' : 'Descending';

					return [ 103 => [ WikiPage::newFromText( $value ) ] ];
				}
			);

		$ascendingOptions = new RequestOptions();
		$ascendingOptions->isChain = false;
		$ascendingOptions->isFirstChain = false;
		$ascendingOptions->sort = true;
		$ascendingOptions->ascending = true;

		$descendingOptions = new RequestOptions();
		$descendingOptions->isChain = false;
		$descendingOptions->isFirstChain = false;
		$descendingOptions->sort = true;
		$descendingOptions->ascending = false;

		$instance = new PrefetchCache(
			$this->store,
			$this->prefetchItemLookup
		);

		$instance->prefetch( [ $subject ], $property, $ascendingOptions );

		$this->assertEquals(
			[ WikiPage::newFromText( 'Ascending' ) ],
			$instance->getPropertyValues( $subject, $property, $ascendingOptions )
		);

		$instance->prefetch( [ $subject ], $property, $descendingOptions );

		$this->assertEquals(
			[ WikiPage::newFromText( 'Ascending' ) ],
			$instance->getPropertyValues( $subject, $property, $ascendingOptions )
		);

		$this->assertEquals(
			[ WikiPage::newFromText( 'Descending' ) ],
			$instance->getPropertyValues( $subject, $property, $descendingOptions )
		);
	}

	public function testPrefetchPassesFingerprintToLookupRequestOptionsWithoutMutatingOriginal() {
		// Wikitext equivalent when evaluated twice:
		// {{#ask: [[Category:Example]] |?Pf }}
		$property = new Property( 'Pf' );
		$subject = WikiPage::newFromText( 'Subject' );
		$requestOptions = new RequestOptions();
		$requestOptions->isChain = false;
		$requestOptions->isFirstChain = false;

		$idTable = $this->getMockBuilder( EntityIdManager::class )
			->disableOriginalConstructor()
			->getMock();

		$this->store->method( 'getObjectIds' )
			->willReturn( $idTable );

		$this->prefetchItemLookup->expects( $this->once() )
			->method( 'getPropertyValues' )
			->willReturnCallback(
				function ( array $subjects, Property $property, RequestOptions $lookupRequestOptions ) use ( $requestOptions ): array {
					$this->assertNotSame( $requestOptions, $lookupRequestOptions );
					$this->assertNotNull(
						$lookupRequestOptions->getOption( RequestOptions::PREFETCH_FINGERPRINT )
					);

					return [];
				}
			);

		$instance = new PrefetchCache(
			$this->store,
			$this->prefetchItemLookup
		);

		$instance->prefetch( [ $subject ], $property, $requestOptions );
		$instance->prefetch( [ $subject ], $property, $requestOptions );

		$this->assertNull(
			$requestOptions->getOption( RequestOptions::PREFETCH_FINGERPRINT )
		);
	}

}
