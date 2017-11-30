<?php


use Phinx\Migration\AbstractMigration;

class DbFix1 extends AbstractMigration
{
    /**
     * Dirty fix. Something broke when we were forced into newer mysql + php7. This "fixes" so that we can log in at least.
     */
    public function change()
    {
      $table = $this->table('throttle');
      $table->changeColumn('user_id', 'integer', ['null' => true]);
      $table->changeColumn('user_id', 'integer', ['default' => null]);
      $table->changeColumn('ip', 'string', ['null' => true]);
      $table->changeColumn('ip', 'string', ['default' => null]);
      $table->changeColumn('type', 'string', ['null' => true]);
      $table->changeColumn('type', 'string', ['default' => null]);

    }
}
