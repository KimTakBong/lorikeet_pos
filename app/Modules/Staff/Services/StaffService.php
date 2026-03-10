<?php

namespace App\Modules\Staff\Services;

use App\Modules\Staff\Repositories\StaffRepository;

class StaffService
{
    public function __construct(
        private StaffRepository $staffRepo
    ) {}

    public function getStaff(array $filters = [])
    {
        return $this->staffRepo->getAll($filters);
    }

    public function getStaffById(int $id)
    {
        return $this->staffRepo->getById($id);
    }

    public function createStaff(array $data)
    {
        return $this->staffRepo->create($data);
    }

    public function updateStaff(int $id, array $data)
    {
        $staff = $this->staffRepo->getById($id);
        return $this->staffRepo->update($staff, $data);
    }

    public function deleteStaff(int $id)
    {
        $staff = $this->staffRepo->getById($id);
        return $this->staffRepo->delete($staff);
    }

    public function getShifts(array $filters = [])
    {
        return $this->staffRepo->getShifts($filters);
    }

    public function startShift(array $data)
    {
        return $this->staffRepo->createShift($data);
    }

    public function endShift(int $shiftId, array $data)
    {
        return $this->staffRepo->endShift($shiftId, $data);
    }

    public function getActiveShifts()
    {
        return $this->staffRepo->getActiveShifts();
    }

    public function getCurrentShiftForStaff(int $staffId)
    {
        return $this->staffRepo->getCurrentShiftForStaff($staffId);
    }

    public function getShiftSummary(int $shiftId)
    {
        return $this->staffRepo->getShiftSummary($shiftId);
    }
}
