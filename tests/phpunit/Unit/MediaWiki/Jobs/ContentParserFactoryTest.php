<?php

namespace SMW\Tests\Unit\MediaWiki\Jobs;

use MediaWiki\Parser\Parser;
use MediaWiki\Title\Title;
use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\Jobs\ContentParserFactory;
use SMW\MediaWiki\RevisionGuard;
use SMW\Parser\ContentParser;

/**
 * @covers \SMW\MediaWiki\Jobs\ContentParserFactory
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 7.0.0
 *
 * @author mwjames
 */
class ContentParserFactoryTest extends TestCase {

	public function testCanConstruct() {
		$parser = $this->createMock( Parser::class );
		$this->assertInstanceOf(
			ContentParserFactory::class,
			new ContentParserFactory(
				static fn (): Parser => $parser,
				$this->createMock( RevisionGuard::class )
			)
		);
	}

	public function testNewContentParserReturnsContentParserBoundToTitle() {
		$title = $this->createMock( Title::class );
		$parser = $this->createMock( Parser::class );

		$instance = new ContentParserFactory(
			static fn (): Parser => $parser,
			$this->createMock( RevisionGuard::class )
		);

		$this->assertInstanceOf(
			ContentParser::class,
			$instance->newContentParser( $title )
		);
	}

	public function testParserProviderIsCalledLazilyPerNewContentParser() {
		$title = $this->createMock( Title::class );
		$parser = $this->createMock( Parser::class );
		$calls = 0;

		$instance = new ContentParserFactory(
			static function () use ( $parser, &$calls ): Parser {
				$calls++;
				return $parser;
			},
			$this->createMock( RevisionGuard::class )
		);

		$this->assertSame( 0, $calls, 'Parser provider must not be invoked at construction time' );

		$instance->newContentParser( $title );
		$this->assertSame( 1, $calls, 'Parser provider invoked once per newContentParser call' );

		$instance->newContentParser( $title );
		$this->assertSame( 2, $calls );
	}

}
