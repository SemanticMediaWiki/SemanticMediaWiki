<?php

namespace SMW\Tests\MediaWiki\Specials;

use SMW\MediaWiki\Specials\SpecialAsk;
use SMW\Tests\TestEnvironment;
use SMW\Tests\Utils\Mock\MockSuperUser;
use Title;

/**
 * @covers \SMW\MediaWiki\Specials\SpecialAsk
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class SpecialAskTest extends \PHPUnit_Framework_TestCase {

	private $testEnvironment;

	protected function setUp() {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->testEnvironment->registerObject( 'Store', $store );
	}

	protected function tearDown() {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\MediaWiki\Specials\SpecialAsk',
			new SpecialAsk()
		);
	}

	public function testExecuteWithValidUser() {

		$outputPage = $this->getMockBuilder( '\OutputPage' )
			->disableOriginalConstructor()
			->getMock();

		$outputPage->expects( $this->atLeastOnce() )
			->method( 'addHtml' );

		$query = '';
		$instance = new SpecialAsk();

		$instance->getContext()->setTitle(
			Title::newFromText( 'SemanticMadiaWiki' )
		);

		$oldOutput = $instance->getOutput();

		$instance->getContext()->setOutput( $outputPage );
		$instance->getContext()->setUser( new MockSuperUser() );

		$instance->execute( $query );

		// Context is static avoid any succeeding tests to fail
		$instance->getContext()->setOutput( $oldOutput );
	}

}
