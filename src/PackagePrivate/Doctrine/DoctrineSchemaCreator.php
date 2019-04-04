<?php

declare( strict_types = 1 );

namespace Wikibase\TermStore\PackagePrivate\Doctrine;

use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;

class DoctrineSchemaCreator {

	private $schemaManager;
	private $tableNames;

	public function __construct( AbstractSchemaManager $schemaManager, TableNames $tableNames ) {
		$this->schemaManager = $schemaManager;
		$this->tableNames = $tableNames;
	}

	public function createSchema() {
		$this->schemaManager->createTable( $this->newItemTermsTable() );
		$this->schemaManager->createTable( $this->newPropertyTermsTable() );
		$this->schemaManager->createTable( $this->newTermInLangTable() );
		$this->schemaManager->createTable( $this->newTextInLangTable() );
		$this->schemaManager->createTable( $this->newTextTable() );
	}

	private function newItemTermsTable(): Table {
		$table = new Table( $this->tableNames->itemTerms() );

		$table->addColumn( 'id', Type::BIGINT, [ 'autoincrement' => true, 'unsigned' => true ] );
		$table->addColumn( 'item_id', Type::INTEGER, [ 'unsigned' => true ] );
		$table->addColumn( 'term_in_lang_id', Type::INTEGER, [ 'unsigned' => true ] );

		$table->setPrimaryKey( [ 'id' ] );
		$table->addIndex( [ 'item_id' ], $this->tableNames->prefix( 'wbt_item_terms_item_id' ) );
		$table->addIndex( [ 'term_in_lang_id' ], $this->tableNames->prefix( 'wbt_item_terms_term_in_lang_id' ) );

		return $table;
	}

	private function newPropertyTermsTable(): Table {
		$table = new Table( $this->tableNames->propertyTerms() );

		$table->addColumn( 'id', Type::INTEGER, [ 'autoincrement' => true, 'unsigned' => true ] );
		$table->addColumn( 'property_id', Type::INTEGER, [ 'unsigned' => true ] );
		$table->addColumn( 'term_in_lang_id', Type::INTEGER, [ 'unsigned' => true ] );

		$table->setPrimaryKey( [ 'id' ] );
		$table->addIndex( [ 'property_id' ], $this->tableNames->prefix( 'wbt_property_terms_property_id' ) );
		$table->addIndex( [ 'term_in_lang_id' ], $this->tableNames->prefix( 'wbt_property_terms_term_in_lang_id' ) );

		return $table;
	}

	private function newTermInLangTable(): Table {
		$table = new Table( $this->tableNames->termInLanguage() );

		$table->addColumn( 'id', Type::INTEGER, [ 'autoincrement' => true, 'unsigned' => true ] );
		$table->addColumn( 'type_id', Type::INTEGER, [ 'unsigned' => true ] );
		$table->addColumn( 'text_in_lang_id', Type::INTEGER, [ 'unsigned' => true ] );

		$table->setPrimaryKey( [ 'id' ] );
		$table->addIndex( [ 'type_id' ], $this->tableNames->prefix( 'wbt_term_in_lang_type_id' ) );
		$table->addIndex( [ 'text_in_lang_id' ], $this->tableNames->prefix( 'wbt_term_in_lang_text_in_lang_id' ) );

		return $table;
	}

	private function newTextInLangTable(): Table {
		$table = new Table( $this->tableNames->textInLanguage() );

		$table->addColumn( 'id', Type::INTEGER, [ 'autoincrement' => true, 'unsigned' => true ] );
		$table->addColumn( 'language', Type::BINARY, [ 'length' => 10 ] );
		$table->addColumn( 'text_id', Type::INTEGER, [ 'unsigned' => true ] );

		$table->setPrimaryKey( [ 'id' ] );
		$table->addIndex( [ 'language' ], $this->tableNames->prefix( 'wbt_text_in_lang_language' ) );
		$table->addIndex( [ 'text_id' ], $this->tableNames->prefix( 'wbt_text_in_lang_text_id' ) );

		return $table;
	}

	private function newTextTable(): Table {
		$table = new Table( $this->tableNames->text() );

		$table->addColumn( 'id', Type::INTEGER, [ 'autoincrement' => true, 'unsigned' => true ] );
		$table->addColumn( 'text', Type::BINARY, [ 'length' => 255 ] );

		$table->setPrimaryKey( [ 'id' ] );
		$table->addUniqueIndex( [ 'text' ], $this->tableNames->prefix( 'wbt_text_text' ) );

		return $table;
	}

}
