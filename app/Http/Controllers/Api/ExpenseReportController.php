<?php

namespace App\Http\Controllers\Api;

use App\Models\Expense;
use App\Models\BillingInvoice;
use App\Constants\Modules;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ExpenseReportController extends BaseApiController
{
    public function summary(Request $request): JsonResponse
    {
        if ($error = $this->requireModuleAccess(Modules::BILLING_EXPENSES)) {
            return $error;
        }

        $startDate = $request->get('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->get('end_date', now()->endOfMonth()->toDateString());

        $query = Expense::whereBetween('expense_date', [$startDate, $endDate]);
        $this->applyBranchFilter($query, $request);

        $summary = [
            'total_expenses' => $query->sum('amount'),
            'total_paid' => (clone $query)->where('payment_status', 'paid')->sum('amount'),
            'total_pending' => (clone $query)->where('payment_status', 'pending')->sum('amount'),
            'total_overdue' => (clone $query)->where('payment_status', 'overdue')->sum('amount'),
            'expense_count' => $query->count(),
            'paid_count' => (clone $query)->where('payment_status', 'paid')->count(),
            'pending_count' => (clone $query)->where('payment_status', 'pending')->count(),
            'overdue_count' => (clone $query)->where('payment_status', 'overdue')->count(),
        ];

        return $this->success($summary);
    }

    public function byCategory(Request $request): JsonResponse
    {
        if ($error = $this->requireModuleAccess(Modules::BILLING_EXPENSES)) {
            return $error;
        }

        $startDate = $request->get('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->get('end_date', now()->endOfMonth()->toDateString());

        $query = Expense::whereBetween('expense_date', [$startDate, $endDate])
            ->with('category');
        $this->applyBranchFilter($query, $request);

        $byCategory = $query->get()
            ->groupBy('expense_category_id')
            ->map(function ($expenses) {
                $category = $expenses->first()->category;
                return [
                    'category_id' => $category->id,
                    'category_name' => $category->name,
                    'category_type' => $category->type,
                    'total_amount' => $expenses->sum('amount'),
                    'count' => $expenses->count(),
                ];
            })
            ->values();

        return $this->success($byCategory);
    }

    public function byDateRange(Request $request): JsonResponse
    {
        if ($error = $this->requireModuleAccess(Modules::BILLING_EXPENSES)) {
            return $error;
        }

        $startDate = $request->get('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->get('end_date', now()->endOfMonth()->toDateString());
        $groupBy = $request->get('group_by', 'day'); // day, week, month

        $query = Expense::whereBetween('expense_date', [$startDate, $endDate]);
        $this->applyBranchFilter($query, $request);

        $expenses = $query->get();

        $grouped = $expenses->groupBy(function ($expense) use ($groupBy) {
            $date = \Carbon\Carbon::parse($expense->expense_date);
            switch ($groupBy) {
                case 'week':
                    return $date->format('Y-W');
                case 'month':
                    return $date->format('Y-m');
                default:
                    return $date->format('Y-m-d');
            }
        })->map(function ($expenses, $key) {
            return [
                'date' => $key,
                'total_amount' => $expenses->sum('amount'),
                'count' => $expenses->count(),
            ];
        })->values();

        return $this->success($grouped);
    }

    public function residentBilling(Request $request): JsonResponse
    {
        if ($error = $this->requireModuleAccess(Modules::BILLING_EXPENSES)) {
            return $error;
        }

        $startDate = $request->get('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->get('end_date', now()->endOfMonth()->toDateString());

        // Get expenses linked to residents
        $expenseQuery = Expense::whereBetween('expense_date', [$startDate, $endDate])
            ->whereNotNull('resident_id')
            ->with(['resident', 'category']);
        $this->applyBranchFilter($expenseQuery, $request);

        // Get invoices
        $invoiceQuery = BillingInvoice::whereBetween('invoice_date', [$startDate, $endDate])
            ->with(['resident', 'items']);
        $this->applyBranchFilter($invoiceQuery, $request);

        $expenses = $expenseQuery->get();
        $invoices = $invoiceQuery->get();

        $byResident = collect()
            ->merge($expenses->groupBy('resident_id'))
            ->merge($invoices->groupBy('resident_id'))
            ->map(function ($items, $residentId) use ($expenses, $invoices) {
                $resident = $items->first()->resident ?? $invoices->where('resident_id', $residentId)->first()?->resident;
                $residentExpenses = $expenses->where('resident_id', $residentId);
                $residentInvoices = $invoices->where('resident_id', $residentId);

                return [
                    'resident_id' => $residentId,
                    'resident_name' => $resident ? $resident->name : 'Unknown',
                    'total_expenses' => $residentExpenses->sum('amount'),
                    'total_invoices' => $residentInvoices->sum('total_amount'),
                    'total_billing' => $residentExpenses->sum('amount') + $residentInvoices->sum('total_amount'),
                    'expense_count' => $residentExpenses->count(),
                    'invoice_count' => $residentInvoices->count(),
                ];
            })
            ->values();

        return $this->success($byResident);
    }

    public function vendorPayments(Request $request): JsonResponse
    {
        if ($error = $this->requireModuleAccess(Modules::BILLING_EXPENSES)) {
            return $error;
        }

        $startDate = $request->get('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->get('end_date', now()->endOfMonth()->toDateString());

        $query = Expense::whereBetween('expense_date', [$startDate, $endDate])
            ->whereNotNull('vendor_name');
        $this->applyBranchFilter($query, $request);

        $byVendor = $query->get()
            ->groupBy('vendor_name')
            ->map(function ($expenses) {
                return [
                    'vendor_name' => $expenses->first()->vendor_name,
                    'total_amount' => $expenses->sum('amount'),
                    'count' => $expenses->count(),
                    'paid_amount' => $expenses->where('payment_status', 'paid')->sum('amount'),
                    'pending_amount' => $expenses->where('payment_status', 'pending')->sum('amount'),
                ];
            })
            ->values()
            ->sortByDesc('total_amount')
            ->values();

        return $this->success($byVendor);
    }
}

