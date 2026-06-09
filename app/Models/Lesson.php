<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lesson extends Model
{
    // CRM database connection
    protected $connection = 'mysql_portal';

    // CRM table name
    protected $table = 'lms_lessons';

    protected $guarded = [];

    public function module()
    {
        return $this->belongsTo(CourseModule::class, 'module_id');
    }

    public function getSafeVideoUrlAttribute()
    {
        $url = $this->video_url;
        if (!$url) {
            return null;
        }

        $isUrl = \Illuminate\Support\Str::startsWith($url, ['http://', 'https://']);
        if (!$isUrl) {
            return $url;
        }

        $lowerUrl = strtolower($url);
        if (str_contains($lowerUrl, 'sharepoint.com') || str_contains($lowerUrl, 'onedrive.live.com') || str_contains($lowerUrl, '1drv.ms')) {
            $parsedUrl = parse_url($url);
            parse_str($parsedUrl['query'] ?? '', $queryParams);

            $queryParams['web'] = '1';
            $queryParams['action'] = 'embedview';

            $newQuery = http_build_query($queryParams);
            $newUrl = ($parsedUrl['scheme'] ?? 'https') . '://' . ($parsedUrl['host'] ?? '');

            if (isset($parsedUrl['port'])) {
                $newUrl .= ':' . $parsedUrl['port'];
            }
            if (isset($parsedUrl['path'])) {
                $newUrl .= $parsedUrl['path'];
            }
            if (!empty($newQuery)) {
                $newUrl .= '?' . $newQuery;
            }
            if (isset($parsedUrl['fragment'])) {
                $newUrl .= '#' . $parsedUrl['fragment'];
            }

            return $newUrl;
        }

        return $url;
    }
}
