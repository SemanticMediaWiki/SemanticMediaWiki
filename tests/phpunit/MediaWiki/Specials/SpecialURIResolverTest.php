<?php

namespace SMW\Tests\MediaWiki\Specials;

use SMW\MediaWiki\Specials\SpecialURIResolver;
use SMW\Tests\TestEnvironment;
use Title;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\MediaWiki\Specials\SpecialURIResolver
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class SpecialURIResolverTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	private $testEnvironment;
	private $stringValidator;

	protected function setUp() : void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();
	}

	protected function tearDown() : void {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testExecuteOnEmptyContext() {

		$instance = new SpecialURIResolver();

		$instance->getContext()->setTitle(
			Title::newFromText( 'SpecialURIResolver' )
		);

		$instance->execute( '' );

		$this->assertContains(
			'https://www.w3.org/2001/tag/issues.html#httpRange-14',
			$instance->getOutput()->getHTML()
		);
	}

}
