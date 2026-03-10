<?php

namespace App\Modules\Staff\Repositories;

use App\Models\Staff;
use App\Models\Shift;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class StaffRepository
{
    public function __construct(
        private Staff $staff,
        private Shift $shift
    ) {}

    public function getAll(array $filters = [], int $perPage = 20)
    {
        $query = $this->staff->query();

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('email', 'like', '%' . $filters['search'] . '%');
            });
        }

        if (!empty($filters['role'])) {
            $query->where('role', $filters['role']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        return $query->orderBy('name')->paginate($perPage);
    }

    public function getById(int $id)
    {
        return $this->staff->with(['shifts'])->findOrFail($id);
    }

    public function create(array $data): Staff
    {
        return $this->staff->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => $data['role'] ?? 'staff',
            'is_active' => $data['is_active'] ?? true,
        ]);
    }

    public function update(Staff $staff, array $data): Staff
    {
        $updateData = [
            'name' => $data['name'] ?? $staff->name,
            'email' => $data['email'] ?? $staff->email,
            'role' => $data['role'] ?? $staff->role,
            'is_active' => $data['is_active'] ?? $staff->is_active,
        ];

        // Update password only if provided
        if (!empty($data['password'])) {
            $updateData['password'] = Hash::make($data['password']);
        }

        $staff->update($updateData);

        return $staff->fresh();
    }

    public function delete(Staff $staff): bool
    {
        return $staff->delete();
    }

    public function getShifts(array $filters = [])
    {
        $query = $this->shift->with(['staff']);

        if (!empty($filters['staff_id'])) {
            $query->where('staff_id', $filters['staff_id']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('start_time', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('start_time', '<=', $filters['date_to']);
        }

        return $query->orderBy('start_time', 'desc')->paginate(20);
    }

    public function createShift(array $data): Shift
    {
        return $this->shift->create([
            'staff_id' => $data['staff_id'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'] ?? null,
            'opening_cash' => $data['opening_cash'] ?? 0,
            'closing_cash' => $data['closing_cash'] ?? null,
            'expected_cash' => $data['expected_cash'] ?? null,
            'cash_difference' => $data['cash_difference'] ?? null,
        ]);
    }

    public function updateShift(Shift $shift, array $data): Shift
    {
        $shift->update($data);
        return $shift->fresh();
    }

    public function endShift(int $shiftId, array $data): Shift
    {
        $shift = $this->shift->findOrFail($shiftId);
        
        // Calculate expected cash from orders
        $expectedCash = DB::table('orders')
            ->where('shift_id', $shiftId)
            ->where('payment_status', 'paid')
            ->sum('grand_total');

        $shift->update([
            'end_time' => $data['end_time'] ?? now(),
            'closing_cash' => $data['closing_cash'],
            'expected_cash' => $expectedCash,
            'cash_difference' => $data['closing_cash'] - $expectedCash,
        ]);

        return $shift->fresh();
    }

    public function getActiveShifts()
    {
        return $this->shift->whereNull('end_time')
            ->whereDate('start_time', today())
            ->with(['staff'])
            ->get();
    }

    public function getCurrentShiftForStaff(int $staffId)
    {
        return $this->shift->where('staff_id', $staffId)
            ->whereNull('end_time')
            ->latest('start_time')
            ->first();
    }

    public function getShiftSummary(int $shiftId)
    {
        $shift = $this->shift->findOrFail($shiftId);

        // Calculate expected cash from orders
        $expectedCash = DB::table('orders')
            ->where('shift_id', $shiftId)
            ->where('payment_status', 'paid')
            ->sum('grand_total');

        return [
            'shift_id' => $shiftId,
            'staff_id' => $shift->staff_id,
            'staff_name' => $shift->staff->name ?? null,
            'start_time' => $shift->start_time,
            'opening_cash' => $shift->opening_cash,
            'expected_cash' => $expectedCash,
            'total_orders' => DB::table('orders')
                ->where('shift_id', $shiftId)
                ->count(),
        ];
    }
}
