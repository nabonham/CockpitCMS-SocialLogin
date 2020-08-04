<?php

namespace SocialLogin\Controller;

class Accounts extends \Cockpit\Controller\Accounts {

    public function index() {

        if (!$this->module('cockpit')->hasaccess('cockpit', 'accounts')) {
            return $this->helper('admin')->denyRequest();
        }

        $current  = $this->user['_id'];
        $groups   = $this->module('cockpit')->getGroups();

        return $this->render('sociallogin:cockpit/views/accounts/index.php', compact('current', 'groups'));
    }

    public function find() {

        \session_write_close();

        $options = array_merge([
            'sort'   => ['user' => 1]
        ], $this->param('options', []));

        if (isset($options['filter']) && is_string($options['filter'])) {

            $filter = null;

            if (\preg_match('/^\{(.*)\}$/', $options['filter'])) {

                try {
                    $filter = json5_decode($options['filter'], true);
                } catch (\Exception $e) {}
            }

            if (!$filter) {
                $filter = [
                    '$or' => [
                        ['name' => ['$regex' => $options['filter']]],
                        ['user' => ['$regex' => $options['filter']]],
                        ['email' => ['$regex' => $options['filter']]],
                    ]
                ];
            }

            $options['filter'] = $filter;
        }

        $accounts = $this->app->storage->find('cockpit/accounts', $options)->toArray();
        $count    = (!isset($options['skip']) && !isset($options['limit'])) ? count($accounts) : $this->app->storage->count('cockpit/accounts', isset($options['filter']) ? $options['filter'] : []);
        $pages    = isset($options['limit']) ? ceil($count / $options['limit']) : 1;
        $page     = 1;

        if ($pages > 1 && isset($options['skip'])) {
            $page = ceil($options['skip'] / $options['limit']) + 1;
        }

        foreach ($accounts as &$account) {
            unset($account['password'], $account['api_key'], $account['_reset_token']);
            if ($this->app->retrieve('config/social/enabled')) {
                $account['facebook'] = $this->app->storage->count('cockpit/social', ['account_id' => $account['_id'], 'provider' => 'facebook']) ? true : false;
                $account['google'] = $this->app->storage->count('cockpit/social', ['account_id' => $account['_id'], 'provider' => 'google']) ? true : false;
            }
            $this->app->trigger('cockpit.accounts.disguise', [&$account]);
        }

        return compact('accounts', 'count', 'pages', 'page');
    }
}