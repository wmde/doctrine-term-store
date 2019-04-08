<?php

declare( strict_types = 1 );

namespace Wikibase\TermStore;

use Onoi\MessageReporter\MessageReporter;

interface TermStore {

	public function install( MessageReporter $reporter = null );

	/**
	 * CAUTION! This removes all data!
	 */
	public function uninstall( MessageReporter $reporter = null );

	public function newPropertyTermStore(): PropertyTermStore;

}
