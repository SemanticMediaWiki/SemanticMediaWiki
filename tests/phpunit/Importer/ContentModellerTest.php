<?php

namespace SMW\Tests\Importer;

use PHPUnit\Framework\TestCase;
use SMW\Importer\ContentModeller;
use SMW\Importer\ImportContents;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\Importer\ContentModeller
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class ContentModellerTest extends TestCase {

	private $contentModeller;
	private $testEnvironment;

	protected function setUp(): void {
		parent::setUp();

		$this->contentModeller = new ContentModeller();

		$this->testEnvironment = new TestEnvironment();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			ContentModeller::class,
			new ContentModeller()
		);
	}

	public function testMakeContentList() {
		$contents = [
			'description' => '...',
			'import' => [
				'page' => 'Foo',
				'version' => 1
			]
		];

		$instance = new ContentModeller();

		$contents = $instance->makeContentList( 'Foo', $contents );

		foreach ( $contents as $content ) {
			$this->assertInstanceOf(
				ImportContents::class,
				$content
			);
		}
	}

}
