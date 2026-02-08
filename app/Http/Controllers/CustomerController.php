<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CustomerController extends Controller
{ 
  public function search(Request $request)
  {
    $data = Customer::where('pharmacy_branch_id', $request->auth->pharmacy_branch_id)
        ->where(function ($query) use ($request) {
            $query->where('name', 'like', '%' . $request->q . '%')
                  ->orWhere('mobile', 'like', '%' . $request->q . '%');
        })
        ->limit(20)
        ->get(['id', 'name', 'mobile']);

    return response()->json($data);
  }

  public function index(Request $request)
  {
    try {
      $user = $request->auth;
      $limit = (int) ($request->limit ?? 20);
      $page = max((int) ($request->page ?? 1), 1);
      $offset = ($page - 1) * $limit;

      $query = Customer::where('pharmacy_branch_id', $user->pharmacy_branch_id);

      if (!empty($request->q)) {
        $query->where(function ($q) use ($request) {
          $q->where('name', 'like', '%' . $request->q . '%')
            ->orWhere('mobile', 'like', '%' . $request->q . '%')
            ->orWhere('code', 'like', '%' . $request->q . '%');
        });
      }

      $total = $query->count();
      $customers = $query->orderBy('id', 'desc')
        ->offset($offset)
        ->limit($limit)
        ->get();

      return response()->json([
        'total' => $total,
        'data' => $customers,
        'page' => $page,
        'limit' => $limit,
      ]);
    } catch (\Throwable $th) {
      return response()->json([
          'success' => false,
          'message' => 'Something went wrong!',
          'error'   => $th->getMessage(),
      ], 500);
    }
  }

  public function show(Request $request, $id)
  {
    $user = $request->auth;
    $customer = Customer::where('pharmacy_branch_id', $user->pharmacy_branch_id)
      ->with('documents')
      ->findOrFail($id);

    return response()->json($customer);
  }

  public function store(Request $request)
  {
    try {
      $user = $request->auth;

      $this->validate($request, [
        'mobile' => 'required|string|max:50',
        'name' => 'nullable|string|max:255',
        'email' => 'nullable|email|max:255',
        'balance' => 'nullable|numeric',
      ]);

      $customer = Customer::create([
        'pharmacy_branch_id' => $user->pharmacy_branch_id,
        'code' => $request->mobile,
        'mobile' => $request->mobile,
        'name' => $request->name,
        'email' => $request->email,
        'address' => $request->address,
        'balance' => $request->balance ?? 0,
        'status' => Customer::STATUS_ACTIVE,
      ]);

      return response()->json([
          'status' => true,
          'data' => $customer
      ], 201);
    } catch (\Throwable $th) {
      return response()->json([
          'success' => false,
          'message' => 'Save failed.',
          'error'   => $th->getMessage(),
      ], 500);
    }
  }

  public function update(Request $request, $id)
  {
    try {
      $user = $request->auth;
      $customer = Customer::where('pharmacy_branch_id', $user->pharmacy_branch_id)->findOrFail($id);

      $this->validate($request, [
        'code' => 'sometimes|required|string|max:50',
        'mobile' => 'sometimes|required|string|max:50',
        'name' => 'nullable|string|max:255',
        'email' => 'nullable|email|max:255',
        'balance' => 'nullable|numeric',
      ]);

      $customer->fill($request->only([
        'code',
        'mobile',
        'name',
        'email',
        'balance',
        'address',
        'status',
      ]));

      $customer->save();

      return response()->json([
          'status' => true,
          'data' => $customer
      ]);
    } catch (\Throwable $th) {
      return response()->json([
          'success' => false,
          'message' => 'Update failed.',
          'error'   => $th->getMessage(),
      ], 500);
    }
  }

  public function destroy(Request $request, $id)
  {
    $user = $request->auth;
    $customer = Customer::where('pharmacy_branch_id', $user->pharmacy_branch_id)
                ->with('documents')
                ->findOrFail($id);

    foreach ($customer->documents as $document) {
      if ($document->file_path && file_exists($document->file_path)) {
        unlink($document->file_path);
      }
      $document->delete();
    }

    $customer->delete();

    return response()->json(['success' => true]);
  }

  public function listDocuments(Request $request, $id)
  {
    $user = $request->auth;
    $customer = Customer::where('pharmacy_branch_id', $user->pharmacy_branch_id)
      ->findOrFail($id);

    $documents = $customer->documents()->orderBy('id', 'desc')->get();

    return response()->json($documents);
  }

  public function uploadDocument(Request $request, $id)
  {
    $user = $request->auth;
    $customer = Customer::where('pharmacy_branch_id', $user->pharmacy_branch_id)
      ->findOrFail($id);

    $this->validate($request, [
      'type' => 'required|string|max:50',
      'files' => 'required',
      'files.*' => 'file|mimes:jpg,jpeg,png,pdf|max:5120',
    ]);

    $documents = [];
    $files = $request->file('files', []);
    $directory = 'public/customers/' . $user->pharmacy_branch_id;

    if (!is_dir($directory)) {
      mkdir($directory, 0755, true);
    }

    foreach ($files as $file) {
      $extension = $file->getClientOriginalExtension();
      $safeName = Str::uuid()->toString() . '.' . $extension;
      $file->move($directory, $safeName);

      $filePath = $directory . '/' . $safeName;

      $documents[] = CustomerDocument::create([
        'customer_id' => $customer->id,
        'pharmacy_branch_id' => $user->pharmacy_branch_id,
        'type' => $request->type,
        'file_name' => $safeName,
        'file_path' => $filePath,
        'file_size' => filesize($filePath),
      ]);
    }

    return response()->json($documents, 201);
  }

  public function deleteDocument(Request $request, $id, $documentId)
  {
    $user = $request->auth;
    $customer = Customer::where('pharmacy_branch_id', $user->pharmacy_branch_id)->findOrFail($id);

    $document = $customer->documents()
      ->where('id', $documentId)
      ->where('pharmacy_branch_id', $user->pharmacy_branch_id)
      ->firstOrFail();

    if ($document->file_path && file_exists($document->file_path)) {
      unlink($document->file_path);
    }

    $document->delete();

    return response()->json(['success' => true]);
  }
  
}
