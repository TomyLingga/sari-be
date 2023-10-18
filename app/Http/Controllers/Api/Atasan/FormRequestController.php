<?php

namespace App\Http\Controllers\Api\Atasan;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class FormRequestController extends Controller
{
    public function index()
    {
        $data = FormRequest::where('atasan_id', $this->userData->sub)
                    ->with('category','atasan', 'user', 'deptPic', 'executor')
                    ->latest()
                    ->get();

        if ($data->isEmpty()) {
            return response()->json([
                'message' => "No Request Found",
                'success' => false,
                'code' => 404
            ], 404);
        }

        return response()->json([
            'data' => $data,
            'message' => 'Data Retrieved Successfully',
            'code' => 200,
            'success' => true,
        ], 200);
    }

    public function approve($id)
    {
        DB::beginTransaction();

        try {
            $existingRequest = FormRequest::with('category')->findOrFail($id);

            if ($existingRequest->atasan_id != $this->userData->sub) {
                return response()->json([
                    'message' => 'The user is not the requestor`s superior.',
                    'code' => 403,
                    'success' => false,
                ], 403);
            }
            if ($existingRequest->status != 1) {
                return response()->json([
                    'message' => 'Request cannot be approved. Status is not 1. Current status = '.$existingRequest->status,
                    'code' => 403,
                    'success' => false,
                ], 403);
            }

            $existingRequest->update([
                'approve_atasan' => now()->toDateString(),
                'hp_b' => $this->userData->no_hp,
                'status' => 2,
                'info' => "Waiting approval from ".$existingRequest->category->nama_kategori
            ]);

            DB::commit();

            return response()->json([
                'data' => $existingRequest,
                'message' => 'Request Updated Successfully',
                'code' => 200,
                'success' => true,
            ], 200);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => 'Something went wrong',
                'err' => $e->getTrace()[0],
                'errMsg' => $e->getMessage(),
                'code' => 500,
                'success' => false,
            ], 500);
        }
    }

    public function decline(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'keterangan' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => $validator->errors(),
                    'code' => 400,
                    'success' => false
                ], 400);
            }
            $data = FormRequest::findOrFail($id);

            if ($data->atasan_id != $this->userData->sub) {
                return response()->json([
                    'message' => 'The user is not the requestor`s superior.',
                    'code' => 403,
                    'success' => false,
                ], 403);
            }

            $data->update([
                'status' => 0,
                'keterangan' => $request->input('keterangan'),
                'info' => "Declined by ".$this->userData->name
            ]);

            return response()->json([
                'data' => $data,
                'message' => "Request cancel Success",
                'code' => 200,
                'success' => true
            ], 200);
        } catch (\Exception $e) {

            return response()->json([
                'message' => "Something went wrong",
                'err' => $e->getTrace()[0],
                'errMsg' => $e->getMessage(),
                'code' => 500,
                'success' => false
            ], 500);
        }
    }
}
