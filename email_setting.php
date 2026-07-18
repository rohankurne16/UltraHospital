<?php
include("config/hospital.php");

$sql = "SELECT * FROM email_templates ORDER BY id DESC";
$result = mysqli_query($conn, $sql);
?>

<table class="table table-bordered table-hover">
    <thead>
        <tr>
            <th>#</th>
            <th>Template Name</th>
            <th>Subject</th>
            <th>Body</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        <?php
        if(mysqli_num_rows($result) > 0){
            $i = 1;
            while($row = mysqli_fetch_assoc($result)){
        ?>
        <tr>
            <td><?= $i++; ?></td>
            <td><?= htmlspecialchars($row['template_name']); ?></td>
            <td><?= htmlspecialchars($row['subject']); ?></td>
             <td><?= htmlspecialchars($row['body']); ?></td>
            <td>
                <a href="edit_email_template.php?id=<?= $row['id']; ?>" class="btn btn-sm btn-primary">
                    Edit
                </a>

                <a href="view_email_template.php?id=<?= $row['id']; ?>" class="btn btn-sm btn-info">
                    View
                </a>
            </td>
        </tr>
        <?php
            }
        } else {
        ?>
        <tr>
            <td colspan="4" class="text-center">No Email Templates Found.</td>
        </tr>
        <?php } ?>
    </tbody>
</table>