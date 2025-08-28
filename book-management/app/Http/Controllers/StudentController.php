<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Http\Requests\StoreStudentRequest;
use App\Http\Requests\UpdateStudentRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Exception;

class StudentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Student::query()
            ->withCount(['borrows as active_borrows_count' => function ($query) {
                $query->whereNull('returned_date');
            }]);

        // 検索フィルター
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('student_number', 'like', "%{$search}%");
            });
        }

        if ($request->filled('grade')) {
            $query->where('grade', $request->grade);
        }

        if ($request->filled('class')) {
            $query->where('class', $request->class);
        }

        $students = $query->orderBy('grade')
                         ->orderBy('class')
                         ->orderBy('name')
                         ->get();

        return response()->json([
            'data' => $students,
            'message' => 'Students retrieved successfully'
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreStudentRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $student = Student::create($validated);
        return response()->json([
            'data' => $student,
            'message' => 'Student created successfully'
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Student $student): JsonResponse
    {
        $student->load([
            'borrows' => function ($query) {
                $query->with('book')
                      ->orderBy('borrowed_date', 'desc');
            }
        ]);

        // アクティブな貸出と履歴を分離
        $activeborrows = $student->borrows->whereNull('returned_date');
        $borrowHistory = $student->borrows->whereNotNull('returned_date');

        return response()->json([
            'data' => [
                'student' => $student,
                'active_borrows' => $activeborrows,
                'borrow_history' => $borrowHistory
            ],
            'message' => 'Student retrieved successfully'
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateStudentRequest $request, Student $student): JsonResponse
    {
        $validated = $request->validated();
        $student->update($validated);
        return response()->json([
            'data' => $student,
            'message' => 'Student updated successfully'
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Student $student): JsonResponse
    {
        $student->delete();

        return response()->json([
            'message' => 'Student deleted successfully'
        ]);
    }

    /**
     * Get borrows for a specific student.
     */
    public function getBorrows(Student $student): JsonResponse
    {
        $student->load(['borrows' => function ($query) {
            $query->with('book')->orderBy('borrowed_date', 'desc');
        }]);

        $activeBorrows = $student->borrows->whereNull('returned_date')->values();
        $borrowHistory = $student->borrows->whereNotNull('returned_date')->values();

        return response()->json([
            'active_borrows' => $activeBorrows,
            'borrow_history' => $borrowHistory
        ]);
    }
}
