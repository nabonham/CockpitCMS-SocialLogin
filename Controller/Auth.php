<?php

namespace SocialLogin\Controller;

use HybridAuth\HybridAuth;

class Auth extends \LimeExtra\Controller {

    public function login() {
        $redirectTo = '/';
        if ($this->param('to') && \substr($this->param('to'), 0, 1) == '/') {
            $redirectTo = $this->param('to');
        }
        return $this->render('sociallogin:cockpit/views/layouts/login.php', compact('redirectTo'));
    }

    public function authorize($provider = null) {
        if (!$this->app->retrieve("config/social/{$provider}")) {
            $this->app->stop('{"error": "Missing provider"}', 412);
        }else {
            $client_id = $this->app->retrieve("config/social/{$provider}/client_id", null);
            $client_secret = $this->app->retrieve("config/social/{$provider}/client_secret", null);

            //$token = $this->app->param('token', $this->app->request->server['HTTP_COCKPIT_TOKEN'] ?? $this->app->helper('utils')->getBearerToken());

            if (isset($client_id,$client_secret)){
                $config = [
                    //'callback' => \Hybridauth\HttpClient\Util::getCurrentUrl(),
                    'callback' => $this->app->getSiteUrl(true)."/auth/social/authorize?provider={$provider}",
                    'keys' => [ 
                        'id'     => $client_id,
                        'secret' => $client_secret 
                    ],
                ];
                $hybridAuthProvider = "Hybridauth\\Provider\\".ucfirst($provider);
                if(!class_exists($hybridAuthProvider)) {
                    $this->app->stop('{"error": "Provider not available"}', 406);
                }
                try {
                    //$dbStorage = new \SocialLogin\Storage\CockpitStorage($this->app);
                    //$adapter = new $hybridAuthProvider($config, null, $dbStorage);
                    $adapter = new $hybridAuthProvider($config);
                    $adapter->authenticate();

                    $info = [
                        'provider' => $provider,
                        'access_token' => $adapter->getAccessToken(),
                        'user_profile' => $adapter->getUserProfile()
                    ];

                    $user = $this->app->module('sociallogin')->getOrCreateUser($info);

                    if ($this->module("cockpit")->hasaccess('cockpit', 'backend', @$user['group'])) {
                        $sessionTTL = $this->app->retrieve('config/social/session_ttl', 3600);
                        @session_regenerate_id();
                        @\session_start([
                        'cookie_lifetime' => $sessionTTL,
                        ]);
                        $this->module('cockpit')->setUser($user, true);
                        $this->reroute('/');
                    }

                    return array_merge([
                        'authorized' => true
                    ], $user);
                    
                }
                catch (\Exception $e) {
                    return [
                        'error' => $e->getMessage()
                    ];
                }            
            }
            $this->app->stop('{"error": "Provider not configured"}', 406);
        }
    }
    
}
