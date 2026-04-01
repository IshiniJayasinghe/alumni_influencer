<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

// ── Home ─────────────────────────────────────────────────────────────────────
$routes->get('/', 'Home::index');

// ── Authentication ────────────────────────────────────────────────────────────
$routes->get('register',       'AuthController::register');
$routes->post('register',      'AuthController::registerPost');
$routes->get('verify-email',   'AuthController::verifyEmail');

$routes->get('login',          'AuthController::login');
$routes->post('login',         'AuthController::loginPost');
$routes->get('logout',         'AuthController::logout');

$routes->get('forgot-password',  'AuthController::forgotPassword');
$routes->post('forgot-password', 'AuthController::forgotPasswordPost');
$routes->get('reset-password',   'AuthController::resetPassword');
$routes->post('reset-password',  'AuthController::resetPasswordPost');

// ── Profile ───────────────────────────────────────────────────────────────────
$routes->get('profile',         'ProfileController::index');
$routes->get('profile/manage',  'ProfileController::manage');
$routes->post('profile/update', 'ProfileController::update');
$routes->get('profile/remove-image', 'ProfileController::removeProfileImage');

// Certifications
$routes->post('profile/add-certification',          'ProfileController::addCertification');
$routes->post('profile/edit-certification/(:num)',   'ProfileController::editCertification/$1');
$routes->get('profile/delete-certification/(:num)',  'ProfileController::deleteCertification/$1');

// Professional Licences
$routes->post('profile/add-licence',          'ProfileController::addLicence');
$routes->post('profile/edit-licence/(:num)',   'ProfileController::editLicence/$1');
$routes->get('profile/delete-licence/(:num)', 'ProfileController::deleteLicence/$1');

// Degrees
$routes->post('profile/add-degree',          'ProfileController::addDegree');
$routes->post('profile/edit-degree/(:num)',   'ProfileController::editDegree/$1');
$routes->get('profile/delete-degree/(:num)', 'ProfileController::deleteDegree/$1');

// Short Courses
$routes->post('profile/add-course',          'ProfileController::addCourse');
$routes->post('profile/edit-course/(:num)',   'ProfileController::editCourse/$1');
$routes->get('profile/delete-course/(:num)', 'ProfileController::deleteCourse/$1');

// Employment History
$routes->post('profile/add-employment',          'ProfileController::addEmployment');
$routes->post('profile/edit-employment/(:num)',   'ProfileController::editEmployment/$1');
$routes->get('profile/delete-employment/(:num)', 'ProfileController::deleteEmployment/$1');

// ── Bidding ───────────────────────────────────────────────────────────────────
$routes->get('bids',              'BidController::index');
$routes->post('bids/add',         'BidController::add');
$routes->get('bids/delete/(:num)', 'BidController::delete/$1');

// ── Developer Portal ──────────────────────────────────────────────────────────
$routes->get('developer',                  'DeveloperController::index');
$routes->post('developer/generate-key',    'DeveloperController::generateKey');
$routes->get('developer/revoke/(:num)',    'DeveloperController::revoke/$1');
$routes->get('developer/profile/(:num)',   'DeveloperController::profile/$1');
$routes->get('developer/profile',          'DeveloperController::profile');

// ── API Documentation (Swagger UI) ────────────────────────────────────────────
$routes->get('api-docs',     'ApiDocsController::index');
$routes->get('openapi.json', 'DeveloperController::openApiJson');

// ── Public API (bearer-token protected) ──────────────────────────────────────
$routes->get('api/featured', 'ApiController::featuredToday');

// ── Cron (secret-header protected, not publicly accessible) ──────────────────
// Called daily at 6 PM via server cron:
//   0 18 * * * curl -s -H "X-Cron-Secret: YOUR_SECRET" \
//       http://localhost/alumni_influencer_fixed/public/cron/pick-winner
$routes->get('cron/pick-winner', 'CronController::pickWinner');
