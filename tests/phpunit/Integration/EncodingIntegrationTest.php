<?php

namespace SMW\Tests\Integration;

use SMW\ApplicationFactory;
use SMW\MediaWiki\Hooks\BaseTemplateToolbox;
use Title;

/**
 * @covers \SMW\MediaWiki\Hooks\BaseTemplateToolbox
 * @covers \SMWInfolink
 *
 * @group SMW
 * @group SMWExtension
 *
 * @group semantic-mediawiki-integration
 * @group mediawiki-databaseless
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class EncodingIntegrationTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider baseTemplateToolboxDataProvider
	 */
	public function testBaseTemplateToolboxURLEncoding( $setup, $expected ) {

		$toolbox  = '';

		foreach ( $setup['settings'] as $key => $value) {
			ApplicationFactory::getInstance()->getSettings()->set( $key, $value );
		}

		$instance = new BaseTemplateToolbox( $setup['skinTemplate'], $toolbox );

		$instance->process();

		$this->assertContains(
			$expected,
			$toolbox['smw-browse']['href']
		);

		ApplicationFactory::clear();
	}

	public function baseTemplateToolboxDataProvider() {

		$specialName = str_replace( '%3A', ':',
			\SMW\UrlEncoder::encode( \SpecialPage::getTitleFor( 'Browse' )->getPrefixedText() )
		);

		$provider = array();

		$provider[] = array( $this->newBaseTemplateToolboxSetup( '2013/11/05' ), "$specialName/2013-2F11-2F05" );
		$provider[] = array( $this->newBaseTemplateToolboxSetup( '2013-06-30' ), "$specialName/2013-2D06-2D30" );
		$provider[] = array( $this->newBaseTemplateToolboxSetup( '2013$06&30' ), "$specialName/2013-2406-2630" );
		$provider[] = array( $this->newBaseTemplateToolboxSetup( '2013\Foo' ),   "$specialName/2013-5CFoo" );

		return $provider;
	}

	private function newBaseTemplateToolboxSetup( $text ) {

		$settings = array(
			'smwgNamespacesWithSemanticLinks' => array( NS_MAIN => true ),
			'smwgToolboxBrowseLink'           => true
		);

		$message = $this->getMockBuilder( '\Message' )
			->disableOriginalConstructor()
			->getMock();

		$skin = $this->getMockBuilder( '\Skin' )
			->disableOriginalConstructor()
			->getMock();

		$skin->expects( $this->atLeastOnce() )
			->method( 'getTitle' )
			->will( $this->returnValue( Title::newFromText( $text, NS_MAIN ) ) );

		$skin->expects( $this->atLeastOnce() )
			->method( 'msg' )
			->will( $this->returnValue( $message ) );

		$skinTemplate = $this->getMockBuilder( '\SkinTemplate' )
			->disableOriginalConstructor()
			->getMock();

		$skinTemplate->expects( $this->atLeastOnce() )
			->method( 'getSkin' )
			->will( $this->returnValue( $skin ) );

		$skinTemplate->data['isarticle'] = true;

		return array( 'settings' => $settings, 'skinTemplate' => $skinTemplate );
	}

}
