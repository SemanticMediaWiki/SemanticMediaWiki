<?php

namespace SMW\Tests\MediaWiki;

use MediaWiki\Title\Title;
use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\Page\ConceptPage;
use SMW\MediaWiki\Page\PropertyPage;
use SMW\MediaWiki\PageFactory;
use SMW\Store;

/**
 * @covers \SMW\MediaWiki\PageFactory
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class PageFactoryTest extends TestCase {

	private $store;

	protected function setUp(): void {
		parent::setUp();

		$this->store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			PageFactory::class,
			new PageFactory( $this->store )
		);
	}

	public function testNewPageFromNotRegisteredNamespaceThrowsException() {
		$title = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->atLeastOnce() )
			->method( 'getNamespace' )
			->willReturn( NS_MAIN );

		$instance = new PageFactory( $this->store );

		$this->expectException( 'RuntimeException' );
		$instance->newPageFromTitle( $title );
	}

	/**
	 * @dataProvider namespaceProvider
	 */
	public function testNewPageFromTitle( $namespace, $expected ) {
		$title = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->any() )
			->method( 'canExist' )
			->willReturn( true );

		$title->expects( $this->atLeastOnce() )
			->method( 'getNamespace' )
			->willReturn( $namespace );

		$instance = new PageFactory( $this->store );

		$this->assertInstanceOf(
			$expected,
			$instance->newPageFromTitle( $title )
		);
	}

	public function namespaceProvider() {
		$provider[] = [
			SMW_NS_PROPERTY,
			PropertyPage::class
		];

		$provider[] = [
			SMW_NS_CONCEPT,
			ConceptPage::class
		];

		return $provider;
	}

}
