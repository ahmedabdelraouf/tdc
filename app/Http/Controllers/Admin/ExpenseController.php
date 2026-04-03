<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\User;
use App\Models\Car;
use App\Models\FuelType;
use App\Models\MaintenanceCategory;
use App\Models\ExpenseCategory;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ExpenseController extends Controller
{
    /**
     * Display a listing of expenses.
     */
    public function index(Request $request)
    {
        $query = Expense::with([
            'user', 
            'car', 
            'fuelType', 
            'maintenanceCategory', 
            'expenseCategory'
        ]);

        // Filters
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->has('car_id')) {
            $query->where('car_id', $request->car_id);
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('date', [$request->start_date, $request->end_date]);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('notes', 'like', "%{$search}%");
            });
        }

        $expenses = $query->orderBy('date', 'desc')
                          ->paginate($request->get('per_page', 15));

        return response()->json($expenses);
    }

    /**
     * Store a newly created expense.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'car_id' => 'required|exists:cars,id',
            'type' => 'required|in:fuel,maintenance,other',
            'fuel_type_id' => 'nullable|exists:fuel_types,id',
            'maintenance_category_id' => 'nullable|exists:maintenance_categories,id',
            'expense_category_id' => 'nullable|exists:expense_categories,id',
            'amount' => 'required|numeric|min:0',
            'quantity' => 'nullable|numeric|min:0',
            'price_per_unit' => 'nullable|numeric|min:0',
            'date' => 'required|date',
            'notes' => 'nullable|string',
            'receipt_image' => 'nullable|string',
        ]);

        if ($request->hasFile('receipt_image')) {
            $validated['receipt_image'] = $request->file('receipt_image')->store('expenses/receipts', 'r2');
        }

        // Auto-calculate amount if quantity and price_per_unit provided
        if (isset($validated['quantity']) && isset($validated['price_per_unit'])) {
            $validated['amount'] = $validated['quantity'] * $validated['price_per_unit'];
        }

        $expense = Expense::create($validated);

        AuditLog::log(
            'create',
            'expenses',
            auth()->id(),
            'expenses',
            $expense->id,
            null,
            $expense->toArray()
        );

        return response()->json([
            'message' => 'Expense created successfully',
            'expense' => $expense->load(['user', 'car', 'fuelType', 'maintenanceCategory', 'expenseCategory'])
        ], 201);
    }

    /**
     * Display the specified expense.
     */
    public function show(Expense $expense)
    {
        return response()->json($expense->load([
            'user', 'car', 'fuelType', 'maintenanceCategory', 'expenseCategory'
        ]));
    }

    /**
     * Update the specified expense.
     */
    public function update(Request $request, Expense $expense)
    {
        $oldValues = $expense->toArray();

        $validated = $request->validate([
            'user_id' => 'sometimes|required|exists:users,id',
            'car_id' => 'sometimes|required|exists:cars,id',
            'type' => 'sometimes|required|in:fuel,maintenance,other',
            'fuel_type_id' => 'nullable|exists:fuel_types,id',
            'maintenance_category_id' => 'nullable|exists:maintenance_categories,id',
            'expense_category_id' => 'nullable|exists:expense_categories,id',
            'amount' => 'sometimes|required|numeric|min:0',
            'quantity' => 'nullable|numeric|min:0',
            'price_per_unit' => 'nullable|numeric|min:0',
            'date' => 'sometimes|required|date',
            'notes' => 'nullable|string',
            'receipt_image' => 'nullable|string',
        ]);

        if ($request->hasFile('receipt_image')) {
            if ($expense->receipt_image) {
                Storage::disk('r2')->delete($expense->receipt_image);
            }
            $validated['receipt_image'] = $request->file('receipt_image')->store('expenses/receipts', 'r2');
        }

        // Auto-calculate amount if quantity and price_per_unit provided
        if (isset($validated['quantity']) && isset($validated['price_per_unit'])) {
            $validated['amount'] = $validated['quantity'] * $validated['price_per_unit'];
        }

        $expense->update($validated);

        AuditLog::log(
            'update',
            'expenses',
            auth()->id(),
            'expenses',
            $expense->id,
            $oldValues,
            $expense->fresh()->toArray()
        );

        return response()->json([
            'message' => 'Expense updated successfully',
            'expense' => $expense->fresh()->load(['user', 'car', 'fuelType', 'maintenanceCategory', 'expenseCategory'])
        ]);
    }

    /**
     * Remove the specified expense (soft delete).
     */
    public function destroy(Expense $expense)
    {
        $oldValues = $expense->toArray();

        // Delete receipt from R2
        if ($expense->receipt_image) {
            Storage::disk('r2')->delete($expense->receipt_image);
        }

        $expense->delete();

        AuditLog::log(
            'delete',
            'expenses',
            auth()->id(),
            'expenses',
            $expense->id,
            $oldValues,
            null
        );

        return response()->json([
            'message' => 'Expense deleted successfully'
        ]);
    }

    /**
     * Bulk delete expenses.
     */
    public function bulkDelete(Request $request)
    {
        $validated = $request->validate([
            'expense_ids' => 'required|array',
            'expense_ids.*' => 'exists:expenses,id',
        ]);

        $expenses = Expense::whereIn('id', $validated['expense_ids'])->get();

        foreach ($expenses as $expense) {
            if ($expense->receipt_image) {
                Storage::disk('r2')->delete($expense->receipt_image);
            }

            AuditLog::log(
                'bulk_delete',
                'expenses',
                auth()->id(),
                'expenses',
                $expense->id,
                $expense->toArray(),
                null
            );

            $expense->delete();
        }

        return response()->json([
            'message' => count($validated['expense_ids']) . ' expenses deleted successfully'
        ]);
    }

    /**
     * Export expenses to CSV.
     */
    public function export(Request $request)
    {
        $query = Expense::with(['user', 'car', 'fuelType', 'maintenanceCategory', 'expenseCategory']);

        // Apply filters
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('date', [$request->start_date, $request->end_date]);
        }

        $expenses = $query->get();

        $csvData = "ID,User,Car,Type,Category,Amount,Quantity,Price/Unit,Date,Notes,Created At\n";

        foreach ($expenses as $expense) {
            $category = '';
            switch ($expense->type) {
                case 'fuel':
                    $category = $expense->fuelType?->name ?? '';
                    break;
                case 'maintenance':
                    $category = $expense->maintenanceCategory?->name ?? '';
                    break;
                case 'other':
                    $category = $expense->expenseCategory?->name ?? '';
                    break;
            }

            $csvData .= "{$expense->id},\"{$expense->user->name}\",\"{$expense->car->full_description}\",{$expense->type},\"{$category}\",{$expense->amount},{$expense->quantity},{$expense->price_per_unit},{$expense->date},\"{$expense->notes}\",{$expense->created_at}\n";
        }

        return response($csvData)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="expenses_' . date('Y-m-d') . '.csv"');
    }

    /**
     * Get expense statistics.
     */
    public function statistics(Request $request)
    {
        $query = Expense::query();

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('date', [$request->start_date, $request->end_date]);
        }

        $totalAmount = $query->sum('amount');
        
        $byType = Expense::selectRaw('type, SUM(amount) as total')
            ->groupBy('type')
            ->get()
            ->pluck('total', 'type');

        $byMonth = Expense::selectRaw('DATE_FORMAT(date, "%Y-%m") as month, SUM(amount) as total')
            ->groupBy('month')
            ->orderBy('month')
            ->limit(12)
            ->get();

        return response()->json([
            'total_amount' => $totalAmount,
            'by_type' => $byType,
            'by_month' => $byMonth,
        ]);
    }
}
