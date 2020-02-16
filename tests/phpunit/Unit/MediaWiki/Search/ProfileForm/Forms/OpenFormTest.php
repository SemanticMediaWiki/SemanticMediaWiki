<?php

namespace SMW\Tests\MediaWiki\Search\ProfileForm\Forms;

use SMW\MediaWiki\Search\ProfileForm\Forms\OpenForm;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\MediaWiki\Search\ProfileForm\Forms\OpenForm
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class OpenFormTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	private $webRequest;

	protected function setUp() : void {

		$this->webRequest = $this->getMockBuilder( '\WebRequest' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			OpenForm::class,
			new OpenForm( $this->webRequest )
		);
	}

	public function testMakeFields() {

		$this->webRequest->expects( $this->at( 0 ) )
			->method( 'getArray' )
			->with( $this->equalTo( 'property' ) )
			->will( $this->returnValue(  [ 'Bar' ] ) );

		$this->webRequest->expects( $this->at( 1 ) )
			->method( 'getArray' )
			->with( $this->equalTo( 'pvalue' ) )
			->will( $this->returnValue( [ 42 ] ) );

		$this->webRequest->expects( $this->at( 2 ) )
			->method( 'getArray' )
			->with( $this->equalTo( 'op' ) )
			->will( $this->returnValue( [ 'OR' ] ) );

		$instance = new OpenForm(
			$this->webRequest
		);

		$instance->isActiveForm( true );

		$this->assertContains(
			'<div class="smw-input-group"><div class="smw-input-field" style="display:inline-block;">',
			$instance->makeFields( [] )
		);

		$this->assertEquals(
			[
				'property' => [ 'Bar' ],
				'pvalue' => [ 42 ],
				'op' => [ 'OR' ]
			],
			$instance->getParameters()
		);
	}

}
