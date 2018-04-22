<?php

namespace SMW\Tests\MediaWiki\Search\Form;

use SMW\MediaWiki\Search\Form\FormsBuilder;

/**
 * @covers \SMW\MediaWiki\Search\Form\FormsBuilder
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class FormsBuilderTest extends \PHPUnit_Framework_TestCase {

	private $webRequest;
	private $formsFactory;

	protected function setUp() {

		$this->webRequest = $this->getMockBuilder( '\WebRequest' )
			->disableOriginalConstructor()
			->getMock();

		$this->formsFactory = $this->getMockBuilder( '\SMW\MediaWiki\Search\Form\FormsFactory' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			FormsBuilder::class,
			new FormsBuilder( $this->webRequest, $this->formsFactory )
		);
	}

	public function testBuildFromJSON() {

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$openForm = $this->getMockBuilder( '\SMW\MediaWiki\Search\Form\OpenForm' )
			->disableOriginalConstructor()
			->getMock();

		$customForm = $this->getMockBuilder( '\SMW\MediaWiki\Search\Form\CustomForm' )
			->disableOriginalConstructor()
			->getMock();

		$customForm->expects( $this->any() )
			->method( 'getParameters' )
			->will( $this->returnValue( [] ) );

		$this->formsFactory->expects( $this->any() )
			->method( 'newOpenForm' )
			->will( $this->returnValue( $openForm ) );

		$this->formsFactory->expects( $this->any() )
			->method( 'newCustomForm' )
			->will( $this->returnValue( $customForm ) );

		$instance = new FormsBuilder(
			$this->webRequest,
			$this->formsFactory
		);

		$form = [
			'forms' => [
				'Foo' => [
					'Property one'
				],
				'Bar' => [
					'Property two'
				]
			]
		];

		$expected = [
			"<div class='divider' style='display:none;'></div>",
			'<div id="smw-form-definitions" class="is-disabled">',
			'<div id="smw-form-foo" class="smw-fields"></div>',
			'<div id="smw-form-bar" class="smw-fields"></div></div>'
		];

		$this->assertContains(
			implode( '', $expected ),
			$instance->buildFromJSON( json_encode( $form ) )
		);

		$expected = [
			'<div id="smw-search-forms" class="smw-select is-disabled" data-nslist="[]">',
			'<label for="smw-form"><a>Form</a>:&nbsp;</label><select id="smw-form" name="smw-form">',
			"<option value='' ></option>",
			"<option value='foo' >Foo</option>",
			"<option value='bar' >Bar</option>",
			'</select></div>'
		];

		$this->assertContains(
			implode( '', $expected ),
			$instance->makeSelectList( $title )
		);
	}


}
