<?php

namespace SMW\Tests\Unit;

use MediaWiki\MediaWikiServices;
use MediaWiki\Message\Message;
use MediaWiki\Output\OutputPage;
use MediaWiki\SpecialPage\SpecialPage;
use PHPUnit\Framework\TestCase;
use SMW\Encoder;
use SMW\MediaWiki\Hooks\SidebarBeforeOutput;
use SMW\Services\ServicesFactory as ApplicationFactory;

/**
 * @covers \SMW\MediaWiki\Hooks\SidebarBeforeOutput
 * @covers \SMW\Formatters\Infolink
 *
 * @group SMW
 * @group SMWExtension
 *
 * @group semantic-mediawiki-integration
 * @group mediawiki-databaseless
 *
 * @license GPL-2.0-or-later
 * @since 1.9
 *
 * @author mwjames
 */
class EncodingIntegrationTest extends TestCase {

	/**
	 * @dataProvider sidebarBeforeOutputDataProvider
	 */
	public function testSidebarBeforeOutputURLEncoding( $setup, $expected ) {
		$sidebar = [];

		foreach ( $setup['settings'] as $key => $value ) {
			ApplicationFactory::getInstance()->getSettings()->set( $key, $value );
		}

		$instance = new SidebarBeforeOutput(
			ApplicationFactory::getInstance()->getNamespaceExaminer()
		);

		$instance->setOptions(
			[
				'smwgBrowseFeatures' => $setup['settings']['smwgBrowseFeatures']
			]
		);

		$instance->process( $setup['skin'], $sidebar );

		$this->assertStringContainsString(
			$expected,
			$sidebar['TOOLBOX']['smwbrowselink']['href']
		);

		ApplicationFactory::clear();
	}

	public function sidebarBeforeOutputDataProvider() {
		$specialName = str_replace( '%3A', ':',
			Encoder::encode( SpecialPage::getTitleFor( 'Browse' )->getPrefixedText() )
		);

		$provider = [];

		$provider[] = [ $this->newSidebarBeforeOutputSetup( '2013/11/05' ), "$specialName/:2013-2F11-2F05" ];
		$provider[] = [ $this->newSidebarBeforeOutputSetup( '2013-06-30' ), "$specialName/:2013-2D06-2D30" ];
		$provider[] = [ $this->newSidebarBeforeOutputSetup( '2013$06&30' ), "$specialName/:2013-2406-2630" ];
		$provider[] = [ $this->newSidebarBeforeOutputSetup( '2013\Foo' ), "$specialName/:2013-5CFoo" ];

		return $provider;
	}

	private function newSidebarBeforeOutputSetup( $text ) {
		$settings = [
			'smwgNamespacesWithSemanticLinks' => [ NS_MAIN => true ],
			'smwgBrowseFeatures'           => SMW_BROWSE_TLINK
		];

		$message = $this->getMockBuilder( Message::class )
			->disableOriginalConstructor()
			->getMock();

		$output = $this->getMockBuilder( OutputPage::class )
			->disableOriginalConstructor()
			->getMock();

		$output->expects( $this->atLeastOnce() )
			->method( 'isArticle' )
			->willReturn( true );

		$skin = $this->getMockBuilder( '\Skin' )
			->disableOriginalConstructor()
			->getMock();

		$skin->expects( $this->atLeastOnce() )
			->method( 'getTitle' )
			->willReturn( MediaWikiServices::getInstance()->getTitleFactory()->newFromText( $text, NS_MAIN ) );

		$skin->expects( $this->atLeastOnce() )
			->method( 'msg' )
			->willReturn( $message )
			->with( 'smw_browselink' );

		$skin->expects( $this->any() )
			->method( 'getOutput' )
			->willReturn( $output );

		return [ 'settings' => $settings, 'skin' => $skin ];
	}

}
