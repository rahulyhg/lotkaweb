<?php


use Phinx\Migration\AbstractMigration;

class CreateMediaTable extends AbstractMigration
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
        $table = $this->table('media');
        $table
          ->addColumn('name', 'string', array('limit' => 255))
          ->addColumn('description', 'text')
          ->addColumn('filename', 'string', array('limit' => 255))
          ->addColumn('visible_to', 'text')
          ->addColumn('publish_at', 'datetime', array('null' => true))
          ->addColumn('unpublish_at', 'datetime', array('null' => true))
          ->addColumn('notes', 'text')
          ->addColumn('created_at', 'datetime', array('default' => '0000-00-00 00:00:00'))
          ->addColumn('updated_at', 'datetime', array('default' => '0000-00-00 00:00:00'))
          ->create();
      
        $table = $this->table('media_attribute');
        $table
          ->addColumn('media_id', 'integer', array('limit' => 10))
          ->addColumn('attribute_id', 'integer', array('limit' => 10))
          ->addColumn('created_at', 'datetime', array('default' => '0000-00-00 00:00:00'))
          ->addColumn('updated_at', 'datetime', array('default' => '0000-00-00 00:00:00'))
          ->create();      
    }
}
