<?php

namespace SMW\Tests\DataValues;

use SMW\DataValues\ValueParserFactory;

/**
 * @covers \SMW\DataValues\ValueParserFactory
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
			'\SMW\DataValues\ValueParserFactory',
			new ValueParserFactory()
		);

		ValueParserFactory::clear();

		$this->assertInstanceOf(
			'\SMW\DataValues\ValueParserFactory',
			ValueParserFactory::getInstance()
		);
	}

	public function testCanConstructMonolingualTextValueParser() {

		$instance = new ValueParserFactory();

		$this->assertInstanceOf(
			'\SMW\DataValues\ValueParsers\MonolingualTextValueParser',
			$instance->newMonolingualTextValueParser()
		);
	}

}
