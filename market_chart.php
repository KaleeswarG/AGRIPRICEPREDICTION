<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: index.html");
    exit();
}

$crop = "";
$dates = [];
$prices = [];
$crop_found = true;

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['crop_name'])) {
    $crop = htmlspecialchars(trim($_POST['crop_name']));

    $conn = new mysqli("localhost", "root", "", "agri_db");

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Fetch market price history
    $sql = "SELECT date, price FROM market_prices WHERE crop_name = ? ORDER BY date ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $crop);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $dates[] = $row['date'];
            $prices[] = $row['price'];
        }
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
    <title>Market Prices Graph</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f0f0f0; text-align: center; }
        .box {
            margin: 50px auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            max-width: 600px;
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
    </style>
</head>
<body>

<div class="box">
    <h2>Enter Crop Name</h2>
    <form method="POST" action="">
        <input type="text" name="crop_name" placeholder="e.g., Rice" required>
        <br>
        <button type="submit">Check Prices</button>
    </form>

    <?php if (!$crop_found): ?>
        <p style="color: red;"><strong>No data found for "<?php echo htmlspecialchars($crop); ?>".</strong></p>
    <?php elseif (!empty($dates) && !empty($prices)): ?>
        <h3>Market Price Trend for <strong><?php echo htmlspecialchars($crop); ?></strong></h3>
        <canvas id="priceChart" width="400" height="200"></canvas>
        <script>
            const ctx = document.getElementById('priceChart').getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode($dates); ?>,
                    datasets: [{
                        label: 'Price (₹)',
                        data: <?php echo json_encode($prices); ?>,
                        borderColor: 'rgba(75, 192, 192, 1)',
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        borderWidth: 2,
                        tension: 0.3,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        x: {
                            title: { display: true, text: 'Date' },
                            ticks: { maxRotation: 90, minRotation: 45 }
                        },
                        y: {
                            title: { display: true, text: 'Price (₹)' },
                            beginAtZero: false
                        }
                    }
                }
            });
        </script>
    <?php endif; ?>

    <a href="dashboard.php">← Back to Dashboard</a>
</div>

</body>
</html>
