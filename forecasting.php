<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: index.html");
    exit();
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "agri_db";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get current date and date 6 days back (for last 7 days total)
$end_date = date("Y-m-d");
$start_date = date("Y-m-d", strtotime("-6 days"));

// Fetch crop names and price range (min-max) in last 7 days
$sql = "SELECT id, crop_name, MIN(price) as min_price, MAX(price) as max_price 
        FROM market_prices 
        WHERE date BETWEEN ? AND ?
        GROUP BY crop_name
        ORDER BY crop_name";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$result = $stmt->get_result();

$crop_data = [];
while ($row = $result->fetch_assoc()) {
    $crop_data[] = $row;
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Forecasting - Agri Price Prediction System</title>
  <style>
    body { font-family: Arial, sans-serif; background-color: #f0f0f0; padding: 40px; }
    table { width: 80%; margin: 20px auto; border-collapse: collapse; background: #fff; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
    th, td { padding: 12px; border: 1px solid #ddd; text-align: center; }
    th { background-color: #4CAF50; color: white; }
    h2, p { text-align: center; }
    .note { text-align: center; margin-top: 30px; font-size: 16px; color: #555; }
  </style>
</head>
<body>

<h2>Crop Price Forecasting (Last 7 Days)</h2>

<table>
  <tr>
    <th>ID</th>
    <th>Crop Name</th>
    <th>Price Range (₹ Min - Max)</th>
  </tr>
  <?php foreach ($crop_data as $index => $crop): ?>
  <tr>
    <td><?php echo $index + 1; ?></td>
    <td><?php echo htmlspecialchars($crop['crop_name']); ?></td>
    <td>₹<?php echo $crop['min_price']; ?> - ₹<?php echo $crop['max_price']; ?></td>
  </tr>
  <?php endforeach; ?>
</table>

<div class="note">
  <p>This price range summary is based on the last 7 days of market data and trends.</p>
  <a href="dashboard.php" style="color: green;">← Back to Dashboard</a>

</div>


</body>
</html>
