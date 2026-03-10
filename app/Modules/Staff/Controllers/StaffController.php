<?php

namespace App\Modules\Staff\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Staff\Services\StaffService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class StaffController extends Controller
{
    public function __construct(
        private StaffService $staffService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = [
            'search' => $request->query('search'),
            'role' => $request->query('role'),
            'is_active' => $request->query('is_active'),
        ];

        $staff = $this->staffService->getStaff(array_filter($filters));

        return response()->json([
            'success' => true,
            'data' => $staff,
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $staff = $this->staffService->getStaffById($id);

        return response()->json([
            'success' => true,
            'data' => $staff,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'email' => 'required|email|unique:staff,email',
            'password' => 'required|min:6',
            'role' => ['required', Rule::in(['owner', 'manager', 'cashier', 'staff'])],
            'is_active' => 'boolean',
        ]);

        $staff = $this->staffService->createStaff($validated);

        return response()->json([
            'success' => true,
            'data' => $staff,
            'message' => 'Staff created successfully',
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:150',
            'email' => 'sometimes|required|email|unique:staff,email,' . $id,
            'password' => 'sometimes|nullable|min:6',
            'role' => ['sometimes', Rule::in(['owner', 'manager', 'cashier', 'staff'])],
            'is_active' => 'boolean',
        ]);

        $staff = $this->staffService->updateStaff($id, $validated);

        return response()->json([
            'success' => true,
            'data' => $staff,
            'message' => 'Staff updated successfully',
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->staffService->deleteStaff($id);

        return response()->json([
            'success' => true,
            'message' => 'Staff deleted successfully',
        ]);
    }

    public function shifts(Request $request): JsonResponse
    {
        $filters = [
            'staff_id' => $request->query('staff_id'),
            'date_from' => $request->query('date_from'),
            'date_to' => $request->query('date_to'),
        ];

        $shifts = $this->staffService->getShifts(array_filter($filters));

        return response()->json([
            'success' => true,
            'data' => $shifts,
        ]);
    }

    public function startShift(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'staff_id' => 'required|exists:staff,id',
            'opening_cash' => 'required|integer|min:0',
        ]);

        $shift = $this->staffService->startShift([
            'staff_id' => $validated['staff_id'],
            'start_time' => now(),
            'opening_cash' => $validated['opening_cash'],
        ]);

        return response()->json([
            'success' => true,
            'data' => $shift,
            'message' => 'Shift started successfully',
        ]);
    }

    public function endShift(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'closing_cash' => 'required|integer|min:0',
        ]);

        $shift = $this->staffService->endShift($id, [
            'closing_cash' => $validated['closing_cash'],
        ]);

        return response()->json([
            'success' => true,
            'data' => $shift,
            'message' => 'Shift ended successfully',
        ]);
    }

    public function activeShifts(): JsonResponse
    {
        $shifts = $this->staffService->getActiveShifts();

        return response()->json([
            'success' => true,
            'data' => $shifts,
        ]);
    }

    public function getShiftSummary(int $id): JsonResponse
    {
        $summary = $this->staffService->getShiftSummary($id);

        return response()->json([
            'success' => true,
            'data' => $summary,
        ]);
    }
}
