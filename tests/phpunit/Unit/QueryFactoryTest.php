<?php

namespace SMW\Tests;

use SMW\QueryFactory;
use SMW\StringCondition;

/**
 * @covers \SMW\QueryFactory
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class QueryFactoryTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\QueryFactory',
			new QueryFactory()
		);
	}

	public function testCanConstructQuery() {

		$description = $this->getMockBuilder( '\SMW\Query\Language\Description' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$instance = new QueryFactory();

		$this->assertInstanceOf(
			'\SMWQuery',
			$instance->newQuery( $description )
		);
	}

	public function testCanConstructDescriptionFactory() {

		$instance = new QueryFactory();

		$this->assertInstanceOf(
			'\SMW\Query\DescriptionFactory',
			$instance->newDescriptionFactory()
		);
	}

	public function testCanConstructPrintRequestFactory() {

		$instance = new QueryFactory();

		$this->assertInstanceOf(
			'\SMW\Query\PrintRequestFactory',
			$instance->newPrintRequestFactory()
		);
	}

	public function testCanConstructRequestOptions() {

		$instance = new QueryFactory();

		$this->assertInstanceOf(
			'\SMW\RequestOptions',
			$instance->newRequestOptions()
		);
	}

	public function testCanConstructStringCondition() {

		$instance = new QueryFactory();

		$this->assertInstanceOf(
			'\SMW\StringCondition',
			$instance->newStringCondition( '', StringCondition::STRCOND_PRE )
		);
	}

	public function testCanConstructQueryParser() {

		$instance = new QueryFactory();

		$this->assertInstanceOf(
			'\SMWQueryParser',
			$instance->newQueryParser()
		);
	}

}
