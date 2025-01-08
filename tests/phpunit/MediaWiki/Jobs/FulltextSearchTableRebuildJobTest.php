<?php

namespace SMW\Tests\MediaWiki\Jobs;

use SMW\DIWikiPage;
use SMW\MediaWiki\Jobs\FulltextSearchTableRebuildJob;
use SMW\StoreFactory;
use SMW\Tests\SMWIntegrationTestCase;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\MediaWiki\Jobs\FulltextSearchTableRebuildJob
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class FulltextSearchTableRebuildJobTest extends SMWIntegrationTestCase {

	private $testEnvironment;

	/**
	 * @var bool
	 */
	protected $isUsableUnitTestDatabase = true;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->testEnvironment->registerObject( 'Store', $this->getStore() );
	}

	protected function tearDown(): void {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {
		$title = $this->getMockBuilder( 'Title' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'SMW\MediaWiki\Jobs\FulltextSearchTableRebuildJob',
			new FulltextSearchTableRebuildJob( $title )
		);
	}

	/**
	 * @dataProvider parametersProvider
	 */
	public function testRunJob( $parameters ) {
		$subject = DIWikiPage::newFromText( __METHOD__ );

		$instance = new FulltextSearchTableRebuildJob(
			$subject->getTitle(),
			$parameters
		);

		$this->assertTrue(
			$instance->run()
		);
	}

	public function parametersProvider() {
		$provider[] = [
			[]
		];

		$provider[] = [
			[ 'table' => 'Foo' ]
		];

		$provider[] = [
			[ 'mode' => 'full' ]
		];

		return $provider;
	}

	protected function getStore() {
		return StoreFactory::getStore();
	}

}
