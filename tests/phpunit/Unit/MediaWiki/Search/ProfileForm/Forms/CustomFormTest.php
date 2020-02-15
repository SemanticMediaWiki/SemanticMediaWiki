<?php

namespace SMW\Tests\MediaWiki\Search\ProfileForm\Forms;

use SMW\MediaWiki\Search\ProfileForm\Forms\CustomForm;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\MediaWiki\Search\ProfileForm\Forms\CustomForm
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class CustomFormTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	private $webRequest;

	protected function setUp() : void {

		$this->webRequest = $this->getMockBuilder( '\WebRequest' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			CustomForm::class,
			new CustomForm( $this->webRequest )
		);
	}

	public function testMakeFields() {

		$this->webRequest->expects( $this->at( 0 ) )
			->method( 'getArray' )
			->with( $this->equalTo( 'barproperty' ) )
			->will( $this->returnValue( [ 1001 ] ) );

		$instance = new CustomForm(
			$this->webRequest
		);

		$instance->isActiveForm( true );

		$form = [
			'<div class="smw-input-field" style="display:inline-block;">',
			'<input class="smw-input" name="barproperty[]" value="1001" placeholder="Bar property ..." ',
			'data-property="Bar property" title="Bar property"/></div>'
		];

		$this->assertContains(
			implode( '', $form ),
			$instance->makeFields( [ 'Bar property'] )
		);

		$this->assertEquals(
			[
				'barproperty' => 1001
			],
			$instance->getParameters()
		);
	}

}
