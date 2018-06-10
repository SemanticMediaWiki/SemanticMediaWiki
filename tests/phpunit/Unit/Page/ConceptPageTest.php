<?php

namespace SMW\Tests\Page;

use SMW\DIWikiPage;
use SMW\Page\ConceptPage;

/**
 * @covers \SMW\Page\ConceptPage
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class ConceptPageTest extends \PHPUnit_Framework_TestCase {

	private $title;

	protected function setUp() {
		parent::setUp();

		$subject = DIWikiPage::newFromText( __METHOD__, SMW_NS_CONCEPT );
		$this->title = $subject->getTitle();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			ConceptPage::class,
			new ConceptPage( $this->title )
		);
	}

}
