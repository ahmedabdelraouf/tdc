<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Model as CarModel;
use App\Models\Year;
use App\Models\Color;
use App\Models\Shape;
use App\Models\FuelType;
use App\Models\MaintenanceCategory;
use App\Models\ExpenseCategory;
use App\Models\Cylinder;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class StaticDataController extends Controller
{
    /**
     * Handle generic CRUD for static data models.
     */
    public function index(Request $request, string $model)
    {
        $modelClass = $this->getModelClass($model);
        
        if (!$modelClass) {
            return response()->json(['message' => 'Invalid model type'], 400);
        }

        $query = $modelClass::query();

        if ($request->has('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }

        // Special handling for models with brand_id
        if ($model === 'models' && $request->has('brand_id')) {
            $query->where('brand_id', $request->brand_id);
        }

        // Special handling for years with model_id
        if ($model === 'years' && $request->has('model_id')) {
            $query->where('model_id', $request->model_id);
        }

        // Special handling for colors with year_id
        if ($model === 'colors' && $request->has('year_id')) {
            $query->where('year_id', $request->year_id);
        }

        $items = $query->orderBy('name')->paginate($request->get('per_page', 50));

        return response()->json($items);
    }

    public function store(Request $request, string $model)
    {
        $modelClass = $this->getModelClass($model);
        
        if (!$modelClass) {
            return response()->json(['message' => 'Invalid model type'], 400);
        }

        $rules = $this->getValidationRules($model);
        $validated = $request->validate($rules);

        $item = $modelClass::create($validated);

        AuditLog::log(
            'create',
            $model,
            auth()->id(),
            $modelClass::getTable(),
            $item->id,
            null,
            $item->toArray()
        );

        return response()->json([
            'message' => ucfirst($model) . ' created successfully',
            'data' => $item
        ], 201);
    }

    public function show(string $model, int $id)
    {
        $modelClass = $this->getModelClass($model);
        
        if (!$modelClass) {
            return response()->json(['message' => 'Invalid model type'], 400);
        }

        $item = $modelClass::findOrFail($id);
        return response()->json($item);
    }

    public function update(Request $request, string $model, int $id)
    {
        $modelClass = $this->getModelClass($model);
        
        if (!$modelClass) {
            return response()->json(['message' => 'Invalid model type'], 400);
        }

        $item = $modelClass::findOrFail($id);
        $oldValues = $item->toArray();

        $rules = $this->getValidationRules($model, $id);
        $validated = $request->validate($rules);

        $item->update($validated);

        AuditLog::log(
            'update',
            $model,
            auth()->id(),
            $modelClass::getTable(),
            $item->id,
            $oldValues,
            $item->fresh()->toArray()
        );

        return response()->json([
            'message' => ucfirst($model) . ' updated successfully',
            'data' => $item->fresh()
        ]);
    }

    public function destroy(string $model, int $id)
    {
        $modelClass = $this->getModelClass($model);
        
        if (!$modelClass) {
            return response()->json(['message' => 'Invalid model type'], 400);
        }

        $item = $modelClass::findOrFail($id);
        $oldValues = $item->toArray();

        $item->delete();

        AuditLog::log(
            'delete',
            $model,
            auth()->id(),
            $modelClass::getTable(),
            $item->id,
            $oldValues,
            null
        );

        return response()->json([
            'message' => ucfirst($model) . ' deleted successfully'
        ]);
    }

    /**
     * Bulk import from CSV.
     */
    public function import(Request $request, string $model)
    {
        $modelClass = $this->getModelClass($model);
        
        if (!$modelClass) {
            return response()->json(['message' => 'Invalid model type'], 400);
        }

        $validated = $request->validate([
            'file' => 'required|file|mimes:csv,txt,xlsx,xls',
        ]);

        // Implementation depends on preferred CSV/Excel package
        // For now, return placeholder
        return response()->json([
            'message' => 'Import functionality - implement with your preferred CSV/Excel package'
        ]);
    }

    /**
     * Get model class from string.
     */
    private function getModelClass(string $model): ?string
    {
        $mapping = [
            'brands' => Brand::class,
            'models' => CarModel::class,
            'years' => Year::class,
            'colors' => Color::class,
            'shapes' => Shape::class,
            'fuel_types' => FuelType::class,
            'maintenance_categories' => MaintenanceCategory::class,
            'expense_categories' => ExpenseCategory::class,
            'cylinders' => Cylinder::class,
        ];

        return $mapping[$model] ?? null;
    }

    /**
     * Get validation rules for each model type.
     */
    private function getValidationRules(string $model, ?int $id = null): array
    {
        $uniqueRule = $id ? ',ignore,' . $id : '';

        $rules = [
            'brands' => [
                'name' => 'required|string|max:255|unique:brands,name' . $uniqueRule,
                'country' => 'nullable|string|max:255',
            ],
            'models' => [
                'brand_id' => 'required|exists:brands,id',
                'name' => 'required|string|max:255',
            ],
            'years' => [
                'model_id' => 'required|exists:models,id',
                'year' => 'required|integer|min:1900|max:' . (date('Y') + 5),
            ],
            'colors' => [
                'year_id' => 'required|exists:years,id',
                'name' => 'required|string|max:255',
                'hex_code' => 'nullable|string|max:7',
            ],
            'shapes' => [
                'name' => 'required|string|max:255|unique:shapes,name' . $uniqueRule,
            ],
            'fuel_types' => [
                'name' => 'required|string|max:255|unique:fuel_types,name' . $uniqueRule,
                'unit' => 'required|in:liter,kWh',
                'price' => 'required|numeric|min:0',
                'is_active' => 'boolean',
            ],
            'maintenance_categories' => [
                'name' => 'required|string|max:255|unique:maintenance_categories,name' . $uniqueRule,
                'description' => 'nullable|string',
                'is_active' => 'boolean',
            ],
            'expense_categories' => [
                'name' => 'required|string|max:255|unique:expense_categories,name' . $uniqueRule,
                'description' => 'nullable|string',
                'is_active' => 'boolean',
            ],
            'cylinders' => [
                'model_id' => 'required|exists:models,id',
                'count' => 'required|integer|min:1',
            ],
        ];

        return $rules[$model] ?? [];
    }
}
