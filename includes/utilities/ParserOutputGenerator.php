<?php

namespace SMW;

use ParserOptions;
use Revision;
use User;
use Title;

/**
 * Produces a ParserOutput object
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
 * Produces a ParserOutput object
 *
 * @ingroup Generator
 */
class ParserOutputGenerator {

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
	 * Generates an object from the page content
	 *
	 * @since 1.9
	 *
	 * @return ParserOutputGenerator
	 */
	public function generate() {

		$this->buildFromText();

		//	if ( class_exists( 'ContentHandler') ) {
		//		$this->buildFromContent();
		//	}

		return $this;
	}

	/**
	 * Generates an object from reparsing the page content
	 *
	 * @since 1.9
	 */
	protected function buildFromText() {
		Profiler::In( __METHOD__ );

		// For now nothing can be done to get rid of this global
		if ( $this->parser === null ) {
			$this->parser = $GLOBALS['wgParser'];
		}

		if ( $this->revision === null ) {
			$this->revision = Revision::newFromTitle( $this->title );
		}

		if ( $this->revision !== null && $this->parserOptions === null ) {
			$this->parserOptions = new ParserOptions( User::newFromId( $this->revision->getUser() ) );
		}

		if ( $this->revision !== null && $this->parserOptions !== null ) {
			$this->parserOutput = $this->parser->parse(
				$this->revision->getText(),
				$this->title,
				$this->parserOptions,
				true,
				true,
				$this->revision->getID()
			);
		} else {
			$this->errors = array( __METHOD__ . " No revision available for {$this->title->getPrefixedDBkey()}" );
		}

		Profiler::Out( __METHOD__ );
	}

}
