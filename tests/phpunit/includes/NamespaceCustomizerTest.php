<?php

namespace SMW\Test;

use SMW\NamespaceCustomizer;
use SMW\ExtensionContext;

/**
 * @covers \SMW\NamespaceCustomizer
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
class NamespaceCustomizerTest extends SemanticMediaWikiTestCase {

	/**
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\NamespaceCustomizer';
	}

	/**
	 * @since 1.9
	 *
	 * @return NamespaceCustomizer
	 */
	private function newInstance( &$test = array(), $context = null ) {

		$default = array(
			'smwgNamespacesWithSemanticLinks' => array(),
			'wgNamespacesWithSubpages' => array(),
			'wgExtraNamespaces'  => array(),
			'wgNamespaceAliases' => array(),
			'wgLanguageCode'     => 'en'
		);

		$test = array_merge( $default, $test );

		return new NamespaceCustomizer( $test, '../..' );
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

		$instance = $this->newInstance( $test );
		$instance->run();

		$this->assertNotEmpty( $test );

	}

}
