<?php

namespace SMW\Tests\MediaWiki\Hooks;

use SMW\MediaWiki\Hooks\EditPageForm;
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

		$instance->setOptions(
			[
				'smwgEnabledEditPageHelp' => false
			]
		);

		$this->assertTrue(
			$instance->process( $editPage )
		);
	}

	public function testDisabledOnUserPreference() {

		$editPage = $this->getMockBuilder( '\EditPage' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new EditPageForm(
			$this->namespaceExaminer
		);

		$instance->setOptions(
			[
				'prefs-disable-editpage' => true
			]
		);

		$editPage->editFormPageTop = '';

		$instance->process( $editPage );

		$this->assertEmpty(
			$editPage->editFormPageTop
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

		$instance->setOptions(
			[
				'smwgEnabledEditPageHelp' => true
			]
		);

		$instance->process( $editPage );

		$this->assertContains(
			$expected,
			$editPage->editFormPageTop
		);
	}

	public function titleProvider() {

		$provider[] = [
			Title::newFromText( 'Foo', SMW_NS_PROPERTY ),
			SMW_NS_PROPERTY,
			true,
			'smw-editpage-property-annotation-enabled'
		];

		$provider[] = [
			Title::newFromText( 'Modification date', SMW_NS_PROPERTY ),
			SMW_NS_PROPERTY,
			true,
			'smw-editpage-property-annotation-disabled'
		];

		$provider[] = [
			Title::newFromText( 'Foo', SMW_NS_CONCEPT ),
			SMW_NS_CONCEPT,
			true,
			'smw-editpage-concept-annotation-enabled'
		];

		$provider[] = [
			Title::newFromText( 'Foo', NS_MAIN ),
			NS_MAIN,
			true,
			'smw-editpage-annotation-enabled'
		];

		$provider[] = [
			Title::newFromText( 'Foo', NS_MAIN ),
			NS_MAIN,
			false,
			'smw-editpage-annotation-disabled'
		];

		return $provider;
	}

}
