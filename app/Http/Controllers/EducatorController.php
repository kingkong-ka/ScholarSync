<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Violation;
use App\Models\ViolationType;
use App\Models\OffenseCategory;
use Illuminate\Support\Facades\DB;

class EducatorController extends Controller
{
    /**
     * Display the educator dashboard
     */
    public function dashboard()
    {
        // Get top violators (students with most violations)
        $topViolators = User::role('student')
            ->withCount('violations')
            ->having('violations_count', '>', 0)
            ->orderBy('violations_count', 'desc')
            ->take(5)
            ->get();

        // Get total violations count
        $totalViolations = Violation::count();

        // Get total students count
        $totalStudents = User::role('student')->count();

        // Get total rewards count
        $totalRewards = DB::table('rewards')->count();

        // Get recent violations
        $recentViolations = Violation::with(['student', 'violationType'])
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        // Get violations by month
        $violationsByMonth = DB::table('violations')
            ->selectRaw('MONTH(violation_date) as month, COUNT(*) as count')
            ->whereRaw('YEAR(violation_date) = YEAR(CURDATE())')
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->pluck('count', 'month')
            ->toArray();

        // Get violations by type
        $violationsByType = DB::table('violations')
            ->join('violation_types', 'violations.violation_type_id', '=', 'violation_types.id')
            ->selectRaw('violation_types.violation_name, COUNT(*) as count')
            ->groupBy('violation_types.violation_name')
            ->orderBy('count', 'desc')
            ->get()
            ->pluck('count', 'violation_name')
            ->toArray();

        // Get violator and non-violator counts
        $violatorCount = User::role('student')
            ->whereHas('violations')
            ->count();

        $nonViolatorCount = $totalStudents - $violatorCount;

        return view('educator.dashboard', [
            'topViolators' => $topViolators,
            'totalViolations' => $totalViolations,
            'totalStudents' => $totalStudents,
            'totalRewards' => $totalRewards,
            'recentViolations' => $recentViolations,
            'violationsByMonth' => $violationsByMonth,
            'violationsByType' => $violationsByType,
            'violatorCount' => $violatorCount,
            'nonViolatorCount' => $nonViolatorCount
        ]);
    }

    /**
     * Show the form for creating a new violation type
     */
    public function createViolationType()
    {
        return view('educator.newViolation');
    }

    /**
     * Display the behavior page
     */
    public function behavior()
    {
        return view('educator.behavior');
    }

    /**
     * View a specific violation
     * @param int $id The violation ID
     * @return \Illuminate\View\View
     */
    public function viewViolation($id)
    {
        try {
            // Fetch the actual violation data from the database with relationships
            $violation = \App\Models\Violation::with(['student', 'violationType', 'violationType.offenseCategory'])
                ->findOrFail($id);

            // If student relationship is null, try to find the student directly
            if (!$violation->student) {
                $student = \App\Models\User::where('student_id', $violation->student_id)->first();
                if ($student) {
                    // Manually attach the student to the violation
                    $violation->setRelation('student', $student);
                }
            }

            return view('educator.viewViolation', compact('violation'));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error viewing violation: ' . $e->getMessage());
            return redirect()->route('educator.violation')
                ->with('error', 'Error viewing violation: ' . $e->getMessage());
        }
    }

    /**
     * Get students by penalty
     * @param string $penalty The penalty code (W, VW, WW, Pro, Exp)
     * @return \Illuminate\View\View
     */
    public function studentsByPenalty($penalty)
    {
        try {
            // Log the penalty parameter for debugging
            \Illuminate\Support\Facades\Log::info('studentsByPenalty called with penalty: ' . $penalty);

            // Get all students with active violations of the specified penalty
            $students = User::role('student')
                ->whereHas('violations', function($query) use ($penalty) {
                    $query->where('penalty', $penalty)
                          ->where('status', 'active');
                })
                ->with(['violations' => function($query) use ($penalty) {
                    $query->where('penalty', $penalty)
                          ->where('status', 'active')
                          ->with('violationType');
                }])
                ->get();

            // Log the number of students found
            \Illuminate\Support\Facades\Log::info('Found ' . count($students) . ' students with penalty: ' . $penalty);

            return view('educator.studentsByPenalty', [
                'students' => $students,
                'penalty' => $penalty
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error fetching students by penalty: ' . $e->getMessage());
            return redirect()->route('educator.violation')
                ->with('error', 'Error fetching students: ' . $e->getMessage());
        }
    }

//STUDENT-MAUAL//
    /**
 * Show the form for editing the student violation manual
 */
public function editManual()
{
    // Get all offense categories with their violation types
    $categories = OffenseCategory::with(['violationTypes' => function($query) {
        $query->orderBy('violation_name');
    }])->get();

    return view('educator.editManual', compact('categories'));
}

/**
 * Update the student violation manual
 */
public function updateManual(Request $request)
{
    try {
        // Log the request data for debugging
        \Illuminate\Support\Facades\Log::info('Manual update request received', [
            'request_data' => $request->all()
        ]);

        // Validate the request
        $validated = $request->validate([
            'categories' => 'required|array',
            'categories.*.id' => 'required|exists:offense_categories,id',
            'categories.*.category_name' => 'required|string|max:255',
            'categories.*.violationTypes' => 'nullable|array', // Changed from required to nullable
            'categories.*.violationTypes.*.id' => 'required|exists:violation_types,id',
            'categories.*.violationTypes.*.violation_name' => 'required|string|max:500',
            'categories.*.violationTypes.*.default_penalty' => 'required|string|in:W,VW,WW,Pro,Exp',
            'categories.*.violationTypes.*.offenses' => 'nullable|string|max:255',
            'categories.*.violationTypes.*.penalties_text' => 'nullable|string|max:500',
            'categories.*.new_violations' => 'nullable|array',
            'categories.*.new_violations.*.name' => 'nullable|string|max:500',
            'categories.*.new_violations.*.default_penalty' => 'nullable|string|in:W,VW,WW,Pro,Exp',
            'categories.*.new_violations.*.offenses' => 'nullable|string|max:255',
            'categories.*.new_violations.*.penalties_text' => 'nullable|string|max:500',
            'new_category' => 'nullable|array',
            'new_category.name' => 'nullable|string|max:255',
            'new_category.description' => 'nullable|string|max:500',
            'new_category.violations' => 'nullable|array',
            'new_category.violations.*.name' => 'nullable|string|max:500',
            'new_category.violations.*.default_penalty' => 'nullable|string|in:W,VW,WW,Pro,Exp',
            'new_category.violations.*.offenses' => 'nullable|string|max:255',
            'new_category.violations.*.penalties_text' => 'nullable|string|max:500',
            'delete_violations' => 'nullable|array',
            'delete_violations.*' => 'nullable|exists:violation_types,id',
            'delete_categories' => 'nullable|array',
            'delete_categories.*' => 'nullable|exists:offense_categories,id',
        ]);

        // Log validated data
        \Illuminate\Support\Facades\Log::info('Validation passed');

        // Start a database transaction
        \DB::beginTransaction();

        $updatedCategories = 0;
        $updatedViolations = 0;
        $newViolations = 0;
        $newCategories = 0;
        $deletedViolations = 0;
        $deletedCategories = 0;

        // Delete categories if requested
        if ($request->has('delete_categories') && is_array($request->delete_categories)) {
            foreach ($request->delete_categories as $categoryId) {
                $category = OffenseCategory::find($categoryId);
                if ($category) {
                    // Delete all violations in this category
                    $violations = ViolationType::where('offense_category_id', $categoryId)->get();
                    foreach ($violations as $violation) {
                        $violation->delete();
                        $deletedViolations++;
                    }

                    // Delete the category
                    $category->delete();
                    $deletedCategories++;

                    \Illuminate\Support\Facades\Log::info("Deleted category {$categoryId} and all its violations");
                }
            }
        }

        // Delete individual violations if requested
        if ($request->has('delete_violations') && is_array($request->delete_violations)) {
            foreach ($request->delete_violations as $violationId) {
                $violation = ViolationType::find($violationId);
                if ($violation) {
                    $violation->delete();
                    $deletedViolations++;

                    \Illuminate\Support\Facades\Log::info("Deleted violation {$violationId}");
                }
            }
        }

        // Update existing categories and violations
        foreach ($request->categories as $categoryData) {
            // Update category
            $category = OffenseCategory::findOrFail($categoryData['id']);
            $oldCategoryName = $category->category_name;
            $category->category_name = $categoryData['category_name'];
            $category->save();
            $updatedCategories++;

            // Log category update
            if ($oldCategoryName !== $category->category_name) {
                \Illuminate\Support\Facades\Log::info("Updated category {$category->id}", [
                    'old_name' => $oldCategoryName,
                    'new_name' => $category->category_name
                ]);
            }

            // Update existing violations
            if (isset($categoryData['violationTypes']) && is_array($categoryData['violationTypes'])) {
                foreach ($categoryData['violationTypes'] as $violationData) {
                    // Check if the violation still exists (might have been deleted)
                    $violation = ViolationType::find($violationData['id']);
                    if ($violation) {
                        $oldViolationName = $violation->violation_name;
                        $oldPenalty = $violation->default_penalty;

                        $violation->violation_name = $violationData['violation_name'];
                        $violation->default_penalty = $violationData['default_penalty'];

                        // Save penalties text to description field
                        if (isset($violationData['penalties_text'])) {
                            $violation->description = $violationData['penalties_text'];
                        }

                        // Save offenses if provided
                        if (isset($violationData['offenses'])) {
                            $violation->offenses = $violationData['offenses'];
                        }

                        $violation->save();
                        $updatedViolations++;

                        // Log violation update
                        if ($oldViolationName !== $violation->violation_name || $oldPenalty !== $violation->default_penalty) {
                            \Illuminate\Support\Facades\Log::info("Updated violation {$violation->id}");
                        }
                    }
                }
            }

            // Add new violations to existing category
            if (isset($categoryData['new_violations'])) {
                foreach ($categoryData['new_violations'] as $newViolationData) {
                    // Skip empty entries
                    if (empty($newViolationData['name'])) {
                        continue;
                    }

                    // Create new violation
                    $newViolation = new ViolationType();
                    $newViolation->offense_category_id = $category->id;
                    $newViolation->violation_name = $newViolationData['name'];
                    $newViolation->default_penalty = $newViolationData['default_penalty'];

                    // Add description field if provided
                    if (isset($newViolationData['penalties_text'])) {
                        $newViolation->description = $newViolationData['penalties_text'];
                    }

                    $newViolation->save();
                    $newViolations++;

                    \Illuminate\Support\Facades\Log::info("Added new violation to category {$category->id}", [
                        'violation_name' => $newViolation->violation_name
                    ]);
                }
            }
        }

        // Add new category if provided
        if ($request->has('new_category') && !empty($request->new_category['name'])) {
            // Create new category
            $newCategory = new OffenseCategory();
            $newCategory->category_name = $request->new_category['name'];
            $newCategory->description = $request->new_category['description'] ?? '';
            $newCategory->save();
            $newCategories++;

            \Illuminate\Support\Facades\Log::info("Added new category", [
                'category_name' => $newCategory->category_name
            ]);

            // Add violations to the new category
            if (isset($request->new_category['violations'])) {
                foreach ($request->new_category['violations'] as $violationData) {
                    // Skip empty entries
                    if (empty($violationData['name'])) {
                        continue;
                    }

                    // Create new violation
                    $newViolation = new ViolationType();
                    $newViolation->offense_category_id = $newCategory->id;
                    $newViolation->violation_name = $violationData['name'];
                    $newViolation->default_penalty = $violationData['default_penalty'];

                    // Add description field if provided
                    if (isset($violationData['penalties_text'])) {
                        $newViolation->description = $violationData['penalties_text'];
                    }

                    $newViolation->save();
                    $newViolations++;

                    \Illuminate\Support\Facades\Log::info("Added new violation to new category", [
                        'violation_name' => $newViolation->violation_name
                    ]);
                }
            }
        }

        // Commit the transaction
        \DB::commit();

        // Log success
        \Illuminate\Support\Facades\Log::info('Manual update successful', [
            'updated_categories' => $updatedCategories,
            'updated_violations' => $updatedViolations,
            'new_violations' => $newViolations,
            'new_categories' => $newCategories
        ]);

        // Simple success message
        return redirect()->route('student-manual')
            ->with('success', "Student Violation Manual Updated Successfully");
    } catch (\Exception $e) {
        // Rollback the transaction in case of error
        \DB::rollBack();

        // Log the error
        \Illuminate\Support\Facades\Log::error('Error updating manual: ' . $e->getMessage(), [
            'exception' => $e,
            'trace' => $e->getTraceAsString()
        ]);

        // Redirect with error message
        return redirect()->back()
            ->withInput()
            ->with('error', 'An error occurred while updating the manual: ' . $e->getMessage());
    }
}
}
