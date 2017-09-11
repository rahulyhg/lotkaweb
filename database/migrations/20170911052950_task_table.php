<?php


use Phinx\Migration\AbstractMigration;

class TaskTable extends AbstractMigration
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
        $table = $this->table('tasks');
        $table
          ->addColumn('title', 'string', array('limit' => 255))
          ->addColumn('description', 'text')
          ->addColumn('due_date', 'datetime', array('default' => '0000-00-00 00:00:00'))
          ->addColumn('priority', 'integer')          
          ->addColumn('completed', 'boolean')
          ->addColumn('completed_at', 'datetime', array('null' => true))
          ->addColumn('user_id', 'integer', array('limit' => 10))          
          ->addColumn('created_at', 'datetime', array('default' => '0000-00-00 00:00:00'))
          ->addColumn('updated_at', 'datetime', array('default' => '0000-00-00 00:00:00'))
          ->create();
    }
}
