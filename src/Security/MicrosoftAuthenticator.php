<?php


namespace App\Security;


use App\Repository\UserRepository;
use App\Security\Exception\NotVerifiedEmailException;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\Authenticator\SocialAuthenticator;
use League\OAuth2\Client\Provider\GithubResourceOwner;
use League\OAuth2\Client\Provider\Google;
use League\OAuth2\Client\Provider\GoogleUser;
use Stevenmaguire\OAuth2\Client\Provider\MicrosoftResourceOwner;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class MicrosoftAuthenticator extends SocialAuthenticator
{
    use TargetPathTrait;

    private  $router;
    private  $clientRegistry;
    private  $userRepository;

    public function __construct (RouterInterface $router, ClientRegistry $clientRegistry, UserRepository $userRepository) {

        $this->router = $router;
        $this->clientRegistry = $clientRegistry;
        $this->userRepository = $userRepository;
    }

    public function start(Request $request, AuthenticationException $authException = null)
    {
        return new RedirectResponse($this->router->generate('app_login'));
    }

    public function supports(Request $request)
    {
        return 'oauth_check' === $request->attributes->get('_route') && $request->get('service') === 'microsoft';
    }

    public function getCredentials(Request $request)
    {
        return $this->fetchAccessToken($this->getClient());
    }


    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        /** @var MicrosoftResourceOwner $microsoftUser */
        $microsoftUser = $this->getClient()->fetchUserFromToken($credentials);

        if ($microsoftUser->getEmail() === null) {
            throw new NotVerifiedEmailException();
        }

        $user = $this->userRepository->findOrCreateFromMicrosoftOauth($microsoftUser);

        return $user;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        if ($request->hasSession()) {
            $request->getSession()->set(Security::AUTHENTICATION_ERROR, $exception);
        }

        return new RedirectResponse($this->router->generate('app_login'));
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $providerKey)
    {
        $targetPath = $this->getTargetPath($request->getSession(), $providerKey);

        return new RedirectResponse($targetPath ?: '/');
    }

    private function getClient (): \KnpU\OAuth2ClientBundle\Client\OAuth2ClientInterface
    {
        return $this->clientRegistry->getClient('microsoft');
    }
}