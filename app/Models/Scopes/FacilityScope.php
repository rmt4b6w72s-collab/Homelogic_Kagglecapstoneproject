<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class FacilityScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        // Never apply scope to User model to prevent infinite recursion during auth
        if ($model instanceof \App\Models\User) {
            return;
        }

        $user = Auth::user();
        
        // Super admins can see all data (no scope applied)
        if ($user && $user->role === 'super_admin') {
            return;
        }

        // Get facility from app container (set by middleware)
        try {
            $facility = app()->bound('facility') ? app('facility') : null;
        } catch (\Exception $e) {
            // If facility is not bound or there's an error, set to null
            $facility = null;
        }
        
        if ($facility) {
            // If model has direct facility_id, filter by it
            if ($model->getConnection()->getSchemaBuilder()->hasColumn($model->getTable(), 'facility_id')) {
                $builder->where('facility_id', $facility->id);
            } 
            // If model has branch_id, filter through branch->facility_id
            elseif ($model->getConnection()->getSchemaBuilder()->hasColumn($model->getTable(), 'branch_id')) {
                $builder->whereHas('branch', function ($query) use ($facility) {
                    $query->where('facility_id', $facility->id);
                });
            }
            // If model has assigned_branch_id, filter through branch->facility_id
            elseif ($model->getConnection()->getSchemaBuilder()->hasColumn($model->getTable(), 'assigned_branch_id')) {
                $builder->whereHas('assignedBranch', function ($query) use ($facility) {
                    $query->where('facility_id', $facility->id);
                });
            }
        } elseif ($user && $user->facility_id) {
            // Fallback to user's facility_id if no facility in context
            if ($model->getConnection()->getSchemaBuilder()->hasColumn($model->getTable(), 'facility_id')) {
                $builder->where('facility_id', $user->facility_id);
            } elseif ($model->getConnection()->getSchemaBuilder()->hasColumn($model->getTable(), 'branch_id')) {
                $builder->whereHas('branch', function ($query) use ($user) {
                    $query->where('facility_id', $user->facility_id);
                });
            } elseif ($model->getConnection()->getSchemaBuilder()->hasColumn($model->getTable(), 'assigned_branch_id')) {
                $builder->whereHas('assignedBranch', function ($query) use ($user) {
                    $query->where('facility_id', $user->facility_id);
                });
            }
        }
    }
}

