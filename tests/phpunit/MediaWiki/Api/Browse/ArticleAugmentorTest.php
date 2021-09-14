<?php

namespace SMW\Tests\MediaWiki\Api\Browse;

use SMW\MediaWiki\Api\Browse\ArticleAugmentor;

/**
 * @covers \SMW\MediaWiki\Api\Browse\ArticleAugmentor
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class ArticleAugmentorTest extends \PHPUnit_Framework_TestCase {

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
			->will( $this->returnValue( 'NS:FOO' ) );

		$titleFactory = $this->getMockBuilder( '\SMW\MediaWiki\TitleFactory' )
			->disableOriginalConstructor()
			->getMock();

		$titleFactory->expects( $this->any() )
			->method( 'newFromID' )
			->with(	$this->equalTo( 42 ) )
			->will( $this->returnValue( $title ) );

		$instance = new ArticleAugmentor(
			$titleFactory
		);

		$instance->augment( $res, $parameters );

		$this->assertEquals(
			$res['query'],
			$expected
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
			->will( $this->returnValue( 'http://example.org/FOO' ) );

		$titleFactory = $this->getMockBuilder( '\SMW\MediaWiki\TitleFactory' )
			->disableOriginalConstructor()
			->getMock();

		$titleFactory->expects( $this->any() )
			->method( 'newFromID' )
			->with(	$this->equalTo( 42 ) )
			->will( $this->returnValue( $title ) );

		$instance = new ArticleAugmentor(
			$titleFactory
		);

		$instance->augment( $res, $parameters );

		$this->assertEquals(
			$res['query'],
			$expected
		);
	}

}
