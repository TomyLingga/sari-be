<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Problem;
use App\Models\Category;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;

class ProblemController extends Controller
{
    public function index()
    {
        $data = Problem::where('user_id', $this->userData->sub)
                    ->with('category', 'user', 'executor')
                    ->latest()
                    ->get();

        if ($data->isEmpty()) {
            return response()->json([
                'message' => "No Problem Found",
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

        try {
            $validator = Validator::make($request->all(), [
                'office' => 'required',
                'email_inl' => 'required',
                'remark' => 'required',
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

            $problems = Problem::whereMonth('created_at', $currentMonth)
                            ->whereYear('created_at', $currentYear)
                            ->get();

            $count = $problems->filter(function ($problem) use ($category, $request) {
                return $problem->category->id_kategori == $category->id_kategori;
            })->count() + 1;

            $countFormatted = str_pad($count, 4, '0', STR_PAD_LEFT);

            $monthInRoman = $this->getMonthInRoman($currentMonth);

            $nomor = "IN{$countFormatted}/{$departmentData['kode']}/{$monthInRoman}/{$currentYear}";

            $data = [
                'no_wo' => $nomor,
                'jenis_permintaan' => $request->jenis_permintaan,
                'user_id' => $this->userData->sub,
                'keperluan' => $request->keperluan,
                'office' => $request->office,
                'status' => 1,
                'info' => "Waiting for a response from the ".$departmentData['department']." department ",
                'waktu_request' => now()->toDateString(),
                'email_inl' => $request->email_inl,
                'remark' => $request->remark,
                'nrk' => $this->userData->nrk,
                'hp' => $this->userData->no_hp,
            ];

            $data = Problem::create($data);

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
            $existingProblem = Problem::with('category','user','executor')
                                        ->findOrFail($id);

            if ($existingProblem->user_id != $this->userData->sub) {
                return response()->json([
                    'message' => 'User not own this request.',
                    'code' => 403,
                    'success' => false,
                ], 403);
            }

            if ($existingProblem->status != 1) {
                return response()->json([
                    'message' => 'Problem cannot be edited. Status is not 1. Current status = '.$existingProblem->status,
                    'code' => 403,
                    'success' => false,
                ], 403);
            }

            $fieldsToUpdate = ['office', 'email_inl', 'remark', 'jenis_permintaan', 'keperluan'];

            foreach ($fieldsToUpdate as $field) {
                if ($request->has($field)) {
                    $existingProblem->$field = $request->input($field);
                }
            }


            $existingProblem->save();

            DB::commit();

            return response()->json([
                'data' => $existingProblem,
                'message' => 'Problem Updated Successfully',
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
            $data = Problem::findOrFail($id);

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
