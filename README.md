#ZensusPlugin

Stud.IP Plugin fÃ¼r die VerknÃ¼pfung mit der Zensus Evaluationssoftware

Die Konfiguration muss nach Installation unter Admin/Globale Eisntellungen/Konfiguration vorgenommen werden.

###In jedem Fall mÃ¼ssen folgende Einstellungen vorgenommen werden:
*UNIZENSUSPLUGIN_URL_PREFIX*

hier gehÃ¶rt die URL des Zensus Systems rein, z.B. http://zensus.zuhause.de/

*UNIZENSUSPLUGIN_XMLRPC_ENDPOINT*

hier die URL des RPC services, das ist normalerweise die gleicher URL
mit remote hintendran, z.B. http://zensus.zuhause.de/remote

*UNIZENSUSPLUGIN_SHARED_SECRET1* 	  	
*UNIZENSUSPLUGIN_SHARED_SECRET2*

Hier gehÃ¶ren zwei PasswÃ¶rter hinein, mit denen die Verbindung gesichert
wird. Im Zensus mÃ¼ssen die auch hinterlegt werden (dort Geheimnis1/2 genannt).

AuÃŸerdem muss im Zensus die URL zum Stud.IP
Export eingetragen werden, das wÃ¤re:
https://studip.zuhause.de/plugins.php/unizensusadminplugin/export

ZusÃ¤tzlich muss man im Zensus ein persÃ¶nliches Authentifizierungstoken hinterlegen, damit wird dann gesteuert welche Daten man in Zensus importieren darf. Wenn man in Stud.IP als root-Administrator so ein Token erzeugt, dann kann man von Zensus aus alle Veranstaltungen
importieren. Das Token erzeugt man, indem man auf der Stud.IP Startseite dem Link Lehrevaluation-Administration folgt, und dann den Unterpunkt "Export Token" anklickt. Hier kann man das Token erzeugen, im Zensus muss man es dann in der Konfiguration eintragen.

###weitere KonfigurationsmÃ¶glichkeiten
*UNIZENSUSPLUGIN_DISPLAYNAME*

Damit kann man die Ãœberschrift des Plugins in der MenÃ¼zeile anpassen.

*UNIZENSUSPLUGIN_SHOWN_IN_OVERVIEW*

Damit kann man einstellen, ob die Nutzer auf der Ãœbersichtseite "Meine Veranstaltungen" ein Icon fÃ¼r die Evaluation eingeblendet bekommen sollen. Das Icon wird rot, wenn sich der Status der Evaluation Ã¤ndert.

*UNIZENSUSPLUGIN_BEGIN_EVALUATION*
*UNIZENSUSPLUGIN_END_EVALUATION*

Mit diesen beiden Einstellungen kann ein globaler Zeitraum fÃ¼r die Evaluation eingestellt werden. AuÃŸerhalb des Zeitraums wird dann Ã¼ber das Plugin kein Zugang zum Zensus angeboten. Abweichend von diesem globalen Zeitraum kann fÃ¼r jede Veranstaltung ein individueller Zeitraum angegeben werden. Das erledigt man am einfachsten mit dem Administrationsplugin, dort wird auch fÃ¼r jede Veranstaltung der aktuelle gÃ¼ltige Zeitrahmen angezeigt. Im Moment wird zusÃ¤tzlich der Beginn der Evaluation aufgrund der Termine der Veranstaltung berechnet, also z.B. bei einer regelmÃ¤ÃŸigen Veranstaltung wird der vorletzte regelmÃ¤ÃŸige Termin ermittelt und dann der Montag der Woche als Starttermin benutzt und eine  Laufzeit von 3 Wochen.

*UNIZENSUSPLUGIN_NAG_SCREEN_CONTENT* 

Der Inhalt des Hinweisfensters bei noch ausstehenden Evaluationen. Hier kann Stud.IP Formatierung benutzt werden. 

###Funktionsbeschreibung
GrundsÃ¤tzlich findet man unter dem Punkt "Lehrevaluation-Administration" eine Liste mit Veranstaltungen, die nach Einrichtungen/Semester usw. gefiltert werden kann. In der Tabelle kann man nun sehen, ob die Veranstaltung zur Evaluation vorgesehen ist und wie der aktuelle Status ist. Dazu muss man fÃ¼r die Veranstaltung im Stud.IP das Zensus-Plugin aktivieren. Man kann auf dieser Seite die gewÃ¼snchten Veranstaltungen markieren und mit dem Punkt "Evaluationsplugin fÃ¼r ausgewÃ¤hlte Veranstaltungen ein/ausschalten:" das Plugin aktivieren. Danach wird fÃ¼r diese Veranstaltungen in der Spalte "Zensus Status" der Stand der Evaluation im Zensus angezeigt. Wenn im Zensus keine Evaluation vorgesehen ist, ist das einfach "error not found".
Wird im Zensus fÃ¼r diese Veranstaltung eine Evaluation aktiviert, so bekommen die Teilnehmer in der Veranstaltung einen weiteren Reiter zur Evaluation angezeigt. DarÃ¼ber bekommen sie eine Link, mit dem sie einmalig im Zensus an der Evaluation teilnehmen kÃ¶nnen. Die Dozenten kÃ¶nnen hier den Stand einsehen, und evtl. nach Auswertung hier zur Auswertung gelangen.
Die weiteren MÃ¶glichkeiten diene der Auswahl eines Zeitraums zur Evaluation. Das muss man nicht nutzen, solange hier keine EinschrÃ¤nkung gewÃ¼nscht ist, ansonsten kann man hier Start- und Endzeitpunkte fÃ¼r eine oder mehrere Veranstaltungen setzen. Diese EinschrÃ¤nkungen gelten _nur_ fÃ¼r den Zugang von Stud.IP aus.

Aktiviert man zusÃ¤tzlich das enthaltene Plugin *UniZensusNagScreen*, so wird Studierenden auf der Seite "Meine Veranstaltungen" einmal pro Session ein Dioalogfenster eingeblendet wenn in der Veranstaltungsliste noch Veranstaltungen sichtbar sind, die vom Studierneden noch nicht evaluiert wurden. Der Text des Hinweisfensters kann Ã¼ber die Einstellung *UNIZENSUSPLUGIN_NAG_SCREEN_CONTENT* geÃ¤ndert werden.
