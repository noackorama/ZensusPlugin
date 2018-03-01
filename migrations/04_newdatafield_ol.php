<?php
class NewdatafieldOl extends DBMigration
{
    function up()
    {
        DBManager::get()->exec("INSERT IGNORE INTO `datafields` (`datafield_id`, `name`, `object_type`, `object_class`, `edit_perms`, `view_perms`, `priority`, `mkdate`, `chdate`, `type`, `typeparam`) VALUES
('6c691ebe8c034f77a2cf643efce811c9', 'Lehrevaluation für Studierende freigeben', 'usersemdata', '8', 'root', 'root', 0, NULL, NULL, 'bool', '')");
    }

    function down()
    {
        DBManager::get()->exec("DELETE FROM datafields WHERE datafield_id = '6c691ebe8c034f77a2cf643efce811c9'");
        DBManager::get()->exec("DELETE FROM datafields_entries WHERE datafield_id = '6c691ebe8c034f77a2cf643efce811c9'");
    }
}