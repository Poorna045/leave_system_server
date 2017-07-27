<?php

class Api_model extends CI_Model
{
    function __construct()
    {
        parent::__construct();

        $this->load->database();
$CI =   &get_instance();
$this->db2 = $this->load->database('db2', TRUE);      
    }


    public function testdata(){

//  $data= $this->db2->query("select raghuerp_db.staff.dispname,raghuerp.Type_of_leave.reg_no from raghuerp_db.staff,raghuerp.Type_of_leave")->result();

        // $data= $this->db->query("select * from staff");
        $this->db2->where('reg_no', 'RECCSE001');
   
        $data = $this->db2->get('raghuerp_db.staff');
	return $data->row();
    }
    
    
    // validate login details
    function login($username, $password) 
    {
        $this->db->where('Username', $username);
        $this->db->where('Password', md5($password));
        $this->db->limit(1);
        $ugt = $this->db->get('raghuerp_db.staff');
        $cnt = $ugt->num_rows();

        if ($cnt) {
            $data = $ugt->row();
            return array("success"=>true, "userid"=>$data->reg_no, "dept"=>$data->Department, "colg"=>$data->college, "utype"=>$data->role, "name"=>$data->Username);
        } else {
            return array("success"=>false, "error"=>$username);
        }
    }

// login
public function logstat($insert){
$name= $insert['username'];
$pass= $insert['password'];


$sql="select utype from users where reg_no='$name' AND  password=md5('$pass')";
$query = $this->db->query($sql);

 $datas=$query->result();

if(sizeof($datas)==0){
  return false;
}else{
$utype=$datas[0]->utype;

if($utype=='stf'){
  $sql="select raghuerp_db.staff.reg_no,raghuerp_db.staff.dispname,raghuerp_db.staff.designation,raghuerp_db.staff.department,raghuerp_db.staff.college,raghuerp.Role.role from raghuerp_db.staff,raghuerp.Role where  raghuerp_db.staff.reg_no='$name' and raghuerp.Role.reg_no='$name' ";
$query = $this->db->query($sql)->row();
if($query!=''){
 $data=$query;
}else if($query==''){
   $sql="select raghuerp_db.staff.reg_no,raghuerp_db.staff.dispname,raghuerp_db.staff.designation,raghuerp_db.staff.department,raghuerp_db.staff.college,concat('Staff') as role from raghuerp_db.staff where  raghuerp_db.staff.reg_no='$name' ";
$query = $this->db->query($sql)->row();  
$data=$query;
}
 $data->utype=$utype;
}else if($utype=='std'){
  $sql="select * from raghuerp_db.students where reg_no='$name'";
$query = $this->db->query($sql);

 $data=$query->row();
 $data->utype=$utype;
}
else if($utype=='adm'){
  $sql="select raghuerp_db.admins.reg_no,raghuerp_db.admins.name,raghuerp.Role.role from raghuerp_db.admins,raghuerp.Role where raghuerp_db.admins.reg_no='$name' and raghuerp.Role.reg_no='$name'";
$query = $this->db->query($sql);

 $data=$query->row();
 $data->utype=$utype;
}

return $data;
}

}
    // user data
    function userData($eid) {
        $this->db->where('eid', $eid);
        $this->db->limit(1);
        $ugt = $this->db->get('examiner');
        $data = $ugt->row();
        return $data;
    }

   // changePassword
    function changePassword($eid, $params)
    {
        $old_pass = $params[0];
        $new_pass = $params[1];

        $data = $this->userData($eid);
        if($data->password == md5($old_pass)) {
            $upd['password'] = md5($new_pass);
            $this->db->where('eid', $data->eid);
            $st = $this->db->update("examiner", $upd);
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
    
     public function delete_userrole($role_id){


$this->db->query("DELETE FROM raghuerp.Role WHERE role_id='$role_id'");

return ;
     
   }

   public function getrolesname(){

$query2=$this->db->query("select * from raghuerp.allroles group by rname");

return $query2->result();
     
   }
    public function getroleslist(){

$query2=$this->db->query("select * from raghuerp.Role")->result();

return $query2;
     
   }

public function edit_Holiday($holdate,$holtype,$holname){

$query2=$this->db->query("Update raghuerp.holidays set holname='$holname',holtype='$holtype' where holdate='$holdate'");

return ;
    
}


public function adduserrole($reg_no,$role,$type,$upto){

 $data=$this->db->query("select * from raghuerp.Role where reg_no = '$reg_no'")->result();

   
    if(sizeof($data)==0){

$query=$this->db->query("INSERT INTO `raghuerp`.`Role` (`role_id`, `reg_no`, `role`,`type`,`upto`) VALUES (NULL, '$reg_no', '$role','$type','$upto')");


return ;
    }else{
        return 'already exists';
    }
    
}



public function changeuserrole($reg_no,$role,$role_id){

 $data=$this->db->query("select * from raghuerp.Role where reg_no = '$reg_no'")->result();

    if(sizeof($data)==0){

$query=$this->db->query("Update raghuerp.Role set reg_no='$reg_no', role='$role' where role_id='$role_id' ");


return ;
    }else if(sizeof($data)==1 && $data[0]->role_id==$role_id){

$query=$this->db->query("Update raghuerp.Role set reg_no='$reg_no', role='$role' where role_id='$role_id' ");


return ;
    }else{
        return 'already exists';
    }
    
}


public function changeconfig($sno,$email,$designation){
  

$query=$this->db->query("Update raghuerp.configuration set email='$email' where sno='$sno' ");


return '';
  
    
}

public function deleteconfig($sno){

 

$query=$this->db->query("Delete from raghuerp.configuration  where sno='$sno' ");


return '';
}
public function addconfig($email,$designation){


    $data=$this->db->query("select * from raghuerp.configuration where designation='$designation' OR email='$email' ")->result();

   
    if(sizeof($data)==0){

$query=$this->db->query("INSERT INTO `raghuerp`.`configuration` (`sno`, `designation`, `email`) VALUES (NULL, '$designation', '$email')");


return '';
    }else{
        return 'already exists';
    }


}
    

public function add_Holiday($holdate,$holtype,$holname){

 $data=$this->db->query("select * from raghuerp.holidays where holdate='$holdate'")->result();

   
    if(sizeof($data)==0){

$query=$this->db->query("INSERT INTO `holidays` (`sno`, `holdate`, `holname`, `holtype`) VALUES (NULL, '$holdate', '$holname', '$holtype')");


return ;
    }else{
        return 'already exists';
    }
    
}

public function addleavetype($type,$typename,$lstatus,$value,$carry){

    $data=$this->db->query("select type  from raghuerp.leavetypes where type='$type'")->result();

    $data2=$this->db->query("SHOW COLUMNS FROM `raghuerp`.`Type_of_leave` LIKE '$typename'")->result();

    if(sizeof($data)==0 && sizeof($data2)==0){

$query=$this->db->query("INSERT INTO `raghuerp`.`leavetypes` (`typeId`, `type`, `typename`, `lstatus`,`totaldays`,`carryfwd`) VALUES (NULL, '$type', '$typename', '$lstatus','$value','$carry')");


$query2=$this->db->query("ALTER TABLE `raghuerp`.`Type_of_leave` ADD `$typename` FLOAT NOT NULL ");
if($lstatus=='enable'){
$query3=$this->db->query("update raghuerp.Type_of_leave a set a.Total=(a.Total+".$value.") ,a.Remaining=(a.Remaining+".$value."),a.`$typename`='$value'");
}else{
    $query3=$this->db->query("update raghuerp.Type_of_leave a set a.Total=(a.Total+0) ,a.Remaining=(a.Remaining+0)");
}
return '' ;
    }else{
        return 'already exists';
    }
}


public function get_Info(){
$query=$this->db->query('SELECT * FROM   raghuerp.leave_issues');

return $query->result();
}


public function assignleaves($reg_no,$leaves,$name){
 $type=$leaves[0]['typename'];
  $value=$leaves[0]['value'];
$query=$this->db->query("INSERT INTO `raghuerp`.`Type_of_leave` (`reg_no`,`emp_name`,$type) VALUES ('$reg_no','$name','$value')");

$data=$this->db->query("select Total,Remaining from raghuerp.Type_of_leave where reg_no='$reg_no'");
$data=$data->row();
$total=$data->Total;
$rem=$data->Remaining;
$sql="update raghuerp.Type_of_leave set Total=(".$total."+$type),Remaining=(".$rem."+$type) where reg_no='$reg_no'";
$this->db->query($sql);
 
for($i=1;$i< sizeof($leaves);$i++){
    $type=$leaves[$i]['typename'];
      $value=$leaves[$i]['value'];
if($type!='LOP'){
$query=$this->db->query("update raghuerp.Type_of_leave set $type='$value' where reg_no='$reg_no' ");

$data=$this->db->query("select Total,Remaining from raghuerp.Type_of_leave where reg_no='$reg_no'");
$data=$data->row();
$total=$data->Total;
$rem=$data->Remaining;
$sql="update raghuerp.Type_of_leave set Total=(".$total."+$type),Remaining=(".$rem."+$type) where reg_no='$reg_no'";
$this->db->query($sql);
}
}



return ;
}

public function get_Status(){

$this->db->where('status','Pending');
$query = $this->db->get('raghuerp.leave_issues');


return $query->result();
}

public function get_Empdata($role,$dept,$colg){
  if($role=='HOD'){

$sql="SELECT raghuerp.Type_of_leave.*,concat('') as dispname,concat('') as department,concat('') as college,concat('') as designation FROM raghuerp.Type_of_leave ";

$query = $this->db->query($sql);

return $query->result();
  }
  if($role=='Principal'){

$sql="SELECT raghuerp.Type_of_leave.*,concat('') as dispname,concat('') as department,concat('') as college,concat('') as designation FROM raghuerp.Type_of_leave ";

$query = $this->db->query($sql);

return $query->result();
  }
    if($role=='Dean'){

$sql="SELECT raghuerp.Type_of_leave.*,concat('') as dispname,concat('') as department,concat('') as college,concat('') as designation FROM raghuerp.Type_of_leave";

$query = $this->db->query($sql);

return $query->result();
  }
    if($role=='Management'){

$sql="SELECT raghuerp.Type_of_leave.*,concat('') as dispname,concat('') as department,concat('') as college,concat('') as designation FROM raghuerp.Type_of_leave ";

$query = $this->db->query($sql);

return $query->result();
  }
}


public function delete_Holiday($sno){

$sql="DELETE FROM `raghuerp`.`holidays` WHERE `raghuerp`.`holidays`.`sno` = '$sno'";

$query = $this->db->query($sql);


return ;
}

public function check_Holiday($insert){
$from=$insert['from'];
$to=$insert['to'];
$sql="select count(*) as count from raghuerp.holidays where holdate between '$from' and '$to'";

$query = $this->db->query($sql);


return $query->result();
}


public function get_Holiday(){

$sql="select year(holdate * 1) as year,month(holdate * 1) as month,day(holdate * 1) as day from raghuerp.holidays";

$query = $this->db->query($sql);


return $query->result();
}

public function getholidaylist(){

$sql="select * from raghuerp.holidays ORDER BY `raghuerp`.`holidays`.`holdate` ASC";

$query = $this->db->query($sql);


return $query->result();
}

public function emp_One($id){


$dataa=$this->db->query("select type,typename from raghuerp.leavetypes where lstatus='enable'")->result();


$i=0;
foreach($dataa as $na){
    
   

$type=$na->typename;
$this->db->where('reg_no',$id);
$query = $this->db->get('raghuerp.Type_of_leave')->row()->$type;


$leavesdata[$i]['type']=$na->type;
$leavesdata[$i]['val']=$query;

$i++;

}
$this->db->where('reg_no',$id);
$tol = $this->db->get('raghuerp.Type_of_leave')->row()->Total;
$leavesdata[$i]['type']='Total';
$leavesdata[$i]['val']=$tol;
$this->db->where('reg_no',$id);
$rem = $this->db->get('raghuerp.Type_of_leave')->row()->Remaining;
$leavesdata[$i+1]['type']='Remaining';
$leavesdata[$i+1]['val']=$rem;


return $leavesdata;


}

public function emp_Oneleaves(){


$dataa=$this->db->query("select type,typename from raghuerp.leavetypes where lstatus='enable'")->result();



    
   
$test=$this->db->query("select * from raghuerp.Type_of_leave ")->result();
// foreach($test as $te){
    for($k=0 ; $k<sizeof($test);$k++){
   $te=$test[$k];
// foreach($dataa as $na){
    $i=1;
    $va=0;
    $leavesdata=[];
    for($r=0 ; $r<sizeof($dataa); $r++){
        $na=$dataa[$r];
    
$type=$na->typename;

// $this->db->where('reg_no',$te->reg_no);
// $name = $this->db->get('Type_of_leave')->row()->emp_name;

$this->db->where('reg_no',$te->reg_no);
$val = $this->db->get('raghuerp.Type_of_leave')->row()->$type;


$this->db->where('reg_no',$te->reg_no);
$reg_no = $this->db->get('raghuerp.Type_of_leave')->row()->reg_no;

if($va==0){
    
$leavesdata[0]=$reg_no;
// $leavesdata[1]=$name;
$va=1;
}
//$leavesdata[$i][$na->type]=$na->type;
$leavesdata[$i]=$val;


$i++;
}

$test[$k]=[];
$test[$k]=$leavesdata;

// unset($test[$te]);
// $test[$te]=$leavesdata;
}

return $test;

//echo $leavesdata;
// return $leave->row();

}






public function leave_Apply($insert){

return $this->db->insert('leave_issues',$insert);


}

public function coff_Apply($insert){

return $this->db->insert('coff',$insert);


}

public function crontesting(){

$this->db->query("update lmscron set cname='testingcron' where cid='16'");


}


public function leaveApprovalCR(){

 $sql="select l.*,s.department,s.college,s.email,(select s.email from raghuerp_db.staff s where s.reg_no=l.alternateId ) as altrmail from raghuerp.leave_issues l inner join raghuerp_db.staff s on l.reg_no=s.reg_no and l.to_date < SYSDATE() and l.status='Pending'";
 $leaveslist=$this->db->query($sql)->result();

 $con="SELECT * FROM raghuerp.configuration c where c.designation='Director' or c.designation='Chairman'";
 $config=$this->db->query($con)->result();
 
 for($i=0;$i<sizeof($leaveslist);$i++){
     $tol=$leaveslist[$i]->type_of_leave;
     $eid=$leaveslist[$i]->reg_no;
     $lid=$leaveslist[$i]->leave_id;
     $days=$leaveslist[$i]->days;
     $colg=$leaveslist[$i]->college;
     $dept=$leaveslist[$i]->department;

     $val=0;
if($tol != 'LOP'){
  $this->db->where('reg_no',$eid);
$remaining=$this->db->get('raghuerp.Type_of_leave')->row()->Remaining;

    $this->db->where('type',$tol);
 $ltype=$this->db->get('raghuerp.leavetypes')->row()->typename;

  $this->db->where('reg_no',$eid);
$sl=$this->db->get('raghuerp.Type_of_leave')->row()->$ltype;

   $this->db->where('reg_no',$eid);
$LOP=$this->db->get('raghuerp.Type_of_leave')->row()->LOP;

  $this->db->where('leave_id',$lid);
$lp=$this->db->get('raghuerp.leave_issues')->row()->lop;

}else if($tol == 'LOP'){
$this->db->where('reg_no',$eid);
$remaining=$this->db->get('raghuerp.Type_of_leave')->row()->Remaining;


   $this->db->where('reg_no',$eid);
$LOP=$this->db->get('raghuerp.Type_of_leave')->row()->LOP;

  $this->db->where('leave_id',$lid);
$lp=$this->db->get('raghuerp.leave_issues')->row()->lop;

  $val=1;
}


if($val==1){

  if($LOP-$lp<0){
 $sql="UPDATE raghuerp.Type_of_leave set LOP='0' WHERE reg_no='$eid' ";
$this->db->query($sql);
  }
   else if($LOP-$lp>=0){
 $sql="UPDATE raghuerp.Type_of_leave set LOP=$LOP-$lp WHERE reg_no='$eid' ";
$this->db->query($sql);
  }
 
}
else if($val==0){
    if($LOP-$lp<0){
       $sql="UPDATE raghuerp.Type_of_leave set $ltype=$sl+($days-$lp),LOP='0',Remaining=$remaining+($days-$lp) WHERE reg_no='$eid' ";

$this->db->query($sql);
  }
   else if($LOP-$lp>=0){
        $sql="UPDATE raghuerp.Type_of_leave set $ltype=$sl+($days-$lp),LOP=$LOP-$lp,Remaining=$remaining+($days-$lp) WHERE reg_no='$eid' ";

$this->db->query($sql);
  }

}



$qsl="select s.email,s.reg_no,r.role from raghuerp_db.staff s inner join raghuerp.Role r on s.reg_no=r.reg_no and s.college='$colg' and ( r.role='Principal' or (s.department='$dept' and r.role='HOD'))";
 $princ=$this->db->query($qsl)->result();

if($leaveslist[$i]->altrmail!=''){
 $test=[$leaveslist[$i]->email,$leaveslist[$i]->altrmail];

 $test[2]=$config[0]->email;
 $test[3]=$config[1]->email;
$qe=4;
}else if($leaveslist[$i]->altrmail==''){
   $test=[$leaveslist[$i]->email];

 $test[1]=$config[0]->email;
 $test[2]=$config[1]->email;
$qe=3;  
}
 for($k=0;$k<sizeof($princ);$k++){
     if($princ[$k]->role=='HOD' && $leaveslist[$i]->reg_no==$eid){
        
     }
     else if($princ[$k]->role=='Principal' && $leaveslist[$i]->reg_no==$eid){

     }else{
     $test[$qe]=$princ[$k]->email;
     $qe++;
     }
 
 }
 


 


 $message =  $eid ." \' s Leave Request is Closed" ;

        $this->db->set('status','Cancelled');
         $this->db->set('reject_reason','Closed');
		 $this->db->where('leave_id',$lid);
		 $this->db->update('raghuerp.leave_issues');
 
    

for($a=0;$a<sizeof($test);$a++){
     $params = Array(
        'to' => $test[$a],
        'cc' => '',
        'subject' => 'Leave Status',
        'message' => $message,
        'apptype' => 'LeaveSystem',
        'type'  => 'html', 
      );
        $this->sendEmail($params);

}

$test=[];

 }
// $pre="INSERT INTO raghuerp.lmscron(`cid`, `cname`, `cyear`) VALUES (NULL,'approval',year(now()))";
// $this->db->query($pre);

return;


}


public function carryforwardCR(){

//$this->db->query("update raghuerp.Type_of_leave a set a.Total=(a.Total+".$value.") ,a.Remaining=(a.Remaining+".$value."),a.`$typename`='$value'");
// }else{
//$this->db->query("update raghuerp.Type_of_leave a set a.Total=(a.Total+0) ,a.Remaining=(a.Remaining+0)");




$ltypes=$this->db->query("select * from raghuerp.leavetypes l where l.lstatus='enable'")->result();

for($i=0;$i<sizeof($ltypes);$i++){
$type=$ltypes[$i]->typename;
$tol=$ltypes[$i]->totaldays;
// $this->db->where('type',$this->post('type'));
// $typeval=$this->db->get('raghuerp.Type_of_leave')->row()->$type;

if($ltypes[$i]->lstatus=='enable' && $ltypes[$i]->carryfwd=='1'){
$this->db->query("update raghuerp.Type_of_leave t set t.Total=(t.Total+'$tol'),t.Remaining=(t.Remaining+'$tol'),`t`.`$type`=('$tol'+`t`.`$type`) ");


}else if($ltypes[$i]->lstatus=='enable' && $type=='LOP'){
// $this->db->query("update raghuerp.Type_of_leave t set `t`.`$type`=".$tol.",t.Total=(t.Total+'$tol'-$type),t.Remaining=(t.Remaining+'$tol'-$type) ");
}else if($ltypes[$i]->lstatus=='enable' && $ltypes[$i]->carryfwd!='1'){
   
$this->db->query("update raghuerp.Type_of_leave t set t.Total=((t.Total-`t`.`$type`)+'$tol'),t.Remaining=((t.Remaining-`t`.`$type`)+'$tol'),`t`.`$type`='$tol' ");
}

}
 $this->db->query("update raghuerp.Type_of_leave t set t.Total=0,t.Remaining=0 ");
for($k=0;$k<sizeof($ltypes);$k++){
 $type=$ltypes[$k]->typename;   
if($type=='LOP'){

}elseif($type!='LOP'){
    $this->db->query("update raghuerp.Type_of_leave t set t.Total=(t.Total+`t`.`$type`),t.Remaining=(t.Remaining+`t`.`$type`) ");
}

}


$pre="INSERT INTO raghuerp.lmscron(`cid`, `cname`, `cyear`) VALUES (NULL,'carryfwd',year(now()))";
$this->db->query($pre);
return ;
}

public function temprolesdelCR(){

 $empCount="select * from Role where upto<=CURDATE() and type='Temporary'";
      $query5 = $this->db->query($empCount);
     $data= $query5->result();

     for($k=0;$k<sizeof($data);$k++){
         $id=$data[$k]->role_id;
         $sql="DELETE FROM raghuerp.Role WHERE  role_id='$id' ";
     }

return ;
}


public function emp_Count($insert){

 $role=$insert['role'];
   $dept= $insert['dept'];
   $colg=$insert['colg'];
 
  
 
 
   if($insert['role']=='Management'){
 $empCount="SELECT COUNT(*)  as empcount,(SELECT COUNT(*) FROM raghuerp.leave_issues INNER JOIN raghuerp_db.staff ON raghuerp.leave_issues.reg_no = raghuerp_db.staff.reg_no  AND raghuerp.leave_issues.status='Accepted'  AND raghuerp.leave_issues.from_date<= concat(CURDATE(),' 23:00:00')  AND raghuerp.leave_issues.to_date >= concat(CURDATE(),' 00:00:00') ) as leavesLength FROM raghuerp_db.staff  ";
      $query5 = $this->db->query($empCount);
     return $query5->result();
  }
   if($insert['role']=='Principal'){
 $empCount="SELECT COUNT(*)  as empcount,(SELECT COUNT(*) FROM raghuerp.leave_issues INNER JOIN raghuerp_db.staff ON raghuerp.leave_issues.reg_no = raghuerp_db.staff.reg_no  AND raghuerp.leave_issues.status='Accepted' AND raghuerp_db.staff.college=(select id from raghuerp_db.colleges c where c.college='$colg') AND  raghuerp.leave_issues.from_date<= concat(CURDATE(),' 23:00:00')  AND raghuerp.leave_issues.to_date >= concat(CURDATE(),' 00:00:00') ) as leavesLength FROM raghuerp_db.staff Where  raghuerp_db.staff.college=(select id from raghuerp_db.colleges c where c.college='$colg')";
      $query5 = $this->db->query($empCount);
     return $query5->result();
  }

}


public function all_Data($insert){

$inst=$insert['inst'];
$role=$insert['role'];
$dept=$insert['dept'];
$colg=$insert['colg'];
$id=$insert['day'];

if($insert['inst']==1){
    if($role == "Principal"){

       if($colg=='RCP'){
      $bphar="SELECT raghuerp.leave_issues.* ,raghuerp_db.staff.dispname,raghuerp_db.staff.college,raghuerp_db.staff.department,raghuerp_db.staff.designation,concat('') as role FROM raghuerp.leave_issues INNER JOIN raghuerp_db.staff ON raghuerp.leave_issues.reg_no = raghuerp_db.staff.reg_no  AND raghuerp_db.staff.department='BPHARM' AND concat('$id',' 12:00:00') Between raghuerp.leave_issues.from_date AND raghuerp.leave_issues.to_date";
    $query7 = $this->db->query($bphar);
     $query['bPHAR'] =$query7->result();

      $mphar="SELECT raghuerp.leave_issues.* ,raghuerp_db.staff.dispname,raghuerp_db.staff.college,raghuerp_db.staff.department,raghuerp_db.staff.designation,concat('') as role FROM raghuerp.leave_issues INNER JOIN raghuerp_db.staff ON raghuerp.leave_issues.reg_no = raghuerp_db.staff.reg_no  AND raghuerp_db.staff.department='MPHARM' AND concat('$id',' 12:00:00') Between raghuerp.leave_issues.from_date AND raghuerp.leave_issues.to_date";
    $query8 = $this->db->query($mphar);
     $query['mPHAR'] =$query8->result();

      $sql="SELECT raghuerp.leave_issues.* ,raghuerp_db.staff.dispname,raghuerp_db.staff.college,raghuerp_db.staff.department,raghuerp_db.staff.designation,concat('') as role FROM raghuerp.leave_issues INNER JOIN raghuerp_db.staff ON raghuerp.leave_issues.reg_no = raghuerp_db.staff.reg_no AND raghuerp_db.staff.college='$colg' AND concat('$id',' 12:00:00') Between raghuerp.leave_issues.from_date AND raghuerp.leave_issues.to_date";
     $query1 = $this->db->query($sql);
     $query['data'] =$query1->result();
}
else{
          $sql="SELECT raghuerp.leave_issues.* ,raghuerp_db.staff.dispname,raghuerp_db.staff.college,raghuerp_db.staff.department,raghuerp_db.staff.designation,concat('') as role FROM raghuerp.leave_issues INNER JOIN raghuerp_db.staff ON raghuerp.leave_issues.reg_no = raghuerp_db.staff.reg_no AND raghuerp_db.staff.college='$colg' AND concat('$id',' 12:00:00') Between raghuerp.leave_issues.from_date AND raghuerp.leave_issues.to_date";
     $query1 = $this->db->query($sql);
     $query['data'] =$query1->result();

      $CSE="SELECT raghuerp.leave_issues.* ,raghuerp_db.staff.dispname,raghuerp_db.staff.college,raghuerp_db.staff.department,raghuerp_db.staff.designation,concat('') as role FROM raghuerp.leave_issues INNER JOIN raghuerp_db.staff ON raghuerp.leave_issues.reg_no = raghuerp_db.staff.reg_no AND raghuerp_db.staff.college='$colg' AND raghuerp_db.staff.department='CSE' AND concat('$id',' 12:00:00') Between raghuerp.leave_issues.from_date AND raghuerp.leave_issues.to_date";
    $query2 = $this->db->query($CSE);
     $query['CSE'] =$query2->result();

        $ECE="SELECT raghuerp.leave_issues.* ,raghuerp_db.staff.dispname,raghuerp_db.staff.college,raghuerp_db.staff.department,raghuerp_db.staff.designation,concat('') as role FROM raghuerp.leave_issues INNER JOIN raghuerp_db.staff ON raghuerp.leave_issues.reg_no = raghuerp_db.staff.reg_no AND raghuerp_db.staff.college='$colg' AND raghuerp_db.staff.department='ECE'  AND concat('$id',' 12:00:00') Between raghuerp.leave_issues.from_date AND raghuerp.leave_issues.to_date";
    $query3 = $this->db->query($ECE);
     $query['ECE'] =$query3->result();

       $CIVIL="SELECT raghuerp.leave_issues.* ,raghuerp_db.staff.dispname,raghuerp_db.staff.college,raghuerp_db.staff.department,raghuerp_db.staff.designation,concat('') as role FROM raghuerp.leave_issues INNER JOIN raghuerp_db.staff ON raghuerp.leave_issues.reg_no = raghuerp_db.staff.reg_no AND raghuerp_db.staff.college='$colg' AND raghuerp_db.staff.department='CIVIL' AND concat('$id',' 12:00:00') Between raghuerp.leave_issues.from_date AND raghuerp.leave_issues.to_date";
    $query4 = $this->db->query($CIVIL);
     $query['CIVIL'] =$query4->result();

      $EEE="SELECT raghuerp.leave_issues.* ,raghuerp_db.staff.dispname,raghuerp_db.staff.college,raghuerp_db.staff.department,raghuerp_db.staff.designation,concat('') as role FROM raghuerp.leave_issues INNER JOIN raghuerp_db.staff ON raghuerp.leave_issues.reg_no = raghuerp_db.staff.reg_no AND raghuerp_db.staff.college='$colg' AND raghuerp_db.staff.department='EEE'  AND concat('$id',' 12:00:00') Between raghuerp.leave_issues.from_date AND raghuerp.leave_issues.to_date";
    $query5 = $this->db->query($EEE);
     $query['EEE'] =$query5->result();

      $MECH="SELECT raghuerp.leave_issues.* ,raghuerp_db.staff.dispname,raghuerp_db.staff.college,raghuerp_db.staff.department,raghuerp_db.staff.designation,concat('') as role FROM raghuerp.leave_issues INNER JOIN raghuerp_db.staff ON raghuerp.leave_issues.reg_no = raghuerp_db.staff.reg_no AND raghuerp_db.staff.college='$colg' AND raghuerp_db.staff.department='MECH'  AND concat('$id',' 12:00:00') Between raghuerp.leave_issues.from_date AND raghuerp.leave_issues.to_date";
    $query6 = $this->db->query($MECH);
     $query['MECH'] =$query6->result();

}


    }
    else{

    $REC="SELECT raghuerp.leave_issues.* ,raghuerp_db.staff.dispname,raghuerp_db.staff.college,raghuerp_db.staff.department,raghuerp_db.staff.designation,concat('') as role FROM raghuerp.leave_issues INNER JOIN raghuerp_db.staff ON raghuerp.leave_issues.reg_no = raghuerp_db.staff.reg_no  AND raghuerp_db.staff.college='REC' AND raghuerp.leave_issues.from_date <= concat(CURDATE(),' 23:00:00') AND raghuerp.leave_issues.to_date <= concat(CURDATE(),' 00:00:00')";
    $query1 = $this->db->query($REC);
      $query['REC'] =$query1->result();

     $RIT="SELECT raghuerp.leave_issues.* ,raghuerp_db.staff.dispname,raghuerp_db.staff.college,raghuerp_db.staff.department,raghuerp_db.staff.designation,concat('') as role FROM raghuerp.leave_issues INNER JOIN raghuerp_db.staff ON raghuerp.leave_issues.reg_no = raghuerp_db.staff.reg_no AND raghuerp_db.staff.college='RIT' AND raghuerp.leave_issues.from_date <= concat(CURDATE(),' 23:00:00') AND raghuerp.leave_issues.to_date <= concat(CURDATE(),' 00:00:00')";
    $query2 = $this->db->query($RIT);
    $query['RIT'] =$query2->result();

     $RCP="SELECT raghuerp.leave_issues.* ,raghuerp_db.staff.dispname,raghuerp_db.staff.college,raghuerp_db.staff.department,raghuerp_db.staff.designation,concat('') as role FROM raghuerp.leave_issues INNER JOIN raghuerp_db.staff ON raghuerp.leave_issues.reg_no = raghuerp_db.staff.reg_no  AND raghuerp_db.staff.college='RCP' AND raghuerp.leave_issues.from_date <= concat(CURDATE(),' 23:00:00') AND raghuerp.leave_issues.to_date <= concat(CURDATE(),' 00:00:00')";
    $query3 = $this->db->query($RCP);
     $query['RCP'] =$query3->result();

           $sql="SELECT raghuerp.leave_issues.* ,raghuerp_db.staff.dispname,raghuerp_db.staff.college,raghuerp_db.staff.department,raghuerp_db.staff.designation,concat('') as role FROM raghuerp.leave_issues INNER JOIN raghuerp_db.staff ON raghuerp.leave_issues.reg_no = raghuerp_db.staff.reg_no   AND raghuerp.leave_issues.from_date <= concat(CURDATE(),' 23:00:00') AND raghuerp.leave_issues.to_date <= concat(CURDATE(),' 00:00:00')";
    $query4 = $this->db->query($sql);
     $query['data'] =$query4->result();

   
    }
return $query;
}
else{


$sql="SELECT raghuerp.leave_issues.* ,raghuerp_db.staff.dispname,raghuerp_db.staff.college,raghuerp_db.staff.department,raghuerp_db.staff.designation,concat('') as role FROM raghuerp.leave_issues INNER JOIN raghuerp_db.staff ON raghuerp.leave_issues.reg_no = raghuerp_db.staff.reg_no  AND raghuerp_db.staff.department='$dept' AND raghuerp_db.staff.college='$colg'  AND from_date <= concat(CURDATE(),' 23:00:00') AND to_date <= concat(CURDATE(),' 00:00:00')";

$query = $this->db->query($sql);

return $query->result();
}
}

public function all_monthData($insert){

$role=$insert['role'];
$dept=$insert['dept'];
$colg=$insert['colg'];
$id=$insert['year'];
$mon=$insert['month'];

   
  

 if($role == "Principal"){

        $deptslist="select d.department from raghuerp_db.departments d where d.college=(select c.id from raghuerp_db.colleges c where c.college='$colg') group by d.department";
        $list=$this->db->query($deptslist)->result();
for($i=0;$i<sizeof($list);$i++){
    $deptl=$list[$i]->department;
   
  $depts="select l.*, c.college,d.department,s.firstname,s.email,s.designation,s.employment_type,(select s.firstname from raghuerp_db.staff s where s.reg_no=l.alternateId) as Altername from  raghuerp.leave_issues l left join raghuerp_db.staff s on l.reg_no=s.reg_no   AND ( YEAR(`l`.`from_date`)='$id' OR YEAR(`l`.`to_date`)='$id' )  AND ( MONTH(`from_date`)='$mon' OR MONTH(`to_date`)='$mon' )  inner join  raghuerp_db.departments d on s.department=d.id inner join raghuerp_db.colleges c on d.college=c.id and c.college='$colg' and d.department='$deptl'";
    $query8 = $this->db->query($depts);
    // $query['data']=(object) null;
    $query['data'][$deptl] =[];
     $query['data'][$deptl] =$query8->result();
}

$alldepts="select l.*, c.college,d.department,s.firstname,s.email,s.designation,s.employment_type,(select s.firstname from raghuerp_db.staff s where s.reg_no=l.alternateId) as Altername from  raghuerp.leave_issues l left join raghuerp_db.staff s on l.reg_no=s.reg_no   AND ( YEAR(`l`.`from_date`)='$id' OR YEAR(`l`.`to_date`)='$id' )  AND ( MONTH(`from_date`)='$mon' OR MONTH(`to_date`)='$mon' )  inner join  raghuerp_db.departments d on s.department=d.id inner join raghuerp_db.colleges c on d.college=c.id and c.college='$colg'";
    $querys = $this->db->query($alldepts);
    // $query['data']=(object) null;
    $query['data']['ALL'] =[];
     $query['data']['ALL'] =$querys->result();


      $bphar="select d.department, count(l.leave_id) as y from raghuerp_db.staff s left join raghuerp.leave_issues l on s.reg_no=l.reg_no   AND ( YEAR(`l`.`from_date`)='$id' OR YEAR(`l`.`to_date`)='$id' )  AND ( MONTH(`l`.`from_date`)='$mon' OR MONTH(`l`.`to_date`)='$mon' )  inner join  raghuerp_db.departments d on s.department=d.id and s.college=(select id from raghuerp_db.colleges c where c.college='$colg') group by d.department";
    $query7 = $this->db->query($bphar);
     $query['depts'] =$query7->result();

return $query;
    }
    else if($role=='Dean'){

        
$clgquery="select c.college as name,concat('0') as y from raghuerp_db.colleges c group by c.college";
$query['clg']=$this->db->query($clgquery)->result();

    $bphar="select c.college as name, count(l.leave_id) as y from raghuerp_db.staff s left join raghuerp.leave_issues l on s.reg_no=l.reg_no    AND ( YEAR(`l`.`from_date`)='$id' OR YEAR(`l`.`to_date`)='$id' )  AND ( MONTH(`l`.`from_date`)='$mon' OR MONTH(`l`.`to_date`)='$mon' )  inner join raghuerp_db.colleges c on s.college=c.id  group by c.college";
    $query7 = $this->db->query($bphar);
     $query['colgs'] =$query7->result();


$colgdataam=[];


for($s=0;$s<sizeof($query['clg']);$s++){

for($t=0;$t<sizeof($query['colgs']);$t++){
	
	$colgdataam[$s]=(object) null;

if($query['colgs'][$t]->name==$query['clg'][$s]->name){
	$colgdataam[$s]->name=$query['clg'][$s]->name;
	$colgdataam[$s]->y=(int)$query['colgs'][$t]->y;

	break;
}
else{
    $colgdataam[$s]->name=$query['clg'][$s]->name;
    $colgdataam[$s]->y=(int)$query['clg'][$s]->y;
}

	}
  
}
$colgs=$colgdataam;

   return $colgs;
    } 
  

}


public function all_yearData($insert){


$role=$insert['role'];
$dept=$insert['dept'];
$colg=$insert['colg'];
$id=$insert['year'];


 if($role == "Principal"){

        $deptslist="select d.department from raghuerp_db.departments d where d.college=(select c.id from raghuerp_db.colleges c where c.college='$colg') group by d.department";
        $list=$this->db->query($deptslist)->result();
for($i=0;$i<sizeof($list);$i++){
    $deptl=$list[$i]->department;
   
  $depts="select l.*, c.college,d.department,s.firstname,s.email,s.designation,s.employment_type,(select s.firstname from raghuerp_db.staff s where s.reg_no=l.alternateId) as Altername from  raghuerp.leave_issues l left join raghuerp_db.staff s on l.reg_no=s.reg_no   AND ( YEAR(`l`.`from_date`)='$id' OR YEAR(`l`.`to_date`)='$id' )  inner join  raghuerp_db.departments d on s.department=d.id inner join raghuerp_db.colleges c on d.college=c.id and c.college='$colg' and d.department='$deptl'";
    $query8 = $this->db->query($depts);
    // $query['data']=(object) null;
    $query['data'][$deptl] =[];
     $query['data'][$deptl] =$query8->result();
}

$alldepts="select l.*, c.college,d.department,s.firstname,s.email,s.designation,s.employment_type,(select s.firstname from raghuerp_db.staff s where s.reg_no=l.alternateId) as Altername from  raghuerp.leave_issues l left join raghuerp_db.staff s on l.reg_no=s.reg_no   AND ( YEAR(`l`.`from_date`)='$id' OR YEAR(`l`.`to_date`)='$id' )  inner join  raghuerp_db.departments d on s.department=d.id inner join raghuerp_db.colleges c on d.college=c.id and c.college='$colg'";
    $querys = $this->db->query($alldepts);
    // $query['data']=(object) null;
    $query['data']['ALL'] =[];
     $query['data']['ALL'] =$querys->result();

      $bphar="select d.department, count(l.leave_id) as y from raghuerp_db.staff s left join raghuerp.leave_issues l on s.reg_no=l.reg_no   AND ( YEAR(`l`.`from_date`)='$id' OR YEAR(`l`.`to_date`)='$id' )  inner join  raghuerp_db.departments d on s.department=d.id and s.college=(select id from raghuerp_db.colleges c where c.college='$colg') group by d.department";
    $query7 = $this->db->query($bphar);
     $query['depts'] =$query7->result();

return $query;
    }
    else if($role=='Dean'){

        
$clgquery="select c.college as name,concat('0') as y from raghuerp_db.colleges c group by c.college";
$query['clg']=$this->db->query($clgquery)->result();

    $bphar="select c.college as name, count(l.leave_id) as y from raghuerp_db.staff s left join raghuerp.leave_issues l on s.reg_no=l.reg_no   AND ( YEAR(`l`.`from_date`)='$id' OR YEAR(`l`.`to_date`)='$id' )  inner join raghuerp_db.colleges c on s.college=c.id  group by c.college";
    $query7 = $this->db->query($bphar);
     $query['colgs'] =$query7->result();


$colgdataam=[];


for($s=0;$s<sizeof($query['clg']);$s++){

for($t=0;$t<sizeof($query['colgs']);$t++){
	
	$colgdataam[$s] = (object) null;

if($query['colgs'][$t]->name == $query['clg'][$s]->name){
	$colgdataam[$s]->name = $query['clg'][$s]->name;
	$colgdataam[$s]->y    = (int)$query['colgs'][$t]->y;

	break;
}
else{
    $colgdataam[$s]->name = $query['clg'][$s]->name;
    $colgdataam[$s]->y    = (int)$query['clg'][$s]->y;
}

	}
  
}
$colgs = $colgdataam;

   return $colgs;
    }

}


public function today_Data($insert){


$role=$insert['role'];
$dept=$insert['dept'];
$colg=$insert['colg'];
$date=$insert['date'];

 if($role == "Principal"){

        $deptslist="select d.department from raghuerp_db.departments d where d.college=(select c.id from raghuerp_db.colleges c where c.college='$colg') group by d.department";
        $list=$this->db->query($deptslist)->result();
for($i=0;$i<sizeof($list);$i++){
    $dept=$list[$i]->department;
   
  $depts="select l.*, c.college,d.department,s.firstname,s.email,s.designation,s.employment_type,(select s.firstname from raghuerp_db.staff s where s.reg_no=l.alternateId) as Altername from  raghuerp.leave_issues l left join raghuerp_db.staff s on l.reg_no=s.reg_no  and l.from_date <= concat('$date',' 23:00:00') and l.to_date >= concat('$date',' 00:00:00') inner join  raghuerp_db.departments d on s.department=d.id inner join raghuerp_db.colleges c on d.college=c.id and c.college='$colg' and d.department='$dept'";
    $query8 = $this->db->query($depts);
    // $query['data']=(object) null;
    $query['data'][$dept] =[];
     $query['data'][$dept] =$query8->result();
}

$alldepts="select l.*, c.college,d.department,s.firstname,s.email,s.designation,s.employment_type,(select s.firstname from raghuerp_db.staff s where s.reg_no=l.alternateId) as Altername from  raghuerp.leave_issues l left join raghuerp_db.staff s on l.reg_no=s.reg_no  and l.from_date <= concat('$date',' 23:00:00') and l.to_date >= concat('$date',' 00:00:00') inner join  raghuerp_db.departments d on s.department=d.id inner join raghuerp_db.colleges c on d.college=c.id and c.college='$colg'";
    $querys = $this->db->query($alldepts);
    // $query['data']=(object) null;
    $query['data']['ALL'] =[];
     $query['data']['ALL'] =$querys->result();

      $bphar="select d.department, count(l.leave_id) as y from raghuerp_db.staff s left join raghuerp.leave_issues l on s.reg_no=l.reg_no  and l.from_date <= concat('$date',' 23:00:00') and l.to_date >= concat('$date',' 00:00:00') inner join  raghuerp_db.departments d on s.department=d.id and s.college=(select id from raghuerp_db.colleges c where c.college='$colg') group by d.department";
    $query7 = $this->db->query($bphar);
     $query['depts'] =$query7->result();

return $query;
    }
    else if($role=='Dean'){

        
$clgquery="select c.college as name,concat('0') as y from raghuerp_db.colleges c group by c.college";
$query['clg']=$this->db->query($clgquery)->result();

    $bphar="select c.college as name, count(l.leave_id) as y from raghuerp_db.staff s left join raghuerp.leave_issues l on s.reg_no=l.reg_no and  l.from_date <= concat('$date',' 23:00:00') and l.to_date > concat('$date',' 00:00:00') inner join raghuerp_db.colleges c on s.college=c.id  group by c.college";
    $query7 = $this->db->query($bphar);
     $query['colgs'] =$query7->result();




$colgdataam=[];


for($s=0;$s<sizeof($query['clg']);$s++){

for($t=0;$t<sizeof($query['colgs']);$t++){
	
	$colgdataam[$s]=(object) null;

if($query['colgs'][$t]->name==$query['clg'][$s]->name){
	$colgdataam[$s]->name=$query['clg'][$s]->name;
	$colgdataam[$s]->y=(int)$query['colgs'][$t]->y;

	break;
}
else{
    $colgdataam[$s]->name=$query['clg'][$s]->name;
    $colgdataam[$s]->y=(int)$query['clg'][$s]->y;
}

	}
  
}
$colgs=$colgdataam;

   return $colgs;
    }


}

public function current_Date(){
  $sql="SELECT CURDATE() as todayDate from DUAL";

$query = $this->db->query($sql);

return $query->result();
}
public function upDays($data){
    $days=$data['days'];

$this->db->where('stud_name',$days);
$this->db->update('raghuerp.Type_of_leave',$updatee);


return $query->row();

}


public function getTypeleavesdata(){


$sql="SELECT * FROM raghuerp.Type_of_leave";

$query = $this->db->query($sql);


return $query->result();

}

public function get_coffHistory($insert){
$data= $insert;

$sql="SELECT * FROM raghuerp.coff WHERE reg_no='$data' ORDER BY from_date DESC";

$query = $this->db->query($sql);


return $query->result();

}

public function get_History($insert){
$data= $insert;

$sql="SELECT *,concat('') as Altername FROM raghuerp.leave_issues WHERE reg_no='$data' ORDER BY from_date DESC";

$query = $this->db->query($sql);


return $query->result();

}



public function update_Data($updatee){
$name= $updatee['stud_name'];

$this->db->where('stud_name',$name);
$this->db->update('Student',$updatee);
return;

}

public function delete_Data(){

$this->db->where('stud_id',$this->uri->segment(3));
$this->db->delete('Student');

}
public function getAvail($data){
$empid=$data;
$this->db->where('reg_no',$empid['emp_id']);
    $query=$this->db->get('raghuerp.Type_of_leave');

   return $query->result();

}

public function setAvail($updatee){
$name= $updatee;

$this->db->where('leave_id',$name['leave_id']);
$this->db->update('raghuerp.leave_issues',$name);
return;

}


public function update_Acceptcoff($lid,$eid,$dept,$colg,$acceptedBy,$nam,$type,$uname,$days,$emailHOD){

   $this->db->where('reg_no',$eid);
$coffleave=$this->db->get('raghuerp.Type_of_leave')->row()->compensatory_leave;
   
   $this->db->where('reg_no',$eid);
$rem=$this->db->get('raghuerp.Type_of_leave')->row()->Remaining;

   $this->db->where('reg_no',$eid);
$tot=$this->db->get('raghuerp.Type_of_leave')->row()->Total;

 $this->db->set('status','Accepted');
		 $this->db->where('cid',$lid);
		 $this->db->update('raghuerp.coff');


$s=$days+$coffleave;
$c=$days+$rem;
$t=$days+$tot;

 $this->db->set('compensatory_leave',$s);
  $this->db->set('Remaining',$c);
   $this->db->set('Total',$t);
		 $this->db->where('reg_no',$eid);
		 $this->db->update('raghuerp.Type_of_leave');

//email





// $sql2="select reg_no,email,dispname from raghuerp_db.staff where (((role='Hod' and department='$dept' ||  role='Principal' )  and college='$colg' )||reg_no='$eid')" ;

// $test=$this->db->query($sql2)->result();
$test=$emailHOD;

$sql3="select email from raghuerp.configuration where designation='Director' || designation='Chairman'" ;

$emails=$this->db->query($sql3)->result();

$tes=sizeof($test);
$te=sizeof($emails);


  $mail=[];
$i=sizeof($test);

  
foreach($emails as $na){

	$test[$i]= $na->email;

	$i++;
}

 $message =  $acceptedBy ." is ". $type ."  ".  $uname  .  " \' s coff Request " ;


for($a=0;$a<sizeof($test);$a++){
     $params = Array(
        'to' => $test[$a],
        'cc' => '',
        'subject' => 'Leave Status',
        'message' => $message,
        'apptype' => 'LeaveSystem',
        'type'  => 'html', 
      );
        $this->sendEmail($params);

}
// $this->sendEmail($test,$message);

//email




return;

}



public function update_Accept($lid,$eid,$dept,$colg,$acceptedBy,$nam,$aid,$type,$uname,$emailHod){



 $this->db->set('status','Accepted');
  $this->db->set('status','Accepted');
		 $this->db->where('leave_id',$lid);
		 $this->db->update('raghuerp.leave_issues');



//email





// $sql2="select reg_no,email,dispname from raghuerp_db.staff where (((role='Hod' and department='$dept' ||  role='Principal' )  and college='$colg' )||reg_no='$eid'||reg_no='$aid')" ;

// $test=$this->db->query($sql2)->result();
$test=$emailHod;

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

 $message =  $acceptedBy ." is ". $type ."  ".  $uname  .  " \' s Leave Request " ;

for($a=0;$a<sizeof($test);$a++){
     $params = Array(
        'to' => $test[$a],
        'cc' => '',
        'subject' => 'Leave Status',
        'message' => $message,
        'apptype' => 'LeaveSystem',
        'type'  => 'html', 
      );
        $this->sendEmail($params);

}
// $this->sendEmail($test,$message);

//email




return;

}


public function update_Reasoncoff($type,$reason,$days,$eid,$cid,$rejectedBy,$dept,$colg,$role,$nam,$uname,$emailHOD){


//email



// $sql2="select reg_no,email,dispname from raghuerp_db.staff where (((role='Hod' and department='$dept' ||  role='Principal' )  and college='$colg' )||reg_no='$eid')" ;

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



if($type == 'Rejected'){

 $sql="UPDATE raghuerp.coff set status='$type',rejectedby='$rejectedBy' WHERE cid='$cid' ";
$this->db->query($sql);

 $message =  $role ." is ". $type ."  ".  $uname .  " \' s coff \' s Request " ;
 
 $this->sendEmail($test,$message);
return;
}
if($type == 'Cancelled'){
 $sql="UPDATE raghuerp.coff set status='$type' WHERE cid='$cid' ";
$this->db->query($sql);

 $message =  $uname  ." is ". $type   .  "  coff \' s Request " ;
for($a=0;$a<sizeof($test);$a++){
     $params = Array(
        'to' => $test[$a],
        'cc' => '',
        'subject' => 'Leave Status',
        'message' => $message,
        'apptype' => 'LeaveSystem',
        'type'  => 'html', 
      );
        $this->sendEmail($params);

}
//   $this->sendEmail($test,$message);
return ;
}
}



public function update_Reason($type,$reason,$days,$eid,$lid,$tol,$rejectedBy,$dept,$colg,$role,$nam,$alterid,$uname,$emailHOD){
$val=0;
if($tol != 'LOP'){
  $this->db->where('reg_no',$eid);
$remaining=$this->db->get('raghuerp.Type_of_leave')->row()->Remaining;

    $this->db->where('type',$tol);
 $ltype=$this->db->get('raghuerp.leavetypes')->row()->typename;

  $this->db->where('reg_no',$eid);
$sl=$this->db->get('raghuerp.Type_of_leave')->row()->$ltype;

   $this->db->where('reg_no',$eid);
$LOP=$this->db->get('raghuerp.Type_of_leave')->row()->LOP;

  $this->db->where('leave_id',$lid);
$lp=$this->db->get('raghuerp.leave_issues')->row()->lop;

}else if($tol == 'LOP'){
$this->db->where('reg_no',$eid);
$remaining=$this->db->get('raghuerp.Type_of_leave')->row()->Remaining;


   $this->db->where('reg_no',$eid);
$LOP=$this->db->get('raghuerp.Type_of_leave')->row()->LOP;

  $this->db->where('leave_id',$lid);
$lp=$this->db->get('raghuerp.leave_issues')->row()->lop;

  $val=1;
}


if($val==1){

  if($LOP-$lp<0){
 $sql="UPDATE raghuerp.Type_of_leave set LOP='0' WHERE reg_no='$eid' ";
$this->db->query($sql);
  }
   else if($LOP-$lp>=0){
 $sql="UPDATE raghuerp.Type_of_leave set LOP=$LOP-$lp WHERE reg_no='$eid' ";
$this->db->query($sql);
  }
 
}
else if($val==0){
    if($LOP-$lp<0){
       $sql="UPDATE raghuerp.Type_of_leave set $ltype=$sl+($days-$lp),LOP='0',Remaining=$remaining+($days-$lp) WHERE reg_no='$eid' ";

$this->db->query($sql);
  }
   else if($LOP-$lp>=0){
        $sql="UPDATE raghuerp.Type_of_leave set $ltype=$sl+($days-$lp),LOP=$LOP-$lp,Remaining=$remaining+($days-$lp) WHERE reg_no='$eid' ";

$this->db->query($sql);
  }

}


//email



// $sql2="select reg_no,email,dispname from raghuerp_db.staff where (((role='Hod' and department='$dept' ||  role='Principal' )  and college='$colg' )||reg_no='$eid' || reg_no='$alterid')" ;

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

//print_r($mail);

// foreach($emails as $na){
//    echo $na->email.' $na test';
// 	$mail[$i]= $na->email;
//   echo $mail[$i].' ptest';
// 	$i++;
// }



//email




if($type == 'Rejected'){

 $message =  $role ." is ". $type ."  ".  $uname .  " \' s Leave Request " ;

        $this->db->set('status','Rejected');
         $this->db->set('rejectedby',$rejectedBy);
         $this->db->set('reject_reason',$reason);
		 $this->db->where('leave_id',$lid);
		 $this->db->update('raghuerp.leave_issues');
 
    for($a=0;$a<sizeof($test);$a++){
     $params = Array(
        'to' => $test[$a],
        'cc' => '',
        'subject' => 'Leave Status',
        'message' => $message,
        'apptype' => 'LeaveSystem',
        'type'  => 'html', 
      );
        $this->sendEmail($params);

}
//   $this->sendEmail($test,$message);
return;
}
if($type == 'Alternate Suggestion'){

 $message =  $role ." is ". $type  ."  ". $uname .  " \' s Leave Request " ;


 $this->db->set('status','Alternate Suggestion');
 $this->db->set('rejectedby',$rejectedBy);
  $this->db->set('alter_reason',$reason);
		 $this->db->where('leave_id',$lid);
		 $this->db->update('raghuerp.leave_issues');
    
for($a=0;$a<sizeof($test);$a++){
     $params = Array(
        'to' => $test[$a],
        'cc' => '',
        'subject' => 'Leave Status',
        'message' => $message,
        'apptype' => 'LeaveSystem',
        'type'  => 'html', 
      );
        $this->sendEmail($params);

}

//    $this->sendEmail($test,$message);
return;
}
if($type == 'Cancelled'){

 $message =  $uname  ." is ". $type   .  " Leave Request " ;

        $this->db->set('status','Cancelled');
		 $this->db->where('leave_id',$lid);
		 $this->db->update('raghuerp.leave_issues');
 
    for($a=0;$a<sizeof($test);$a++){
     $params = Array(
        'to' => $test[$a],
        'cc' => '',
        'subject' => 'Leave Status',
        'message' => $message,
        'apptype' => 'LeaveSystem',
        'type'  => 'html', 
      );
        $this->sendEmail($params);

}

//    $this->sendEmail($test,$message);
return ;
}
}
public function emp_Two($id){

$this->db->where('reg_no',$id);
 $this->db->order_by("leave_id", "DESC");
$this->db->limit(3);
$query = $this->db->get('raghuerp.leave_issues');


return $query->result();
}

// send mail
public function sendEmail($params)
    {
        $to = $params['to'];
        $cc = $params['cc'];
        $subject = $params['subject']; // Leave Status or ..
        $message = $params['message'];
        $type = $params['type'];  // html or ..
       $apptype=$params['apptype']; // eg LeaveSystem or ..
        $sql = "insert into raghuerp_db.messages(mtype, mailtype, mto, cc, subject, message,application_type) values('mail', '$type', '$to', '$cc', '$subject', '$message','$apptype')";
        if ($this->db->query($sql)) {
            return (array("success" => true));
        }
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
