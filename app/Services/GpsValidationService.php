<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\OfficeLocation;
use Illuminate\Support\Collection;

class GpsValidationService
{
    /**
     * Validate if employee's current location is within any assigned office radius.
     *
     * @return array{valid: bool, office_location_id: int|null, distance: float|null, reason: string|null}
     */
    public function validateEmployeeLocation(Employee $employee, float $latitude, float $longitude): array
    {
        $offices = $this->getAssignedActiveOffices($employee);

        if ($offices->isEmpty()) {
            // Check if employee has any offices assigned (even inactive)
            $hasAnyOffices = $employee->officeLocations()->exists();

            return [
                'valid' => false,
                'office_location_id' => null,
                'distance' => null,
                'reason' => $hasAnyOffices ? 'no_active_offices' : 'no_assigned_offices',
            ];
        }

        // Find the closest office within radius
        $closestWithinRadius = null;
        $closestDistance = PHP_FLOAT_MAX;

        foreach ($offices as $office) {
            $distance = $office->distanceTo($latitude, $longitude);

            if ($office->isWithinRadius($latitude, $longitude) && $distance < $closestDistance) {
                $closestWithinRadius = $office;
                $closestDistance = $distance;
            }
        }

        if ($closestWithinRadius) {
            return [
                'valid' => true,
                'office_location_id' => $closestWithinRadius->id,
                'distance' => $closestDistance,
                'reason' => null,
            ];
        }

        return [
            'valid' => false,
            'office_location_id' => null,
            'distance' => null,
            'reason' => 'outside_radius',
        ];
    }

    /**
     * Get all active office locations assigned to an employee.
     */
    public function getAssignedActiveOffices(Employee $employee): Collection
    {
        return $employee->officeLocations()
            ->where('is_active', true)
            ->get();
    }

    /**
     * Find the nearest office from employee's assigned offices.
     *
     * @return array{office: OfficeLocation, distance: float}|null
     */
    public function findNearestOffice(Employee $employee, float $latitude, float $longitude): ?array
    {
        $offices = $this->getAssignedActiveOffices($employee);

        if ($offices->isEmpty()) {
            return null;
        }

        $nearest = null;
        $shortestDistance = PHP_FLOAT_MAX;

        foreach ($offices as $office) {
            $distance = $office->distanceTo($latitude, $longitude);

            if ($distance < $shortestDistance) {
                $nearest = $office;
                $shortestDistance = $distance;
            }
        }

        return [
            'office' => $nearest,
            'distance' => $shortestDistance,
        ];
    }

    /**
     * Calculate distance between two GPS coordinates using Haversine formula.
     *
     * @return float Distance in meters
     */
    public function calculateDistance(
        float $lat1,
        float $lon1,
        float $lat2,
        float $lon2
    ): float {
        $earthRadius = 6371000; // Earth's radius in meters

        $latFrom = deg2rad($lat1);
        $lonFrom = deg2rad($lon1);
        $latTo = deg2rad($lat2);
        $lonTo = deg2rad($lon2);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
            cos($latFrom) * cos($latTo) *
            sin($lonDelta / 2) * sin($lonDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return round($earthRadius * $c, 2);
    }
}
