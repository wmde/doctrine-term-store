<?php

declare( strict_types = 1 );

namespace Wikibase\TermStore;

interface TermStore {

	public function install();

	/**
	 * CAUTION! This removes all data!
	 */
	public function uninstall();

	public function newPropertyTermStore(): PropertyTermStore;

}
