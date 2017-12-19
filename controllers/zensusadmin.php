<?php
class ZensusadminController extends PluginController
{

    function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);
        if (Request::option("institut_id")) {
            $GLOBALS['user']->cfg->store("MY_INSTITUTES_DEFAULT", Request::option("institut_id"));
        }
        if (Request::option("semester_id")) {
            $GLOBALS['user']->cfg->store("MY_COURSES_SELECTED_CYCLE", Request::option("semester_id"));
        }
        if (!$GLOBALS['perm']->have_perm("root")) {
            $institut_ids = array_map(function ($data) {
                return $data['Institut_id'];
            }, Institute::getMyInstitutes());
            if (!in_array($GLOBALS['user']->cfg->MY_INSTITUTES_DEFAULT, $institut_ids)) {
                $GLOBALS['user']->cfg->store("MY_INSTITUTES_DEFAULT", $institut_ids[0]);
            }
        }
    }

    public function changestatus_action()
    {
        $GLOBALS['perm']->check('admin');
        CSRFProtection::verifyUnsafeRequest();
        $uplugin = PluginManager::getInstance()->getPlugin('UniZensusPlugin');
        $datafield_id = UniZensusPlugin::$datafield_id_vorgesehen;
        $db = DBManager::get();
        if ($datafield_id) {
            $inserted = 0;
            $deleted = $db->execute("DELETE FROM datafields_entries WHERE datafield_id=? AND range_id IN (?)", [$datafield_id, Request::optionArray('all_sem')]);
            foreach (Request::optionArray('zensus') as $range_id => $d) {
                $inserted += $db->execute("INSERT INTO datafields_entries (datafield_id,range_id,content,chdate) VALUES (?,?,'1',UNIX_TIMESTAMP())", [$datafield_id, $range_id]);
            }
            PageLayout::postSuccess(_("Änderungen gespeichert."));
        }
        $this->redirect(URLHelper::getURL('dispatch.php/admin/courses'));
    }

    public function selection_action()
    {
        $GLOBALS['perm']->check('admin');
        Navigation::activateItem('/browse/my_courses/zensusadmin_selection');
        PageLayout::addSqueezePackage('tablesorter');
        $this->institut = Institute::find($GLOBALS['user']->cfg->MY_INSTITUTES_DEFAULT);
        $this->semester = Semester::find($GLOBALS['user']->cfg->MY_COURSES_SELECTED_CYCLE);
        $this->data = $this->getSeminareData($this->institut, $this->semester);
        $this->datafield_fb = DataField::find(UniZensusPlugin::$datafield_id_fb);
        $this->datafield_form = DataField::find(UniZensusPlugin::$datafield_id_form);

    }

    public function status_action()
    {
        if (!$this->plugin->user_is_eval_admin) {
            throw new AccessDeniedException();
        }
        Navigation::activateItem('/browse/my_courses/zensusadmin_status');



    }

    private function getSeminareData($institut_id, $semester_id)
    {
        $institut = is_object($institut_id) ? $institut_id : Institute::find($institut_id);
        $semester = is_object($semester_id) ? $semester_id : Semester::find($semester_id);
        $datafield_id = UniZensusPlugin::$datafield_id_vorgesehen;

        $data = [];
        $db = DBManager::get();

        if ($institut && $semester) {
            $seminare_condition = " AND seminare.start_time <=".(int)$semester["beginn"]." AND (".(int)$semester["beginn"]." <= (seminare.start_time + seminare.duration_time) OR seminare.duration_time = -1) ";
            if ($institut->is_fak) {
                $query = "SELECT seminare.Name as name,seminare.Seminar_id as seminar_id, seminare.VeranstaltungsNummer as nr, seminare.Institut_id as institut_id FROM seminare LEFT JOIN seminar_inst USING (Institut_id)
        INNER JOIN Institute ON seminar_inst.institut_id = Institute.Institut_id
        INNER JOIN datafields_entries ON range_id = seminare.seminar_id AND datafield_id = '{$datafield_id}' AND content = '1'
         WHERE Institute.fakultaets_id  =  '{$institut->id}'  $seminare_condition
        GROUP BY seminare.Seminar_id ORDER BY VeranstaltungsNummer,Name";
            } else {
                $query = "SELECT seminare.Name as name,seminare.Seminar_id as seminar_id, seminare.VeranstaltungsNummer as nr, seminare.Institut_id as institut_id FROM seminare LEFT JOIN seminar_inst USING (Institut_id)
INNER JOIN datafields_entries ON range_id = seminare.seminar_id AND datafield_id = '{$datafield_id}' AND content = '1'
        WHERE seminar_inst.institut_id = '{$institut->id}'  $seminare_condition
        GROUP BY seminare.Seminar_id ORDER BY VeranstaltungsNummer,Name";
            }
            $institutes = [$institut->id => $institut];
            foreach ($db->fetchAll($query) as $r) {
                $course_id = $r['seminar_id'];
                $data[$course_id] = $r;
                if (!isset($institutes[$r['institut_id']])) {
                    $in = Institute::find($r['institut_id']);
                    $institutes[$in->id] = $in;
                }
                $data[$course_id]['institute'] = (string)$institutes[$r['institut_id']]->name;
                $dozenten = new SimpleCollection(CourseMember::findByCourseAndStatus($course_id, 'dozent'));
                $data[$course_id]['dozenten'] = $dozenten->getUserFullname('no_title_rev');
                $data[$course_id]['wdhl'] = @DatafieldEntryModel::find([UniZensusPlugin::$datafield_id_wdhl,$course_id,''])->content ? 'x' : '';
                $data[$course_id]['fb'] = @DatafieldEntryModel::find([UniZensusPlugin::$datafield_id_fb,$course_id,''])->content;
                $data[$course_id]['form'] = @DatafieldEntryModel::find([UniZensusPlugin::$datafield_id_form,$course_id,''])->content;
                $data[$course_id]['sprache'] = @DatafieldEntryModel::find([UniZensusPlugin::$datafield_id_sprache,$course_id,''])->content;
                $uplugin = PluginManager::getInstance()->getPlugin('UniZensusPlugin');
                if ($uplugin) {
                    $uplugin->setId($course_id);
                    list($starttime,$endtime) = $uplugin->getCourseEvaluationTimeframe();
                    $data[$course_id]['eval_start_time'] = $starttime ? strftime('%x', $starttime) : '';
                    $data[$course_id]['eval_end_time'] = $endtime ? strftime('%x', $endtime) : '';
                }
            }
            return $data;
        }
        /*
        global $perm;
        $db = new DB_Seminar();
        $db2 = new DB_Seminar();
        $datafield1 = md5('UNIZENSUSPLUGIN_BEGIN_EVALUATION');
        $datafield2 = md5('UNIZENSUSPLUGIN_END_EVALUATION');
        $pluginid = $this->zensuspluginid;

        $ret = array();
        list($institut_id, $all) = explode('_', $_SESSION['zensus_admin']['institut_id']);
        if ($institut_id == "all"  && $perm->have_perm("root"))
            $query = "SELECT Name,Seminar_id as seminar_id, VeranstaltungsNummer, visible FROM seminare WHERE 1 $seminare_condition ORDER BY VeranstaltungsNummer,Name";
        elseif ($all == 'all')
            $query = "SELECT seminare.Name,seminare.Seminar_id as seminar_id, seminare.VeranstaltungsNummer, seminare.visible FROM seminare LEFT JOIN seminar_inst USING (Institut_id)
        INNER JOIN Institute ON seminar_inst.institut_id = Institute.Institut_id WHERE Institute.fakultaets_id  = '{$institut_id}' $seminare_condition
        GROUP BY seminare.Seminar_id ORDER BY VeranstaltungsNummer,Name";
        else
            $query = "SELECT seminare.Name,seminare.Seminar_id as seminar_id, seminare.VeranstaltungsNummer, seminare.visible FROM seminare LEFT JOIN seminar_inst USING (Institut_id)
        WHERE seminar_inst.institut_id = '{$institut_id}' $seminare_condition
        GROUP BY seminare.Seminar_id ORDER BY VeranstaltungsNummer,Name";
        $db->query($query);
        while($db->next_record()){
            $seminar_id = $db->f("seminar_id");
            $ret[$seminar_id] = $db->Record;
            $ret[$seminar_id]['Name'] = $ret[$seminar_id]['VeranstaltungsNummer'] . ' ' . $ret[$seminar_id]['Name'];
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
            $ret[$seminar_id] = array_merge($ret[$seminar_id], UniZensusPlugin::getAdditionalExportData($seminar_id));
            if ($ret[$seminar_id]['eval_participants']) {
                $ret[$seminar_id]['teilnehmer_anzahl_aktuell'] = $ret[$seminar_id]['eval_participants'];
            }
            unset($ret[$seminar_id]['eval_participants']);
        }
        return $ret;
        */
    }
}
