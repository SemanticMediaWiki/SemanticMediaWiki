<?php

namespace SMW\Tests\MediaWiki\Specials\PendingTasks;

use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\Specials\PendingTasks\IncompleteSetupTasks;
use SMW\SetupFile;

/**
 * @covers \SMW\MediaWiki\Specials\PendingTasks\IncompleteSetupTasks
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.2
 *
 * @author mwjames
 */
class IncompleteSetupTasksTest extends TestCase {

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
		$setupFile = $this->getMockBuilder( SetupFile::class )
			->disableOriginalConstructor()
			->getMock();

		$setupFile->expects( $this->atLeastOnce() )
			->method( 'findIncompleteTasks' )
			->willReturn( [ 'Foo', 'Bar' ] );

		$instance = new IncompleteSetupTasks(
			$setupFile
		);

		$this->assertStringContainsString(
			'<ul><li>⧼Foo⧽</li><li>⧼Bar⧽</li></ul>',
			$instance->getHtml()
		);
	}

}
