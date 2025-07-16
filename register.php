<?php
  session_start();
  require_once 'db_connect.php';

  if (isset($_SESSION['user_id'])) {
      header('Location: index.php');
      exit;
  }

  if ($_SERVER['REQUEST_METHOD'] == 'POST') {
      $name = $_POST['name'];
      $username = $_POST['username'];
      $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

      $sql = "INSERT INTO users (name, username, password, role) VALUES (?, ?, ?, 'customer')";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param('sss', $name, $username, $password);
      if ($stmt->execute()) {
          header('Location: customer_login.php');
      } else {
          $error = "Registration failed.";
      }
  }
  ?>
  <!DOCTYPE html>
  <html lang="en">
  <head>
      <meta charset="UTF-8">
      <title>Register</title>
      <link rel="stylesheet" href="styles.css">
  </head>
  <body>
      <div class="container">
          <h2>Register</h2>
          <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>
          <form method="POST">
              <input type="text" name="name" placeholder="Name" required>
              <input type="text" name="username" placeholder="Username" required>
              <input type="password" name="password" placeholder="Password" required>
              <button type="submit">Register</button>
          </form>
          <p><a href="login.php">Back to Login</a></p>
      </div>
  </body>
  </html>