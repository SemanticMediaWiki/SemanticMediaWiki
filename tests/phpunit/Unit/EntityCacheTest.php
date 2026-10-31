<?php

namespace SMW\Tests\Unit;

use LogicException;
use PHPUnit\Framework\TestCase;
use SMW\DataItems\WikiPage;
use SMW\EntityCache;
use SMW\Tests\Utils\EntityCacheFactory;
use Wikimedia\ObjectCache\BagOStuff;
use Wikimedia\ObjectCache\EmptyBagOStuff;
use Wikimedia\ObjectCache\HashBagOStuff;

/**
 * Each EntityCache instance stands for one process; instances built over the
 * same backend share its entries but not their in-process caches.
 *
 * @covers \SMW\EntityCache
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
class EntityCacheTest extends TestCase {

	private BagOStuff $backend;
	private float $now = 1700000000.0;

	protected function setUp(): void {
		$this->backend = new HashBagOStuff();
	}

	private function newEntityCache(): EntityCache {
		return EntityCacheFactory::newEntityCache( $this->backend, $this->now );
	}

	private function lookUp( EntityCache $entityCache, WikiPage $subject, mixed $generatedValue ): mixed {
		return $this->lookUpWith( $entityCache, $subject, static fn () => $generatedValue );
	}

	private function lookUpWith( EntityCache $entityCache, WikiPage $subject, callable $callback ): mixed {
		return $entityCache->getWithSetCallback(
			$subject,
			$this->makeKey( $subject ),
			EntityCache::TTL_WEEK,
			$callback
		);
	}

	/**
	 * @param EntityCache $entityCache
	 * @param WikiPage[] $subjects
	 * @param string[][]|null &$batches Names of the subjects of each generating call
	 *
	 * @return array<string,mixed> Values keyed by subject name
	 */
	private function lookUpMulti( EntityCache $entityCache, array $subjects, ?array &$batches = [] ): array {
		$subjectsByKey = [];

		foreach ( $subjects as $subject ) {
			$subjectsByKey[$this->makeKey( $subject )] = $subject;
		}

		$values = $entityCache->getMultiWithUnionSetCallback(
			$subjectsByKey,
			EntityCache::TTL_WEEK,
			function ( array $missing ) use ( &$batches ) {
				$batches[] = array_values( array_map( [ $this, 'nameOf' ], $missing ) );

				return array_map( fn ( WikiPage $subject ) => 'generated ' . $this->nameOf( $subject ), $missing );
			}
		);

		$valuesByName = [];

		foreach ( $values as $key => $value ) {
			$valuesByName[$this->nameOf( $subjectsByKey[$key] )] = $value;
		}

		return $valuesByName;
	}

	private function nameOf( WikiPage $subject ): string {
		return $subject->getSubobjectName() === '' ? $subject->getDBkey() : $subject->getDBkey() . '#' . $subject->getSubobjectName();
	}

	private function makeKey( WikiPage $subject ): string {
		return $this->newEntityCache()->makeKey( 'test', $subject->getHash() );
	}

	private function waitSeconds( int $seconds ): void {
		$this->now += $seconds;
	}

	private function invalidateDuringGeneration( WikiPage $subject ): callable {
		return function () use ( $subject ) {
			$this->newEntityCache()->invalidate( $subject );

			return 'generated from outdated data';
		};
	}

	public function testMakeCacheKey() {
		$instance = $this->newEntityCache();

		$this->assertStringContainsString(
			EntityCache::CACHE_NAMESPACE,
			$instance->makeKey( 'Foo' )
		);

		$subject = WikiPage::newFromText( 'Foo' );

		$this->assertEquals(
			$instance->makeKey( $subject ),
			$instance->makeKey( 'Foo#0##' )
		);

		$this->assertEquals(
			$instance->makeKey( $subject->getTitle() ),
			$instance->makeKey( 'Foo#0##' )
		);
	}

	public function testMakeCacheKey_SubNamespace() {
		$instance = EntityCacheFactory::newEntityCache( new HashBagOStuff( [ 'keyspace' => 'wiki' ] ) );
		$subject = WikiPage::newFromText( 'Foo' );

		$this->assertStringStartsWith( 'wiki:smw:entity:', $instance->makeKey( $subject ) );
		$this->assertStringStartsWith( 'wiki:smw:entity:foo:', $instance->makeKey( ':foo', $subject ) );
	}

	public function testLookUpReturnsTheGeneratedValueOnAMiss() {
		$this->assertSame(
			'generated',
			$this->lookUp( $this->newEntityCache(), WikiPage::newFromText( 'Foo' ), 'generated' )
		);
	}

	public function testValueCachedByOneProcessIsReturnedToAnother() {
		$subject = WikiPage::newFromText( 'Foo' );
		$this->lookUp( $this->newEntityCache(), $subject, 'cached' );

		$this->assertSame(
			'cached',
			$this->lookUp( $this->newEntityCache(), $subject, 'regenerated' )
		);
	}

	public function testRepeatedLookUpInAProcessIsServedFromMemory() {
		$this->backend = new EmptyBagOStuff();
		$subject = WikiPage::newFromText( 'Foo' );
		$entityCache = $this->newEntityCache();
		$this->lookUp( $entityCache, $subject, 'cached' );

		$this->assertSame(
			'cached',
			$this->lookUp( $entityCache, $subject, 'regenerated' )
		);
	}

	public function testRepeatedLookUpInAProcessDoesNotReadTheBackend() {
		$this->backend = new class extends HashBagOStuff {
			public int $reads = 0;

			protected function doGetMulti( array $keys, $flags = 0 ) {
				$this->reads++;

				return parent::doGetMulti( $keys, $flags );
			}
		};
		$subject = WikiPage::newFromText( 'Foo' );
		$entityCache = $this->newEntityCache();
		$this->lookUp( $entityCache, $subject, 'cached' );
		$reads = $this->backend->reads;

		$this->lookUp( $entityCache, $subject, 'regenerated' );

		$this->assertSame( $reads, $this->backend->reads );
	}

	public function testValueReadFromTheBackendIsServedFromMemory() {
		$subject = WikiPage::newFromText( 'Foo' );
		$this->lookUp( $this->newEntityCache(), $subject, 'cached' );
		$entityCache = $this->newEntityCache();
		$this->lookUp( $entityCache, $subject, 'regenerated' );

		$this->newEntityCache()->invalidate( $subject );

		$this->assertSame( 'cached', $this->lookUp( $entityCache, $subject, 'regenerated' ) );
	}

	public function testGeneratingAValueOfAPageKeepsItsOtherValuesCached() {
		$page = WikiPage::newFromText( 'Foo' );
		$this->lookUp( $this->newEntityCache(), $page, 'cached' );

		$this->lookUp( $this->newEntityCache(), new WikiPage( 'Foo', NS_MAIN, '', 'Bar' ), 'other value of the page' );

		$this->assertSame( 'cached', $this->lookUp( $this->newEntityCache(), $page, 'regenerated' ) );
	}

	public function testValueIsRegeneratedOnceItExpires() {
		$subject = WikiPage::newFromText( 'Foo' );
		$this->lookUp( $this->newEntityCache(), $subject, 'expired' );
		$this->waitSeconds( EntityCache::TTL_WEEK + 1 );

		$this->assertSame(
			'regenerated',
			$this->lookUp( $this->newEntityCache(), $subject, 'regenerated' )
		);
	}

	public function testValueCachedForAMonthIsStillCachedAfterAWeek() {
		$subject = WikiPage::newFromText( 'Foo' );
		$this->newEntityCache()->getWithSetCallback(
			$subject,
			$this->makeKey( $subject ),
			EntityCache::TTL_MONTH,
			static fn () => 'cached'
		);
		$this->waitSeconds( EntityCache::TTL_WEEK + 1 );

		$this->assertSame(
			'cached',
			$this->lookUp( $this->newEntityCache(), $subject, 'regenerated' )
		);
	}

	public function testValueCachedForAMonthAfterAnInvalidationIsStillCachedAfterAWeek() {
		$subject = WikiPage::newFromText( 'Foo' );
		$this->newEntityCache()->invalidate( $subject );
		$this->newEntityCache()->getWithSetCallback(
			$subject,
			$this->makeKey( $subject ),
			EntityCache::TTL_MONTH,
			static fn () => 'cached'
		);
		$this->waitSeconds( EntityCache::TTL_WEEK + 1 );

		$this->assertSame( 'cached', $this->lookUp( $this->newEntityCache(), $subject, 'regenerated' ) );
	}

	public function testMultiLookUpRegeneratesValuesOnceTheyExpire() {
		$subject = WikiPage::newFromText( 'Foo' );
		$this->lookUpMulti( $this->newEntityCache(), [ $subject ] );
		$this->waitSeconds( EntityCache::TTL_WEEK + 1 );

		$this->lookUpMulti( $this->newEntityCache(), [ $subject ], $batches );

		$this->assertSame( [ [ 'Foo' ] ], $batches );
	}

	public function testGeneratedFalseIsNotCached() {
		$subject = WikiPage::newFromText( 'Foo' );
		$entityCache = $this->newEntityCache();
		$this->lookUp( $entityCache, $subject, false );

		$this->assertSame(
			'regenerated',
			$this->lookUp( $entityCache, $subject, 'regenerated' )
		);
	}

	public function testLookUpAfterInvalidationInTheSameProcessRegeneratesTheValue() {
		$subject = WikiPage::newFromText( 'Foo' );
		$entityCache = $this->newEntityCache();
		$this->lookUp( $entityCache, $subject, 'outdated' );

		$entityCache->invalidate( $subject );

		$this->assertSame(
			'regenerated',
			$this->lookUp( $entityCache, $subject, 'regenerated' )
		);
	}

	public function testMultiLookUpAfterInvalidationInTheSameProcessRegeneratesTheValue() {
		$subject = WikiPage::newFromText( 'Foo' );
		$entityCache = $this->newEntityCache();
		$this->lookUpMulti( $entityCache, [ $subject ] );

		$entityCache->invalidate( $subject );
		$this->lookUpMulti( $entityCache, [ $subject ], $batches );

		$this->assertSame( [ [ 'Foo' ] ], $batches );
	}

	public function testLookUpAfterClearingTheProcessCacheSeesAnInvalidationByAnotherProcess() {
		$subject = WikiPage::newFromText( 'Foo' );
		$entityCache = $this->newEntityCache();
		$this->lookUp( $entityCache, $subject, 'outdated' );
		$this->newEntityCache()->invalidate( $subject );

		$entityCache->clearProcessCache();

		$this->assertSame( 'regenerated', $this->lookUp( $entityCache, $subject, 'regenerated' ) );
	}

	public function testInvalidationByAnotherProcessIsSeenOnceTheInProcessCacheExpires() {
		$subject = WikiPage::newFromText( 'Foo' );
		$entityCache = $this->newEntityCache();
		$this->lookUp( $entityCache, $subject, 'outdated' );

		$this->newEntityCache()->invalidate( $subject );
		$this->waitSeconds( BagOStuff::TTL_PROC_SHORT + 1 );

		$this->assertSame(
			'regenerated',
			$this->lookUp( $entityCache, $subject, 'regenerated' )
		);
	}

	public function testInvalidatingAPageInvalidatesItsSubobjects() {
		$subobject = new WikiPage( 'Foo', NS_MAIN, '', 'Bar' );
		$this->lookUp( $this->newEntityCache(), $subobject, 'outdated' );

		$this->newEntityCache()->invalidate( WikiPage::newFromText( 'Foo' ) );

		$this->assertSame(
			'regenerated',
			$this->lookUp( $this->newEntityCache(), $subobject, 'regenerated' )
		);
	}

	public function testInvalidateAcceptsATitle() {
		$subject = WikiPage::newFromText( 'Foo' );
		$this->lookUp( $this->newEntityCache(), $subject, 'outdated' );

		$this->newEntityCache()->invalidate( $subject->getTitle() );

		$this->assertSame(
			'regenerated',
			$this->lookUp( $this->newEntityCache(), $subject, 'regenerated' )
		);
	}

	public function testInvalidatingAPageKeepsOtherPagesCached() {
		$subject = WikiPage::newFromText( 'Foo' );
		$this->lookUp( $this->newEntityCache(), $subject, 'cached' );

		$this->newEntityCache()->invalidate( WikiPage::newFromText( 'Bar' ) );

		$this->assertSame(
			'cached',
			$this->lookUp( $this->newEntityCache(), $subject, 'regenerated' )
		);
	}

	public function testInvalidatingAPageKeepsTheValuesOfOtherPagesInMemory() {
		$this->backend = new EmptyBagOStuff();
		$subject = WikiPage::newFromText( 'Foo' );
		$entityCache = $this->newEntityCache();
		$this->lookUp( $entityCache, $subject, 'cached' );

		$entityCache->invalidate( WikiPage::newFromText( 'Bar' ) );

		$this->assertSame(
			'cached',
			$this->lookUp( $entityCache, $subject, 'regenerated' )
		);
	}

	public function testValueGeneratedRightAfterAnInvalidationIsCached() {
		$subject = WikiPage::newFromText( 'Foo' );
		$this->lookUp( $this->newEntityCache(), $subject, 'outdated' );
		$this->newEntityCache()->invalidate( $subject );
		$this->lookUp( $this->newEntityCache(), $subject, 'regenerated' );

		$this->assertSame(
			'regenerated',
			$this->lookUp( $this->newEntityCache(), $subject, 'regenerated again' )
		);
	}

	public function testValueGeneratedWhileAnotherProcessInvalidatesThePageIsNotCached() {
		$subject = WikiPage::newFromText( 'Foo' );
		$this->lookUp( $this->newEntityCache(), $subject, 'outdated' );
		$this->newEntityCache()->invalidate( $subject );

		$this->lookUpWith( $this->newEntityCache(), $subject, $this->invalidateDuringGeneration( $subject ) );

		$this->assertSame(
			'regenerated',
			$this->lookUp( $this->newEntityCache(), $subject, 'regenerated' )
		);
	}

	public function testFirstValueOfAPageGeneratedWhileAnotherProcessInvalidatesItIsNotCached() {
		$subject = WikiPage::newFromText( 'Foo' );

		$this->lookUpWith( $this->newEntityCache(), $subject, $this->invalidateDuringGeneration( $subject ) );

		$this->assertSame(
			'regenerated',
			$this->lookUp( $this->newEntityCache(), $subject, 'regenerated' )
		);
	}

	public function testMultiLookUpGeneratesOnlyTheMissingValues() {
		$this->lookUp( $this->newEntityCache(), WikiPage::newFromText( 'Foo' ), 'cached Foo' );

		$values = $this->lookUpMulti(
			$this->newEntityCache(),
			[ WikiPage::newFromText( 'Foo' ), WikiPage::newFromText( 'Bar' ) ],
			$batches
		);

		$this->assertSame( [ 'Foo' => 'cached Foo', 'Bar' => 'generated Bar' ], $values );
		$this->assertSame( [ [ 'Bar' ] ], $batches );
	}

	public function testMultiLookUpRegeneratesOnlyTheValuesOfInvalidatedSubjects() {
		$subjects = [ WikiPage::newFromText( 'Foo' ), WikiPage::newFromText( 'Bar' ), WikiPage::newFromText( 'Baz' ) ];
		$this->lookUpMulti( $this->newEntityCache(), $subjects );

		$this->newEntityCache()->invalidate( WikiPage::newFromText( 'Bar' ) );
		$this->lookUpMulti( $this->newEntityCache(), $subjects, $batches );

		$this->assertSame( [ [ 'Bar' ] ], $batches );
	}

	public function testMultiLookUpOfAPageAndItsSubobjectReadsEachCacheKeyOnce() {
		$this->backend = new class extends HashBagOStuff {
			protected function doGetMulti( array $keys, $flags = 0 ) {
				// SqlBagOStuff fails on a duplicate key
				if ( count( $keys ) !== count( array_unique( $keys ) ) ) {
					throw new LogicException( 'Duplicate key read' );
				}

				return parent::doGetMulti( $keys, $flags );
			}
		};

		$values = $this->lookUpMulti(
			$this->newEntityCache(),
			[ WikiPage::newFromText( 'Foo' ), new WikiPage( 'Foo', NS_MAIN, '', 'Bar' ) ]
		);

		$this->assertSame( [ 'Foo' => 'generated Foo', 'Foo#Bar' => 'generated Foo#Bar' ], $values );
	}

	public function testMultiLookUpOfAPageAndItsSubobjectCachesBoth() {
		$subjects = [ WikiPage::newFromText( 'Foo' ), new WikiPage( 'Foo', NS_MAIN, '', 'Bar' ) ];
		$this->lookUpMulti( $this->newEntityCache(), $subjects );
		$batches = [];

		$this->lookUpMulti( $this->newEntityCache(), $subjects, $batches );

		$this->assertSame( [], $batches );
	}

	public function testRepeatedMultiLookUpInAProcessIsServedFromMemory() {
		$this->backend = new EmptyBagOStuff();
		$subjects = [ WikiPage::newFromText( 'Foo' ), WikiPage::newFromText( 'Bar' ) ];
		$entityCache = $this->newEntityCache();
		$this->lookUpMulti( $entityCache, $subjects );
		$batches = [];

		$this->lookUpMulti( $entityCache, $subjects, $batches );

		$this->assertSame( [], $batches );
	}

	public function testFetchReturnsFalseWhenNothingIsCached() {
		$subject = WikiPage::newFromText( 'Foo' );

		$this->assertFalse( $this->newEntityCache()->fetch( $subject, $this->makeKey( $subject ) ) );
	}

	public function testFetchReturnsAValueCachedByAnotherProcess() {
		$subject = WikiPage::newFromText( 'Foo' );
		$this->lookUp( $this->newEntityCache(), $subject, 'cached' );

		$this->assertSame( 'cached', $this->newEntityCache()->fetch( $subject, $this->makeKey( $subject ) ) );
	}

	public function testFetchReturnsACachedNull() {
		$subject = WikiPage::newFromText( 'Foo' );
		$this->lookUp( $this->newEntityCache(), $subject, null );

		$this->assertNull( $this->newEntityCache()->fetch( $subject, $this->makeKey( $subject ) ) );
	}

	public function testFetchReturnsFalseOnceTheSubjectIsInvalidated() {
		$subject = WikiPage::newFromText( 'Foo' );
		$this->lookUp( $this->newEntityCache(), $subject, 'cached' );

		$this->newEntityCache()->invalidate( $subject );

		$this->assertFalse( $this->newEntityCache()->fetch( $subject, $this->makeKey( $subject ) ) );
	}

}
