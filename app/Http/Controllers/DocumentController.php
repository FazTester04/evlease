<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    /**
     * Upload a document (insurance or road tax).
     */
    public function upload(Request $request)
    {
        $validated = $request->validate([
            'car_id'       => 'required|exists:cars,id',
            'type'         => 'required|in:insurance,road_tax',
            'document'     => 'required|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'expiry_date'  => 'required|date',
            'status'       => 'required|in:valid,expiring,expired',
        ]);

        $path = $request->file('document')->store('documents', 'public');

        Document::create([
            'car_id'      => $validated['car_id'],
            'name'        => ucfirst(str_replace('_', ' ', $validated['type'])),
            'type'        => $validated['type'],
            'file_path'   => $path,
            'expiry_date' => $validated['expiry_date'],
            'status'      => $validated['status'],
        ]);

        return redirect()->back()->with('success', 'Document uploaded successfully.');
    }
}