<?php
/**
 * @author Markus Krötzsch
 *
 * An abstract query page base class that supports array-based
 * data retrieval instead of the SQL-based access used by MW.
 * @file
 * @ingroup SMW
 */

/**
 * Abstract base class for SMW's variant of the MW QueryPage.
 * Subclasses must implement getResults() and formatResult(), as
 * well as some other standard functions of QueryPage.
 * @ingroup SMW
 */
abstract class SMWQueryPage extends QueryPage {

	/** @var MessageFormatter */
	protected $msgFormatter;

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

		if ( $this->getRequest()->getCheck( 'property' ) ) {
			$parameters['property'] = $this->getRequest()->getVal( 'property' );
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
			$this->msgFormatter = new \SMW\MessageFormatter( $this->getLanguage() );
		}
		return $this->msgFormatter;
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
	public function getSearchForm( $property = '' ) {
		return	Xml::tags( 'form', array( 'method' => 'get', 'action' => htmlspecialchars( $GLOBALS['wgScript'] ) ),
				Html::hidden( 'title', $this->getContext()->getTitle()->getPrefixedText() ) .
				Xml::fieldset( $this->msg( 'properties' )->text(),
					Xml::inputLabel( $this->msg( 'smw-sp-property-searchform' )->text(), 'property', 'property', 20, $property ) . ' ' .
					Xml::submitButton( $this->msg( 'allpagessubmit' )->text() ) ) );
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
			$options->addStringCondition( str_replace( ' ', '_', $property ), SMWStringCondition::STRCOND_MID );
		}

		$res = $this->getResults( $options );
		$num = count( $res );

		$out->addHTML( $this->getPageHeader() );

		// if list is empty, show it
		if ( $num == 0 ) {
			$out->addHTML( '<p>' . $this->msg( 'specialpage-empty' )->escaped() . '</p>' );
			return;
		}

		$top = wfShowingResults( $offset, $num );
		$out->addHTML( "<p>{$top}\n" );

		// often disable 'next' link when we reach the end
		$atend = $num < $limit;
		$sl = $this->getLanguage()->viewPrevNext(
			$this->getTitleFor( $this->getName() ),
			$offset,
			$limit,
			$this->linkParameters(),
			$atend
		);

		$out->addHTML( "<br />{$sl}</p>\n" );

		if ( $num > 0 ) {
			$s = array();
			if ( ! $this->listoutput )
				$s[] = $this->openList( $offset );

			foreach ( $res as $r ) {
				$format = $this->formatResult( $sk, $r );
				if ( $format ) {
					$s[] = $this->listoutput ? $format : "<li>{$format}</li>\n";
				}
			}

			if ( ! $this->listoutput )
				$s[] = $this->closeList();
			$str = $this->listoutput ? $this->getLanguage()->listToText( $s ) : implode( '', $s );
			$out->addHTML( $str );
		}

		$out->addHTML( "<p>{$sl}</p>\n" );

		return $num;
	}
}
