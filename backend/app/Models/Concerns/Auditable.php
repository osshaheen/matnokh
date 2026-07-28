<?php

namespace App\Models\Concerns;

use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Records create/update/delete activity for a model so the dashboard can
 * show an audit trail (سجل النشاطات) without each model repeating options.
 */
trait Auditable
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        // these models use `$guarded = []`, so "unguarded" is every column
        return LogOptions::defaults()
            ->logUnguarded()
            ->dontLogIfAttributesChangedOnly(['updated_at'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName(class_basename($this));
    }
}
