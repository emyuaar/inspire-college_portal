<?php

namespace App\Models\Website;

use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    // WEBSITE database
    protected $connection = 'mysql_website';

    protected $table = 'courses';

    protected $fillable = [
        'title',
        'slug',
        'excerpt',
        'overview',
        'modules',
        'requirements',
        'assessment',
        'qualification',
        'career',
        'image',
        'regular_price',
        'sale_price',
        'deposit',
        'number_of_months',
        'monthly_installment',
        'qualification_id',
        'course_category_id',
        'status',
        'completion_mode',
        'uses_credit_based_completion',
        'total_required_credits',
        'mandatory_credits_required',
        'optional_credits_required',
        'minimum_optional_units',
        'maximum_optional_units',
        'completion_rule_type',
    ];

    protected $casts = [
        'uses_credit_based_completion' => 'boolean',
        'total_required_credits' => 'decimal:2',
        'mandatory_credits_required' => 'decimal:2',
        'optional_credits_required' => 'decimal:2',
        'minimum_optional_units' => 'integer',
        'maximum_optional_units' => 'integer',
    ];

    protected $dates = [
        'deleted_at',
        'created_at',
        'updated_at',
    ];

    public function enrolments()
    {
        return $this->hasMany(\App\Models\Crm\Enrolment::class, 'course_id');
    }

    public function category()
    {
        return $this->belongsTo(CourseCategory::class, 'course_category_id');
    }

    public function promotions()
    {
        return $this->hasMany(CoursePromotion::class, 'course_id');
    }

    public function qualification()
    {
        return $this->belongsTo(Qualification::class, 'qualification_id');
    }

    public function usesCreditBasedCompletion(): bool
    {
        return $this->completion_mode === 'credit_based'
            && (bool) $this->uses_credit_based_completion;
    }

    // pivot row (deposit/months/monthly)
    public function activeCoursePromotion()
    {
        $now = now();
        // Timezone note: Portal usually runs in UTC or app config. Website used 'Europe/London'. 
        // I will use now() respecting app timezone for consistency, or strictly copy 'Europe/London' if critical.
        // Assuming app timezone is aligned.

        return $this->hasOne(CoursePromotion::class, 'course_id')
            ->where('course_promotions.is_active', 1)
            ->whereHas('promotion', function ($q) use ($now) {
                $q->where('promotions.is_active', 1)
                ->where(function ($qq) use ($now) {
                    $qq->whereNull('promotions.starts_at')
                        ->orWhere('promotions.starts_at', '<=', $now);
                })
                ->where(function ($qq) use ($now) {
                    $qq->whereNull('promotions.ends_at')
                        ->orWhere('promotions.ends_at', '>=', $now);
                });
            })
            ->latest('course_promotions.id');
    }

    // actual promotion (badge/discount/dates) via pivot
    public function activePromotion()
    {
        $now = now();

        return $this->hasOneThrough(
                Promotion::class,
                CoursePromotion::class,
                'course_id',
                'id',
                'id',
                'promotion_id'
            )
            ->where('course_promotions.is_active', 1)
            ->where('promotions.is_active', 1)
            ->where(function ($q) use ($now) {
                $q->whereNull('promotions.starts_at')
                ->orWhere('promotions.starts_at', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('promotions.ends_at')
                ->orWhere('promotions.ends_at', '>=', $now);
            })
            ->latest('course_promotions.id');
    }
}
