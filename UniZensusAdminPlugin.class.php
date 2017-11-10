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
require_once "lib/classes/StudipForm.class.php";
require_once "UniZensusPlugin.class.php";
require_once 'zensus_xml_func.php';   // XML-Funktionen

if (!function_exists('get_route')) {
    include 'get_route.php';
}

class UniZensusAdminPlugin extends StudipPlugin implements SystemPlugin {

    private $user_is_eval_admin;
    private $zensuspluginid;


    public function __construct() {

        parent::__construct();

        if ($this->hasPermission()) {
            $navigation = new Navigation($this->getDisplayname(), PluginEngine::getLink($this, array(), 'show'));
            if (strpos(get_route(),'unizensusadminplugin') !== false) {

                //Navigation::addItem('/UniZensusAdmin/show', clone $navigation);
                $token_navigation = new Navigation(_("Export Token"), PluginEngine::getLink($this, array(), 'token'));
                $subnav = clone $navigation;
                $subnav->addSubNavigation('show', clone $navigation);
                $subnav->addSubNavigation('token', $token_navigation);
                $navigation->addSubNavigation('sub', $subnav);
                Navigation::addItem('/UniZensusAdmin', $navigation);
            } else {
                Navigation::addItem('/start/UniZensusAdmin', clone $navigation);
            }
            $info = PluginManager::getInstance()->getPluginInfo('unizensusplugin');
            $this->zensuspluginid = $info['id'];
        }

    }

    function getDisplayname() {
        return _("Lehrevaluation-Administration");
    }

    private function hasPermission() {
        return $GLOBALS['perm']->have_perm('admin');
    }


    function token_action()
    {
        if (!$this->hasPermission()) {
            throw new AccessDeniedException("Nur Root und ausgewählte Admins dürfen dieses Plugin sehen.");
        }
        Navigation::activateItem('/UniZensusAdmin/sub/token');
        if (Request::submitted('generate_token')) {
            UserConfig::get($GLOBALS['user']->id)->store('UNIZENSUSPLUGIN_AUTH_TOKEN', md5(uniqid('ZensusToken',1)));
        }
        ob_start();
        echo '<p>';
        echo _("Für den Import der Veranstaltungsdaten in das Zensus System müssen sie dort ein Authentifizierungstoken hinterlegen.");
        echo '<br>' . _("Hier können Sie ein Token für Ihre aktuelle Nutzerkennung generieren.");
        echo '</p>';
        echo '<div>';
        echo '<span style="font-weight:bold; padding-right:10px;">' . _("Nutzerkennung:") . '</span>';
        echo $GLOBALS['auth']->auth['uname'] . ' (' . $GLOBALS['auth']->auth['perm'] . ')';
        echo '</div>';
        echo '<div>';
        echo '<span style="font-weight:bold; padding-right:10px;">' . _("Token:") . '</span>';
        echo htmlReady(UserConfig::get($GLOBALS['user']->id)->UNIZENSUSPLUGIN_AUTH_TOKEN);
        echo '</div>';
        echo '<div>';
        echo '<form method="post" action="?">';
        echo '<button class="button" type="submit" name="generate_token">' . _("neues Token erzeugen") . '</button>';
        echo '</form>';
        echo '</div>';
        PageLayout::setTitle($this->getDisplayname());
        $layout = $GLOBALS['template_factory']->open('layouts/base_without_infobox');
        $layout->content_for_layout = ob_get_clean();
        echo $layout->render();
    }

    function show_action() {

        if (!$this->hasPermission()) {
            throw new AccessDeniedException("Nur Root und ausgewählte Admins dürfen dieses Plugin sehen.");
        }
        Navigation::activateItem('/UniZensusAdmin/sub/show');
        ob_start();
        $cols = array();
        $cols[] = array(1,'','');
        $cols[] = array(10,_("Nr."),'VeranstaltungsNummer');
        $cols[] = array(20,_("Veranstaltung"),'Name');
        $cols[] = array(15,_("Dozenten"),'dozenten');
        $cols[] = array(5,_("&sum; Stud.IP"),'teilnehmer_anzahl_aktuell');
        $cols[] = array(5,_("Zensus Status"),'zensus_status');
        $cols[] = array(5,_("&sum; Zensus"),'zensus_numvotes');
        $cols[] = array(5,_("Plugin aktiv"),'plugin_activated');
        $cols[] = array(10,_("Startzeit manuell"),'begin_evaluation');
        $cols[] = array(10,_("Endzeit manuell"),'end_evaluation');
        $cols[] = array(10,_("Startzeit automatisch"),'time_frame_begin');
        $cols[] = array(10,_("Endzeit automatisch"),'time_frame_end');

        $form_fields['starttime']  = array('type' => 'text');
        $form_fields['starttime']['attributes'] = array('size'=>10, 'onMouseOver' => 'jQuery(this).datepicker();');
        $form_fields['endtime'] = array('type' => 'text');
        $form_fields['endtime']['attributes'] = array('size'=>10, 'onMouseOver' => 'jQuery(this).datepicker();');
        $form_fields['plugin_status']  = array('type' => 'radio',  'separator' => '&nbsp;', 'default_value' => 1, 'options' => array(array('name'=>_("Ein"),'value'=>'1'),array('name'=>_("Aus"),'value'=>'0')));
        $form_buttons['set_plugin_status'] = array('name' => 'uebernehmen', 'caption' => _("Plugin ein/ausschalten"));
        $form_buttons['set_starttime'] = array('name' => 'uebernehmen', 'caption' => _("Startzeit übernehmen"));
        $form_buttons['set_endtime'] = array('name' => 'uebernehmen', 'caption' => _("Endzeit übernehmen"));
        $form = new StudipForm($form_fields, $form_buttons, 'studipform', false);

        if($form->isClicked('set_starttime') || $form->isClicked('set_endtime')){
            if(is_array($_REQUEST['sem_choosen'])){
                if ($form->isClicked('set_starttime')){
                    $datafield_value = $form->getFormFieldValue('starttime');
                    if ($datafield_value) {
                        $datafield_value = strftime('%Y-%m-%d', strtotime($datafield_value));
                    }
                    $datafield_id = md5('UNIZENSUSPLUGIN_BEGIN_EVALUATION');
                } else {
                    $datafield_value = $form->getFormFieldValue('endtime');
                    if ($datafield_value) {
                        $datafield_value = strftime('%Y-%m-%d', strtotime($datafield_value));
                    }
                    $datafield_id = md5('UNIZENSUSPLUGIN_END_EVALUATION');
                }
                $db = new DB_Seminar();
                foreach(array_keys($_REQUEST['sem_choosen']) as $seminar_id){
                    $db->queryf("REPLACE INTO datafields_entries (range_id, datafield_id, content, chdate) VALUES ('%s','%s','%s',UNIX_TIMESTAMP())",
                        $seminar_id, $datafield_id , $datafield_value);
                }
                $form->doFormReset();
            }
        }
        if($form->isClicked('set_plugin_status')){
            if(is_array($_REQUEST['sem_choosen'])){
                $set_to_status = $form->getFormFieldValue('plugin_status') ? 'on' : 'off';
                $db = new DB_Seminar();
                foreach(array_keys($_REQUEST['sem_choosen']) as $seminar_id){
                    $db->queryf("REPLACE INTO plugins_activated (pluginid,poiid,state) VALUES ('%s','%s','%s')",
                        $this->zensuspluginid, 'sem' . $seminar_id, $set_to_status);
                }
                $form->doFormReset();
            }
        }

        if (Request::submitted('reset_search')) {
            $_SESSION['_default_sem'] = Semester::findCurrent()->semester_id;
            $_SESSION['zensus_admin']['check_eval'] = false;
            $_SESSION['zensus_admin']['plugin_activated'] = false;
            $_SESSION['zensus_admin']['filter_name'] = "";
        }

        if (Request::submitted('choose_institut') || Request::submitted('export')) {
            $_SESSION['_default_sem'] = Request::option('select_sem', $_SESSION['_default_sem']);
            $_SESSION['zensus_admin']['check_eval'] = isset($_REQUEST['check_eval']);
            $_SESSION['zensus_admin']['plugin_activated'] = isset($_REQUEST['plugin_activated']);
            $_SESSION['zensus_admin']['filter_name'] = trim(Request::get('filter_name'));
        }

        if(!$_SESSION['_default_sem'] || $_SESSION['_default_sem'] == 'all'){
            $semester = SemesterData::GetInstance();
            $one_semester = $semester->getCurrentSemesterData();
            $_SESSION['_default_sem'] = $one_semester['semester_id'];
        }
        if ($_SESSION['_default_sem']){
            $semester = SemesterData::GetInstance();
            $one_semester = $semester->getSemesterData($_SESSION['_default_sem']);
            if($one_semester["beginn"]){
                $sem_condition = "AND seminare.start_time <=".$one_semester["beginn"]." AND (".$one_semester["beginn"]." <= (seminare.start_time + seminare.duration_time) OR seminare.duration_time = -1) ";
            }
        }
        if ($_SESSION['zensus_admin']['filter_name']) {
            $sem_condition .= " AND (seminare.Name LIKE '%".addslashes($_SESSION['zensus_admin']['filter_name'])."%' ";
            $sem_condition .= " OR seminare.VeranstaltungsNummer LIKE '%".addslashes($_SESSION['zensus_admin']['filter_name'])."%') ";
        }
        if(isset($_REQUEST['sortby'])){
            foreach($cols as $col){
                if($_REQUEST['sortby'] == $col[2]){
                    if($_SESSION['zensus_admin']['sortby']['field'] == $_REQUEST['sortby']){
                        $_SESSION['zensus_admin']['sortby']['direction'] = (int)!$_SESSION['zensus_admin']['sortby']['direction'];
                    } else {
                        $_SESSION['zensus_admin']['sortby']['field'] = $_REQUEST['sortby'];
                        $_SESSION['zensus_admin']['sortby']['direction'] = 0;
                    }
                    break;
                }
            }
        }
        $_my_inst = $this->getInstitute($sem_condition);
        if (is_array($_my_inst)){
            $_my_inst_arr = array_keys($_my_inst);
            if(!$_SESSION['zensus_admin']['institut_id']){
                $_SESSION['zensus_admin']['institut_id'] = $_my_inst_arr[1];
            }
            if($_REQUEST['institut_id']){
                $_SESSION['zensus_admin']['institut_id'] = ($_my_inst[$_REQUEST['institut_id']]) ? $_REQUEST['institut_id'] : $_my_inst_arr[1];
            }
            ?>
            <form action="<?=PluginEngine::getLink($this)?>" method="post">
            <?= (class_exists('CSRFProtection') ? CSRFProtection::tokenTag() : '') ?>
            <div style="font-weight:bold;font-size:10pt;margin:10px;">
            <?=_("Bitte w&auml;hlen Sie eine Einrichtung aus:")?>
            </div>
            <div style="margin-left:10px;">
            <select name="institut_id" style="vertical-align:middle;">
            <?
            reset($_my_inst);
            $num_all = 0;
            while (list($key,$value) = each($_my_inst)){
                if ($key == 'all') $num_all = $value["num_sem"];
                if ($value["num_sem"] == '0' && $num_all > 0 && $key != $_SESSION['zensus_admin']['institut_id'] ) continue;
                printf ("<option %s value=\"%s\" style=\"%s\">%s (%s)</option>\n",
                    ($key == $_SESSION['zensus_admin']['institut_id']) ? "selected" : "" , $key,($value["is_fak"] ? "font-weight:bold;" : ""),
                    htmlReady($value["name"]), $value["num_sem"]);

                if ($value["is_fak"] == 'all'){
                    $num_inst = $value["num_inst"];
                    for ($i = 0; $i < $num_inst; ++$i){
                        list($key,$value) = each($_my_inst);
                        if ($value["num_sem"] == '0') continue;
                        printf("<option %s value=\"%s\">&nbsp;&nbsp;&nbsp;&nbsp;%s (%s)</option>\n",
                            ($key == $_SESSION['zensus_admin']['institut_id']) ? "selected" : "", $key,
                            htmlReady($value["name"]), $value["num_sem"]);
                    }
                }
            }
            list($institut_id,) = explode('_', $_SESSION['zensus_admin']['institut_id']);
            if($institut_id == 'all') $institut_id = 'root';
            ?>
            </select>&nbsp;
            <?=SemesterData::GetSemesterSelector(array('name'=>'select_sem', 'style'=>'vertical-align:middle;'), $_SESSION['_default_sem'], 'semester_id', false)?>
            <?=Studip\Button::create(_('Auswählen'), "choose_institut")?>
            <?=Studip\Button::create(_('Zurücksetzen'), "reset_search")?>
            <br>
            <span style="font-size:80%;">
            ausgewählte ID: <span style="background-color:yellow;"><?=$institut_id?></span>
            </span>
            </div>
            <div style="font-size:10pt;margin:10px;">
            <b><?=_("Angezeigte Veranstaltungen einschränken:")?></b>
            <span style="margin-left:10px;font-size:10pt;">
            <input type="text" id="filter_name" name="filter_name" value="<?=htmlReady($_SESSION['zensus_admin']['filter_name'])?>" style="vertical-align:middle;">
            &nbsp;<label for="filter_name"><?=_("Name/Nummer der Veranstaltung")?></label>
            </span>
            <span style="margin-left:10px;font-size:10pt;">
            <input type="checkbox" id="check_eval" name="check_eval" <?=$_SESSION['zensus_admin']['check_eval'] ? 'checked' : ''?> value="1" style="vertical-align:middle;">
            &nbsp;<label for="check_eval"><?=_("Evaluation in Zensus aktiviert")?></label>
            </span>
            <span style="margin-left:10px;font-size:10pt;">
            <input type="checkbox" id="plugin_activated" name="plugin_activated" <?=$_SESSION['zensus_admin']['plugin_activated'] ? 'checked' : ''?> value="1" style="vertical-align:middle;">
            &nbsp;<label for="plugin_activated"><?=_("Plugin eingeschaltet")?></label>
            </span>
            </div>
            <div style="font-size:10pt;margin:10px;">
            <b><?=_("Angezeigte Veranstaltungen exportieren:")?></b>
            <?= \Studip\Button::create(_("Export"), 'export'); ?>
            </div>
            </form>
            <hr>
            <?
            $data = $this->getSeminareData($sem_condition);
            if (count($data)) {
                if($form->isClicked('switch')){
                    foreach($data as $seminar_id => $semdata) {
                        if(!isset($_REQUEST['sem_choosen'][$seminar_id])) $data[$seminar_id]['choosen'] = true;
                    }
                } else if(is_array($_REQUEST['sem_choosen'])){
                    foreach($data as $seminar_id => $semdata) {
                        if(isset($_REQUEST['sem_choosen'][$seminar_id])) $data[$seminar_id]['choosen'] = true;
                    }
                }
                echo chr(10).$form->getFormStart(PluginEngine::getLink($this));
                echo chr(10).'<div style="margin:10px;font-size:10pt;font-weight:bold">';
                echo _("Start- und Endzeiten für ausgewählte Veranstaltungen setzen:");
                echo chr(10). '</div>';
                echo chr(10).'<div style="margin:10px;font-size:10pt;">';
                echo  '<span>' . _("Startzeit:") . '</span>';
                echo chr(10) .'<span style="padding-left:10px;">' . $form->getFormField('starttime');
                echo '</span><span style="padding-left:10px;">'. $form->getFormButton('set_starttime', array('style' => 'vertical-align:middle'));
                echo chr(10). '</span></div>';
                echo chr(10).'<div style="margin:10px;font-size:10pt;">';
                echo '<span>' ._("Endzeit:") . '</span>';
                echo chr(10) .'<span style="padding-left:10px;">' . $form->getFormField('endtime');
                echo '</span><span style="padding-left:10px;">'. $form->getFormButton('set_endtime', array('style' => 'vertical-align:middle'));
                echo chr(10). '</span></div>';
                echo chr(10).'<div style="margin:10px;font-size:10pt;font-weight:bold">';
                echo _("Evaluationsplugin für ausgewählte Veranstaltungen ein/ausschalten:") .'</div>';
                echo chr(10).'<div style="margin:10px;font-size:10pt;">';
                echo chr(10) . $form->getFormField('plugin_status');
                echo '&nbsp;&nbsp;&nbsp;'. $form->getFormButton('set_plugin_status', array('style' => 'vertical-align:middle'));
                echo chr(10). '</div>';
                echo chr(10). '<a name="zensustable"></a>';
                echo "<table class=\"default\">";
                echo "<tr>";
                foreach($cols as $i => $col) {
                    echo "<th width=\"{$col[0]}%\" style=\"font-size:80%;\">";
                    if (!$i) {
                        echo '<input type="checkbox" onChange="jQuery(\'input[name^=sem_choosen]\').attr(\'checked\',this.checked);">';
                    } else {
                        if($col[1]){
                            echo '<a class="tree" href="';
                            echo PluginEngine::getLink($this,array('sortby' => $col[2], 'foo' => rand())) . '#zensustable';
                            echo '">'.$col[1].'&nbsp;';
                            if($col[2] == $_SESSION['zensus_admin']['sortby']['field']){
                                printf('<img src="%s/images/%s" border="0" align="top">', $this->getPluginUrl(),$_SESSION['zensus_admin']['sortby']['direction'] ? 'dreieck_up.png' : 'dreieck_down.png');
                            }
                            echo '</a>';
                        }
                    }
                    echo "</th>";
                }
                echo "</tr>";
            }
            foreach($data as $seminar_id => $semdata) {
                if($semdata['activated_by_sem'] == 'on' || ($semdata['activated_by_sem'] != 'off' && $semdata['activated_by_default'] == 'on')){
                    $plugin = PluginManager::getInstance()->getPluginById($this->zensuspluginid);
                    $plugin->setId($seminar_id);
                    $plugin->getCourseStatus();
                    $plugin->semester_id = $_SESSION['_default_sem'] ? $_SESSION['_default_sem'] : null;
                    if($_SESSION['zensus_admin']['check_eval'] && !in_array($plugin->course_status['status'], array('prepare','run','analyze','finished'))){
                        unset($data[$seminar_id]);
                        continue;
                    }
                    $data[$seminar_id]['link'] = "<a href=\"".PluginEngine::GetLink($plugin,array('cid' => $seminar_id)) . "\">"
                    . htmlReady($plugin->course_status['status'])."</a>";
                    $data[$seminar_id]['zensus_status'] = $plugin->course_status['status'];
                    $data[$seminar_id]['zensus_numvotes'] = $plugin->course_status['numvotes'];
                    $data[$seminar_id]['time_frame_begin'] = $plugin->course_status['time_frame']['begin'];
                    $data[$seminar_id]['time_frame_end'] = $plugin->course_status['time_frame']['end'];
                    $data[$seminar_id]['plugin_activated'] = true;
                } else {
                    $plugin = null;
                    if($_SESSION['zensus_admin']['check_eval'] || $_SESSION['zensus_admin']['plugin_activated']){
                        unset($data[$seminar_id]);
                        continue;
                    }
                    $data[$seminar_id]['plugin_activated'] = false;
                }
                $data[$seminar_id]['dozenten'] = join(', ',(array)$semdata['dozenten']);
                $sorter[$seminar_id] = $data[$seminar_id][$_SESSION['zensus_admin']['sortby']['field']];
            }
            if($_SESSION['zensus_admin']['sortby']['field'] && count($data) && count($data) == count($sorter)){
                array_multisort($sorter, ($_SESSION['zensus_admin']['sortby']['direction'] ? SORT_ASC : SORT_DESC), $data);
            }
            if (Request::submitted('export')) {
                ob_end_clean();
                $captions = array('Veranstaltung', 'Dozenten', 'Teilnehmer Stud.IP', 'Zensus Status', 'Teilnehmer Zensus', 'Plugin aktiv', 'Startzeit','Endzeit');
                $csvdata = array();
                $c = 0;
                foreach($data as $seminar_id => $semdata) {
                    $csvdata[$c][] = $semdata['Name'];
                    $csvdata[$c][] = $semdata['dozenten'];
                    $csvdata[$c][] = $semdata['teilnehmer_anzahl_aktuell'];
                    $csvdata[$c][] = $semdata['zensus_status'];
                    $csvdata[$c][] = (int)$semdata['zensus_numvotes'];
                    $csvdata[$c][] = $semdata['plugin_activated'] ? 'ja' : 'nein';
                    $csvdata[$c][] = $semdata['begin_evaluation'] ? date("d.m.Y", $semdata['begin_evaluation']) : ($semdata['time_frame_begin'] ? date("d.m.Y", $semdata['time_frame_begin']) : '');
                    $csvdata[$c][] = $semdata['end_evaluation'] ? date("d.m.Y", $semdata['end_evaluation']) : ($semdata['time_frame_end'] ? date("d.m.Y", $semdata['time_frame_end']) : '');
                    ++$c;
                }
                $tmpname = md5(uniqid('tmp'));
                if (array_to_csv($csvdata, $GLOBALS['TMP_PATH'] . '/' . $tmpname, $captions)) {
                    header('Location: ' . GetDownloadLink($tmpname, 'Veranstaltungen_Lehrevaluation.csv', 4, 'force'));
                    page_close();
                    die();
                }
            }
            $semlink = $GLOBALS['perm']->have_studip_perm('admin', $_SESSION['zensus_admin']['institut_id']) ? 'seminar_main.php?auswahl=' : 'details.php?sem_id=';
            foreach($data as $seminar_id => $semdata) {
                $sem = new Seminar($seminar_id);
                $dates = $sem->getDatesExport(array(
                    'semester_id' => $_SESSION['_default_sem'],
                    'show_room'   => false
                ));
                echo "<tr class=\"" . TextHelper::cycle('hover_odd', 'hover_even') . "\">\n";
                echo '<td align="center"><input type="checkbox" name="sem_choosen['.$seminar_id.']" value="1" '.($semdata['choosen'] ? 'checked':'').'></td>';
                printf ("<td>
                    <a title=\"%s\" href=\"%s\">
                    %s
                    </a></td>
                    <td>
                    <a title=\"%s\" href=\"%s\">
                    %s%s%s
                    </a></td>
                    <td align=\"center\">
                    %s</td>
                    <td align=\"center\">%s</td>
                    <td align=\"center\">%s</td>
                    <td align=\"center\">%s</td>
                    <td align=\"center\">%s</td>
                    <td align=\"center\">%s</td>
                    <td align=\"center\">%s</td>
                    <td align=\"center\">%s</td>
                    <td align=\"center\">%s</td>
                    ",
                    htmlready($dates),
                    UrlHelper::getLink($semlink.$seminar_id),
                    htmlready($semdata['VeranstaltungsNummer']),
                    htmlready($dates),
                    UrlHelper::getLink($semlink.$seminar_id),
                    htmlready(substr($semdata['Name'], 0, 60)),
                    (strlen($semdata['Name'])>60) ? "..." : "",
                    !$semdata['visible'] ? ' ' . _("(versteckt)") : '',
                    htmlReady($semdata['dozenten']),
                    htmlReady($semdata['teilnehmer_anzahl_aktuell']),
                    $semdata['link'],
                    htmlReady($semdata['zensus_numvotes']),
                    ($semdata['plugin_activated'] ? 'ja' : 'nein') ,
                    $semdata['begin_evaluation'] ? date("d.m.Y", $semdata['begin_evaluation']) : '-',
                    $semdata['end_evaluation'] ? date("d.m.Y", $semdata['end_evaluation']) : '-',
                    ($semdata['time_frame_begin'] ? date("d.m.Y", $semdata['time_frame_begin']) : '-'),
                    ($semdata['time_frame_end'] ? date("d.m.Y", $semdata['time_frame_end']) : '-')
                    );
                echo "</tr>";
            }
            echo "</table>";
            echo $form->getFormEnd();
            if ($_SESSION['zensus_admin']['institut_id'] && !count($data)) {
                echo MessageBox::info(_("Im gewählten Bereich existieren keine Veranstaltungen"));
            }
        } else {
            echo MessageBox::info(_("Sie wurden noch keinen Einrichtungen zugeordnet."));
        }

        PageLayout::setTitle($this->getDisplayname());

        $layout = $GLOBALS['template_factory']->open('layouts/base_without_infobox');

        $layout->content_for_layout = ob_get_clean();

        echo $layout->render();
    }

    function getInstitute($seminare_condition){
        global $perm, $user,$_default_sem;
        $db = new DB_Seminar();
        $db2 = new DB_Seminar();
        if($perm->have_perm('root')){
            $db->query("SELECT COUNT(*) FROM seminare WHERE 1 $seminare_condition");
            $db->next_record();
            $_my_inst['all'] = array("name" => _("alle") , "num_sem" => $db->f(0));
            $db->query("SELECT a.Institut_id,a.Name, 1 AS is_fak, count(seminar_id) AS num_sem FROM Institute a
                LEFT JOIN seminare ON(seminare.Institut_id=a.Institut_id $seminare_condition  ) WHERE a.Institut_id=fakultaets_id GROUP BY a.Institut_id ORDER BY is_fak,Name,num_sem DESC");
        } else {
            $db->query("SELECT a.Institut_id,b.Name, IF(b.Institut_id=b.fakultaets_id,1,0) AS is_fak,count(seminar_id) AS num_sem FROM user_inst a LEFT JOIN Institute b USING (Institut_id)
                LEFT JOIN seminare ON(seminare.Institut_id=b.Institut_id $seminare_condition  )    WHERE a.user_id='$user->id' AND a.inst_perms='admin' GROUP BY a.Institut_id ORDER BY is_fak,Name,num_sem DESC");
        }
        while($db->next_record()){
            $_my_inst[$db->f("Institut_id")] = array("name" => $db->f("Name"), "is_fak" => $db->f("is_fak"), "num_sem" => $db->f("num_sem"));
            if ($db->f("is_fak")){
                $_my_inst[$db->f("Institut_id").'_all'] = array("name" => '[Alle unter '.$db->f("Name").']', "is_fak" => 'all', "num_sem" => $db->f("num_sem"));
                $db2->query("SELECT a.Institut_id, a.Name,count(seminar_id) AS num_sem FROM Institute a
                    LEFT JOIN seminare ON(seminare.Institut_id=a.Institut_id $seminare_condition  ) WHERE fakultaets_id='" . $db->f("Institut_id") . "' AND a.Institut_id!='" .$db->f("Institut_id") . "'
                    GROUP BY a.Institut_id ORDER BY a.Name,num_sem DESC");
                $num_inst = 0;
                $num_sem_alle = $db->f("num_sem");
                while ($db2->next_record()){
                    if(!$_my_inst[$db2->f("Institut_id")]){
                        ++$num_inst;
                        $num_sem_alle += $db2->f("num_sem");
                    }
                    $_my_inst[$db2->f("Institut_id")] = array("name" => $db2->f("Name"), "is_fak" => 0 , "num_sem" => $db2->f("num_sem"));
                }
                $_my_inst[$db->f("Institut_id")]["num_inst"] = $num_inst;
                $_my_inst[$db->f("Institut_id").'_all']["num_inst"] = $num_inst;
                $_my_inst[$db->f("Institut_id").'_all']["num_sem"] = $num_sem_alle;
            }
        }
        return $_my_inst;
    }

    function getSeminareData($seminare_condition){
        global $perm;
        $db = new DB_Seminar();
        $db2 = new DB_Seminar();
        $datafield1 = md5('UNIZENSUSPLUGIN_BEGIN_EVALUATION');
        $datafield2 = md5('UNIZENSUSPLUGIN_END_EVALUATION');
        $pluginid = $this->zensuspluginid;

        $ret = array();
        list($institut_id, $all) = explode('_', $_SESSION['zensus_admin']['institut_id']);
        if ($institut_id == "all"  && $perm->have_perm("root"))
            $query = "SELECT Name,Seminar_id as seminar_id, VeranstaltungsNummer, visible FROM seminare WHERE 1 $seminare_condition ORDER BY Name";
        elseif ($all == 'all')
            $query = "SELECT seminare.Name,seminare.Seminar_id as seminar_id, seminare.VeranstaltungsNummer, seminare.visible FROM seminare LEFT JOIN seminar_inst USING (Institut_id)
        INNER JOIN Institute ON seminar_inst.institut_id = Institute.Institut_id WHERE Institute.fakultaets_id  = '{$institut_id}' $seminare_condition
        GROUP BY seminare.Seminar_id ORDER BY Name";
        else
        $query = "SELECT seminare.Name,seminare.Seminar_id as seminar_id, seminare.VeranstaltungsNummer, seminare.visible FROM seminare LEFT JOIN seminar_inst USING (Institut_id)
        WHERE seminar_inst.institut_id = '{$institut_id}' $seminare_condition
        GROUP BY seminare.Seminar_id ORDER BY Name";
        $db->query($query);
        while($db->next_record()){
            $seminar_id = $db->f("seminar_id");
            $ret[$seminar_id] = $db->Record;
            $query2 = "SELECT seminar_user.user_id,username,Nachname FROM seminar_user LEFT JOIN auth_user_md5 USING (user_id) WHERE seminar_id='$seminar_id' AND status='dozent' ORDER BY position,Nachname";
            $db2->query($query2);
            $c = 0;
            while($db2->next_record()){
                $ret[$seminar_id]['dozenten'][$db2->f('username')] = $db2->f('Nachname');
                if(++$c > 2) {
                    $ret[$seminar_id]['dozenten'][] = '...';
                    break;
                }
            }
            $query2 = "SELECT COUNT(*) FROM seminar_user WHERE seminar_id='$seminar_id' AND status IN ('autor')";
            $db2->query($query2);
            $db2->next_record();
            $ret[$seminar_id]['teilnehmer_anzahl_aktuell'] = $db2->f(0);
            $query2 = "SELECT datafield_id,content FROM datafields_entries WHERE range_id='$seminar_id' AND datafield_id IN('$datafield1','$datafield2')";
            $db2->query($query2);
            while($db2->next_record()){
                if($db2->f('datafield_id') == $datafield1) $ret[$seminar_id]['begin_evaluation'] = UniZensusPlugin::SQLDateToTimestamp($db2->f('content'));
                if($db2->f('datafield_id') == $datafield2) $ret[$seminar_id]['end_evaluation'] = UniZensusPlugin::SQLDateToTimestamp($db2->f('content'));
            }
            $query2 = "SELECT state, 'sem' AS activated_by
            FROM plugins_activated pat
            WHERE pat.pluginid = '$pluginid'
            AND pat.poiid = 'sem$seminar_id'
            UNION SELECT 'on', 'default'
            FROM seminar_inst s
            JOIN Institute i ON i.Institut_id = s.institut_id
            JOIN plugins_default_activations pa ON i.fakultaets_id = pa.institutid
            OR i.Institut_id = pa.institutid
            JOIN plugins p ON pa.pluginid = p.pluginid
            WHERE s.seminar_id = '$seminar_id'
            AND p.pluginid = '$pluginid'";
            $db2->query($query2);
            while($db2->next_record()){
                $ret[$seminar_id]['activated_by_' . $db2->f('activated_by')] = $db2->f('state');
            }
        }
        return $ret;
    }

    function getExportData($key, $seminar_id)
    {
        static $data = array();
        if (!$data[$seminar_id]) {
            $data = array();
            $data[$seminar_id] = UniZensusPlugin::getAdditionalExportData($seminar_id);
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
        if ($key == 'resultstore') {
            return zensus_xmltag($key, (int)$data[$seminar_id]['eval_stored']);
        }
    }

    function export_action()
    {
        global $ex_sem, $ex_only_homeinst,$ex_sem_class, $ex_only_visible;

        global $xml_groupnames_fak,$xml_names_fak,$xml_groupnames_inst,$xml_names_inst
        ,$xml_groupnames_lecture,$xml_names_lecture,$xml_groupnames_person
        ,$xml_names_person,$xml_groupnames_studiengaenge,$xml_names_studiengaenge;

        require_once ('lib/export/export_xml_vars.inc.php');   // XML-Variablen

        //Uni OL
        /*
        $xml_names_lecture['teilnehmer_anzahl_aktuell'] = array($this, 'getExportData');
        $xml_names_lecture['resultpublic'] = array($this, 'getExportData');
        $xml_names_lecture['resultstore'] = array($this, 'getExportData');
        */

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
        zensus_export_range($range_id, $ex_sem, 'direct',$auth_uid);
    }

    public static function onEnable($plugin_id)
    {
        //allow for nobody
        $rp = new RolePersistence();
        $rp->assignPluginRoles($plugin_id, range(1,7));
    }

}