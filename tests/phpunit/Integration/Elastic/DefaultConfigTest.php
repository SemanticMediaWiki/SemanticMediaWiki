<?php

namespace SMW\Tests\Integration\Elastic;

/**
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class DefaultConfigTest extends \PHPUnit_Framework_TestCase {

	private $contents;

	protected function setUp() {
		parent::setUp();
		$this->contents = file_get_contents( $GLOBALS['smwgIP'] . 'data/elastic/default-profile.json' );
	}

	public function testJSONFile() {

		$this->assertInternalType(
			'string',
			json_encode( json_decode( $this->contents ) )
		);
	}

	/**
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

		$defaultSettings = include $GLOBALS['smwgIP'] . 'DefaultSettings.php';

		foreach ( $defaultSettings['smwgElasticsearchConfig'] as $key => $configs ) {

			if ( $key === 'index_def' ) {
				continue;
			}

			foreach ( $configs as $k => $v ) {
				yield $key => [ $key, $k, $v ];
			}
		}
	}

}
