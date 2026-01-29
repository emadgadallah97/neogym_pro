{{-- show_data.blade.php --}}
<div>
    <h3>البيانات المستخرجة:</h3>
    <p>رقم القومي: {{ $data['national_id'] }}</p>
    <p>الاسم: {{ $data['name'] }}</p>
    <p>تاريخ الميلاد: {{ $data['dob'] }}</p>
</div>
