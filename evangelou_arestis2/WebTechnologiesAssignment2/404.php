<?php
http_response_code(404);

echo '<!DOCTYPE html>';
echo '<html lang="en">';
echo '<head>';
echo '    <meta charset="UTF-8">';
echo '    <meta name="viewport" content="width=device-width, initial-scale=1.0">';
echo '    <title>404 - Page Not Found</title>';
echo '    <link rel="stylesheet" href="styles.css">';
echo '</head>';
echo '<body class="error-page-body">';
echo '<main class="error-page">';
echo '    <div class="error-content">';
echo '        <p class="error-display">404</p>';
echo '        <h1 class="error-heading">Page Not Found!</h1>';
echo '        <p class="error-subtitle">The page you are looking for doesn’t exist.</p>';
echo '        <a class="error-button" href="index.php">GO TO HOMEPAGE</a>';
echo '    </div>';
echo '</main>';
echo '</body>';
echo '</html>';