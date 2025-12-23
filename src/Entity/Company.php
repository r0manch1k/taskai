<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\CompanyRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CompanyRepository::class)]
class Company
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $domain = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $token = null;

    #[ORM\Column(nullable: true)]
    private ?int $spaceId = null;

    #[ORM\ManyToOne(inversedBy: 'companies')]
    #[ORM\JoinColumn(nullable: false)]
    private ?BotUser $botUser = null;

    #[ORM\Column(nullable: true)]
    private ?int $boardId = null;

    #[ORM\Column(nullable: true)]
    private ?int $userId = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDomain(): ?string
    {
        return $this->domain;
    }

    public function setDomain(string $domain): static
    {
        $this->domain = $domain;

        return $this;
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function setToken(?string $token): static
    {
        $this->token = $token;

        return $this;
    }

    public function getSpaceId(): ?int
    {
        return $this->spaceId;
    }

    public function setSpaceId(?int $spaceId): static
    {
        $this->spaceId = $spaceId;

        return $this;
    }

    public function getBotUser(): ?BotUser
    {
        return $this->botUser;
    }

    public function setBotUser(?BotUser $botUser): static
    {
        $this->botUser = $botUser;

        return $this;
    }

    public function getBoardId(): ?int
    {
        return $this->boardId;
    }

    public function setBoardId(?int $boardId): static
    {
        $this->boardId = $boardId;

        return $this;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function setUserId(?int $userId): static
    {
        $this->userId = $userId;

        return $this;
    }
}
