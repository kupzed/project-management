@extends('layouts.app')

@section('header')
    <div class="flex w-full md:ml-0">
        <h1 class="text-2xl font-semibold text-gray-900">Activity</h1>
    </div>
@endsection 

@section('content')
@if(isset($activity))
<div class="container mx-auto">
    <div class="max-w-3xl mx-auto">
        <div class="md:flex md:items-center md:justify-between mb-4">
            <div class="flex-1 min-w-0">
                <h2 class="text-2xl font-bold leading-7 text-gray-900 mt-4 sm:text-2xl sm:truncate">
                    {{ $activity->name }}
                </h2>
                <div class="mt-1 flex flex-col sm:flex-row sm:flex-wrap sm:mt-0 sm:space-x-6">
                    <div class="mt-2 flex items-center text-sm text-gray-500">
                        <span class="inline-flex rounded-full px-2 text-xs font-semibold leading-5 
                            @if($activity->status === 'completed') bg-emerald-100 text-emerald-800 
                            @elseif($activity->status === 'pending') bg-rose-100 text-rose-800 
                            @else bg-amber-100 text-amber-800 
                            @endif "> {{ $activity->status }}
                        </span>
                    </div>
                    <div class="mt-2 flex items-center text-sm text-gray-500">
                        <svg class="flex-shrink-0 mr-1.5 h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd" />
                        </svg>
                        Aktivitas: {{ $activity->due_date->format('d F Y') }}
                    </div>
                </div>
            </div>
            <div class="mt-4 flex md:mt-0 md:ml-4">
                <a href="{{ route('activities.edit', $activity) }}" class="ml-3 inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Edit Aktivitas
                </a>
            </div>
        </div>

        <div class="bg-white shadow overflow-hidden sm:rounded-lg">
            <div class="px-4 py-5 sm:px-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900">Informasi Aktivitas</h3>
            </div>
            <div class="border-t border-gray-200">
                <dl>
                    <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Project</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                            @if($activity->project)
                            <a href="{{ route('projects.show', $activity->project) }}" class="text-indigo-600 hover:text-indigo-900">
                                {{ $activity->project->name }}
                            </a>
                            @else
                            <span class="text-gray-500">Project tidak ditemukan</span>
                            @endif
                        </dd>
                    </div>
                    <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Deskripsi</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                            {{ $activity->description }}
                        </dd>
                    </div>
                    <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Status</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                            <span class="inline-flex rounded-full px-2 text-xs font-semibold leading-5 
                                @if($activity->status === 'completed') bg-emerald-100 text-emerald-800 
                                @elseif($activity->status === 'pending') bg-rose-100 text-rose-800 
                                @else bg-amber-100 text-amber-800 
                                @endif "> {{ $activity->status }}
                            </span>
                        </dd>
                    </div>
                    <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Tanggal Aktivitas</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                            {{ $activity->due_date->format('d F Y') }}
                        </dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>
</div>
@else
<div class="container mx-auto px-4 sm:px-6 lg:px-8">
    <div class="max-w-3xl mx-auto">
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
            <strong class="font-bold">Error!</strong>
            <span class="block sm:inline">Data aktivitas tidak ditemukan.</span>
        </div>
    </div>
</div>
@endif
@endsection 