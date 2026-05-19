<?php
session_start();
include('includes/config.php');

$isLoggedIn = isset($_SESSION['login']);
$user_id = $_SESSION['user_id'] ?? null;

if (!$isLoggedIn || !$user_id) {
    header("Location: login.php");
    exit;
}

include('includes/header.php');

/* =========================
   FETCH USER DATA
========================= */
$stmt = $dbh->prepare("
    SELECT * FROM tbluser
    WHERE user_id = ?
");
$stmt->execute([$user_id]);

$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("User not found.");
}
//couting member days
    $regDate = $user['reg_date'];

    $today = new DateTime();
    $reg   = new DateTime($regDate);

    $interval = $today->diff($reg);

    $memberDays = $interval->days;

/* =========================
   VARIABLES
========================= */
$successMsg = '';
$errorMsg   = '';

$fullname = $user['fullname'];
$gmail    = $user['gmail'];
$gender   = $user['gender'];
$phone    = $user['phone_num'];
$address  = $user['address'];

/* =========================
   UPDATE PROFILE
========================= */
if (isset($_POST['update_profile'])) {

    $fullname = trim($_POST['fullname'] ?? '');
    $gender   = trim($_POST['gender'] ?? '');
    $phone    = trim($_POST['phone_num'] ?? '');
    $address  = trim($_POST['address'] ?? '');

    $current_password = trim($_POST['current_password'] ?? '');
    $new_password     = trim($_POST['new_password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');

    /* ========= VALIDATION ========= */

    if (
        empty($fullname) ||
        empty($gender) ||
        empty($phone) ||
        empty($address)
    ) {

        $errorMsg = "Please fill in all required fields.";

    }

    // Malaysia phone validation
    elseif (!preg_match('/^01[0-9]-?[0-9]{7,8}$/', $phone)) {

        $errorMsg = "Invalid phone number format.";

    }

    // PASSWORD CHANGE
    elseif (
        !empty($current_password) ||
        !empty($new_password) ||
        !empty($confirm_password)
    ) {

        // Verify current password
        if (!password_verify($current_password, $user['password'])) {

            $errorMsg = "Current password is incorrect.";

        }
        
        // Password length
        elseif (strlen($new_password) < 8) {

            $errorMsg = "New password must be at least 8 characters.";

        }

        // No spaces
        elseif (preg_match('/\s/', $new_password)) {

            $errorMsg = "Password cannot contain spaces.";

        }
         // NEW RULE: new password cannot be same as current password
        elseif ($current_password === $new_password) {

            $errorMsg = "New password cannot be the same as current password.";

        }
        // Confirm password
        elseif ($new_password !== $confirm_password) {

            $errorMsg = "Confirm password does not match.";

        }

    }

    /* ========= UPDATE DATABASE ========= */

    if (empty($errorMsg)) {

        try {

            // Update profile only
            if (empty($new_password)) {

                $updateSql = "
                    UPDATE tbluser
                    SET
                        fullname = :fullname,
                        gender = :gender,
                        phone_num = :phone,
                        address = :address
                    WHERE user_id = :user_id
                ";

                $query = $dbh->prepare($updateSql);

                $query->bindParam(':fullname', $fullname, PDO::PARAM_STR);
                $query->bindParam(':gender', $gender, PDO::PARAM_STR);
                $query->bindParam(':phone', $phone, PDO::PARAM_STR);
                $query->bindParam(':address', $address, PDO::PARAM_STR);
                $query->bindParam(':user_id', $user_id, PDO::PARAM_INT);

                $query->execute();

            }

            // Update with password
            else {

                $hashedPassword = password_hash($new_password, PASSWORD_BCRYPT);

                $updateSql = "
                    UPDATE tbluser
                    SET
                        fullname = :fullname,
                        gender = :gender,
                        phone_num = :phone,
                        address = :address,
                        password = :password
                    WHERE user_id = :user_id
                ";

                $query = $dbh->prepare($updateSql);

                $query->bindParam(':fullname', $fullname, PDO::PARAM_STR);
                $query->bindParam(':gender', $gender, PDO::PARAM_STR);
                $query->bindParam(':phone', $phone, PDO::PARAM_STR);
                $query->bindParam(':address', $address, PDO::PARAM_STR);
                $query->bindParam(':password', $hashedPassword, PDO::PARAM_STR);
                $query->bindParam(':user_id', $user_id, PDO::PARAM_INT);

                $query->execute();
            }

            $successMsg = "Profile updated successfully.";

        } catch (PDOException $e) {

            $errorMsg = "Something went wrong.";

        }
    }
}   
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Profile - My PC Store</title>

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<!-- Bootstrap -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<!-- Fonts -->
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Poppins:wght@300;400;500&display=swap" rel="stylesheet">

<!-- Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<!-- External CSS -->
<link rel="stylesheet" href="newstyle.css">

</head>

<body>

<div class="container py-5">

    <div class="row g-4">

        <!-- LEFT PROFILE CARD -->
        <div class="col-lg-4">

            <div class="dark-card p-4 text-center">

                <div class="mb-4">
                    <i class="fa-solid fa-circle-user"
                       style="font-size:120px; color:#d4af37;"></i>
                </div>

                <h3 class="mb-2">
                    <?php echo htmlentities(shortName($username)); ?>
                </h3>

                <p class="text-soft mb-1">
                    <?php echo htmlentities($gmail); ?>
                </p>

                <hr style="border-color:#2a2a2a;">

                <div class="text-start">

                    <p>
                        <i class="fa fa-user text-warning me-2"></i>
                        Customer Account
                    </p>

                    <p>
                        <i class="fa fa-shield-halved text-warning me-2"></i>
                        Secure Profile
                    </p>

                    <p>
                        <i class="fa fa-envelope text-warning me-2"></i>
                        Verified Email
                    </p>

                    <p>
                        <i class="fa fa-calendar text-warning me-2"></i>
                        Member for <?php echo $memberDays; ?> days
                    </p>

                </div>

            </div>

        </div>

        <!-- RIGHT FORM -->
        <div class="col-lg-8">

            <div class="dark-card p-4">

                <h2 class="section-title mb-4" style="color:white;">
                    My Profile
                </h2>

                <!-- SUCCESS -->
                <?php if(!empty($successMsg)): ?>

                    <div class="alert alert-success">
                        <?php echo $successMsg; ?>
                    </div>

                <?php endif; ?>

                <!-- ERROR -->
                <?php if(!empty($errorMsg)): ?>

                    <div class="alert alert-danger">
                        <?php echo $errorMsg; ?>
                    </div>

                <?php endif; ?>

                <form method="POST">

                    <div class="row">

                        <!-- FULLNAME -->
                        <div class="col-md-6 mb-3">

                            <label class="form-label">
                                User Name
                            </label>

                            <input type="text"
                                   name="fullname"
                                   class="form-control"
                                   value="<?php echo htmlentities($fullname); ?>"
                                   required>

                        </div>

                        <!-- EMAIL -->
                        <div class="col-md-6 mb-3">

                            <label class="form-label">
                                Email Address
                            </label>

                            <input type="email"
                                   class="form-control"
                                   value="<?php echo htmlentities($gmail); ?>"
                                   disabled>

                        </div>

                    </div>

                    <div class="row">

                        <!-- PHONE -->
                        <div class="col-md-6 mb-3">

                            <label class="form-label">
                                Phone Number
                            </label>

                            <input type="text"
                                   name="phone_num"
                                   id="phone_num"
                                   class="form-control"
                                   maxlength="12"
                                   value="<?php echo htmlentities($phone); ?>"
                                   required>

                        </div>

                        <!-- GENDER -->
                        <div class="col-md-6 mb-3">

                            <label class="form-label">
                                Gender
                            </label>

                            <select name="gender"
                                    class="form-select"
                                    required>

                                <option value="">Select Gender</option>

                                <option value="Male"
                                    <?php if($gender == 'Male') echo 'selected'; ?>>
                                    Male
                                </option>

                                <option value="Female"
                                    <?php if($gender == 'Female') echo 'selected'; ?>>
                                    Female
                                </option>

                                <option value="Other"
                                    <?php if($gender == 'Other') echo 'selected'; ?>>
                                    Other
                                </option>

                            </select>

                        </div>

                    </div>

                    <!-- ADDRESS -->
                    <div class="mb-4">

                        <label class="form-label">
                            Address
                        </label>

                        <textarea name="address"
                                  class="form-control"
                                  rows="4"
                                  required><?php echo htmlentities($address); ?></textarea>

                    </div>

                    <hr style="border-color:#2a2a2a;">

                    <h4 class="mb-4" style="color:#d4af37;">
                        Change Password
                    </h4>

                    <div class="row">

                        <!-- CURRENT PASSWORD -->
                        <div class="col-md-4 mb-3">

                            <label class="form-label">
                                Current Password
                            </label>

                            <input type="password"
                                   name="current_password"
                                   class="form-control">

                        </div>

                        <!-- NEW PASSWORD -->
                        <div class="col-md-4 mb-3">

                            <label class="form-label">
                                New Password
                            </label>

                            <input type="password"
                            name="new_password"
                            class="form-control"
                            pattern="^(?!.*\s).{8,}$"
                            title="Password must be at least 8 characters and cannot contain spaces">

                        </div>

                        <!-- CONFIRM PASSWORD -->
                        <div class="col-md-4 mb-3">

                            <label class="form-label">
                                Confirm Password
                            </label>

                            <input type="password"
                                   name="confirm_password"
                                   class="form-control">

                        </div>

                    </div>

                    <button type="submit"
                            name="update_profile"
                            class="btn-cta mt-3">

                        Save Changes

                    </button>

                </form>

            </div>

        </div>

    </div>

</div>

<!-- PHONE AUTO FORMAT -->
<script>

document.getElementById('phone_num').addEventListener('input', function(e) {

    let value = e.target.value.replace(/\D/g, '');

    if (value.length > 3) {
        value = value.slice(0,3) + '-' + value.slice(3);
    }

    e.target.value = value;

});

</script>

<?php include('includes/footer.php'); ?>

</body>
</html>