<?php

namespace App\Http\Controllers\dashbord;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Intervention\Image\Facades\Image; // مكتبة للتعامل مع الصور
use thiagoalessio\TesseractOCR\TesseractOCR;
 // مكتبة للتعرف على النصوص في الصورة

class NationalIdController extends Controller
{
    public function upload(Request $request)
    {
        // التحقق من الصورة
        $request->validate([
            'national_id_image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        // تخزين الصورة في مجلد 'uploads'
        $image = $request->file('national_id_image');
        $imagePath = $image->store('uploads', 'public');

        // استخدام Tesseract لاستخراج النص من الصورة
        $text = (new TesseractOCR('attachments/uploads/45qChMDObRoDelg2kEMSevv6nZPxLvi3nOQ1QCsi.jpg'))->run();

        // معالجة النص لاستخراج البيانات
        $data = $this->extractDataFromText($text);

        // عرض البيانات المستخرجة أو تخزينها في قاعدة البيانات
        return view('dashbord.show', compact('data'));
    }

    private function extractDataFromText($text)
    {
        // نموذج لاستخراج البيانات باستخدام التعبيرات العادية
        // سيتم تعديلها بناءً على تنسيق البيانات المستخرجة من الرقم القومي

        preg_match('/رقم القومي: (\d+)/', $text, $idMatches);
        preg_match('/الاسم: ([\w\s]+)/', $text, $nameMatches);
        preg_match('/تاريخ الميلاد: (\d{2}\/\d{2}\/\d{4})/', $text, $dobMatches);

        return [
            'national_id' => $idMatches[1] ?? null,
            'name' => $nameMatches[1] ?? null,
            'dob' => $dobMatches[1] ?? null,
        ];
    }
}