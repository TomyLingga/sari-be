<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Model;

class FormRequest extends Model
{
    use HasFactory, Notifiable;

    protected $primaryKey = 'id';

    protected $table = 'fr_it';

    protected $fillable = [
        'no_wo',
        'kategori',
        'jenis_permintaan',
        'user_id',
        'atasan_id',
        'approve_user',
        'approve_atasan',
        'approve_kategori_fr',
        'approve_kategori_mgr',
        'keperluan',
        'prioritas',
        'waktu_request',
        'office',
        'lokasi',
        'email_inl',
        'waktu_selesai',
        'waktu_mulai',
        'eksekutor',
        'keterangan',
        'catatan',
        'nrk',
        'hp_a',
        'hp_b',
        'hp_c',
        'hp_d',
        'info',
        'status',
        'created_at',
        'updated_at',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class, 'jenis_permintaan');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function atasan()
    {
        return $this->belongsTo(User::class, 'atasan_id');
    }
    public function deptPic()
    {
        return $this->belongsTo(User::class, 'waktu_request');
    }
    public function executor()
    {
        return $this->belongsTo(User::class, 'eksekutor');
    }
}
