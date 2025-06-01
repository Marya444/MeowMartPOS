<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Gate;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $search = $request->input('search');

        $products = Product::when($search, function ($query) use ($search) {
            return $query->where('name', 'like', "%$search%")
                ->orWhere('barcode', 'like', "%$search%");
        })->orderBy('name')->paginate($perPage);

        return response()->json($products);
    }

    public function store(Request $request)
    {
        if (Gate::denies('manage-products')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

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

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('products', 'public');
        }

        $product = Product::create($data);

        return response()->json(['message' => 'Product created', 'product' => $product], 201);
    }

    public function show(Product $product)
    {
        return response()->json($product);
    }

    public function update(Request $request, Product $product)
    {
        if (Gate::denies('manage-products')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

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

        if ($request->hasFile('image')) {
            if ($product->image_path) {
                Storage::disk('public')->delete($product->image_path);
            }
            $data['image_path'] = $request->file('image')->store('products', 'public');
        }

        $product->update($data);

        return response()->json(['message' => 'Product updated', 'product' => $product]);
    }

    public function destroy(Product $product)
    {
        if (Gate::denies('manage-products')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if ($product->image_path) {
            Storage::disk('public')->delete($product->image_path);
        }

        $product->delete();

        return response()->json(['message' => 'Product deleted']);
    }

    public function lowStock()
    {
        $products = Product::whereColumn('stock_quantity', '<', 'min_stock_level')
            ->orWhere(function ($query) {
                $query->whereNull('min_stock_level')->where('stock_quantity', '<', 5);
            })->get();

        return response()->json($products);
    }

    public function search(Request $request)
    {
        $search = $request->input('query');

        if (!$search) {
            return response()->json([], 400);
        }

        $products = Product::where('name', 'like', "%$search%")
            ->orWhere('barcode', 'like', "%$search%")
            ->limit(10)
            ->get();

        return response()->json($products);
    }
}
