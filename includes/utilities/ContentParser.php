<?php

namespace SMW;

use ParserOptions;
use Revision;
use User;
use Title;

/**
 * Fetch page content
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * Fetch page content either by parsing an invoked text compenent, reparsing
 * a text revision, or accessing the ContentHandler to produce a ParserOutput
 * object
 *
 * @ingroup SMW
 */
class ContentParser {

	/** @var Title */
	protected $title;

	/** @var ParserOutput */
	protected $parserOutput = null;

	/** @var Revision */
	protected $revision = null;

	/** @var ParserOptions */
	protected $parserOptions = null;

	/** @var Parser */
	protected $parser = null;

	/** @var string */
	protected $text = null;

	/** @var array */
	protected $errors = array();

	/**
	 * @since 1.9
	 *
	 * @param Title $title
	 */
	public function __construct( Title $title ) {
		$this->title = $title;
	}

	/**
	 * Returns collected errors occurred during processing
	 *
	 * @since 1.9
	 *
	 * @return array
	 */
	public function getErrors() {
		return $this->errors;
	}

	/**
	 * Returns Title object
	 *
	 * @since 1.9
	 *
	 * @return Title
	 */
	public function getTitel() {
		return $this->title;
	}

	/**
	 * Returns ParserOutput object
	 *
	 * @since 1.9
	 *
	 * @return ParserOutput|null
	 */
	public function getOutput() {
		return $this->parserOutput;
	}

	/**
	 * Invokes text to be parsed
	 *
	 * @since 1.9
	 *
	 * @return ContentParser|null
	 */
	public function setText( $text ) {
		$this->text = $text;
		return $this;
	}

	/**
	 * Generates or fetches output content from the appropriate source
	 *
	 * @since 1.9
	 *
	 * @return ContentParser
	 */
	public function parse() {

		if ( $this->text !== null ) {
			$this->parseText();
		} else {

		//	if ( class_exists( 'ContentHandler') ) {
		//		$this->fetchFromContentHandler(); // Add unit test
		//	} else {}
			$this->fetchFromParser();
		}

		return $this;
	}

	/**
	 * Parsing text
	 *
	 * @since 1.9
	 */
	protected function parseText() {
		Profiler::In( __METHOD__ );

		$this->parserOutput = $this->getParser()->parse( $this->text, $this->getTitel(), $this->getParserOptions() );

		Profiler::Out( __METHOD__ );
	}

	/**
	 * Using the content handler to return a ParserOutput object
	 *
	 * @since 1.9
	 */
	protected function fetchFromContentHandler() {
		Profiler::In( __METHOD__ );

		$content = $this->getRevision()->getContent( Revision::RAW );

		if ( !$content ) {
			// if there is no content, pretend the content is empty
			$content = $this->getRevision()->getContentHandler()->makeEmptyContent();
		}

		// Revision ID must be passed to the parser output to get revision variables correct
		$this->parserOutput = $content->getParserOutput( $this->getTitle(), $getRevision()->getId(), null, false );

		Profiler::Out( __METHOD__ );
	}

	/**
	 * Reparsing page content from a revision
	 *
	 * @since 1.9
	 */
	protected function fetchFromParser() {
		Profiler::In( __METHOD__ );

		if ( $this->getRevision() !== null ) {
			$this->parserOutput = $this->getParser()->parse(
				$this->getRevision()->getText(),
				$this->getTitel(),
				$this->getParserOptions(),
				true,
				true,
				$this->getRevision()->getID()
			);
		} else {
			$this->errors = array( __METHOD__ . " No revision available for {$this->getTitel()->getPrefixedDBkey()}" );
		}

		Profiler::Out( __METHOD__ );
	}

	/**
	 * Returns ParserOptions object
	 *
	 * @since 1.9
	 *
	 * @return ParserOptions
	 */
	protected function getParserOptions() {

		if ( $this->parserOptions === null ) {
			if ( $this->revision === null ) {
				$this->parserOptions = new ParserOptions();
			} else {
				$this->parserOptions = new ParserOptions( User::newFromId( $this->revision->getUser() ) );
			}
		}

		return $this->parserOptions;
	}

	/**
	 * Returns Revision object
	 *
	 * @note Revision::READ_NORMAL does not exists in MW 1.19
	 *
	 * @since 1.9
	 *
	 * @return Revision
	 */
	protected function getRevision() {

		if ( $this->revision === null ) {
			if ( class_exists( 'ContentHandler') ) {
				$this->revision = Revision::newFromTitle( $this->getTitel(), false, Revision::READ_NORMAL );
			} else{
				$this->revision = Revision::newFromTitle( $this->getTitel() );
			}
		}

		return $this->revision;
	}

	/**
	 * Returns Parser object
	 *
	 * @since 1.9
	 *
	 * @return Parser
	 */
	protected function getParser() {

		if ( $this->parser === null ) {
			$this->parser = $GLOBALS['wgParser'];
		}

		return $this->parser;
	}
}
