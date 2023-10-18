<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory, Notifiable;

    protected $primaryKey = 'id';

    protected $table = 'jenis_permintaan';

    protected $fillable = [
        'id_kategori',
        'nama_kategori',
        'nama_permintaan',
        'created_at',
        'updated_at',
    ];

    public function formRequests()
    {
        return $this->hasMany(FormRequest::class, 'jenis_permintaan');
    }

    public function problems()
    {
        return $this->hasMany(Problem::class, 'jenis_permintaan');
    }
}
