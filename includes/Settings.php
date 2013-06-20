<?php

namespace SMW;

use ArrayObject;

/**
 * Encapsulate settings in an instantiatable settings class
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
 * @note Initial idea has been borrowed from EducationProgram Extension/Jeroen De Dauw
 *
 * @since 1.9
 *
 * @file
 *
 * @license GNU GPL v2+
 * @author mwjames
 */

/**
 * This class encapsulates settings (mostly retrieved from $GLOBALS) and
 * make it an instantiatable object
 *
 * @ingroup SMW
 */
class Settings {

	/** @var ArrayObject */
	protected $settings;

	/** @var Settings */
	private static $instance = null;

	/**
	 * @note Here we use composition over inheritance but if it necessary
	 * this class can be extended to use the ArrayObject without interrupting
	 * the interface therefore use the factory method for instantiation
	 *
	 * @since 1.9
	 *
	 * @param ArrayObject $settings
	 */
	protected function __construct( ArrayObject $settings ) {
		$this->settings = $settings;
	}

	/**
	 * Assemble individual SMW related settings into one accessible array for
	 * easy instantiation since we don't have unique way of accessing only
	 * SMW related settings ( e.g. $smwgSettings['...']) we need this method
	 * as short cut to invoke only smwg* related settings
	 *
	 * @par Example:
	 * @code
	 *  $settings = \SMW\Settings::newFromGlobals();
	 *
	 *  $settings->get( 'smwgDefaultStore' );
	 * @endcode
	 *
	 * @since 1.9
	 *
	 * @return Settings
	 */
	public static function newFromGlobals() {

		$settings = array(
			'smwgScriptPath' => $GLOBALS['smwgScriptPath'],
			'smwgIP' => $GLOBALS['smwgIP'],
			'smwgDefaultStore' => $GLOBALS['smwgDefaultStore'],
			'smwgSparqlDatabase' => $GLOBALS['smwgSparqlDatabase'],
			'smwgSparqlQueryEndpoint' => $GLOBALS['smwgSparqlQueryEndpoint'],
			'smwgSparqlUpdateEndpoint' => $GLOBALS['smwgSparqlUpdateEndpoint'],
			'smwgSparqlDataEndpoint' => $GLOBALS['smwgSparqlDataEndpoint'],
			'smwgSparqlDefaultGraph' => $GLOBALS['smwgSparqlDefaultGraph'],
			'smwgHistoricTypeNamespace' => $GLOBALS['smwgHistoricTypeNamespace'],
			'smwgNamespaceIndex' => $GLOBALS['smwgNamespaceIndex'],
			'smwgShowFactbox' => $GLOBALS['smwgShowFactbox'],
			'smwgShowFactboxEdit' => $GLOBALS['smwgShowFactboxEdit'],
			'smwgToolboxBrowseLink' => $GLOBALS['smwgToolboxBrowseLink'],
			'smwgInlineErrors' => $GLOBALS['smwgInlineErrors'],
			'smwgUseCategoryHierarchy' => $GLOBALS['smwgUseCategoryHierarchy'],
			'smwgCategoriesAsInstances' => $GLOBALS['smwgCategoriesAsInstances'],
			'smwgLinksInValues' => $GLOBALS['smwgLinksInValues'],
			'smwgDefaultNumRecurringEvents' => $GLOBALS['smwgDefaultNumRecurringEvents'],
			'smwgMaxNumRecurringEvents' => $GLOBALS['smwgMaxNumRecurringEvents'],
			'smwgBrowseShowInverse' => $GLOBALS['smwgBrowseShowInverse'],
			'smwgBrowseShowAll' => $GLOBALS['smwgBrowseShowAll'],
			'smwgSearchByPropertyFuzzy' => $GLOBALS['smwgSearchByPropertyFuzzy'],
			'smwgTypePagingLimit' => $GLOBALS['smwgTypePagingLimit'],
			'smwgConceptPagingLimit' => $GLOBALS['smwgConceptPagingLimit'],
			'smwgPropertyPagingLimit' => $GLOBALS['smwgPropertyPagingLimit'],
			'smwgQEnabled' => $GLOBALS['smwgQEnabled'],
			'smwgQMaxLimit' => $GLOBALS['smwgQMaxLimit'],
			'smwgIgnoreQueryErrors' => $GLOBALS['smwgIgnoreQueryErrors'],
			'smwgQSubcategoryDepth' => $GLOBALS['smwgQSubcategoryDepth'],
			'smwgQEqualitySupport' => $GLOBALS['smwgQEqualitySupport'],
			'smwgQSortingSupport' => $GLOBALS['smwgQSortingSupport'],
			'smwgQRandSortingSupport' => $GLOBALS['smwgQRandSortingSupport'],
			'smwgQDefaultNamespaces' => $GLOBALS['smwgQDefaultNamespaces'],
			'smwgQComparators' => $GLOBALS['smwgQComparators'],
			'smwStrictComparators' => $GLOBALS['smwStrictComparators'],
			'smwgQMaxSize' => $GLOBALS['smwgQMaxSize'],
			'smwgQMaxDepth' => $GLOBALS['smwgQMaxDepth'],
			'smwgQFeatures' => $GLOBALS['smwgQFeatures'],
			'smwgQDefaultLimit' => $GLOBALS['smwgQDefaultLimit'],
			'smwgQMaxInlineLimit' => $GLOBALS['smwgQMaxInlineLimit'],
			'smwgQPrintoutLimit' => $GLOBALS['smwgQPrintoutLimit'],
			'smwgQDefaultLinking' => $GLOBALS['smwgQDefaultLinking'],
			'smwgQConceptCaching' => $GLOBALS['smwgQConceptCaching'],
			'smwgQConceptMaxSize' => $GLOBALS['smwgQConceptMaxSize'],
			'smwgQConceptMaxDepth' => $GLOBALS['smwgQConceptMaxDepth'],
			'smwgQConceptFeatures' => $GLOBALS['smwgQConceptFeatures'],
			'smwgQConceptCacheLifetime' => $GLOBALS['smwgQConceptCacheLifetime'],
			'smwgResultFormats' => $GLOBALS['smwgResultFormats'],
			'smwgResultAliases' => $GLOBALS['smwgResultAliases'],
			'smwgQuerySources' => $GLOBALS['smwgQuerySources'],
			'smwgPDefaultType' => $GLOBALS['smwgPDefaultType'],
			'smwgAllowRecursiveExport' => $GLOBALS['smwgAllowRecursiveExport'],
			'smwgExportBacklinks' => $GLOBALS['smwgExportBacklinks'],
			'smwgMaxNonExpNumber' => $GLOBALS['smwgMaxNonExpNumber'],
			'smwgEnableUpdateJobs' => $GLOBALS['smwgEnableUpdateJobs'],
			'smwgNamespacesWithSemanticLinks' => $GLOBALS['smwgNamespacesWithSemanticLinks'],
			'smwgPageSpecialProperties' => $GLOBALS['smwgPageSpecialProperties'],
			'smwgDeclarationProperties' => $GLOBALS['smwgDeclarationProperties'],
			'smwgTranslate' => $GLOBALS['smwgTranslate'],
			'smwgAdminRefreshStore' => $GLOBALS['smwgAdminRefreshStore'],
			'smwgAutocompleteInSpecialAsk' => $GLOBALS['smwgAutocompleteInSpecialAsk'],
			'smwgAutoRefreshSubject' => $GLOBALS['smwgAutoRefreshSubject'],
			'smwgAutoRefreshOnPurge' => $GLOBALS['smwgAutoRefreshOnPurge'],
			'smwgAutoRefreshOnPageMove' => $GLOBALS['smwgAutoRefreshOnPageMove'],
			'smwgContLang' => $GLOBALS['smwgContLang'],
			'smwgMaxPropertyValues' => $GLOBALS['smwgMaxPropertyValues'],
			'smwgQSubpropertyDepth' => $GLOBALS['smwgQSubpropertyDepth'],
			'smwgNamespace' => $GLOBALS['smwgNamespace'],
			'smwgMasterStore' => $GLOBALS['smwgMasterStore'],
			'smwgIQRunningNumber' => $GLOBALS['smwgIQRunningNumber'],
			'smwgCacheType' => $GLOBALS['smwgCacheType'],
			'smwgCacheUsage' => $GLOBALS['smwgCacheUsage'],
			'smwgStatisticsCache' => $GLOBALS['smwgStatisticsCache'],
			'smwgStatisticsCacheExpiry' => $GLOBALS['smwgStatisticsCacheExpiry'],
			'smwgFixedProperties' => $GLOBALS['smwgFixedProperties'],
		);

		if ( self::$instance === null ) {
			self::$instance = self::newFromArray( $settings ) ;
		}

		return self::$instance;
	}

	/**
	 * Factory method for immediate instantiation of a settings object for a
	 * given array
	 *
	 * @par Example:
	 * @code
	 *  $settings = \SMW\Settings::newFromArray( array( 'Foo' => 'Bar' ) );
	 *
	 *  $settings->get( 'Foo' );
	 * @endcode
	 *
	 * @since 1.9
	 *
	 * @return Settings
	 */
	public static function newFromArray( array $settings ) {
		return new self( new ArrayObject( $settings ) );
	}

	/**
	 * Verifies if a specified setting for a given key does exists or not
	 *
	 * @since 1.9
	 *
	 * @param string $key
	 *
	 * @return boolean
	 */
	public function exists( $key ) {
		return $this->settings->offsetExists( $key );
	}

	/**
	 * Overrides settings for a given key
	 *
	 * @since 1.9
	 *
	 * @param string $key
	 * @param string $value
	 */
	public function set( $key, $value ) {
		$this->settings->offsetSet( $key, $value );
		return $this;
	}

	/**
	 * Returns settings for a given key (nested settings are supported)
	 *
	 * @par Example:
	 * @code
	 *  $settings = \SMW\Settings::newFromArray( array(
	 *   'Foo' => 'Bar'
	 *   'Parent' => array(
	 *     'Child' => array( 'Lisa', 'Lula', array( 'Lila' ) )
	 *   )
	 *  );
	 *
	 *  $settings->get( 'Child' ) will return array( 'Lisa', 'Lula', array( 'Lila' ) )
	 * @endcode
	 *
	 * @since 1.9
	 *
	 * @param string $key
	 *
	 * @return mixed
	 */
	public function get( $key ) {

		if ( !$this->exists( $key ) ) {

			// If the key wasn't found it could be because of a nested array
			// therefore iterate and verify otherwise throw an exception
			$value = $this->doIterate( $key );
			if ( $value !== null ) {
				return $value;
			}

			throw new SettingsArgumentException( "{$key} is not a valid settings key" );
		}

		return $this->settings->offsetGet( $key );
	}

	/**
	 * Reset instance
	 *
	 * @since 1.9
	 */
	public static function reset() {
		self::$instance = null;
	}

	/**
	 * Iterate over nested array to find its value for a given key
	 *
	 * @since 1.9
	 *
	 * @param string $key
	 *
	 * @return mixed|null
	 */
	private function doIterate( $key ) {
		$iterator = new \RecursiveIteratorIterator(
			new \RecursiveArrayIterator( $this->settings ),
			\RecursiveIteratorIterator::CHILD_FIRST
		);

		foreach( $iterator as $it => $value ) {
			if ( $key === $it ) {
				return $value;
			}
		}

		return null;
	}
}
