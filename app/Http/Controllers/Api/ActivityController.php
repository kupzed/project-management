<?php

namespace App\Http\Controllers\Api;

use App\Models\Activity;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;

class ActivityController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Activity::query();

        if ($request->has('id_project')) {
            $query->where('id_project', $request->id_project);
        }

        if ($request->has('tanggal')) {
            $query->whereDate('tanggal', $request->tanggal);
        }

        $activities = $query->with('project')->orderBy('tanggal', 'desc')->get();

        return response()->json($activities);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id_project' => 'required|exists:projects,id_project',
            'id_kategori' => 'required|string|max:255',
            'tanggal' => 'required|date',
            'keterangan' => 'required|string'
        ]);

        $activity = Activity::create($validated);

        return response()->json($activity, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Activity $activity): JsonResponse
    {
        return response()->json($activity->load('project'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Activity $activity): JsonResponse
    {
        $validated = $request->validate([
            'id_project' => 'exists:projects,id_project',
            'id_kategori' => 'string|max:255',
            'tanggal' => 'date',
            'keterangan' => 'string'
        ]);

        $activity->update($validated);

        return response()->json($activity);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Activity $activity): JsonResponse
    {
        $activity->delete();
        return response()->json(null, 204);
    }
}
