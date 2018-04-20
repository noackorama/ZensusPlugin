<?php
/**
* UniZensusAdminPlugin.class.php
*
*
*
*
* @author        André Noack <noack@data-quest.de>, Suchi & Berg GmbH <info@data-quest.de>
* @version        $Id: UniZensusAdminPlugin.class.php,v 1.6 2013/04/04 15:17:49 anoack Exp $
*/
// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// UniZensusAdminPlugin.class.php
//
// Copyright (C) 2007 André Noack <noack@data-quest.de>
// Suchi & Berg GmbH <info@data-quest.de>
// +---------------------------------------------------------------------------+
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or any later version.
// +---------------------------------------------------------------------------+
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
// +---------------------------------------------------------------------------+
require_once "UniZensusPlugin.class.php";
require_once 'zensus_xml_func.php';   // XML-Funktionen

class UniZensusAdminPlugin extends StudipPlugin implements SystemPlugin, AdminCourseAction
{

    public $user_is_eval_admin;
    public $user_is_eval_agent;
    public $zensuspluginid;


    public function __construct()
    {
        parent::__construct();
        $this->user_is_eval_admin = $GLOBALS['perm']->have_perm('root') || RolePersistence::isAssignedRole($GLOBALS['user']->id, 'eval_admin') ;
        $this->user_is_eval_agent = $this->user_is_eval_admin || RolePersistence::isAssignedRole($GLOBALS['user']->id, 'eval_agent') ;
        if (Navigation::hasItem("/browse/my_courses")) {
            $my_courses = Navigation::getItem("/browse/my_courses");
            if ($this->user_is_eval_agent) {
                $auswahl = new Navigation(_("Unizensus Auswahl"), PluginEngine::getURL($this, array(), "zensusadmin/selection"));
                $my_courses->addSubNavigation('zensusadmin_selection', $auswahl);
            }
            if ($this->user_is_eval_admin) {
                $status = new Navigation(_("Unizensus Status"), PluginEngine::getURL($this, array(), "zensusadmin/status"));
                $my_courses->addSubNavigation('zensusadmin_status', $status);
            }

        }

        $info = PluginManager::getInstance()->getPluginInfo('unizensusplugin');
        $this->zensuspluginid = $info['id'];
    }

    public function getPluginName()
    {
        return _("Lehrevaluation");
    }

    public function getAdminActionURL()
    {
        return PluginEngine::getURL($this, array(), "zensusadmin/changestatus");
    }

    public function useMultimode()
    {
        return _('zur Lehrevaluation auswählen');
    }

    public function getAdminCourseActionTemplate($course_id, $values = null, $semester = null) {
        $factory = $GLOBALS['template_factory'];
        $template = $factory->open("shared/string");
        $value = (int)UniZensusPlugin::getDatafieldValue(UniZensusPlugin::$datafield_id_markiert, $course_id);
        $input = '
        <label>
        <input type="hidden" name="all_sem[]" value="' . $course_id . '">
        <input name="zensus[' . $course_id . ']" type="checkbox" value="1" '. ($value ? 'checked' : '') .'>
        </label>';
        $template->set_attribute("content", $input);
        return $template;
    }

    function getDisplayname() {
        return _("Lehrevaluation-Administration");
    }

    private function hasPermission($user_id = null) {
        if (!$user_id) {
            $user_id = $GLOBALS['user']->id;
        }
        if ($user_id === 'nobody') {
            return false;
        }
        if ($GLOBALS['perm']->get_perm($user_id) == 'root') {
            return true;
        }
        if ($this->user_is_eval_admin === null) {
            $this->user_is_eval_admin = RolePersistence::isAssignedRole($user_id, 'eval_admin');
        }
        return $this->user_is_eval_admin;
    }


    function getExportData($key, $seminar_id)
    {
        static $data = array();
        if (!$data[$seminar_id]) {
            $data = array();
            $data[$seminar_id] = UniZensusPlugin::getAdditionalExportData($seminar_id);
            $uplugin = PluginManager::getInstance()->getPlugin('UniZensusPlugin');
            if ($uplugin) {
                $uplugin->setId($seminar_id);
                list($starttime,$endtime) = $uplugin->getCourseEvaluationTimeframe();
                $data[$seminar_id]['eval_start_time'] = $starttime ? strftime('%Y-%m-%d', $starttime) : '';
                $data[$seminar_id]['eval_end_time'] = $endtime ? strftime('%Y-%m-%d', $endtime) : '';
            }
        }
        if ($key == 'teilnehmer_anzahl_aktuell') {
            if ($data[$seminar_id]['eval_participants']) {
                return zensus_xmltag($key, $data[$seminar_id]['eval_participants']);
            } else {
                return zensus_xmltag($key,DbManager::get()->query("SELECT COUNT(*) FROM seminar_user WHERE seminar_id='".$seminar_id."' AND status='autor'")->fetchColumn());
            }
        }
        if ($key == 'resultpublic') {
            return zensus_xmltag($key, (int)$data[$seminar_id]['eval_public']);
        }
        if ($key == 'resultpublicstud') {
            return zensus_xmltag($key, (int)$data[$seminar_id]['eval_public_stud']);
        }
        if ($key == 'resultstore') {
            return zensus_xmltag($key, (int)$data[$seminar_id]['eval_stored']);
        }
        if ($key == 'form_teilnahme') {
            return zensus_xmltag($key, $data[$seminar_id]['form']);
        }
        if ($key == 'art_fragebogen') {
            return zensus_xmltag($key, $data[$seminar_id]['fb']);
        }
        if ($key == 'wdhl') {
            return zensus_xmltag($key, (int)$data[$seminar_id]['wdhl']);
        }
        if ($key == 'sprache') {
            return zensus_xmltag($key, $data[$seminar_id]['sprache']);
        }
        if ($key == 'flif_fol') {
            $value = $data[$seminar_id]['flif_course'] + $data[$seminar_id]['fol_course']*2;
            return zensus_xmltag($key, $value);
        }
        return zensus_xmltag($key, $data[$seminar_id][$key]);
    }

    function export_action()
    {
        global $ex_sem, $ex_only_homeinst,$ex_sem_class, $ex_only_visible;

        global $xml_groupnames_fak,$xml_names_fak,$xml_groupnames_inst,$xml_names_inst
        ,$xml_groupnames_lecture,$xml_names_lecture,$xml_groupnames_person
        ,$xml_names_person,$xml_groupnames_studiengaenge,$xml_names_studiengaenge;

        require_once ('lib/export/export_xml_vars.inc.php');   // XML-Variablen

        //Uni OL

        $xml_names_lecture['teilnehmer_anzahl_aktuell'] = array($this, 'getExportData');
        $xml_names_lecture['resultpublic'] = array($this, 'getExportData');
        $xml_names_lecture['resultstore'] = array($this, 'getExportData');
        $xml_names_lecture['flif_course'] = array($this, 'getExportData');
        $xml_names_lecture['fol_course'] = array($this, 'getExportData');
        $xml_names_lecture['eval_end_time'] = array($this, 'getExportData');
        $xml_names_lecture['eval_start_time'] = array($this, 'getExportData');
        $xml_names_lecture['flif_fol'] = array($this, 'getExportData');
        $xml_names_lecture['resultpublicstud'] = array($this, 'getExportData');
        $xml_names_lecture['form_teilnahme'] = array($this, 'getExportData');
        $xml_names_lecture['art_fragebogen'] = array($this, 'getExportData');
        $xml_names_lecture['wdhl'] = array($this, 'getExportData');
        $xml_names_lecture['sprache'] = array($this, 'getExportData');

        $authcode = Request::option('authcode');
        if ($authcode) {
            $auth_uid = DbManager::get()->query("SELECT user_id FROM user_config WHERE field='UNIZENSUSPLUGIN_AUTH_TOKEN' AND value='$authcode'")->fetchColumn();
            if (!$auth_uid) $export_error = 'wrong authcode';
        } else {
            $export_error = 'missing authcode';
        }
        $ex_tstamp = Request::get('ex_tstamp');
        list($y,$M,$d,$h,$m) = explode('-', $ex_tstamp);
        $tstamp = mktime($h,$m,0,$M,$d,(int)$y);
        $hash = md5(get_config('UNIZENSUSPLUGIN_SHARED_SECRET1') . $ex_tstamp . get_config('UNIZENSUSPLUGIN_SHARED_SECRET2'));
        if ((Request::option('ex_hash') != $hash || $tstamp < (time() - 600))) {
            $export_error = 'authorization failed';
        } else {
            if (Request::option('ex_sem') == 'next') {
                $ex_sem = Semester::findNext()->semester_id;
            } else {
                $ex_sem = Semester::findCurrent()->semester_id;
            }
            if (!$ex_sem) {
                $export_error = 'no valid semester found';
            }
        }
        $range_id = Request::option('range_id', 'root');
        $ex_only_visible = Request::int('ex_only_visible', 0);
        $ex_only_homeinst = Request::int('ex_only_homeinst', 1);
        $ex_sem_class = Request::intArray('ex_sem_class');
        if (!count($ex_sem_class)) $ex_sem_class[] = 1;
        ini_set('memory_limit', '256M');
        while(ob_get_level()) ob_end_clean();
        header("Content-type: text/xml; charset=utf-8");
        if ($export_error) {
            header('HTTP/1.1 403 Forbidden');
            echo '<?xml version="1.0"?>' . chr(10);
            echo zensus_xmltag('studip_export_error_msg', strip_tags($export_error));
            exit();
        }
        zensus_export_range($range_id, $ex_sem, 'direct', $this->hasPermission($auth_uid) ? false : $auth_uid);
    }

    public static function onEnable($plugin_id)
    {
        //allow for nobody
        $rp = new RolePersistence();
        $rp->assignPluginRoles($plugin_id, range(1,7));
    }

    function export_participants_action()
    {
        $ex_tstamp = Request::get('ex_tstamp');
        list($y,$M,$d,$h,$m) = explode('-', $ex_tstamp);
        $tstamp = mktime($h,$m,0,$M,$d,(int)$y);
        $hash = md5(get_config('UNIZENSUSPLUGIN_SHARED_SECRET1') . $ex_tstamp . get_config('UNIZENSUSPLUGIN_SHARED_SECRET2'));
        $range_id = Request::option('range_id');
        if ((Request::option('ex_hash') != $hash || $tstamp < (time() - 600))) {
            $export_error = 'authorization failed';
        }
        try {
            $course = Seminar::getInstance($range_id);
        } catch (Exception $e) {
             $export_error = 'course not found';
        }
        while(ob_get_level()) ob_end_clean();
        if ($export_error) {
            header('HTTP/1.1 403 Forbidden');
            echo strip_tags($export_error);
            exit();
        }
        $st = DBManager::get()->prepare("SELECT a.user_id,a.email FROM seminar_user su INNER JOIN auth_user_md5 a USING(user_id) WHERE su.status IN ('autor') AND su.seminar_id=?");
        $st->execute(array($range_id));
        $participants = $st->fetchAll(PDO::FETCH_NUM);
        header("Content-type: text/csv; charset=windows-1252");
        echo array_to_csv($participants);
    }

}
