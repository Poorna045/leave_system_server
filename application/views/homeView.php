<?php

echo "<br>Students Information !<br><br>";
if(isset($records)){


    
   //  echo  'Id    ||   Name    ||  %  ||  Dept <br>';
foreach($records as $rec){?>
<?php echo $rec->emp_id ;?>
   <?php echo $rec->reason ;?>
     <?php
   // echo $rec->stud_id. '        '.$rec->stud_name.'       '.$rec->percentage.'       '.$rec->dept;
    //echo anchor("HomeController/DeleteStudData/$rec->stud_id",'<button>x</button>').'<br>';
}
}
else{
    echo "NO records were found !";
}

// echo $records->stud_id;

?>
