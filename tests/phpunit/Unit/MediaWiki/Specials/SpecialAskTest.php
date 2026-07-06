<?php

namespace SMW\Tests\Unit\MediaWiki\Specials;

use MediaWiki\MediaWikiServices;
use MediaWiki\Output\OutputPage;
use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\Specials\SpecialAsk;
use SMW\Query\QuerySourceFactory;
use SMW\Services\ServicesFactory as ApplicationFactory;
use SMW\Settings;
use SMW\Tests\TestEnvironment;
use SMW\Tests\Utils\Mock\MockSuperUser;

/**
 * @covers \SMW\MediaWiki\Specials\SpecialAsk
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class SpecialAskTest extends TestCase {

	private $testEnvironment;
	private $querySourceFactory;
	private $settings;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		// Use real services from the locator so QuerySourceFactory can resolve
		// the SQL store and SpecialAsk can render through the default flow.
		$applicationFactory = ApplicationFactory::getInstance();
		$this->querySourceFactory = $applicationFactory->getQuerySourceFactory();
		$this->settings = $applicationFactory->getSettings();
	}

	protected function tearDown(): void {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {
		$querySourceFactory = $this->createMock( QuerySourceFactory::class );
		$settings = $this->createMock( Settings::class );

		$this->assertInstanceOf(
			SpecialAsk::class,
			new SpecialAsk( $querySourceFactory, $settings )
		);
	}

	public function testExecuteWithValidUser() {
		$outputPage = $this->getMockBuilder( OutputPage::class )
			->disableOriginalConstructor()
			->getMock();

		$outputPage->expects( $this->atLeastOnce() )
			->method( 'addHtml' );

		$query = '';
		$instance = new SpecialAsk( $this->querySourceFactory, $this->settings );

		$instance->getContext()->setTitle(
			MediaWikiServices::getInstance()->getTitleFactory()->newFromText( 'SemanticMadiaWiki' )
		);

		$oldOutput = $instance->getOutput();

		$instance->getContext()->setOutput( $outputPage );
		$instance->getContext()->setUser( new MockSuperUser() );

		$instance->execute( $query );

		// Context is static avoid any succeeding tests to fail
		$instance->getContext()->setOutput( $oldOutput );
	}

}
