<?php

namespace SMW\Tests\Unit\MediaWiki\Page;

use PHPUnit\Framework\TestCase;
use SMW\DataItems\WikiPage;
use SMW\MediaWiki\Page\PropertyPage;
use SMW\Property\DeclarationExaminerFactory;
use SMW\SQLStore\SQLStore;

/**
 * @covers \SMW\MediaWiki\Page\PropertyPage
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class PropertyPageTest extends TestCase {

	private $title;
	private $store;
	private $declarationExaminerFactory;

	protected function setUp(): void {
		parent::setUp();

		$this->declarationExaminerFactory = $this->getMockBuilder( DeclarationExaminerFactory::class )
			->disableOriginalConstructor()
			->getMock();

		$this->store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->title = WikiPage::newFromText( __METHOD__, SMW_NS_PROPERTY )->getTitle();
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

		$this->assertSame(
			null,
			$instance->view()
		);
	}
}
