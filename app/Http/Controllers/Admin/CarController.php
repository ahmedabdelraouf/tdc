<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Car;
use App\Models\User;
use App\Models\Brand;
use App\Models\Model as CarModel;
use App\Models\Year;
use App\Models\Color;
use App\Models\Shape;
use App\Models\FuelType;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CarController extends Controller
{
    /**
     * Display a listing of cars.
     */
    public function index(Request $request)
    {
        $query = Car::with(['user', 'brand', 'model', 'year', 'color', 'shape', 'fuelType']);

        // Filters
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->has('brand_id')) {
            $query->where('brand_id', $request->brand_id);
        }

        if ($request->has('model_id')) {
            $query->where('model_id', $request->model_id);
        }

        if ($request->has('fuel_type_id')) {
            $query->where('fuel_type_id', $request->fuel_type_id);
        }

        if ($request->has('is_default')) {
            $query->where('is_default', $request->is_default);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('vin_number', 'like', "%{$search}%")
                  ->orWhere('plate_number', 'like', "%{$search}%");
            });
        }

        $cars = $query->orderBy('created_at', 'desc')
                      ->paginate($request->get('per_page', 15));

        return response()->json($cars);
    }

    /**
     * Store a newly created car.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'brand_id' => 'required|exists:brands,id',
            'model_id' => 'required|exists:models,id',
            'year_id' => 'required|exists:years,id',
            'color_id' => 'required|exists:colors,id',
            'shape_id' => 'required|exists:shapes,id',
            'fuel_type_id' => 'required|exists:fuel_types,id',
            'vin_number' => 'nullable|string|max:50',
            'plate_number' => 'nullable|string|max:20',
            'kilometers' => 'integer|min:0',
            'cylinder' => 'nullable|integer',
            'is_default' => 'boolean',
            'images' => 'nullable|array',
            'license_front' => 'nullable|string',
            'license_back' => 'nullable|string',
        ]);

        if ($request->hasFile('images')) {
            $imagePaths = [];
            foreach ($request->file('images') as $image) {
                $path = $image->store('cars/images', 'r2');
                $imagePaths[] = $path;
            }
            $validated['images'] = json_encode($imagePaths);
        }

        if ($request->hasFile('license_front')) {
            $validated['license_front'] = $request->file('license_front')->store('cars/licenses', 'r2');
        }

        if ($request->hasFile('license_back')) {
            $validated['license_back'] = $request->file('license_back')->store('cars/licenses', 'r2');
        }

        // If is_default is true, unset other default cars for this user
        if (!empty($validated['is_default'])) {
            Car::where('user_id', $validated['user_id'])
               ->update(['is_default' => false]);
        }

        $car = Car::create($validated);

        AuditLog::log(
            'create',
            'cars',
            auth()->id(),
            'cars',
            $car->id,
            null,
            $car->toArray()
        );

        return response()->json([
            'message' => 'Car created successfully',
            'car' => $car->load(['user', 'brand', 'model', 'year', 'color', 'shape', 'fuelType'])
        ], 201);
    }

    /**
     * Display the specified car.
     */
    public function show(Car $car)
    {
        return response()->json($car->load([
            'user', 'brand', 'model', 'year', 'color', 'shape', 'fuelType', 'expenses'
        ]));
    }

    /**
     * Update the specified car.
     */
    public function update(Request $request, Car $car)
    {
        $oldValues = $car->toArray();

        $validated = $request->validate([
            'user_id' => 'sometimes|required|exists:users,id',
            'brand_id' => 'sometimes|required|exists:brands,id',
            'model_id' => 'sometimes|required|exists:models,id',
            'year_id' => 'sometimes|required|exists:years,id',
            'color_id' => 'sometimes|required|exists:colors,id',
            'shape_id' => 'sometimes|required|exists:shapes,id',
            'fuel_type_id' => 'sometimes|required|exists:fuel_types,id',
            'vin_number' => 'nullable|string|max:50',
            'plate_number' => 'nullable|string|max:20',
            'kilometers' => 'integer|min:0',
            'cylinder' => 'nullable|integer',
            'is_default' => 'boolean',
            'images' => 'nullable|array',
            'license_front' => 'nullable|string',
            'license_back' => 'nullable|string',
        ]);

        if ($request->hasFile('images')) {
            // Delete old images
            if ($car->images) {
                foreach ($car->images as $image) {
                    Storage::disk('r2')->delete($image);
                }
            }

            $imagePaths = [];
            foreach ($request->file('images') as $image) {
                $path = $image->store('cars/images', 'r2');
                $imagePaths[] = $path;
            }
            $validated['images'] = json_encode($imagePaths);
        }

        if ($request->hasFile('license_front')) {
            if ($car->license_front) {
                Storage::disk('r2')->delete($car->license_front);
            }
            $validated['license_front'] = $request->file('license_front')->store('cars/licenses', 'r2');
        }

        if ($request->hasFile('license_back')) {
            if ($car->license_back) {
                Storage::disk('r2')->delete($car->license_back);
            }
            $validated['license_back'] = $request->file('license_back')->store('cars/licenses', 'r2');
        }

        // If is_default is true, unset other default cars for this user
        if (isset($validated['is_default']) && $validated['is_default']) {
            Car::where('user_id', $car->user_id)
               ->where('id', '!=', $car->id)
               ->update(['is_default' => false]);
        }

        $car->update($validated);

        AuditLog::log(
            'update',
            'cars',
            auth()->id(),
            'cars',
            $car->id,
            $oldValues,
            $car->fresh()->toArray()
        );

        return response()->json([
            'message' => 'Car updated successfully',
            'car' => $car->fresh()->load(['user', 'brand', 'model', 'year', 'color', 'shape', 'fuelType'])
        ]);
    }

    /**
     * Remove the specified car (soft delete).
     */
    public function destroy(Car $car)
    {
        $oldValues = $car->toArray();

        // Delete associated files from R2
        if ($car->images) {
            foreach ($car->images as $image) {
                Storage::disk('r2')->delete($image);
            }
        }
        if ($car->license_front) {
            Storage::disk('r2')->delete($car->license_front);
        }
        if ($car->license_back) {
            Storage::disk('r2')->delete($car->license_back);
        }

        $car->delete();

        AuditLog::log(
            'delete',
            'cars',
            auth()->id(),
            'cars',
            $car->id,
            $oldValues,
            null
        );

        return response()->json([
            'message' => 'Car deleted successfully'
        ]);
    }

    /**
     * Bulk delete cars.
     */
    public function bulkDelete(Request $request)
    {
        $validated = $request->validate([
            'car_ids' => 'required|array',
            'car_ids.*' => 'exists:cars,id',
        ]);

        $cars = Car::whereIn('id', $validated['car_ids'])->get();

        foreach ($cars as $car) {
            // Delete associated files
            if ($car->images) {
                foreach ($car->images as $image) {
                    Storage::disk('r2')->delete($image);
                }
            }

            AuditLog::log(
                'bulk_delete',
                'cars',
                auth()->id(),
                'cars',
                $car->id,
                $car->toArray(),
                null
            );

            $car->delete();
        }

        return response()->json([
            'message' => count($validated['car_ids']) . ' cars deleted successfully'
        ]);
    }

    /**
     * Export cars to CSV.
     */
    public function export(Request $request)
    {
        $query = Car::with(['user', 'brand', 'model', 'year', 'fuelType']);

        // Apply filters
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        if ($request->has('brand_id')) {
            $query->where('brand_id', $request->brand_id);
        }

        $cars = $query->get();

        $csvData = "ID,User,Brand,Model,Year,VIN,Plate,Kilometers,Fuel Type,Default,Created At\n";

        foreach ($cars as $car) {
            $default = $car->is_default ? 'Yes' : 'No';
            $csvData .= "{$car->id},\"{$car->user->name}\",\"{$car->brand->name}\",\"{$car->model->name}\",{$car->year->year},{$car->vin_number},{$car->plate_number},{$car->kilometers},\"{$car->fuelType->name}\",{$default},{$car->created_at}\n";
        }

        return response($csvData)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="cars_' . date('Y-m-d') . '.csv"');
    }
}
