<?php

namespace SMW\Tests\Integration;

use PHPUnit\Framework\TestCase;

/**
 * Parity check between extension.json's `namespaces` block and the runtime
 * NamespaceManager bootstrap. Asserts that after `wfLoadExtension` plus the
 * `CanonicalNamespaces` hook have fired, every globals key the design
 * touches is in the expected state.
 *
 * Reads live `$GLOBALS` after the MediaWiki + SMW bootstrap has run; relies
 * on the post-bootstrap state being idempotent so test ordering does not
 * affect outcomes.
 *
 * @group medium
 *
 * @license GPL-2.0-or-later
 * @since 7.0.0
 */
class NamespaceRegistrationParityTest extends TestCase {

	public function testCustomNamespaceConstants(): void {
		$this->assertSame( 102, SMW_NS_PROPERTY );
		$this->assertSame( 103, SMW_NS_PROPERTY_TALK );
		$this->assertSame( 108, SMW_NS_CONCEPT );
		$this->assertSame( 109, SMW_NS_CONCEPT_TALK );
		$this->assertSame( 112, SMW_NS_SCHEMA );
		$this->assertSame( 113, SMW_NS_SCHEMA_TALK );
	}

	public function testExtraNamespacesContainsAllSixCanonicalNames(): void {
		$extra = $GLOBALS['wgExtraNamespaces'] ?? [];
		$this->assertSame( 'Property', $extra[SMW_NS_PROPERTY] ?? null );
		$this->assertSame( 'Property_talk', $extra[SMW_NS_PROPERTY_TALK] ?? null );
		$this->assertSame( 'Concept', $extra[SMW_NS_CONCEPT] ?? null );
		$this->assertSame( 'Concept_talk', $extra[SMW_NS_CONCEPT_TALK] ?? null );
		$this->assertSame( 'smw/schema', $extra[SMW_NS_SCHEMA] ?? null );
		$this->assertSame( 'smw/schema_talk', $extra[SMW_NS_SCHEMA_TALK] ?? null );
	}

	public function testTalkNamespacesHaveSubpages(): void {
		$this->assertTrue( $GLOBALS['wgNamespacesWithSubpages'][SMW_NS_PROPERTY_TALK] ?? false );
		$this->assertTrue( $GLOBALS['wgNamespacesWithSubpages'][SMW_NS_CONCEPT_TALK] ?? false );
		$this->assertTrue( $GLOBALS['wgNamespacesWithSubpages'][SMW_NS_SCHEMA_TALK] ?? false );
	}

	public function testContentNamespacesContainsPropertyAndConceptExactlyOnce(): void {
		$contentNamespaces = $GLOBALS['wgContentNamespaces'];

		// Assert presence and count separately so a regression that drops the
		// namespace fails differently from one that duplicates it.
		$this->assertContains( SMW_NS_PROPERTY, $contentNamespaces, 'SMW_NS_PROPERTY missing from wgContentNamespaces' );
		$this->assertContains( SMW_NS_CONCEPT, $contentNamespaces, 'SMW_NS_CONCEPT missing from wgContentNamespaces' );
		$this->assertCount( 1, array_keys( $contentNamespaces, SMW_NS_PROPERTY, true ), 'SMW_NS_PROPERTY duplicated in wgContentNamespaces' );
		$this->assertCount( 1, array_keys( $contentNamespaces, SMW_NS_CONCEPT, true ), 'SMW_NS_CONCEPT duplicated in wgContentNamespaces' );
	}

	public function testSchemaContentModelIsRegistered(): void {
		$this->assertSame(
			CONTENT_MODEL_SMW_SCHEMA,
			$GLOBALS['wgNamespaceContentModels'][SMW_NS_SCHEMA] ?? null
		);
	}

	public function testNamespacesToBeSearchedByDefault(): void {
		$this->assertTrue( $GLOBALS['wgNamespacesToBeSearchedDefault'][SMW_NS_PROPERTY] ?? false );
		$this->assertTrue( $GLOBALS['wgNamespacesToBeSearchedDefault'][SMW_NS_CONCEPT] ?? false );
	}

	public function testSemanticLinksDefaultsForSmwNamespaces(): void {
		$this->assertTrue( $GLOBALS['smwgNamespacesWithSemanticLinks'][SMW_NS_PROPERTY] ?? false );
		$this->assertTrue( $GLOBALS['smwgNamespacesWithSemanticLinks'][SMW_NS_CONCEPT] ?? false );
		$this->assertTrue( $GLOBALS['smwgNamespacesWithSemanticLinks'][SMW_NS_SCHEMA] ?? false );
	}
}
