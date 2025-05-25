
<?php
$servername = "localhost";
$username = "root";
$password = ""; // Set this if your MySQL has a password
$dbname = "agri_db"; // Replace with your actual DB name

$apiKey = "9eabad2c655bb46dbcc22e3c889ac437"; // Your real OpenWeatherMap API key

$cities = [
     "Ariyalur", "Chengalpattu", "Chennai", "Coimbatore", "Cuddalore", "Dharmapuri",
     "Dindigul", "Erode", "kanchipuram", "Karur", "Krishnagiri", 
     "Kanyakumari", "madurai", "Mayiladuthurai", "Nagapattinam", "Namakkal", "sivakasi",
      "Perambalur", "Pudukkottai", "Ramanathapuram", "Salem", "Sivaganga", "Tenkasi",
       "Thanjavur", "Theni", "Thoothukudi", "Tiruchirappalli", "Tirunelveli", "Tirupathur", "Tiruvallur", 
       "Thiruvarur", "Tiruvannamalai", "Tiruppur", "Vellore", "Villupuram", "Virudhunagar"
];

// Connect to MySQL
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("DB Connection failed: " . $conn->connect_error);
}

$date = date("Y-m-d");

foreach ($cities as $city) {
    $url = "https://api.openweathermap.org/data/2.5/weather?q=" . urlencode($city) . "&appid=" . $apiKey . "&units=metric";
    $response = @file_get_contents($url);

    if (!$response) {
        echo "❌ $city\n";
        continue;
    }

    $weather = json_decode($response, true);
    if (isset($weather['cod']) && $weather['cod'] != 200) {
        echo "❌ $city\n";
        continue;
    }

    $temperature = $weather['main']['temp'] ?? 0;
    $humidity = $weather['main']['humidity'] ?? 0;
    $rainfall = $weather['rain']['1h'] ?? ($weather['rain']['3h'] ?? 0);

    $stmt = $conn->prepare("INSERT INTO placedetails (place, date, temperature, humidity, rainfall) VALUES (?, ?, ?, ?, ?)");
    if (!$stmt) {
        echo "❌ $city\n";
        continue;
    }

    $stmt->bind_param("ssddd", $city, $date, $temperature, $humidity, $rainfall);
    if ($stmt->execute()) {
        echo "✅ $city\n";
    } else {
        echo "❌ $city\n";
    }

    $stmt->close();
}

$conn->close();
echo "✅ Done.\n";
?>
