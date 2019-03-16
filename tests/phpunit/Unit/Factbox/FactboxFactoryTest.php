<?php

namespace SMW\Tests\Factbox;

use SMW\Factbox\FactboxFactory;

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
			FactboxFactory::class,
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

	public function testCanConstructCheckMagicWords() {

		$instance = new FactboxFactory();

		$this->assertInstanceOf(
			'\SMW\Factbox\CheckMagicWords',
			$instance->newCheckMagicWords( [] )
		);
	}

	public function testCanConstructFactbox() {

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->any() )
			->method( 'getNamespace' )
			->will( $this->returnValue( NS_MAIN ) );

		$parserOutput = $this->getMockBuilder( '\ParserOutput' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new FactboxFactory();

		$this->assertInstanceOf(
			'\SMW\Factbox\Factbox',
			$instance->newFactbox( $title, $parserOutput )
		);
	}

}
