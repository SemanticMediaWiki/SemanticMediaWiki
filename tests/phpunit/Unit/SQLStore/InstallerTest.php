<?php

namespace SMW\Tests\SQLStore;

use SMW\SQLStore\Installer;

/**
 * @covers \SMW\SQLStore\Installer
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class InstallerTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$tableSchemaManager = $this->getMockBuilder( '\SMW\SQLStore\TableSchemaManager' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\SQLStore\Installer',
			new Installer( $tableSchemaManager )
		);
	}

	public function testInstall() {

		$tableSchemaManager = $this->getMockBuilder( '\SMW\SQLStore\TableSchemaManager' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new Installer( $tableSchemaManager );

		$this->assertTrue(
			$instance->install()
		);
	}

	public function testInstallNonVerbose() {

		$tableSchemaManager = $this->getMockBuilder( '\SMW\SQLStore\TableSchemaManager' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new Installer( $tableSchemaManager );

		$this->assertTrue(
			$instance->install( false )
		);
	}

	public function testUninstall() {

		$tableSchemaManager = $this->getMockBuilder( '\SMW\SQLStore\TableSchemaManager' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new Installer( $tableSchemaManager );

		$this->assertTrue(
			$instance->uninstall()
		);
	}

}
