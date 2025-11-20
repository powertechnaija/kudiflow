<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        // The BelongsToStore trait automatically restricts this to the user's store
        $products = QueryBuilder::for(Product::class)
            ->with(['variants']) // Eager load variants
            ->allowedFilters([
                'name', 
                AllowedFilter::partial('search', 'name'), // ?filter[search]=shirt
                AllowedFilter::exact('variants.sku'),     // ?filter[variants.sku]=SKU-123
                AllowedFilter::exact('variants.barcode')  // ?filter[variants.barcode]=123456
            ])
            ->allowedSorts(['name', 'created_at'])
            ->paginate($request->get('per_page', 20));

        return response()->json($products);
    }
    
    // For the Barcode Scanner on Mobile
    public function findByBarcode(Request $request)
    {
        $request->validate(['barcode' => 'required|string']);

        // We search across variants
        $product = Product::whereHas('variants', function ($query) use ($request) {
            $query->where('barcode', $request->barcode);
        })->with(['variants' => function($q) use ($request) {
            // Only load the specific variant matched? Or all? Usually all.
            $q->where('barcode', $request->barcode);
        }])->first();

        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        return response()->json($product);
    }

    // ProductController.php
    public function store(Request $request)
    {
        // 1. SAAS-AWARE VALIDATION
        $storeId = Auth::user()->store_id;

        $request->validate([
            'name' => 'required|string',
            'description' => 'nullable|string',
            'variants' => 'required|array|min:1',
            
            // "distinct" checks for duplicates inside the input array itself
            'variants.*.sku' => [
                'required',
                'distinct',
                // Complex Rule: Unique SKU ONLY within this specific store's products
                Rule::unique('product_variants', 'sku')->where(function ($query) use ($storeId) {
                    return $query->where('store_id', $storeId);
                }),
            ],
            'variants.*.price' => 'required|numeric|min:0',
            'variants.*.cost_price' => 'required|numeric|min:0',
            'variants.*.size' => 'nullable|string',
            'variants.*.color' => 'nullable|string',
            // Barcode also needs to be unique per store
            'variants.*.barcode' => [
                'nullable',
                'distinct',
                Rule::unique('product_variants', 'barcode')->where(function ($query) use ($storeId) {
                    return $query->where('store_id', $storeId);
                }),
            ],
        ]);

        // 2. CREATION LOGIC
        return DB::transaction(function () use ($request, $storeId) {
            // Create Product (BelongsToStore trait automatically adds store_id)
            $product = Product::create([
                'store_id' => $storeId, // Explicitly set store_id
                'name' => $request->name,
                'description' => $request->description
            ]);

            // Create Variants
            // Note: Variants don't usually need store_id if they link to a product, 
            // but we must ensure the relationship is set via the parent.
            foreach ($request->variants as $variantData) {
                $product->variants()->create([
                    'store_id' => $storeId,
                    'sku' => $variantData['sku'],
                    'price' => $variantData['price'],
                    'cost_price' => $variantData['cost_price'],
                    'stock_quantity' => 0, // Initial stock is 0. Must use Purchase/Opening Balance to add stock.
                    'size' => $variantData['size'] ?? null,
                    'color' => $variantData['color'] ?? null,
                    'barcode' => $variantData['barcode'] ?? null,
                ]);
            }

            return response()->json($product->load('variants'), 201);
        });
    }
}
