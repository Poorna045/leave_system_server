<html>
<head>
        <title>My Form</title>

        <style type="text/css" media="screen">
                
              label { display:block; }  

        </style>
</head>
<body>
        <h1>Welcome to Student Data Base!</h1>

        <?php echo form_open('HomeController/InsertStudData'); ?>
        
        <!--<p>
          
            <label for="stud_id"> Student Id :  </label>
            <input type="text" name="stud_id" id="stud_id" >
        
        </p>-->

        <p>
          
            <label for="stud_name"> Student Name :  </label>
            <input type="text" name="stud_name" #name  id="stud_name" >
        
        </p>
        
        <p>
          
            <label for="percentage"> Percentage :  </label>
            <input type="text" name="percentage" id="percentage" >
        
        </p>
        
        <p>
          
            <label for="dept"> Department :  </label>
            <input type="text" name="dept" id="dept" >
        
        </p>
        
        <p>
          
            <input type="submit" value="Add Student" >
            
      
        </p>


        <?php echo form_close(); ?>
<?php  echo anchor("HomeController/dos",'<button>Update Student</button>') ?>
</body>
</html>