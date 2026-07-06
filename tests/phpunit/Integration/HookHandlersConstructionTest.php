<?php

namespace SMW\Tests\Integration;

use MediaWiki\MediaWikiServices;
use MediaWikiIntegrationTestCase;

/**
 * Walks the HookHandlers section of extension.json and constructs each entry
 * via MediaWikiServices' ObjectFactory. Catches typos in service names,
 * constructor-signature drift, and missing autoload entries deterministically
 * on every CI run.
 *
 * @coversNothing
 * @group SMW
 * @group SMWExtension
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 7.0.0
 */
class HookHandlersConstructionTest extends MediaWikiIntegrationTestCase {

	/**
	 * @dataProvider hookHandlersProvider
	 * @since 7.0.0
	 */
	public function testHandlerConstructs( string $id, array $spec ): void {
		if ( $id === 'placeholder' ) {
			$this->markTestSkipped( 'No HookHandlers entries in extension.json yet.' );
		}

		$objectFactory = MediaWikiServices::getInstance()->getObjectFactory();
		$handler = $objectFactory->createObject( $spec );
		$this->assertIsObject( $handler, "HookHandlers[$id] failed to construct" );
	}

	/**
	 * @since 7.0.0
	 */
	public function hookHandlersProvider(): array {
		$json = json_decode(
			file_get_contents( __DIR__ . '/../../../extension.json' ),
			true
		);
		$cases = [];
		foreach ( $json['HookHandlers'] ?? [] as $id => $spec ) {
			$cases[$id] = [ $id, $spec ];
		}
		if ( $cases === [] ) {
			$cases['placeholder'] = [ 'placeholder', [ 'class' => self::class ] ];
		}
		return $cases;
	}
}
