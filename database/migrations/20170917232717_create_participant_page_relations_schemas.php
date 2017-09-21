<?php


use Phinx\Migration\AbstractMigration;

class CreateParticipantPageRelationsSchemas extends AbstractMigration
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
        $table = $this->table('character_attribute');
        $table
          ->addColumn('character_id', 'integer', array('limit' => 10))
          ->addColumn('attribute_id', 'integer', array('limit' => 10))
          ->addColumn('created_at', 'datetime', array('default' => '0000-00-00 00:00:00'))
          ->addColumn('updated_at', 'datetime', array('default' => '0000-00-00 00:00:00'))
          ->create();
      
        $table = $this->table('group_attribute');
        $table
          ->addColumn('group_id', 'integer', array('limit' => 10))
          ->addColumn('attribute_id', 'integer', array('limit' => 10))
          ->addColumn('created_at', 'datetime', array('default' => '0000-00-00 00:00:00'))
          ->addColumn('updated_at', 'datetime', array('default' => '0000-00-00 00:00:00'))
          ->create();
      
        $table = $this->table('plot_attribute');
        $table
          ->addColumn('plot_id', 'integer', array('limit' => 10))
          ->addColumn('attribute_id', 'integer', array('limit' => 10))
          ->addColumn('created_at', 'datetime', array('default' => '0000-00-00 00:00:00'))
          ->addColumn('updated_at', 'datetime', array('default' => '0000-00-00 00:00:00'))
          ->create();
      
        $table = $this->table('relation_attribute');
        $table
          ->addColumn('relation_id', 'integer', array('limit' => 10))
          ->addColumn('attribute_id', 'integer', array('limit' => 10))
          ->addColumn('created_at', 'datetime', array('default' => '0000-00-00 00:00:00'))
          ->addColumn('updated_at', 'datetime', array('default' => '0000-00-00 00:00:00'))
          ->create();
      
        $table = $this->table('user_attribute');
        $table
          ->addColumn('user_id', 'integer', array('limit' => 10))
          ->addColumn('attribute_id', 'integer', array('limit' => 10))
          ->addColumn('created_at', 'datetime', array('default' => '0000-00-00 00:00:00'))
          ->addColumn('updated_at', 'datetime', array('default' => '0000-00-00 00:00:00'))
          ->create();      

        $table = $this->table('ticket_attribute');
        $table
          ->addColumn('ticket_id', 'integer', array('limit' => 10))
          ->addColumn('attribute_id', 'integer', array('limit' => 10))
          ->addColumn('created_at', 'datetime', array('default' => '0000-00-00 00:00:00'))
          ->addColumn('updated_at', 'datetime', array('default' => '0000-00-00 00:00:00'))
          ->create();      
      
        $table = $this->table('post_attribute');
        $table
          ->addColumn('post_id', 'integer', array('limit' => 10))
          ->addColumn('attribute_id', 'integer', array('limit' => 10))
          ->addColumn('created_at', 'datetime', array('default' => '0000-00-00 00:00:00'))
          ->addColumn('updated_at', 'datetime', array('default' => '0000-00-00 00:00:00'))
          ->create();    
      
        $table = $this->table('character_group');
        $table
          ->addColumn('character_id', 'integer', array('limit' => 10))
          ->addColumn('group_id', 'integer', array('limit' => 10))
          ->addColumn('created_at', 'datetime', array('default' => '0000-00-00 00:00:00'))
          ->addColumn('updated_at', 'datetime', array('default' => '0000-00-00 00:00:00'))
          ->create();
      
        $table = $this->table('character_plots');
        $table
          ->addColumn('character_id', 'integer', array('limit' => 10))
          ->addColumn('plot_id', 'integer', array('limit' => 10))
          ->addColumn('created_at', 'datetime', array('default' => '0000-00-00 00:00:00'))
          ->addColumn('updated_at', 'datetime', array('default' => '0000-00-00 00:00:00'))
          ->create();
      
        $table = $this->table('character_relation');
        $table
          ->addColumn('character_id', 'integer', array('limit' => 10))
          ->addColumn('relation_id', 'integer', array('limit' => 10))
          ->addColumn('created_at', 'datetime', array('default' => '0000-00-00 00:00:00'))
          ->addColumn('updated_at', 'datetime', array('default' => '0000-00-00 00:00:00'))
          ->create();
      
        $table = $this->table('group_group');
        $table
          ->addColumn('group_id', 'integer', array('limit' => 10))
          ->addColumn('parent_group_id', 'integer', array('limit' => 10))
          ->addColumn('created_at', 'datetime', array('default' => '0000-00-00 00:00:00'))
          ->addColumn('updated_at', 'datetime', array('default' => '0000-00-00 00:00:00'))
          ->create();
      
        $table = $this->table('group_plot');
        $table
          ->addColumn('group_id', 'integer', array('limit' => 10))
          ->addColumn('plot_id', 'integer', array('limit' => 10))
          ->addColumn('created_at', 'datetime', array('default' => '0000-00-00 00:00:00'))
          ->addColumn('updated_at', 'datetime', array('default' => '0000-00-00 00:00:00'))
          ->create();
      
        $table = $this->table('group_relation');
        $table
          ->addColumn('group_id', 'integer', array('limit' => 10))
          ->addColumn('relation_id', 'integer', array('limit' => 10))
          ->addColumn('created_at', 'datetime', array('default' => '0000-00-00 00:00:00'))
          ->addColumn('updated_at', 'datetime', array('default' => '0000-00-00 00:00:00'))
          ->create();
      
        $table = $this->table('user_character');
        $table
          ->addColumn('user_id', 'integer', array('limit' => 10))
          ->addColumn('character_id', 'integer', array('limit' => 10))
          ->addColumn('created_at', 'datetime', array('default' => '0000-00-00 00:00:00'))
          ->addColumn('updated_at', 'datetime', array('default' => '0000-00-00 00:00:00'))
          ->create();
      
        $table = $this->table('user_group');
        $table
          ->addColumn('user_id', 'integer', array('limit' => 10))
          ->addColumn('group_id', 'integer', array('limit' => 10))
          ->addColumn('created_at', 'datetime', array('default' => '0000-00-00 00:00:00'))
          ->addColumn('updated_at', 'datetime', array('default' => '0000-00-00 00:00:00'))
          ->create();
    }
}
