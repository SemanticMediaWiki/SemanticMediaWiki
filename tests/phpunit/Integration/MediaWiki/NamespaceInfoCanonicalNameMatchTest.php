<?php

namespace SMW\Tests\Integration\MediaWiki;

use SMW\NamespaceManager;
use SMW\ApplicationFactory;
use SMW\Settings;
use SMW\Tests\Utils\MwHooksHandler;

/**
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class NamespaceInfoCanonicalNameMatchTest extends \PHPUnit_Framework_TestCase {

	private $mwHooksHandler;

	protected function setUp() {
		parent::setUp();

		$this->mwHooksHandler = new MwHooksHandler();
	}

	public function tearDown() {
		$this->mwHooksHandler->restoreListedHooks();

		parent::tearDown();
	}

	public function testRunNamespaceManagerWithNoConstantsDefined() {

		$this->mwHooksHandler->deregisterListedHooks();

		$default = [
			'smwgNamespacesWithSemanticLinks' => [],
			'wgNamespacesWithSubpages' => [],
			'wgExtraNamespaces'   => [],
			'wgNamespaceAliases'  => [],
			'wgContentNamespaces' => [],
			'wgNamespacesToBeSearchedDefault' => [],
			'wgLanguageCode'      => 'en'
		];

		$instance = $this->getMockBuilder( '\SMW\NamespaceManager' )
			->setMethods( [ 'isDefinedConstant' ] )
			->getMock();

		$instance->expects( $this->atLeastOnce() )
			->method( 'isDefinedConstant' )
			->will( $this->returnValue( false ) );

		$instance->init( $default );
	}

	public function testCanonicalNames() {

		$this->mwHooksHandler->deregisterListedHooks();
		$namespaceInfo = ApplicationFactory::getInstance()->singleton( 'NamespaceInfo' );

		$count = 0;
		$index = NamespaceManager::buildNamespaceIndex( Settings::newFromGlobals()->get( 'smwgNamespaceIndex' ) );
		$names = NamespaceManager::getCanonicalNames();

		$this->assertInternalType( 'array', $names );
		$this->assertInternalType( 'array', $index );

		foreach ( $index as $ns => $idx ) {

			$mwNamespace = $namespaceInfo->getCanonicalName( $idx );

			if ( $mwNamespace && isset( $names[$idx] ) ) {
				$this->assertEquals( $mwNamespace, $names[$idx] );
				$count++;
			}
		}

		$this->assertCount(
			$count,
			$names,
			"Asserts that expected amount of cannonical names have been verified"
		);
	}

}
