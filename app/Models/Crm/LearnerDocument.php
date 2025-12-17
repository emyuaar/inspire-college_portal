<?php

namespace App\Models\Crm;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LearnerDocument extends Model
{
    use SoftDeletes;

    protected $connection = 'mysql_crm';
    protected $table = 'learner_documents';

    protected $fillable = [
        'learner_id',
        'category',
        'title',
        'file_path',
    ];
}