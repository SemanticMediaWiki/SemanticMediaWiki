<?php

namespace SMW\Tests\Integration;

use SMW\Services\ServicesFactory as ApplicationFactory;
use SMW\MediaWiki\Hooks\SidebarBeforeOutput;
use Title;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\MediaWiki\Hooks\SidebarBeforeOutput
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
class EncodingIntegrationTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	/**
	 * @dataProvider sidebarBeforeOutputDataProvider
	 */
	public function testSidebarBeforeOutputURLEncoding( $setup, $expected ) {
		$sidebar  = [];

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

		$this->assertContains(
			$expected,
			$sidebar['TOOLBOX']['smwbrowselink']['href']
		);

		ApplicationFactory::clear();
	}

	public function sidebarBeforeOutputDataProvider() {
		$specialName = str_replace( '%3A', ':',
			\SMW\Encoder::encode( \SpecialPage::getTitleFor( 'Browse' )->getPrefixedText() )
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

		$message = $this->getMockBuilder( '\Message' )
			->disableOriginalConstructor()
			->getMock();

		$output = $this->getMockBuilder( '\OutputPage' )
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
			->willReturn( Title::newFromText( $text, NS_MAIN ) );

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
