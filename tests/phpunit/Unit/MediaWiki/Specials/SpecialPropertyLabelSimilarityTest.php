<?php

namespace SMW\Tests\Unit\MediaWiki\Specials;

use MediaWiki\MediaWikiServices;
use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\Specials\SpecialPropertyLabelSimilarity;
use SMW\Services\ServicesFactory as ApplicationFactory;
use SMW\SQLStore\SQLStore;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\MediaWiki\Specials\SpecialPropertyLabelSimilarity
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class SpecialPropertyLabelSimilarityTest extends TestCase {

	private $testEnvironment;
	private $store;
	private $settings;
	private $queryFactory;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		// PropertyLabelSimilarityLookup performs SQL via the SQLStore-typed
		// connection, so the test needs a real SQLStore (the previous setup
		// registered a non-SQL Store mock but the SUT explicitly resolved an
		// SQLStore via `getStore( SQLStore::class )`). Use the real default
		// store here to mirror the same indirection.
		$applicationFactory = ApplicationFactory::getInstance();
		$this->store = $applicationFactory->getStore( SQLStore::class );
		$this->settings = $applicationFactory->getSettings();
		$this->queryFactory = $applicationFactory->getQueryFactory();
	}

	protected function tearDown(): void {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanExecute() {
		$instance = new SpecialPropertyLabelSimilarity( $this->store, $this->settings, $this->queryFactory );

		$instance->getContext()->setTitle(
			MediaWikiServices::getInstance()->getTitleFactory()->newFromText( 'SpecialPropertyLabelSimilarity' )
		);

		$this->assertTrue(
			$instance->execute( '' )
		);
	}

}
