<?php

namespace SMW\Indicator\EntityExaminerIndicators;

use MediaWiki\Html\TemplateParser;
use SMW\Constraint\ConstraintError;
use SMW\DataItems\WikiPage;
use SMW\EntityCache;
use SMW\Indicator\IndicatorProviders\TypableSeverityIndicatorProvider;
use SMW\Localizer\Message;
use SMW\Localizer\MessageLocalizerTrait;
use SMW\RequestOptions;
use SMW\Store;

/**
 * @license GPL-2.0-or-later
 * @since 3.2
 *
 * @author mwjames
 */
class ConstraintErrorEntityExaminerIndicatorProvider implements TypableSeverityIndicatorProvider {

	use MessageLocalizerTrait;

	const LOOKUP_LIMIT = 20;

	private bool $checkConstraintErrors = true;

	protected array $indicators = [];

	private string $severityType = '';

	private mixed $languageCode = '';

	private string $errorTitle;

	/**
	 * @since 3.2
	 */
	public function __construct(
		private Store $store,
		private EntityCache $entityCache,
		private TemplateParser $templateParser,
	) {
	}

	/**
	 * @since 3.2
	 */
	public function isSeverityType( string $severityType ): bool {
		return $this->severityType === $severityType;
	}

	/**
	 * @since 3.2
	 */
	public function getName(): string {
		return 'smw-entity-examiner-deferred-constraint-error';
	}

	/**
	 * @since 3.2
	 */
	public function setConstraintErrorCheck( mixed $checkConstraintErrors ): void {
		$this->checkConstraintErrors = (bool)$checkConstraintErrors;
	}

	/**
	 * @since 3.2
	 */
	public function hasIndicator( WikiPage $subject, array $options ): bool {
		if ( $this->checkConstraintErrors ) {
			$this->checkConstraintErrors( $subject, $options );
		}

		return $this->indicators !== [];
	}

	/**
	 * @since 3.2
	 */
	public function getIndicators(): array {
		return $this->indicators;
	}

	/**
	 * @since 3.2
	 */
	public function getModules(): array {
		return [];
	}

	/**
	 * @since 3.2
	 */
	public function getInlineStyle(): string {
		// The standard helplink interferes with the alignment (due to a text
		// component) therefore disabled it when indicators are present
		return '#mw-indicator-mw-helplink {display:none;}';
	}

	protected function checkConstraintErrors( $subject, array $options ): void {
		$this->runCheck( $subject, $options );
	}

	protected function runCheck( $subject, array $options ) {
		$this->languageCode = $options['uselang'] ?? Message::USER_LANGUAGE;

		$errors = $this->findErrors( $subject );
		$top = '';

		if ( $errors === [] ) {
			$this->indicators = [
				'id'      => $this->getName(),
				'content' => '',
			];
			return $this->indicators;
		}

		$this->errorTitle = 'smw-constraint-error';
		$this->severityType = TypableSeverityIndicatorProvider::SEVERITY_WARNING;

		$margin = isset( $options['dir'] ) && $options['dir'] === 'rtl' ? 'right' : 'left';

		if ( count( $errors ) >= self::LOOKUP_LIMIT ) {
			$top = $this->msg( [ 'smw-constraint-error-limit', self::LOOKUP_LIMIT ], Message::TEXT, $this->languageCode );
			$top .= $this->templateParser->processTemplate(
				'ConstraintTopLine',
				[
					'margin' => $margin
				]
			);

			$top = $this->templateParser->processTemplate(
				'ConstraintStickyTop',
				[
					'html-content' => $top
				]
			);
			$content = '<div><ul><li>' . implode( '</li><li>', $errors ) . '</li></ul></div>';
		} else {
			$content = '<div style="padding-top:10px;"><ul><li>' . implode( '</li><li>', $errors ) . '</li></ul></div>';
		}

		$bottom = $this->templateParser->processTemplate(
			'Line',
			[
				'margin' => $margin
			]
		);
		$bottom .= $this->templateParser->processTemplate(
			'Comment',
			[
				'html-comment' => $this->msg( 'smw-constraint-error-suggestions', Message::TEXT, $this->languageCode )
			]
		);

		$bottomMarker = $this->templateParser->processTemplate(
			'BottomMarker',
			[
				'margin' => $margin,
				'label' => 'constraint',
				'data-background-color' => '#00BCD4',
				'color' => '#ffffff'
			]
		);

		if ( count( $errors ) >= 3 ) {
			$bottom .= $this->templateParser->processTemplate(
				'BottomSticky',
				[
					'html-content' => $bottomMarker
				]
			);
		} else {
			$bottom .= $bottomMarker;
		}

		$title = $this->msg(
			[ 'smw-indicator-constraint-violation', count( $errors ) ],
			Message::TEXT,
			$this->languageCode
		);

		$this->indicators = [
			'id'      => $this->getName(),
			'title'   => $title,
			'content' => $top . $content . $bottom,
		];
	}

	private function findErrors( $subject ): array {
		$key = $this->entityCache->makeKey( $subject, 'constraint-error' );

		$errors = $this->entityCache->fetch( $key );
		if ( $errors !== false ) {
			return $this->decodeErrors( $errors );
		}

		$errorLookup = $this->store->service( 'ErrorLookup' );

		$requestOptions = new RequestOptions();
		$requestOptions->setCaller( __METHOD__ );
		$requestOptions->setOption( 'checkConstraintErrors', $this->checkConstraintErrors );
		$requestOptions->setLimit( self::LOOKUP_LIMIT );

		$res = $errorLookup->findErrorsByType(
			ConstraintError::ERROR_TYPE,
			$subject,
			$requestOptions
		);

		$errors = $errorLookup->buildArray( $res );

		// Store `null` as string to have the cache return something and not
		// interpret an empty [] as `false`
		if ( $errors === [] ) {
			$errors = 'null';
		}

		$this->entityCache->save( $key, $errors, EntityCache::TTL_WEEK );

		// Being an associate member means once the subject is invalidated (during
		// save, delete etc.), the current cache entry is evicted as well.
		$this->entityCache->associate( $subject, $key );

		return $this->decodeErrors( $errors );
	}

	private function decodeErrors( $errors ): array {
		if ( $errors === 'null' ) {
			return [];
		}

		$messages = [];

		foreach ( (array)$errors as $error ) {
			$messages[] = Message::decode( $error, Message::PARSE, $this->languageCode );
		}

		return $messages;
	}

}
