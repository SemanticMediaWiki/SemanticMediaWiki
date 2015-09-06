<?php

namespace SMW\Tests\MediaWiki\Hooks;

use SMW\MediaWiki\Hooks\EditPageForm;
use SMW\MediaWiki\Renderer\HtmlFormRenderer;

use SMW\ApplicationFactory;

use Title;

/**
 * @covers \SMW\MediaWiki\Hooks\EditPageForm
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class EditPageFormTest extends \PHPUnit_Framework_TestCase {

	private $applicationFactory;

	protected function setUp() {
		$this->applicationFactory = ApplicationFactory::getInstance();
		$this->applicationFactory->getSettings()->set( 'smwgEnabledEditPageHelp', true );
	}

	protected function tearDown() {
		$this->applicationFactory->clear();
	}

	public function testCanConstruct() {

		$editPage = $this->getMockBuilder( '\EditPage' )
			->disableOriginalConstructor()
			->getMock();

		$htmlFormRenderer = $this->getMockBuilder( '\SMW\MediaWiki\Renderer\HtmlFormRenderer' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\MediaWiki\Hooks\EditPageForm',
			new EditPageForm( $editPage, $htmlFormRenderer )
		);
	}

	public function testDisabledHelp() {

		$this->applicationFactory->getSettings()->set( 'smwgEnabledEditPageHelp', false );

		$editPage = $this->getMockBuilder( '\EditPage' )
			->disableOriginalConstructor()
			->getMock();

		$htmlFormRenderer = $this->getMockBuilder( '\SMW\MediaWiki\Renderer\HtmlFormRenderer' )
			->disableOriginalConstructor()
			->getMock();

		$editPage->expects( $this->never() )
			->method( 'getMessageBuilder' );

		$instance = new EditPageForm( $editPage, $htmlFormRenderer );

		$this->assertTrue(
			$instance->process()
		);
	}

	/**
	 * @dataProvider titleProvider
	 */
	public function testExtendEditFormPageTop( $title, $namespaces, $expected ) {

		$this->applicationFactory->getSettings()->set( 'smwgNamespacesWithSemanticLinks', $namespaces );

		$message = $this->getMockBuilder( '\Message' )
			->disableOriginalConstructor()
			->getMock();

		$messageBuilder = $this->getMockBuilder( '\SMW\MediaWiki\MessageBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$messageBuilder->expects( $this->any() )
			->method( 'getMessage' )
			->with( $this->equalTo( $expected ) )
			->will( $this->returnValue( $message ) );

		$htmlFormRenderer = new HtmlFormRenderer( $title, $messageBuilder );

		$editPage = $this->getMockBuilder( '\EditPage' )
			->disableOriginalConstructor()
			->getMock();

		$editPage->expects( $this->any() )
			->method( 'getTitle' )
			->will( $this->returnValue( $title ) );

		$editPage->editFormPageTop = '';

		$instance = new EditPageForm( $editPage, $htmlFormRenderer );

		$this->assertTrue(
			$instance->process()
		);
	}

	public function titleProvider() {

		$provider[] = array(
			Title::newFromText( 'Foo', SMW_NS_PROPERTY ),
			array( SMW_NS_PROPERTY => true ),
			'smw-editpage-property-annotation-enabled'
		);

		$provider[] = array(
			Title::newFromText( 'Modification date', SMW_NS_PROPERTY ),
			array( SMW_NS_PROPERTY => true ),
			'smw-editpage-property-annotation-disabled'
		);

		$provider[] = array(
			Title::newFromText( 'Foo', SMW_NS_CONCEPT ),
			array( SMW_NS_CONCEPT => true ),
			'smw-editpage-concept-annotation-enabled'
		);

		$provider[] = array(
			Title::newFromText( 'Foo', NS_MAIN ),
			array( NS_MAIN => true ),
			'smw-editpage-annotation-enabled'
		);

		$provider[] = array(
			Title::newFromText( 'Foo', NS_MAIN ),
			array( NS_MAIN => false ),
			'smw-editpage-annotation-disabled'
		);

		return $provider;
	}

}
