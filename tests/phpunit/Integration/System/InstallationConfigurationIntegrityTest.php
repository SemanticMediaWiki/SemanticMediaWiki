<?php

namespace SMW\Tests\System;

use SMW\ApplicationFactory;
use SMW\Tests\Utils\GlobalsProvider;

/**
 * @group SMW
 * @group SMWExtension
 *
 * @group semantic-mediawiki-system
 * @group mediawiki-databaseless
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class InstallationGlobalsProviderIntegrityTest extends \PHPUnit_Framework_TestCase {

	private $globalsProvider;
	private $applicationFactory;

	protected function setUp() {
		parent::setUp();

		$this->globalsProvider = GlobalsProvider::getInstance();
		$this->applicationFactory = ApplicationFactory::getInstance();
	}

	protected function tearDown() {
		$this->globalsProvider->clear();
		$this->applicationFactory->clear();

		parent::tearDown();
	}

	public function testNamespaceSettingOnExampleIfSet() {

		$expected = 'http://example.org/id/';

		if ( $this->globalsProvider->get( 'smwgNamespace' ) !== $expected ) {
			$this->markTestSkipped( "Skip test due to missing {$expected} setting" );
		}

		$this->assertSame(
			$this->globalsProvider->get( 'smwgNamespace' ),
			$this->applicationFactory->getSettings()->get( 'smwgNamespace' )
		);
	}

	/**
	 * @dataProvider smwgNamespacesWithSemanticLinksProvider
	 */
	public function testNamespacesWithSemanticLinksOnTravisCustomNamespace( $type, $container ) {

		if ( !defined( 'NS_TRAVIS' ) ) {
			$this->markTestSkipped( 'Test can only be executed with a specified NS_TRAVIS' );
		}

		$namespace = NS_TRAVIS;
		$extraNamespaces = $this->globalsProvider->get( 'wgExtraNamespaces' );

		$this->assertTrue(
			isset( $extraNamespaces[$namespace] )
		);

		$foundNamespaceEntry = false;

		foreach ( $container as $key => $value ) {
			if ( $key === $namespace ) {
				$foundNamespaceEntry = true;
				break;
			}
		}

		$this->assertTrue(
			$foundNamespaceEntry,
			"Asserts that smwgNamespacesWithSemanticLinks retrieved from {$type} contains the expected {$namespace} NS"
		);
	}

	/**
	 * @since 1.9
	 */
	public function smwgNamespacesWithSemanticLinksProvider() {

		$provider = [];

		$provider[] = [
			'GLOBALS',
			GlobalsProvider::getInstance()->get( 'smwgNamespacesWithSemanticLinks' )
		];

		$provider[] = [
			'Settings',
			ApplicationFactory::getInstance()->getSettings()->get( 'smwgNamespacesWithSemanticLinks' )
		];

		return $provider;
	}

}
