<?php

namespace AppBundle\Controller;

use AppBundle\Service\GithubPRChecker;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\BrowserKit\Response;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="homepage")
     */
    public function indexAction()
    {
        return $this->render('default/index.html.twig');
    }

    /**
     * @Route("/verify", name="verify")
     */
    public function verifyUserAction(Request $request)
    {
        $user = $this->getUser();
        $key = $user->getUsername() . '_pullrequests';
        $cache = $this->get('cache');
        $pullrequests = [];

        if (!$request->query->has('force_update') or $request->query->get('force_update') == 0) {
            $pullrequests = $cache->fetch($key);
        }

        if (!$pullrequests) {
            $token = $this->getDoctrine()->getRepository('AppBundle:Credentials')->getGithubToken($user->getId());
            $github = new GithubPRChecker($token);
            $pullrequests = $github->getUserPullRequests($user->getUsername());
            $cache->save($key, $pullrequests);
        }


        return $this->render('default/verify_result.html.twig', [
           'pullrequests' => $pullrequests
        ]);
    }
}
