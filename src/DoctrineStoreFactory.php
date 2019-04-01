<?php

declare( strict_types = 1 );

namespace Wikibase\TermStore;

use Doctrine\DBAL\Connection;
use Wikibase\TermStore\PackagePrivate\Doctrine\DoctrinePropertyTermStore;
use Wikibase\TermStore\PackagePrivate\Doctrine\DoctrineSchemaCreator;

class DoctrineStoreFactory {

	private $connection;

	public function __construct( Connection $connection ) {
		$this->connection = $connection;
	}

	public function createSchema() {
		( new DoctrineSchemaCreator( $this->connection->getSchemaManager() ) )->createSchema();
	}

	public function newPropertyTermStore(): PropertyTermStore {
		return new DoctrinePropertyTermStore();
	}

}