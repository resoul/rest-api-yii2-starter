<?php

use Middleware\Framework\Db\Model\Migration;

/**
 * Example application migration.
 *
 * Replace this table with application-specific schema or remove this migration
 * when starting a new project.
 */
class m261008_120000_create_example_record_table extends Migration
{
    protected string $table = '{{%example_record}}';

    public function up(): void
    {
        $this->createTable($this->table, [
            'id' => $this->primaryKey()->comment('ID'),
            'name' => $this->string(255)->notNull()->comment('Name'),
            ...$this->timestampColumns(),
        ], $this->tableOptions);
    }
}
