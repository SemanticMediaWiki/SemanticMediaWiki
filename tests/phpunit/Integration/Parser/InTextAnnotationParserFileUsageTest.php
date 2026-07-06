<?php

namespace SMW\Tests\Integration\Parser;

use MediaWiki\Parser\ParserOutput;
use MediaWiki\Parser\ParserOutputLinkTypes;
use MediaWiki\Title\Title;
use PHPUnit\Framework\TestCase;
use SMW\Services\ServicesFactory as ApplicationFactory;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\Parser\InTextAnnotationParser
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 7.1.0
 */
class InTextAnnotationParserFileUsageTest extends TestCase {

	private TestEnvironment $testEnvironment;
	private ApplicationFactory $applicationFactory;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment( [
			'smwgNamespacesWithSemanticLinks' => [ NS_MAIN => true ],
			'smwgMainCacheType' => 'hash',
		] );

		$this->applicationFactory = ApplicationFactory::getInstance();
	}

	protected function tearDown(): void {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	/**
	 * Parses the given text through SMW's in-text annotation parser and returns
	 * the rendered text together with the file dbkeys registered as a
	 * dependency on the ParserOutput (these populate the file's usage tracking).
	 *
	 * This runs only SMW's annotation parser, not MediaWiki's full parser, so
	 * the returned image set reflects exactly what addFileUsage() registers and
	 * is not influenced by any subsequent `[[File:...]]` embed.
	 *
	 * @return array{0: string, 1: string[]}
	 */
	private function parse( string $text ): array {
		$parserOutput = new ParserOutput();
		$title = Title::newFromText( 'InTextAnnotationParserFileUsageTest', NS_MAIN );

		$parserData = $this->applicationFactory->newParserData( $title, $parserOutput );

		$this->applicationFactory->newInTextAnnotationParser( $parserData )->parse( $text );

		$media = $parserOutput->getLinkList( ParserOutputLinkTypes::MEDIA );

		return [ $text, array_map( static fn ( array $item ): string => $item['link']->getDBkey(), $media ) ];
	}

	public function testNonImageFileValueIsTrackedWithoutChangingItsRendering() {
		// #6141 A non-image file referenced through a property value must be
		// recorded as a file-usage dependency, just like an embedded image.
		[ $text, $images ] = $this->parse( '[[Has file::File:Example6141.mp4]]' );

		$this->assertContains( 'Example6141.mp4', $images );

		// Its rendering stays a plain (colon-prefixed) link, it is not turned
		// into a file embed.
		$this->assertStringContainsString( '[[:File:Example6141.mp4', $text );
	}

	public function testImageFileValueRemainsTrackedAndEmbedded() {
		[ $text, $images ] = $this->parse( '[[Has file::File:Example6141.png]]' );

		// An image is covered by the same registration (it is also a local file
		// without a subobject); in a full parse its `[[File:...]]` embed would
		// register the dependency as well.
		$this->assertContains( 'Example6141.png', $images );

		// The rendering stays an embed, the link has no escaping colon prefix.
		$this->assertStringContainsString( '[[File:Example6141.png', $text );
		$this->assertStringNotContainsString( '[[:File:Example6141.png', $text );
	}

	public function testNonFileValueIsNotTracked() {
		[ , $images ] = $this->parse( '[[Has page::Example6141]]' );

		$this->assertNotContains( 'Example6141', $images );
	}

	public function testMediaNamespaceValueIsNotTracked() {
		// A `Media:` reference is a link to the media, not a file embed, and is
		// not in the File namespace, so it must not be recorded as file usage.
		[ , $images ] = $this->parse( '[[Has file::Media:Example6141.mp4]]' );

		$this->assertNotContains( 'Example6141.mp4', $images );
	}

	public function testLocalFileValueWithSubobjectIsNotTracked() {
		[ , $images ] = $this->parse( '[[Has file::File:Example6141.mp4#Foo]]' );

		$this->assertNotContains( 'Example6141.mp4', $images );
	}
}
