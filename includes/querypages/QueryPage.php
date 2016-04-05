<?php

namespace SMW;

use Html;
use SMWRequestOptions;
use SMWStringCondition;
use Xml;

/**
 * An abstract query page base class that supports array-based
 * data retrieval instead of the SQL-based access used by MW.
 *
 *
 * @license GNU GPL v2+
 * @since   ??
 *
 * @author Markus Krötzsch
 */

/**
 * Abstract base class for SMW's variant of the MW QueryPage.
 * Subclasses must implement getResults() and formatResult(), as
 * well as some other standard functions of QueryPage.
 *
 * @ingroup SMW
 * @ingroup QueryPage
 */
abstract class QueryPage extends \QueryPage {

	/** @var MessageFormatter */
	protected $msgFormatter;

	/** @var Linker */
	protected $linker = null;

	/** @var array */
	protected $selectOptions = array();

	/** @var array */
	protected $useSerchForm = false;

	/**
	 * Implemented by subclasses to provide concrete functions.
	 */
	abstract function getResults( $requestoptions );

	/**
	 * Clear the cache and save new results
	 * @todo Implement caching for SMW query pages
	 */
	function recache( $limit, $ignoreErrors = true ) {
		/// TODO
	}

	function isExpensive() {
		return false; // Disables caching for now
	}

	function isSyndicated() {
		return false; // TODO: why not?
	}

	/**
	 * @see QueryPage::linkParameters
	 *
	 * @since 1.9
	 *
	 * @return array
	 */
	public function linkParameters() {

		$parameters = array();
		$property   = $this->getRequest()->getVal( 'property' );

		if ( $property !== null && $property !== '' ) {
			$parameters['property'] = $property;
		}

		return $parameters;
	}

	/**
	 * Returns a MessageFormatter object
	 *
	 * @since  1.9
	 *
	 * @return MessageFormatter
	 */
	public function getMessageFormatter() {

		if ( !isset( $this->msgFormatter ) ) {
			$this->msgFormatter = new MessageFormatter( $this->getLanguage() );
		}

		return $this->msgFormatter;
	}

	/**
	 * Returns a Linker object
	 *
	 * @since  1.9
	 *
	 * @return Linker
	 */
	public function getLinker() {

		if ( $this->linker === null ) {
			$this->linker = smwfGetLinker();
		}

		return $this->linker;
	}

	/**
	 * Generates a search form
	 *
	 * @since 1.9
	 *
	 * @param string $property
	 *
	 * @return string
	 */
	public function getSearchForm( $property = '', $cacheDate = '' ) {

		$this->useSerchForm = true;
		$this->getOutput()->addModules( 'ext.smw.property' );

		// No need to verify $this->selectOptions because its values are set
		// during doQuery() which is processed before this form is generated
		$resultCount = wfShowingResults( $this->selectOptions['offset'], $this->selectOptions['count'] );

		$selection = $this->getLanguage()->viewPrevNext(
			$this->getContext()->getTitle(),
			$this->selectOptions['offset'],
			$this->selectOptions['limit'],
			$this->linkParameters(),
			$this->selectOptions['end']
		);

		return Xml::tags( 'form', array(
			'method' => 'get',
			'action' => htmlspecialchars( $GLOBALS['wgScript'] )
		), Html::hidden( 'title', $this->getContext()->getTitle()->getPrefixedText() ) .
			Xml::fieldset( $this->msg( 'properties' )->text(),
				Xml::tags( 'p', array(), $resultCount ) .
				Xml::tags( 'p', array(), $selection ) .
				Xml::tags( 'p', array(), $cacheDate ) .
				Xml::tags( 'hr', array( 'style' => 'margin-bottom:10px;' ), '' ) .
				Xml::inputLabel( $this->msg( 'smw-sp-property-searchform' )->text(), 'property', 'smw-property-input', 20, $property ) . ' ' .
				Xml::submitButton( $this->msg( 'allpagessubmit' )->text() )
			)
		);
	}

	/**
	 * This is the actual workhorse. It does everything needed to make a
	 * real, honest-to-gosh query page.
	 * Alas, we need to overwrite the whole beast since we do not assume
	 * an SQL-based storage backend.
	 *
	 * @param $offset database query offset
	 * @param $limit database query limit
	 * @param $property database string query
	 */
	function doQuery( $offset = false, $limit = false, $property = false ) {
		$out  = $this->getOutput();
		$sk   = $this->getSkin();

		$options = new SMWRequestOptions();
		$options->limit = $limit;
		$options->offset = $offset;
		$options->sort = true;

		if ( $property ) {
			$options->addStringCondition( $property, SMWStringCondition::STRCOND_MID );
		}

		$res = $this->getResults( $options );
		$num = count( $res );

		// often disable 'next' link when we reach the end
		$atend = $num < $limit;

		$this->selectOptions = array(
			'offset' => $offset,
			'limit'  => $limit,
			'end'    => $atend,
			'count'  => $num
		);

		$out->addHTML( $this->getPageHeader() );

		// if list is empty, show it
		if ( $num == 0 ) {
			$out->addHTML( '<p>' . $this->msg( 'specialpage-empty' )->escaped() . '</p>' );
			return;
		}

		// If unused properties and wanted properties are using the searchForm
		// then the useSerchForm if-inclusion can be scrapped
		if ( !$this->useSerchForm ) {

			$top = wfShowingResults( $offset, $num );

			$sl = $this->getLanguage()->viewPrevNext(
				$this->getTitleFor( $this->getName() ),
				$this->selectOptions['offset'],
				$this->selectOptions['limit'],
				$this->linkParameters(),
				$this->selectOptions['end']
			);

			$out->addHTML( "<p>{$top}\n" );
			$out->addHTML( "<br />{$sl}</p>\n" );
		}

		if ( $num > 0 ) {
			$s = array();
			if ( ! $this->listoutput ) {
				$s[] = $this->openList( $offset );
			}

			foreach ( $res as $r ) {
				$format = $this->formatResult( $sk, $r );
				if ( $format ) {
					$s[] = $this->listoutput ? $format : "<li>{$format}</li>\n";
				}
			}

			if ( ! $this->listoutput ) {
				$s[] = $this->closeList();
			}
			$str = $this->listoutput ? $this->getLanguage()->listToText( $s ) : implode( '', $s );
			$out->addHTML( $str );
		}

		if ( !$this->useSerchForm ) {
			$out->addHTML( "<p>{$sl}</p>\n" );
		}

		return $num;
	}
}
