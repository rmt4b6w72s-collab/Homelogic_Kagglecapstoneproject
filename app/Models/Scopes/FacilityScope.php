<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class FacilityScope implements Scope
{
    private static array $columnCache = [];

    private function tableHasColumn(Model $model, string $column): bool
    {
        $table = $model->getTable();
        $key = "{$table}.{$column}";

        if (!isset(static::$columnCache[$key])) {
            static::$columnCache[$key] = $model->getConnection()
                ->getSchemaBuilder()->hasColumn($table, $column);
        }

        return static::$columnCache[$key];
    }

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
            $facility = null;
        }

        $facilityId = $facility?->id ?? ($user?->facility_id ?: null);

        if (!$facilityId) {
            return;
        }

        if ($this->tableHasColumn($model, 'facility_id')) {
            $builder->where('facility_id', $facilityId);
        } elseif ($this->tableHasColumn($model, 'branch_id')) {
            $builder->whereHas('branch', function ($query) use ($facilityId) {
                $query->where('facility_id', $facilityId);
            });
        } elseif ($this->tableHasColumn($model, 'assigned_branch_id')) {
            $builder->whereHas('assignedBranch', function ($query) use ($facilityId) {
                $query->where('facility_id', $facilityId);
            });
        }
    }
}

