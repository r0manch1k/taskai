<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Company;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;

class CompanyService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger,
    ) {
    }

    public function getCompany(int $id): Company
    {
        $company = $this->entityManager->getRepository(Company::class)->find($id);

        if (!$company) {
            throw new InvalidArgumentException("Компании с ID {$id} не существует.");
        }

        return $company;
    }

    public function setSpaceId(Company $company, int $spaceId): bool
    {
        $company->setSpaceId($spaceId);
        $this->entityManager->flush();

        return true;
    }

    public function setBoardId(Company $company, int $boardId): bool
    {
        $company->setBoardId($boardId);
        $this->entityManager->flush();

        return true;
    }
}
