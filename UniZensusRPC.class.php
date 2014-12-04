<?php
require_once "vendor/phpxmlrpc/xmlrpc.inc";

class UniZensusRPC {

	var $timeout = 2;
	var $cache = 300;
	var $debug = 0;

	/**
	 *
	 */
	function UniZensusRPC(){
		$this->client = new xmlrpc_client($GLOBALS['UNIZENSUSPLUGIN_XMLRPC_ENDPOINT']);
		$this->client->setDebug($this->debug);
	}

	function getCourseStatus($course_id, $user_id = null){
		$id = md5($course_id . $user_id);
		if (!is_null( ($cached_result = $this->getResultFromCache($id)) )){
			return $cached_result;
		}
		$result = array();
		if (is_null($user_id)){
			$msg = new xmlrpcmsg("info.course_status", array(new xmlrpcval($course_id, "string"),
													new xmlrpcval('Stud.IP', 'string')));
		} else {
			$msg = new xmlrpcmsg("info.course_status", array(new xmlrpcval($course_id, "string"),
													new xmlrpcval('Stud.IP', 'string'),
													new xmlrpcval($user_id, 'string')));
		}
		ob_start();
		$response = $this->client->send($msg, $this->timeout);
		$debug = ob_get_clean();
		if (!$response->faultCode()){
			$result = php_xmlrpc_decode($response->value());
		} else {
			  error_log("UnizensusPlugin: An error occurred \n"
			  			. "Code: ".$response->faultCode()." \n"
			  			. "Reason: ".$response->faultString()." \n"
			            . "course_id:$course_id user_id:$user_id \n"
			            . $debug);

		}
		$old_data = $this->getResultFromCache($id, true);
		$last_changed = $old_data['last_changed'];
		unset($old_data['last_changed']);
		if(serialize($old_data) != serialize($result)){
			$result['last_changed'] = time();
		} else {
			$result['last_changed'] = $last_changed;
		}
		$this->putResultToCache($id, $result);
		return $result;
	}

	function getEvaluationURL($target, $course_id, $user_id){
		$tstamp = date('Y-m-d-H-i', time() + 120);
		$hash = md5($course_id . $GLOBALS['UNIZENSUSPLUGIN_SHARED_SECRET1'] . $tstamp . $GLOBALS['UNIZENSUSPLUGIN_SHARED_SECRET2'] . $user_id . $target);
		//$hash = hash_hmac('md5', $course_id.$course_id.$target.$tstamp.$user_id, $GLOBALS['UNIZENSUSPLUGIN_SHARED_SECRET1'] . $GLOBALS['UNIZENSUSPLUGIN_SHARED_SECRET2']);
		$url = $GLOBALS['UNIZENSUSPLUGIN_URL_PREFIX'] . 'app?service=pex/StudIpLoginPage';
		$url .= "&sp=$tstamp&sp=$hash&sp=$user_id&sp=$target&sp=".$course_id."&sp=$course_id";
		return $url;
	}

	function putResultToCache($id, $result){
		if ($this->cache){
			$db = new DB_Seminar();
			$data = mysql_escape_string(serialize($result));
			$db->query("REPLACE INTO unizensusplugincache (id,data,chdate) VALUES ('$id', '$data', UNIX_TIMESTAMP())");
			return $db->affected_rows();
		}
	}

	function getResultFromCache($id, $override = false){
		$result = null;
		if ($this->cache){
			$db = new DB_Seminar();
			$db->query("SELECT data FROM unizensusplugincache WHERE id='$id'" . (!$override ? " AND chdate > " . (time() - $this->cache) : ""));
			if ($db->next_record()){
				$result = unserialize($db->f('data'));
			}
		}
		return $result;
	}
}
