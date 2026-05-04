<?php

namespace App\Http\Controllers;

use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ReportController extends Controller
{
    /**
     * Show the reports page.
     */
    public function index()
    {
        $student = Auth::user();

        $internship = $student->internships()->first();

        $reports = Report::where('student_id', $student->id)
            ->where('internship_id', $internship->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('student.reports.index', [
            'reports' => $reports,
            'internship' => $internship,
            'totalReports' => $reports->count(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'internship_id' => 'required|exists:internships,id',
            'report_file' => 'required|file|max:5120|mimes:pdf,docx,txt',
        ]);

        $student = Auth::user();
        $file = $request->file('report_file');
        $filename = Str::uuid() . '.' . $file->extension();
        $path = $file->storeAs('reports', $filename, 'public');



        Report::create([
            'student_id' => $student->id,
            'internship_id' => $request->internship_id,
            'file_path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'status' => 'pending',
            'coordinator_reviewed_by' => null,
            'created_at' => now(),
        ]);

        return back()->with('success', 'Report submitted successfully.');
    }
}
