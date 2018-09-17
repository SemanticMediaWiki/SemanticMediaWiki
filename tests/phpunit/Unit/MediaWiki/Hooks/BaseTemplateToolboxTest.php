<?php

namespace SMW\Tests\MediaWiki\Hooks;

use SMW\MediaWiki\Hooks\BaseTemplateToolbox;
use SMW\Tests\Utils\Mock\MockTitle;
use Title;

/**
 * @covers \SMW\MediaWiki\Hooks\BaseTemplateToolbox
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class BaseTemplateToolboxTest extends \PHPUnit_Framework_TestCase {

	private $namespaceExaminer;
	private $skinTemplate;

	protected function setUp() {
		parent::setUp();

		$this->namespaceExaminer = $this->getMockBuilder( '\SMW\NamespaceExaminer' )
			->disableOriginalConstructor()
			->getMock();

		$this->skinTemplate = $this->getMockBuilder( '\SkinTemplate' )
			->disableOriginalConstructor()
			->getMock();
	}

	protected function tearDown() {
		parent::tearDown();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			BaseTemplateToolbox::class,
			new BaseTemplateToolbox( $this->namespaceExaminer )
		);
	}

	/**
	 * @dataProvider skinTemplateDataProvider
	 */
	public function testProcess( $setup, $expected ) {

		$this->namespaceExaminer->expects( $this->any() )
			->method( 'isSemanticEnabled' )
			->will( $this->returnValue( $setup['settings']['isEnabledNamespace'] ) );

		$toolbox = [];

		$instance = new BaseTemplateToolbox(
			$this->namespaceExaminer
		);

		$instance->setOptions(
			[
				'smwgBrowseFeatures' => $setup['settings']['smwgBrowseFeatures']
			]
		);

		$this->assertTrue(
			$instance->process( $setup['skinTemplate'], $toolbox )
		);

		if ( $expected['count'] == 0 ) {
			$this->assertEmpty( $toolbox );
		} else {
			$this->assertCount(
				$expected['count'],
				$toolbox['smw-browse']
			);
		}
	}

	public function skinTemplateDataProvider() {

		#0 Standard title
		$settings = [
			'isEnabledNamespace' => true,
			'smwgBrowseFeatures' => SMW_BROWSE_TLINK
		];

		$skinTemplate = $this->getMockBuilder( '\SkinTemplate' )
			->disableOriginalConstructor()
			->getMock();

		$skinTemplate->expects( $this->atLeastOnce() )
			->method( 'getSkin' )
			->will( $this->returnValue( $this->newSkinStub() ) );

		$skinTemplate->data['isarticle'] = true;

		$provider[] = [
			[
				'skinTemplate' => $skinTemplate,
				'settings' => $settings
			],
			[ 'count' => 4 ],
		];

		#1 isarticle = false
		$skinTemplate = $this->getMockBuilder( '\SkinTemplate' )
			->disableOriginalConstructor()
			->getMock();

		$skinTemplate->expects( $this->atLeastOnce() )
			->method( 'getSkin' )
			->will( $this->returnValue( $this->newSkinStub() ) );

		$skinTemplate->data['isarticle'] = false;

		$provider[] = [
			[
				'skinTemplate' => $skinTemplate,
				'settings' => $settings
			],
			[ 'count' => 0 ],
		];

		#2 smwgBrowseFeatures = false
		$skinTemplate = $this->getMockBuilder( '\SkinTemplate' )
			->disableOriginalConstructor()
			->getMock();

		$skinTemplate->expects( $this->atLeastOnce() )
			->method( 'getSkin' )
			->will( $this->returnValue( $this->newSkinStub() ) );

		$skinTemplate->data['isarticle'] = true;

		$settings = [
			'isEnabledNamespace' => true,
			'smwgBrowseFeatures' => SMW_BROWSE_NONE
		];

		$provider[] = [
			[
				'skinTemplate' => $skinTemplate,
				'settings' => $settings
			],
			[ 'count' => 0 ],
		];

		#3 smwgNamespacesWithSemanticLinks = false
		$skinTemplate = $this->getMockBuilder( '\SkinTemplate' )
			->disableOriginalConstructor()
			->getMock();

		$skinTemplate->expects( $this->atLeastOnce() )
			->method( 'getSkin' )
			->will( $this->returnValue( $this->newSkinStub() ) );

		$skinTemplate->data['isarticle'] = true;

		$settings = [
			'isEnabledNamespace' => false,
			'smwgBrowseFeatures' => SMW_BROWSE_TLINK
		];

		$provider[] = [
			[
				'skinTemplate' => $skinTemplate,
				'settings' => $settings
			],
			[ 'count' => 0 ],
		];

		#4 Special page
		$settings = [
			'isEnabledNamespace' => true,
			'smwgBrowseFeatures' => SMW_BROWSE_TLINK
		];

		$title = MockTitle::buildMock( __METHOD__ );

		$title->expects( $this->atLeastOnce() )
			->method( 'isSpecialPage' )
			->will( $this->returnValue( true ) );

		$skin = $this->getMockBuilder( '\Skin' )
			->disableOriginalConstructor()
			->getMock();

		$skin->expects( $this->atLeastOnce() )
			->method( 'getTitle' )
			->will( $this->returnValue( $title ) );

		$skinTemplate = $this->getMockBuilder( '\SkinTemplate' )
			->disableOriginalConstructor()
			->getMock();

		$skinTemplate->expects( $this->atLeastOnce() )
			->method( 'getSkin' )
			->will( $this->returnValue( $skin ) );

		$skinTemplate->data['isarticle'] = true;

		$provider[] = [
			[
				'skinTemplate' => $skinTemplate,
				'settings' => $settings
			],
			[ 'count' => 0 ],
		];

		return $provider;
	}

	private function newSkinStub() {

		$message = $this->getMockBuilder( '\Message' )
			->disableOriginalConstructor()
			->getMock();

		$skin = $this->getMockBuilder( '\Skin' )
			->disableOriginalConstructor()
			->getMock();

		$skin->expects( $this->atLeastOnce() )
			->method( 'getTitle' )
			->will( $this->returnValue( Title::newFromText( __METHOD__ ) ) );

		$skin->expects( $this->any() )
			->method( 'msg' )
			->will( $this->returnValue( $message ) );

		return $skin;
	}

}
