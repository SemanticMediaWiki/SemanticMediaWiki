<?php

namespace SMW\Tests\Unit\MediaWiki\Hooks;

use PHPUnit\Framework\TestCase;
use SMW\EventDispatcher\EventDispatcher;
use SMW\MediaWiki\Hooks\RevisionFromEditComplete;
use SMW\Property\AnnotatorFactory;
use SMW\Schema\SchemaFactory;
use SMW\Store;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\MediaWiki\Hooks\RevisionFromEditComplete
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 1.9
 *
 * @author mwjames
 */
class RevisionFromEditCompleteTest extends TestCase {

	private $testEnvironment;
	private $eventDispatcher;
	private $propertyAnnotatorFactory;
	private $schemaFactory;
	private $store;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->eventDispatcher = $this->getMockBuilder( EventDispatcher::class )
			->disableOriginalConstructor()
			->getMock();

		$this->propertyAnnotatorFactory = $this->getMockBuilder( AnnotatorFactory::class )
			->disableOriginalConstructor()
			->getMock();

		$this->schemaFactory = $this->getMockBuilder( SchemaFactory::class )
			->disableOriginalConstructor()
			->getMock();
	}

	protected function tearDown(): void {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			RevisionFromEditComplete::class,
			new RevisionFromEditComplete(
				$this->propertyAnnotatorFactory,
				$this->schemaFactory,
				$this->store,
				$this->eventDispatcher
			)
		);
	}

}
