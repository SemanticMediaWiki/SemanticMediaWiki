<?php

namespace SMW\Tests\MediaWiki\Jobs;

use RuntimeException;
use SMW\DIWikiPage;
use SMW\MediaWiki\Jobs\FulltextSearchTableRebuildJob;
use SMW\StoreFactory;
use SMW\Tests\TestEnvironment;
use SMW\Tests\Utils\Connection\TestDatabaseTableBuilder;

/**
 * @covers \SMW\MediaWiki\Jobs\FulltextSearchTableRebuildJob
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class FulltextSearchTableRebuildJobTest extends \PHPUnit\Framework\TestCase {

	private $testEnvironment;

	/**
	 * @var TestDatabaseTableBuilder
	 */
	protected $testDatabaseTableBuilder;

	/**
	 * @var bool
	 */
	protected $isUsableUnitTestDatabase = true;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->testDatabaseTableBuilder = TestDatabaseTableBuilder::getInstance(
			$this->getStore()
		);

		try {
			$this->testDatabaseTableBuilder->doBuild();
		} catch ( RuntimeException $e ) {
			$this->isUsableUnitTestDatabase = false;
		}

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
