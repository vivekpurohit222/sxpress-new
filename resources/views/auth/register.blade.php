@extends('layouts.app')

@section('content')
<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

    :root {
        --primary: #4f46e5;
        --primary-dark: #4338ca;
        --primary-light: #818cf8;
        --secondary: #0ea5e9;
        --success: #10b981;
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

    .register-container {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }

    .register-card {
        display: flex;
        background: rgba(255, 255, 255, 0.95);
        border-radius: 24px;
        overflow: hidden;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        max-width: 1000px;
        width: 100%;
        backdrop-filter: blur(10px);
    }

    .register-visual {
        flex: 1;
        background: linear-gradient(135deg, var(--success) 0%, var(--secondary) 100%);
        padding: 60px 40px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        color: white;
        position: relative;
        overflow: hidden;
    }

    .register-visual::before {
        content: '';
        position: absolute;
        width: 300px;
        height: 300px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 50%;
        top: -100px;
        right: -100px;
    }

    .register-visual::after {
        content: '';
        position: absolute;
        width: 200px;
        height: 200px;
        background: rgba(255, 255, 255, 0.08);
        border-radius: 50%;
        bottom: -50px;
        left: -50px;
    }

    .register-visual h1 {
        font-size: 2.5rem;
        font-weight: 700;
        margin-bottom: 16px;
        z-index: 1;
    }

    .register-visual p {
        font-size: 1.1rem;
        opacity: 0.9;
        text-align: center;
        z-index: 1;
        max-width: 300px;
    }

    .register-visual .icon-bg {
        font-size: 120px;
        margin-bottom: 30px;
        opacity: 0.3;
        z-index: 1;
    }

    .register-form-section {
        flex: 1.2;
        padding: 50px 45px;
        overflow-y: auto;
        max-height: 90vh;
    }

    .register-header {
        margin-bottom: 35px;
    }

    .register-header h2 {
        font-size: 2rem;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 8px;
    }

    .register-header p {
        color: #64748b;
        font-size: 0.95rem;
    }

    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
    }

    .form-group {
        margin-bottom: 22px;
        position: relative;
    }

    .form-group label {
        position: absolute;
        left: 16px;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
        font-size: 0.9rem;
        pointer-events: none;
        transition: all 0.3s ease;
        background: #fff;
        padding: 0 4px;
    }

    .form-group input, .form-group select {
        width: 100%;
        padding: 16px 16px 16px 48px;
        border: 2px solid #e2e8f0;
        border-radius: 12px;
        font-size: 0.95rem;
        transition: all 0.3s ease;
        font-family: 'Inter', sans-serif;
        background: #fff;
    }

    .form-group select {
        appearance: none;
        cursor: pointer;
    }

    .form-group select ~ .input-icon {
        pointer-events: none;
    }

    .form-group input:focus, .form-group select:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
    }

    .form-group input:focus + label,
    .form-group input:not(:placeholder-shown) + label {
        top: 0;
        font-size: 0.75rem;
        color: var(--primary);
    }

    .form-group select:focus + label,
    .form-group select:valid + label {
        top: 0;
        font-size: 0.75rem;
        color: var(--primary);
    }

    .input-icon {
        position: absolute;
        left: 16px;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
        transition: color 0.3s ease;
        pointer-events: none;
    }

    .select-arrow {
        position: absolute;
        right: 16px;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
        pointer-events: none;
    }

    .form-group input:focus ~ .input-icon {
        color: var(--primary);
    }

    .form-group.has-error input,
    .form-group.has-error select {
        border-color: #ef4444;
    }

    .form-group .error-text {
        color: #ef4444;
        font-size: 0.8rem;
        margin-top: 4px;
        display: none;
    }

    .form-group.has-error .error-text {
        display: block;
    }

    .password-requirements {
        margin-top: 6px;
        font-size: 0.8rem;
        color: #94a3b8;
    }

    .btn-register {
        width: 100%;
        padding: 16px;
        background: linear-gradient(135deg, var(--success) 0%, #059669 100%);
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

    .btn-register:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 30px -10px rgba(16, 185, 129, 0.5);
    }

    .btn-register:active {
        transform: translateY(0);
    }

    .divider {
        display: flex;
        align-items: center;
        margin: 25px 0;
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

    .login-prompt {
        text-align: center;
        color: #64748b;
        font-size: 0.95rem;
    }

    .login-prompt a {
        color: var(--success);
        text-decoration: none;
        font-weight: 600;
    }

    .login-prompt a:hover {
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

    .office-select {
        position: relative;
    }

    .office-select select {
        padding-right: 40px;
    }

    @media (max-width: 768px) {
        .register-card {
            flex-direction: column;
            max-width: 500px;
        }

        .register-visual {
            padding: 40px 30px;
        }

        .register-visual h1 {
            font-size: 1.8rem;
        }

        .register-visual .icon-bg {
            font-size: 80px;
        }

        .register-form-section {
            padding: 35px 25px;
            max-height: none;
        }

        .form-row {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="register-container">
    <div class="register-card">
        <div class="register-visual">
            <i class="bi bi-box-seam icon-bg"></i>
            <h1>Join SXpress</h1>
            <p>Create your account and start managing freight efficiently</p>
        </div>

        <div class="register-form-section">
            <div class="register-header">
                <h2>Create Account</h2>
                <p>Fill in your details to get started</p>
            </div>

            @if ($errors->any())
                <div class="error-message">
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('register') }}">
                @csrf

                <div class="form-group">
                    <i class="bi bi-person-fill input-icon"></i>
                    <input
                        type="text"
                        id="name"
                        name="name"
                        placeholder=" "
                        value="{{ old('name') }}"
                        required
                        autocomplete="name"
                        autofocus
                    >
                    <label for="name">Full Name</label>
                </div>

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

                <div class="form-group office-select">
                    <i class="bi bi-building input-icon"></i>
                    <select id="office" name="office" required>
                        <option value="" disabled selected></option>
                        <option value="Rajkot" {{ old('office') == 'Rajkot' ? 'selected' : '' }}>Rajkot</option>
                        <option value="Kashmore Gate" {{ old('office') == 'Kashmore Gate' ? 'selected' : '' }}>Kashmore Gate</option>
                        <option value="Navagam" {{ old('office') == 'Navagam' ? 'selected' : '' }}>Navagam</option>
                        <option value="Dayabasti" {{ old('office') == 'Dayabasti' ? 'selected' : '' }}>Dayabasti</option>
                        <option value="Swarup Nagar" {{ old('office') == 'Swarup Nagar' ? 'selected' : '' }}>Swarup Nagar</option>
                        <option value="Shapar (1)" {{ old('office') == 'Shapar (1)' ? 'selected' : '' }}>Shapar (1)</option>
                        <option value="Shapar (2)" {{ old('office') == 'Shapar (2)' ? 'selected' : '' }}>Shapar (2)</option>
                    </select>
                    <label for="office">Office Branch</label>
                    <i class="bi bi-chevron-down select-arrow"></i>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <i class="bi bi-lock-fill input-icon"></i>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder=" "
                            required
                            autocomplete="new-password"
                        >
                        <label for="password">Password</label>
                    </div>

                    <div class="form-group">
                        <i class="bi bi-lock-fill input-icon"></i>
                        <input
                            type="password"
                            id="password_confirmation"
                            name="password_confirmation"
                            placeholder=" "
                            required
                            autocomplete="new-password"
                        >
                        <label for="password_confirmation">Confirm Password</label>
                    </div>
                </div>

                <div class="password-requirements">
                    <i class="bi bi-info-circle"></i> Password must be at least 8 characters
                </div>

                <div style="margin-top: 30px;">
                    <button type="submit" class="btn-register">
                        <span>Create Account</span>
                        <i class="bi bi-arrow-right"></i>
                    </button>
                </div>
            </form>

            @if (Route::has('login'))
                <div class="divider"><span>or</span></div>

                <p class="login-prompt">
                    Already have an account? <a href="{{ route('login') }}">Sign In</a>
                </p>
            @endif
        </div>
    </div>
</div>
@endsection