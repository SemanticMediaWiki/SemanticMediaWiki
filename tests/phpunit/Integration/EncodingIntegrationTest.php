<?php

namespace SMW\Tests\Integration;

use SMW\BaseTemplateToolbox;
use SMW\ExtensionContext;
use SMW\Settings;

use Title;

/**
 * @covers \SMW\BaseTemplateToolbox
 * @covers \SMWInfolink
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 * @group semantic-mediawiki-integration
 * @group mediawiki-databaseless
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class EncodingIntegrationTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider baseTemplateToolboxDataProvider
	 *
	 * @since  1.9
	 */
	public function testBaseTemplateToolboxURLEncoding( $setup, $expected ) {

		$toolbox  = '';

		$context = new ExtensionContext();
		$context->getDependencyBuilder()->getContainer()->registerObject( 'Settings', Settings::newFromArray( $setup['settings'] ) );

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

		$mockSkin = $this->getMockBuilder( '\Skin' )
			->disableOriginalConstructor()
			->getMock();

		$mockSkin->expects( $this->atLeastOnce() )
			->method( 'getTitle' )
			->will( $this->returnValue( Title::newFromText( $text, NS_MAIN ) ) );

		$mockSkin->expects( $this->atLeastOnce() )
			->method( 'getContext' )
			->will( $this->returnValue( new \RequestContext() ) );

		$mockSkinTemplate = $this->getMockBuilder( '\SkinTemplate' )
			->disableOriginalConstructor()
			->getMock();

		$mockSkinTemplate->expects( $this->atLeastOnce() )
			->method( 'getSkin' )
			->will( $this->returnValue( $mockSkin ) );

		$mockSkinTemplate->data['isarticle'] = true;

		return array( 'settings' => $settings, 'skinTemplate' => $mockSkinTemplate );
	}

}
