<!DOCTYPE html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Masuk · SIPKPA Propindo</title>@vite(['resources/css/app.css', 'resources/js/app.js'])</head>
<body class="min-h-screen bg-slate-50 text-slate-900"><main class="grid min-h-screen lg:grid-cols-2">
    <section class="relative hidden flex-col justify-between overflow-hidden bg-teal-950 p-14 text-white lg:flex"><div class="absolute -right-40 -bottom-40 size-160 rounded-full border border-teal-800" aria-hidden="true"></div><p class="relative text-lg font-bold tracking-widest">SIPKPA PROPINDO</p><div class="relative max-w-lg"><p class="mb-6 text-xs font-semibold tracking-[.25em] text-teal-300 uppercase">Pendidikan · Evaluasi · Prediksi</p><h1 class="text-5xl leading-tight font-semibold tracking-tight">Mendampingi langkah menuju profesi advokat.</h1><p class="mt-6 text-lg leading-relaxed text-teal-100/75">Kelola akademik dan identifikasi kebutuhan pendampingan peserta melalui analisis Random Forest.</p></div><p class="relative text-xs text-teal-200/60">Advokat Propindo · Pendidikan Khusus Profesi Advokat</p></section>
    <section class="flex items-center justify-center p-7"><div class="w-full max-w-sm"><p class="mb-10 text-sm font-bold tracking-widest text-teal-800 lg:hidden">SIPKPA PROPINDO</p><span class="rounded-full bg-teal-100 px-3 py-1 text-xs font-semibold text-teal-800">Portal Akademik</span><h2 class="mt-6 text-3xl font-bold tracking-tight">Selamat datang kembali</h2><p class="mt-3 text-sm text-slate-500">Masuk menggunakan akun resmi Advokat Propindo.</p>
        @if ($errors->any())<div role="alert" class="mt-6 rounded-xl bg-rose-50 p-4 text-sm text-rose-800">{{ $errors->first() }}</div>@endif
        <form method="POST" action="{{ route('login.store') }}" class="mt-8 grid gap-5">@csrf
            <x-field name="email" label="Email" type="email" autocomplete="username" autofocus />
            <x-field name="password" label="Kata sandi" type="password" autocomplete="current-password" />
            <label class="flex items-center gap-2 text-sm text-slate-600"><input type="checkbox" name="remember" value="1" @checked(old('remember')) class="size-4 accent-teal-700">Ingat saya</label>
            <button type="submit" class="btn w-full">Masuk ke dasbor →</button>
        </form><p class="mt-8 text-center text-xs leading-relaxed text-slate-400">Akses sesuai peran Admin Akademik atau Manajemen.<br>Hubungi administrator jika mengalami kendala akun.</p>
    </div></section>
</main></body></html>
