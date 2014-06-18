#ZensusPlugin

Stud.IP Plugin für die Verknüpfung mit der Zensus Evaluationssoftware

Die Konfiguration muss nach Installation unter Admin/Globale Eisntellungen/Konfiguration vorgenommen werden.

###In jedem Fall müssen folgende Einstellungen vorgenommen werden:
*UNIZENSUSPLUGIN_URL_PREFIX*

hier gehört die URL des Zensus Systems rein, z.B. http://zensus.zuhause.de/

*UNIZENSUSPLUGIN_XMLRPC_ENDPOINT*

hier die URL des RPC services, das ist normalerweise die gleicher URL
mit remote hintendran, z.B. http://zensus.zuhause.de/remote

*UNIZENSUSPLUGIN_SHARED_SECRET1* 	  	
*UNIZENSUSPLUGIN_SHARED_SECRET2*

Hier gehören zwei Passwörter hinein, mit denen die Verbindung gesichert
wird. Im Zensus müssen die auch hinterlegt werden (dort Geheimnis1/2 genannt).

Außerdem muss im Zensus die URL zum Stud.IP
Export eingetragen werden, das wäre:
https://studip.zuhause.de/plugins.php/unizensusadminplugin/export

Zusätzlich muss man im Zensus ein persönliches Authentifizierungstoken hinterlegen, damit wird dann gesteuert welche Daten man in Zensus importieren darf. Wenn man in Stud.IP als root-Administrator so ein Token erzeugt, dann kann man von Zensus aus alle Veranstaltungen
importieren. Das Token erzeugt man, indem man auf der Stud.IP Startseite dem Link Lehrevaluation-Administration folgt, und dann den Unterpunkt "Export Token" anklickt. Hier kann man das Token erzeugen, im Zensus muss man es dann in der Konfiguration eintragen.

###weitere Konfigurationsmöglichkeiten
*UNIZENSUSPLUGIN_DISPLAYNAME*

Damit kann man die Überschrift des Plugins in der Menüzeile anpassen.

*UNIZENSUSPLUGIN_SHOWN_IN_OVERVIEW*

Damit kann man einstellen, ob die Nutzer auf der Übersichtseite "Meine Veranstaltungen" ein Icon für die Evaluation eingeblendet bekommen sollen. Das Icon wird rot, wenn sich der Status der Evaluation ändert.

*UNIZENSUSPLUGIN_BEGIN_EVALUATION*
*UNIZENSUSPLUGIN_END_EVALUATION*

Mit diesen beiden Einstellungen kann ein globaler Zeitraum für die Evaluation eingestellt werden. Außerhalb des Zeitraums wird dann über das Plugin kein Zugang zum Zensus angeboten. Abweichend von diesem globalen Zeitraum kann für jede Veranstaltung ein individueller Zeitraum angegeben werden. Das erledigt man am einfachsten mit dem Administrationsplugin, dort wird auch für jede Veranstaltung der aktuelle gültige Zeitrahmen angezeigt. Im Moment wird zusätzlich der Beginn der Evaluation aufgrund der Termine der Veranstaltung berechnet, also z.B. bei einer regelmäßigen Veranstaltung wird der vorletzte regelmäßige Termin ermittelt und dann der Montag der Woche als Starttermin benutzt und eine  Laufzeit von 3 Wochen.

*UNIZENSUSPLUGIN_NAG_SCREEN_CONTENT* 

Der Inhalt des Hinweisfensters bei noch ausstehenden Evaluationen. Hier kann Stud.IP Formatierung benutzt werden. 

###Funktionsbeschreibung
Grundsätzlich findet man unter dem Punkt "Lehrevaluation-Administration" eine Liste mit Veranstaltungen, die nach Einrichtungen/Semester usw. gefiltert werden kann. In der Tabelle kann man nun sehen, ob die Veranstaltung zur Evaluation vorgesehen ist und wie der aktuelle Status ist. Dazu muss man für die Veranstaltung im Stud.IP das Zensus-Plugin aktivieren. Man kann auf dieser Seite die gewüsnchten Veranstaltungen markieren und mit dem Punkt "Evaluationsplugin für ausgewählte Veranstaltungen ein/ausschalten:" das Plugin aktivieren. Danach wird für diese Veranstaltungen in der Spalte "Zensus Status" der Stand der Evaluation im Zensus angezeigt. Wenn im Zensus keine Evaluation vorgesehen ist, ist das einfach "error not found".
Wird im Zensus für diese Veranstaltung eine Evaluation aktiviert, so bekommen die Teilnehmer in der Veranstaltung einen weiteren Reiter zur Evaluation angezeigt. Darüber bekommen sie eine Link, mit dem sie einmalig im Zensus an der Evaluation teilnehmen können. Die Dozenten können hier den Stand einsehen, und evtl. nach Auswertung hier zur Auswertung gelangen.
Die weiteren Möglichkeiten diene der Auswahl eines Zeitraums zur Evaluation. Das muss man nicht nutzen, solange hier keine Einschränkung gewünscht ist, ansonsten kann man hier Start- und Endzeitpunkte für eine oder mehrere Veranstaltungen setzen. Diese Einschränkungen gelten _nur_ für den Zugang von Stud.IP aus.

Aktiviert man zusätzlich das enthaltene Plugin *UniZensusNagScreen*, so wird Studierenden auf der Seite "Meine Veranstaltungen" einmal pro Session ein Dioalogfenster eingeblendet wenn in der Veranstaltungsliste noch Veranstaltungen sichtbar sind, die vom Studierneden noch nicht evaluiert wurden. Der Text des Hinweisfensters kann über die Einstellung *UNIZENSUSPLUGIN_NAG_SCREEN_CONTENT* geändert werden.
