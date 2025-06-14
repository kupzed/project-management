<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Project;
use App\Models\Activity;

class DashboardController extends Controller
{
    public function index()
    {
        $projects = Project::latest()->take(5)->get();
        $activities = Activity::with('project')->latest()->take(5)->get();
        
        return view('dashboard', compact('projects', 'activities'));
    }
}