<?php

namespace SMW\Tests\MediaWiki\Hooks;

use MediaWiki\Title\Title;
use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\Hooks\ArticleFromTitle;
use SMW\MediaWiki\Page\ConceptPage;
use SMW\MediaWiki\Page\PropertyPage;
use SMW\Store;

/**
 * @covers \SMW\MediaWiki\Hooks\ArticleFromTitle
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.0
 *
 * @author mwjames
 */
class ArticleFromTitleTest extends TestCase {

	private $store;

	protected function setUp(): void {
		parent::setUp();

		$this->store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			ArticleFromTitle::class,
			new ArticleFromTitle( $this->store )
		);
	}

	/**
	 * @dataProvider namespaceProvider
	 */
	public function testProcess( $namespace, $expected ) {
		$title = $this->createMock( Title::class );
		$title->expects( $this->any() )
			->method( 'canExist' )
			->willReturn( true );

		$title->expects( $this->atLeastOnce() )
			->method( 'getNamespace' )
			->willReturn( $namespace );

		$article = $this->getMockBuilder( '\Article' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new ArticleFromTitle( $this->store );
		$instance->process( $title, $article );

		$this->assertInstanceOf(
			$expected,
			$article
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
