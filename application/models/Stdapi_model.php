<?php

class Stdapi_model extends CI_Model
{
    function __construct()
    {
        parent::__construct();

        $this->load->database();
    }

    // Unique email check
    function is_unique_email($email)
    {
        $count = $this->db->query("select count(*) as count from student where email = '".$email."' ")->row()->count;
        if(!$count) {
            return true;
        } else {
            return false;
        }
    }

    // unique mobile check
    function is_unique_mobile($mobile)
    {
        $count = $this->db->query("select count(*) as count from student where mobile = '".$mobile."' ")->row()->count;
        if(!$count) {
            return true;
        } else {
            return false;
        } 
    }
    
    // validate login details
    function login($username, $password) 
    {
        $this->db->where('email', $username);
        $this->db->where('password', md5($password));
        $this->db->where('status', '1');
        $this->db->limit(1);
        $ugt = $this->db->get('student');
        $cnt = $ugt->num_rows();
        if ($cnt) {
            $data = $ugt->row();
            return array("success"=>true, "userid"=>$data->std_id, "email"=>$data->email, "mobile"=>$data->mobile, "studentname"=>$data->first_name.' '.$data->last_name);
        } else {
            return array("success"=>false, "error"=> $ugt->result());
        }
    }

    // user data
    function userData($std_id) {
        $this->db->where('std_id', $std_id);
        $this->db->limit(1);
        $ugt = $this->db->get('student');
        $data = $ugt->row();
        return $data;
    }

    // changePassword
    function changePassword($std_ideid, $params)
    {
        $old_pass = $params[0];
        $new_pass = $params[1];

        $data = $this->userData($std_id);
        if($data->password == md5($old_pass)) {
            $upd['password'] = md5($new_pass);
            $upd['modified_on'] = date("Y-m-d H:i:s");
            $this->db->where('std_id', $data->std_id);
            $st = $this->db->update("student", $upd);
            if($st) {
                $dt['sus'] = "true";
                $dt['message'] = "Password Changed Successfully...!";
                return $dt;
            } else {
                $dt['sus'] = "false";
                $dt['message'] = "Unable Change Password.. Try Again..";
                return $dt;
            }
        } else {
            $dt['sus'] = "false";
            $dt['message'] = "Old Password Does Not Match...!";
            return $dt;
        }
    }

    // register
    function register($params)
    {
        $first_name = $params[0];
        $email = $params[1];
        $mobile = $params[2];
        $password = $params[3];

        $count = $this->db->query("select count(*) as count from student where email='".$email."' ")->row()->count;
        if($count > 0) {
            $return['status'] = false;
            $return['message'] = "Already having account with this email address..";
        } else {
            $data['first_name'] = $first_name;
            $data['mobile'] = $mobile;
            $data['email'] = $email;
            $data['password'] = md5($password);
            $data['created_on'] = date("Y-m-d H:i:s");
            $data['modified_on'] = date("Y-m-d H:i:s");
            $this->db->insert("student", $data);
            $std_id = $this->db->insert_id();

            $str = 'ABCDEEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz123456789';
            $shuffled = str_shuffle($str);

            $adata['std_id'] = $std_id;
            $adata['act_code'] = $shuffled;
            $adata['created_on'] = date("Y-m-d H:i:s");
            $adata['modified_on'] = date("Y-m-d H:i:s");
            $this->db->insert("student_activate", $adata);
            $st = $this->db->insert_id();

            $message = "Hello ".$first_name.",<br><br>
            <h4>Welcome to Raghu's Classes</h4>
            to activate your account 
            <a href='".base_url()."student/emailverification/".$shuffled."/".$std_id."'>Click Here</a>
            <br><br>
            Thank you<br>
            Team,<br>
            Raghu's Classes
            ";

            $sms_message = "Hello ".$first_name.", Welcome to Raghu's Classes, please confirm your email to activate your login.   --Raghu's Classes";
            $msg_st = $this->sendSMS($mobile, $sms_message);
            $this->sendEmail($email,'Email Verification',$message);
            
            if($st) {
                $return['status'] = true;
                $return['message'] = "success";
            } else {
                $return['status'] = false;
                $return['message'] = "fails";
            }
        }
        return $return;
    }


    // all courses
    function allCourses($std_id) 
    {
        // $rs = $this->db->query("select * from courses where status=1")->result();
        $rs = $this->db->query("select *, c.course_id as course_id from courses c left join student_course sc on sc.std_id=".$std_id." and sc.course_id=c.course_id and c.course_type='paid' and sc.end_dt>=now() where c.status=1")->result();
        return $rs;
    }

    // student courses
    function stdCourses($std_id) 
    {
        $rs = $this->db->query("SELECT *, sc.course_id as course_id FROM student_course sc inner join courses c on c.course_id = sc.course_id where sc.std_id=".$std_id." and sc.end_dt > now() and c.status=1")->result();
        return $rs;
    }

    // dash board summary - (total free courses, total paid courses, registerd courses, expire soon courses)
    function dashSummary($std_id)
    {
        $rs = $this->db->query("select (select count(*) from courses where status=1 and course_type='free') tot_fc, (select count(*) from courses where status=1 and course_type='paid') tot_pc, (select count(*) from student_course where std_id=".$std_id." and end_dt >= now()) reg_c, (SELECT count(*) FROM student_course where std_id=".$std_id." and datediff(end_dt,now()) < 5) exp_soon")->row();
        return $rs;
    }

    // classroomValidate
    function classroomValidate($std_id, $params)
    {
        $course_id = $params[0];
        $chapter_id = $params[1];

        $cnt1 = $this->db->query("select count(*) as count from student_course where std_id=".$std_id." and course_id=".$course_id." and end_dt >= now()")->row()->count;
        $cnt2 = $this->db->query("select count(*) as count from student_course where std_id=".$std_id." and chapter_id= ".$chapter_id." and  end_dt >= now()")->row()->count;
        $spcnt = $this->db->query("select count(*) as count from courses where course_id=".$course_id." and course_type='free'")->row()->count;
        
        if ($cnt1 > 0) {
            $type = 'cr';
        } else if($cnt2 > 0) {
            $type = 'ch';
        } else {
            $type = 'fr';
        }

        if($cnt1 > 0 || $cnt2 > 0 || $spcnt > 0) {
            $return['status'] = true;
            $return['type'] = $type;
        } else {
            $return['status'] = false;
            $return['type'] = '';
        }
        return $return;
    }

    // subjects and chapters in a course
    // function courseDetails($std_id, $params)
    // {
    //     $course_id = $params[0]; 
    //     $subscribe = false;
    //     $cnt = $this->db->query("select *, datediff(end_dt,now()) as remaining_days ,count(*) as count from student_course where std_id=".$std_id." and course_id=".$course_id." and end_dt >= now()")->row();
    //     if($cnt->count > 0) {
    //         $subscribe = true;
    //         $return['student_course'] = $cnt;
    //     }
    //     $subs = $this->db->query("SELECT sc.course_id, sc.subject_id, s.subject FROM subject_course sc inner join subjects s on s.subject_id=sc.subject_id where course_id=". $course_id." and s.status=1 ")->result();
    //     $cors = $this->db->query("SELECT * from courses where course_id=". $course_id)->row();
    //     foreach ($subs as $sb)
    //     {
    //         $chaps[$sb->subject_id] = $this->db->query("select *, (select count(*) from student_course where chapter_id=ch.chapter_id) as subscribe from chapters ch where ch.subject_id=".$sb->subject_id." and ch.status=1")->result();
    //     }
    //     $return['status'] = true;
    //     $return['subscribe'] = $subscribe;
    //     $return['course'] = $cors;
    //     $return['subjects'] = $subs;
    //     $return['chapters'] = $chaps;
       
    //     return $return;
    // }

    // subjects and chapters in a course
    function courseDetails($std_id, $params)
    {
        $course_id = $params[0]; 
        $subscribe = false;
        $cnt = $this->db->query("select *, datediff(end_dt,now()) as remaining_days ,count(*) as count from student_course where std_id=".$std_id." and course_id=".$course_id." and end_dt >= now()")->row();
        if($cnt->count > 0) {
            $subscribe = true;
            $return['student_course'] = $cnt;
        }
        // $subs = $this->db->query("SELECT sc.course_id, sc.subject_id, s.subject FROM subject_course sc inner join subjects s on s.subject_id=sc.subject_id where course_id=". $course_id." and s.status=1 ")->result();
        $subjects = $this->db->query("select sb.*, sc.scid as s_subscribe from subjects sb left join student_course sc on std_id=".$std_id." and sb.subject_id=sc.subject_id and end_dt >= now() where sb.subject_id in (SELECT subject_id FROM subject_course where course_id=".$course_id.") and sb.status=1")->result();
        $cors = $this->db->query("SELECT * from courses where course_id=". $course_id)->row();
        // foreach ($subs as $sb)
        // {
        //     $chaps[$sb->subject_id] = $this->db->query("select *, (select count(*) from student_course where chapter_id=ch.chapter_id) as subscribe from chapters ch where ch.subject_id=".$sb->subject_id." and ch.status=1")->result();
        // }
        $return['status'] = true;
        $return['subscribe'] = $subscribe;
        $return['course'] = $cors;
        $return['subjects'] = $subjects;
        // $return['chapters'] = $chaps;
       
        return $return;
    }

    // subject details
    function subjectDetails($std_id, $params) {
        $subject_id = $params[0];
        $course_id = $params[1];
        $chapter_id = $params[2];
        $chp_cnt = 0;
        if($chapter_id) {
            $chp_cnt = $this->db->query("select count(*) as count from student_course where std_id=".$std_id." and chapter_id= ".$chapter_id." and  end_dt >= now()")->row()->count;
        }           
        $cnt = $this->db->query("select count(*) as count from student_course where std_id=".$std_id." and course_id=".$course_id." and end_dt >= now()")->row()->count;
        $spcnt = $this->db->query("select count(*) as count from courses where course_id=".$course_id." and course_type='free'")->row()->count;

        $subject = $this->db->query("select * from subjects where subject_id=".$subject_id." and status=1 ")->row();
        $return['subject'] = $subject;
        $return['status'] = true;

        if($cnt > 0 || $spcnt > 0 || $chp_cnt > 0) {
            $return['subscribe'] = true;
        } else {
            $return['subscribe'] = false;
        }
        return $return;
    }

    // chapter details
    function chapterDetails($std_id, $params)
    {
        $chapter_id = $params[0];
        $course_id = $params[1];
        $cnt = $this->db->query("select count(*) as count from student_course where std_id=".$std_id." and course_id=".$course_id." and end_dt >= now()")->row()->count;
        $cnt1 = $this->db->query("select count(*) as count from student_course where std_id=".$std_id." and chapter_id= ".$chapter_id." and  end_dt >= now()")->row()->count;
        $spcnt = $this->db->query("select count(*) as count from courses where course_id=".$course_id." and course_type='free'")->row()->count;
        if($cnt > 0 || $cnt1 > 0 || $spcnt > 0) {
            $chapter = $this->db->query("select * from chapters where chapter_id=".$chapter_id." and status=1")->row();
            $return['chapter'] = $chapter;
            $return['status'] = true;
        } else {
            $return['status'] = false;
        }
        return $return;
    }

    // topic. details
    function topicDetails($std_id, $params)
    {
        $topic_id = $params[0];
        $course_id = $params[1];
        $cnt = $this->db->query("select count(*) as count from student_course where std_id=".$std_id." and course_id=".$course_id." and end_dt >= now()")->row()->count;
        $cnt1 = $this->db->query("select count(*) as count from student_course where std_id=".$std_id." and chapter_id= (select chapter_id from topics where topic_id=".$topic_id." limit 1) and end_dt >= now()")->row()->count;
        $spcnt = $this->db->query("select count(*) as count from courses where course_id=".$course_id." and course_type='free'")->row()->count;
        if($cnt > 0 || $cnt1 > 0 || $spcnt) {
            $topic = $this->db->query("select * from topics where topic_id=".$topic_id." and status=1")->row();
            $return['topic'] = $topic;
            $return['status'] = true;
        } else {
            $return['status'] = false;
        }
        return $return;
    }

    // lesson details
    function lessonDetails($std_id, $params)
    {
        $lesson_id = $params[0];
        $course_id = $params[1];
        $cnt = $this->db->query("select count(*) as count from student_course where std_id=".$std_id." and course_id=".$course_id." and end_dt >= now()")->row()->count;
        $cnt1 = $this->db->query("select count(*) as count from student_course where std_id=".$std_id." and chapter_id=(select chapter_id from topics where topic_id=(select topic_id from lessons where lesson_id=".$lesson_id." limit 1) limit 1) and end_dt >= now()")->row()->count;
        $spcnt = $this->db->query("select count(*) as count from courses where course_id=".$course_id." and course_type='free'")->row()->count;
        if($cnt > 0 || $cnt1 > 0 || $spcnt > 0) {
            $lesson = $this->db->query("select * from lessons where lesson_id=".$lesson_id." and status=1")->row();
            $return['lesson'] = $lesson;
            $return['status'] = true;
        } else {
            $return['status'] = false;
        }
        return $return;
    }

    // theory details
    function theoryDetails($std_id, $params)
    {
        $theory_id = $params[0];
        $course_id = $params[1];
        $cnt = $this->db->query("select count(*) as count from student_course where std_id=".$std_id." and course_id=".$course_id." and end_dt >= now()")->row()->count;
        $cnt1 = $this->db->query("select count(*) as count from student_course where std_id=".$std_id." and chapter_id=(select chapter_id from topics where topic_id=(select topic_id from lessons where lesson_id=(select lesson_id from theories where theory_id=".$theory_id." limit 1) limit 1) limit 1) and end_dt >= now()")->row()->count;
        $spcnt = $this->db->query("select count(*) as count from courses where course_id=".$course_id." and course_type='free'")->row()->count;
        if($cnt > 0 || $cnt1 > 0 || $spcnt > 0) {
            $theory = $this->db->query("select * from theories where theory_id=".$theory_id." and status=1")->row();
            $files = $this->db->query("select * from links where theory_id = ".$theory_id." and status=1")->result();
            $return['theory'] = $theory;
            $return['files'] = $files;
            $return['status'] = true;
        } else {
            $return['status'] = false;
        }
        return $return;
    }
    
    // subjects list by course id
    function subjectsByCourse($std_id, $params) {
        $course_id = $params[0];
        
        $subjects = $this->db->query("select sb.*, sc.scid as s_subscribe from subjects sb left join student_course sc on std_id=".$std_id." and sb.subject_id=sc.subject_id and end_dt >= now() where sb.subject_id in (SELECT subject_id FROM subject_course where course_id=".$course_id.") and sb.status=1")->result();
        $return["subjects"] = $subjects;
        $return["status"] = true;
        return $return;
    }

    // chapters list by subject id
    function chaptersBySubject($std_id, $params) {
        $subject_id = $params[0];
        $course_id = $params[1];
        
        $chapters = $this->db->query("select ch.*, sc.scid as c_subscribe from chapters ch left join student_course sc on std_id=".$std_id." and ch.chapter_id=sc.chapter_id and end_dt >= now() where ch.subject_id in (".$subject_id.") and ch.status=1")->result();
        $return["chapters"] = $chapters;
        $return["status"] = true;
        return $return;
    }

    // topics list by chapter id
    function topicsByChapter($std_id, $params) {
        $chapter_id = $params[0];
        $course_id = $params[1];
        
        $topics = $this->db->query("select tp.*, sc.scid as t_subscribe from topics tp left join student_course sc on std_id=".$std_id." and tp.topic_id=sc.topic_id and end_dt >= now() where tp.topic_id in (".$chapter_id.") and tp.status=1")->result();
        $return["topics"] = $topics;
        $return["status"] = true;
        return $return;
    }

    // lessons list by topic
    function lessonsByTopic($std_id, $params) {
        $topic_id = $params[0];
        $course_id = $params[1];
        
        // $lessons = $this->db->query("select ls.*, sc.scid as l_subscribe from lessons ls left join student_course sc on std_id=".$std_id." and ls.lesson_id=sc.lesson_id and end_dt >= now() where ls.lesson_id in (".$topic_id.") and ls.status=1")->result();

        $lessons = $this->db->query("(select position, exam_id as lesson_id, exam_title as lesson, exam_type as type, topic_id, '0' as credits, exam_duration as duration, status from exams where status=1 and topic_id=".$topic_id.") UNION (select position, lesson_id, lesson, 'lesson' as type, topic_id, credits, duration, status from lessons where status=1 and topic_id=".$topic_id.") order by position asc")->result();
        $return["lessons"] = $lessons;
        $return["status"] = true;
        return $return;
    }

    // theories by lesson 
    function theoriesByLesson($std_id, $params) {
        $lesson_id = $params[0];
        $course_id = $params[1];
        
        $theories = $this->db->query("select * from theories where lesson_id = ".$lesson_id." and status=1 ")->result();
        foreach($theories as $th) {
            $files[$th->theory_id] = $this->db->query("select * from links where theory_id=".$th->theory_id."  and status=1 order by position asc")->result();
        }
        $return["theories"] = $theories;
        $return['files'] = $files;
        $return["status"] = true;
        return $return;
    }

    // chapter subscription subject and chater details 
    function subsChapsByChapter($std_id, $params)
    {
        $chapter_id = $params[0];
        $course_id = $params[1];
        $cnt = $this->db->query("select count(*) as count from student_course where std_id=".$std_id." and chapter_id= ".$chapter_id." and  end_dt >= now()")->row()->count;
        if($cnt > 0) {
            $chapter = $this->db->query("select * from chapters where chapter_id=".$chapter_id."  and status='1' order by position asc")->row();
            $subject = $this->db->query("select * from subjects where subject_id=".$chapter->subject_id." and status='1' order by position asc")->row();
            $return['subject'] = $subject;
            $return['chapter'] = $chapter;
            $return['status'] = true;
        } else {
            $return['status'] = false;
        }
        return $return;
    }

    // questions by exam_id
    function questions($std_id, $params)
    {
        $exam_id = $params[0];
        $course_id = $params[1];
        $subject_id = $params[2];
        $chapter_id = $params[3];
        $topic_id = $params[4];

        $count = $this->db->query("select count(*) as count from student_course where std_id=".$std_id." and (course_id=".$course_id." or subject_id=".$subject_id." or chapter_id=".$chapter_id." or topic_id=".$topic_id.")")->row()->count;
        $ex_st = $this->db->query("select count(*) as count from std_exam_answers where std_id=".$std_id." and exam_id=".$exam_id)->row()->count;
        if($count > 0) {
            $exam_details = $this->db->query("select * from exams where exam_id=".$exam_id)->row();
            if($ex_st > 0) {
                $questions = $this->db->query("select eq.*, qb.question, qb.cha, qb.chb, qb.chc, qb.chd, qb.answer, sea.choice as user_answer, qb.explanation_video as video, explanation_document as notes from exam_questions eq inner join question_bank qb on qb.ques_id = eq.ques_id inner join std_exam_answers sea on sea.exam_id = eq.exam_id and sea.eq_id = eq.eq_id where eq.exam_id = ".$exam_id." order by eq.ques_order")->result();
                $return['exam_finish_status'] = true;
            } else {
                $questions = $this->db->query("select eq.*, qb.question, qb.cha, qb.chb, qb.chc, qb.chd, qb.answer, qb.explanation_video as video, explanation_document as notes from exam_questions eq inner join question_bank qb on qb.ques_id = eq.ques_id where eq.exam_id = ".$exam_id." order by eq.ques_order")->result();
                $return['exam_finish_status'] = false;
            }
            

            $return['status'] = true;            
            $return['exam'] = $exam_details;
            $return['questions'] = $questions;
        } else {
            $return['status'] = false;
        }
        return $return;
    }

    // submit exam
    function submitExam($std_id, $params)
    {
        // return $params;
        $questions = json_decode($params[0]);
        foreach($questions as $ques) {
            $data['exam_id'] = $ques->exam_id;
            $data['eq_id'] = $ques->eq_id;
            $data['std_id'] = $std_id;
            $data['choice'] = $ques->user_answer;
            $ques->user_answer == $ques->answer ? $data['score'] = $ques->marks : $data['score'] = 0;
            $ques->user_answer == $ques->answer ? $data['result'] = 1 : $data['result'] = 0;
            $this->db->insert("std_exam_answers", $data);
        }
        if($this->db->insert_id() > 0) {
            $return['status'] = true;
        } else {
            $return['status'] = false;
        }
        return $return;
    }

    // subscribe function
    function subscribe($std_id, $params)
    {
        $type = $params[0];
        $id = $params[1];
        $start_dt = date("Y-m-d H:i:s");

        switch ($type) {
            case 'Course':
                $duration = $this->db->query("select duration from courses where course_id = ".$id." and status='1'")->row()->duration;
                $data['course_id'] = $id;
                break;
            case 'Subject':
                $duration = $this->db->query("select duration from subjects where subject_id = ".$id." and status='1'")->row()->duration;
                $data['subject_id'] = $id;
                break;
            case 'Chapter':
                $duration = $this->db->query("select duration from chapters where chapter_id = ".$id." and status='1'")->row()->duration;
                $data['chapter_id'] = $id;
                break;
            case 'Topic':
                $duration = $this->db->query("select duration from topics where topic_id = ".$id." and status='1'")->row()->duration;
                $data['topic_id'] = $id;
                break;
            case 'Lesson':
                $duration = $this->db->query("select duration from lessons where lesson_id = ".$id." and status='1'")->row()->duration;
                $data['lesson_id'] = $id;
                break;
                
            default:
                $return['status'] = false;
                return $return;
        }
        $end_dt = date('Y-m-d H:i:s', strtotime('+'.$duration.' day', time()));
        $data['std_id'] = $std_id;
        $data['start_dt'] = $start_dt;
        $data['end_dt'] = $end_dt;

        $this->db->insert("student_course", $data);

        if($this->db->insert_id()) {
            $return['status'] = true;
        } else {
            $return['status'] = false;
        }
        return $return;
    }












    ########################### Supporting functions
    // sending email
	function sendEmail($to,$subject,$message)
	{

		$this->load->library('email');

		$this->email->from("testmail@akrivia.in", "Raghu's Classes");
		$this->email->to($to);
		// $this->email->cc('admin@raghueducational.org');
		// $this->email->bcc('techlead.it@raghues.com');
        $this->email->set_mailtype("html");

		$this->email->subject($subject);
		$this->email->message($message);

		$st = $this->email->send();

		if($st) {
			return true;
		} else {
			return false;
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
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		curl_exec($ch);
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
?>
