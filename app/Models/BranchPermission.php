<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BranchPermission extends Model
{
    protected $table = 'branch_permissions';

    protected $fillable = ['branch_id', 'permission'];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
