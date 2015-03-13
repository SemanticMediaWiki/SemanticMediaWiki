<?php

namespace SMW;

use InvalidArgumentException;

/**
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 */
class ControlledVocabularyContentMapper {

	/**
	 * @var string
	 */
	private $uri = '';

	/**
	 * @var string
	 */
	private $name = '';

	/**
	 * @var array
	 */
	private $list = array();

	/**
	 * @since 2.2
	 *
	 * @return string
	 */
	public function getUri() {
		return $this->uri;
	}

	/**
	 * @since 2.2
	 *
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * @since 2.2
	 *
	 * @return string
	 */
	public function getList() {
		return $this->list;
	}

	/**
	 * @since 2.2
	 *
	 * @param string $term
	 *
	 * @return string
	 */
	public function getTypeForTerm( $term ) {

		if ( isset( $this->list[$term] ) ) {
			return $this->list[$term];
		}

		return '';
	}

	/**
	 * @since 2.2
	 *
	 * @param string $content
	 */
	public function parse( $content ) {

		if ( $content === '' ) {
			return;
		}

		$importDefintions = preg_split( "([\n][\s]?)", $content );

		// Get definition from first line
		$fristLine = array_shift( $importDefintions );
		if ( strpos( $fristLine, '|' ) === false ) {
			return;
		}

		list( $this->uri, $this->name ) = explode( '|', $fristLine, 2 );

		// tolerate initial space
		if ( $this->uri[0] == ' ' ) {
			$this->uri = mb_substr( $this->uri, 1 );
		}

		foreach ( $importDefintions as $importDefintion ) {

			if ( strpos( $importDefintion, '|') === false ) {
				continue;
			}

			list( $secname, $typestring ) = explode( '|', $importDefintion, 2 );
			$this->list[str_replace( '_', ' ', trim( $secname ) )] = $typestring;
		}
	}

}
