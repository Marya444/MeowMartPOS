<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    /**
     * Display a listing of products with optional pagination
     */
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10); // Default 10 items per page
        $search = $request->input('search');

        $products = Product::when($search, function ($query) use ($search) {
            return $query->where('name', 'like', '%' . $search . '%')
                ->orWhere('barcode', 'like', '%' . $search . '%');
        })
            ->orderBy('name')
            ->paginate($perPage);

        return response()->json($products);
    }

    /**
     * Store a newly created product in storage
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
            'min_stock_level' => 'nullable|integer|min:0',
            'barcode' => 'nullable|string|unique:products',
            'category' => 'nullable|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $data = $request->except('image');

        // Handle image upload
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('products', 'public');
            $data['image_path'] = $path;
        }

        $product = Product::create($data);

        return response()->json([
            'message' => 'Product created successfully',
            'product' => $product
        ], 201);
    }

    /**
     * Display the specified product
     */
    public function show(Product $product)
    {
        return response()->json($product);
    }

    /**
     * Update the specified product in storage
     */
    public function update(Request $request, Product $product)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'price' => 'sometimes|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'stock_quantity' => 'sometimes|integer|min:0',
            'min_stock_level' => 'nullable|integer|min:0',
            'barcode' => 'sometimes|string|unique:products,barcode,' . $product->id,
            'category' => 'nullable|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $data = $request->except('image');

        // Handle image update
        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($product->image_path) {
                Storage::disk('public')->delete($product->image_path);
            }

            $path = $request->file('image')->store('products', 'public');
            $data['image_path'] = $path;
        }

        $product->update($data);

        return response()->json([
            'message' => 'Product updated successfully',
            'product' => $product
        ]);
    }

    /**
     * Remove the specified product from storage
     */
    public function destroy(Product $product)
    {
        // Delete associated image if exists
        if ($product->image_path) {
            Storage::disk('public')->delete($product->image_path);
        }

        $product->delete();

        return response()->json([
            'message' => 'Product deleted successfully'
        ]);
    }

    /**
     * Get low stock products (below minimum stock level)
     */
    public function lowStock()
    {
        $products = Product::whereColumn('stock_quantity', '<', 'min_stock_level')
            ->orWhere(function ($query) {
                $query->whereNull('min_stock_level')
                    ->where('stock_quantity', '<', 5); // Default threshold
            })
            ->get();

        return response()->json($products);
    }

    /**
     * Search products by name or barcode
     */
    public function search(Request $request)
    {
        $search = $request->input('query');

        if (!$search) {
            return response()->json([], 400);
        }

        $products = Product::where('name', 'like', '%' . $search . '%')
            ->orWhere('barcode', 'like', '%' . $search . '%')
            ->limit(10)
            ->get();

        return response()->json($products);
    }
}
