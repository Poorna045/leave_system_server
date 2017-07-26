<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Cronjob extends CI_Controller {

	/**
	 * Index Page for this controller.
	 *
	 * Maps to the following URL
	 * 		http://example.com/index.php/welcome
	 *	- or -
	 * 		http://example.com/index.php/welcome/index
	 *	- or -
	 * Since this controller is set as the default controller in
	 * config/routes.php, it's displayed at http://example.com/
	 *
	 * So any other public methods not prefixed with an underscore will
	 * map to /index.php/welcome/<method_name>
	 * @see https://codeigniter.com/user_guide/general/urls.html
	 */
    public function __construct()
    {
        parent::__construct();
        $this->load->model('api_model');
    }
	
    public function __destruct() {  
        $this->db->close();  
    } 
	public function index()
	{
		echo "okay";
	}

	// every minitue nom data cron
	public function minute_cron()
	{
		$this->db->query("call cron_nom()");
		$this->alarams();
	}

	// Daily night cron function 
	public function dat_cron()
	{
		$this->db->query("call cron_power_quality()");
		$this->db->query("call cron_consumption()");
		$this->db->query("call cron_trend_analysis()");
		$this->db->query("call cron_cost()");
	}

	// alaram notifications
	public function alarams()
	{
		$cron_duration = 5 * 60;
		$alert_duration = 30*60;

		$al_ary = [];
		$fn_ary = [];
		$cd = date('Y-m-d H:i:s', time() - $cron_duration);
		$ad = date('Y-m-d H:i:s', time() - $alert_duration);
		$query = $this->db->query("SELECT * FROM data dt INNER JOIN alert_config ac on ac.did = dt.did and ac.dtype = dt.dtype where ttime > '".$cd."' order by ttime asc, dt.did asc");
		foreach($query->result() as $qr) {
			$prm = $qr->param;
			if($qr->$prm < $qr->min || $qr->$prm > $qr->max) {
				$al_ary[$qr->fid] = $qr;
			}
		}
		$recent_alerts = $this->db->query("SELECT * FROM alerts where ttime > '".$ad."'")->result();
		// print_r($al_ary);
		foreach ($al_ary as $fr) {
			// print_r($fr); echo "<br>#####<br>";//exit();
			$this->isRecentAlert($fr, $recent_alerts);
		}

		$sends = $this->db->query("SELECT * FROM alerts where send_state=0 and a_level=2")->result();
		$paramAry = array(
							"act_power"		=> "Active Power",
							"react_power"	=> "Reactive Power",
							"kva"			=> "Apparent Power",
							"pf"			=> "Power Factor",
							"fre"			=> "Frequency",
							"lc"			=> "Line Current",
							"lv"			=> "Line Voltage"
						);

		$fupids = '';
		foreach ($sends as $res) { // print_r($res); exit();
			$location = $this->api_model->getDidDtypeName($res->did, $res->dtype);

			$subject = 'Alert: '.$paramAry[$res->param].' out of range..';
			$message = '<h3>'.$subject.'</h3>';
			$message.= '<table><tr><td>Location</td><td> : '.$location.'</td></tr>';
			$message.= '<tr><td>Reading</td><td> : '.$res->value.'</td></tr>';
			$message.= '<tr><td>Time</td><td> : '.$res->ttime.'</td></tr></table>';
			$message.= '<h4>EnMontoR <br><small>Energy Monitoring System.</small></h4>';
			$mail_to = explode(',', $res->mailids);
			$sms_msg = $subject.', location: '.$location.', reading: '.$res->value.', time: '.$res->ttime;
			$sms_to  = explode(',', $res->mobiles);
			$fupids==''?$fupids=$res->aid:$fupids=','.$res->aid;
			foreach ($mail_to as $mail) {
				$this->sendEmail($mail, $subject, $message);
			}
			foreach ($sms_to as $ph) {
				$this->sendSMS($ph,$sms_msg);
			}
		}
		if($fupids != '') {
			$query = $this->db->query("UPDATE alerts set send_state=1 where aid in (".$fupids.")");
		}
		echo "okay";
	}

	function isRecentAlert($fr, $recent_alerts) 
	{
		// print_r($fr);
		// echo "<br>@@@@@@@@@<br>";
		// print_r($recent_alerts[0]); 
		foreach ($recent_alerts as $ra) {
			if($ra->did == $fr->did && $ra->dtype == $fr->dtype && $ra->param == $fr->param) {
				// echo " ## inside ## ";
				return true;
			}
		}

		$prm = $fr->param;
		$in['did'] = $fr->did;
		$in['dtype'] = $fr->dtype;
		$in['param'] = $fr->param;
		$in['value'] = $fr->$prm;
		$in['ttime'] = $fr->ttime;
		$in['mailids'] = $fr->mailids;
		$in['mobiles'] = $fr->mobiles;
		$in['a_level'] = $fr->a_level;
		$this->db->insert("alerts", $in);
		return true;
	}

	// Alaram & Events - NOT USED
	public function alarams11()
	{
		$ttime5 = time() - (35*24*60*60);
		$ttime = date('Y-m-d H:i:s', $ttime5);
		$query = $this->db->query("SELECT * FROM data where ttime > '".$ttime."' ");
		$data = $query->result();

		$query = $this->db->query("SELECT * FROM alert_config ");
		$aconfigs = $query->result();

		$half_hr_back = time() - (30*60);
		$attime = date('Y-m-d H:i:s', $half_hr_back);
		$query = $this->db->query("SELECT * FROM alerts where ");
		$palerts = $query->result();

		$res = [];
		foreach ($data as $dt) {
			$this->isInConfigArray($aconfigs,$palerts,$dt->did,$dt->dtype,'act_power',$dt->act_power);
		}

		$fupids = '';
		$query = $this->db->query("SELECT * FROM alerts WHERE send_state=0 and a_level='2' ");
		$result = $query->result();
		foreach ($result as $res) {
			$location = $this->api_model->getDidDtypeName($res->did, $res->dtype);
			$subject = 'Alert: '.$res->param.' out of range..';
			$message = '<h3>'.$subject.'</h3>';
			$message.= '<table><tr><td>Location</td><td> : '.$location.'</td></tr>';
			$message.= '<tr><td>Reading</td><td> : '.$res->value.'</td></tr>';
			$message.= '<tr><td>Time</td><td> : '.$res->ttime.'</td></tr></table>';
			$message.= '<h4>EnMontoR <br><small>Energy Monitoring System.</small></h4>';
			$mail_to = explode(',', $res->mailids);
			$sms_msg = $subject.', location: '.$location.', reading: '.$res->value.', time: '.$res->ttime;
			$sms_to  = explode(',', $res->mobiles);
			$fupids==''?$fupids=$res->aid:$fupids=','.$res->aid;
			foreach ($mail_to as $mail) {
				$this->sendEmail($mail, $subject, $message);
			}
			foreach ($sms_to as $ph) {
				$this->sendSMS($ph,$sms_msg);
			}
		}
		$query = $this->db->query("UPDATE alerts set send_state=1 where aid in (".$fupids.")");
	}
	// ALaram config check function - NOT USED
	function isInConfigArray($array, $array1, $did, $dtype, $param, $value)
	{
		foreach ($array as $ar) {
			if($ar->did === $did && $ar->dtype === $dtype && $ar->param === $param) {
				if($ar->min > $value || $ar->max < $value) {
					if(!$this->isInPalerts($array1, $did, $dtype, $param)) {
						$data['did'] = $did;
						$data['dtype'] = $dtype;
						$data['param'] = $param;
						$data['value'] = $value;
						$data['ttime'] = $ar->ttime;
						$data['mailids'] = $ar->mailids;
						$data['mobiles'] = $ar->mobiles;
						$data['a_level'] = $ar->a_level;
						$this->db->insert("alerts", $data);
					}
				}
			}
		}
	}
	// ALaram ALERTS check function - NOT USED
	function isInPalerts($array, $did, $dtype, $param)
	{
		foreach ($array as $ar) {
			if($ar->did === $did && $ar->dtype === $dtype && $ar->param === $param) {
				return true;
			} else {
				return false;
			}
		}
	}

	// sending email
	function sendEmail($to,$subject,$message)
	{

		$this->load->library('email');

		$this->email->from('testmail@akrivia.in', 'EnMontoR Notification Alert');
		$this->email->to($to);
		// $this->email->cc('admin@raghueducational.org');
		// $this->email->bcc('techlead.it@raghues.com');

		$this->email->subject($subject);
		$this->email->message($message);

		$st = $this->email->send();

		if($st) {
			echo 'success';
		} else {
			echo 'fails';
		}

	}

	function sendSMS($to,$msg)
	{
		$URL = "http://login.smsmoon.com/API/sms.php";
		$post_fields = array(
		    'username' => 'raghuedu',
		    'password' => 'abcd.1234',
		    'from' => 'RAGHUT',
		    'to' => $to,
		    'msg' => $msg,
		    'type' => '1',
		    'dnd_check' => '0'
		);

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $URL);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);

		curl_exec($ch);
	}
	

 
}
