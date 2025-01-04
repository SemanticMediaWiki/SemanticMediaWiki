<?php

namespace SMW\Tests\MediaWiki\Specials\PendingTasks;

use SMW\MediaWiki\Specials\PendingTasks\IncompleteSetupTasks;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\MediaWiki\Specials\PendingTasks\IncompleteSetupTasks
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class IncompleteSetupTasksTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	public function testCanConstruct() {
		$this->assertInstanceOf(
			IncompleteSetupTasks::class,
			new IncompleteSetupTasks()
		);
	}

	public function testGetTitle() {
		$instance = new IncompleteSetupTasks();

		$this->assertEquals(
			'smw-pendingtasks-tab-setup',
			$instance->getTitle()
		);
	}

	public function testGetHtml() {
		$setupFile = $this->getMockBuilder( '\SMW\SetupFile' )
			->disableOriginalConstructor()
			->getMock();

		$setupFile->expects( $this->atLeastOnce() )
			->method( 'findIncompleteTasks' )
			->willReturn( [ 'Foo', 'Bar' ] );

		$instance = new IncompleteSetupTasks(
			$setupFile
		);

		$this->assertContains(
			'<ul><li>⧼Foo⧽</li><li>⧼Bar⧽</li></ul>',
			$instance->getHtml()
		);
	}

}
