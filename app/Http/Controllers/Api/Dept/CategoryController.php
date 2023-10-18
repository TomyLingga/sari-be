<?php

namespace App\Http\Controllers\Api\Dept;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Category;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;

class CategoryController extends Controller
{
    private function findOrFail($model, $conditions)
    {
        return $model::where($conditions)->firstOrFail();
    }

    public function index()
    {
        // dd($this->userData);
        $data = Category::where('id_kategori', $this->userData->departemen)
                    ->orderBy('nama_permintaan', 'ASC')
                    ->get();

        if ($data->isEmpty()) {
            return response()->json([
                'message' => "No Data Found",
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

    public function indexByDept($id)
    {
        $data = Category::where('id_kategori', $id)
                    ->orderBy('nama_permintaan', 'asc')
                    ->get();

        if ($data->isEmpty()) {
            return response()->json([
                'message' => "No Data Found",
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

    public function indexDept()
    {
        $data = Category::orderBy('nama_kategori', 'asc')
                        ->pluck('nama_kategori', 'id_kategori');

        if ($data->isEmpty()) {
            return response()->json([
                'message' => "No Data Found",
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
                'nama_permintaan' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => $validator->errors(),
                    'code' => 400,
                    'success' => false
                ], 400);
            }

            $departmentId = $this->userData->departemen;

            $deptResponse = Http::withHeaders([
                'Authorization' => $this->token,
            ])->get($this->urlDept . $departmentId);

            $departmentData = $deptResponse->json()['data'] ?? [];

            if (empty($departmentData)) {
                return response()->json([
                    'message' => 'Department not found',
                    'success' => true,
                    'code' => 401
                ], 401);
            }

            $Category = Category::create([
                'id_kategori' => $departmentId,
                'nama_kategori' => $departmentData['department'],
                'nama_permintaan' => $request->get('nama_permintaan'),
            ]);

            DB::commit();

            return response()->json([
                'data' => $Category,
                'message' => 'Data Created Successfully.',
                'code' => 200,
                'success' => true
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

    public function show(Category $category)
    {
        try {
            if ($category->id_kategori != $this->userData->departemen) {
                return response()->json([
                    'message' => 'Category not found for this department',
                    'success' => false,
                    'code' => 404
                ], 404);
            }

            return response()->json([
                'data' => $category,
                'message' => 'Category Retrieved Successfully',
                'code' => 200,
                'success' => true,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Something went wrong',
                'err' => $e->getTrace()[0],
                'errMsg' => $e->getMessage(),
                'code' => 500,
                'success' => false,
            ], 500);
        }
    }

    public function update(Request $request, Category $category)
    {
        DB::beginTransaction();

        try {
            $validator = Validator::make($request->all(), [
                'nama_permintaan' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => $validator->errors(),
                    'code' => 400,
                    'success' => false
                ], 400);
            }

            $departmentId = $this->userData->departemen;

            $deptResponse = Http::withHeaders([
                'Authorization' => $this->token,
            ])->get($this->urlDept . $departmentId);

            $departmentData = $deptResponse->json()['data'] ?? [];

            if (empty($departmentData)) {
                return response()->json([
                    'message' => 'Department not found',
                    'success' => true,
                    'code' => 401
                ], 401);
            }

            $category->nama_permintaan = $request->get('nama_permintaan');
            $category->save();

            DB::commit();

            return response()->json([
                'data' => $category,
                'message' => 'Data Updated Successfully.',
                'code' => 200,
                'success' => true
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
