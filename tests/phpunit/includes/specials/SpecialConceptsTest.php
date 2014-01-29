<?php

namespace SMW\Test;

use SMW\SpecialConcepts;
use SMW\DIWikiPage;
use SMWDataItem;

use Title;

/**
 * @covers SMW\SpecialConcepts
 *
 * @ingroup Test
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

		$matches = array(
			'tag' => 'span',
			'attributes' => array( 'class' => 'smw-sp-concept-docu' )
		);

		$this->assertTag( $matches, $this->getText() );
	}

	/**
	 * @depends testExecute
	 */
	public function testGetHtmlForAnEmptySubject() {

		$instance = $this->getInstance();

		$matches = array(
			'tag' => 'span',
			'attributes' => array( 'class' => 'smw-sp-concept-empty' )
		);

		$this->assertTag(
			$matches,
			$instance->getHtml( array(), 0, 0, 0 )
		);

	}

	/**
	 * @depends testGetHtmlForAnEmptySubject
	 */
	public function testGetHtmlForSingleSubject() {

		$subject  = DIWikiPage::newFromTitle( Title::newFromText( __METHOD__ ) );
		$instance = $this->getInstance();

		$matches = array(
			'tag' => 'span',
			'attributes' => array( 'class' => 'smw-sp-concept-count' )
		);

		$this->assertTag(
			$matches,
			$instance->getHtml( array( $subject ), 1, 1, 1 )
		);

	}

}
