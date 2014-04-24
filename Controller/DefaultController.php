<?php

namespace Nethesis\Service\SmsBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;

class DefaultController extends Controller
{
    public function sendAction()
    {
        $request = $this->getRequest();

        $operator = $request->request->get('operator');
        $login = $request->request->get('login');
        $pass = $request->request->get('pass');
        $user = $request->request->get('user', null);
        $cell = $request->request->get('cell', null);
        $template = $request->request->get('template');
        $caller = $request->request->get('caller');
        $portech_ip = $request->request->get('portech_ip', null);
        $custom_url = $request->request->get('custom_url', null);

        return new JsonResponse('');
    }

}
