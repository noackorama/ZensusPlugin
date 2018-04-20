<?php
class ZensusadminController extends PluginController
{

    function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);
        $this->filter =& $_SESSION[__CLASS__]['filter'];
        if (Request::option("institut_id")) {
            $GLOBALS['user']->cfg->store("MY_INSTITUTES_DEFAULT", Request::option("institut_id"));
        }
        if (Request::option("semester_id")) {
            $GLOBALS['user']->cfg->store("MY_COURSES_SELECTED_CYCLE", Request::option("semester_id"));
        }
        if (!Semester::find($GLOBALS['user']->cfg->MY_COURSES_SELECTED_CYCLE)) {
            $GLOBALS['user']->cfg->MY_COURSES_SELECTED_CYCLE = Semester::findCurrent()->id;
        }
        if (!$this->plugin->user_is_eval_admin) {
            $z_role = current(array_filter(RolePersistence::getAssignedRoles(User::findCurrent()->id), function ($r) {
                return $r->rolename == 'eval_agent';
            }));
            $z_institutes = array_filter(RolePersistence::getAssignedRoleInstitutes(User::findCurrent()->id, $z_role->roleid));
            $this->z_institutes = $z_institutes;
            if (!count($z_institutes)) {
                if (!$GLOBALS['user']->cfg->MY_INSTITUTES_DEFAULT) {
                    $GLOBALS['user']->cfg->MY_INSTITUTES_DEFAULT = 'all';
                }
            } else {
                if (!in_array($GLOBALS['user']->cfg->MY_INSTITUTES_DEFAULT, $z_institutes)) {
                    $GLOBALS['user']->cfg->store("MY_INSTITUTES_DEFAULT", $z_institutes[0]);
                    $this->filter['institute'] = $z_institutes[0];
                }
            }
        } else {
            if (!$GLOBALS['user']->cfg->MY_INSTITUTES_DEFAULT) {
                $GLOBALS['user']->cfg->MY_INSTITUTES_DEFAULT = 'all';
            }
            if (Request::submitted('toggle_zensus_active')) {
                $this->filter['zensus_activated'] = $this->filter['zensus_activated'] ? 0 : 1;
                $this->filter['zensus_deactivated'] = 0;
            }
            if (Request::submitted('toggle_zensus_nonactive')) {
                $this->filter['zensus_activated'] = 0;
                $this->filter['zensus_deactivated'] = $this->filter['zensus_deactivated'] ? 0 : 1;
            }
            if (Request::submitted('toggle_plugin_active')) {
                $this->filter['plugin_activated'] = $this->filter['plugin_activated'] ? 0 : 1;
            }
        }
        $this->filter['institute'] = $GLOBALS['user']->cfg->MY_INSTITUTES_DEFAULT;

    }

    public function changestatus_action()
    {
        $GLOBALS['perm']->check('admin');
        CSRFProtection::verifyUnsafeRequest();
        $datafield_id = UniZensusPlugin::$datafield_id_markiert;
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
        if (!$this->plugin->user_is_eval_agent) {
            throw new AccessDeniedException();
        }

        $this->semester = Semester::find($GLOBALS['user']->cfg->MY_COURSES_SELECTED_CYCLE);
        $this->datafield_fb = DataField::find(UniZensusPlugin::$datafield_id_fb);
        $this->datafield_form = DataField::find(UniZensusPlugin::$datafield_id_form);
        $this->datafield_wdhl = DataField::find(UniZensusPlugin::$datafield_id_wdhl);
        $this->datafield_sprache = DataField::find(UniZensusPlugin::$datafield_id_sprache);

        if (Request::submitted('mail')) {
            return $this->mail_action();
        }
        if (count(Request::getArray('selected_courses'))) {
            $this->courses = array();
            foreach(Request::getArray('selected_courses') as $key => $value) {
                if (!@DatafieldEntryModel::find([UniZensusPlugin::$datafield_id_vorgesehen, $key,''])->content) {
                    $this->courses[$key] = $value;
                }
            }
            if (count(Request::getArray('selected_courses')) != count($this->courses)) {
                PageLayout::postInfo(_('Grün hinterlegte Veranstaltungen können nicht bearbeitet werden!'));
            }
        }
        if (Request::submitted('set_timespan')) {
            return $this->set_timespan_action();
        }
        if (Request::submitted('set_frage')) {
            return $this->set_frage_action();
        }
        if (Request::submitted('set_wdhl')) {
            return $this->set_wdhl_action();
        }
        if (Request::submitted('set_form')) {
            return $this->set_form_action();
        }
        if (Request::submitted('set_sprache')) {
            return $this->set_sprache_action();
        }
        Navigation::activateItem('/browse/my_courses/zensusadmin_selection');
        PageLayout::addSqueezePackage('tablesorter');

        $this->data = $this->getSeminareData($this->plugin->user_is_eval_admin ? $this->filter['institute'] :  $GLOBALS['user']->cfg->MY_INSTITUTES_DEFAULT, $this->semester);
        if (Request::submitted('export')) {
            $captions = array('Institut', 'Nr.', 'Titel', 'Lehrende', 'TN', 'Status Evaluation', 'Form der Teilnahme', 'Sprache', 'Art des Fragebogens', 'Wdh', 'Erhebungszeitraum Start', 'Erhebungszeitraum Ende');
            $csvdata = array();
            $c = 0;
            foreach($this->data as $course_id => $r) {
                $csvdata[$c][] = $r['institute'];
                $csvdata[$c][] = $r['nr'];
                $csvdata[$c][] = $r['name'];
                $csvdata[$c][] = join(',', $r['dozenten']);
                $csvdata[$c][] = $r['teilnehmer_anzahl_aktuell'];
                $csvdata[$c][] = $r['evaluation'] ? 'Ja' : 'Nein';
                $csvdata[$c][] = DataFieldEntry::createDataFieldEntry($this->datafield_fb, $course_id, $r['fb'])->getDisplayValue();
                $csvdata[$c][] = DataFieldEntry::createDataFieldEntry($this->datafield_sprache, $course_id, $r['sprache'])->getDisplayValue();
                $csvdata[$c][] = DataFieldEntry::createDataFieldEntry($this->datafield_form, $course_id, $r['form'])->getDisplayValue();
                $csvdata[$c][] = $r['wdhl'] ? 'Ja' : 'Nein';
                $csvdata[$c][] = $r['eval_start_time'];
                $csvdata[$c][] = $r['eval_end_time'];
                ++$c;
            }
            $tmpname = md5(uniqid('tmp'));
            if (array_to_csv($csvdata, $GLOBALS['TMP_PATH'] . '/' . $tmpname, $captions)) {
                $this->redirect(
                    FileManager::getDownloadURLForTemporaryFile(
                        $tmpname,
                        'Veranstaltungen_Lehrevaluation_Auswahl.csv'
                    )
                );
                return;
            }
        }


    }

    public function status_action()
    {
        if (!$this->plugin->user_is_eval_admin) {
            throw new AccessDeniedException();
        }
        if (Request::submitted('mail')) {
            return $this->mail_action();
        }
        $this->courses = Request::getArray('selected_courses');
        if (Request::submitted('activate_plugin')) {
            return $this->activate_plugin_action();
        }
        if (Request::submitted('set_timespan')) {
            return $this->set_timespan_action();
        }
        PageLayout::addSqueezePackage('tablesorter');
        $this->semester = Semester::find($GLOBALS['user']->cfg->MY_COURSES_SELECTED_CYCLE);
        Navigation::activateItem('/browse/my_courses/zensusadmin_status');
        $this->data = $this->getSeminareData($this->filter['institute'], $this->semester, true, $this->filter);


        if (Request::submitted('export')) {
            $captions = array('Nr.', 'Titel', 'Lehrende', 'TN', 'Zensus TN', 'Zensus Status', 'Übertraggun an Zensus', 'Ergebnis', 'Studierenden', 'Erhebungszeitraum Start', 'Erhebungszeitraum Ende');
            $csvdata = array();
            $c = 0;
            foreach ($this->data as $course_id => $r) {
                $csvdata[$c][] = $r['nr'];
                $csvdata[$c][] = $r['name'];
                $csvdata[$c][] = join(',', $r['dozenten']);
                $csvdata[$c][] = $r['teilnehmer_anzahl_aktuell'];
                $csvdata[$c][] = $r['zensus_numvotes'];
                $csvdata[$c][] = $r['zensus_status'];
                $csvdata[$c][] = $r['evaluation'] ? 'Ja' : 'Nein';
                $csvdata[$c][] = $r['eval_public'];
                $csvdata[$c][] = $r['eval_public_stud'];
                $csvdata[$c][] = $r['eval_start_time'];
                $csvdata[$c][] = $r['eval_end_time'];
                ++$c;
            }
            $tmpname = md5(uniqid('tmp'));
            if (array_to_csv($csvdata, $GLOBALS['TMP_PATH'] . '/' . $tmpname, $captions)) {
                $this->redirect(
                    FileManager::getDownloadURLForTemporaryFile(
                        $tmpname,
                        'Veranstaltungen_Lehrevaluation_Status.csv'
                    )
                );
                return;
            }
        }

    }

    public function set_timespan_action($return_to = 'status')
    {
        if (!$this->plugin->user_is_eval_agent) {
            throw new AccessDeniedException();
        }
        if (Request::submitted('save')) {
            CSRFProtection::verifyUnsafeRequest();
            $db = DBManager::get();
            $ok = 0;
            if (Request::get('autodate')) {
                $ok += $db->execute("DELETE FROM datafields_entries WHERE range_id IN (?) AND datafield_id=?", [array_keys(Request::getArray('selected_courses')), md5('UNIZENSUSPLUGIN_BEGIN_EVALUATION')]);
                $ok += $db->execute("DELETE FROM datafields_entries WHERE range_id IN (?) AND datafield_id=?", [array_keys(Request::getArray('selected_courses')), md5('UNIZENSUSPLUGIN_END_EVALUATION')]);
            } else {
                $startdate = Request::get('startdate') ? strftime('%Y-%m-%d', strtotime(Request::get('startdate'))) : null;
                $enddate = Request::get('enddate') ? strftime('%Y-%m-%d', strtotime(Request::get('enddate'))) : null;

                foreach (array_keys(Request::getArray('selected_courses')) as $seminar_id) {
                    if ($startdate) {
                        $ok += $db->execute("REPLACE INTO datafields_entries (range_id, datafield_id, content, chdate) VALUES (?,?,?,UNIX_TIMESTAMP())", [$seminar_id, md5('UNIZENSUSPLUGIN_BEGIN_EVALUATION'), $startdate]);
                    }
                    if ($enddate) {
                        $ok += $db->execute("REPLACE INTO datafields_entries (range_id, datafield_id, content, chdate) VALUES (?,?,?,UNIX_TIMESTAMP())", [$seminar_id, md5('UNIZENSUSPLUGIN_END_EVALUATION'), $enddate]);
                    }
                }
            }
            if ($ok) {
                PageLayout::postSuccess(_("Start- Endzeiten wurden geändert."));
            }
            return $this->redirect($this->url_for('/' . $return_to));
        }
        $this->render_template('zensusadmin/set_timespan');
    }

    public function activate_plugin_action()
    {
        if (!$this->plugin->user_is_eval_admin) {
            throw new AccessDeniedException();
        }
        if (Request::submitted('save')) {
            CSRFProtection::verifyUnsafeRequest();
            $set_to_status = Request::get('plugin_active') ? 'on' : 'off';
            $db = DBManager::get();
            $ok = 0;
            foreach(array_keys(Request::getArray('selected_courses')) as $seminar_id) {
                $ok += $db->execute("REPLACE INTO plugins_activated (pluginid,poiid,state) VALUES (?,?,?)",
                    [$this->plugin->zensuspluginid, 'sem' . $seminar_id, $set_to_status]);
                if ($set_to_status === 'off') {
                    $db->execute("DELETE FROM datafields_entries WHERE datafield_id = ? AND range_id = ?", [UniZensusPlugin::$datafield_id_vorgesehen, $seminar_id]);
                } else {
                    $db->execute("REPLACE INTO datafields_entries (content,datafield_id,range_id,sec_range_id,chdate) VALUES (?,?,?,'',UNIX_TIMESTAMP())", ['1', UniZensusPlugin::$datafield_id_vorgesehen , $seminar_id]);
                }
            }
            if ($ok) {
                PageLayout::postSuccess(_("Pluginstatus wurde geändert."));
            }
            return $this->redirect($this->url_for('/status'));
        }
        $this->render_template('zensusadmin/activate_plugin');
    }

    public function set_frage_action()
    {
        if (!$this->plugin->user_is_eval_agent) {
            throw new AccessDeniedException();
        }
        $this->datafield_form = DataField::find(UniZensusPlugin::$datafield_id_form);
        if (Request::submitted('save')) {
            CSRFProtection::verifyUnsafeRequest();
            $stored = false;
            $submit_value = $_REQUEST['set_frage'][UniZensusPlugin::$datafield_id_form];
            foreach (array_keys(Request::getArray('selected_courses')) as $course_id) {
                $df = DataFieldEntry::createDataFieldEntry($this->datafield_form, $course_id);
                $df->setValueFromSubmit($submit_value);
                $stored += $df->store();
            }
            if ($stored) {
                PageLayout::postSuccess(_("Art des Fragebogens gespeichert."));
            }

            return $this->redirect($this->url_for('/selection'));
        }
        $this->render_template('zensusadmin/set_frage');
    }

    public function set_sprache_action()
    {
        if (!$this->plugin->user_is_eval_agent) {
            throw new AccessDeniedException();
        }
        $this->datafield_sprache = DataField::find(UniZensusPlugin::$datafield_id_sprache);
        if (Request::submitted('save')) {
            CSRFProtection::verifyUnsafeRequest();
            $stored = false;
            $submit_value = $_REQUEST['set_sprache'][UniZensusPlugin::$datafield_id_sprache];
            foreach (array_keys(Request::getArray('selected_courses')) as $course_id) {
                $df = DataFieldEntry::createDataFieldEntry($this->datafield_sprache, $course_id);
                $df->setValueFromSubmit($submit_value);
                $stored += $df->store();
            }
            if ($stored) {
                PageLayout::postSuccess(_("Sprache gespeichert."));
            }

            return $this->redirect($this->url_for('/selection'));
        }
        $this->render_template('zensusadmin/set_sprache');
    }

    public function set_wdhl_action()
    {
        if (!$this->plugin->user_is_eval_agent) {
            throw new AccessDeniedException();
        }
        $this->institute = Institute::find($this->filter['institute']);
        if (!$this->institute) {
            return $this->render_text(MessageBox::info(_("Bitte wählen sie eine Einrichtung aus!")));
        }
        $this->semester = Semester::find($GLOBALS['user']->cfg->MY_COURSES_SELECTED_CYCLE);
        if (Request::submitted('save') && $this->institute && $this->semester) {
            CSRFProtection::verifyUnsafeRequest();
            $db = DBManager::get();
            $ok = 0;
            $courses = array_keys($this->getSeminareData($this->plugin->user_is_eval_admin ? $this->filter['institute'] :  $GLOBALS['user']->cfg->MY_INSTITUTES_DEFAULT, $this->semester));
            if (Request::get('wdhl') == 1) {
                foreach ($courses as $range_id) {
                    $ok += $db->execute("REPLACE INTO datafields_entries (datafield_id,range_id,content,chdate) VALUES (?,?,'1',UNIX_TIMESTAMP())", [UniZensusPlugin::$datafield_id_wdhl, $range_id]);
                }
            } else {
                $ok = $db->execute("DELETE FROM datafields_entries WHERE datafield_id=? AND range_id IN (?)", [UniZensusPlugin::$datafield_id_wdhl, $courses]);
            }
            if ($ok) {
                PageLayout::postSuccess(_("Wiederholung für alle Veranstaltungen der Einrichtung wurde geändert."));
            }
            return $this->redirect($this->url_for('/selection'));
        }
        $this->render_template('zensusadmin/set_wdhl');
    }

    public function set_form_action()
    {
        if (!$this->plugin->user_is_eval_agent) {
            throw new AccessDeniedException();
        }
        $this->datafield_fb = DataField::find(UniZensusPlugin::$datafield_id_fb);
        if (Request::submitted('save')) {
            CSRFProtection::verifyUnsafeRequest();
            $stored = false;
            $submit_value = $_REQUEST['set_form'][UniZensusPlugin::$datafield_id_fb];
            foreach (array_keys(Request::getArray('selected_courses')) as $course_id) {
                $df = DataFieldEntry::createDataFieldEntry($this->datafield_fb, $course_id);
                $df->setValueFromSubmit($submit_value);
                $stored += $df->store();
            }
            if ($stored) {
                PageLayout::postSuccess(_("Form der Teilnahme gespeichert."));
            }

            return $this->redirect($this->url_for('/selection'));
        }
        $this->render_template('zensusadmin/set_form');
    }

    public function token_action()
    {
        if (!$this->plugin->user_is_eval_admin) {
            throw new AccessDeniedException();
        }
        if (Request::submitted('generate_token')) {
            UserConfig::get($GLOBALS['user']->id)->store('UNIZENSUSPLUGIN_AUTH_TOKEN', md5(uniqid('ZensusToken',1)));
            PageLayout::postSuccess(_("Ein neues Token wurde erzeugt."));
        }
    }

    public function mail_action()
    {
        if (!$this->plugin->user_is_eval_agent) {
            throw new AccessDeniedException();
        }
        $user_sql = "SELECT DISTINCT username " .
            "FROM auth_user_md5 u " .
            "INNER JOIN seminar_user su USING(user_id) " .
            "WHERE su.status = 'dozent' " .
            "AND su.Seminar_id IN (?) " .
            "ORDER BY Nachname,Vorname";

        $selected_teilnehmer = DBManager::get()->fetchFirst($user_sql, [array_keys(Request::getArray('selected_courses'))]);
        $_SESSION['sms_data'] = array();
        $_SESSION['sms_data']['p_rec'] = array_filter($selected_teilnehmer);
        $this->redirect(URLHelper::getURL('dispatch.php/messages/write', array('default_subject' => _('Hinweis zur Lehrevaluation'),'default_tags' => "Lehrevaluation", 'emailrequest' => 1)));
    }

    private function getSeminareData($institut_id, $semester_id, $fetch_zensus_data = false, $filter = [])
    {
        if ($institut_id != 'all') $institut = is_object($institut_id) ? $institut_id : Institute::find($institut_id);
        $semester = is_object($semester_id) ? $semester_id : Semester::find($semester_id);
        $datafield_id = UniZensusPlugin::$datafield_id_markiert;

        $data = [];
        $db = DBManager::get();

        if ($semester) {
            $seminare_condition = " AND seminare.start_time <=".(int)$semester["beginn"]." AND (".(int)$semester["beginn"]." <= (seminare.start_time + seminare.duration_time) OR seminare.duration_time = -1) ";
            if ($institut) {
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
                    $institutes = [$institut->id => $institut];
                }
            } elseif ($institut_id == 'all') {
                $query = "SELECT seminare.Name as name,seminare.Seminar_id as seminar_id, seminare.VeranstaltungsNummer as nr, seminare.Institut_id as institut_id FROM seminare 
INNER JOIN datafields_entries ON range_id = seminare.seminar_id AND datafield_id = '{$datafield_id}' AND content = '1'
        WHERE 1 $seminare_condition
        GROUP BY seminare.Seminar_id ORDER BY VeranstaltungsNummer,Name";
            } else {
                return [];
            }
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
                $data[$course_id]['teilnehmer_anzahl_aktuell'] = CourseMember::countByCourseAndStatus($course_id, 'autor');
                $data[$course_id]['wdhl'] = @DatafieldEntryModel::find([UniZensusPlugin::$datafield_id_wdhl,$course_id,''])->content;
                $data[$course_id]['fb'] = @DatafieldEntryModel::find([UniZensusPlugin::$datafield_id_fb,$course_id,''])->content;
                $data[$course_id]['form'] = @DatafieldEntryModel::find([UniZensusPlugin::$datafield_id_form,$course_id,''])->content;
                $data[$course_id]['sprache'] = @DatafieldEntryModel::find([UniZensusPlugin::$datafield_id_sprache,$course_id,''])->content;
                $data[$course_id]['evaluation'] = @DatafieldEntryModel::find([UniZensusPlugin::$datafield_id_vorgesehen,$course_id,''])->content;
                $uplugin = PluginManager::getInstance()->getPlugin('UniZensusPlugin');
                if ($uplugin) {
                    $uplugin->setId($course_id);
                    list($starttime, $endtime) = $uplugin->getCourseEvaluationTimeframe();
                    $data[$course_id]['eval_start_time'] = $starttime ? strftime('%x', $starttime) : '';
                    $data[$course_id]['eval_end_time'] = $endtime ? strftime('%x', $endtime) : '';
                    if ($uplugin->isActivated($course_id)) {
                        $data[$course_id]['plugin_activated'] = true;
                        if ($fetch_zensus_data) {
                            $data[$course_id] = array_merge($data[$course_id], UniZensusPlugin::getAdditionalExportData($course_id));
                            if ($data[$course_id]['eval_participants']) {
                                $data[$course_id]['teilnehmer_anzahl_aktuell'] = $data[$course_id]['eval_participants'];
                            }
                            unset($data[$course_id]['eval_participants']);
                            $uplugin->getCourseStatus();
                            $data[$course_id]['zensus_status'] = $uplugin->course_status['status'];
                            $data[$course_id]['zensus_numvotes'] = $uplugin->course_status['numvotes'];
                        }
                    }
                }
                if (@$filter['zensus_activated'] == 1
                    && !in_array($data[$course_id]['zensus_status'], ['prepare','run','analyze','finished'])) {
                    unset($data[$course_id]);
                }
                if (@$filter['zensus_deactivated'] == 1
                    && in_array($data[$course_id]['zensus_status'], ['prepare','run','analyze','finished'])) {
                    unset($data[$course_id]);
                }
                if (@$filter['plugin_activated'] == 1
                    && !@$data[$course_id]['evaluation']) {
                    unset($data[$course_id]);
                }
            }
            return $data;
        }

    }
}
