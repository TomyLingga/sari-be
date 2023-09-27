<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class FormRequestController extends Controller
{
    private function findOrFail($model, $conditions)
    {
        return $model::where($conditions)->firstOrFail();
    }

    public function index()
    {
        // dd($this->userData);
        $data = FormRequest::where('user_id', $this->userData->sub)
                    ->latest()
                    ->get();

        if ($data->isEmpty()) {
            return response()->json([
                'message' => "No Suppliers Found",
                'success' => false,
                'code' => 404
            ], 404);
        }

        return response()->json([
            'data' => $data,
            'message' => 'Spdk Retrieved Successfully',
            'code' => 200,
            'success' => true,
        ], 200);
    }

    public function store(Request $request)
    {
        DB::beginTransaction();

        try {
            $validator = Validator::make($request->all(), [
                'atasan_id' => 'required',
                'kategori' => 'required',
                'jenis_permintaan' => 'required',
                'keperluan' => 'required',
                'office' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => $validator->errors(),
                    'code' => 400,
                    'success' => false
                ], 400);
            }

            $groupId = $request->input('id_grup');
            $subGroupId = $request->input('id_sub_grup');
            $tglPerolehan = $request->input('tgl_perolehan');
            $spesifikasi = json_encode($request->spesifikasi);

            $group = $this->findOrFail(Group::class, ['id' => $groupId]);
            $subGroup = $this->findOrFail(SubGroup::class, ['id' => $subGroupId, 'id_grup' => $groupId]);
            $lokasi = $this->findOrFail(Location::class, ['id' => $request->input('id_lokasi')]);
            // $supplier = $this->findOrFail(Supplier::class, ['id' => $request->input('id_supplier')]);
            // $adjustment = $this->findOrFail(Adjustment::class, ['id' => $request->input('id_kode_adjustment')]);

            $departmentId = $request->input('id_departemen');

            // sleep(3);

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

            // sleep(3);

            $getPic = Http::withHeaders([
                'Authorization' => $this->token,
            ])->get($this->urlUser . $request->input('id_pic'));

            $pic = $getPic->json()['data'] ?? [];

            if (empty($pic)) {
                return response()->json([
                    'message' => 'User/PIC not found',
                    'success' => true,
                    'code' => 401
                ], 401);
            }

            $kodeAktiva = str_pad(FixedAssets::where('id_sub_grup', $subGroupId)->count(), 2, '0', STR_PAD_LEFT);

            $month = date('m', strtotime($tglPerolehan));
            $year = date('Y', strtotime($tglPerolehan));
            $attemptCount = 0;
            $maxAttempts = 15;

            $nomorAset = FixedAssets::whereHas('subGroup', function ($query) use ($groupId) {
                $query->where('id_grup', $groupId);
            })
                ->where('id_departemen', $departmentData['id'])
                ->whereMonth('tgl_perolehan', $month)
                ->whereYear('tgl_perolehan', $year)
                ->count() + 1;

            $numberOfLeadingZeros = max(0, 5 - strlen((string) $nomorAset));
            $formattedNomorAset = $numberOfLeadingZeros > 0 ? str_repeat('0', $numberOfLeadingZeros) . $nomorAset : $nomorAset;

            do {
                $existingAsset = FixedAssets::whereHas('subGroup', function ($query) use ($groupId) {
                    $query->where('id_grup', $groupId);
                })
                    ->where('id_departemen', $departmentData['id'])
                    ->whereMonth('tgl_perolehan', $month)
                    ->whereYear('tgl_perolehan', $year)
                    ->where('nomor', $formattedNomorAset)
                    ->first();

                if ($existingAsset) {
                    $nomorAset = $nomorAset + 1;
                    $formattedNomorAset = str_pad($nomorAset, 5, '0', STR_PAD_LEFT);

                    $attemptCount++;
                } else {
                    break;
                }

            } while ($attemptCount < $maxAttempts);

            if ($attemptCount === $maxAttempts) {
                DB::rollback();
                return response()->json([
                    'message' => 'Could not find an available asset number after multiple attempts',
                    'success' => false,
                    'code' => 409
                ], 409);
            }

            $data = [
                'id_sub_grup' => $subGroupId,
                'nama' => $request->nama,
                'brand' => $request->brand,
                'kode_aktiva' => $kodeAktiva,
                'kode_penyusutan' => $kodeAktiva,
                'nomor' => $formattedNomorAset,
                'masa_manfaat' => $request->masa_manfaat,
                'tgl_perolehan' => $tglPerolehan,
                'nilai_perolehan' => $request->nilai_perolehan,
                'nilai_depresiasi_awal' => $request->nilai_depresiasi_awal,
                'id_lokasi' => $lokasi->id,
                'id_departemen' => $departmentData['id'],
                'id_pic' => $pic['id'],
                'cost_centre' => $request->cost_centre,
                'kondisi' => $request->kondisi,
                'id_supplier' => $request->id_supplier,
                'id_mis' => $request->id_mis,
                'spesifikasi' => $spesifikasi,
                'keterangan' => $request->keterangan,
                'status' => 1,
            ];

            if ($request->has('id_kode_adjustment')) {
                $data['id_kode_adjustment'] = $request->id_kode_adjustment;
            }

            $data = FixedAssets::create($data);

            if ($request->has('fairValue')) {
                $fairValue = FairValue::create([
                    'id_fixed_asset' => $data->id,
                    'nilai' => $request->fairValue,
                ]);
            }

            if ($request->has('valueInUse')) {
                $valueInUse = ValueInUse::create([
                    'id_fixed_asset' => $data->id,
                    'nilai' => $request->valueInUse,
                ]);
            }

            $data->load('subGroup', 'location', 'adjustment', 'fairValues', 'valueInUses');

            LoggerService::logAction($this->userData, $data, 'create', null, $data->toArray());

            DB::commit();

            return response()->json([
                'data' => $data,
                'message' => 'Asset Created Successfully',
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
