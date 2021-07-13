<?php
class InitPlugin extends Migration
{
	function up()
    {
		DBManager::get()->exec("CREATE TABLE IF NOT EXISTS `unizensusplugincache` (
		`id` VARCHAR( 32 ) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL NOT NULL ,
		`data` MEDIUMBLOB NOT NULL ,
		`chdate` INT UNSIGNED NOT NULL ,
		PRIMARY KEY ( `id` )
		) ENGINE = InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC ");
		$studip_db_version = new DBSchemaVersion();
		if ($studip_db_version->get() < 226) {
            DBManager::get()->exec("INSERT IGNORE INTO `config` ( `config_id` , `parent_id` , `field` , `value` , `is_default` , `type` , `range` , `section` , `position` , `mkdate` , `chdate` , `description` , `comment` , `message_template` )
VALUES
(
MD5( 'UNIZENSUSPLUGIN_SHARED_SECRET1' ) , '', 'UNIZENSUSPLUGIN_SHARED_SECRET1', 'geheim1', '1', 'string', 'global', '', '1', UNIX_TIMESTAMP( ) , UNIX_TIMESTAMP( ) , 'Mit Unizensus geteiltes Geheimnis Teil 1', '', ''
),
(
MD5( 'UNIZENSUSPLUGIN_SHARED_SECRET2' ) , '', 'UNIZENSUSPLUGIN_SHARED_SECRET2', 'geheim2', '1', 'string', 'global', '', '2', UNIX_TIMESTAMP( ) , UNIX_TIMESTAMP( ) , 'Mit Unizensus geteiltes Geheimnis Teil 2', '', ''
),
(
MD5( 'UNIZENSUSPLUGIN_SHOWN_IN_OVERVIEW' ) , '', 'UNIZENSUSPLUGIN_SHOWN_IN_OVERVIEW', '0', '1', 'boolean', 'global', '', '3', UNIX_TIMESTAMP( ) , UNIX_TIMESTAMP( ) , 'Anzeige von Unizensus Evaluationen auf der Übersichtsseite', '', ''
),
(
MD5( 'UNIZENSUSPLUGIN_DISPLAYNAME' ) , '', 'UNIZENSUSPLUGIN_DISPLAYNAME', 'Lehrevaluation UniZensus', '1', 'string', 'global', '', '4', UNIX_TIMESTAMP( ) , UNIX_TIMESTAMP( ) , 'Überschrift Unizensus Evaluationsplugin', '', ''
),
(
MD5( 'UNIZENSUSPLUGIN_XMLRPC_ENDPOINT' ) , '', 'UNIZENSUSPLUGIN_XMLRPC_ENDPOINT', 'http://openquestionnaire.de:8080/zensus/remote', '1', 'string', 'global', '', '5', UNIX_TIMESTAMP( ) , UNIX_TIMESTAMP( ) , 'URL des Unizensus XML-RPC Service', '', ''
),
(
MD5( 'UNIZENSUSPLUGIN_URL_PREFIX' ) , '', 'UNIZENSUSPLUGIN_URL_PREFIX', 'http://openquestionnaire.de:8080/zensus/', '1', 'string', 'global', '', '6', UNIX_TIMESTAMP( ) , UNIX_TIMESTAMP( ) , 'URL Unizensus', '', ''
),
(
MD5( 'UNIZENSUSPLUGIN_BEGIN_EVALUATION' ) , '', 'UNIZENSUSPLUGIN_BEGIN_EVALUATION', '', '1', 'string', 'global', '', '7', UNIX_TIMESTAMP( ) , UNIX_TIMESTAMP( ) , 'Beginn des Lehrevaluationszeitraums (YYYY-MM-DD)', '', ''
),
(
MD5( 'UNIZENSUSPLUGIN_END_EVALUATION' ) , '', 'UNIZENSUSPLUGIN_END_EVALUATION', '', '1', 'string', 'global', '', '8', UNIX_TIMESTAMP( ) , UNIX_TIMESTAMP( ) , 'Ende des Lehrevaluationszeitraums (YYYY-MM-DD)', '', ''
)");
        } else {
            DBManager::get()->exec("INSERT IGNORE INTO `config` ( `field` , `value` , `type` , `range` , `section` , `mkdate` , `chdate` , `description` )
VALUES
(
 'UNIZENSUSPLUGIN_SHARED_SECRET1', 'geheim1', 'string', 'global', 'plugins', UNIX_TIMESTAMP( ) , UNIX_TIMESTAMP( ) , 'Mit Unizensus geteiltes Geheimnis Teil 1'
),
(
 'UNIZENSUSPLUGIN_SHARED_SECRET2', 'geheim2', 'string', 'global', 'plugins', UNIX_TIMESTAMP( ) , UNIX_TIMESTAMP( ) , 'Mit Unizensus geteiltes Geheimnis Teil 2'
),
(
 'UNIZENSUSPLUGIN_SHOWN_IN_OVERVIEW', '1', 'boolean', 'global', 'plugins', UNIX_TIMESTAMP( ) , UNIX_TIMESTAMP( ) , 'Anzeige von Unizensus Evaluationen auf der Übersichtsseite'
),
(
 'UNIZENSUSPLUGIN_DISPLAYNAME', 'Lehrevaluation UniZensus', 'string', 'global', 'plugins', UNIX_TIMESTAMP( ) , UNIX_TIMESTAMP( ) , 'Überschrift Unizensus Evaluationsplugin'
),
(
 'UNIZENSUSPLUGIN_XMLRPC_ENDPOINT', 'http://openquestionnaire.de:8080/zensus/remote', 'string', 'global', 'plugins', UNIX_TIMESTAMP( ) , UNIX_TIMESTAMP( ) , 'URL des Unizensus XML-RPC Service'
),
(
 'UNIZENSUSPLUGIN_URL_PREFIX', 'http://openquestionnaire.de:8080/zensus/', 'string', 'global', 'plugins', UNIX_TIMESTAMP( ) , UNIX_TIMESTAMP( ) , 'URL Unizensus'
),
(
'UNIZENSUSPLUGIN_BEGIN_EVALUATION', '', 'string', 'global', 'plugins', UNIX_TIMESTAMP( ) , UNIX_TIMESTAMP( ) , 'Beginn des Lehrevaluationszeitraums (YYYY-MM-DD)'
),
(
'UNIZENSUSPLUGIN_END_EVALUATION', '', 'string', 'global', 'plugins',  UNIX_TIMESTAMP( ) , UNIX_TIMESTAMP( ) , 'Ende des Lehrevaluationszeitraums (YYYY-MM-DD)'
)");
        }
		DBManager::get()->exec("INSERT IGNORE INTO datafields (datafield_id, name, object_type, object_class, edit_perms, view_perms, priority, mkdate, chdate) VALUES (MD5('UNIZENSUSPLUGIN_BEGIN_EVALUATION'), 'Beginn des Lehrevaluationszeitraums (YYYY-MM-DD)', 'sem', NULL, 'root', 'root', 0, NULL, NULL),
(MD5('UNIZENSUSPLUGIN_END_EVALUATION'), 'Ende des Lehrevaluationszeitraums (YYYY-MM-DD)', 'sem', NULL, 'root', 'root', 1, NULL, NULL)");
	}

	function down()
	{
		DBManager::get()->exec("DELETE FROM `config` WHERE `field`
IN('UNIZENSUSPLUGIN_SHARED_SECRET1', 'UNIZENSUSPLUGIN_SHARED_SECRET2', 'UNIZENSUSPLUGIN_SHOWN_IN_OVERVIEW',
 'UNIZENSUSPLUGIN_DISPLAYNAME', 'UNIZENSUSPLUGIN_XMLRPC_ENDPOINT', 'UNIZENSUSPLUGIN_URL_PREFIX','UNIZENSUSPLUGIN_BEGIN_EVALUATION','UNIZENSUSPLUGIN_END_EVALUATION')");
		DBManager::get()->exec("DELETE FROM `datafields` WHERE `datafield_id` IN (MD5('UNIZENSUSPLUGIN_BEGIN_EVALUATION'),MD5('UNIZENSUSPLUGIN_END_EVALUATION'))");
		DBManager::get()->exec("DELETE FROM `datafields_entries` WHERE `datafield_id` IN (MD5('UNIZENSUSPLUGIN_BEGIN_EVALUATION'),MD5('UNIZENSUSPLUGIN_END_EVALUATION'))");
		DBManager::get()->exec("DROP TABLE IF EXISTS `unizensusplugincache`");
	}

}
