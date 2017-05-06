<?php

namespace SMW\Tests\Integration\MediaWiki;

use MWNamespace;
use SMW\NamespaceManager;
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
class MWNamespaceCanonicalNameMatchTest extends \PHPUnit_Framework_TestCase {

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

		$default = array(
			'smwgNamespacesWithSemanticLinks' => array(),
			'wgNamespacesWithSubpages' => array(),
			'wgExtraNamespaces'   => array(),
			'wgNamespaceAliases'  => array(),
			'wgContentNamespaces' => array(),
			'wgNamespacesToBeSearchedDefault' => array(),
			'wgLanguageCode'      => 'en'
		);

		$instance = $this->getMockBuilder( '\SMW\NamespaceManager' )
			->setConstructorArgs( array(
				&$default
			) )
			->setMethods( array( 'isDefinedConstant' ) )
			->getMock();

		$instance->expects( $this->atLeastOnce() )
			->method( 'isDefinedConstant' )
			->will( $this->returnValue( false ) );

		$instance->init();
	}

	public function testCanonicalNames() {

		$this->mwHooksHandler->deregisterListedHooks();

		$count = 0;
		$index = NamespaceManager::buildNamespaceIndex( Settings::newFromGlobals()->get( 'smwgNamespaceIndex' ) );
		$names = NamespaceManager::getCanonicalNames();

		$this->assertInternalType( 'array', $names );
		$this->assertInternalType( 'array', $index );

		foreach ( $index as $ns => $idx ) {

			$mwNamespace = MWNamespace::getCanonicalName( $idx );

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
