<?php

namespace SMW\Tests\Unit\MediaWiki\Renderer;

use MediaWiki\Message\Message;
use MediaWiki\Title\Title;
use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\MessageBuilder;
use SMW\MediaWiki\Renderer\HtmlFormRenderer;
use SMW\Tests\Utils\UtilityFactory;

/**
 * @covers \SMW\MediaWiki\Renderer\HtmlFormRenderer
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.1
 *
 * @author mwjames
 */
class HtmlFormRendererTest extends TestCase {

	private $stringValidator;

	protected function setUp(): void {
		parent::setUp();

		$this->stringValidator = UtilityFactory::getInstance()->newValidatorFactory()->newStringValidator();
	}

	public function testCanConstruct() {
		$title = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();

		$messageBuilder = $this->getMockBuilder( MessageBuilder::class )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			HtmlFormRenderer::class,
			new HtmlFormRenderer( $title, $messageBuilder )
		);
	}

	public function testGetMessageBuilder() {
		$title = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();

		$messageBuilder = $this->getMockBuilder( MessageBuilder::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = new HtmlFormRenderer( $title, $messageBuilder );

		$this->assertSame(
			$messageBuilder,
			$instance->getMessageBuilder()
		);
	}

	public function testGetForm() {
		$title = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();

		$message = $this->getMockBuilder( Message::class )
			->disableOriginalConstructor()
			->getMock();

		$message->expects( $this->any() )
			->method( 'title' )
			->willReturnSelf();

		$message->expects( $this->any() )
			->method( 'numParams' )
			->willReturnSelf();

		$message->expects( $this->any() )
			->method( 'rawParams' )
			->willReturnSelf();

		$message->expects( $this->any() )
			->method( 'text' )
			->willReturn( 'SomeText' );

		$messageBuilder = $this->getMockBuilder( MessageBuilder::class )
			->disableOriginalConstructor()
			->getMock();

		$messageBuilder->expects( $this->any() )
			->method( 'getMessage' )
			->willReturn( $message );

		$instance = new HtmlFormRenderer( $title, $messageBuilder );

		$instance
			->setName( 'SomeForm' )
			->withFieldset()
			->addParagraph( 'SomeDescription' )
			->addQueryParameter( 'SomeQueryParameter', 'SomeQueryValue' )
			->addPaging( 10, 0, 5 )
			->addHorizontalRule()
			->addInputField( 'SomeInputFieldLabel', 'foo', 'Foo', 'FooId', 333 )
			->addLineBreak()
			->addNonBreakingSpace()
			->addInputField( 'AnotherInputFieldLabel', 'AnotherInputFieldName', 'AnotherInputFieldValue' )
			->addSubmitButton( 'FindFoo' );

		$expected = [
			'form id="smw-form-SomeForm" name="SomeForm" method="get"',
			'<p class="smw-form-paragraph">SomeDescription</p>',
			'input size="333" id="FooId" class="smw-form-input" value="Foo" name="foo"',
			'input size="20" id="AnotherInputFieldName" class="smw-form-input" value="AnotherInputFieldValue" name="AnotherInputFieldName"',
			'input type="submit" value="FindFoo"',
			// '<br />&nbsp;' MW 1.27 <br/>&nbsp;
		];

		$this->stringValidator->assertThatStringContains(
			$expected,
			$instance->getForm()
		);
	}

	public function testOptionsSelecList() {
		$title = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();

		$message = $this->getMockBuilder( Message::class )
			->disableOriginalConstructor()
			->getMock();

		$message->expects( $this->any() )
			->method( 'text' )
			->willReturn( 'SomeText' );

		$messageBuilder = $this->getMockBuilder( MessageBuilder::class )
			->disableOriginalConstructor()
			->getMock();

		$messageBuilder->expects( $this->any() )
			->method( 'getMessage' )
			->willReturn( $message );

		$instance = new HtmlFormRenderer( $title, $messageBuilder );

		$instance
			->setName( 'optionsSelecListForm' )
			->withFieldset()
			->setMethod( 'isNeithergetNorPostMethodUseDefaultInstead' )
			->addOptionSelectList(
				'optionlistLabel',
				'optionlistName',
				'b',
				[ 'f' => 'foo', 'b' => 'bar' ],
				'optionslistId' );

		$expected = [
			'form id="smw-form-optionsSelecListForm" name="optionsSelecListForm" method="get"',
			'<fieldset id="smw-form-fieldset-optionsSelecListForm">',
			'<label for="optionslistId">optionlistLabel</label>&#160;',
			'<select name="optionlistName" id="optionslistId" class="smw-form-select">',
			'<option value="b" selected="">bar</option>',
			'<option value="f">foo</option>'
		];

		$this->stringValidator->assertThatStringContains(
			$expected,
			$instance->getForm()
		);
	}

	public function testCheckbox() {
		$title = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();

		$message = $this->getMockBuilder( Message::class )
			->disableOriginalConstructor()
			->getMock();

		$message->expects( $this->any() )
			->method( 'text' )
			->willReturn( 'SomeText' );

		$messageBuilder = $this->getMockBuilder( MessageBuilder::class )
			->disableOriginalConstructor()
			->getMock();

		$messageBuilder->expects( $this->any() )
			->method( 'getMessage' )
			->willReturn( $message );

		$instance = new HtmlFormRenderer( $title, $messageBuilder );

		$instance
			->setName( 'checkboxForm' )
			->addHeader( 'invalidLevel', 'someHeader' )
			->withFieldset()
			->setMethod( 'post' )
			->setActionUrl( 'http://example.org/foo' )
			->addCheckbox(
				'checkboxLabel',
				'checkboxName',
				true,
				'checkBoxId' );

		$expected = [
			'<form id="smw-form-checkboxForm" name="checkboxForm" method="post" action="http://example.org/foo">',
			'<h2>someHeader</h2>',
			'<fieldset id="smw-form-fieldset-checkboxForm">',
			'<input id="checkboxName" class="smw-form-checkbox" checked="" type="checkbox" value="1" name="checkboxName">',
			'<label class="smw-form-checkbox" for="checkboxName">checkboxLabel</label>'
		];

		$this->stringValidator->assertThatStringContains(
			$expected,
			$instance->getForm()
		);
	}

}
