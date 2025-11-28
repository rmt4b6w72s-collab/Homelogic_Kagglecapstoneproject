<?php

namespace App\Http\Controllers\Api;

use App\Models\BillingInvoice;
use App\Models\InvoiceItem;
use App\Constants\Modules;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class BillingInvoiceController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        if ($error = $this->requireModuleAccess(Modules::BILLING_EXPENSES)) {
            return $error;
        }

        $query = BillingInvoice::with(['facility', 'branch', 'resident', 'items.category', 'createdBy']);

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->get('status'));
        }

        // Filter by resident
        if ($request->has('resident_id')) {
            $query->where('resident_id', $request->get('resident_id'));
        }

        // Filter by date range
        if ($request->has('start_date')) {
            $query->where('invoice_date', '>=', $request->get('start_date'));
        }
        if ($request->has('end_date')) {
            $query->where('invoice_date', '<=', $request->get('end_date'));
        }

        // Filter by due date
        if ($request->has('due_before')) {
            $query->where('due_date', '<=', $request->get('due_before'));
        }

        // Filter by branch
        $this->applyBranchFilter($query, $request);

        // Search
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                  ->orWhereHas('resident', function($q) use ($search) {
                      $q->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%");
                  });
            });
        }

        return $this->paginate($request, $query->orderBy('invoice_date', 'desc'));
    }

    public function store(Request $request): JsonResponse
    {
        if ($error = $this->requireModuleAccess(Modules::BILLING_EXPENSES)) {
            return $error;
        }

        $validated = $request->validate([
            'branch_id' => 'nullable|exists:branches,id',
            'resident_id' => 'required|exists:residents,id',
            'invoice_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:invoice_date',
            'tax_amount' => 'nullable|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.expense_category_id' => 'nullable|exists:expense_categories,id',
        ]);

        DB::beginTransaction();
        try {
            $invoiceData = [
                'facility_id' => auth()->user()->facility_id,
                'branch_id' => $validated['branch_id'] ?? null,
                'resident_id' => $validated['resident_id'],
                'invoice_date' => $validated['invoice_date'],
                'due_date' => $validated['due_date'],
                'tax_amount' => $validated['tax_amount'] ?? 0,
                'discount_amount' => $validated['discount_amount'] ?? 0,
                'notes' => $validated['notes'] ?? null,
                'created_by' => auth()->id(),
                'status' => 'draft',
            ];

            // If branch_id not provided, infer from resident
            if (!$invoiceData['branch_id']) {
                $resident = \App\Models\Resident::find($validated['resident_id']);
                if ($resident) {
                    $invoiceData['branch_id'] = $resident->branch_id;
                }
            }

            $invoice = BillingInvoice::create($invoiceData);

            // Create invoice items
            $subtotal = 0;
            foreach ($validated['items'] as $itemData) {
                $total = $itemData['quantity'] * $itemData['unit_price'];
                $subtotal += $total;

                InvoiceItem::create([
                    'billing_invoice_id' => $invoice->id,
                    'description' => $itemData['description'],
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['unit_price'],
                    'total' => $total,
                    'expense_category_id' => $itemData['expense_category_id'] ?? null,
                ]);
            }

            // Calculate and update totals
            $invoice->subtotal = $subtotal;
            $invoice->total_amount = $subtotal + $invoice->tax_amount - $invoice->discount_amount;
            $invoice->save();

            DB::commit();

            return $this->success($invoice->load(['facility', 'branch', 'resident', 'items.category', 'createdBy']), 'Invoice created successfully', 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Failed to create invoice: ' . $e->getMessage(), 500);
        }
    }

    public function show($id): JsonResponse
    {
        if ($error = $this->requireModuleAccess(Modules::BILLING_EXPENSES)) {
            return $error;
        }

        $invoice = BillingInvoice::with(['facility', 'branch', 'resident', 'items.category', 'createdBy'])
            ->findOrFail($id);

        return $this->success($invoice);
    }

    public function update(Request $request, $id): JsonResponse
    {
        if ($error = $this->requireModuleAccess(Modules::BILLING_EXPENSES)) {
            return $error;
        }

        $invoice = BillingInvoice::findOrFail($id);

        // Don't allow updates to paid or cancelled invoices
        if (in_array($invoice->status, ['paid', 'cancelled'])) {
            return $this->error('Cannot update paid or cancelled invoice', 400);
        }

        $validated = $request->validate([
            'invoice_date' => 'sometimes|required|date',
            'due_date' => 'sometimes|required|date|after_or_equal:invoice_date',
            'tax_amount' => 'nullable|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'items' => 'sometimes|required|array|min:1',
            'items.*.id' => 'nullable|exists:invoice_items,id',
            'items.*.description' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.expense_category_id' => 'nullable|exists:expense_categories,id',
        ]);

        DB::beginTransaction();
        try {
            $invoice->update([
                'invoice_date' => $validated['invoice_date'] ?? $invoice->invoice_date,
                'due_date' => $validated['due_date'] ?? $invoice->due_date,
                'tax_amount' => $validated['tax_amount'] ?? $invoice->tax_amount,
                'discount_amount' => $validated['discount_amount'] ?? $invoice->discount_amount,
                'notes' => $validated['notes'] ?? $invoice->notes,
            ]);

            // Update items if provided
            if (isset($validated['items'])) {
                $existingItemIds = collect($validated['items'])->pluck('id')->filter();
                $invoice->items()->whereNotIn('id', $existingItemIds)->delete();

                $subtotal = 0;
                foreach ($validated['items'] as $itemData) {
                    $total = $itemData['quantity'] * $itemData['unit_price'];
                    $subtotal += $total;

                    if (isset($itemData['id'])) {
                        InvoiceItem::where('id', $itemData['id'])->update([
                            'description' => $itemData['description'],
                            'quantity' => $itemData['quantity'],
                            'unit_price' => $itemData['unit_price'],
                            'total' => $total,
                            'expense_category_id' => $itemData['expense_category_id'] ?? null,
                        ]);
                    } else {
                        InvoiceItem::create([
                            'billing_invoice_id' => $invoice->id,
                            'description' => $itemData['description'],
                            'quantity' => $itemData['quantity'],
                            'unit_price' => $itemData['unit_price'],
                            'total' => $total,
                            'expense_category_id' => $itemData['expense_category_id'] ?? null,
                        ]);
                    }
                }

                $invoice->subtotal = $subtotal;
                $invoice->total_amount = $subtotal + $invoice->tax_amount - $invoice->discount_amount;
                $invoice->save();
            }

            DB::commit();

            return $this->success($invoice->load(['facility', 'branch', 'resident', 'items.category', 'createdBy']), 'Invoice updated successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Failed to update invoice: ' . $e->getMessage(), 500);
        }
    }

    public function destroy($id): JsonResponse
    {
        if ($error = $this->requireModuleAccess(Modules::BILLING_EXPENSES)) {
            return $error;
        }

        $invoice = BillingInvoice::findOrFail($id);

        // Don't allow deletion of sent/paid invoices
        if (in_array($invoice->status, ['sent', 'paid'])) {
            return $this->error('Cannot delete sent or paid invoice', 400);
        }

        $invoice->delete();

        return $this->success(null, 'Invoice deleted successfully');
    }

    public function send($id): JsonResponse
    {
        if ($error = $this->requireModuleAccess(Modules::BILLING_EXPENSES)) {
            return $error;
        }

        $invoice = BillingInvoice::findOrFail($id);

        if ($invoice->status !== 'draft') {
            return $this->error('Only draft invoices can be sent', 400);
        }

        $invoice->update(['status' => 'sent']);

        return $this->success($invoice->load(['facility', 'branch', 'resident', 'items.category', 'createdBy']), 'Invoice sent successfully');
    }

    public function markAsPaid(Request $request, $id): JsonResponse
    {
        if ($error = $this->requireModuleAccess(Modules::BILLING_EXPENSES)) {
            return $error;
        }

        $invoice = BillingInvoice::findOrFail($id);

        $validated = $request->validate([
            'payment_date' => 'nullable|date',
            'payment_method' => 'nullable|string|max:255',
        ]);

        $invoice->markAsPaid(
            $validated['payment_date'] ?? now(),
            $validated['payment_method'] ?? null
        );

        return $this->success($invoice->load(['facility', 'branch', 'resident', 'items.category', 'createdBy']), 'Invoice marked as paid successfully');
    }
}

