<?php

declare( strict_types = 1 );

namespace Wikibase\TermStore\PackagePrivate\Doctrine;

use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;

class DoctrineSchemaCreator {

	private $schemaManager;

	public function __construct( AbstractSchemaManager $schemaManager ) {
		$this->schemaManager = $schemaManager;
	}

	public function createSchema() {
		$this->schemaManager->createTable( $this->newItemTermsTable() );
	}

	private function newItemTermsTable(): Table {
		$table = new Table( 'wbt_item_terms' );

		$table->addColumn( 'id', Type::BIGINT );
//		$table->addColumn( 'item_id', Type::INTEGER );
//		$table->addColumn( 'term_in_lang_id', Type::INTEGER );

		return $table;
	}
}
