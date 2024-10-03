<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Footer</title>
  <style>
    body {
      display: flex;
      flex-direction: column;
      min-height: 100vh;
      margin: 0;
      font-family: 'Roboto Mono', monospace;
    }

    .content {
      flex: 1;
      padding-bottom: 60px;
      /* Ensure there's space for the footer */
    }

    footer {
      position: fixed;
      left: 0;
      bottom: 0;
      width: 100%;
      background-color: #333;
      color: white;
      text-align: center;
      padding: 10px 0;
    }
  </style>
</head>

<body>
  <div class="content">
    <!-- Page content goes here -->
  </div>

  <footer>
    <p>&copy; <?php echo date("Y"); ?> PREP UNI. All rights reserved.</p>
  </footer>
</body>

</html>