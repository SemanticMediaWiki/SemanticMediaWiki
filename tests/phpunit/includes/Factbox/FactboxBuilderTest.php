<?php

namespace SMW\Tests\Factbox;

use SMW\Factbox\FactboxBuilder;

use Title;

/**
 * @covers \SMW\Factbox\FactboxBuilder
 *
 * @group SMW
 * @group SMWExtension
 *
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
class FactboxBuilderTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\Factbox\FactboxBuilder',
			new FactboxBuilder()
		);
	}

	public function testCanConstructFactboxCache() {

		$outputPage = $this->getMockBuilder( '\OutputPage' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new FactboxBuilder();

		$this->assertInstanceOf(
			'\SMW\FactboxCache',
			$instance->newFactboxCache( $outputPage )
		);
	}

	public function testCanConstructFactbox() {

		$parserData = $this->getMockBuilder( '\SMW\ParserData' )
			->disableOriginalConstructor()
			->getMock();

		$contextSource = $this->getMockBuilder( '\IContextSource' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new FactboxBuilder();

		$this->assertInstanceOf(
			'\SMW\Factbox',
			$instance->newFactbox( $parserData, $contextSource )
		);
	}

}
