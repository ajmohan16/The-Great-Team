<?php
// verify_otp.php
session_start();

// Check if OTP is set in the session
if (!isset($_SESSION['otp'])) {
    header('Location: login.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $otp = filter_input(INPUT_POST, 'otp', FILTER_SANITIZE_NUMBER_INT);

    // Check for expired OTP
    if (time() > $_SESSION['otp_expiry']) {
        echo '<div class="alert alert-danger" role="alert">OTP has expired. Please request a new one.</div>';
        exit();
    }

    if ($otp == $_SESSION['otp']) {
        // Regenerate session ID to prevent fixation attacks
        session_regenerate_id(true);

        // Mark user as logged in
        $_SESSION['loggedin'] = true;

        // Clear OTP session data
        unset($_SESSION['otp'], $_SESSION['otp_expiry']);

        // Redirect to the home page
        header('Location: landing_page.php');
        exit();
    } else {
        $error_message = "Invalid OTP. Please try again.";
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
<body class="container mt-5">
    <h2>OTP Verification</h2>
    <form action="verify_otp.php" method="post">
        <div class="mb-3">
            <label for="otp" class="form-label">Enter OTP:</label>
            <input type="text" id="otp" name="otp" class="form-control" required>
        </div>
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger" role="alert">
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>
        <button type="submit" class="btn btn-primary">Verify OTP</button>
    </form>
</body>
</html>
