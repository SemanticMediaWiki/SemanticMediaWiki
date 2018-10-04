<?php

namespace SMW\Test;

use SMW\DIWikiPage;
use SMW\SpecialConcepts;
use SMW\Tests\Utils\UtilityFactory;
use Title;
use SMW\Tests\TestEnvironment;

/**
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class SpecialConceptsTest extends \PHPUnit_Framework_TestCase {

	private $stringValidator;
	private $testEnvironment;

	protected function setUp() {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();
		$this->stringValidator = $this->testEnvironment->newValidatorFactory()->newStringValidator();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			SpecialConcepts::class,
			new SpecialConcepts()
		);
	}

	public function testExecute() {

		$expected = 'p class="smw-special-concept-docu plainlinks"';

		$outputPage = $this->getMockBuilder( '\OutputPage' )
			->disableOriginalConstructor()
			->getMock();

		$outputPage->expects( $this->atLeastOnce() )
			->method( 'addHtml' )
			 ->with( $this->stringContains( $expected ) );

		$query = '';
		$instance = new SpecialConcepts();

		$instance->getContext()->setTitle(
			Title::newFromText( 'SemanticMadiaWiki' )
		);

		$oldOutput = $instance->getOutput();

		$instance->getContext()->setOutput( $outputPage );
		$instance->execute( $query );

		// Context is static avoid any succeeding tests to fail
		$instance->getContext()->setOutput( $oldOutput );
	}

	/**
	 * @depends testExecute
	 */
	public function testGetHtmlForAnEmptySubject() {

		$instance = new SpecialConcepts();

		$this->stringValidator->assertThatStringContains(
			'div class="smw-special-concept-empty"',
			$instance->getHtml( [], 0, 0 )
		);
	}

	/**
	 * @depends testGetHtmlForAnEmptySubject
	 */
	public function testGetHtmlForSingleSubject() {

		$subject  = DIWikiPage::newFromText( __METHOD__ );
		$instance = new SpecialConcepts();

		$this->stringValidator->assertThatStringContains(
			'div class="smw-special-concept-count"',
			$instance->getHtml( [ $subject ], 1, 0 )
		);
	}

}
