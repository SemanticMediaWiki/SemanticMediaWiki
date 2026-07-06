<?php

namespace SMW\Tests\Unit\MediaWiki\Jobs;

use MediaWiki\Parser\ParserOutput;
use MediaWiki\Title\Title;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use SMW\MediaWiki\Jobs\ParserDataFactory;
use SMW\ParserData;

/**
 * @covers \SMW\MediaWiki\Jobs\ParserDataFactory
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 7.0.0
 *
 * @author mwjames
 */
class ParserDataFactoryTest extends TestCase {

	public function testCanConstruct() {
		$this->assertInstanceOf(
			ParserDataFactory::class,
			new ParserDataFactory( $this->createMock( LoggerInterface::class ) )
		);
	}

	public function testNewParserDataReturnsParserDataBoundToTitleAndOutput() {
		$title = $this->createMock( Title::class );
		$parserOutput = new ParserOutput();

		$instance = new ParserDataFactory( $this->createMock( LoggerInterface::class ) );

		$this->assertInstanceOf(
			ParserData::class,
			$instance->newParserData( $title, $parserOutput )
		);
	}

}
