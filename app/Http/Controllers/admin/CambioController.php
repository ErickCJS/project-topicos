<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CambioController extends Controller
{
    public function index()
    {
        return view('admin.change-management.cambios.index');
    }

    public function create()
    {
        return redirect()->route('cambios.index');
    }

    public function store(Request $request)
    {
        return redirect()->route('cambios.index')->with('success', 'Cambio registrado correctamente.');
    }

    public function show(string $id)
    {
        return redirect()->route('cambios.index');
    }

    public function edit(string $id)
    {
        return redirect()->route('cambios.index');
    }

    public function update(Request $request, string $id)
    {
        return redirect()->route('cambios.index')->with('success', 'Cambio actualizado correctamente.');
    }

    public function destroy(string $id)
    {
        return redirect()->route('cambios.index')->with('success', 'Cambio eliminado correctamente.');
    }
}
