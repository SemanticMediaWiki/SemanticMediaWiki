<?php

namespace SMW\Tests\Unit\SQLStore\Installer;

use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\ParsoidUse;
use SMW\SQLStore\Installer\ParserExaminer;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\SQLStore\Installer\ParserExaminer
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 */
class ParserExaminerTest extends TestCase {

	private const LOST_ANNOTATIONS = 'does not store the in-text annotations';
	private const SHOWN_AS_LINKS = 'show as links';

	public function testReportsNothingWithoutParsoid() {
		$this->assertSame( '', $this->reportFor( rendering: false, linksUpdates: false ) );
	}

	public function testWarnsAboutLostAnnotationsAndRenderingWhenParsoidDoesBoth() {
		$report = $this->reportFor( rendering: true, linksUpdates: true );

		$this->assertStringContainsString( self::LOST_ANNOTATIONS, $report );
		$this->assertStringContainsString( self::SHOWN_AS_LINKS, $report );
	}

	public function testWarnsOnlyAboutRenderingWhenParsoidOnlyRendersPages() {
		$report = $this->reportFor( rendering: true, linksUpdates: false );

		$this->assertStringNotContainsString( self::LOST_ANNOTATIONS, $report );
		$this->assertStringContainsString( self::SHOWN_AS_LINKS, $report );
	}

	public function testWarnsOnlyAboutLostAnnotationsWhenParsoidOnlyRunsLinksUpdates() {
		$report = $this->reportFor( rendering: false, linksUpdates: true );

		$this->assertStringContainsString( self::LOST_ANNOTATIONS, $report );
		$this->assertStringNotContainsString( self::SHOWN_AS_LINKS, $report );
	}

	private function reportFor( bool $rendering, bool $linksUpdates ): string {
		$parsoidUse = $this->createStub( ParsoidUse::class );
		$parsoidUse->method( 'forRendering' )->willReturn( $rendering );
		$parsoidUse->method( 'forLinksUpdates' )->willReturn( $linksUpdates );

		$messageReporter = TestEnvironment::getUtilityFactory()->newSpyMessageReporter();

		$instance = new ParserExaminer( $parsoidUse );
		$instance->setMessageReporter( $messageReporter );
		$instance->reportParsoidUse();

		// The report wraps its lines, which can split an asserted phrase
		return preg_replace( '/\s+/', ' ', $messageReporter->getMessagesAsString() );
	}

}
