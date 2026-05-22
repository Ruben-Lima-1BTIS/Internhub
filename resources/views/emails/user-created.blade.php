<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Created</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            background-color: #f8fafc;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            font-family: sans-serif;
        }

        .card {
            width: 100%;
            max-width: 32rem;
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 1rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }
        
        .card-header {
            padding: 2.5rem 2rem 1.5rem;
            text-align: center;
        }

        .logo-wrapper {
            margin-bottom: 1.5rem;
        }

        .logo {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 0.375rem;
            background-color: #0f172a;
            color: #ffffff;
            font-weight: 600;
            font-size: 0.875rem;
        }

        .card-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: #0f172a;
        }

        .card-subtitle {
            font-size: 0.875rem;
            color: #64748b;
            margin-top: 0.5rem;
        }

        .divider {
            border: none;
            border-top: 1px solid #f1f5f9;
        }

        .card-body {
            padding: 2rem;
            text-align: center;
        }

        .body-heading {
            font-size: 1rem;
            font-weight: 500;
            color: #0f172a;
            margin-bottom: 0.75rem;
        }

        .body-text {
            font-size: 0.875rem;
            color: #475569;
            line-height: 1.625;
            margin-bottom: 2rem;
        }

        .btn {
            display: inline-block;
            background-color: #0f172a;
            color: #ffffff;
            font-size: 0.875rem;
            font-weight: 500;
            padding: 0.625rem 1.25rem;
            border-radius: 0.5rem;
            text-decoration: none;
            transition: background-color 0.15s ease;
        }

        .btn:hover {
            background-color: #1e293b;
        }

        /* Footer */
        .card-footer {
            border-top: 1px solid #f1f5f9;
            padding: 1.5rem 2rem;
            text-align: center;
        }

        .footer-text {
            font-size: 0.75rem;
            color: #94a3b8;
        }
    </style>
</head>
<body>

<div class="card">

    <div class="card-header">
        <div class="logo-wrapper">
            <div class="logo">IH</div>
        </div>
        <h1 class="card-title">Account created</h1>
        <p class="card-subtitle">You're ready to get started</p>
    </div>

    <hr class="divider">

    <div class="card-body">
        <h2 class="body-heading">Welcome aboard</h2>
        <p class="body-text">
            Your account has been successfully created. For security purposes, you'll be asked to change your password on your first login.
        </p>
        <a href="#" class="btn">Sign in</a>
    </div>

    <div class="card-footer">
        <p class="footer-text">
            If you didn't request this account, you can ignore this email.
        </p>
    </div>

</div>

</body>
</html>