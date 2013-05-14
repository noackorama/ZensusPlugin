<?php
require_once "UniZensusRPC.class.php";
require_once "lib/classes/Seminar.class.php";


class UniZensusPlugin extends AbstractStudIPStandardPlugin {

	var $is_not_activatable = true;

	function SQLDateToTimestamp($sqldate){
		$date_values = explode("-", $sqldate); //YYYY-MM-DD
		if (checkdate((int)$date_values[1],(int)$date_values[2],(int)$date_values[0])){
			return mktime(0,0,0,$date_values[1],$date_values[2],$date_values[0], 0);
		} else {
			return false;
		}
	}

	/**
	 *
	 */
	function UniZensusPlugin(){
		AbstractStudIPStandardPlugin::AbstractStudIPStandardPlugin();
		$this->setPluginiconname('images/icon_zensus_neu.gif');
		$this->setChangeindicatoriconname('images/icon_zensus_neu_changed.gif');
		$this->RPC = new UniZensusRPC();
		if ($this->isVisible()){
			$tab = new PluginNavigation();
			$tab->setDisplayname($GLOBALS['UNIZENSUSPLUGIN_DISPLAYNAME']);
			$this->setNavigation($tab);
		}
	}

	function setId($id) {
		$this->id = $id;
		if ($this->isVisible()){
			$tab = new PluginNavigation();
			$tab->setDisplayname($GLOBALS['UNIZENSUSPLUGIN_DISPLAYNAME']);
			$this->setNavigation($tab);
		}
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

	function isShownInOverview(){
		return ($GLOBALS['UNIZENSUSPLUGIN_SHOWN_IN_OVERVIEW'] && $this->isVisible());
    }

	function getOverviewMessage($has_changed = false){
		if (!$GLOBALS['perm']->have_studip_perm('dozent', $this->getId())){
			if($this->course_status['questionnaire'] == true) return _("Den Fragebogen aufrufen und an der Evaluation teilnehmen"); if($this->course_status['status'] == 'run') return _("Sie haben an dieser Evaluation bereits teilgenommen!");
		}
		return $GLOBALS['UNIZENSUSPLUGIN_DISPLAYNAME'] . ': ' .$this->getCourseStatusMessage() . ($has_changed ? ' (' . _("geändert"). ')' : '');
	}

	function isVisible(){
		$this->getCourseStatus();
		if (
			($this->course_status['status']
			&& $this->course_status['status'] != 'not found'
			&& ( (($this->course_status['preview'] || $this->course_status['questionnaire']) && $GLOBALS['perm']->have_studip_perm('autor' , $this->getId()))
			    )
			&& (!isset($this->course_status['time_frame'])
				|| ($this->course_status['time_frame']['begin'] < time()
					&& $this->course_status['time_frame']['end'] > time()
					)
				)
			)
			|| $GLOBALS['perm']->have_perm('admin')
			|| $GLOBALS['perm']->have_studip_perm('dozent', $this->getId())
			) return true;
		else return false;
	}

	function hasChanged($lastviewed){
		$this->getCourseAndUserStatus();
		if ($GLOBALS['perm']->have_studip_perm('dozent', $this->getId())){
			return $this->course_status['last_changed'] > $lastviewed;
		} else {
			return $this->course_status['questionnaire'] == true;
		}
	}

	function getChangeMessages($lastlogin, $ids){
		return array();
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

	function getCourseEvaluationTimeframe(){
		if($this->getId()){
			$db = new DB_Seminar(sprintf("SELECT content, datafield_id FROM datafields_entries WHERE range_id='%s' AND datafield_id IN (%s)",
									$this->getId(), "'".md5('UNIZENSUSPLUGIN_END_EVALUATION')."','".md5('UNIZENSUSPLUGIN_BEGIN_EVALUATION')."'"));
			while($db->next_record()){
				if($db->f('datafield_id') == md5('UNIZENSUSPLUGIN_END_EVALUATION')) $end = $this->SQLDateToTimestamp($db->f('content'));
				if($db->f('datafield_id') == md5('UNIZENSUSPLUGIN_BEGIN_EVALUATION')) $begin = $this->SQLDateToTimestamp($db->f('content'));
			}
			if($begin && $end && ($begin <= $end)) return array($begin, strtotime('now 23:59', $end));
			list($calcbegin,$calcend) = $this->getCalculatedCourseTimeFrame($this->getId());
			if($calcbegin && !$begin) $begin = $calcbegin;
			if($calcend && !$end) $end = $calcend;
			if($begin && $end && ($begin <= $end)) return array($begin, strtotime('now 23:59', $end));
			$globalbegin = $this->SQLDateToTimestamp($GLOBALS['UNIZENSUSPLUGIN_BEGIN_EVALUATION']);
			$globalend = $this->SQLDateToTimestamp($GLOBALS['UNIZENSUSPLUGIN_END_EVALUATION']);
			if($globalbegin && !$begin) $begin = $globalbegin;
			if($globalend && !$end) $end = $globalend;
			if($begin && $end && ($begin <= $end)) return array($begin, strtotime('now 23:59', $end));
			else return array($globalbegin, strtotime('now 23:59', $globalend));
		}
	}

	function getCalculatedCourseTimeFrame($seminar_id){
		$db = new DB_Seminar();
		$begin = false;
		$end = false;
		$seminar = Seminar::GetInstance($seminar_id);
		$semester = SemesterData::GetInstance();
		if($seminar->getSemesterDurationTime() == 0){
			$current_sem = $semester->getSemesterDataByDate($seminar->getSemesterStartTime());
		} else {
			$current_sem = $semester->getCurrentSemesterData();
		}
		$query = sprintf("SELECT date FROM termine WHERE range_id='%s' AND date BETWEEN %s AND %s ORDER BY date DESC LIMIT 0,3", $seminar_id, (int)$current_sem['beginn'],(int)$current_sem['ende']) ;
		$db->query($query);
		$db->next_record();
		if($db->num_rows() > 1){
			if($seminar->getMetaDateCount() > 0){
				$db->next_record();
				$db->next_record();
			} else {
				$db->next_record();
			}
		}
		$begin = $db->f('date');
		if($seminar->getMetaDateCount() > 0 && $begin && date('w', $begin) != 1) $begin = strtotime("last monday", $begin);
		if($begin) $end = strtotime("+20 days", $begin);
		return array($begin,$end);
	}

	function show($args){
		if (!$this->isVisible()) return;
		$this->getCourseAndUserStatus();
		$pluginrelativepath = $this->getPluginUrl();
		echo chr(10) . '<div style="margin:10px;">';
		echo chr(10) . '<h2>' . _("Status der Lehrevaluation:") . '</h2>';
		if (isset($this->course_status['time_frame'])){
			echo chr(10) . '<p>';
			echo chr(10) . _("Zeitraum:") . '&nbsp;'. strftime("%x", $this->course_status['time_frame']['begin']) . ' - ' . strftime("%x", $this->course_status['time_frame']['end']);
			echo chr(10) . '</p>';
		}
		if (($this->course_status['status']	&& $this->course_status['status'] != 'not found')){
			echo chr(10) . '<p>';
			echo chr(10) . $this->getCourseStatusMessage();
			echo chr(10) . '</p>';
			if ($this->course_status['preview'] || $this->course_status['questionnaire'] || $this->course_status['results']){
				echo chr(10) . '<div style="font-weight:bold;font-size:10pt;border: 1px solid;padding:5px">' . _("Mögliche Aktionen:") . '<div style="font-size:10pt;margin-left:10px">';
				if ($this->course_status['preview']) {
					echo chr(10) . '<p><a target="_blank" href="' . $this->RPC->getEvaluationURL('preview',$this->getZensusCourseId(),$GLOBALS['user']->id) . '">';
					echo chr(10) . '<img src="'.$pluginrelativepath.'/images/link_extern.gif" hspace="2" border="0">' . _("Eine Voransicht des Fragebogens aufrufen") . '</a></p>';
				}
				if ($this->course_status['results'] && $GLOBALS['perm']->have_studip_perm('dozent', $this->getId()) && $GLOBALS['auth']->auth['perm'] != 'admin' && $GLOBALS['auth']->auth['perm'] != 'root') {
					echo chr(10) . '<p><a target="_blank" href="' . $this->RPC->getEvaluationURL('results',$this->getZensusCourseId(),$GLOBALS['user']->id) . '">';
					echo chr(10) . '<img src="'.$pluginrelativepath.'/images/link_extern.gif" hspace="2" border="0">' . _("Die Ergebnisse der Evaluation aufrufen") . '</a></p>';
				}
				if ($this->course_status['pdfdetailfreetexts'] && $GLOBALS['perm']->have_studip_perm('dozent', $this->getId()) && $GLOBALS['auth']->auth['perm'] != 'admin' && $GLOBALS['auth']->auth['perm'] != 'root') {
					echo chr(10) . '<p><a target="_blank" href="' . $this->RPC->getEvaluationURL('pdfdetailfreetexts',$this->getZensusCourseId(),$GLOBALS['user']->id) . '">';
					echo chr(10) . '<img src="'.$GLOBALS['ASSETS_URL'].'images/pdf-icon.gif" hspace="2" border="0" align="absbottom">' . _("Die Ergebnisse (Detailauswertung) der Evaluation als PDF aufrufen") . '</a></p>';

					echo chr(10) . '<p><a target="_blank" href="' . $this->RPC->getEvaluationURL('pdfresults',$this->getZensusCourseId(),$GLOBALS['user']->id) . '">';
					echo chr(10) . '<img src="'.$GLOBALS['ASSETS_URL'].'images/pdf-icon.gif" hspace="2" border="0" align="absbottom">' . _("Die Ergebnisse (Profillinie) der Evaluation als PDF aufrufen") . '</a></p>';
				}
				if($GLOBALS['perm']->have_perm('root')){
					foreach(Seminar::getInstance($this->getId())->getMembers('dozent') as $m){
						echo '<hr>';
						echo '<h3>' . htmlready($m['Nachname'].', '.$m['Vorname']) . '</h4>';
						if ($this->course_status['results']) {
							echo chr(10) . '<p><a target="_blank" href="' . $this->RPC->getEvaluationURL('results',$this->getZensusCourseId(),$m['user_id'
								]) . '">';
							echo chr(10) . '<img src="'.$pluginrelativepath.'/images/link_extern.gif" hspace="2" border="0">' . _("Die Ergebnisse der
								Evaluation aufrufen") . '</a></p>';
						}
						if ($this->course_status['pdfdetailfreetexts']) {
							echo chr(10) . '<p><a target="_blank" href="' . $this->RPC->getEvaluationURL('pdfdetailfreetexts',$this->getZensusCourseId(),$m
								['user_id']) . '">';
							echo chr(10) . '<img src="'.$GLOBALS['ASSETS_URL'].'images/pdf-icon.gif" hspace="2" border="0" align="absbottom">' . _("Die
								Ergebnisse (Detailauswertung) der Evaluation als PDF aufrufen") . '</a></p>';

							echo chr(10) . '<p><a target="_blank" href="' . $this->RPC->getEvaluationURL('pdfresults',$this->getZensusCourseId(),$m[
								'user_id']) . '">';
							echo chr(10) . '<img src="'.$GLOBALS['ASSETS_URL'].'images/pdf-icon.gif" hspace="2" border="0" align="absbottom">' . _("Die
								Ergebnisse (Profillinie) der Evaluation als PDF aufrufen") . '</a></p>';
						}
						echo '<hr>';
					}
				}
				if ($this->course_status['questionnaire']){
					if (!$GLOBALS['perm']->have_studip_perm('dozent' , $this->getId())){
						echo chr(10) . '<p><a target="_blank" href="' . $this->RPC->getEvaluationURL('questionnaire',$this->getZensusCourseId(),$GLOBALS['user']->id) . '">';
						echo chr(10) . '<img src="'.$pluginrelativepath.'/images/link_extern.gif" hspace="2" border="0">' . _("Den Fragebogen aufrufen und an der Evaluation teilnehmen") . '</a></p>';
					} else {
						echo chr(10) . '<p>'._("Als Dozent der Veranstaltung können sie nicht an der Evaluation teilnehmen!") . '</p>';
					}
				} elseif ($this->course_status['status'] == 'run' && !$GLOBALS['perm']->have_studip_perm('dozent' , $this->getId())){
					echo chr(10) .'<p>'. _("Sie haben an dieser Evaluation bereits teilgenommen!") . '</p>';
				}

				echo chr(10) . '</div></div>';
			}
		} else {
			echo chr(10) . '<p style="font-weight:bold">' . _("Zu dieser Veranstaltung ist keine Evaluation verfügbar.") . '</p>';
		}
		echo chr(10) . '</div>';
	}
}
?>