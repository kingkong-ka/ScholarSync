<?php

namespace App\Http\Controllers\Student;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use App\Models\Notification;
use App\Http\Controllers\Controller;

class StudentController extends Controller
{
    protected $unreadCount = 0;

    public function __construct()
    {
        try {
            if (Auth::check()) {
                $this->unreadCount = Notification::where('user_id', Auth::id())
                    ->where('is_read', false)
                    ->count();
            }
        } catch (\Exception $e) {
            // If there's any error (like table doesn't exist), set count to 0
            $this->unreadCount = 0;
        }

        View::share('unreadCount', $this->unreadCount);
    }


    /**
     * Display the student dashboard
     */
    public function dashboard()
    {
        return view('student.dashboard', ['user' => auth()->user(), 'unreadCount' => $this->unreadCount]);
    }

    /**
     * Display the student account information
     */
    public function account()
    {
        $user = Auth::user();

        // Get additional data that might be needed for the dashboard
        $data = [
            'user' => $user,
            'unreadCount' => $this->unreadCount,
            'status' => 'Good Standing', // This could be fetched from a database in a real application
            'studentId' => 'STU-' . str_pad($user->id, 5, '0', STR_PAD_LEFT),
            'gradeLevel' => '11', // This could be fetched from a database in a real application
        ];

        return view('student.dashboard', $data);
    }

    public function violation()
    {
        $violations = collect(); // Create an empty collection
        return view('student.violation', compact('violations'));
    }

    public function behavior()
    {
        // You can add logic to fetch student behavior records here
        return view('student.behavior');
    }


}