<?php

namespace SMW\Tests\MediaWiki\Search\ProfileForm;

use SMW\MediaWiki\Search\ProfileForm\FormsBuilder;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\MediaWiki\Search\ProfileForm\FormsBuilder
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class FormsBuilderTest extends \PHPUnit_Framework_TestCase {

	private $stringValidator;
	private $webRequest;
	private $formsFactory;

	protected function setUp() {

		$this->stringValidator = TestEnvironment::newValidatorFactory()->newStringValidator();

		$this->webRequest = $this->getMockBuilder( '\WebRequest' )
			->disableOriginalConstructor()
			->getMock();

		$this->formsFactory = $this->getMockBuilder( '\SMW\MediaWiki\Search\ProfileForm\FormsFactory' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			FormsBuilder::class,
			new FormsBuilder( $this->webRequest, $this->formsFactory )
		);
	}

	public function testBuildForm() {

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$openForm = $this->getMockBuilder( '\SMW\MediaWiki\Search\ProfileForm\Forms\OpenForm' )
			->disableOriginalConstructor()
			->getMock();

		$customForm = $this->getMockBuilder( '\SMW\MediaWiki\Search\ProfileForm\Forms\CustomForm' )
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

		$data = [
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
			'<div id="smw-form-bar" class="smw-fields"></div>',
			'<div id="smw-form-foo" class="smw-fields"></div></div>'
		];

		$this->assertContains(
			implode( '', $expected ),
			$instance->buildForm( $data )
		);

		$expected = [
			'<button type="button" id="smw-search-forms" class="smw-selectmenu-button is-disabled".*',
			'name="smw-form" value="".*',
			'data-list="[{&quot;id&quot;:&quot;bar&quot;,&quot;name&quot;:&quot;Bar&quot;,&quot;desc&quot;:&quot;Bar&quot;},{&quot;id&quot;:&quot;foo&quot;,&quot;name&quot;:&quot;Foo&quot;,&quot;desc&quot;:&quot;Foo&quot;}]" data-nslist="[]">Form</button><input type="hidden" name="smw-form"/>'
		];

		$this->stringValidator->assertThatStringContains(
			$expected,
			$instance->buildFormList( $title )
		);
	}


}
