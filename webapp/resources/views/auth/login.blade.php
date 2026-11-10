<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk · SIPKPA Propindo</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="login-page">
    <main class="login-workspace">
        <section class="login-identity" aria-labelledby="login-product-title">
            <div class="login-identity-body">
                <h1 id="login-product-title">SIPKPA</h1>
                <p class="login-identity-description">Sistem Prediksi Kelulusan Peserta PKPA</p>
                <p class="login-organization">Advokat Propindo</p>
            </div>
        </section>

        <section class="login-form-area" aria-labelledby="login-title">
            <div class="login-form-container">
                <h2 id="login-title">Masuk ke akun</h2>
                <p class="login-description">Masukkan email dan kata sandi untuk melanjutkan.</p>
                @if ($errors->any())
                    <div role="alert" class="login-error">{{ $errors->first() }}</div>
                @endif

                <form method="POST" action="{{ route('login.store') }}" class="login-form">
                    @csrf
                    <x-field name="email" label="Email" type="email" autocomplete="username" inputmode="email" autocapitalize="none" spellcheck="false" autofocus />
                    <x-field name="password" label="Kata sandi" type="password" autocomplete="current-password" />
                    <label for="remember" class="login-remember">
                        <input id="remember" type="checkbox" name="remember" value="1" @checked(old('remember')) class="size-4 accent-teal-700"> Ingat saya
                    </label>
                    <button type="submit" class="btn w-full">Masuk</button>
                </form>
                <p class="login-help">Hubungi administrator jika Anda mengalami kendala akses.</p>
            </div>
        </section>
    </main>
</body>
</html>
