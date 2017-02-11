<?php

namespace SMW\Tests\MediaWiki\Hooks;

use SMW\MediaWiki\Hooks\EditPageForm;
use SMW\MediaWiki\Renderer\HtmlFormRenderer;
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

	private $namespaceExaminer;

	protected function setUp() {
		parent::setUp();

		$this->namespaceExaminer = $this->getMockBuilder( '\SMW\NamespaceExaminer' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\MediaWiki\Hooks\EditPageForm',
			new EditPageForm( $this->namespaceExaminer )
		);
	}

	public function testDisabledHelp() {

		$editPage = $this->getMockBuilder( '\EditPage' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new EditPageForm(
			$this->namespaceExaminer
		);

		$instance->isEnabledEditPageHelp(
			false
		);

		$this->assertTrue(
			$instance->process( $editPage )
		);
	}

	/**
	 * @dataProvider titleProvider
	 */
	public function testExtendEditFormPageTop( $title, $namespaces, $isSemanticEnabled, $expected ) {

		$this->namespaceExaminer->expects( $this->any() )
			->method( 'isSemanticEnabled' )
			->with( $this->equalTo( $namespaces ) )
			->will( $this->returnValue( $isSemanticEnabled ) );

		$editPage = $this->getMockBuilder( '\EditPage' )
			->disableOriginalConstructor()
			->getMock();

		$editPage->expects( $this->any() )
			->method( 'getTitle' )
			->will( $this->returnValue( $title ) );

		$editPage->editFormPageTop = '';

		$instance = new EditPageForm(
			$this->namespaceExaminer
		);

		$instance->isEnabledEditPageHelp(
			true
		);

		$instance->process( $editPage );

		$this->assertContains(
			$expected,
			$editPage->editFormPageTop
		);
	}

	public function titleProvider() {

		$provider[] = array(
			Title::newFromText( 'Foo', SMW_NS_PROPERTY ),
			SMW_NS_PROPERTY,
			true,
			'smw-editpage-property-annotation-enabled'
		);

		$provider[] = array(
			Title::newFromText( 'Modification date', SMW_NS_PROPERTY ),
			SMW_NS_PROPERTY,
			true,
			'smw-editpage-property-annotation-disabled'
		);

		$provider[] = array(
			Title::newFromText( 'Foo', SMW_NS_CONCEPT ),
			SMW_NS_CONCEPT,
			true,
			'smw-editpage-concept-annotation-enabled'
		);

		$provider[] = array(
			Title::newFromText( 'Foo', NS_MAIN ),
			NS_MAIN,
			true,
			'smw-editpage-annotation-enabled'
		);

		$provider[] = array(
			Title::newFromText( 'Foo', NS_MAIN ),
			NS_MAIN,
			false,
			'smw-editpage-annotation-disabled'
		);

		return $provider;
	}

}
