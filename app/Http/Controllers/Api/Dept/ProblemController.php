<?php

namespace App\Http\Controllers\Api\Dept;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use App\Models\Problem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use DateTime;

class ProblemController extends Controller
{
    public function index()
    {
        $departemenId = $this->userData->departemen;

        $categoryIds = Category::where('id_kategori', $departemenId)->pluck('id');

        $data = Problem::with('category', 'user', 'executor')->whereIn('jenis_permintaan', $categoryIds)->latest()->get();

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

    public function show($id){
        try {

            $data = Problem::with('category', 'user', 'executor')->findOrFail($id);

            return response()->json([
                'data' => $data,
                'message' => 'Data Retrieved Successfully',
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

    public function execute($id)
    {
        try {
            $data = Problem::findOrFail($id);

            $data->update([
                'status' => 2,
                'info' => "Handled by ".$this->userData->name,
                'waktu_mulai' => now(),
                'eksekutor' => $this->userData->sub,
            ]);

            return response()->json([
                'data' => $data,
                'message' => "Problem reject Success",
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

    public function done(Request $request, $id)
    {
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

        try {
            $data = Problem::findOrFail($id);
            $doneTime = now();

            $datetime1 = new DateTime($data->waktu_mulai);
            $datetime2 = new DateTime($doneTime);
            $interval = $datetime1->diff($datetime2);
            $waktu = $interval->format('%d Hari %h Jam %i Menit');

            $data->update([
                'status' => 3,
                'keterangan' => $request->input('keterangan'),
                'info' => "Done by {$this->userData->name}",
                'waktu_selesai' => now(),
                'waktu_pengerjaan' => $waktu,
            ]);

            return response()->json([
                'data' => $data,
                'message' => "Problem reject Success",
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
            $data = Problem::findOrFail($id);

            $data->update([
                'status' => 0,
                'keterangan' => $request->input('keterangan'),
                'info' => "Declined by ".$this->userData->name
            ]);

            return response()->json([
                'data' => $data,
                'message' => "Problem reject Success",
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
