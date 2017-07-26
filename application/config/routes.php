<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| URI ROUTING
| -------------------------------------------------------------------------
| This file lets you re-map URI requests to specific controller functions.
|
| Typically there is a one-to-one relationship between a URL string
| and its corresponding controller class/method. The segments in a
| URL normally follow this pattern:
|
|	example.com/class/method/id/
|
| In some instances, however, you may want to remap this relationship
| so that a different class/function is called than the one
| corresponding to the URL.
|
| Please see the user guide for complete details:
|
|	https://codeigniter.com/user_guide/general/routing.html
|
| -------------------------------------------------------------------------
| RESERVED ROUTES
| -------------------------------------------------------------------------
|
| There are three reserved routes:
|
|	$route['default_controller'] = 'welcome';
|
| This route indicates which controller class should be loaded if the
| URI contains no data. In the above example, the "welcome" class
| would be loaded.
|
|	$route['404_override'] = 'errors/page_missing';
|
| This route will tell the Router which controller/method to use if those
| provided in the URL cannot be matched to a valid route.
|
|	$route['translate_uri_dashes'] = FALSE;
|
| This is not exactly a route, but allows you to automatically route
| controller and method names that contain dashes. '-' isn't a valid
| class or method name character, so it requires translation.
| When you set this option to TRUE, it will replace ALL dashes in the
| controller and method URI segments.
|
| Examples:	my-controller/index	-> my_controller/index
|		my-controller/my-method	-> my_controller/my_method
*/
$route['default_controller'] = 'api';
$route['404_override'] = '';
$route['translate_uri_dashes'] = FALSE;

#########################################
############ EXAMINER ROUTES ############
#########################################

// Login verfication
$route['api/validLogin']['post'] = 'api/validLogin';
// Userdata 
$route['api/userData']['post'] = 'api/userData';
// change password 
$route['api/changePassword']['post'] = 'api/changePassword';


$route['logstatus']="api/logStatus";
$route['getinfo']="api/getInfos";
$route['leaveapply']="api/leaveApplys";
$route['leavehistory']="api/getHistory";
$route['status']="api/getStatus";
$route['empdata']="api/getEmpdata";
$route['empone']="api/empOne";
$route['emponeavail']="api/emp_OneAvail";
$route['todaydata']="api/todayData";
$route['upaccept']="api/updateAccept";
$route['upreason']="api/updateReason";
$route['emptwo']="api/empTwo";
$route['testt']="api/testt";
$route['currentdate']="api/currentDate";
$route['getdash']="api/getDash";
$route['leavetype']="api/leaveType";
$route['alternate']="api/alternateData";
$route['alternateaccept']="api/alternateAccept";
$route['cancelstatus']="api/cancelStatus";
$route['collegedata']="api/collegeData";
$route['departmentdata']="api/departmentData";
$route['persondata']="api/personData";
$route['empcount']="api/empCount";
$route['getprinc']="api/getPrinc";
$route['getcolg']="api/getColg";
$route['getdept']="api/getDept";
$route['alldata']="api/allData";
$route['allyeardata']="api/allYearData";
$route['allmonthdata']="api/allMonthData";
$route['getholiday']="api/getHoliday";
$route['checkholiday']="api/checkHoliday";

$route['getemail']="api/getEmail";
$route['applycoff']="api/applyCoff";
$route['coffhistory']="api/coffHistory";
$route['upreasoncoff']="api/upReasoncoff";
$route['coffstatus']="api/coffStatus";
$route['upAcceptcoff']="api/upAcceptcoff";
$route['getadmltypes']="api/getadmltypes";
$route['updateleavetype']="api/updateleavetype";
$route['addleavetype']="api/addleavetype";
$route['deleteleavetype']="api/deleteleavetype";
$route['getholidaylist']="api/getholidaylist";
$route['deleteholiday']="api/deleteholiday";
$route['getholidaylist']="api/getholidaylist";
$route['editholiday']="api/editholiday";
$route['addholiday']="api/addholiday";
$route['getroleslist']="api/getroleslist";
$route['deleteuserrole']="api/deleteuserrole";
$route['getrolesname']="api/getrolesname";
$route['changeuserrole']="api/changeuserrole";
$route['adduserrole']="api/adduserrole";
$route['getnewUserslist']="api/getnewUserslist";
$route['assignleaves']="api/assignleaves";
$route['getrole']="api/getrole";
$route['getTypeleavesdata']="api/getTypeleavesdata";
$route['empOneleaves']="api/empOneleaves";
$route['userDataa']="api/userDataa";
$route['checkvalid']="api/checkvalid";
$route['changeconfig']="api/changeconfig";
$route['deleteconfig']="api/deleteconfig";
$route['addconfig']="api/addconfig";


//cron jobs 

// $route['holidaysCR']="api/holidaysCR";
$route['carryforwardCR']="api/carryforwardCR";
$route['leaveApprovalCR']="api/leaveApprovalCR";
$route['crontesting']="api/crontesting";

