<?php

namespace SMW\Tests\MediaWiki\Page;

use SMW\DIWikiPage;
use SMW\MediaWiki\Page\ConceptPage;

/**
 * @covers \SMW\MediaWiki\Page\ConceptPage
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class ConceptPageTest extends \PHPUnit\Framework\TestCase {

	private $title;

	protected function setUp(): void {
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
