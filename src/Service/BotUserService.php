<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\BotUser;
use App\Entity\Company;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;

class BotUserService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger,
    ) {
    }

    public function getBotUser(int $id): BotUser
    {
        $botUser = $this->entityManager->getRepository(BotUser::class)->find($id);

        if (!$botUser) {
            $botUser = new BotUser($id);
            $this->entityManager->persist($botUser);
            $this->entityManager->flush();
        }

        return $botUser;
    }

    public function setCompany(BotUser $botUser, Company $company): Company
    {
        $exist = null;

        foreach ($botUser->getCompanies() as $c) {
            if ($c->getDomain() === $company->getDomain()) {
                $exist = $c;
                break;
            }
        }

        if (null !== $exist) {
            $exist->setToken($company->getToken());
            $exist->setDomain($company->getDomain());

            $this->entityManager->flush();

            return $exist;
        }

        $botUser->addCompany($company);
        $this->entityManager->persist($company);
        $this->entityManager->flush();

        return $company;
    }

    public function setCompanyId(BotUser $botUser, int $companyId): Company
    {
        $exist = null;

        foreach ($botUser->getCompanies() as $c) {
            if ($c->getId() === $companyId) {
                $exist = $c;
                break;
            }
        }

        if (null === $exist) {
            throw new InvalidArgumentException("Компании с ID {$companyId} не существует у этого пользователя.");
        }

        $botUser->setCompanyId($companyId);
        $this->entityManager->flush();

        return $exist;
    }
}
