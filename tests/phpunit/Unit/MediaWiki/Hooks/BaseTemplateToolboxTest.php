<?php

namespace SMW\Tests\MediaWiki\Hooks;

use SMW\Tests\Utils\Mock\MockTitle;

use SMW\MediaWiki\Hooks\BaseTemplateToolbox;
use SMW\ApplicationFactory;
use SMW\Settings;

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

	protected function tearDown() {
		ApplicationFactory::clear();

		parent::tearDown();
	}

	public function testCanConstruct() {

		$skinTemplate = $this->getMockBuilder( '\SkinTemplate' )
			->disableOriginalConstructor()
			->getMock();

		$toolbox = '';

		$this->assertInstanceOf(
			'\SMW\MediaWiki\Hooks\BaseTemplateToolbox',
			new BaseTemplateToolbox( $skinTemplate, $toolbox )
		);
	}

	/**
	 * @dataProvider skinTemplateDataProvider
	 */
	public function testProcess( $setup, $expected ) {

		$toolbox  = '';

		ApplicationFactory::getInstance()->registerObject(
			'Settings',
			Settings::newFromArray( $setup['settings'] )
		);

		$instance = new BaseTemplateToolbox( $setup['skinTemplate'], $toolbox );

		$this->assertTrue( $instance->process() );

		if ( $expected['count'] == 0 ) {
			return $this->assertEmpty( $toolbox );
		}

		$this->assertCount(
			$expected['count'],
			$toolbox['smw-browse']
		);
	}

	public function skinTemplateDataProvider() {

		#0 Standard title
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
			->will( $this->returnValue( Title::newFromText( __METHOD__ ) ) );

		$skin->expects( $this->any() )
			->method( 'msg' )
			->will( $this->returnValue( $message ) );

		$skinTemplate = $this->getMockBuilder( '\SkinTemplate' )
			->disableOriginalConstructor()
			->getMock();

		$skinTemplate->expects( $this->atLeastOnce() )
			->method( 'getSkin' )
			->will( $this->returnValue( $skin ) );

		$skinTemplate->data['isarticle'] = true;

		$provider[] = array(
			array( 'skinTemplate' => $skinTemplate, 'settings' => $settings ),
			array( 'count'        => 4 ),
		);

		#1 isarticle = false
		$skinTemplate = $this->getMockBuilder( '\SkinTemplate' )
			->disableOriginalConstructor()
			->getMock();

		$skinTemplate->expects( $this->atLeastOnce() )
			->method( 'getSkin' )
			->will( $this->returnValue( $skin ) );

		$skinTemplate->data['isarticle'] = false;

		$provider[] = array(
			array( 'skinTemplate' => $skinTemplate, 'settings' => $settings ),
			array( 'count'        => 0 ),
		);

		#2 smwgToolboxBrowseLink = false
		$skinTemplate = $this->getMockBuilder( '\SkinTemplate' )
			->disableOriginalConstructor()
			->getMock();

		$skinTemplate->expects( $this->atLeastOnce() )
			->method( 'getSkin' )
			->will( $this->returnValue( $skin ) );

		$skinTemplate->data['isarticle'] = true;

		$settings = array(
			'smwgNamespacesWithSemanticLinks' => array( NS_MAIN => true ),
			'smwgToolboxBrowseLink'           => false
		);

		$provider[] = array(
			array( 'skinTemplate' => $skinTemplate, 'settings' => $settings ),
			array( 'count'        => 0 ),
		);

		#3 smwgNamespacesWithSemanticLinks = false
		$settings = array(
			'smwgNamespacesWithSemanticLinks' => array( NS_MAIN => false ),
			'smwgToolboxBrowseLink'           => true
		);

		$provider[] = array(
			array( 'skinTemplate' => $skinTemplate, 'settings' => $settings ),
			array( 'count'        => 0 ),
		);

		#4 Special page
		$settings = array(
			'smwgNamespacesWithSemanticLinks' => array( NS_MAIN => true ),
			'smwgToolboxBrowseLink'           => true
		);

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

		$provider[] = array(
			array( 'skinTemplate' => $skinTemplate, 'settings' => $settings ),
			array( 'count'        => 0 ),
		);

		return $provider;
	}

}
