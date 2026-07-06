<?php

namespace SMW\Tests\Unit\MediaWiki\Search\ProfileForm\Forms;

use MediaWiki\Request\WebRequest;
use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\Search\ProfileForm\Forms\SortForm;

/**
 * @covers \SMW\MediaWiki\Search\ProfileForm\Forms\SortForm
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class SortFormTest extends TestCase {

	private $webRequest;

	protected function setUp(): void {
		$this->webRequest = $this->getMockBuilder( WebRequest::class )
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
		$this->webRequest->expects( $this->once() )
			->method( 'getVal' )
			->with( 'sort' )
			->willReturn( 'Foo' );

		$instance = new SortForm(
			$this->webRequest
		);

		$this->assertStringContainsString(
			'smw-search-sort',
			$instance->makeFields( [] )
		);

		$this->assertEquals(
			[ 'sort' => 'Foo' ],
			$instance->getParameters()
		);
	}

}
