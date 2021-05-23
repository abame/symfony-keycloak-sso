<?php

namespace App\Security\User;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use LightSaml\Model\Assertion\Attribute;
use LightSaml\Model\Assertion\AttributeStatement;
use LightSaml\Model\Protocol\Response;
use LightSaml\SpBundle\Security\User\UserCreatorInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\String\Slugger\AsciiSlugger;

class UserCreator implements UserCreatorInterface
{
    private EntityManagerInterface $userManager;

    public function __construct(EntityManagerInterface $userManager)
    {
        $this->userManager = $userManager;
    }

    public function createUser(Response $response): ?UserInterface
    {
        $assertion = $response->getFirstAssertion();
        $attrStmt = $assertion->getFirstAttributeStatement();

        $attributes = $attrStmt->getAllAttributes();
        $rolesArray = $this->roles($attributes);

        $user = new User();
        $this->setCommon($user, $attrStmt);

        $roles = [];
        if (!is_null($rolesArray['admin'])) {
            $roles[] = $rolesArray['admin'];
        }
        $user->setRoles($roles);

        $this->userManager->persist($user);
        $this->userManager->flush();

        return $user;
    }

    private function setCommon(UserInterface $user, AttributeStatement $attrStmt)
    {
        $attrMap = array();
        $attributes = $attrStmt->getAllAttributes();

        foreach ($attributes as $attr) {
            $attrMap[$attr->getFriendlyName()] = $attr->getFirstAttributeValue();
        }

        $mail = false;
        if (isset($attrMap['email'])) {
            $mail = $attrMap['email'];
        }
        $firstName = false;
        if (isset($attrMap['givenName'])) {
            $firstName = $attrMap['givenName'];
        }
        $lastName = false;
        if (isset($attrMap['surname'])) {
            $lastName = $attrMap['surname'];
        }

        $id = '';
        if (isset($attrMap['id'])) {
            $id = $attrMap['id'];
        }
        /** @var User $user */
        $user->setKeycloakId($id);

        $user->setEmail($mail);
        $user->setFirstName($firstName);
        $user->setLastName($lastName);
        $user->setSlug((new AsciiSlugger())->slug(sprintf('%s %s1', strtolower($firstName), strtolower($lastName))));
    }
    /**
     * set user roles
     * @param $attributes
     * @return array
     */
    private function roles($attributes): array
    {
        $roleAdminAttribute = array_values(array_filter($attributes, function ($att) {
            /**
             * @var Attribute $att
             */
            return $att->getFirstAttributeValue() === 'ROLE_ADMIN';
        }));

        $roleAdmin = null;

        if (!empty($roleAdminAttribute)) {
            /**
             * @var Attribute $adminAttribute
             */
            $adminAttribute = $roleAdminAttribute[0];
            $roleAdmin = $adminAttribute->getFirstAttributeValue();
        }

        return ['admin' => $roleAdmin];
    }
}
