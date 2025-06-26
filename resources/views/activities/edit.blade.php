@extends('layouts.app')

@section('header')
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold text-gray-900">Edit Activity</h1>
    </div>
@endsection 

@section('content')
<div class="flex min-h-full flex-col justify-center px-6 py-12 lg:px-8">
    <div class="sm:mx-auto sm:w-full sm:max-w-sm">
        <h2 class="text-center text-2xl font-bold tracking-tight text-gray-900">
            Form Edit Aktivitas
        </h2>
        @if(isset($activity->project))
            <h2 class="text-center text-2xl font-bold tracking-tight text-gray-900">
                Untuk Project : {{ $activity->project->name }}
            </h2>
        @endif
    </div>

    <div class="mt-10 sm:mx-auto sm:w-full sm:max-w-sm">
        <form class="space-y-6" action="{{ route('activities.update', $activity) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            
            <div>
                <label for="name" class="block text-sm font-medium text-gray-900">Nama Aktivitas</label>
                <div class="mt-2">
                    <input type="text" name="name" id="name" value="{{ old('name', $activity->name) }}" required class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm" />
                </div>
                @error('name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            @if(isset($activity->project))
                <input type="hidden" name="project_id" value="{{ $activity->project_id }}">
            @else
                <label for="project_id" class="block text-sm font-medium text-gray-900">Project</label>
                <div class="mt-2">
                    <select id="project_id" name="project_id" required class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm">
                        <option value="">Pilih Project</option>
                        @foreach($projects as $project)
                            <option value="{{ $project->id }}" {{ old('project_id', $activity->project_id) == $project->id ? 'selected' : '' }}>
                                {{ $project->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @error('project_id')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            @endif

            <div>
                <label for="kategori" class="block text-sm font-medium text-gray-900">Kategori</label>
                <div class="mt-2">
                    <select id="kategori" name="kategori" required class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm">
                        <option value="">Pilih Kategori</option>
                        <option value="Expense Report" {{ old('kategori', $activity->kategori) == 'Expense Report' ? 'selected' : '' }}>Expense Report</option>
                        <option value="Invoice" {{ old('kategori', $activity->kategori) == 'Invoice' ? 'selected' : '' }}>Invoice</option>
                        <option value="Purchase Order" {{ old('kategori', $activity->kategori) == 'Purchase Order' ? 'selected' : '' }}>Purchase Order</option>
                        <option value="Payment" {{ old('kategori', $activity->kategori) == 'Payment' ? 'selected' : '' }}>Payment</option>
                        <option value="Quotation" {{ old('kategori', $activity->kategori) == 'Quotation' ? 'selected' : '' }}>Quotation</option>
                        <option value="Faktur Pajak" {{ old('kategori', $activity->kategori) == 'Faktur Pajak' ? 'selected' : '' }}>Faktur Pajak</option>
                        <option value="Kasbon" {{ old('kategori', $activity->kategori) == 'Kasbon' ? 'selected' : '' }}>Kasbon</option>
                        <option value="Laporan Teknis" {{ old('kategori', $activity->kategori) == 'Laporan Teknis' ? 'selected' : '' }}>Laporan Teknis</option>
                        <option value="Surat Masuk" {{ old('kategori', $activity->kategori) == 'Surat Masuk' ? 'selected' : '' }}>Surat Masuk</option>
                        <option value="Surat Keluar" {{ old('kategori', $activity->kategori) == 'Surat Keluar' ? 'selected' : '' }}>Surat Keluar</option>
                    </select>
                </div>
                @error('kategori')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="description" class="block text-sm font-medium text-gray-900">Deskripsi</label>
                <div class="mt-2">
                    <textarea id="description" name="description" rows="4" required class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm">{{ old('description', $activity->description) }}</textarea>
                </div>
                @error('description')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="activity_date" class="block text-sm font-medium text-gray-900">Tanggal Aktivitas</label>
                <div class="mt-2">
                    <input type="date" name="activity_date" id="activity_date" value="{{ old('activity_date', $activity->activity_date ? $activity->activity_date->format('Y-m-d') : '') }}" required class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm" />
                </div>
                @error('activity_date')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="col-span-full">
                <label for="upload-file" class="block text-sm/6 font-medium text-gray-900">Attachment File</label>
                <div class="mt-2 flex justify-center rounded-lg border border-dashed border-gray-900/25 px-6 py-10">
                    <div class="text-center">
                        <svg class="mx-auto size-12 text-gray-300" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" data-slot="icon">
                            <path fill-rule="evenodd" d="M1.5 6a2.25 2.25 0 0 1 2.25-2.25h16.5A2.25 2.25 0 0 1 22.5 6v12a2.25 2.25 0 0 1-2.25 2.25H3.75A2.25 2.25 0 0 1 1.5 18V6ZM3 16.06V18c0 .414.336.75.75.75h16.5A.75.75 0 0 0 21 18v-1.94l-2.69-2.689a1.5 1.5 0 0 0-2.12 0l-.88.879.97.97a.75.75 0 1 1-1.06 1.06l-5.16-5.159a1.5 1.5 0 0 0-2.12 0L3 16.061Zm10.125-7.81a1.125 1.125 0 1 1 2.25 0 1.125 1.125 0 0 1-2.25 0Z" clip-rule="evenodd" />
                        </svg>
                        <div class="mt-4 flex text-sm/6 text-gray-600">
                            <label for="file-upload" class="relative cursor-pointer rounded-md bg-white font-semibold text-indigo-600 focus-within:ring-2 focus-within:ring-indigo-600 focus-within:ring-offset-2 focus-within:outline-hidden hover:text-indigo-500">
                                <span>Upload a file</span>
                                <input id="file-upload" name="attachment" type="file" class="sr-only" />
                            </label>
                            <p class="pl-1">or drag and drop</p>
                        </div>
                        <p id="file-name" class="text-xs/5 text-gray-600">
                            @if($activity->attachment)
                                <a href="{{ asset('storage/' . $activity->attachment) }}" target="_blank" class="text-indigo-600 hover:underline">{{ basename($activity->attachment) }}</a>
                            @endif
                        </p>
                        <p class="text-xs/5 text-gray-600">PNG, JPG, GIF up to 10MB</p>
                    </div>
                </div>
            </div>
            <script>
                const fileInput = document.getElementById('file-upload');
                const fileName = document.getElementById('file-name');
                const dropArea = fileInput.closest('.flex.justify-center');
                dropArea.addEventListener('dragover', (e) => {
                    e.preventDefault();
                    dropArea.classList.add('ring-2', 'ring-indigo-600');
                });
                dropArea.addEventListener('dragleave', (e) => {
                    e.preventDefault();
                    dropArea.classList.remove('ring-2', 'ring-indigo-600');
                });
                dropArea.addEventListener('drop', (e) => {
                    e.preventDefault();
                    dropArea.classList.remove('ring-2', 'ring-indigo-600');
                    if (e.dataTransfer.files.length) {
                        fileInput.files = e.dataTransfer.files;
                        fileName.textContent = e.dataTransfer.files[0].name;
                    }
                });
                fileInput.addEventListener('change', (e) => {
                    if (fileInput.files.length) {
                        fileName.textContent = fileInput.files[0].name;
                    }
                });
            </script>

            <div class="flex items-center justify-between">
                <a href="{{ url()->previous() }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    <svg class="-ml-1 mr-2 h-5 w-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Kembali
                </a>
                <button type="submit" class="flex justify-center rounded-md bg-indigo-600 px-3 py-1.5 text-sm font-semibold text-white shadow-xs hover:bg-indigo-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                    <svg class="-ml-1 mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    Update Aktivitas
                </button>
            </div>
        </form>
    </div>
</div>
@endsection 