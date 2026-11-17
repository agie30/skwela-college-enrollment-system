<?php
session_start();

if (isset($_SESSION["user_id"])) {
    $role = $_SESSION["role"] ?? "";

    $redirects = [
        "admin" => "frontend/admin/dashboard.php",
        "registrar" => "frontend/registrar/dashboard.php",
        "student" => "frontend/student/dashboard.php"
    ];

    if (isset($redirects[$role])) {
        header("Location: " . $redirects[$role]);
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Skwela College Enrollment System</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Manrope:wght@500;600;700;800&display=swap" rel="stylesheet">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            font-family: 'Inter', sans-serif;
            background: #f4f7fc;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 25px;
        }

        .login-container {
            width: 100%;
            max-width: 1050px;
            min-height: 620px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            background: #ffffff;
            border-radius: 22px;
            overflow: hidden;
            box-shadow: 0 25px 70px rgba(20, 48, 91, 0.12);
            animation: appear .7s ease;
        }

        .brand-panel {
            background: linear-gradient(145deg, #102d59, #174d91 65%, #247fbc);
            padding: 65px 55px;
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            overflow: hidden;
        }

        .brand-panel::before {
            content: "";
            position: absolute;
            width: 350px;
            height: 350px;
            border: 1px solid rgba(255,255,255,.12);
            border-radius: 50%;
            right: -130px;
            top: -100px;
        }

        .brand-panel::after {
            content: "";
            position: absolute;
            width: 260px;
            height: 260px;
            background: rgba(255,255,255,.05);
            border-radius: 50%;
            right: -70px;
            bottom: -100px;
        }

        .brand-content,
        .brand-footer,
        .brand-logo {
            position: relative;
            z-index: 1;
        }

        .brand-logo {
            display: flex;
            align-items: center;
            gap: 13px;
        }

        .logo-mark {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            background: white;
            color: #174d91;
            display: grid;
            place-items: center;
            font-family: 'Manrope', sans-serif;
            font-size: 25px;
            font-weight: 800;
        }

        .logo-text strong {
            display: block;
            font-family: 'Manrope', sans-serif;
            font-size: 19px;
            letter-spacing: 2px;
        }

        .logo-text small {
            font-size: 10px;
            letter-spacing: 2px;
            opacity: .75;
        }

        .brand-content {
            margin-top: 65px;
            max-width: 400px;
        }

        .brand-label {
            color: #8ed9ff;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 2px;
            margin-bottom: 20px;
        }

        .brand-content h1 {
            font-family: 'Manrope', sans-serif;
            font-size: clamp(32px, 4vw, 45px);
            line-height: 1.2;
            margin-bottom: 22px;
        }

        .brand-content p {
            color: rgba(255,255,255,.78);
            line-height: 1.8;
            font-size: 14px;
        }

        .brand-footer {
            font-size: 11px;
            color: rgba(255,255,255,.65);
        }

        .form-panel {
            padding: 65px 55px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .welcome-label {
            color: #2785c2;
            font-size: 11px;
            letter-spacing: 2px;
            font-weight: 700;
            margin-bottom: 13px;
        }

        .form-panel h2 {
            font-family: 'Manrope', sans-serif;
            color: #18345a;
            font-size: 31px;
            margin-bottom: 10px;
        }

        .form-description {
            color: #7a8799;
            font-size: 13px;
            line-height: 1.7;
            margin-bottom: 35px;
        }

        .form-group {
            margin-bottom: 21px;
        }

        .form-group label {
            display: block;
            color: #283b55;
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 9px;
        }

        .input-wrapper {
            position: relative;
        }

        .form-group input {
            width: 100%;
            height: 51px;
            border: 1px solid #dce4ef;
            border-radius: 9px;
            padding: 0 15px;
            outline: none;
            font-family: inherit;
            font-size: 13px;
            color: #18345a;
            transition: .2s ease;
            background: #fbfcfe;
        }

        .form-group input:focus {
            border-color: #247dc0;
            background: white;
            box-shadow: 0 0 0 4px rgba(36,125,192,.09);
        }

        .password-input {
            padding-right: 65px !important;
        }

        .show-password {
            position: absolute;
            right: 13px;
            top: 50%;
            transform: translateY(-50%);
            border: none;
            background: none;
            color: #247dc0;
            cursor: pointer;
            font-size: 11px;
            font-weight: 700;
        }

        .login-button {
            width: 100%;
            height: 52px;
            border: none;
            border-radius: 9px;
            background: #185b9c;
            color: white;
            font-family: inherit;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            transition: .25s ease;
            margin-top: 7px;
        }

        .login-button:hover {
            background: #104474;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(24,91,156,.2);
        }

        .login-button:disabled {
            opacity: .65;
            cursor: not-allowed;
            transform: none;
        }

        .error-message {
            display: none;
            background: #fff0f0;
            color: #bd3636;
            border: 1px solid #ffd5d5;
            padding: 12px;
            border-radius: 8px;
            font-size: 12px;
            margin-bottom: 18px;
            line-height: 1.5;
        }

        .form-note {
            text-align: center;
            font-size: 11px;
            color: #98a3b2;
            margin-top: 25px;
            line-height: 1.6;
        }

        @keyframes appear {
            from {
                opacity: 0;
                transform: translateY(15px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media(max-width: 750px) {
            .login-container {
                grid-template-columns: 1fr;
                max-width: 480px;
            }

            .brand-panel {
                padding: 35px;
                min-height: 260px;
            }

            .brand-content {
                margin-top: 35px;
            }

            .brand-content h1 {
                font-size: 30px;
            }

            .brand-footer {
                margin-top: 25px;
            }

            .form-panel {
                padding: 40px 30px;
            }
        }
    </style>
</head>

<body>

<div class="login-container">

    <section class="brand-panel">

        <div class="brand-logo">
            <div class="logo-mark">S</div>

            <div class="logo-text">
                <strong>SKWELA</strong>
                <small>COLLEGE PORTAL</small>
            </div>
        </div>

        <div class="brand-content">
            <div class="brand-label">STUDENT ENROLLMENT MANAGEMENT</div>

            <h1>Welcome to a smarter way of learning.</h1>

            <p>
                Access your college enrollment portal in one place.
                Manage academic records, applications, and enrollment
                information through a secure platform.
            </p>
        </div>

        <div class="brand-footer">
            © <?= date("Y") ?> Skwela College Enrollment System
        </div>

    </section>

    <section class="form-panel">

        <div class="welcome-label">ACCOUNT ACCESS</div>

        <h2>Sign in</h2>

        <p class="form-description">
            Enter your registered email and password to access your account.
        </p>

        <div class="error-message" id="errorMessage"></div>

        <form id="loginForm">

            <div class="form-group">
                <label for="email">EMAIL ADDRESS</label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    placeholder="Enter your email address"
                    autocomplete="username"
                    required
                >
            </div>

            <div class="form-group">
                <label for="password">PASSWORD</label>

                <div class="input-wrapper">

                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="password-input"
                        placeholder="Enter your password"
                        autocomplete="current-password"
                        required
                    >

                    <button
                        type="button"
                        class="show-password"
                        id="showPassword"
                    >
                        SHOW
                    </button>

                </div>
            </div>

            <button type="submit" class="login-button" id="loginButton">
                Sign in to your account
            </button>

        </form>

        <p class="form-note">
            Secure access for authorized students and school personnel.
            <br>
            Need assistance? Contact your school administrator.
        </p>

    </section>

</div>

<script>
const loginForm = document.getElementById("loginForm");
const loginButton = document.getElementById("loginButton");
const errorMessage = document.getElementById("errorMessage");
const passwordInput = document.getElementById("password");
const showPassword = document.getElementById("showPassword");

showPassword.addEventListener("click", function () {
    const isPassword = passwordInput.type === "password";

    passwordInput.type = isPassword ? "text" : "password";
    showPassword.textContent = isPassword ? "HIDE" : "SHOW";
});

loginForm.addEventListener("submit", async function (event) {
    event.preventDefault();

    errorMessage.style.display = "none";
    loginButton.disabled = true;
    loginButton.textContent = "Signing in...";

    const email = document.getElementById("email").value.trim();
    const password = passwordInput.value;

    try {
        const response = await fetch("backend/routes/auth.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            credentials: "same-origin",
            body: JSON.stringify({
                action: "login",
                email: email,
                password: password
            })
        });

        const result = await response.json();

        if (!response.ok || result.success !== true) {
            throw new Error(result.message || "Invalid email or password.");
        }

        const role = String(
            result.role ||
            result.user?.role ||
            result.data?.role ||
            result.data?.user?.role ||
            ""
        ).toLowerCase();

        const destinations = {
            admin: "frontend/admin/dashboard.php",
            registrar: "frontend/registrar/dashboard.php",
            student: "frontend/student/dashboard.php"
        };

        if (!destinations[role]) {
            throw new Error("Your account role could not be identified.");
        }

        window.location.href = destinations[role];

    } catch (error) {
        errorMessage.textContent = error.message;
        errorMessage.style.display = "block";

        loginButton.disabled = false;
        loginButton.textContent = "Sign in to your account";
    }
});
</script>

</body>
</html>