<?php

namespace SMW\Tests\Page;

use SMW\DIWikiPage;
use SMW\Page\PropertyPage;

/**
 * @covers \SMW\Page\PropertyPage
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
	private $propertySpecificationReqMsgBuilder;

	protected function setUp() {
		parent::setUp();

		$this->propertySpecificationReqMsgBuilder = $this->getMockBuilder( '\SMW\PropertySpecificationReqMsgBuilder' )
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
			new PropertyPage( $this->title, $this->store, $this->propertySpecificationReqMsgBuilder )
		);
	}

	public function testGetHtml() {

		$instance = new PropertyPage(
			$this->title,
			$this->store,
			$this->propertySpecificationReqMsgBuilder
		);

		$this->assertEquals(
			'',
			$instance->view()
		);
	}
}
