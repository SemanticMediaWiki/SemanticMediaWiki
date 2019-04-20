<?php

namespace SMW\Tests\MediaWiki\Search\ProfileForm\Forms;

use SMW\MediaWiki\Search\ProfileForm\Forms\SortForm;

/**
 * @covers \SMW\MediaWiki\Search\ProfileForm\Forms\SortForm
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class SortFormTest extends \PHPUnit_Framework_TestCase {

	private $webRequest;

	protected function setUp() {

		$this->webRequest = $this->getMockBuilder( '\WebRequest' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			SortForm::class,
			new SortForm( $this->webRequest )
		);
	}

	public function testMakeFields() {

		$this->webRequest->expects( $this->at( 0 ) )
			->method( 'getVal' )
			->with( $this->equalTo( 'sort' ) )
			->will( $this->returnValue( 'Foo' ) );

		$instance = new SortForm(
			$this->webRequest
		);

		$this->assertContains(
			'smw-search-sort',
			$instance->makeFields( [] )
		);

		$this->assertEquals(
			[ 'sort' => 'Foo' ],
			$instance->getParameters()
		);
	}

}
