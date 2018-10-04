<?php

namespace SMW\Query\Language;

use Exception;
use SMW\DataValueFactory;
use SMW\DIWikiPage;
use SMW\Localizer;

/**
 * Description of a single class as given by a wiki category, or of a
 * disjunction of such classes. Corresponds to (disjunctions of) atomic classes
 * in OWL and to (unions of) classes in RDF.
 *
 * @license GNU GPL v2+
 * @since 1.6
 *
 * @author Markus Krötzsch
 */
class ClassDescription extends Description {

	/**
	 * @var array of DIWikiPage
	 */
	protected $m_diWikiPages;

	/**
	 * @var integer|null
	 */
	protected $hierarchyDepth;

	/**
	 * Constructor.
	 *
	 * @param mixed $content DIWikiPage or array of DIWikiPage
	 *
	 * @throws Exception
	 */
	public function __construct( $content ) {
		if ( $content instanceof DIWikiPage ) {
			$this->m_diWikiPages = [ $content ];
		} elseif ( is_array( $content ) ) {
			$this->m_diWikiPages = $content;
		} else {
			throw new Exception( "ClassDescription::__construct(): parameter must be an DIWikiPage object or an array of such objects." );
		}
	}

	/**
	 * @since 3.0
	 *
	 * @param integer $hierarchyDepth
	 */
	public function setHierarchyDepth( $hierarchyDepth ) {

		if ( $hierarchyDepth > $GLOBALS['smwgQSubcategoryDepth'] ) {
			$hierarchyDepth = $GLOBALS['smwgQSubcategoryDepth'];
		}

		$this->hierarchyDepth = $hierarchyDepth;
	}

	/**
	 * @since 3.0
	 *
	 * @return integer|null
	 */
	public function getHierarchyDepth() {
		return $this->hierarchyDepth;
	}

	/**
	 * @since 3.0
	 *
	 * @param ClassDescription $description
	 *
	 * @return boolean
	 */
	public function isMergableDescription( ClassDescription $description ) {

		if ( isset( $this->isNegation ) && isset( $description->isNegation ) ) {
			return true;
		}

		if ( !isset( $this->isNegation ) && !isset( $description->isNegation ) ) {
			return true;
		}

		return false;
	}

	/**
	 * @since  3.0
	 *
	 * @param DIWikiPage $dataItem
	 */
	public function addClass( DIWikiPage $dataItem ) {
		$this->m_diWikiPages[] = $dataItem;
	}

	/**
	 * @param ClassDescription $description
	 */
	public function addDescription( ClassDescription $description ) {
		$this->m_diWikiPages = array_merge( $this->m_diWikiPages, $description->getCategories() );
	}

	/**
	 * @see Description::getFingerprint
	 * @since 2.5
	 *
	 * @return string
	 */
	public function getFingerprint() {

		$hash = [];

		foreach ( $this->m_diWikiPages as $subject ) {
			$hash[$subject->getHash()] = true;
		}

		ksort( $hash );
		$extra = ( isset( $this->isNegation ) ? '|' . $this->isNegation : '' );

		return 'Cl:' . md5( implode( '|', array_keys( $hash ) ) . $this->hierarchyDepth . $extra );
	}

	/**
	 * @return array of DIWikiPage
	 */
	public function getCategories() {
		return $this->m_diWikiPages;
	}

	public function getQueryString( $asValue = false ) {

		$first = true;
		$namespaceText = Localizer::getInstance()->getNamespaceTextById( NS_CATEGORY );

		foreach ( $this->m_diWikiPages as $wikiPage ) {
			$wikiValue = DataValueFactory::getInstance()->newDataValueByItem( $wikiPage, null );
			if ( $first ) {
				$result = '[[' . $namespaceText . ':' . ( isset( $this->isNegation ) ? '!' : '' ) . $wikiValue->getText();
				$first = false;
			} else {
				$result .= '||' . ( isset( $this->isNegation ) ? '!' : '' ) . $wikiValue->getText();
			}
		}

		if ( $this->hierarchyDepth !== null ) {
			$result .= '|+depth=' . $this->hierarchyDepth;
		}

		$result .= ']]';

		if ( $asValue ) {
			return ' <q>' . $result . '</q> ';
		}

		return $result;
	}

	public function isSingleton() {
		return false;
	}

	public function getSize() {

		if ( $GLOBALS['smwgQSubcategoryDepth'] > 0 ) {
			return 1; // disj. of cats should not cause much effort if we compute cat-hierarchies anyway!
		}

		return count( $this->m_diWikiPages );
	}

	public function getQueryFeatures() {

		if ( count( $this->m_diWikiPages ) > 1 ) {
			return SMW_CATEGORY_QUERY | SMW_DISJUNCTION_QUERY;
		}

		return SMW_CATEGORY_QUERY;
	}

	public function prune( &$maxsize, &$maxdepth, &$log ) {

		if ( $maxsize >= $this->getSize() ) {
			$maxsize = $maxsize - $this->getSize();
			return $this;
		} elseif ( $maxsize <= 0 ) {
			$log[] = $this->getQueryString();
			$result = new ThingDescription();
		} else {
			$result = new ClassDescription( array_slice( $this->m_diWikiPages, 0, $maxsize ) );
			$rest = new ClassDescription( array_slice( $this->m_diWikiPages, $maxsize ) );

			$result->setHierarchyDepth(
				$this->getHierarchyDepth()
			);

			$log[] = $rest->getQueryString();
			$maxsize = 0;
		}

		$result->setPrintRequests( $this->getPrintRequests() );

		return $result;
	}

}
