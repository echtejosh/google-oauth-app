<?php

namespace App\Http\Controllers;

use App\Services\EmailService;
use Exception;
use Framework\Foundation\View;
use Framework\Http\HeaderBag;
use Framework\Http\RedirectResponse;
use Framework\Http\Request;
use Framework\Http\Response;
use Google_Client;
use Google_Service_Oauth2;

class HomeController
{
    private EmailService $email_service;

    public function __construct(EmailService $email_service)
    {
        $this->email_service = $email_service;
    }

    /**
     * Default view.
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function auth(Request $request): RedirectResponse
    {
        $email = $_SESSION['user_account']->getEmail();
        $this->email_service->send_email($email);
        return redirect('home');
    }

    /**
     * Default view.
     *
     * @param Request $request
     * @return View
     */
    public function home(Request $request): View
    {
        return view('home');
    }

    /**
     * @param Request $request
     * @return View
     */
    public function account(Request $request): View
    {
        $account = session()->get('account_info');

        return view('account')->with('account', $account);
    }

    public function logout(Request $request): RedirectResponse
    {
        // here google make access token invalid
        $access_token = $_SESSION['access_token'];


        //Unset token and user data from session
        session()->forget('access_token');
        session()->forget('userData');

        //Reset OAuth access token
        $client = new Google_Client();

        $client->revokeToken($access_token);

        //Destroy entire session
        session_destroy();

        //Redirect to homepage
        $redirect_uri = url('auth/index.php');
        header('Location: ' . filter_var($redirect_uri, FILTER_SANITIZE_URL));
        exit();
    }

}
