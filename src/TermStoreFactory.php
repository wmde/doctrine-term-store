<?php

declare( strict_types = 1 );

namespace Wikibase\TermStore;

interface TermStoreFactory {

	public function install();

	public function uninstall();

	public function newPropertyTermStore(): PropertyTermStore;

}
