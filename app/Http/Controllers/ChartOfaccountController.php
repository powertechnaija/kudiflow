<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ChartOfaccountController extends Controller
{

/**
     * Display a listing of the resource.
     * use ChartOfAccount model
     * use BelongsToStore trait
     * fetch all chart of accounts for the current store
     *
     * @return json response;
     */
    
     public function index()
     {
         $chartOfAccounts = \App\Models\ChartOfAccount::where('store_id', auth()->user()->store_id)->get();
 
         return response()->json($chartOfAccounts);
     }
         
     public function store(Request $request)
     {
         //validate request
         $request->validate([
             'name' => 'required',
             'code' => 'required',
             'type' => 'required',
             'description' => 'nullable',
         ]);
         $chartOfAccount = \App\Models\ChartOfAccount::create([
             'name' => $request->name,
             'code' => $request->code,
             'type' => $request->type,
             'description' => $request->description,
             'store_id' => auth()->user()->store_id,
         ]);
 
         return response()->json($chartOfAccount);
     }
     
 }
 
