<?php

namespace SMW\Tests\MediaWiki\Page;

use SMW\DIWikiPage;
use SMW\MediaWiki\Page\PropertyPage;

/**
 * @covers \SMW\MediaWiki\Page\PropertyPage
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class PropertyPageTest extends \PHPUnit_Framework_TestCase {

	private $title;
	private $store;
	private $declarationExaminerFactory;

	protected function setUp() {
		parent::setUp();

		$this->declarationExaminerFactory = $this->getMockBuilder( '\SMW\Property\DeclarationExaminerFactory' )
			->disableOriginalConstructor()
			->getMock();

		$this->store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->title = DIWikiPage::newFromText( __METHOD__, SMW_NS_PROPERTY )->getTitle();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			PropertyPage::class,
			new PropertyPage( $this->title, $this->store, $this->declarationExaminerFactory )
		);
	}

	public function testGetHtml() {

		$instance = new PropertyPage(
			$this->title,
			$this->store,
			$this->declarationExaminerFactory
		);

		$this->assertEquals(
			'',
			$instance->view()
		);
	}
}
