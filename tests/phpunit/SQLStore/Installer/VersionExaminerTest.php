<?php

namespace SMW\Tests\SQLStore\Installer;

use SMW\MediaWiki\Database;
use SMW\SetupFile;
use SMW\SQLStore\Installer\VersionExaminer;
use SMW\Tests\TestEnvironment;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\SQLStore\TableBuilder\VersionExaminer
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class VersionExaminerTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	private $spyMessageReporter;
	private SetupFile $setupFile;

	protected function setUp(): void {
		parent::setUp();

		$this->spyMessageReporter = TestEnvironment::getUtilityFactory()->newSpyMessageReporter();

		$this->setupFile = $this->getMockBuilder( SetupFile::class )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$connection = $this->getMockBuilder( '\Wikimedia\Rdbms\Database' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->assertInstanceOf(
			VersionExaminer::class,
			new VersionExaminer( $connection )
		);
	}

	public function testRequirements() {
		$connection = $this->getMockBuilder( '\Wikimedia\Rdbms\Database' )
			->disableOriginalConstructor()
			->onlyMethods( [ 'getServerInfo' ] )
			->getMockForAbstractClass();

		$connection->expects( $this->atLeastOnce() )
			->method( 'getServerInfo' )
			->willReturn( 1 );

		$connection->expects( $this->atLeastOnce() )
			->method( 'getType' )
			->willReturn( 'foo' );

		$instance = new VersionExaminer(
			$connection
		);

		$instance->setMessageReporter( $this->spyMessageReporter );
		$instance->setSetupFile( $this->setupFile );

		$requirements = $instance->defineDatabaseRequirements(
			[ 'foo' => 2 ]
		);

		$this->assertArrayHasKey( 'type', $requirements );
		$this->assertArrayHasKey( 'latest_version', $requirements );
		$this->assertArrayHasKey( 'minimum_version', $requirements );
	}

	public function testRequirements_InvalidDefined() {
		$connection = $this->getMockBuilder( '\Wikimedia\Rdbms\Database' )
			->disableOriginalConstructor()
			->onlyMethods( [ 'getServerInfo' ] )
			->getMockForAbstractClass();

		$connection->expects( $this->atLeastOnce() )
			->method( 'getType' )
			->willReturn( 'foo' );

		$instance = new VersionExaminer(
			$connection
		);

		$this->expectException( '\RuntimeException' );

		$requirements = $instance->defineDatabaseRequirements(
			[ 'foobar' => 2 ]
		);
	}

	public function testMeetsVersionMinRequirement() {
		$connection = $this->getMockBuilder( '\Wikimedia\Rdbms\Database' )
			->disableOriginalConstructor()
			->onlyMethods( [ 'getServerInfo' ] )
			->getMockForAbstractClass();

		$connection->expects( $this->atLeastOnce() )
			->method( 'getServerInfo' )
			->willReturn( 1 );

		$connection->expects( $this->atLeastOnce() )
			->method( 'getType' )
			->willReturn( 'foo' );

		$this->setupFile->expects( $this->once() )
			->method( 'remove' );

		$instance = new VersionExaminer(
			$connection
		);

		$instance->setMessageReporter( $this->spyMessageReporter );
		$instance->setSetupFile( $this->setupFile );

		$this->assertTrue(
						$instance->meetsVersionMinRequirement( [ 'foo' => 1 ] )
		);
	}

	public function testMeetsVersionMinRequirement_FailsMinimumRequirement() {
		$connection = $this->getMockBuilder( '\Wikimedia\Rdbms\Database' )
			->disableOriginalConstructor()
			->onlyMethods( [ 'getServerInfo' ] )
			->getMockForAbstractClass();

		$connection->expects( $this->atLeastOnce() )
			->method( 'getServerInfo' )
			->willReturn( 1 );

		$connection->expects( $this->atLeastOnce() )
			->method( 'getType' )
			->willReturn( 'foo' );

		$this->setupFile->expects( $this->once() )
			->method( 'set' );

		$this->setupFile->expects( $this->once() )
			->method( 'finalize' );

		$instance = new VersionExaminer(
			$connection
		);

		$instance->setMessageReporter( $this->spyMessageReporter );
		$instance->setSetupFile( $this->setupFile );

		$this->assertFalse(
						$instance->meetsVersionMinRequirement( [ 'foo' => 2 ] )
		);

		$this->assertContains(
			"The `foo` database version of 1 doesn't meet the minimum requirement of 2",
			$this->spyMessageReporter->getMessagesAsString()
		);
	}

}
