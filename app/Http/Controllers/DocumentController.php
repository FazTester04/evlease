<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    /**
     * Upload a new document.
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

        $names = [
            'insurance' => 'Insurance',
            'road_tax'  => 'Road Tax',
        ];
        $name = $names[$validated['type']] ?? ucfirst(str_replace('_', ' ', $validated['type']));

        Document::create([
            'car_id'      => $validated['car_id'],
            'name'        => $name,
            'type'        => $validated['type'],
            'file_path'   => $path,
            'expiry_date' => $validated['expiry_date'],
            'status'      => $validated['status'],
        ]);

        return redirect()->back()->with('success', 'Document uploaded successfully.');
    }

    /**
     * Update document metadata.
     */
    public function update(Request $request, Document $document)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'expiry_date' => 'nullable|date',
            'status'      => 'required|in:valid,expiring,expired',
        ]);

        $document->update($validated);

        return redirect()->back()->with('success', 'Document updated successfully.');
    }

    /**
     * Delete a document.
     */
    public function destroy(Document $document)
    {
        // Optionally delete the file from storage
        if ($document->file_path) {
            Storage::disk('public')->delete($document->file_path);
        }

        $document->delete();

        return redirect()->back()->with('success', 'Document deleted successfully.');
    }
public function index()
{
    $documents = Document::with([
        'car',                       // for car documents
        'driver',                    // for driver documents
        'leasePayment.lease.car',    // for payment receipts → car
        'leasePayment.lease.driver', // for payment receipts → driver
    ])
    ->orderBy('created_at', 'desc')
    ->get();

    return Inertia::render('documents', [
        'documents' => $documents,
    ]);
}
}