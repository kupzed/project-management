@extends('layouts.app')

@section('header')
    <div class="flex w-full md:ml-0">
        <h1 class="text-2xl font-semibold text-gray-900">Activity</h1>
    </div>
@endsection

@section('content')
<div class="container mx-auto">
    <div class="py-4">
        <div class="max-w-7xl mx-auto">
            <div class="flex justify-between items-center">
                <h1 class="text-2xl font-semibold text-gray-900">Daftar Aktivitas</h1>
                <a href="{{ route('activities.create') }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Tambah Aktivitas
                </a>
            </div>
        </div>
        <div class="max-w-7xl">
            <div class="mt-8">
                <div class="bg-white shadow overflow-hidden sm:rounded-md">
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
                                            <span class="inline-flex rounded-full px-2 text-xs font-semibold leading-5 
                                                @if($activity->status === 'completed') bg-green-100 text-green-800 
                                                @elseif($activity->status === 'pending') bg-red-100 text-red-800 
                                                @elseif($activity->status === 'in-progress') bg-yellow-100 text-yellow-800 
                                                @else bg-grey-100 text-grey-800 
                                                @endif "> {{ $activity->status }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="mt-2 sm:flex sm:justify-between">
                                        <div class="sm:flex">
                                            <p class="flex items-center text-sm text-gray-500">
                                                {{ $activity->project->name }}
                                            </p>
                                        </div>
                                        <div class="mt-2 flex items-center text-sm text-gray-500 sm:mt-0">
                                            <svg class="flex-shrink-0 mr-1.5 h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd" />
                                            </svg>
                                            <p>
                                                Aktivitas: {{ $activity->due_date->format('d F Y') }}
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