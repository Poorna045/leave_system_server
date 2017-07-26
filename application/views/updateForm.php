<html>
<head>
        <title>My Form</title>

        <style type="text/css" media="screen">
                
              label { display:block; }  

        </style>
</head>
<body>
        <h1>Welcome to Student Data Base!</h1>

        <h3>Update Student Data Here!</h3>

        <?php echo form_open('HomeController/UpdateStudData'); ?>
        
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
          
            <input type="submit" value="Update Student" >
           
      
        </p>


        <?php echo form_close(); ?>

</body>
</html>