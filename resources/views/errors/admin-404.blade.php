<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page Not Found - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <main class="min-vh-100 d-flex align-items-center py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-7 text-center">
                    <div class="display-4 fw-bold text-secondary">404</div>
                    <h1 class="h3 mb-3">Page Not Found</h1>
                    <p class="text-muted mb-4">The admin page you requested may have moved, expired, or is no longer available.</p>
                    <div class="d-flex flex-wrap gap-2 justify-content-center">
                        <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('admin.dashboard') }}" class="btn btn-outline-secondary">Back to Previous Page</a>
                        <a href="{{ route('admin.dashboard') }}" class="btn btn-success">Admin Dashboard</a>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
