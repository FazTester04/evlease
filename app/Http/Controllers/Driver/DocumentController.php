<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Document;
use App\Models\Lease;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    /**
     * Display a list of the driver's documents.
     */
    public function index()
    {
        $user = Auth::user();

        $documents = $user->documents()
            ->with('lease.car')
            ->latest()
            ->paginate(10);

        return Inertia::render('Driver/Documents/Index', [
            'documents' => $documents,
        ]);
    }

    /**
     * Show the form to upload a new document.
     */
    public function create(Request $request)
    {
        $user = Auth::user();

        // Get active leases for the driver to optionally associate the document
        $leases = $user->leases()
            ->where('status', 'active')
            ->with('car')
            ->get();

        return Inertia::render('Driver/Documents/Create', [
            'leases' => $leases,
            'preselectedLeaseId' => $request->lease_id, // for pre-filling if coming from lease page
        ]);
    }

    /**
     * Store a newly uploaded document.
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'lease_id' => 'nullable|exists:leases,id',
            'type' => 'required|in:driving_license,road_tax,insurance',
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120', // 5MB max
            'expiry_date'  => 'nullable|date|after:today',
        ]);

        // Ensure the lease belongs to the driver if provided
        if ($request->lease_id) {
            $lease = Lease::findOrFail($request->lease_id);
            if ($lease->driver_id !== $user->id) {
                abort(403, 'Unauthorized.');
            }
        }

        $file = $request->file('file');
        $path = $file->store('documents/' . $user->id, 'public');

        $document = $user->documents()->create([
            'lease_id' => $request->lease_id,
            'type' => $request->type,
            'name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'expiry_date'  => $request->expiry_date,
        ]);

        return redirect()->route('driver.documents.index')
            ->with('success', 'Document uploaded successfully.');
    }

    /**
     * Delete a document (only if it belongs to the driver).
     */
    public function destroy(Document $document)
    {
        $user = Auth::user();

        // Ensure the document belongs to the driver
        if ($document->driver_id !== $user->id) {
            abort(403);
        }

        // Delete the file from storage
        Storage::disk('public')->delete($document->file_path);

        $document->delete();

        return redirect()->back()
            ->with('success', 'Document deleted.');
    }
}