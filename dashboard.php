<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: index.html");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard</title>
  <style>
    body { font-family: Arial, sans-serif; background-color: #f8f8f8; text-align: center; }
    header { background-color: #4CAF50; color: white; padding: 15px; }
    nav { background-color: #333; padding: 1rem; text-align: center; }
    nav a { color: white; padding: 10px 20px; text-decoration: none; display: inline-block; }
    nav a:hover { background-color: #575757; }
    .container { max-width: 800px; margin: 20px auto; background: white; padding: 20px; border-radius: 8px; }
    footer { background-color: #333; color: white; text-align: center; padding: 10px; position: fixed; width: 100%; bottom: 0; }
  </style>
</head>
<body>

<header> 
  <h1>Agri Price Prediction System</h1>
</header>

<nav>
  <a href="#home">Home</a>
  <a href="predictt.php">Predict Price</a>
  <a href="market_chart.php">Market Prices</a>
  <a href="forecasting.php">Forecasting</a>
  <a href="logout.php">Logout</a> <!-- Link to logout logic -->
</nav>

<div class="container">
  <h2>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h2>
  <p>You are now logged in. Use the navigation menu to explore features.</p>

  <!-- Prediction Form -->
  
</div>


<footer>
  <p>&copy; 2025 Agri Price Prediction System. All rights reserved.</p>
</footer>

</body>
</html>
