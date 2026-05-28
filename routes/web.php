<?php

use App\Http\Controllers\Admin\AssociateController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\CertificateApplicationController;
use App\Http\Controllers\Admin\CertificateController;
use App\Http\Controllers\Admin\CertificateTypeController;
use App\Http\Controllers\Admin\ClientController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\InvoiceController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Associate\AuthController as AssociateAuthController;
use App\Http\Controllers\Associate\CertificateApplicationController as AssociateCertificateApplicationController;
use App\Http\Controllers\Associate\ClientController as AssociateClientController;
use App\Http\Controllers\Associate\DashboardController as AssociateDashboardController;
use App\Http\Controllers\CommonController;
use Illuminate\Support\Facades\Route;

Route::middleware('prevent-back')
    ->prefix('admin')
    ->group(function () {
        Route::middleware('admin-auth')->group(function () {
            Route::get('login', [AuthController::class, 'login'])->name('login');
            Route::post('verify_login', [AuthController::class, 'verifyLogin'])
                ->middleware('throttle:5,1');
            Route::post('verify_login_otp', [AuthController::class, 'verifyLoginOtp'])
                ->middleware('throttle:10,1');
        });

        Route::middleware('admin-all')->group(function () {
            Route::get('/', [DashboardController::class, 'index']);
            Route::get('dashboard', [DashboardController::class, 'index']);
            Route::get('profile', [ProfileController::class, 'index']);
            Route::post('profile/save_profile', [ProfileController::class, 'save_profile']);
            Route::post('profile/email-two-factor', [ProfileController::class, 'saveEmailTwoFactor']);
            Route::get('security', [ProfileController::class, 'security']);
            Route::post('security/save_change_password', [ProfileController::class, 'save_change_password']);
            Route::get('logout', [DashboardController::class, 'logout']);

            Route::prefix('associate')->group(function () {
                Route::get('list', [AssociateController::class, 'list'])->name('admin.associate.list');
                Route::get('add', [AssociateController::class, 'add']);
                Route::post('save', [AssociateController::class, 'save']);
                Route::get('edit/{id}', [AssociateController::class, 'edit']);
                Route::post('update/{id}', [AssociateController::class, 'update']);
                Route::get('view/{id}', [AssociateController::class, 'view']);
            });

            Route::prefix('client')->group(function () {
                Route::get('list', [ClientController::class, 'list'])->name('admin.client.list');
                Route::get('add', [ClientController::class, 'add']);
                Route::post('save', [ClientController::class, 'save']);
                Route::get('edit/{id}', [ClientController::class, 'edit']);
                Route::post('update/{id}', [ClientController::class, 'update']);
                Route::get('view/{id}', [ClientController::class, 'view']);
            });

            Route::prefix('certificate-type')->group(function () {
                Route::get('list', [CertificateTypeController::class, 'list'])->name('admin.certificate_type.list');
                Route::get('add', [CertificateTypeController::class, 'add']);
                Route::post('save', [CertificateTypeController::class, 'save']);
                Route::get('edit/{id}', [CertificateTypeController::class, 'edit']);
                Route::post('update/{id}', [CertificateTypeController::class, 'update']);
                Route::get('view/{id}', [CertificateTypeController::class, 'view']);
            });

            Route::prefix('certificate')->group(function () {
                Route::get('due-list', [CertificateController::class, 'dueList'])->name('admin.certificate.due_list');
                Route::get('due-audit-list', [CertificateController::class, 'dueAuditList'])->name('admin.certificate.due_audit_list');
                Route::get('list', [CertificateController::class, 'list'])->name('admin.certificate.list');
                Route::get('add', [CertificateController::class, 'addForm']);
                Route::post('save', [CertificateController::class, 'save']);
                Route::get('edit/{id}', [CertificateController::class, 'edit']);
                Route::post('update/{id}', [CertificateController::class, 'update']);
                Route::get('view/{id}', [CertificateController::class, 'view']);
                Route::get('upload/{id}', [CertificateController::class, 'uploadCertificate']);
                Route::post('save-image/{id}', [CertificateController::class, 'saveCertificateImage']);
                Route::get('audit', [CertificateController::class, 'auditForm']);
                Route::post('save-audit', [CertificateController::class, 'saveAudit']);
                Route::post('log-expiry-calc', [CertificateController::class, 'logClientExpiryCalc']);
            });

            Route::prefix('invoice')->group(function () {
                Route::get('list', [InvoiceController::class, 'list'])->name('admin.invoice.list');
                Route::get('add', [InvoiceController::class, 'add']);
                Route::get('clients', [InvoiceController::class, 'getClients']);
                Route::get('certificates', [InvoiceController::class, 'getCertificates']);
                Route::post('save', [InvoiceController::class, 'save']);
                Route::get('edit/{id}', [InvoiceController::class, 'edit']);
                Route::post('update/{id}', [InvoiceController::class, 'update']);
                Route::get('view/{id}', [InvoiceController::class, 'view']);
                Route::get('pdf/{id}', [InvoiceController::class, 'pdf']);
            });

            Route::prefix('payment')->group(function () {
                Route::get('list', [PaymentController::class, 'list'])->name('admin.payment.list');
                Route::get('add', [PaymentController::class, 'add']);
                Route::get('clients', [PaymentController::class, 'getClients']);
                Route::get('invoices', [PaymentController::class, 'getInvoices']);
                Route::post('save', [PaymentController::class, 'save']);
                Route::get('edit/{id}', [PaymentController::class, 'edit']);
                Route::post('update/{id}', [PaymentController::class, 'update']);
                Route::get('view/{id}', [PaymentController::class, 'view']);
            });

            Route::prefix('report')->group(function () {
                Route::get('associate', [ReportController::class, 'associate'])->name('admin.report.associate');
                Route::get('client', [ReportController::class, 'client'])->name('admin.report.client');
            });

            Route::prefix('certificate-application')->group(function () {
                Route::get('add', [CertificateApplicationController::class, 'add'])->name('admin.certificate_application.add');
                Route::get('list', [CertificateApplicationController::class, 'list'])->name('admin.certificate_application.list');
                Route::get('clients', [CertificateApplicationController::class, 'getClients']);
                Route::get('check_client', [CertificateApplicationController::class, 'checkClient']);
                Route::post('save_documents', [CertificateApplicationController::class, 'saveDocuments']);
                Route::post('resolve_old_client', [CertificateApplicationController::class, 'resolveOldClient']);
                Route::get('form', [CertificateApplicationController::class, 'applicationForm'])->name('admin.certificate_application.form');
                Route::post('save_application', [CertificateApplicationController::class, 'saveApplication']);
                Route::get('edit/{id}', [CertificateApplicationController::class, 'edit']);
                Route::post('update/{id}', [CertificateApplicationController::class, 'update']);
                Route::get('view/{id}', [CertificateApplicationController::class, 'view']);
                Route::get('pdf/{id}', [CertificateApplicationController::class, 'pdf']);
                Route::get('documents/{id}', [CertificateApplicationController::class, 'documents']);
                Route::get('upload-document/{id}', [CertificateApplicationController::class, 'uploadDocument']);
                Route::post('save-document/{id}', [CertificateApplicationController::class, 'saveDocument']);
            });

            Route::prefix('common')->group(function () {
                Route::get('states', [CommonController::class, 'getStates']);
                Route::get('clients-by-associate', [CommonController::class, 'clientsByAssociate'])
                    ->name('admin.common.clients_by_associate');
                Route::post('upload_files', [CommonController::class, 'upload_files']);
                Route::post('upload_ckeditor_image', [CommonController::class, 'uploadCkeditorImage']);
            });
        });

        Route::prefix('common')->group(function () {
            Route::post('upload_files', [CommonController::class, 'upload_files']);
            Route::post('upload_ckeditor_image', [CommonController::class, 'uploadCkeditorImage'])->name('upload.ckeditor.image');
        });
    });

Route::middleware('prevent-back')->group(function () {
    Route::middleware('associate-auth')->group(function () {
        Route::get('login', [AssociateAuthController::class, 'login'])->name('associate.login');
        Route::post('login/send-otp', [AssociateAuthController::class, 'sendOtp'])
            ->middleware('throttle:5,1');
        Route::post('login/verify-otp', [AssociateAuthController::class, 'verifyOtp'])
            ->middleware('throttle:10,1');
    });

    Route::middleware('associate-all')->group(function () {
        Route::get('dashboard', [AssociateDashboardController::class, 'index']);
        Route::prefix('client')->group(function () {
            Route::get('list', [AssociateClientController::class, 'list']);
            Route::get('add', [AssociateClientController::class, 'add']);
            Route::post('save', [AssociateClientController::class, 'save']);
            Route::get('edit/{id}', [AssociateClientController::class, 'edit']);
            Route::post('update/{id}', [AssociateClientController::class, 'update']);
            Route::get('view/{id}', [AssociateClientController::class, 'view']);
        });
        Route::prefix('common')->group(function () {
            Route::get('states', [CommonController::class, 'getStates']);
            Route::post('upload_files', [CommonController::class, 'upload_files']);
        });
        Route::prefix('certificate-application')->group(function () {
            Route::get('add', [AssociateCertificateApplicationController::class, 'add'])
                ->name('associate.certificate_application.add');
            Route::get('list', [AssociateCertificateApplicationController::class, 'list'])
                ->name('associate.certificate_application.list');
            Route::get('check_client', [AssociateCertificateApplicationController::class, 'checkClient']);
            Route::post('save_documents', [AssociateCertificateApplicationController::class, 'saveDocuments']);
            Route::post('resolve_old_client', [AssociateCertificateApplicationController::class, 'resolveOldClient']);
            Route::get('form', [AssociateCertificateApplicationController::class, 'applicationForm'])
                ->name('associate.certificate_application.form');
            Route::post('save_application', [AssociateCertificateApplicationController::class, 'saveApplication']);
            Route::get('edit/{id}', [AssociateCertificateApplicationController::class, 'edit']);
            Route::post('update/{id}', [AssociateCertificateApplicationController::class, 'update']);
            Route::get('view/{id}', [AssociateCertificateApplicationController::class, 'view']);
            Route::get('pdf/{id}', [AssociateCertificateApplicationController::class, 'pdf']);
            Route::get('documents/{id}', [AssociateCertificateApplicationController::class, 'documents']);
            Route::get('upload-document/{id}', [AssociateCertificateApplicationController::class, 'uploadDocument']);
            Route::post('save-document/{id}', [AssociateCertificateApplicationController::class, 'saveDocument']);
        });
        Route::get('logout', [AssociateAuthController::class, 'logout']);
    });
});
