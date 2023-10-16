<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\FormRequest;
use App\Models\Category;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;

class FormRequestController extends Controller
{
    public function indexAll()
    {
        $data = FormRequest::with('category','atasan', 'user', 'deptPic', 'executor')
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

    public function show($id)
    {
        $data = FormRequest::where('id', $id)
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

    public function index()
    {
        $data = FormRequest::where('user_id', $this->userData->sub)
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

    public function store(Request $request)
    {
        DB::beginTransaction();
        // dd($this->userData);
        try {
            $validator = Validator::make($request->all(), [
                'office' => 'required',
                'email_inl' => 'required',
                'atasan_id' => 'required',
                'kategori' => 'required',
                'jenis_permintaan' => 'required',
                'keperluan' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => $validator->errors(),
                    'code' => 400,
                    'success' => false
                ], 400);
            }

            $atasan = User::where('id', $request->atasan_id)->first();

            if (!$atasan) {
                return response()->json([
                    'message' => 'Atasan not found',
                    'success' => false,
                    'code' => 404
                ], 404);
            }

            $category = Category::where('id', $request->jenis_permintaan)->first();

            if (!$category) {
                return response()->json([
                    'message' => 'Category not found',
                    'success' => false,
                    'code' => 404
                ], 404);
            }

            $departmentId = $request->kategori;

            $deptResponse = Http::withHeaders([
                'Authorization' => $this->token,
            ])->get($this->urlDept . $departmentId);

            $departmentData = $deptResponse->json()['data'] ?? [];
            if (empty($departmentData)) {
                return response()->json([
                    'message' => 'Category not found',
                    'success' => true,
                    'code' => 401
                ], 401);
            }

            if ($category->id_kategori != $departmentData['id']) {
                return response()->json([
                    'message' => 'Request type not belongs to this Category/Department',
                    'success' => false,
                    'code' => 404
                ], 404);
            }

            $currentMonth = now()->format('m');
            $currentYear = now()->format('Y');
            $count = FormRequest::where('jenis_permintaan', $request->jenis_permintaan)
                ->whereMonth('created_at', $currentMonth)
                ->whereYear('created_at', $currentYear)
                ->count() + 1;

            $countFormatted = str_pad($count, 4, '0', STR_PAD_LEFT);

            $monthInRoman = $this->getMonthInRoman($currentMonth);

            $nomor = "REQ{$countFormatted}/{$departmentData['kode']}/{$monthInRoman}/{$currentYear}";

            $data = [
                'no_wo' => $nomor,
                'kategori' => $request->kategori,
                'jenis_permintaan' => $request->jenis_permintaan,
                'user_id' => $this->userData->sub,
                'nrk' => $this->userData->nrk,
                'hp_a' => $this->userData->no_hp,
                'atasan_id' => $atasan->id,
                'approve_user' => now()->toDateString(),
                'keperluan' => $request->keperluan,
                'office' => $request->office,
                'email_inl' => $request->email_inl,
                'status' => 1,
                'info' => "Menunggu persetujuan dari ".$atasan->name.", ".$atasan->jabatan
            ];

            $data = FormRequest::create($data);

            DB::commit();

            return response()->json([
                'data' => $data,
                'message' => 'Request Created Successfully',
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

    public function edit(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            $existingRequest = FormRequest::with('category','atasan', 'user', 'deptPic', 'executor')
                                        ->findOrFail($id);

            if ($existingRequest->user_id != $this->userData->sub) {
                return response()->json([
                    'message' => 'User not own this request.',
                    'code' => 403,
                    'success' => false,
                ], 403);
            }
            if ($existingRequest->status != 1) {
                return response()->json([
                    'message' => 'Request cannot be edited. Status is not 1. Current status = '.$existingRequest->status,
                    'code' => 403,
                    'success' => false,
                ], 403);
            }

            $fieldsToUpdate = ['office', 'email_inl', 'atasan_id', 'kategori', 'jenis_permintaan', 'keperluan'];

            foreach ($fieldsToUpdate as $field) {
                if ($request->has($field)) {
                    $existingRequest->$field = $request->input($field);
                }
            }


            $existingRequest->save();

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

    public function cancel(Request $request, $id)
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

            if ($data->user_id != $this->userData->sub) {
                return response()->json([
                    'message' => 'User not own this request.',
                    'code' => 403,
                    'success' => false,
                ], 403);
            }

            $data->update([
                'status' => 0,
                'keterangan' => $request->input('keterangan'),
                'info' => "Canceled by ".$this->userData->name
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
