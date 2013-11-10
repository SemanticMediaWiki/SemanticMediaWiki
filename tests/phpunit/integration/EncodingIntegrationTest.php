<?php

namespace SMW\Test;

use SMW\BaseTemplateToolbox;
use SMW\ExtensionContext;

/**
 * @covers \SMW\BaseTemplateToolbox
 * @covers \SMWInfolink
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class EncodingIntegrationTest extends SemanticMediaWikiTestCase {

	/**
	 * @return string|false
	 */
	public function getClass() {
		return false;
	}

	/**
	 * @dataProvider baseTemplateToolboxDataProvider
	 *
	 * @since  1.9
	 */
	public function testBaseTemplateToolboxURLEncoding( $setup, $expected ) {

		$toolbox  = '';

		$context = new ExtensionContext();
		$context->getDependencyBuilder()->getContainer()->registerObject( 'Settings', $this->newSettings( $setup['settings'] ) );

		$instance = new BaseTemplateToolbox( $setup['skinTemplate'], $toolbox );
		$instance->invokeContext( $context );

		$instance->process();

		$this->assertContains(
			$expected,
			$toolbox['smw-browse']['href'],
			'Asserts that process() returns an encoded URL'
		);

	}

	/**
	 * @return array
	 */
	public function baseTemplateToolboxDataProvider() {

		$provider = array();

		$provider[] = array( $this->newBaseTemplateToolboxSetup( '2013/11/05' ), 'Special:Browse/2013-2F11-2F05' );
		$provider[] = array( $this->newBaseTemplateToolboxSetup( '2013-06-30' ), 'Special:Browse/2013-2D06-2D30' );
		$provider[] = array( $this->newBaseTemplateToolboxSetup( '2013$06&30' ), 'Special:Browse/2013-2406-2630' );

		return $provider;
	}

	/**
	 * @return array
	 */
	private function newBaseTemplateToolboxSetup( $text ) {

		$settings = array(
			'smwgNamespacesWithSemanticLinks' => array( NS_MAIN => true ),
			'smwgToolboxBrowseLink'           => true
		);

		$mockSkin = $this->newMockBuilder()->newObject( 'Skin', array(
			'getTitle'   => $this->newTitle( NS_MAIN, $text ),
			'getContext' => $this->newContext()
		) );

		$mockSkinTemplate = $this->newMockBuilder()->newObject( 'SkinTemplate', array(
			'getSkin'  => $mockSkin,
		) );

		$mockSkinTemplate->data['isarticle'] = true;

		return array( 'settings' => $settings, 'skinTemplate' => $mockSkinTemplate );
	}

}
