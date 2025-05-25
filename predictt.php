<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: index.html");
    exit();
}

$crop = "";
$place = "";
$predicted_price = "";
$temperature = $humidity = $rainfall = null;
$crop_found = true;

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['crop_name'], $_POST['place_name'])) {
    $crop = htmlspecialchars(trim($_POST['crop_name']));
    $place = htmlspecialchars(trim($_POST['place_name']));
    
    // Database connection
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "agri_db";

    $conn = new mysqli($servername, $username, $password, $dbname);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Updated query to avoid LIMIT inside IN-subquery
    $sql = "SELECT mp.price 
            FROM market_prices mp
            JOIN (
                SELECT DISTINCT date 
                FROM market_prices
                WHERE LOWER(crop_name) = LOWER(?)
                ORDER BY date DESC
                LIMIT 7
            ) recent_dates ON mp.date = recent_dates.date
            WHERE LOWER(mp.crop_name) = LOWER(?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $crop, $crop);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $prices = [];
        while ($row = $result->fetch_assoc()) {
            $prices[] = $row['price'];
        }

        // Calculate the average price over the past 7 days
        $avg_price = round(array_sum($prices) / count($prices), 2);

        // Get today's weather data from placedetails table
        $weather_sql = "SELECT temperature, humidity, rainfall FROM placedetails 
                        WHERE place = ? AND date = CURDATE()";
        $weather_stmt = $conn->prepare($weather_sql);
        $weather_stmt->bind_param("s", $place);
        $weather_stmt->execute();
        $weather_result = $weather_stmt->get_result();

        if ($weather_result->num_rows > 0) {
            $weather = $weather_result->fetch_assoc();
            $temperature = $weather['temperature'];
            $humidity = $weather['humidity'];
            $rainfall = $weather['rainfall'];
        }

        // --- Adjustment Logic ---
        $adjustment = 0;

        // --- Temperature Effects ---
        if ($temperature !== null) {
            if ($temperature <= 15) {
                $adjustment -= 0.08 * $avg_price;
            } elseif ($temperature == 16 || $temperature == 17) {
                $adjustment -= 0.05 * $avg_price;
            } elseif ($temperature >= 18 && $temperature <= 20) {
                $adjustment -= 0.03 * $avg_price;
            } elseif ($temperature >= 21 && $temperature <= 23) {
                $adjustment -= 0.02 * $avg_price;
            } elseif ($temperature >= 24 && $temperature <= 26) {
                $adjustment -= 0.01 * $avg_price;
            } elseif ($temperature >= 27 && $temperature <= 29) {
                $adjustment += 0.03 * $avg_price;
            } elseif ($temperature == 30 || $temperature == 31) {
                $adjustment += 0.05 * $avg_price;
            } elseif ($temperature >= 32 && $temperature <= 34) {
                $adjustment += 0.07 * $avg_price;
            } elseif ($temperature == 35 || $temperature == 36) {
                $adjustment += 0.10 * $avg_price;
            }
        }

        // --- Humidity Effects ---
        if ($humidity !== null) {
            if ($humidity < 30) {
                $adjustment += 0.03 * $avg_price;
            } elseif ($humidity > 85) {
                $adjustment += 0.05 * $avg_price;
            }
        }

        // --- Rainfall Effects ---
        if ($rainfall !== null) {
            if ($rainfall >= 0 && $rainfall < 2) {
                $adjustment -= 0.05 * $avg_price;
            } elseif ($rainfall >= 2 && $rainfall < 3) {
                $adjustment -= 0.03 * $avg_price;
            } elseif ($rainfall >= 3 && $rainfall < 4) {
                $adjustment -= 0.02 * $avg_price;
            } elseif ($rainfall >= 4 && $rainfall < 5) {
                $adjustment -= 0.01 * $avg_price;
            } elseif ($rainfall >= 5 && $rainfall < 10) {
                $adjustment += 0.02 * $avg_price;
            } elseif ($rainfall >= 10 && $rainfall < 20) {
                $adjustment += 0.05 * $avg_price;
            } elseif ($rainfall >= 20 && $rainfall < 40) {
                $adjustment += 0.07 * $avg_price;
            } elseif ($rainfall >= 40 && $rainfall < 60) {
                $adjustment += 0.10 * $avg_price;
            } elseif ($rainfall >= 60) {
                $adjustment += 0.12 * $avg_price;
            }
        }

        // --- Seasonal Crop Supply-Demand Effect ---
        $month = date('n');
        if (in_array($month, [3, 4, 5])) {
            $adjustment -= 0.04 * $avg_price;
        } elseif (in_array($month, [9, 10])) {
            $adjustment += 0.06 * $avg_price;
        }

        // Final predicted price (rounded and non-negative)
        $predicted_price = max(0, round($avg_price + $adjustment, 2));

        $weather_stmt->close();
    } else {
        $crop_found = false;
    }

    $stmt->close();
    $conn->close();
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Price Prediction</title>
  <style>
    body { font-family: Arial, sans-serif; background-color: #f0f0f0; text-align: center; }
    .box {
      margin: 100px auto;
      background: white;
      padding: 30px;
      border-radius: 10px;
      max-width: 500px;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }
    input, button {
      padding: 10px;
      width: 80%;
      margin: 10px 0;
      font-size: 16px;
    }
    button {
      background-color: #4CAF50;
      color: white;
      border: none;
      cursor: pointer;
    }
    button:hover {
      background-color: #45a049;
    }
    a {
      text-decoration: none;
      color: green;
      display: inline-block;
      margin-top: 20px;
    }
    p { margin: 10px 0; }
  </style>
</head>
<body>

<div class="box">
  <h2>Crop Price Prediction</h2>
  <form method="POST" action="">
    <input type="text" name="crop_name" placeholder="Enter crop name (e.g., Rice)" required><br>
    <input type="text" name="place_name" placeholder="Enter place name (e.g., Madurai)" required><br>
    <button type="submit">Predict Price</button>
  </form>

  <?php if (!$crop_found): ?>
    <p style="color: red;"><strong>No data found for "<?php echo htmlspecialchars($crop); ?>".</strong></p>
  <?php elseif ($predicted_price !== ""): ?>
    <h3>Prediction Result</h3>
    <p>Crop: <strong><?php echo htmlspecialchars($crop); ?></strong></p>
    <p>Place: <strong><?php echo htmlspecialchars($place); ?></strong></p>
    <p>Predicted Price: <strong>₹<?php echo $predicted_price; ?></strong></p>
    <?php if ($temperature !== null): ?>
      <p>Temperature: <strong><?php echo $temperature; ?>°C</strong></p>
      <p>Humidity: <strong><?php echo $humidity; ?>%</strong></p>
      <p>Rainfall: <strong><?php echo $rainfall; ?> mm</strong></p>
    <?php else: ?>
      <p style="color: orange;">No weather data found for "<?php echo htmlspecialchars($place); ?>" today.</p>
    <?php endif; ?>
  <?php endif; ?>

  <a href="dashboard.php">← Back to Dashboard</a>
</div>

</body>
</html>
