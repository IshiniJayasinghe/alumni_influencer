<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

abstract class BaseController extends Controller
{
    protected $request;
    protected $helpers = ['url', 'form'];

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        helper($this->helpers);
        session();
    }

    protected function requireLogin()
    {
        if (! session()->get('user_id')) {
            return redirect()->to(base_url('login'))->with('error', 'Please login first.');
        }

        return null;
    }

    protected function requireDeveloper()
    {
        if ($redirect = $this->requireLogin()) {
            return $redirect;
        }

        if (session()->get('role') !== 'developer') {
            return redirect()->to(base_url('/'))->with('error', 'Developer access only.');
        }

        return null;
    }

    protected function isValidUrl(?string $url): bool
    {
        if ($url === null || trim($url) === '') {
            return true;
        }

        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    protected function flashTokenLink(string $label, string $link)
    {
        return session()->setFlashdata('token_link', $label . ': ' . $link);
    }
}
