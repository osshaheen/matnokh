<?php

namespace App\Http\Controllers\Api\Concerns;

use App\Models\Setting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Shared list behaviour for the dashboard tables: free-text search,
 * column filters, date range, sorting and pagination — all opt-in per
 * controller so no endpoint exposes a column it didn't declare.
 */
trait HandlesResourceQuery
{
    /**
     * @param  array<int,string>  $searchable  columns matched by `?search=`
     * @param  array<string,string>  $filters   request key => column
     * @param  array<int,string>  $sortable    columns allowed in `?sort=`
     */
    protected function listing(
        Builder $query,
        Request $request,
        array $searchable = [],
        array $filters = [],
        array $sortable = ['id'],
        string $dateColumn = 'created_at',
    ): LengthAwarePaginator {
        if ($searchable && ($term = trim((string) $request->query('search', ''))) !== '') {
            $query->where(function (Builder $q) use ($searchable, $term) {
                foreach ($searchable as $column) {
                    str_contains($column, '.')
                        ? $this->searchRelation($q, $column, $term)
                        : $q->orWhere($column, 'like', "%{$term}%");
                }
            });
        }

        foreach ($filters as $key => $column) {
            $value = $request->query($key);
            if ($value === null || $value === '') {
                continue;
            }
            is_array($value)
                ? $query->whereIn($column, $value)
                : $query->where($column, $value);
        }

        if ($from = $request->query('from')) {
            $query->whereDate($dateColumn, '>=', $from);
        }
        if ($to = $request->query('to')) {
            $query->whereDate($dateColumn, '<=', $to);
        }

        $sort = (string) $request->query('sort', 'id');
        $dir = strtolower((string) $request->query('dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $query->orderBy(in_array($sort, $sortable, true) ? $sort : 'id', $dir);

        $perPage = min(max((int) $request->query('per_page', 15), 1), 100);

        return $query->paginate($perPage)->withQueryString();
    }

    /** `relation.column` search — e.g. `customer.name`. */
    protected function searchRelation(Builder $query, string $path, string $term): void
    {
        [$relation, $column] = explode('.', $path, 2);
        $query->orWhereHas($relation, fn (Builder $q) => $q->where($column, 'like', "%{$term}%"));
    }

    /** Blocks destructive calls while the platform-wide switch is off. */
    protected function guardDeletion(): void
    {
        if (! Setting::get('deletion_enabled')) {
            throw new HttpException(403, 'الحذف معطّل من إعدادات النظام');
        }
    }

    protected function guardTrash(): void
    {
        if (! Setting::get('trash_enabled')) {
            throw new HttpException(403, 'سلّة المحذوفات معطّلة من إعدادات النظام');
        }
    }

    protected function guardRestore(): void
    {
        $this->guardTrash();

        if (! Setting::get('restore_enabled')) {
            throw new HttpException(403, 'الاستعادة معطّلة من إعدادات النظام');
        }
    }

    /** @throws ValidationException */
    protected function fail(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }
}
