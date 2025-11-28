<?php

namespace App\Http\Controllers\Api;

use App\Models\Expense;
use App\Models\ExpenseApproval;
use App\Constants\Modules;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class ExpenseController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        if ($error = $this->requireModuleAccess(Modules::BILLING_EXPENSES)) {
            return $error;
        }

        $query = Expense::with(['facility', 'branch', 'category', 'resident', 'createdBy', 'approvedBy'])
            ->orderBy('expense_date', 'desc');

        // Filter by date range
        if ($request->has('start_date')) {
            $query->where('expense_date', '>=', $request->get('start_date'));
        }
        if ($request->has('end_date')) {
            $query->where('expense_date', '<=', $request->get('end_date'));
        }

        // Filter by category
        if ($request->has('category_id')) {
            $query->where('expense_category_id', $request->get('category_id'));
        }

        // Filter by status
        if ($request->has('payment_status')) {
            $query->where('payment_status', $request->get('payment_status'));
        }

        // Filter by resident
        if ($request->has('resident_id')) {
            $query->where('resident_id', $request->get('resident_id'));
        }

        // Filter by branch
        $this->applyBranchFilter($query, $request);

        // Search
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('vendor_name', 'like', "%{$search}%")
                  ->orWhere('invoice_number', 'like', "%{$search}%");
            });
        }

        return $this->paginate($request, $query->orderBy('expense_date', 'desc'));
    }

    public function store(Request $request): JsonResponse
    {
        if ($error = $this->requireModuleAccess(Modules::BILLING_EXPENSES)) {
            return $error;
        }

        $validated = $request->validate([
            'branch_id' => 'nullable|exists:branches,id',
            'expense_category_id' => 'required|exists:expense_categories,id',
            'resident_id' => 'nullable|exists:residents,id',
            'vendor_name' => 'nullable|string|max:255',
            'description' => 'required|string',
            'amount' => 'required|numeric|min:0',
            'currency' => 'nullable|string|max:3',
            'expense_date' => 'required|date',
            'payment_date' => 'nullable|date',
            'payment_method' => 'nullable|in:cash,check,card,transfer,other',
            'payment_status' => 'nullable|in:pending,paid,overdue',
            'invoice_number' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $validated['facility_id'] = auth()->user()->facility_id;
        $validated['created_by'] = auth()->id();
        $validated['currency'] = $validated['currency'] ?? 'USD';
        $validated['payment_status'] = $validated['payment_status'] ?? 'pending';

        $expense = Expense::create($validated);

        return $this->success($expense->load(['facility', 'branch', 'category', 'resident', 'createdBy']), 'Expense created successfully', 201);
    }

    public function show($id): JsonResponse
    {
        if ($error = $this->requireModuleAccess(Modules::BILLING_EXPENSES)) {
            return $error;
        }

        $expense = Expense::with(['facility', 'branch', 'category', 'resident', 'createdBy', 'approvedBy', 'approvals.approver'])
            ->findOrFail($id);

        return $this->success($expense);
    }

    public function update(Request $request, $id): JsonResponse
    {
        if ($error = $this->requireModuleAccess(Modules::BILLING_EXPENSES)) {
            return $error;
        }

        $expense = Expense::findOrFail($id);

        $validated = $request->validate([
            'branch_id' => 'nullable|exists:branches,id',
            'expense_category_id' => 'sometimes|required|exists:expense_categories,id',
            'resident_id' => 'nullable|exists:residents,id',
            'vendor_name' => 'nullable|string|max:255',
            'description' => 'sometimes|required|string',
            'amount' => 'sometimes|required|numeric|min:0',
            'currency' => 'nullable|string|max:3',
            'expense_date' => 'sometimes|required|date',
            'payment_date' => 'nullable|date',
            'payment_method' => 'nullable|in:cash,check,card,transfer,other',
            'payment_status' => 'nullable|in:pending,paid,overdue',
            'invoice_number' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $expense->update($validated);

        return $this->success($expense->load(['facility', 'branch', 'category', 'resident', 'createdBy', 'approvedBy']), 'Expense updated successfully');
    }

    public function destroy($id): JsonResponse
    {
        if ($error = $this->requireModuleAccess(Modules::BILLING_EXPENSES)) {
            return $error;
        }

        $expense = Expense::findOrFail($id);
        $expense->delete();

        return $this->success(null, 'Expense deleted successfully');
    }

    public function approve(Request $request, $id): JsonResponse
    {
        if ($error = $this->requireModuleAccess(Modules::BILLING_EXPENSES)) {
            return $error;
        }

        $expense = Expense::findOrFail($id);

        $validated = $request->validate([
            'comments' => 'nullable|string',
        ]);

        $expense->update([
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        // Create approval record
        ExpenseApproval::create([
            'expense_id' => $expense->id,
            'approver_id' => auth()->id(),
            'status' => 'approved',
            'comments' => $validated['comments'] ?? null,
            'approved_at' => now(),
        ]);

        return $this->success($expense->load(['facility', 'branch', 'category', 'resident', 'createdBy', 'approvedBy']), 'Expense approved successfully');
    }

    public function markAsPaid(Request $request, $id): JsonResponse
    {
        if ($error = $this->requireModuleAccess(Modules::BILLING_EXPENSES)) {
            return $error;
        }

        $expense = Expense::findOrFail($id);

        $validated = $request->validate([
            'payment_date' => 'nullable|date',
            'payment_method' => 'nullable|in:cash,check,card,transfer,other',
        ]);

        $expense->update([
            'payment_status' => 'paid',
            'payment_date' => $validated['payment_date'] ?? now(),
            'payment_method' => $validated['payment_method'] ?? null,
        ]);

        return $this->success($expense->load(['facility', 'branch', 'category', 'resident', 'createdBy']), 'Expense marked as paid successfully');
    }

    public function uploadReceipt(Request $request, $id): JsonResponse
    {
        if ($error = $this->requireModuleAccess(Modules::BILLING_EXPENSES)) {
            return $error;
        }

        $expense = Expense::findOrFail($id);

        $validated = $request->validate([
            'receipt' => 'required|file|mimes:jpeg,jpg,png,pdf|max:10240',
        ]);

        if ($expense->receipt_url) {
            Storage::disk('public')->delete($expense->receipt_url);
        }

        $path = $request->file('receipt')->store('expense-receipts', 'public');
        $expense->update(['receipt_url' => $path]);

        return $this->success([
            'receipt_url' => Storage::disk('public')->url($path),
        ], 'Receipt uploaded successfully');
    }
}

