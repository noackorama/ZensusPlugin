<?php
class MoreDatafields extends Migration
{
    function up()
    {
        DBManager::get()->exec("INSERT IGNORE INTO `datafields` (`datafield_id`, `name`, `object_type`, `object_class`, `edit_perms`, `view_perms`, `priority`, `mkdate`, `chdate`, `type`, `typeparam`) VALUES
('5a005542e66248e2a5560cdd0e00025d', 'Lehrevaluation: Veranstaltung für Lehrevaluation vorgesehen', 'sem', NULL, 'root', 'root', 0, NULL, NULL, 'bool', ''),
('96776f3e6053e6f6bbb6d61a78e33389', 'Lehrevaluation: Wiederholungen', 'sem', NULL, 'root', 'root', 0, NULL, NULL, 'bool', ''),
('7ae912151565bbcb76c3ac60bbd2f56c', 'Lehrevaluation: Art des Fragebogens', 'sem', NULL, 'root', 'root', 0, NULL, NULL, 'selectbox', 'Basisbogen\n+fakult. spez. FB\n+VA spez. FB'),
('a07535cf2f8a72df33c12ddfa4b53dde', 'Lehrevaluation: Form der Teilnahme', 'sem', NULL, 'root', 'root', 0, NULL, NULL, 'selectbox', 'Papierform\nOnline Abschluss\nOnline Direkteinsicht')");
    }

    function down()
    {
        DBManager::get()->exec("DELETE FROM datafields WHERE datafield_id IN ('5a005542e66248e2a5560cdd0e00025d','96776f3e6053e6f6bbb6d61a78e33389','7ae912151565bbcb76c3ac60bbd2f56c','a07535cf2f8a72df33c12ddfa4b53dde')");
        DBManager::get()->exec("DELETE FROM datafields_entries WHERE datafield_id IN ('5a005542e66248e2a5560cdd0e00025d','96776f3e6053e6f6bbb6d61a78e33389','7ae912151565bbcb76c3ac60bbd2f56c','a07535cf2f8a72df33c12ddfa4b53dde')");

    }
}