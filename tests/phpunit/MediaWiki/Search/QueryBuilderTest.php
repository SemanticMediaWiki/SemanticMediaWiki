<?php

namespace SMW\Tests\MediaWiki\Search;

use SMW\MediaWiki\Search\QueryBuilder;

/**
 * @covers \SMW\MediaWiki\Search\QueryBuilder
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class QueryBuilderTest extends \PHPUnit\Framework\TestCase {

	private $webRequest;
	private $store;

	protected function setUp(): void {
		$this->store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->webRequest = $this->getMockBuilder( '\WebRequest' )
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
			'\SMWQuery',
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

		$description = $this->getMockBuilder( '\SMW\Query\Language\ThingDescription' )
			->disableOriginalConstructor()
			->getMock();

		$query = $this->getMockBuilder( '\SMWQuery' )
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

		$query = $this->getMockBuilder( '\SMWQuery' )
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

		$this->webRequest->expects( $this->at( 0 ) )
			->method( 'getVal' )
			->with( 'smw-form' )
			->willReturn( 'foo' );

		$this->webRequest->expects( $this->at( 1 ) )
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

		$this->webRequest->expects( $this->at( 0 ) )
			->method( 'getVal' )
			->with( 'smw-form' )
			->willReturn( 'foo-2' );

		$this->webRequest->expects( $this->at( 1 ) )
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

		$this->webRequest->expects( $this->at( 0 ) )
			->method( 'getVal' )
			->with( 'smw-form' )
			->willReturn( 'open' );

		$this->webRequest->expects( $this->at( 1 ) )
			->method( 'getArray' )
			->with( 'property' )
			->willReturn( [ 'Bar' ] );

		$this->webRequest->expects( $this->at( 2 ) )
			->method( 'getArray' )
			->with( 'pvalue' )
			->willReturn( [ 42 ] );

		$this->webRequest->expects( $this->at( 3 ) )
			->method( 'getArray' )
			->with( 'op' )
			->willReturn( [ 'OR' ] );

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
