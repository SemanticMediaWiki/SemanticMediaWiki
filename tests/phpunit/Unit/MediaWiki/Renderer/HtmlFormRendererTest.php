<?php

namespace SMW\Tests\MediaWiki\Renderer;

use SMW\Tests\Utils\UtilityFactory;
use SMW\MediaWiki\Renderer\HtmlFormRenderer;

/**
 * @covers \SMW\MediaWiki\Renderer\HtmlFormRenderer
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class HtmlFormRendererTest extends \PHPUnit_Framework_TestCase {

	private $stringValidator;

	protected function setUp() {
		parent::setUp();

		$this->stringValidator = UtilityFactory::getInstance()->newValidatorFactory()->newStringValidator();
	}

	public function testCanConstruct() {

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$messageBuilder = $this->getMockBuilder( '\SMW\MediaWiki\MessageBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\MediaWiki\Renderer\HtmlFormRenderer',
			new HtmlFormRenderer( $title, $messageBuilder )
		);
	}

	public function testGetMessageBuilder() {

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$messageBuilder = $this->getMockBuilder( '\SMW\MediaWiki\MessageBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new HtmlFormRenderer( $title, $messageBuilder );

		$this->assertSame(
			$messageBuilder,
			$instance->getMessageBuilder()
		);
	}

	public function testGetForm() {

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$message = $this->getMockBuilder( '\Message' )
			->disableOriginalConstructor()
			->getMock();

		$message->expects( $this->any() )
			->method( 'title' )
			->will( $this->returnSelf() );

		$message->expects( $this->any() )
			->method( 'numParams' )
			->will( $this->returnSelf() );

		$message->expects( $this->any() )
			->method( 'rawParams' )
			->will( $this->returnSelf() );

		$message->expects( $this->any() )
			->method( 'text' )
			->will( $this->returnValue( 'SomeText' ) );

		$messageBuilder = $this->getMockBuilder( '\SMW\MediaWiki\MessageBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$messageBuilder->expects( $this->any() )
			->method( 'getMessage' )
			->will( $this->returnValue( $message ) );

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

		$expected = array(
			'form id="smw-form-SomeForm" name="SomeForm" method="get"',
			'<p class="smw-form-paragraph">SomeDescription</p>',
			'input name="foo" size="333" value="Foo" id="FooId"',
			'input name="AnotherInputFieldName" size="20" value="AnotherInputFieldValue" id="AnotherInputFieldName"',
			'input type="submit" value="FindFoo"',
			'<br />&nbsp;'
		);

		$this->stringValidator->assertThatStringContains(
			$expected,
			$instance->getForm()
		);
	}

	public function testOptionsSelecList() {

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$message = $this->getMockBuilder( '\Message' )
			->disableOriginalConstructor()
			->getMock();

		$message->expects( $this->any() )
			->method( 'text' )
			->will( $this->returnValue( 'SomeText' ) );

		$messageBuilder = $this->getMockBuilder( '\SMW\MediaWiki\MessageBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$messageBuilder->expects( $this->any() )
			->method( 'getMessage' )
			->will( $this->returnValue( $message ) );

		$instance = new HtmlFormRenderer( $title, $messageBuilder );

		$instance
			->setName( 'optionsSelecListForm' )
			->withFieldset()
			->setMethod( 'isNeithergetNorPostMethodUseDefaultInstead' )
			->addOptionSelectList(
				'optionlistLabel',
				'optionlistName',
				'b',
				array( 'f' => 'foo', 'b' =>'bar' ),
				'optionslistId');

		$expected = array(
			'form id="smw-form-optionsSelecListForm" name="optionsSelecListForm" method="get"',
			'<fieldset id="smw-form-fieldset-optionsSelecListForm">',
			'<label for="optionslistId">optionlistLabel</label>&#160;',
			'<select name="optionlistName" id="optionslistId" class="smw-form-select">',
			'<option value="b" selected="">bar</option>',
			'<option value="f">foo</option>'
		);

		$this->stringValidator->assertThatStringContains(
			$expected,
			$instance->getForm()
		);
	}

	public function testCheckbox() {

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$message = $this->getMockBuilder( '\Message' )
			->disableOriginalConstructor()
			->getMock();

		$message->expects( $this->any() )
			->method( 'text' )
			->will( $this->returnValue( 'SomeText' ) );

		$messageBuilder = $this->getMockBuilder( '\SMW\MediaWiki\MessageBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$messageBuilder->expects( $this->any() )
			->method( 'getMessage' )
			->will( $this->returnValue( $message ) );

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

		$expected = array(
			'<form id="smw-form-checkboxForm" name="checkboxForm" method="post" action="http://example.org/foo">',
			'<h2>someHeader</h2>',
			'<fieldset id="smw-form-fieldset-checkboxForm">',
			'<input name="checkboxName" type="checkbox" value="1" checked="checked" id="checkboxName" class="smw-form-checkbox" />',
			'<label for="checkboxName" class="smw-form-checkbox">checkboxLabel</label>'
		);

		$this->stringValidator->assertThatStringContains(
			$expected,
			$instance->getForm()
		);
	}

}
