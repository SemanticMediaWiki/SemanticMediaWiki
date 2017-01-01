<?php

namespace SMW\Tests\MediaWiki\Specials\Admin;

use SMW\Tests\TestEnvironment;
use SMW\MediaWiki\Specials\Admin\SupplementaryLinksActionHandler;

/**
 * @covers \SMW\MediaWiki\Specials\Admin\SupplementaryLinksActionHandler
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class SupplementaryLinksActionHandlerTest extends \PHPUnit_Framework_TestCase {

	private $testEnvironment;
	private $store;
	private $outputFormatter;

	protected function setUp() {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

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
			'\SMW\MediaWiki\Specials\Admin\SupplementaryLinksActionHandler',
			new SupplementaryLinksActionHandler( $this->outputFormatter )
		);
	}

	public function testOutputConfigurationList() {

		$this->outputFormatter->expects( $this->atLeastOnce() )
			->method( 'addHtml' );

		$instance = new SupplementaryLinksActionHandler(
			$this->outputFormatter
		);

		$instance->doOutputConfigurationList();
	}

	public function testOutputStatistics() {

		$semanticStatistics = array(
			'PROPUSES' => 0,
			'ERRORUSES' => 0,
			'USEDPROPS' => 0,
			'TOTALPROPS' => 0,
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

		$instance = new SupplementaryLinksActionHandler(
			$this->outputFormatter
		);

		$instance->doOutputStatistics();
	}

}
