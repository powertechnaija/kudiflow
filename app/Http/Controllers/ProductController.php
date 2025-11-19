<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

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
        $product = Product::create($request->only(['name', 'description']));

        foreach ($request->variants as $variant) {
            $product->variants()->create($variant);
        }

        return response()->json($product->load('variants'), 201);
    }
}
