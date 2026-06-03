<?php

namespace SMW\MediaWiki\Specials;

use MediaWiki\HookContainer\HookContainer;
use MediaWiki\SpecialPage\SpecialPage;
use PermissionsError;
use SMW\Localizer\Message;
use SMW\MediaWiki\JobFactory;
use SMW\MediaWiki\JobQueue;
use SMW\MediaWiki\Specials\Admin\OutputFormatter;
use SMW\MediaWiki\Specials\Admin\TaskHandler;
use SMW\MediaWiki\Specials\Admin\TaskHandlerFactory;
use SMW\MediaWiki\Specials\Admin\TaskHandlerRegistry;
use SMW\Services\ServicesFactory as ApplicationFactory;
use SMW\Settings;
use SMW\SQLStore\SQLStore;
use SMW\Store;
use SMW\Utils\HtmlTabs;

/**
 * This special page for MediaWiki provides an administrative interface
 * that allows to execute certain functions related to the maintenance
 * of the semantic database.
 *
 * Access to the special page and its function is limited to users with the
 * `smw-admin` right.
 *
 * @license GPL-2.0-or-later
 * @since   2.5
 *
 * @author mwjames
 * @author Markus Krötzsch
 */
class SpecialAdmin extends SpecialPage {

	/**
	 * @since 7.0.0
	 */
	public function __construct(
		private readonly Store $store,
		private readonly Settings $settings,
		private readonly HookContainer $hookContainer,
		private readonly JobFactory $jobFactory,
		private readonly JobQueue $jobQueue
	) {
		// MediaWiki 1.46 deprecated passing the restriction through the
		// SpecialPage constructor in favour of overriding getRestriction().
		// Versions before 1.46 enforce the restriction via the
		// constructor-set property, so keep passing it there for them.
		if ( version_compare( MW_VERSION, '1.46', '<' ) ) {
			parent::__construct( 'SMWAdmin', 'smw-admin' );
		} else {
			parent::__construct( 'SMWAdmin' );
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public function getRestriction(): string {
		return 'smw-admin';
	}

	/**
	 * {@inheritDoc}
	 */
	public function doesWrites(): bool {
		return true;
	}

	/**
	 * @see SpecialPage::execute
	 */
	public function execute( $query ) {
		if ( !$this->userCanExecute( $this->getUser() ) ) {
			throw new PermissionsError( 'smw-admin', [ 'smw-admin-permission-missing' ] );
		}

		// https://phabricator.wikimedia.org/T109652#1562641
		$this->getRequest()->setVal(
			'wpEditToken',
			$this->getUser()->getEditToken()
		);

		$this->setHeaders();
		$output = $this->getOutput();

		$output->setPageTitle( $this->msg_text( 'smw-title' ) );
		$output->addHelpLink( $this->msg_text( 'smw-admin-helplink' ), true );

		$output->addModuleStyles( [
			'ext.smw.styles',
			'ext.smw.special.styles',
			'mediawiki.codex.messagebox.styles'
		] );
		$output->addModules( 'ext.smw.admin' );

		// Partial DI: MwCollaboratorFactory is still resolved through
		// ApplicationFactory because it is not registered as a global SMW.X
		// service.
		$mwCollaboratorFactory = ApplicationFactory::getInstance()->newMwCollaboratorFactory();

		$htmlFormRenderer = $mwCollaboratorFactory->newHtmlFormRenderer(
			$this->getContext()->getTitle(),
			$this->getLanguage()
		);

		// Some functions require methods only provided by the SQLStore (or any
		// inherit class thereof). When the injected default Store is not an
		// SQLStore (e.g. SPARQLStore) the admin tasks need a separately-built
		// SQL store; resolving it through ApplicationFactory mirrors the
		// partial-DI pattern used by SpecialPropertyLabelSimilarity.
		$store = $this->store instanceof SQLStore
			? $this->store
			: ApplicationFactory::getInstance()->getStore( SQLStore::class );

		$outputFormatter = new OutputFormatter(
			$this->getOutput()
		);

		$adminFeatures = $this->settings->get( 'smwgAdminFeatures' );

		// Disable the feature in case the function is not supported
		if ( $this->settings->get( 'smwgEnabledFulltextSearch' ) === false ) {
			$adminFeatures &= ~SMW_ADM_FULLT;
		}

		$taskHandlerFactory = new TaskHandlerFactory(
			$store,
			$htmlFormRenderer,
			$outputFormatter,
			$this->jobFactory,
			$this->jobQueue
		);

		$taskHandlerFactory->setHookContainer(
			$this->hookContainer
		);

		$taskHandlerRegistry = $taskHandlerFactory->newTaskHandlerRegistry(
			$this->getUser(),
			$adminFeatures
		);

		if ( $query !== null ) {
			$this->getRequest()->setVal( 'action', $query );
		}

		$action = $this->getRequest()->getText( 'action' );

		foreach ( $taskHandlerRegistry->get( TaskHandler::ACTIONABLE ) as $taskHandler ) {
			if ( $taskHandler->isTaskFor( $action ) ) {
				return $taskHandler->handleRequest( $this->getRequest() );
			}
		}

		$output->addHTML(
			$this->buildHTML( $taskHandlerRegistry )
		);
	}

	/**
	 * {@inheritDoc}
	 */
	protected function getGroupName(): string {
		return 'smw_group';
	}

	private function buildHTML( TaskHandlerRegistry $taskHandlerRegistry ): string {
		$maintenanceSection = '';

		foreach ( $taskHandlerRegistry->get( TaskHandler::SECTION_MAINTENANCE ) as $maintenanceTask ) {
			$maintenanceSection .= $maintenanceTask->getHtml();
		}

		$supplementarySection = '';

		foreach ( $taskHandlerRegistry->get( TaskHandler::SECTION_SUPPLEMENT ) as $supplementaryTask ) {
			$supplementarySection .= $supplementaryTask->getHtml();
		}

		$alertsSection = '';

		foreach ( $taskHandlerRegistry->get( TaskHandler::SECTION_ALERTS ) as $alertTask ) {
			$alertsSection .= $alertTask->getHtml();
		}

		$htmlTabs = new HtmlTabs();

		$supportTaskList = $taskHandlerRegistry->get( TaskHandler::SECTION_SUPPORT );
		$supportTask = end( $supportTaskList );

		$default = $alertsSection === '' ? ( $supportTask->hasFeature( SMW_ADM_SHOW_OVERVIEW ) ? 'general' : 'maintenance' ) : 'alerts';

		// If we want to remain on a specific tab on a GET request, use the `tab`
		// parameter since we are unable to fetch any #href hash from a request
		$htmlTabs->setActiveTab(
			$this->getRequest()->getVal( 'tab', $default )
		);

		$htmlTabs->tab(
			'alerts',
			'<span class="smw-icon-alert smw-tab-icon skin-invert"></span>' . $this->msg_text( 'smw-admin-tab-alerts' ),
			[
				'hide'  => $alertsSection === '',
				'class' => 'smw-tab-warning'
			]
		);

		$supportSection = '';
		if ( $supportTask->hasFeature( SMW_ADM_SHOW_OVERVIEW ) ) {
			$supportSection = $supportTask->getHtml();
			$htmlTabs->tab( 'general', $this->msg_text( 'smw-admin-tab-general' ) );
		}

		$htmlTabs->tab( 'maintenance', $this->msg_text( 'smw-admin-tab-maintenance' ) );
		$htmlTabs->tab( 'supplement', $this->msg_text( 'smw-admin-tab-supplement' ) );

		if ( $supportTask->hasFeature( SMW_ADM_SHOW_OVERVIEW ) ) {
			$htmlTabs->content( 'general', $supportSection );
		}

		$htmlTabs->content( 'alerts', $alertsSection );
		$htmlTabs->content( 'maintenance', $maintenanceSection );
		$htmlTabs->content( 'supplement', $supplementarySection );

		$html = $htmlTabs->buildHTML(
			[ 'class' => 'smw-admin' ]
		);

		return $html;
	}

	private function msg_text( string $key, $type = Message::TEXT ): string {
		return Message::get( $key, $type, Message::USER_LANGUAGE );
	}

}
