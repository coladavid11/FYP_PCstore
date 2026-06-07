<?php
session_start();
include('includes/config.php');

if (!isset($_SESSION['admin_login'])) {
    header("location:admin_login.php");
    exit();
}

$email = $_SESSION['admin_login'];
$msg = "";

// Update Logic
if (isset($_POST['update_profile'])) {
    $fullname = $_POST['fullname'];
    $new_email = $_POST['email'];

    $sql = "UPDATE admin SET fullname=:fullname, email=:email WHERE email=:current_email";
    $query = $dbh->prepare($sql);
    $query->bindParam(':fullname', $fullname, PDO::PARAM_STR);
    $query->bindParam(':email', $new_email, PDO::PARAM_STR);
    $query->bindParam(':current_email', $email, PDO::PARAM_STR);

    if ($query->execute()) {
        $_SESSION['admin_login'] = $new_email; // Update session
        $msg = "Profile updated successfully!";
        $email = $new_email; // Update local variable for the fetch below
    }
}

// Fetch Fresh Data
$sql = "SELECT * FROM admin WHERE email = :email";
$query = $dbh->prepare($sql);
$query->bindParam(':email', $email, PDO::PARAM_STR);
$query->execute();
$result = $query->fetch(PDO::FETCH_OBJ);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Edit Profile | My PC Store</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
            /* Dashboard grey */
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .profile-container {
            background-color: #ffffff;
            width: 100%;
            max-width: 500px;
            padding: 40px;
            border-radius: 4px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            border-left: 6px solid #ccac3d;
            /* Gold bar matching dashboard cards */
        }

        .profile-title {
            font-size: 1.8rem;
            margin-bottom: 30px;
            color: #333;
        }

        .profile-info p {
            padding: 15px 0;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .profile-info strong {
            color: #ccac3d;
            font-size: 0.85rem;
            text-transform: uppercase;
        }

        .profile-info span {
            color: #555;
            font-weight: 500;
        }

        .back-link {
            background-color: #ccac3d;
            color: #fff;
            text-decoration: none;
            padding: 12px;
            border-radius: 4px;
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
            transition: 0.3s;
            display: inline-block;
        }

        .back-link:hover {
            background-color: #b3922d;
            transform: translateY(-2px);
        }

        input:focus {
            outline: none;
            border-color: #ccac3d !important;
        }
    </style>
</head>

<body>
    <div class="profile-container">
        <h1 class="profile-title">Edit Profile</h1>

        <?php if ($msg): ?>
            <div
                style="background: #d4edda; color: #155724; padding: 10px; margin-bottom: 20px; border-radius: 4px; font-size: 0.9rem;">
                <?php echo $msg; ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="profile-info">
                <div style="margin-bottom: 20px;">
                    <label style="color: #ccac3d; font-size: 0.8rem; font-weight: 600; text-transform: uppercase;">Full
                        Name</label>
                    <input type="text" name="fullname" value="<?php echo htmlspecialchars($result->fullname); ?>"
                        required
                        style="width: 100%; padding: 12px; border: 1px solid #eee; background: #fdfdfd; border-radius: 4px; margin-top: 5px;">
                </div>

                <div style="margin-bottom: 20px;">
                    <label style="color: #ccac3d; font-size: 0.8rem; font-weight: 600; text-transform: uppercase;">Email
                        Address</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($result->email); ?>" required
                        style="width: 100%; padding: 12px; border: 1px solid #eee; background: #fdfdfd; border-radius: 4px; margin-top: 5px;">
                </div>
            </div>

            <button type="submit" name="update_profile" class="back-link"
                style="border: none; cursor: pointer; width: 100%; margin-bottom: 10px;">
                Save Changes
            </button>
            <a href="admin_profile.php" class="back-link"
                style="display: block; text-align: center; background: #eee; color: #333;">Cancel</a>
        </form>
    </div>
</body>

</html>