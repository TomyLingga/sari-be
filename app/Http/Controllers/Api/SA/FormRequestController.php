<?php

namespace App\Http\Controllers\Api\SA;

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
        $data = FormRequest::with('category','atasan', 'user', 'deptPic', 'executor')
                        ->findOrFail($id);

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
}
