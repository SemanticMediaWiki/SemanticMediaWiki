<?php

namespace SMW\Test;

use ParserOutput;
use WikiPage;
use Title;
use User;

/**
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 *
 * @licence GNU GPL v2+
 * @since 1.9.0.3
 *
 * @author mwjames
 */
class ContentFetcher {

	protected $title = null;
	protected $parserOutput = null;

	public function __construct( Title $title ) {
		$this->title = $title;
	}

	public function fetchOutput() {

		$wikiPage = WikiPage::factory( $this->title );
		$revision = $wikiPage->getRevision();

		$parserOutput = $wikiPage->getParserOutput(
			$wikiPage->makeParserOptions( User::newFromId( $revision->getUser() ) ),
			$revision->getId()
		);

		return $parserOutput;
	}

	public function getOutput() {

		if ( $this->parserOutput === null ) {
			$this->parserOutput = $this->fetchOutput();
		}

		return $this->parserOutput;
	}

	/**
	 * @see PHPUnit_Util_Test::parseAnnotations
	 */
	public function parseAnnotations() {

		$annotations = array();

		$docblock = $this->getOutput() instanceof ParserOutput ? $this->getOutput()->getText() : '';

		if ( preg_match_all('/@(?P<name>[A-Za-z_-]+)(?:[ \t]+(?P<value>.*?))?[ \t]*\r?$/m', $docblock, $matches ) ) {
			$numMatches = count($matches[0]);

			for ( $i = 0; $i < $numMatches; ++$i ) {
				$annotations[$matches['name'][$i]][] = $matches['value'][$i];
			}
		}

		return $annotations;
	}

}
