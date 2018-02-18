<?php

namespace SMW\Tests\Page;

use SMW\Page\RulePage;
use SMW\DIWikiPage;

/**
 * @covers \SMW\Page\RulePage
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class RulePageTest extends \PHPUnit_Framework_TestCase {

	private $title;
	private $ruleFactory;

	protected function setUp() {
		parent::setUp();

		$this->ruleFactory = $this->getMockBuilder( '\SMW\Rule\RuleFactory' )
			->disableOriginalConstructor()
			->getMock();

		$wikiPage = DIWikiPage::newFromText( __METHOD__, SMW_NS_RULE );
		$this->title = $wikiPage->getTitle();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			RulePage::class,
			new RulePage( $this->title, $this->ruleFactory )
		);
	}

	public function testGetHtml() {

		$instance = new RulePage(
			$this->title,
			$this->ruleFactory
		);

		$this->assertEquals(
			'',
			$instance->view()
		);
	}

}
