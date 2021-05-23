<?php

namespace App\Services;

use Exception;
use LightSaml\Binding\AbstractBinding;
use LightSaml\Binding\BindingFactory;
use LightSaml\Binding\BindingFactoryInterface;
use LightSaml\Build\Container\BuildContainerInterface;
use LightSaml\Context\Profile\MessageContext;
use LightSaml\Helper;
use LightSaml\Model\Assertion\Issuer;
use LightSaml\Model\Assertion\NameID;
use LightSaml\Model\Metadata\EntityDescriptor;
use LightSaml\Model\Metadata\SingleLogoutService;
use LightSaml\Model\Protocol\LogoutRequest;
use LightSaml\Model\Protocol\LogoutResponse;
use LightSaml\Model\Protocol\SamlMessage;
use LightSaml\Model\Protocol\Status;
use LightSaml\Model\Protocol\StatusCode;
use LightSaml\Model\XmlDSig\SignatureWriter;
use LightSaml\SamlConstants;
use LightSaml\State\Sso\SsoSessionState;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Http\Logout\LogoutSuccessHandlerInterface;

class SamlLogoutHandler implements LogoutSuccessHandlerInterface
{
    private RouterInterface $router;

    private BuildContainerInterface $buildContainer;

    private BindingFactoryInterface $bindingFactory;

    private string $samlEntityId;

    public function __construct(
        RouterInterface $router,
        BuildContainerInterface $buildContainer,
        BindingFactoryInterface $bindingFactory,
        string $samlEntityId
    ) {
        $this->router = $router;
        $this->buildContainer = $buildContainer;
        $this->bindingFactory = $bindingFactory;
        $this->samlEntityId = $samlEntityId;
    }

    /**
     * capture on logout success event
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     * @throws Exception
     */
    public function onLogoutSuccess(Request $request)
    {
        $own = $this->buildContainer->getOwnContainer();

        $ownEntityId    = $own->getOwnEntityDescriptorProvider()->get()->getEntityID();
        $ownCredentials = $own->getOwnCredentials();
        $ownCredential  = current($ownCredentials);

        $ownSignature = new SignatureWriter($ownCredential->getCertificate(), $ownCredential->getPrivateKey());

        $bindingFactory = new BindingFactory();
        $bindingType = $bindingFactory->detectBindingType($request);
        if (null === $bindingType) {
            // no SAML request: initiate logout
            return $this->sendLogoutRequest($ownEntityId, $ownSignature);
        }
        $messageContext = new MessageContext();
        $binding = $bindingFactory->create($bindingType);
        /* @var $binding AbstractBinding */
        $binding->receive($request, $messageContext);
        $samlRequest = $messageContext->getMessage();
        if ($samlRequest instanceof LogoutResponse) {
            // back from IdP after all other SP have been disconnected
            $status = $samlRequest->getStatus();
            $code = $status->getStatusCode() ? $status->getStatusCode()->getValue() : null;
            if ($code === SamlConstants::STATUS_PARTIAL_LOGOUT || $code === SamlConstants::STATUS_SUCCESS) {
                // OK, logout
                $session = $request->getSession();
                $session->invalidate();
                // redirect to wherever you want
                return new RedirectResponse(
                    $this->router->generate('home')
                );
            }
        } elseif ($samlRequest instanceof LogoutRequest) {
            // logout request from IdP, initiated by another SP
            $response = $this->sendLogoutResponse($samlRequest, $ownEntityId, $ownSignature);
            // clean session
            $session = $request->getSession();
            $session->invalidate();
            return $response;
        }
        throw new Exception('request not handled');
    }

    private function sendLogoutRequest(string $ownEntityId, SignatureWriter $signatureWriter)
    {
        $sessions = $this->buildContainer->getStoreContainer()->getSsoStateStore()->get()->getSsoSessions();
        if (count($sessions) === 0) {
            // No sessions active, go to somewhere else
            return new RedirectResponse(
                $this->router->generate('home')
            );
        }
        $session = $sessions[count($sessions) - 1];
        /* @var $session SsoSessionState */
        $idp = $this->buildContainer->getPartyContainer()->getIdpEntityDescriptorStore()->get(0);
        /* @var $idp EntityDescriptor */
        $slo = $idp->getFirstIdpSsoDescriptor()->getFirstSingleLogoutService();
        /* @var $slo SingleLogoutService */
        $logoutRequest = new LogoutRequest();
        $logoutRequest
            ->setIssuer(new Issuer($ownEntityId))
            ->setSignature($signatureWriter)
            ->setSessionIndex($session->getSessionIndex())
            ->setNameID(new NameID($session->getNameId(), $session->getNameIdFormat()))
            ->setDestination($slo->getLocation())
            ->setID(Helper::generateID())
            ->setIssueInstant(new \DateTime())
            /* here, the SP entity id is a container parameter, change it as you wish */
            ->setIssuer(new Issuer($this->samlEntityId));
        $context = new MessageContext();
        $context->setBindingType($slo->getBinding());
        $context->setMessage($logoutRequest);
        $binding = $this->bindingFactory->create($slo->getBinding());
        return $binding->send($context);
    }

    private function sendLogoutResponse(SamlMessage $samlRequest, string $ownEntityId, SignatureWriter $signatureWriter): Response
    {
        $idp = $this->buildContainer->getPartyContainer()->getIdpEntityDescriptorStore()->get(0);
        $slo = $idp->getFirstIdpSsoDescriptor()->getFirstSingleLogoutService();
        /* @var $slo SingleLogoutService */
        $message = new LogoutResponse();
        $message
            ->setIssuer(new Issuer($ownEntityId))
            ->setSignature($signatureWriter)
            ->setRelayState($samlRequest->getRelayState())
            ->setStatus(new Status(
                new StatusCode(SamlConstants::STATUS_SUCCESS)
            ))
            ->setDestination($slo->getLocation())
            ->setInResponseTo($samlRequest->getID())
            ->setID(Helper::generateID())
            ->setIssueInstant(new \DateTime())
            /* here, the SP entity id is a container parameter, change it as you wish */
            ->setIssuer(new Issuer($this->samlEntityId));
        $context = new MessageContext();
        $context->setBindingType($slo->getBinding());
        $context->setMessage($message);
        $binding = $this->bindingFactory->create($slo->getBinding());
        return $binding->send($context);
    }
}
