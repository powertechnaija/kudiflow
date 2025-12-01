<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LedgerController extends Controller
{
    //
    /**
     * Display a listing of the resource.
     * use JournalEntry Model
     * use Belongs to store traits
     * return json resources
     
     */
    public function index(Request $request)
    {
        
        $journalEntries = \App\Models\JournalEntry::where('store_id', auth()->user()->store_id)->get();
        return response()->json($journalEntries);
    }
}
