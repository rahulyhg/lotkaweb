<?php


use Phinx\Migration\AbstractMigration;

class CreateListElementsForCharacters extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-abstractmigration-class
     *
     * The following commands can be used in this method and Phinx will
     * automatically reverse them when rolling back:
     *
     *    createTable
     *    renameTable
     *    addColumn
     *    renameColumn
     *    addIndex
     *    addForeignKey
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function change()
    {
        $table = $this->table('list');
        $table
          ->addColumn('name', 'string', array('limit' => 255))
          ->addColumn('description', 'text')
          ->addColumn('character_id', 'integer', array('limit' => 10))
          ->addColumn('created_at', 'datetime', array('default' => '0000-00-00 00:00:00'))
          ->addColumn('updated_at', 'datetime', array('default' => '0000-00-00 00:00:00'))
          ->create();
      
        $table = $this->table('list_item');
        $table
          ->addColumn('name', 'string', array('limit' => 255))
          ->addColumn('type', 'string', array('limit' => 255, 'null' => true))
          ->addColumn('description', 'text')
          ->addColumn('list_id', 'integer', array('limit' => 10))
          ->addColumn('created_at', 'datetime', array('default' => '0000-00-00 00:00:00'))
          ->addColumn('updated_at', 'datetime', array('default' => '0000-00-00 00:00:00'))
          ->create();

        $table = $this->table('list_attribute');
        $table
          ->addColumn('list_id', 'integer', array('limit' => 10))
          ->addColumn('taxon_id', 'integer', array('limit' => 10))
          ->addColumn('attribute_id', 'integer', array('limit' => 10))
          ->addColumn('created_at', 'datetime', array('default' => '0000-00-00 00:00:00'))
          ->addColumn('updated_at', 'datetime', array('default' => '0000-00-00 00:00:00'))
          ->create();
      
        $table = $this->table('item_attribute');
        $table
          ->addColumn('item_id', 'integer', array('limit' => 10))
          ->addColumn('attribute_id', 'integer', array('limit' => 10))
          ->addColumn('created_at', 'datetime', array('default' => '0000-00-00 00:00:00'))
          ->addColumn('updated_at', 'datetime', array('default' => '0000-00-00 00:00:00'))
          ->create();
      
        $table = $this->table('taxons');
        $table
          ->addColumn('name', 'string', array('limit' => 255))
          ->addColumn('code', 'string', array('limit' => 128))
          ->addColumn('taxon_id', 'integer', array('limit' => 10))
          ->addColumn('created_at', 'datetime', array('default' => '0000-00-00 00:00:00'))
          ->addColumn('updated_at', 'datetime', array('default' => '0000-00-00 00:00:00'))
          ->create();
    }
}
