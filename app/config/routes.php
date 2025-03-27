<?php
// Định nghĩa các route cho ứng dụng
return [
    'instructor' => [
        'dashboard' => '/WebCourses/app/views/product/instructor_dashboard.php',
        'create_course' => '/WebCourses/app/views/product/create_course.php',
        'manage_lessons' => '/WebCourses/app/views/product/manage_lessons.php',
        'student_list' => '/WebCourses/app/views/product/student_list.php',
        'assignments' => '/WebCourses/app/views/product/assignments.php',
        'forum' => '/WebCourses/app/views/product/forum.php',
        'certificates' => '/WebCourses/app/views/product/certificates.php',
        'notifications' => '/WebCourses/app/views/product/notifications.php',
        'analytics' => '/WebCourses/app/views/product/analytics.php',
        'earnings' => '/WebCourses/app/views/product/earnings.php',
        'settings' => '/WebCourses/app/views/product/settings.php',
        'support' => '/WebCourses/app/views/product/support.php'
    ],
    'assets' => [
        'css' => '/WebCourses/public/css/',
        'js' => '/WebCourses/public/js/',
        'images' => '/WebCourses/public/images/',
        'uploads' => '/WebCourses/public/uploads/'
    ],
    'api' => [
        'base' => '/WebCourses/api/'
    ]
]; 