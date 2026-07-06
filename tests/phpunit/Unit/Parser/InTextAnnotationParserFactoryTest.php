<?php

namespace SMW\Tests\Unit\Parser;

use MediaWiki\HookContainer\HookContainer;
use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\MagicWordsFinder;
use SMW\MediaWiki\MwCollaboratorFactory;
use SMW\MediaWiki\RedirectTargetFinder;
use SMW\Parser\InTextAnnotationParser;
use SMW\Parser\InTextAnnotationParserFactory;
use SMW\ParserData;
use SMW\Settings;

/**
 * @covers \SMW\Parser\InTextAnnotationParserFactory
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 7.0.0
 */
class InTextAnnotationParserFactoryTest extends TestCase {

	private MwCollaboratorFactory $mwCollaboratorFactory;
	private Settings $settings;
	private HookContainer $hookContainer;

	protected function setUp(): void {
		parent::setUp();

		$this->mwCollaboratorFactory = $this->createMock( MwCollaboratorFactory::class );
		$this->settings = $this->createMock( Settings::class );
		$this->hookContainer = $this->createMock( HookContainer::class );
	}

	private function newInstance(): InTextAnnotationParserFactory {
		return new InTextAnnotationParserFactory(
			$this->mwCollaboratorFactory,
			$this->settings,
			$this->hookContainer
		);
	}

	public function testCanConstruct(): void {
		$this->assertInstanceOf(
			InTextAnnotationParserFactory::class,
			$this->newInstance()
		);
	}

	public function testNewForReturnsConfiguredParser(): void {
		$this->mwCollaboratorFactory->expects( $this->once() )
			->method( 'newMagicWordsFinder' )
			->willReturn( $this->createMock( MagicWordsFinder::class ) );

		$this->mwCollaboratorFactory->expects( $this->once() )
			->method( 'newRedirectTargetFinder' )
			->willReturn( $this->createMock( RedirectTargetFinder::class ) );

		$parserData = $this->createMock( ParserData::class );

		$parser = $this->newInstance()->newFor( $parserData );

		$this->assertInstanceOf( InTextAnnotationParser::class, $parser );
	}

}
