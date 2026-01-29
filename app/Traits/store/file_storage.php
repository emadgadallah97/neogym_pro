<?php

namespace App\Traits\store;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

trait file_storage
{
    /**
     * يحفظ ملف واحد أو عدة ملفات على disk('public')
     * ويُعيد:
     * - string في حالة ملف واحد
     * - array في حالة عدة ملفات
     */
    function file_storage($files, $get_directory)
    {
        $paths = [];

        $time = Carbon::now();
        $directory = $get_directory . '/' . $time->format('Y') . '/' . $time->format('m');

        $isMultiple = is_array($files);
        $files = $isMultiple ? $files : [$files];

        foreach ($files as $file) {
            if ($file) {
                // احتفظ بالامتداد الحقيقي
                $extension = $file->getClientOriginalExtension() ?: $file->extension();
                $file_name = $time->format('His') . rand(1, 9) . '.' . $extension;

                // تخزين داخل public
                Storage::disk('public')->putFileAs($directory, $file, $file_name);

                // خزن المسار بالنسبة لـ disk('public')
                $paths[] ='attachments/' . $directory . '/' . $file_name;
            }
        }

        return $isMultiple ? $paths : ($paths[0] ?? null);
    }
}
