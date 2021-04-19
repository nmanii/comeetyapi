<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class AuthenticationController extends Controller
{
    /**
     * @Route("/token", name="token")
     */
    public function fetchToken(Request $request)
    {
        $token = $this->getToken($request);
        return new JsonResponse(['token' => $token]);
    }

    /**
     * @Route("/token-cookie", name="tokenCookie")
     */
    public function fetchTokenCookie(Request $request)
    {
        $token = $this->getToken($request);
        $response = new JsonResponse();

        $date = new \DateTime();
        $date->modify('+7 day');
        $cookie = new Cookie($this->getParameter('authentication.cookie.name'), $token, $date, '/', $this->getParameter('authentication.cookie.domain'), true, true);
        $response->headers->setCookie($cookie);

        return $response;
    }



    /**
     * @Route("/discourse-token", name="discourseToken")
     */
    public function fetchUserDiscourseToken(Request $request)
    {
        $userId = $this->getUser()->getId();

        $user = $this->getDoctrine()
            ->getRepository('AppBundle:User')
            ->findOneBy(['id' => $userId]);

        $originalDiscoursePayload = base64_decode($request->request->get('discoursePayload'));
        $data = array();
        parse_str(urldecode($originalDiscoursePayload), $data);

        if($user->getProfile() === null) {
            $data = 'profile_not_filled';
            $response = new JsonResponse($data);
            return $response;
        }

        $discoursePayload = [
            'nonce' => $data['nonce'],
            'email' => $user->getEmail(),
            'name' => $user->getProfile()->getFirstName().' '.$user->getProfile()->getLastName(),
            'username' => $user->getProfile()->getFirstName(),
            'external_id' => $user->getId(),
        ];

        $ssoKey = $this->container->getParameter('discourse_sso_key');
        $data = [
            'payload' => base64_encode(http_build_query($discoursePayload)),
            'sig' => hash_hmac('sha256', base64_encode(http_build_query($discoursePayload)), $ssoKey)];
        $response = new JsonResponse($data);

        return $response;
    }

    /**
     * @Route("/refresh-token-cookie", name="refreshTokenCookie")
     */
    public function refreshTokenCookie(Request $request)
    {
        $cookieName = $this->getParameter('authentication.cookie.name');
        $token = $request->cookies->get($cookieName);
        $response = new JsonResponse();

        $date = new \DateTime();
        $date->modify('+7 day');
        $cookie = new Cookie($cookieName, $token, $date, '/', $this->getParameter('authentication.cookie.domain'), true, true);
        $response->headers->setCookie($cookie);

        return $response;
    }

    private function getToken(Request $request)
    {
        $username = $request->request->get('username');
        $password = $request->request->get('password');

        $user = $this->getDoctrine()
            ->getRepository('AppBundle:User')
            ->findOneBy(['username' => $username]);

        if(!$user ||
            !$user->isActive() ||
            !$this->get('security.password_encoder')->isPasswordValid($user, $password)) {
            throw new UnauthorizedHttpException('comeety');
        }

        if(!$user->isConfirmed()) {
            //Account is not confirmed
            throw new AccessDeniedHttpException('account_not_confirmed');
        }

        $token = $this->get('lexik_jwt_authentication.encoder')
            ->encode(['username' => $user->getUsername(), 'id' => $user->getId()]);

        return $token;

    }

    /**
     * @Route("/discourse-logout", name="discourse_logout")
     */
    public function logoutDicourse(Request $request)
    {
        $discourseClient = $this->get('discourse.service');
        $userId = $this->getUser()->getId();
        $discourseData = $discourseClient->getUserByExternalId($userId);

        $discourseUserId = $discourseData['user']['id'];
        $discourseClient->logoutUser($discourseUserId);
    }
}
