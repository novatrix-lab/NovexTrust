<?php

declare(strict_types=1);

// Arabic cockpit copy (RTL). Refined in the milestone-10 i18n pass.
return [
    'app_tagline' => 'لوحة الامتثال',
    'nav' => [
        'dashboard' => 'لوحة التحكم',
        'entities' => 'المنشآت',
        'import' => 'استيراد',
        'logout' => 'تسجيل الخروج',
    ],
    'login' => [
        'title' => 'تسجيل الدخول',
        'email' => 'البريد الإلكتروني',
        'password' => 'كلمة المرور',
        'submit' => 'تسجيل الدخول',
        'failed' => 'بيانات الاعتماد هذه لا تطابق سجلاتنا.',
    ],
    'status' => [
        'safe' => 'آمن',
        'due_soon' => 'يقترب موعده',
        'overdue' => 'متأخر',
        'done' => 'منجز',
    ],
    'dashboard' => [
        'title' => 'لوحة الامتثال',
        'filter_all' => 'جميع الحالات',
        'col_holder' => 'المنشأة / الشخص',
        'col_document' => 'المستند',
        'col_due' => 'تاريخ الاستحقاق',
        'col_status' => 'الحالة',
        'col_responsible' => 'المسؤول',
        'unassigned' => 'غير مُسند',
        'mark_done' => 'وضع كمنجز',
        'empty' => 'لا توجد مواعيد نهائية بعد. ارفع المستندات وأكّدها لبدء التتبع.',
    ],
    'entities' => [
        'title' => 'منشآت العملاء',
        'import' => 'استيراد CSV',
        'col_name' => 'الاسم القانوني',
        'col_jurisdiction' => 'الاختصاص',
        'col_documents' => 'المستندات',
        'col_people' => 'الأشخاص',
        'view' => 'عرض',
        'empty' => 'لا توجد منشآت بعد. استورد ملف CSV لإضافة عملائك.',
        'documents' => 'المستندات',
        'deadlines' => 'المواعيد النهائية',
        'people' => 'الأشخاص',
    ],
    'import' => [
        'title' => 'استيراد المنشآت (CSV)',
        'help' => 'الأعمدة: legal_name (مطلوب)، trade_name، jurisdiction_type (mainland|freezone)، authority، license_number.',
        'file' => 'ملف CSV',
        'submit' => 'استيراد',
        'result' => 'تم استيراد :imported، وتم تخطي :skipped.',
    ],
];
