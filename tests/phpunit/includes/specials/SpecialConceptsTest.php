<?php

namespace SMW\Test;

use SMW\DIWikiPage;
use SMW\SpecialConcepts;
use SMW\Tests\Utils\UtilityFactory;
use Title;

/**
 * @covers SMW\SpecialConcepts
 *
 *
 * @group SMW
 * @group SMWExtension
 * @group SpecialPage
 * @group medium
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class SpecialConceptsTest extends SpecialPageTestCase {

	private $stringValidator;

	protected function setUp() {
		parent::setUp();

		$this->stringValidator = UtilityFactory::getInstance()->newValidatorFactory()->newStringValidator();
	}

	public function getClass() {
		return '\SMW\SpecialConcepts';
	}

	/**
	 * @return SpecialConcepts
	 */
	protected function getInstance() {
		return new SpecialConcepts();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf( $this->getClass(), $this->getInstance() );
	}

	public function testExecute() {

		$this->execute();

		$this->stringValidator->assertThatStringContains(
			'p class="smw-special-concept-docu plainlinks"',
			$this->getText()
		);
	}

	/**
	 * @depends testExecute
	 */
	public function testGetHtmlForAnEmptySubject() {

		$instance = $this->getInstance();

		$this->stringValidator->assertThatStringContains(
			'div class="smw-special-concept-empty"',
			$instance->getHtml( [], 0, 0, 0, 0 )
		);
	}

	/**
	 * @depends testGetHtmlForAnEmptySubject
	 */
	public function testGetHtmlForSingleSubject() {

		$subject  = DIWikiPage::newFromTitle( Title::newFromText( __METHOD__ ) );
		$instance = $this->getInstance();

		$this->stringValidator->assertThatStringContains(
			'div class="smw-special-concept-count"',
			$instance->getHtml( [ $subject ], 1, 0, 1, 1 )
		);
	}

}
