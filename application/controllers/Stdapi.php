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
        header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");

    exit(0);
} 

class Stdapi extends REST_Controller {
	public function __construct()
	{
		parent::__construct();
		$this->load->model('stdapi_model');
		$this->load->helper('jwt');
	}
	public function __destruct() {  
	    $this->db->close();  
	} 
    


	// Default function 
	public function index_get()
	{
		$this->response('NULL');
	}
	// user register function 
	public function register_post()
	{
		$first_name = $this->post("first_name");
		$email = $this->post("email");
		$mobile = $this->post("mobile");
		$password = $this->post("password");

		$is_unique_email = $this->stdapi_model->is_unique_email($email);
		$is_unique_mobile = $this->stdapi_model->is_unique_mobile($mobile);

		if($is_unique_email) {
			if($is_unique_mobile) {				
				if($this->stdapi_model->register([$first_name, $email, $mobile, $password])){
					$result['status'] = 1;
					$result['message'] = "Registered Successfully";
					$success = true;
					$error = null;
				} else {
					$result['status'] = 0;
					$result['message'] = "Registration fails";
					$success = false;
					$error = "Registration Fails";
				}
			} else {
				$result['status'] = 2;
				$result['message'] = "Mobile number should be unique";
				$success = false;;
				$error = "Mobile no should be unique";
			}
		} else {
			$result['status'] = 3;
			$result['message'] = "Email should be unique";
			$success = false;
			$error = "Email should be unique";
		}

		$response['success'] = $success;
		$response['error'] = $error;
		$response['data'] = $result;
		$this->response($response);
	}

	//check valid login or not
	//if valid return success
	//if invallid return error
	public function validLogin_post()
	{
		$username = $this->post('username');
		$password = $this->post('password');
		
		$result = $this->stdapi_model->login($username, $password);
		// $this->response('hi');
		if ($result['success']) {
			// user logged in, generate a token and return
			$id = $result['userid'];
			$token = array();
			$token['userid'] = $id;
			$result['token'] = JWT::encode($token, $this->config->item('jwt_key'));
			$this->response($result);
		} else {
			// authentication failed, return error
			$this->response(
				array(
					"success"=>$result['success'], 
					"error"=>$result['error']
				)
			);
		}
	}	
		
	function getData($type, $params=null) {
		$success = true;
		$error = '';
		$result = '';
		$response = [];
				
		if(!$_SERVER['HTTP_TOKEN']) {
			$success = false;
			$error = "Token not provided 123";
		}
		
		if ($success) {
			try 
			{
				$token = JWT::decode($_SERVER['HTTP_TOKEN'], $this->config->item('jwt_key'));
	
				if ($token->userid) {		
					switch($type) {
						case 'userData'				: $result = $this->stdapi_model->userData($token->userid); break;
						case 'changePassword'		: $result = $this->stdapi_model->changePassword($token->userid, $params); break;
						// courses 
						case 'dashSummary'			: $result = $this->stdapi_model->dashSummary($token->userid); break;
						case 'allCourses'			: $result = $this->stdapi_model->allCourses($token->userid); break;
						case 'stdCourses'			: $result = $this->stdapi_model->stdCourses($token->userid); break;

						case 'courseDetails'		: $result = $this->stdapi_model->courseDetails($token->userid, $params); break;
						case 'classroomValidate'	: $result = $this->stdapi_model->classroomValidate($token->userid, $params); break;

						case 'subjectDetails'		: $result = $this->stdapi_model->subjectDetails($token->userid, $params); break;
						case 'chapterDetails'		: $result = $this->stdapi_model->chapterDetails($token->userid, $params); break;
						case 'topicDetails'			: $result = $this->stdapi_model->topicDetails($token->userid, $params); break;
						case 'lessonDetails'		: $result = $this->stdapi_model->lessonDetails($token->userid, $params); break;
						case 'theoryDetails'		: $result = $this->stdapi_model->theoryDetails($token->userid, $params); break;

						case 'subjectsByCourse'		: $result = $this->stdapi_model->subjectsByCourse($token->userid, $params); break;
						case 'chaptersBySubject'	: $result = $this->stdapi_model->chaptersBySubject($token->userid, $params); break;
						case 'topicsByChapter'		: $result = $this->stdapi_model->topicsByChapter($token->userid, $params); break;
						case 'lessonsByTopic'		: $result = $this->stdapi_model->lessonsByTopic($token->userid, $params); break;
						case 'theoriesByLesson'		: $result = $this->stdapi_model->theoriesByLesson($token->userid, $params); break;

						case 'subsChapsByChapter'	: $result = $this->stdapi_model->subsChapsByChapter($token->userid, $params); break;
						case 'subscribe'			: $result = $this->stdapi_model->subscribe($token->userid, $params); break;
						case 'questions'			: $result = $this->stdapi_model->questions($token->userid, $params); break;
						case 'submitExam'			: $result = $this->stdapi_model->submitExam($token->userid, $params); break;
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

	// Change Password
	public function changePassword_post()
	{
		$old_pass = $this->post('old_pass');
		$new_pass = $this->post('new_pass');

		$this->getData('changePassword', [$old_pass, $new_pass]);
	}


	// dashboard summary
	public function dashSummary_post()
	{
		$this->getData('dashSummary', []);
	}

	// all courses
	public function allCourses_post()
	{
		$this->getData('allCourses', []);
	}

	// student courses courses
	public function stdCourses_post()
	{
		$this->getData('stdCourses', [$std_id]);
	}

	// student courses courses
	public function courseDetails_post()
	{
		$course_id = $this->post('course_id');
		$this->getData('courseDetails', [$course_id]);
	}

	// subject details by subject id
	public function subjectDetails_post()
	{
		$course_id = $this->post('course_id');
		$subject_id = $this->post('subject_id');
		$chapter_id = $this->post('chapter_id');
		$this->getData('subjectDetails', [$subject_id, $course_id, $chapter_id]);
	}

	// chapter details by chapter id
	public function chapterDetails_post()
	{
		$chapter_id = $this->post('chapter_id');
		$course_id = $this->post('course_id');
		$this->getData('chapterDetails', [$chapter_id, $course_id]);
	}

	// topic details by topic id
	public function topicDetails_post()
	{
		$topic_id = $this->post('topic_id');
		$course_id = $this->post('course_id');
		$this->getData('topicDetails', [$topic_id, $course_id]);
	}

	// lesson details by lesson id
	public function lessonDetails_post()
	{
		$lesson_id = $this->post('lesson_id');
		$course_id = $this->post('course_id');
		$this->getData('lessonDetails', [$lesson_id, $course_id]);
	}

	// theory details by theory id
	public function theoryDetails_post()
	{
		$theory_id = $this->post('theory_id');
		$course_id = $this->post('course_id');
		$this->getData('theoryDetails', [$theory_id, $course_id]);
	}

	// subjects list by course id
	public function subjectsByCourse_post()
	{
		$course_id = $this->post('course_id');
		$chapter_id = $this->post('chapter_id');
		$this->getData('subjectsByCourse', [$course_id, $chapter_id]);
	}

	// chapters list by subject id
	public function chaptersBySubject_post()
	{
		$subject_id = $this->post('subject_id');
		$course_id = $this->post('course_id');
		$chapter_id = $this->post('chapter_id');
		$this->getData('chaptersBySubject', [$subject_id, $course_id, $chapter_id]);
	}

	// topics list by chapter id
	public function topicsByChapter_post()
	{
		$chapter_id = $this->post('chapter_id');
		$course_id = $this->post('course_id');
		$this->getData('topicsByChapter', [$chapter_id,$course_id]);
	}

	// lessons list by topic id
	public function lessonsByTopic_post()
	{
		$topic_id = $this->post('topic_id');
		$course_id = $this->post('course_id');
		$this->getData('lessonsByTopic', [$topic_id, $course_id]);
	}

	// theories list by lesson id
	public function theoriesByLesson_post()
	{
		$lesson_id = $this->post('lesson_id');
		$course_id = $this->post('course_id');
		$this->getData('theoriesByLesson', [$lesson_id, $course_id]);
	}

	// lessons list by topic id
	public function subsChapsByChapter_post()
	{
		$chapter_id = $this->post('chapter_id');
		$course_id = $this->post('course_id');
		$this->getData('subsChapsByChapter', [$chapter_id, $course_id]);
	}

	// class room validate
	public function classroomValidate_post()
	{
		$course_id = $this->post("course_id");
		$chapter_id = $this->post("chapter_id");
		$this->getData('classroomValidate', [$course_id, $chapter_id]);
	}

	// course subscription
	public function subscribe_post()
	{
		$type = $this->post("type");
		$id = $this->post("id");
		$this->getData('subscribe', [$type, $id]);
	}

	// question by exam_id
	public function questions_post()
	{
		$exam_id = $this->post("exam_id");
		$course_id = $this->post("course_id");
		$subject_id = $this->post("subject_id");
		$chapter_id = $this->post("chapter_id");
		$topic_id = $this->post("topic_id");
		$this->getData('questions', [$exam_id, $course_id, $subject_id, $chapter_id, $topic_id]);
	}


	// submit exam
	public function submitExam_post()
	{
		$questions = $this->post("questions");
		$this->getData('submitExam', [$questions]);
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