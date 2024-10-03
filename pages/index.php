<?php
session_start(); // Start the session

// Check if the user is already logged in, redirect them to their dashboard
if (isset($_SESSION['user_id'])) {
  if ($_SESSION['role'] === 'trainer') {
    header("Location: ../trainers/dashboard.php");
  } elseif ($_SESSION['role'] === 'student') {
    header("Location: ../students/dashboard.php");
  } else {
    header("Location: ../admin/dashboard.php");
  }
  exit();
}

// Include the database connection if needed (optional)
// require_once '../config/dbconnection.php';
?>
<?php
include('../components/navbar.php');
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Welcome to Tutoring Services</title>
  <link rel="stylesheet" href="../assets/css/style.css"> <!-- Adjust the path if necessary -->
  <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.9.0/main.min.css' rel='stylesheet' />
  <!-- Your Custom CSS -->
  <style>
    body {
      font-family: Arial, sans-serif;
      margin: 0;
      padding: 0;
      background-color: #f4f4f4;
      color: #333;
    }

    header {
      background: url('https://images.unsplash.com/photo-1517486808906-6ca8b3f04846?q=80&w=2349&auto=format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D') no-repeat center center/cover;
      color: black;
      /* Changed header text color to black */
      text-align: center;
      padding: 190px 40px;
      /* Increased padding to show more of the image */
    }

    header h1 {
      font-size: 3em;
      margin: 0;
      text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
      /* Reduced shadow for clearer black text */
    }

    nav ul {
      list-style: none;
      padding: 0;
    }

    nav ul li {
      display: inline;
      margin-right: 20px;
    }

    nav ul li a {
      color: black;
      /* Set link text color to black */
      text-decoration: none;
      font-weight: bold;
      border-bottom: 2px solid red;
      /* Red underline */
    }

    nav ul li a:hover {
      color: red;
      /* Change text and underline color to red on hover */
      border-bottom: 2px solid red;
    }

    main {
      padding: 20px;
      max-width: 1200px;
      margin: 0 auto;
      background: #fff;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    section {
      margin-bottom: 40px;
    }

    section h2 {
      font-size: 2em;
      margin-bottom: 10px;
      color: black;
      /* Set section headers to black */
    }

    section p {
      font-size: 1.2em;
      line-height: 1.6;
    }

    section a {
      color: black;
      /* Link text color in sections */
      text-decoration: none;
      border-bottom: 2px solid red;
      /* Red underline */
    }

    section a:hover {
      color: red;
      /* Change text and underline color to red on hover */
      border-bottom: 2px solid red;
    }

    #calendar {
      max-width: 900px;
      margin: 40px auto;
    }

    footer {
      text-align: center;
      padding: 20px;
      background-color: #333;
      color: white;
    }

    footer p {
      margin: 0;
    }

    @media (max-width: 768px) {
      header h1 {
        font-size: 2em;
      }

      main {
        padding: 10px;
      }

      section h2 {
        font-size: 1.5em;
      }

      section p {
        font-size: 1em;
      }
    }
  </style>
</head>

<body>
  <header>
    <h1>Welcome to Tutoring Services</h1>
    <nav>
      <ul>
        <li><a href="login.php">Login</a></li>
        <li><a href="register.php">Register</a></li>
      </ul>
    </nav>
  </header>

  <main>
    <section>
      <h2>About Us</h2>
      <p>Welcome to our tutoring platform, where students can connect with experienced trainers across a variety of subjects. Our goal is to provide quality education and personalized learning experiences for every student.</p>
    </section>

    <section>
      <h2>Our Services</h2>
      <p>We offer a wide range of tutoring services in subjects such as Mathematics, Science, Computer Science, and more. Our platform allows students to book sessions with their preferred trainers at times that suit their schedules.</p>
      <p><a href="register.php">Register or login to our platform</a></p>
    </section>

    <section>
      <h2>Why Choose Us?</h2>
      <ul>
        <li>Expert trainers with years of experience</li>
        <li>Flexible scheduling and personalized sessions</li>
        <li>Easy booking and payment process</li>
      </ul>
    </section>
  </main>

  <!-- Calendar Container -->
  <div id="calendar"></div>

  <!-- FullCalendar JS -->
  <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.9.0/main.min.js'></script>
  <!-- Your Custom JS -->
  <script src="../assets/js/calendar.js"></script> <!-- Adjust the path if necessary -->

  <?php
  include '../components/footer.php';  // Adjust the path if necessary
  ?>
</body>

</html>