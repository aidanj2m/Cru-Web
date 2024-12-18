<?php
// Add security headers
header("Content-Security-Policy: default-src 'self' https://www.meetcru.com https://apps.apple.com; style-src 'self' 'unsafe-inline'; img-src 'self' https://www.meetcru.com; script-src 'self' 'unsafe-inline';");
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Permissions-Policy: geolocation=(), microphone=(), camera=()");
header("Strict-Transport-Security: max-age=31536000; includeSubDomains");

$code = isset($_GET['code']) ? $_GET['code'] : '';
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Add iOS Smart App Banner -->
    <meta name="apple-itunes-app" content="app-id=6739068968">
    <!-- Favicon configuration -->
    <link rel="icon" type="image/png" href="https://www.meetcru.com/Cru_icon.png">
    <link rel="apple-touch-icon" href="https://www.meetcru.com/Cru_icon.png">
    <title>Join my Cr&uuml;</title>
    
    <!-- Social Preview Meta Tags -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://www.meetcru.com/join/<?php echo htmlspecialchars($code); ?>">
    <meta property="og:title" content="Join my Cr&uuml;">
    <meta property="og:description" content="Join my private group on Cr&uuml; - the social space for your closest friends.">
    <meta property="og:image" content="https://www.meetcru.com/generate-preview.php?code=<?php echo urlencode($code); ?>&group=<?php echo urlencode($_GET['group'] ?? ''); ?>&image=<?php echo urlencode($_GET['image'] ?? ''); ?>">
    
    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:url" content="https://www.meetcru.com/join/<?php echo htmlspecialchars($code); ?>">
    <meta name="twitter:title" content="Join my Cr&uuml;">
    <meta name="twitter:description" content="Join my private group on Cr&uuml; - the social space for your closest friends.">
    <meta name="twitter:image" content="https://www.meetcru.com/generate-preview.php?code=<?php echo urlencode($code); ?>&group=<?php echo urlencode($_GET['group'] ?? ''); ?>&image=<?php echo urlencode($_GET['image'] ?? ''); ?>">

    <style>
        body {
            font-family: -apple-system, system-ui, BlinkMacSystemFont, sans-serif;
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background-color: white;
            color: #0A84FF;
            text-align: center;
        }

        .container {
            padding: 20px;
            max-width: 400px;
            width: 90%;
        }

        .logo-container {
            position: relative;
            width: 100px;
            height: 100px;
            margin: 0 auto 32px;
            animation: float 3s ease-in-out infinite;
        }

        .logo {
            width: 100%;
            height: 100%;
            border-radius: 24px;
            object-fit: cover;
            box-shadow: 0 8px 24px rgba(10, 132, 255, 0.15);
        }

        .logo-ring {
            position: absolute;
            top: -4px;
            left: -4px;
            right: -4px;
            bottom: -4px;
            border-radius: 28px;
            border: 2px solid #0A84FF;
            animation: pulse 2s ease-in-out infinite;
        }

        .title {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 16px;
            color: #0A84FF;
            opacity: 0;
            animation: fadeIn 0.5s ease-out forwards;
        }

        .message {
            font-size: 18px;
            color: #666;
            margin-bottom: 24px;
            animation: fadeIn 0.5s ease-out 0.2s forwards;
            opacity: 0;
        }

        .button {
            display: none;
            background-color: #0A84FF;
            color: white;
            padding: 16px 32px;
            border-radius: 16px;
            text-decoration: none;
            font-weight: 600;
            font-size: 18px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(10, 132, 255, 0.2);
            opacity: 0;
            animation: fadeIn 0.5s ease-out forwards;
        }

        .button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(10, 132, 255, 0.3);
        }

        .loading {
            width: 40px;
            height: 40px;
            border: 3px solid rgba(10, 132, 255, 0.1);
            border-radius: 50%;
            border-top: 3px solid #0A84FF;
            animation: spin 1s linear infinite;
            margin: 20px auto;
            opacity: 0;
            animation: fadeIn 0.5s ease-out 0.4s forwards, spin 1s linear infinite;
        }

        @keyframes float {
            0%, 100% { 
                transform: translateY(0);
                filter: drop-shadow(0 8px 24px rgba(10, 132, 255, 0.15));
            }
            50% { 
                transform: translateY(-10px);
                filter: drop-shadow(0 12px 28px rgba(10, 132, 255, 0.2));
            }
        }

        @keyframes pulse {
            0%, 100% { opacity: 0.5; }
            50% { opacity: 0.8; }
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo-container">
            <img src="https://www.meetcru.com/Cru_icon.png" alt="Cru" class="logo">
            <div class="logo-ring"></div>
        </div>
        <h1 class="title">Opening Cr&uuml</h1>
        <p class="message" id="message">Taking you to the app...</p>
        <div class="loading" id="loading"></div>
        <a href="#" id="openButton" class="button">Open App</a>
    </div>

    <script>
        const code = '<?php echo htmlspecialchars($code); ?>';
        const customLink = `cru://join/${code}`;
        let hasOpened = false;

        function showFallback() {
            document.getElementById('message').textContent = 'App not installed?';
            document.getElementById('loading').style.display = 'none';
            document.getElementById('openButton').style.display = 'block';
            
            const openButton = document.getElementById('openButton');
            openButton.href = 'https://apps.apple.com/us/app/crü/id6739068968';
            openButton.onclick = null;
            
            setTimeout(() => {
                if (!hasOpened && !document.hidden) {
                    window.location.href = 'https://apps.apple.com/us/app/crü/id6739068968';
                }
            }, 2500);
        }

        function openApp() {
            if (hasOpened) return;
            hasOpened = true;

            window.location.href = customLink;

            setTimeout(() => {
                if (!document.hidden) {
                    const iframe = document.createElement('iframe');
                    iframe.style.display = 'none';
                    iframe.src = customLink;
                    document.body.appendChild(iframe);
                    
                    setTimeout(() => {
                        document.body.removeChild(iframe);
                    }, 100);
                }
            }, 100);

            setTimeout(() => {
                if (!document.hidden) {
                    showFallback();
                }
            }, 1500);
        }

        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                hasOpened = true;
            }
        });

        document.getElementById('openButton').onclick = function(e) {
            e.preventDefault();
            window.location.href = customLink;
        };

        if (document.readyState === 'complete') {
            openApp();
        } else {
            window.addEventListener('load', openApp);
        }
    </script>
</body>
</html>