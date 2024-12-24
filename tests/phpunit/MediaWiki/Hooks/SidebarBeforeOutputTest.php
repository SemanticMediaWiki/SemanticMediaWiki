<?php

namespace SMW\Tests\MediaWiki\Hooks;

use SMW\MediaWiki\Hooks\SidebarBeforeOutput;
use SMW\Tests\Utils\Mock\MockTitle;
use Title;

/**
 * @covers \SMW\MediaWiki\Hooks\SidebarBeforeOutput
 *
 * @license GNU GPL v2+
 */
class SidebarBeforeOutputTest extends \PHPUnit\Framework\TestCase {

	private $namespaceExaminer;

	protected function setUp(): void {
		parent::setUp();

		$this->namespaceExaminer = $this->getMockBuilder( '\SMW\NamespaceExaminer' )
			->disableOriginalConstructor()
			->getMock();
	}

	protected function tearDown(): void {
		parent::tearDown();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			SidebarBeforeOutput::class,
			new SidebarBeforeOutput( $this->namespaceExaminer )
		);
	}

	/**
	 * @dataProvider skinTemplateDataProvider
	 */
	public function testProcess( $setup, $expected ) {
		$this->namespaceExaminer->expects( $this->any() )
			->method( 'isSemanticEnabled' )
			->willReturn( $setup['settings']['isEnabledNamespace'] );

		$sidebar = [];

		$instance = new SidebarBeforeOutput(
			$this->namespaceExaminer
		);

		$instance->setOptions(
			[
				'smwgBrowseFeatures' => $setup['settings']['smwgBrowseFeatures']
			]
		);

		$this->assertTrue(
			$instance->process( $setup['skin'], $sidebar )
		);

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

		$skin = $this->getMockBuilder( '\Skin' )
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
		$message = $this->getMockBuilder( '\Message' )
			->disableOriginalConstructor()
			->getMock();

		$output = $this->getMockBuilder( '\OutputPage' )
			->disableOriginalConstructor()
			->getMock();

		$output->expects( $this->atLeastOnce() )
			->method( 'isArticle' )
			->willReturn( $isArticle );

		$skin = $this->getMockBuilder( '\Skin' )
			->disableOriginalConstructor()
			->getMock();

		$skin->expects( $this->atLeastOnce() )
			->method( 'getTitle' )
			->willReturn( Title::newFromText( __METHOD__ ) );

		$skin->expects( $this->any() )
			->method( 'msg' )
			->willReturn( $message );

		$skin->expects( $this->any() )
			->method( 'getOutput' )
			->willReturn( $output );

		return $skin;
	}
}
