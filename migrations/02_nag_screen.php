<?php
class NagScreen extends Migration
{
	function up()
    {
        $studip_db_version = new DBSchemaVersion();
        if ($studip_db_version->get() < 226) {
            DBManager::get()->exec("INSERT IGNORE INTO `config` ( `config_id` , `parent_id` , `field` , `value` , `is_default` , `type` , `range` , `section` , `position` , `mkdate` , `chdate` , `description` , `comment` , `message_template` )
VALUES
(
MD5( 'UNIZENSUSPLUGIN_NAG_SCREEN_CONTENT' ) , '', 'UNIZENSUSPLUGIN_NAG_SCREEN_CONTENT', '!!Sie haben noch nicht alle Ihre Lehrveranstaltungen evaluiert!
Zur Durchführung der Evaluation klicken Sie auf der Seite \"Meine Veranstaltungen\" pro Veranstaltungszeile auf das (noch rote) Evaluationssymbol und dort auf den Fragebogen.
Vielen Dank!

%%Hinweis: Die Teilnahme ist grundsätzlich freiwillig und anonym und hat keine Auswirkung auf Ihr persönliches Studium. Bei Fragen zu dieser Meldung wenden Sie sich bitte an studip@studip.de . %%', '1', 'string', 'global', '', '1', UNIX_TIMESTAMP( ) , UNIX_TIMESTAMP( ) , 'Text der bei noch nicht erfolgter Evaluierung in einem Popup angezeigt werden soll (Stud.IP Formatierung)', '', ''
)");
        } else {
            DBManager::get()->exec("INSERT IGNORE INTO `config` ( `field` , `value` , `type` , `range` , `section` , `mkdate` , `chdate` , `description` )
VALUES
(
'UNIZENSUSPLUGIN_NAG_SCREEN_CONTENT', '!!Sie haben noch nicht alle Ihre Lehrveranstaltungen evaluiert!
Zur Durchführung der Evaluation klicken Sie auf der Seite \"Meine Veranstaltungen\" pro Veranstaltungszeile auf das (noch rote) Evaluationssymbol und dort auf den Fragebogen.
Vielen Dank!

%%Hinweis: Die Teilnahme ist grundsätzlich freiwillig und anonym und hat keine Auswirkung auf Ihr persönliches Studium. Bei Fragen zu dieser Meldung wenden Sie sich bitte an studip@studip.de . %%', 'string', 'global', 'plugins', UNIX_TIMESTAMP( ) , UNIX_TIMESTAMP( ) , 'Text der bei noch nicht erfolgter Evaluierung in einem Popup angezeigt werden soll (Stud.IP Formatierung)'
)");
        }
    }

	function down()
	{
		DBManager::get()->exec("DELETE FROM `config` WHERE `field`
IN('UNIZENSUSPLUGIN_NAG_SCREEN_CONTENT')");
	}

}
