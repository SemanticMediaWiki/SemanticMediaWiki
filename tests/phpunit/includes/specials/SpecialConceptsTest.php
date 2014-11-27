<?php

namespace SMW\Test;

use SMW\Tests\Utils\UtilityFactory;

use SMW\SpecialConcepts;
use SMW\DIWikiPage;
use SMWDataItem;

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
			'span class="smw-sp-concept-docu"',
			$this->getText()
		);
	}

	/**
	 * @depends testExecute
	 */
	public function testGetHtmlForAnEmptySubject() {

		$instance = $this->getInstance();

		$this->stringValidator->assertThatStringContains(
			'span class="smw-sp-concept-empty"',
			$instance->getHtml( array(), 0, 0, 0 )
		);
	}

	/**
	 * @depends testGetHtmlForAnEmptySubject
	 */
	public function testGetHtmlForSingleSubject() {

		$subject  = DIWikiPage::newFromTitle( Title::newFromText( __METHOD__ ) );
		$instance = $this->getInstance();

		$this->stringValidator->assertThatStringContains(
			'span class="smw-sp-concept-count"',
			$instance->getHtml( array( $subject ), 1, 1, 1 )
		);
	}

}
