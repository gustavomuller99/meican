<?php

use yii\db\Migration;

class m260831_000000_add_new_workflow_type extends Migration
{
    public function up()
    {
        $this->execute("
			ALTER TABLE `meican_bpm_node` CHANGE `type` `type` ENUM('New_Request','Duration','Domain','User','Bandwidth','Request_User_Authorization','Request_Group_Authorization','Accept_Automatically','Deny_Automatically','Hour','WeekDay','Group','Device','Request_User_Authorization_Blockchain','Request_Group_Authorization_Blockchain') CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;
        ");

        $this->execute("
			ALTER TABLE `meican_bpm_flow_control` CHANGE `type` `type` ENUM('New_Request','Duration','Domain','User','Bandwidth','Request_User_Authorization','Request_Group_Authorization','Accept_Automatically','Deny_Automatically','Hour','WeekDay','Group','Device','Request_User_Authorization_Blockchain','Request_Group_Authorization_Blockchain') CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;
        ");

        $this->execute("
			ALTER TABLE `meican_connection_auth` CHANGE `type` `type` ENUM('USER', 'GROUP', 'WORKFLOW', 'BLOCKCHAIN', 'GROUP_BLOCKCHAIN') CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;
        ");
    }

    public function down()
    {
        echo "m260831_000000_add_new_workflow_type cannot be reverted.\n";

        return false;
    }
}