<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page Not Found - GrihasthiKart</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <main class="min-vh-100 d-flex align-items-center py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-7 text-center">
                    <img src="{{ asset('assets/images/logos/logo.png') }}" alt="GrihasthiKart" style="height: 56px;">
                    <div class="display-4 fw-bold text-success mt-4">404</div>
                    <h1 class="h3 mb-3">Page not found</h1>
                    <p class="text-muted mb-4">The page you are looking for may have moved, expired, or does not exist.</p>
                    <div class="d-flex flex-wrap gap-2 justify-content-center">
                        <a href="{{ route('home') }}" class="btn btn-success">Back to Home</a>
                        <a href="{{ route('products.index') }}" class="btn btn-outline-success">Continue Shopping</a>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
