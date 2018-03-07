<?php
/**
* create zensus_xmlheader
*
* This function creates a xml-header for output.
* Its contents are Name of University, Stud.IP-Version, Range of Export (e.g. "root"), and temporal range.
*
* @access   public
* @return       string  xml-header
*/
function zensus_xmlheader($ex_sem = '')
{
global $UNI_NAME_CLEAN, $SOFTWARE_VERSION;
    $semester = $ex_sem ? Semester::find($ex_sem) : Semester::findCurrent();
    $xml_tag_string = "<" . "?xml version=\"1.0\" encoding=\"utf-8\"?" . ">\n";
    $xml_tag_string .= "<studip version=\"" . zensus_xmlescape ($SOFTWARE_VERSION) . "\" logo=\"". zensus_xmlescape ($GLOBALS['ASSETS_URL']."images/logos/logo2b.gif") . "\"";
    if ($UNI_NAME_CLEAN != "") $xml_tag_string .= " uni=\"" . zensus_xmlescape ($UNI_NAME_CLEAN) . "\"";
    if ($semester)
        $xml_tag_string .= " zeitraum=\"" . zensus_xmlescape ($semester->name) . "\" semester_id=\"" . zensus_xmlescape ($semester->getId()) . "\"";
    $xml_tag_string .= ">\n";
    return $xml_tag_string;
}

/**
* create opening xml-tag
*
* This function creates an open xml-tag.
* The tag-name is defined by the given parameter $tag_name.
* An optional parameter allows to set an attribute named "key".
*
* @access   public
* @param        string  tag name
* @param        string  value for optional attribute "key"
* @return       string  xml open tag
*/
function zensus_xmlopen_tag($tag_name, $tag_key = "")
{
    if ($tag_key != "")
        $xml_tag_string .= " key=\"" . zensus_xmlescape ($tag_key ) ."\"" ;
    $xml_tag_string = "<" . $tag_name . $xml_tag_string .  ">";
    return $xml_tag_string;
}

/**
* create closing xml-tag
*
* This function creates a closed xml-tag.
* The tag-name is defined by the given parameter $tag_name.
*
* @access   public
* @param        string  tag name
* @return       string  xml close tag
*/
function zensus_xmlclose_tag($tag_name)
{
    $xml_tag_string = "</" . $tag_name .  ">\n";
    return $xml_tag_string;
}

/**
* create xml-tag
*
* This function creates a xml-tag.
* The tag-name is defined by the given parameter $tag_name.
* The given parameter tag_content is put between open tag and close tag.
*
* @access   public
* @param        string  tag name
* @param        string  content for xml-tag
* @param        array   array of tag attributes
* @return       string  xml tag
*/
function zensus_xmltag($tag_name, $tag_content, $tag_attributes = null)
{
    if (is_array($tag_attributes)){
        foreach($tag_attributes as $key => $value){
            $xml_tag_string .= " $key=\"" .zensus_xmlescape($value)."\" ";
        }
    }
    $xml_tag_string = "<" . $tag_name . $xml_tag_string .  ">"
        . zensus_xmlescape ( $tag_content )
        . "</" . $tag_name .  ">\n";
    return $xml_tag_string;
}

/**
* create xml-footer
*
* This function creates the footer for xml output,
* which is a closing "studip"-tag.
*
* @access   public
* @return       string  xml footer
*/
function zensus_xmlfooter()
{
    $xml_tag_string = "</studip>";
    return $xml_tag_string;
}

/**
 * escapes special characters for xml use
 * optinally encodes to utf8
 *
 * @param string $string the string to escape
 * @param bool $utf8encode encode the string as utf-8
 * @return string
 */
function zensus_xmlescape($string, $utf8encode = true)
{
    $string = preg_replace('/[\x00-\x08\x0b\x0c\x0e-\x1f]/', '', $string);
    if ($utf8encode) {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    } else {
        return htmlspecialchars(html_entity_decode($string, ENT_QUOTES, 'UTF-8'), ENT_QUOTES, 'UTF-8', false);
    }
}

/**
* Writes the xml-stream into a file or to the screen.
*
* This function writes the xml-stream $object_data into a file or to the screen,
* depending on the content of $output_mode.
*
* @access   public
* @param        string  $object_data    xml-stream
* @param        string  $output_mode    switch for output target
*/
function zensus_output_data($object_data, $output_mode = "direct", $flush = false)
{
    global $xml_file;
    static $fp;
    if (is_null($fp)) {
        $fp = fopen('php://temp', 'r+');
    }

    fwrite($fp, $object_data);

    if($flush && is_resource($fp)) {
        rewind($fp);
        if (in_array($output_mode, words('file processor passthrough choose')) ) {
            stream_copy_to_stream($fp, $xml_file);
        } elseif ($output_mode == "direct") {
            $out = fopen('php://output', 'w');
            stream_copy_to_stream($fp, $out);
            fclose($out);
        }
        fclose($fp);
    }
}

/**
* Exports data of the given range.
*
* This function calls the functions that export the data sepcified by the given $export_range.
* It calls the function output_data afterwards.
*
* @access   public
* @param        string  $range_id   Stud.IP-range_id for export
*/
function zensus_export_range($range_id, $ex_sem, $o_mode = 'direct', $auth_uid = null)
{
    global $persons;

    $db=new DB_Seminar;

    zensus_output_data ( zensus_xmlheader(), $o_mode);

    if ($auth_uid && $GLOBALS['perm']->get_perm($auth_uid) != 'root') {
        if ($range_id == 'root') {
            $db->query("SELECT Institut_id, fakultaets_id FROM user_inst INNER JOIN Institute USING (Institut_id) WHERE inst_perms = 'admin' AND user_id = '" . $auth_uid . "' ORDER BY Institut_id=fakultaets_id");
            $faks = array();
            $insts = array();
            while ($db->next_record()) {
                if ($db->f('fakultaets_id') == $db->f('Institut_id')) $faks[] = $db->f('Institut_id');
                else if (!in_array($db->f('Institut_id'), $faks)) $insts[] = $db->f('Institut_id');
            }
            foreach($faks as $f) {
                $db->query("SELECT * FROM Institute WHERE Institut_id = '" . $f . "'");
                if (($db->next_record()) And ($db->f("Name") != ""))
                {
                    zensus_export_inst( $f ,'all', $o_mode);
                }
                $db->query("SELECT * FROM Institute WHERE fakultaets_id = '" . $f . "' ");
                while ($db->next_record()) {
                    if (($db->f("Name") != "") And ($db->f("Institut_id") != $f))
                    {
                        zensus_export_inst( $db->f("Institut_id"),'all', $o_mode );
                    }
                }
            }
            foreach ($insts as $i) {
                zensus_export_inst( $i ,'all', $o_mode);
            }
        } else {
            if (get_object_type($range_id) == 'inst' && $GLOBALS['perm']->have_studip_perm('admin', $range_id, $auth_uid)) {
                zensus_export_inst( $range_id, 'all' , $o_mode);
            }
            if (get_object_type($range_id) == 'fak' && $GLOBALS['perm']->have_studip_perm('admin', $range_id, $auth_uid)) {
            $db->query("SELECT Institut_id FROM Institute WHERE fakultaets_id = '" . $range_id . "' ORDER BY Institut_id=fakultaets_id");
                while ($db->next_record()) {
                    zensus_export_inst( $db->f("Institut_id"),'all', $o_mode );
                }
            }
            if (get_object_type($range_id) == 'sem' && $GLOBALS['perm']->have_studip_perm('admin', $range_id, $auth_uid)) {
                $db->query("SELECT * FROM seminare WHERE Seminar_id = '" . $range_id . "'");
                if (($db->next_record()) And ($db->f("Name") != ""))
                {
                    zensus_export_inst( $db->f("Institut_id"), $db->f("Seminar_id") , $o_mode);
                }
            }
        }
    } else {
        //    Ist die Range-ID eine Einrichtungs-ID?
        $db->query("SELECT * FROM Institute WHERE Institut_id = '" . $range_id . "'");
        if (($db->next_record()) And ($db->f("Name") != ""))
        {
            zensus_export_inst( $range_id ,'all', $o_mode);
        }

        //  Ist die Range-ID eine Fakultaets-ID? Dann auch untergeordnete Institute exportieren!
        $db->query("SELECT * FROM Institute WHERE fakultaets_id = '" . $range_id . "' ");
        while ($db->next_record())
        if (($db->f("Name") != "") And ($db->f("Institut_id") != $range_id))
        {
            zensus_export_inst( $db->f("Institut_id"),'all', $o_mode );
        }

        //    Ist die Range-ID eine Seminar-ID?
        $db->query("SELECT * FROM seminare WHERE Seminar_id = '" . $range_id . "'");
        if (($db->next_record()) And ($db->f("Name") != ""))
        {
            zensus_export_inst( $db->f("Institut_id"), $db->f("Seminar_id") , $o_mode);
        }


        //    Ist die Range-ID ein Range-Tree-Item?
        if($range_id != 'root'){
            $tree_object = new RangeTreeObject($range_id);
            $range_name = $tree_object->item_data["name"];
            //    Tree-Item ist ein Institut:
            if ($tree_object->item_data['studip_object'] == 'inst')
            {
                zensus_export_inst( $tree_object->item_data['studip_object_id'], 'all', $o_mode );
            }
            //    Tree-Item hat Institute als Kinder:
            $inst_array = $tree_object->GetInstKids();

            if (sizeof($inst_array) > 0)
            {
                while (list($key, $inst_ids) = each($inst_array))
                {
                    zensus_export_inst($inst_ids, 'all', $o_mode);
                }
            }
        }

        $db->query("SELECT sem_tree_id FROM sem_tree WHERE sem_tree_id = '$range_id' ");
        if ($db->next_record() || $range_id=='root'){
            if (isset($ex_sem) && $semester = Semester::find($ex_sem)){
                $args = array('sem_number' => array(SemesterData::GetSemesterIndexById($ex_sem)));
            } else {
                $args = array();
            }
            if ($range_id != 'root') {
                $the_tree = TreeAbstract::GetInstance('StudipSemTree', $args);
                $sem_ids = array_unique($the_tree->getSemIds($range_id, true));
            }
            if(is_array($sem_ids) || $range_id == 'root'){
                if(is_array($sem_ids)) {
                    $to_export = DbManager::get()
                            ->query("SELECT DISTINCT Institut_id FROM seminare
                                      WHERE Seminar_id IN('".join("','", $sem_ids)."')")
                            ->fetchAll(PDO::FETCH_COLUMN);
                } else {
                    $sem_ids = 'root';
                    $to_export = DbManager::get()
                            ->query("SELECT DISTINCT Institut_id FROM seminare 
                                    " . ($semester ?
                                       "WHERE seminare.start_time <=".$semester->beginn." AND (".$semester->beginn." <= (seminare.start_time + seminare.duration_time) OR seminare.duration_time = -1)"
                                        : ""))->fetchAll(PDO::FETCH_COLUMN);
                }
                foreach($to_export as $inst) zensus_export_inst($inst, $sem_ids, $o_mode);
            }
        }
    }
    if (is_array($persons)){
        zensus_export_persons(array_keys($persons), $o_mode);
    }
    zensus_output_data ( zensus_xmlfooter(), $o_mode, $flush = true);
}


/**
* Exports a Stud.IP-institute.
*
* This function gets the data of an institute and writes it into $data_object.
* It calls one of the functions export_sem, export_pers or export_teilis and then output_data.
*
* @access   public
* @param        string  $inst_id    Stud.IP-inst_id for export
* @param        string  $ex_sem_id  allows to choose if only a specific lecture is to be exported
*/
function zensus_export_inst($inst_id, $ex_sem_id = "all", $o_mode = 'direct')
{
    global $xml_file, $xml_names_inst, $xml_groupnames_inst, $INST_TYPE;

    $db=new DB_Seminar;

    $db->query("SELECT * FROM Institute WHERE Institut_id = '" . $inst_id . "'");
    $db->next_record();
    $data_object .= zensus_xmlopen_tag($xml_groupnames_inst["object"], $db->f("Institut_id"));
    while ( list($key, $val) = each($xml_names_inst))
    {
        if ($val == "") $val = $key;
        if (($key == "type") AND ($INST_TYPE[$db->f($key)]["name"] != ""))
            $data_object .= zensus_xmltag($val, $INST_TYPE[$db->f($key)]["name"]);
        elseif ($db->f($key) != "")
            $data_object .= zensus_xmltag($val, $db->f($key));
    }
    reset($xml_names_inst);
    $db->query("SELECT Name, Institut_id FROM Institute WHERE Institut_id = '" . $db->f('fakultaets_id') . "' AND fakultaets_id = '" . $db->f('fakultaets_id') . "'");
    $db->next_record();
    {
        if ($db->f("Name") != "")
            $data_object .= zensus_xmltag($xml_groupnames_inst["childobject"], $db->f("Name"), array('key' => $db->f('Institut_id')));
    }
    // freie Datenfelder ausgeben
    $data_object .= zensus_export_datafields($inst_id, $xml_groupnames_inst["childgroup2"], $xml_groupnames_inst["childobject2"], 'inst');
    zensus_output_data( $data_object, $o_mode );
    $data_object = "";

    zensus_export_sem($inst_id, $ex_sem_id, $o_mode);

    $data_object .= zensus_xmlclose_tag($xml_groupnames_inst["object"]);

    zensus_output_data($data_object, $o_mode);
}

/**
* Exports lecture-data.
*
* This function gets the data of the lectures at an institute and writes it into $data_object.
* It calls output_data afterwards.
*
* @access   public
* @param        string  $inst_id    Stud.IP-inst_id for export
* @param        string  $ex_sem_id  allows to choose if only a specific lecture is to be exported
*/
function zensus_export_sem($inst_id, $ex_sem_id = "all", $o_mode = 'direct')
{
    global $xml_file, $xml_names_lecture, $xml_groupnames_lecture, $SEM_TYPE, $SEM_CLASS, $persons;
    global $ex_sem, $ex_only_homeinst,$ex_sem_class, $ex_only_visible;

    $datafield_id = UniZensusPlugin::$datafield_id_vorgesehen;


    $db=new DB_Seminar;
    $db2=new DB_Seminar;
    $db3=new DB_Seminar;

    $order = "seminare.status, seminare.Name";
    $group = "FIRSTGROUP";
    $group_tab_zelle = "status";
    $do_group = true;

    if (isset($ex_sem) && $semester = Semester::find($ex_sem)){
        $addquery = " AND seminare.start_time <=".$semester->beginn." AND (".$semester->beginn." <= (seminare.start_time + seminare.duration_time) OR seminare.duration_time = -1) ";
    }

    if ($ex_sem_id != "all" && $ex_sem_id != "root"){
        if (!is_array($ex_sem_id)) $ex_sem_id = array($ex_sem_id);
        $ex_sem_id = array_flip($ex_sem_id);
    }

    if ($ex_only_visible) $addquery .= " AND visible=1 ";

    if(count($ex_sem_class)) {
        $allowed_sem_types = array();
        foreach($ex_sem_class as $semclassid){
            $allowed_sem_types += array_keys(SeminarCategories::get($semclassid)->getTypes());
        }
        $addquery .= sprintf(" AND seminare.status IN(%s) ", join(",", $allowed_sem_types));
    }

    if($ex_only_homeinst){
        $db->query("SELECT seminare.*,Seminar_id as seminar_id, Institute.Name as heimateinrichtung FROM seminare
                LEFT JOIN Institute ON seminare.Institut_id=Institute.Institut_id
                INNER JOIN datafields_entries ON range_id = seminare.seminar_id AND datafield_id = '{$datafield_id}' AND content = '1'
                WHERE seminare.Institut_id = '" . $inst_id . "' " . $addquery . "
                ORDER BY " . $order);
    } else {
        $db->query("SELECT seminare.*,seminar_inst.seminar_id, Institute.Name as heimateinrichtung FROM seminar_inst
                LEFT JOIN seminare USING (Seminar_id)
                LEFT JOIN Institute ON seminare.Institut_id=Institute.Institut_id
                INNER JOIN datafields_entries ON range_id = seminare.seminar_id AND datafield_id = '{$datafield_id}' AND content = '1'
                WHERE seminar_inst.Institut_id = '" . $inst_id . "' " . $addquery . "
                ORDER BY " . $order);
    }

    $data_object .= zensus_xmlopen_tag( $xml_groupnames_lecture["group"] );

    while ($db->next_record())
    {
        if (is_array($ex_sem_id) && !isset($ex_sem_id[$db->f("seminar_id")])) continue;
        $group_string = "";
        if (($do_group) AND ($group != $db->f($group_tab_zelle)))
        {
            if ($group != "FIRSTGROUP")
            $group_string .= zensus_xmlclose_tag($xml_groupnames_lecture["subgroup1"]);
            if ($group_tab_zelle == "status")
            $group_string .= zensus_xmlopen_tag($xml_groupnames_lecture["subgroup1"], $SEM_TYPE[$db->f($group_tab_zelle)]["name"]);
            else
            $group_string .= zensus_xmlopen_tag($xml_groupnames_lecture["subgroup1"], $db->f($group_tab_zelle));
            $group = $db->f($group_tab_zelle);
            if (($do_subgroup) AND ($subgroup == $db->f($subgroup_tab_zelle)))
            $subgroup = "NEXTGROUP";
        }
        if (($do_subgroup) AND ($subgroup != $db->f($subgroup_tab_zelle)))
        {
            if ($subgroup != "FIRSTGROUP")
            $group_string = zensus_xmlclose_tag($xml_groupnames_lecture["subgroup2"]) . $group_string;
            $group_string .= zensus_xmlopen_tag($xml_groupnames_lecture["subgroup2"], $db->f($subgroup_tab_zelle));
            $subgroup = $db->f($subgroup_tab_zelle);
        }
        $data_object .= $group_string;
        $data_object .= zensus_xmlopen_tag($xml_groupnames_lecture["object"], $db->f("seminar_id"));
        while ( list($key, $val) = each($xml_names_lecture))
        {
            if (is_callable($val)) {
                $data_object .= call_user_func($val, $key, $db->f("seminar_id"));
                continue;
            }
            if ($val == "") $val = $key;
            if ($key == "status") {
                $data_object .= zensus_xmltag($val, $SEM_TYPE[$db->f($key)]["name"]);
            }
            elseif (($key == "bereich") AND (($SEM_CLASS[$SEM_TYPE[$db->f("status")]["class"]]["bereiche"]))) {
                $data_object .= zensus_xmlopen_tag($xml_groupnames_lecture["childgroup3"]);
                $pathes = get_sem_tree_path($db->f("seminar_id"));
                if (is_array($pathes)){
                    foreach ($pathes as $sem_tree_id => $path_name)
                    $data_object .= zensus_xmltag($val, $path_name);
                } else {
                    $data_object .= zensus_xmltag($val, "n.a.");
                }
                $data_object .= zensus_xmlclose_tag($xml_groupnames_lecture["childgroup3"]);
            }
            elseif ($key == "admission_turnout")
            {
                $data_object .= zensus_xmlopen_tag($val, sprintf ("%s", $db->f("admission_type")) ? _("max.") : _("erw.")) . $db->f($key) . zensus_xmlclose_tag($val);
            }
            elseif ($key == "teilnehmer_anzahl_aktuell")
            {
                $db3->query("SELECT COUNT(user_id) FROM seminar_user WHERE seminar_id='".$db->f('seminar_id')."' AND status='autor'");
                $db3->next_record();
                $data_object .= zensus_xmltag($val, $db3->f(0));
            }
            elseif ($key == "Institut_id")
            {
                $data_object .= zensus_xmltag($val, $db->f('heimateinrichtung') , array('key' => $db->f($key)));
            }
            elseif ($db->f($key) != "")
            $data_object .= zensus_xmltag($val, $db->f($key));
        }
        $db2->query("SELECT seminar_user.position, auth_user_md5.user_id,auth_user_md5.username, auth_user_md5.Vorname, auth_user_md5.Nachname, user_info.title_front, user_info.title_rear FROM seminar_user
                        LEFT JOIN user_info USING(user_id)
                        LEFT JOIN auth_user_md5 USING(user_id)
                        WHERE (seminar_user.status = 'dozent') AND (seminar_user.Seminar_id = '" . $db->f('seminar_id') . "') ORDER BY seminar_user.position ");
        $data_object .= "<" . $xml_groupnames_lecture["childgroup2"] . ">\n";
        while ($db2->next_record())
        {
            $persons[$db2->f('user_id')] = true;
            $content_string = $db2->f("Vorname") . " " . $db2->f("Nachname");
            if ($db2->f("title_front") != "")
            $content_string = $db2->f("title_front") . " " . $content_string;
            if ($db2->f("title_rear") != "")
            $content_string = $content_string . ", " . $db2->f("title_rear");
            $data_object .= zensus_xmltag($xml_groupnames_lecture["childobject2"], $content_string, array('key' => $db2->f('username')));
        }
        $data_object .= zensus_xmlclose_tag( $xml_groupnames_lecture["childgroup2"] );
        // freie Datenfelder ausgeben
        $data_object .= zensus_export_datafields($db->f("seminar_id"), $xml_groupnames_lecture["childgroup4"], $xml_groupnames_lecture["childobject4"],'sem',$db->f("status"));
        $data_object .= zensus_xmlclose_tag( $xml_groupnames_lecture["object"] );
        reset($xml_names_lecture);
        zensus_output_data($data_object, $o_mode);
        $data_object = "";
    }

    if (($do_subgroup) AND ($subgroup != "FIRSTGROUP"))
    $data_object .= zensus_xmlclose_tag($xml_groupnames_lecture["subgroup2"]);
    if (($do_group) AND ($group != "FIRSTGROUP"))
    $data_object .= zensus_xmlclose_tag($xml_groupnames_lecture["subgroup1"]);

    $data_object .= zensus_xmlclose_tag($xml_groupnames_lecture["group"]);
    zensus_output_data($data_object, $o_mode);
}




/**
* Exports list of persons.
*
*
* @access   public
* @param        array   $persons    Stud.IP-user_ids for export
*/
function zensus_export_persons($persons, $o_mode = 'direct')
{
    global $xml_names_person, $xml_groupnames_person;
    $db = new DB_Seminar;
    if (is_array($persons)){
        $db->query("SELECT * FROM auth_user_md5
                LEFT JOIN user_info info USING(user_id)
                WHERE auth_user_md5.user_id IN('".join("','", $persons)."')");

        while ($db->next_record()) {
            $data_object = zensus_xmlopen_tag($xml_groupnames_person["object"], $db->f("username"));
            $data_object .= zensus_xmltag('id', $db->f('user_id'));
            while ( list($key, $val) = each($xml_names_person)){
                if ($val == "") $val = $key;
                if ($db->f($key) != "") $data_object .= zensus_xmltag($val, $db->f($key));
            }
            // freie Datenfelder ausgeben
            $data_object .= zensus_export_datafields($db->f("user_id"), $xml_groupnames_person["childgroup1"], $xml_groupnames_person["childobject1"],'user');
            $data_object .= zensus_xmlclose_tag( $xml_groupnames_person["object"] );
            reset($xml_names_person);
            zensus_output_data($data_object, $o_mode);
            $data_object = "";
        }
    }
    $data_object = "";
}

/**
* helper function to export custom datafields
*
* only visible datafields are exported (depending on user perms)
* @access   public
* @param    string  $range_id   id for object to export
* @param    string  $childgroup_tag name of outer tag
* @param    string  $childobject_tag    name of inner tags
*/
function zensus_export_datafields($range_id, $childgroup_tag, $childobject_tag, $object_type = null, $object_class_hint = null){
    $ret = '';
    $d_fields = false;
    $localEntries = DataFieldEntry::getDataFieldEntries($range_id,$object_type, $object_class_hint);
    if(is_array($localEntries )){
        foreach ($localEntries as $entry){
            if (!$d_fields) $ret .= zensus_xmlopen_tag( $childgroup_tag );
            $ret .= zensus_xmltag($childobject_tag, $entry->getDisplayValue(false), array('key' => $entry->getName(), 'id' => $entry->getId()));
            $d_fields = true;
        }
    }
    if ($d_fields) $ret .= zensus_xmlclose_tag( $childgroup_tag );
    return $ret;
}

