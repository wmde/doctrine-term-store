<?php

declare( strict_types = 1 );

namespace Wikibase\TermStore;

use Doctrine\DBAL\Connection;
use Onoi\MessageReporter\MessageReporter;
use Onoi\MessageReporter\NullMessageReporter;
use Wikibase\TermStore\PackagePrivate\Doctrine\DoctrinePropertyTermStore;
use Wikibase\TermStore\PackagePrivate\Doctrine\DoctrineSchemaCreator;
use Wikibase\TermStore\PackagePrivate\Doctrine\TableNames;

/**
 * Doctrine implementation of the Abstract Factory TermStoreFactory
 */
class DoctrineTermStore implements TermStore {

	private $connection;
	private $tableNames;

	public function __construct( Connection $connection, $tableNamePrefix ) {
		$this->connection = $connection;
		$this->tableNames = new TableNames( $tableNamePrefix );
	}

	public function install( MessageReporter $reporter = null ) {
		( new DoctrineSchemaCreator(
			$this->connection->getSchemaManager(),
			$this->tableNames,
			$reporter === null ? new NullMessageReporter() : $reporter
		) )->createSchema();
	}

	/**
	 * CAUTION! This drops all tables part of the term store!
	 */
	public function uninstall() {
		$schema = $this->connection->getSchemaManager();
		$schema->dropTable( $this->tableNames->itemTerms() );
		$schema->dropTable( $this->tableNames->propertyTerms() );
		$schema->dropTable( $this->tableNames->termInLanguage() );
		$schema->dropTable( $this->tableNames->textInLanguage() );
		$schema->dropTable( $this->tableNames->text() );
	}

	public function newPropertyTermStore(): PropertyTermStore {
		return new DoctrinePropertyTermStore( $this->connection, $this->tableNames );
	}

}
