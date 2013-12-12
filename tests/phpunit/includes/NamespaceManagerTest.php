<?php

namespace SMW\Test;

use SMW\NamespaceManager;

/**
 * @covers \SMW\NamespaceManager
 *
 * @group SMW
 * @group SMWExtension
 * @group medium
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class NamespaceManagerTest extends SemanticMediaWikiTestCase {

	/**
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\NamespaceManager';
	}

	/**
	 * @since 1.9
	 *
	 * @return NamespaceManager
	 */
	private function newInstance( &$test = array(), $langCode = 'en' ) {

		$default = array(
			'smwgNamespacesWithSemanticLinks' => array(),
			'wgNamespacesWithSubpages' => array(),
			'wgExtraNamespaces'  => array(),
			'wgNamespaceAliases' => array(),
			'wgLanguageCode'     => $langCode
		);

		$test = array_merge( $default, $test );

		$smwBasePath = __DIR__ . '../../../..';

		return new NamespaceManager( $test, $smwBasePath );
	}

	/**
	 * @since 1.9
	 */
	public function testCanConstruct() {
		$this->assertInstanceOf( $this->getClass(), $this->newInstance() );
	}

	/**
	 * @since 1.9
	 */
	public function testExecution() {

		$test = array();

		$this->newInstance( $test )->run();
		$this->assertNotEmpty( $test );

	}

	/**
	 * @since 1.9
	 */
	public function testExecutionWithIncompleteConfiguration() {

		$test = array(
			'wgExtraNamespaces'  => '',
			'wgNamespaceAliases' => ''
		);

		$this->newInstance( $test )->run();
		$this->assertNotEmpty( $test );

	}

	/**
	 * @since 1.9
	 */
	public function testExecutionWithLanguageFallback() {

		$test = array();

		$this->newInstance( $test, 'foo' )->run();
		$this->assertNotEmpty( $test );

	}

	/**
	 * @since 1.9
	 */
	public function testGetCanonicalNames() {

		$result = NamespaceManager::getCanonicalNames();

		$this->assertInternalType( 'array', $result );
		$this->assertCount( 6, $result );

	}

	/**
	 * @since 1.9
	 */
	public function testBuildNamespaceIndex() {
		$this->assertInternalType( 'array', NamespaceManager::buildNamespaceIndex( 100 ) );
	}

	/**
	 * @since 1.9
	 */
	public function testInitCustomNamespace() {

		$test = array();
		NamespaceManager::initCustomNamespace( $test );

		$this->assertNotEmpty( $test );
		$this->assertEquals(
			100,
			$test['smwgNamespaceIndex'],
			'Asserts that smwgNamespaceIndex is being set to a default index'
		);

	}

}
