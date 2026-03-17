<?php

namespace SMW\Tests\Factbox;

use MediaWiki\Parser\ParserOutput;
use MediaWiki\Title\Title;
use PHPUnit\Framework\TestCase;
use SMW\Factbox\CachedFactbox;
use SMW\Factbox\CheckMagicWords;
use SMW\Factbox\Factbox;
use SMW\Factbox\FactboxFactory;

/**
 * @covers \SMW\Factbox\FactboxFactory
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.0
 *
 * @author mwjames
 */
class FactboxFactoryTest extends TestCase {

	public function testCanConstruct() {
		$this->assertInstanceOf(
			FactboxFactory::class,
			new FactboxFactory()
		);
	}

	public function testCanConstructCachedFactbox() {
		$instance = new FactboxFactory();

		$this->assertInstanceOf(
			CachedFactbox::class,
			$instance->newCachedFactbox()
		);
	}

	public function testCanConstructCheckMagicWords() {
		$instance = new FactboxFactory();

		$this->assertInstanceOf(
			CheckMagicWords::class,
			$instance->newCheckMagicWords( [] )
		);
	}

	public function testCanConstructFactbox() {
		$title = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->any() )
			->method( 'getNamespace' )
			->willReturn( NS_MAIN );

		$parserOutput = $this->getMockBuilder( ParserOutput::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = new FactboxFactory();

		$this->assertInstanceOf(
			Factbox::class,
			$instance->newFactbox( $title, $parserOutput )
		);
	}

}
