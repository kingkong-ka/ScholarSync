<?php

namespace App\Http\Controllers\Educator;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\Violation;
use App\Models\User;
use App\Models\StudentDetail;
use App\Models\OffenseCategory;
use App\Models\ViolationType;
use Exception;

/**
 * EducatorController
 * Handles all educator-related functionality including violation management,
 * student tracking, and dashboard statistics.
 */
class EducatorController extends Controller
{
    // =============================================
    // Private Helper Methods
    // =============================================

    /**
     * Get violation statistics for a specific time period
     * @param string $period The time period to get statistics for (month, last_month, last_3_months)
     * @return \Illuminate\Support\Collection
     */
    private function getViolationStats($period = 'month')
    {
        $query = DB::table('violations')
            ->join('violation_types', 'violations.violation_type_id', '=', 'violation_types.id')
            ->select(
                'violation_types.violation_name',
                DB::raw('COUNT(violations.id) as count')
            )
            ->where('violations.status', 'active')
            ->groupBy('violation_types.violation_name')
            ->orderByDesc('count');

        switch ($period) {
            case 'last_month':
                $query->whereBetween('violations.created_at', [
                    now()->subMonth()->startOfMonth(),
                    now()->subMonth()->endOfMonth()
                ]);
                break;
            case 'last_3_months':
                $query->whereBetween('violations.created_at', [
                    now()->subMonths(3)->startOfMonth(),
                    now()->endOfMonth()
                ]);
                break;
            default: // this_month
                $query->whereBetween('violations.created_at', [
                    now()->startOfMonth(),
                    now()->endOfMonth()
                ]);
                break;
        }

        return $query->get();
    }

    /**
     * Get severity level based on penalty
     * @param string $penalty The penalty code
     * @return string The severity level
     */
    private function getSeverityFromPenalty($penalty)
    {
        switch ($penalty) {
            case 'W':
                return 'Low';
            case 'VW':
                return 'Medium';
            case 'WW':
                return 'High';
            case 'Exp':
                return 'Very High';
            default:
                return 'Low';
        }
    }

    /**
     * Get penalty code from penalty name
     * @param string $penalty The penalty name
     * @return string The penalty code
     */
    private function getPenaltyCode($penalty)
    {
        switch ($penalty) {
            case 'warning':
                return 'W';
            case 'verbal':
                return 'VW';
            case 'written':
                return 'WW';
            case 'probation':
                return 'Pro';
            case 'expulsion':
                return 'Exp';
            default:
                return 'W';
        }
    }

    // =============================================
    // Dashboard Methods
    // =============================================

    /**
     * Get violation statistics by period
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getViolationStatsByPeriod(Request $request)
    {
        try {
            $period = $request->input('period', 'month');
            $stats = $this->getViolationStats($period);
            return response()->json($stats);
        } catch (Exception $e) {
            Log::error('Error in getViolationStatsByPeriod: ' . $e->getMessage());
            return response()->json(['error' => 'Unable to fetch violation statistics.'], 500);
        }
    }

    /**
     * Display the educator dashboard with statistics and charts
     * @return \Illuminate\View\View
     */
    public function dashboard()
    {
        try {
            // Get total number of active students
            $totalStudents = DB::table('users')
                ->join('student_details', 'users.user_id', '=', 'student_details.user_id')
                ->where('users.role', 'student')
                ->where('users.status', 'active')
                ->count();

            // Get total violations
            $totalViolations = DB::table('violations')
                ->where('status', 'active')
                ->count();
                
            // Get total rewards
            $totalRewards = DB::table('rewards')
                ->where('status', 'active')
                ->count();

            // Get number of students with violations
            $violatorCount = DB::table('users')
                ->join('student_details', 'users.user_id', '=', 'student_details.user_id')
                ->join('violations', 'student_details.student_id', '=', 'violations.student_id')
                ->where('users.role', 'student')
                ->where('users.status', 'active')
                ->where('violations.status', 'active')
                ->distinct('users.user_id')
                ->count('users.user_id');

            // Calculate non-violators
            $nonViolatorCount = $totalStudents - $violatorCount;

            // Get top violators
            $topViolators = DB::table('users')
                ->join('student_details', 'users.user_id', '=', 'student_details.user_id')
                ->join('violations', 'student_details.student_id', '=', 'violations.student_id')
                ->where('users.role', 'student')
                ->where('users.status', 'active')
                ->where('violations.status', 'active')
                ->select(
                    'users.fname',
                    'users.lname',
                    'users.user_id',
                    'student_details.student_id',
                    DB::raw('COUNT(violations.id) as violation_count')
                )
                ->groupBy('users.fname', 'users.lname', 'users.user_id', 'student_details.student_id')
                ->orderByDesc('violation_count')
                ->limit(5)
                ->get();
                
            // Get recent violations
            $recentViolations = Violation::with(['student', 'violationType'])
                ->where('status', 'active')
                ->orderByDesc('created_at')
                ->limit(10)
                ->get();
                
            // Get violations by month (last 6 months)
            $violationsByMonth = DB::table('violations')
                ->select(DB::raw('MONTH(created_at) as month, YEAR(created_at) as year, COUNT(*) as count'))
                ->where('status', 'active')
                ->whereBetween('created_at', [now()->subMonths(6), now()])
                ->groupBy('year', 'month')
                ->orderBy('year')
                ->orderBy('month')
                ->get()
                ->map(function($item) {
                    $monthName = date('F', mktime(0, 0, 0, $item->month, 1));
                    return [
                        'month' => $monthName,
                        'count' => $item->count
                    ];
                });
                
            // Get violations by type
            $violationsByType = DB::table('violations')
                ->join('violation_types', 'violations.violation_type_id', '=', 'violation_types.id')
                ->select('violation_types.violation_name', DB::raw('COUNT(*) as count'))
                ->where('violations.status', 'active')
                ->groupBy('violation_types.violation_name')
                ->orderByDesc('count')
                ->get();

            return view('educator.staff-dashboard', compact(
                'topViolators',
                'totalViolations',
                'totalStudents',
                'totalRewards',
                'recentViolations',
                'violationsByMonth',
                'violationsByType',
                'violatorCount',
                'nonViolatorCount'
            ));
        } catch (Exception $e) {
            Log::error('Error in dashboard: ' . $e->getMessage());
            return view('educator.staff-dashboard', ['error' => 'Unable to load dashboard data'])
                ->with('topViolators', collect());
        }
    }

    // =============================================
    // Violation Management Methods
    // =============================================

    /**
     * Display all violations
     * @return \Illuminate\View\View
     */
    public function violations()
    {
        try {
            $violations = Violation::with(['violationType', 'offenseCategory', 'student'])
                ->orderBy('created_at', 'desc')
                ->get();

            $categories = OffenseCategory::orderBy('category_name')->get();

            return view('educator.violation', compact('violations', 'categories'));
        } catch (Exception $e) {
            Log::error('Error in violations: ' . $e->getMessage());
            return back()->with('error', 'Unable to fetch violations.');
        }
    }

    /**
     * Display the form to add a new violation
     * @return \Illuminate\View\View
     */
    public function addViolation()
    {
        try {
            // Get active students
            $students = DB::table('users')
                ->join('student_details', 'users.user_id', '=', 'student_details.user_id')
                ->where('users.role', 'student')
                ->where('users.status', 'active')
                ->select('users.fname', 'users.lname', 'student_details.student_id')
                ->orderBy('users.lname')
                ->get();

            // Get offense categories
            $offenseCategories = DB::table('offense_categories')
                ->select('id', 'category_name')
                ->orderBy('category_name')
                ->get();

            Log::info('Students found: ' . $students->count());

            if ($students->isEmpty()) {
                $students = collect(); // Return empty collection if no students found
            }

            return view('educator.educator_add_violator', [
                'students' => $students,
                'offenseCategories' => $offenseCategories
            ]);
        } catch (Exception $e) {
            Log::error('Error fetching data: ' . $e->getMessage());
            return view('educator.educator_add_violator', [
                'students' => collect(),
                'offenseCategories' => collect()
            ]);
        }
    }

    /**
     * Display students by penalty type
     * @param string|null $penalty The penalty type
     * @return \Illuminate\View\View
     */
    public function studentsByPenalty($penalty = null)
    {
        try {
            $penaltyNames = [
                'W' => 'Warning',
                'VW' => 'Verbal Warning',
                'WW' => 'Written Warning',
                'Pro' => 'Probation',
                'Exp' => 'Expulsion'
            ];

            $penaltyName = $penaltyNames[$penalty] ?? 'Unknown';

            $students = DB::table('users')
                ->join('student_details', 'users.user_id', '=', 'student_details.user_id')
                ->join('violations', 'student_details.student_id', '=', 'violations.student_id')
                ->where('users.role', 'student')
                ->where('users.status', 'active')
                ->where('violations.penalty', $penalty)
                ->where('violations.status', 'active')
                ->select(
                    'users.fname',
                    'users.lname', 
                    'student_details.student_id',
                    DB::raw('COUNT(violations.id) as violation_count'),
                    DB::raw('MAX(violations.created_at) as latest_violation'),
                    'violations.penalty',
                    'violations.status'
                )
                ->groupBy('users.fname', 'users.lname', 'student_details.student_id', 'violations.penalty', 'violations.status')
                ->orderByDesc('latest_violation')
                ->get();

            return view('educator.students-by-penalty', [
                'students' => $students,
                'penaltyName' => $penaltyName
            ]);
        } catch (Exception $e) {
            Log::error('Error in studentsByPenalty: ' . $e->getMessage());
            return back()->with('error', 'Unable to fetch students by penalty.');
        }
    }

    // =============================================
    // Violation Type Management Methods
    // =============================================

    /**
     * Get violation types for a specific category
     * @param int $categoryId The category ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function getViolationTypes($categoryId)
    {
        try {
            Log::info('Fetching violation types for category: ' . $categoryId);
            
            // First, verify the category exists
            $category = DB::table('offense_categories')->where('id', $categoryId)->first();
            Log::info('Category found: ' . json_encode($category));
            
            if (!$category) {
                Log::error('Category not found with ID: ' . $categoryId);
                return response()->json(['error' => 'Category not found'], 404);
            }

            // Get violation types for the category
            $violationTypes = DB::table('violation_types')
                ->where('offense_category_id', $categoryId)
                ->select('id', 'violation_name as name', 'default_penalty')
                ->orderBy('violation_name')
                ->get();

            Log::info('Found violation types for category ' . $category->category_name . ': ' . $violationTypes->count());

            // Add severity information based on default_penalty
            $violationTypes = $violationTypes->map(function ($type) {
                $type->severity = $this->getSeverityFromPenalty($type->default_penalty);
                return $type;
            });

            return response()->json($violationTypes);
        } catch (Exception $e) {
            Log::error('Error fetching violation types: ' . $e->getMessage());
            return response()->json([], 500);
        }
    }

    /**
     * Store a new violation type
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeViolationType(Request $request)
    {
        try {
            // Validate the request
            $validated = $request->validate([
                'violation_name' => 'required|string|max:500',
                'category' => 'required|string',
                'severity' => 'required|string',
                'offense' => 'required|string',
                'penalty' => 'required|string'
            ]);

            // Get the offense category ID
            $category = DB::table('offense_categories')
                ->where('category_name', $validated['category'])
                ->first();

            if (!$category) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid category selected'
                ], 400);
            }

            // Insert into violation_types table
            $violationTypeId = DB::table('violation_types')->insertGetId([
                'offense_category_id' => $category->id,
                'violation_name' => $validated['violation_name'],
                'default_penalty' => $this->getPenaltyCode($validated['penalty']),
                'created_at' => now(),
                'updated_at' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Violation type added successfully',
                'violation_type_id' => $violationTypeId
            ]);

        } catch (Exception $e) {
            Log::error('Error storing violation type: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error adding violation type: ' . $e->getMessage()
            ], 500);
        }
    }

    // =============================================
    // Violation Form Data Methods
    // =============================================

    /**
     * Get form data for violation creation
     * @return \Illuminate\Http\JsonResponse
     */
    public function getViolationFormData()
    {
        try {
            $students = DB::table('users')
                ->join('student_details', 'users.user_id', '=', 'student_details.user_id')
                ->where('users.role', 'student')
                ->where('users.status', 'active')
                ->select('users.fname', 'users.lname', 'student_details.student_id')
                ->orderBy('users.lname')
                ->get();

            $categories = DB::table('offense_categories')
                ->select('id', 'category_name')
                ->orderBy('category_name')
                ->get();

            $severities = ['Low', 'Medium', 'High', 'Very High'];
            $offenses = ['1st', '2nd', '3rd'];
            $penalties = [
                ['value' => 'warning', 'label' => 'Warning'],
                ['value' => 'verbal', 'label' => 'Verbal Warning'],
                ['value' => 'written', 'label' => 'Written Warning'],
                ['value' => 'probation', 'label' => 'Probation'],
                ['value' => 'expulsion', 'label' => 'Expulsion']
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'students' => $students,
                    'categories' => $categories,
                    'severities' => $severities,
                    'offenses' => $offenses,
                    'penalties' => $penalties
                ]
            ]);
        } catch (Exception $e) {
            Log::error('Error fetching violation form data: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching form data: ' . $e->getMessage()
            ], 500);
        }
    }

    // =============================================
    // Violation CRUD Methods
    // =============================================

    /**
     * Store a new violation
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeViolation(Request $request)
    {
        try {
            // Validate the request
            $validated = $request->validate([
                'student_id' => 'required',
                'violation_date' => 'required|date',
                'violation_type_id' => 'required',
                'severity' => 'required',
                'offense' => 'required',
                'penalty' => 'required',
                'consequence' => 'required'
            ]);

            // Create new violation record
            $violation = Violation::create([
                'student_id' => $validated['student_id'],
                'violation_date' => $validated['violation_date'],
                'violation_type_id' => $validated['violation_type_id'],
                'severity' => $validated['severity'],
                'offense' => $validated['offense'],
                'penalty' => $validated['penalty'],
                'consequence' => $validated['consequence'],
                'created_at' => now(),
                'updated_at' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Violation recorded successfully',
                'violation_id' => $violation->id
            ]);

        } catch (Exception $e) {
            Log::error('Error storing violation: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error recording violation: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * View a specific violation
     * @param int $id The violation ID
     * @return \Illuminate\View\View
     */
    public function viewViolation($id)
    {
        try {
            $violation = Violation::with(['student', 'violationType', 'offenseCategory'])
                ->findOrFail($id);
            
            // If student relationship is null, try to find the student directly
            if (!$violation->student) {
                $student = User::where('student_id', $violation->student_id)->first();
                if ($student) {
                    // Manually attach the student to the violation
                    $violation->setRelation('student', $student);
                }
            }

            return view('educator.viewViolation', compact('violation'));
        } catch (Exception $e) {
            Log::error('Error viewing violation: ' . $e->getMessage());
            return redirect()->route('educator.violations')
                ->with('error', 'Error viewing violation: ' . $e->getMessage());
        }
    }

    /**
     * Edit a specific violation
     * @param int $id The violation ID
     * @return \Illuminate\View\View
     */
    public function editViolation($id)
    {
        try {
            $violation = Violation::with(['student', 'violationType', 'offenseCategory'])
                ->findOrFail($id);

            $students = DB::table('users')
                ->join('student_details', 'users.user_id', '=', 'student_details.user_id')
                ->where('users.role', 'student')
                ->where('users.status', 'active')
                ->select('users.fname', 'users.lname', 'student_details.student_id')
                ->orderBy('users.lname')
                ->get();

            $offenseCategories = DB::table('offense_categories')
                ->select('id', 'category_name')
                ->orderBy('category_name')
                ->get();

            return view('educator.edit_violation', compact('violation', 'students', 'offenseCategories'));
        } catch (Exception $e) {
            Log::error('Error editing violation: ' . $e->getMessage());
            return redirect()->route('educator.violations')
                ->with('error', 'Error editing violation: ' . $e->getMessage());
        }
    }

    /**
     * Update a specific violation
     * @param Request $request
     * @param int $id The violation ID
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateViolation(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'student_id' => 'required',
                'violation_date' => 'required|date',
                'violation_type_id' => 'required',
                'severity' => 'required',
                'offense' => 'required',
                'penalty' => 'required',
                'consequence' => 'required'
            ]);

            $violation = Violation::findOrFail($id);
            $violation->update([
                'student_id' => $validated['student_id'],
                'violation_date' => $validated['violation_date'],
                'violation_type_id' => $validated['violation_type_id'],
                'severity' => $validated['severity'],
                'offense' => $validated['offense'],
                'penalty' => $validated['penalty'],
                'consequence' => $validated['consequence'],
                'updated_at' => now()
            ]);

            return redirect()->route('educator.violations')
                ->with('success', 'Violation updated successfully');
        } catch (Exception $e) {
            Log::error('Error updating violation: ' . $e->getMessage());
            return redirect()->route('educator.violations')
                ->with('error', 'Error updating violation: ' . $e->getMessage());
        }
    }

    
}