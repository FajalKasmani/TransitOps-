<?php
declare(strict_types=1);

http_response_code(404);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page Not Found - TransitOps</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background: #0f172a; color: #f8fafc; font-family: sans-serif; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; }
        .error-card { max-width: 500px; padding: 2.5rem; border-radius: 16px; background: rgba(30, 41, 59, 0.7); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.05); text-align: center; }
    </style>
</head>
<body>
    <div class="error-card shadow-lg animate__animated animate__fadeIn">
        <div class="text-warning mb-4" style="font-size: 4rem;"><i class="bi bi-question-circle-fill"></i></div>
        <h2 class="fw-bold mb-3">404 - Page Not Found</h2>
        <p class="text-muted mb-4">The page or resource you are looking for does not exist or has been relocated.</p>
        <a href="/TransitOps/public/index.php" class="btn btn-primary px-4 py-2" style="border-radius: 10px;">Return to Dashboard</a>
    </div>
</body>
</html>
