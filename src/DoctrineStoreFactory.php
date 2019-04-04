<?php

declare( strict_types = 1 );

namespace Wikibase\TermStore;

use Doctrine\DBAL\Connection;
use Wikibase\TermStore\PackagePrivate\Doctrine\DoctrinePropertyTermStore;
use Wikibase\TermStore\PackagePrivate\Doctrine\DoctrineSchemaCreator;
use Wikibase\TermStore\PackagePrivate\Doctrine\Tables;

/**
 * Doctrine implementation of the Abstract Factory TermStoreFactory
 */
class DoctrineStoreFactory implements TermStoreFactory {

	private $connection;

	public function __construct( Connection $connection ) {
		$this->connection = $connection;
	}

	public function install() {
		( new DoctrineSchemaCreator( $this->connection->getSchemaManager() ) )->createSchema();
	}

	/**
	 * CAUTION! This drops all tables part of the term store!
	 */
	public function uninstall() {
		$schema = $this->connection->getSchemaManager();
		$schema->dropTable( Tables::ITEM_TERMS );
		$schema->dropTable( Tables::PROPERTY_TERMS );
		$schema->dropTable( Tables::TERM_IN_LANGUAGE );
		$schema->dropTable( Tables::TEXT_IN_LANGUAGE );
		$schema->dropTable( Tables::TEXT );
	}

	public function newPropertyTermStore(): PropertyTermStore {
		return new DoctrinePropertyTermStore( $this->connection );
	}

}
