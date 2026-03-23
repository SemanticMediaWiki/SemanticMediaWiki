<?php

namespace SMW\Tests\Unit\SQLStore\Installer;

use PHPUnit\Framework\TestCase;
use SMW\SetupFile;
use SMW\SQLStore\Installer\TableOptimizer;
use SMW\SQLStore\TableBuilder;
use SMW\SQLStore\TableBuilder\Table;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\SQLStore\Installer\TableOptimizer
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.2
 *
 * @author mwjames
 */
class TableOptimizerTest extends TestCase {

	private $spyMessageReporter;
	private $setupFile;
	private $tableBuilder;

	protected function setUp(): void {
		parent::setUp();

		$this->spyMessageReporter = TestEnvironment::getUtilityFactory()->newSpyMessageReporter();

		$this->setupFile = $this->getMockBuilder( SetupFile::class )
			->disableOriginalConstructor()
			->getMock();

		$this->tableBuilder = $this->getMockBuilder( TableBuilder::class )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			TableOptimizer::class,
			new TableOptimizer( $this->tableBuilder )
		);
	}

	public function testRunForTables() {
		$table = $this->getMockBuilder( Table::class )
			->disableOriginalConstructor()
			->getMock();

		$this->tableBuilder->expects( $this->atLeastOnce() )
			->method( 'optimize' );

		$this->setupFile->expects( $this->once() )
			->method( 'set' );

		$instance = new TableOptimizer(
			$this->tableBuilder
		);

		$instance->setMessageReporter( $this->spyMessageReporter );
		$instance->setSetupFile( $this->setupFile );

		$instance->runForTables( [ $table ] );
	}

}
