
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset Password</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            margin: 0;
            font-family: 'Nunito', 'Open Sans', Arial, sans-serif;
            background: #f2f5fa;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        .reset-container {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(33,150,243,0.1);
            padding: 40px;
            max-width: 400px;
            width: 90%;
            text-align: center;
        }
        h1 {
            font-size: 1.8rem;
            margin-bottom: 20px;
            color: #2c2c3c;
        }
        p {
            margin: 0 0 20px;
            color: #555;
        }
        input[type="email"] {
            width: 100%;
            padding: 12px 10px;
            margin-bottom: 20px;
            border: 1px solid #dedede;
            border-radius: 8px;
            font-size: 1rem;
        }
        button {
            width: 100%;
            padding: 12px;
            background: linear-gradient(90deg, #2196f3 60%, #1565c0 100%);
            border: none;
            border-radius: 8px;
            color: #fff;
            font-size: 1rem;
            cursor: pointer;
            transition: background 0.25s, transform 0.25s;
        }
        button:hover {
            background: linear-gradient(90deg, #1565c0 60%, #2196f3 100%);
            transform: translateY(-2px);
        }
        .message {
            margin-top: 20px;
            padding: 12px;
            border-radius: 8px;
            display: none;
        }
        .message.error {
            background: #ffe0e0;
            color: #b71c1c;
            border: 1px solid #f38d8d;
        }
        .message.success {
            background: #d0f8ce;
            color: #176c0c;
            border: 1.5px solid #8ae68c;
        }
    </style>
</head>
<body>
<div class="reset-container">
    <h1>Reset Password</h1>
    <p>Please enter your email address to receive a password reset link.</p>
    <?php if ($error): ?>
        <div class="message error" style="display:block;"><?php echo htmlspecialchars($error); ?></div>
    <?php elseif ($success): ?>
        <div class="message success" style="display:block;"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    <form action="reset_password.php" method="POST" autocomplete="off">
        <input type="email" name="email" placeholder="Enter your email" required>
        <button type="submit">Send Reset Link</button>
    </form>
</div>
</body>
</html>