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
 * Creates ~150 dog-themed pages covering all common SMW property types
 * (Text, Number, Quantity, Page, Date), multi-value properties, templates with
 * annotations, categories, and inline queries. Useful for development
 * and testing of SMW features.
 *
 * Usage:
 *   php seedDemoData.php --force
 *   php seedDemoData.php --force --clear-only
 *
 * @since 7.0.0
 */
class seedDemoData extends Maintenance {

	private User $user;
	private WikiPageFactory $wikiPageFactory;
	private DemoContentBuilder $content;

	public function __construct() {
		parent::__construct();
		$this->content = new DemoContentBuilder();
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
				$this->output( "  - Create SMW Query Examples page\n" );
			}
			$this->output( "\nRe-run with --force to execute.\n" );
			return;
		}

		$maintenanceCheck = new MaintenanceCheck();
		if ( !$maintenanceCheck->canExecute() ) {
			exit( $maintenanceCheck->getMessage() );
		}

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

		// 2. Templates
		$this->output( "\n--- Creating templates ---\n" );
		$createdTemplates = 0;
		if ( $this->editPage( 'Template:Dog breed', $this->content->renderBreedTemplate(), 'Seed: create dog breed template' ) ) {
			$this->output( "  Created Template:Dog breed\n" );
			$createdTemplates++;
		}
		if ( $this->editPage( 'Template:Breed query row', $this->content->renderBreedQueryRowTemplate(), 'Seed: create breed query row template' ) ) {
			$this->output( "  Created Template:Breed query row\n" );
			$createdTemplates++;
		}

		// 3. Categories
		$this->output( "\n--- Creating categories ---\n" );
		$categories = $this->content->getCategoryData();
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
		foreach ( $this->content->getBreedData() as $breed ) {
			$content = $this->content->renderBreedArticle( $breed );
			if ( $this->editPage( $breed['name'], $content, 'Seed: create dog breed page' ) ) {
				$this->output( "  Created: {$breed['name']}\n" );
				$createdBreeds++;
			}
		}

		// 5. Topic pages
		$this->output( "\n--- Creating topic pages ---\n" );
		$createdTopics = 0;
		foreach ( $this->content->getTopicPages() as $page ) {
			if ( $this->editPage( $page['title'], $page['content'] . "\n[[Category:Seed data]]", 'Seed: create topic page' ) ) {
				$this->output( "  Created: {$page['title']}\n" );
				$createdTopics++;
			}
		}

		// 6. SMW query showcase
		$this->output( "\n--- Creating query showcase page ---\n" );
		$createdQueryShowcase = 0;
		if ( $this->editPage( 'SMW Query Examples', $this->content->renderQueryShowcase(), 'Seed: query examples page' ) ) {
			$this->output( "  Created: SMW Query Examples\n" );
			$createdQueryShowcase = 1;
		}

		// Summary
		$this->output( "\n=== Summary ===\n" );
		$this->output( "  {$createdBreeds} breed pages\n" );
		$this->output( "  {$createdTopics} topic pages\n" );
		$this->output( "  {$createdQueryShowcase} query showcase page\n" );
		$this->output( "  {$createdProperties} SMW properties\n" );
		$this->output( "  {$createdTemplates} templates\n" );
		$this->output( "  {$createdCategories} categories\n" );
		$total = $createdTrackingCategory + $createdBreeds + $createdTopics +
			$createdQueryShowcase + $createdProperties + $createdTemplates + $createdCategories;
		$this->output( "  Total: ~{$total} pages\n" );
	}
}

$maintClass = seedDemoData::class;
require_once RUN_MAINTENANCE_IF_MAIN;
