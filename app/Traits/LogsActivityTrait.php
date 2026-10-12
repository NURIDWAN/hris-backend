<?php

namespace App\Traits;

use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

trait LogsActivityTrait
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly($this->getLogAttributes())
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName($this->getLogName());
    }

    /**
     * Get the attributes to log. Override in model if needed.
     */
    protected function getLogAttributes(): array
    {
        return ['*'];
    }

    /**
     * Get the log name for this model. Override in model if needed.
     */
    protected function getLogName(): string
    {
        // Convert class name to snake_case log name
        // e.g., Employee -> employee, LeaveRequest -> leave_request
        $className = class_basename($this);

        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $className));
    }

    /**
     * Customize activity before it's saved.
     */
    public function tapActivity(\Spatie\Activitylog\Contracts\Activity $activity, string $eventName): void
    {
        // Set company_id from the model if it has one
        if (property_exists($this, 'company_id') || $this->getAttribute('company_id')) {
            $activity->company_id = $this->company_id;
        } elseif (app()->bound('tenant') && app('tenant')) {
            $activity->company_id = app('tenant')->id;
        }
    }
}
