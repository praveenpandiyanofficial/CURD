<?php

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $uname = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $photo = '';

    // Initialize ID for update case
    $id = isset($_POST['id']) ? $_POST['id'] : '';

    // Check if a file has been uploaded
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
        $photo = $_FILES['photo']['name'];
        $targetDir = 'uploads/';
        $targetFile = $targetDir . basename($photo);

        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        if (move_uploaded_file($_FILES['photo']['tmp_name'], $targetFile)) {
            echo "File uploaded successfully!";
        } else {
            echo "File upload failed!";
        }
    }

    $con = new mysqli("localhost", "root", "", "test");

    if ($con->connect_error) {
        die("Connection failed: " . $con->connect_error);
    }

    // If updating an existing record
    if (!empty($id)) {
        if (!empty($photo)) {
            $sql = $con->prepare("UPDATE curd SET name=?, email=?, phone=?, image=? WHERE id=?");
            $sql->bind_param('ssssi', $uname, $email, $phone, $photo, $id);
        } else {
            // If no new photo is uploaded, update without changing the image
            $sql = $con->prepare("UPDATE curd SET name=?, email=?, phone=? WHERE id=?");
            $sql->bind_param('sssi', $uname, $email, $phone, $id);
        }
    } else {
        // Insert new record
        $sql = $con->prepare("INSERT INTO curd (name, email, phone, image) VALUES (?, ?, ?, ?)");
        $sql->bind_param('ssss', $uname, $email, $phone, $photo);
    }

    if ($sql->execute()) {
        echo "Record saved successfully!";
    } else {
        echo "Error: " . $sql->error;
    }

    $sql->close();
    $con->close();
}

// Handle Delete request
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];

    $con = new mysqli("localhost", "root", "", "test");
    if ($con->connect_error) {
        die("Connection failed: " . $con->connect_error);
    }

    // Get the image name to delete it from the folder
    $getImageQuery = "SELECT image FROM curd WHERE id=?";
    $stmt = $con->prepare($getImageQuery);
    $stmt->bind_param('i', $delete_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    if ($row['image'] != '') {
        unlink('uploads/' . $row['image']);  // Delete the image file
    }
    $stmt->close();

    // Delete the record
    $deleteQuery = $con->prepare("DELETE FROM curd WHERE id=?");
    $deleteQuery->bind_param('i', $delete_id);
    if ($deleteQuery->execute()) {
        echo "Record deleted successfully!";
    } else {
        echo "Error deleting record: " . $con->error;
    }

    $deleteQuery->close();
    $con->close();
}

// Fetch record for editing
if (isset($_GET['id']) && $_SERVER['REQUEST_METHOD'] == 'GET') {
    $id = $_GET['id'];
    $con = new mysqli('localhost', 'root', '', 'test');
    $sql = "SELECT * FROM curd WHERE id=$id";
    $result = $con->query($sql);

    if ($result->num_rows > 0) {
        $row1 = $result->fetch_assoc();
        $up_name = $row1['name'];
        $up_email = $row1['email'];
        $up_phone = $row1['phone'];
        $up_photo = $row1['image'];
    }

    $con->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PHP CRUD Operations</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <h2 class="text-center mt-5">PHP CRUD Operations</h2>

        <!-- Create/Update Form -->
        <div class="row mt-4">
            <div class="col-md-6 offset-md-3">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Add/Update Record</h4>
                    </div>
                    <div class="card-body">
                        <form id="crudForm" action="<?php echo $_SERVER['PHP_SELF'] ?>" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="id" id="id" value="<?php echo isset($id) ? $id : ''; ?>">
                            <div class="form-group mb-3">
                                <label for="name">Name</label>
                                <input type="text" class="form-control" id="name" name="name" placeholder="Enter name" value="<?php echo isset($up_name) ? $up_name : ''; ?>">
                            </div>
                            <div class="form-group mb-3">
                                <label for="email">Email</label>
                                <input type="email" class="form-control" id="email" name="email" placeholder="Enter email" value="<?php echo isset($up_email) ? $up_email : ''; ?>">
                            </div>
                            <div class="form-group mb-3">
                                <label for="phone">Phone</label>
                                <input type="text" class="form-control" id="phone" name="phone" placeholder="Enter phone" value="<?php echo isset($up_phone) ? $up_phone : ''; ?>">
                            </div>
                            <div class="form-group mb-3">
                                <label for="photo">Photo</label>
                                <input type="file" class="form-control" id="photo" name="photo">
                                <?php if (isset($up_photo) && !empty($up_photo)) : ?>
                                    <img style="width: 100px; height: 100px;" src="uploads/<?php echo $up_photo ?>" alt="Current Photo">
                                <?php endif; ?>
                            </div>
                            <button type="submit" class="btn btn-primary">Save</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Records Table -->
        <div class="row mt-5">
            <div class="col-md-8 offset-md-2">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Records</h4>
                    </div>
                    <div class="card-body">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Photo</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                              <?php 
                              $con = new mysqli('localhost', 'root', '', 'test');
                              $sql = 'SELECT * FROM curd';
                              $result = $con->query($sql);
                              while ($row = $result->fetch_assoc()) {
                              ?>
                                <tr>
                                    <td><?php echo $row['id'] ?></td>
                                    <td><?php echo $row['name'] ?></td>
                                    <td><?php echo $row['email'] ?></td>
                                    <td><?php echo $row['phone'] ?></td>
                                    <td><img style="width: 100px; height: 100px;" src="uploads/<?php echo $row['image'] ?>" alt="img"></td>
                                    <td>
                                        <a class="btn btn-success btn-sm" href="<?php echo $_SERVER['PHP_SELF'] ?>?id=<?php echo $row['id']; ?>">Edit</a>
                                        <a class="btn btn-danger btn-sm" href="<?php echo $_SERVER['PHP_SELF'] ?>?delete_id=<?php echo $row['id']; ?>" onclick="return confirm('Are you sure you want to delete this record?')">Delete</a>
                                    </td>
                                </tr>
                              <?php } $con->close(); ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
