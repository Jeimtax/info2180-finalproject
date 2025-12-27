<!DOCTYPE html>
<html>
<head>
  <title>Hashed Password</title>
</head>
<body>
  <h1>Generated Hash</h1>
  <p>
    <?php
      $password = 'password123';
      $hashed = password_hash($password, PASSWORD_DEFAULT);
      echo htmlspecialchars($hashed);
    ?>
  </p>
</body>
</html>
