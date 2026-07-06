<?php

namespace SMW\Tests\Unit;

use PHPUnit\Framework\TestCase;
use SMW\Query\DescriptionFactory;
use SMW\Query\Language\Description;
use SMW\Query\Parser;
use SMW\Query\PrintRequestFactory;
use SMW\Query\ProfileAnnotatorFactory;
use SMW\Query\Query;
use SMW\Query\QueryResult;
use SMW\QueryFactory;
use SMW\RequestOptions;
use SMW\Store;
use SMW\StringCondition;

/**
 * @covers \SMW\QueryFactory
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.4
 *
 * @author mwjames
 */
class QueryFactoryTest extends TestCase {

	public function testCanConstruct() {
		$this->assertInstanceOf(
			QueryFactory::class,
			new QueryFactory()
		);
	}

	public function testCanConstructProfileAnnotatorFactory() {
		$instance = new QueryFactory();

		$this->assertInstanceOf(
			ProfileAnnotatorFactory::class,
			$instance->newProfileAnnotatorFactory()
		);
	}

	public function testCanConstructQuery() {
		$description = $this->getMockBuilder( Description::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$instance = new QueryFactory();

		$this->assertInstanceOf(
			Query::class,
			$instance->newQuery( $description )
		);
	}

	public function testCanConstructDescriptionFactory() {
		$instance = new QueryFactory();

		$this->assertInstanceOf(
			DescriptionFactory::class,
			$instance->newDescriptionFactory()
		);
	}

	public function testCanConstructPrintRequestFactory() {
		$instance = new QueryFactory();

		$this->assertInstanceOf(
			PrintRequestFactory::class,
			$instance->newPrintRequestFactory()
		);
	}

	public function testCanConstructRequestOptions() {
		$instance = new QueryFactory();

		$this->assertInstanceOf(
			RequestOptions::class,
			$instance->newRequestOptions()
		);
	}

	public function testCanConstructStringCondition() {
		$instance = new QueryFactory();

		$this->assertInstanceOf(
			StringCondition::class,
			$instance->newStringCondition( '', StringCondition::STRCOND_PRE )
		);
	}

	public function testCanConstructQueryParser() {
		$instance = new QueryFactory();

		$this->assertInstanceOf(
			Parser::class,
			$instance->newQueryParser()
		);
	}

	public function testCanConstructQueryResult() {
		$description = $this->getMockBuilder( Description::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$query = $this->getMockBuilder( Query::class )
			->disableOriginalConstructor()
			->getMock();

		$query->expects( $this->once() )
			->method( 'getDescription' )
			->willReturn( $description );

		$instance = new QueryFactory();

		$this->assertInstanceOf(
			QueryResult::class,
			$instance->newQueryResult( $store, $query )
		);
	}

}
