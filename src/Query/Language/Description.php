<?php

namespace SMW\Query\Language;

use SMW\Query\PrintRequest as PrintRequest;

/**
 * Abstract base class for all descriptions
 *
 * @license GNU GPL v2+
 * @since 1.6
 *
 * @author Markus Krötzsch
 */
abstract class Description {

	/**
	 * @var \SMW\Query\PrintRequest[]
	 */
	protected $m_printreqs = array();

	/**
	 * Get the (possibly empty) array of all print requests that
	 * exist for the entities that fit this description.
	 *
	 * @return array of SMW\Query\PrintRequest
	 */
	public function getPrintRequests() {
		return $this->m_printreqs;
	}

	/**
	 * Set the array of print requests completely.
	 *
	 * @param PrintRequest[] $printRequests
	 */
	public function setPrintRequests( array $printRequests ) {
		$this->m_printreqs = $printRequests;
	}

	/**
	 * Add a single SMW\Query\PrintRequest.
	 *
	 * @param \SMW\Query\PrintRequest $printRequest
	 */
	public function addPrintRequest( PrintRequest $printRequest ) {
		$this->m_printreqs[] = $printRequest;
	}

	/**
	 * Add a new print request, but at the beginning of the list of requests
	 * (thus it will be printed first).
	 *
	 * @param PrintRequest $printRequest
	 */
	public function prependPrintRequest( PrintRequest $printRequest ) {
		array_unshift( $this->m_printreqs, $printRequest );
	}

	/**
	 * Return a string expressing this query.
	 * Some descriptions have different syntax in property value positions. The
	 * parameter $asvalue specifies whether the serialisation should take that into
	 * account.
	 *
	 * Example: The SMWValueDescription [[Paris]] returns the single result "Paris"
	 * but can also be used as value in [[has location::Paris]] which is preferred
	 * over the canonical [[has location::\<q\>[[Paris]]\</q\>]].
	 *
	 * The result should be a plain query string that SMW is able to parse,
	 * without any kind of HTML escape sequences.
	 *
	 * @param boolean $asValue
	 *
	 * @return string
	 */
	abstract public function getQueryString( $asValue = false );

	/**
	 * Return true if the description is required to encompass at most a single
	 * result, independently of the knowledge base.
	 *
	 * @return boolean
	 */
	abstract public function isSingleton();

	/**
	 * Compute the size of the description. Default is 1.
	 *
	 * @return integer
	 */
	public function getSize() {
		return 1;
	}

	/**
	 * Compute the depth of the description. Default is 0.
	 *
	 * @return integer
	 */
	public function getDepth() {
		return 0;
	}

	/**
	 * Report on query features used in description. Return values are (sums of)
	 * query feature constants such as SMW_PROPERTY_QUERY.
	 */
	public function getQueryFeatures() {
		return 0;
	}

	/**
	 * Recursively restrict query to a maximal size and depth as given.
	 * Returns a possibly changed description that should be used as a replacement.
	 * Reduce values of parameters to account for the returned descriptions size.
	 * Default implementation for non-nested descriptions of size 1.
	 * The parameter $log contains a list of all pruned conditions, updated when some
	 * description was reduced.
	 *
	 * @note Objects must not do changes on $this during pruning, since $this can be
	 * reused in multiple places of one or many queries. Make new objects to reflect
	 * changes!
	 */
	public function prune( &$maxsize, &$maxDepth, &$log ) {

		if ( ( $maxsize < $this->getSize() ) || ( $maxDepth < $this->getDepth() ) ) {
			$log[] = $this->getQueryString();

			$result = new ThingDescription();
			$result->setPrintRequests( $this->getPrintRequests() );

			return $result;
		}

		$maxsize = $maxsize - $this->getSize();
		$maxDepth = $maxDepth - $this->getDepth();

		return $this;
	}

}
