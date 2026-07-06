<?php

namespace SMW\Tests\Unit;

use MediaWiki\HookContainer\HookContainer;
use PHPUnit\Framework\TestCase;
use SMW\Exception\SettingNotFoundException;
use SMW\Exception\SettingsAlreadyLoadedException;
use SMW\Listener\ChangeListener\ChangeListener;
use SMW\Settings;
use SMW\Tests\Utils\SilenceUserDeprecationTrait;

/**
 * @covers \SMW\Settings
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 1.9
 *
 * @author mwjames
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class SettingsTest extends TestCase {

	use SilenceUserDeprecationTrait;

	private $hookContainer;

	protected function setUp(): void {
		parent::setUp();

		$this->hookContainer = $this->getMockBuilder( HookContainer::class )
			->disableOriginalConstructor()
			->getMock();
	}

	protected function tearDown(): void {
		parent::tearDown();
	}

	/**
	 * @dataProvider settingsProvider
	 */
	public function testCanConstruct( array $settings ) {
		$instance = Settings::newFromArray( $settings );

		$this->assertInstanceOf(
			Settings::class,
			$instance
		);

		$this->assertFalse(
			$instance === Settings::newFromArray( $settings )
		);
	}

	/**
	 * @dataProvider settingsProvider
	 */
	public function testGet( array $settings ) {
		$instance = Settings::newFromArray( $settings );

		foreach ( $settings as $name => $value ) {
			$this->assertEquals( $value, $instance->get( $name ) );
		}

		$this->assertTrue( true );
	}

	public function testUnknownSettingThrowsException() {
		$instance = Settings::newFromArray( [ 'Foo' => 'bar' ] );

		$this->expectException( SettingNotFoundException::class );
		$instance->get( 'foo' );
	}

	public function testSafeGetOnUnknownSetting() {
		$instance = Settings::newFromArray( [ 'Foo' => 'bar' ] );

		$this->assertFalse(
			$instance->safeGet( 'foo', false )
		);
	}

	public function testRegisterChangeListener() {
		$changeListener = $this->getMockBuilder( ChangeListener::class )
			->disableOriginalConstructor()
			->getMock();

		$changeListener->expects( $this->once() )
			->method( 'canTrigger' )
			->with( 'Foo' )
			->willReturn( true );

		$changeListener->expects( $this->once() )
			->method( 'setAttrs' )
			->with( [ 'Foo' => 'Bar' ] );

		$changeListener->expects( $this->once() )
			->method( 'trigger' )
			->with( 'Foo' );

		$instance = Settings::newFromArray( [] );
		$instance->registerChangeListener( $changeListener );

		$instance->set( 'Foo', 'Bar' );
		$instance->clearChangeListeners();
	}

	/**
	 * @dataProvider settingsProvider
	 */
	public function testSet( array $settings ) {
		$instance = Settings::newFromArray( [] );

		foreach ( $settings as $name => $value ) {
			$instance->set( $name, $value );
			$this->assertEquals( $value, $instance->get( $name ) );
		}

		$this->assertTrue( true );
	}

	/**
	 * @dataProvider globalsSettingsProvider
	 */
	public function testNewFromGlobals( $setting ) {
		$instance = new Settings();

		$instance->setHookContainer(
			$this->hookContainer
		);

		$instance->loadFromGlobals();

		$this->assertTrue(
			$instance->has( $setting ),
			"Failed asserting that `{$setting}` exists. It could be that `{$setting}`\n" .
			"is invoked by the `LocalSettings.php` or some parameter is using the `smwg` prefix.\n"
		);
	}

	public function testReloadAttemptThrowsException() {
		$instance = new Settings();

		$instance->setHookContainer(
			$this->hookContainer
		);

		$instance->loadFromGlobals();

		$this->expectException( SettingsAlreadyLoadedException::class );
		$instance->loadFromGlobals();
	}

	/**
	 * Both legacy SMW_* integer constants and the new string / array-of-strings
	 * form must produce identical internal state once Settings::loadFromGlobals()
	 * routes the registered keys through LegacyConstantNormalizer (#6586).
	 */
	public function testLoadFromGlobals_factboxStringForm_internalRepresentation() {
		$savedShow = $GLOBALS['smwgShowFactbox'] ?? null;
		$savedFeatures = $GLOBALS['smwgFactboxFeatures'] ?? null;

		try {
			$GLOBALS['smwgShowFactbox'] = 'nonempty';
			$GLOBALS['smwgFactboxFeatures'] = [ 'cache', 'purge-refresh' ];

			$instance = new Settings();
			$instance->setHookContainer( $this->hookContainer );
			$instance->loadFromGlobals();

			$this->assertSame( SMW_FACTBOX_NONEMPTY, $instance->get( 'smwgShowFactbox' ) );
			$this->assertTrue( $instance->isFlagSet( 'smwgFactboxFeatures', SMW_FACTBOX_CACHE ) );
			$this->assertTrue( $instance->isFlagSet( 'smwgFactboxFeatures', SMW_FACTBOX_PURGE_REFRESH ) );
			$this->assertFalse( $instance->isFlagSet( 'smwgFactboxFeatures', SMW_FACTBOX_DISPLAY_SUBOBJECT ) );
		} finally {
			$GLOBALS['smwgShowFactbox'] = $savedShow;
			$GLOBALS['smwgFactboxFeatures'] = $savedFeatures;
		}
	}

	/**
	 * @dataProvider provideFlagBoundaryDualAcceptCases
	 */
	public function testLoadFromGlobals_flagDualAccept( string $key, mixed $userValue, mixed $expected ) {
		$saved = $GLOBALS[$key] ?? null;

		try {
			$GLOBALS[$key] = $userValue;

			$instance = new Settings();
			$instance->setHookContainer( $this->hookContainer );
			// Legacy-form rows in the provider deliberately trip the
			// LegacyConstantNormalizer deprecation; swallow the PHP-level
			// user-deprecation so CI stderr stays clean. The deprecation
			// emission itself is covered in LegacyConstantNormalizerTest.
			$this->withSilencedUserDeprecation( static fn () => $instance->loadFromGlobals() );

			$this->assertSame( $expected, $instance->get( $key ) );
		} finally {
			$GLOBALS[$key] = $saved;
		}
	}

	/**
	 * Each of the 13 SMW bitmask settings in PR C must accept both the legacy
	 * SMW_* integer-OR form and the new array-of-strings form, producing
	 * identical internal integer state (#6586).
	 */
	public function provideFlagBoundaryDualAcceptCases(): array {
		return [
			'smwgQFeatures array'             => [ 'smwgQFeatures', [ 'property', 'category' ], SMW_PROPERTY_QUERY | SMW_CATEGORY_QUERY ],
			'smwgQFeatures legacy'            => [ 'smwgQFeatures', SMW_PROPERTY_QUERY | SMW_CATEGORY_QUERY, SMW_PROPERTY_QUERY | SMW_CATEGORY_QUERY ],
			'smwgQSortFeatures array'         => [ 'smwgQSortFeatures', [ 'sort', 'random' ], SMW_QSORT | SMW_QSORT_RANDOM ],
			'smwgQSortFeatures legacy'        => [ 'smwgQSortFeatures', SMW_QSORT | SMW_QSORT_RANDOM, SMW_QSORT | SMW_QSORT_RANDOM ],
			'smwgSparqlQFeatures array'       => [ 'smwgSparqlQFeatures', [ 'redirects', 'subproperties' ], SMW_SPARQL_QF_REDI | SMW_SPARQL_QF_SUBP ],
			'smwgCategoryFeatures array'      => [ 'smwgCategoryFeatures', [ 'redirect', 'instance' ], SMW_CAT_REDIRECT | SMW_CAT_INSTANCE ],
			'smwgBrowseFeatures array'        => [ 'smwgBrowseFeatures', [ 'toolbox-link', 'use-api' ], SMW_BROWSE_TLINK | SMW_BROWSE_USE_API ],
			'smwgAdminFeatures array'         => [ 'smwgAdminFeatures', [ 'refresh', 'setup' ], SMW_ADM_REFRESH | SMW_ADM_SETUP ],
			'smwgParserFeatures array'        => [ 'smwgParserFeatures', [ 'strict', 'inline-errors' ], SMW_PARSER_STRICT | SMW_PARSER_INL_ERROR ],
			'smwgDVFeatures array'            => [ 'smwgDVFeatures', [ 'provider-redirect', 'preferred-label' ], SMW_DV_PROV_REDI | SMW_DV_PPLB ],
			'smwgFulltextSearchIDT array'     => [ 'smwgFulltextSearchIndexableDataTypes', [ 'blob', 'uri' ], SMW_FT_BLOB | SMW_FT_URI ],
			'smwgRemoteReqFeatures array'     => [ 'smwgRemoteReqFeatures', [ 'send-response' ], SMW_REMOTE_REQ_SEND_RESPONSE ],
			'smwgExperimentalFeatures array'  => [ 'smwgExperimentalFeatures', [ 'queryresult-prefetch' ], SMW_QUERYRESULT_PREFETCH ],
			'smwgFieldTypeFeatures array'     => [ 'smwgFieldTypeFeatures', [ 'char-nocase', 'char-long' ], SMW_FIELDT_CHAR_NOCASE | SMW_FIELDT_CHAR_LONG ],
			'smwgFieldTypeFeatures false'     => [ 'smwgFieldTypeFeatures', false, false ],
			'smwgQConceptFeatures array'      => [ 'smwgQConceptFeatures', [ 'property', 'namespace' ], SMW_PROPERTY_QUERY | SMW_NAMESPACE_QUERY ],
			'smwgQueryProfiler array'         => [ 'smwgQueryProfiler', [ 'parameters', 'duration' ], SMW_QPRFL_PARAMS | SMW_QPRFL_DUR ],
			'smwgQueryProfiler legacy'        => [ 'smwgQueryProfiler', SMW_QPRFL_PARAMS | SMW_QPRFL_DUR, SMW_QPRFL_PARAMS | SMW_QPRFL_DUR ],
			'smwgQueryProfiler false'         => [ 'smwgQueryProfiler', false, false ],
			'smwgQueryProfiler empty array'   => [ 'smwgQueryProfiler', [], 0 ],
			'smwgQueryProfiler true legacy'   => [ 'smwgQueryProfiler', true, 0 ],
		];
	}

	/**
	 * @dataProvider provideEnumBoundaryDualAcceptCases
	 */
	public function testLoadFromGlobals_enumDualAccept( string $key, mixed $userValue, mixed $expected ) {
		$saved = $GLOBALS[$key] ?? null;

		try {
			$GLOBALS[$key] = $userValue;

			$instance = new Settings();
			$instance->setHookContainer( $this->hookContainer );
			$this->withSilencedUserDeprecation( static fn () => $instance->loadFromGlobals() );

			$this->assertSame( $expected, $instance->get( $key ) );
		} finally {
			$GLOBALS[$key] = $saved;
		}
	}

	/**
	 * Each of the 5 SMW enum settings in PR B must accept both the legacy integer
	 * constant and the new string form, producing identical internal state (#6586).
	 */
	public function provideEnumBoundaryDualAcceptCases(): array {
		return [
			'smwgShowFactboxEdit string'           => [ 'smwgShowFactboxEdit', 'nonempty', SMW_FACTBOX_NONEMPTY ],
			'smwgShowFactboxEdit legacy'           => [ 'smwgShowFactboxEdit', SMW_FACTBOX_NONEMPTY, SMW_FACTBOX_NONEMPTY ],
			'smwgQEqualitySupport string'          => [ 'smwgQEqualitySupport', 'full', SMW_EQ_FULL ],
			'smwgQEqualitySupport legacy'          => [ 'smwgQEqualitySupport', SMW_EQ_FULL, SMW_EQ_FULL ],
			'smwgQConceptCaching string'           => [ 'smwgQConceptCaching', 'all', CONCEPT_CACHE_ALL ],
			'smwgQConceptCaching legacy'           => [ 'smwgQConceptCaching', CONCEPT_CACHE_ALL, CONCEPT_CACHE_ALL ],
			'smwgSparqlRepositoryFeatures string'  => [ 'smwgSparqlRepositoryFeatures', 'connection-ping', SMW_SPARQL_CONNECTION_PING ],
			'smwgSparqlRepositoryFeatures legacy'  => [ 'smwgSparqlRepositoryFeatures', SMW_SPARQL_CONNECTION_PING, SMW_SPARQL_CONNECTION_PING ],
			'smwgResultFormatsFeatures string'     => [ 'smwgResultFormatsFeatures', 'template-outsep', SMW_RF_TEMPLATE_OUTSEP ],
			'smwgResultFormatsFeatures legacy'     => [ 'smwgResultFormatsFeatures', SMW_RF_TEMPLATE_OUTSEP, SMW_RF_TEMPLATE_OUTSEP ],
		];
	}

	/**
	 * Locks in the current behaviour of legacy SMW_* integer-constant config values
	 * for the factbox settings, ahead of the string-config migration (#6586). The
	 * same assertions must hold after the normalizer is wired in.
	 */
	public function testLoadFromGlobals_factboxLegacyConstants_internalRepresentation() {
		$savedShow = $GLOBALS['smwgShowFactbox'] ?? null;
		$savedFeatures = $GLOBALS['smwgFactboxFeatures'] ?? null;

		try {
			$GLOBALS['smwgShowFactbox'] = SMW_FACTBOX_NONEMPTY;
			$GLOBALS['smwgFactboxFeatures'] = SMW_FACTBOX_CACHE | SMW_FACTBOX_PURGE_REFRESH;

			$instance = new Settings();
			$instance->setHookContainer( $this->hookContainer );
			$this->withSilencedUserDeprecation( static fn () => $instance->loadFromGlobals() );

			$this->assertSame( SMW_FACTBOX_NONEMPTY, $instance->get( 'smwgShowFactbox' ) );
			$this->assertTrue( $instance->isFlagSet( 'smwgFactboxFeatures', SMW_FACTBOX_CACHE ) );
			$this->assertTrue( $instance->isFlagSet( 'smwgFactboxFeatures', SMW_FACTBOX_PURGE_REFRESH ) );
			$this->assertFalse( $instance->isFlagSet( 'smwgFactboxFeatures', SMW_FACTBOX_DISPLAY_SUBOBJECT ) );
		} finally {
			$GLOBALS['smwgShowFactbox'] = $savedShow;
			$GLOBALS['smwgFactboxFeatures'] = $savedFeatures;
		}
	}

	public function testMung() {
		$instance = Settings::newFromArray( [ 'Foo' => 123 ] );

		$this->assertEquals(
			'123bar',
			$instance->mung( 'Foo', 'bar' )
		);
	}

	public function testMungOnUnknownTypeThrowsException() {
		$instance = Settings::newFromArray( [ 'Foo' => 123 ] );

		$this->expectException( '\RuntimeException' );
		$instance->mung( 'Foo', 456 );
	}

	/**
	 * Provides sample data to be tested
	 *
	 * @return array
	 */
	public function settingsProvider() {
		return [ [ [
			'baz' => 'BAH',
			'bar' => [ '9001' ],
			'foo' => [ '9001', [ 9001, 4.2 ] ],
			'~[,,_,,]:3' => [ 9001, 4.2 ],
		] ] ];
	}

	/**
	 * Provides and collects individual smwg* settings
	 */
	public function globalsSettingsProvider() {
		$settings = array_intersect_key( $GLOBALS,
			array_flip( preg_grep( '/^smwg/', array_keys( $GLOBALS ) ) )
		);

		unset( $settings['smwgDeprecationNotices'] );

		foreach ( $settings as $key => $value ) {
			yield [ $key ];
		}
	}

}
