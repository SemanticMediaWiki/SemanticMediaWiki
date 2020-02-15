<?php

namespace SMW\Tests\SQLStore\Installer;

use SMW\MediaWiki\Database;
use SMW\SQLStore\Installer\TableOptimizer;
use SMW\Tests\TestEnvironment;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\SQLStore\TableBuilder\TableOptimizer
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class TableOptimizerTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	private $spyMessageReporter;
	private $setupFile;
	private $tableBuilder;

	protected function setUp() : void {
		parent::setUp();

		$this->spyMessageReporter = TestEnvironment::getUtilityFactory()->newSpyMessageReporter();

		$this->setupFile = $this->getMockBuilder( '\SMW\SetupFile' )
			->disableOriginalConstructor()
			->getMock();

		$this->tableBuilder = $this->getMockBuilder( '\SMW\SQLStore\TableBuilder' )
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

		$table = $this->getMockBuilder( '\SMW\SQLStore\TableBuilder\Table' )
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
