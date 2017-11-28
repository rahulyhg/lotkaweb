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
      $table->changeColumn('user_id', ['null' => true]);
      $table->changeColumn('user_id', ['default' => null]);
      $table->changeColumn('ip', ['null' => true]);
      $table->changeColumn('ip', ['default' => null]);
      $table->changeColumn('type', ['null' => true]);
      $table->changeColumn('type', ['default' => null]);

    }
}
