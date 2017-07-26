<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Student extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->model('stdapi_model');
    }
	
    public function __destruct() {  
        $this->db->close();  
    } 
    
	public function emailverification($act_code, $std_id)
	{
        $count = $this->db->query("select count(*) as count from student_activate where std_id='".$std_id."' and act_code='".$act_code."' and used=0")->row()->count;
        if($count > 0) {
            $mdt = date("Y-m-d H:i:s");
            $this->db->simple_query("update student_activate set used=1, modified_on='".$mdt."' where std_id='".$std_id."' and act_code='".$act_code."' and used=0");
            $this->db->simple_query("update student set status=1, modified_on='".$mdt."' where std_id=".$std_id);
            $message = "<h4>Thank you for confirming your email</h4>
            Team,<br>myExams Exam Portal";
            $sms_message = "Thank you for confirming your email.    --Team, myExams Exam Portal";
            $std = $this->db->query("select * from student where std_id='".$std_id."' ")->row();
            if(sizeof($std)) {
                $std_email = $std->email;
                $std_mobile = $std->mobile;
                $this->stdapi_model->sendEmail($std_email,'Successfully Email Verified',$message);
                $this->stdapi_model->sendSMS($std_mobile, $sms_message);
            }           

            echo "<h4>Thank you for confirming your email</h4><p>Now you can login to examportal</p>";
        } else {
            echo "Unable process your request, please contact exam portal administrator";
        }
	}

}
?>