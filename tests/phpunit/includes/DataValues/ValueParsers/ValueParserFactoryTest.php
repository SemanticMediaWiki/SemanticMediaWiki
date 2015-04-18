<?php

namespace SMW\Tests\DataValues\ValueParsers;

use SMW\DataValues\ValueParsers\ValueParserFactory;

/**
 * @covers \SMW\DataValues\ValueParsers\ValueParserFactory
 *
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 */
class ValueParserFactoryTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\DataValues\ValueParsers\ValueParserFactory',
			new ValueParserFactory()
		);

		ValueParserFactory::clear();

		$this->assertInstanceOf(
			'\SMW\DataValues\ValueParsers\ValueParserFactory',
			ValueParserFactory::getInstance()
		);
	}

	public function testCanConstructImportValueParser() {

		$instance = new ValueParserFactory();

		$this->assertInstanceOf(
			'\SMW\DataValues\ValueParsers\ImportValueParser',
			$instance->newImportValueParser()
		);
	}

}
