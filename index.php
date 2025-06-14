<?php
// Start session with secure configuration
session_start([
    'cookie_lifetime' => 0,
    'cookie_path' => '/',
    'cookie_domain' => $_SERVER['HTTP_HOST'] ?? '',
    'cookie_secure' => isset($_SERVER['HTTPS']),
    'cookie_httponly' => true,
    'cookie_samesite' => 'Strict'
]);

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Content-Security-Policy: default-src \'self\'; script-src \'self\' \'unsafe-inline\'; style-src \'self\' \'unsafe-inline\'; img-src \'self\' data:; font-src \'self\';');

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    // Check user role and redirect accordingly
    $stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    if ($user && $user['role'] === 'admin') {
        header('Location: admin/index.php');
    } else {
        header('Location: deliverypickup.php');
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ayam Gepuk - Authentic Malaysian Smashed Chicken Restaurant</title>
    <meta name="description" content="Experience the authentic taste of Malaysian Ayam Gepuk - crispy smashed chicken with traditional spices. Order online for delivery or pickup.">
    <meta name="keywords" content="ayam gepuk, malaysian food, smashed chicken, authentic cuisine, online order, delivery, pickup">
    <meta name="robots" content="index, follow">
    
    <!-- Open Graph Meta Tags -->
    <meta property="og:title" content="Ayam Gepuk - Authentic Malaysian Smashed Chicken">
    <meta property="og:description" content="Experience the authentic taste of Malaysian Ayam Gepuk - crispy smashed chicken with traditional spices.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?>">
    <meta property="og:image" content="images/ayamgepuk.jpg">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="images/logo.png">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Header Styles */
        .header {
            background: rgba(0, 0, 0, 0.9);
            backdrop-filter: blur(10px);
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
            padding: 1rem 0;
            transition: all 0.3s ease;
        }

        .header.scrolled {
            background: rgba(0, 0, 0, 0.95);
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.3);
        }

        .nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            display: flex;
            align-items: center;
            font-size: 1.8rem;
            font-weight: bold;
            color: #f5c542;
            text-decoration: none;
        }

        .logo img {
            height: 40px;
            margin-right: 10px;
            border-radius: 50%;
        }

        .auth-buttons {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: linear-gradient(45deg, #f5c542, #f39c12);
            color: #333;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(245, 197, 66, 0.4);
        }

        .btn-secondary {
            background: transparent;
            color: white;
            border: 2px solid rgba(255, 255, 255, 0.3);
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.6);
        }

        /* Hero Section */
        .hero {
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('images/welcome.jpg') center/cover;
            opacity: 0.3;
            z-index: -1;
        }

        .hero-content {
            max-width: 800px;
            padding: 2rem;
            animation: fadeInUp 1s ease;
        }

        .hero h1 {
            font-size: 3.5rem;
            margin-bottom: 1rem;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
        }

        .hero p {
            font-size: 1.3rem;
            margin-bottom: 2rem;
            opacity: 0.9;
        }

        .cta-buttons {
            display: flex;
            gap: 20px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn-large {
            padding: 15px 30px;
            font-size: 1.1rem;
            border-radius: 30px;
        }

        /* Features Section */
        .features {
            padding: 80px 0;
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }

        .section-title {
            text-align: center;
            font-size: 2.5rem;
            margin-bottom: 3rem;
            color: white;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
        }

        .feature-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            padding: 2rem;
            border-radius: 15px;
            text-align: center;
            color: white;
            transition: transform 0.3s ease;
        }

        .feature-card:hover {
            transform: translateY(-10px);
        }

        .feature-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        .feature-card h3 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        /* Footer */
        .footer {
            background: #333;
            color: white;
            padding: 40px 0 20px;
            text-align: center;
        }

        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
            margin-bottom: 20px;
        }

        .footer-section h3 {
            margin-bottom: 15px;
            color: #f5c542;
        }

        .footer-section p,
        .footer-section a {
            color: #ccc;
            text-decoration: none;
            margin-bottom: 5px;
            display: block;
        }

        .footer-section a:hover {
            color: #f5c542;
        }

        .footer-bottom {
            border-top: 1px solid #555;
            padding-top: 20px;
            margin-top: 20px;
        }

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .hero h1 {
                font-size: 2.5rem;
            }

            .hero p {
                font-size: 1.1rem;
            }

            .cta-buttons {
                flex-direction: column;
                align-items: center;
            }

            .nav {
                flex-direction: column;
                gap: 15px;
            }

            .auth-buttons {
                order: -1;
            }
        }

        /* Loading Animation */
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Accessibility */
        .sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }

        /* Focus styles for accessibility */
        .btn:focus,
        a:focus {
            outline: 2px solid #f5c542;
            outline-offset: 2px;
        }

        /* Print styles */
        @media print {
            .header, .footer {
                display: none;
            }
            
            .hero {
                height: auto;
                padding: 2rem 0;
            }
            
            body {
                background: white;
                color: black;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header" id="header">
        <div class="container">
            <nav class="nav">
                <a href="index.php" class="logo" aria-label="Ayam Gepuk Home">
                    <img src="images/logo.png" alt="Ayam Gepuk Logo" onerror="this.style.display='none'">
                    Ayam Gepuk
                </a>
                <div class="auth-buttons">
                    <a href="login.php" class="btn btn-secondary" aria-label="Login to your account">
                        <span>👤</span> Login
                    </a>
                    <a href="register.php" class="btn btn-primary" aria-label="Create new account">
                        <span>📝</span> Sign Up
                    </a>
                </div>
            </nav>
        </div>
    </header>

    <!-- Hero Section -->
    <main>
        <section class="hero" id="hero">
            <div class="hero-content">
                <h1>Real Taste of Ayam Gepuk</h1>
                <p>Authentic Malaysian smashed chicken with traditional spices, delivered fresh to your doorstep or ready for pickup</p>
                <div class="cta-buttons">
                    <a href="login.php" class="btn btn-primary btn-large" aria-label="Order food online">
                        🍽️ Order Now
                    </a>
                    <a href="welcomepage.php" class="btn btn-secondary btn-large" aria-label="Learn more about us">
                        ℹ️ Learn More
                    </a>
                </div>
            </div>
        </section>

        <!-- Features Section -->
        <section class="features" id="features">
            <div class="container">
                <h2 class="section-title">Why Choose Ayam Gepuk?</h2>
                <div class="features-grid">
                    <div class="feature-card">
                        <div class="feature-icon">🔥</div>
                        <h3>Authentic Recipe</h3>
                        <p>Traditional Malaysian smashed chicken recipe passed down through generations, using only the finest spices and cooking techniques.</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">🚚</div>
                        <h3>Fast Delivery</h3>
                        <p>Quick and reliable delivery service ensuring your food arrives hot and fresh. Average delivery time: 25-30 minutes.</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">🥘</div>
                        <h3>Fresh Ingredients</h3>
                        <p>We source only the freshest ingredients daily from local suppliers to ensure the highest quality in every bite.</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">📱</div>
                        <h3>Easy Ordering</h3>
                        <p>Simple online ordering system with real-time tracking, multiple payment options, and order history.</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">⭐</div>
                        <h3>Customer Favorite</h3>
                        <p>Rated 4.8/5 stars by over 1000+ satisfied customers. Experience the taste that keeps people coming back.</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">💯</div>
                        <h3>Quality Guarantee</h3>
                        <p>100% satisfaction guarantee. If you're not happy with your order, we'll make it right or provide a full refund.</p>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>Contact Info</h3>
                    <p>📞 Phone: +60 3-2141-8200</p>
                    <p>📧 Email: info@ayamgepuk.com</p>
                    <p>📍 Address: Jalan Bukit Bintang 123, Kuala Lumpur</p>
                </div>
                <div class="footer-section">
                    <h3>Quick Links</h3>
                    <a href="menu.php">Menu</a>
                    <a href="login.php">Login</a>
                    <a href="register.php">Register</a>
                    <a href="#features">About Us</a>
                </div>
                <div class="footer-section">
                    <h3>Follow Us</h3>
                    <a href="#" rel="noopener" target="_blank">Facebook</a>
                    <a href="#" rel="noopener" target="_blank">Instagram</a>
                    <a href="#" rel="noopener" target="_blank">Twitter</a>
                    <a href="#" rel="noopener" target="_blank">WhatsApp</a>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> Ayam Gepuk Restaurant. All rights reserved. | Made with ❤️ for authentic Malaysian cuisine</p>
            </div>
        </div>
    </footer>

    <script>
        // Header scroll effect
        window.addEventListener('scroll', function() {
            const header = document.getElementById('header');
            if (window.scrollY > 100) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        });

        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const targetId = this.getAttribute('href').substring(1);
                const targetElement = document.getElementById(targetId);
                if (targetElement) {
                    targetElement.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Add loading animation to buttons on click
        document.querySelectorAll('.btn').forEach(button => {
            button.addEventListener('click', function() {
                if (!this.href || this.href.includes('#')) return;
                
                const originalText = this.innerHTML;
                this.innerHTML = '<span class="loading"></span> Loading...';
                this.style.pointerEvents = 'none';
                
                // Reset after 2 seconds if still on page (in case of navigation failure)
                setTimeout(() => {
                    this.innerHTML = originalText;
                    this.style.pointerEvents = 'auto';
                }, 2000);
            });
        });

        // Keyboard navigation support
        document.addEventListener('keydown', function(e) {
            // Skip to main content with Alt+M
            if (e.altKey && e.key === 'm') {
                e.preventDefault();
                document.querySelector('main').focus();
            }
        });

        // Performance monitoring
        window.addEventListener('load', function() {
            // Log page load time for monitoring
            const loadTime = performance.now();
            console.log(`Page loaded in ${Math.round(loadTime)}ms`);
        });

        // Error handling for images
        document.querySelectorAll('img').forEach(img => {
            img.addEventListener('error', function() {
                this.style.display = 'none';
                console.warn('Image failed to load:', this.src);
            });
        });

        // Check if service worker is supported for PWA capabilities
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                // Optionally register service worker for PWA functionality
                console.log('Service Worker support detected');
            });
        }
    </script>

    <!-- Schema.org structured data for better SEO -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "Restaurant",
        "name": "Ayam Gepuk",
                    "description": "Authentic Malaysian smashed chicken restaurant with traditional spices",
        "url": "<?php echo (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST']; ?>",
                  "telephone": "+60-3-2141-8200",
        "email": "info@ayamgepuk.com",
        "address": {
            "@type": "PostalAddress",
                          "streetAddress": "Jalan Bukit Bintang 123",
            "addressLocality": "Kuala Lumpur",
                          "addressCountry": "MY"
        },
                    "servesCuisine": "Malaysian",
        "priceRange": "$$",
        "aggregateRating": {
            "@type": "AggregateRating",
            "ratingValue": "4.8",
            "reviewCount": "1000"
        },
        "hasMenu": "<?php echo (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST']; ?>/menu.php",
        "acceptsReservations": false,
        "paymentAccepted": "Cash, Credit Card, Online Payment"
    }
    </script>
</body>
</html> 