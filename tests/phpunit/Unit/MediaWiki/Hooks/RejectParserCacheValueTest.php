<?php

namespace SMW\Tests\MediaWiki\Hooks;

use SMW\DIWikiPage;
use SMW\MediaWiki\Hooks\RejectParserCacheValue;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\MediaWiki\Hooks\RejectParserCacheValue
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class RejectParserCacheValueTest extends \PHPUnit_Framework_TestCase {

	private $testEnvironment;
	private $dependencyLinksValidator;

	protected function setUp() {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->dependencyLinksValidator = $this->getMockBuilder( '\SMW\SQLStore\QueryDependency\DependencyLinksValidator' )
			->disableOriginalConstructor()
			->getMock();
	}

	protected function tearDown() {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			RejectParserCacheValue::class,
			new RejectParserCacheValue( $this->dependencyLinksValidator )
		);
	}

	public function testProcessOnArchaicDependencies_RejectParserCacheValue() {

		$subject = DIWikiPage::newFromText( __METHOD__ );

		$this->dependencyLinksValidator->expects( $this->once() )
			->method( 'hasArchaicDependencies' )
			->with( $this->equalTo( $subject ) )
			->will( $this->returnValue( true ) );

		$instance = new RejectParserCacheValue(
			$this->dependencyLinksValidator
		);

		$this->assertFalse(
			$instance->process( $subject->getTitle() )
		);
	}

}
