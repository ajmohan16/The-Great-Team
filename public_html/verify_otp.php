<?php
session_start();

// Check if the user is logged in and has an OTP
if (!isset($_SESSION['otp'])) {
    // Redirect to login page if OTP is not set
    header('Location: login.php');
    exit();
}

// Handle OTP verification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $enteredOtp = $_POST['otp'];

    // Check if entered OTP matches the session OTP
    if ($enteredOtp == $_SESSION['otp']) {
        // OTP is correct, proceed with further actions (e.g., logging in the user)
        echo "OTP verified successfully! You are logged in.";
        // Optionally, clear OTP session after successful verification
        unset($_SESSION['otp']);
        unset($_SESSION['username']);
        unset($_SESSION['phone']);
    } else {
        // OTP is incorrect
        echo "Invalid OTP. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2>OTP Verification</h2>
        <form action="verify_otp.php" method="post">
            <div class="mb-3">
                <label for="otp" class="form-label">Enter OTP:</label>
                <input type="text" id="otp" name="otp" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary">Verify OTP</button>
        </form>
    </div>
</body>
</html>
