<?php

    return [

        /*
        |--------------------------------------------------------------------------
        | Validation Language Lines
        |--------------------------------------------------------------------------
        |
        | The following language lines contain the default error messages used by
        | the validator class. Some of these rules have multiple versions such
        | as the size rules. Feel free to tweak each of these messages here.
        |
        */

        'accepted' => 'يجب قبول حقل :attribute.',
        'accepted_if' => 'يجب قبول حقل :attribute عندما يكون :other هو :value.',
        'active_url' => 'حقل :attribute يجب أن يكون رابطًا صالحًا.',
        'after' => 'حقل :attribute يجب أن يكون تاريخًا بعد :date.',
        'after_or_equal' => 'حقل :attribute يجب أن يكون تاريخًا بعد أو يساوي :date.',
        'alpha' => 'حقل :attribute يجب أن يحتوي على أحرف فقط.',
        'alpha_dash' => 'حقل :attribute يجب أن يحتوي على أحرف، أرقام، شرطات، وشرطات سفلية فقط.',
        'alpha_num' => 'حقل :attribute يجب أن يحتوي على أحرف وأرقام فقط.',
        'any_of' => 'حقل :attribute غير صالح.',
        'array' => 'حقل :attribute يجب أن يكون مصفوفة.',
        'ascii' => 'حقل :attribute يجب أن يحتوي على أحرف أبجدية رقمية ذات بايت واحد ورموز فقط.',
        'before' => 'حقل :attribute يجب أن يكون تاريخًا قبل :date.',
        'before_or_equal' => 'حقل :attribute يجب أن يكون تاريخًا قبل أو يساوي :date.',
        'between' => [
            'array' => 'حقل :attribute يجب أن يحتوي بين :min و :max عنصر.',
            'file' => 'حقل :attribute يجب أن يكون بين :min و :max كيلوبايت.',
            'numeric' => 'حقل :attribute يجب أن يكون بين :min و :max.',
            'string' => 'حقل :attribute يجب أن يكون بين :min و :max حرف.',
        ],
        'boolean' => 'حقل :attribute يجب أن يكون صحيح أو خطأ.',
        'can' => 'حقل :attribute يحتوي على قيمة غير مصرح بها.',
        'confirmed' => 'تأكيد حقل :attribute لا يتطابق.',
        'contains' => 'حقل :attribute يفتقد قيمة مطلوبة.',
        'current_password' => 'كلمة المرور غير صحيحة.',
        'date' => 'حقل :attribute يجب أن يكون تاريخًا صالحًا.',
        'date_equals' => 'حقل :attribute يجب أن يكون تاريخًا مساويًا لـ :date.',
        'date_format' => 'حقل :attribute يجب أن يتطابق مع الصيغة :format.',
        'decimal' => 'حقل :attribute يجب أن يحتوي على :decimal منازل عشرية.',
        'declined' => 'يجب رفض حقل :attribute.',
        'declined_if' => 'يجب رفض حقل :attribute عندما يكون :other هو :value.',
        'different' => 'حقل :attribute و :other يجب أن يكونا مختلفين.',
        'digits' => 'حقل :attribute يجب أن يكون :digits أرقام.',
        'digits_between' => 'حقل :attribute يجب أن يكون بين :min و :max أرقام.',
        'dimensions' => 'حقل :attribute يحتوي على أبعاد صورة غير صالحة.',
        'distinct' => 'حقل :attribute يحتوي على قيمة مكررة.',
        'doesnt_end_with' => 'حقل :attribute يجب ألا ينتهي بأي من القيم التالية: :values.',
        'doesnt_start_with' => 'حقل :attribute يجب ألا يبدأ بأي من القيم التالية: :values.',
        'email' => 'حقل :attribute يجب أن يكون بريدًا إلكترونيًا صالحًا.',
        'ends_with' => 'حقل :attribute يجب أن ينتهي بأحد القيم التالية: :values.',
        'enum' => ':attribute المحدد غير صالح.',
        'exists' => ':attribute المحدد غير صالح.',
        'extensions' => 'حقل :attribute يجب أن يحتوي على أحد الامتدادات التالية: :values.',
        'file' => 'حقل :attribute يجب أن يكون ملفًا.',
        'filled' => 'حقل :attribute يجب أن يحتوي على قيمة.',
        'gt' => [
            'array' => 'حقل :attribute يجب أن يحتوي على أكثر من :value عنصر.',
            'file' => 'حقل :attribute يجب أن يكون أكبر من :value كيلوبايت.',
            'numeric' => 'حقل :attribute يجب أن يكون أكبر من :value.',
            'string' => 'حقل :attribute يجب أن يكون أكبر من :value حرف.',
        ],
        'gte' => [
            'array' => 'حقل :attribute يجب أن يحتوي على :value عنصر أو أكثر.',
            'file' => 'حقل :attribute يجب أن يكون أكبر من أو يساوي :value كيلوبايت.',
            'numeric' => 'حقل :attribute يجب أن يكون أكبر من أو يساوي :value.',
            'string' => 'حقل :attribute يجب أن يكون أكبر من أو يساوي :value حرف.',
        ],
        'hex_color' => 'حقل :attribute يجب أن يكون لونًا سداسي عشري صالح.',
        'image' => 'حقل :attribute يجب أن يكون صورة.',
        'in' => ':attribute المحدد غير صالح.',
        'in_array' => 'حقل :attribute يجب أن يوجد في :other.',
        'in_array_keys' => 'حقل :attribute يجب أن يحتوي على مفتاح واحد على الأقل من المفاتيح التالية: :values.',
        'integer' => 'حقل :attribute يجب أن يكون عددًا صحيحًا.',
        'ip' => 'حقل :attribute يجب أن يكون عنوان IP صالح.',
        'ipv4' => 'حقل :attribute يجب أن يكون عنوان IPv4 صالح.',
        'ipv6' => 'حقل :attribute يجب أن يكون عنوان IPv6 صالح.',
        'json' => 'حقل :attribute يجب أن يكون نص JSON صالح.',
        'list' => 'حقل :attribute يجب أن يكون قائمة.',
        'lowercase' => 'حقل :attribute يجب أن يكون بأحرف صغيرة.',
        'lt' => [
            'array' => 'حقل :attribute يجب أن يحتوي على أقل من :value عنصر.',
            'file' => 'حقل :attribute يجب أن يكون أقل من :value كيلوبايت.',
            'numeric' => 'حقل :attribute يجب أن يكون أقل من :value.',
            'string' => 'حقل :attribute يجب أن يكون أقل من :value حرف.',
        ],
        'lte' => [
            'array' => 'حقل :attribute يجب ألا يحتوي على أكثر من :value عنصر.',
            'file' => 'حقل :attribute يجب أن يكون أقل من أو يساوي :value كيلوبايت.',
            'numeric' => 'حقل :attribute يجب أن يكون أقل من أو يساوي :value.',
            'string' => 'حقل :attribute يجب أن يكون أقل من أو يساوي :value حرف.',
        ],
        'mac_address' => 'حقل :attribute يجب أن يكون عنوان MAC صالح.',
        'max' => [
            'array' => 'حقل :attribute يجب ألا يحتوي على أكثر من :max عنصر.',
            'file' => 'حقل :attribute يجب ألا يكون أكبر من :max كيلوبايت.',
            'numeric' => 'حقل :attribute يجب ألا يكون أكبر من :max.',
            'string' => 'حقل :attribute يجب ألا يكون أكبر من :max حرف.',
        ],
        'max_digits' => 'حقل :attribute يجب ألا يحتوي على أكثر من :max أرقام.',
        'mimes' => 'حقل :attribute يجب أن يكون ملفًا من نوع: :values.',
        'mimetypes' => 'حقل :attribute يجب أن يكون ملفًا من نوع: :values.',
        'min' => [
            'array' => 'حقل :attribute يجب أن يحتوي على الأقل :min عنصر.',
            'file' => 'حقل :attribute يجب أن يكون على الأقل :min كيلوبايت.',
            'numeric' => 'حقل :attribute يجب أن يكون على الأقل :min.',
            'string' => 'حقل :attribute يجب أن يكون على الأقل :min حرف.',
        ],
        'min_digits' => 'حقل :attribute يجب أن يحتوي على الأقل :min أرقام.',
        'missing' => 'حقل :attribute يجب أن يكون مفقودًا.',
        'missing_if' => 'حقل :attribute يجب أن يكون مفقودًا عندما يكون :other هو :value.',
        'missing_unless' => 'حقل :attribute يجب أن يكون مفقودًا ما لم يكن :other هو :value.',
        'missing_with' => 'حقل :attribute يجب أن يكون مفقودًا عندما تكون :values موجودة.',
        'missing_with_all' => 'حقل :attribute يجب أن يكون مفقودًا عندما تكون جميع :values موجودة.',
        'multiple_of' => 'حقل :attribute يجب أن يكون مضاعفًا لـ :value.',
        'not_in' => ':attribute المحدد غير صالح.',
        'not_regex' => 'صيغة حقل :attribute غير صالحة.',
        'numeric' => 'حقل :attribute يجب أن يكون رقمًا.',
        'password' => [
            'letters' => 'حقل :attribute يجب أن يحتوي على حرف واحد على الأقل.',
            'mixed' => 'حقل :attribute يجب أن يحتوي على حرف كبير وحرف صغير على الأقل.',
            'numbers' => 'حقل :attribute يجب أن يحتوي على رقم واحد على الأقل.',
            'symbols' => 'حقل :attribute يجب أن يحتوي على رمز واحد على الأقل.',
            'uncompromised' => 'حقل :attribute ظهر في تسريب بيانات. الرجاء اختيار :attribute مختلف.',
        ],
        'present' => 'حقل :attribute يجب أن يكون حاضرًا.',
        'present_if' => 'حقل :attribute يجب أن يكون حاضرًا عندما يكون :other هو :value.',
        'present_unless' => 'حقل :attribute يجب أن يكون حاضرًا ما لم يكن :other هو :value.',
        'present_with' => 'حقل :attribute يجب أن يكون حاضرًا عندما تكون :values موجودة.',
        'present_with_all' => 'حقل :attribute يجب أن يكون حاضرًا عندما تكون جميع :values موجودة.',
        'prohibited' => 'حقل :attribute محظور.',
        'prohibited_if' => 'حقل :attribute محظور عندما يكون :other هو :value.',
        'prohibited_if_accepted' => 'حقل :attribute محظور عندما يتم قبول :other.',
        'prohibited_if_declined' => 'حقل :attribute محظور عندما يتم رفض :other.',
        'prohibited_unless' => 'حقل :attribute محظور ما لم يكن :other في :values.',
        'prohibits' => 'حقل :attribute يمنع وجود :other.',
        'regex' => 'صيغة حقل :attribute غير صالحة.',
        'required' => 'حقل :attribute مطلوب.',
        'required_array_keys' => 'حقل :attribute يجب أن يحتوي على إدخالات لـ: :values.',
        'required_if' => 'حقل :attribute مطلوب عندما يكون :other هو :value.',
        'required_if_accepted' => 'حقل :attribute مطلوب عندما يتم قبول :other.',
        'required_if_declined' => 'حقل :attribute مطلوب عندما يتم رفض :other.',
        'required_unless' => 'حقل :attribute مطلوب ما لم يكن :other في :values.',
        'required_with' => 'حقل :attribute مطلوب عندما تكون :values موجودة.',
        'required_with_all' => 'حقل :attribute مطلوب عندما تكون جميع :values موجودة.',
        'required_without' => 'حقل :attribute مطلوب عندما لا تكون :values موجودة.',
        'required_without_all' => 'حقل :attribute مطلوب عندما لا تكون أي من :values موجودة.',
        'same' => 'حقل :attribute يجب أن يتطابق مع :other.',
        'size' => [
            'array' => 'حقل :attribute يجب أن يحتوي على :size عنصر.',
            'file' => 'حقل :attribute يجب أن يكون :size كيلوبايت.',
            'numeric' => 'حقل :attribute يجب أن يكون :size.',
            'string' => 'حقل :attribute يجب أن يكون :size حرف.',
        ],
        'starts_with' => 'حقل :attribute يجب أن يبدأ بأحد القيم التالية: :values.',
        'string' => 'حقل :attribute يجب أن يكون نصًا.',
        'timezone' => 'حقل :attribute يجب أن يكون منطقة زمنية صالحة.',
        'unique' => 'حقل :attribute تم استخدامه مسبقًا.',
        'uploaded' => 'فشل تحميل حقل :attribute.',
        'uppercase' => 'حقل :attribute يجب أن يكون بأحرف كبيرة.',
        'url' => 'حقل :attribute يجب أن يكون رابطًا صالحًا.',
        'ulid' => 'حقل :attribute يجب أن يكون ULID صالح.',
        'uuid' => 'حقل :attribute يجب أن يكون UUID صالح.',

        /*
        |--------------------------------------------------------------------------
        | Custom Validation Language Lines
        |--------------------------------------------------------------------------
        |
        | Here you may specify custom validation messages for attributes using the
        | convention "attribute.rule" to name the lines. This makes it quick to
        | specify a specific custom language line for a given attribute rule.
        |
        */

        'custom' => [
            'attribute-name' => [
                'rule-name' => 'رسالة مخصصة',
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | Custom Validation Attributes
        |--------------------------------------------------------------------------
        |
        | The following language lines are used to swap our attribute placeholder
        | with something more reader friendly such as "E-Mail Address" instead
        | of "email". This simply helps us make our message more expressive.
        |
        */

        'attributes' => [
            'name' => 'الاسم',
            'code' => 'الكود',
            'is_active' => 'الحالة النشطة',

            'otp_code' => 'رمز التحقق (OTP)',
            'target_type' => 'نوع المستهدف',
            'target_id' => 'معرّف المستهدف',
            'purpose' => 'الغرض',
            'via' => 'طريقة الإرسال',
            'length' => 'طول رمز التحقق',
            'identifier' => 'معرف',
            'type' => 'نوع',
            'password' => 'كلمة المرور',

            'vendor' => [
                'name' => 'اسم البائع',
                'email' => 'البريد الإلكتروني للبائع',
                'phone' => 'هاتف البائع',
                'password' => 'كلمة مرور البائع',
                'national_id_photo' => 'صورة الهوية الوطنية',
            ],

            'rentalShop' => [
                'name' => 'اسم المتجر',
                'phone' => 'هاتف المتجر',
                'image' => 'صورة المتجر',
                'is_active' => 'حالة المتجر',
                'transport_license_photo' => 'صورة رخصة النقل',
                'commerical_registration_photo' => 'صورة السجل التجاري',

                'address' => [
                    'latitude' => 'خط العرض',
                    'longitude' => 'خط الطول',
                    'country_id' => 'الدولة',
                ],

            ],

            'day_of_week' => 'اليوم',
            'open_time' => 'وقت الفتح',
            'close_time' => 'وقت الإغلاق',
            'rental_shop_id' => 'المتجر',
        ],

    ];
