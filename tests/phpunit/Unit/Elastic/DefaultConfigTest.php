<?php

namespace SMW\Tests\Unit\Elastic;

use PHPUnit\Framework\TestCase;
use SMW\Exception\JSONParseException;
use SMW\Setup\ConfigBootstrap;

/**
 * @license GPL-2.0-or-later
 * @since 3.2
 *
 * @author mwjames
 */
class DefaultConfigTest extends TestCase {

	private $contents;

	protected function setUp(): void {
		parent::setUp();
		$this->contents = file_get_contents(
			$GLOBALS['smwgIP'] . 'data/elastic/default-profile.json'
		);
	}

	public function testJSONFileValidity() {
		$jsonParseException = new JSONParseException(
			$this->contents
		);

		$this->assertEmpty(
			'',
			$jsonParseException->getMessage()
		);
	}

	/**
	 * @depends testJSONFileValidity
	 *
	 * @dataProvider defaultSettingsProvider
	 */
	public function testComparePHP_JSONConfigKeys( $key, $k, $expected ) {
		$contents = json_decode( $this->contents, true );

		$this->assertEquals(
			$expected,
			$contents[$key][$k]
		);
	}

	public function defaultSettingsProvider() {
		// smwgElasticsearchConfig defaults now live in ConfigBootstrap. Seed
		// them into a temporary copy of $GLOBALS so the data provider can read
		// them without permanently mutating the real globals.
		$saved = $GLOBALS['smwgElasticsearchConfig'] ?? null;
		unset( $GLOBALS['smwgElasticsearchConfig'] );

		ConfigBootstrap::seedComputedDefaults();

		$elasticsearchConfig = $GLOBALS['smwgElasticsearchConfig'];

		// Restore to pre-provider state.
		if ( $saved === null ) {
			unset( $GLOBALS['smwgElasticsearchConfig'] );
		} else {
			$GLOBALS['smwgElasticsearchConfig'] = $saved;
		}

		foreach ( $elasticsearchConfig as $key => $configs ) {

			if ( $key === 'index_def' ) {
				continue;
			}

			foreach ( $configs as $k => $v ) {
				yield "$key-$k" => [ $key, $k, $v ];
			}
		}
	}

}
