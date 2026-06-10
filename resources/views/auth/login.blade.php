@extends('layouts.app')

@section('content')
<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

    :root {
        --primary: #4f46e5;
        --primary-dark: #4338ca;
        --primary-light: #818cf8;
        --secondary: #0ea5e9;
        --bg-gradient-start: #0f172a;
        --bg-gradient-end: #1e293b;
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Inter', sans-serif;
        min-height: 100vh;
        background: linear-gradient(135deg, var(--bg-gradient-start) 0%, var(--bg-gradient-end) 100%);
    }

    .login-container {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }

    .login-card {
        display: flex;
        background: rgba(255, 255, 255, 0.95);
        border-radius: 24px;
        overflow: hidden;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        max-width: 1000px;
        width: 100%;
        backdrop-filter: blur(10px);
    }

    .login-visual {
        flex: 1;
        background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
        padding: 60px 40px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        color: white;
        position: relative;
        overflow: hidden;
    }

    .login-visual::before {
        content: '';
        position: absolute;
        width: 300px;
        height: 300px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 50%;
        top: -100px;
        right: -100px;
    }

    .login-visual::after {
        content: '';
        position: absolute;
        width: 200px;
        height: 200px;
        background: rgba(255, 255, 255, 0.08);
        border-radius: 50%;
        bottom: -50px;
        left: -50px;
    }

    .login-visual h1 {
        font-size: 2.5rem;
        font-weight: 700;
        margin-bottom: 16px;
        z-index: 1;
    }

    .login-visual p {
        font-size: 1.1rem;
        opacity: 0.9;
        text-align: center;
        z-index: 1;
        max-width: 300px;
    }

    .login-visual .icon-bg {
        font-size: 120px;
        margin-bottom: 30px;
        opacity: 0.3;
        z-index: 1;
    }

    .login-form-section {
        flex: 1;
        padding: 60px 50px;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }

    .login-header {
        margin-bottom: 40px;
    }

    .login-header h2 {
        font-size: 2rem;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 8px;
    }

    .login-header p {
        color: #64748b;
        font-size: 0.95rem;
    }

    .form-group {
        margin-bottom: 24px;
        position: relative;
    }

    .form-group label {
        position: absolute;
        left: 16px;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
        font-size: 0.95rem;
        pointer-events: none;
        transition: all 0.3s ease;
        background: #fff;
        padding: 0 4px;
    }

    .form-group input {
        width: 100%;
        padding: 16px 16px 16px 48px;
        border: 2px solid #e2e8f0;
        border-radius: 12px;
        font-size: 1rem;
        transition: all 0.3s ease;
        font-family: 'Inter', sans-serif;
    }

    .form-group input:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
    }

    .form-group input:focus + label,
    .form-group input:not(:placeholder-shown) + label {
        top: 0;
        font-size: 0.8rem;
        color: var(--primary);
    }

    .input-icon {
        position: absolute;
        left: 16px;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
        transition: color 0.3s ease;
    }

    .form-group input:focus ~ .input-icon {
        color: var(--primary);
    }

    .remember-forgot {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
    }

    .form-check {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .form-check input[type="checkbox"] {
        width: 18px;
        height: 18px;
        border-radius: 6px;
        border: 2px solid #e2e8f0;
        cursor: pointer;
        accent-color: var(--primary);
    }

    .form-check label {
        color: #64748b;
        font-size: 0.9rem;
        cursor: pointer;
    }

    .forgot-link {
        color: var(--primary);
        text-decoration: none;
        font-size: 0.9rem;
        font-weight: 500;
        transition: color 0.3s ease;
    }

    .forgot-link:hover {
        color: var(--primary-dark);
    }

    .btn-login {
        width: 100%;
        padding: 16px;
        background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
        border: none;
        border-radius: 12px;
        color: white;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }

    .btn-login:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 30px -10px rgba(79, 70, 229, 0.5);
    }

    .btn-login:active {
        transform: translateY(0);
    }

    .divider {
        display: flex;
        align-items: center;
        margin: 30px 0;
        color: #94a3b8;
        font-size: 0.9rem;
    }

    .divider::before,
    .divider::after {
        content: '';
        flex: 1;
        height: 1px;
        background: #e2e8f0;
    }

    .divider span {
        padding: 0 16px;
    }

    .register-prompt {
        text-align: center;
        color: #64748b;
        font-size: 0.95rem;
    }

    .register-prompt a {
        color: var(--primary);
        text-decoration: none;
        font-weight: 600;
    }

    .register-prompt a:hover {
        text-decoration: underline;
    }

    .error-message {
        background: #fef2f2;
        border: 1px solid #fecaca;
        color: #dc2626;
        padding: 12px 16px;
        border-radius: 8px;
        margin-bottom: 20px;
        font-size: 0.9rem;
    }

    @media (max-width: 768px) {
        .login-card {
            flex-direction: column;
            max-width: 450px;
        }

        .login-visual {
            padding: 40px 30px;
        }

        .login-visual h1 {
            font-size: 1.8rem;
        }

        .login-visual .icon-bg {
            font-size: 80px;
        }

        .login-form-section {
            padding: 40px 30px;
        }
    }
</style>

<div class="login-container">
    <div class="login-card">
        <div class="login-visual">
            <i class="bi bi-truck icon-bg"></i>
            <h1>SXpress Logistics</h1>
            <p>Streamline your freight management with our comprehensive tracking solution</p>
        </div>

        <div class="login-form-section">
            <div class="login-header">
                <h2>Welcome Back</h2>
                <p>Sign in to access your dashboard</p>
            </div>

            @if ($errors->any())
                <div class="error-message">
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf

                <div class="form-group">
                    <i class="bi bi-envelope-fill input-icon"></i>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder=" "
                        value="{{ old('email') }}"
                        required
                        autocomplete="email"
                    >
                    <label for="email">Email Address</label>
                </div>

                <div class="form-group">
                    <i class="bi bi-lock-fill input-icon"></i>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder=" "
                        required
                        autocomplete="current-password"
                    >
                    <label for="password">Password</label>
                </div>

                <div class="remember-forgot">
                    <div class="form-check">
                        <input type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                        <label for="remember">Remember me</label>
                    </div>

                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}" class="forgot-link">Forgot password?</a>
                    @endif
                </div>

                <button type="submit" class="btn-login">
                    <span>Sign In</span>
                    <i class="bi bi-arrow-right"></i>
                </button>
            </form>

            @if (Route::has('register'))
                <div class="divider"><span>or</span></div>

                <p class="register-prompt">
                    Don't have an account? <a href="{{ route('register') }}">Create Account</a>
                </p>
            @endif
        </div>
    </div>
</div>
@endsection