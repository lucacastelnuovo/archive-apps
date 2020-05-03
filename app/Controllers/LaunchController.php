<?php

namespace App\Controllers;

use DB;
use App\Helpers\SessionHelper;
use App\Helpers\LicenseHelper;
use App\Helpers\JWTHelper;
use Zend\Diactoros\ServerRequest;

class LaunchController extends Controller
{
    /**
     * Launch access to app
     *
     * @param ServerRequest $request
     * @param string $id
     * 
     * @return HtmlResponse|RedirectResponse
     */
    public function launch(ServerRequest $request, $id)
    {
        $app = DB::get('apps', ['gumroad_id', 'url'], ['id' => $id]);

        if (!$app) {
            return $this->respond(
                'launch.twig',
                ['error' => 'App not found']
            );
        }

        $license = DB::get('licenses', 'license', [
            'app_id' => $id,
            'user_id' => SessionHelper::get('id')
        ]);

        if (!$license) {
            return $this->respond(
                'launch.twig',
                ['error' => 'License not found']
            );
        }

        if (!LicenseHelper::validate($app['gumroad_id'], $license)) {
            return $this->respond(
                'launch.twig',
                ['error' => 'License invalid']
            );
        }

        // TODO: get tiers
        $jwt = JWTHelper::create('auth', [
            'sub' => SessionHelper::get('id')
        ]);

        DB::create(
            'history',
            [
                'app_id' => $id,
                'user_id' => SessionHelper::get('id'),
                'user_agent' => $request->getHeader('user-agent')[0],
                'user_ip' => $_SERVER['REMOTE_ADDR'],
            ]
        );

        return $this->redirect("{$app['url']}?code={$jwt}");
    }
}
