<?php

include(__DIR__.'/vendor/autoload.php');

$this->module('sociallogin')->extend([
    'uniqueUsername' => function($username) use($app) {
        $newname = $username;
        $index = 1;
        while($count = $this->app->storage->count('cockpit/accounts', ['user' => $newname]) > 0){
            $index++;
            $newname = "{$username}{$index}";
        }
        return $newname;
    },
    'getOrCreateUser' => function($info) use($app) {
        if(!isset($info['provider'])) return null;
        if(!isset($info['access_token'])) return null;
        if(!isset($info['user_profile'])) return null;
    
        $maybeUser = $app->storage->findOne('cockpit/accounts', ['email' => $info['user_profile']->email]);
        $now = time();
        if(!$maybeUser) {       
            $userCheck = $info['user_profile']->firstName.$info['user_profile']->lastName;
            $uniqueName = $app->module('sociallogin')->uniqueUsername($userCheck);
            $userGroup = $app->retrieve('config/social/group', 'user');
     
            $user = [
                '_modified' => $now,
                '_created' => $now,
                'user'   => $uniqueName,
                'name' => $info['user_profile']->displayName,
                'email'  => $info['user_profile']->email,
                'active' => true,
                'group'  => $userGroup,
                'i18n'   => $app->helper('i18n')->locale,
                'generated' => true
            ];

          $this->app->storage->insert('cockpit/accounts', $user);
          $maybeUser = $user;
          $maybeUser['_fresh'] = true;
        }

        $maybeProvider = $app->storage->findOne('cockpit/social', ['account_id' => $maybeUser['_id'], 'provider' => $info['provider']]);
        if (!$maybeProvider) {
            $provider = [
                '_modified' => $now,
                '_created' => $now,
                'account_id' => $maybeUser['_id'],
                'provider' => $info['provider'],
                'access_token' => $info['access_token'],
                'user_profile' => $info['user_profile']
            ];

            $this->app->storage->save('cockpit/social', $provider);
            $maybeProvider = $provider;
        }else {
            $maybeProvider['_modified'] = $now;
            $maybeProvider['access_token'] = $info['access_token'];
            $maybeProvider['user_profile'] = $info['user_profile'];

            $app->storage->save('cockpit/social',$maybeProvider);
        }

        return $maybeUser;
      },
]);

if (!$app->retrieve('config/social/enabled')) {
    return;
}

$app->bindClass('SocialLogin\\Controller\\Accounts', 'accounts');

$app->on('cockpit.bootstrap', function() {
    $this->bind('/auth/social/authorize', function(){
        return $this->invoke('SocialLogin\\Controller\\Auth','authorize',[$provider => $this->param('provider')]);
    });
    $this->bind('/auth/login', function(){
        return $this->invoke('SocialLogin\\Controller\\Auth','login',[]);
    });
    $this->bind('/social', function(){
        return $this->invoke('SocialLogin\\Controller\\Auth','social');
    });
    $this->bind('/memory', function(){
        return $this->invoke('SocialLogin\\Controller\\Auth','memory');
    });
    $this->bind('/searchuser', function(){
        return $this->invoke('SocialLogin\\Controller\\Auth','search',['username' => $this->param('username')]);
    });
});

/**
* since we don't have the cockpit.account.remove trigger
* we bind after the account removal endpoint to delete
* the social info of the removed account.
*/
$app->on('after', function() {
    if ($this['route'] == '/accounts/remove' && $this->response->body = '{"success":true}') {
        if ($data = $this->request->param('account', false)){
            $this->storage->remove('cockpit/social', ['account_id' => $data['_id']]);
        }
    }
});