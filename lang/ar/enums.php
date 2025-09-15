<?php

return [
    'store_status' => [
        'pending' => 'قيد الانتظار',
        'approved' => 'مقبول',
        'rejected' => 'مرفوض',
    ],
    'setting_type' => [
        'string' => 'نص',
        'integer' => 'رقم صحيح',
        'boolean' => 'قيمة منطقية',
        'file' => 'ملف',
    ],
    'working_day' => [
        'saturday' => 'السبت',
        'sunday' => 'الأحد',
        'monday' => 'الإثنين',
        'tuesday' => 'الثلاثاء',
        'wednesday' => 'الأربعاء',
        'thursday' => 'الخميس',
        'friday' => 'الجمعة',
    ],
    'product_status' => [
        'pending' => 'قيد الانتظار',
        'active' => 'نشط',
        'inactive' => 'غير نشط',
        'rejected' => 'مرفوض',
    ],
    'sale_type' => [
        'percentage' => 'نسبة مئوية',
        'fixed' => 'مبلغ ثابت',
    ],
    'add_on_applicable_to' => [
        'product' => 'منتج',
        'delivery' => 'توصيل',
    ],
    'option_input_type' => [
        'single' => 'اختيار واحد',
        'multiple' => 'اختيارات متعددة',
    ],
    'coupon_type' => [
        'free_delivery' => 'توصيل مجاني',
        'regular' => 'عادي',
    ],
    'object_type' => [
        'general' => 'عام',
        'product' => 'منتج',
        'store' => 'متجر',
        'category' => 'فئة',
    ],
    'compaign_participation_status' => [
        'pending' => 'قيد الانتظار',
        'approved' => 'مقبول',
        'rejected' => 'مرفوض',
        'withdrawn' => 'منسحب',
    ],
    'offer_status' => [
        'pending' => 'قيد الانتظار',
        'active' => 'نشط',
        'inactive' => 'غير نشط',
        'expired' => 'منتهي',
        'rejected' => 'مرفوض',
        'pasued' => 'معلق',
        'approved' => 'مقبول',
    ],
    'plan_billing_cycle' => [
        'monthly' => 'شهري',
        'yearly' => 'سنوي',
    ],
    'plan_type' => [
        'subscription' => 'اشتراك',
        'commission' => 'عمولة',
    ],
    'subscription_status' => [
        'active' => 'نشط',
        'inactive' => 'غير نشط',
        'cancelled' => 'ملغي',
        'expired' => 'منتهي',
        'trial' => 'تجريبي',
        'pending' => 'قيد الانتظار'
    ],
    'couier_avaliable_status' => [
        'available' => 'متاح',
        'busy' => 'مشغول',
        'off' => 'متوقف',
    ],
    'user_status' => [
        'pending' => 'قيد الانتظار',
        'active' => 'نشط',
        'inactive' => 'غير نشط',
        'blocked' => 'مسح',
    ],
    'ad_type' => [
        'standard' => 'عادي',
        'video' => 'فيديو',
    ],
    'ad_status' => [
        'pending' => 'قيد الانتظار',
        'active' => 'نشط',
        'inactive' => 'غير نشط',
        'rejected' => 'مرفوض',
        'pasued' => 'متوقف مؤقتًا',
        'approved' => 'موافق عليه',
        'expired' => 'منتهي',
    ],
    'address_type' => [
        'work' => 'عمل',
        'home' => 'منزل',
        'other' => 'أخرى',
    ],
    'report_type' => [
    'delivery_issue' => 'مشكلة توصيل',
        'payment_issue' => 'مشكلة دفع',
        'order_issue' => 'مشكلة طلب',
        'worng_item_received' => 'تم استلام سلعة خاطئة',
        'damaged_item_received' => 'تم استلام سلعة تالفة',
        'customer_service_issue' => 'مشكلة خدمة العملاء',
        'app_bug' => 'خطأ في التطبيق',
        'other' => 'أخرى',
    ],
    'report_status' => [
        'pending' => 'قيد الانتظار',
        'processing' => 'قيد المعالجة',
        'resolved' => 'تم الحل',
        'closed' => 'مغلق',
    ],
    'unit_type' => [
        'weight' => 'وزن',
        'volume' => 'حجم',
        'length' => 'طول',
        'count' => 'عدد',
    ],
    'national_identity_type' => [
        'national_id' => 'الهوية الوطنية',
        'driving_license' => 'رخصة القيادة',
        'passport' => 'جواز السفر',
        'other' => 'أخرى',
    ],
    'product_watermark_position' => [
        'top_left' => 'أعلى اليسار',
        'top_right' => 'أعلى اليمين',
        'bottom_left' => 'أسفل اليسار',
        'bottom_right' => 'أسفل اليمين',
        'center' => 'الوسط',
    ],
    'conversation_type' => [
        'support' => 'الدعم',
        'delivery' => 'التوصيل',
        'inquiry' => 'استفسار',
    ],
    'message_type' => [
        'text' => 'نص',
        'image' => 'صورة',
        'video' => 'فيديو',
        'audio' => 'صوت',
    ],
    'delivery_type_unit' => [
        'minute' => 'دقيقة',
        'hour' => 'ساعة',
        'day' => 'يوم',
    ],
    'order_status' => [
        'pending' => 'قيد الانتظار',
        'confirmed' => 'تم التأكيد',
        'processing' => 'قيد المعالجة',
        'ready_for_delivery' => 'جاهز للتسليم',
        'on_the_way' => 'في الطريق',
        'delivered' => 'تم التسليم',
        'cancelled' => 'تم الإلغاء',
    ],
    'order_type' => [
        'pickup' => 'استلام',
        'deliver' => 'توصيل',
        'service' => 'خدمة',
        'pos' => 'نقطة بيع',
    ],
    'payment_status' => [
        'unpaid' => 'غير مدفوع',
        'paid' => 'مدفوع',
        'partially_paid' => 'مدفوع جزئياً',
    ],

];
