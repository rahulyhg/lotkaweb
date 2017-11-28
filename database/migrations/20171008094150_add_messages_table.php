<?php


use Phinx\Migration\AbstractMigration;

class AddMessagesTable extends AbstractMigration
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
        $table = $this->table('messages');
        $table
          ->addColumn('recipient_id', 'integer', array('limit' => 10))
          ->addColumn('sender_id', 'integer', array('limit' => 10))
          ->addColumn('parent_message_id', 'integer', array('limit' => 10))
          ->addColumn('read_at', 'datetime', array('null' => true))
          ->addColumn('subject', 'text')
          ->addColumn('body', 'text', array('null' => true))
          ->addColumn('type', 'datetime', array('null' => true))          
          ->addColumn('created_at', 'datetime', array('default' => '0000-00-00 00:00:00'))
          ->addColumn('updated_at', 'datetime', array('default' => '0000-00-00 00:00:00'))
          ->create();
    }
}
