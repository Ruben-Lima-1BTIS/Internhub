<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Company;
use App\Models\ClassModel;
use App\Models\Internship;
use App\Models\User;
use App\Models\UserClass;
use App\Models\UserInternship;
use Illuminate\Support\Facades\Hash;
use App\Mail\UserCreatedMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Rules\StrongPassword;

class HRController extends Controller
{

    public function create()
    {
        return view('hr.create-records', [
            'coordinators' => User::where('role', User::ROLE_COORDINATOR)->get(),
            'companies' => Company::all(),
            'classes' => ClassModel::all(),
            'supervisors' => User::where('role', User::ROLE_SUPERVISOR)->get(),
            'students' => User::where('role', User::ROLE_STUDENT)->get(),
            'internships' => Internship::all(),
        ]);
    }

    public function delete()
    {
        return view('hr.delete-records', [
            'coordinators' => User::where('role', User::ROLE_COORDINATOR)->get(),
            'supervisors' => User::where('role', User::ROLE_SUPERVISOR)->get(),
            'students' => User::where('role', User::ROLE_STUDENT)
                ->with(['userClass', 'internships'])
                ->get(),
            'companies' => Company::all(),
            'classes' => ClassModel::all(),
            'internships' => Internship::all(),
        ]);
    }
    public function createCompany(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'email' => 'required|email',
            'phone' => 'required|string|max:20',
        ]);

        Company::create([
            'name' => $request->name,
            'address' => $request->address,
            'email' => $request->email,
            'phone' => $request->phone,
        ]);

        return back()->with('success', 'Company created successfully!');
    }

    public function createUser(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => ['required', 'string', 'confirmed', new StrongPassword()],
            'role' => 'required|string',
            'company_id' => 'nullable|integer|exists:companies,id',
        ]);

        $validated['password'] = Hash::make($validated['password']);

        $validated2 = $request->validate([
            'class_id' => 'nullable|integer|exists:classes,id',
        ]);

        try {
            $user = User::create($validated);
            if ($validated['role'] == "student") {
                $validated2['user_id'] = $user->id;
                UserClass::create($validated2);
            }
        } catch (\Exception $e) {
            Log::error('User creation failed', ['exception' => $e]);
            return back()->withErrors('User could not be created. Please try again.');
        }

        Mail::to($user->email)->send(new UserCreatedMail($user->email));

        return back()->with('success', 'User created successfully!');
    }

    public function createClass(Request $request)
    {
        $validated = $request->validate([
            'course' => 'required|string|max:255',
            'sigla' => 'required|string|max:50|unique:classes,sigla',
            'year' => 'nullable|integer',
        ]);

        $validated2 = $request->validate([
            'user_id' => 'nullable|integer|exists:users,id',
        ]);

        try {
            $class = ClassModel::create($validated);
            $validated2['class_id'] = $class->id;
            UserClass::create($validated2);
            return back()->with('success', 'Class created successfully!');
        } catch (\Exception $e) {
            return back()->withErrors($e->getMessage());
        }
    }

    public function createInternship(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'company_id' => 'required|exists:companies,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'total_hours_required' => 'required|integer|min:1',
            'min_hours_day' => 'required|integer|min:0',
        ]);

        try {
            Internship::create($validated);
        } catch (\Exception $e) {
            return back()->withErrors($e->getMessage());
        }

        return back()->with('success', 'Internship created successfully!');
    }

    public function assignUserInternship(Request $request)
    {
        $validated = $request->validate([
            'role' => 'required|in:student,supervisor',
            'internship_id' => 'required|exists:internships,id',
            'student_id' => 'required_if:role,student|exists:users,id',
            'supervisor_id' => 'required_if:role,supervisor|exists:users,id',
        ]);

        $userId = $validated['role'] === 'student'
            ? $validated['student_id']
            : $validated['supervisor_id'];

        $user = User::where('id', $userId)
            ->where('role', $validated['role'])
            ->firstOrFail();

        $exists = UserInternship::where('user_id', $userId)
            ->where('internship_id', $validated['internship_id'])
            ->exists();

        if ($exists) {
            return back()->withErrors('This user is already assigned to this internship.');
        }

        UserInternship::create([
            'user_id' => $userId,
            'internship_id' => $validated['internship_id'],
        ]);

        return back()->with('success', 'User assigned to internship successfully!');
    }

    public function deleteSupervisor(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        User::where('id', $request->user_id)
            ->where('role', User::ROLE_SUPERVISOR)
            ->firstOrFail()
            ->delete();

        return back()->with('success', 'Supervisor deleted successfully!');
    }

    public function deleteStudent(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        User::where('id', $request->user_id)
            ->where('role', User::ROLE_STUDENT)
            ->firstOrFail()
            ->delete();

        return back()->with('success', 'Student deleted successfully!');
    }

    public function deleteCoordinator(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        User::where('id', $request->user_id)
            ->where('role', User::ROLE_COORDINATOR)
            ->firstOrFail()
            ->delete();

        return back()->with('success', 'Coordinator deleted successfully!');
    }

    public function deleteCompany(Request $request)
    {
        $request->validate([
            'company_id' => 'required|exists:companies,id',
        ]);

        Company::findOrFail($request->company_id)->delete();

        return back()->with('success', 'Company deleted successfully!');
    }

    public function deleteClass(Request $request)
    {
        $request->validate([
            'class_id' => 'required|exists:classes,id',
        ]);

        ClassModel::findOrFail($request->class_id)->delete();

        return back()->with('success', 'Class deleted successfully!');
    }

    public function deleteInternship(Request $request)
    {
        $request->validate([
            'internship_id' => 'required|exists:internships,id',
        ]);

        Internship::findOrFail($request->internship_id)->delete();

        return back()->with('success', 'Internship deleted successfully!');
    }

    public function unassignUserInternship(Request $request)
    {
        $request->validate([
            'role' => 'required|in:student,supervisor',
            'internship_id' => 'required|exists:internships,id',
        ]);

        $userId = $request->role === 'student'
            ? $request->student_id
            : $request->supervisor_id;

        if (!$userId) {
            return back()->withErrors('Please select a user.');
        }

        $assignment = UserInternship::where('user_id', $userId)
            ->where('internship_id', $request->internship_id)
            ->first();

        if (!$assignment) {
            return back()->withErrors('This user is not assigned to this internship.');
        }

        $assignment->delete();

        return back()->with('success', 'User unassigned from internship successfully!');
    }
}