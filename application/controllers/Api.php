<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require APPPATH . "/libraries/REST_Controller.php";

if (isset($_SERVER['HTTP_ORIGIN'])) {
	header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
	header('Access-Control-Allow-Credentials: true');
	header('Access-Control-Max-Age: 86400');    // cache for 1 day
}

// Access-Control headers are received during OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {

	if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
		header("Access-Control-Allow-Methods: GET, POST, OPTIONS");

	if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
		header("Access-Control-Allow-Headers:        {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");

	exit(0);
}


class Api extends REST_Controller {
            
	public function __construct()
	{
		parent::__construct();
		$this->load->model('api_model');
		$this->load->helper('jwt');
		$token = new stdClass();
			$token->userid = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJyZWdfbm8iOiJSRUNDU0UwMDEifQ._bKC0f-KOjAkw_a6iPar6b2ldyOpP9ucY7U2sXp0oTs';
	}
	public function __destruct() {  
	    $this->db->close();  
	} 
    
	public function index_get()
	{
		$this->response('NULL');
	}
	public function validLogin_post()
	{
		$username = $this->post('username');
		$password = $this->post('password');
		$result = $this->api_model->login($username, $password);
		
		if ($result['success']) {
			// user logged in, generate a token and return
			$id = $result['userid'];
			$token = array();
			$token['userid'] = $id;
			$result['token'] = JWT::encode($token, $this->config->item('jwt_key'));
			$result['name'] = $result['name'];
			$result['utype'] = $result['utype'];
			$result['uid'] = $result['uid'];
			$this->response($result);
		} else {
			// authentication failed, return error
			$this->response(
				array(
					"success"=>$result['success'], 
					"error"=>$result['error'],
					"data" => $username
				)
			);
		}
	}	
	public function userDataa_get()
	{
		return $this->api_model->testdata();
	}

	//cron sinding messages with time interval
public function processMessages_get()
  {
    $return = [];
    // mtype = 'mail' and 
    $sql = "select * from messages where processed = 0";
    if($query = $this->db->query($sql)) {
          $result = $query->result();
      for ($i=0; $i<sizeof($result); $i++) {
        $mid = $result[$i]->mid;
        $mtype = $result[$i]->mtype;  //  mail  or sms 
        $mailtype = $result[$i]->mailtype;
        $mto = $result[$i]->mto;
        $cc = $result[$i]->cc;
        $bcc = $result[$i]->bcc;
        $subject = $result[$i]->subject;
        $message = $result[$i]->message;
        $processed = $result[$i]->processed;

        $item = $result[$i];
        if ($item->mtype == 'mail') {
          $data = $this->sendEmail($item);
          $return[] = "update messages set processed = 1, processed_return = '" . $data . "' where mid=" . $mid . ';';
        } else if ($item->mtype == 'sms') {
          $data = $this->sendSMS($item);
          $return[] = "update messages set processed = 1, processed_return = '" . $data . "' where mid=" . $mid . ';';
        }
      }
    }

    // update all rows
    for ($j=0; $j<sizeof($return); $j++) {
      $query = $this->db->query($return[$j]);
    }
    
  }

// send sms 
 function sendSMS($params)
  {
        $to = $params->mto;
        $message = $params->message;
    // echo $to, $message . "<br>";
    // return;
      $URL = "http://login.smsmoon.com/API/sms.php";
    $post_fields = array(
        'username' => 'raghuedu',
        'password' => 'abcd.1234',
        'from' => 'RAGHUT',
        'to' => $to,
        'msg' => $message,
        'type' => '1',
        'dnd_check' => '0'
    );
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $URL);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_exec($ch);
  }

// sending email
  function sendEmail($params)
  {
    $to = $params->mto;
        $cc = $params->cc;
    $subject = $params->subject;
    $message = $params->message;
        $type = $params->mailtype;
    // echo $to, $cc, $subject, $message, $type . "<br>";
    // return;
    if ($to != '' && $subject != '' && $message != '') {
      $config = Array(
        'protocol' => 'smtp',
        'smtp_host' => $this->config->item('smtp_host'),
        'smtp_port' => $this->config->item('smtp_port'),
        'smtp_user' => $this->config->item('smtp_user'),
        'smtp_pass' => $this->config->item('smtp_pass'),
        'mailtype'  => 'html', 
        'charset'   => 'iso-8859-1'
      );
            $domain = $this->config->item('domain');
            if ($type != '') {
                $this->email->set_mailtype($type);
                $message =
                  '<html><head>
                  <link href="' . $domain . '/assets/global/bootstrap/css/bootstrap.css" rel="stylesheet" type="text/css" />
                  <link href="' . $domain . '/assets/global/css/components.css" rel="stylesheet" id="style_components" type="text/css" />
                  </head><body>' . $message . '</body></html>';
            }
      $this->load->library('email', $config);
      $this->email->set_newline("\r\n");
      $this->load->library('email');
      $this->email->from('researchlabs@raghueducational.org');
      $this->email->to($to);
      if ($cc) 
                $this->email->cc($cc);
      // $this->email->bcc('techlead.it@raghues.com');
      $this->email->subject($subject);
      $this->email->message($message);
      $st = $this->email->send();
            // debug: print_r($st);
      return $st;
      // if($st) {
      //  return (array("success" => true));
      // } else {
      //  return (array("success" => false, "error" => 'Unable to send mail' . $this->email->print_debugger()));
      // }
    } else {
            // return (array("success" => false, "error" => 'Unable to send mail. To, subject or message is null'));
        }
  }



		
	function getData($type, $params=null) {
       
		$success = true;
		$error = '';
		$result = '';
		$response = [];
				
		if(!$_SERVER['HTTP_TOKEN']) {
			$success = false;
			$error = "Token not provided";
		}
		
		if ($success) {
			try 
			{
				$token = JWT::decode($_SERVER['HTTP_TOKEN'], $this->config->item('jwt_key'));
	
				if ($token->userid) {		
					switch($type) {
						case 'userData'				: $result = $this->api_model->userData($token->userid); break;
						case 'changePassword'		: $result = $this->api_model->changePassword($token->userid, $params); break;
                        case 'getHistory'           : $result = $this->api_model->get_History($params); break;


						
					}
				
					$success = true;
				}
			} 
			catch (Exception $e)
			{
				$success = false;
				$error = "Token authentication failed";
			}					
		}
		
		$response['success'] = $success;
		$response['error'] = $error;
		if ($success) {
			$response['data'] = $result;
		}		
		$this->response($response);
	}

	// user data
	public function userData_post()
	{
		$this->getData('userData', []);
	}

	// // Change Password
	public function changePassword_post()
	{
		$old_pass = $this->post('old_pass');
		$new_pass = $this->post('new_pass');

		$this->getData('changePassword', [$old_pass, $new_pass]);
	}
	

public function assignleaves_post(){
 
 $reg_no=$this->post('reg_no');
 $leaves=$this->post('leaves');
  $name=$this->post('name');
  
  
		$success = true;
		$error = '';
		$result = '';
		$response = [];
				
		if(!$_SERVER['HTTP_TOKEN']) {
			$success = false;
			$error = "Token not provided";
		}
		
		if ($success) {

				$token = JWT::decode($_SERVER['HTTP_TOKEN'], $this->config->item('jwt_key'));
	
				if ($token->userid) {
					$receive = $this->api_model->assignleaves($reg_no,$leaves,$name);
        

return $this->response('');	 
		 
      }
        }
}





public function addleavetype_post(){
 
 $type=$this->post('type');
 $typename=$this->post('typename');
  $lstatus=$this->post('lstatus');
  $value=$this->post('value');
  $carry=$this->post('carry');
  
		$success = true;
		$error = '';
		$result = '';
		$response = [];
				
		if(!$_SERVER['HTTP_TOKEN']) {
			$success = false;
			$error = "Token not provided";
		}
		
		if ($success) {

				$token = JWT::decode($_SERVER['HTTP_TOKEN'], $this->config->item('jwt_key'));
	
				if ($token->userid) {
					$receive = $this->api_model->addleavetype($type,$typename,$lstatus,$value,$carry);
          if($receive==''){

return $this->response('');	 
		  }else{
			  return $this->response($receive);	
		  }
      }
        }
}



public function deleteleavetype_post(){
 
 $type=$this->post('type');

  
		$success = true;
		$error = '';
		$result = '';
		$response = [];
				
		if(!$_SERVER['HTTP_TOKEN']) {
			$success = false;
			$error = "Token not provided";
		}
		
		if ($success) {

				$token = JWT::decode($_SERVER['HTTP_TOKEN'], $this->config->item('jwt_key'));
	
				if ($token->userid) {
				$this->db->where('type',$this->post('type'));
$typename=$this->db->get('raghuerp.leavetypes')->row()->typename;


					$sql="DELETE FROM `raghuerp`.`leavetypes` WHERE type='$type'" ;
 $this->db->query($sql);

 $total=$this->db->query("update raghuerp.Type_of_leave a set a.Total=(a.Total-a.".$typename."),a.Remaining=(a.Remaining-a.".$typename.")");

 $sql2="ALTER TABLE `raghuerp`.`Type_of_leave` DROP `$typename`" ;
 $this->db->query($sql2);
 
          
return $this->response('');	 
      }
        }
}


public function getnewUserslist_get(){
   
		$success = true;
		$error = '';
		$result = '';
		$response = [];
				
		if(!$_SERVER['HTTP_TOKEN']) {
			$success = false;
			$error = "Token not provided";
		}
		
		if ($success) {

				$token = JWT::decode($_SERVER['HTTP_TOKEN'], $this->config->item('jwt_key'));
	
				if ($token->userid) {
			


           $sql2="select s.reg_no,s.dispname,s.employment_type,(select department from raghuerp_db.departments where raghuerp_db.departments.id=s.department ) as department,(select college from raghuerp_db.colleges where s.college=raghuerp_db.colleges.id ) as college,s.designation from raghuerp_db.staff s WHERE NOT EXISTS ( SELECT 1 FROM raghuerp.Type_of_leave t WHERE s.reg_no = t.reg_no )" ;
           $result=$this->db->query($sql2)->result();
 
          
return $this->response($result);	 


      }
        }
}



public function addholiday_post(){
 
 $holdate=$this->post('holdate');
 $holtype=$this->post('holtype');
 $holname=$this->post('holname');
  
		$success = true;
		$error = '';
		$result = '';
		$response = [];
				
		if(!$_SERVER['HTTP_TOKEN']) {
			$success = false;
			$error = "Token not provided";
		}
		
		if ($success) {

				$token = JWT::decode($_SERVER['HTTP_TOKEN'], $this->config->item('jwt_key'));
	
				if ($token->userid) {
    $receive = $this->api_model->add_Holiday($holdate,$holtype,$holname);

  if($receive==''){

return $this->response('');	 
		  }else{
			  return $this->response($receive);	
		  }
}              
  }
}



public function getrolesname_get(){
   
		$success = true;
		$error = '';
		$result = '';
		$response = [];
				
		if(!$_SERVER['HTTP_TOKEN']) {
			$success = false;
			$error = "Token not provided";
		}
		
		if ($success) {

				$token = JWT::decode($_SERVER['HTTP_TOKEN'], $this->config->item('jwt_key'));
	
				if ($token->userid) {
    $receive = $this->api_model->getrolesname();

return $this->response($receive);	 
		  
}              
  }
}

public function deleteuserrole_post(){
 
 $role_id=$this->post('role_id');

  
		$success = true;
		$error = '';
		$result = '';
		$response = [];
				
		if(!$_SERVER['HTTP_TOKEN']) {
			$success = false;
			$error = "Token not provided";
		}
		
		if ($success) {

				$token = JWT::decode($_SERVER['HTTP_TOKEN'], $this->config->item('jwt_key'));
	
				if ($token->userid) {
    $receive = $this->api_model->delete_userrole($role_id);

return $this->response('');	 
		  
}              
  }
}

public function getroleslist_get(){
   
		$success = true;
		$error = '';
		$result = '';
		$response = [];
				
		if(!$_SERVER['HTTP_TOKEN']) {
			$success = false;
			$error = "Token not provided";
		}
		
		if ($success) {

				$token = JWT::decode($_SERVER['HTTP_TOKEN'], $this->config->item('jwt_key'));
	
				if ($token->userid) {
    $receive = $this->api_model->getroleslist();

     return $this->response($receive);
}              
  }
}



public function adduserrole_post(){
 
 $reg_no=$this->post('reg_no');
 $role=$this->post('role');
 $type=$this->post('type');
 $upto=$this->post('upto');

  
		$success = true;
		$error = '';
		$result = '';
		$response = [];
				
		if(!$_SERVER['HTTP_TOKEN']) {
			$success = false;
			$error = "Token not provided";
		}
		
		if ($success) {

				$token = JWT::decode($_SERVER['HTTP_TOKEN'], $this->config->item('jwt_key'));
	
				if ($token->userid) {
    $receive = $this->api_model->adduserrole($reg_no,$role,$type,$upto);
	if($receive==''){

     return $this->response('');
	}else{
		return $this->response($receive);
	}
}              
  }
}
public function changeuserrole_post(){
 
 $reg_no=$this->post('reg_no');
 $role=$this->post('role');
 $role_id=$this->post('role_id');
  
		$success = true;
		$error = '';
		$result = '';
		$response = [];
				
		if(!$_SERVER['HTTP_TOKEN']) {
			$success = false;
			$error = "Token not provided";
		}
		
		if ($success) {

				$token = JWT::decode($_SERVER['HTTP_TOKEN'], $this->config->item('jwt_key'));
	
				if ($token->userid) {
    $receive = $this->api_model->changeuserrole($reg_no,$role,$role_id);
if($receive==''){
     return $this->response('');
}else{
	return $this->response($receive);
}
}              
  }
}

public function changeconfig_post(){
 
 $designation=$this->post('designation');
 $email=$this->post('email');
 $sno=$this->post('sno');
  
		$success = true;
		$error = '';
		$result = '';
		$response = [];
				
		if(!$_SERVER['HTTP_TOKEN']) {
			$success = false;
			$error = "Token not provided";
		}
		
		if ($success) {

				$token = JWT::decode($_SERVER['HTTP_TOKEN'], $this->config->item('jwt_key'));
	
				if ($token->userid) {
    $receive = $this->api_model->changeconfig($sno,$email,$designation);

     return $this->response('');

}              
  }
}
public function deleteconfig_post(){
 

 $sno=$this->post('sno');
  
		$success = true;
		$error = '';
		$result = '';
		$response = [];
				
		if(!$_SERVER['HTTP_TOKEN']) {
			$success = false;
			$error = "Token not provided";
		}
		
		if ($success) {

				$token = JWT::decode($_SERVER['HTTP_TOKEN'], $this->config->item('jwt_key'));
	
				if ($token->userid) {
    $receive = $this->api_model->deleteconfig($sno);

	return $this->response($receive);

}              
  }
}
public function addconfig_post(){
 
 $designation=$this->post('designation');
 $email=$this->post('email');

  
		$success = true;
		$error = '';
		$result = '';
		$response = [];
				
		if(!$_SERVER['HTTP_TOKEN']) {
			$success = false;
			$error = "Token not provided";
		}
		
		if ($success) {

				$token = JWT::decode($_SERVER['HTTP_TOKEN'], $this->config->item('jwt_key'));
	
				if ($token->userid) {
    $receive = $this->api_model->addconfig($email,$designation);
if($receive=='already exists'){
     return $this->response('already exists');
}else{
	return $this->response($receive);
}
}              
  }
}

public function editholiday_post(){
 
 $holdate=$this->post('holdate');
 $holtype=$this->post('holtype');
 $holname=$this->post('holname');
  
		$success = true;
		$error = '';
		$result = '';
		$response = [];
				
		if(!$_SERVER['HTTP_TOKEN']) {
			$success = false;
			$error = "Token not provided";
		}
		
		if ($success) {

				$token = JWT::decode($_SERVER['HTTP_TOKEN'], $this->config->item('jwt_key'));
	
				if ($token->userid) {
    $receive = $this->api_model->edit_Holiday($holdate,$holtype,$holname);

     return $this->response('');
}              
  }
}


public function updateleavetype_post(){
 
 $type=$this->post('type');
 $lstatus=$this->post('lstatus');
  $typename=$this->post('typename');
  $value=$this->post('value');
   $carry=$this->post('carry');
  
		$success = true;
		$error = '';
		$result = '';
		$response = [];
				
		if(!$_SERVER['HTTP_TOKEN']) {
			$success = false;
			$error = "Token not provided";
		}
		
		if ($success) {

				$token = JWT::decode($_SERVER['HTTP_TOKEN'], $this->config->item('jwt_key'));
	
				if ($token->userid) {
					if($lstatus=='disable'){
					$sql="update raghuerp.leavetypes l set l.lstatus='$lstatus',l.totaldays=0,l.carryfwd=0 where type='$type' ";
			
 $this->db->query($sql);
 $sql2="update raghuerp.Type_of_leave  set Total=Total-".$typename.",Remaining=Remaining-".$typename.",$typename=0 ";
			
 $this->db->query($sql2);


					}else if($lstatus=='enable'){
$sql="update raghuerp.leavetypes l set l.lstatus='$lstatus',l.totaldays='$value',l.carryfwd='$carry' where type='$type' ";
 $this->db->query($sql);
  $sql2="update raghuerp.Type_of_leave  set Total=Total+".$value.",Remaining=Remaining+".$value.",$typename='$value' ";
			
 $this->db->query($sql2);
					}
          
return $this->response('');	 
      }
        }
}

public function getadmltypes_get(){
 
  
		$success = true;
		$error = '';
		$result = '';
		$response = [];
				
		if(!$_SERVER['HTTP_TOKEN']) {
			$success = false;
			$error = "Token not provided";
		}
		
		if ($success) {

				$token = JWT::decode($_SERVER['HTTP_TOKEN'], $this->config->item('jwt_key'));
	
				if ($token->userid) {
					$sql="select * from raghuerp.leavetypes ";
$receive = $this->db->query($sql)->result();
          
return $this->response($receive);	 
      }
        }
}

public function logStatus_post(){

$logstatus['username']=$this->post("username");
$logstatus['password']=$this->post("password");


$result = $this->api_model->logstat($logstatus);
	
		if ($result) {
			//user logged in, generate a token and return
			$id = $result->reg_no;
			$token = array();
			$token['userid'] = $id;
			$result->token = JWT::encode($token, $this->config->item('jwt_key'));
		
			$this->response($result);
		} else {
		//	authentication failed, return error
		

			$this->response(false);
			
		}

}

public function getrole_post(){
 $id=$this->post('reg_no');
  $utype=$this->post('utype');

  
		$success = true;
		$error = '';
		$result = '';
		$response = [];
				
		if(!$_SERVER['HTTP_TOKEN']) {
			$success = false;
			$error = "Token not provided";
		}
		
		if ($success) {

				$token = JWT::decode($_SERVER['HTTP_TOKEN'], $this->config->item('jwt_key'));
	
				if ($token->userid) {
				
			if($utype=='stf'){		
  $sql="select concat('') as reg_no,concat('') as  dispname,concat('') as designation,concat('') as department,concat('') as college,raghuerp.Role.role from raghuerp.Role where   raghuerp.Role.reg_no='$id' ";
$query = $this->db->query($sql)->result();
$query2 = $this->db->query($sql)->row();
if(sizeof($query)!=0){

	return $this->response($query2);	

}else if(sizeof($query)==0){
	
   $sql="select concat('') as reg_no,concat('') as  dispname,concat('') as designation,concat('') as department,concat('') as college,concat('Staff') as role ";
$query = $this->db->query($sql)->row();  

return $this->response($query);	
}

// $receive = $this->db->query("select Role.role,staff.dispname,staff.college,staff.department,staff.designation from Role,staff where Role.reg_no='$id' and staff.reg_no='$id'")->row();
         
// return $this->response($receive);	 
       }else if($utype=='adm'){

$sql="select concat('') as reg_no,concat('') as name,raghuerp.Role.role from raghuerp.Role where raghuerp.Role.reg_no='$id' ";
$query = $this->db->query($sql)->row();

	return $this->response($query);	


}else if($utype='std'){
return $this->response('student role');	
}
        }	
}
}

public function postData_post(){



$insert['stud_name']=$this->post("stud_name");
$insert['percentage']=$this->post("percentage");
$insert['dept']=$this->post("dept");
$insert['username']=$this->post("username");
$insert['password']=$this->post("password");
$result = $this->api_model->add_Data($insert);
if(!$result){
$this->response('insert',200); 
}
else{
$this->response('fail',400); 
}
}



public function leaveApplys_post(){


$insert['reg_no']=$this->post("reg_no");
$insert['type_of_leave']=$this->post("type");
$insert['from_date']=$this->post("from");
$insert['to_date']=$this->post("to");
$insert['reason']=$this->post("reason");
$insert['status']=$this->post("status");
$insert['remarks']=$this->post("remarks");
$insert['days']=$this->post("days");
$insert['lop']=$this->post("lop");
$insert['previous']=$this->post("prev");
$insert['alternateId']=$this->post("alternateId");
$insert['alternateStatus']=$this->post("alternateStatus");

$emails=$this->post("emails");
$empname=$this->post('name');
$dept=$this->post('dept');
$colg=$this->post('colg');
$altrId=$this->post('alternateId');

$id=$insert['reg_no'];
$from=$insert['from_date'];
$to=$insert['to_date'];
//$result = $this->HomeModel->leave_Apply($insert);
	$success = true;
		$error = '';
		$result = '';
		$response = [];
				
		if(!$_SERVER['HTTP_TOKEN']) {
			$success = false;
			$error = "Token not provided";
		}
		
		if ($success) {

				$token = JWT::decode($_SERVER['HTTP_TOKEN'], $this->config->item('jwt_key'));
	
				if ($token->userid) {

  
    $this->db->where('reg_no',$this->post('reg_no'));
     $remaining =   $this->db->get('raghuerp.Type_of_leave')->row()->Remaining;

    
$sql="SELECT *FROM raghuerp.leave_issues WHERE (('$from' <= from_date AND '$to' > from_date) OR ('$from' < to_date AND '$to' >= to_date)) and  reg_no = '$id' AND status != 'Rejected' AND status != 'Cancelled' AND status != 'Alternate Suggestion'";
    // $sql="SELECT *FROM raghuerp.leave_issues WHERE (('$from' <= from_date AND '$from' >= to_date) OR ('$to' <= from_date AND '$to' >= to_date)) AND reg_no = '$id' AND status != 'Rejected' AND status != 'Cancelled' AND status != 'Alternate Suggestion'";

    $query = $this->db->query($sql);

    $go = $query->result(); 

     if($go){
$result='Already Exists';
}else{

	$this->db->where('type',$this->post('type'));
$ltype=$this->db->get('raghuerp.leavetypes')->row()->typename;






$ve=2;

$message = $empname .' Details,<br /><br />
           '. $empname .' applied leave Request<br /><br />
              -------------------------------------------------<br /><br />
              <table>
              <thead>
			   <tr>
              <th colspan='.$ve.'>leave details</th>
              </tr>
			   </thead>
			   <tbody>
              <tr>
              <td>Faculty Id</th>
			  <td>' . $insert['reg_no'] . '</td>
			  </tr>
			  <tr>
              <td>Department</th>
			  <td>' . $dept . '</td>
			  </tr>
			  <tr>
              <td>College</th>
			  <td>' . $colg . '</td>
			  </tr>
			  <tr>
              <td>From Date</th>
			  <td>' . $insert['from_date'] . '</td>
			  </tr>
			  <tr>
              <td>To Date</th>
			  <td>' . $insert['to_date'] . '</td>
			  </tr>
			  <tr>
              <td>Duration</th>
			  <td>' . $insert['days'] . ' days</td>
			  </tr>
			   <tr>
              <td>Delegated Person</th>
			  <td>' . $altrId . '</td>
			  </tr>
              </tbody>
              </table>
              -------------------------------------------------<br /><br />
        
          Thanks<br/>REI Team';

         

if($insert['type_of_leave']!='LOP'){
	   
$this->db->where('reg_no',$this->post('reg_no'));
$sl=$this->db->get('raghuerp.Type_of_leave')->row()->$ltype;

//$sql2="select reg_no,email,dispname from raghuerp_db.staff where (((role='Hod' and department='$dept' ||  role='Principal' )  and college='$colg' )|| reg_no='$altrId'||reg_no='$id')" ;

//$test=$this->db->query($sql2)->result();


$te=sizeof($emails);
$mail=[];
$i=0;

    // foreach($test as $a){
// echo $a['email'].' test';
//echo $a->email.' test';

// $mail[$i]= $a->email;
  
//     $i++;

// }
// foreach($emails as $na){

// 	$mail[$i]= $na;

// 	$i++;
// }


if($sl>=$insert['days']){
    $result ='apply';
    if($insert['previous']=='0'){
    $insert['previous']=$insert['days'];
    }
     $this->api_model->leave_Apply($insert);
     $lastid=$this->db->insert_id();
 
    $this->db->set($ltype,($sl-$insert['days']));
     $this->db->set('Remaining',($remaining-$insert['previous']));
    $this->db->where('reg_no',$this->post('reg_no'));
    $this->db->update('raghuerp.Type_of_leave');



}else if($insert['lop']>0){
    $result ='apply';
    // if($insert['previous']=='0'){
    // $insert['previous']=$sl;
    // }
     $this->api_model->leave_Apply($insert);
     $lastid=$this->db->insert_id();
 
    $this->db->set($ltype,'0');
         $this->db->set('Remaining',($remaining-$insert['previous']));
    $this->db->where('reg_no',$this->post('reg_no'));
    $this->db->update('raghuerp.Type_of_leave');

    $this->db->where('reg_no',$this->post('reg_no'));
$l=$this->db->get('raghuerp.Type_of_leave')->row()->LOP;
$lp=$insert['lop'];
$sql="UPDATE raghuerp.Type_of_leave SET LOP = $lp+$l WHERE reg_no='$id'";
   $this->db->query($sql);
   $sql2="UPDATE raghuerp.leave_issues SET  lop='$lp' WHERE leave_id='$lastid'";
   $this->db->query($sql2);


}else if($sl<$insert['days']){
    $result='lop';
     $send=$sl;

}
}else if($insert['type_of_leave']=='LOP'){

 
    $insert['lop']=$insert['days'];
    
    $result ='apply';
     $this->api_model->leave_Apply($insert);
       $this->db->where('reg_no',$this->post('reg_no'));
$l=$this->db->get('raghuerp.Type_of_leave')->row()->LOP;
    $this->db->set($ltype,$insert['days']+$l);
    $this->db->where('reg_no',$this->post('reg_no'));
    $this->db->update('raghuerp.Type_of_leave');


}

     }

if($result=='apply'){

$data['info']='insert';

for($a=0;$a<sizeof($emails);$a++){
     $params = Array(
        'to' => $emails[$a],
        'cc' => '',
        'subject' => 'Leave Status',
        'message' => $message,
        'apptype' => 'LeaveSystem',
        'type'  => 'html', 
      );
        $this->api_model->sendEmail($params);

}

// $this->api_model->sendEmail($emails,$message);
$this->response($data); 
}
else if($result=='lop'){
    $data['send']=$send;
    $data['info']='lop';
$this->response($data); 
}
else if($result=='Already Exists'){
    $data['send']=$go;
    $data['info']=$result;
   $this->response($data); 
}
}
 }
}


public function applyCoff_post(){


$insert['reg_no']=$this->post("reg_no");
$insert['from_date']=$this->post("from");
$insert['to_date']=$this->post("to");
$insert['reason']=$this->post("reason");
$insert['status']=$this->post("status");
$insert['days']=$this->post("days");


$emails=$this->post("emails");
$empname=$this->post('name');
$dept=$this->post('dept');
$colg=$this->post('colg');

$id=$insert['reg_no'];
$from=$insert['from_date'];
$to=$insert['to_date'];
//$result = $this->HomeModel->leave_Apply($insert);
	$success = true;
		$error = '';
		$result = '';
		$response = [];
				
		if(!$_SERVER['HTTP_TOKEN']) {
			$success = false;
			$error = "Token not provided";
		}
		
		if ($success) {

				$token = JWT::decode($_SERVER['HTTP_TOKEN'], $this->config->item('jwt_key'));
	
				if ($token->userid) {

  
    $this->db->where('reg_no',$this->post('reg_no'));
     $remaining =   $this->db->get('raghuerp.Type_of_leave')->row()->Remaining;

    
$sql="SELECT *FROM raghuerp.coff WHERE (('$from' <= from_date AND '$to' > from_date) OR ('$from' < to_date AND '$to' >= to_date)) and  reg_no = '$id' AND status != 'Rejected' ";
    // $sql="SELECT *FROM raghuerp.leave_issues WHERE (('$from' <= from_date AND '$from' >= to_date) OR ('$to' <= from_date AND '$to' >= to_date)) AND reg_no = '$id' AND status != 'Rejected' AND status != 'Cancelled' AND status != 'Alternate Suggestion'";

    $query = $this->db->query($sql);

    $go = $query->result(); 

     if($go){
$result='Already Exists';
}else{



$ve=2;

$message = $empname .' Details,<br /><br />
           '. $empname .' applied coff \'s Request<br /><br />
              -------------------------------------------------<br /><br />
              <table>
              <thead>
			   <tr>
              <th colspan='.$ve.'>leave details</th>
              </tr>
			   </thead>
			   <tbody>
              <tr>
              <td>Faculty Id</th>
			  <td>' . $insert['reg_no'] . '</td>
			  </tr>
			  <tr>
              <td>Department</th>
			  <td>' . $dept . '</td>
			  </tr>
			  <tr>
              <td>College</th>
			  <td>' . $colg . '</td>
			  </tr>
			  <tr>
              <td>From Date</th>
			  <td>' . $insert['from_date'] . '</td>
			  </tr>
			  <tr>
              <td>To Date</th>
			  <td>' . $insert['to_date'] . '</td>
			  </tr>
			  <tr>
              <td>Duration</th>
			  <td>' . $insert['days'] . ' days </td>
			  </tr>
              </tbody>
              </table>
              -------------------------------------------------<br /><br />
        
          Thanks<br/>REI Team';

         


    $result ='apply';
     $this->api_model->coff_Apply($insert);
 
     }

if($result=='apply'){

$data['info']='insert';
for($a=0;$a<sizeof($emails);$a++){
     $params = Array(
        'to' => $emails[$a],
        'cc' => '',
        'subject' => 'Leave Status',
        'message' => $message,
        'apptype' => 'LeaveSystem',
        'type'  => 'html', 
      );
        $this->api_model->sendEmail($params);

}
// $this->api_model->sendEmail($emails,$message);
$this->response($data); 
}

else if($result=='Already Exists'){
    $data['send']=$go;
    $data['info']=$result;
   $this->response($data); 
}
}
 }
}

public function getInfos_get(){
 
$receive = $this->api_model->get_Info(); 
return $this->response($receive);	 

}


//coff history based on reg_no
public function coffHistory_post(){
  
    $insert['emp_id']=$this->post("reg_no");
    
		$success = true;
		$error = '';
		$result = '';
		$response = [];
				
		if(!$_SERVER['HTTP_TOKEN']) {
			$success = false;
			$error = "Token not provided";
		}
		
		if ($success) {

				$token = JWT::decode($_SERVER['HTTP_TOKEN'], $this->config->item('jwt_key'));
	
				if ($token->userid) {

    //$this->getData('getHistory', [$insert['emp_id']]);
 $receive = $this->api_model->get_coffHistory($insert['emp_id']); 

 if($receive){
    return $this->response($receive);
 }else{
     return $this->response(false);
 }
                 }
        }


}

//leavehistory based on reg_no
public function getHistory_post(){
  
    $insert['emp_id']=$this->post("reg_no");
    
		$success = true;
		$error = '';
		$result = '';
		$response = [];
				
		if(!$_SERVER['HTTP_TOKEN']) {
			$success = false;
			$error = "Token not provided";
		}
		
		if ($success) {

				$token = JWT::decode($_SERVER['HTTP_TOKEN'], $this->config->item('jwt_key'));
	
				if ($token->userid) {

    //$this->getData('getHistory', [$insert['emp_id']]);
 $receive = $this->api_model->get_History($insert['emp_id']); 

 if($receive){
    return $this->response($receive);
 }else{
     return $this->response(false);
 }
                 }
        }


}



public function coffStatus_post(){

      $dept=$this->post('dept');
     $role=$this->post('role');
     $colg=$this->post('colg');
     
		$success = true;
		$error = '';
		$result = '';
		$response = [];
				
		if(!$_SERVER['HTTP_TOKEN']) {
			$success = false;
			$error = "Token not provided";
		}
		
		if ($success) {

				$token = JWT::decode($_SERVER['HTTP_TOKEN'], $this->config->item('jwt_key'));
	
				if ($token->userid) {
  


        $sql="SELECT raghuerp.coff.*,concat('') as dispname,concat('') as college,concat('') as department,concat('') as designation,concat('') as role FROM raghuerp.coff
		ORDER BY
   CASE raghuerp.coff.status
      WHEN 'Pending' THEN 1
      WHEN 'Accepted' THEN 2
      WHEN 'Rejected' THEN 3
       WHEN 'Cancelled' THEN 4
      ELSE 5
   END ";
$query = $this->db->query($sql);

$receive = $query->result();
    
         
return $this->response($receive);
      }
        }

}

public function getStatus_post(){

      $dept=$this->post('dept');
     $role=$this->post('role');
     $colg=$this->post('colg');
     
		$success = true;
		$error = '';
		$result = '';
		$response = [];
				
		if(!$_SERVER['HTTP_TOKEN']) {
			$success = false;
			$error = "Token not provided";
		}
		
		if ($success) {

				$token = JWT::decode($_SERVER['HTTP_TOKEN'], $this->config->item('jwt_key'));
	
				if ($token->userid) {
  
    if($role=='HOD'){

        $sql="SELECT raghuerp.leave_issues.* ,concat('') as dispname,concat('') as college,concat('') as department,concat('') as designation ,concat('') as  Altername,concat('') as  Alterdept,concat('') as  Altercolg FROM raghuerp.leave_issues where raghuerp.leave_issues.status IN ('Pending','Cancelled','Rejected','Accepted','Alternate Suggestion')
ORDER BY
   CASE raghuerp.leave_issues.status
      WHEN 'Pending' THEN 1
      WHEN 'Accepted' THEN 2
      WHEN 'Rejected' THEN 3
      WHEN 'Alternate Suggestion' THEN 4
       WHEN 'Cancelled' THEN 5
      ELSE 6
   END";
$query = $this->db->query($sql);

$receive = $query->result();
    
}else if($role=='Principal'){

     $sql="SELECT raghuerp.leave_issues.* ,concat('') as dispname,concat('') as college,concat('') as department,concat('') as designation,concat('') as role, concat('') as Altername,concat('') as Alterdept,concat('') as  Altercolg,concat('') as Hodname FROM raghuerp.leave_issues where (raghuerp.leave_issues.status='Pending' || raghuerp.leave_issues.status='Accepted')  ";
$query = $this->db->query($sql);

$receive = $query->result();
    }else if($role == 'Management'){

       $sql="SELECT raghuerp.leave_issues.* ,concat('') as dispname,concat('') as  college,concat('') as department,concat('') as designation,concat('') as role FROM raghuerp.leave_issues where raghuerp.leave_issues.status ='Pending' ";

$query = $this->db->query($sql);

$receive = $query->result(); 
    }

          
return $this->response($receive);
      }
        }

}

public function getEmpdata_post(){
  $role=$this->post('role');
  $dept=$this->post('dept');
  $colg=$this->post('colg');
  
		$success = true;
		$error = '';
		$result = '';
		$response = [];
				
		if(!$_SERVER['HTTP_TOKEN']) {
			$success = false;
			$error = "Token not provided";
		}
		
		if ($success) {

				$token = JWT::decode($_SERVER['HTTP_TOKEN'], $this->config->item('jwt_key'));
	
				if ($token->userid) {
$receive = $this->api_model->get_Empdata($role,$dept,$colg);
          
return $this->response($receive);	 
      }
        }
}


public function getTypeleavesdata_get(){
 
  
		$success = true;
		$error = '';
		$result = '';
		$response = [];
				
		if(!$_SERVER['HTTP_TOKEN']) {
			$success = false;
			$error = "Token not provided";
		}
		
		if ($success) {

				$token = JWT::decode($_SERVER['HTTP_TOKEN'], $this->config->item('jwt_key'));
	
				if ($token->userid) {
$receive = $this->api_model->getTypeleavesdata();
          
return $this->response($receive);	 
      }
        }
}

public function empOne_post(){
  $id=$this->post('emp_id');
  
		$success = true;
		$error = '';
		$result = '';
		$response = [];
				
		if(!$_SERVER['HTTP_TOKEN']) {
			$success = false;
			$error = "Token not provided";
		}
		
		if ($success) {

				$token = JWT::decode($_SERVER['HTTP_TOKEN'], $this->config->item('jwt_key'));
	
				if ($token->userid) {
$receive = $this->api_model->emp_One($id);
         
return $this->response($receive);	 
       }
        }
}
public function empOneleaves_get(){

  
		$success = true;
		$error = '';
		$result = '';
		$response = [];
				
		if(!$_SERVER['HTTP_TOKEN']) {
			$success = false;
			$error = "Token not provided";
		}
		
		if ($success) {

				$token = JWT::decode($_SERVER['HTTP_TOKEN'], $this->config->item('jwt_key'));
	
				if ($token->userid) {
$receive = $this->api_model->emp_Oneleaves();
         
return $this->response($receive);	 
       }
        }
}

public function emp_OneAvail_post(){
  $id=$this->post('emp_id');
   $lid=$this->post('leave_id');
   
		$success = true;
		$error = '';
		$result = '';
		$response = [];
				
		if(!$_SERVER['HTTP_TOKEN']) {
			$success = false;
			$error = "Token not provided";
		}
		
		if ($success) {

				$token = JWT::decode($_SERVER['HTTP_TOKEN'], $this->config->item('jwt_key'));
	
				if ($token->userid) {

$sql="SELECT *FROM raghuerp.leave_issues INNER JOIN raghuerp.Type_of_leave ON raghuerp.leave_issues.reg_no = raghuerp.Type_of_leave.reg_no AND raghuerp.leave_issues.leave_id='$lid'";
    $query = $this->db->query($sql);
        
return $this->response($query->row());	 
        }
        }
}


public function getHoliday_get(){

		$success = true;
		$error = '';
		$result = '';
		$response = [];
				
		if(!$_SERVER['HTTP_TOKEN']) {
			$success = false;
			$error = "Token not provided";
		}
		
		if ($success) {

				$token = JWT::decode($_SERVER['HTTP_TOKEN'], $this->config->item('jwt_key'));
	
				if ($token->userid) {
    $receive = $this->api_model->get_Holiday();

     return $this->response($receive);
}              
  }
        }

		


public function getholidaylist_get(){

		$success = true;
		$error = '';
		$result = '';
		$response = [];
				
		if(!$_SERVER['HTTP_TOKEN']) {
			$success = false;
			$error = "Token not provided";
		}
		
		if ($success) {

				$token = JWT::decode($_SERVER['HTTP_TOKEN'], $this->config->item('jwt_key'));
	
				if ($token->userid) {
    $receive = $this->api_model->getholidaylist();

     return $this->response($receive);
}              
  }
        }


public function deleteholiday_post(){

   $sno=$this->post('sno');
  
   

		$success = true;
		$error = '';
		$result = '';
		$response = [];
				
		if(!$_SERVER['HTTP_TOKEN']) {
			$success = false;
			$error = "Token not provided";
		}
		
		if ($success) {

				$token = JWT::decode($_SERVER['HTTP_TOKEN'], $this->config->item('jwt_key'));
	
				if ($token->userid) {
    $receive = $this->api_model->delete_Holiday($sno);

     return $this->response('');
}              
  }
        }


public function checkHoliday_post(){

   $insert['from']=$this->post('from');
    $insert['to']=$this->post('to');
   

		$success = true;
		$error = '';
		$result = '';
		$response = [];
				
		if(!$_SERVER['HTTP_TOKEN']) {
			$success = false;
			$error = "Token not provided";
		}
		
		if ($success) {

				$token = JWT::decode($_SERVER['HTTP_TOKEN'], $this->config->item('jwt_key'));
	
				if ($token->userid) {
    $receive = $this->api_model->check_Holiday($insert);

     return $this->response($receive);
}              
  }
        }


public function empCount_post(){

   $insert['dept']=$this->post('dept');
     $insert['role']=$this->post('role');
     $insert['colg']=$this->post('colg');


		$success = true;
		$error = '';
		$result = '';
		$response = [];
				
		if(!$_SERVER['HTTP_TOKEN']) {
			$success = false;
			$error = "Token not provided";
		}
		
		if ($success) {

				$token = JWT::decode($_SERVER['HTTP_TOKEN'], $this->config->item('jwt_key'));
	
				if ($token->userid) {
    $receive = $this->api_model->emp_Count($insert);

     return $this->response($receive);
}              
  }
        }


public function upAcceptcoff_post(){
  $lid=$this->post('cid');
   $eid=$this->post('eid');
    $dept=$this->post('dept');
	 $colg=$this->post('colg');
	  $acceptedBy=$this->post('acceptedBy');
	   $name=$this->post('name');
	    $type=$this->post('type');
		 $uname=$this->post('username');
		  $days=$this->post('days');
		  $emailHod=$this->post('email');
		$success = true;
		$error = '';
		$result = '';
		$response = [];
				
		if(!$_SERVER['HTTP_TOKEN']) {
			$success = false;
			$error = "Token not provided";
		}
		
		if ($success) {

				$token = JWT::decode($_SERVER['HTTP_TOKEN'], $this->config->item('jwt_key'));
	
				if ($token->userid) {
$receive = $this->api_model->update_Acceptcoff($lid,$eid,$dept,$colg,$acceptedBy,$name,$type,$uname,$days,$emailHod);
               
if(!$receive)
{


return $this->response('');	 
}
else
{
    return $this->response('update failed',400);
}
 }
       }
}

public function updateAccept_post(){
  $lid=$this->post('lid');
   $eid=$this->post('eid');
    $dept=$this->post('dept');
	 $colg=$this->post('colg');
	  $acceptedBy=$this->post('acceptedBy');
	   $name=$this->post('name');
       $aid=$this->post('aid');
	    $type=$this->post('type');
		 $uname=$this->post('username');
		 $emailHod=$this->post('email');
		$success = true;
		$error = '';
		$result = '';
		$response = [];
				
		if(!$_SERVER['HTTP_TOKEN']) {
			$success = false;
			$error = "Token not provided";
		}
		
		if ($success) {

				$token = JWT::decode($_SERVER['HTTP_TOKEN'], $this->config->item('jwt_key'));
	
				if ($token->userid) {
$receive = $this->api_model->update_Accept($lid,$eid,$dept,$colg,$acceptedBy,$name,$aid,$type,$uname,$emailHod);
               
if(!$receive)
{

$send=$this->api_model->get_Status();
return $this->response($send);	 
}
else
{
    return $this->response('update failed',400);
}
 }
                }
}


public function currentDate_get(){

		$success = true;
		$error = '';
		$result = '';
		$response = [];
				
		if(!$_SERVER['HTTP_TOKEN']) {
			$success = false;
			$error = "Token not provided";
		}
		
		if ($success) {

				$token = JWT::decode($_SERVER['HTTP_TOKEN'], $this->config->item('jwt_key'));
	
				if ($token->userid) {
    $receive = $this->api_model->current_Date();
  
             
return $this->response($receive);	
   }
                }
}

// cron
public function leaveApprovalCR_get(){

	
    $receive = $this->api_model->leaveApprovalCR();
  
             
return $this->response($receive);	
   
                
}
// cron 
public function crontesting_get(){

	
    $receive = $this->api_model->crontesting();
  
             
return $this->response($receive);	
   
                
}

// cron 
public function carryforwardCR_get(){

	
    $receive = $this->api_model->carryforwardCR();
  
             
return $this->response($receive);	
   
                
}

// cron
public function temprolesdelCR_get(){

	
    $receive = $this->api_model->temprolesdelCR();
  
             
return $this->response($receive);	
   
                
}


public function todayData_post(){


   $insert['dept']=$this->post('dept');
     $insert['role']=$this->post('role');
     $insert['colg']=$this->post('colg');
     $insert['date']=$this->post('date');
     
		$success = true;
		$error = '';
		$result = '';
		$response = [];
				
		if(!$_SERVER['HTTP_TOKEN']) {
			$success = false;
			$error = "Token not provided";
		}
		
		if ($success) {

				$token = JWT::decode($_SERVER['HTTP_TOKEN'], $this->config->item('jwt_key'));
	
				if ($token->userid) {
$receive = $this->api_model->today_Data($insert);
          
if($receive)
{


return $this->response($receive);	 
}
else
{
    return $this->response(' failed',400);
}
      }
        }
}


public function allData_post(){


   $insert['dept']=$this->post('dept');
    $insert['inst']=$this->post('inst');
     $insert['role']=$this->post('role');
     $insert['colg']=$this->post('colg');
     $insert['day']=$this->post('day');
     
		$success = true;
		$error = '';
		$result = '';
		$response = [];
				
		if(!$_SERVER['HTTP_TOKEN']) {
			$success = false;
			$error = "Token not provided";
		}
		
		if ($success) {

				$token = JWT::decode($_SERVER['HTTP_TOKEN'], $this->config->item('jwt_key'));
	
				if ($token->userid) {
$receive = $this->api_model->all_Data($insert);
          
if($receive)
{


return $this->response($receive);	 
}
else
{
    return $this->response(' failed',400);
}
      }
        }
}

public function allMonthData_post(){


   $insert['dept']=$this->post('dept');
     $insert['role']=$this->post('role');
          $insert['colg']=$this->post('colg');
     $insert['year']=$this->post('year');
     $insert['month']=$this->post('month');
     
		$success = true;
		$error = '';
		$result = '';
		$response = [];
				
		if(!$_SERVER['HTTP_TOKEN']) {
			$success = false;
			$error = "Token not provided";
		}
		
		if ($success) {

				$token = JWT::decode($_SERVER['HTTP_TOKEN'], $this->config->item('jwt_key'));
	
				if ($token->userid) {
$receive = $this->api_model->all_monthData($insert);
       
if($receive)
{


return $this->response($receive);	 
}
else
{
    return $this->response(' failed',400);
}
         }
        }
}

public function allYearData_post(){


   $insert['dept']=$this->post('dept');
     $insert['role']=$this->post('role');
          $insert['colg']=$this->post('colg');
     $insert['year']=$this->post('year');
     
		$success = true;
		$error = '';
		$result = '';
		$response = [];
				
		if(!$_SERVER['HTTP_TOKEN']) {
			$success = false;
			$error = "Token not provided";
		}
		
		if ($success) {

				$token = JWT::decode($_SERVER['HTTP_TOKEN'], $this->config->item('jwt_key'));
	
				if ($token->userid) {
$receive = $this->api_model->all_yearData($insert);
       
if($receive)
{


return $this->response($receive);	 
}
else
{
    return $this->response(' failed',400);
}
         }
        }
}


public function getDept_post(){

$colg=$this->post('colg');

		$success = true;
		$error = '';
		$result = '';
		$response = [];
				
		if(!$_SERVER['HTTP_TOKEN']) {
			$success = false;
			$error = "Token not provided";
		}
		
		if ($success) {

				$token = JWT::decode($_SERVER['HTTP_TOKEN'], $this->config->item('jwt_key'));
	
				if ($token->userid) {
$sql="SELECT department as dept,CONCAT('0') as val FROM raghuerp_db.departments d WHERE d.college=(select id from raghuerp_db.colleges c where c.college='$colg')  group by department";
// $sql="SELECT department as dept,GROUP_CONCAT( DISTINCT department SEPARATOR '=0, ')as val FROM  staff WHERE college='$colg' GROUP BY department";
$query=$this->db->query($sql);
           
return $this->response($query->result());
}
     }
        }
public function getColg_get(){



		$success = true;
		$error = '';
		$result = '';
		$response = [];
				
		if(!$_SERVER['HTTP_TOKEN']) {
			$success = false;
			$error = "Token not provided";
		}
		
		if ($success) {

				$token = JWT::decode($_SERVER['HTTP_TOKEN'], $this->config->item('jwt_key'));
	
				if ($token->userid) {
$sql="SELECT college as colg,CONCAT('0')as val FROM raghuerp_db.colleges  group by college";
$result=$this->db->query($sql);
$query['colg']=$result->result();
 $sql2="SELECT d.department as dept,CONCAT('0')as val,c.college as colg FROM raghuerp_db.colleges c inner join raghuerp_db.departments d on d.college=c.id group by c.college,d.department";
$result2=$this->db->query($sql2);
$query['colgdata']=$result2->result();
         
return $this->response($query);
       }
        }
}

public function checkvalid_post(){

$id=$this->post('emp_id');


		$success = true;
		$error = '';
		$result = '';
		$response = [];
				
		if(!$_SERVER['HTTP_TOKEN']) {
			$success = false;
			$error = "Token not provided";
		}
		
		if ($success) {

				$token = JWT::decode($_SERVER['HTTP_TOKEN'], $this->config->item('jwt_key'));
	
				if ($token->userid) {
$sql="select * from raghuerp.Type_of_leave where reg_no='$id'";
$q1=$this->db->query($sql)->result();

        
return $this->response($q1);
}
        }
        }

public function getPrinc_post(){

$colg=$this->post('colg');
$dept=$this->post('dept');

		$success = true;
		$error = '';
		$result = '';
		$response = [];
				
		if(!$_SERVER['HTTP_TOKEN']) {
			$success = false;
			$error = "Token not provided";
		}
		
		if ($success) {

				$token = JWT::decode($_SERVER['HTTP_TOKEN'], $this->config->item('jwt_key'));
	
				if ($token->userid) {
// $sql="select raghuerp_db.staff.department as name, count(*) as y from raghuerp.leave_issues inner join raghuerp_db.staff on raghuerp.leave_issues.reg_no=raghuerp_db.staff.reg_no AND raghuerp.leave_issues.from_date <= concat(CURDATE(),' 23:00:00') and raghuerp.leave_issues.to_date > concat(CURDATE(),' 12:00:00') AND college='$colg' AND  raghuerp_db.staff.department!='' and raghuerp.leave_issues.status='Accepted'  group by raghuerp_db.staff.department";
// $sql="SELECT department as name,COUNT(*) as y FROM  raghuerp_db.staff WHERE college='$colg' GROUP BY department";
// $q1=$this->db->query($sql);
// $query['pm']=$q1->result();
// $sql2="select raghuerp_db.staff.department as name, count(*) as y from raghuerp.leave_issues inner join raghuerp_db.staff on raghuerp.leave_issues.reg_no=raghuerp_db.staff.reg_no AND raghuerp.leave_issues.from_date < concat(CURDATE(),' 12:00:00') and raghuerp.leave_issues.to_date >= concat(CURDATE(),' 00:00:00') AND college='$colg' AND raghuerp_db.staff.department!='' and raghuerp.leave_issues.status='Accepted' group by raghuerp_db.staff.department";
// $q2=$this->db->query($sql2);
// $query['am']=$q2->result();

// $a1=date("Y-m-d").' 09:00:00';
// $a2=date("Y-m-d").' 12:00:00';
// $a3=date("Y-m-d").' 17:00:00';
// $query['pm'] = $this->api_model->GetMultipleQueryResult("call college_leaves_summary('$a2','$a3','$colg')");
// $query['am'] = $this->api_model->GetMultipleQueryResult("call college_leaves_summary('$a1','$a2','$colg')");


$sql="select d.department as name, count(l.leave_id) as y from raghuerp_db.staff s left join raghuerp.leave_issues l on s.reg_no=l.reg_no  and l.from_date <= concat(CURDATE(),' 23:00:00') and l.to_date > concat(CURDATE(),' 12:00:00') and l.status='Accepted' inner join  raghuerp_db.departments d on s.department=d.id and s.college=(select id from raghuerp_db.colleges c where c.college='$colg') group by d.department";
$q1=$this->db->query($sql);
$query['pm']=$q1->result();
$sql2="select d.department as name, count(l.leave_id) as y from raghuerp_db.staff s left join raghuerp.leave_issues l on s.reg_no=l.reg_no and l.from_date < concat(CURDATE(),' 12:00:00') and l.to_date >= concat(CURDATE(),' 00:00:00') and l.status='Accepted' inner join raghuerp_db.departments d on s.department=d.id and s.college=(select id from raghuerp_db.colleges c where c.college='$colg') group by d.department";
$q2=$this->db->query($sql2);
$query['am']=$q2->result();

// $sql="call college_leaves_summary('$a1','$a2','$colg')";
// $query['am']=$this->db->query($sql)->result();


// $sql2="call college_leaves_summary('$a2','$a3','$colg')";
//   $query['pm']  =$this->db->query($sql2)->result();
    
return $this->response($query);
}
        }
        }


public function getDash_get(){

		$success = true;
		$error = '';
		$result = '';
		$response = [];
				
		if(!$_SERVER['HTTP_TOKEN']) {
			$success = false;
			$error = "Token not provided";
		}
		
		if ($success) {

				$token = JWT::decode($_SERVER['HTTP_TOKEN'], $this->config->item('jwt_key'));
	
				if ($token->userid) {
// $sql="select count(raghuerp_db.staff.reg_no) as emp_count,
//     (select count(*) as rec_pm from raghuerp.leave_issues inner join raghuerp_db.staff on raghuerp.leave_issues.reg_no=raghuerp_db.staff.reg_no AND raghuerp.leave_issues.from_date <= concat(CURDATE(),' 23:00:00') and raghuerp.leave_issues.to_date > concat(CURDATE(),' 12:00:00') AND raghuerp_db.staff.college='REC' AND raghuerp.leave_issues.status='Accepted') REC_pM,
//      (select count(*) as rec_am from raghuerp.leave_issues inner join raghuerp_db.staff on raghuerp.leave_issues.reg_no=raghuerp_db.staff.reg_no AND raghuerp.leave_issues.from_date < concat(CURDATE(),' 12:00:00') and raghuerp.leave_issues.to_date >= concat(CURDATE(),' 00:00:00') AND raghuerp_db.staff.college='REC' AND raghuerp.leave_issues.status='Accepted') REC_aM,
//      (select count(*) as rit_pm from raghuerp.leave_issues inner join raghuerp_db.staff on raghuerp.leave_issues.reg_no=raghuerp_db.staff.reg_no AND raghuerp.leave_issues.from_date <= concat(CURDATE(),' 23:00:00') and raghuerp.leave_issues.to_date > concat(CURDATE(),' 12:00:00') AND raghuerp_db.staff.college='RIT' AND raghuerp.leave_issues.status='Accepted') RIT_pM,
//      (select count(*) as rit_am from raghuerp.leave_issues inner join raghuerp_db.staff on raghuerp.leave_issues.reg_no=raghuerp_db.staff.reg_no AND raghuerp.leave_issues.from_date < concat(CURDATE(),' 12:00:00') and raghuerp.leave_issues.to_date >= concat(CURDATE(),' 00:00:00') AND raghuerp_db.staff.college='RIT' AND raghuerp.leave_issues.status='Accepted') RIT_aM,
//      (select count(*) as rcp_pm from raghuerp.leave_issues inner join raghuerp_db.staff on raghuerp.leave_issues.reg_no=raghuerp_db.staff.reg_no AND raghuerp.leave_issues.from_date <= concat(CURDATE(),' 23:00:00') and raghuerp.leave_issues.to_date > concat(CURDATE(),' 12:00:00') AND raghuerp_db.staff.college='RCP' AND raghuerp.leave_issues.status='Accepted') RCP_pM,
//      (select count(*) as rcp_am from raghuerp.leave_issues inner join raghuerp_db.staff on raghuerp.leave_issues.reg_no=raghuerp_db.staff.reg_no AND raghuerp.leave_issues.from_date < concat(CURDATE(),' 12:00:00') and raghuerp.leave_issues.to_date >= concat(CURDATE(),' 00:00:00') AND raghuerp_db.staff.college='RCP' AND raghuerp.leave_issues.status='Accepted') RCP_aM,
//        (select count(*) as rcp_pm from raghuerp.leave_issues inner join raghuerp_db.staff on raghuerp.leave_issues.reg_no=raghuerp_db.staff.reg_no AND raghuerp.leave_issues.from_date <= concat(CURDATE(),' 23:00:00') and raghuerp.leave_issues.to_date > concat(CURDATE(),' 12:00:00') AND raghuerp_db.staff.college='REC'  AND raghuerp_db.staff.department='MECH'  AND raghuerp.leave_issues.status='Accepted') RecMech_pM,
//            (select count(*) as rcp_pm from raghuerp.leave_issues inner join raghuerp_db.staff on raghuerp.leave_issues.reg_no=raghuerp_db.staff.reg_no AND raghuerp.leave_issues.from_date < concat(CURDATE(),' 12:00:00') and raghuerp.leave_issues.to_date >= concat(CURDATE(),' 00:00:00') AND raghuerp_db.staff.college='REC'  AND raghuerp_db.staff.department='MECH'  AND raghuerp.leave_issues.status='Accepted') RecMech_aM,
//          (select count(*) as rcp_pm from raghuerp.leave_issues inner join raghuerp_db.staff on raghuerp.leave_issues.reg_no=raghuerp_db.staff.reg_no AND raghuerp.leave_issues.from_date <= concat(CURDATE(),' 23:00:00') and raghuerp.leave_issues.to_date > concat(CURDATE(),' 12:00:00') AND raghuerp_db.staff.college='REC'  AND raghuerp_db.staff.department='CSE'  AND raghuerp.leave_issues.status='Accepted') RecCse_pM,
//            (select count(*) as rcp_pm from raghuerp.leave_issues inner join raghuerp_db.staff on raghuerp.leave_issues.reg_no=raghuerp_db.staff.reg_no AND raghuerp.leave_issues.from_date < concat(CURDATE(),' 12:00:00') and raghuerp.leave_issues.to_date >= concat(CURDATE(),' 00:00:00') AND raghuerp_db.staff.college='REC'  AND raghuerp_db.staff.department='CSE'  AND raghuerp.leave_issues.status='Accepted') RecCse_aM,
//             (select count(*) as rcp_pm from raghuerp.leave_issues inner join raghuerp_db.staff on raghuerp.leave_issues.reg_no=raghuerp_db.staff.reg_no AND raghuerp.leave_issues.from_date <= concat(CURDATE(),' 23:00:00') and raghuerp.leave_issues.to_date > concat(CURDATE(),' 12:00:00') AND raghuerp_db.staff.college='REC'  AND raghuerp_db.staff.department='EEE'  AND raghuerp.leave_issues.status='Accepted') RecEee_pM,
//            (select count(*) as rcp_pm from raghuerp.leave_issues inner join raghuerp_db.staff on raghuerp.leave_issues.reg_no=raghuerp_db.staff.reg_no AND raghuerp.leave_issues.from_date < concat(CURDATE(),' 12:00:00') and raghuerp.leave_issues.to_date >= concat(CURDATE(),' 00:00:00') AND raghuerp_db.staff.college='REC'  AND raghuerp_db.staff.department='EEE'  AND raghuerp.leave_issues.status='Accepted') RecEee_aM,
//             (select count(*) as rcp_pm from raghuerp.leave_issues inner join raghuerp_db.staff on raghuerp.leave_issues.reg_no=raghuerp_db.staff.reg_no AND raghuerp.leave_issues.from_date <= concat(CURDATE(),' 23:00:00') and raghuerp.leave_issues.to_date > concat(CURDATE(),' 12:00:00') AND raghuerp_db.staff.college='REC'  AND raghuerp_db.staff.department='CIVIL'  AND raghuerp.leave_issues.status='Accepted') RecCivil_pM,
//            (select count(*) as rcp_pm from raghuerp.leave_issues inner join raghuerp_db.staff on raghuerp.leave_issues.reg_no=raghuerp_db.staff.reg_no AND raghuerp.leave_issues.from_date < concat(CURDATE(),' 12:00:00') and raghuerp.leave_issues.to_date >= concat(CURDATE(),' 00:00:00') AND raghuerp_db.staff.college='REC'  AND raghuerp_db.staff.department='CIVIL'  AND raghuerp.leave_issues.status='Accepted') RecCivil_aM,
//             (select count(*) as rcp_pm from raghuerp.leave_issues inner join raghuerp_db.staff on raghuerp.leave_issues.reg_no=raghuerp_db.staff.reg_no AND raghuerp.leave_issues.from_date <= concat(CURDATE(),' 23:00:00') and raghuerp.leave_issues.to_date > concat(CURDATE(),' 12:00:00') AND raghuerp_db.staff.college='REC'  AND raghuerp_db.staff.department='ECE'  AND raghuerp.leave_issues.status='Accepted') RecEce_pM,
//            (select count(*) as rcp_pm from raghuerp.leave_issues inner join raghuerp_db.staff on raghuerp.leave_issues.reg_no=raghuerp_db.staff.reg_no AND raghuerp.leave_issues.from_date < concat(CURDATE(),' 12:00:00') and raghuerp.leave_issues.to_date >= concat(CURDATE(),' 00:00:00') AND raghuerp_db.staff.college='REC'  AND raghuerp_db.staff.department='ECE'  AND raghuerp.leave_issues.status='Accepted') RecEce_aM,
//             (select count(*) as rcp_pm from raghuerp.leave_issues inner join raghuerp_db.staff on raghuerp.leave_issues.reg_no=raghuerp_db.staff.reg_no AND raghuerp.leave_issues.from_date <= concat(CURDATE(),' 23:00:00') and raghuerp.leave_issues.to_date > concat(CURDATE(),' 12:00:00') AND raghuerp_db.staff.college='RIT'  AND raghuerp_db.staff.department='MECH'  AND raghuerp.leave_issues.status='Accepted') RitMech_pM,
//            (select count(*) as rcp_pm from raghuerp.leave_issues inner join raghuerp_db.staff on raghuerp.leave_issues.reg_no=raghuerp_db.staff.reg_no AND raghuerp.leave_issues.from_date < concat(CURDATE(),' 12:00:00') and raghuerp.leave_issues.to_date >= concat(CURDATE(),' 00:00:00') AND raghuerp_db.staff.college='RIT'  AND raghuerp_db.staff.department='MECH'  AND raghuerp.leave_issues.status='Accepted') RitMech_aM,
//             (select count(*) as rcp_pm from raghuerp.leave_issues inner join raghuerp_db.staff on raghuerp.leave_issues.reg_no=raghuerp_db.staff.reg_no AND raghuerp.leave_issues.from_date <= concat(CURDATE(),' 23:00:00') and raghuerp.leave_issues.to_date > concat(CURDATE(),' 12:00:00') AND raghuerp_db.staff.college='RIT'  AND raghuerp_db.staff.department='CSE'  AND raghuerp.leave_issues.status='Accepted') RitCse_pM,
//            (select count(*) as rcp_pm from raghuerp.leave_issues inner join raghuerp_db.staff on raghuerp.leave_issues.reg_no=raghuerp_db.staff.reg_no AND raghuerp.leave_issues.from_date < concat(CURDATE(),' 12:00:00') and raghuerp.leave_issues.to_date >= concat(CURDATE(),' 00:00:00') AND raghuerp_db.staff.college='RIT'  AND raghuerp_db.staff.department='CSE'  AND raghuerp.leave_issues.status='Accepted') RitCse_aM,
//             (select count(*) as rcp_pm from raghuerp.leave_issues inner join raghuerp_db.staff on raghuerp.leave_issues.reg_no=raghuerp_db.staff.reg_no AND raghuerp.leave_issues.from_date <= concat(CURDATE(),' 23:00:00') and raghuerp.leave_issues.to_date > concat(CURDATE(),' 12:00:00') AND raghuerp_db.staff.college='RIT'  AND raghuerp_db.staff.department='EEE'  AND raghuerp.leave_issues.status='Accepted') RitEee_pM,
//            (select count(*) as rcp_pm from raghuerp.leave_issues inner join raghuerp_db.staff on raghuerp.leave_issues.reg_no=raghuerp_db.staff.reg_no AND raghuerp.leave_issues.from_date < concat(CURDATE(),' 12:00:00') and raghuerp.leave_issues.to_date >= concat(CURDATE(),' 00:00:00') AND raghuerp_db.staff.college='RIT'  AND raghuerp_db.staff.department='EEE'  AND raghuerp.leave_issues.status='Accepted') RitEee_aM,
//             (select count(*) as rcp_pm from raghuerp.leave_issues inner join raghuerp_db.staff on raghuerp.leave_issues.reg_no=raghuerp_db.staff.reg_no AND raghuerp.leave_issues.from_date <= concat(CURDATE(),' 23:00:00') and raghuerp.leave_issues.to_date > concat(CURDATE(),' 12:00:00') AND raghuerp_db.staff.college='RIT'  AND raghuerp_db.staff.department='CIVIL'  AND raghuerp.leave_issues.status='Accepted') RitCivil_pM,
//            (select count(*) as rcp_pm from raghuerp.leave_issues inner join raghuerp_db.staff on raghuerp.leave_issues.reg_no=raghuerp_db.staff.reg_no AND raghuerp.leave_issues.from_date < concat(CURDATE(),' 12:00:00') and raghuerp.leave_issues.to_date >= concat(CURDATE(),' 00:00:00') AND raghuerp_db.staff.college='RIT'  AND raghuerp_db.staff.department='CIVIL'  AND raghuerp.leave_issues.status='Accepted') RitCivil_aM,
//             (select count(*) as rcp_pm from raghuerp.leave_issues inner join raghuerp_db.staff on raghuerp.leave_issues.reg_no=raghuerp_db.staff.reg_no AND raghuerp.leave_issues.from_date <= concat(CURDATE(),' 23:00:00') and raghuerp.leave_issues.to_date > concat(CURDATE(),' 12:00:00') AND raghuerp_db.staff.college='RIT'  AND raghuerp_db.staff.department='ECE'  AND raghuerp.leave_issues.status='Accepted') RitEce_pM,
//            (select count(*) as rcp_pm from raghuerp.leave_issues inner join raghuerp_db.staff on raghuerp.leave_issues.reg_no=raghuerp_db.staff.reg_no AND raghuerp.leave_issues.from_date < concat(CURDATE(),' 12:00:00') and raghuerp.leave_issues.to_date >= concat(CURDATE(),' 00:00:00') AND raghuerp_db.staff.college='RIT'  AND raghuerp_db.staff.department='ECE'  AND raghuerp.leave_issues.status='Accepted') RitEce_aM,
//             (select count(*) as rcp_pm from raghuerp.leave_issues inner join raghuerp_db.staff on raghuerp.leave_issues.reg_no=raghuerp_db.staff.reg_no AND raghuerp.leave_issues.from_date <= concat(CURDATE(),' 23:00:00') and raghuerp.leave_issues.to_date > concat(CURDATE(),' 12:00:00') AND raghuerp_db.staff.college='RCP'  AND raghuerp_db.staff.department='M.Pharmacy'  AND raghuerp.leave_issues.status='Accepted') RcpMphar_pM,
//            (select count(*) as rcp_pm from raghuerp.leave_issues inner join raghuerp_db.staff on raghuerp.leave_issues.reg_no=raghuerp_db.staff.reg_no AND raghuerp.leave_issues.from_date < concat(CURDATE(),' 12:00:00') and raghuerp.leave_issues.to_date >= concat(CURDATE(),' 00:00:00') AND raghuerp_db.staff.college='RCP'  AND raghuerp_db.staff.department='M.Pharmacy'  AND raghuerp.leave_issues.status='Accepted') RcpMphar_aM,
//             (select count(*) as rcp_pm from raghuerp.leave_issues inner join raghuerp_db.staff on raghuerp.leave_issues.reg_no=raghuerp_db.staff.reg_no AND raghuerp.leave_issues.from_date <= concat(CURDATE(),' 23:00:00') and raghuerp.leave_issues.to_date > concat(CURDATE(),' 12:00:00') AND raghuerp_db.staff.college='RCP'  AND raghuerp_db.staff.department='B.Pharmacy'  AND raghuerp.leave_issues.status='Accepted') RcpBphar_pM,
//            (select count(*) as rcp_pm from raghuerp.leave_issues inner join raghuerp_db.staff on raghuerp.leave_issues.reg_no=raghuerp_db.staff.reg_no AND raghuerp.leave_issues.from_date < concat(CURDATE(),' 12:00:00') and raghuerp.leave_issues.to_date >= concat(CURDATE(),' 00:00:00') AND raghuerp_db.staff.college='RCP'  AND raghuerp_db.staff.department='B.Pharmacy'  AND raghuerp.leave_issues.status='Accepted') RcpBphar_aM   
      
//     from raghuerp_db.staff";


// $query=$this->db->query($sql);
         
// return $this->response($query->result());





$sql="select c.college from raghuerp_db.colleges c group by c.college";
$clgs=$this->db->query($sql)->result();


//  $oal=(object) null;

for($i=0;$i<sizeof($clgs);$i++){
$name= $clgs[$i]->college;

$sql3="select d.department as name, count(l.leave_id) as y from raghuerp_db.staff s left join raghuerp.leave_issues l on s.reg_no=l.reg_no  and l.from_date <= concat(CURDATE(),' 23:00:00') and l.to_date > concat(CURDATE(),' 12:00:00') and l.status='Accepted' inner join  raghuerp_db.departments d on s.department=d.id and s.college=(select id from raghuerp_db.colleges c where c.college='$name') group by d.department";
$q1=$this->db->query($sql3);
$query['pm']=$q1->result();
$sql4="select d.department as name, count(l.leave_id) as y from raghuerp_db.staff s left join raghuerp.leave_issues l on s.reg_no=l.reg_no and l.from_date < concat(CURDATE(),' 12:00:00') and l.to_date >= concat(CURDATE(),' 00:00:00') and l.status='Accepted' inner join raghuerp_db.departments d on s.department=d.id and s.college=(select id from raghuerp_db.colleges c where c.college='$name') group by d.department";
$q2=$this->db->query($sql4);
$query['am']=$q2->result();



$sql="select d.department,concat('0') as y from raghuerp_db.departments d where d.college=(select c.id from raghuerp_db.colleges c where c.college='$name') group by d.department";
$depts=$this->db->query($sql)->result();

// $count=sizeof($clgs)+sizeof($clgs);
// $oal->$name=[];


//  $oal[$name][0]=(object) null;
//  $oal[$name][1]=(object) null;




$oal[$i]['name']=$name;
$oal[$i]['id']=$name.'-am';
$oal[$i]['data']=[];



   if(sizeof($query['am'])!=0){
for($j=0;$j<sizeof($query['am']);$j++){

$oal[$i]['data'][$j][0]=$query['am'][$j]->name;
$oal[$i]['data'][$j][1]=(int)$query['am'][$j]->y;


}

}
else{
	for($p=0;$p<sizeof($depts);$p++){
	$oal[$i]['data'][$p][0]=$depts[$p]->department;
$oal[$i]['data'][$p][1]=(int)$depts[$p]->y;	

	}
}




}

$count=sizeof($clgs);
for($i=0;$i<sizeof($clgs);$i++){
$name= $clgs[$i]->college;

$sql3="select d.department as name, count(l.leave_id) as y from raghuerp_db.staff s left join raghuerp.leave_issues l on s.reg_no=l.reg_no  and l.from_date <= concat(CURDATE(),' 23:00:00') and l.to_date > concat(CURDATE(),' 12:00:00') and l.status='Accepted' inner join  raghuerp_db.departments d on s.department=d.id and s.college=(select id from raghuerp_db.colleges c where c.college='$name') group by d.department";
$q1=$this->db->query($sql3);
$query['pm']=$q1->result();
$sql4="select d.department as name, count(l.leave_id) as y from raghuerp_db.staff s left join raghuerp.leave_issues l on s.reg_no=l.reg_no and l.from_date < concat(CURDATE(),' 12:00:00') and l.to_date >= concat(CURDATE(),' 00:00:00') and l.status='Accepted' inner join raghuerp_db.departments d on s.department=d.id and s.college=(select id from raghuerp_db.colleges c where c.college='$name') group by d.department";
$q2=$this->db->query($sql4);
$query['am']=$q2->result();



$sql="select d.department,concat('0') as y from raghuerp_db.departments d where d.college=(select c.id from raghuerp_db.colleges c where c.college='$name') group by d.department";
$depts=$this->db->query($sql)->result();

// $count=sizeof($clgs)+sizeof($clgs);
// $oal->$name=[];


//  $oal[$name][0]=(object) null;
//  $oal[$name][1]=(object) null;





$oal[$i+$count]['name']=$name;
$oal[$i+$count]['id']=$name.'-pm';
$oal[$i+$count]['data']=[];


   if(sizeof($query['am'])!=0){

for($q=0;$q<sizeof($query['pm']);$q++){

$oal[$i+$count]['data'][$q][0]=$query['pm'][$q]->name;
$oal[$i+$count]['data'][$q][1]=(int)$query['pm'][$q]->y;
   
}
}
else{
	for($p=0;$p<sizeof($depts);$p++){

$oal[$i+$count]['data'][$p][0]=$depts[$p]->department;
$oal[$i+$count]['data'][$p][1]=(int)$depts[$p]->y;
	}
}
}






$clgquery="select c.college as name,concat('0') as y,concat(c.college,'-am') as drilldown from raghuerp_db.colleges c group by c.college";
$colgdata=$this->db->query($clgquery)->result();


$sql3="select c.college as name, count(l.leave_id) as y,concat(c.college,'-pm') as drilldown from raghuerp_db.staff s left join raghuerp.leave_issues l on s.reg_no=l.reg_no and  l.from_date <= concat(CURDATE(),' 23:00:00') and l.to_date > concat(CURDATE(),' 12:00:00') and l.status='Accepted' inner join raghuerp_db.colleges c on s.college=c.id  group by c.college";
$q1=$this->db->query($sql3);
$querypm=$q1->result();
$sql4="select c.college as name, count(l.leave_id) as y,concat(c.college,'-am') as drilldown from raghuerp_db.staff s left join raghuerp.leave_issues l on s.reg_no=l.reg_no and l.from_date < concat(CURDATE(),' 12:00:00') and l.to_date >= concat(CURDATE(),' 00:00:00') and l.status='Accepted' inner join raghuerp_db.colleges c on s.college=c.id  group by c.college";
$q2=$this->db->query($sql4);
$queryam=$q2->result();

$colgdataam=[];

$colgdatapm=[];


for($s=0;$s<sizeof($colgdata);$s++){

for($t=0;$t<sizeof($queryam);$t++){
	$colgdatapm[$s]=(object) null;
	$colgdataam[$s]=(object) null;

if($queryam[$t]->name==$colgdata[$s]->name){
	$colgdataam[$s]->name=$colgdata[$s]->name;
	$colgdataam[$s]->y=(int)$queryam[$t]->y;
	$colgdataam[$s]->drilldown=$queryam[$t]->drilldown;
    
	$colgdatapm[$s]->name=$colgdata[$s]->name;
	$colgdatapm[$s]->y=(int)$querypm[$t]->y;
	$colgdatapm[$s]->drilldown=$querypm[$t]->drilldown ;
	
	break;
}
else{


	$colgdataam[$s]->name=$colgdata[$s]->name;
	$colgdataam[$s]->y=(int)$colgdata[$s]->y;
	$colgdataam[$s]->drilldown=$colgdata[$s]->drilldown;
    
	$colgdatapm[$s]->name=$colgdata[$s]->name;
	$colgdatapm[$s]->y=(int)$colgdata[$t]->y;
	$colgdatapm[$s]->drilldown=$colgdata[$s]->name.'-pm' ;

	
	break;
}

	}


   
}




			




$data['oal']=$oal;
$data['clgam']=$colgdataam;
$data['clgpm']=$colgdatapm;


return $this->response($data);


}
		}
       }
        



public function upReasoncoff_post(){



  $type=$this->post('type');
  $reason=$this->post('reason');
   $days=$this->post('days');
   $eid=$this->post('eid');
    $cid=$this->post('cid');
	 $rejectedBy=$this->post('rejectedBy');
	  $dept=$this->post('dept');
     $colg=$this->post('colg');
	  $role=$this->post('role');
	    $name=$this->post('name');
		 $uname=$this->post('username');
		 $emailHOD=$this->post('email');
     
		$success = true;
		$error = '';
		$result = '';
		$response = [];
				
		if(!$_SERVER['HTTP_TOKEN']) {
			$success = false;
			$error = "Token not provided";
		}
		
		if ($success) {

				$token = JWT::decode($_SERVER['HTTP_TOKEN'], $this->config->item('jwt_key'));
	
				if ($token->userid) {
$stat = $this->api_model->update_Reasoncoff($type,$reason,$days,$eid,$cid,$rejectedBy,$dept,$colg,$role,$name,$uname,$emailHOD);
          

if(!$stat)
{

return $this->response('');	

}

else
{
    return $this->response('update failed',400);
     
}
      }
        }
}


public function updateReason_post(){



  $type=$this->post('type');
  $reason=$this->post('reason');
   $days=$this->post('days');
   $eid=$this->post('eid');
    $lid=$this->post('lid');
     $tol=$this->post('tol');
	  $alterid=$this->post('alterid'); 
	 $rejectedBy=$this->post('rejectedBy');
	  $dept=$this->post('dept');
     $colg=$this->post('colg');
	  $role=$this->post('role');
	    $name=$this->post('name');
		 $uname=$this->post('username');
		 $emailHod=$this->post('email');
     
		$success = true;
		$error = '';
		$result = '';
		$response = [];
				
		if(!$_SERVER['HTTP_TOKEN']) {
			$success = false;
			$error = "Token not provided";
		}
		
		if ($success) {

				$token = JWT::decode($_SERVER['HTTP_TOKEN'], $this->config->item('jwt_key'));
	
				if ($token->userid) {
$stat = $this->api_model->update_Reason($type,$reason,$days,$eid,$lid,$tol,$rejectedBy,$dept,$colg,$role,$name,$alterid,$uname,$emailHod);
          

if(!$stat)
{

return $this->response('');	

}

else
{
    return $this->response('update failed',400);
     
}
      }
        }
}

public function empTwo_post(){
  $id=$this->post('emp_id');
  
		$success = true;
		$error = '';
		$result = '';
		$response = [];
				
		if(!$_SERVER['HTTP_TOKEN']) {
			$success = false;
			$error = "Token not provided";
		}
		
		if ($success) {

				$token = JWT::decode($_SERVER['HTTP_TOKEN'], $this->config->item('jwt_key'));
	
				if ($token->userid) {
$receive = $this->api_model->emp_Two($id); 
         
return $this->response($receive);	 

}    
   }
        }

public function leaveType_get(){
    
		$success = true;
		$error = '';
		$result = '';
		$response = [];
				
		if(!$_SERVER['HTTP_TOKEN']) {
			$success = false;
			$error = "Token not provided";
		}
		
		if ($success) {

				$token = JWT::decode($_SERVER['HTTP_TOKEN'], $this->config->item('jwt_key'));
	
				if ($token->userid) {
     $sql="SELECT *,concat('0') as value FROM raghuerp.leavetypes where lstatus='enable'";

    $query = $this->db->query($sql);
     $this->response($query->result());
                }
        }


}

public function alternateData_post(){

 $id=$this->post('id');
	$success = true;
		$error = '';
		$result = '';
		$response = [];
				
		if(!$_SERVER['HTTP_TOKEN']) {
			$success = false;
			$error = "Token not provided";
		}
		
		if ($success) {

				$token = JWT::decode($_SERVER['HTTP_TOKEN'], $this->config->item('jwt_key'));
	
				if ($token->userid) {
 $sql="SELECT l.*,concat('') as dispname,concat('') as college,concat('') as department,concat('') as designation FROM raghuerp.leave_issues l where l.alternateId='$id' AND l.alternateStatus IN ('Pending','Rejected','Accepted')
ORDER BY
   CASE l.alternateStatus
      WHEN 'Pending' THEN 1
      WHEN 'Rejected' THEN 2
      WHEN 'Accepted' THEN 3
      ELSE 4
   END";
    $query = $this->db->query($sql);
  
return $this->response($query->result());	
                }
                }
}

public function alternateAccept_post(){

 $lid=$this->post('lid');
  $status=$this->post('status');
  $reason=$this->post('reason');
   $dept=$this->post('dept');
  $colg=$this->post('colg');
  $aid=$this->post('id');
  $name=$this->post('name');
  $uname=$this->post('uname');
    $uid=$this->post('uid');
	$emailHOD=$this->post('email');
  $i=0;
	$success = true;
		$error = '';
		$result = '';
		$response = [];
				
		if(!$_SERVER['HTTP_TOKEN']) {
			$success = false;
			$error = "Token not provided";
		}
		
		if ($success) {

				$token = JWT::decode($_SERVER['HTTP_TOKEN'], $this->config->item('jwt_key'));
	
				if ($token->userid) {
 $sql="UPDATE raghuerp.leave_issues SET alternateStatus='$status',delegatedreason='$reason' WHERE leave_id='$lid' ";


    $query = $this->db->query($sql);



// $sql2="select reg_no,email,dispname from raghuerp_db.staff where (((role='Hod' and department='$dept' ||  role='Principal' )  and college='$colg' )||reg_no='$aid'||reg_no='$uid')" ;

// $test=$this->db->query($sql2)->result();
$test=$emailHOD;

$sql3="select email from raghuerp.configuration where designation='Director' || designation='Chairman'" ;

$emails=$this->db->query($sql3)->result();

$tes=sizeof($test);
$te=sizeof($emails);

$mail=[];
$i=$tes;

  
foreach($emails as $na){

	$test[$i]= $na->email;

	$i++;
}

 $message =" Delegated Person ". $name ." is ". $status ."  ".  $uname .  "  \' s Leave  Request " ;
 for($a=0;$a<sizeof($test);$a++){
     $params = Array(
        'to' => $test[$a],
        'cc' => '',
        'subject' => 'Leave Status',
        'message' => $message,
        'apptype' => 'LeaveSystem',
        'type'  => 'html', 
      );
        $this->api_model->sendEmail($params);

}
//  $this->api_model->sendEmail($test,$message);
  
return ;	
                }
		}
}


public function cancelStatus_post(){
  
    $lid=$this->post("lid");
    	$success = true;
		$error = '';
		$result = '';
		$response = [];
				
		if(!$_SERVER['HTTP_TOKEN']) {
			$success = false;
			$error = "Token not provided";
		}
		
		if ($success) {

				$token = JWT::decode($_SERVER['HTTP_TOKEN'], $this->config->item('jwt_key'));
	
				if ($token->userid) {
 $sql="UPDATE raghuerp.leave_issues SET status='Cancelled' WHERE leave_id='$lid' ";
    $query = $this->db->query($sql);
  
return;	
                }        
        }
}

public function collegeData_get(){
  
  
    	$success = true;
		$error = '';
		$result = '';
		$response = [];
				
		if(!$_SERVER['HTTP_TOKEN']) {
			$success = false;
			$error = "Token not provided";
		}
		
		if ($success) {

				$token = JWT::decode($_SERVER['HTTP_TOKEN'], $this->config->item('jwt_key'));
	
				if ($token->userid) {
					$sql="SELECT college  FROM raghuerp_db.staff where college!='' GROUP BY college";
//  $sql="SELECT raghuerp_db.staff.college  AS college  FROM raghuerp_db.staff WHERE raghuerp_db.staff.inst_id='$instid' AND raghuerp_db.staff.role!='Dean' AND raghuerp_db.staff.role!='Management' GROUP BY College";
    $query = $this->db->query($sql);
  
return $this->response($query->result());	 
                }
        }
}

public function departmentData_post(){
  
    $colg=$this->post("colg");
    	$success = true;
		$error = '';
		$result = '';
		$response = [];
				
		if(!$_SERVER['HTTP_TOKEN']) {
			$success = false;
			$error = "Token not provided";
		}
		
		if ($success) {

				$token = JWT::decode($_SERVER['HTTP_TOKEN'], $this->config->item('jwt_key'));
	
				if ($token->userid) {
					$sql="SELECT d.department FROM raghuerp_db.departments d inner join raghuerp_db.staff s on d.id=s.department and  d.college=(select id from raghuerp_db.colleges c where c.college='$colg' ) GROUP BY d.department";
//  $sql="SELECT department  FROM raghuerp_db.staff WHERE inst_id='$instid' AND college='$colg' AND role != 'Dean' GROUP BY department";
    $query = $this->db->query($sql);
  
return $this->response($query->result());	 
                }
        }
}

// public function personData_post(){
 
//     $colg=$this->post("colg");
//     $dept=$this->post("dept");
// 	$empid=$this->post('emp_id');
//     	$success = true;
// 		$error = '';
// 		$result = '';
// 		$response = [];
				
// 		if(!$_SERVER['HTTP_TOKEN']) {
// 			$success = false;
// 			$error = "Token not provided";
// 		}
		
// 		if ($success) {

// 				$token = JWT::decode($_SERVER['HTTP_TOKEN'], $this->config->item('jwt_key'));
	
// 				if ($token->userid) {
// 					$sql="SELECT reg_no,dispname  FROM raghuerp_db.staff WHERE college='$colg' AND department='$dept'  AND role != 'Principal' and role!='Dean' AND reg_no!='$empid' ";
//     $query = $this->db->query($sql);
  
// return $this->response($query->result());	 
//                 }
//         }
// }


       public function getEmail_get(){
           

		   $sql="select * from raghuerp.configuration";
		   $emails=$this->db->query($sql);
		   return $this->response($emails->result());
        }


	// Get Multiple query results function
	public function GetMultipleQueryResult($queryString)
    {
	    if (empty($queryString)) {
	                return false;
	            }

	    $index     = 0;
	    $ResultSet = array();

	    /* execute multi query */
	    if (mysqli_multi_query($this->db->conn_id, $queryString)) {
	        do {
	            if (false != $result = mysqli_store_result($this->db->conn_id)) {
	                $rowID = 0;
	                while ($row = $result->fetch_assoc()) {
	                    $ResultSet[$index][$rowID] = $row;
	                    $rowID++;
	                }
	            }
	            $index++;
	        } while (mysqli_more_results($this->db->conn_id) && mysqli_next_result($this->db->conn_id));
	    }

	    return $ResultSet;
    }




}