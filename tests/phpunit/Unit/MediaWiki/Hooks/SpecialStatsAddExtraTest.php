<?php

namespace SMW\Tests\Unit\MediaWiki\Hooks;

use MediaWiki\Context\IContextSource;
use MediaWiki\MediaWikiServices;
use MediaWiki\Output\OutputPage;
use PHPUnit\Framework\TestCase;
use SMW\DataTypeRegistry;
use SMW\MediaWiki\Hooks\SpecialStatsAddExtra;
use SMW\Services\ServicesFactory as ApplicationFactory;
use SMW\Store;

/**
 * @covers \SMW\MediaWiki\Hooks\SpecialStatsAddExtra
 * @group smenatic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 1.9
 *
 * @author mwjames
 */
class SpecialStatsAddExtraTest extends TestCase {

	private function newStoreMock(): Store {
		return $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();
	}

	private function newDataTypeRegistryMock( array $knownTypeLabels = [] ): DataTypeRegistry {
		$registry = $this->createMock( DataTypeRegistry::class );
		$registry->method( 'getKnownTypeLabels' )->willReturn( $knownTypeLabels );
		return $registry;
	}

	protected function tearDown(): void {
		ApplicationFactory::clear();

		parent::tearDown();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			SpecialStatsAddExtra::class,
			new SpecialStatsAddExtra( $this->newStoreMock(), $this->newDataTypeRegistryMock() )
		);
	}

	/**
	 * @dataProvider statisticsDataProvider
	 */
	public function testProcess( $setup, $expected ) {
		if ( !defined( 'SMW_EXTENSION_LOADED' ) ) {
			$this->markTestSkipped( 'SMW_EXTENSION_LOADED is not defined in this environment' );
		}

		$store = $this->newStoreMock();
		$store->expects( $this->atLeastOnce() )
			->method( 'getStatistics' )
			->willReturn( $setup['statistics'] );

		$extraStats = $setup['extraStats'];

		$instance = new SpecialStatsAddExtra( $store, $this->newDataTypeRegistryMock() );

		$context = $this->newContext();

		$this->assertTrue(
			$instance->onSpecialStatsAddExtra( $extraStats, $context )
		);

		$this->assertTrue(
			$this->matchArray( $extraStats, $expected['statistics'] )
		);
	}

	public function testProcess_InjectedDataTypeRegistryDrivesDataTypeCount() {
		if ( !defined( 'SMW_EXTENSION_LOADED' ) ) {
			$this->markTestSkipped( 'SMW_EXTENSION_LOADED is not defined in this environment' );
		}

		$store = $this->newStoreMock();
		$store->expects( $this->atLeastOnce() )
			->method( 'getStatistics' )
			->willReturn( [
				'QUERY' => 2002,
				'QUERYFORMATS' => [ 'foo' => 9999 ],
			] );

		// The fake registry exposes a single type label. The handler must read
		// the count from the injected registry, not from the global
		// `DataTypeRegistry::getInstance()`.
		$instance = new SpecialStatsAddExtra(
			$store,
			$this->newDataTypeRegistryMock( [ 'Bar' ] )
		);

		$extraStats = [];
		$instance->onSpecialStatsAddExtra( $extraStats, $this->newContext() );

		$dataTypeEntry = $this->findStatEntryByCount( $extraStats, 1 );
		$this->assertNotNull(
			$dataTypeEntry,
			'expected an entry with number=1 corresponding to count( [ "Bar" ] )'
		);

		$queryEntry = $this->findStatEntryByCount( $extraStats, 2002 );
		$this->assertNotNull( $queryEntry, 'expected the QUERY entry' );

		$queryFormatEntry = $this->findStatEntryByCount( $extraStats, 9999 );
		$this->assertNotNull( $queryFormatEntry, 'expected the QUERYFORMATS "foo" entry' );
	}

	private function findStatEntryByCount( array $extraStats, int $number ): ?array {
		foreach ( $extraStats as $entries ) {
			foreach ( $entries as $entry ) {
				if ( is_array( $entry ) && ( $entry['number'] ?? null ) === $number ) {
					return $entry;
				}
			}
		}
		return null;
	}

	public function matchArray( array $matcher, $searchValue ) {
		foreach ( $matcher as $key => $value ) {

			if ( $searchValue === $key || $searchValue === $value ) {
				return true;
			}

			if ( is_array( $value ) ) {
				return $this->matchArray( $value, $searchValue );
			}
		}

		return $searchValue !== null ? false : true;
	}

	public function statisticsDataProvider() {
		$input = [
			'PROPUSES' => 1001
		];

		# 0
		$provider[] = [
			[
				'extraStats' => [],
				'statistics' => $input
			],
			[
				'statistics' => 1001
			]
		];

		# 1 unknown
		$provider[] = [
			[
				'extraStats' => [],
				'statistics' => [ 'Yeey' => 2002 ]
			],
			[
				'statistics' => null
			]
		];

		# 2 MW 1.21+
		$provider[] = [
			[
				'extraStats' => [],
				'statistics' => $input
			],
			[
				'statistics' => 1001
			]
		];

		# 3 MW 1.21+ - unknown
		$provider[] = [
			[
				'extraStats' => [],
				'statistics' => [ 'Quuxy' => 2002 ]
			],
			[
				'statistics' => null
			]
		];

		return $provider;
	}

	private function newContext(): IContextSource {
		$output = $this->getMockBuilder( OutputPage::class )
			->disableOriginalConstructor()
			->getMock();

		$language = MediaWikiServices::getInstance()->getLanguageFactory()->getLanguage( 'en' );

		$context = $this->createMock( IContextSource::class );
		$context->method( 'getOutput' )->willReturn( $output );
		$context->method( 'getLanguage' )->willReturn( $language );

		return $context;
	}

}
