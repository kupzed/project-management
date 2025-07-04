@extends('layouts.app')

@section('header')
    <div class="flex w-full md:ml-0">
        <h1 class="text-2xl font-semibold text-gray-900">Detail Project</h1>
    </div>
@endsection 

@section('content')
<div class="container mx-auto">
    <div class="max-w-3xl mx-auto">
        <div class="flex justify-between items-center">
            <div class="flex-1 min-w-0">
                <h2 class="text-2xl font-bold leading-7 text-gray-900 my-2 sm:text-2xl">
                    {{ $project->name }}
                </h2>
                <div class="my-2 flex flex-col sm:flex-row sm:flex-wrap sm:mt-0 sm:space-x-6">
                    <div class="my-2 flex items-center text-sm text-gray-500">
                        <svg class="flex-shrink-0 mr-1.5 h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd" />
                        </svg>
                        Mulai: {{ $project->start_date->format('d F Y') }}
                    </div>
                    <div class="my-2 flex items-center text-sm text-gray-500">
                        <span class="inline-flex rounded-full px-2 text-xs font-semibold leading-5 
                            @if($project->status === 'Complete') bg-green-100 text-green-800
                            @elseif($project->status === 'Ongoing') bg-blue-100 text-blue-800
                            @elseif($project->status === 'Prospect') bg-yellow-100 text-yellow-800
                            @elseif($project->status === 'Cancel') bg-red-100 text-red-800
                            @else bg-grey-100 text-grey-800
                            @endif"> {{ $project->status }}
                        </span>
                    </div>
                </div>
            </div>
            <div class="mt-2 flex md:mt-0 md:ml-4">
                <a href="{{ route('projects.edit', $project) }}" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Edit Project
                </a>
            </div>
        </div>

        <div class="bg-white shadow overflow-hidden sm:rounded-lg">
            <div class="px-4 py-5 sm:px-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900">Informasi Project</h3>
            </div>
            <div class="border-t border-gray-200">
                <dl>
                    <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Nama Project</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                            {{ $project->name }}
                        </dd>
                    </div>
                    <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Customer</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                            @if($project->customer)
                                <a href="{{ route('customers.show', $project->customer) }}" class="text-indigo-600 hover:underline">{{ $project->customer->nama }}</a>
                            @else
                                -
                            @endif
                        </dd>
                    </div>
                    <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Deskripsi</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                            {{ $project->description }}
                        </dd>
                    </div>
                    <div class="bg-white-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Status</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                            <span class="inline-flex rounded-full px-2 text-xs font-semibold leading-5 
                                @if($project->status === 'Complete') bg-green-100 text-green-800
                                @elseif($project->status === 'Ongoing') bg-blue-100 text-blue-800
                                @elseif($project->status === 'Prospect') bg-yellow-100 text-yellow-800
                                @else bg-red-100 text-red-800
                                @endif"> {{ $project->status }}
                            </span>
                        </dd>
                    </div>
                    <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Tanggal Mulai</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                            {{ $project->start_date->format('d F Y') }}
                        </dd>
                    </div>
                    <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Tanggal Selesai</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                            @if ($project->finish_date)
                                {{ $project->finish_date->format('d F Y') }}
                            @else
                                Tanggal belum ditambahkan
                            @endif
                        </dd>
                    </div>
                </dl>
            </div>
        </div>

        <!-- Activities Section -->
        <div class="mt-8">
            <div class="flex justify-between items-center">
                <h3 class="text-lg leading-6 font-medium text-gray-900">
                    Aktivitas Project
                </h3>
                <a href="{{ route('activities.create', ['project_id' => $project->id]) }}" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Tambah Aktivitas
                </a>
            </div>

            <div class="mt-4 bg-white shadow overflow-hidden sm:rounded-md">
                <ul class="divide-y divide-gray-200">
                    @forelse($project->activities as $activity)
                    <li>
                        <a href="{{ route('activities.show', $activity) }}" class="block hover:bg-gray-50">
                        <div class="px-4 py-4 sm:px-6">
                            <div class="flex items-center justify-between">
                                <p class="text-sm font-medium text-indigo-600 truncate">
                                    {{ $activity->name }}
                                </p>
                                <div class="ml-2 flex-shrink-0 flex">
                                    <span class="inline-flex rounded-full px-2 text-xs font-semibold leading-5 bg-gray-300 text-grey-900">
                                        {{ $activity->kategori }}
                                    </span>
                                </div>
                            </div>
                            <div class="mt-2 sm:flex sm:justify-between">
                                <div class="sm:flex">
                                    <p class="flex items-center text-sm text-gray-500">
                                        {{ $activity->description }}
                                    </p>
                                </div>
                                <div class="mt-2 flex items-center text-sm text-gray-500 sm:mt-0">
                                    <svg class="flex-shrink-0 mr-1.5 h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd" />
                                    </svg>
                                    <p>
                                        Aktivitas: {{ $activity->activity_date->format('d F Y') }}
                                    </p>
                                </div>
                            </div>
                        </div>
                        </a>
                    </li>
                    @empty
                    <li class="px-4 py-4 sm:px-6">
                        <p class="text-sm text-gray-500">Belum ada aktivitas</p>
                    </li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection 