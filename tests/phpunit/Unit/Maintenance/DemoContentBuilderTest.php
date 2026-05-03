<?php

declare( strict_types=1 );

namespace SMW\Tests\Unit\Maintenance;

use PHPUnit\Framework\TestCase;
use SMW\Maintenance\DemoContentBuilder;

/**
 * @covers \SMW\Maintenance\DemoContentBuilder
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 7.0.0
 */
class DemoContentBuilderTest extends TestCase {

	private DemoContentBuilder $builder;

	protected function setUp(): void {
		parent::setUp();
		$this->builder = new DemoContentBuilder();
	}

	public function testGetBreedDataReturnsExpectedShape(): void {
		$breeds = $this->builder->getBreedData();

		$this->assertGreaterThanOrEqual( 79, count( $breeds ) );

		foreach ( $breeds as $breed ) {
			foreach ( [
				'name', 'group', 'origin',
				'weightMin', 'weightMax', 'heightMin', 'heightMax',
				'lifeMin', 'lifeMax', 'coat', 'temperaments',
			] as $key ) {
				$this->assertArrayHasKey( $key, $breed,
					"missing key {$key} on breed " . ( $breed['name'] ?? '?' ) );
			}
			$this->assertLessThanOrEqual( $breed['weightMax'], $breed['weightMin'],
				"weight inverted for {$breed['name']}" );
			$this->assertLessThanOrEqual( $breed['heightMax'], $breed['heightMin'],
				"height inverted for {$breed['name']}" );
			$this->assertLessThanOrEqual( $breed['lifeMax'], $breed['lifeMin'],
				"life inverted for {$breed['name']}" );
			$this->assertIsArray( $breed['temperaments'] );
			$this->assertNotEmpty( $breed['temperaments'] );
		}
	}

	public function testGetBreedDataNamesAreUnique(): void {
		$names = array_column( $this->builder->getBreedData(), 'name' );
		$this->assertSame( $names, array_unique( $names ), 'breed names must be unique' );
	}

	public function testGetCategoryDataIncludesExpectedCategories(): void {
		$categories = $this->builder->getCategoryData();
		$names = array_column( $categories, 0 );

		$expected = [
			'Herding Group', 'Sporting Group', 'Hound Group', 'Terrier Group',
			'Toy Group', 'Working Group', 'Non-Sporting Group',
			'Dog Breeds', 'Dogs', 'Breed Groups',
			'Small Dogs', 'Medium Dogs', 'Large Dogs', 'Templates',
			// Origin-derived categories (one per unique breed origin).
			'Dogs from Germany', 'Dogs from United Kingdom', 'Dogs from Japan',
		];
		foreach ( $expected as $name ) {
			$this->assertContains( $name, $names, "missing category {$name}" );
		}

		$this->assertSame( $names, array_unique( $names ), 'category names must be unique' );
	}

	public function testGetCategoryDataEntriesAreNameContentTuples(): void {
		foreach ( $this->builder->getCategoryData() as $entry ) {
			$this->assertCount( 2, $entry, 'category entry must be a [name, content] tuple' );
			$this->assertIsString( $entry[0] );
			$this->assertIsString( $entry[1] );
			$this->assertNotEmpty( $entry[0] );
			$this->assertNotEmpty( $entry[1] );
		}
	}

	public function testGetTopicPagesReturnsEightEntries(): void {
		$topics = $this->builder->getTopicPages();
		$this->assertCount( 8, $topics );
		foreach ( $topics as $page ) {
			$this->assertArrayHasKey( 'title', $page );
			$this->assertArrayHasKey( 'content', $page );
			$this->assertNotEmpty( $page['title'] );
			$this->assertNotEmpty( $page['content'] );
		}
	}

	public function testRenderBreedTemplateContainsAllPropertyAssignments(): void {
		$template = $this->builder->renderBreedTemplate();

		$this->assertStringContainsString( '[[Breed group::{{{breed_group|}}}]]', $template );
		$this->assertStringContainsString( '[[Origin country::{{{origin|}}}]]', $template );
		$this->assertStringContainsString( '[[Minimum weight::{{{weight_min|}}} kg]]', $template );
		$this->assertStringContainsString( '[[Maximum weight::{{{weight_max|}}} kg]]', $template );
		$this->assertStringContainsString( '[[Minimum height::{{{height_min|}}} cm]]', $template );
		$this->assertStringContainsString( '[[Maximum height::{{{height_max|}}} cm]]', $template );
		$this->assertStringContainsString( '[[Minimum life expectancy::{{{life_expectancy_min|}}}]]', $template );
		$this->assertStringContainsString( '[[Maximum life expectancy::{{{life_expectancy_max|}}}]]', $template );
		$this->assertStringContainsString( '[[Coat type::{{{coat_type|}}}]]', $template );
		$this->assertStringContainsString( '[[Size category::{{{size_category|}}}]]', $template );
		$this->assertStringContainsString( '[[Exercise level::{{{exercise_level|}}}]]', $template );
		$this->assertStringContainsString( '[[Related breed::{{{related_breed|}}}]]', $template );
		$this->assertStringContainsString( '[[Recognition date::{{{recognition_date|}}}]]', $template );

		$this->assertStringContainsString( '<noinclude>', $template );
		$this->assertStringContainsString( '<includeonly>', $template );
		$this->assertStringContainsString( '[[Category:Templates]]', $template );
		$this->assertStringContainsString( '[[Category:Seed data]]', $template );
	}

	/**
	 * @dataProvider groupNameProvider
	 */
	public function testRenderCategoryDescriptionForKnownGroups( string $group ): void {
		$description = $this->builder->renderCategoryDescription( $group );
		$this->assertNotEmpty( $description );
	}

	public static function groupNameProvider(): array {
		return [
			[ 'Herding' ], [ 'Sporting' ], [ 'Hound' ], [ 'Terrier' ],
			[ 'Toy' ], [ 'Working' ], [ 'Non-Sporting' ],
		];
	}

	public function testRenderCategoryDescriptionForUnknownGroupReturnsEmpty(): void {
		$this->assertSame( '', $this->builder->renderCategoryDescription( 'NonExistentGroup' ) );
	}

	public function testRenderBreedArticleContainsTemplateAndStructure(): void {
		$breed = [
			'name' => 'Border Collie',
			'group' => 'Herding',
			'origin' => 'United Kingdom',
			'weightMin' => 14,
			'weightMax' => 20,
			'heightMin' => 46,
			'heightMax' => 56,
			'lifeMin' => 12,
			'lifeMax' => 15,
			'coat' => 'Medium',
			'temperaments' => [ 'Intelligent', 'Energetic', 'Loyal' ],
		];
		$wikitext = $this->builder->renderBreedArticle( $breed );

		// Template invocation
		$this->assertStringContainsString( '{{Dog breed', $wikitext );
		$this->assertStringContainsString( '| breed_group = Herding', $wikitext );
		$this->assertStringContainsString( '| origin = United Kingdom', $wikitext );
		$this->assertStringContainsString( '| weight_min = 14', $wikitext );
		$this->assertStringContainsString( '| coat_type = Medium', $wikitext );

		// Section headers
		$this->assertStringContainsString( '== Appearance ==', $wikitext );
		$this->assertStringContainsString( '== Temperament ==', $wikitext );
		$this->assertStringContainsString( '== Care ==', $wikitext );
		$this->assertStringContainsString( '=== Exercise ===', $wikitext );
		$this->assertStringContainsString( '=== Grooming ===', $wikitext );
		$this->assertStringContainsString( '=== Health ===', $wikitext );
		$this->assertStringContainsString( '== History ==', $wikitext );

		// Category links
		$this->assertStringContainsString( '[[Category:Dog Breeds]]', $wikitext );
		$this->assertStringContainsString( '[[Category:Herding Group]]', $wikitext );
		$this->assertStringContainsString( '[[Category:Dogs from United Kingdom]]', $wikitext );
		$this->assertStringContainsString( '[[Category:Seed data]]', $wikitext );

		// Size derivation: weightMax 20 → Medium
		$this->assertStringContainsString( '| size_category = Medium', $wikitext );
		$this->assertStringContainsString( '[[Category:Medium Dogs]]', $wikitext );

		// Exercise derivation: 'Energetic' present → High
		$this->assertStringContainsString( '| exercise_level = High', $wikitext );

		// Temperament #set annotations
		$this->assertStringContainsString( '{{#set:', $wikitext );
		$this->assertStringContainsString( 'Temperament=Intelligent', $wikitext );
	}

	public function testRenderBreedArticleHandlesGroupWithNoSiblings(): void {
		// A breed in a fictitious group has no same-group siblings in the
		// real breed data, so getRelatedBreed() returns ''.
		$breed = [
			'name' => 'Synthetic Test Breed',
			'group' => 'NonExistentGroup',
			'origin' => 'Atlantis',
			'weightMin' => 10,
			'weightMax' => 15,
			'heightMin' => 30,
			'heightMax' => 35,
			'lifeMin' => 10,
			'lifeMax' => 12,
			'coat' => 'Short',
			'temperaments' => [ 'Calm' ],
		];

		$wikitext = $this->builder->renderBreedArticle( $breed );

		// Template still produced; related_breed parameter has no value.
		$this->assertStringContainsString( '{{Dog breed', $wikitext );
		$this->assertMatchesRegularExpression( '/\| related_breed = \s*\n/', $wikitext );
	}

	/**
	 * @dataProvider groupRenderingProvider
	 */
	public function testRenderBreedArticleEmbedsGroupSpecificRoleAndHistory(
		string $breedName, string $expectedRoleSnippet, string $expectedHistorySnippet
	): void {
		$breeds = $this->builder->getBreedData();
		$breed = null;
		foreach ( $breeds as $b ) {
			if ( $b['name'] === $breedName ) {
				$breed = $b;
				break;
			}
		}
		$this->assertNotNull( $breed, "fixture breed {$breedName} not in seeded data" );

		$wikitext = $this->builder->renderBreedArticle( $breed );

		$this->assertStringContainsString( $expectedRoleSnippet, $wikitext,
			"missing group-specific role snippet for {$breedName}" );
		$this->assertStringContainsString( $expectedHistorySnippet, $wikitext,
			"missing group-specific history snippet for {$breedName}" );
		$this->assertStringContainsString( "[[Category:{$breed['group']} Group]]", $wikitext );
	}

	public static function groupRenderingProvider(): array {
		// One breed per group; exercises every entry in the $roles and
		// $histories dispatch tables in renderBreedArticle.
		return [
			'Herding'      => [ 'Border Collie', 'valued herding and farm dog', 'as a herding dog' ],
			'Sporting'     => [ 'Labrador Retriever', 'popular sporting and hunting companion', 'as a sporting dog' ],
			'Hound'        => [ 'Beagle', 'skilled hunting hound', 'prized for their hunting abilities' ],
			'Terrier'      => [ 'Jack Russell Terrier', 'spirited vermin hunter and companion', 'bred to hunt and control vermin' ],
			'Toy'          => [ 'Chihuahua', 'beloved companion dog', 'cherished companion' ],
			'Working'      => [ 'Siberian Husky', 'dependable working dog', 'for practical working tasks' ],
			'Non-Sporting' => [ 'Poodle', 'versatile companion', 'served various roles throughout history' ],
		];
	}

	public function testRenderBreedArticleProducesValidRelatedBreedLinkWhenSiblingsExist(): void {
		// Pick a real Herding breed — siblings exist, so related_breed gets a value.
		$breeds = $this->builder->getBreedData();
		$borderCollie = null;
		foreach ( $breeds as $b ) {
			if ( $b['name'] === 'Border Collie' ) {
				$borderCollie = $b;
				break;
			}
		}
		$this->assertNotNull( $borderCollie );

		$wikitext = $this->builder->renderBreedArticle( $borderCollie );

		// related_breed line has a non-empty value (a real breed name).
		$this->assertMatchesRegularExpression( '/\| related_breed = [A-Z][^\n]+/', $wikitext );
	}

	public function testRenderQueryShowcaseContainsAllSections(): void {
		$wikitext = $this->builder->renderQueryShowcase();

		$this->assertStringContainsString( '== Total breed count ==', $wikitext );
		$this->assertStringContainsString( '== Working group breeds, heaviest first ==', $wikitext );
		$this->assertStringContainsString( '== Breeds from Germany ==', $wikitext );
		$this->assertStringContainsString( '== Heaviest breeds across all groups (over 50 kg) ==', $wikitext );
		$this->assertStringContainsString( '== Breeds with a related breed ==', $wikitext );
		$this->assertStringContainsString( '== Earliest recognized breeds (Date property) ==', $wikitext );
		$this->assertStringContainsString( '== Breeds with intelligent temperament (multi-value #set) ==', $wikitext );
		$this->assertStringContainsString( '== Toy breeds (template format) ==', $wikitext );

		$this->assertStringContainsString( '[[Category:Seed data]]', $wikitext );
	}

	public function testRenderQueryShowcaseHasEightWellFormedAskQueries(): void {
		$wikitext = $this->builder->renderQueryShowcase();

		// Each {{#ask: must have a matching closing }} — count opens.
		$this->assertSame( 8, substr_count( $wikitext, '{{#ask:' ),
			'expected 8 #ask queries' );

		// All braces balanced overall.
		$this->assertSame( substr_count( $wikitext, '{{' ), substr_count( $wikitext, '}}' ),
			'opening and closing braces unbalanced' );
	}

	public function testRenderQueryShowcaseUsesEachDemoFeature(): void {
		$wikitext = $this->builder->renderQueryShowcase();

		// Original five.
		$this->assertStringContainsString( 'format=count', $wikitext );
		$this->assertStringContainsString( 'format=ul', $wikitext );
		$this->assertStringContainsString( 'sort=Maximum weight', $wikitext );
		$this->assertStringContainsString( 'order=desc', $wikitext );
		$this->assertStringContainsString( '[[Maximum weight::>50]]', $wikitext );
		$this->assertStringContainsString( '[[Related breed::+]]', $wikitext );
		$this->assertStringContainsString( '[[Breed group::Working]]', $wikitext );
		$this->assertStringContainsString( '[[Origin country::Germany]]', $wikitext );

		// Date filter (Date datatype).
		$this->assertStringContainsString( '[[Recognition date::>1880-01-01]]', $wikitext );
		$this->assertStringContainsString( 'sort=Recognition date', $wikitext );
		$this->assertStringContainsString( 'order=asc', $wikitext );

		// Multi-value #set lookup (Temperament is annotated via {{#set}}).
		$this->assertStringContainsString( '[[Temperament::Intelligent]]', $wikitext );

		// format=template requires a template parameter.
		$this->assertStringContainsString( 'format=template', $wikitext );
		$this->assertStringContainsString( 'template=Breed query row', $wikitext );
	}

	public function testRenderBreedQueryRowTemplateIsWellFormed(): void {
		$template = $this->builder->renderBreedQueryRowTemplate();

		// Has noinclude documentation block and includeonly render block.
		$this->assertStringContainsString( '<noinclude>', $template );
		$this->assertStringContainsString( '</noinclude>', $template );
		$this->assertStringContainsString( '<includeonly>', $template );
		$this->assertStringContainsString( '</includeonly>', $template );

		// Uses positional parameters 1, 2, 3 (breed name, weight, origin).
		$this->assertStringContainsString( '{{{1|}}}', $template );
		$this->assertStringContainsString( '{{{2|}}}', $template );
		$this->assertStringContainsString( '{{{3|}}}', $template );

		// Categorised so the seed cleanup can find it.
		$this->assertStringContainsString( '[[Category:Templates]]', $template );
		$this->assertStringContainsString( '[[Category:Seed data]]', $template );
	}
}
