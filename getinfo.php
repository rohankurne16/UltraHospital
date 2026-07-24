<?php
    



    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $category_id =  $_POST['category_id'];

        $sql = "SELECT category_title, category_description, flag FROM category_master WHERE id = '$category_id'";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
           echo json_encode([
            "status" => "success",
            "category_title" => $row['category_title'],
            "category_description" => $row['category_description'],
            "flag" => $row['flag']
        ]);
        } else {
            echo json_encode([
                "status" => "error",
                "message" => "Category not found"
            ]);
        }
    }

?>