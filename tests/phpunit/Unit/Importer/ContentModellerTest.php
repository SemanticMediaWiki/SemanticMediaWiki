<?php

namespace SMW\Tests\Importer;

use SMW\Importer\ContentModeller;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\Importer\ContentModeller
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class ContentModellerTest extends \PHPUnit_Framework_TestCase {

	private $contentModeller;
	private $testEnvironment;
	private $fixtures;

	protected function setUp() {
		parent::setUp();

		$this->contentModeller = new ContentModeller();

		$this->testEnvironment = new TestEnvironment();
		$this->fixtures = __DIR__ . '/Fixtures';
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
				'\SMW\Importer\ImportContents',
				$content
			);
		}
	}

}
