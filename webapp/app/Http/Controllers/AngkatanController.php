<?php

namespace App\Http\Controllers;

use App\Http\Requests\AngkatanRequest;
use App\Models\AngkatanPkpa;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

class AngkatanController extends Controller
{
    public function index(): View
    {
        return view('angkatan.index', ['angkatan' => AngkatanPkpa::withCount('peserta')->latest('tanggal_mulai')->paginate(15)]);
    }

    public function create(): View
    {
        return view('angkatan.form', ['angkatan' => new AngkatanPkpa]);
    }

    public function store(AngkatanRequest $request): RedirectResponse
    {
        AngkatanPkpa::create($request->validated());

        return to_route('angkatan.index')->with('success', 'Angkatan berhasil ditambahkan.');
    }

    public function edit(AngkatanPkpa $angkatan): View
    {
        return view('angkatan.form', compact('angkatan'));
    }

    public function update(AngkatanRequest $request, AngkatanPkpa $angkatan): RedirectResponse
    {
        $angkatan->update($request->validated());

        return to_route('angkatan.index')->with('success', 'Angkatan berhasil diperbarui.');
    }

    public function destroy(AngkatanPkpa $angkatan): RedirectResponse
    {
        if ($angkatan->peserta()->exists()) {
            throw ValidationException::withMessages(['angkatan' => 'Angkatan yang masih memiliki peserta tidak dapat dihapus.']);
        }
        $angkatan->delete();

        return to_route('angkatan.index')->with('success', 'Angkatan berhasil dihapus.');
    }
}
