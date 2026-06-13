<?php

use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| File này cấu hình Pest để mọi test trong tests/Feature và tests/Unit
| đều kế thừa Tests\TestCase (chuẩn Laravel). Thiếu file này sẽ gây lỗi
| "Did you forget to use the pest()->extend() function?" khi chạy các
| test viết theo cú pháp Pest (it(), uses(), expect()...).
|
*/

uses(TestCase::class)->in('Feature', 'Unit');
