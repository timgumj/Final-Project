<?php
// No need to call session_start() here because it should be called in the including pages

// Determine the user's role
$role = isset($_SESSION['role']) ? $_SESSION['role'] : null;

// Get the current page for the active link highlight
$current_page = basename($_SERVER['PHP_SELF']);

// Get the user's last name
$lastname = isset($_SESSION['lastname']) ? $_SESSION['lastname'] : '';
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Navigation</title>
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Roboto+Mono:wght@200;400;700&display=swap" rel="stylesheet">
  <style>
    /* Additional styling for the navbar */
    .navbar-brand {
      font-weight: bold;
      font-family: 'Roboto Mono', monospace;
      display: flex;
      align-items: center;
    }

    .navbar-brand::before {
      content: '';
      display: inline-block;
      width: 12px;
      height: 12px;
      margin-right: 8px;
      border-radius: 50%;
      background-color: red;
    }

    .navbar-nav .nav-link {
      margin-right: 15px;
      font-size: 18px;
      font-weight: 300;
      /* Thin font weight */
      color: white !important;
      position: relative;
      font-family: 'Roboto Mono', monospace;
    }

    .navbar-nav .nav-link::after {
      content: '';
      position: absolute;
      left: 0;
      bottom: -3px;
      width: 0;
      height: 2px;
      background-color: red;
      transition: width 0.3s;
    }

    .navbar-nav .nav-link:hover::after {
      width: 100%;
    }

    .navbar-nav .nav-link:hover {
      color: white !important;
    }

    .navbar-nav .active::after {
      width: 100%;
    }

    .navbar-nav .nav-item .nav-link.logout {
      color: red !important;
    }

    .navbar-nav .nav-item .nav-link.logout::after {
      background-color: red !important;
    }

    .navbar-nav .nav-item .nav-link.lastname {
      color: red !important;
      font-weight: bold;
    }

    body {
      font-family: 'Roboto Mono', monospace;
    }

    /* Make the navbar sticky */
    .navbar {
      position: sticky;
      top: 0;
      z-index: 1000;
    }

    /* Ensure no margin or padding pushes the navbar down */
    body,
    html {
      margin: 0;
      padding: 0;
    }
  </style>
</head>

<body>

  <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <a class="navbar-brand" href="#">PREP UNI</a>
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav"
      aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ml-auto">
        <?php if ($role === 'admin'): ?>
          <li class="nav-item">
            <a class="nav-link <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>" href="../admin/dashboard.php">Dashboard</a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?php echo $current_page == 'manage_users.php' ? 'active' : ''; ?>" href="../admin/manage_users.php">Manage Users</a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?php echo $current_page == 'manage_services.php' ? 'active' : ''; ?>" href="../admin/manage_services.php">Services</a>
          </li>

          <li class="nav-item">
            <a class="nav-link <?php echo $current_page == 'manage_bookings.php' ? 'active' : ''; ?>" href="../admin/manage_bookings.php">Bookings</a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?php echo $current_page == 'manage_reviews.php' ? 'active' : ''; ?>" href="../admin/manage_reviews.php">Reviews</a>
          </li>

        <?php elseif ($role === 'trainer'): ?>
          <li class="nav-item">
            <a class="nav-link <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>" href="../trainers/dashboard.php">Dashboard</a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?php echo $current_page == 'edit_profile.php' ? 'active' : ''; ?>" href="../trainers/edit_profile.php">Edit Profile</a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?php echo $current_page == 'manage_services.php' ? 'active' : ''; ?>" href="../trainers/manage_services.php">Services</a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?php echo $current_page == 'view_bookings.php' ? 'active' : ''; ?>" href="../trainers/view_bookings.php">Bookings</a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?php echo $current_page == 'reviews.php' ? 'active' : ''; ?>" href="../trainers/reviews.php">Reviews</a>
          </li>

        <?php elseif ($role === 'student'): ?>
          <li class="nav-item">
            <a class="nav-link <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>" href="../students/dashboard.php">Dashboard</a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?php echo $current_page == 'profile.php' ? 'active' : ''; ?>" href="../students/profile.php">Profile</a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?php echo $current_page == 'book_service.php' ? 'active' : ''; ?>" href="../students/book_service.php">Book Service</a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?php echo $current_page == 'courses.php' ? 'active' : ''; ?>" href="../students/courses.php">Available Courses</a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?php echo $current_page == 'my_reviews.php' ? 'active' : ''; ?>" href="../students/my_reviews.php">Reviews</a>
          </li>
        <?php else: ?>
          <li class="nav-item">
            <a class="nav-link <?php echo $current_page == 'login.php' ? 'active' : ''; ?>" href="../pages/login.php">Login</a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?php echo $current_page == 'register.php' ? 'active' : ''; ?>" href="../pages/register.php">Register</a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?php echo $current_page == 'index.php' ? 'active' : ''; ?>" href="../pages/index.php">Home</a>
          </li>
        <?php endif; ?>
        <?php if ($role): ?>
          <li class="nav-item">
            <a class="nav-link logout" href="../pages/logout.php">Logout</a>
          </li>
          <?php if (!empty($lastname)): ?>
            <li class="nav-item">
              <span class="nav-link lastname"><?php echo htmlspecialchars($lastname); ?></span>
            </li>
          <?php endif; ?>
        <?php endif; ?>
      </ul>
    </div>
  </nav>

  <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.6.0/dist/umd/popper.min.js"></script>
  <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>

</html>