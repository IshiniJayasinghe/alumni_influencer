<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

$routes->get('/', 'Home::index');

$routes->get('register', 'AuthController::register');
$routes->post('register', 'AuthController::registerPost');
$routes->get('verify-email', 'AuthController::verifyEmail');

$routes->get('login', 'AuthController::login');
$routes->post('login', 'AuthController::loginPost');
$routes->get('logout', 'AuthController::logout');

$routes->get('forgot-password', 'AuthController::forgotPassword');
$routes->post('forgot-password', 'AuthController::forgotPasswordPost');
$routes->get('reset-password', 'AuthController::resetPassword');
$routes->post('reset-password', 'AuthController::resetPasswordPost');

$routes->get('profile', 'ProfileController::index');
$routes->get('profile/manage', 'ProfileController::manage');
$routes->post('profile/update', 'ProfileController::update');
$routes->get('profile/remove-image', 'ProfileController::removeProfileImage');

$routes->post('profile/add-certification', 'ProfileController::addCertification');
$routes->post('profile/edit-certification/(:num)', 'ProfileController::editCertification/$1');
$routes->get('profile/delete-certification/(:num)', 'ProfileController::deleteCertification/$1');

$routes->post('profile/add-licence', 'ProfileController::addLicence');
$routes->post('profile/edit-licence/(:num)', 'ProfileController::editLicence/$1');
$routes->get('profile/delete-licence/(:num)', 'ProfileController::deleteLicence/$1');

$routes->post('profile/add-degree', 'ProfileController::addDegree');
$routes->post('profile/edit-degree/(:num)', 'ProfileController::editDegree/$1');
$routes->get('profile/delete-degree/(:num)', 'ProfileController::deleteDegree/$1');

$routes->post('profile/add-course', 'ProfileController::addCourse');
$routes->post('profile/edit-course/(:num)', 'ProfileController::editCourse/$1');
$routes->get('profile/delete-course/(:num)', 'ProfileController::deleteCourse/$1');

$routes->post('profile/add-employment', 'ProfileController::addEmployment');
$routes->post('profile/edit-employment/(:num)', 'ProfileController::editEmployment/$1');
$routes->get('profile/delete-employment/(:num)', 'ProfileController::deleteEmployment/$1');

$routes->get('bids', 'BidController::index');
$routes->post('bids/add', 'BidController::add');
$routes->get('bids/delete/(:num)', 'BidController::delete/$1');

$routes->get('developer', 'DeveloperController::index');
$routes->post('developer/generate-key', 'DeveloperController::generateKey');
$routes->get('developer/revoke/(:num)', 'DeveloperController::revoke/$1');
$routes->get('developer/profile/(:num)', 'DeveloperController::profile/$1');
$routes->get('developer/profile', 'DeveloperController::profile');

$routes->get('api-docs', 'ApiDocsController::index');
$routes->get('openapi.json', 'DeveloperController::openApiJson');

$routes->get('api/alumni', 'ApiController::alumni');
$routes->get('api/featured', 'ApiController::featuredToday');

// Called daily at 6 PM via server cron:
//   0 18 * * * curl -s -H "X-Cron-Secret: YOUR_SECRET" \
//       http://localhost/alumni_influencer/public/cron/pick-winner
$routes->get('cron/pick-winner', 'CronController::pickWinner');

$routes->get('dashboard', 'DashboardController::index');
$routes->get('dashboard/alumni', 'DashboardController::alumni');
$routes->post('dashboard/alumni/presets/save', 'DashboardController::savePreset');
$routes->get('dashboard/alumni/presets/apply/(:segment)', 'DashboardController::applyPreset/$1');
$routes->get('dashboard/alumni/presets/delete/(:segment)', 'DashboardController::deletePreset/$1');
$routes->get('dashboard/alumni/export/csv', 'DashboardController::exportFilteredCsv');
$routes->get('dashboard/alumni/export/pdf', 'DashboardController::exportFilteredPdf');
$routes->get('dashboard/charts', 'DashboardController::charts');
$routes->get('dashboard/export/csv', 'DashboardController::exportCsv');
$routes->get('dashboard/export/pdf', 'DashboardController::exportPdf');
$routes->match(['get', 'post'], 'dashboard/report', 'DashboardController::report');

$routes->get('api/analytics/summary', 'AnalyticsApiController::summary');
$routes->get('api/analytics/industries', 'AnalyticsApiController::industries');
$routes->get('api/analytics/employers', 'AnalyticsApiController::topEmployers');
$routes->get('api/analytics/job-titles', 'AnalyticsApiController::jobTitles');
$routes->get('api/analytics/programmes', 'AnalyticsApiController::programmes');
$routes->get('api/analytics/graduation-years', 'AnalyticsApiController::graduationYears');
$routes->get('api/analytics/certifications', 'AnalyticsApiController::certifications');
$routes->get('api/analytics/skills-gap', 'AnalyticsApiController::skillsGap');
$routes->get('api/analytics/geographic-distribution', 'AnalyticsApiController::geographicDistribution');
