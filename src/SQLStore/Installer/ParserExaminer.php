<?php

namespace SMW\SQLStore\Installer;

use Onoi\MessageReporter\MessageReporterAwareTrait;
use SMW\MediaWiki\ParsoidUse;
use SMW\Utils\CliMsgFormatter;

/**
 * @private
 *
 * @license GPL-2.0-or-later
 * @since 7.4.1
 */
class ParserExaminer {

	use MessageReporterAwareTrait;

	public function __construct(
		private readonly ParsoidUse $parsoidUse
	) {
	}

	public function reportParsoidUse(): void {
		$text = [];

		if ( $this->parsoidUse->forLinksUpdates() ) {
			$text[] = 'MediaWiki uses Parsoid for links updates, so Semantic MediaWiki does not store the ' .
				'in-text annotations and categories of saved pages, and updating a page, such as by ' .
				're-saving it or running `rebuildData.php`, removes the ones it had.';
		}

		if ( $this->parsoidUse->forRendering() ) {
			$text[] = 'MediaWiki renders pages with Parsoid, so in-text annotations show as links to pages ' .
				'named after the whole annotation, such as "Has text::Intext".';
		}

		if ( $text === [] ) {
			return;
		}

		$text[] = "\n\nSemantic MediaWiki does not support Parsoid yet, see " .
			'https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/7205. To use the legacy parser, ' .
			'set `$wgUseParsoidParser = false;` and do not set `$wgUseParsoidLinksUpdate` to true. Then run ' .
			'`rebuildData.php` for all pages to restore the lost annotations and categories.';

		$cliMsgFormatter = new CliMsgFormatter();

		$this->messageReporter->reportMessage(
			$cliMsgFormatter->section( 'Compatibility notice' )
		);

		$this->messageReporter->reportMessage(
			"\n" . $cliMsgFormatter->wordwrap( $text ) . "\n"
		);
	}

}
