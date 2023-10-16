<?php

namespace App\Http\Controllers\Api\Dept;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class FormRequestController extends Controller
{
    public function index()
    {
        $data = FormRequest::where('kategori', $this->userData->departemen)
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

    public function approve(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            $validator = Validator::make($request->all(), [
                'prioritas' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => $validator->errors(),
                    'code' => 400,
                    'success' => false
                ], 400);
            }

            $existingRequest = FormRequest::with('category')->findOrFail($id);

            if ($existingRequest->kategori != $this->userData->departemen) {
                return response()->json([
                    'message' => 'The request is not addressed to user`s department.',
                    'code' => 403,
                    'success' => false,
                ], 403);
            }
            if ($existingRequest->status != 2) {
                return response()->json([
                    'message' => 'Request cannot be approved. Status is not 2. Current status = '.$existingRequest->status,
                    'code' => 403,
                    'success' => false,
                ], 403);
            }

            $existingRequest->update([
                'approve_kategori_mgr' => now()->toDateString(),
                'hp_c' => $this->userData->no_hp,
                'prioritas' => $request->input('prioritas'),
                'status' => 3,
                'info' => "Request telah di approve ".$this->userData->name.", menunggu eksekutor."
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

            if ($data->kategori != $this->userData->departemen) {
                return response()->json([
                    'message' => 'The request is not addressed to user`s department.',
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

    public function execute(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            $validator = Validator::make($request->all(), [
                'action' => 'required|in:1,2,3',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => $validator->errors(),
                    'code' => 400,
                    'success' => false
                ], 400);
            }

            $existingRequest = FormRequest::with('category')->findOrFail($id);

            if ($existingRequest->kategori != $this->userData->departemen) {
                return response()->json([
                    'message' => 'The request is not addressed to the user`s department.',
                    'code' => 403,
                    'success' => false,
                ], 403);
            }

            if ($existingRequest->status < 2 || $existingRequest->status > 6) {
                return response()->json([
                    'message' => 'Request cannot be approved. Status must be between 2 and 6. Current status = ' . $existingRequest->status,
                    'code' => 403,
                    'success' => false,
                ], 403);
            }

            // Common data for all actions
            $commonData = [
                'eksekutor' => $this->userData->sub,
                'hp_d' => $this->userData->no_hp,
            ];

            switch ($request->input('action')) {
                case 1:
                    $updateData = array_merge($commonData, [
                        'waktu_mulai' => now()->toDateString(),
                        'status' => 5,
                        'info' => "Onprogress oleh " . $this->userData->name,
                    ]);
                    break;

                case 2:
                    $validator = Validator::make($request->all(), [
                        'keterangan' => 'required',
                    ]);

                    if ($validator->fails()) {
                        return response()->json([
                            'message' => $validator->errors(),
                            'code' => 400,
                            'success' => false,
                        ], 400);
                    }

                    $updateData = array_merge($commonData, [
                        'status' => 4,
                        'info' => "Di-pending oleh " . $this->userData->name,
                        'keterangan' => $request->input('keterangan'),
                    ]);
                    break;

                case 3:
                    $validator = Validator::make($request->all(), [
                        'keterangan' => 'required',
                    ]);

                    if ($validator->fails()) {
                        return response()->json([
                            'message' => $validator->errors(),
                            'code' => 400,
                            'success' => false,
                        ], 400);
                    }

                    if ($existingRequest->status != 5) {
                        return response()->json([
                            'message' => 'Cannot complete requests that have not been started. Status must be 5. Current status = ' . $existingRequest->status,
                            'code' => 403,
                            'success' => false,
                        ], 403);
                    }

                    $updateData = array_merge($commonData, [
                        'approve_kategori_fr' => now()->toDateString(),
                        'waktu_selesai' => now()->toDateString(),
                        'status' => 6,
                        'info' => "Diselesaikan oleh " . $this->userData->name,
                        'keterangan' => $request->input('keterangan'),
                    ]);
                    break;

                default:
                    return response()->json([
                        'message' => 'Unknown action',
                        'code' => 403,
                        'success' => false,
                    ], 403);
            }

            $existingRequest->update($updateData);

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
}
