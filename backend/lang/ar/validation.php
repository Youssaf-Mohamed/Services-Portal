<?php

return [
    'required' => 'حقل :attribute مطلوب.',
    'email' => 'يجب أن يكون :attribute عنوان بريد إلكتروني صحيح.',
    'min' => [
        'string' => 'يجب أن يحتوي :attribute على الأقل على :min حرفًا.',
        'numeric' => 'يجب أن تكون قيمة :attribute على الأقل :min.',
    ],
    'max' => [
        'string' => 'يجب ألا يتجاوز :attribute :max حرفًا.',
        'numeric' => 'يجب ألا تتجاوز قيمة :attribute :max.',
    ],
    'unique' => ':attribute مُستخدم بالفعل.',
    'confirmed' => 'تأكيد :attribute غير مطابق.',
    
    'attributes' => [
        'email' => 'البريد الإلكتروني',
        'password' => 'كلمة المرور',
        'name' => 'الاسم',
    ],
];
