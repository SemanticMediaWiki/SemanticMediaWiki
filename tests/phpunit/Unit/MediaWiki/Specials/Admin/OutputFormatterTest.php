<?php

namespace SMW\Tests\MediaWiki\Specials\Admin;

use SMW\MediaWiki\Specials\Admin\OutputFormatter;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\MediaWiki\Specials\Admin\OutputFormatter
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class OutputFormatterTest extends \PHPUnit_Framework_TestCase {

	private $testEnvironment;
	private $outputPage;

	protected function setUp() {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->outputPage = $this->getMockBuilder( '\OutputPage' )
			->disableOriginalConstructor()
			->getMock();
	}

	protected function tearDown() {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\MediaWiki\Specials\Admin\OutputFormatter',
			new OutputFormatter( $this->outputPage )
		);
	}

	public function testGetSpecialPageLinkWith() {

		$instance = new OutputFormatter( $this->outputPage );

		$this->assertInternalType(
			'string',
			$instance->getSpecialPageLinkWith()
		);
	}

	public function testEncodeAsJson() {

		$instance = new OutputFormatter( $this->outputPage );

		$this->assertInternalType(
			'string',
			$instance->encodeAsJson( [] )
		);
	}

	public function testAddParentLink() {

		$this->outputPage->expects( $this->once() )
			->method( 'prependHTML' );

		$instance = new OutputFormatter( $this->outputPage );
		$instance->addParentLink();
	}

	public function testSetPageTitle() {

		$this->outputPage->expects( $this->once() )
			->method( 'setPageTitle' );

		$instance = new OutputFormatter( $this->outputPage );
		$instance->setPageTitle( 'Foo' );
	}

	public function testAddHTML() {

		$this->outputPage->expects( $this->once() )
			->method( 'addHTML' );

		$instance = new OutputFormatter( $this->outputPage );
		$instance->addHTML( 'Foo' );
	}

	public function testAddWikiText() {

		$this->outputPage->expects( $this->once() )
			->method( 'addWikiText' );

		$instance = new OutputFormatter( $this->outputPage );
		$instance->addWikiText( 'Foo' );
	}

}
