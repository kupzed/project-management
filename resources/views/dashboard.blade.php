@extends('layouts.app')

@section('header')
    <div class="flex w-full md:ml-0">
        <h1 class="text-2xl font-semibold text-gray-900">Dashboard</h1>
    </div>
@endsection 

@section('content')
<div class="container mx-auto">
    <div class="py-4">
        {{-- <div class="max-w-7xl mx-auto mt-5">
            <h1 class="text-2xl font-semibold text-gray-900">Dashboard</h1>
        </div> --}}
        <div class="max-w-7xl mx-auto">
            <!-- Recent Projects -->
            <div class="my-2">
                <h2 class="text-lg leading-6 font-medium text-gray-900">Project Terbaru</h2>
                <div class="mt-4 bg-white shadow overflow-hidden sm:rounded-md">
                    <ul class="divide-y divide-gray-200">
                        @forelse($projects as $project)
                        <li>
                            <a href="{{ route('projects.show', $project) }}" class="block hover:bg-gray-50">
                                <div class="px-4 py-4 sm:px-6">
                                    <div class="flex items-center justify-between">
                                        <p class="text-sm font-medium text-indigo-600 truncate">
                                            {{ $project->name }}
                                        </p>
                                        <div class="ml-2 flex-shrink-0 flex">
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
                                    <div class="mt-2 sm:flex sm:justify-between">
                                        <div class="sm:flex">
                                            <p class="flex items-center text-sm text-gray-500">
                                                Customer: {{ $project->customer->nama }} | Deskripsi: {{ Str::limit($project->description, 100) }}
                                            </p>
                                        </div>
                                        <div class="mt-2 flex items-center text-sm text-gray-500 sm:mt-0">
                                            <svg class="flex-shrink-0 mr-1.5 h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd" />
                                            </svg>
                                            <p>
                                                Mulai: {{ $project->start_date->format('d F Y') }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </li>
                        @empty
                        <li class="px-4 py-4 sm:px-6">
                            <p class="text-sm text-gray-500">Belum ada project</p>
                        </li>
                        @endforelse
                    </ul>
                </div>
            </div>

            <!-- Recent Activities -->
            <div class="mt-8">
                <h2 class="text-lg leading-6 font-medium text-gray-900">Aktivitas Terbaru</h2>
                <div class="mt-4 bg-white shadow overflow-hidden sm:rounded-md">
                    <ul class="divide-y divide-gray-200">
                        @forelse($activities as $activity)
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
                                                Project: {{ $activity->project->name }}
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
</div>
@endsection