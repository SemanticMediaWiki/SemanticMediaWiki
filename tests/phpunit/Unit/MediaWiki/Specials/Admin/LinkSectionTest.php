<?php

namespace SMW\Tests\MediaWiki\Specials\Admin;

use SMW\Tests\TestEnvironment;
use SMW\MediaWiki\Specials\Admin\LinkSection;

/**
 * @covers \SMW\MediaWiki\Specials\Admin\LinkSection
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class LinkSectionTest extends \PHPUnit_Framework_TestCase {

	private $testEnvironment;
	private $store;
	private $htmlFormRenderer;
	private $outputFormatter;

	protected function setUp() {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->htmlFormRenderer = $this->getMockBuilder( '\SMW\MediaWiki\Renderer\HtmlFormRenderer' )
			->disableOriginalConstructor()
			->getMock();

		$this->outputFormatter = $this->getMockBuilder( '\SMW\MediaWiki\Specials\Admin\OutputFormatter' )
			->disableOriginalConstructor()
			->getMock();

		$this->testEnvironment->registerObject( 'Store', $this->store );
	}

	protected function tearDown() {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\MediaWiki\Specials\Admin\LinkSection',
			new LinkSection( $this->htmlFormRenderer, $this->outputFormatter )
		);
	}

	public function testGetForm() {

		$instance = new LinkSection(
			$this->htmlFormRenderer,
			$this->outputFormatter
		);

		$this->assertInternalType(
			'string',
			$instance->getForm()
		);
	}

	public function testOutputConfigurationList() {

		$this->outputFormatter->expects( $this->atLeastOnce() )
			->method( 'addHtml' );

		$instance = new LinkSection(
			$this->htmlFormRenderer,
			$this->outputFormatter
		);

		$instance->outputConfigurationList();
	}

	public function testOutputStatistics() {

		$semanticStatistics = array(
			'PROPUSES' => 0,
			'ERRORUSES' => 0,
			'USEDPROPS' => 0,
			'OWNPAGE' => 0,
			'DECLPROPS' => 0,
			'DELETECOUNT' => 0,
			'SUBOBJECTS' => 0,
			'QUERY' => 0,
			'CONCEPTS' => 0
		);

		$this->store->expects( $this->once() )
			->method( 'getStatistics' )
			->will( $this->returnValue( $semanticStatistics ) );

		$this->outputFormatter->expects( $this->atLeastOnce() )
			->method( 'addHtml' );

		$instance = new LinkSection(
			$this->htmlFormRenderer,
			$this->outputFormatter
		);

		$instance->outputStatistics();
	}

}
