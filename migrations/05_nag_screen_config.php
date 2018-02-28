<?php
class NagScreenConfig extends Migration
{
    function up()
    {
        $query = "INSERT IGNORE INTO `config` (
                    `config_id`, `parent_id`, `field`, `value`, `is_default`,
                    `type`, `range`, `section`, `mkdate`, `chdate`, `description`
                  ) VALUES (
                    MD5(:field), '', :field, :value, 1, 'string', :range, '',
                    UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), :description
                  )";
        $statement = DBManager::get()->prepare($query);
        $statement->bindValue(':field', 'UNIZENSUS_NAG_SCREEN_LAST_SHOWN');
        $statement->bindValue(':value', '0');
        $statement->bindValue(':range', 'user');
        $statement->bindValue(':description', 'Speichert wann das Zensus Popup zuletzt angezeigt wurde.');
        $statement->execute();
        $statement->bindValue(':field', 'UNIZENSUSPLUGIN_NAG_SCREEN_CONTENT_DOZENT');
        $statement->bindValue(':value', 'Liebe/r Lehrende,

die aktuelle Lehrveranstaltungsevaluation läuft wieder. Eine möglichst hohe Beteiligung und die Freischaltung der Ergebnisse sind sehr wichtig.
Bitte motivieren Sie Ihre Studierenden, an der Evaluation teilzunehmen.

In Stud.IP können Sie innerhalb der Veranstaltung unter dem grauen Reiter "Lehrevaluation Unizensus" 
- die TeilnehmerInnenzahl anpassen,
- die Ergebnisse Ihrer/m Studiendekan/in und der/m Evaluationsbeauftragten freischalten,
- die Auswertungsdokumente in- und exklusive der Freitextantworten Ihren Studierenden weiterleiten.

Vielen Dank für Ihre Unterstützung!
Ihr Referat Studium und Lehre ( evaluation@uni-oldenburg.de )');
        $statement->bindValue(':range', 'global');
        $statement->bindValue(':description', 'Text der bei Dozenten nach Start der Evaluierung in einem Popup einmalig angezeigt werden soll (Stud.IP Formatierung).');
        $statement->execute();
    }

    function down()
    {
        DBManager::get()->exec("DELETE FROM config WHERE field = 'UNIZENSUS_NAG_SCREEN_LAST_SHOWN'");
        DBManager::get()->exec("DELETE FROM config WHERE field = 'UNIZENSUSPLUGIN_NAG_SCREEN_CONTENT_DOZENT'");
        DBManager::get()->exec("DELETE FROM user_config WHERE field = 'UNIZENSUS_NAG_SCREEN_LAST_SHOWN'");
    }
}
