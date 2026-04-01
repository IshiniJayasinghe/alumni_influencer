<?php

namespace App\Controllers;

use Config\Database;

class ProfileController extends BaseController
{
    public function index()
    {
        $db     = Database::connect();
        $userId = session()->get('user_id');

        if (!$userId) {
            return redirect()->to('/login');
        }

        $user           = $db->table('users')->where('id', $userId)->get()->getRowArray();
        $certifications = $db->table('certifications')->where('user_id', $userId)->get()->getResultArray();
        $licences       = $db->table('professional_licences')->where('user_id', $userId)->get()->getResultArray();
        $degrees        = $db->table('degrees')->where('user_id', $userId)->get()->getResultArray();
        $shortCourses   = $db->table('short_courses')->where('user_id', $userId)->get()->getResultArray();

        return view('profile/index', [
            'user'           => $user,
            'certifications' => array_merge($certifications, $licences),
            'qualifications' => array_merge($degrees, $shortCourses),
        ]);
    }

    public function manage()
    {
        $db     = Database::connect();
        $userId = session()->get('user_id');

        if (!$userId) {
            return redirect()->to('/login');
        }

        return view('profile/manage', [
            'user'        => $db->table('users')->where('id', $userId)->get()->getRowArray(),
            'certifications' => $db->table('certifications')->where('user_id', $userId)->get()->getResultArray(),
            'licences'    => $db->table('professional_licences')->where('user_id', $userId)->get()->getResultArray(),
            'degrees'     => $db->table('degrees')->where('user_id', $userId)->get()->getResultArray(),
            'courses'     => $db->table('short_courses')->where('user_id', $userId)->get()->getResultArray(),
            'employment'  => $db->table('employment_history')->where('user_id', $userId)->get()->getResultArray(),
        ]);
    }

    // -------------------------------------------------------------------------
    // Profile update (bio, LinkedIn, image)
    // -------------------------------------------------------------------------

    public function update()
    {
        $db     = Database::connect();
        $userId = session()->get('user_id');

        if (!$userId) {
            return redirect()->to('/login');
        }

        $linkedinUrl = trim((string) $this->request->getPost('linkedin_url'));

        // Validate LinkedIn URL if provided
        if ($linkedinUrl !== '' && !$this->isValidUrl($linkedinUrl)) {
            return redirect()->back()->with('error', 'LinkedIn URL is not a valid URL.');
        }

        $data = [
            'name'          => trim((string) $this->request->getPost('name')),
            'job_title_now' => trim((string) $this->request->getPost('job_title_now')),
            'bio'           => trim((string) $this->request->getPost('bio')),
            'linkedin_url'  => $linkedinUrl !== '' ? $linkedinUrl : null,
            'updated_at'    => date('Y-m-d H:i:s'),
        ];

        $file = $this->request->getFile('profile_image');
        if ($file && $file->isValid() && !$file->hasMoved()) {
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
            $extension         = strtolower($file->getExtension());

            if (in_array($extension, $allowedExtensions, true)) {
                $newName    = $file->getRandomName();
                $uploadPath = FCPATH . 'uploads/profile_images/';

                if (!is_dir($uploadPath)) {
                    mkdir($uploadPath, 0755, true);
                }

                $oldUser = $db->table('users')->where('id', $userId)->get()->getRowArray();
                $file->move($uploadPath, $newName);
                $data['profile_image'] = $newName;

                if (!empty($oldUser['profile_image'])) {
                    $oldPath = $uploadPath . $oldUser['profile_image'];
                    if (is_file($oldPath)) {
                        @unlink($oldPath);
                    }
                }
            } else {
                return redirect()->back()->with('error', 'Profile image must be JPG, JPEG, PNG or WEBP.');
            }
        }

        $db->table('users')->where('id', $userId)->update($data);

        return redirect()->to('/profile/manage')->with('success', 'Profile updated successfully.');
    }

    public function removeProfileImage()
    {
        $db     = Database::connect();
        $userId = session()->get('user_id');

        if (!$userId) {
            return redirect()->to('/login');
        }

        $user = $db->table('users')->where('id', $userId)->get()->getRowArray();

        if (!empty($user['profile_image'])) {
            $path = FCPATH . 'uploads/profile_images/' . $user['profile_image'];
            if (is_file($path)) {
                @unlink($path);
            }
        }

        $db->table('users')->where('id', $userId)->update([
            'profile_image' => null,
            'updated_at'    => date('Y-m-d H:i:s'),
        ]);

        return redirect()->to('/profile/manage')->with('success', 'Profile picture removed.');
    }

    // -------------------------------------------------------------------------
    // Certifications
    // -------------------------------------------------------------------------

    public function addCertification()
    {
        $db     = Database::connect();
        $userId = session()->get('user_id');

        $certificationName = trim((string) $this->request->getPost('certification_name'));
        $organisationName  = trim((string) $this->request->getPost('organisation_name'));
        $courseUrl         = trim((string) $this->request->getPost('course_url'));
        $completionDate    = trim((string) $this->request->getPost('completion_date'));

        if ($certificationName === '') {
            return redirect()->to('/profile/manage')->with('error', 'Certification name is required.');
        }

        if ($courseUrl !== '' && !$this->isValidUrl($courseUrl)) {
            return redirect()->to('/profile/manage')->with('error', 'Course URL is not a valid URL.');
        }

        $db->table('certifications')->insert([
            'user_id'            => $userId,
            'certification_name' => $certificationName,
            'organisation_name'  => $organisationName,
            'course_url'         => $courseUrl !== '' ? $courseUrl : null,
            'completion_date'    => $completionDate !== '' ? $completionDate : null,
        ]);

        return redirect()->to('/profile/manage')->with('success', 'Certification added.');
    }

    public function editCertification($id)
    {
        $db     = Database::connect();
        $userId = session()->get('user_id');

        $item = $db->table('certifications')->where('id', (int) $id)->where('user_id', $userId)->get()->getRowArray();
        if (!$item) {
            return redirect()->to('/profile/manage')->with('error', 'Certification not found.');
        }

        $certificationName = trim((string) $this->request->getPost('certification_name'));
        $organisationName  = trim((string) $this->request->getPost('organisation_name'));
        $courseUrl         = trim((string) $this->request->getPost('course_url'));
        $completionDate    = trim((string) $this->request->getPost('completion_date'));

        if ($certificationName === '') {
            return redirect()->to('/profile/manage')->with('error', 'Certification name is required.');
        }

        if ($courseUrl !== '' && !$this->isValidUrl($courseUrl)) {
            return redirect()->to('/profile/manage')->with('error', 'Course URL is not a valid URL.');
        }

        $db->table('certifications')->where('id', (int) $id)->where('user_id', $userId)->update([
            'certification_name' => $certificationName,
            'organisation_name'  => $organisationName,
            'course_url'         => $courseUrl !== '' ? $courseUrl : null,
            'completion_date'    => $completionDate !== '' ? $completionDate : null,
        ]);

        return redirect()->to('/profile/manage')->with('success', 'Certification updated.');
    }

    public function deleteCertification($id)
    {
        $db     = Database::connect();
        $userId = session()->get('user_id');

        $db->table('certifications')->where('id', (int) $id)->where('user_id', $userId)->delete();

        return redirect()->to('/profile/manage')->with('success', 'Certification deleted.');
    }

    // -------------------------------------------------------------------------
    // Professional Licences
    // -------------------------------------------------------------------------

    public function addLicence()
    {
        $db     = Database::connect();
        $userId = session()->get('user_id');

        $licenceName   = trim((string) $this->request->getPost('licence_name'));
        $awardingBody  = trim((string) $this->request->getPost('awarding_body'));
        $officialUrl   = trim((string) $this->request->getPost('official_url'));
        $completionDate = trim((string) $this->request->getPost('completion_date'));

        if ($licenceName === '') {
            return redirect()->to('/profile/manage')->with('error', 'Licence name is required.');
        }

        if ($officialUrl !== '' && !$this->isValidUrl($officialUrl)) {
            return redirect()->to('/profile/manage')->with('error', 'Official URL is not a valid URL.');
        }

        $db->table('professional_licences')->insert([
            'user_id'         => $userId,
            'licence_name'    => $licenceName,
            'awarding_body'   => $awardingBody,
            'official_url'    => $officialUrl !== '' ? $officialUrl : null,
            'completion_date' => $completionDate !== '' ? $completionDate : null,
        ]);

        return redirect()->to('/profile/manage')->with('success', 'Licence added.');
    }

    public function editLicence($id)
    {
        $db     = Database::connect();
        $userId = session()->get('user_id');

        $item = $db->table('professional_licences')->where('id', (int) $id)->where('user_id', $userId)->get()->getRowArray();
        if (!$item) {
            return redirect()->to('/profile/manage')->with('error', 'Licence not found.');
        }

        $licenceName    = trim((string) $this->request->getPost('licence_name'));
        $awardingBody   = trim((string) $this->request->getPost('awarding_body'));
        $officialUrl    = trim((string) $this->request->getPost('official_url'));
        $completionDate = trim((string) $this->request->getPost('completion_date'));

        if ($licenceName === '') {
            return redirect()->to('/profile/manage')->with('error', 'Licence name is required.');
        }

        if ($officialUrl !== '' && !$this->isValidUrl($officialUrl)) {
            return redirect()->to('/profile/manage')->with('error', 'Official URL is not a valid URL.');
        }

        $db->table('professional_licences')->where('id', (int) $id)->where('user_id', $userId)->update([
            'licence_name'    => $licenceName,
            'awarding_body'   => $awardingBody,
            'official_url'    => $officialUrl !== '' ? $officialUrl : null,
            'completion_date' => $completionDate !== '' ? $completionDate : null,
        ]);

        return redirect()->to('/profile/manage')->with('success', 'Licence updated.');
    }

    public function deleteLicence($id)
    {
        $db     = Database::connect();
        $userId = session()->get('user_id');

        $db->table('professional_licences')->where('id', (int) $id)->where('user_id', $userId)->delete();

        return redirect()->to('/profile/manage')->with('success', 'Licence deleted.');
    }

    // -------------------------------------------------------------------------
    // Degrees
    // -------------------------------------------------------------------------

    public function addDegree()
    {
        $db     = Database::connect();
        $userId = session()->get('user_id');

        $degreeName      = trim((string) $this->request->getPost('degree_name'));
        $institutionName = trim((string) $this->request->getPost('institution_name'));
        $officialUrl     = trim((string) $this->request->getPost('official_url'));
        $completionDate  = trim((string) $this->request->getPost('completion_date'));

        if ($degreeName === '') {
            return redirect()->to('/profile/manage')->with('error', 'Degree name is required.');
        }

        if ($officialUrl !== '' && !$this->isValidUrl($officialUrl)) {
            return redirect()->to('/profile/manage')->with('error', 'Official URL is not a valid URL.');
        }

        $db->table('degrees')->insert([
            'user_id'          => $userId,
            'degree_name'      => $degreeName,
            'institution_name' => $institutionName,
            'official_url'     => $officialUrl !== '' ? $officialUrl : null,
            'completion_date'  => $completionDate !== '' ? $completionDate : null,
        ]);

        return redirect()->to('/profile/manage')->with('success', 'Degree added.');
    }

    public function editDegree($id)
    {
        $db     = Database::connect();
        $userId = session()->get('user_id');

        $item = $db->table('degrees')->where('id', (int) $id)->where('user_id', $userId)->get()->getRowArray();
        if (!$item) {
            return redirect()->to('/profile/manage')->with('error', 'Degree not found.');
        }

        $degreeName      = trim((string) $this->request->getPost('degree_name'));
        $institutionName = trim((string) $this->request->getPost('institution_name'));
        $officialUrl     = trim((string) $this->request->getPost('official_url'));
        $completionDate  = trim((string) $this->request->getPost('completion_date'));

        if ($degreeName === '') {
            return redirect()->to('/profile/manage')->with('error', 'Degree name is required.');
        }

        if ($officialUrl !== '' && !$this->isValidUrl($officialUrl)) {
            return redirect()->to('/profile/manage')->with('error', 'Official URL is not a valid URL.');
        }

        $db->table('degrees')->where('id', (int) $id)->where('user_id', $userId)->update([
            'degree_name'      => $degreeName,
            'institution_name' => $institutionName,
            'official_url'     => $officialUrl !== '' ? $officialUrl : null,
            'completion_date'  => $completionDate !== '' ? $completionDate : null,
        ]);

        return redirect()->to('/profile/manage')->with('success', 'Degree updated.');
    }

    public function deleteDegree($id)
    {
        $db     = Database::connect();
        $userId = session()->get('user_id');

        $db->table('degrees')->where('id', (int) $id)->where('user_id', $userId)->delete();

        return redirect()->to('/profile/manage')->with('success', 'Degree deleted.');
    }

    // -------------------------------------------------------------------------
    // Short Courses
    // -------------------------------------------------------------------------

    public function addCourse()
    {
        $db     = Database::connect();
        $userId = session()->get('user_id');

        $courseName     = trim((string) $this->request->getPost('course_name'));
        $providerName   = trim((string) $this->request->getPost('provider_name'));
        $courseUrl      = trim((string) $this->request->getPost('course_url'));
        $completionDate = trim((string) $this->request->getPost('completion_date'));

        if ($courseName === '') {
            return redirect()->to('/profile/manage')->with('error', 'Course name is required.');
        }

        if ($courseUrl !== '' && !$this->isValidUrl($courseUrl)) {
            return redirect()->to('/profile/manage')->with('error', 'Course URL is not a valid URL.');
        }

        $db->table('short_courses')->insert([
            'user_id'         => $userId,
            'course_name'     => $courseName,
            'provider_name'   => $providerName,
            'course_url'      => $courseUrl !== '' ? $courseUrl : null,
            'completion_date' => $completionDate !== '' ? $completionDate : null,
        ]);

        return redirect()->to('/profile/manage')->with('success', 'Short course added.');
    }

    public function editCourse($id)
    {
        $db     = Database::connect();
        $userId = session()->get('user_id');

        $item = $db->table('short_courses')->where('id', (int) $id)->where('user_id', $userId)->get()->getRowArray();
        if (!$item) {
            return redirect()->to('/profile/manage')->with('error', 'Course not found.');
        }

        $courseName     = trim((string) $this->request->getPost('course_name'));
        $providerName   = trim((string) $this->request->getPost('provider_name'));
        $courseUrl      = trim((string) $this->request->getPost('course_url'));
        $completionDate = trim((string) $this->request->getPost('completion_date'));

        if ($courseName === '') {
            return redirect()->to('/profile/manage')->with('error', 'Course name is required.');
        }

        if ($courseUrl !== '' && !$this->isValidUrl($courseUrl)) {
            return redirect()->to('/profile/manage')->with('error', 'Course URL is not a valid URL.');
        }

        $db->table('short_courses')->where('id', (int) $id)->where('user_id', $userId)->update([
            'course_name'     => $courseName,
            'provider_name'   => $providerName,
            'course_url'      => $courseUrl !== '' ? $courseUrl : null,
            'completion_date' => $completionDate !== '' ? $completionDate : null,
        ]);

        return redirect()->to('/profile/manage')->with('success', 'Course updated.');
    }

    public function deleteCourse($id)
    {
        $db     = Database::connect();
        $userId = session()->get('user_id');

        $db->table('short_courses')->where('id', (int) $id)->where('user_id', $userId)->delete();

        return redirect()->to('/profile/manage')->with('success', 'Course deleted.');
    }

    // -------------------------------------------------------------------------
    // Employment History
    // -------------------------------------------------------------------------

    public function addEmployment()
    {
        $db     = Database::connect();
        $userId = session()->get('user_id');

        $companyName  = trim((string) $this->request->getPost('company_name'));
        $jobTitle     = trim((string) $this->request->getPost('job_title'));
        $startDate    = trim((string) $this->request->getPost('start_date'));
        $endDate      = trim((string) $this->request->getPost('end_date'));
        $description  = trim((string) $this->request->getPost('description'));
        $isCurrent    = $this->request->getPost('is_current') ? 1 : 0;

        if ($companyName === '' || $jobTitle === '') {
            return redirect()->to('/profile/manage')->with('error', 'Company name and job title are required.');
        }

        $db->table('employment_history')->insert([
            'user_id'      => $userId,
            'company_name' => $companyName,
            'job_title'    => $jobTitle,
            'start_date'   => $startDate !== '' ? $startDate : null,
            'end_date'     => $isCurrent ? null : ($endDate !== '' ? $endDate : null),
            'is_current'   => $isCurrent,
            'description'  => $description !== '' ? $description : null,
        ]);

        return redirect()->to('/profile/manage')->with('success', 'Employment added.');
    }

    public function editEmployment($id)
    {
        $db     = Database::connect();
        $userId = session()->get('user_id');

        $item = $db->table('employment_history')->where('id', (int) $id)->where('user_id', $userId)->get()->getRowArray();
        if (!$item) {
            return redirect()->to('/profile/manage')->with('error', 'Employment record not found.');
        }

        $companyName = trim((string) $this->request->getPost('company_name'));
        $jobTitle    = trim((string) $this->request->getPost('job_title'));
        $startDate   = trim((string) $this->request->getPost('start_date'));
        $endDate     = trim((string) $this->request->getPost('end_date'));
        $description = trim((string) $this->request->getPost('description'));
        $isCurrent   = $this->request->getPost('is_current') ? 1 : 0;

        if ($companyName === '' || $jobTitle === '') {
            return redirect()->to('/profile/manage')->with('error', 'Company name and job title are required.');
        }

        $db->table('employment_history')->where('id', (int) $id)->where('user_id', $userId)->update([
            'company_name' => $companyName,
            'job_title'    => $jobTitle,
            'start_date'   => $startDate !== '' ? $startDate : null,
            'end_date'     => $isCurrent ? null : ($endDate !== '' ? $endDate : null),
            'is_current'   => $isCurrent,
            'description'  => $description !== '' ? $description : null,
        ]);

        return redirect()->to('/profile/manage')->with('success', 'Employment updated.');
    }

    public function deleteEmployment($id)
    {
        $db     = Database::connect();
        $userId = session()->get('user_id');

        $db->table('employment_history')->where('id', (int) $id)->where('user_id', $userId)->delete();

        return redirect()->to('/profile/manage')->with('success', 'Employment deleted.');
    }
}
