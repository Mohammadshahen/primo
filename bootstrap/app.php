<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Database\QueryException;
// use Throwable;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        
        // التعامل مع خطأ Model Not Found
        $exceptions->render(function (ModelNotFoundException $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                $modelName = class_basename($e->getModel());
                $modelNames = [
                    'User' => 'المستخدم',
                    'Patient' => 'المريض',
                    'Doctor' => 'الطبيب',
                    'Appointment' => 'الموعد',
                    'Department' => 'القسم',
                    'MedicalRecord' => 'السجل الطبي',
                    'Prescription' => 'الوصفة الطبية',
                    'Invoice' => 'الفاتورة',
                    'Payment' => 'الدفعة',
                    'Service' => 'الخدمة',
                ];
                
                $translatedModel = $modelNames[$modelName] ?? $modelName;
                
                return response()->json([
                    'success' => false,
                    'message' => "{$translatedModel} غير موجود",
                    'error_code' => 'MODEL_NOT_FOUND',
                ], 404);
            }
        });

        // التعامل مع أخطاء التحقق من البيانات
        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'خطأ في البيانات المدخلة',
                    'error_code' => 'VALIDATION_ERROR',
                    'errors' => $e->errors(),
                ], 422);
            }
        });

        // التعامل مع خطأ عدم المصادقة
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'يجب تسجيل الدخول للوصول إلى هذا المورد',
                    'error_code' => 'UNAUTHENTICATED',
                ], 401);
            }
        });

        // التعامل مع خطأ عدم الصلاحية
        $exceptions->render(function (AuthorizationException $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'ليس لديك صلاحية للقيام بهذا الإجراء',
                    'error_code' => 'FORBIDDEN',
                ], 403);
            }
        });

        // التعامل مع خطأ Route Not Found
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'المسار المطلوب غير موجود',
                    'error_code' => 'ROUTE_NOT_FOUND',
                ], 404);
            }
        });

        // التعامل مع خطأ Method Not Allowed
        $exceptions->render(function (MethodNotAllowedHttpException $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'طريقة الطلب غير مسموحة',
                    'error_code' => 'METHOD_NOT_ALLOWED',
                    'allowed_methods' => $e->getHeaders()['Allow'] ?? null,
                ], 405);
            }
        });

        // التعامل مع أخطاء قاعدة البيانات
        $exceptions->render(function (QueryException $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                $errorCode = $e->errorInfo[1] ?? null;
                
                // Duplicate entry
                if ($errorCode == 1062) {
                    return response()->json([
                        'success' => false,
                        'message' => 'هذا العنصر موجود مسبقاً',
                        'error_code' => 'DUPLICATE_ENTRY',
                    ], 409);
                }
                
                // Foreign key constraint
                if ($errorCode == 1451) {
                    return response()->json([
                        'success' => false,
                        'message' => 'لا يمكن حذف هذا العنصر لارتباطه ببيانات أخرى',
                        'error_code' => 'FOREIGN_KEY_CONSTRAINT',
                    ], 409);
                }

                // Foreign key constraint - cannot add or update
                if ($errorCode == 1452) {
                    return response()->json([
                        'success' => false,
                        'message' => 'البيانات المرتبطة غير موجودة',
                        'error_code' => 'FOREIGN_KEY_VIOLATION',
                    ], 422);
                }
                
                // في بيئة الإنتاج، لا نظهر تفاصيل الخطأ
                if (app()->environment('production')) {
                    return response()->json([
                        'success' => false,
                        'message' => 'حدث خطأ في قاعدة البيانات',
                        'error_code' => 'DATABASE_ERROR',
                    ], 500);
                }
                
                return response()->json([
                    'success' => false,
                    'message' => 'حدث خطأ في قاعدة البيانات',
                    'error_code' => 'DATABASE_ERROR',
                    'debug' => [
                        'sql_error' => $e->getMessage(),
                        'sql_code' => $errorCode,
                    ],
                ], 500);
            }
        });

        // التعامل مع أخطاء HTTP العامة
        $exceptions->render(function (HttpException $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                $messages = [
                    400 => 'طلب غير صالح',
                    401 => 'غير مصرح لك بالوصول',
                    403 => 'ممنوع الوصول',
                    404 => 'غير موجود',
                    405 => 'طريقة غير مسموحة',
                    408 => 'انتهت مهلة الطلب',
                    409 => 'تعارض في البيانات',
                    422 => 'بيانات غير قابلة للمعالجة',
                    429 => 'طلبات كثيرة جداً، يرجى المحاولة لاحقاً',
                    500 => 'خطأ في الخادم الداخلي',
                    502 => 'بوابة غير صالحة',
                    503 => 'الخدمة غير متاحة حالياً',
                    504 => 'انتهت مهلة البوابة',
                ];
                
                $statusCode = $e->getStatusCode();
                $message = $e->getMessage() ?: ($messages[$statusCode] ?? 'حدث خطأ غير متوقع');
                
                return response()->json([
                    'success' => false,
                    'message' => $message,
                    'error_code' => 'HTTP_ERROR',
                ], $statusCode);
            }
        });

        // التعامل مع الأخطاء العامة غير المتوقعة
        $exceptions->render(function (Throwable $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                // في بيئة الإنتاج
                if (app()->environment('production')) {
                    return response()->json([
                        'success' => false,
                        'message' => 'حدث خطأ غير متوقع، يرجى المحاولة لاحقاً',
                        'error_code' => 'SERVER_ERROR',
                    ], 500);
                }
                
                // في بيئة التطوير، نظهر تفاصيل الخطأ
                return response()->json([
                    'success' => false,
                    'message' => 'حدث خطأ غير متوقع',
                    'error_code' => 'SERVER_ERROR',
                    'debug' => [
                        'exception' => get_class($e),
                        'message' => $e->getMessage(),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'trace' => collect($e->getTrace())->take(5)->toArray(),
                    ],
                ], 500);
            }
        });

    })->create();
