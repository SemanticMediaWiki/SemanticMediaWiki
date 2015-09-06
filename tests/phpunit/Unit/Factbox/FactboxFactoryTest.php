<?php

namespace SMW\Tests\Factbox;

use SMW\Factbox\FactboxFactory;

use Title;

/**
 * @covers \SMW\Factbox\FactboxFactory
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
class FactboxFactoryTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\Factbox\FactboxFactory',
			new FactboxFactory()
		);
	}

	public function testCanConstructCachedFactbox() {

		$instance = new FactboxFactory();

		$this->assertInstanceOf(
			'\SMW\Factbox\CachedFactbox',
			$instance->newCachedFactbox()
		);
	}

	public function testCanConstructFactbox() {

		$parserData = $this->getMockBuilder( '\SMW\ParserData' )
			->disableOriginalConstructor()
			->getMock();

		$contextSource = $this->getMockBuilder( '\IContextSource' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new FactboxFactory();

		$this->assertInstanceOf(
			'\SMW\Factbox\Factbox',
			$instance->newFactbox( $parserData, $contextSource )
		);
	}

}
