<?php
session_start();
if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit();
}

// Connect to database
$servername = "localhost";
$username = "root";
$password = ""; // update if needed
$dbname = "ayam_gepuk"; // use your actual database name

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$email = $_SESSION['email'];
$sql = "SELECT username FROM users WHERE email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->bind_result($userName);
$stmt->fetch();
$stmt->close();
$conn->close();

// Fallback in case username not found
if (!$userName) {
    $userName = "Customer";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Delivery Page</title>
  <style>
    html, body {
      height: 100%;
      margin: 0;
      padding: 0;
      display: flex;
      flex-direction: column;
      font-family: Arial, sans-serif;
      background: url('images/background.jpg');
      background-size: cover;
      background-position: center;
      color: #000;
    }
    .content-wrapper {
      flex: 1;
    }
    .header {
      background-color: #8B0000;
      padding: 10px 20px;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }
    .header img { height: 50px; }
    .header nav a {
      color: white;
      margin-left: 15px;
      text-decoration: none;
      font-weight: bold;
    }
    .location-box {
      background: #fff;
      border: 1px solid #ddd;
      margin: 20px auto;
      padding: 20px;
      width: 90%;
      max-width: 500px;
      border-radius: 8px;
      text-align: center;
    }
    .location-box input[type="text"] {
      width: 90%;
      padding: 10px;
      margin: 10px 0;
      border: 1px solid #ccc;
      border-radius: 5px;
    }
    .location-box button {
      background: #8B0000;
      color: white;
      border: none;
      padding: 10px 20px;
      margin: 5px;
      font-weight: bold;
      cursor: pointer;
      border-radius: 5px;
    }
    #map-container {
      margin: 20px auto;
      width: 90%;
      max-width: 700px;
      display: none;
    }
    #map-frame {
      width: 100%;
      height: 350px;
      border: none;
      border-radius: 10px;
    }
    .logout-link {
      color: white;
      background-color: red;
      padding: 5px 12px;
      border-radius: 5px;
      text-decoration: none;
      margin-left: 15px;
    }
    .footer {
      background: black;
      color: white;
      padding: 20px;
      font-size: 14px;
      display: flex;
      flex-wrap: wrap;
      justify-content: space-between;
      margin-top: auto;
    }
    .footer-column {
      flex: 1;
      min-width: 150px;
      margin: 10px 0;
    }
    .footer input[type="email"] {
      padding: 8px;
      width: 80%;
      margin-top: 10px;
    }
    .footer button {
      padding: 8px 15px;
      background: gold;
      border: none;
      cursor: pointer;
      font-weight: bold;
      margin-top: auto;
    }
  </style>
</head>
<body>
  <div class="content-wrapper">
    <!-- Header -->
    <div class="header">
      <img src="images/logo.png" alt="Logo">
      <nav>
        <a href="#">HOME</a>
        <a href="#">EXPLORE PG</a>
        <a href="#">FOOD</a>
        <a href="#">PG DEALS</a>
        <a href="#">OUTLETS</a>
        <a href="#">DELIVERY/PICKUP</a>
        <a href="#">REVIEW</a>
        <a href="#">CONTACT US</a>
        <a href="logout.php" class="logout-link">Logout</a>
      </nav>
    </div>

    <!-- Location Search -->
    <div class="location-box">
      <h2>HYE <?php echo strtoupper($userName); ?></h2>
      <p>Where should we deliver your food today?</p>
      <input type="text" id="locationInput" placeholder="Type your location">
      <br>
      <button id="searchBtn">Search</button>
      <button id="currentBtn">Use My Current Location</button>
      <br><br>
      <button id="confirmBtn" style="display: none;">Confirm Location</button>
    </div>

    <!-- Map Display -->
    <div id="map-container">
      <iframe id="map-frame" allowfullscreen loading="lazy"
        referrerpolicy="no-referrer-when-downgrade"></iframe>
    </div>
  </div>

  <!-- Footer -->
  <div class="footer">
    <div class="footer-column">
      <img src="images/logo.png" alt="Logo" style="height: 60px;"><br>
      AYAM GEPUK PAK GEMBUS MALAYSIA<br>
      Subscribe to get latest updates and deals straight to your inbox.
      <br>
      <input type="email" placeholder="Your email address">
      <br>
      <button>SUBSCRIBE</button>
    </div>
    <div class="footer-column">
      <p><strong>HOME</strong></p>
      <p>EXPLORE</p>
      <p>MENU</p>
      <p>PROMOTIONS</p>
      <p>OUTLETS</p>
    </div>
    <div class="footer-column">
      <p><strong>NEWS</strong></p>
      <p>BUSINESS OPPORTUNITY</p>
      <p>DELIVERY/PICKUP</p>
      <p>CONTACT US</p>
    </div>
  </div>

  <script>
    const mapFrame = document.getElementById('map-frame'),
          mapContainer = document.getElementById('map-container'),
          searchBtn = document.getElementById('searchBtn'),
          currentBtn = document.getElementById('currentBtn'),
          confirmBtn = document.getElementById('confirmBtn'),
          locationInput = document.getElementById('locationInput');

    // When user searches manually
    searchBtn.addEventListener('click', () => {
      const loc = locationInput.value.trim();
      if (!loc) return alert('Please type a location');
      mapFrame.src = `https://www.google.com/maps?q=${encodeURIComponent(loc)}&output=embed`;
      mapContainer.style.display = 'block';
      confirmBtn.style.display = 'inline-block';
    });

    // When user uses GPS
    currentBtn.addEventListener('click', () => {
      if (!navigator.geolocation) {
        return alert('Geolocation not supported');
      }
      navigator.geolocation.getCurrentPosition(pos => {
        const { latitude: lat, longitude: lng } = pos.coords;
        // Reverse-geocode to address
        fetch(`https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${lat}&lon=${lng}`)
          .then(r => r.json())
          .then(data => {
            const address = data.display_name || `${lat},${lng}`;
            locationInput.value = address;
            mapFrame.src = `https://www.google.com/maps?q=${lat},${lng}&output=embed`;
            mapContainer.style.display = 'block';
            confirmBtn.style.display = 'inline-block';
          })
          .catch(()=> alert('Unable to get address'));
      }, () => alert('Unable to retrieve your location'));
    });

    // Confirm button
confirmBtn.addEventListener('click', () => {
  const locationValue = locationInput.value.trim();
  if (!locationValue) {
    alert('Location input is empty.');
    return;
  }

  fetch('save_location.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: `location=${encodeURIComponent(locationValue)}`
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      alert('Location saved!');
      window.location.href = 'menu.php';
    } else {
      alert('Failed to save location: ' + (data.message || ''));
    }
  })
  .catch(() => alert('An error occurred while saving location.'));
});

  </script>
</body>
</html>




