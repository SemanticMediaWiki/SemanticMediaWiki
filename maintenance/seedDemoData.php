<?php

declare( strict_types=1 );

namespace SMW\Maintenance;

use MediaWiki\CommentStore\CommentStoreComment;
use MediaWiki\Content\WikitextContent;
use MediaWiki\Maintenance\Maintenance;
use MediaWiki\Page\WikiPageFactory;
use MediaWiki\Title\Title;
use MediaWiki\User\User;

/**
 * Load the required class
 */
// @codeCoverageIgnoreStart
if ( getenv( 'MW_INSTALL_PATH' ) !== false ) {
	require_once getenv( 'MW_INSTALL_PATH' ) . '/maintenance/Maintenance.php';
} else {
	require_once __DIR__ . '/../../../maintenance/Maintenance.php';
}
// @codeCoverageIgnoreEnd

/**
 * Seed the dev wiki with demo pages including SMW annotations.
 *
 * Creates ~147 dog-themed pages covering all common SMW property types
 * (Text, Number, Quantity, Page, Date), multi-value properties, templates with
 * annotations, categories, and inline queries. Useful for development
 * and testing of SMW features.
 *
 * Usage:
 *   php seedDemoData.php --force
 *   php seedDemoData.php --force --clear-only
 *
 * @since 6.1
 */
class seedDemoData extends Maintenance {

	private const BREED_GROUPS = [
		'Herding', 'Sporting', 'Hound', 'Terrier', 'Toy', 'Working', 'Non-Sporting',
	];

	private array $breeds = [];
	private array $topicPages = [];
	private User $user;
	private WikiPageFactory $wikiPageFactory;

	public function __construct() {
		parent::__construct();
		$this->addDescription( 'Seed the dev wiki with demo pages and SMW annotations for development and testing' );
		$this->addOption( 'clear-only', 'Only clear existing pages, do not seed', false, false );
		$this->addOption( 'force', 'Actually execute (without this flag, only shows what would happen)', false, false );
	}

	public function execute(): void {
		$clearOnly = $this->hasOption( 'clear-only' );

		if ( !$this->hasOption( 'force' ) ) {
			$this->output( "\n" );
			$this->output( "This script will:\n" );
			$this->output( "  - Delete all pages in Category:Seed data\n" );
			if ( !$clearOnly ) {
				$this->output( "  - Create dog breed pages, topic pages, SMW properties, templates, and categories\n" );
				$this->output( "  - Update Main Page\n" );
			}
			$this->output( "\nRe-run with --force to execute.\n" );
			return;
		}

		if ( !( $maintenanceCheck = new MaintenanceCheck() )->canExecute() ) {
			exit( $maintenanceCheck->getMessage() );
		}

		$this->initBreedData();
		$this->initTopicPages();
		$user = User::newSystemUser( User::MAINTENANCE_SCRIPT_USER, [ 'steal' => true ] );
		if ( $user === null ) {
			$this->fatalError( 'Failed to create maintenance script user.' );
		}
		$this->user = $user;
		$this->wikiPageFactory = $this->getServiceContainer()->getWikiPageFactory();

		$this->clearWiki();

		if ( !$clearOnly ) {
			$this->seedWiki();
		}

		$this->output( "\nDone!\n" );
	}

	private function clearWiki(): void {
		$this->output( "\n=== Clearing existing pages ===\n" );

		$dbw = $this->getPrimaryDB();
		$deletePageFactory = $this->getServiceContainer()->getDeletePageFactory();

		// Find all pages in Category:Seed data
		$result = $dbw->newSelectQueryBuilder()
			->select( [ 'cl_from' ] )
			->from( 'categorylinks' )
			->where( [ 'cl_to' => 'Seed_data' ] )
			->caller( __METHOD__ )
			->fetchResultSet();

		$pageIds = [];
		foreach ( $result as $row ) {
			$pageIds[] = $row->cl_from;
		}

		$totalDeleted = 0;

		if ( $pageIds !== [] ) {
			// Fetch page info for all category members
			$pages = $dbw->newSelectQueryBuilder()
				->select( [ 'page_id', 'page_title', 'page_namespace' ] )
				->from( 'page' )
				->where( [ 'page_id' => $pageIds ] )
				->caller( __METHOD__ )
				->fetchResultSet();

			foreach ( $pages as $row ) {
				$title = Title::newFromRow( $row );

				// Delete the Seed data category itself last
				if ( $title->getText() === 'Seed data' && $title->getNamespace() === NS_CATEGORY ) {
					continue;
				}

				$page = $this->wikiPageFactory->newFromTitle( $title );
				$deleter = $deletePageFactory->newDeletePage( $page, $this->user );
				$status = $deleter->deleteUnsafe( 'Clearing seed data' );

				if ( $status->isGood() ) {
					$this->output( "  Deleted: {$title->getPrefixedText()}\n" );
					$totalDeleted++;
				} else {
					$this->output( "  FAILED: {$title->getPrefixedText()}\n" );
				}
			}
		}

		// Delete the tracking category itself
		$seedCatTitle = Title::newFromText( 'Category:Seed data' );
		if ( $seedCatTitle && $seedCatTitle->exists() ) {
			$page = $this->wikiPageFactory->newFromTitle( $seedCatTitle );
			$deleter = $deletePageFactory->newDeletePage( $page, $this->user );
			$status = $deleter->deleteUnsafe( 'Clearing seed data' );
			if ( $status->isGood() ) {
				$this->output( "  Deleted: Category:Seed data\n" );
				$totalDeleted++;
			}
		}

		$this->output( "Deleted {$totalDeleted} pages\n" );

		// Run jobs to clear any SMW change propagation
		$this->output( "\n=== Running job queue ===\n" );
		$runner = $this->getServiceContainer()->getJobRunner();
		$runner->run( [ 'maxJobs' => 5000, 'maxTime' => 60 ] );
		$this->output( "  Done\n" );
	}

	private function editPage( string $titleText, string $content, string $summary ): bool {
		$title = Title::newFromText( $titleText );
		if ( !$title ) {
			$this->output( "  ERROR: Invalid title: {$titleText}\n" );
			return false;
		}

		// Refuse to overwrite pages that exist but are not seed data
		if ( $title->exists() && !$this->isSeedPage( $title ) ) {
			$this->output( "  SKIPPED: {$titleText} (already exists, not seed data)\n" );
			return false;
		}

		$page = $this->wikiPageFactory->newFromTitle( $title );
		$updater = $page->newPageUpdater( $this->user );
		$updater->setContent( 'main', new WikitextContent( $content ) );
		// RecentChange has no MediaWiki namespace yet
		$updater->setRcPatrolStatus( \RecentChange::PRC_AUTOPATROLLED );
		$updater->saveRevision(
			CommentStoreComment::newUnsavedComment( $summary ),
			$title->exists() ? EDIT_UPDATE : EDIT_NEW
		);

		if ( !$updater->wasSuccessful() ) {
			$this->error( "  ERROR: Failed to save {$titleText}: " . $updater->getStatus()->getMessage()->text() . "\n" );
			return false;
		}

		return true;
	}

	private function isSeedPage( Title $title ): bool {
		$dbr = $this->getReplicaDB();
		$row = $dbr->newSelectQueryBuilder()
			->select( 'cl_from' )
			->from( 'categorylinks' )
			->where( [
				'cl_from' => $title->getArticleID(),
				'cl_to' => 'Seed_data',
			] )
			->caller( __METHOD__ )
			->fetchRow();
		return $row !== false;
	}

	private function seedWiki(): void {
		$this->output( "\n=== Seeding wiki ===\n" );

		// 0. Seed data tracking category
		$createdTrackingCategory = 0;
		if ( $this->editPage(
			'Category:Seed data',
			'Pages created by the seed script for development and testing.',
			'Seed: create tracking category'
		) ) {
			$this->output( "  Created Category:Seed data\n" );
			$createdTrackingCategory = 1;
		}

		// 1. SMW Properties
		$this->output( "\n--- Creating SMW properties ---\n" );
		$properties = [
			[ 'Breed group', 'Text', 'The breed group classification (e.g. Herding, Sporting).' ],
			[ 'Origin country', 'Text', 'The country or region where the breed originated.' ],
			[ 'Minimum weight', 'Quantity', 'The minimum typical weight for the breed.' ],
			[ 'Maximum weight', 'Quantity', 'The maximum typical weight for the breed.' ],
			[ 'Minimum height', 'Quantity', 'The minimum typical height for the breed.' ],
			[ 'Maximum height', 'Quantity', 'The maximum typical height for the breed.' ],
			[ 'Minimum life expectancy', 'Number', 'The minimum typical life expectancy for the breed, in years.' ],
			[ 'Maximum life expectancy', 'Number', 'The maximum typical life expectancy for the breed, in years.' ],
			[ 'Coat type', 'Text', 'The type of coat (e.g. Short, Long, Wire, Curly).' ],
			[ 'Size category', 'Text', 'The size classification (Small, Medium, Large).' ],
			[ 'Exercise level', 'Text', 'The exercise needs of the breed (Low, Moderate, High).' ],
			[ 'Temperament', 'Text', 'A temperament trait associated with the breed.' ],
			[ 'Related breed', 'Page', 'A link to a related dog breed page.' ],
			[ 'Recognition date', 'Date', 'The date the breed was officially recognized by a major kennel club.' ],
		];

		$unitDeclarations = [
			'Minimum weight' => "[[Corresponds to::1 kg]]\n[[Corresponds to::0.001 t]]\n[[Corresponds to::2.20462 lb]]",
			'Maximum weight' => "[[Corresponds to::1 kg]]\n[[Corresponds to::0.001 t]]\n[[Corresponds to::2.20462 lb]]",
			'Minimum height' => "[[Corresponds to::1 cm]]\n[[Corresponds to::0.01 m]]\n[[Corresponds to::0.393701 in]]",
			'Maximum height' => "[[Corresponds to::1 cm]]\n[[Corresponds to::0.01 m]]\n[[Corresponds to::0.393701 in]]",
		];

		$createdProperties = 0;
		foreach ( $properties as [ $name, $type, $desc ] ) {
			$units = $unitDeclarations[$name] ?? '';
			$content = "This is a property of type [[Has type::{$type}]].\n\n{$desc}";
			if ( $units !== '' ) {
				$content .= "\n\n== Units ==\n{$units}";
			}
			$content .= "\n\n[[Category:Seed data]]";
			if ( $this->editPage( "Property:{$name}", $content, 'Seed: create SMW property' ) ) {
				$this->output( "  Created property: {$name} ({$type})\n" );
				$createdProperties++;
			}
		}

		// 2. Template
		$this->output( "\n--- Creating template ---\n" );
		$createdTemplate = 0;
		if ( $this->editPage( 'Template:Dog breed', $this->getBreedTemplate(), 'Seed: create dog breed template' ) ) {
			$this->output( "  Created Template:Dog breed\n" );
			$createdTemplate = 1;
		}

		// 3. Categories
		$this->output( "\n--- Creating categories ---\n" );
		$categories = $this->getCategoryData();
		$createdCategories = 0;
		foreach ( $categories as [ $name, $content ] ) {
			if ( $this->editPage( "Category:{$name}", $content . "\n\n[[Category:Seed data]]", 'Seed: create category' ) ) {
				$this->output( "  Created Category:{$name}\n" );
				$createdCategories++;
			}
		}

		// 4. Breed pages
		$this->output( "\n--- Creating breed pages ---\n" );
		$createdBreeds = 0;
		foreach ( $this->breeds as $breed ) {
			$content = $this->generateBreedArticle( $breed );
			if ( $this->editPage( $breed['name'], $content, 'Seed: create dog breed page' ) ) {
				$this->output( "  Created: {$breed['name']}\n" );
				$createdBreeds++;
			}
		}

		// 5. Topic pages
		$this->output( "\n--- Creating topic pages ---\n" );
		$createdTopics = 0;
		foreach ( $this->topicPages as $page ) {
			if ( $this->editPage( $page['title'], $page['content'] . "\n[[Category:Seed data]]", 'Seed: create topic page' ) ) {
				$this->output( "  Created: {$page['title']}\n" );
				$createdTopics++;
			}
		}

		// 6. Main Page
		$this->output( "\n--- Updating Main Page ---\n" );
		$updatedMainPage = 0;
		if ( $this->editPage( 'Main Page', $this->getMainPageContent(), 'Seed: update main page' ) ) {
			$this->output( "  Updated Main Page\n" );
			$updatedMainPage = 1;
		}

		// Summary
		$this->output( "\n=== Summary ===\n" );
		$this->output( "  {$createdBreeds} breed pages\n" );
		$this->output( "  {$createdTopics} topic pages\n" );
		$this->output( "  {$createdProperties} SMW properties\n" );
		$this->output( "  {$createdTemplate} template\n" );
		$this->output( "  {$createdCategories} categories\n" );
		$total = $createdTrackingCategory + $createdBreeds + $createdTopics +
			$createdProperties + $createdTemplate + $createdCategories + $updatedMainPage;
		$this->output( "  Total: ~{$total} pages\n" );
	}

	private function getBreedTemplate(): string {
		return '<noinclude>
This template is used to create standardized dog breed articles with Semantic MediaWiki annotations.

== Usage ==
<pre>
{{Dog breed
| breed_group =
| origin =
| weight_min =
| weight_max =
| height_min =
| height_max =
| life_expectancy_min =
| life_expectancy_max =
| coat_type =
| size_category =
| exercise_level =
| temperament =
| related_breed =
| recognition_date =
}}
</pre>
[[Category:Templates]]
[[Category:Seed data]]
</noinclude><includeonly>{| class="wikitable" style="width: 20rem; clear: right; float: right; margin-left: 16px;"
|-
! colspan="2" style="text-align: center; font-size: 1.125rem;" | {{PAGENAME}}
|-
! Breed Group
| [[Breed group::{{{breed_group|}}}]]
|-
! Origin
| [[Origin country::{{{origin|}}}]]
|-
! Weight
| [[Minimum weight::{{{weight_min|}}} kg]] – [[Maximum weight::{{{weight_max|}}} kg]]
|-
! Height
| [[Minimum height::{{{height_min|}}} cm]] – [[Maximum height::{{{height_max|}}} cm]]
|-
! Life Expectancy
| [[Minimum life expectancy::{{{life_expectancy_min|}}}]] – [[Maximum life expectancy::{{{life_expectancy_max|}}}]] years
|-
! Coat Type
| [[Coat type::{{{coat_type|}}}]]
|-
! Size Category
| [[Size category::{{{size_category|}}}]]
|-
! Exercise Level
| [[Exercise level::{{{exercise_level|}}}]]
|-
! Temperament
| {{{temperament|}}}
|-
! Related Breed
| [[Related breed::{{{related_breed|}}}]]
|-
! Recognized
| [[Recognition date::{{{recognition_date|}}}]]
|}</includeonly>';
	}

	/** @return array<int, array{string, string}> */
	private function getCategoryData(): array {
		$categories = [];

		// Breed group categories
		foreach ( self::BREED_GROUPS as $group ) {
			$breedsInGroup = array_filter( $this->breeds, static fn ( $b ) => $b['group'] === $group );
			$breedList = implode( "\n", array_map( static fn ( $b ) => "* [[{$b['name']}]]", $breedsInGroup ) );
			$desc = $this->getCategoryDescription( $group );

			$categories[] = [ "{$group} Group", "The '''{$group} Group''' consists of dog breeds that share common traits and historical roles.\n\n== Breeds ==\n{$breedList}\n\n== Characteristics ==\n{$desc}\n\n[[Category:Breed Groups]]" ];
		}

		// Meta categories
		$categories[] = [ 'Dog Breeds', "All dog breeds documented on this wiki.\n\n[[Category:Dogs]]" ];
		$categories[] = [ 'Dog Care', "Articles about caring for dogs, including health, nutrition, grooming, and training.\n\n[[Category:Dogs]]" ];
		$categories[] = [ 'Dog Activities', "Articles about activities involving dogs, including shows, sports, and working roles.\n\n[[Category:Dogs]]" ];
		$categories[] = [ 'Dog History', "Articles about the history of dogs and dog breeds.\n\n[[Category:Dogs]]" ];
		$categories[] = [ 'Dogs', 'The top-level category for all dog-related content on this wiki.' ];
		$categories[] = [ 'Breed Groups', "The major breed groups used to classify dog breeds.\n\n[[Category:Dogs]]" ];
		$categories[] = [ 'Small Dogs', "Dog breeds in the small size category (under 10 kg).\n\n[[Category:Dog Breeds]]" ];
		$categories[] = [ 'Medium Dogs', "Dog breeds in the medium size category (10–25 kg).\n\n[[Category:Dog Breeds]]" ];
		$categories[] = [ 'Large Dogs', "Dog breeds in the large size category (over 25 kg).\n\n[[Category:Dog Breeds]]" ];
		$categories[] = [ 'Templates', 'Templates used on this wiki.' ];

		// Origin country categories
		$origins = array_unique( array_column( $this->breeds, 'origin' ) );
		foreach ( $origins as $origin ) {
			$categories[] = [ "Dogs from {$origin}", "Dog breeds originating from {$origin}.\n\n[[Category:Dog Breeds]]" ];
		}

		return $categories;
	}

	private function getCategoryDescription( string $group ): string {
		$descriptions = [
			'Herding' => 'Herding breeds were developed to control the movement of livestock. These intelligent, responsive dogs are known for their ability to work closely with humans and their keen awareness of their surroundings.',
			'Sporting' => 'Sporting breeds were developed to assist hunters in finding, flushing, or retrieving game. They are generally active, alert, and require regular vigorous exercise.',
			'Hound' => 'Hound breeds were developed for hunting, either by tracking prey by scent (scent hounds) or by chasing prey by sight (sight hounds). They tend to be independent and determined.',
			'Terrier' => 'Terrier breeds were developed to hunt and kill vermin. They are typically feisty, energetic, and tenacious, with a characteristic boldness that belies their often small size.',
			'Toy' => 'Toy breeds were developed primarily as companions. Despite their small size, many toy breeds have bold personalities and make excellent watchdogs.',
			'Working' => 'Working breeds were developed for practical tasks such as guarding property, pulling sleds, and performing water rescues. They are typically large, strong, and intelligent.',
			'Non-Sporting' => 'The Non-Sporting Group is a diverse collection of breeds that do not fit neatly into other groups. They vary widely in size, coat type, and temperament.',
		];
		return $descriptions[$group] ?? '';
	}

	private function generateBreedArticle( array $breed ): string {
		$sizeCategory = $breed['weightMax'] <= 10 ? 'Small' : ( $breed['weightMax'] <= 25 ? 'Medium' : 'Large' );
		$exerciseLevel = in_array( 'Energetic', $breed['temperaments'] ) ? 'High' : ( in_array( 'Playful', $breed['temperaments'] ) ? 'Moderate' : 'Low' );
		$temperamentStr = implode( ', ', $breed['temperaments'] );
		$temperamentLower = strtolower( implode( ', ', array_slice( $breed['temperaments'], 0, 3 ) ) );
		$sizeLower = strtolower( $sizeCategory );

		$roles = [
			'Herding' => 'valued herding and farm dog',
			'Sporting' => 'popular sporting and hunting companion',
			'Hound' => 'skilled hunting hound',
			'Terrier' => 'spirited vermin hunter and companion',
			'Toy' => 'beloved companion dog',
			'Working' => 'dependable working dog',
			'Non-Sporting' => 'versatile companion',
		];
		$role = $roles[$breed['group']] ?? 'companion';

		$groomingInfo = $this->getGroomingInfo( $breed['coat'] );
		$groomingDetails = $this->getGroomingDetails( $breed['coat'] );
		$temperamentDetails = $this->getTemperamentDetails( $breed );
		$exerciseInfo = $this->getExerciseInfo( $breed, $exerciseLevel );
		$healthInfo = $this->getHealthInfo( $breed );
		$historyInfo = $this->getHistoryInfo( $breed );

		// Find a related breed from the same group
		$relatedBreed = $this->getRelatedBreed( $breed );

		// Generate a deterministic recognition date from the breed name
		$recognitionDate = $this->getRecognitionDate( $breed['name'] );

		// Build temperament set annotations using #set (inline [[Property::Value]] renders as text)
		$temperamentSetValues = implode( '|', array_map(
			static fn ( $t ) => 'Temperament=' . trim( $t ),
			$breed['temperaments']
		) );

		return "{{Dog breed
| breed_group = {$breed['group']}
| origin = {$breed['origin']}
| weight_min = {$breed['weightMin']}
| weight_max = {$breed['weightMax']}
| height_min = {$breed['heightMin']}
| height_max = {$breed['heightMax']}
| life_expectancy_min = {$breed['lifeMin']}
| life_expectancy_max = {$breed['lifeMax']}
| coat_type = {$breed['coat']}
| size_category = {$sizeCategory}
| exercise_level = {$exerciseLevel}
| temperament = {$temperamentStr}
| related_breed = {$relatedBreed}
| recognition_date = {$recognitionDate}
}}
{{#set: {$temperamentSetValues} }}
The '''{$breed['name']}''' is a [[:Category:{$sizeCategory} Dogs|{$sizeLower}]]-sized dog breed from the [[:Category:{$breed['group']} Group|{$breed['group']} Group]], originating in [[:Category:Dogs from {$breed['origin']}|{$breed['origin']}]]. Known for being {$temperamentLower}, this breed has been a {$role} for centuries.

== Appearance ==
The {$breed['name']} stands {$breed['heightMin']}–{$breed['heightMax']} cm tall and weighs {$breed['weightMin']}–{$breed['weightMax']} kg. It has a distinctive " . strtolower( $breed['coat'] ) . " coat that requires {$groomingInfo}. See [[Dog Grooming]] for more on coat care.

== Temperament ==
The {$breed['name']} is known for its " . strtolower( $temperamentStr ) . " nature. {$temperamentDetails} For more on canine temperament and communication, see [[Dog Behavior]].

== Care ==
=== Exercise ===
{$exerciseInfo}

=== Grooming ===
The " . strtolower( $breed['coat'] ) . " coat of the {$breed['name']} {$groomingDetails}

=== Health ===
The {$breed['name']} has a typical lifespan of {$breed['lifeMin']}–{$breed['lifeMax']} years. {$healthInfo} See [[Canine Health]] for general health information.

== History ==
{$historyInfo}

[[Category:Dog Breeds]]
[[Category:{$breed['group']} Group]]
[[Category:{$sizeCategory} Dogs]]
[[Category:Dogs from {$breed['origin']}]]
[[Category:Seed data]]";
	}

	private function getGroomingInfo( string $coat ): string {
		$info = [
			'Short' => 'minimal grooming — weekly brushing is usually sufficient',
			'Medium' => 'regular grooming — brushing two to three times per week',
			'Long' => 'extensive grooming — daily brushing to prevent mats and tangles',
			'Wire' => 'regular hand-stripping or professional grooming every few months',
			'Curly' => 'regular professional grooming every four to six weeks',
			'Double' => 'regular brushing, with heavy shedding during seasonal coat changes',
			'Smooth' => 'minimal grooming — occasional brushing to remove loose hair',
			'Silky' => 'regular brushing to maintain its smooth, flowing texture',
		];
		return $info[$coat] ?? 'regular grooming';
	}

	private function getGroomingDetails( string $coat ): string {
		$details = [
			'Short' => 'is easy to maintain. A weekly brush with a bristle brush removes loose hair and keeps the coat looking its best.',
			'Medium' => 'benefits from brushing two to three times per week to prevent tangles and reduce shedding.',
			'Long' => 'requires daily attention to prevent mats and tangles. Regular professional grooming is recommended.',
			'Wire' => 'should be hand-stripped several times a year to maintain its texture. Regular brushing between stripping sessions keeps it tidy.',
			'Curly' => 'does not shed much but grows continuously, requiring regular professional trimming every four to six weeks.',
			'Double' => 'sheds heavily twice a year during seasonal changes. Daily brushing during these periods and weekly brushing otherwise helps manage the undercoat.',
			'Smooth' => 'is low-maintenance, requiring only occasional brushing with a soft brush or grooming mitt.',
			'Silky' => 'needs regular brushing to keep it smooth and tangle-free. Many owners opt for professional grooming to maintain the coat.',
		];
		return $details[$coat] ?? 'requires regular grooming.';
	}

	private function getTemperamentDetails( array $breed ): string {
		if ( in_array( 'Energetic', $breed['temperaments'] ) ) {
			return 'This is an active breed that thrives with an engaged owner who can provide plenty of exercise and mental stimulation. They excel in activities like agility, obedience, and interactive play.';
		}
		if ( in_array( 'Calm', $breed['temperaments'] ) ) {
			return 'This is a relaxed breed that adapts well to various living situations. While they enjoy moderate activity, they are equally content with quiet time at home.';
		}
		if ( in_array( 'Independent', $breed['temperaments'] ) ) {
			return 'This breed has an independent streak and may require patient, consistent training. They bond deeply with their family but may be reserved with strangers.';
		}
		return 'They make excellent family companions and adapt well to various lifestyles when given proper socialization and training.';
	}

	private function getExerciseInfo( array $breed, string $level ): string {
		if ( $level === 'High' ) {
			return "The {$breed['name']} is a high-energy breed that needs at least 60–90 minutes of vigorous exercise daily. Activities like running, hiking, and interactive games are ideal.";
		}
		if ( $level === 'Moderate' ) {
			return "The {$breed['name']} needs moderate daily exercise — about 30–60 minutes of walking, play, or other activities to stay healthy and happy.";
		}
		return "The {$breed['name']} has relatively low exercise needs. A couple of short walks and some playtime each day are usually sufficient.";
	}

	private function getHealthInfo( array $breed ): string {
		if ( $breed['weightMax'] >= 40 ) {
			return 'As a large breed, they may be prone to hip and elbow dysplasia, bloat (gastric torsion), and joint issues. Regular veterinary checkups and weight management are important.';
		}
		if ( $breed['weightMax'] <= 5 ) {
			return 'As a small breed, they may be prone to dental issues, luxating patella, and tracheal collapse. Regular dental care and careful handling are important.';
		}
		return 'Regular veterinary checkups, a balanced diet, and appropriate exercise help ensure a long and healthy life.';
	}

	private function getHistoryInfo( array $breed ): string {
		$histories = [
			'Herding' => "The {$breed['name']} was originally developed in {$breed['origin']} as a herding dog, working alongside farmers and shepherds to manage livestock. Their intelligence and responsiveness made them indispensable on the farm.",
			'Sporting' => "The {$breed['name']} was bred in {$breed['origin']} as a sporting dog, assisting hunters in the field. Their keen senses and cooperative nature made them excellent hunting partners.",
			'Hound' => "The {$breed['name']} has ancient roots in {$breed['origin']}, where they were prized for their hunting abilities. Whether tracking by scent or coursing by sight, these dogs were essential to the hunt.",
			'Terrier' => "The {$breed['name']} originated in {$breed['origin']}, where they were bred to hunt and control vermin. Their tenacity, courage, and compact size made them perfect for pursuing quarry into underground dens.",
			'Toy' => "The {$breed['name']} has been a cherished companion in {$breed['origin']} for centuries. Originally bred as a lapdog for nobility, they have remained popular for their affectionate nature and portable size.",
			'Working' => "The {$breed['name']} was developed in {$breed['origin']} for practical working tasks. Their strength, endurance, and intelligence made them valued for guarding, drafting, and rescue work.",
			'Non-Sporting' => "The {$breed['name']} originated in {$breed['origin']} and has served various roles throughout history. Today they are primarily kept as companions, though many retain the instincts of their working ancestors.",
		];
		return $histories[$breed['group']] ?? "The {$breed['name']} originated in {$breed['origin']}.";
	}

	private function getRelatedBreed( array $breed ): string {
		$sameGroup = array_values( array_filter(
			$this->breeds,
			static fn ( $b ) => $b['group'] === $breed['group'] && $b['name'] !== $breed['name']
		) );
		if ( $sameGroup === [] ) {
			return '';
		}
		// Deterministic selection based on breed name hash
		$index = crc32( $breed['name'] ) % count( $sameGroup );
		return $sameGroup[$index]['name'];
	}

	private function getRecognitionDate( string $breedName ): string {
		// Generate a deterministic year (1878–1995) and month from the breed name
		$hash = crc32( $breedName );
		$year = 1878 + ( abs( $hash ) % 118 );
		$month = 1 + ( abs( $hash >> 8 ) % 12 );
		$day = 1 + ( abs( $hash >> 16 ) % 28 );
		return sprintf( '%04d-%02d-%02d', $year, $month, $day );
	}

	private function getMainPageContent(): string {
		return "Welcome to the '''Dog Breeds Wiki''', a comprehensive resource for information about dog breeds from around the world.

== Featured Breeds ==
{| class=\"wikitable\" style=\"width: 100%;\"
|-
! Breed !! Group !! Origin !! Size
|-
| [[Labrador Retriever]] || Sporting || Canada || Large
|-
| [[German Shepherd]] || Herding || Germany || Large
|-
| [[Golden Retriever]] || Sporting || Scotland || Large
|-
| [[French Bulldog]] || Non-Sporting || France || Medium
|-
| [[Beagle]] || Hound || England || Medium
|-
| [[Poodle]] || Non-Sporting || Germany || Medium–Large
|}

== Browse by Group ==
* [[:Category:Herding Group|Herding Group]] — Border Collie, German Shepherd, and more
* [[:Category:Sporting Group|Sporting Group]] — Labrador Retriever, Golden Retriever, and more
* [[:Category:Hound Group|Hound Group]] — Beagle, Greyhound, and more
* [[:Category:Terrier Group|Terrier Group]] — Jack Russell Terrier, Bull Terrier, and more
* [[:Category:Toy Group|Toy Group]] — Chihuahua, Pomeranian, and more
* [[:Category:Working Group|Working Group]] — Siberian Husky, Boxer, and more
* [[:Category:Non-Sporting Group|Non-Sporting Group]] — Poodle, French Bulldog, and more

== Browse by Size ==
* [[:Category:Small Dogs|Small Dogs]] — Under 10 kg
* [[:Category:Medium Dogs|Medium Dogs]] — 10–25 kg
* [[:Category:Large Dogs|Large Dogs]] — Over 25 kg

== Topics ==
* [[Dog Training]]
* [[Canine Health]]
* [[Dog Nutrition]]
* [[Dog Behavior]]
* [[Dog Grooming]]
* [[Dog Shows]]
* [[Working Dogs]]
* [[History of Dog Domestication]]

== SMW Queries ==
=== Breeds by Group ===
{{#ask: [[Category:Dog Breeds]] [[Breed group::Herding]]
| ?Origin country
| ?Coat type
| ?Size category
| limit=5
| sort=Origin country
}}

=== Longest-Living Breeds ===
{{#ask: [[Category:Dog Breeds]]
| ?Minimum life expectancy
| ?Maximum life expectancy
| ?Size category
| sort=Maximum life expectancy
| order=desc
| limit=5
}}

[[Category:Dogs]]
[[Category:Seed data]]";
	}

	private function initBreedData(): void {
		$this->breeds = [
			// Herding
			[ 'name' => 'Border Collie', 'group' => 'Herding', 'origin' => 'United Kingdom', 'weightMin' => 14, 'weightMax' => 20, 'heightMin' => 46, 'heightMax' => 56, 'lifeMin' => 12, 'lifeMax' => 15, 'coat' => 'Medium', 'temperaments' => [ 'Intelligent', 'Energetic', 'Loyal', 'Eager to please' ] ],
			[ 'name' => 'German Shepherd', 'group' => 'Herding', 'origin' => 'Germany', 'weightMin' => 22, 'weightMax' => 40, 'heightMin' => 55, 'heightMax' => 65, 'lifeMin' => 9, 'lifeMax' => 13, 'coat' => 'Double', 'temperaments' => [ 'Loyal', 'Intelligent', 'Confident', 'Brave' ] ],
			[ 'name' => 'Australian Shepherd', 'group' => 'Herding', 'origin' => 'United States', 'weightMin' => 18, 'weightMax' => 29, 'heightMin' => 46, 'heightMax' => 58, 'lifeMin' => 13, 'lifeMax' => 15, 'coat' => 'Medium', 'temperaments' => [ 'Intelligent', 'Energetic', 'Protective', 'Playful' ] ],
			[ 'name' => 'Pembroke Welsh Corgi', 'group' => 'Herding', 'origin' => 'Wales', 'weightMin' => 10, 'weightMax' => 14, 'heightMin' => 25, 'heightMax' => 30, 'lifeMin' => 12, 'lifeMax' => 15, 'coat' => 'Medium', 'temperaments' => [ 'Friendly', 'Playful', 'Alert', 'Loyal' ] ],
			[ 'name' => 'Shetland Sheepdog', 'group' => 'Herding', 'origin' => 'Scotland', 'weightMin' => 6, 'weightMax' => 12, 'heightMin' => 33, 'heightMax' => 41, 'lifeMin' => 12, 'lifeMax' => 14, 'coat' => 'Long', 'temperaments' => [ 'Intelligent', 'Gentle', 'Loyal', 'Alert' ] ],
			[ 'name' => 'Belgian Malinois', 'group' => 'Herding', 'origin' => 'Belgium', 'weightMin' => 20, 'weightMax' => 30, 'heightMin' => 56, 'heightMax' => 66, 'lifeMin' => 12, 'lifeMax' => 14, 'coat' => 'Short', 'temperaments' => [ 'Confident', 'Protective', 'Intelligent', 'Alert' ] ],
			[ 'name' => 'Rough Collie', 'group' => 'Herding', 'origin' => 'Scotland', 'weightMin' => 18, 'weightMax' => 29, 'heightMin' => 51, 'heightMax' => 61, 'lifeMin' => 12, 'lifeMax' => 14, 'coat' => 'Long', 'temperaments' => [ 'Gentle', 'Loyal', 'Intelligent', 'Friendly' ] ],
			[ 'name' => 'Old English Sheepdog', 'group' => 'Herding', 'origin' => 'England', 'weightMin' => 27, 'weightMax' => 45, 'heightMin' => 54, 'heightMax' => 61, 'lifeMin' => 10, 'lifeMax' => 12, 'coat' => 'Long', 'temperaments' => [ 'Playful', 'Gentle', 'Intelligent', 'Friendly' ] ],
			[ 'name' => 'Cardigan Welsh Corgi', 'group' => 'Herding', 'origin' => 'Wales', 'weightMin' => 11, 'weightMax' => 17, 'heightMin' => 27, 'heightMax' => 32, 'lifeMin' => 12, 'lifeMax' => 15, 'coat' => 'Medium', 'temperaments' => [ 'Loyal', 'Affectionate', 'Alert', 'Intelligent' ] ],
			[ 'name' => 'Australian Cattle Dog', 'group' => 'Herding', 'origin' => 'Australia', 'weightMin' => 15, 'weightMax' => 22, 'heightMin' => 43, 'heightMax' => 51, 'lifeMin' => 12, 'lifeMax' => 16, 'coat' => 'Short', 'temperaments' => [ 'Energetic', 'Loyal', 'Brave', 'Alert' ] ],
			[ 'name' => 'Bouvier des Flandres', 'group' => 'Herding', 'origin' => 'Belgium', 'weightMin' => 27, 'weightMax' => 40, 'heightMin' => 59, 'heightMax' => 68, 'lifeMin' => 10, 'lifeMax' => 12, 'coat' => 'Wire', 'temperaments' => [ 'Loyal', 'Protective', 'Gentle', 'Calm' ] ],
			[ 'name' => 'Briard', 'group' => 'Herding', 'origin' => 'France', 'weightMin' => 25, 'weightMax' => 45, 'heightMin' => 56, 'heightMax' => 69, 'lifeMin' => 10, 'lifeMax' => 12, 'coat' => 'Long', 'temperaments' => [ 'Loyal', 'Protective', 'Intelligent', 'Brave' ] ],

			// Sporting
			[ 'name' => 'Labrador Retriever', 'group' => 'Sporting', 'origin' => 'Canada', 'weightMin' => 25, 'weightMax' => 36, 'heightMin' => 55, 'heightMax' => 62, 'lifeMin' => 10, 'lifeMax' => 12, 'coat' => 'Short', 'temperaments' => [ 'Friendly', 'Energetic', 'Gentle', 'Eager to please' ] ],
			[ 'name' => 'Golden Retriever', 'group' => 'Sporting', 'origin' => 'Scotland', 'weightMin' => 25, 'weightMax' => 34, 'heightMin' => 51, 'heightMax' => 61, 'lifeMin' => 10, 'lifeMax' => 12, 'coat' => 'Long', 'temperaments' => [ 'Friendly', 'Intelligent', 'Gentle', 'Patient' ] ],
			[ 'name' => 'English Springer Spaniel', 'group' => 'Sporting', 'origin' => 'England', 'weightMin' => 18, 'weightMax' => 25, 'heightMin' => 46, 'heightMax' => 51, 'lifeMin' => 12, 'lifeMax' => 14, 'coat' => 'Medium', 'temperaments' => [ 'Friendly', 'Playful', 'Energetic', 'Eager to please' ] ],
			[ 'name' => 'Cocker Spaniel', 'group' => 'Sporting', 'origin' => 'United States', 'weightMin' => 10, 'weightMax' => 14, 'heightMin' => 34, 'heightMax' => 39, 'lifeMin' => 12, 'lifeMax' => 15, 'coat' => 'Silky', 'temperaments' => [ 'Gentle', 'Affectionate', 'Playful', 'Friendly' ] ],
			[ 'name' => 'German Shorthaired Pointer', 'group' => 'Sporting', 'origin' => 'Germany', 'weightMin' => 20, 'weightMax' => 32, 'heightMin' => 53, 'heightMax' => 64, 'lifeMin' => 12, 'lifeMax' => 14, 'coat' => 'Short', 'temperaments' => [ 'Energetic', 'Intelligent', 'Friendly', 'Eager to please' ] ],
			[ 'name' => 'Irish Setter', 'group' => 'Sporting', 'origin' => 'Ireland', 'weightMin' => 27, 'weightMax' => 32, 'heightMin' => 58, 'heightMax' => 67, 'lifeMin' => 12, 'lifeMax' => 15, 'coat' => 'Long', 'temperaments' => [ 'Energetic', 'Affectionate', 'Playful', 'Friendly' ] ],
			[ 'name' => 'Weimaraner', 'group' => 'Sporting', 'origin' => 'Germany', 'weightMin' => 25, 'weightMax' => 40, 'heightMin' => 56, 'heightMax' => 69, 'lifeMin' => 10, 'lifeMax' => 13, 'coat' => 'Short', 'temperaments' => [ 'Energetic', 'Brave', 'Friendly', 'Alert' ] ],
			[ 'name' => 'Vizsla', 'group' => 'Sporting', 'origin' => 'Hungary', 'weightMin' => 18, 'weightMax' => 30, 'heightMin' => 53, 'heightMax' => 64, 'lifeMin' => 12, 'lifeMax' => 15, 'coat' => 'Short', 'temperaments' => [ 'Affectionate', 'Energetic', 'Gentle', 'Loyal' ] ],
			[ 'name' => 'Brittany', 'group' => 'Sporting', 'origin' => 'France', 'weightMin' => 14, 'weightMax' => 18, 'heightMin' => 44, 'heightMax' => 52, 'lifeMin' => 12, 'lifeMax' => 14, 'coat' => 'Medium', 'temperaments' => [ 'Energetic', 'Friendly', 'Eager to please', 'Alert' ] ],
			[ 'name' => 'English Setter', 'group' => 'Sporting', 'origin' => 'England', 'weightMin' => 20, 'weightMax' => 36, 'heightMin' => 58, 'heightMax' => 68, 'lifeMin' => 11, 'lifeMax' => 15, 'coat' => 'Long', 'temperaments' => [ 'Gentle', 'Friendly', 'Calm', 'Affectionate' ] ],
			[ 'name' => 'Chesapeake Bay Retriever', 'group' => 'Sporting', 'origin' => 'United States', 'weightMin' => 25, 'weightMax' => 36, 'heightMin' => 53, 'heightMax' => 66, 'lifeMin' => 10, 'lifeMax' => 13, 'coat' => 'Double', 'temperaments' => [ 'Loyal', 'Protective', 'Intelligent', 'Brave' ] ],
			[ 'name' => 'Nova Scotia Duck Tolling Retriever', 'group' => 'Sporting', 'origin' => 'Canada', 'weightMin' => 17, 'weightMax' => 23, 'heightMin' => 43, 'heightMax' => 53, 'lifeMin' => 12, 'lifeMax' => 14, 'coat' => 'Medium', 'temperaments' => [ 'Intelligent', 'Energetic', 'Playful', 'Alert' ] ],

			// Hound
			[ 'name' => 'Beagle', 'group' => 'Hound', 'origin' => 'England', 'weightMin' => 9, 'weightMax' => 11, 'heightMin' => 33, 'heightMax' => 41, 'lifeMin' => 12, 'lifeMax' => 15, 'coat' => 'Short', 'temperaments' => [ 'Friendly', 'Curious', 'Playful', 'Gentle' ] ],
			[ 'name' => 'Dachshund', 'group' => 'Hound', 'origin' => 'Germany', 'weightMin' => 7, 'weightMax' => 15, 'heightMin' => 20, 'heightMax' => 23, 'lifeMin' => 12, 'lifeMax' => 16, 'coat' => 'Smooth', 'temperaments' => [ 'Playful', 'Brave', 'Stubborn', 'Curious' ] ],
			[ 'name' => 'Greyhound', 'group' => 'Hound', 'origin' => 'United Kingdom', 'weightMin' => 27, 'weightMax' => 40, 'heightMin' => 68, 'heightMax' => 76, 'lifeMin' => 10, 'lifeMax' => 13, 'coat' => 'Short', 'temperaments' => [ 'Gentle', 'Calm', 'Independent', 'Affectionate' ] ],
			[ 'name' => 'Basset Hound', 'group' => 'Hound', 'origin' => 'France', 'weightMin' => 20, 'weightMax' => 29, 'heightMin' => 28, 'heightMax' => 38, 'lifeMin' => 12, 'lifeMax' => 13, 'coat' => 'Short', 'temperaments' => [ 'Patient', 'Gentle', 'Stubborn', 'Friendly' ] ],
			[ 'name' => 'Bloodhound', 'group' => 'Hound', 'origin' => 'Belgium', 'weightMin' => 36, 'weightMax' => 50, 'heightMin' => 58, 'heightMax' => 69, 'lifeMin' => 10, 'lifeMax' => 12, 'coat' => 'Short', 'temperaments' => [ 'Gentle', 'Patient', 'Stubborn', 'Friendly' ] ],
			[ 'name' => 'Afghan Hound', 'group' => 'Hound', 'origin' => 'Afghanistan', 'weightMin' => 23, 'weightMax' => 27, 'heightMin' => 63, 'heightMax' => 74, 'lifeMin' => 12, 'lifeMax' => 14, 'coat' => 'Long', 'temperaments' => [ 'Independent', 'Reserved', 'Playful', 'Calm' ] ],
			[ 'name' => 'Rhodesian Ridgeback', 'group' => 'Hound', 'origin' => 'Zimbabwe', 'weightMin' => 29, 'weightMax' => 41, 'heightMin' => 61, 'heightMax' => 69, 'lifeMin' => 10, 'lifeMax' => 12, 'coat' => 'Short', 'temperaments' => [ 'Loyal', 'Intelligent', 'Brave', 'Protective' ] ],
			[ 'name' => 'Whippet', 'group' => 'Hound', 'origin' => 'England', 'weightMin' => 6, 'weightMax' => 14, 'heightMin' => 44, 'heightMax' => 56, 'lifeMin' => 12, 'lifeMax' => 15, 'coat' => 'Short', 'temperaments' => [ 'Gentle', 'Calm', 'Affectionate', 'Playful' ] ],
			[ 'name' => 'Irish Wolfhound', 'group' => 'Hound', 'origin' => 'Ireland', 'weightMin' => 48, 'weightMax' => 70, 'heightMin' => 71, 'heightMax' => 86, 'lifeMin' => 6, 'lifeMax' => 10, 'coat' => 'Wire', 'temperaments' => [ 'Gentle', 'Calm', 'Patient', 'Brave' ] ],
			[ 'name' => 'Saluki', 'group' => 'Hound', 'origin' => 'Middle East', 'weightMin' => 18, 'weightMax' => 27, 'heightMin' => 58, 'heightMax' => 71, 'lifeMin' => 12, 'lifeMax' => 14, 'coat' => 'Smooth', 'temperaments' => [ 'Independent', 'Gentle', 'Reserved', 'Calm' ] ],
			[ 'name' => 'Borzoi', 'group' => 'Hound', 'origin' => 'Russia', 'weightMin' => 27, 'weightMax' => 48, 'heightMin' => 66, 'heightMax' => 82, 'lifeMin' => 9, 'lifeMax' => 14, 'coat' => 'Silky', 'temperaments' => [ 'Calm', 'Independent', 'Gentle', 'Reserved' ] ],

			// Terrier
			[ 'name' => 'Jack Russell Terrier', 'group' => 'Terrier', 'origin' => 'England', 'weightMin' => 6, 'weightMax' => 8, 'heightMin' => 25, 'heightMax' => 30, 'lifeMin' => 13, 'lifeMax' => 16, 'coat' => 'Smooth', 'temperaments' => [ 'Energetic', 'Brave', 'Playful', 'Stubborn' ] ],
			[ 'name' => 'Bull Terrier', 'group' => 'Terrier', 'origin' => 'England', 'weightMin' => 22, 'weightMax' => 38, 'heightMin' => 53, 'heightMax' => 56, 'lifeMin' => 11, 'lifeMax' => 14, 'coat' => 'Short', 'temperaments' => [ 'Playful', 'Brave', 'Energetic', 'Loyal' ] ],
			[ 'name' => 'Yorkshire Terrier', 'group' => 'Terrier', 'origin' => 'England', 'weightMin' => 2, 'weightMax' => 3, 'heightMin' => 18, 'heightMax' => 23, 'lifeMin' => 13, 'lifeMax' => 16, 'coat' => 'Silky', 'temperaments' => [ 'Brave', 'Confident', 'Playful', 'Affectionate' ] ],
			[ 'name' => 'Airedale Terrier', 'group' => 'Terrier', 'origin' => 'England', 'weightMin' => 18, 'weightMax' => 29, 'heightMin' => 56, 'heightMax' => 61, 'lifeMin' => 10, 'lifeMax' => 13, 'coat' => 'Wire', 'temperaments' => [ 'Intelligent', 'Brave', 'Confident', 'Friendly' ] ],
			[ 'name' => 'Scottish Terrier', 'group' => 'Terrier', 'origin' => 'Scotland', 'weightMin' => 8, 'weightMax' => 10, 'heightMin' => 25, 'heightMax' => 28, 'lifeMin' => 12, 'lifeMax' => 15, 'coat' => 'Wire', 'temperaments' => [ 'Independent', 'Confident', 'Brave', 'Alert' ] ],
			[ 'name' => 'West Highland White Terrier', 'group' => 'Terrier', 'origin' => 'Scotland', 'weightMin' => 7, 'weightMax' => 10, 'heightMin' => 25, 'heightMax' => 28, 'lifeMin' => 12, 'lifeMax' => 16, 'coat' => 'Double', 'temperaments' => [ 'Friendly', 'Brave', 'Playful', 'Alert' ] ],
			[ 'name' => 'Staffordshire Bull Terrier', 'group' => 'Terrier', 'origin' => 'England', 'weightMin' => 11, 'weightMax' => 17, 'heightMin' => 36, 'heightMax' => 41, 'lifeMin' => 12, 'lifeMax' => 14, 'coat' => 'Short', 'temperaments' => [ 'Brave', 'Loyal', 'Affectionate', 'Gentle' ] ],
			[ 'name' => 'Fox Terrier', 'group' => 'Terrier', 'origin' => 'England', 'weightMin' => 6, 'weightMax' => 9, 'heightMin' => 33, 'heightMax' => 39, 'lifeMin' => 12, 'lifeMax' => 15, 'coat' => 'Wire', 'temperaments' => [ 'Energetic', 'Brave', 'Alert', 'Playful' ] ],
			[ 'name' => 'Cairn Terrier', 'group' => 'Terrier', 'origin' => 'Scotland', 'weightMin' => 6, 'weightMax' => 8, 'heightMin' => 23, 'heightMax' => 33, 'lifeMin' => 13, 'lifeMax' => 15, 'coat' => 'Wire', 'temperaments' => [ 'Brave', 'Curious', 'Playful', 'Independent' ] ],
			[ 'name' => 'Norfolk Terrier', 'group' => 'Terrier', 'origin' => 'England', 'weightMin' => 5, 'weightMax' => 6, 'heightMin' => 23, 'heightMax' => 25, 'lifeMin' => 12, 'lifeMax' => 15, 'coat' => 'Wire', 'temperaments' => [ 'Brave', 'Friendly', 'Playful', 'Loyal' ] ],

			// Toy
			[ 'name' => 'Chihuahua', 'group' => 'Toy', 'origin' => 'Mexico', 'weightMin' => 1, 'weightMax' => 3, 'heightMin' => 15, 'heightMax' => 23, 'lifeMin' => 14, 'lifeMax' => 18, 'coat' => 'Smooth', 'temperaments' => [ 'Brave', 'Alert', 'Loyal', 'Playful' ] ],
			[ 'name' => 'Pomeranian', 'group' => 'Toy', 'origin' => 'Germany', 'weightMin' => 1, 'weightMax' => 3, 'heightMin' => 18, 'heightMax' => 24, 'lifeMin' => 12, 'lifeMax' => 16, 'coat' => 'Double', 'temperaments' => [ 'Playful', 'Friendly', 'Alert', 'Curious' ] ],
			[ 'name' => 'Pug', 'group' => 'Toy', 'origin' => 'China', 'weightMin' => 6, 'weightMax' => 8, 'heightMin' => 25, 'heightMax' => 33, 'lifeMin' => 12, 'lifeMax' => 15, 'coat' => 'Short', 'temperaments' => [ 'Affectionate', 'Playful', 'Gentle', 'Calm' ] ],
			[ 'name' => 'Cavalier King Charles Spaniel', 'group' => 'Toy', 'origin' => 'England', 'weightMin' => 5, 'weightMax' => 8, 'heightMin' => 30, 'heightMax' => 33, 'lifeMin' => 9, 'lifeMax' => 14, 'coat' => 'Silky', 'temperaments' => [ 'Affectionate', 'Gentle', 'Patient', 'Friendly' ] ],
			[ 'name' => 'Shih Tzu', 'group' => 'Toy', 'origin' => 'China', 'weightMin' => 4, 'weightMax' => 7, 'heightMin' => 20, 'heightMax' => 28, 'lifeMin' => 10, 'lifeMax' => 16, 'coat' => 'Long', 'temperaments' => [ 'Affectionate', 'Playful', 'Friendly', 'Loyal' ] ],
			[ 'name' => 'Maltese', 'group' => 'Toy', 'origin' => 'Malta', 'weightMin' => 1, 'weightMax' => 4, 'heightMin' => 20, 'heightMax' => 25, 'lifeMin' => 12, 'lifeMax' => 15, 'coat' => 'Silky', 'temperaments' => [ 'Gentle', 'Playful', 'Affectionate', 'Friendly' ] ],
			[ 'name' => 'Papillon', 'group' => 'Toy', 'origin' => 'France', 'weightMin' => 2, 'weightMax' => 5, 'heightMin' => 20, 'heightMax' => 28, 'lifeMin' => 13, 'lifeMax' => 16, 'coat' => 'Long', 'temperaments' => [ 'Intelligent', 'Friendly', 'Energetic', 'Alert' ] ],
			[ 'name' => 'Havanese', 'group' => 'Toy', 'origin' => 'Cuba', 'weightMin' => 3, 'weightMax' => 6, 'heightMin' => 22, 'heightMax' => 29, 'lifeMin' => 13, 'lifeMax' => 15, 'coat' => 'Silky', 'temperaments' => [ 'Playful', 'Affectionate', 'Intelligent', 'Friendly' ] ],
			[ 'name' => 'Italian Greyhound', 'group' => 'Toy', 'origin' => 'Italy', 'weightMin' => 3, 'weightMax' => 5, 'heightMin' => 33, 'heightMax' => 38, 'lifeMin' => 14, 'lifeMax' => 15, 'coat' => 'Short', 'temperaments' => [ 'Gentle', 'Affectionate', 'Playful', 'Alert' ] ],
			[ 'name' => 'Japanese Chin', 'group' => 'Toy', 'origin' => 'Japan', 'weightMin' => 2, 'weightMax' => 7, 'heightMin' => 20, 'heightMax' => 27, 'lifeMin' => 10, 'lifeMax' => 14, 'coat' => 'Silky', 'temperaments' => [ 'Gentle', 'Loyal', 'Independent', 'Alert' ] ],

			// Working
			[ 'name' => 'Siberian Husky', 'group' => 'Working', 'origin' => 'Russia', 'weightMin' => 16, 'weightMax' => 27, 'heightMin' => 51, 'heightMax' => 60, 'lifeMin' => 12, 'lifeMax' => 14, 'coat' => 'Double', 'temperaments' => [ 'Energetic', 'Friendly', 'Independent', 'Playful' ] ],
			[ 'name' => 'Boxer', 'group' => 'Working', 'origin' => 'Germany', 'weightMin' => 25, 'weightMax' => 32, 'heightMin' => 53, 'heightMax' => 63, 'lifeMin' => 10, 'lifeMax' => 12, 'coat' => 'Short', 'temperaments' => [ 'Playful', 'Energetic', 'Loyal', 'Brave' ] ],
			[ 'name' => 'Rottweiler', 'group' => 'Working', 'origin' => 'Germany', 'weightMin' => 36, 'weightMax' => 60, 'heightMin' => 56, 'heightMax' => 69, 'lifeMin' => 8, 'lifeMax' => 10, 'coat' => 'Short', 'temperaments' => [ 'Loyal', 'Confident', 'Protective', 'Brave' ] ],
			[ 'name' => 'Doberman Pinscher', 'group' => 'Working', 'origin' => 'Germany', 'weightMin' => 27, 'weightMax' => 45, 'heightMin' => 61, 'heightMax' => 72, 'lifeMin' => 10, 'lifeMax' => 13, 'coat' => 'Short', 'temperaments' => [ 'Intelligent', 'Loyal', 'Alert', 'Confident' ] ],
			[ 'name' => 'Great Dane', 'group' => 'Working', 'origin' => 'Germany', 'weightMin' => 45, 'weightMax' => 90, 'heightMin' => 71, 'heightMax' => 86, 'lifeMin' => 7, 'lifeMax' => 10, 'coat' => 'Short', 'temperaments' => [ 'Friendly', 'Patient', 'Gentle', 'Brave' ] ],
			[ 'name' => 'Bernese Mountain Dog', 'group' => 'Working', 'origin' => 'Switzerland', 'weightMin' => 36, 'weightMax' => 50, 'heightMin' => 58, 'heightMax' => 70, 'lifeMin' => 7, 'lifeMax' => 10, 'coat' => 'Long', 'temperaments' => [ 'Gentle', 'Affectionate', 'Loyal', 'Calm' ] ],
			[ 'name' => 'Saint Bernard', 'group' => 'Working', 'origin' => 'Switzerland', 'weightMin' => 54, 'weightMax' => 82, 'heightMin' => 65, 'heightMax' => 90, 'lifeMin' => 8, 'lifeMax' => 10, 'coat' => 'Long', 'temperaments' => [ 'Gentle', 'Patient', 'Calm', 'Friendly' ] ],
			[ 'name' => 'Akita', 'group' => 'Working', 'origin' => 'Japan', 'weightMin' => 32, 'weightMax' => 59, 'heightMin' => 61, 'heightMax' => 71, 'lifeMin' => 10, 'lifeMax' => 13, 'coat' => 'Double', 'temperaments' => [ 'Loyal', 'Brave', 'Protective', 'Reserved' ] ],
			[ 'name' => 'Alaskan Malamute', 'group' => 'Working', 'origin' => 'United States', 'weightMin' => 34, 'weightMax' => 39, 'heightMin' => 58, 'heightMax' => 64, 'lifeMin' => 10, 'lifeMax' => 14, 'coat' => 'Double', 'temperaments' => [ 'Friendly', 'Loyal', 'Playful', 'Energetic' ] ],
			[ 'name' => 'Newfoundland', 'group' => 'Working', 'origin' => 'Canada', 'weightMin' => 45, 'weightMax' => 68, 'heightMin' => 63, 'heightMax' => 74, 'lifeMin' => 8, 'lifeMax' => 10, 'coat' => 'Long', 'temperaments' => [ 'Gentle', 'Patient', 'Calm', 'Loyal' ] ],
			[ 'name' => 'Portuguese Water Dog', 'group' => 'Working', 'origin' => 'Portugal', 'weightMin' => 16, 'weightMax' => 27, 'heightMin' => 43, 'heightMax' => 57, 'lifeMin' => 11, 'lifeMax' => 13, 'coat' => 'Curly', 'temperaments' => [ 'Intelligent', 'Energetic', 'Loyal', 'Playful' ] ],
			[ 'name' => 'Great Pyrenees', 'group' => 'Working', 'origin' => 'France', 'weightMin' => 36, 'weightMax' => 54, 'heightMin' => 63, 'heightMax' => 82, 'lifeMin' => 10, 'lifeMax' => 12, 'coat' => 'Long', 'temperaments' => [ 'Calm', 'Patient', 'Protective', 'Gentle' ] ],

			// Non-Sporting
			[ 'name' => 'Poodle', 'group' => 'Non-Sporting', 'origin' => 'Germany', 'weightMin' => 18, 'weightMax' => 32, 'heightMin' => 38, 'heightMax' => 60, 'lifeMin' => 12, 'lifeMax' => 15, 'coat' => 'Curly', 'temperaments' => [ 'Intelligent', 'Energetic', 'Playful', 'Alert' ] ],
			[ 'name' => 'French Bulldog', 'group' => 'Non-Sporting', 'origin' => 'France', 'weightMin' => 8, 'weightMax' => 14, 'heightMin' => 28, 'heightMax' => 33, 'lifeMin' => 10, 'lifeMax' => 14, 'coat' => 'Short', 'temperaments' => [ 'Playful', 'Affectionate', 'Calm', 'Patient' ] ],
			[ 'name' => 'Bulldog', 'group' => 'Non-Sporting', 'origin' => 'England', 'weightMin' => 18, 'weightMax' => 25, 'heightMin' => 31, 'heightMax' => 40, 'lifeMin' => 8, 'lifeMax' => 10, 'coat' => 'Short', 'temperaments' => [ 'Gentle', 'Calm', 'Loyal', 'Friendly' ] ],
			[ 'name' => 'Dalmatian', 'group' => 'Non-Sporting', 'origin' => 'Croatia', 'weightMin' => 20, 'weightMax' => 32, 'heightMin' => 48, 'heightMax' => 61, 'lifeMin' => 11, 'lifeMax' => 13, 'coat' => 'Short', 'temperaments' => [ 'Energetic', 'Playful', 'Friendly', 'Intelligent' ] ],
			[ 'name' => 'Chow Chow', 'group' => 'Non-Sporting', 'origin' => 'China', 'weightMin' => 20, 'weightMax' => 32, 'heightMin' => 43, 'heightMax' => 51, 'lifeMin' => 9, 'lifeMax' => 15, 'coat' => 'Double', 'temperaments' => [ 'Independent', 'Loyal', 'Reserved', 'Calm' ] ],
			[ 'name' => 'Shiba Inu', 'group' => 'Non-Sporting', 'origin' => 'Japan', 'weightMin' => 8, 'weightMax' => 11, 'heightMin' => 33, 'heightMax' => 43, 'lifeMin' => 12, 'lifeMax' => 15, 'coat' => 'Double', 'temperaments' => [ 'Alert', 'Independent', 'Brave', 'Loyal' ] ],
			[ 'name' => 'Boston Terrier', 'group' => 'Non-Sporting', 'origin' => 'United States', 'weightMin' => 5, 'weightMax' => 11, 'heightMin' => 38, 'heightMax' => 43, 'lifeMin' => 11, 'lifeMax' => 13, 'coat' => 'Short', 'temperaments' => [ 'Friendly', 'Intelligent', 'Playful', 'Gentle' ] ],
			[ 'name' => 'Lhasa Apso', 'group' => 'Non-Sporting', 'origin' => 'Tibet', 'weightMin' => 5, 'weightMax' => 8, 'heightMin' => 25, 'heightMax' => 28, 'lifeMin' => 12, 'lifeMax' => 15, 'coat' => 'Long', 'temperaments' => [ 'Independent', 'Alert', 'Loyal', 'Confident' ] ],
			[ 'name' => 'Bichon Frise', 'group' => 'Non-Sporting', 'origin' => 'France', 'weightMin' => 3, 'weightMax' => 5, 'heightMin' => 23, 'heightMax' => 30, 'lifeMin' => 14, 'lifeMax' => 15, 'coat' => 'Curly', 'temperaments' => [ 'Playful', 'Gentle', 'Affectionate', 'Friendly' ] ],
			[ 'name' => 'Keeshond', 'group' => 'Non-Sporting', 'origin' => 'Netherlands', 'weightMin' => 16, 'weightMax' => 20, 'heightMin' => 43, 'heightMax' => 46, 'lifeMin' => 12, 'lifeMax' => 15, 'coat' => 'Double', 'temperaments' => [ 'Friendly', 'Playful', 'Alert', 'Intelligent' ] ],
			[ 'name' => 'Shar-Pei', 'group' => 'Non-Sporting', 'origin' => 'China', 'weightMin' => 18, 'weightMax' => 29, 'heightMin' => 44, 'heightMax' => 51, 'lifeMin' => 8, 'lifeMax' => 12, 'coat' => 'Short', 'temperaments' => [ 'Loyal', 'Independent', 'Calm', 'Protective' ] ],
			[ 'name' => 'Finnish Spitz', 'group' => 'Non-Sporting', 'origin' => 'Finland', 'weightMin' => 9, 'weightMax' => 14, 'heightMin' => 39, 'heightMax' => 50, 'lifeMin' => 12, 'lifeMax' => 15, 'coat' => 'Double', 'temperaments' => [ 'Friendly', 'Playful', 'Intelligent', 'Brave' ] ],
		];
	}

	private function initTopicPages(): void {
		$this->topicPages = [
			[
				'title' => 'Dog Training',
				'content' => "'''Dog training''' is the application of behavior analysis which uses the environmental events of antecedents and consequences to modify the [[dog]] behavior, either for it to assist in specific activities or undertake particular tasks, or for it to participate effectively in contemporary domestic life.

== Methods ==
Modern dog training methods focus on positive reinforcement, which rewards desired behaviors rather than punishing unwanted ones. Common approaches include:
* '''Clicker training''' — using a clicker device to mark the exact moment a dog performs a desired behavior
* '''Lure-reward training''' — using treats to guide a dog into position
* '''Relationship-based training''' — building on the bond between dog and handler

== Basic Commands ==
Most training programs teach these fundamental commands:
# Sit
# Stay
# Come (recall)
# Down
# Heel
# Leave it

== Socialization ==
Early socialization is critical for puppies, typically between 3 and 14 weeks of age. Exposure to different people, animals, environments, and experiences during this period helps develop a well-adjusted adult dog.

[[Category:Dog Care]]",
			],
			[
				'title' => 'Canine Health',
				'content' => "'''Canine health''' encompasses the prevention, diagnosis, and treatment of diseases and conditions in domestic dogs.

== Common Health Issues ==
Dogs may experience a variety of health problems throughout their lives:
* '''Hip dysplasia''' — particularly common in large breeds like the [[German Shepherd]] and [[Labrador Retriever]]
* '''Dental disease''' — affects most dogs over age three
* '''Obesity''' — a growing concern, especially in breeds like the [[Pug]] and [[Beagle]]
* '''Allergies''' — can be environmental, food-based, or flea-related

== Preventive Care ==
Regular veterinary checkups, vaccinations, and parasite prevention are the foundation of canine health care.

=== Vaccination Schedule ===
Core vaccines include:
* Rabies
* Distemper
* Parvovirus
* Adenovirus

== Nutrition ==
Proper nutrition varies by breed, size, age, and activity level. Large breeds like the [[Great Dane]] and [[Saint Bernard]] have different nutritional needs than toy breeds like the [[Chihuahua]].

== Exercise Requirements ==
Exercise needs vary significantly by breed group:
* '''Sporting''' and '''Herding''' breeds typically need 1–2 hours of vigorous exercise daily
* '''Toy''' breeds may need only 30 minutes of moderate activity
* '''Working''' breeds often need both physical and mental stimulation

[[Category:Dog Care]]",
			],
			[
				'title' => 'Dog Shows',
				'content' => "'''Dog shows''' (also known as '''conformation shows''') are events where dogs are evaluated by judges based on how well they conform to their [[breed]] standard.

== Types of Dog Shows ==
* '''Conformation shows''' — judging physical appearance and structure
* '''Obedience trials''' — testing training and responsiveness
* '''Agility competitions''' — timed obstacle course runs
* '''Field trials''' — evaluating hunting and sporting abilities
* '''Rally obedience''' — combining elements of obedience and agility

== Major Dog Shows ==
Some of the world's most prestigious dog shows include:
* '''Westminster Kennel Club Dog Show''' (United States) — held annually since 1877
* '''Crufts''' (United Kingdom) — the world's largest dog show
* '''World Dog Show''' — organized by the Fédération Cynologique Internationale

[[Category:Dog Activities]]",
			],
			[
				'title' => 'Dog Grooming',
				'content' => "'''Dog grooming''' refers to the hygienic care and cleaning of a dog, as well as the process of enhancing a dog's physical appearance for showing or other types of competition.

== Coat Types and Care ==
Different coat types require different grooming approaches:

{| class=\"wikitable\"
|-
! Coat Type !! Grooming Frequency !! Example Breeds
|-
| Short || Weekly brushing || [[Beagle]], [[Boxer]]
|-
| Medium || 2–3 times weekly || [[Border Collie]], [[Australian Shepherd]]
|-
| Long || Daily brushing || [[Afghan Hound]], [[Shih Tzu]]
|-
| Wire || Hand-stripping every 4–6 weeks || [[Airedale Terrier]], [[Scottish Terrier]]
|-
| Curly || Every 4–6 weeks (professional) || [[Poodle]], [[Bichon Frise]]
|-
| Double || Daily during shedding season || [[Siberian Husky]], [[Akita]]
|}

== Basic Grooming Tasks ==
# Brushing and combing
# Bathing (every 4–8 weeks for most breeds)
# Nail trimming (every 2–4 weeks)
# Ear cleaning
# Teeth brushing (ideally daily)
# Eye care

[[Category:Dog Care]]",
			],
			[
				'title' => 'History of Dog Domestication',
				'content' => "The '''history of dog domestication''' traces the transformation of wolves into the domestic dogs we know today, a process that began at least 15,000 years ago.

== Timeline ==
* '''~40,000–15,000 years ago''' — Initial domestication, likely from a now-extinct wolf population
* '''~10,000 years ago''' — Dogs spread to all inhabited continents
* '''~4,000 years ago''' — First evidence of distinct breed types in ancient art
* '''19th century''' — Formal breed standardization and kennel clubs established

== How Domestication Happened ==
The most widely accepted theory suggests that wolves self-domesticated by scavenging near human settlements. The friendliest wolves gained access to more food, survived better, and gradually evolved into proto-dogs.

== Early Roles ==
Dogs served many purposes in early human societies:
* '''Hunting partners''' — the ancestors of modern hound and sporting breeds
* '''Livestock guardians''' — early versions of [[Great Pyrenees]] and similar breeds
* '''Herding''' — leading to today's herding breeds
* '''Companionship''' — the oldest and most enduring role

== Modern Breeds ==
Today there are over 400 recognized dog breeds worldwide, organized into groups based on their original function. From the tiny [[Chihuahua]] to the massive [[Irish Wolfhound]], the diversity of modern dogs is a testament to thousands of years of selective breeding.

[[Category:Dog History]]",
			],
			[
				'title' => 'Working Dogs',
				'content' => "'''Working dogs''' are breeds that were developed to perform practical tasks beyond companionship, such as guarding, pulling sleds, or performing water rescues.

== Types of Working Dogs ==

=== Guard Dogs ===
Breeds like the [[Rottweiler]], [[Doberman Pinscher]], and [[Akita]] have been used for centuries to protect property and livestock.

=== Sled Dogs ===
The [[Siberian Husky]] and [[Alaskan Malamute]] were bred to pull sleds in Arctic conditions.

=== Search and Rescue ===
The [[Saint Bernard]] is perhaps the most famous rescue dog. Modern search and rescue dogs include [[German Shepherd]]s, [[Labrador Retriever]]s, and [[Bloodhound]]s.

=== Service Dogs ===
Many breeds serve as assistance dogs:
* '''Guide dogs''' — [[Labrador Retriever]], [[Golden Retriever]]
* '''Hearing dogs''' — various breeds
* '''Mobility assistance''' — [[Bernese Mountain Dog]], [[Great Dane]]

=== Police and Military ===
The [[Belgian Malinois]] and [[German Shepherd]] are the most common police and military working dogs.

[[Category:Dog Activities]]",
			],
			[
				'title' => 'Dog Nutrition',
				'content' => "'''Dog nutrition''' is the study of the dietary needs of dogs and the science of preparing adequate dog food.

== Essential Nutrients ==
Dogs require a balanced diet containing:
* '''Proteins''' — the building blocks of muscles, organs, and immune function
* '''Fats''' — concentrated energy source, essential for skin and coat health
* '''Carbohydrates''' — provide energy and fiber
* '''Vitamins''' — A, B complex, C, D, E, and K
* '''Minerals''' — calcium, phosphorus, iron, zinc, and others
* '''Water''' — the most critical nutrient

== Feeding by Life Stage ==

=== Puppies ===
Puppies need more calories and nutrients per kilogram of body weight than adult dogs. Large breed puppies like [[Great Dane]] and [[Bernese Mountain Dog]] require controlled growth diets.

=== Adults ===
Adult dogs should be fed based on their size, breed, and activity level:
* Small breeds ([[Chihuahua]], [[Maltese]]): higher metabolism, may need more frequent meals
* Large breeds ([[Labrador Retriever]], [[Rottweiler]]): prone to bloat, benefit from multiple smaller meals
* Active breeds ([[Border Collie]], [[Vizsla]]): may need 20–40% more calories

=== Seniors ===
Older dogs often need fewer calories but more joint-supporting nutrients.

== Foods to Avoid ==
Several common human foods are toxic to dogs:
* Chocolate
* Grapes and raisins
* Onions and garlic
* Xylitol (artificial sweetener)
* Macadamia nuts
* Alcohol

[[Category:Dog Care]]",
			],
			[
				'title' => 'Dog Behavior',
				'content' => "'''Dog behavior''' encompasses the internally coordinated responses of dogs to internal and external stimuli. Understanding dog behavior is essential for effective [[Dog Training|training]].

== Communication ==

=== Body Language ===
* '''Tail wagging''' — not always a sign of happiness; speed, height, and direction matter
* '''Ear position''' — forward (alert), back (submissive or fearful)
* '''Play bow''' — front end down, rear end up, an invitation to play
* '''Hackles raised''' — arousal, not necessarily aggression

=== Vocalizations ===
* '''Barking''' — alert, play, anxiety, or attention-seeking
* '''Howling''' — communication, response to sirens, separation anxiety
* '''Growling''' — warning signal, but also occurs during play
* '''Whining''' — stress, excitement, or seeking attention

== Breed-Specific Behaviors ==
* '''Herding breeds''' ([[Border Collie]], [[Australian Shepherd]]) may try to herd children or other pets
* '''Hounds''' ([[Beagle]], [[Bloodhound]]) are scent-driven and may follow their nose over commands
* '''Terriers''' ([[Jack Russell Terrier]], [[Cairn Terrier]]) have high prey drive and love to dig
* '''Retrievers''' ([[Labrador Retriever]], [[Golden Retriever]]) are mouthy and love to carry objects
* '''Guardian breeds''' ([[Akita]], [[Rottweiler]]) are naturally protective

== Common Behavioral Issues ==
* Separation anxiety
* Resource guarding
* Leash reactivity
* Excessive barking
* Destructive chewing

Most behavioral issues can be addressed through proper [[Dog Training|training]], adequate exercise, and mental stimulation.

[[Category:Dog Care]]",
			],
		];
	}
}

$maintClass = seedDemoData::class;
require_once RUN_MAINTENANCE_IF_MAIN;
