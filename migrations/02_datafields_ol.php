<?php
class DatafieldsOl extends DBMigration
{
    function up()
    {
        DBManager::get()->exec("INSERT IGNORE INTO `datafields` (`datafield_id`, `name`, `object_type`, `object_class`, `edit_perms`, `view_perms`, `priority`, `mkdate`, `chdate`, `type`, `typeparam`) VALUES
('02cbd6d7113a78778747f057316a2068', 'Konkrete Teilnehmeranzahl für Evaluation', 'sem', NULL, 'root', 'root', 0, NULL, NULL, 'textline', ''),
('1e074f09e8394e0937f6286379b6f6cc', 'Lehrevaluation öffentlich', 'usersemdata', '8', 'root', 'root', 0, NULL, NULL, 'bool', ''),
('da9abc75739eb0d567e8dcf19668320f', 'Lehrevaluation speichern', 'usersemdata', '8', 'root', 'root', 0, NULL, NULL, 'bool', '')");
    }
    
    function down()
    {
        DBManager::get()->exec("DELETE FROM datafields WHERE datafield_id IN ('02cbd6d7113a78778747f057316a2068','1e074f09e8394e0937f6286379b6f6cc','da9abc75739eb0d567e8dcf19668320f'");
        DBManager::get()->exec("DELETE FROM datafields_entries WHERE datafield_id IN ('02cbd6d7113a78778747f057316a2068','1e074f09e8394e0937f6286379b6f6cc','da9abc75739eb0d567e8dcf19668320f'");
        
    }
}