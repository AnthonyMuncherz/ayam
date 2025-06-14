<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Welcome to Ayam Gepuk</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background-image: url('images/welcome.jpg'); 
            background-size: cover;
            background-position: center;
            font-family: Arial, sans-serif;
            color: white;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: rgba(0, 0, 0, 0.7);
            padding: 20px;
        }

        .logo img {
            height: 50px;
        }

        .header a img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            transition: transform 0.2s;
        }

        .header a img:hover {
            transform: scale(1.1);
        }

        .container {
            display: flex;
            justify-content: center;
            align-items: center;
            height: calc(100vh - 80px);
        }

        .center-text {
            text-align: center;
            background-color: rgba(0, 0, 0, 0.6);
            padding: 40px;
            border-radius: 10px;
        }

        h1 {
            font-size: 48px;
            color: #f5c542;
        }

        p {
            font-size: 20px;
            color: #ffffff;
        }
    </style>
</head>
<body>

    <!-- Header with logo and profile icon -->
    <div class="header">
        <!-- Logo section -->
        <div class="logo">
            <img src="images/logo.png" alt="Ayam Gepuk Logo"> <!-- Replace with your actual logo filename -->
        </div>

        <!-- Profile icon linking to login -->
        <a href="login.php">
            <img src="data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI0OCIgaGVpZ2h0PSI0OCIgdmlld0JveD0iMCAwIDI0IDI0IiBmaWxsPSIjZmZmZmZmIj48Y2lyY2xlIGN4PSIxMiIgY3k9IjgiIHI9IjQiLz48cGF0aCBkPSJNMTIgMTBDMTAuMzIgMTAgOC45IDEwLjk4IDguMSAxMi4zNkE2LjAxIDYuMDEgMCAwIDAgNiAxNmMwIC41NS40NSAxIDEgMWgxMmMuNTUgMCAxLS40NSAxLTFhNi4wMSA2LjAxIDAgMCAwLTEuMS0zLjY0QzE1LjEgMTAuOTggMTMuNjggMTAgMTIgMTBaIi8+PC9zdmc+" alt="Profile Icon">
        </a>
    </div>

    <!-- Main welcome message -->
    <div class="container">
        <div class="center-text">
            <h1>Real Taste of Ayam Gepuk</h1>
            <p>Authentic Indonesian flavor, right at your fingertips.</p>
        </div>
    </div>

</body>
</html>
