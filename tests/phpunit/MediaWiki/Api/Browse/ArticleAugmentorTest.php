<?php

namespace SMW\Tests\MediaWiki\Api\Browse;

use SMW\MediaWiki\Api\Browse\ArticleAugmentor;

/**
 * @covers \SMW\MediaWiki\Api\Browse\ArticleAugmentor
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class ArticleAugmentorTest extends \PHPUnit\Framework\TestCase {

	public function testCanConstruct() {
		$titleFactory = $this->getMockBuilder( '\SMW\MediaWiki\TitleFactory' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			ArticleAugmentor::class,
			new ArticleAugmentor( $titleFactory )
		);
	}

	public function testAugmentOnFullText() {
		$res = [
			'query' => [
				'Foo#0' => [
					'id' => 42,
					'label' => 'Foo',
					'key' => 'Foo',
					'ns' => 0
				]
			],
			'query-continue-offset' => 0,
			'version' => 1,
			'meta' => [
				'type'  => 'property',
				'limit' => 50,
				'count' => 1
			]
		];

		$parameters = [
			'fullText' => true
		];

		$expected = [
			'Foo#0' => [
				'label' => 'Foo',
				'key' => 'Foo',
				'ns' => 0,
				'fullText' => 'NS:FOO'
			]
		];

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->any() )
			->method( 'getFullText' )
			->willReturn( 'NS:FOO' );

		$titleFactory = $this->getMockBuilder( '\SMW\MediaWiki\TitleFactory' )
			->disableOriginalConstructor()
			->getMock();

		$titleFactory->expects( $this->any() )
			->method( 'newFromID' )
			->with(	42 )
			->willReturn( $title );

		$instance = new ArticleAugmentor(
			$titleFactory
		);

		$instance->augment( $res, $parameters );

		$this->assertEquals(
			$expected,
			$res['query']
		);
	}

	public function testAugmentOnFullURL() {
		$res = [
			'query' => [
				'Foo#0' => [
					'id' => 42,
					'label' => 'Foo',
					'key' => 'Foo',
					'ns' => 0
				]
			],
			'query-continue-offset' => 0,
			'version' => 1,
			'meta' => [
				'type'  => 'property',
				'limit' => 50,
				'count' => 1
			]
		];

		$parameters = [
			'fullURL' => true
		];

		$expected = [
			'Foo#0' => [
				'label' => 'Foo',
				'key' => 'Foo',
				'ns' => 0,
				'fullURL' => 'http://example.org/FOO'
			]
		];

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->any() )
			->method( 'getFullURL' )
			->willReturn( 'http://example.org/FOO' );

		$titleFactory = $this->getMockBuilder( '\SMW\MediaWiki\TitleFactory' )
			->disableOriginalConstructor()
			->getMock();

		$titleFactory->expects( $this->any() )
			->method( 'newFromID' )
			->with(	42 )
			->willReturn( $title );

		$instance = new ArticleAugmentor(
			$titleFactory
		);

		$instance->augment( $res, $parameters );

		$this->assertEquals(
			$expected,
			$res['query']
		);
	}

}
