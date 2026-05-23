<?php

namespace SMW\Tests\Unit\MediaWiki\Hooks;

use MediaWiki\MediaWikiServices;
use MediaWiki\Message\Message;
use MediaWiki\Output\OutputPage;
use PHPUnit\Framework\TestCase;
use Skin;
use SMW\MediaWiki\Hooks\SidebarBeforeOutput;
use SMW\NamespaceExaminer;
use SMW\Settings;
use SMW\Tests\Utils\Mock\MockTitle;

/**
 * @covers \SMW\MediaWiki\Hooks\SidebarBeforeOutput
 *
 * @license GPL-2.0-or-later
 */
class SidebarBeforeOutputTest extends TestCase {

	private $namespaceExaminer;
	private $settings;

	protected function setUp(): void {
		parent::setUp();

		$this->namespaceExaminer = $this->getMockBuilder( NamespaceExaminer::class )
			->disableOriginalConstructor()
			->getMock();

		$this->settings = $this->createMock( Settings::class );
	}

	protected function tearDown(): void {
		parent::tearDown();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			SidebarBeforeOutput::class,
			new SidebarBeforeOutput( $this->namespaceExaminer, $this->settings )
		);
	}

	/**
	 * @dataProvider skinTemplateDataProvider
	 */
	public function testProcess( $setup, $expected ) {
		$this->namespaceExaminer->expects( $this->any() )
			->method( 'isSemanticEnabled' )
			->willReturn( $setup['settings']['isEnabledNamespace'] );

		$this->settings->method( 'isFlagSet' )
			->with( 'smwgBrowseFeatures', SMW_BROWSE_TLINK )
			->willReturn( ( $setup['settings']['smwgBrowseFeatures'] & SMW_BROWSE_TLINK ) === SMW_BROWSE_TLINK );

		$sidebar = [];

		$instance = new SidebarBeforeOutput(
			$this->namespaceExaminer,
			$this->settings
		);

		$instance->onSidebarBeforeOutput( $setup['skin'], $sidebar );

		if ( $expected['count'] == 0 ) {
			$this->assertEmpty( $sidebar );
		} else {
			$this->assertCount(
				$expected['count'],
				$sidebar['TOOLBOX']
			);
		}
	}

	public function skinTemplateDataProvider() {
		# 0 Standard title
		$settings = [
			'isEnabledNamespace' => true,
			'smwgBrowseFeatures' => SMW_BROWSE_TLINK
		];

		$provider[] = [
			[
				'skin' => $this->newSkinStub( true ),
				'settings' => $settings
			],
			[ 'count' => 1 ],
		];

		# 1 isArticle = false
		$provider[] = [
			[
				'skin' => $this->newSkinStub( false ),
				'settings' => $settings
			],
			[ 'count' => 0 ],
		];

		$settings = [
			'isEnabledNamespace' => true,
			'smwgBrowseFeatures' => SMW_BROWSE_NONE
		];

		$provider[] = [
			[
				'skin' => $this->newSkinStub( true ),
				'settings' => $settings
			],
			[ 'count' => 0 ],
		];

		# 3 smwgNamespacesWithSemanticLinks = false

		$settings = [
			'isEnabledNamespace' => false,
			'smwgBrowseFeatures' => SMW_BROWSE_TLINK
		];

		$provider[] = [
			[
				'skin' => $this->newSkinStub( true ),
				'settings' => $settings
			],
			[ 'count' => 0 ],
		];

		# 4 Special page
		$settings = [
			'isEnabledNamespace' => true,
			'smwgBrowseFeatures' => SMW_BROWSE_TLINK
		];

		$title = MockTitle::buildMock( __METHOD__ );

		$title->expects( $this->atLeastOnce() )
			->method( 'isSpecialPage' )
			->willReturn( true );

		$skin = $this->getMockBuilder( Skin::class )
			->disableOriginalConstructor()
			->getMock();

		$skin->expects( $this->atLeastOnce() )
			->method( 'getTitle' )
			->willReturn( $title );

		$provider[] = [
			[
				'skin' => $skin,
				'settings' => $settings
			],
			[ 'count' => 0 ],
		];

		return $provider;
	}

	private function newSkinStub( bool $isArticle ) {
		$message = $this->getMockBuilder( Message::class )
			->disableOriginalConstructor()
			->getMock();

		$output = $this->getMockBuilder( OutputPage::class )
			->disableOriginalConstructor()
			->getMock();

		$output->expects( $this->atLeastOnce() )
			->method( 'isArticle' )
			->willReturn( $isArticle );

		$skin = $this->getMockBuilder( Skin::class )
			->disableOriginalConstructor()
			->getMock();

		$skin->expects( $this->atLeastOnce() )
			->method( 'getTitle' )
			->willReturn( MediaWikiServices::getInstance()->getTitleFactory()->newFromText( __METHOD__ ) );

		$skin->expects( $this->any() )
			->method( 'msg' )
			->willReturn( $message );

		$skin->expects( $this->any() )
			->method( 'getOutput' )
			->willReturn( $output );

		return $skin;
	}
}
