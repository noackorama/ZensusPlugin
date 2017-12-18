<?php
require_once "UniZensusRPC.class.php";

class UniZensusPlugin extends StudipPlugin implements StandardPlugin
{

    public static $datafield_id_teilnehmer = '02cbd6d7113a78778747f057316a2068';
    public static $datafield_id_auswertung_oeffentlich = '1e074f09e8394e0937f6286379b6f6cc';
    public static $datafield_id_auswertung_speichern = 'da9abc75739eb0d567e8dcf19668320f';
    public static $datafield_id_auswertung_studierende = '6c691ebe8c034f77a2cf643efce811c9';
    public static $datafield_id_fol = '71d3f2e6ba39e4a2bc2f8551bd599b64';
    public static $datafield_id_flif = 'f298887055ee46a0ab6ac525647aeac1';
    public static $datafield_id_vorgesehen = '5a005542e66248e2a5560cdd0e00025d';
    public static $datafield_id_wdhl = '96776f3e6053e6f6bbb6d61a78e33389';
    public static $datafield_id_fb = '7ae912151565bbcb76c3ac60bbd2f56c';
    public static $datafield_id_form= 'a07535cf2f8a72df33c12ddfa4b53dde';
    public static $datafield_id_sprache= '';


    private $id;

    static function SQLDateToTimestamp($sqldate){
        $date_values = explode("-", $sqldate); //YYYY-MM-DD
        if (checkdate((int)$date_values[1],(int)$date_values[2],(int)$date_values[0])){
            return mktime(0,0,0,$date_values[1],$date_values[2],$date_values[0]);
        } else {
            return false;
        }
    }


    /**
     *
     */
    function __construct()
    {
        parent::__construct();
        $this->RPC = new UniZensusRPC();

    }

    function getIconNavigation($course_id, $last_visit, $user_id)
    {
        $this->setId($course_id);
        if (Config::get()->UNIZENSUSPLUGIN_SHOWN_IN_OVERVIEW && $this->isVisible()) {
            $has_changed = $this->hasChanged($last_visit);
            $message = $this->getOverviewMessage($has_changed);
            $nav = new Navigation(Config::get()->UNIZENSUSPLUGIN_DISPLAYNAME, PluginEngine::getUrl($this),array(),'show');
            $nav->setImage($has_changed ? 'icons/20/red/evaluation' : 'icons/20/black/evaluation', array('title' => $message));
            return $nav;
        }
    }

    function getTabNavigation($course_id)
    {
        $this->setId($course_id);
        if ($this->isVisible()) {
            $tab = new Navigation(Config::get()->UNIZENSUSPLUGIN_DISPLAYNAME, PluginEngine::getUrl($this),array(),'show');
            $tab->setActiveImage(Assets::image_path('icons/16/black/evaluation'));
            $tab->setImage(Assets::image_path('icons/16/white/evaluation'));
            return array(get_class($this) => $tab);
        }
    }

    function getNotificationObjects($course_id, $since, $user_id)
    {
    }
    function getInfoTemplate($course_id)
    {
    }

    function setId($id) {
        $this->id = $id;
    }

    function getId()
    {
        return $this->id;
    }

    function getZensusCourseId(){
        $seminar = Seminar::GetInstance($this->getId());
        $semester = SemesterData::GetInstance();
        if($seminar->getSemesterDurationTime() == 0){
            $current_sem = $semester->getSemesterDataByDate($seminar->getSemesterStartTime());
        } else {
            $current_sem = $semester->getCurrentSemesterData();
        }
        $this->semester_id = $current_sem['semester_id'];
        return  $this->semester_id . '_' . $this->getId();
    }

    function getCourseStatus(){
        if($this->getId()  && get_object_type($this->getId()) == 'sem'){
            $this->course_status = $this->RPC->getCourseStatus($this->getZensusCourseId());
            $time_frame = $this->getCourseEvaluationTimeframe();
            if(is_array($time_frame)){
                $this->course_status['time_frame']['begin'] = $time_frame[0];
                $this->course_status['time_frame']['end'] = $time_frame[1];
            }
        }
    }

    function getCourseAndUserStatus(){
        if($this->getId()  && get_object_type($this->getId()) == 'sem'){
            $this->course_status = $this->RPC->getCourseStatus($this->getZensusCourseId(),$GLOBALS['user']->id);
            $time_frame = $this->getCourseEvaluationTimeframe();
            if(is_array($time_frame)){
                $this->course_status['time_frame']['begin'] = $time_frame[0];
                $this->course_status['time_frame']['end'] = $time_frame[1];
            }
        }
    }

    function getOverviewMessage($has_changed = false){
        if (!$GLOBALS['perm']->have_studip_perm('tutor', $this->getId())){
            if($this->course_status['questionnaire'] == true) return _("Den Fragebogen aufrufen und an der Evaluation teilnehmen"); if($this->course_status['status'] == 'run') return _("Sie haben an dieser Evaluation bereits teilgenommen!");
        }
        return Config::get()->UNIZENSUSPLUGIN_DISPLAYNAME . ': ' .$this->getCourseStatusMessage() . ($has_changed ? ' (' . _("geändert"). ')' : '');
    }

    function isVisible() {
        if (!$this->isActivated($this->getId())) {
            return false;
        }
        $this->getCourseAndUserStatus();
        if ($this->course_status['status']
            && strpos($this->course_status['status'], 'error') === false) {
            $additional_data = self::getAdditionalExportData($this->getId());
            $results_available = $this->isAnyResultAvailable($GLOBALS['user']->id);
        }
        if (
            ($this->course_status['status']
            && strpos($this->course_status['status'], 'error') === false
            && ( (($this->course_status['preview'] || $this->course_status['questionnaire'] || $this->course_status['status'] == 'finished'
                ) && $GLOBALS['perm']->have_studip_perm('autor' , $this->getId()))
                )
            && (!isset($this->course_status['time_frame'])
                || ($this->course_status['time_frame']['begin'] < time()
                    && $this->course_status['time_frame']['end'] > time()
                    )
                || ($this->course_status['status'] == 'finished')
                )
            )
            || $GLOBALS['perm']->have_perm('admin')
            || $GLOBALS['perm']->have_studip_perm('tutor', $this->getId())
            ) return true;
        else return false;
    }

    function hasChanged($lastviewed) {
        $this->getCourseAndUserStatus();
        if ($GLOBALS['perm']->have_studip_perm('tutor', $this->getId())){
            return $this->course_status['last_changed'] > $lastviewed;
        } else {
            return $this->course_status['questionnaire'] == true;
        }
    }

    function getCourseStatusMessage(){
        switch ($this->course_status['status']) {
                case 'prepare':
                    return _("Die Evaluation wird vorbereitet.");
                break;
                case 'run':
                    if (isset($this->course_status['time_frame']) &&
                    ($this->course_status['time_frame']['begin'] > time() || $this->course_status['time_frame']['end'] < time())){
                        return _("Außerhalb des Evaluierungszeitraums.");
                    } else {
                        return _("Die Evaluation läuft.");
                    }
                break;
                case 'analyze':
                    return _("Die Evaluation wird ausgewertet.");
                break;
                case 'finished':
                    return _("Die Evaluation ist abgeschlossen.");
                break;
                default:
                    return _("Unbekannter Status.");
            }
    }

    function getCourseEvaluationTimeframe()
    {
        if ($this->getId()) {
            $end = strtotime(self::getDatafieldValue(md5('UNIZENSUSPLUGIN_END_EVALUATION'), $this->getId()));
            $begin = strtotime(self::getDatafieldValue(md5('UNIZENSUSPLUGIN_BEGIN_EVALUATION'), $this->getId()));
            if ($begin && $end && ($begin <= $end)) return array($begin, strtotime('now 23:59', $end));
            list($calcbegin, $calcend) = $this->getCalculatedCourseTimeFrame($this->getId());
            if ($calcbegin && !$begin) $begin = $calcbegin;
            if ($calcend && !$end) $end = $calcend;
            if ($begin && $end && ($begin <= $end)) return array($begin, strtotime('now 23:59', $end));
            $globalbegin = $this->SQLDateToTimestamp(Config::get()->UNIZENSUSPLUGIN_BEGIN_EVALUATION);
            $globalend = $this->SQLDateToTimestamp(Config::get()->UNIZENSUSPLUGIN_END_EVALUATION);
            if ($globalbegin && !$begin) $begin = $globalbegin;
            if ($globalend && !$end) $end = $globalend;
            if ($begin && $end && ($begin <= $end)) return array($begin, strtotime('now 23:59', $end));
            else return array($globalbegin, strtotime('now 23:59', $globalend));
        }
    }

    function getCalculatedCourseTimeFrame($seminar_id)
    {
        $begin = false;
        $end = false;
        $course = Course::find($seminar_id);
        if ($course->duration_time == 0) {
            $current_sem = $course->start_semester;
        } else {
            $current_sem = Semester::findCurrent();
        }
        $termine = DBManager::get()->fetchColumn("SELECT count(*) FROM termine WHERE range_id=? AND date BETWEEN ? AND ? ", array($seminar_id, (int)$current_sem['beginn'], (int)$current_sem['ende']));
        if ($termine) {
            $num = round($termine * 90 / 100);
            if (!$num) $num = 1;
            $begin = DBManager::get()->fetchColumn("SELECT date FROM termine WHERE range_id=? AND date BETWEEN ? AND ? ORDER BY date ASC LIMIT ?,1", array($seminar_id, (int)$current_sem['beginn'], (int)$current_sem['ende'], (int)$num - 1));
            if ($begin) {
                $begin = strtotime("00:00", $begin);
                $end = strtotime("+20 days", $begin);
            }
        }
        return array($begin, $end);
    }

    public static function getDatafieldValue($datafield_id, $range_id, $sec_range_id = '')
    {
        $db = DBManager::get();
        $p = $db->prepare("SELECT content FROM datafields_entries WHERE datafield_id=? AND range_id=? AND sec_range_id=?");
        if ($p->execute(array($datafield_id, $range_id, $sec_range_id))) {
            return $p->fetchColumn();
        }
    }

    public static function setDatafieldValue($content, $datafield_id, $range_id, $sec_range_id = '')
    {
        $db = DBManager::get();
        $p = $db->prepare("REPLACE INTO datafields_entries (content,datafield_id,range_id,sec_range_id) VALUES (?,?,?,?)");
        return $p->execute(array($content, $datafield_id, $range_id, $sec_range_id));
    }

    public static function unsetDatafieldValue($datafield_id, $range_id, $sec_range_id = '')
    {
        $db = DBManager::get();
        $p = $db->prepare("DELETE FROM datafields_entries WHERE datafield_id=? AND range_id=? AND sec_range_id=?");
        return $p->execute(array($datafield_id, $range_id, $sec_range_id));
    }

    public static function getAdditionalExportData($seminar_id)
    {
        $eval_public = $eval_stored =  $eval_public_stud = array();
        $ret['fol_course'] = (int)self::getDatafieldValue(self::$datafield_id_fol, $seminar_id);
        $ret['flif_course'] = (int)self::getDatafieldValue(self::$datafield_id_flif, $seminar_id);
        $ret['eval_participants'] = self::getDatafieldValue(self::$datafield_id_teilnehmer, $seminar_id);
        foreach(Seminar::getInstance($seminar_id)->getMembers('dozent') as $dozent) {
            $eval_public[] = (int)self::getDatafieldValue(self::$datafield_id_auswertung_oeffentlich, $seminar_id, $dozent['user_id']);
            $eval_stored[] = (int)self::getDatafieldValue(self::$datafield_id_auswertung_speichern, $seminar_id, $dozent['user_id']);
            $eval_public_stud[] = (int)self::getDatafieldValue(self::$datafield_id_auswertung_studierende, $seminar_id, $dozent['user_id']);
        }
        $ret['eval_public'] = count($eval_public) ? min($eval_public) : 0;
        $ret['eval_stored'] = array_sum($eval_stored) == count(Seminar::getInstance($seminar_id)->getMembers('dozent'));
        $ret['eval_public_stud'] = count($eval_public_stud) ? min($eval_public_stud) : 0;
        return $ret;
    }

    function show_action()
    {
        if (!$this->isVisible()) return;
        PageLayout::setTitle($_SESSION['SessSemName']['header_line'] . ' - ' . Config::get()->UNIZENSUSPLUGIN_DISPLAYNAME);
        Navigation::activateItem('/course/' . get_class($this));
        ob_start();
        $pluginrelativepath = $this->getPluginUrl();
        $user_id = $GLOBALS['user']->id;
        echo chr(10) . '<div style="padding:10px;background-color:white">';
        //Uni OL
        if ($GLOBALS['perm']->get_studip_perm($this->getId()) == 'dozent') {
            $lehrende = Seminar::getInstance($this->getID())->getMembers('dozent');
            if (Request::submitted('ok')) {
                self::setDatafieldValue((Request::int('eval_participants') ? Request::int('eval_participants') : ''), self::$datafield_id_teilnehmer, $this->getID());
                self::setDatafieldValue(Request::int('eval_public'), self::$datafield_id_auswertung_oeffentlich, $this->getID(), $GLOBALS['user']->id);
                self::setDatafieldValue(Request::int('eval_stored'), self::$datafield_id_auswertung_speichern, $this->getID(), $GLOBALS['user']->id);
                self::setDatafieldValue(Request::int('eval_public_stud'), self::$datafield_id_auswertung_studierende, $this->getID(), $GLOBALS['user']->id);
                $new_start = strtotime(Request::get('time_frame1'));
                $new_end = strtotime(Request::get('time_frame2'));
                if ($new_start && $new_end && $new_end >= $new_start) {
                    self::setDatafieldValue(date('Y-m-d', $new_end), md5('UNIZENSUSPLUGIN_END_EVALUATION'), $this->getId());
                    self::setDatafieldValue(date('Y-m-d', $new_start), md5('UNIZENSUSPLUGIN_BEGIN_EVALUATION'), $this->getId());
                } else {
                    self::unsetDatafieldValue(md5('UNIZENSUSPLUGIN_END_EVALUATION'), $this->getId());
                    self::unsetDatafieldValue(md5('UNIZENSUSPLUGIN_BEGIN_EVALUATION'), $this->getId());
                }
                echo MessageBox::success(_("Die Einstellungen wurden gespeichert."));
            }
            $this->getCourseAndUserStatus();
            $valid_end = strtotime(self::getDatafieldValue(md5('UNIZENSUSPLUGIN_END_EVALUATION'), $this->getId()));
            $valid_begin = strtotime(self::getDatafieldValue(md5('UNIZENSUSPLUGIN_BEGIN_EVALUATION'), $this->getId()));
            if ($valid_end !== false && $valid_end < $valid_begin) $valid_end = false;
            echo chr(10) . '<form action="?" method="post">';
            echo chr(10) . (class_exists('CSRFProtection') ? CSRFProtection::tokenTag() : '') ;

            echo chr(10) . '<fieldset><legend>'._("Einstellungen").'</legend>';
            echo chr(10). '<table cellspacing="2" border="0">';
            echo '<tr><td>';
            echo '<label style="font-weight:bold" for="time_frame">' ._("Zeitraum") . '</label>';
            echo '</td><td align="center">';
            echo 'Start:<input class="has-date-picker hasDatePicker" name="time_frame1" id="time_frame1" size="10" value="' . ($valid_begin ? strftime('%x', $this->course_status['time_frame']['begin']) :'') . '" type="text" ' . (!$valid_begin && $this->course_status['time_frame']['begin'] ? 'placeholder="'.strftime('%x', $this->course_status['time_frame']['begin']).'"' : '') . '>';
            echo 'Ende:<input class="has-date-picker hasDatePicker" name="time_frame2" id="time_frame2" size="10" value="' . ($valid_end ? strftime('%x', $this->course_status['time_frame']['end']) : '') . '" type="text" ' . (!$valid_end && $this->course_status['time_frame']['end'] ? 'placeholder="'.strftime('%x', $this->course_status['time_frame']['end']).'"' : '') . '>';
            echo '</td><td>';
            echo '<div style="font-style:italic; padding-left: 10px;">';
            echo _("Innerhalb dieses Zeitraumes ist die Evaluation für die Studierenden zugänglich. Bitte ändern Sie diesen nur bei Bedarf (z.B. für Blockseminare).");
            echo '</div>';
            echo '</td></tr>';

            echo '<tr><td colspan="3"><hr></td></tr>';
            echo '<tr><td>';
            echo '<label style="font-weight:bold" for="eval_participants">' ._("Konkrete Teilnehmeranzahl") . '</label>';
            echo '</td><td align="center">';
            echo '<input name="eval_participants" id="eval_participants" value="'.self::getDatafieldValue(self::$datafield_id_teilnehmer, $this->getID()).'" type="text" size="2">';
            echo '</td><td>';
            echo '<div style="font-style:italic; padding-left: 10px;">';
            echo sprintf(_("Diese Teilnehmeranzahl wird zur Auswertung der Evaluation herangezogen. Wenn Sie einen neuen Wert eingeben, wird Ihnen dieser nach einer nächtlichen Aktualisierung der Daten am nächsten Tag in dem Ergebnis-PDF angezeigt. Wenn Sie keinen anderen Wert eingeben, wird die Anzahl der Teilnehmer aus Stud.IP benutzt (zur Zeit: %s).")
                    , count(Seminar::getInstance($this->getId())->getMembers('autor')));
            echo '</div>';
            echo '</td></tr>';

            echo '<tr><td colspan="3"><hr></td></tr>';

            echo '<tr><td>';
            echo '<label style="font-weight:bold" for="eval_public_stud">' ._("Ergebnisweiterleitung an Studierende") . '</label>';
            if (count($lehrende)) {
                echo '<ul style="font-size:smaller">';
                foreach($lehrende as $l) {
                    echo '<li>';
                    echo htmlReady($l['Nachname'] . ', '. $l['Vorname'][0] . '.');
                    echo '&nbsp;(' . (self::getDatafieldValue(self::$datafield_id_auswertung_studierende, $this->getID(), $l['user_id']) + 1) . ')';
                    echo '</li>';
                }
                echo '</ul>';
            }
            echo '</td><td align="center" nowrap>';
            echo '1.&nbsp;<input style="vertical-align:bottom" name="eval_public_stud" id="eval_public_stud" type="radio" value="0" '.(self::getDatafieldValue(self::$datafield_id_auswertung_studierende, $this->getID(), $GLOBALS['user']->id) == 0 ? 'checked' : '').' >';
            echo '</td><td>';
            echo '<div style="font-style:italic; padding-left: 10px;">';
            echo sprintf(_("Keine Ergebnisweiterleitung.")
            , htmlready(Seminar::getInstance($this->getId())->getName()) );
            echo '</div>';
            echo '</td></tr>';
            echo '<tr><td>';
            echo '&nbsp;';
            echo '</td><td align="center" nowrap>';
            echo '2.&nbsp;<input style="vertical-align:bottom" name="eval_public_stud" id="eval_public_stud" type="radio" value="1" '.(self::getDatafieldValue(self::$datafield_id_auswertung_studierende, $this->getID(), $GLOBALS['user']->id) == 1 ? 'checked' : '').' >';
            echo '</td><td>';
            echo '<div style="font-style:italic; padding-left: 10px;">';
            echo sprintf(_("Mit der <b>Übermittlung</b> der Auswertung der Ergebnisse <u>inklusive</u> der Freitextantworten <b>an die Studierenden</b> dieser Lehrveranstaltung bin ich einverstanden. Mir ist bekannt, dass ich meine Einwilligung jederzeit ohne Angabe von Gründen mit Wirkung für die Zukunft widerrufen kann.")
            , htmlready(Seminar::getInstance($this->getId())->getName()) );
            echo '</div>';
            echo '</td></tr>';
            echo '<tr><td>';
            echo '&nbsp;';
            echo '</td><td align="center" nowrap>';
            echo '3.&nbsp;<input style="vertical-align:bottom" name="eval_public_stud" id="eval_public_stud" type="radio" value="2" '.(self::getDatafieldValue(self::$datafield_id_auswertung_studierende, $this->getID(), $GLOBALS['user']->id) == 2 ? 'checked' : '').' >';
            echo '</td><td>';
            echo '<div style="font-style:italic; padding-left: 10px;">';
            echo sprintf(_("Mit der <b>Übermittlung</b> der Auswertung der Ergebnisse <u>ohne</u> die Freitextantworten <b>an die Studierenden</b> dieser Lehrveranstaltung bin ich einverstanden. Mir ist bekannt, dass ich meine Einwilligung jederzeit ohne Angabe von Gründen mit Wirkung für die Zukunft widerrufen kann.")
            , htmlready(Seminar::getInstance($this->getId())->getName()) );
            echo '</div>';
            echo '</td></tr>';
            /*echo '<input name="eval_public_stud" id="eval_public_stud" type="checkbox" value="1" '.(self::getDatafieldValue(self::$datafield_id_auswertung_studierende, $this->getID(), $GLOBALS['user']->id) ? 'checked' : '').' >';
            echo '</td><td>';
            echo '<div style="font-style:italic; padding-left: 10px;">';
            echo sprintf(_("Mit der <b>Übermittlung</b> der Auswertung der Ergebnisse inklusive der Freitextantworten <b>an die Studierenden</b> dieser Lehrveranstaltung bin ich einverstanden. Mir ist bekannt, dass ich meine Einwilligung jederzeit ohne Angabe von Gründen mit Wirkung für die Zukunft widerrufen kann.")
            , htmlready(Seminar::getInstance($this->getId())->getName()) );
            echo '</div>';
            echo '</td></tr>';
            */
            echo '<tr><td colspan="3"><hr></td></tr>';

            echo '<tr><td>';
            echo '<label style="font-weight:bold" for="eval_public">' ._("Ergebnisweiterleitung an Studiendekanin/Studiendekan sowie Evaluationsbeauftragte/n") . '</label>';
            if (count($lehrende)) {
                echo '<ul style="font-size:smaller">';
                foreach($lehrende as $l) {
                    echo '<li>';
                    echo htmlReady($l['Nachname'] . ', '. $l['Vorname'][0] . '.');
                    echo '&nbsp;(' . (self::getDatafieldValue(self::$datafield_id_auswertung_oeffentlich, $this->getID(), $l['user_id']) + 1) . ')';
                    echo '</li>';
                }
                echo '</ul>';
            }
            echo '</td><td align="center" nowrap>';
            echo '1.&nbsp;<input style="vertical-align:bottom" name="eval_public" id="eval_public" type="radio" value="0" '.(self::getDatafieldValue(self::$datafield_id_auswertung_oeffentlich, $this->getID(), $GLOBALS['user']->id) == 0 ? 'checked' : '').' >';
            echo '</td><td>';
            echo '<div style="font-style:italic; padding-left: 10px;">';
            echo sprintf(_("Keine Ergebnisweiterleitung.")
            , htmlready(Seminar::getInstance($this->getId())->getName()) );
            echo '</div>';
            echo '</td></tr>';
            echo '<tr><td>';
            echo '&nbsp;';
            echo '</td><td align="center" nowrap>';
            echo '2.&nbsp;<input style="vertical-align:bottom" name="eval_public" id="eval_public" type="radio" value="1" '.(self::getDatafieldValue(self::$datafield_id_auswertung_oeffentlich, $this->getID(), $GLOBALS['user']->id) == 1 ? 'checked' : '').' >';
            echo '</td><td>';
            echo '<div style="font-style:italic; padding-left: 10px;">';
            echo sprintf(_("Mit der <b>Übermittlung</b> der Auswertung der Ergebnisse der studentischen Lehrveranstaltungsevaluation aus der Lehrveranstaltung (%s) <b>an die Studiendekanin bzw. den Studiendekan und die Evaluationsbeauftragte bzw. den Evaluationsbeauftragten</b> bin ich einverstanden. Mir ist bekannt, dass ich meine Einwilligung jederzeit ohne Angabe von Gründen mit Wirkung für die Zukunft widerrufen kann.")
            , htmlready(Seminar::getInstance($this->getId())->getName()) );
            echo '</div>';
            echo '</td></tr>';

               echo '<tr><td colspan="3"><hr></td></tr>';

               echo '<tr><td>';
            echo '<label style="font-weight:bold" for="eval_stored">' ._("Ergebnis dauerhaft speichern") . '</label>';
            if (count($lehrende)) {
                echo '<ul style="font-size:smaller">';
                foreach($lehrende as $l) {
                    echo '<li>';
                    echo htmlReady($l['Nachname'] . ', '. $l['Vorname'][0] . '.');
                    echo '&nbsp;(' . (self::getDatafieldValue(self::$datafield_id_auswertung_speichern, $this->getID(), $l['user_id']) ? 'Ja' : 'Nein') . ')';
                    echo '</li>';
                }
                echo '</ul>';
            }
            echo '</td><td align="center">';
            echo '<input name="eval_stored" id="eval_stored" type="checkbox" value="1" '.(self::getDatafieldValue(self::$datafield_id_auswertung_speichern, $this->getID(), $GLOBALS['user']->id) ? 'checked' : '').' >';
            echo '</td><td>';
            echo '<div style="font-style:italic; padding-left: 10px;">';
            echo sprintf(_("Mit der dauerhaften Speicherung der Auswertung der Ergebnisse der studentischen Lehrveranstaltungsevaluation aus der Lehrveranstaltung (%s) bin ich einverstanden. Mir ist bekannt, dass ich meine Einwilligung jederzeit ohne Angabe von Gründen mit Wirkung für die Zukunft widerrufen kann.")
            , htmlready(Seminar::getInstance($this->getId())->getName()) );
            echo '</div>';
            echo '</td></tr>';



            echo '<tr><td colspan="3" align="center">' . Studip\Button::create(_("Übernehmen"), 'ok') . '</td></tr>';
            echo chr(10). '</table>';

            echo chr(10) . '</fieldset></form>';

        }
        echo chr(10) . '<h3>' . _("Status der Lehrevaluation:") . '</h3>';
        if (isset($this->course_status['time_frame'])){
            echo chr(10) . '<p>';
            echo chr(10) . _("Zeitraum:") . '&nbsp;'. strftime("%x", $this->course_status['time_frame']['begin']) . ' - ' . strftime("%x", $this->course_status['time_frame']['end']);
            echo chr(10) . '</p>';
        }
        //Uni OL
        $additional_data = self::getAdditionalExportData($this->getId());
        if ($GLOBALS['perm']->have_perm('root')) {
            $weiterleitung[0] = _("Keine Ergebnisweiterleitung");
            $weiterleitung[1] = _("Ergebnisweiterleitung an Studiendekanin/Studiendekan");
            $weiterleitung[2] = _("Ergebnisweiterleitung an Studiendekanin/Studiendekan und Evaluationsbeauftragte/n");
            $weiterleitung_stud[0] = _("Keine Ergebnisweiterleitung");
            $weiterleitung_stud[1] = _("Ergebnisweiterleitung mit Freitextantworten");
            $weiterleitung_stud[2] = _("Ergebnisweiterleitung ohne Freitextantworten");
            echo chr(10) . '<p>' . _("An Zensus übermittelte Einstellungen:");
            echo chr(10) . '<ul>';
            echo chr(10) . '<li>' . _("Konkrete Teilnehmerzahl") . ': ';
            echo (int)$additional_data['eval_participants'];
            echo chr(10) . '</li>';
            echo chr(10) . '<li>' . _("Ergebnisweiterleitung an Studiendekanin/Studiendekan") . ': ';
            echo $weiterleitung[$additional_data['eval_public']];
            echo chr(10) . '</li>';
            echo chr(10) . '<li>' . _("Ergebnis dauerhaft speichern") . ': ';
            echo $additional_data['eval_stored'] ? _("Ja") : _("Nein");
            echo chr(10) . '</li>';
            echo chr(10) . '<li>' . _("Ergebnisweiterleitung an Studierende") . ': ';
            echo $weiterleitung_stud[$additional_data['eval_public_stud']];
            echo chr(10) . '</li>';
            echo chr(10) . '</ul>';
            echo chr(10) . '</p>';
        }
        $results_available = ($this->course_status['status'] == 'finished' || $GLOBALS['perm']->have_studip_perm('tutor', $this->getId())) && $this->isAnyResultAvailable($user_id);
        if ($this->course_status['numvotes'] < 1) $this->course_status['results'] = false;
        if (($this->course_status['status'] && strpos($this->course_status['status'], 'error') === false) || $this->course_status['pdfquestionnaire']) {
            echo chr(10) . '<p>';
            echo chr(10) . $this->getCourseStatusMessage();
            if ($GLOBALS['perm']->have_studip_perm('tutor', $this->getId()) && $this->course_status['numvotes'] != -1) {
                echo '<br>' . _("Anzahl der Bewertungen für diese Veranstaltung: ") . $this->course_status['numvotes'];
            }
            echo chr(10) . '</p>';
            if ($this->course_status['preview'] || $this->course_status['pdfquestionnaire'] || $this->course_status['questionnaire'] || $this->course_status['noresultsreason'] || $results_available){
                echo chr(10) . '<div style="font-weight:bold;font-size:10pt;border: 1px solid;padding:5px">' . _("Mögliche Aktionen:") . '<div style="font-size:10pt;margin-left:10px">';
                if ($this->course_status['preview']) {
                    echo chr(10) . '<p><a target="_blank" href="' . $this->RPC->getEvaluationURL('preview',$this->getZensusCourseId(),$GLOBALS['user']->id) . '">';
                    echo chr(10) . '<img src="'.$pluginrelativepath.'/images/link_extern.gif" hspace="2" border="0">' . _("Eine Voransicht des Fragebogens aufrufen") . '</a></p>';
                }
                if ($this->course_status['pdfquestionnaire'] && $GLOBALS['perm']->have_studip_perm('tutor', $this->getId()) ) {
                    echo chr(10) . '<p><a target="_blank" href="' . $this->RPC->getEvaluationURL('pdfquestionnaire',$this->getZensusCourseId(),$GLOBALS['user']->id) . '">';
                    echo chr(10) . '<img src="'.$pluginrelativepath.'/images/pdf-icon.gif" hspace="2" border="0" align="absbottom">' . _("Papierfragebogen für manuelle Erhebung als PDF aufrufen") . '</a></p>';
                }

                if ($results_available
                    && $GLOBALS['perm']->have_studip_perm('tutor', $this->getId())
                    && $GLOBALS['auth']->auth['perm'] != 'admin'
                    && $GLOBALS['auth']->auth['perm'] != 'root') {
                    if ($this->checkResultforUser('pdfresults', $user_id)) {
                        echo chr(10) . '<p><a target="_blank" href="' . $this->RPC->getEvaluationURL('results',$this->getZensusCourseId(),$GLOBALS['user']->id) . '">';
                        echo chr(10) . '<img src="'.$pluginrelativepath.'/images/link_extern.gif" hspace="2" border="0">' . _("Die Ergebnisse der Evaluation aufrufen") . '</a></p>';
                    }
                    if ($this->checkResultforUser('pdfdetailfreetexts', $user_id)) {
                        echo chr(10) . '<p><a target="_blank" href="' . $this->RPC->getEvaluationURL('pdfdetailfreetexts',$this->getZensusCourseId(),$GLOBALS['user']->id) . '">';
                        echo chr(10) . '<img src="'.$pluginrelativepath.'/images/pdf-icon.gif" hspace="2" border="0" align="absbottom">' . _("Die Ergebnisse (Detailauswertung mit Kommentaren) der Evaluation als PDF aufrufen") . '</a></p>';
                    }
                    if ($this->checkResultforUser('pdfresults', $user_id)) {
                        echo chr(10) . '<p><a target="_blank" href="' . $this->RPC->getEvaluationURL('pdfresults',$this->getZensusCourseId(),$GLOBALS['user']->id) . '">';
                        echo chr(10) . '<img src="'.$pluginrelativepath.'/images/pdf-icon.gif" hspace="2" border="0" align="absbottom">' . _("Die Ergebnisse (Profillinie) der Evaluation als PDF aufrufen") . '</a></p>';
                    }
                }
                if ($results_available
                    && $GLOBALS['perm']->get_studip_perm($this->getId()) == 'autor'
                    && !$this->course_status['questionnaire']
                    ) {
                    if ($additional_data['eval_public_stud']) {
                        $checked_user_id = $GLOBALS['user']->id;
                        foreach (Seminar::getInstance($this->getId())->getMembers('dozent') as $m) {
                            if ($this->checkResultforUser('pdfresults', $m['user_id'])) {
                                $checked_user_id = $m['user_id'];
                                break;
                            }
                        }
                        if ($this->checkResultforUser('pdfresults', $checked_user_id)) {
                            echo chr(10) . '<p><a target="_blank" href="' . $this->RPC->getEvaluationURL('results', $this->getZensusCourseId(), $checked_user_id) . '">';
                            echo chr(10) . '<img src="' . $pluginrelativepath . '/images/link_extern.gif" hspace="2" border="0">' . _("Die Ergebnisse der Evaluation aufrufen") . '</a></p>';
                        }
                        //hier könnte evtl. pdfdetail benutzt werden, im Moment nur für OL relevant
                        if ($additional_data['eval_public_stud'] == 2 && $this->checkResultforUser('pdfresults', $checked_user_id)) {
                            echo chr(10) . '<p><a target="_blank" href="' . $this->RPC->getEvaluationURL('pdfresults', $this->getZensusCourseId(), $checked_user_id) . '">';
                            echo chr(10) . '<img src="' . $pluginrelativepath . '/images/pdf-icon.gif" hspace="2" border="0" align="absbottom">' . _("Die Ergebnisse (Profillinie) der Evaluation als PDF aufrufen") . '</a></p>';
                        }
                        if ($additional_data['eval_public_stud'] == 1 && $this->checkResultforUser('pdfdetailfreetexts', $checked_user_id)) {
                            echo chr(10) . '<p><a target="_blank" href="' . $this->RPC->getEvaluationURL('pdfdetailfreetexts', $this->getZensusCourseId(), $checked_user_id) . '">';
                            echo chr(10) . '<img src="' . $pluginrelativepath . '/images/pdf-icon.gif" hspace="2" border="0" align="absbottom">' . _("Die Ergebnisse (Detailauswertung mit Kommentaren) der Evaluation als PDF aufrufen") . '</a></p>';
                        }
                    } else {
                        echo chr(10) . '<p>';
                        echo _("Die Evaluation ist beendet, aber die Auswertung liegt nicht vor, da entweder die notwendige Rücklaufquote nicht erreicht wurde bzw. die Ergebnisse von der/dem Lehrenden nicht freigegeben wurden.");
                        echo chr(10) . '</p>';
                    }
                }

                if (!$results_available && $this->course_status['noresultsreason']) {
                    if ($GLOBALS['perm']->have_studip_perm('tutor', $this->getId())) {
                        if($this->course_status['noresultsreason'] == 'wrong phase') {
                            echo chr(10) . '<p>';
                            echo _("Die Auswertung liegt noch nicht vor, da die Evaluation noch läuft.");
                            echo chr(10) . '</p>';
                        }
                        if($this->course_status['noresultsreason'] == 'not public' && $this->course_status['status'] == 'finished') {
                            echo chr(10) . '<p>';
                            echo _("Die Auswertung ist nicht öffentlich.");
                            echo chr(10) . '</p>';
                        }
                        if($this->course_status['noresultsreason'] == 'too few answers' && $this->course_status['status'] == 'finished') {
                            echo chr(10) . '<p>';
                            echo _("Die Auswertung liegt nicht vor, weil die notwendige Rücklaufquote nicht erreicht wurde.");
                            echo chr(10) . '</p>';
                        }
                    } else if ($GLOBALS['perm']->have_studip_perm('autor', $this->getId())) {
                        echo chr(10) . '<p>';
                        echo _("Die Evaluation ist beendet, aber die Auswertung liegt nicht vor, da entweder die notwendige Rücklaufquote nicht erreicht wurde bzw. die Ergebnisse von der/dem Lehrenden nicht freigegeben wurden.");
                        echo chr(10) . '</p>';
                    }
                }
                if ($GLOBALS['perm']->have_perm('root')) {
                    foreach(Seminar::getInstance($this->getId())->getMembers('dozent') as $m) {
                        if ($this->isAnyResultAvailable($m['user_id'])) {
                            echo '<hr>';
                            echo '<h3>' . htmlready($m['Nachname'].', '.$m['Vorname']) . '</h4>';
                            if ($this->checkResultforUser('pdfresults', $m['user_id'])) {
                                echo chr(10) . '<p><a target="_blank" href="' . $this->RPC->getEvaluationURL('results',$this->getZensusCourseId(),$m['user_id']) . '">';
                                echo chr(10) . '<img src="'.$pluginrelativepath.'/images/link_extern.gif" hspace="2" border="0">' . _("Die Ergebnisse der Evaluation aufrufen") . '</a></p>';
                            }
                            if ($this->checkResultforUser('pdfdetail', $m['user_id'])) {
                                echo chr(10) . '<p><a target="_blank" href="' . $this->RPC->getEvaluationURL('pdfdetail',$this->getZensusCourseId(),$m['user_id']) . '">';
                                echo chr(10) . '<img src="'.$pluginrelativepath.'/images/pdf-icon.gif" hspace="2" border="0" align="absbottom">' . _("Die Ergebnisse (Detailauswertung) der Evaluation als PDF aufrufen") . '</a></p>';
                            }
                            if ($this->checkResultforUser('pdfdetailfreetexts', $m['user_id'])) {
                                echo chr(10) . '<p><a target="_blank" href="' . $this->RPC->getEvaluationURL('pdfdetailfreetexts',$this->getZensusCourseId(),$m['user_id']) . '">';
                                echo chr(10) . '<img src="'.$pluginrelativepath.'/images/pdf-icon.gif" hspace="2" border="0" align="absbottom">' . _("Die Ergebnisse (Detailauswertung mit Kommentaren) der Evaluation als PDF aufrufen") . '</a></p>';
                            }
                            if ($this->checkResultforUser('pdfresults', $m['user_id'])) {
                                echo chr(10) . '<p><a target="_blank" href="' . $this->RPC->getEvaluationURL('pdfresults',$this->getZensusCourseId(),$m['user_id']) . '">';
                                echo chr(10) . '<img src="'.$pluginrelativepath.'/images/pdf-icon.gif" hspace="2" border="0" align="absbottom">' . _("Die Ergebnisse (Profillinie) der Evaluation als PDF aufrufen") . '</a></p>';
                            }
                        }
                    }
                    echo '<hr>';
                }
                if ($this->course_status['questionnaire']) {
                    if (!$GLOBALS['perm']->have_studip_perm('tutor' , $this->getId())) {
                        if (!$this->course_status['pdfquestionnaire']) {
                            echo chr(10) . '<p><a target="_blank" href="' . $this->RPC->getEvaluationURL('questionnaire',$this->getZensusCourseId(),$GLOBALS['user']->id) . '">';
                            echo chr(10) . '<img src="'.$pluginrelativepath.'/images/link_extern.gif" hspace="2" border="0">' . _("Den Fragebogen aufrufen und an der Evaluation teilnehmen") . '</a></p>';
                        } else {
                            echo chr(10) .'<p>'. _("Für diese Evaluation ist ein Papierfragebogen vorgesehen. Weitere Informationen bekommen Sie von den Lehrenden der Veranstaltung.") . '</p>';
                        }
                    } else {
                        echo chr(10) . '<p>'._("Als Dozent/Tutor der Veranstaltung können sie nicht an der Evaluation teilnehmen!") . '</p>';
                    }
                } elseif ($this->course_status['status'] == 'run' && !$GLOBALS['perm']->have_studip_perm('tutor' , $this->getId())){
                    echo chr(10) .'<p>'. _("Sie haben an dieser Evaluation bereits teilgenommen!") . '</p>';
                }

                echo chr(10) . '</div>';
                echo '</div>';
            }
        } else {
            echo chr(10) . '<p style="font-weight:bold">' . _("Zu dieser Veranstaltung ist keine Evaluation verfügbar.") . '</p>';
        }
        if ($GLOBALS['perm']->have_perm('root')) {
            $ex_tstamp = date('Y-m-d-H-i');
            $ex_hash = md5(get_config('UNIZENSUSPLUGIN_SHARED_SECRET1') . $ex_tstamp . get_config('UNIZENSUSPLUGIN_SHARED_SECRET2'));
            $range_id = $this->getId();
            $cid = null;
            $ex_only_visible = 0;
            $authcode = UserConfig::get($GLOBALS['user']->id)->UNIZENSUSPLUGIN_AUTH_TOKEN;
            $link = UrlHelper::getLink('plugins.php/unizensusadminplugin/export', compact('range_id', 'ex_tstamp', 'ex_hash','authcode','ex_only_visible','cid'));
            printf('<a href="%s" target="_blank">XML Export</a>', $link);
            if (Request::get('debug')) {
                echo '<pre>'.print_r($this->course_status,1).'</pre>';
            }
        }
        echo chr(10) . '</div>';

        $layout = $GLOBALS['template_factory']->open('layouts/base.php');
        $layout->content_for_layout = ob_get_clean();
        echo $layout->render();
    }

    function isAnyResultAvailable($user_id) {
        if ($this->course_status['results']) {
            return true;
        }
        foreach (words('pdfdetail pdfdetailfreetexts pdfresults') as $key) {
            if ($this->checkResultForUser($key, $user_id)) {
                return true;
            }
        }
    }

    function checkResultforUser($result_key, $user_id)
    {
        if ($this->course_status[$result_key]['type'] == 'course'
            && $this->course_status[$result_key]['content'][0]['available'] == 1) {
            return 'course';
        }
        if ($this->course_status[$result_key]['type'] == 'personalized') {
            foreach ((array)$this->course_status[$result_key]['content'] as $k => $v) {
                if ($v['lecturer_id'] == $user_id && $v['available'] == 1) {
                    return 'personalized';
                }
            }
        }
    }
}
?>
