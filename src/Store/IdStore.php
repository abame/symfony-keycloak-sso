<?php

namespace App\Store;

use App\Entity\IdEntry;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use LightSaml\Provider\TimeProvider\TimeProviderInterface;
use LightSaml\Store\Id\IdStoreInterface;

class IdStore implements IdStoreInterface
{
    private EntityManagerInterface $manager;

    private TimeProviderInterface $timeProvider;

    public function __construct(EntityManagerInterface $manager, TimeProviderInterface $timeProvider)
    {
        $this->manager = $manager;
        $this->timeProvider = $timeProvider;
    }

    public function set($entityId, $id, DateTime $expiryTime): void
    {
        $idEntry = $this->manager->find(IdEntry::class, ['entityId' => $entityId, 'id' => $id]);
        if (null == $idEntry) {
            $idEntry = new IdEntry();
        }
        $idEntry->setEntityId($entityId)
            ->setId($id)
            ->setExpiryTime($expiryTime);
        $this->manager->persist($idEntry);
        $this->manager->flush();
    }

    /**
     * check if entity has an id entry or not
     * @param string $entityId
     * @param string $id
     *
     * @return bool
     */
    public function has($entityId, $id): bool
    {
        /** @var IdEntry $idEntry */
        $idEntry = $this->manager->find(IdEntry::class, ['entityId' => $entityId, 'id' => $id]);
        if (null == $idEntry) {
            return false;
        }
        if ($idEntry->getExpiryTime()->getTimestamp() < $this->timeProvider->getTimestamp()) {
            return false;
        }

        return true;
    }
}
