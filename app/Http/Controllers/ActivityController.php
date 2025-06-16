<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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
            'status' => 'required|in:pending,in-progress,completed',
            'due_date' => 'required|date',
        ]);

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
            'status' => 'required|in:pending,in-progress,completed',
            'due_date' => 'required|date',
        ]);

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