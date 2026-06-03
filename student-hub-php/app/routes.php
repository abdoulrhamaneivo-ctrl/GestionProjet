<?php
/**
 * EMSP Assignment Manager - MVC Route Registry
 */

declare(strict_types=1);

/** @var \App\Core\Router $router */

// Auth routes
$router->get('/', [App\Controllers\CommunityController::class, 'index']);
$router->get('/auth/login', [App\Controllers\AuthController::class, 'showLogin']);
$router->post('/auth/login', [App\Controllers\AuthController::class, 'processLogin']);
$router->get('/auth/forgot-password', [App\Controllers\AuthController::class, 'showForgotPassword']);
$router->post('/auth/forgot-password', [App\Controllers\AuthController::class, 'processForgotPassword']);
$router->get('/auth/change-password', [App\Controllers\AuthController::class, 'showChangePassword']);
$router->post('/auth/change-password', [App\Controllers\AuthController::class, 'processChangePassword']);
$router->get('/auth/logout', [App\Controllers\AuthController::class, 'processLogout']);

// Public community routes
$router->get('/community', [App\Controllers\CommunityController::class, 'index']);
$router->get('/community/project', [App\Controllers\CommunityController::class, 'show']);
$router->post('/community/comment', [App\Controllers\CommunityController::class, 'comment']);
$router->post('/community/reply', [App\Controllers\CommunityController::class, 'reply']);

// Notifications
$router->get('/notifications', [App\Controllers\NotificationController::class, 'index']);
$router->post('/notifications/read-all', [App\Controllers\NotificationController::class, 'markAllRead']);

// Student routes
$router->get('/student/dashboard', [App\Controllers\StudentController::class, 'dashboard']);
$router->get('/student/submit', [App\Controllers\StudentController::class, 'showSubmitForm']);
$router->post('/student/submit', [App\Controllers\StudentController::class, 'processSubmit']);
$router->get('/student/peer-gallery', [App\Controllers\StudentController::class, 'peerGallery']);
$router->post('/student/peer-gallery/comment', [App\Controllers\StudentController::class, 'addPeerComment']);
$router->get('/student/report-pdf', [App\Controllers\StudentController::class, 'downloadReportPdf']);
$router->get('/student/project/report-pdf', [App\Controllers\StudentController::class, 'downloadProjectPdf']);

// Professor routes
$router->get('/prof/dashboard', [App\Controllers\ProfController::class, 'dashboard']);
$router->get('/prof/grade', [App\Controllers\ProfController::class, 'showGradeForm']);
$router->post('/prof/grade', [App\Controllers\ProfController::class, 'processGrade']);
$router->post('/prof/publish-notes', [App\Controllers\ProfController::class, 'publishNotes']);
$router->post('/prof/publish-prompt/dismiss', [App\Controllers\ProfController::class, 'dismissPublishPrompt']);
$router->post('/prof/password-reset/resolve', [App\Controllers\ProfController::class, 'resolvePasswordReset']);
$router->post('/prof/exercise/finish-corrections', [App\Controllers\ProfController::class, 'confirmCorrectionsFinished']);
$router->get('/prof/exercise/add-exemption', [App\Controllers\ProfController::class, 'showAddExemption']);
$router->post('/prof/exercise/add-exemption', [App\Controllers\ProfController::class, 'processAddExemption']);
$router->post('/prof/exercise/exemption/delete', [App\Controllers\ProfController::class, 'processDeleteExemption']);
$router->get('/prof/exercise/export-grades', [App\Controllers\ProfController::class, 'exportExerciseGrades']);
$router->get('/prof/exercises/export-all-grades', [App\Controllers\ProfController::class, 'exportAllExercisesGrades']);

// Admin routes
$router->get('/admin/dashboard', [App\Controllers\AdminController::class, 'dashboard']);
$router->get('/admin/exercise/create', [App\Controllers\AdminController::class, 'showCreateExercise']);
$router->post('/admin/exercise/create', [App\Controllers\AdminController::class, 'processCreateExercise']);
$router->get('/admin/exercises', [App\Controllers\AdminController::class, 'listExercises']);
$router->get('/admin/exercises/edit', [App\Controllers\AdminController::class, 'showEditExercise']);
$router->post('/admin/exercises/edit', [App\Controllers\AdminController::class, 'processUpdateExercise']);
$router->post('/admin/exercises/delete', [App\Controllers\AdminController::class, 'processDeleteExercise']);
$router->post('/admin/exercise/block', [App\Controllers\AdminController::class, 'processToggleBlock']);
$router->post('/admin/exercise/archive', [App\Controllers\AdminController::class, 'processToggleArchive']);
$router->post('/admin/reset-password/dismiss', [App\Controllers\AdminController::class, 'dismissResetPasswordResult']);
$router->post('/admin/password-reset/resolve', [App\Controllers\AdminController::class, 'resolvePasswordReset']);
$router->get('/admin/exercise/export-grades', [App\Controllers\AdminController::class, 'exportExerciseGrades']);
$router->get('/admin/exercises/export-all-grades', [App\Controllers\AdminController::class, 'exportAllExercisesGrades']);
$router->get('/admin/group/manage', [App\Controllers\AdminController::class, 'showManageGroups']);
$router->post('/admin/group/create', [App\Controllers\AdminController::class, 'processCreateGroup']);
$router->post('/admin/exemption/add', [App\Controllers\AdminController::class, 'processAddExemption']);
$router->post('/admin/exemption/delete', [App\Controllers\AdminController::class, 'processDeleteExemption']);
$router->get('/admin/students/import', [App\Controllers\AdminController::class, 'showImportStudents']);
$router->post('/admin/students/import', [App\Controllers\AdminController::class, 'processImportStudents']);
$router->get('/admin/students/import/download', [App\Controllers\AdminController::class, 'downloadImportCsv']);
$router->get('/admin/students/export', [App\Controllers\AdminController::class, 'exportAllStudentsCsv']);
$router->get('/admin/students', [App\Controllers\AdminController::class, 'listStudents']);
$router->get('/admin/students/create', [App\Controllers\AdminController::class, 'showCreateStudent']);
$router->post('/admin/students/create', [App\Controllers\AdminController::class, 'processCreateStudent']);
$router->get('/admin/students/edit', [App\Controllers\AdminController::class, 'showEditStudent']);
$router->post('/admin/students/edit', [App\Controllers\AdminController::class, 'processUpdateStudent']);
$router->post('/admin/students/delete', [App\Controllers\AdminController::class, 'processDeleteStudent']);
$router->get('/admin/professors', [App\Controllers\AdminController::class, 'listProfessors']);
$router->get('/admin/admins', [App\Controllers\AdminController::class, 'listAdmins']);
