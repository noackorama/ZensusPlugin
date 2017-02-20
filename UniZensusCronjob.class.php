<?php
class UniZensusCronjob extends CronJob
{
    public static function getName()
    {
        return _('UniZensus: Leert die Cache-Tabelle');
    }

    public static function getDescription()
    {
        return self::getName();
    }

    public function execute($last_result, $parameters = array())
    {
        $query = "TRUNCATE TABLE `unizensusplugincache`";
        DBManager::get()->exec($query);
    }
}
