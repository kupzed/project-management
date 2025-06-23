<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index()
    {
        $customers = Customer::latest()->paginate(10);
        return view('customers.index', compact('customers'));
    }

    public function create()
    {
        return view('customers.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:255',
            'kategori' => 'required|in:pribadi,perusahaan',
            'alamat' => 'required',
            'website' => 'nullable|string|max:255',
            'email' => 'required|email|max:255',
            'kontak_1' => 'required|string|max:255',
            'kontak_1_nama' => 'required|string|max:255',
            'kontak_1_jabatan' => 'required|string|max:255',
            'kontak_2_nama' => 'nullable|string|max:255',
            'kontak_2' => 'nullable|string|max:255',
            'kontak_2_jabatan' => 'nullable|string|max:255',
        ]);
        Customer::create($validated);
        return redirect()->route('customers.index')->with('success', 'Customer berhasil ditambahkan.');
    }

    public function show(Customer $customer)
    {
        return view('customers.show', compact('customer'));
    }

    public function edit(Customer $customer)
    {
        return view('customers.edit', compact('customer'));
    }

    public function update(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:255',
            'kategori' => 'required|in:pribadi,perusahaan',
            'alamat' => 'required',
            'website' => 'nullable|string|max:255',
            'email' => 'required|email|max:255',
            'kontak_1' => 'required|string|max:255',
            'kontak_1_nama' => 'required|string|max:255',
            'kontak_1_jabatan' => 'required|string|max:255',
            'kontak_2_nama' => 'nullable|string|max:255',
            'kontak_2' => 'nullable|string|max:255',
            'kontak_2_jabatan' => 'nullable|string|max:255',
        ]);
        $customer->update($validated);
        return redirect()->route('customers.index')->with('success', 'Customer berhasil diupdate.');
    }

    public function destroy(Customer $customer)
    {
        $customer->delete();
        return redirect()->route('customers.index')->with('success', 'Customer berhasil dihapus.');
    }
} 