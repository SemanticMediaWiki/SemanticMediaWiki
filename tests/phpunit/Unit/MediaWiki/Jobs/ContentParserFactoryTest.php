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
		$this->assertInstanceOf(
			ContentParserFactory::class,
			new ContentParserFactory(
				$this->createMock( Parser::class ),
				$this->createMock( RevisionGuard::class )
			)
		);
	}

	public function testNewContentParserReturnsContentParserBoundToTitle() {
		$title = $this->createMock( Title::class );

		$instance = new ContentParserFactory(
			$this->createMock( Parser::class ),
			$this->createMock( RevisionGuard::class )
		);

		$this->assertInstanceOf(
			ContentParser::class,
			$instance->newContentParser( $title )
		);
	}

}
