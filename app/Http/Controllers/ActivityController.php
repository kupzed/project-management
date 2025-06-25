<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ActivityController extends Controller
{
    public function index()
    {
        $activities = Activity::with('project')->latest()->paginate(10);
        return view('activities.index', compact('activities'));
    }

    public function create()
    {
        $projects = Project::all();
        return view('activities.create', compact('projects'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'project_id' => 'required|exists:projects,id',
            'kategori' => 'required|in:Expense Report,Invoice,Purchase Order,Payment,Quotation,Faktur Pajak,Kasbon,Laporan Teknis,Surat Masuk,Surat Keluar',
            'activity_date' => 'required|date',
            'attachment' => 'nullable|file|max:10240', // 10MB
        ]);

        if ($request->hasFile('attachment')) {
            $validated['attachment'] = $request->file('attachment')->store('attachments', 'public');
        }

        Activity::create($validated);

        return redirect()->route('activities.index')
            ->with('success', 'Aktivitas berhasil dibuat.');
    }

    public function show(Activity $activity)
    {
        try {
            $activity->load('project');
            Log::info('Activity Data:', ['activity' => $activity->toArray()]);
            return view('activities.show', compact('activity'));
        } catch (\Exception $e) {
            Log::error('Error showing activity: ' . $e->getMessage());
            return redirect()->route('activities.index')
                ->with('error', 'Terjadi kesalahan saat menampilkan aktivitas.');
        }
    }

    public function edit(Activity $activity)
    {
        $projects = Project::all();
        return view('activities.edit', compact('activity', 'projects'));
    }

    public function update(Request $request, Activity $activity)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'project_id' => 'required|exists:projects,id',
            'kategori' => 'required|in:Expense Report,Invoice,Purchase Order,Payment,Quotation,Faktur Pajak,Kasbon,Laporan Teknis,Surat Masuk,Surat Keluar',
            'activity_date' => 'required|date',
            'attachment' => 'nullable|file|max:10240', // 10MB
        ]);

        if ($request->hasFile('attachment')) {
            // Hapus file lama jika ada
            if ($activity->attachment) {
                Storage::disk('public')->delete($activity->attachment);
            }
            $validated['attachment'] = $request->file('attachment')->store('attachments', 'public');
        }

        $activity->update($validated);

        return redirect()->route('activities.show', $activity)
            ->with('success', 'Aktivitas berhasil diperbarui.');
    }

    public function destroy(Activity $activity)
    {
        $activity->delete();

        return redirect()->route('activities.index')
            ->with('success', 'Aktivitas berhasil dihapus.');
    }
} 