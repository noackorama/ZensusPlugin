<?php

class MigrateCurrentCourses extends Migration
{
    public function up()
    {
        $db = DBManager::get();
        $semester = Semester::findCurrent();
        $zensus_id = $db->fetchColumn("SELECT pluginid FROM plugins WHERE pluginclassname = 'UniZensusPlugin'");
        if ($zensus_id && $semester) {
            $number = $db->execute("INSERT IGNORE INTO datafields_entries (content,datafield_id,range_id,sec_range_id,chdate)
             SELECT '1','5a005542e66248e2a5560cdd0e00025d', seminar_id, '', UNIX_TIMESTAMP() FROM plugins_activated INNER JOIN seminare ON CONCAT('sem',seminar_id) = poiid AND start_time = ? WHERE pluginid = ? AND state = 'on' ", [$semester->beginn, $zensus_id]);
            $this->write($number . ' courses migrated');
        }
    }
}
