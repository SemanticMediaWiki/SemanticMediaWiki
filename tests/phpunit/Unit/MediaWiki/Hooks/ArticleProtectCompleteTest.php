<?php

namespace SMW\Tests\Unit\MediaWiki\Hooks;

use MediaWiki\User\User;
use PHPUnit\Framework\TestCase;
use SMW\Localizer\Message;
use SMW\MediaWiki\Hooks\ArticleProtectComplete;
use SMW\Settings;
use SMW\Tests\TestEnvironment;
use WikiPage;

/**
 * @covers \SMW\MediaWiki\Hooks\ArticleProtectComplete
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class ArticleProtectCompleteTest extends TestCase {

	private $spyLogger;
	private $testEnvironment;
	private $settings;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->spyLogger = $this->testEnvironment->getUtilityFactory()->newSpyLogger();

		$this->settings = $this->getMockBuilder( Settings::class )
			->disableOriginalConstructor()
			->getMock();
	}

	protected function tearDown(): void {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			ArticleProtectComplete::class,
			new ArticleProtectComplete( $this->settings, $this->spyLogger )
		);
	}

	public function testProcessOnSelfInvokedReason() {
		$instance = new ArticleProtectComplete( $this->settings, $this->spyLogger );

		$wikiPage = $this->getMockBuilder( WikiPage::class )
			->disableOriginalConstructor()
			->getMock();

		$user = $this->getMockBuilder( User::class )
			->disableOriginalConstructor()
			->getMock();

		$protections = [];
		$reason = Message::get( 'smw-edit-protection-auto-update' );

		$this->assertTrue(
			$instance->onArticleProtectComplete( $wikiPage, $user, $protections, $reason )
		);

		$this->assertStringContainsString(
			'No changes required, invoked by own process',
			$this->spyLogger->getMessagesAsString()
		);
	}

}
