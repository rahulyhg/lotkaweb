<?php


use Phinx\Migration\AbstractMigration;

class OpenPageSchemas extends AbstractMigration
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
      
      /* CREATE BASIC STRUCTURE */
      if (!$this->hasTable('orders') ) {
        $orders = $this->table('orders');
        $orders->addColumn('name', 'string', array('limit' => 255))
               ->addColumn('email', 'string', array('limit' => 255))
               ->addColumn('type', 'string', array('limit' => 255))
               ->addColumn('amount', 'integer', array('limit' => 11))
               ->addColumn('size', 'string', array('limit' => 16))
               ->addColumn('preference', 'string', array('limit' => 255))
               ->addColumn('user_id', 'integer', array('limit' => 11))
               ->addColumn('attested_id', 'integer', array('limit' => 11))
               ->addColumn('orderdate', 'datetime', array('default' => '0000-00-00 00:00:00'))         
               ->addColumn('created', 'datetime', array('null' => true))
               ->addColumn('updated', 'datetime', array('null' => true))
               ->create();
      }
  
      if (!$this->hasTable('shirts') ) {
        $shirts = $this->table('shirts');
        $shirts->addColumn('type', 'string', array('limit' => 255))
               ->addColumn('type_class', 'string', array('limit' => 255))
               ->addColumn('size', 'string', array('limit' => 16))
               ->addColumn('available', 'boolean', array('default' => 0))
               ->create();
      }
                   
      if (!$this->hasTable('surnames') ) {
        $surnames = $this->table('surnames');
        $surnames->addColumn('surname', 'string', array('limit' => 255))
                 ->addColumn('order_id', 'integer', array('limit' => 11))
                 ->addColumn('available', 'boolean', array('default' => 0))
                 ->create();
      }
                           
      if (!$this->hasTable('teams') ) {
        $teams = $this->table('teams');
        $teams->addColumn('type', 'string', array('limit' => 255))
              ->addColumn('name', 'string', array('limit' => 255))
              ->addColumn('available', 'boolean', array('default' => 0))
              ->create();
      }
      
      if (!$this->hasTable('tickets') ) {
        $tickets = $this->table('tickets');
        $tickets->addColumn('sku', 'string', array('limit' => 255))
                ->addColumn('price', 'integer', array('limit' => 11))
                ->addColumn('description', 'string', array('limit' => 255))
                ->addColumn('statement_descriptor', 'string', array('limit' => 22))
                ->addColumn('available', 'boolean', array('default' => 0))
                ->addColumn('img', 'string', array('limit' => 255))
                ->addColumn('weight', 'integer', array('limit' => 11))
                ->addColumn('surname', 'boolean', array('default' => 0))
                ->addColumn('shirtType', 'boolean', array('default' => 0))
                ->addColumn('size', 'boolean', array('default' => 0))
                ->addColumn('teamPreference', 'boolean', array('default' => 0))
                ->addColumn('visible', 'boolean', array('default' => 0))
                ->create();
      }
    }
}
