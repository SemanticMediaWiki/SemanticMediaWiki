<?php

namespace SMW\Tests\Unit\MediaWiki\Search;

use MediaWiki\Request\WebRequest;
use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\Search\QueryBuilder;
use SMW\Query\Language\ThingDescription;
use SMW\Query\Query;
use SMW\Store;

/**
 * @covers \SMW\MediaWiki\Search\QueryBuilder
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class QueryBuilderTest extends TestCase {

	private $webRequest;
	private $store;

	protected function setUp(): void {
		$this->store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->webRequest = $this->getMockBuilder( WebRequest::class )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			QueryBuilder::class,
			new QueryBuilder( $this->webRequest )
		);
	}

	public function testGetQuery() {
		$instance = new QueryBuilder(
			$this->webRequest
		);

		$this->assertNull(
			$instance->getQuery( 'Foo' )
		);

		$this->assertInstanceOf(
			Query::class,
			$instance->getQuery( '[[Foo::bar]]' )
		);
	}

	public function testAddNamespaceCondition() {
		$this->webRequest->expects( $this->any() )
			->method( 'getCheck' )
			->with( 'ns6' )
			->willReturn( true );

		$instance = new QueryBuilder(
			$this->webRequest
		);

		$description = $this->getMockBuilder( ThingDescription::class )
			->disableOriginalConstructor()
			->getMock();

		$query = $this->getMockBuilder( Query::class )
			->disableOriginalConstructor()
			->getMock();

		$query->expects( $this->once() )
			->method( 'getDescription' )
			->willReturn( $description );

		$query->expects( $this->once() )
			->method( 'setDescription' );

		$instance->addNamespaceCondition( $query, [ 6 => true ] );
	}

	public function testAddSort() {
		$this->webRequest->expects( $this->any() )
			->method( 'getVal' )
			->with( 'sort' )
			->willReturn( 'recent' );

		$instance = new QueryBuilder(
			$this->webRequest
		);

		$query = $this->getMockBuilder( Query::class )
			->disableOriginalConstructor()
			->getMock();

		$query->expects( $this->once() )
			->method( 'setSortKeys' );

		$instance->addSort( $query );
	}

	public function testGetQueryString_EmptyFieldValues_ReturnsTermOnly() {
		$instance = new QueryBuilder(
			$this->webRequest,
			[ 'foo' ]
		);

		$this->assertEquals(
			'Foo',
			$instance->getQueryString( $this->store, 'Foo' )
		);
	}

	public function testGetQueryString_FormFieldValues() {
		$form_def = [
			'forms' => [
				'Foo' => [
					'Bar property'
				]
			]
		];

		$this->webRequest->expects( $this->once() )
			->method( 'getVal' )
			->with( 'smw-form' )
			->willReturn( 'foo' );

		$this->webRequest->expects( $this->once() )
			->method( 'getArray' )
			->with( 'barproperty' )
			->willReturn( [ 'Foobar' ] );

		$instance = new QueryBuilder(
			$this->webRequest,
			$form_def
		);

		$this->assertEquals(
			'<q>[[Bar property::Foobar]]</q>  Foo',
			$instance->getQueryString( $this->store, 'Foo' )
		);
	}

	public function testGetQueryString_DifferentFormsFieldValues() {
		$form_def = [
			'forms' => [
				'Foo-1' => [
					'Bar property'
				],
				'Foo-2' => [
					'Bar property'
				]
			]
		];

		$this->webRequest->expects( $this->once() )
			->method( 'getVal' )
			->with( 'smw-form' )
			->willReturn( 'foo-2' );

		$this->webRequest->expects( $this->once() )
			->method( 'getArray' )
			->with( 'barproperty' )
			->willReturn( [ '', 42 ] );

		$instance = new QueryBuilder(
			$this->webRequest,
			$form_def
		);

		$this->assertEquals(
			'<q>[[Bar property::42]]</q>  Foo',
			$instance->getQueryString( $this->store, 'Foo' )
		);
	}

	public function testGetQueryString_OpenFormFieldValues() {
		$form_def = [
			'forms' => [
				'open'
			]
		];

		$this->webRequest->expects( $this->once() )
			->method( 'getVal' )
			->with( 'smw-form' )
			->willReturn( 'open' );

		$this->webRequest->expects( $this->exactly( 3 ) )
			->method( 'getArray' )
			->willReturnCallback( static function ( $key ) {
				$map = [
					'property' => [ 'Bar' ],
					'pvalue'   => [ 42 ],
					'op'       => [ 'OR' ],
				];
				return $map[$key] ?? [];
			} );

		$instance = new QueryBuilder(
			$this->webRequest,
			$form_def
		);

		$this->assertEquals(
			'<q>[[Bar::42]] </q> OR Foo',
			$instance->getQueryString( $this->store, 'Foo' )
		);
	}

}
