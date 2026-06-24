<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourseModule extends Model
{
    public const SECTION_TYPES = [
        'unit' => 'Unit',
        'guidelines' => 'Guidelines',
        'general_resources' => 'General Resources',
        'induction' => 'Induction',
        'support_material' => 'Support Material',
        'bonus_material' => 'Bonus / Extra Material',
    ];

    protected $connection = 'mysql_portal';
    protected $table = 'lms_course_modules';

    protected $fillable = [
        'course_id',
        'title',
        'description',
        'sort_order',
        'section_type',
        'unit_code',
        'unit_title',
        'credits',
        'glh',
        'tqt',
        'unit_type',
        'optional_group_id',
        'included_in_completion',
        'status',
    ];

    protected $casts = [
        'credits' => 'decimal:2',
        'glh' => 'decimal:2',
        'tqt' => 'decimal:2',
        'included_in_completion' => 'boolean',
    ];

    public function lessons()
    {
        return $this->hasMany(Lesson::class, 'module_id')->orderBy('sort_order');
    }

    public function assignments()
    {
        return $this->hasMany(Assignment::class, 'module_id')->orderBy('id');
    }

    public function optionalGroup()
    {
        return $this->belongsTo(CourseOptionalGroup::class, 'optional_group_id');
    }

    public function selections()
    {
        return $this->hasMany(LearnerCourseUnitSelection::class, 'module_id');
    }

    public function isUnit(): bool
    {
        return ($this->section_type ?: 'unit') === 'unit';
    }

    public function getSectionTypeLabelAttribute(): string
    {
        return self::SECTION_TYPES[$this->section_type ?: 'unit'] ?? 'Section';
    }
}
