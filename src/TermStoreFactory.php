<?php

declare( strict_types = 1 );

namespace Wikibase\TermStore;

interface TermStoreFactory {

	public function createSchema();

	public function newPropertyTermStore(): PropertyTermStore;

}
