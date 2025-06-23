@extends('layouts.app')

@section('header')
    <div class="flex w-full md:ml-0">
        <h1 class="text-2xl font-semibold text-gray-900">Detail Customer</h1>
    </div>
@endsection

@section('content')
<div class="container mx-auto">
    <div class="max-w-3xl mx-auto">
        <div class="md:flex md:items-center md:justify-between mb-4">
            <div class="flex-1 min-w-0">
                <h2 class="text-2xl font-bold leading-7 text-gray-900 mt-4 sm:text-2xl sm:truncate">
                    {{ $customer->nama }}
                </h2>
                <div class="mt-1 flex flex-col sm:flex-row sm:flex-wrap sm:mt-0 sm:space-x-6">
                    <div class="mt-2 flex items-center text-sm text-gray-500">
                        <span class="inline-flex rounded-full px-2 text-xs font-semibold leading-5 
                            @if($customer->kategori === 'perusahaan') bg-blue-100 text-blue-800
                            @else bg-green-100 text-green-800
                            @endif"> {{ ucfirst($customer->kategori) }}
                        </span>
                    </div>
                </div>
            </div>
            <div class="mt-2 flex md:mt-0 md:ml-4">
                <a href="{{ route('customers.edit', $customer) }}" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Edit Customer
                </a>
            </div>
        </div>

        <div class="bg-white shadow overflow-hidden sm:rounded-lg">
            <div class="px-4 py-5 sm:px-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900">Informasi Customer</h3>
            </div>
            <div class="border-t border-gray-200">
                <dl>
                    <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Alamat</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                            {{ $customer->alamat }}
                        </dd>
                    </div>
                    <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Website</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                            {{ $customer->website ?? '-' }}
                        </dd>
                    </div>
                    <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Email</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                            {{ $customer->email }}
                        </dd>
                    </div>
                    <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Kontak 1</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                            {{ $customer->kontak_1 }}<br>
                            <span class="text-xs text-gray-500">Nama: {{ $customer->kontak_1_nama }}, Jabatan: {{ $customer->kontak_1_jabatan }}</span>
                        </dd>
                    </div>
                    <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Kontak 2</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                            @if($customer->kontak_2)
                                {{ $customer->kontak_2 }}<br>
                                <span class="text-xs text-gray-500">Nama: {{ $customer->kontak_2_nama }}, Jabatan: {{ $customer->kontak_2_jabatan }}</span>
                            @else
                                -
                            @endif
                        </dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>
</div>
@endsection 