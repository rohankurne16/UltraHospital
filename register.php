<!DOCTYPE html>
<html>
<head>
    <title>Practice Ajax Code</title>


</head>

<body>

<div class="container">

    <h1>Registration</h1>

    <form method="post">

        <label>Name :</label>
        <input type="text" name="name" id="name" placeholder="Enter Name" required> <br><br>

        <label>Email :</label>
        <input type="email" name="email" id="email" placeholder="Enter Email" required><br><br>

        <label>Password :</label>
        <input type="password" name="password" id="password" placeholder="Enter Password" required><br><br>

        <button type="submit">Submit</button>

    </form>

</div>

<script>
    function getData(name) {
       
    $.ajax({
        url: 'getinfo.php',
        type: 'POST',
        data: { name: Name },
        dataType: 'json',
        success: function (data) {
         
            if (data) {
            $('input[id="name"]').val(Name);
                // Populate the form fields with the retrieved data
                 $('input[id="name"]').val(data.name);
                $('input[id="email"]').val(data.email);
                $('input[id="password"]').val(data.password);
             
                // You can add more fields as necessary
            } else {
              
                showAlert('error','Failed to retrieve category details.');
            }
        },
        error: function () {
                 
            showAlert('error','An error occurred while fetching category details.');
        }
    });
}

</script>

</body>
</html>	