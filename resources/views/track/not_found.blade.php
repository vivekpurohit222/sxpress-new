<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GR Not Found - SXpress Logistics</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .not-found-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            padding: 60px 40px;
            text-align: center;
            max-width: 500px;
        }
        .not-found-icon {
            font-size: 5rem;
            color: #dc3545;
            margin-bottom: 20px;
        }
        h1 {
            font-size: 2rem;
            margin-bottom: 10px;
        }
        p {
            color: #666;
        }
        .gr-no {
            font-size: 1.5rem;
            font-weight: 700;
            color: #667eea;
            background: #f0f0ff;
            padding: 10px 20px;
            border-radius: 8px;
            display: inline-block;
            margin: 20px 0;
        }
        .footer {
            margin-top: 30px;
            color: #999;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="not-found-card">
        <div class="not-found-icon">
            <i class="bi bi-search"></i>
        </div>
        <h1>GR Not Found</h1>
        <p>We couldn't find any shipment with the following GR number:</p>
        <div class="gr-no">{{ $gr_no }}</div>
        <p class="text-muted small">Please verify the GR number and try again.</p>
        <a href="{{ url('/') }}" class="btn btn-primary mt-3">
            <i class="bi bi-house"></i> Go to Login
        </a>
        <div class="footer">
            <p class="mb-0">SXpress Logistics - Track your shipments</p>
        </div>
    </div>
</body>
</html>