<?php

use yii\db\Migration;

class m260830_000000_add_blockchain_address_to_user extends Migration
{
    public function up()
    {
        $this->execute("
            ALTER TABLE `meican_user`
            ADD COLUMN `blockchain_address` VARCHAR(42) NULL DEFAULT NULL AFTER `authkey`,
            ADD COLUMN `blockchain_private_key` VARCHAR(66) NULL DEFAULT NULL AFTER `blockchain_address`
        ");
    }

    public function down()
    {
        $this->execute("
            ALTER TABLE `meican_user`
            DROP COLUMN `blockchain_private_key`,
            DROP COLUMN `blockchain_address`
        ");
    }
}
