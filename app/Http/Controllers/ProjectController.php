<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function index()
    {
        $projects = Project::latest()->paginate(10);
        return view('projects.index', compact('projects'));
    }

    public function create()
    {
        $customers = \App\Models\Customer::all();
        return view('projects.create', compact('customers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'status' => 'required|in:Ongoing,Prospect,Complete,Cancel',
            'start_date' => 'required|date',
            'finish_date' => 'nullable|date', // Sekarang opsional
            'customer_id' => 'required|exists:customers,id',
        ]);

        Project::create($validated);

        return redirect()->route('projects.index')
            ->with('success', 'Project berhasil dibuat.');
    }

    public function show(Project $project)
    {
        $project->load('activities');
        return view('projects.show', compact('project'));
    }

    public function edit(Project $project)
    {
        $customers = \App\Models\Customer::all();
        return view('projects.edit', compact('project', 'customers'));
    }

    public function update(Request $request, Project $project)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'status' => 'required|in:Ongoing,Prospect,Complete,Cancel',
            'start_date' => 'required|date',
            'finish_date' => 'nullable|date', // Sekarang opsional
            'customer_id' => 'required|exists:customers,id',
        ]);

        $project->update($validated);

        return redirect()->route('projects.show', $project)
            ->with('success', 'Project berhasil diperbarui.');
    }

    public function destroy(Project $project)
    {
        $project->delete();

        return redirect()->route('projects.index')
            ->with('success', 'Project berhasil dihapus.');
    }
} 