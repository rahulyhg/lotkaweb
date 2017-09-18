<?php


use Phinx\Migration\AbstractMigration;

class CreateParticipantPageSchemas extends AbstractMigration
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
        $table = $this->table('attributes');
        $table
          ->addColumn('name', 'string', array('limit' => 255))
          ->addColumn('value', 'string', array('limit' => 255, 'null' => true))
          ->addColumn('created_at', 'datetime', array('default' => '0000-00-00 00:00:00'))
          ->addColumn('updated_at', 'datetime', array('default' => '0000-00-00 00:00:00'))
          ->create();
      
        $table = $this->table('characters');
        $table
          ->addColumn('name', 'string', array('limit' => 255))
          ->addColumn('description', 'text')
          ->addColumn('created_at', 'datetime', array('default' => '0000-00-00 00:00:00'))
          ->addColumn('updated_at', 'datetime', array('default' => '0000-00-00 00:00:00'))
          ->create();
      
        $table = $this->table('groups');
        $table
          ->addColumn('name', 'string', array('limit' => 255))
          ->addColumn('description', 'text')
          ->addColumn('created_at', 'datetime', array('default' => '0000-00-00 00:00:00'))
          ->addColumn('updated_at', 'datetime', array('default' => '0000-00-00 00:00:00'))
          ->create();
      
        $table = $this->table('plots');
        $table
          ->addColumn('name', 'string', array('limit' => 255))
          ->addColumn('description', 'text')
          ->addColumn('created_at', 'datetime', array('default' => '0000-00-00 00:00:00'))
          ->addColumn('updated_at', 'datetime', array('default' => '0000-00-00 00:00:00'))
          ->create();
      
        $table = $this->table('relations');
        $table
          ->addColumn('name', 'string', array('limit' => 255))
          ->addColumn('description', 'text')
          ->addColumn('created_at', 'datetime', array('default' => '0000-00-00 00:00:00'))
          ->addColumn('updated_at', 'datetime', array('default' => '0000-00-00 00:00:00'))
          ->create();
    }
}
