<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\BotUserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BotUserRepository::class)]
class BotUser
{
    #[ORM\Id]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * @var Collection<int, Company>
     */
    #[ORM\OneToMany(targetEntity: Company::class, mappedBy: 'botUser', orphanRemoval: true)]
    private Collection $companies;

    #[ORM\Column(nullable: true)]
    private ?int $companyId = null;

    public function __construct(int $id)
    {
        $this->id = $id;
        $this->companies = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Collection<int, Company>
     */
    public function getCompanies(): Collection
    {
        return $this->companies;
    }

    public function isCompanyExists(string $domain): ?Company
    {
        foreach ($this->getCompanies() as $company) {
            if ($company->getDomain() === $domain) {
                return $company;
            }
        }

        return null;
    }

    public function addCompany(Company $company): static
    {
        if (!$this->companies->contains($company)) {
            $this->companies->add($company);
            $company->setBotUser($this);
        }

        return $this;
    }

    public function removeCompany(Company $company): static
    {
        if ($this->companies->removeElement($company)) {
            // set the owning side to null (unless already changed)
            if ($company->getBotUser() === $this) {
                $company->setBotUser(null);
            }
        }

        return $this;
    }

    public function getCompanyId(): ?int
    {
        return $this->companyId;
    }

    public function setCompanyId(?int $companyId): static
    {
        $this->companyId = $companyId;

        return $this;
    }
}
